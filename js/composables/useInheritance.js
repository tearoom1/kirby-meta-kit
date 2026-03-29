/**
 * Composable for SEO field inheritance logic
 * Centralizes inheritance checking for meta fields
 */

/**
 * Check if title is inherited (from language or page title)
 * @param {Object} page - Page object
 * @returns {boolean}
 */
export function isTitleInherited(page) {
  if (page.id === 'site') return false;
  return page.metaTitleInheritance?.inherited || !page.hasMetaTitle;
}

/**
 * Check if description is inherited (from language or site)
 * @param {Object} page - Page object
 * @param {Object} siteSettings - Site settings object
 * @returns {boolean}
 */
export function isDescriptionInherited(page, siteSettings) {
  return page.metaDescriptionInheritance?.inherited === true ||
    (!page.hasMetaDescription && !!siteSettings?.siteMetaDescription);
}

/**
 * Check if OG title is inherited
 * @param {Object} page - Page object
 * @returns {boolean}
 */
export function isOgTitleInherited(page) {
  if (page.id === 'site') return false;
  return page.ogTitleInheritance?.inherited || !page.hasOgTitle;
}

/**
 * Check if OG description is inherited
 * @param {Object} page - Page object
 * @param {Object} siteSettings - Site settings object
 * @returns {boolean}
 */
export function isOgDescriptionInherited(page, siteSettings) {
  if (page.ogDescriptionInheritance?.inherited) return true;
  if (page.hasOgDescription) return false;
  return page.hasMetaDescription || !!siteSettings?.siteMetaDescription;
}

/**
 * Get effective title value (considering inheritance)
 * @param {Object} page - Page object
 * @param {string} type - 'meta' or 'og'
 * @returns {string|null}
 */
export function getEffectiveTitle(page, type = 'meta') {
  const isOg = type === 'og';
  const inheritance = isOg ? page.ogTitleInheritance : page.metaTitleInheritance;

  // Check for language inheritance first
  if (inheritance?.inherited && inheritance.inheritedValue) {
    return inheritance.inheritedValue;
  }

  if (isOg) {
    return page.hasOgTitle ? page.ogTitle : (page.hasMetaTitle ? page.metaTitle : page.title);
  }

  return page.hasMetaTitle ? page.metaTitle : page.title;
}

/**
 * Get effective description value (considering inheritance)
 * @param {Object} page - Page object
 * @param {string} type - 'meta' or 'og'
 * @param {Object} siteSettings - Site settings object
 * @returns {string|null}
 */
export function getEffectiveDescription(page, type = 'meta', siteSettings = {}) {
  const isOg = type === 'og';
  const inheritance = isOg ? page.ogDescriptionInheritance : page.metaDescriptionInheritance;

  // Check for language inheritance first
  if (inheritance?.inherited && inheritance.inheritedValue) {
    return inheritance.inheritedValue;
  }

  if (isOg) {
    if (page.hasOgDescription) return page.ogDescription;
    if (page.hasMetaDescription) return page.metaDescription;
    return siteSettings?.siteMetaDescription || null;
  }

  return page.hasMetaDescription ? page.metaDescription : (siteSettings?.siteMetaDescription || null);
}

/**
 * Get inheritance source text for tooltips
 * @param {Object} page - Page object
 * @param {string} fieldType - Field type (metaTitle, metaDescription, ogTitle, ogDescription)
 * @param {Object} siteSettings - Site settings object
 * @returns {string|false} Source name or false if not inherited
 */
export function getInheritanceSource(page, fieldType, siteSettings = {}) {
  const inheritanceMap = {
    metaTitle: page.metaTitleInheritance,
    metaDescription: page.metaDescriptionInheritance,
    ogTitle: page.ogTitleInheritance,
    ogDescription: page.ogDescriptionInheritance
  };

  const inheritance = inheritanceMap[fieldType];

  // Language-level inheritance
  if (inheritance?.inherited && inheritance.inheritedFrom) {
    return inheritance.inheritedFrom;
  }

  // Field-level inheritance
  switch (fieldType) {
    case 'metaTitle':
      return !page.hasMetaTitle ? 'page title' : false;

    case 'metaDescription':
      if (!page.hasMetaDescription && siteSettings?.siteMetaDescription) {
        return 'site';
      }
      return false;

    case 'ogTitle':
      if (!page.hasOgTitle) {
        return page.hasMetaTitle ? 'meta title' : 'page title';
      }
      return false;

    case 'ogDescription':
      if (!page.hasOgDescription) {
        if (page.hasMetaDescription) return 'meta description';
        if (siteSettings?.siteMetaDescription) return 'site';
      }
      return false;

    default:
      return false;
  }
}

/**
 * Check whether a field is specifically inherited from the site fallback
 * @param {Object} page - Page object
 * @param {string} fieldType - Field type (metaDescription, ogDescription, etc.)
 * @param {Object} siteSettings - Site settings object
 * @returns {boolean}
 */
export function isInheritedFromSite(page, fieldType, siteSettings = {}) {
  return getInheritanceSource(page, fieldType, siteSettings) === 'site';
}

/**
 * Build tooltip text with inheritance prefix
 * @param {string} content - Content text
 * @param {string|false} inheritanceSource - Source name or false
 * @param {boolean} showContent - Whether to show the content
 * @param {number} maxLength - Maximum content length before truncation
 * @returns {string}
 */
export function buildTooltipText(content, inheritanceSource, showContent = true, maxLength = 200) {
  let text = content || '';
  let prefix = '';

  if (text && text.length > maxLength) {
    text = text.substring(0, maxLength) + '...';
  }

  if (inheritanceSource) {
    prefix = 'Inherited from ' + inheritanceSource;
  }

  if (showContent) {
    text = (inheritanceSource ? ':\n\n' : '') + text;
    return prefix + text;
  }

  return prefix;
}

export default {
  isTitleInherited,
  isDescriptionInherited,
  isOgTitleInherited,
  isOgDescriptionInherited,
  getEffectiveTitle,
  getEffectiveDescription,
  getInheritanceSource,
  isInheritedFromSite,
  buildTooltipText
};
