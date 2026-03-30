<template>
  <k-dialog ref="dialog" size="large" :cancel-button="false" submitButton="">
    <k-headline>{{ headline }}</k-headline>

    <div v-if="isLoading" class="k-meta-kit-loading">
      <k-icon class="k-meta-kit-spinner" type="loader"/>
      <span>Reviewing content…</span>
    </div>

    <div v-else-if="error" class="k-meta-kit-review-dialog">
      <p class="k-meta-kit-review-error">{{ error }}</p>
    </div>

    <div v-else-if="reviewData" class="k-meta-kit-review-dialog">
      <div class="k-meta-kit-review-summary-card">
        <strong>{{ reviewData.review?.summary || fallbackSummary }}</strong>
        <p v-if="reviewData.review?.overallQuality" class="k-meta-kit-review-quality">
          Overall quality: {{ reviewData.review.overallQuality }}
        </p>
        <p v-if="reviewData.review?.searchIntent" class="k-meta-kit-review-quality">
          Search intent: {{ reviewData.review.searchIntent }}
        </p>
      </div>

      <div v-if="reviewData.review?.keyphrases?.length" class="k-meta-kit-review-block">
        <h3>Suggested keyphrases</h3>
        <div v-for="(item, index) in reviewData.review.keyphrases" :key="`phrase-${index}`" class="k-meta-kit-review-suggestion">
          <strong>{{ item.phrase }}</strong>
          <p>{{ item.reason }}</p>
        </div>
      </div>

      <div class="k-meta-kit-review-grid">
        <div class="k-meta-kit-review-block">
          <h3>What works</h3>
          <ul v-if="reviewData.review?.strengths?.length" class="k-meta-kit-review-list">
            <li v-for="(item, index) in reviewData.review.strengths" :key="`strength-${index}`">{{ item }}</li>
          </ul>
          <p v-else class="k-meta-kit-review-empty">No strengths returned.</p>
        </div>

        <div class="k-meta-kit-review-block">
          <h3>What to improve</h3>
          <ul v-if="reviewData.review?.improvements?.length" class="k-meta-kit-review-list">
            <li v-for="(item, index) in reviewData.review.improvements" :key="`improvement-${index}`">{{ item }}</li>
          </ul>
          <p v-else class="k-meta-kit-review-empty">No improvements returned.</p>
        </div>
      </div>

      <div v-if="reviewData.review?.metadataFit?.length" class="k-meta-kit-review-block">
        <h3>Metadata fit</h3>
        <ul class="k-meta-kit-review-list">
          <li v-for="(item, index) in reviewData.review.metadataFit" :key="`metadata-${index}`">{{ item }}</li>
        </ul>
      </div>

      <div v-if="reviewData.review?.priorityPages?.length" class="k-meta-kit-review-block">
        <h3>Pages to review first</h3>
        <div v-for="(page, index) in reviewData.review.priorityPages" :key="`priority-page-${index}`" class="k-meta-kit-review-page">
          <strong>{{ page.page }}</strong>
          <p>{{ page.reason }}</p>
        </div>
      </div>

      <div v-if="reviewData.review?.nextSteps?.length" class="k-meta-kit-review-block">
        <h3>Next steps</h3>
        <ul class="k-meta-kit-review-list">
          <li v-for="(item, index) in reviewData.review.nextSteps" :key="`next-${index}`">{{ item }}</li>
        </ul>
      </div>

      <div class="k-meta-kit-dialog-footer">
        <div class="k-meta-kit-dialog-footer-actions k-meta-kit-dialog-footer-actions-start">
          <a
            v-if="reviewData.scope === 'page' && reviewData.page?.panelUrl"
            :href="reviewData.page.panelUrl"
            class="k-link k-meta-kit-dialog-panel-link"
          >
            Edit in Panel
          </a>
        </div>
        <div class="k-meta-kit-dialog-footer-meta"></div>
        <div class="k-meta-kit-dialog-footer-actions k-meta-kit-dialog-footer-actions-end">
          <k-button @click="close">Close</k-button>
        </div>
      </div>
    </div>
  </k-dialog>
</template>

<script>
export default {
  props: {
    api: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      reviewData: null,
      isLoading: false,
      error: '',
      headline: 'AI Content Review'
    };
  },
  computed: {
    fallbackSummary() {
      return 'Review the page topic, search intent, and whether the content clearly supports a strong SEO angle.';
    }
  },
  methods: {
    async openPage(pageId, title = 'Page Content Review') {
      await this.fetchReview('meta-kit/review-page', { pageId }, title);
    },
    async openSelection(pageIds, title = 'Selected Pages Content Review') {
      await this.fetchReview('meta-kit/review-pages', { pageIds }, title);
    },
    async openSite(title = 'Site Content Review') {
      await this.fetchReview('meta-kit/review-site', {}, title);
    },
    async fetchReview(route, payload, title) {
      this.headline = title;
      this.reviewData = null;
      this.error = '';
      this.isLoading = true;
      this.$refs.dialog.open();

      try {
        const response = await this.api.post(route, payload);
        if (response.status === 'success') {
          this.reviewData = response.data;
        } else {
          this.error = response.message || 'Failed to load review.';
        }
      } catch (error) {
        this.error = error?.message || 'Failed to load review.';
      } finally {
        this.isLoading = false;
      }
    },
    close() {
      this.$refs.dialog.close();
      this.reviewData = null;
      this.error = '';
    }
  }
};
</script>
