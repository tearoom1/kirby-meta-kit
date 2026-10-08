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
    <meta-kit-length-meter
      :length="(value || '').length"
      :ranges="ranges"
    />
    <div v-if="isGenerating" class="k-meta-kit-dialog-generating">
      <k-icon class="k-meta-kit-spinner" type="loader"/>
      <span>{{ $t('meta-kit.generate.generating') }}</span>
    </div>
  </div>
</template>

<script>
import { getRangesForPageAndType } from '../../../composables/useValidation.js';
import MetaKitLengthMeter from '../common/MetaKitLengthMeter.vue';

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
    },
  },
  components: { MetaKitLengthMeter },
  computed: {
    ranges() {
      return getRangesForPageAndType({ template: this.template }, this.type === 'og' ? 'ogDescription' : 'description', this.validationSettings);
    },
  }
};
</script>
