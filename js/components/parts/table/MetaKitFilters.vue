<template>
  <div class="k-meta-kit-controls">
    <!-- Display: preferences (view, inherited values, page size), remembered per browser -->
    <div class="k-meta-kit-filter-dropdown k-meta-kit-display-dropdown">
      <button
        class="k-meta-kit-filter-button"
        :class="{ 'active': openMenu === 'display' }"
        @click="toggleMenu('display')"
      >
        <k-icon type="preview" />
        <span>{{ $t('meta-kit.display') }}</span>
        <k-icon :type="openMenu === 'display' ? 'angle-up' : 'angle-down'" />
      </button>

      <div v-if="openMenu === 'display'" class="k-meta-kit-filter-dropdown-content">
        <div class="k-meta-kit-filter-group">
          <div class="k-meta-kit-filter-group-title">{{ $t('meta-kit.view') }}</div>
          <div class="k-mk2-seg" role="radiogroup">
            <button v-for="option in VIEW_OPTIONS" :key="option" type="button" :class="{ 'is-on': viewMode === option }" @click="updateViewMode(option)">{{ $t('meta-kit.view.' + option) }}</button>
          </div>
        </div>
        <div class="k-meta-kit-filter-group">
          <div class="k-meta-kit-filter-group-title">{{ $t('meta-kit.inheritance') }}</div>
          <div class="k-mk2-seg" role="radiogroup">
            <button v-for="option in INHERITANCE_OPTIONS" :key="option" type="button" :class="{ 'is-on': inheritance === option }" @click="$emit('update:inheritance', option)">{{ $t('meta-kit.inheritance.' + option) }}</button>
          </div>
        </div>
        <div class="k-meta-kit-filter-group">
          <div class="k-meta-kit-filter-group-title">{{ $t('meta-kit.pagination.perPageLabel') }}</div>
          <div class="k-mk2-seg" role="radiogroup">
            <button v-for="option in PAGE_SIZES" :key="option" type="button" :class="{ 'is-on': pageSize === option }" @click="$emit('update:page-size', option)">{{ option === ALL_PAGES ? $t('meta-kit.pagination.all') : option }}</button>
          </div>
        </div>
        <div class="k-meta-kit-filter-group k-mk2-display-foot">{{ $t('meta-kit.display.remembered') }}</div>
      </div>
    </div>

    <!-- Sort: an icon in the box instead of a word in front of it -->
    <div class="k-meta-kit-sort-select">
      <label class="k-meta-kit-sort-icon" for="k-meta-kit-sort-mode" :title="$t('meta-kit.sort')"><k-icon type="order-alpha-asc" /></label>
      <select
        id="k-meta-kit-sort-mode"
        class="k-meta-kit-view-select-input"
        :value="sortBy"
        @change="$emit('update:sort-by', $event.target.value)"
        :title="$t('meta-kit.sort.choose')"
      >
        <option value="default">{{ $t('meta-kit.sort.default') }}</option>
        <option value="attention">{{ $t('meta-kit.sort.attention') }}</option>
        <option value="name-asc">{{ $t('meta-kit.sort.nameAsc') }}</option>
        <option value="name-desc">{{ $t('meta-kit.sort.nameDesc') }}</option>
        <option value="level-asc">{{ $t('meta-kit.sort.levelAsc') }}</option>
        <option value="level-desc">{{ $t('meta-kit.sort.levelDesc') }}</option>
        <option value="status">{{ $t('meta-kit.status') }}</option>
        <option value="template">{{ $t('meta-kit.template') }}</option>
      </select>
    </div>

    <div class="k-meta-kit-search-wrapper">
      <k-search-input
        icon="search"
        :value="searchQuery"
        @input="$emit('update:search-query', $event)"
        :placeholder="$t('meta-kit.search.placeholder')"
        class="k-meta-kit-search"
      />
      <button
        v-if="searchQuery"
        class="k-meta-kit-search-clear"
        @click="$emit('update:search-query', '')"
        :title="$t('meta-kit.search.clear')"
      >
        <k-icon type="cancel"/>
      </button>
    </div>

    <div class="k-meta-kit-filter-dropdown">
      <button
        class="k-meta-kit-filter-button"
        @click="toggleMenu('filters')"
        :class="{ 'active': openMenu === 'filters' || activeFilters.length > 0 }"
      >
        <k-icon type="filter" />
        <span>{{ $t('meta-kit.filters') }}</span>
        <span v-if="activeFilters.length > 0" class="k-meta-kit-filter-count">{{ activeFilters.length }}</span>
        <k-icon :type="openMenu === 'filters' ? 'angle-up' : 'angle-down'" />
      </button>

      <div v-if="openMenu === 'filters'" class="k-meta-kit-filter-dropdown-content">
        <div class="k-meta-kit-filter-group k-meta-kit-filter-group-grid">
          <div class="k-meta-kit-filter-group-title">{{ $t('meta-kit.filter.state') }}</div>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="good"
              :checked="isFilterActive('good')"
              @change="toggleFilter('good')"
            />
            <span>{{ $t('meta-kit.state.good') }}</span>
          </label>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="attention"
              :checked="isFilterActive('attention')"
              @change="toggleFilter('attention')"
            />
            <span>{{ $t('meta-kit.filter.attention') }}</span>
          </label>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="warning"
              :checked="isFilterActive('warning')"
              @change="toggleFilter('warning')"
            />
            <span>{{ $t('meta-kit.filter.warnings') }}</span>
          </label>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="error"
              :checked="isFilterActive('error')"
              @change="toggleFilter('error')"
            />
            <span>{{ $t('meta-kit.filter.fixes') }}</span>
          </label>
        </div>

        <div class="k-meta-kit-filter-group k-meta-kit-filter-group-grid">
          <div class="k-meta-kit-filter-group-title">{{ $t('meta-kit.filter.fields') }}</div>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="type-slug"
              :checked="isFilterActive('type-slug')"
              @change="toggleFilter('type-slug')"
            />
            <span>{{ $t('meta-kit.field.slug') }}</span>
          </label>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="type-title"
              :checked="isFilterActive('type-title')"
              @change="toggleFilter('type-title')"
            />
            <span>{{ $t('meta-kit.field.metaTitle') }}</span>
          </label>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="type-description"
              :checked="isFilterActive('type-description')"
              @change="toggleFilter('type-description')"
            />
            <span>{{ $t('meta-kit.field.metaDescription.short') }}</span>
          </label>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="type-og-title"
              :checked="isFilterActive('type-og-title')"
              @change="toggleFilter('type-og-title')"
            />
            <span>{{ $t('meta-kit.field.ogTitle') }}</span>
          </label>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="type-og-description"
              :checked="isFilterActive('type-og-description')"
              @change="toggleFilter('type-og-description')"
            />
            <span>{{ $t('meta-kit.field.ogDescription.short') }}</span>
          </label>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="type-og-image"
              :checked="isFilterActive('type-og-image')"
              @change="toggleFilter('type-og-image')"
            />
            <span>{{ $t('meta-kit.field.ogImage.short') }}</span>
          </label>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="type-noindex"
              :checked="isFilterActive('type-noindex')"
              @change="toggleFilter('type-noindex')"
            />
            <span>{{ $t('meta-kit.field.noindex.short') }}</span>
          </label>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="type-duplicates"
              :checked="isFilterActive('type-duplicates')"
              @change="toggleFilter('type-duplicates')"
            />
            <span>{{ $t('meta-kit.field.duplicates.short') }}</span>
          </label>
        </div>

        <div class="k-meta-kit-filter-group">
          <div class="k-meta-kit-filter-group-title">{{ $t('meta-kit.filter.metadata') }}</div>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="complete"
              :checked="isFilterActive('complete')"
              @change="toggleFilter('complete')"
            />
            <span>{{ $t('meta-kit.filter.complete') }}</span>
          </label>
        </div>

        <div class="k-meta-kit-filter-group">
          <div class="k-meta-kit-filter-group-title">{{ $t('meta-kit.status') }}</div>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="listed"
              :checked="isFilterActive('listed')"
              @change="toggleFilter('listed')"
            />
            <span>{{ $t('meta-kit.status.listed') }}</span>
          </label>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="unlisted"
              :checked="isFilterActive('unlisted')"
              @change="toggleFilter('unlisted')"
            />
            <span>{{ $t('meta-kit.status.unlisted') }}</span>
          </label>
          <label class="k-meta-kit-filter-option">
            <input
              type="checkbox"
              value="drafts"
              :checked="isFilterActive('drafts')"
              @change="toggleFilter('drafts')"
            />
            <span>{{ $t('meta-kit.status.drafts') }}</span>
          </label>
        </div>

        <div v-if="activeFilters.length > 0 || searchQuery" class="k-meta-kit-filter-actions">
          <button @click="clearFilters" class="k-meta-kit-filter-clear">
            {{ $t('meta-kit.filter.clear') }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
const VIEW_OPTIONS = ['count', 'meta', 'og'];
const INHERITANCE_OPTIONS = ['none', 'dimmed', 'marked'];
const ALL_PAGES = 99999;
const PAGE_SIZES = [10, 25, 50, 100, ALL_PAGES];

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
    sortBy: {
      type: String,
      default: 'default'
    },
    // How inherited values appear in the table (none | dimmed | marked)
    inheritance: {
      type: String,
      default: 'dimmed',
      validator: value => INHERITANCE_OPTIONS.includes(value)
    },
    pageSize: {
      type: Number,
      default: 25
    }
  },
  data() {
    return {
      VIEW_OPTIONS,
      INHERITANCE_OPTIONS,
      PAGE_SIZES,
      ALL_PAGES,
      // 'display' | 'filters' | null
      openMenu: null
    };
  },
  computed: {
    viewMode() {
      if (!this.showPreview) {
        return 'count';
      }

      return this.previewMode === 'og' ? 'og' : 'meta';
    }
  },
  methods: {
    updateViewMode(mode) {
      if (mode === 'count') {
        this.$emit('update:show-preview', false);
        return;
      }

      this.$emit('update:preview-mode', mode);
      this.$emit('update:show-preview', true);
    },
    toggleMenu(menu) {
      this.openMenu = this.openMenu === menu ? null : menu;
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

      this.$emit('update:active-filters', Array.from(filters));
    },
    // Clears the filters and the search; the display preferences stay
    clearFilters() {
      this.$emit('update:active-filters', []);
      this.$emit('update:search-query', '');
      this.openMenu = null;
    }
  },
  mounted() {
    this._outsideClickHandler = (e) => {
      if (!this.$el.contains(e.target)) {
        this.openMenu = null;
      }
    };
    document.addEventListener('click', this._outsideClickHandler);
  },
  beforeDestroy() {
    document.removeEventListener('click', this._outsideClickHandler);
  }
};
</script>
