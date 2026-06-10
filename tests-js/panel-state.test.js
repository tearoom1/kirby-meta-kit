import test from 'node:test';
import assert from 'node:assert/strict';
import {
  filterPages,
  sortPages,
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

test('filterPages supports attention, warning and error filters', () => {
  const attentionPages = [
    {
      id: 'alpha',
      title: 'Alpha landing page',
      template: 'default',
      status: 'listed',
      hasMetaTitle: true,
      metaTitle: 'Strong landing title',
      hasMetaDescription: false,
      metaDescription: '',
      hasOgTitle: true,
      ogTitle: 'Strong OG title',
      hasOgDescription: false,
      ogDescription: '',
      hasOgImage: false,
      robots: ''
    },
    {
      id: 'beta',
      title: 'Beta landing page',
      template: 'default',
      status: 'listed',
      hasMetaTitle: true,
      metaTitle: 'Another strong title',
      hasMetaDescription: false,
      metaDescription: '',
      hasOgTitle: true,
      ogTitle: 'Another OG title',
      hasOgDescription: false,
      ogDescription: '',
      hasOgImage: true,
      robots: 'noindex, follow'
    },
    {
      id: 'gamma',
      title: 'Gamma landing page',
      template: 'default',
      status: 'listed',
      hasMetaTitle: true,
      metaTitle: 'Gamma strong title',
      hasMetaDescription: true,
      metaDescription: 'Gamma has a unique description',
      hasOgTitle: true,
      ogTitle: 'Gamma OG title',
      hasOgDescription: true,
      ogDescription: 'Gamma OG description',
      hasOgImage: true,
      robots: ''
    },
    {
      id: 'one/two/three/four/five',
      title: 'Delta landing page',
      template: 'default',
      status: 'listed',
      hasMetaTitle: true,
      metaTitle: 'Delta strong title',
      hasMetaDescription: true,
      metaDescription: 'Delta has a unique description',
      hasOgTitle: true,
      ogTitle: 'Delta OG title',
      hasOgDescription: true,
      ogDescription: 'Delta OG description',
      hasOgImage: true,
      robots: ''
    }
  ];

  const context = {
    siteSettings: {
      siteMetaDescription: 'Fallback site description',
      appendSiteName: false,
      siteHasOgImage: true
    },
    validationSettings: {
      ranges: {
        title: {
          optimal: { min: 1, max: 120 },
          warning: { min: 1, max: 140 }
        },
        ogTitle: {
          optimal: { min: 1, max: 120 },
          warning: { min: 1, max: 140 }
        },
        description: {
          optimal: { min: 1, max: 220 },
          warning: { min: 1, max: 260 }
        },
        ogDescription: {
          optimal: { min: 1, max: 220 },
          warning: { min: 1, max: 260 }
        }
      }
    }
  };

  assert.deepEqual(
    filterPages(attentionPages, ['attention'], '', context).map((p) => p.id),
    ['alpha', 'beta', 'one/two/three/four/five']
  );

  assert.deepEqual(
    filterPages(attentionPages, ['warning'], '', context).map((p) => p.id),
    ['alpha', 'beta']
  );

  assert.deepEqual(
    filterPages(attentionPages, ['error'], '', context).map((p) => p.id),
    ['one/two/three/four/five']
  );

  assert.deepEqual(
    filterPages(attentionPages, ['warning', 'type-description'], '', context).map((p) => p.id),
    ['alpha', 'beta']
  );

  assert.deepEqual(
    filterPages(attentionPages, ['warning', 'type-og-image'], '', context).map((p) => p.id),
    ['alpha']
  );

  assert.deepEqual(
    filterPages(attentionPages, ['good', 'type-description'], '', context).map((p) => p.id),
    ['gamma', 'one/two/three/four/five']
  );

  assert.deepEqual(
    filterPages(attentionPages, ['type-description'], '', context).map((p) => p.id),
    ['alpha', 'beta', 'gamma', 'one/two/three/four/five']
  );
});

test('filterPages treats main-language inherited meta title and description as warnings', () => {
  const inheritedPages = [
    {
      id: 'translated-page',
      title: 'Translated Page',
      template: 'default',
      status: 'listed',
      hasMetaTitle: false,
      metaTitle: null,
      metaTitleInheritance: { inherited: true, inheritedFrom: 'en', inheritedValue: 'English title fallback' },
      hasMetaDescription: false,
      metaDescription: null,
      metaDescriptionInheritance: {
        inherited: true,
        inheritedFrom: 'en',
        inheritedValue: 'English description fallback for the translated page that is long enough to stay comfortably inside the configured OG description warning range for this regression test.'
      },
      hasOgTitle: true,
      ogTitle: 'OG title',
      hasOgDescription: true,
      ogDescription: 'OG description',
      hasOgImage: true,
      robots: ''
    }
  ];

  const context = {
    siteSettings: {
      siteMetaDescription: 'Fallback site description',
      appendSiteName: false,
      siteHasOgImage: true
    },
    validationSettings: {
      ranges: {
        title: {
          optimal: { min: 1, max: 120 },
          warning: { min: 1, max: 140 }
        },
        description: {
          optimal: { min: 1, max: 220 },
          warning: { min: 1, max: 260 }
        }
      }
    }
  };

  assert.deepEqual(
    filterPages(inheritedPages, ['warning', 'type-title'], '', context).map((p) => p.id),
    ['translated-page']
  );

  assert.deepEqual(
    filterPages(inheritedPages, ['warning', 'type-description'], '', context).map((p) => p.id),
    ['translated-page']
  );

  const inheritedOgPages = [
    {
      ...inheritedPages[0],
      hasOgTitle: false,
      ogTitle: null,
      hasOgDescription: false,
      ogDescription: null
    }
  ];

  assert.deepEqual(
    filterPages(inheritedOgPages, ['warning', 'type-og-title'], '', context).map((p) => p.id),
    ['translated-page']
  );

  assert.deepEqual(
    filterPages(inheritedOgPages, ['warning', 'type-og-description'], '', context).map((p) => p.id),
    ['translated-page']
  );
});

test('pagination helpers compute slices and total pages', () => {
  const filtered = filterPages(pages, [], '');
  assert.equal(getTotalPages(filtered, 2), 2);
  assert.deepEqual(paginatePages(filtered, 2, 2).map((p) => p.id), ['blog/post-b', 'drafts/post-c']);
  assert.equal(getTotalPages(filtered, 99999), 1);
});

test('sortPages supports name, level, status and attention sorting', () => {
  const sortablePages = [
    {
      id: 'products',
      title: 'Products',
      template: 'default',
      status: 'listed',
      hasMetaTitle: true,
      metaTitle: 'Products page title',
      hasMetaDescription: true,
      metaDescription: 'A'.repeat(150),
      hasOgTitle: true,
      ogTitle: 'Products Open Graph Title',
      hasOgDescription: true,
      ogDescription: 'B'.repeat(180),
      hasOgImage: true,
      robots: ''
    },
    {
      id: 'about/team',
      title: 'Team',
      template: 'article',
      status: 'unlisted',
      hasMetaTitle: true,
      metaTitle: 'Team page title',
      hasMetaDescription: true,
      metaDescription: 'short',
      hasOgTitle: true,
      ogTitle: 'Team Open Graph Title',
      hasOgDescription: true,
      ogDescription: 'C'.repeat(180),
      hasOgImage: false,
      robots: ''
    },
    {
      id: 'blog/news/update',
      title: 'Update',
      template: 'news',
      status: 'draft',
      hasMetaTitle: false,
      metaTitle: null,
      hasMetaDescription: false,
      metaDescription: null,
      hasOgTitle: false,
      ogTitle: null,
      hasOgDescription: false,
      ogDescription: null,
      hasOgImage: false,
      robots: ''
    }
  ];

  const context = {
    siteSettings: {
      siteMetaDescription: 'Fallback site description',
      appendSiteName: false,
      siteHasOgImage: true
    },
    validationSettings: {}
  };

  assert.deepEqual(
    sortPages(sortablePages, 'name-asc', context).map((page) => page.title),
    ['Products', 'Team', 'Update']
  );

  assert.deepEqual(
    sortPages(sortablePages, 'level-asc', context).map((page) => page.id),
    ['products', 'about/team', 'blog/news/update']
  );

  assert.deepEqual(
    sortPages(sortablePages, 'status', context).map((page) => page.status),
    ['listed', 'unlisted', 'draft']
  );

  assert.deepEqual(
    sortPages(sortablePages, 'attention', context).map((page) => page.id),
    ['about/team', 'blog/news/update', 'products']
  );
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

test('change detection ignores page-specific OG edits for the site row', () => {
  const sitePage = {
    id: 'site',
    metaTitle: 'Site Title',
    metaDescription: 'Site Desc',
    ogTitle: null,
    ogDescription: null
  };

  assert.equal(
    hasPageFieldChanges(sitePage, {
      metaTitle: 'Site Title',
      metaDescription: 'Site Desc',
      ogTitle: 'Generated OG Title',
      ogDescription: 'Generated OG Desc'
    }),
    false
  );

  assert.equal(
    hasPageFieldChanges(sitePage, {
      metaTitle: 'Updated Site Title',
      metaDescription: 'Site Desc',
      ogTitle: '',
      ogDescription: ''
    }),
    true
  );
});
