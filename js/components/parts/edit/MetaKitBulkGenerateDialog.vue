<template>
  <k-dialog ref="dialog" class="k-meta-kit-dialog" size="medium">
    <k-headline>Generate Missing Metadata</k-headline>
    <k-text>Select which fields to generate for {{ selectedCount }} selected page(s):</k-text>

    <div class="k-meta-kit-bulk-options">
      <label class="k-meta-kit-bulk-option">
        <input
          type="checkbox"
          v-model="options.title"
        />
        <div class="k-meta-kit-bulk-option-content">
          <strong>Meta Title</strong>
          <span>Generate meta titles for search engines (pages without one)</span>
        </div>
      </label>
      <label class="k-meta-kit-bulk-option">
        <input
          type="checkbox"
          v-model="options.description"
        />
        <div class="k-meta-kit-bulk-option-content">
          <strong>Meta Description</strong>
          <span>Generate meta descriptions for search engines (pages without one)</span>
        </div>
      </label>
      <label class="k-meta-kit-bulk-option">
        <input
          type="checkbox"
          v-model="options.ogTitle"
        />
        <div class="k-meta-kit-bulk-option-content">
          <strong>OG Title</strong>
          <span>Generate social media titles (pages without one)</span>
        </div>
      </label>
      <label class="k-meta-kit-bulk-option">
        <input
          type="checkbox"
          v-model="options.ogDescription"
        />
        <div class="k-meta-kit-bulk-option-content">
          <strong>OG Description</strong>
          <span>Generate social media descriptions (pages without one)</span>
        </div>
      </label>
    </div>

    <template #footer>
      <k-button-group class="k-meta-kit-bulk-buttons">
        <k-button @click="close()">Cancel</k-button>
        <k-button
          icon="sparkling"
          class="k-meta-kit-button-ai-generate"
          :disabled="!hasAnySelected"
          @click="generate"
        >
          Generate
        </k-button>
      </k-button-group>
    </template>
  </k-dialog>
</template>

<script>
export default {
  props: {
    selectedCount: {
      type: Number,
      default: 0
    }
  },
  data() {
    return {
      options: {
        title: false,
        description: true,
        ogTitle: false,
        ogDescription: false
      }
    };
  },
  computed: {
    hasAnySelected() {
      return this.options.title || this.options.description || this.options.ogTitle || this.options.ogDescription;
    }
  },
  methods: {
    open() {
      // Reset to defaults
      this.options.title = false;
      this.options.description = true;
      this.options.ogTitle = false;
      this.options.ogDescription = false;
      this.$refs.dialog.open();
    },
    close() {
      this.$refs.dialog.close();
    },
    generate() {
      this.$emit('generate', { ...this.options });
      this.close();
    }
  }
};
</script>
