<template>
  <k-dialog ref="dialog" size="medium" cancelButton="Close" submitButton="">
    <k-headline>Legacy SEO Cleanup</k-headline>

    <div v-if="isLoading" class="k-meta-kit-loading">
      <k-icon class="k-meta-kit-spinner" type="loader"/>
      <span>Scanning all languages for legacy metadata...</span>
    </div>
    <div v-else>
      <div class="k-meta-kit-legacy-list">
        <p>Summary of legacy fields by language:</p>
        <ul>
          <li v-for="item in summary.byLanguage" :key="item.code">
            <strong>{{ item.code.toUpperCase() }}</strong>: {{ item.count }} item(s)
          </li>
        </ul>
        <p><strong>Total:</strong> {{ summary.total }} item(s) across all languages</p>
        <k-box theme="negative">
          <k-icon type="alert"/>
          <span>Warning: This cleanup removes legacy SEO fields permanently.</span>
        </k-box>
        <k-button
          icon="download"
          :disabled="isMigrating || summary.total === 0"
          :progress="isMigrating"
          @click="$emit('cleanup')"
          theme="positive"
        >
          Clean Up All Languages
        </k-button>
      </div>
    </div>
  </k-dialog>
</template>

<script>
export default {
  props: {
    summary: {
      type: Object,
      default: () => ({total: 0, byLanguage: []})
    },
    isLoading: {
      type: Boolean,
      default: false
    },
    isMigrating: {
      type: Boolean,
      default: false
    }
  },
  methods: {
    open() {
      this.$refs.dialog.open();
      this.$emit('load-summary');
    },
    close() {
      this.$refs.dialog.close();
    }
  }
};
</script>
