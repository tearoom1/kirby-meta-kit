<template>
  <div class="k-meta-kit-controls">
    <k-button
      size="sm"
      @click="$emit('update:show-preview', !showPreview)"
      :title="showPreview ? 'Show character counts' : 'Show preview text'"
    >
      {{ showPreview ? 'Count' : 'Preview' }}
    </k-button>

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

    <select
      class="k-meta-kit-metadata-filter"
      :value="metadataFilter"
      @change="$emit('update:metadata-filter', $event.target.value)"
    >
      <option value="all">All Pages</option>
      <option value="missing-title">Missing Title</option>
      <option value="missing-description">Missing Description</option>
      <option value="missing-image">Missing OG Image</option>
      <option value="complete">Complete Metadata</option>
    </select>

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
    searchQuery: {
      type: String,
      default: ''
    },
    metadataFilter: {
      type: String,
      default: 'all'
    },
    pageSize: {
      type: Number,
      default: 25
    },
    pageSizeOptions: {
      type: Array,
      default: () => [
        {value: 25, text: '25 per page'},
        {value: 50, text: '50 per page'},
        {value: 100, text: '100 per page'},
        {value: 99999, text: 'All'}
      ]
    }
  }
};
</script>
