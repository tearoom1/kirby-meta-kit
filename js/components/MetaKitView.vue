<template>
  <k-panel-inside class="k-meta-kit-view">
    <!-- License Warning -->
    <div v-if="!hasValidLicense" class="k-meta-kit-warning">
      <k-box theme="negative">
        <k-icon type="alert"/>
        <span><strong>No valid license:</strong> AI generation and saving changes are disabled. Meta tags are limited to 20 characters. Please activate your license to use all features.</span>
      </k-box>
    </div>

    <!-- Language Switcher -->
    <div
      v-if="languages && languages.length > 1"
      class="k-button-group k-language-selector k-meta-kit-language-bar"
      data-layout="collapsed"
      aria-label="Translations"
    >
      <k-button
        v-for="lang in languages"
        :key="lang.code"
        :aria-current="lang.code === language ? 'true' : undefined"
        :aria-label="lang.code"
        :title="lang.name"
        :theme="lang.code === language ? 'dark' : 'empty'"
        variant="filled"
        size="sm"
        responsive="true"
        @click="goToLanguage(lang.code)"
      >
        {{ lang.code }}
      </k-button>
    </div>

    <!-- Stats Cards -->
    <meta-kit-stats
      :filtered-count="filteredPages.length"
      :total-count="pagesData.length"
      :cards="statsCards"
      :search-active="!!(searchQuery || activeFilters.length)"
    />

    <!-- Actions & Filters -->
    <meta-kit-actions
      :selected-count="selectedPages.length"
      :ai-enabled="aiEnabled"
      :is-generating="isGeneratingAll"
      @edit-selected="showSelectedPagesDialog"
      @generate-missing="generateAllDescriptions"
      @review-selected="reviewSelectedPages"
      @review-site="reviewSite"
      @refresh="refreshPages"
    >
      <template #filters>
        <meta-kit-filters
          :show-preview.sync="showPreviewInTable"
          :preview-mode.sync="previewMode"
          :search-query.sync="searchQuery"
          :active-filters.sync="activeFilters"
          :sort-by.sync="sortBy"
        />
      </template>
    </meta-kit-actions>

    <!-- Pages Table -->
    <meta-kit-table
      :pages="paginatedPages"
      :start-index="(currentPage - 1) * pageSize"
      :selected-pages="selectedPages"
      :is-all-selected="isAllCurrentPageSelected"
      :show-preview="showPreviewInTable"
      :preview-mode="previewMode"
      :ai-enabled="aiEnabled"
      :site-settings="siteSettingsData"
      :validation-settings="validationSettingsData"
      @toggle-select-all="toggleSelectAllCurrentPage"
      @toggle-page="togglePageSelection"
      @edit-page="editSinglePageMetadata"
      @generate-page="openSinglePageGenerate"
    />

    <!-- Pagination + Page Size -->
    <div class="k-meta-kit-pagination">
      <!-- left: empty balancing column -->
      <div></div>

      <!-- center: page nav -->
      <div class="k-meta-kit-pagination-nav">
        <template v-if="totalPages > 1">
          <k-button
            icon="angle-left"
            :disabled="currentPage === 1"
            @click="previousPage"
          />
          <span class="k-meta-kit-pagination-info">
            Page {{ currentPage }} of {{ totalPages }}
            <template v-if="searchQuery || activeFilters.length">({{ filteredPages.length }} of {{ pagesData.length }})</template>
            <template v-else>({{ pagesData.length }} total)</template>
          </span>
          <k-button
            icon="angle-right"
            :disabled="currentPage === totalPages"
            @click="nextPage"
          />
        </template>
      </div>

      <!-- right: page size selector -->
      <div class="k-meta-kit-pagination-end">
        <select
          class="k-meta-kit-pagesize-select"
          :value="pageSize"
          @change="changePageSize($event.target.value)"
        >
          <option v-for="option in pageSizeOptions" :key="option.value" :value="option.value">
            {{ option.text }}
          </option>
        </select>
      </div>
    </div>

    <!-- Bulk Edit Dialog -->
    <meta-kit-bulk-edit-dialog
      ref="allPagesDialog"
      :api="$api"
      :site-settings="siteSettingsData"
      :ai-enabled="aiEnabled"
      @saved="handleSavedUpdates"
    />

    <!-- Single Page Edit Dialog -->
    <meta-kit-single-page-dialog
      ref="singlePageDialog"
      :api="$api"
      :site-settings="siteSettingsData"
      :ai-enabled="aiEnabled"
      @review-page="reviewSinglePage"
      @saved="handleSavedUpdates"
    />

    <meta-kit-review-dialog
      ref="reviewDialog"
      :api="$api"
    />

    <!-- Bulk Generation Dialog (used for both bulk and single-page AI generate) -->
    <meta-kit-bulk-generate-dialog
      ref="bulkGenerateDialog"
      :selected-count="singleGeneratePageId ? 1 : selectedPages.length"
      @generate="performBulkGeneration"
    />

    <!-- Loading Overlay -->
    <div v-if="isGeneratingAll" class="k-meta-kit-loading-overlay">
      <div class="k-meta-kit-loading-content">
        <div class="k-meta-kit-loading-spinner">
          <k-icon type="loader" />
        </div>
        <div class="k-meta-kit-loading-text">
          <template v-if="isGeneratingAll">Generating metadata with AI...</template>
        </div>
        <div v-if="loadingProgress" class="k-meta-kit-loading-progress">
          {{ loadingProgress }}
        </div>
      </div>
    </div>
  </k-panel-inside>
</template>

<script>
// Table component
import MetaKitStats from './parts/table/MetaKitStats.vue';
import MetaKitFilters from './parts/table/MetaKitFilters.vue';
import MetaKitActions from './parts/table/MetaKitActions.vue';
import MetaKitTable from './parts/table/MetaKitTable.vue';

// Edit/Dialog components
import MetaKitBulkGenerateDialog from './parts/edit/MetaKitBulkGenerateDialog.vue';
import MetaKitSinglePageDialog from './parts/edit/MetaKitSinglePageDialog.vue';
import MetaKitBulkEditDialog from './parts/edit/MetaKitBulkEditDialog.vue';
import MetaKitReviewDialog from './parts/edit/MetaKitReviewDialog.vue';
import {
  filterPages,
  sortPages,
  paginatePages,
  getTotalPages,
  isAllCurrentPageSelected as isAllSelectedOnPage,
  toggleSelectAllCurrentPage as toggleSelectAllOnPage
} from '../composables/panelState.js';
import {
  getStatusClass,
  getSlugValidationConfig,
  getSlugValidationIssues
} from '../composables/useValidation.js';
import {
  getEffectiveDescription,
  isInheritedFromSite,
  isInheritedFromLanguage
} from '../composables/useInheritance.js';
import { getTableTitleDisplay } from '../composables/panelDisplay.js';

export default {
  components: {
    MetaKitTable,
    MetaKitBulkGenerateDialog,
    MetaKitSinglePageDialog,
    MetaKitBulkEditDialog,
    MetaKitReviewDialog,
    MetaKitStats,
    MetaKitFilters,
    MetaKitActions
  },
  props: {
    pages: Array,
    language: String,
    languages: Array,
    validationSettings: {
      type: Object,
      default: () => ({})
    },
    aiEnabled: {
      type: Boolean,
      default: true
    },
    hasValidLicense: {
      type: Boolean,
      default: false
    },
    siteSettings: {
      type: Object,
      default: () => ({
        appendSiteName: true,
        siteMetaTitle: '',
        titleSeparator: '|'
      })
    }
  },
  data() {
    return {
      isLoadingPages: false,
      isGeneratingAll: false,
      pagesData: this.pages || [],
      siteSettingsData: this.siteSettings || {},
      validationSettingsData: this.validationSettings || {},

      // Single-page AI generate: null = bulk mode, string = single page ID
      singleGeneratePageId: null,

      // Pagination & Selection
      selectedPages: [],
      currentPage: 1,
      pageSize: 10,
      pageSizeOptions: [
        {value: 10, text: '10/page'},
        {value: 25, text: '25/page'},
        {value: 50, text: '50/page'},
        {value: 100, text: '100/page'},
        {value: 99999, text: 'All'}
      ],
      searchQuery: '',
      activeFilters: [],
      sortBy: 'default',
      showPreviewInTable: false,
      previewMode: 'meta',
      loadingProgress: ''
    };
  },
  computed: {
    filteredPages() {
      const filtered = filterPages(this.pagesData, this.activeFilters, this.searchQuery, {
        siteSettings: this.siteSettingsData,
        validationSettings: this.validationSettingsData
      });

      return sortPages(filtered, this.sortBy, {
        siteSettings: this.siteSettingsData,
        validationSettings: this.validationSettingsData
      });
    },
    paginatedPages() {
      return paginatePages(this.filteredPages, this.currentPage, this.pageSize);
    },
    totalPages() {
      return getTotalPages(this.filteredPages, this.pageSize);
    },
    isAllCurrentPageSelected() {
      return isAllSelectedOnPage(this.paginatedPages, this.selectedPages);
    },
    statsCards() {
      return [
        this.buildStatusBuckets(this.pagesData, this.filteredPages, (page) => this.classifySlug(page), 'Slug', {
          detailLines: [
            'Good = slug is valid',
            'Review = slug has warnings',
            'Fix = slug has errors'
          ]
        }),
        this.buildStatusBuckets(this.pagesData, this.filteredPages, (page) => this.classifyTitle(page), 'Meta Title', {
          detailLines: [
            'Good = valid title, including page-title fallback',
            'Review = title length warning only',
            'Fix = missing or invalid title'
          ]
        }),
        this.buildStatusBuckets(this.pagesData, this.filteredPages, (page) => this.classifyDescription(page), 'Meta Description', {
          detailLines: [
            'Good = valid unique description',
            'Review = inherited from site or length warning',
            'Fix = missing or invalid description'
          ]
        }),
        this.buildStatusBuckets(this.pagesData, this.filteredPages, (page) => this.classifyOgImage(page), 'OG Image', {
          detailLines: [
            'Good = page-specific OG image',
            'Review = inherited from site'
          ]
        }),
        this.buildStatusBuckets(this.pagesData, this.filteredPages, (page) => this.classifyNoindex(page), 'Noindex Pages', {
          attentionStatuses: ['review'],
          detailLines: [
            'Good = indexable page',
            'Review = page is set to noindex'
          ]
        })
      ].map((card) => ({
        ...card,
        attentionClass: card.filteredFix > 0
          ? 'k-meta-kit-stats-red'
          : (card.filteredAttention > 0 ? 'k-meta-kit-stats-amber' : 'k-meta-kit-stats-green')
      }));
    }
  },
  watch: {
    searchQuery() {
      this.currentPage = 1;
    },
    activeFilters() {
      this.currentPage = 1;
    },
    sortBy() {
      this.currentPage = 1;
    }
  },
  methods: {
    buildStatusBuckets(allPages, filteredPages, classify, label, options = {}) {
      const attentionStatuses = options.attentionStatuses || ['review', 'fix'];
      const summarize = (pages) => pages.reduce((acc, page) => {
        const status = classify(page);
        acc[status]++;
        return acc;
      }, { good: 0, review: 0, fix: 0 });

      const total = summarize(allPages);
      const filtered = summarize(filteredPages);
      const filteredAttention = attentionStatuses.reduce((sum, status) => sum + filtered[status], 0);
      const totalAttention = attentionStatuses.reduce((sum, status) => sum + total[status], 0);

      return {
        key: label.toLowerCase().replace(/\s+/g, '-'),
        label,
        filteredGood: filtered.good,
        filteredReview: filtered.review,
        filteredFix: filtered.fix,
        totalGood: total.good,
        totalReview: total.review,
        totalFix: total.fix,
        filteredAttention,
        totalAttention,
        tooltip: this.buildStatsTooltip({
          label,
          filtered,
          total,
          filteredAttention,
          totalAttention,
          detailLines: options.detailLines || []
        })
      };
    },

    buildStatsTooltip({ label, filtered, total, filteredAttention, totalAttention, detailLines }) {
      const hasScopedView = !!(this.searchQuery || this.activeFilters.length);
      const lines = [label];

      if (hasScopedView) {
        lines.push(`Visible pages: ${this.filteredPages.length} of ${this.pagesData.length}`);
        lines.push(`Needs attention here: ${filteredAttention}`);
        lines.push(`Needs attention overall: ${totalAttention}`);
      } else {
        lines.push(`Needs attention: ${filteredAttention} of ${this.pagesData.length}`);
      }

      lines.push(
        `Good: ${filtered.good}`,
        `Review: ${filtered.review}`,
        `Fix: ${filtered.fix}`
      );

      if (detailLines.length > 0) {
        lines.push('', ...detailLines);
      }

      if (hasScopedView) {
        lines.push('', `Overall split: ${total.good} good, ${total.review} review, ${total.fix} fix`);
      }

      return lines.join('\n');
    },

    mergeUpdatedPage(updatedPage) {
      if (!updatedPage || !updatedPage.id) return;

      const existingIndex = this.pagesData.findIndex((page) => page.id === updatedPage.id);
      if (existingIndex === -1) return;

      this.$set(this.pagesData, existingIndex, updatedPage);
    },

    handleSavedUpdates(payload = {}) {
      if (payload.page) {
        this.mergeUpdatedPage(payload.page);
      } else if (Array.isArray(payload.pages)) {
        payload.pages.forEach((page) => this.mergeUpdatedPage(page));
      } else {
        // Dialog didn't pass updated data — fall back to full refresh
        this.refreshPages();
      }

      if (payload.siteSettings) {
        this.siteSettingsData = payload.siteSettings;
      }
    },

    classifyTitle(page) {
      const length = getTableTitleDisplay(page, this.siteSettingsData, 'meta').charCount;
      const status = getStatusClass(page, length, 'title', this.validationSettingsData);
      if (status === 'k-meta-kit-status-error' || !status) return 'fix';
      if (isInheritedFromLanguage(page, 'metaTitle', this.siteSettingsData) && length) return 'review';
      if (status === 'k-meta-kit-status-warning') return 'review';
      return 'good';
    },

    classifyDescription(page) {
      const desc = getEffectiveDescription(page, 'meta', this.siteSettingsData);
      if (!desc) return 'fix';
      const status = getStatusClass(page, desc.length, 'description', this.validationSettingsData);
      if (status === 'k-meta-kit-status-error' || !status) return 'fix';

      if (
        isInheritedFromSite(page, 'metaDescription', this.siteSettingsData) ||
        isInheritedFromLanguage(page, 'metaDescription', this.siteSettingsData)
      ) {
        return 'review';
      }

      if (status === 'k-meta-kit-status-warning') return 'review';
      return 'good';
    },

    classifyOgImage(page) {
      if (page.hasOgImage) return 'good';
      if (this.siteSettingsData?.siteHasOgImage) return 'review';
      return 'fix';
    },

    classifyNoindex(page) {
      return page.robots && page.robots.includes('noindex') ? 'review' : 'good';
    },

    classifySlug(page) {
      if (page.id === 'site') return 'good';

      const slug = page.id.split('/').pop() || '';
      const wordCount = slug.split(/[-_]/).filter(Boolean).length;
      const length = slug.length;
      const numSlashes = page.id.split('/').length - 1;
      const cfg = getSlugValidationConfig(page, this.validationSettingsData);
      const avgWordLength = wordCount > 0 ? Math.ceil(length / wordCount) : length;
      const issues = getSlugValidationIssues({ numSlashes, wordCount, length, avgWordLength, cfg });

      if (issues.some(issue => issue.severity === 'error')) return 'fix';
      if (issues.some(issue => issue.severity === 'warning')) return 'review';
      return 'good';
    },

    async refreshPages() {
      this.isLoadingPages = true;
      try {
        const response = await this.$api.get('meta-kit/pages', {
          _ts: Date.now()
        });
        if (response.status === 'success') {
          this.pagesData = response.data;
          if (response.siteSettings) {
            this.siteSettingsData = response.siteSettings;
          }
          if (response.validationSettings) {
            this.validationSettingsData = response.validationSettings;
          }
        }
      } catch (error) {
        window.panel.notification.error('Failed to refresh pages');
      } finally {
        this.isLoadingPages = false;
      }
    },

    // Open the field-selection dialog for a single page's AI generation
    openSinglePageGenerate(pageId) {
      this.singleGeneratePageId = pageId;
      this.$refs.bulkGenerateDialog.open();
    },

    // Open the field-selection dialog for bulk (selected pages) generation
    generateAllDescriptions() {
      this.singleGeneratePageId = null;
      this.$refs.bulkGenerateDialog.open();
    },

    async performBulkGeneration(options) {
      if (!options.title && !options.description && !options.ogTitle && !options.ogDescription) {
        window.panel.notification.error('Please select at least one field to generate');
        return;
      }

      this.isGeneratingAll = true;

      // Single-page mode when triggered from the table row AI button
      const pageIds = this.singleGeneratePageId
        ? [this.singleGeneratePageId]
        : this.selectedPages;

      this.singleGeneratePageId = null;

      try {
        const response = await this.$api.post('meta-kit/generate-all', {
          generateTitle: options.title,
          generateDescription: options.description,
          generateOgTitle: options.ogTitle,
          generateOgDescription: options.ogDescription,
          pageIds
        });

        if (response.status === 'success') {
          const details = `Generated: ${response.generated || 0}, Skipped: ${response.skipped || 0}, Failed: ${response.failed || 0}`;
          window.panel.notification.success(`${response.message || 'Generation completed!'} ${details}`);
          await this.refreshPages();
        } else {
          window.panel.notification.error(response.message || 'Generation failed');
        }
      } catch (error) {
        let errorMessage = 'Failed to generate metadata';
        if (error.message) {
          errorMessage += `: ${error.message}`;
        } else if (error.error) {
          errorMessage += `: ${error.error}`;
        }
        window.panel.notification.error(errorMessage);
      } finally {
        this.isGeneratingAll = false;
        this.loadingProgress = '';
      }
    },

    async editSinglePageMetadata(pageId) {
      this.$refs.singlePageDialog.open(pageId);
    },

    reviewSinglePage(pageId, title = 'Page SEO Review') {
      this.$refs.reviewDialog.openPage(pageId, title);
    },

    reviewSelectedPages() {
      if (!this.selectedPages.length) return;
      this.$refs.reviewDialog.openSelection(
        this.selectedPages,
        `SEO Review for ${this.selectedPages.length} Selected Page${this.selectedPages.length > 1 ? 's' : ''}`
      );
    },

    reviewSite() {
      this.$refs.reviewDialog.openSite('Site SEO Review');
    },

    goToLanguage(langCode) {
      if (langCode === this.language) return;
      const baseUrl = window.location.origin + window.location.pathname.split('?')[0];
      window.location.href = baseUrl + '?language=' + langCode;
    },

    changePageSize(newSize) {
      this.pageSize = parseInt(newSize);
      this.currentPage = 1;
    },
    nextPage() {
      if (this.currentPage < this.totalPages) {
        this.currentPage++;
      }
    },
    previousPage() {
      if (this.currentPage > 1) {
        this.currentPage--;
      }
    },

    isPageSelected(pageId) {
      return this.selectedPages.includes(pageId);
    },
    togglePageSelection(pageId) {
      const index = this.selectedPages.indexOf(pageId);
      if (index > -1) {
        this.selectedPages.splice(index, 1);
      } else {
        this.selectedPages.push(pageId);
      }
    },
    toggleSelectAllCurrentPage() {
      this.selectedPages = toggleSelectAllOnPage(this.paginatedPages, this.selectedPages);
    },
    async showSelectedPagesDialog() {
      if (this.selectedPages.length === 0) return;
      this.$refs.allPagesDialog.open(this.selectedPages);
    }
  }
};
</script>
