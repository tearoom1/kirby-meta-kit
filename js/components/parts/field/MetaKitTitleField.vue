<template>
  <div :class="fieldClass">
    <div v-if="label || aiEnabled" class="k-meta-kit-dialog-field-header">
      <label v-if="label" class="k-meta-kit-dialog-field-label">{{ label }}</label>
      <k-button
        v-if="aiEnabled"
        icon="sparkling"
        :size="buttonSize"
        :disabled="isGenerating"
        @click="$emit('generate')"
        :title="buttonSize === 'xs' ? 'AI Generate' : undefined"
      >
        <template v-if="buttonSize !== 'xs'">AI Generate</template>
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
    <div class="k-meta-kit-dialog-field-meta">
      <span>
        <span v-if="value"
              class="k-meta-kit-field-length"
              :class="statusClass">
          {{ charCount }} chars
        </span>
      </span>
    </div>
    <div v-if="isGenerating" class="k-meta-kit-dialog-generating">
      <k-icon class="k-meta-kit-spinner" type="loader"/>
      <span>Generating...</span>
    </div>
  </div>
</template>

<script>
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
    }
  },
  computed: {
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
    statusClass() {
      const titleToUse = this.effectiveTitle;
      if (!titleToUse) return '';

      // For site page, no color coding
      if (this.isSitePage) {
        return '';
      }

      let finalLength = titleToUse.length;
      if (this.shouldAppendSiteName) {
        finalLength = this.fullTitle.length;
      }

      // Different optimal ranges for meta vs OG
      let optimal, warning;
      if (this.type === 'og') {
        optimal = { min: 40, max: 60 };
        warning = { min: 35, max: 70 };
      } else {
        optimal = { min: 50, max: 60 };
        warning = { min: 45, max: 66 };
      }

      if (finalLength >= optimal.min && finalLength <= optimal.max) {
        return 'k-meta-kit-status-success';
      }

      if (finalLength >= warning.min && finalLength <= warning.max) {
        return 'k-meta-kit-status-warning';
      }

      return 'k-meta-kit-status-error';
    }
  }
};
</script>
