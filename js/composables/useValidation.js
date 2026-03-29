/**
 * Composable for SEO validation logic
 * Centralizes validation ranges and status calculations
 */

// Default SEO length ranges for different field types
export const DEFAULT_SEO_RANGES = {
  title: { optimal: { min: 20, max: 60 }, warning: { min: 15, max: 75 } },
  ogTitle: { optimal: { min: 20, max: 60 }, warning: { min: 15, max: 75 } },
  description: { optimal: { min: 140, max: 160 }, warning: { min: 126, max: 176 } },
  ogDescription: { optimal: { min: 150, max: 250 }, warning: { min: 135, max: 300 } }
};

// Default slug validation ranges
export const DEFAULT_SLUG_RANGES = {
  depth: { optimal: { min: 0, max: 2 }, warning: { min: 0, max: 3 } },
  words: { optimal: { min: 1, max: 8 }, warning: { min: 1, max: 10 } },
  length: { optimal: { min: 1, max: 60 }, warning: { min: 1, max: 70 } },
  wordLength: { optimal: { min: 1, max: 15 }, warning: { min: 1, max: 20 } }
};

// Status CSS class mappings
export const STATUS_CLASSES = {
  optimal: 'k-meta-kit-status-optimal',
  warning: 'k-meta-kit-status-warning',
  error: 'k-meta-kit-status-error'
};

/**
 * Get merged range configuration for a specific page and field type
 * @param {Object} page - Page object with template info
 * @param {string} type - Field type (title, description, ogTitle, ogDescription)
 * @param {Object} validationSettings - Validation settings from props
 * @returns {Object} Merged range configuration
 */
export function getRangesForPageAndType(page, type, validationSettings = {}) {
  const defaults = validationSettings?.ranges || {};
  const templates = validationSettings?.templates || {};

  const templateName = page?.template;
  const templateConfig = templateName && templates[templateName] ? templates[templateName] : {};
  const templateRanges = templateConfig?.ranges || templateConfig || {};

  const merged = {
    ...DEFAULT_SEO_RANGES,
    ...defaults,
    ...templateRanges
  };

  return merged?.[type];
}

/**
 * Get slug validation configuration for a page
 * @param {Object} page - Page object with template info
 * @param {Object} validationSettings - Validation settings from props
 * @returns {Object} Merged slug configuration
 */
export function getSlugValidationConfig(page, validationSettings = {}) {
  const defaults = validationSettings?.slug || {};
  const templates = validationSettings?.templates || {};

  const templateName = page?.template;
  const templateConfig = templateName && templates[templateName] ? templates[templateName] : {};
  const templateSlug = templateConfig?.slug || {};

  const mergeRule = (key, fallbackOptimal, fallbackWarning) => {
    const raw = { ...(defaults[key] || {}), ...(templateSlug[key] || {}) };

    if (Object.keys(raw).length === 0) {
      return {
        optimal: { ...fallbackOptimal },
        warning: { ...fallbackWarning }
      };
    }

    // New format
    if (raw.optimal && raw.warning) {
      return {
        optimal: { ...fallbackOptimal, ...(raw.optimal || {}) },
        warning: { ...fallbackWarning, ...(raw.warning || {}) }
      };
    }

    // Backward compatibility: warningMax/errorMax
    const warningMax = typeof raw.warningMax === 'number' ? raw.warningMax : fallbackWarning.max;
    const errorMax = typeof raw.errorMax === 'number' ? raw.errorMax : fallbackWarning.max;
    return {
      optimal: { ...fallbackOptimal, max: warningMax },
      warning: { ...fallbackWarning, max: errorMax }
    };
  };

  return {
    depth: mergeRule('depth', DEFAULT_SLUG_RANGES.depth.optimal, DEFAULT_SLUG_RANGES.depth.warning),
    words: mergeRule('words', DEFAULT_SLUG_RANGES.words.optimal, DEFAULT_SLUG_RANGES.words.warning),
    length: mergeRule('length', DEFAULT_SLUG_RANGES.length.optimal, DEFAULT_SLUG_RANGES.length.warning),
    wordLength: mergeRule('wordLength', DEFAULT_SLUG_RANGES.wordLength.optimal, DEFAULT_SLUG_RANGES.wordLength.warning)
  };
}

/**
 * Check if a value is outside a range
 * @param {number} value - Value to check
 * @param {Object} range - Range object with min and max
 * @returns {boolean}
 */
export function isOutsideRange(value, range) {
  if (!range) return false;
  if (typeof range.min === 'number' && value < range.min) return true;
  if (typeof range.max === 'number' && value > range.max) return true;
  return false;
}

/**
 * Get status class based on length and ranges
 * @param {Object} page - Page object for template-specific config
 * @param {number} length - Character length
 * @param {string} type - Field type
 * @param {Object} validationSettings - Validation settings
 * @returns {string} CSS class name
 */
export function getStatusClass(page, length, type, validationSettings = {}) {
  if (!length || length === 0) return '';

  const ranges = getRangesForPageAndType(page, type, validationSettings);
  if (!ranges) return '';

  if (length >= ranges.optimal.min && length <= ranges.optimal.max) {
    return STATUS_CLASSES.optimal;
  }

  if (length >= ranges.warning.min && length <= ranges.warning.max) {
    return STATUS_CLASSES.warning;
  }

  return STATUS_CLASSES.error;
}

/**
 * Extract status value from status class
 * @param {string} statusClass - Full CSS class name
 * @returns {string} Status value (optimal, warning, error)
 */
export function getStatusValue(statusClass) {
  if (!statusClass) return '';
  const match = statusClass.match(/k-meta-kit-status-(\w+)/);
  return match ? match[1] : '';
}

/**
 * Get validation reason text for tooltips
 * @param {Object} page - Page object
 * @param {string} type - Field type
 * @param {number} length - Character length
 * @param {Object} validationSettings - Validation settings
 * @returns {string} Reason text (without leading newlines - caller should add if needed)
 */
export function getLengthValidationReason(page, type, length, validationSettings = {}) {
  const ranges = getRangesForPageAndType(page, type, validationSettings);
  if (!ranges || !length) return '';

  const statusClass = getStatusClass(page, length, type, validationSettings);
  // No validation reason needed for optimal or empty status
  if (!statusClass || statusClass === STATUS_CLASSES.optimal) return '';

  const optimal = `${ranges.optimal.min}-${ranges.optimal.max}`;
  const warning = `${ranges.warning.min}-${ranges.warning.max}`;

  if (statusClass === STATUS_CLASSES.warning) {
    return `Why warning:\nLength ${length} is outside optimal (${optimal}), but within warning (${warning}).`;
  }

  return `Why error:\nLength ${length} is outside warning (${warning}). Optimal is ${optimal}.`;
}

/**
 * Get slug validation issues
 * @param {Object} params - Validation parameters
 * @returns {Array} Array of issue objects
 */
export function getSlugValidationIssues({ numSlashes, wordCount, length, avgWordLength, cfg }) {
  const checks = [
    { key: 'Depth', value: numSlashes, optimal: cfg.depth?.optimal, warning: cfg.depth?.warning },
    { key: 'Words', value: wordCount, optimal: cfg.words?.optimal, warning: cfg.words?.warning },
    { key: 'Length', value: length, optimal: cfg.length?.optimal, warning: cfg.length?.warning },
  ];

  const issues = [];
  for (const check of checks) {
    const optimal = `${check.optimal.min}-${check.optimal.max}`;
    const warning = `${check.warning.min}-${check.warning.max}`;

    if (isOutsideRange(check.value, check.warning)) {
      issues.push({
        severity: 'error',
        key: check.key,
        value: check.value,
        optimal,
        warning
      });
      continue;
    }

    if (isOutsideRange(check.value, check.optimal)) {
      issues.push({
        severity: 'warning',
        key: check.key,
        value: check.value,
        optimal,
        warning
      });
    }
  }

  return issues;
}

export default {
  DEFAULT_SEO_RANGES,
  DEFAULT_SLUG_RANGES,
  STATUS_CLASSES,
  getRangesForPageAndType,
  getSlugValidationConfig,
  isOutsideRange,
  getStatusClass,
  getStatusValue,
  getLengthValidationReason,
  getSlugValidationIssues
};
