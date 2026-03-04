import test from 'node:test';
import assert from 'node:assert/strict';
import {
  filterPages,
  paginatePages,
  getTotalPages,
  isAllCurrentPageSelected,
  toggleSelectAllCurrentPage,
  hasPageFieldChanges,
  hasAnyBulkChanges
} from '../js/composables/panelState.js';

const pages = [
  {
    id: 'site',
    title: 'Site',
    template: 'site',
    status: 'published',
    hasMetaTitle: true,
    hasMetaDescription: true,
    hasOgTitle: false,
    hasOgDescription: false,
    hasOgImage: false,
    metaDescription: 'site description',
  },
  {
    id: 'blog/post-a',
    title: 'Post A',
    template: 'article',
    status: 'listed',
    hasMetaTitle: false,
    hasMetaDescription: true,
    hasOgTitle: false,
    hasOgDescription: false,
    hasOgImage: true,
    metaDescription: 'alpha',
  },
  {
    id: 'blog/post-b',
    title: 'Post B',
    template: 'article',
    status: 'unlisted',
    hasMetaTitle: true,
    hasMetaDescription: false,
    hasOgTitle: true,
    hasOgDescription: true,
    hasOgImage: false,
    metaDescription: '',
  },
  {
    id: 'drafts/post-c',
    title: 'Post C',
    template: 'article',
    status: 'draft',
    hasMetaTitle: true,
    hasMetaDescription: true,
    hasOgTitle: true,
    hasOgDescription: true,
    hasOgImage: true,
    metaDescription: 'gamma text',
  },
];

test('filterPages applies metadata filters with AND behavior', () => {
  const result = filterPages(pages, ['missing-title', 'missing-og-title'], '');
  assert.deepEqual(result.map((p) => p.id), ['blog/post-a']);
});

test('filterPages applies status filters with OR behavior', () => {
  const result = filterPages(pages, ['unlisted', 'drafts'], '');
  assert.deepEqual(result.map((p) => p.id), ['blog/post-b', 'drafts/post-c']);
});

test('filterPages combines metadata and status groups', () => {
  const result = filterPages(pages, ['missing-description', 'unlisted', 'drafts'], '');
  assert.deepEqual(result.map((p) => p.id), ['blog/post-b']);
});

test('filterPages applies case-insensitive search across fields', () => {
  const result = filterPages(pages, [], 'GaMmA');
  assert.deepEqual(result.map((p) => p.id), ['drafts/post-c']);
});

test('pagination helpers compute slices and total pages', () => {
  const filtered = filterPages(pages, [], '');
  assert.equal(getTotalPages(filtered, 2), 2);
  assert.deepEqual(paginatePages(filtered, 2, 2).map((p) => p.id), ['blog/post-b', 'drafts/post-c']);
  assert.equal(getTotalPages(filtered, 99999), 1);
});

test('selection helpers detect all-selected and toggle correctly', () => {
  const pageSlice = pages.slice(1, 3); // post-a, post-b
  const selected = ['blog/post-a'];
  assert.equal(isAllCurrentPageSelected(pageSlice, selected), false);

  const toggledOn = toggleSelectAllCurrentPage(pageSlice, selected);
  assert.deepEqual(new Set(toggledOn), new Set(['blog/post-a', 'blog/post-b']));
  assert.equal(isAllCurrentPageSelected(pageSlice, toggledOn), true);

  const toggledOff = toggleSelectAllCurrentPage(pageSlice, toggledOn);
  assert.deepEqual(toggledOff, []);
});

test('change detection helpers cover popup edit scenarios', () => {
  const page = {
    id: 'blog/post-a',
    metaTitle: 'Title',
    metaDescription: 'Desc',
    ogTitle: 'OG Title',
    ogDescription: 'OG Desc'
  };

  const unchanged = {
    metaTitle: 'Title',
    metaDescription: 'Desc',
    ogTitle: 'OG Title',
    ogDescription: 'OG Desc'
  };
  const changed = {
    ...unchanged,
    ogDescription: 'Updated OG Desc'
  };

  assert.equal(hasPageFieldChanges(page, unchanged), false);
  assert.equal(hasPageFieldChanges(page, changed), true);

  const editedFields = {
    'blog/post-a': unchanged,
    'blog/post-b': {
      metaTitle: '',
      metaDescription: '',
      ogTitle: '',
      ogDescription: 'changed'
    }
  };
  const bulkPages = [
    page,
    { id: 'blog/post-b', metaTitle: '', metaDescription: '', ogTitle: '', ogDescription: '' }
  ];

  assert.equal(hasAnyBulkChanges(bulkPages, editedFields), true);
});

