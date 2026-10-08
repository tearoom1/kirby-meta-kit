/**
 * Panel translations for code outside Vue templates. In the panel this uses
 * Kirby's window.panel.t() (texts from translations/*.json, registered in
 * index.php); elsewhere, e.g. in Node tests, it falls back to English.
 */
import en from '../../translations/en.json' with { type: 'json' };

function fill(text, data) {
  return String(text).replace(/\{\s*(\w+)\s*\}/g, (match, name) => (name in data ? String(data[name]) : match));
}

export function t(key, data = {}) {
  const fullKey = `meta-kit.${key}`;
  const fallback = en[fullKey] ?? fullKey;
  const panel = globalThis.window?.panel;

  return panel?.t ? panel.t(fullKey, data, fill(fallback, data)) : fill(fallback, data);
}
