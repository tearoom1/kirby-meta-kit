import test from 'node:test';
import assert from 'node:assert/strict';
import {
  planGeneration,
  runGeneration,
  applySuggestions,
  generateFieldSuggestion
} from '../js/composables/bulkGeneration.js';

const pages = [
  { id: 'site', title: 'Site', hasMetaTitle: false, hasMetaDescription: true, hasOgTitle: false, hasOgDescription: false },
  { id: 'a', title: 'A', hasMetaTitle: true, hasMetaDescription: false, hasOgTitle: false, hasOgDescription: true },
  { id: 'b', title: 'B', hasMetaTitle: true, hasMetaDescription: true, hasOgTitle: true, hasOgDescription: true }
];

test('plans one job per missing selected field, without OG fields for the site', () => {
  const jobs = planGeneration(pages, { title: true, description: true, ogTitle: true });

  assert.deepEqual(jobs.map((job) => `${job.pageId}:${job.field}`), [
    'site:metaTitle',
    'a:metaDescription',
    'a:ogTitle'
  ]);
  assert.equal(jobs[0].label, 'Meta Title');
});

test('runs jobs in order, reports progress and collects errors', async () => {
  const jobs = planGeneration(pages, { title: true, description: true });
  const progress = [];

  const result = await runGeneration(jobs, {
    generate: async (job) => {
      if (job.pageId === 'a') throw new Error('Not enough text');
      return `text for ${job.pageId}`;
    },
    onProgress: ({ done, total }) => progress.push(`${done}/${total}`)
  });

  assert.deepEqual(progress, ['0/2', '1/2', '2/2']);
  assert.deepEqual(result.suggestions.map((s) => [s.pageId, s.value, s.selected]), [['site', 'text for site', true]]);
  assert.deepEqual(result.errors.map((e) => [e.pageId, e.message]), [['a', 'Not enough text']]);
  assert.equal(result.cancelled, false);
});

test('stops before the next job once cancelled', async () => {
  const jobs = planGeneration(pages, { title: true, description: true, ogTitle: true });
  let calls = 0;

  const result = await runGeneration(jobs, {
    generate: async () => `text ${++calls}`,
    isCancelled: () => calls >= 1
  });

  assert.equal(calls, 1);
  assert.equal(result.suggestions.length, 1);
  assert.equal(result.cancelled, true);
});

test('saves only selected, non-empty suggestions and keeps going after errors', async () => {
  const saved = [];
  const result = await applySuggestions([
    { pageId: 'a', field: 'metaTitle', value: 'Keep', selected: true },
    { pageId: 'b', field: 'metaTitle', value: 'Skip', selected: false },
    { pageId: 'c', field: 'metaTitle', value: '   ', selected: true },
    { pageId: 'd', field: 'metaTitle', value: 'Fails', selected: true },
    { pageId: 'e', field: 'metaTitle', value: 'Also kept', selected: true }
  ], async (suggestion) => {
    if (suggestion.pageId === 'd') throw new Error('Forbidden');
    saved.push(suggestion.pageId);
  });

  assert.deepEqual(saved, ['a', 'e']);
  assert.deepEqual(result.errors.map((e) => [e.pageId, e.message]), [['d', 'Forbidden']]);
});

test('generateFieldSuggestion posts without saving and unwraps the content', async () => {
  const calls = [];
  const api = {
    post: async (path, body) => {
      calls.push([path, body]);
      return body.pageId === 'x'
        ? { status: 'error', message: 'Not enough text' }
        : { status: 'success', content: 'Generated' };
    }
  };

  assert.equal(await generateFieldSuggestion(api, { pageId: 'a', field: 'metaTitle' }, 'de'), 'Generated');
  assert.deepEqual(calls[0], ['meta-kit/generate-field', { pageId: 'a', fieldName: 'metaTitle', language: 'de' }]);
  await assert.rejects(generateFieldSuggestion(api, { pageId: 'x', field: 'metaTitle' }), /Not enough text/);
});
