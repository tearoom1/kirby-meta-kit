<template>
  <k-field v-bind="$props" class="k-mk-review-field">
    <div class="k-mk-review-bar">
      <span v-if="statusText" class="k-mk-review-status">{{ statusText }}</span>
      <k-button
        icon="preview"
        size="sm"
        :disabled="disabled || !canReview"
        text="Review Content"
        @click="reviewSeo"
      />
    </div>

    <meta-kit-review-dialog ref="dialog" :api="$api" />
  </k-field>
</template>

<script>
import MetaKitReviewDialog from '../../components/parts/edit/MetaKitReviewDialog.vue';

export default {
  components: { MetaKitReviewDialog },
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
      if (!this.aiEnabled) return 'Configure AI to enable reviews';
      if (!this.reviewEnabled) return 'Content review is disabled';
      return null;
    }
  },
  methods: {
    reviewSeo() {
      if (!this.pageId || !this.canReview) return;
      this.$refs.dialog.openPage(this.pageId, 'SEO Review');
    }
  }
};
</script>
