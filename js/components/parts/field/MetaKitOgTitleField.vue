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
          {{ value.length }} chars
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
      default: 'No OG title'
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
    // The title to use - either the meta title or fallback to page title
    effectiveTitle() {
      return this.value || this.pageTitle || '';
    },
    fullTitle() {
      // todo fixme
      const titleToUse = this.effectiveTitle;

      if (!titleToUse || !this.siteSettings.appendSiteName) {
        return titleToUse;
      }

      const separator = this.siteSettings.titleSeparator || '|';
      const siteName = this.siteSettings.siteMetaTitle || '';

      if (!siteName) {
        return titleToUse;
      }

      return `${titleToUse} ${separator} ${siteName}`;
    },
    statusClass() {
      if (!this.value) return '';

      const length = this.value.length;
      // OG titles are typically shorter, around 40-60 characters
      const optimal = { min: 40, max: 60 };
      const warning = { min: 35, max: 70 };

      if (length >= optimal.min && length <= optimal.max) {
        return 'k-meta-kit-status-success';
      }

      if (length >= warning.min && length <= warning.max) {
        return 'k-meta-kit-status-warning';
      }

      return 'k-meta-kit-status-error';
    }
  }
};
</script>
