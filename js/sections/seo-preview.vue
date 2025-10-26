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
      siteName: null,
      separator: '|',
      siteOgImage: null,
      updateTimeout: null,
      fieldCheckInterval: null,
      lastFieldValues: {},
      filesObserver: null
    }
  },
  async mounted() {
    await this.load();

    // Listen for custom SEO field updates from AI generator
    document.addEventListener('seo-field-updated', this.handleSeoFieldUpdate, true);

    // Listen for input changes in the document
    document.addEventListener('input', this.handleDOMInput, true);
    document.addEventListener('change', this.handleDOMInput, true);

    // Set up MutationObserver to detect field resets (when discarding changes)
    this.setupFieldObserver();

    // Set up MutationObserver for file field changes
    this.setupFilesObserver();
  },
  beforeDestroy() {
    // Clean up event listeners
    document.removeEventListener('seo-field-updated', this.handleSeoFieldUpdate, true);
    document.removeEventListener('input', this.handleDOMInput, true);
    document.removeEventListener('change', this.handleDOMInput, true);

    // Clear field check interval
    if (this.fieldCheckInterval) {
      clearInterval(this.fieldCheckInterval);
    }

    // Disconnect files observer
    if (this.filesObserver) {
      this.filesObserver.disconnect();
    }
  },
  methods: {
    setupFieldObserver() {
      // Watch for when field values change programmatically (like when discarding)
      const checkFieldValues = () => {
        // Get current OG image from DOM
        const getImageSrc = () => {
          const ogImageField = document.querySelector('.k-field-name-ogimage');
          if (ogImageField) {
            const img = ogImageField.querySelector('img');
            if (img && img.srcset) {
              // Get the first URL from srcset
              const firstUrl = img.srcset.split(',')[0].trim().split(' ')[0];
              if (firstUrl) {
                return firstUrl;
              }
            }
          }
          return '';
        };

        const currentValues = {
          metatitle: document.querySelector('[name="metatitle"]')?.value || '',
          metadescription: document.querySelector('[name="metadescription"]')?.value || '',
          ogtitle: document.querySelector('[name="ogtitle"]')?.value || '',
          ogdescription: document.querySelector('[name="ogdescription"]')?.value || '',
          ogimage: getImageSrc()
        };

        const valuesChanged = Object.keys(currentValues).some(
          key => this.lastFieldValues[key] !== currentValues[key]
        );

        if (valuesChanged && Object.keys(this.lastFieldValues).length > 0) {
          this.updatePreviewFromDOM();
        }

        this.lastFieldValues = currentValues;
      };

      // Check every 500ms for field changes
      this.fieldCheckInterval = setInterval(checkFieldValues, 500);
      // Initialize current values
      checkFieldValues();
    },
    setupFilesObserver() {
      // Watch for changes in the ogImage files field specifically
      const observeFiles = () => {
        // Wait a bit for the DOM to be ready
        setTimeout(() => {
          const ogImageField = document.querySelector('.k-field-name-ogimage');
          if (ogImageField) {
            this.filesObserver = new MutationObserver((mutations) => {
              // Any change in the ogImage field should trigger update
              clearTimeout(this.updateTimeout);
              this.updateTimeout = setTimeout(() => {
                this.updatePreviewFromDOM();
              }, 800); // Longer delay for file operations
            });

            // Observe the ogImage field for changes
            this.filesObserver.observe(ogImageField, {
              childList: true,
              subtree: true,
              attributes: true,
              attributeFilter: ['src', 'style']
            });
          }
        }, 1000); // Wait for blocks to render
      };

      observeFiles();
    },
    handleDOMInput(event) {
      const target = event.target;
      const fieldName = target.name || target.getAttribute('name');

      // Check if this is an SEO field we care about
      const seoFields = ['metatitle', 'metadescription', 'ogtitle', 'ogdescription', 'ogimage'];
      if (fieldName && seoFields.includes(fieldName.toLowerCase())) {
        // Debounce updates
        clearTimeout(this.updateTimeout);
        this.updateTimeout = setTimeout(() => {
          this.updatePreviewFromDOM();
        }, 500);
      }

      // Also check if this is a file field change (Kirby files field)
      if (target.closest('.k-files-field') || target.closest('.k-upload')) {
        clearTimeout(this.updateTimeout);
        this.updateTimeout = setTimeout(() => {
          this.updatePreviewFromDOM();
        }, 500);
      }
    },
    handleSeoFieldUpdate(event) {
      // AI generator triggered - use provided data directly (not saved yet)
      if (event.detail && event.detail.seoData) {
        this.updatePreviewFromData(event.detail.seoData, event.detail.pageTitle || 'Page Title');
      }
    },
    updatePreviewFromDOM() {
      // Extract values directly from DOM inputs
      const getFieldValue = (name) => {
        const input = document.querySelector(`[name="${name}"], [name="${name.toLowerCase()}"]`);
        return input ? input.value : '';
      };

      // Extract OG image from files field
      const getOgImage = () => {
        const ogImageField = document.querySelector('.k-field-name-ogimage');
        if (ogImageField) {
          const img = ogImageField.querySelector('img');
          if (img && img.srcset) {
            // Get the first URL from srcset
            const firstUrl = img.srcset.split(',')[0].trim().split(' ')[0];
            if (firstUrl) {
              return firstUrl;
            }
          }
        }
        // Return empty string to indicate no page-specific image
        // The updatePreviewFromData will handle the site fallback
        return '';
      };

      const seoData = {
        metatitle: getFieldValue('metatitle'),
        metadescription: getFieldValue('metadescription'),
        ogtitle: getFieldValue('ogtitle'),
        ogdescription: getFieldValue('ogdescription'),
        ogimage: getOgImage()
      };

      // Get page title (might be in a different field)
      const pageTitle = getFieldValue('title') || 'Page Title';

      this.updatePreviewFromData(seoData, pageTitle);
    },
    updatePreviewFromData(seoData, pageTitle) {
      // Use stored site name and separator (extracted from initial load)
      const siteName = this.siteName || this.$store?.state?.system?.title || 'Site Name';
      const separator = this.separator || '|';

      // Get page meta title from SEO data or use page title
      const pageMetaTitle = seoData.metatitle || pageTitle || 'Page Title';

      // Build full title (page title + separator + site name)
      const fullTitle = pageMetaTitle + ' ' + separator + ' ' + siteName;

      // Handle OG image: use page image if exists, fall back to site default, or null
      let ogImage;
      if (seoData.ogimage !== undefined) {
        // Empty string means no page-specific image, use site default
        ogImage = seoData.ogimage === '' ? (this.siteOgImage || null) : seoData.ogimage;
      } else {
        // undefined means preserve existing
        ogImage = this.meta?.ogImage || null;
      }

      // Get descriptions - handle empty strings as fallback
      const metaDesc = seoData.metadescription && seoData.metadescription.trim()
        ? seoData.metadescription
        : 'No description available';

      const ogDesc = (seoData.ogdescription && seoData.ogdescription.trim())
        ? seoData.ogdescription
        : metaDesc;

      this.meta = {
        url: window.location.origin,
        title: fullTitle,
        description: metaDesc,
        ogTitle: seoData.ogtitle || pageMetaTitle,
        ogDescription: ogDesc,
        ogImage: ogImage
      };
    },
    async load() {
      try {
        const response = await this.$api.get(this.parent + '/sections/' + this.name);

        // Get meta from response
        let newMeta = null;
        if (response.meta) {
          newMeta = response.meta;
        } else if (response.data && response.data.meta) {
          newMeta = response.data.meta;
        }

        if (newMeta) {
          this.meta = newMeta;
          // Store the site's default OG image only if the page field is empty
          if (!this.siteOgImage && newMeta.ogImage) {
            // Check if there's a page-specific image in the field
            setTimeout(() => {
              const ogImageField = document.querySelector('.k-field-name-ogimage');
              const hasPageImage = ogImageField && ogImageField.querySelector('img')?.srcset;
              // If no page image in field, the loaded image must be the site default
              if (!hasPageImage) {
                this.siteOgImage = newMeta.ogImage;
              }
            }, 1500); // Wait for DOM to be fully ready
          }
          // Extract and store site name from the title on first load
          if (!this.siteName) {
            this.extractSiteInfo();
          }
        }
      } catch (error) {
        // Silently fail - preview will show loading state
      }
    },
    extractSiteInfo() {
      // Extract site name and separator from the loaded title
      // Title format: "Page Title | Site Name"
      if (this.meta && this.meta.title) {
        // Try to find separator - prefer | first as it's most common
        const separators = ['|', '–', '—', '-', '•', '/'];
        for (const sep of separators) {
          const searchPattern = ` ${sep} `;
          if (this.meta.title.includes(searchPattern)) {
            this.separator = sep;
            // Split and take the last part as site name
            const parts = this.meta.title.split(searchPattern);
            if (parts.length > 1) {
              this.siteName = parts[parts.length - 1].trim();
            }
            break;
          }
        }
      }
      // Fallback
      if (!this.siteName) {
        this.siteName = this.$store?.state?.system?.title || 'Site Name';
      }
      if (!this.separator) {
        this.separator = '|';
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
