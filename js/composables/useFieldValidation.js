/**
 * Composable for inline field validation
 * Returns validation state with status, theme, and message for field components
 */

// Default validation ranges
const DEFAULT_RANGES = {
  title: { optimal: { min: 20, max: 60 }, warning: { min: 15, max: 75 } },
  description: { optimal: { min: 140, max: 160 }, warning: { min: 126, max: 176 } }
};

/**
 * Get validation result for a field value
 * @param {number} length - Character count
 * @param {Object} ranges - Validation ranges with optimal and warning
 * @param {string} suffix - Optional suffix to append to messages (e.g., "(Includes site name)")
 * @returns {{status: string, theme: string, message: string}}
 */
export function getFieldValidation(length, ranges = {}, suffix = '') {
  if (!length) {
    return { status: '', theme: '', message: '' };
  }

  const optimal = ranges.optimal || DEFAULT_RANGES.title.optimal;
  const warning = ranges.warning || DEFAULT_RANGES.title.warning;
  const rangeText = `${optimal.min}-${optimal.max}`;
  const suffixText = suffix ? ` ${suffix}` : '';

  // Optimal range
  if (length >= optimal.min && length <= optimal.max) {
    return {
      status: 'optimal',
      theme: 'positive',
      message: `Optimal length. ${rangeText} characters recommended.${suffixText}`
    };
  }

  // Warning range - too short
  if (length >= warning.min && length < optimal.min) {
    return {
      status: 'warning',
      theme: 'notice',
      message: `Too short. ${rangeText} recommended.${suffixText}`
    };
  }

  // Warning range - too long
  if (length > optimal.max && length <= warning.max) {
    return {
      status: 'warning',
      theme: 'notice',
      message: `Slightly too long. ${rangeText} recommended.${suffixText}`
    };
  }

  // Error - much too short
  if (length < warning.min) {
    return {
      status: 'error',
      theme: 'negative',
      message: `Much too short! ${rangeText} recommended.${suffixText}`
    };
  }

  // Error - too long
  return {
    status: 'error',
    theme: 'negative',
    message: `Too long! ${rangeText} recommended.${suffixText}`
  };
}

/**
 * Get validation for title fields
 * @param {number} length - Character count
 * @param {Object} settings - Validation settings with ranges
 * @param {boolean} includesSiteName - Whether the count includes site name
 * @returns {{status: string, theme: string, message: string}}
 */
export function getTitleValidation(length, settings = {}, includesSiteName = false) {
  const ranges = settings.ranges || DEFAULT_RANGES.title;
  const suffix = includesSiteName ? '(Includes length of site name)' : '';
  return getFieldValidation(length, ranges, suffix);
}

/**
 * Get validation for description fields
 * @param {number} length - Character count
 * @param {Object} settings - Validation settings with ranges
 * @returns {{status: string, theme: string, message: string}}
 */
export function getDescriptionValidation(length, settings = {}) {
  const ranges = settings.ranges || DEFAULT_RANGES.description;
  return getFieldValidation(length, ranges);
}

/**
 * Check if site name should be appended to title
 * @param {Object} settings - Validation settings
 * @param {string} fieldType - 'meta' or 'og'
 * @returns {boolean}
 */
export function shouldAppendSiteName(settings = {}, fieldType = 'meta') {
  // Don't append for site page itself
  if (settings.pageId === 'site') {
    return false;
  }

  // Check if appendSiteName is enabled
  if (!settings.appendSiteName) {
    return false;
  }

  // Check appendSiteNameTo setting
  const appendTo = settings.appendSiteNameTo;
  if (!appendTo) {
    // If not set, fallback to old behavior (append to all)
    return true;
  }

  // Check if current field type is in the list
  return appendTo.split(',').map(s => s.trim()).includes(fieldType);
}

/**
 * Calculate character count with optional site name
 * @param {string} value - Field value
 * @param {Object} settings - Validation settings
 * @param {string} fieldType - 'meta' or 'og'
 * @returns {number}
 */
export function getCharCountWithSiteName(value, settings = {}, fieldType = 'meta') {
  if (!value) return 0;

  const baseLength = value.length;

  if (shouldAppendSiteName(settings, fieldType) && settings.siteMetaTitle) {
    const separator = settings.titleSeparator || '|';
    return `${value} ${separator} ${settings.siteMetaTitle}`.length;
  }

  return baseLength;
}

/**
 * Get title preview with site name
 * @param {string} value - Field value
 * @param {Object} settings - Validation settings
 * @param {string} fieldType - 'meta' or 'og'
 * @returns {string}
 */
export function getTitlePreview(value, settings = {}, fieldType = 'meta') {
  if (!value) return '';

  if (shouldAppendSiteName(settings, fieldType) && settings.siteMetaTitle) {
    const separator = settings.titleSeparator || '|';
    return `${value} ${separator} ${settings.siteMetaTitle}`;
  }

  return value;
}

export default {
  DEFAULT_RANGES,
  getFieldValidation,
  getTitleValidation,
  getDescriptionValidation,
  shouldAppendSiteName,
  getCharCountWithSiteName,
  getTitlePreview
};
