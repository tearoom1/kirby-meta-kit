import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync, readdirSync, statSync } from 'node:fs';
import { join } from 'node:path';
import { t } from '../js/composables/i18n.js';

const en = JSON.parse(readFileSync(new URL('../translations/en.json', import.meta.url)));
const de = JSON.parse(readFileSync(new URL('../translations/de.json', import.meta.url)));
const root = new URL('..', import.meta.url).pathname;

const placeholders = (text) => [...String(text).matchAll(/\{\s*(\w+)\s*\}/g)].map((m) => m[1]).sort();

function filesIn(dir, extensions) {
  return readdirSync(join(root, dir)).flatMap((name) => {
    const path = join(dir, name);
    if (statSync(join(root, path)).isDirectory()) return filesIn(path, extensions);
    return extensions.some((ext) => name.endsWith(ext)) ? [path] : [];
  });
}

test('English and German have the same keys and placeholders', () => {
  assert.deepEqual(Object.keys(de).sort(), Object.keys(en).sort());
  for (const key of Object.keys(en)) {
    assert.deepEqual(placeholders(de[key]), placeholders(en[key]), `placeholders of ${key}`);
    assert.ok(de[key].trim(), `German text for ${key}`);
  }
});

test('every key used in components, scripts, blueprints and PHP exists', () => {
  const sources = [
    ...filesIn('js', ['.vue', '.js']),
    ...filesIn('blueprints', ['.yml', '.php']),
    ...filesIn('classes', ['.php']),
    ...filesIn('src', ['.php']),
    'index.php'
  ];
  const used = new Set();

  for (const file of sources) {
    const code = readFileSync(join(root, file), 'utf8');
    // $t('meta-kit.x'), blueprint values and option texts
    for (const [, key] of code.matchAll(/['"\s]meta-kit\.([A-Za-z0-9_.-]*[A-Za-z0-9])(?![A-Za-z0-9_.-]*\$\{)/g)) used.add(key);
    // t('x') in composables and Texts::get('x') in PHP
    for (const [, key] of code.matchAll(/(?:(?<![$\w.])t|Texts::get)\('([A-Za-z0-9_.-]+)'/g)) used.add(key);
  }

  // Keys ending with a dot are prefixes of dynamically built keys
  const missing = [...used].filter((key) => !key.endsWith('.') && !(`meta-kit.${key}` in en));
  assert.deepEqual(missing, []);
});

test('t() falls back to English and fills placeholders', () => {
  assert.equal(t('stats.good', { count: 3 }), '3 good');
  assert.equal(t('does.not.exist'), 'meta-kit.does.not.exist');
});

test('t() uses the panel translation when available', () => {
  globalThis.window = { panel: { t: (key, data, fallback) => (key === 'meta-kit.stats.good' ? `${data.count} gut` : fallback) } };
  try {
    assert.equal(t('stats.good', { count: 2 }), '2 gut');
  } finally {
    delete globalThis.window;
  }
});
