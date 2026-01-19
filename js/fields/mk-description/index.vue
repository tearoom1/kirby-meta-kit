<template>
  <k-field v-bind="$props" class="k-mk-description-field">
    <template #options>
      <k-field-options />
    </template>
      <k-input type="textarea" class="k-mk-description-textarea"
               :value="value"
               @input="onInput"
               :placeholder="placeholder"
               :disabled="disabled"
               :buttons="false"
               :maxlength="maxlength"
               :counter="false"
               :name="fieldType === 'og' ? 'ogDescription' : 'metaDescription'"
      />
    <template #footer>
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
  </k-field>
</template>

<script>
import { getFieldName, generateAiContent } from '../../composables/useAiGeneration.js';
import { getDescriptionValidation } from '../../composables/useFieldValidation.js';

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
      return getDescriptionValidation(this.charCount, this.validationSettings);
    }
  },
  methods: {
    onInput(value) {
      this.$emit('input', value);
      this.$nextTick(() => {
        document.dispatchEvent(new CustomEvent('meta-kit-field-change', {
          detail: { field: getFieldName(this.fieldType, 'description'), value }
        }));
      });
    },
    async generateWithAi() {
      this.isGenerating = true;
      this.aiError = null;

      const pageId = this.pageId || this.validationSettings.pageId;
      const fieldName = getFieldName(this.fieldType, 'description');

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
