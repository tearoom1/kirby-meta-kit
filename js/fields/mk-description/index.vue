<template>
  <k-textarea-field v-bind="$props" class="k-mk-description-field" :name="fieldType === 'og' ? 'ogDescription' : 'metaDescription'"
                    @input="onInput">
    <template #after>
      <k-text>
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
            :text="isGenerating ? 'Generating…' : 'Generate'"
            :disabled="disabled || isGenerating"
            @click="generateWithAi"
          />
        </span>
        <span v-if="aiError" class="k-mk-ai-error">{{ aiError }}</span>
      </k-text>
    </template>
  </k-textarea-field>
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
    maxlength: Number,
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
      return this.value ? this.value.length : 0;
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
      const optimal = ranges.optimal || {min: 140, max: 160};
      const warning = ranges.warning || {min: 126, max: 176};

      if (length >= optimal.min && length <= optimal.max) {
        return {
          status: 'optimal',
          theme: 'positive',
          message: `Optimal length (${optimal.min}-${optimal.max} characters recommended)`
        };
      }

      if (length >= warning.min && length <= warning.max) {
        if (length < optimal.min) {
          return {
            status: 'warning',
            theme: 'notice',
            message: `Too short. Add ${optimal.min - length} more characters for optimal length (${optimal.min}-${optimal.max} recommended)`
          };
        } else {
          return {
            status: 'warning',
            theme: 'notice',
            message: `Slightly too long. Remove ${length - optimal.max} characters for optimal length (${optimal.min}-${optimal.max} recommended)`
          };
        }
      }

      if (length < warning.min) {
        return {
          status: 'error',
          theme: 'negative',
          message: `Much too short! Add at least ${warning.min - length} more characters (${optimal.min}-${optimal.max} recommended)`
        };
      }

      return {
        status: 'error',
        theme: 'negative',
        message: `Too long! Reduce by ${length - warning.max} characters (${optimal.min}-${optimal.max} recommended)`
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
        const fieldName = this.fieldType === 'og' ? 'ogDescription' : 'metaDescription';
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
.k-mk-description-field {
  .k-mk-description-textarea {
    display: block;
  }

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
