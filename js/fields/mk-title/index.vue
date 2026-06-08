<template>
  <k-field v-bind="$props" class="k-mk-title-field">
    <template #options>
      <k-field-options />
    </template>
    <k-input
      :value="value"
      type="text"
      @input="onInput"
      :placeholder="placeholder"
      :disabled="disabled"
      :name="fieldType === 'og' ? 'ogTitle' : 'metaTitle'"
    />
    <template #footer>
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
            v-if="aiEnabled"
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
  </k-field>
</template>

<script>
import {
  getFieldName,
  getLanguageCode,
  generateAiContent
} from '../../composables/useAiGeneration.js';
import {
  getTitleValidation,
  shouldAppendSiteName,
  getCharCountWithSiteName,
  getTitlePreview
} from '../../composables/useFieldValidation.js';

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
    },
    aiEnabled: {
      type: Boolean,
      default: false
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
      return getCharCountWithSiteName(this.value, this.validationSettings, this.fieldType);
    },
    shouldAppendSiteName() {
      return shouldAppendSiteName(this.validationSettings, this.fieldType);
    },
    titlePreview() {
      return getTitlePreview(this.value, this.validationSettings, this.fieldType);
    },
    validation() {
      return getTitleValidation(this.charCount, this.validationSettings, this.shouldAppendSiteName);
    }
  },
  methods: {
    onInput(value) {
      this.$emit('input', value);
      this.$nextTick(() => {
        document.dispatchEvent(new CustomEvent('meta-kit-field-change', {
          detail: { field: getFieldName(this.fieldType, 'title'), value }
        }));
      });
    },
    async generateWithAi() {
      this.isGenerating = true;
      this.aiError = null;

      const pageId = this.pageId || this.validationSettings.pageId;
      const fieldName = getFieldName(this.fieldType, 'title');

      const result = await generateAiContent(this.$api, pageId, fieldName);

      if (result.success) {
        this.$emit('input', result.content);
      } else {
        this.aiError = result.error;
      }

      this.isGenerating = false;
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
