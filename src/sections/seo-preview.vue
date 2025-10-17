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

      <!-- Share Preview -->
      <div class="k-seo-preview k-seo-preview--twitter">
        <h3 class="k-seo-preview__title">Share / Card Preview</h3>
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
      updateTimeout: null,
      siteName: null,
      separator: '|'
    }
  },
  async mounted() {
    console.log('SEO Preview Section Mounted');
    await this.load();

    // Listen to input events on the entire document (catches all field changes)
    document.addEventListener('input', this.handleInputChange, true);
    document.addEventListener('change', this.handleInputChange, true);
    // Also listen for custom SEO field updates from AI generator
    document.addEventListener('seo-field-updated', this.handleSeoFieldUpdate, true);
  },
  beforeDestroy() {
    // Clean up event listeners
    document.removeEventListener('input', this.handleInputChange, true);
    document.removeEventListener('change', this.handleInputChange, true);
    document.removeEventListener('seo-field-updated', this.handleSeoFieldUpdate, true);
  },
  methods: {
    handleInputChange(event) {
      // Update on any input change
      this.handleUpdate();
    },
    handleSeoFieldUpdate(event) {
      console.log('SEO field updated:', event.detail);
      // Use data passed in event detail
      if (event.detail && event.detail.seoData) {
        this.updatePreviewFromData(event.detail.seoData, event.detail.pageTitle);
      } else {
        // Fallback to loading from form state or API
        this.loadFromFormState() || this.load();
      }
    },
    handleUpdate() {
      // Debounce updates to avoid too many API calls
      clearTimeout(this.updateTimeout);
      this.updateTimeout = setTimeout(() => {
        console.log('Updating preview...');
        // Try to load from form state first (instant), fall back to API
        this.loadFromFormState() || this.load();
      }, 1000);
    },
    updatePreviewFromData(seoData, pageTitle) {
      console.log('Updating preview from data:', seoData, pageTitle);

      // Use stored site name and separator (extracted from initial load)
      const siteName = this.siteName || this.$store?.state?.system?.title || 'Site Name';
      const separator = this.separator || '|';

      const metaTitle = seoData.metatitle || pageTitle || 'Page Title';
      const fullTitle = metaTitle + ' ' + separator + ' ' + siteName;

      // Preserve existing ogImage if we're just updating text fields
      const currentOgImage = this.meta?.ogImage || null;

      this.meta = {
        url: window.location.origin,
        title: fullTitle,
        description: seoData.metadescription || 'No description',
        ogTitle: seoData.ogtitle || fullTitle,
        ogDescription: seoData.ogdescription || seoData.metadescription || 'No description',
        ogImage: currentOgImage // Preserve existing image
      };

      console.log('Preview updated:', this.meta);
    },
    loadFromFormState() {
      try {
        // Try to find the parent form/view with current values
        let parent = this.$parent;
        while (parent && !parent.value) {
          parent = parent.$parent;
        }

        if (!parent || !parent.value) {
          console.log('Could not find parent form values');
          return false;
        }

        console.log('Loading from form state:', parent.value);

        // Extract SEO data
        const seoData = parent.value.seo || {};
        const pageTitle = parent.value.title || 'Page Title';

        // Use the shared update method
        this.updatePreviewFromData(seoData, pageTitle);
        return true;
      } catch (error) {
        console.error('Error loading from form state:', error);
        return false;
      }
    },
    async load() {
      try {
        const response = await this.$api.get(this.parent + '/sections/' + this.name);

        // Try different possible locations
        if (response.meta) {
          this.meta = response.meta;
          // Extract and store site name from the title
          this.extractSiteInfo();
        } else if (response.data && response.data.meta) {
          this.meta = response.data.meta;
          this.extractSiteInfo();
        } else {
          console.warn('No meta found in response');
        }
      } catch (error) {
        console.error('Error loading section:', error);
      }
    },
    extractSiteInfo() {
      // Extract site name and separator from the loaded title
      // Title format: "Page Title | Site Name"
      if (this.meta && this.meta.title) {
        const separators = ['|', '-', '–', '—', '•', '/'];
        for (const sep of separators) {
          if (this.meta.title.includes(` ${sep} `)) {
            this.separator = sep;
            const parts = this.meta.title.split(` ${sep} `);
            if (parts.length > 1) {
              this.siteName = parts[parts.length - 1].trim();
              console.log('Extracted site name:', this.siteName, 'separator:', this.separator);
            }
            break;
          }
        }
      }
      // Fallback
      if (!this.siteName) {
        this.siteName = 'Site Name';
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
