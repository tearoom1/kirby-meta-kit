<template>
  <div :class="fieldClass">
    <div v-if="label || aiEnabled" class="k-meta-kit-dialog-field-header">
      <label v-if="label" class="k-meta-kit-dialog-field-label">{{ label }}</label>
      <k-button
        v-if="aiEnabled"
        icon="sparkling"
        class="k-meta-kit-button-ai-generate"
        :size="buttonSize"
        :disabled="isGenerating"
        @click="$emit('generate')"
        :title="buttonSize === 'xs' ? $t('meta-kit.generate.ai') : undefined"
      >
        <template v-if="buttonSize !== 'xs'">{{ $t('meta-kit.generate.ai') }}</template>
      </k-button>
    </div>
    <k-input
      :value="value"
      @input="$emit('input', $event)"
      :placeholder="placeholder"
      type="textarea"
      :rows="rows"
      :buttons="buttons"
    />
    <div class="k-meta-kit-dialog-field-meta">
      <span>
        <span v-if="value"
              class="k-meta-kit-field-length"
              :class="statusClass">
          {{ $t('meta-kit.chars', { count: value.length }) }}
        </span>
      </span>
    </div>
    <div v-if="isGenerating" class="k-meta-kit-dialog-generating">
      <k-icon class="k-meta-kit-spinner" type="loader"/>
      <span>{{ $t('meta-kit.generate.generating') }}</span>
    </div>
  </div>
</template>

<script>
import { getFieldLengthStatus } from '../../../composables/useValidation.js';

export default {
  props: {
    value: String,
    label: {
      type: String,
      default: ''
    },
    placeholder: {
      type: String,
      default: 'No meta description'
    },
    aiEnabled: {
      type: Boolean,
      default: true
    },
    isGenerating: {
      type: Boolean,
      default: false
    },
    buttonSize: {
      type: String,
      default: 'xs'
    },
    rows: {
      type: Number,
      default: 3
    },
    buttons: {
      type: [Boolean, String],
      default: true
    },
    fieldClass: {
      type: String,
      default: 'k-meta-kit-dialog-table-field-desc'
    },
    type: {
      type: String,
      default: 'meta',
      validator: value => ['meta', 'og'].includes(value)
    },
    // Page template and validation settings: same ranges as the table
    template: {
      type: String,
      default: null
    },
    validationSettings: {
      type: Object,
      default: () => ({})
    }
  },
  computed: {
    statusClass() {
      if (!this.value) return '';

      const status = getFieldLengthStatus(
        this.value.length,
        this.template,
        this.type === 'og' ? 'ogDescription' : 'description',
        this.validationSettings
      );
      return status ? `k-meta-kit-status-${status}` : '';
    }
  }
};
</script>
