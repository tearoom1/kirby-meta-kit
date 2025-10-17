<template>
  <section class="k-seo-preview-section">
    <header class="k-section-header">
      <h2 class="k-headline">{{ label || 'SEO Preview' }}</h2>
    </header>

    <div v-if="meta" class="k-seo-previews">
      <!-- Google Preview -->
      <div class="k-seo-preview k-seo-preview--google">
        <h3 class="k-seo-preview__title">Google Search Preview</h3>
        <div class="k-seo-preview__content">
          <div class="k-google-preview">
            <cite class="k-google-preview__url">{{ displayUrl(meta.url) }}</cite>
            <h3 class="k-google-preview__title">{{ meta.title || 'Page Title' }}</h3>
            <p class="k-google-preview__description">{{ meta.description || 'No description available' }}</p>
          </div>
        </div>
      </div>

      <!-- Twitter Preview -->
      <div class="k-seo-preview k-seo-preview--twitter">
        <h3 class="k-seo-preview__title">Twitter Card Preview</h3>
        <div class="k-seo-preview__content">
          <div class="k-twitter-preview">
            <div v-if="meta.ogImage" class="k-twitter-preview__image" :style="{ backgroundImage: 'url(' + meta.ogImage + ')' }"></div>
            <div class="k-twitter-preview__body">
              <cite class="k-twitter-preview__url">{{ displayUrl(meta.url) }}</cite>
              <h4 class="k-twitter-preview__title">{{ meta.ogTitle || meta.title || 'Page Title' }}</h4>
              <p class="k-twitter-preview__description">{{ truncate(meta.ogDescription || meta.description, 140) || 'No description' }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Facebook Preview -->
      <div class="k-seo-preview k-seo-preview--facebook">
        <h3 class="k-seo-preview__title">Facebook Share Preview</h3>
        <div class="k-seo-preview__content">
          <div class="k-facebook-preview">
            <div v-if="meta.ogImage" class="k-facebook-preview__image" :style="{ backgroundImage: 'url(' + meta.ogImage + ')' }"></div>
            <div class="k-facebook-preview__body">
              <cite class="k-facebook-preview__url">{{ displayUrl(meta.url).toUpperCase() }}</cite>
              <h4 class="k-facebook-preview__title">{{ meta.ogTitle || meta.title || 'Page Title' }}</h4>
              <p class="k-facebook-preview__description">{{ truncate(meta.ogDescription || meta.description, 120) || 'No description' }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div v-else class="k-seo-preview-loading">
      Loading preview...
    </div>
  </section>
</template>

<script>
export default {
  props: {
    label: String,
    parent: String,
    name: String
  },
  data() {
    return {
      meta: null,
      updateTimeout: null
    }
  },
  async mounted() {
    console.log('SEO Preview Section Mounted');
    await this.load();
    
    // Listen to input events on the entire document (catches all field changes)
    document.addEventListener('input', this.handleInputChange, true);
    document.addEventListener('change', this.handleInputChange, true);
  },
  beforeDestroy() {
    // Clean up event listeners
    document.removeEventListener('input', this.handleInputChange, true);
    document.removeEventListener('change', this.handleInputChange, true);
  },
  methods: {
    handleInputChange(event) {
      // Update on any input change
      this.handleUpdate();
    },
    handleUpdate() {
      // Debounce updates to avoid too many API calls
      clearTimeout(this.updateTimeout);
      this.updateTimeout = setTimeout(() => {
        console.log('Updating preview...');
        this.load();
      }, 1000);
    },
    async load() {
      try {
        const response = await this.$api.get(this.parent + '/sections/' + this.name);
        
        // Try different possible locations
        if (response.meta) {
          this.meta = response.meta;
        } else if (response.data && response.data.meta) {
          this.meta = response.data.meta;
        } else {
          console.warn('No meta found in response');
        }
      } catch (error) {
        console.error('Error loading section:', error);
      }
    },
    truncate(text, length) {
      if (!text) return '';
      return text.length > length ? text.substring(0, length) + '...' : text;
    },
    displayUrl(url) {
      if (!url) return 'example.com';
      try {
        const urlObj = new URL(url);
        return urlObj.hostname + urlObj.pathname;
      } catch {
        return url;
      }
    }
  }
}
</script>
