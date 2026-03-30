import test from 'node:test';
import assert from 'node:assert/strict';
import { applySingleFieldUpdate } from '../js/composables/saveFields.js';

test('applySingleFieldUpdate posts the expected payload', async () => {
  const calls = [];
  const api = {
    async post(path, payload) {
      calls.push({ path, payload });
      return { status: 'success', message: 'Field updated successfully' };
    }
  };

  const response = await applySingleFieldUpdate(api, {
    pageId: 'home',
    fieldName: 'metaTitle',
    value: 'Homepage Title'
  });

  assert.equal(response.status, 'success');
  assert.deepEqual(calls, [
    {
      path: 'meta-kit/apply-single-field',
      payload: {
        pageId: 'home',
        fieldName: 'metaTitle',
        value: 'Homepage Title'
      }
    }
  ]);
});

test('applySingleFieldUpdate throws when API returns status error', async () => {
  const api = {
    async post() {
      return { status: 'error', message: 'Write failed' };
    }
  };

  await assert.rejects(
    () => applySingleFieldUpdate(api, {
      pageId: 'home',
      fieldName: 'metaTitle',
      value: 'Homepage Title'
    }),
    /Write failed/
  );
});

test('applySingleFieldUpdate throws a fallback error for malformed responses', async () => {
  const api = {
    async post() {
      return null;
    }
  };

  await assert.rejects(
    () => applySingleFieldUpdate(api, {
      pageId: 'home',
      fieldName: 'ogDescription',
      value: 'Description'
    }),
    /Failed to update ogDescription/
  );
});
