/**
 * Composable for AI content generation
 * Centralizes AI generation logic used across field components
 */

/**
 * Get the current language code from Kirby Panel
 * @returns {string} Language code (defaults to 'en')
 */
export function getLanguageCode() {
  if (typeof window === 'undefined') return 'en';

  return (
    window.panel?.view?.props?.language ||
    window.panel?.language?.code ||
    'en'
  );
}

/**
 * Map field type to API field name
 * @param {string} fieldType - 'meta' or 'og'
 * @param {string} contentType - 'title' or 'description'
 * @returns {string} API field name
 */
export function getFieldName(fieldType, contentType) {
  const fieldMap = {
    meta: { title: 'metaTitle', description: 'metaDescription' },
    og: { title: 'ogTitle', description: 'ogDescription' }
  };
  return fieldMap[fieldType]?.[contentType] || fieldMap.meta[contentType];
}

/**
 * Create AI generation state and methods for a Vue component
 * @param {Object} options - Configuration options
 * @param {Function} options.getApi - Function that returns the $api instance
 * @param {Function} options.getPageId - Function that returns the page ID
 * @param {Function} options.getFieldName - Function that returns the field name
 * @param {Function} options.onSuccess - Callback on successful generation
 * @param {Function} options.onError - Optional callback on error
 * @returns {Object} State and methods for AI generation
 */
export function createAiGenerationMixin(options = {}) {
  return {
    data() {
      return {
        isGenerating: false,
        aiError: null
      };
    },
    methods: {
      async generateWithAi() {
        this.isGenerating = true;
        this.aiError = null;

        try {
          const api = options.getApi ? options.getApi.call(this) : this.$api;
          const pageId = options.getPageId ? options.getPageId.call(this) : this.pageId;
          const fieldName = options.getFieldName ? options.getFieldName.call(this) : this.fieldName;
          const language = getLanguageCode();

          const response = await api.post('meta-kit/generate-field', {
            pageId,
            fieldName,
            language
          });

          if (response.status !== 'success' || !response.content) {
            throw new Error(response.message || 'Failed to generate');
          }

          if (options.onSuccess) {
            options.onSuccess.call(this, response.content);
          } else {
            this.$emit('input', response.content);
          }
        } catch (e) {
          this.aiError = e.message || 'AI generation failed';
          if (options.onError) {
            options.onError.call(this, e);
          }
        } finally {
          this.isGenerating = false;
        }
      }
    }
  };
}

/**
 * Generate AI content directly (for use outside Vue components)
 * @param {Object} api - Kirby Panel API instance
 * @param {string} pageId - Page ID
 * @param {string} fieldName - Field name (metaTitle, metaDescription, etc.)
 * @param {string} language - Language code
 * @returns {Promise<{success: boolean, content?: string, error?: string}>}
 */
export async function generateAiContent(api, pageId, fieldName, language = null) {
  try {
    const response = await api.post('meta-kit/generate-field', {
      pageId,
      fieldName,
      language: language || getLanguageCode()
    });

    if (response.status !== 'success' || !response.content) {
      return {
        success: false,
        error: response.message || 'Failed to generate'
      };
    }

    return {
      success: true,
      content: response.content
    };
  } catch (e) {
    return {
      success: false,
      error: e.message || 'AI generation failed'
    };
  }
}

export default {
  getLanguageCode,
  getFieldName,
  createAiGenerationMixin,
  generateAiContent
};
