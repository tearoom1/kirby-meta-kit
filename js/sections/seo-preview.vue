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
  computed: {
    currentLanguage() {
      return (
        this.$language?.code ||
        window.panel?.view?.props?.language ||
        window.panel?.language?.code ||
        null
      );
    }
  },
  watch: {
    parent() {
      this.handleContextChange();
    },
    name() {
      this.handleContextChange();
    },
    '$route.fullPath'() {
      this.handleContextChange();
    },
    currentLanguage(newLang, oldLang) {
      if (!newLang || newLang === oldLang) return;
      this.handleContextChange();
    }
  },
  data() {
    return {
      meta: null,
      siteName: null,
      separator: '|',
      siteOgImage: null,
      siteOgImageDetermined: false,
      updateTimeout: null,
      fieldCheckInterval: null,
      lastFieldValues: {},
      filesObserver: null,
      contextChangeTimeout: null
    }
  },
  async mounted() {
    await this.load();

    // Listen for custom SEO field updates from AI generator
    document.addEventListener('seo-field-updated', this.handleSeoFieldUpdate, true);

    // Listen for custom field change events from mk-title and mk-description
    document.addEventListener('meta-kit-field-change', this.handleMetaKitFieldChange, true);

    // Listen for input changes in the document
    document.addEventListener('input', this.handleDOMInput, true);
    document.addEventListener('change', this.handleDOMInput, true);

    // Set up MutationObserver to detect field resets (when discarding changes)
    this.setupFieldObserver();

    // Set up MutationObserver for file field changes
    this.setupFilesObserver();

    // After a short delay, check if there's a page-specific image
    // If not, store the loaded image as the site default
    this.$nextTick(() => {
      setTimeout(() => {
        this.determineSiteDefaultImage();
      }, 1000);
    });
  },
  beforeDestroy() {
    // Clean up event listeners
    document.removeEventListener('seo-field-updated', this.handleSeoFieldUpdate, true);
    document.removeEventListener('meta-kit-field-change', this.handleMetaKitFieldChange, true);
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

    // Clear pending timeouts
    if (this.updateTimeout) {
      clearTimeout(this.updateTimeout);
    }
    if (this.contextChangeTimeout) {
      clearTimeout(this.contextChangeTimeout);
    }
  },
  methods: {
    async handleContextChange() {
      clearTimeout(this.contextChangeTimeout);
      this.contextChangeTimeout = setTimeout(async () => {
      // Language switching in the Panel typically triggers a route change.
      // The section component can stay mounted, so we explicitly refetch.
      this.meta = null;
      this.siteName = null;
      this.separator = '|';
      this.siteOgImage = null;
      this.siteOgImageDetermined = false;
      this.lastFieldValues = {};

      await this.load();

      // Refresh based on current inputs (important if there are unsaved changes)
      this.$nextTick(() => {
        setTimeout(() => {
          this.updatePreviewFromDOM();
          this.determineSiteDefaultImage();
        }, 800);
      });
      }, 50);
    },
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

        const getFieldOrNull = (name) => {
          const input = document.querySelector(`[name="${name}"]`);
          if (!input) return null;
          return input.value ?? '';
        };

        const currentValues = {
          metatitle: getFieldOrNull('metaTitle'),
          metadescription: getFieldOrNull('metaDescription'),
          ogtitle: getFieldOrNull('ogTitle'),
          ogdescription: getFieldOrNull('ogDescription'),
          ogimage: getImageSrc()
        };

        // If fields are not mounted yet (common during language switches),
        // do not overwrite the already loaded preview.
        const anyMissing = Object.values(currentValues).some(v => v === null);
        if (anyMissing) {
          return;
        }

        const valuesChanged = Object.keys(currentValues).some(
          key => this.lastFieldValues[key] !== currentValues[key]
        );

        if (valuesChanged && Object.keys(this.lastFieldValues).length > 0 && this.siteOgImageDetermined) {
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
      if (!target) return;

      // Check if this is an SEO field by name attribute
      const fieldName = target.name || target.getAttribute('name');
      const seoFields = ['metaTitle', 'metaDescription', 'ogTitle', 'ogDescription'];

      if (fieldName && seoFields.includes(fieldName)) {
        clearTimeout(this.updateTimeout);
        this.updateTimeout = setTimeout(() => {
          this.updatePreviewFromDOM();
        }, 300);
        return;
      }

      // Check if this is a file field change
      if (target.closest('.k-field-name-ogimage')) {
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
    handleMetaKitFieldChange(event) {
      // Custom field (mk-title or mk-description) changed
      if (event.detail) {
        clearTimeout(this.updateTimeout);
        this.updateTimeout = setTimeout(() => {
          this.updatePreviewFromDOM();
        }, 200);
      }
    },
    updatePreviewFromDOM() {
      // Extract field value by name attribute
      const getFieldValue = (name) => {
        const input = document.querySelector(`[name="${name}"]`);
        if (!input) return null;
        return input.value ?? '';
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
        metatitle: getFieldValue('metaTitle'),
        metadescription: getFieldValue('metaDescription'),
        ogtitle: getFieldValue('ogTitle'),
        ogdescription: getFieldValue('ogDescription'),
        ogimage: getOgImage()
      };

      // If fields are not mounted yet, do not overwrite loaded meta with placeholders
      const values = Object.values(seoData);
      if (values.some(v => v === null)) {
        return;
      }

      // Get page title (might be in a different field)
      const pageTitleValue = getFieldValue('title');
      const pageTitle = pageTitleValue || 'Page Title';

      // Avoid replacing real preview with placeholder content when everything is empty
      const allEmpty = [
        seoData.metatitle,
        seoData.metadescription,
        seoData.ogtitle,
        seoData.ogdescription
      ].every(v => !v || !String(v).trim());
      const isPlaceholderTitle = !pageTitleValue;
      if (allEmpty && isPlaceholderTitle && this.meta) {
        return;
      }

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
        const baseUrl = this.parent + '/sections/' + this.name;
        const lang = this.currentLanguage;
        const url = lang ? `${baseUrl}?language=${encodeURIComponent(lang)}` : baseUrl;
        const response = await this.$api.get(url);

        // Get meta from response
        let newMeta = null;
        if (response.meta) {
          newMeta = response.meta;
        } else if (response.data && response.data.meta) {
          newMeta = response.data.meta;
        }

        if (newMeta) {
          this.meta = newMeta;

          // Extract and store site name from the title on first load
          if (!this.siteName) {
            this.extractSiteInfo();
          }
        }
      } catch (error) {
        // Silently fail - preview will show loading state
      }
    },
    determineSiteDefaultImage() {
      // Check if there's a page-specific image in the field
      const ogImageField = document.querySelector('.k-field-name-ogimage');
      const pageImage = ogImageField?.querySelector('img');
      const hasPageImage = pageImage && pageImage.srcset;

      if (!hasPageImage && this.meta?.ogImage) {
        // No page-specific image, so the loaded image is the site default
        this.siteOgImage = this.meta.ogImage;
      } else if (hasPageImage) {
        // There is a page image, we need to fetch site default separately
        // For now, set to null (no fallback available)
        this.siteOgImage = null;
      }

      // Mark as determined so polling can start updating
      this.siteOgImageDetermined = true;
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
