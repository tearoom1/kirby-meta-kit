import test from 'node:test';
import assert from 'node:assert/strict';
import {
  shouldAppendSiteName,
  buildTitleWithSiteName,
  getFieldEffectiveTitle,
  getFieldTitleDisplay,
  getTableTitleDisplay
} from '../js/composables/panelDisplay.js';

const siteSettings = {
  appendSiteName: true,
  appendSiteNameTo: 'meta,og',
  siteMetaTitle: 'Site Name',
  titleSeparator: '|'
};

test('shouldAppendSiteName honors appendSiteNameTo matrix', () => {
  assert.equal(shouldAppendSiteName(siteSettings, 'meta'), true);
  assert.equal(shouldAppendSiteName(siteSettings, 'og'), true);
  assert.equal(shouldAppendSiteName({ ...siteSettings, appendSiteNameTo: 'meta' }, 'og'), false);
  assert.equal(shouldAppendSiteName({ appendSiteName: false, appendSiteNameTo: '' }, 'meta'), false);
});

test('buildTitleWithSiteName appends title + separator + site name', () => {
  assert.equal(buildTitleWithSiteName('Page Title', siteSettings, 'meta'), 'Page Title | Site Name');
  assert.equal(
    buildTitleWithSiteName('Page Title', { ...siteSettings, appendSiteNameTo: 'meta' }, 'og'),
    'Page Title'
  );
});

test('field title effective fallback chain matches UI behavior', () => {
  assert.equal(
    getFieldEffectiveTitle({ value: '', metaTitle: 'Meta Title', pageTitle: 'Page', type: 'og' }),
    'Meta Title'
  );
  assert.equal(
    getFieldEffectiveTitle({ value: '', metaTitle: '', pageTitle: 'Page', type: 'og' }),
    'Page'
  );
  assert.equal(
    getFieldEffectiveTitle({ value: '', pageTitle: 'Page', type: 'meta' }),
    'Page'
  );
});

test('field title display shows preview and char count correctly', () => {
  const display = getFieldTitleDisplay({
    value: 'Meta Title',
    metaTitle: '',
    pageTitle: 'Page',
    pageId: 'blog/post',
    type: 'meta',
    siteSettings
  });

  assert.equal(display.fullTitle, 'Meta Title | Site Name');
  assert.equal(display.showPreview, true);
  assert.equal(display.charCount, 'Meta Title | Site Name'.length);
});

test('field title display does not preview for site entry', () => {
  const display = getFieldTitleDisplay({
    value: 'Home',
    pageId: 'site',
    type: 'meta',
    siteSettings
  });

  assert.equal(display.showPreview, false);
  assert.equal(display.charCount, 4);
});

test('table title display uses inherited fallback and site-name appending', () => {
  const page = {
    id: 'blog/post',
    title: 'Page',
    hasMetaTitle: false,
    metaTitle: null,
    hasOgTitle: false,
    ogTitle: null,
    metaTitleInheritance: { inherited: false },
    ogTitleInheritance: { inherited: false }
  };

  const metaDisplay = getTableTitleDisplay(page, siteSettings, 'meta');
  assert.equal(metaDisplay.fullTitle, 'Page | Site Name');

  const ogDisplay = getTableTitleDisplay({ ...page, hasMetaTitle: true, metaTitle: 'Meta Fallback' }, siteSettings, 'og');
  assert.equal(ogDisplay.fullTitle, 'Meta Fallback | Site Name');
});

