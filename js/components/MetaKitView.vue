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
      :filtered-with-title="filteredPagesWithTitle"
      :total-with-title="pagesWithTitle"
      :filtered-with-description="filteredPagesWithDescription"
      :total-with-description="pagesWithDescription"
      :filtered-with-image="filteredPagesWithOgImage"
      :total-with-image="pagesWithOgImage"
      :filtered-no-index="filteredPagesNoIndex"
      :total-no-index="pagesNoIndex"
      :search-active="!!(searchQuery || activeFilters.length)"
    />

    <!-- Actions & Filters -->
    <meta-kit-actions
      :selected-count="selectedPages.length"
      :ai-enabled="aiEnabled"
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
      :site-settings="siteSettings"
      :ai-enabled="aiEnabled"
      @saved="refreshPages"
    />

    <!-- Single Page Edit Dialog -->
    <meta-kit-single-page-dialog
      ref="singlePageDialog"
      :api="$api"
      :site-settings="siteSettings"
      :ai-enabled="aiEnabled"
      @saved="refreshPages"
    />

    <!-- Bulk Generation Dialog (used for both bulk and single-page AI generate) -->
    <meta-kit-bulk-generate-dialog
      ref="bulkGenerateDialog"
      :selected-count="singleGeneratePageId ? 1 : selectedPages.length"
      @generate="performBulkGeneration"
    />

    <!-- Loading Overlay -->
    <div v-if="isGeneratingAll || isLoadingPages" class="k-meta-kit-loading-overlay">
      <div class="k-meta-kit-loading-content">
        <div class="k-meta-kit-loading-spinner">
          <k-icon type="loader" />
        </div>
        <div class="k-meta-kit-loading-text">
          <template v-if="isGeneratingAll">Generating metadata with AI...</template>
          <template v-else-if="isLoadingPages">Refreshing pages...</template>
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
import {
  filterPages,
  paginatePages,
  getTotalPages,
  isAllCurrentPageSelected as isAllSelectedOnPage,
  toggleSelectAllCurrentPage as toggleSelectAllOnPage
} from '../composables/panelState.js';

export default {
  components: {
    MetaKitTable,
    MetaKitBulkGenerateDialog,
    MetaKitSinglePageDialog,
    MetaKitBulkEditDialog,
    MetaKitStats,
    MetaKitFilters,
    MetaKitActions
  },
  provide() {
    return {
      siteSettings: this.siteSettings
    };
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
      validationSettingsData: this.validationSettings || {},

      // Single-page AI generate: null = bulk mode, string = single page ID
      singleGeneratePageId: null,

      // Pagination & Selection
      selectedPages: [],
      currentPage: 1,
      pageSize: 25,
      pageSizeOptions: [
        {value: 25, text: '25/page'},
        {value: 50, text: '50/page'},
        {value: 100, text: '100/page'},
        {value: 99999, text: 'All'}
      ],
      searchQuery: '',
      activeFilters: [],
      showPreviewInTable: false,
      previewMode: 'meta',
      loadingProgress: ''
    };
  },
  computed: {
    filteredPages() {
      return filterPages(this.pagesData, this.activeFilters, this.searchQuery);
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
    pagesWithTitle() {
      return this.pagesData.filter(p => p.hasMetaTitle).length;
    },
    pagesWithDescription() {
      return this.pagesData.filter(p => p.hasMetaDescription).length;
    },
    pagesWithOgImage() {
      return this.pagesData.filter(p => p.hasOgImage).length;
    },
    pagesNoIndex() {
      return this.pagesData.filter(p => p.robots && p.robots.includes('noindex')).length;
    },
    filteredPagesWithTitle() {
      return this.filteredPages.filter(p => p.hasMetaTitle).length;
    },
    filteredPagesWithDescription() {
      return this.filteredPages.filter(p => p.hasMetaDescription).length;
    },
    filteredPagesWithOgImage() {
      return this.filteredPages.filter(p => p.hasOgImage).length;
    },
    filteredPagesNoIndex() {
      return this.filteredPages.filter(p => p.robots && p.robots.includes('noindex')).length;
    }
  },
  watch: {
    searchQuery() {
      this.currentPage = 1;
    },
    activeFilters() {
      this.currentPage = 1;
    }
  },
  methods: {
    async refreshPages() {
      this.isLoadingPages = true;
      try {
        const response = await this.$api.get('meta-kit/pages');
        if (response.status === 'success') {
          this.pagesData = response.data;
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
        console.error('Generation error details:', error);
      } finally {
        this.isGeneratingAll = false;
        this.loadingProgress = '';
      }
    },

    async editSinglePageMetadata(pageId) {
      this.$refs.singlePageDialog.open(pageId);
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
