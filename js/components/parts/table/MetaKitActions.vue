<template>
  <div class="k-meta-kit-actions">
    <k-button-group>
      <k-button
        icon="edit"
        :title="hasSelection ? null : scopeHint"
        :disabled="selectedCount === 0"
        @click="$emit('edit-selected')"
      >
        {{ editLabel }}
      </k-button>
      <k-button
        v-if="aiEnabled"
        icon="sparkling"
        class="k-meta-kit-button-ai-generate"
        :title="hasSelection ? null : scopeHint"
        :disabled="isGenerating || selectedCount === 0"
        :progress="isGenerating"
        @click="$emit('generate-missing')"
      >
        {{ $t('meta-kit.actions.generate') }}<span v-if="selectedCount > 0"> ({{ selectedCount }})</span>
      </k-button>
      <k-button icon="refresh" :title="$t('meta-kit.actions.refresh')" @click="$emit('refresh')"></k-button>
    </k-button-group>

    <slot name="filters"></slot>
  </div>
</template>

<script>
export default {
  props: {
    selectedCount: {
      type: Number,
      default: 0
    },
    aiEnabled: {
      type: Boolean,
      default: true
    },
    reviewEnabled: {
      type: Boolean,
      default: false
    },
    isGenerating: {
      type: Boolean,
      default: false
    },
    // Without a selection the actions apply to all filtered pages
    hasSelection: {
      type: Boolean,
      default: false
    },
    isFiltered: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    // Say what the button acts on — the selection, the filtered pages or all pages
    editLabel() {
      const count = this.selectedCount;
      if (this.hasSelection) return this.$t('meta-kit.v2.editSelected', { count });
      return this.$t(this.isFiltered ? 'meta-kit.v2.editFiltered' : 'meta-kit.v2.editAll', { count });
    },
    scopeHint() {
      return this.$t(this.isFiltered ? 'meta-kit.v2.scope.filtered' : 'meta-kit.v2.scope.all');
    }
  }
};
</script>
