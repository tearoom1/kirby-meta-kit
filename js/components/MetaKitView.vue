<template>
  <k-panel-inside class="k-meta-kit-view">
    <!-- Top Bar: Language Switcher + Sponsor (right-aligned) -->
    <div class="k-meta-kit-topbar">
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

      <div class="k-meta-kit-sponsor">
        <k-button
          class="k-meta-kit-sponsor-button"
          icon="heart"
          variant="filled"
          size="sm"
          theme="empty"
          title="Support Meta Kit"
          aria-label="Support Meta Kit"
          @click="$refs.sponsorDropdown.toggle()"
        />
        <k-dropdown-content
          ref="sponsorDropdown"
          align-x="end"
        >
          <div class="k-meta-kit-sponsor-text">
            {{ sponsorText }}
          </div>
          <k-dropdown-item
            icon="heart"
            link="https://github.com/sponsors/tearoom1"
            target="_blank"
          >
            Sponsor on GitHub
          </k-dropdown-item>
          <k-dropdown-item
            icon="heart"
            link="https://buymeacoffee.com/tearoom1"
            target="_blank"
          >
            Buy Me a Coffee
          </k-dropdown-item>
        </k-dropdown-content>
      </div>
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
      :review-enabled="reviewEnabled"
      :is-generating="isGeneratingAll"
      @edit-selected="showSelectedPagesDialog"
      @generate-missing="generateAllDescriptions"
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
      :review-enabled="reviewEnabled"
      :site-settings="siteSettingsData"
      :validation-settings="validationSettingsData"
      :duplicates="duplicates"
      :all-pages="pagesData"
      @toggle-select-all="toggleSelectAllCurrentPage"
      @toggle-page="togglePageSelection"
      @review-page="reviewSinglePage"
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

    <!-- Review generated suggestions before saving -->
    <meta-kit-suggestions-dialog
      ref="suggestionsDialog"
      @save="saveSuggestions"
    />

    <!-- Loading Overlay -->
    <div v-if="isGeneratingAll" class="k-meta-kit-loading-overlay">
      <div class="k-meta-kit-loading-content">
        <div class="k-meta-kit-loading-spinner">
          <k-icon type="loader" />
        </div>
        <div class="k-meta-kit-loading-text">{{ loadingLabel }}</div>
        <div v-if="loadingProgress" class="k-meta-kit-loading-progress">
          {{ loadingProgress }}
        </div>
        <k-button
          v-if="canCancelGeneration"
          icon="cancel"
          variant="filled"
          size="sm"
          :disabled="cancelRequested"
          @click="cancelRequested = true"
        >
          {{ cancelRequested ? 'Stopping after the current field…' : 'Cancel' }}
        </k-button>
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
import MetaKitSuggestionsDialog from './parts/edit/MetaKitSuggestionsDialog.vue';
import {
  GENERATION_FIELDS,
  planGeneration,
  runGeneration,
  applySuggestions,
  generateFieldSuggestion
} from '../composables/bulkGeneration.js';
import { applySingleFieldUpdate } from '../composables/saveFields.js';
import {
  filterPages,
  sortPages,
  paginatePages,
  getTotalPages,
  isAllCurrentPageSelected as isAllSelectedOnPage,
  toggleSelectAllCurrentPage as toggleSelectAllOnPage,
  classifyPageField,
  findDuplicates
} from '../composables/panelState.js';

export default {
  components: {
    MetaKitTable,
    MetaKitBulkGenerateDialog,
    MetaKitSinglePageDialog,
    MetaKitBulkEditDialog,
    MetaKitReviewDialog,
    MetaKitSuggestionsDialog,
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
    reviewEnabled: {
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
      loadingProgress: '',
      loadingLabel: '',
      canCancelGeneration: false,
      cancelRequested: false
    };
  },
  computed: {
    sponsorText() {
      const language = this.panelLanguage();

      if (language.startsWith('de')) {
        return 'Dieses Plugin entsteht mit viel Liebe und laufendem Aufwand. Wenn es dir Zeit spart, hilft eine kleine Spende, Wartung und Weiterentwicklung möglich zu machen.';
      }

      return 'This plugin is built with care and ongoing effort. If it saves you time, a small donation helps keep maintenance and future improvements going.';
    },

    duplicates() {
      return findDuplicates(this.pagesData);
    },
    classifierContext() {
      return {
        siteSettings: this.siteSettingsData,
        validationSettings: this.validationSettingsData,
        duplicates: this.duplicates
      };
    },
    filteredPages() {
      const filtered = filterPages(this.pagesData, this.activeFilters, this.searchQuery, this.classifierContext);
      return sortPages(filtered, this.sortBy, this.classifierContext);
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
        this.buildStatusBuckets(this.pagesData, this.filteredPages, (page) => this.classifyForStats(page, 'slug'), 'Slug', {
          detailLines: [
            'Good = slug is valid',
            'Review = slug has warnings',
            'Fix = slug has errors'
          ]
        }),
        this.buildStatusBuckets(this.pagesData, this.filteredPages, (page) => this.classifyForStats(page, 'title'), 'Meta Title', {
          detailLines: [
            'Good = valid title, including page-title fallback',
            'Review = title length warning only',
            'Fix = missing or invalid title'
          ]
        }),
        this.buildStatusBuckets(this.pagesData, this.filteredPages, (page) => this.classifyForStats(page, 'description'), 'Meta Description', {
          detailLines: [
            'Good = valid unique description',
            'Review = inherited from site or length warning',
            'Fix = missing or invalid description'
          ]
        }),
        this.buildStatusBuckets(this.pagesData, this.filteredPages, (page) => this.classifyForStats(page, 'ogImage'), 'OG Image', {
          detailLines: [
            'Good = page-specific OG image',
            'Review = inherited from site'
          ]
        }),
        this.buildStatusBuckets(this.pagesData, this.filteredPages, (page) => this.classifyForStats(page, 'duplicates'), 'Duplicates', {
          attentionStatuses: ['review'],
          detailLines: [
            'Good = own title and description are unique',
            'Review = same meta title or description as another page'
          ]
        }),
        this.buildStatusBuckets(this.pagesData, this.filteredPages, (page) => this.classifyForStats(page, 'noindex'), 'Noindex Pages', {
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
    panelLanguage() {
      const panel = window.panel || {};
      const panelLanguage = panel.language && panel.language.code;
      const viewLanguage = panel.view && panel.view.props && panel.view.props.language;
      const browserLanguage = window.navigator && window.navigator.language;

      return String(viewLanguage || panelLanguage || browserLanguage || 'en').toLowerCase();
    },

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

    // Stats cards use the shared classifier, in their own wording
    classifyForStats(page, field) {
      const level = classifyPageField(page, field, this.classifierContext);
      return { good: 'good', warning: 'review', error: 'fix' }[level];
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
      if (!GENERATION_FIELDS.some(({ option }) => options[option])) {
        window.panel.notification.error('Please select at least one field to generate');
        return;
      }

      // Single-page mode when triggered from the table row AI button
      const pageIds = this.singleGeneratePageId
        ? [this.singleGeneratePageId]
        : this.selectedPages;
      this.singleGeneratePageId = null;

      const pages = this.pagesData.filter((page) => pageIds.includes(page.id));
      const jobs = planGeneration(pages, options);

      if (jobs.length === 0) {
        window.panel.notification.success('Nothing to generate: the selected pages already have these fields.');
        return;
      }

      this.isGeneratingAll = true;
      this.loadingLabel = 'Generating metadata with AI...';
      this.canCancelGeneration = true;
      this.cancelRequested = false;

      let result;
      try {
        result = await runGeneration(jobs, {
          generate: (job) => generateFieldSuggestion(this.$api, job, this.language || null),
          onProgress: ({ done, total, job }) => {
            this.loadingProgress = job ? `${done + 1} of ${total} · ${job.pageTitle} – ${job.label}` : '';
          },
          isCancelled: () => this.cancelRequested
        });
      } finally {
        this.isGeneratingAll = false;
        this.canCancelGeneration = false;
        this.loadingProgress = '';
      }

      if (options.review && result.suggestions.length > 0) {
        this.$refs.suggestionsDialog.open(result);
        return;
      }

      await this.saveSuggestions(result.suggestions, result.errors);
    },

    async saveSuggestions(suggestions, generationErrors = []) {
      this.isGeneratingAll = true;
      this.loadingLabel = 'Saving metadata...';

      let result;
      try {
        result = await applySuggestions(suggestions, (suggestion) => applySingleFieldUpdate(this.$api, {
          pageId: suggestion.pageId,
          fieldName: suggestion.field,
          value: suggestion.value
        }));
      } finally {
        this.isGeneratingAll = false;
      }

      const failed = [...generationErrors, ...result.errors];
      const summary = `Saved ${result.saved.length} field(s)`;

      if (failed.length > 0) {
        const first = failed[0];
        window.panel.notification.error(
          `${summary}, ${failed.length} failed. ${first.pageTitle} – ${first.label}: ${first.message}`
        );
      } else {
        window.panel.notification.success(summary);
      }

      await this.refreshPages();
    },

    async editSinglePageMetadata(pageId) {
      this.$refs.singlePageDialog.open(pageId);
    },

    reviewSinglePage(pageId, title = 'Page Content Review') {
      this.$refs.reviewDialog.openPage(pageId, title);
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
