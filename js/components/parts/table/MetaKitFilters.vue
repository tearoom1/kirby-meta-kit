<template>
  <div class="k-meta-kit-controls">
    <k-button-group>
      <k-button v-if="showPreview"
        size="sm"
        :theme="previewMode === 'meta' ? 'positive' : ''"
        @click="$emit('update:preview-mode', 'meta')"
        title="Show meta title and description"
      >
        Meta
      </k-button>
      <k-button v-if="showPreview"
        size="sm"
        :theme="previewMode === 'og' ? 'positive' : ''"
        @click="$emit('update:preview-mode', 'og')"
        title="Show OG title and description"
      >
        OG
      </k-button>
      <k-button
        size="sm"
        @click="$emit('update:show-preview', !showPreview)"
        :title="showPreview ? 'Show character counts' : 'Show preview text'"
      >
        {{ showPreview ? 'Overview' : 'Preview' }}
      </k-button>
    </k-button-group>

    <div class="k-meta-kit-search-wrapper">
      <k-search-input
        icon="search"
        :value="searchQuery"
        @input="$emit('update:search-query', $event)"
        placeholder="Filter pages..."
        class="k-meta-kit-search"
      />
      <button
        v-if="searchQuery"
        class="k-meta-kit-search-clear"
        @click="$emit('update:search-query', '')"
        title="Clear search"
      >
        <k-icon type="cancel"/>
      </button>
    </div>

    <div class="k-meta-kit-filter-dropdown">
      <button
        class="k-meta-kit-filter-button"
        @click="toggleFilterDropdown"
        :class="{ 'active': isDropdownOpen || activeFilters.length > 0 }"
      >
        <k-icon type="filter" />
        <span>Filters</span>
        <span v-if="activeFilters.length > 0" class="k-meta-kit-filter-count">{{ activeFilters.length }}</span>
        <k-icon :type="isDropdownOpen ? 'angle-up' : 'angle-down'" />
      </button>

      <div v-if="isDropdownOpen" class="k-meta-kit-filter-dropdown-content">
        <div class="k-meta-kit-filter-group">
          <div class="k-meta-kit-filter-group-title">State</div>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="good"
              :checked="isFilterActive('good')"
              @change="toggleFilter('good')"
            />
            <span>Good</span>
          </label>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="attention"
              :checked="isFilterActive('attention')"
              @change="toggleFilter('attention')"
            />
            <span>Needs Attention</span>
          </label>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="warning"
              :checked="isFilterActive('warning')"
              @change="toggleFilter('warning')"
            />
            <span>Warnings</span>
          </label>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="error"
              :checked="isFilterActive('error')"
              @change="toggleFilter('error')"
            />
            <span>Fixes</span>
          </label>
        </div>

        <div class="k-meta-kit-filter-group">
          <div class="k-meta-kit-filter-group-title">Fields</div>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="type-slug"
              :checked="isFilterActive('type-slug')"
              @change="toggleFilter('type-slug')"
            />
            <span>Slug</span>
          </label>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="type-title"
              :checked="isFilterActive('type-title')"
              @change="toggleFilter('type-title')"
            />
            <span>Meta Title</span>
          </label>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="type-description"
              :checked="isFilterActive('type-description')"
              @change="toggleFilter('type-description')"
            />
            <span>Meta Description</span>
          </label>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="type-og-title"
              :checked="isFilterActive('type-og-title')"
              @change="toggleFilter('type-og-title')"
            />
            <span>OG Title</span>
          </label>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="type-og-description"
              :checked="isFilterActive('type-og-description')"
              @change="toggleFilter('type-og-description')"
            />
            <span>OG Description</span>
          </label>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="type-og-image"
              :checked="isFilterActive('type-og-image')"
              @change="toggleFilter('type-og-image')"
            />
            <span>OG Image</span>
          </label>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="type-noindex"
              :checked="isFilterActive('type-noindex')"
              @change="toggleFilter('type-noindex')"
            />
            <span>Noindex</span>
          </label>
        </div>

        <div class="k-meta-kit-filter-group">
          <div class="k-meta-kit-filter-group-title">Metadata</div>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="complete"
              :checked="isFilterActive('complete')"
              @change="toggleFilter('complete')"
            />
            <span>Complete Metadata</span>
          </label>
        </div>

        <div class="k-meta-kit-filter-group">
          <div class="k-meta-kit-filter-group-title">Status</div>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="listed"
              :checked="isFilterActive('listed')"
              @change="toggleFilter('listed')"
            />
            <span>Listed</span>
          </label>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="unlisted"
              :checked="isFilterActive('unlisted')"
              @change="toggleFilter('unlisted')"
            />
            <span>Unlisted</span>
          </label>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="drafts"
              :checked="isFilterActive('drafts')"
              @change="toggleFilter('drafts')"
            />
            <span>Drafts</span>
          </label>
        </div>

        <div v-if="activeFilters.length > 0" class="k-meta-kit-filter-actions">
          <button @click="clearFilters" class="k-meta-kit-filter-clear">
            Clear all
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
const STATE_FILTERS = new Set(['attention', 'good', 'warning', 'error']);
const TYPE_FILTERS = new Set([
  'type-slug',
  'type-title',
  'type-description',
  'type-og-title',
  'type-og-description',
  'type-og-image',
  'type-noindex'
]);

export default {
  props: {
    showPreview: {
      type: Boolean,
      default: false
    },
    previewMode: {
      type: String,
      default: 'meta',
      validator: value => ['meta', 'og'].includes(value)
    },
    searchQuery: {
      type: String,
      default: ''
    },
    activeFilters: {
      type: Array,
      default: () => []
    },
  },
  data() {
    return {
      isDropdownOpen: false
    };
  },
  methods: {
    toggleFilterDropdown() {
      this.isDropdownOpen = !this.isDropdownOpen;
    },
    isFilterActive(filter) {
      if (filter === 'attention') {
        return this.activeFilters.includes('warning') && this.activeFilters.includes('error');
      }
      return this.activeFilters.includes(filter);
    },
    toggleFilter(filter) {
      const filters = new Set(this.activeFilters.filter((activeFilter) => activeFilter !== 'attention'));

      if (filter === 'attention') {
        if (filters.has('warning') && filters.has('error')) {
          filters.delete('warning');
          filters.delete('error');
        } else {
          filters.add('warning');
          filters.add('error');
        }
      } else {
        if (filters.has(filter)) {
          filters.delete(filter);
        } else {
          filters.add(filter);
        }
      }

      const nextFilters = Array.from(filters);
      const hasStateFilter = nextFilters.some((activeFilter) => STATE_FILTERS.has(activeFilter));
      const typeFilterCount = nextFilters.filter((activeFilter) => TYPE_FILTERS.has(activeFilter)).length;

      if (hasStateFilter && typeFilterCount === 0) {
        nextFilters.push('type-description');
      }

      this.$emit('update:active-filters', nextFilters);
    },
    clearFilters() {
      this.$emit('update:active-filters', []);
      this.isDropdownOpen = false;
    }
  },
  mounted() {
    this._outsideClickHandler = (e) => {
      if (!this.$el.contains(e.target)) {
        this.isDropdownOpen = false;
      }
    };
    document.addEventListener('click', this._outsideClickHandler);
  },
  beforeDestroy() {
    document.removeEventListener('click', this._outsideClickHandler);
  }
};
</script>
