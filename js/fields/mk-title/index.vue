<template>
  <k-field v-bind="$props" class="k-mk-title-field">
    <template #default>
      <k-input
        :value="value"
        type="text"
        @input="onInput"
        :placeholder="placeholder"
        :disabled="disabled"
      />
      <div v-if="titlePreview && shouldAppendSiteName" class="k-mk-title-preview">
        Preview: {{ titlePreview }}
      </div>
      <k-text :theme="validation.theme">
        <span class="k-mk-validation-row">
          <span>
          <span v-if="validation.message" :theme="validation.theme"
                class="k-mk-validation-message k-mk-validation-left">
           <span :class="'k-mk-validation-status-' + validation.status">{{
               charCount
             }}</span> - {{ validation.message }}
          </span>
            </span>
          <k-button
            class="k-mk-ai-button"
            size="xs"
            icon="ai"
            :text="isGenerating ? 'Generating…' : 'Generate with AI'"
            :disabled="disabled || isGenerating"
            @click="generateWithAi"
          />
        </span>
        <span v-if="aiError" class="k-mk-ai-error">{{ aiError }}</span>
      </k-text>
    </template>
  </k-field>
</template>

<script>
export default {
  inheritAttrs: false,
  props: {
    value: String,
    fieldType: {
      type: String,
      default: 'meta'
    },
    pageId: String,
    disabled: Boolean,
    label: String,
    help: String,
    placeholder: String,
    validationSettings: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      isGenerating: false,
      aiError: null
    };
  },
  computed: {
    charCount() {
      if (!this.value) return 0;

      const baseLength = this.value.length;

      // Add site name if applicable
      if (this.shouldAppendSiteName && this.validationSettings.siteMetaTitle) {
        const separator = this.validationSettings.titleSeparator || '|';
        const siteName = this.validationSettings.siteMetaTitle;
        return `${this.value} ${separator} ${siteName}`.length;
      }

      return baseLength;
    },
    shouldAppendSiteName() {
      // Don't append for site page itself
      if (this.validationSettings.pageId === 'site') {
        return false;
      }

      // Check if appendSiteName is enabled
      if (!this.validationSettings.appendSiteName) {
        return false;
      }

      // Check appendSiteNameTo setting (comma-separated string like "meta,og" or "meta" or "og")
      const appendTo = this.validationSettings.appendSiteNameTo;
      if (!appendTo) {
        // If not set, fallback to old behavior (append to all)
        return true;
      }

      // Check if current field type is in the list
      const types = appendTo.split(',').map(s => s.trim());
      return types.includes(this.fieldType);
    },
    titlePreview() {
      if (!this.value) return '';

      if (this.shouldAppendSiteName && this.validationSettings.siteMetaTitle) {
        const separator = this.validationSettings.titleSeparator || '|';
        const siteName = this.validationSettings.siteMetaTitle;
        return `${this.value} ${separator} ${siteName}`;
      }

      return this.value;
    },
    validation() {
      if (!this.value) {
        return {
          status: '',
          theme: '',
          message: ''
        };
      }

      const length = this.charCount;
      const ranges = this.validationSettings.ranges || {};
      const optimal = ranges.optimal || {min: 20, max: 60};
      const warning = ranges.warning || {min: 15, max: 75};

      if (length >= optimal.min && length <= optimal.max) {
        return {
          status: 'optimal',
          theme: 'positive',
          message: `Optimal length. ${optimal.min}-${optimal.max} characters recommended. ${this.shouldAppendSiteName ? '(Includes length of site name)' : ''}`
        };
      }

      if (length >= warning.min && length <= warning.max) {
        if (length < optimal.min) {
          return {
            status: 'warning',
            theme: 'notice',
            message: `Too short. ${optimal.min}-${optimal.max} recommended. ${this.shouldAppendSiteName ? '(Includes length of site name)' : ''}`
          };
        } else {
          return {
            status: 'warning',
            theme: 'notice',
            message: `Slightly too long. ${optimal.min}-${optimal.max} recommended. ${this.shouldAppendSiteName ? '(Includes length of site name)' : ''}`
          };
        }
      }

      if (length < warning.min) {
        return {
          status: 'error',
          theme: 'negative',
          message: `Much too short! ${optimal.min}-${optimal.max} recommended. ${this.shouldAppendSiteName ? '(Includes length of site name)' : ''}`
        };
      }

      return {
        status: 'error',
        theme: 'negative',
        message: `Too long! ${optimal.min}-${optimal.max} recommended. ${this.shouldAppendSiteName ? '(Includes length of site name)' : ''}`
      };
    }
  },
  methods: {
    onInput(value) {
      this.$emit('input', value);
    },
    getLanguageCode() {
      return (
        this.$language?.code ||
        window.panel?.view?.props?.language ||
        window.panel?.language?.code ||
        'en'
      );
    },
    async generateWithAi() {
      this.isGenerating = true;
      this.aiError = null;

      try {
        const pageId = this.pageId || this.validationSettings.pageId;
        const fieldName = this.fieldType === 'og' ? 'ogTitle' : 'metaTitle';
        const language = this.getLanguageCode();

        const response = await this.$api.post('meta-kit/generate-field', {
          pageId,
          fieldName,
          language
        });

        if (response.status !== 'success' || !response.content) {
          throw new Error(response.message || 'Failed to generate');
        }

        this.$emit('input', response.content);
      } catch (e) {
        this.aiError = e.message || 'AI generation failed';
      } finally {
        this.isGenerating = false;
      }
    }
  }
};
</script>

<style>
.k-mk-title-field {

  .k-mk-validation-message {
    display: block;
    padding: 0 1rem;
    font-size: 0.875rem;
  }

  .k-mk-validation-row {
    margin-top: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
  }

  .k-mk-validation-left {
    flex: 1;
    min-width: 0;
  }

  .k-mk-ai-button {
    flex: none;
    color: var(--color-green-600);
  }

  .k-mk-ai-error {
    display: block;
    margin-top: 0.25rem;
  }

  .k-mk-title-preview {
    display: none;
    margin-top: 0.5rem;
    padding: 0.2rem 1rem 0;
    background: var(--color-background);
    border-radius: var(--rounded-xs);
    font-size: 0.875rem;
    color: var(--color-text-dimmed);
    font-style: italic;
  }

  .k-mk-validation-status-optimal {
    font-weight: 600;
    color: var(--color-green-600);
  }

  .k-mk-validation-status-warning {
    font-weight: 600;
    color: var(--color-orange-600);
  }

  .k-mk-validation-status-error {
    font-weight: 600;
    color: var(--color-red-600);
  }

  .k-panel[data-color-scheme="dark"] .k-mk-title-preview {
    background: var(--color-black);
  }

  .k-panel[data-color-scheme="dark"] .k-mk-validation-status-optimal {
    color: var(--color-green-400);
  }

  .k-panel[data-color-scheme="dark"] .k-mk-validation-status-warning {
    color: var(--color-orange-400);
  }

  .k-panel[data-color-scheme="dark"] .k-mk-validation-status-error {
    color: var(--color-red-400);
  }
}
</style>
