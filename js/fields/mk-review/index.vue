<template>
  <k-field v-bind="$props" class="k-mk-review-field">
    <div class="k-mk-review-bar">
      <span v-if="statusText" class="k-mk-review-status">{{ statusText }}</span>
      <k-button
        icon="preview"
        size="sm"
        :disabled="disabled || !canReview"
        :text="$t('meta-kit.table.review')"
        @click="reviewSeo"
      />
    </div>
  </k-field>
</template>

<script>
import { openDialog } from '../../composables/dialogs.js';

export default {
  inheritAttrs: false,
  props: {
    label: String,
    pageId: String,
    disabled: Boolean,
    aiEnabled: {
      type: Boolean,
      default: false
    },
    reviewEnabled: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    canReview() {
      return this.reviewEnabled && this.aiEnabled;
    },
    statusText() {
      if (!this.aiEnabled) return this.$t('meta-kit.review.needsAi');
      if (!this.reviewEnabled) return this.$t('meta-kit.review.disabled');
      return null;
    }
  },
  methods: {
    reviewSeo() {
      if (!this.pageId || !this.canReview) return;
      openDialog(this.$panel, 'review', { pageId: this.pageId, title: this.label || 'SEO Review' });
    }
  }
};
</script>
