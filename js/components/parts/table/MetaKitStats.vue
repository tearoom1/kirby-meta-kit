<template>
  <div class="k-meta-kit-stats">
    <div class="k-meta-kit-stats-card">
      <h3>Total Pages</h3>
      <p>{{ filteredCount }}<span v-if="searchActive" class="k-meta-kit-stats-total"> / {{ totalCount }}</span></p>
    </div>
    <div class="k-meta-kit-stats-card">
      <h3>With Description</h3>
      <p>{{ filteredWithDescription }}<span v-if="searchActive" class="k-meta-kit-stats-total"> / {{ totalWithDescription }}</span></p>
    </div>
    <div class="k-meta-kit-stats-card">
      <h3>With OG Image</h3>
      <p>{{ filteredWithImage }}<span v-if="searchActive" class="k-meta-kit-stats-total"> / {{ totalWithImage }}</span></p>
    </div>
    <div class="k-meta-kit-stats-card">
      <h3>No Index</h3>
      <p>{{ filteredNoIndex }}<span v-if="searchActive" class="k-meta-kit-stats-total"> / {{ totalNoIndex }}</span></p>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    filteredCount: {
      type: Number,
      required: true
    },
    totalCount: {
      type: Number,
      required: true
    },
    filteredWithDescription: {
      type: Number,
      required: true
    },
    totalWithDescription: {
      type: Number,
      required: true
    },
    filteredWithImage: {
      type: Number,
      required: true
    },
    totalWithImage: {
      type: Number,
      required: true
    },
    filteredNoIndex: {
      type: Number,
      required: true
    },
    totalNoIndex: {
      type: Number,
      required: true
    },
    searchActive: {
      type: Boolean,
      default: false
    }
  }
};
</script>
