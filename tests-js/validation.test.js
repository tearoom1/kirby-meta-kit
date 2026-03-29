import test from 'node:test';
import assert from 'node:assert/strict';
import {
  DEFAULT_SEO_RANGES,
  DEFAULT_SLUG_RANGES,
  getRangesForPageAndType,
  getSlugValidationConfig,
  isOutsideRange,
  getStatusClass,
  getStatusValue,
  getLengthValidationReason,
  getSlugValidationIssues
} from '../js/composables/useValidation.js';

// ─── Fixtures ────────────────────────────────────────────────────────────────

const articlePage = { id: 'blog/post-a', template: 'article' };
const homePage    = { id: 'home', template: 'home' };

const validationSettings = {
  ranges: {
    title: { optimal: { min: 30, max: 55 }, warning: { min: 20, max: 65 } },
  },
  templates: {
    article: {
      ranges: {
        title: { optimal: { min: 40, max: 70 }, warning: { min: 30, max: 80 } },
      },
    },
  },
};

// ─── getRangesForPageAndType ──────────────────────────────────────────────────

test('getRangesForPageAndType returns defaults when no settings provided', () => {
  const ranges = getRangesForPageAndType(homePage, 'title', {});
  assert.deepEqual(ranges, DEFAULT_SEO_RANGES.title);
});

test('getRangesForPageAndType applies global range overrides', () => {
  const ranges = getRangesForPageAndType(homePage, 'title', validationSettings);
  assert.deepEqual(ranges, { optimal: { min: 30, max: 55 }, warning: { min: 20, max: 65 } });
});

test('getRangesForPageAndType applies template-specific overrides over global', () => {
  const ranges = getRangesForPageAndType(articlePage, 'title', validationSettings);
  assert.deepEqual(ranges, { optimal: { min: 40, max: 70 }, warning: { min: 30, max: 80 } });
});

test('getRangesForPageAndType returns description defaults when no override', () => {
  const ranges = getRangesForPageAndType(articlePage, 'description', validationSettings);
  assert.deepEqual(ranges, DEFAULT_SEO_RANGES.description);
});

test('getRangesForPageAndType returns undefined for unknown type', () => {
  const ranges = getRangesForPageAndType(articlePage, 'unknown', {});
  assert.equal(ranges, undefined);
});

// ─── isOutsideRange ───────────────────────────────────────────────────────────

test('isOutsideRange returns false when value is within range', () => {
  assert.equal(isOutsideRange(50, { min: 20, max: 60 }), false);
  assert.equal(isOutsideRange(20, { min: 20, max: 60 }), false);
  assert.equal(isOutsideRange(60, { min: 20, max: 60 }), false);
});

test('isOutsideRange returns true when value exceeds max', () => {
  assert.equal(isOutsideRange(61, { min: 20, max: 60 }), true);
});

test('isOutsideRange returns true when value is below min', () => {
  assert.equal(isOutsideRange(5, { min: 20, max: 60 }), true);
});

test('isOutsideRange returns false for null range', () => {
  assert.equal(isOutsideRange(50, null), false);
});

// ─── getStatusClass ───────────────────────────────────────────────────────────

test('getStatusClass returns optimal for a title in the ideal range', () => {
  const cls = getStatusClass(homePage, 55, 'title', {});
  assert.equal(cls, 'k-meta-kit-status-optimal');
});

test('getStatusClass returns warning for a title in the warning band', () => {
  // Default title warning: 15-75, optimal: 20-60
  const cls = getStatusClass(homePage, 70, 'title', {});
  assert.equal(cls, 'k-meta-kit-status-warning');
});

test('getStatusClass returns error for a title outside all ranges', () => {
  const cls = getStatusClass(homePage, 5, 'title', {});
  assert.equal(cls, 'k-meta-kit-status-error');
});

test('getStatusClass returns empty string for zero length', () => {
  assert.equal(getStatusClass(homePage, 0, 'title', {}), '');
});

test('getStatusClass uses template-specific ranges when provided', () => {
  // Article template optimal: 40-70
  assert.equal(getStatusClass(articlePage, 65, 'title', validationSettings), 'k-meta-kit-status-optimal');
  assert.equal(getStatusClass(homePage,    65, 'title', validationSettings), 'k-meta-kit-status-warning');
});

test('getStatusClass returns optimal for description in 140-160', () => {
  assert.equal(getStatusClass(homePage, 150, 'description', {}), 'k-meta-kit-status-optimal');
});

// ─── getStatusValue ───────────────────────────────────────────────────────────

test('getStatusValue extracts status word from class', () => {
  assert.equal(getStatusValue('k-meta-kit-status-optimal'), 'optimal');
  assert.equal(getStatusValue('k-meta-kit-status-warning'), 'warning');
  assert.equal(getStatusValue('k-meta-kit-status-error'),   'error');
});

test('getStatusValue returns empty string for falsy input', () => {
  assert.equal(getStatusValue(''),    '');
  assert.equal(getStatusValue(null),  '');
});

// ─── getLengthValidationReason ────────────────────────────────────────────────

test('getLengthValidationReason returns empty string for optimal length', () => {
  assert.equal(getLengthValidationReason(homePage, 'title', 50, {}), '');
});

test('getLengthValidationReason returns warning reason for warning-band length', () => {
  const reason = getLengthValidationReason(homePage, 'title', 70, {});
  assert.ok(reason.includes('warning'));
  assert.ok(reason.includes('70'));
});

test('getLengthValidationReason returns error reason for out-of-range length', () => {
  const reason = getLengthValidationReason(homePage, 'title', 5, {});
  assert.ok(reason.includes('error'));
  assert.ok(reason.includes('5'));
});

test('getLengthValidationReason returns empty string for zero length', () => {
  assert.equal(getLengthValidationReason(homePage, 'title', 0, {}), '');
});

// ─── getSlugValidationIssues ──────────────────────────────────────────────────

const defaultSlugCfg = {
  depth:     DEFAULT_SLUG_RANGES.depth,
  words:     DEFAULT_SLUG_RANGES.words,
  length:    DEFAULT_SLUG_RANGES.length,
  wordLength: DEFAULT_SLUG_RANGES.wordLength,
};

test('getSlugValidationIssues returns empty array for a healthy slug', () => {
  const issues = getSlugValidationIssues({
    numSlashes: 1, wordCount: 3, length: 20, avgWordLength: 7,
    cfg: defaultSlugCfg,
  });
  assert.equal(issues.length, 0);
});

test('getSlugValidationIssues flags depth error when too deep', () => {
  const issues = getSlugValidationIssues({
    numSlashes: 5, wordCount: 2, length: 20, avgWordLength: 10,
    cfg: defaultSlugCfg,
  });
  const depthIssue = issues.find(i => i.key === 'Depth');
  assert.ok(depthIssue);
  assert.equal(depthIssue.severity, 'error');
});

test('getSlugValidationIssues flags length warning for borderline slug', () => {
  const issues = getSlugValidationIssues({
    numSlashes: 1, wordCount: 5, length: 65, avgWordLength: 13,
    cfg: defaultSlugCfg,
  });
  const lengthIssue = issues.find(i => i.key === 'Length');
  assert.ok(lengthIssue);
  assert.equal(lengthIssue.severity, 'warning');
});

// ─── getSlugValidationConfig ──────────────────────────────────────────────────

test('getSlugValidationConfig returns defaults when no settings provided', () => {
  const cfg = getSlugValidationConfig(homePage, {});
  assert.deepEqual(cfg.depth,  DEFAULT_SLUG_RANGES.depth);
  assert.deepEqual(cfg.words,  DEFAULT_SLUG_RANGES.words);
  assert.deepEqual(cfg.length, DEFAULT_SLUG_RANGES.length);
});

test('getSlugValidationConfig merges template-specific slug rules', () => {
  const settings = {
    templates: {
      article: {
        slug: {
          depth: { optimal: { min: 1, max: 1 }, warning: { min: 0, max: 2 } },
        },
      },
    },
  };
  const cfg = getSlugValidationConfig(articlePage, settings);
  assert.deepEqual(cfg.depth.optimal, { min: 1, max: 1 });
  assert.deepEqual(cfg.words, DEFAULT_SLUG_RANGES.words);
});
