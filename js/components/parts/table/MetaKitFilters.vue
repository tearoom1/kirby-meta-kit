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
          <div class="k-meta-kit-filter-group-title">Metadata</div>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="missing-title"
              :checked="isFilterActive('missing-title')"
              @change="toggleFilter('missing-title')"
            />
            <span>Missing Title</span>
          </label>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="missing-description"
              :checked="isFilterActive('missing-description')"
              @change="toggleFilter('missing-description')"
            />
            <span>Missing Description</span>
          </label>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="missing-og-title"
              :checked="isFilterActive('missing-og-title')"
              @change="toggleFilter('missing-og-title')"
            />
            <span>Missing OG Title</span>
          </label>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="missing-og-description"
              :checked="isFilterActive('missing-og-description')"
              @change="toggleFilter('missing-og-description')"
            />
            <span>Missing OG Description</span>
          </label>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="missing-og-image"
              :checked="isFilterActive('missing-og-image')"
              @change="toggleFilter('missing-og-image')"
            />
            <span>Missing OG Image</span>
          </label>
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

    <select
      class="k-meta-kit-pagesize-select"
      :value="pageSize"
      @change="$emit('change-page-size', $event.target.value)"
    >
      <option v-for="option in pageSizeOptions" :key="option.value" :value="option.value">
        {{ option.text }}
      </option>
    </select>
  </div>
</template>

<script>
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
    pageSize: {
      type: Number,
      default: 25
    },
    pageSizeOptions: {
      type: Array,
      default: () => [
        {value: 25, text: '25/page'},
        {value: 50, text: '50/page'},
        {value: 100, text: '100/page'},
        {value: 99999, text: 'All'}
      ]
    }
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
      return this.activeFilters.includes(filter);
    },
    toggleFilter(filter) {
      const filters = [...this.activeFilters];
      const index = filters.indexOf(filter);

      if (index > -1) {
        filters.splice(index, 1);
      } else {
        filters.push(filter);
      }

      this.$emit('update:active-filters', filters);
    },
    clearFilters() {
      this.$emit('update:active-filters', []);
      this.isDropdownOpen = false;
    }
  },
  mounted() {
    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
      if (!this.$el.contains(e.target)) {
        this.isDropdownOpen = false;
      }
    });
  }
};
</script>
