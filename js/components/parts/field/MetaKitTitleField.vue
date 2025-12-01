<template>
  <div :class="fieldClass">
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
    <div v-if="isGenerating" class="k-meta-kit-dialog-generating">
      <k-icon class="k-meta-kit-spinner" type="loader"/>
      <span>Generating...</span>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    value: String,
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
      // Check if appendSiteNameTo exists (could be false, null, undefined, etc.)
      if (this.siteSettings.appendSiteNameTo === undefined ||
          this.siteSettings.appendSiteNameTo === null ||
          this.siteSettings.appendSiteNameTo === '') {
        // Fallback to old behavior if appendSiteNameTo is not set
        return !!this.siteSettings.appendSiteName;
      }

      // appendSiteNameTo is a comma-separated string like "meta,og" or "meta" or "og"
      const setting = this.siteSettings.appendSiteNameTo;

      // Check if the type is in the comma-separated list
      return setting.split(',').map(s => s.trim()).includes(this.type);
    },
    showPreview() {
      // Show preview for non-site pages when we have a title and site name should be appended
      return !this.isSitePage &&
             this.effectiveTitle &&
             this.shouldAppendSiteName &&
             this.siteSettings.siteMetaTitle;
    },
    fullTitle() {
      const titleToUse = this.effectiveTitle;

      if (!titleToUse || !this.shouldAppendSiteName) {
        return titleToUse;
      }

      const separator = this.siteSettings.titleSeparator || '|';
      const siteName = this.siteSettings.siteMetaTitle || '';

      if (!siteName) {
        return titleToUse;
      }

      return `${titleToUse} ${separator} ${siteName}`;
    },
    charCount() {
      const titleToUse = this.effectiveTitle;
      if (!titleToUse) return 0;

      if (this.isSitePage) {
        return titleToUse.length;
      }

      if (this.shouldAppendSiteName && this.siteSettings.siteMetaTitle) {
        return this.fullTitle.length;
      }

      return titleToUse.length;
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
