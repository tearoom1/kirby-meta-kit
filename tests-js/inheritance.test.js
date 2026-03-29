import test from 'node:test';
import assert from 'node:assert/strict';
import {
  isTitleInherited,
  isDescriptionInherited,
  isOgTitleInherited,
  isOgDescriptionInherited,
  getEffectiveTitle,
  getEffectiveDescription,
  getInheritanceSource,
  buildTooltipText
} from '../js/composables/useInheritance.js';

// ─── Fixtures ────────────────────────────────────────────────────────────────

const siteSettings = { siteMetaDescription: 'Site fallback description' };
const emptySiteSettings = {};

const fullyFilledPage = {
  id: 'about',
  template: 'default',
  title: 'About Us',
  hasMetaTitle: true,
  metaTitle: 'About Meta',
  hasMetaDescription: true,
  metaDescription: 'About description',
  hasOgTitle: true,
  ogTitle: 'About OG Title',
  hasOgDescription: true,
  ogDescription: 'About OG Description',
  metaTitleInheritance: { inherited: false },
  metaDescriptionInheritance: { inherited: false },
  ogTitleInheritance: { inherited: false },
  ogDescriptionInheritance: { inherited: false },
};

const pageWithNoMeta = {
  id: 'contact',
  template: 'default',
  title: 'Contact',
  hasMetaTitle: false,
  metaTitle: null,
  hasMetaDescription: false,
  metaDescription: null,
  hasOgTitle: false,
  ogTitle: null,
  hasOgDescription: false,
  ogDescription: null,
  metaTitleInheritance: { inherited: false },
  metaDescriptionInheritance: { inherited: false },
  ogTitleInheritance: { inherited: false },
  ogDescriptionInheritance: { inherited: false },
};

const pageWithLanguageInheritance = {
  id: 'news',
  template: 'article',
  title: 'News',
  hasMetaTitle: false,
  metaTitle: null,
  metaTitleInheritance: { inherited: true, inheritedFrom: 'en', inheritedValue: 'News EN Title' },
  hasMetaDescription: false,
  metaDescription: null,
  metaDescriptionInheritance: { inherited: true, inheritedFrom: 'en', inheritedValue: 'News EN Description' },
  hasOgTitle: false,
  ogTitle: null,
  ogTitleInheritance: { inherited: true, inheritedFrom: 'en', inheritedValue: 'News EN OG Title' },
  hasOgDescription: false,
  ogDescription: null,
  ogDescriptionInheritance: { inherited: true, inheritedFrom: 'en', inheritedValue: 'News EN OG Desc' },
};

const sitePage = {
  id: 'site',
  template: 'site',
  title: 'My Site',
  hasMetaTitle: true,
  metaTitle: 'Site Meta',
  metaTitleInheritance: { inherited: false },
  ogTitleInheritance: { inherited: false },
};

// ─── isTitleInherited ─────────────────────────────────────────────────────────

test('isTitleInherited returns false for site page', () => {
  assert.equal(isTitleInherited(sitePage), false);
});

test('isTitleInherited returns false when page has its own meta title', () => {
  assert.equal(isTitleInherited(fullyFilledPage), false);
});

test('isTitleInherited returns true when page has no meta title', () => {
  assert.equal(isTitleInherited(pageWithNoMeta), true);
});

test('isTitleInherited returns true when language inheritance is set', () => {
  assert.equal(isTitleInherited(pageWithLanguageInheritance), true);
});

// ─── isDescriptionInherited ───────────────────────────────────────────────────

test('isDescriptionInherited returns false when page has its own description', () => {
  assert.equal(isDescriptionInherited(fullyFilledPage, siteSettings), false);
});

test('isDescriptionInherited returns true when no description but site has one', () => {
  assert.equal(isDescriptionInherited(pageWithNoMeta, siteSettings), true);
});

test('isDescriptionInherited returns false when no description and no site fallback', () => {
  assert.equal(isDescriptionInherited(pageWithNoMeta, emptySiteSettings), false);
});

test('isDescriptionInherited returns true when language inheritance is set', () => {
  assert.equal(isDescriptionInherited(pageWithLanguageInheritance, emptySiteSettings), true);
});

// ─── isOgTitleInherited ───────────────────────────────────────────────────────

test('isOgTitleInherited returns false for site page', () => {
  assert.equal(isOgTitleInherited(sitePage), false);
});

test('isOgTitleInherited returns false when page has its own OG title', () => {
  assert.equal(isOgTitleInherited(fullyFilledPage), false);
});

test('isOgTitleInherited returns true when page has no OG title', () => {
  assert.equal(isOgTitleInherited(pageWithNoMeta), true);
});

// ─── isOgDescriptionInherited ─────────────────────────────────────────────────

test('isOgDescriptionInherited returns false when page has its own OG description', () => {
  assert.equal(isOgDescriptionInherited(fullyFilledPage, emptySiteSettings), false);
});

test('isOgDescriptionInherited returns true when no OG description but meta description exists', () => {
  const page = { ...pageWithNoMeta, hasMetaDescription: true };
  assert.equal(isOgDescriptionInherited(page, emptySiteSettings), true);
});

test('isOgDescriptionInherited returns true when no OG or meta description but site fallback exists', () => {
  assert.equal(isOgDescriptionInherited(pageWithNoMeta, siteSettings), true);
});

test('isOgDescriptionInherited returns false when nothing available', () => {
  assert.equal(isOgDescriptionInherited(pageWithNoMeta, emptySiteSettings), false);
});

// ─── getEffectiveTitle ────────────────────────────────────────────────────────

test('getEffectiveTitle returns meta title when set', () => {
  assert.equal(getEffectiveTitle(fullyFilledPage, 'meta'), 'About Meta');
});

test('getEffectiveTitle falls back to page title when no meta title', () => {
  assert.equal(getEffectiveTitle(pageWithNoMeta, 'meta'), 'Contact');
});

test('getEffectiveTitle returns OG title for type og', () => {
  assert.equal(getEffectiveTitle(fullyFilledPage, 'og'), 'About OG Title');
});

test('getEffectiveTitle falls back to meta title then page title for OG', () => {
  const page = { ...pageWithNoMeta, hasMetaTitle: true, metaTitle: 'Meta Title' };
  assert.equal(getEffectiveTitle(page, 'og'), 'Meta Title');
});

test('getEffectiveTitle returns language-inherited value when set', () => {
  assert.equal(getEffectiveTitle(pageWithLanguageInheritance, 'meta'), 'News EN Title');
  assert.equal(getEffectiveTitle(pageWithLanguageInheritance, 'og'), 'News EN OG Title');
});

// ─── getEffectiveDescription ──────────────────────────────────────────────────

test('getEffectiveDescription returns meta description when set', () => {
  assert.equal(getEffectiveDescription(fullyFilledPage, 'meta', siteSettings), 'About description');
});

test('getEffectiveDescription falls back to site settings when no meta description', () => {
  assert.equal(getEffectiveDescription(pageWithNoMeta, 'meta', siteSettings), 'Site fallback description');
});

test('getEffectiveDescription returns null when no description anywhere', () => {
  assert.equal(getEffectiveDescription(pageWithNoMeta, 'meta', emptySiteSettings), null);
});

test('getEffectiveDescription returns OG description for type og', () => {
  assert.equal(getEffectiveDescription(fullyFilledPage, 'og', siteSettings), 'About OG Description');
});

test('getEffectiveDescription falls back meta → site for OG type', () => {
  const page = { ...pageWithNoMeta, hasMetaDescription: true, metaDescription: 'Meta desc' };
  assert.equal(getEffectiveDescription(page, 'og', emptySiteSettings), 'Meta desc');
});

test('getEffectiveDescription returns language-inherited value when set', () => {
  assert.equal(
    getEffectiveDescription(pageWithLanguageInheritance, 'meta', emptySiteSettings),
    'News EN Description'
  );
});

// ─── getInheritanceSource ─────────────────────────────────────────────────────

test('getInheritanceSource returns false for page with own meta title', () => {
  assert.equal(getInheritanceSource(fullyFilledPage, 'metaTitle', siteSettings), false);
});

test('getInheritanceSource returns "page title" when no meta title', () => {
  assert.equal(getInheritanceSource(pageWithNoMeta, 'metaTitle', siteSettings), 'page title');
});

test('getInheritanceSource returns "site" for missing meta description with site fallback', () => {
  assert.equal(getInheritanceSource(pageWithNoMeta, 'metaDescription', siteSettings), 'site');
});

test('getInheritanceSource returns false for missing description without site fallback', () => {
  assert.equal(getInheritanceSource(pageWithNoMeta, 'metaDescription', emptySiteSettings), false);
});

test('getInheritanceSource returns "meta title" for OG title falling back to meta title', () => {
  const page = { ...pageWithNoMeta, hasMetaTitle: true };
  assert.equal(getInheritanceSource(page, 'ogTitle', emptySiteSettings), 'meta title');
});

test('getInheritanceSource returns "page title" for OG title with no meta title either', () => {
  assert.equal(getInheritanceSource(pageWithNoMeta, 'ogTitle', emptySiteSettings), 'page title');
});

test('getInheritanceSource returns language code when language inheritance is set', () => {
  assert.equal(
    getInheritanceSource(pageWithLanguageInheritance, 'metaTitle', emptySiteSettings),
    'en'
  );
});

// ─── buildTooltipText ─────────────────────────────────────────────────────────

test('buildTooltipText returns content without prefix when no inheritance', () => {
  assert.equal(buildTooltipText('Hello world', false, true), 'Hello world');
});

test('buildTooltipText prepends inherited-from prefix when inheritance exists', () => {
  const result = buildTooltipText('Hello world', 'page title', true);
  assert.ok(result.startsWith('Inherited from page title'));
  assert.ok(result.includes('Hello world'));
});

test('buildTooltipText omits content when showContent is false', () => {
  const result = buildTooltipText('Hello world', 'site', false);
  assert.equal(result, 'Inherited from site');
  assert.ok(!result.includes('Hello world'));
});

test('buildTooltipText truncates long content', () => {
  const long = 'x'.repeat(300);
  const result = buildTooltipText(long, false, true, 200);
  assert.ok(result.endsWith('...'));
  assert.ok(result.length < 220);
});

test('buildTooltipText handles empty content gracefully', () => {
  assert.equal(buildTooltipText('', false, true), '');
  assert.equal(buildTooltipText(null, false, true), '');
});
