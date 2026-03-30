<template>
  <k-field v-bind="$props" class="k-mk-review-field">
    <template #options>
      <k-field-options />
    </template>

    <div class="k-mk-review-box">
      <div class="k-mk-review-header">
        <div>
          <strong>{{ titleText }}</strong>
          <p class="k-mk-review-help">Get a quick AI-assisted review of the current page's SEO setup.</p>
        </div>
        <k-button
          icon="sparkling"
          size="sm"
          :disabled="disabled || !aiEnabled || isLoading"
          :text="isLoading ? 'Reviewing…' : 'Review SEO'"
          @click="reviewSeo"
        />
      </div>

      <div v-if="!aiEnabled" class="k-mk-review-empty">
        Configure AI in Meta Kit to enable review suggestions.
      </div>

      <div v-if="error" class="k-mk-review-error">{{ error }}</div>

      <div v-if="review" class="k-mk-review-result">
        <p v-if="review.review?.summary" class="k-mk-review-summary">{{ review.review.summary }}</p>
        <p v-if="review.review?.overallQuality" class="k-mk-review-quality">
          Overall quality: {{ review.review.overallQuality }}
        </p>
        <p v-if="review.review?.searchIntent" class="k-mk-review-quality">
          Search intent: {{ review.review.searchIntent }}
        </p>

        <div v-if="review.review?.keyphrases?.length" class="k-mk-review-subsection">
          <strong>Suggested keyphrases</strong>
          <ul class="k-mk-review-list">
            <li v-for="(item, index) in review.review.keyphrases" :key="`phrase-${index}`">
              <strong>{{ item.phrase }}</strong> - {{ item.reason }}
            </li>
          </ul>
        </div>

        <div v-if="review.review?.improvements?.length" class="k-mk-review-subsection">
          <strong>What to improve</strong>
          <ul class="k-mk-review-list">
            <li v-for="(item, index) in review.review.improvements" :key="`improvement-${index}`">{{ item }}</li>
          </ul>
        </div>
      </div>
    </div>
  </k-field>
</template>

<script>
export default {
  inheritAttrs: false,
  props: {
    label: String,
    pageId: String,
    disabled: Boolean,
    aiEnabled: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      isLoading: false,
      error: '',
      review: null
    };
  },
  computed: {
    titleText() {
      return this.pageId === 'site' ? 'Review site-wide SEO defaults' : 'Review this page';
    }
  },
  methods: {
    async reviewSeo() {
      if (!this.pageId) return;

      this.isLoading = true;
      this.error = '';

      try {
        const response = await this.$api.post('meta-kit/review-page', {
          pageId: this.pageId
        });

        if (response.status === 'success') {
          this.review = response.data;
        } else {
          this.error = response.message || 'Failed to load SEO review.';
        }
      } catch (error) {
        this.error = error?.message || 'Failed to load SEO review.';
      } finally {
        this.isLoading = false;
      }
    }
  }
};
</script>

<style>
.k-mk-review-field {
  .k-mk-review-box {
    border: 1px solid var(--color-border);
    border-radius: var(--rounded-sm);
    padding: 0.875rem 1rem;
    background: var(--color-back);
  }

  .k-mk-review-header {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: flex-start;
  }

  .k-mk-review-help {
    margin: 0.35rem 0 0;
    color: var(--color-text-dimmed);
    font-size: 0.875rem;
  }

  .k-mk-review-result {
    margin-top: 0.875rem;
  }

  .k-mk-review-summary {
    margin: 0 0 0.75rem;
  }

  .k-mk-review-quality {
    margin: 0.35rem 0 0;
    color: var(--color-text-dimmed);
    font-size: 0.875rem;
  }

  .k-mk-review-subsection {
    margin-top: 0.75rem;
  }

  .k-mk-review-list {
    margin: 0;
    padding-left: 1rem;
  }

  .k-mk-review-list li + li {
    margin-top: 0.375rem;
  }

  .k-mk-review-error {
    margin-top: 0.75rem;
    color: var(--color-red-600);
    font-size: 0.875rem;
  }

  .k-mk-review-empty {
    margin-top: 0.5rem;
    color: var(--color-text-dimmed);
    font-size: 0.875rem;
  }
}
</style>
