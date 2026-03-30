import { getEffectiveTitle } from './useInheritance.js';

export function shouldAppendSiteName(siteSettings = {}, type = 'meta') {
  const appendSiteName = !!siteSettings.appendSiteName;
  const appendTargets = siteSettings.appendSiteNameTo;

  if (appendTargets === undefined || appendTargets === null || appendTargets === '') {
    return appendSiteName;
  }

  return appendTargets.split(',').map((s) => s.trim()).includes(type);
}

export function buildTitleWithSiteName(title, siteSettings = {}, type = 'meta') {
  if (!title) {
    return title || '';
  }

  if (!shouldAppendSiteName(siteSettings, type)) {
    return title;
  }

  const siteName = siteSettings.siteMetaTitle || '';
  if (!siteName) {
    return title;
  }

  const separator = siteSettings.titleSeparator || '|';
  return `${title} ${separator} ${siteName}`;
}

export function getFieldEffectiveTitle({
  value = '',
  metaTitle = '',
  pageTitle = '',
  type = 'meta'
} = {}) {
  if (type === 'og') {
    return value || metaTitle || pageTitle || '';
  }
  return value || pageTitle || '';
}

export function getFieldTitleDisplay({
  value = '',
  metaTitle = '',
  pageTitle = '',
  type = 'meta',
  pageId = '',
  siteSettings = {}
} = {}) {
  const effective = getFieldEffectiveTitle({ value, metaTitle, pageTitle, type });
  const isSitePage = pageId === 'site';
  const withSite = buildTitleWithSiteName(effective, siteSettings, type);
  const showPreview = !isSitePage && !!effective && withSite !== effective;
  const charCount = isSitePage ? effective.length : (withSite || effective).length;

  return {
    effectiveTitle: effective,
    fullTitle: withSite || effective,
    showPreview,
    charCount
  };
}

export function getTableTitleDisplay(page, siteSettings = {}, type = 'meta') {
  const effective = getEffectiveTitle(page, type);
  const full = buildTitleWithSiteName(effective, siteSettings, type);
  return {
    effectiveTitle: effective,
    fullTitle: full || effective || '',
    charCount: (full || effective || '').length
  };
}

