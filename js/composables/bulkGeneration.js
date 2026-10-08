/**
 * Bulk AI generation, one request per page and field, so long runs show
 * progress, can be cancelled and never hit a server or proxy timeout.
 * Kept framework-agnostic so we can test with plain Node test runner.
 */

// Option key from the generate dialog → page field and "has value" flag
export const GENERATION_FIELDS = [
  { option: 'title', field: 'metaTitle', hasKey: 'hasMetaTitle', label: 'Meta Title' },
  { option: 'description', field: 'metaDescription', hasKey: 'hasMetaDescription', label: 'Meta Description' },
  { option: 'ogTitle', field: 'ogTitle', hasKey: 'hasOgTitle', label: 'OG Title' },
  { option: 'ogDescription', field: 'ogDescription', hasKey: 'hasOgDescription', label: 'OG Description' }
];

/**
 * One job per page and selected field that has no value in the current
 * language yet. The site has no OG title/description of its own.
 */
export function planGeneration(pages = [], options = {}) {
  const jobs = [];

  for (const page of pages) {
    for (const { option, field, hasKey, label } of GENERATION_FIELDS) {
      if (!options[option] || page[hasKey]) continue;
      if (page.id === 'site' && field.startsWith('og')) continue;

      jobs.push({
        pageId: page.id,
        pageTitle: page.title || page.id,
        field,
        label
      });
    }
  }

  return jobs;
}

/**
 * Run generation jobs one after another.
 *
 * @param {Array} jobs - from planGeneration()
 * @param {Object} handlers
 * @param {Function} handlers.generate - async (job) => generated text; throws on error
 * @param {Function} [handlers.onProgress] - ({ done, total, job }) before each job
 * @param {Function} [handlers.isCancelled] - () => true to stop before the next job
 * @returns {Promise<{ suggestions: Array, errors: Array, cancelled: boolean }>}
 */
export async function runGeneration(jobs, { generate, onProgress = () => {}, isCancelled = () => false }) {
  const suggestions = [];
  const errors = [];

  for (const [index, job] of jobs.entries()) {
    if (isCancelled()) {
      return { suggestions, errors, cancelled: true };
    }

    onProgress({ done: index, total: jobs.length, job });

    try {
      const value = await generate(job);
      suggestions.push({ ...job, value, selected: true });
    } catch (error) {
      errors.push({ ...job, message: error?.message || 'Generation failed' });
    }
  }

  onProgress({ done: jobs.length, total: jobs.length, job: null });
  return { suggestions, errors, cancelled: false };
}

/**
 * Save the selected suggestions one by one.
 *
 * @param {Array} suggestions
 * @param {Function} apply - async (suggestion) => void; throws on error
 * @returns {Promise<{ saved: Array, errors: Array }>}
 */
export async function applySuggestions(suggestions, apply) {
  const saved = [];
  const errors = [];

  for (const suggestion of suggestions) {
    if (!suggestion.selected || !suggestion.value?.trim()) continue;

    try {
      await apply(suggestion);
      saved.push(suggestion);
    } catch (error) {
      errors.push({ ...suggestion, message: error?.message || 'Saving failed' });
    }
  }

  return { saved, errors };
}

/**
 * Generate one field via the panel API without saving it
 */
export async function generateFieldSuggestion(api, { pageId, field }, language = null) {
  const response = await api.post('meta-kit/generate-field', {
    pageId,
    fieldName: field,
    language
  });

  if (response?.status !== 'success' || !response.content) {
    throw new Error(response?.message || 'Generation failed');
  }

  return response.content;
}
