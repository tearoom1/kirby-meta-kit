<template>
  <k-dialog ref="dialog" size="huge" cancelButton="Close" submitButton="">
    <k-headline>Edit Selected Pages ({{ pages.length }})</k-headline>

    <div v-if="isLoading" class="k-meta-kit-loading">
      <k-icon class="k-meta-kit-spinner" type="loader"/>
      <span>Loading pages...</span>
    </div>

    <div v-else-if="pages.length > 0">
      <!-- Tabs -->
      <div class="k-meta-kit-tabs">
        <k-button
          :theme="activeTab === 'meta' ? 'positive' : ''"
          @click="activeTab = 'meta'"
          size="sm"
        >
          Meta Tags
        </k-button>
        <k-button
          :theme="activeTab === 'og' ? 'positive' : ''"
          @click="activeTab = 'og'"
          size="sm"
        >
          Social Media (OG)
        </k-button>
      </div>

      <!-- Meta Tab -->
      <div v-if="activeTab === 'meta'" class="k-meta-kit-dialog-table-wrapper">
        <div v-for="page in pages" :key="`meta-${page.id}`" class="k-meta-kit-dialog-table-page">
          <div class="k-meta-kit-dialog-page-info">
            <a :href="page.panelUrl" class="k-link">{{ page.title }}</a>
            <a :href="page.panelUrl" class="k-link k-meta-kit-page-id">{{ page.id }}</a>
          </div>
          <slot name="meta" :page="page"></slot>
        </div>
      </div>

      <!-- OG Tab -->
      <div v-if="activeTab === 'og'" class="k-meta-kit-dialog-table-wrapper">
        <div v-for="page in pages" :key="`og-${page.id}`" class="k-meta-kit-dialog-table-page">
          <div class="k-meta-kit-dialog-page-info">
            <a :href="page.panelUrl" class="k-link">{{ page.title }}</a>
            <a :href="page.panelUrl" class="k-link k-meta-kit-page-id">{{ page.id }}</a>
          </div>
          <slot name="og" :page="page"></slot>
        </div>
      </div>
    </div>

    <div v-else class="k-meta-kit-empty">
      <k-icon type="check"/>
      <p>No pages found!</p>
    </div>
  </k-dialog>
</template>

<script>
export default {
  props: {
    pages: {
      type: Array,
      default: () => []
    },
    isLoading: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      activeTab: 'meta'
    };
  },
  methods: {
    open() {
      this.activeTab = 'meta';
      this.$refs.dialog.open();
    },
    close() {
      this.$refs.dialog.close();
    }
  }
};
</script>
