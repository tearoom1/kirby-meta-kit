import test from 'node:test';
import assert from 'node:assert/strict';
import { classifyPageField, getSlugAnalysis } from '../js/composables/panelState.js';

const context = {
  siteSettings: { appendSiteName: false, siteMetaDescription: 'x'.repeat(150), siteHasOgImage: true },
  validationSettings: {}
};

const page = (props = {}) => ({
  id: 'blog/a-good-slug',
  title: 'Page',
  template: 'default',
  hasMetaTitle: true,
  hasMetaDescription: true,
  hasOgTitle: false,
  hasOgDescription: false,
  hasOgImage: false,
  ...props
});

test('title length decides between good, warning and error', () => {
  assert.equal(classifyPageField(page({ metaTitle: 'x'.repeat(40) }), 'title', context), 'good');
  assert.equal(classifyPageField(page({ metaTitle: 'x'.repeat(70) }), 'title', context), 'warning');
  assert.equal(classifyPageField(page({ metaTitle: 'x'.repeat(90) }), 'title', context), 'error');
});

test('a missing description is an error, an inherited one a warning', () => {
  const ownDescription = 'x'.repeat(150);
  assert.equal(classifyPageField(page({ metaDescription: ownDescription }), 'description', context), 'good');
  assert.equal(
    classifyPageField(page({ hasMetaDescription: false }), 'description', { ...context, siteSettings: { appendSiteName: false } }),
    'error'
  );
  assert.equal(classifyPageField(page({ hasMetaDescription: false }), 'description', context), 'warning');
});

test('og image falls back to the site image as a warning', () => {
  assert.equal(classifyPageField(page({ hasOgImage: true }), 'ogImage', context), 'good');
  assert.equal(classifyPageField(page(), 'ogImage', context), 'warning');
  assert.equal(classifyPageField(page(), 'ogImage', { ...context, siteSettings: {} }), 'error');
});

test('noindex is flagged as a warning', () => {
  assert.equal(classifyPageField(page({ robots: 'noindex, follow' }), 'noindex', context), 'warning');
  assert.equal(classifyPageField(page({ robots: 'index, follow' }), 'noindex', context), 'good');
});

test('slug analysis measures the last segment and finds issues', () => {
  const analysis = getSlugAnalysis(page({ id: 'a/b/c/very-long-slug' }), {});
  assert.equal(analysis.slug, 'very-long-slug');
  assert.equal(analysis.wordCount, 3);
  assert.equal(analysis.numSlashes, 3);
  assert.deepEqual(analysis.issues.map((issue) => [issue.key, issue.severity]), [['Depth', 'warning']]);
  assert.equal(classifyPageField(page({ id: 'a/b/c/very-long-slug' }), 'slug', context), 'warning');
});

test('the site root has no slug and is always good', () => {
  const analysis = getSlugAnalysis({ id: 'site' }, {});
  assert.equal(analysis.slug, '');
  assert.deepEqual(analysis.issues, []);
  assert.equal(classifyPageField({ id: 'site' }, 'slug', context), 'good');
});

import { findDuplicates, filterPages } from '../js/composables/panelState.js';

test('finds pages sharing their own title or description, ignoring case and spacing', () => {
  const pages = [
    page({ id: 'a', title: 'A', hasMetaTitle: true, metaTitle: 'Green Tea', hasMetaDescription: true, metaDescription: 'Unique one' }),
    page({ id: 'b', title: 'B', hasMetaTitle: true, metaTitle: ' green  tea ', hasMetaDescription: true, metaDescription: 'Shared text' }),
    page({ id: 'c', title: 'C', hasMetaTitle: true, metaTitle: 'Black Tea', hasMetaDescription: true, metaDescription: 'Shared text' }),
    // Inherited (not own) values are not compared
    page({ id: 'd', title: 'D', hasMetaTitle: false, metaTitle: 'Green Tea', hasMetaDescription: false, metaDescription: 'Shared text' })
  ];

  const duplicates = findDuplicates(pages);

  assert.deepEqual(duplicates.title, { a: ['b'], b: ['a'] });
  assert.deepEqual(duplicates.description, { b: ['c'], c: ['b'] });

  const ctx = { ...context, duplicates };
  assert.deepEqual(pages.map((p) => classifyPageField(p, 'duplicates', ctx)), ['warning', 'warning', 'warning', 'good']);
  assert.deepEqual(
    filterPages(pages, ['type-duplicates', 'warning'], '', ctx).map((p) => p.id),
    ['a', 'b', 'c']
  );
});
