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
      type="text"
    />
    <div v-if="showPreview" class="k-meta-kit-title-preview">
      {{ fullTitle }}
    </div>
    <meta-kit-length-meter
      :length="charCount"
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
import { getFieldTitleDisplay, shouldAppendSiteName } from '../../../composables/panelDisplay.js';

export default {
  props: {
    value: String,
    label: {
      type: String,
      default: ''
    },
    placeholder: {
      type: String,
      default: 'No meta title'
    },
    pageId: {
      type: String,
      required: false
    },
    pageTitle: {
      type: String,
      default: ''
    },
    metaTitle: {
      type: String,
      default: ''
    },
    siteSettings: {
      type: Object,
      required: true
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
    fieldClass: {
      type: String,
      default: 'k-meta-kit-dialog-table-field-title'
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
      return getRangesForPageAndType({ template: this.template }, this.type === 'og' ? 'ogTitle' : 'title', this.validationSettings);
    },
    isSitePage() {
      return this.pageId === 'site';
    },
    // The title to use - with proper fallback chain based on type
    effectiveTitle() {
      if (this.type === 'og') {
        // OG title fallback: ogTitle -> metaTitle -> pageTitle
        return this.value || this.metaTitle || this.pageTitle || '';
      }
      // Meta title fallback: metaTitle -> pageTitle
      return this.value || this.pageTitle || '';
    },
    // Check if site name should be appended based on type and settings
    shouldAppendSiteName() {
      return shouldAppendSiteName(this.siteSettings, this.type);
    },
    showPreview() {
      return getFieldTitleDisplay({
        value: this.value,
        metaTitle: this.metaTitle,
        pageTitle: this.pageTitle,
        type: this.type,
        pageId: this.pageId,
        siteSettings: this.siteSettings
      }).showPreview;
    },
    fullTitle() {
      return getFieldTitleDisplay({
        value: this.value,
        metaTitle: this.metaTitle,
        pageTitle: this.pageTitle,
        type: this.type,
        pageId: this.pageId,
        siteSettings: this.siteSettings
      }).fullTitle;
    },
    charCount() {
      return getFieldTitleDisplay({
        value: this.value,
        metaTitle: this.metaTitle,
        pageTitle: this.pageTitle,
        type: this.type,
        pageId: this.pageId,
        siteSettings: this.siteSettings
      }).charCount;
    },
  }
};
</script>
