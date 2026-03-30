/**
 * Shared panel state helpers for filtering, pagination and edit-change detection.
 * Kept framework-agnostic so we can test with plain Node test runner.
 */

import {
  getStatusClass,
  getSlugValidationConfig,
  getSlugValidationIssues
} from './useValidation.js';
import {
  getEffectiveDescription,
  isInheritedFromSite,
  isInheritedFromLanguage
} from './useInheritance.js';
import { getTableTitleDisplay } from './panelDisplay.js';

const METADATA_FILTERS = new Set([
  'missing-title',
  'missing-description',
  'missing-og-title',
  'missing-og-description',
  'missing-og-image',
  'complete',
  'noindex'
]);

const ATTENTION_FILTERS = new Set(['attention', 'good', 'warning', 'error']);
const TYPE_FILTERS = new Set([
  'type-slug',
  'type-title',
  'type-description',
  'type-og-title',
  'type-og-description',
  'type-og-image',
  'type-noindex'
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

function classifyTitle(page, validationSettings = {}, siteSettings = {}) {
  const length = getTableTitleDisplay(page, siteSettings, 'meta').charCount;
  const status = getStatusClass(page, length, 'title', validationSettings);

  if (status === 'k-meta-kit-status-error' || !status) return 'error';
  if (isInheritedFromLanguage(page, 'metaTitle', siteSettings) && length) return 'warning';
  if (status === 'k-meta-kit-status-warning') return 'warning';
  return 'good';
}

function classifyDescription(page, validationSettings = {}, siteSettings = {}) {
  const desc = getEffectiveDescription(page, 'meta', siteSettings);
  if (!desc) return 'error';
  const status = getStatusClass(page, desc.length, 'description', validationSettings);
  if (status === 'k-meta-kit-status-error' || !status) return 'error';

  if (
    isInheritedFromSite(page, 'metaDescription', siteSettings) ||
    isInheritedFromLanguage(page, 'metaDescription', siteSettings)
  ) {
    return 'warning';
  }

  if (status === 'k-meta-kit-status-warning') return 'warning';
  return 'good';
}

function classifyOgTitle(page, validationSettings = {}, siteSettings = {}) {
  const length = getTableTitleDisplay(page, siteSettings, 'og').charCount;
  const status = getStatusClass(page, length, 'ogTitle', validationSettings);

  if (status === 'k-meta-kit-status-error' || !status) return 'error';
  if (!page.hasOgTitle && isInheritedFromLanguage(page, 'metaTitle', siteSettings) && length) return 'warning';
  if (status === 'k-meta-kit-status-warning') return 'warning';
  return 'good';
}

function classifyOgDescription(page, validationSettings = {}, siteSettings = {}) {
  const desc = getEffectiveDescription(page, 'og', siteSettings);
  if (!desc) return 'error';
  const status = getStatusClass(page, desc.length, 'ogDescription', validationSettings);
  if (status === 'k-meta-kit-status-error' || !status) return 'error';

  if (!page.hasOgDescription && isInheritedFromLanguage(page, 'metaDescription', siteSettings)) return 'warning';
  if (isInheritedFromSite(page, 'ogDescription', siteSettings)) return 'warning';

  if (status === 'k-meta-kit-status-warning') return 'warning';
  return 'good';
}

function classifyOgImage(page, siteSettings = {}) {
  if (page.hasOgImage) return 'good';
  if (siteSettings?.siteHasOgImage) return 'warning';
  return 'error';
}

function classifyNoindex(page) {
  return page.robots && page.robots.includes('noindex') ? 'warning' : 'good';
}

function classifySlug(page, validationSettings = {}) {
  if (page.id === 'site') return 'good';

  const slug = page.id.split('/').pop() || '';
  const wordCount = slug.split(/[-_]/).filter(Boolean).length;
  const length = slug.length;
  const numSlashes = page.id.split('/').length - 1;
  const cfg = getSlugValidationConfig(page, validationSettings);
  const avgWordLength = wordCount > 0 ? Math.ceil(length / wordCount) : length;
  const issues = getSlugValidationIssues({ numSlashes, wordCount, length, avgWordLength, cfg });

  if (issues.some((issue) => issue.severity === 'error')) return 'error';
  if (issues.some((issue) => issue.severity === 'warning')) return 'warning';
  return 'good';
}

function hasWarningAttention(page, context = {}) {
  const { siteSettings = {}, validationSettings = {} } = context;

  return [
    classifySlug(page, validationSettings),
    classifyTitle(page, validationSettings, siteSettings),
    classifyDescription(page, validationSettings, siteSettings),
    classifyOgTitle(page, validationSettings, siteSettings),
    classifyOgDescription(page, validationSettings, siteSettings),
    classifyOgImage(page, siteSettings),
    classifyNoindex(page)
  ].includes('warning');
}

function hasErrorAttention(page, context = {}) {
  const { siteSettings = {}, validationSettings = {} } = context;

  return [
    classifySlug(page, validationSettings),
    classifyTitle(page, validationSettings, siteSettings),
    classifyDescription(page, validationSettings, siteSettings),
    classifyOgTitle(page, validationSettings, siteSettings),
    classifyOgDescription(page, validationSettings, siteSettings),
    classifyOgImage(page, siteSettings)
  ].includes('error');
}

function matchesAttentionFilter(page, filter, context = {}) {
  switch (filter) {
    case 'attention':
      return hasWarningAttention(page, context) || hasErrorAttention(page, context);
    case 'good':
      return !hasWarningAttention(page, context) && !hasErrorAttention(page, context);
    case 'warning':
      return hasWarningAttention(page, context);
    case 'error':
      return hasErrorAttention(page, context);
    default:
      return false;
  }
}

function getTypeStatus(page, filter, context = {}) {
  const { siteSettings = {}, validationSettings = {} } = context;

  switch (filter) {
    case 'type-slug':
      return classifySlug(page, validationSettings);
    case 'type-title':
      return classifyTitle(page, validationSettings, siteSettings);
    case 'type-description':
      return classifyDescription(page, validationSettings, siteSettings);
    case 'type-og-title':
      return classifyOgTitle(page, validationSettings, siteSettings);
    case 'type-og-description':
      return classifyOgDescription(page, validationSettings, siteSettings);
    case 'type-og-image':
      return classifyOgImage(page, siteSettings);
    case 'type-noindex':
      return classifyNoindex(page);
    default:
      return 'good';
  }
}

export function filterPages(pages = [], activeFilters = [], searchQuery = '', context = {}) {
  let filtered = pages;

  if (activeFilters.length > 0) {
    const metadataFilters = activeFilters.filter((f) => METADATA_FILTERS.has(f));
    const attentionFilters = activeFilters.filter((f) => ATTENTION_FILTERS.has(f));
    const typeFilters = activeFilters.filter((f) => TYPE_FILTERS.has(f));
    const statusFilters = activeFilters.filter((f) => STATUS_FILTERS.has(f));

    filtered = filtered.filter((page) => {
      const matchesMetadata =
        metadataFilters.length === 0 ||
        metadataFilters.every((filter) => matchesMetadataFilter(page, filter));

      const typeStatuses = typeFilters.map((filter) => getTypeStatus(page, filter, context));
      const matchesType =
        typeFilters.length === 0 ||
        attentionFilters.length === 0 ||
        typeStatuses.some((status) => {
          return attentionFilters.some((filter) => {
            if (filter === 'attention') {
              return status === 'warning' || status === 'error';
            }
            return status === filter;
          });
        });

      const matchesAttention = (() => {
        if (attentionFilters.length === 0) {
          return true;
        }

        if (typeFilters.length > 0) {
          return typeStatuses.some((status) => {
            return attentionFilters.some((filter) => {
              if (filter === 'attention') {
                return status === 'warning' || status === 'error';
              }
              return status === filter;
            });
          });
        }

        return attentionFilters.some((filter) => matchesAttentionFilter(page, filter, context));
      })();

      const matchesStatus =
        statusFilters.length === 0 ||
        statusFilters.some((filter) => matchesStatusFilter(page, filter));

      return matchesMetadata && matchesType && matchesAttention && matchesStatus;
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

function getPageLevel(page) {
  if (!page?.id || page.id === 'site') {
    return 0;
  }

  return page.id.split('/').length - 1;
}

function getStatusRank(page) {
  switch (page?.status) {
    case 'listed':
    case 'published':
      return 0;
    case 'unlisted':
      return 1;
    case 'draft':
      return 2;
    default:
      return 3;
  }
}

function compareText(a = '', b = '') {
  return String(a).localeCompare(String(b), undefined, { sensitivity: 'base', numeric: true });
}

export function sortPages(pages = [], sortBy = 'default', context = {}) {
  if (!Array.isArray(pages) || pages.length <= 1 || sortBy === 'default') {
    return pages;
  }

  const sorted = [...pages];

  sorted.sort((a, b) => {
    switch (sortBy) {
      case 'name-asc':
        return compareText(a.title, b.title) || compareText(a.id, b.id);
      case 'name-desc':
        return compareText(b.title, a.title) || compareText(b.id, a.id);
      case 'level-asc':
        return getPageLevel(a) - getPageLevel(b) || compareText(a.id, b.id);
      case 'level-desc':
        return getPageLevel(b) - getPageLevel(a) || compareText(a.id, b.id);
      case 'status':
        return getStatusRank(a) - getStatusRank(b) || compareText(a.title, b.title);
      case 'template':
        return compareText(a.template, b.template) || compareText(a.title, b.title);
      case 'attention':
        return (
          getAttentionRank(a, context) - getAttentionRank(b, context) ||
          compareText(a.title, b.title)
        );
      default:
        return 0;
    }
  });

  return sorted;
}

function getAttentionRank(page, context = {}) {
  if (hasErrorAttention(page, context)) return 0;
  if (hasWarningAttention(page, context)) return 1;
  return 2;
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
