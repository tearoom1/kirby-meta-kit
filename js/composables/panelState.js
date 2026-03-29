/**
 * Shared panel state helpers for filtering, pagination and edit-change detection.
 * Kept framework-agnostic so we can test with plain Node test runner.
 */

const METADATA_FILTERS = new Set([
  'missing-title',
  'missing-description',
  'missing-og-title',
  'missing-og-description',
  'missing-og-image',
  'complete',
  'noindex'
]);

const STATUS_FILTERS = new Set(['listed', 'unlisted', 'drafts']);

function matchesMetadataFilter(page, filter) {
  switch (filter) {
    case 'missing-title':
      return !page.hasMetaTitle;
    case 'missing-description':
      return !page.hasMetaDescription;
    case 'missing-og-title':
      return !page.hasOgTitle;
    case 'missing-og-description':
      return !page.hasOgDescription;
    case 'missing-og-image':
      return !page.hasOgImage;
    case 'complete':
      return page.hasMetaTitle && page.hasMetaDescription && page.hasOgImage;
    case 'noindex':
      return !!(page.robots && page.robots.includes('noindex'));
    default:
      return true;
  }
}

function matchesStatusFilter(page, filter) {
  switch (filter) {
    case 'listed':
      return page.status === 'listed' || page.status === 'published';
    case 'unlisted':
      return page.status === 'unlisted';
    case 'drafts':
      return page.status === 'draft';
    default:
      return false;
  }
}

export function filterPages(pages = [], activeFilters = [], searchQuery = '') {
  let filtered = pages;

  if (activeFilters.length > 0) {
    const metadataFilters = activeFilters.filter((f) => METADATA_FILTERS.has(f));
    const statusFilters = activeFilters.filter((f) => STATUS_FILTERS.has(f));

    filtered = filtered.filter((page) => {
      const matchesMetadata =
        metadataFilters.length === 0 ||
        metadataFilters.every((filter) => matchesMetadataFilter(page, filter));

      const matchesStatus =
        statusFilters.length === 0 ||
        statusFilters.some((filter) => matchesStatusFilter(page, filter));

      return matchesMetadata && matchesStatus;
    });
  }

  const query = searchQuery.trim().toLowerCase();
  if (!query) {
    return filtered;
  }

  return filtered.filter((page) => {
    return (
      page.title.toLowerCase().includes(query) ||
      page.id.toLowerCase().includes(query) ||
      page.template.toLowerCase().includes(query) ||
      (page.metaDescription && page.metaDescription.toLowerCase().includes(query))
    );
  });
}

export function paginatePages(filteredPages = [], currentPage = 1, pageSize = 25) {
  if (pageSize >= 99999) {
    return filteredPages;
  }
  const start = (currentPage - 1) * pageSize;
  return filteredPages.slice(start, start + pageSize);
}

export function getTotalPages(filteredPages = [], pageSize = 25) {
  if (pageSize >= 99999) {
    return 1;
  }
  return Math.ceil(filteredPages.length / pageSize);
}

export function isAllCurrentPageSelected(paginatedPages = [], selectedPages = []) {
  if (paginatedPages.length === 0) {
    return false;
  }
  return paginatedPages.every((page) => selectedPages.includes(page.id));
}

export function toggleSelectAllCurrentPage(paginatedPages = [], selectedPages = []) {
  const allSelected = isAllCurrentPageSelected(paginatedPages, selectedPages);
  const next = new Set(selectedPages);

  for (const page of paginatedPages) {
    if (allSelected) {
      next.delete(page.id);
    } else {
      next.add(page.id);
    }
  }

  return Array.from(next);
}

export function hasPageFieldChanges(page, edited) {
  if (!page || !edited) {
    return false;
  }
  return (
    edited.metaTitle !== (page.metaTitle || '') ||
    edited.metaDescription !== (page.metaDescription || '') ||
    edited.ogTitle !== (page.ogTitle || '') ||
    edited.ogDescription !== (page.ogDescription || '')
  );
}

export function hasAnyBulkChanges(pages = [], editedFields = {}) {
  return pages.some((page) => hasPageFieldChanges(page, editedFields[page.id]));
}

