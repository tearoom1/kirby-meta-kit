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
      required: true
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
    }
  },
  computed: {
    isSitePage() {
      return this.pageId === 'site';
    },
    showPreview() {
      return !this.isSitePage &&
             this.value &&
             this.siteSettings.appendSiteName &&
             this.siteSettings.siteMetaTitle;
    },
    fullTitle() {
      if (!this.value || !this.siteSettings.appendSiteName) {
        return this.value || '';
      }

      const separator = this.siteSettings.titleSeparator || '|';
      const siteName = this.siteSettings.siteMetaTitle || '';

      if (!siteName) {
        return this.value;
      }

      return `${this.value} ${separator} ${siteName}`;
    },
    charCount() {
      if (!this.value) return 0;

      if (this.isSitePage) {
        return this.value.length;
      }

      if (this.siteSettings.appendSiteName && this.siteSettings.siteMetaTitle) {
        return this.fullTitle.length;
      }

      return this.value.length;
    },
    statusClass() {
      if (!this.value) return '';

      // For site page, no color coding
      if (this.isSitePage) {
        return '';
      }

      let finalLength = this.value.length;
      if (this.siteSettings.appendSiteName) {
        finalLength = this.fullTitle.length;
      }

      const optimal = { min: 50, max: 60 };
      const warning = { min: 45, max: 66 };

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
