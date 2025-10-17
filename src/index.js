
import './index.css';

panel.plugin('tearoom1/seo-ai', {
  sections: {
    'seo-preview': {
      props: {
        label: String,
        metaTitle: String,
        metaDescription: String,
        ogImage: String,
        pageUrl: String
      },
      template: `
        <section class="k-seo-preview-section">
          <header class="k-section-header">
            <h2 class="k-headline">{{ label }}</h2>
          </header>

          <div class="k-seo-previews">
            <!-- Google Preview -->
            <div class="k-seo-preview k-seo-preview--google">
              <h3 class="k-seo-preview__title">Google Search Preview</h3>
              <div class="k-seo-preview__content">
                <div class="k-google-preview">
                  <cite class="k-google-preview__url">{{ displayUrl }}</cite>
                  <h3 class="k-google-preview__title">{{ metaTitle || 'Page Title' }}</h3>
                  <p class="k-google-preview__description">{{ metaDescription || 'No description available' }}</p>
                </div>
              </div>
            </div>

            <!-- Twitter Preview -->
            <div class="k-seo-preview k-seo-preview--twitter">
              <h3 class="k-seo-preview__title">Twitter Card Preview</h3>
              <div class="k-seo-preview__content">
                <div class="k-twitter-preview">
                  <div v-if="ogImage" class="k-twitter-preview__image" :style="{ backgroundImage: 'url(' + ogImage + ')' }"></div>
                  <div class="k-twitter-preview__body">
                    <cite class="k-twitter-preview__url">{{ displayUrl }}</cite>
                    <h4 class="k-twitter-preview__title">{{ metaTitle || 'Page Title' }}</h4>
                    <p class="k-twitter-preview__description">{{ truncate(metaDescription, 140) || 'No description' }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Facebook Preview -->
            <div class="k-seo-preview k-seo-preview--facebook">
              <h3 class="k-seo-preview__title">Facebook Share Preview</h3>
              <div class="k-seo-preview__content">
                <div class="k-facebook-preview">
                  <div v-if="ogImage" class="k-facebook-preview__image" :style="{ backgroundImage: 'url(' + ogImage + ')' }"></div>
                  <div class="k-facebook-preview__body">
                    <cite class="k-facebook-preview__url">{{ displayUrl }}</cite>
                    <h4 class="k-facebook-preview__title">{{ metaTitle || 'Page Title' }}</h4>
                    <p class="k-facebook-preview__description">{{ truncate(metaDescription, 120) || 'No description' }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>
      `,
      computed: {
        displayUrl() {
          if (!this.pageUrl) return 'example.com';
          try {
            const url = new URL(this.pageUrl);
            return url.hostname + url.pathname;
          } catch {
            return this.pageUrl;
          }
        }
      },
      methods: {
        truncate(text, length) {
          if (!text) return '';
          return text.length > length ? text.substring(0, length) + '...' : text;
        }
      }
    }
  },
  fields: {
    'seo-ai-generator': {
      props: {
        label: String,
        help: String,
        sourceField: {
          type: String,
          default: 'text'
        },
        targetField: {
          type: String,
          default: 'metaDescription'
        }
      },
      data() {
        return {
          loading: false,
          error: null,
          success: false
        };
      },
      template: `
        <k-field v-bind="$props" class="k-seo-ai-generator-field">
          <k-button
            icon="ai"
            :text="buttonText"
            @click="generate"
            :disabled="loading"
            theme="positive"
          />
          <div v-if="loading" class="k-seo-ai-generator__status">
            <k-loader />
            <span>Generating description...</span>
          </div>
          <div v-if="error" class="k-seo-ai-generator__error">
            {{ error }}
          </div>
          <div v-if="success" class="k-seo-ai-generator__success">
            ✓ Description generated successfully!
          </div>
        </k-field>
      `,
      computed: {
        buttonText() {
          return this.loading ? 'Generating...' : 'Generate with AI';
        }
      },
      methods: {
        async generate() {
          this.loading = true;
          this.error = null;
          this.success = false;

          try {
            // Get the source field value
            const sourceValue = this.$store.getters['content/values']()[this.sourceField];

            if (!sourceValue || sourceValue.trim() === '') {
              throw new Error('No content available to generate description');
            }

            // Get current language
            const language = this.$language || 'en';

            // Call the API
            const response = await this.$api.post('seo-ai/generate', {
              text: sourceValue,
              language: language
            });

            if (response.status === 'success' && response.description) {
              // Update the target field
              this.$store.dispatch('content/update', {
                [this.targetField]: response.description
              });

              this.success = true;

              // Clear success message after 3 seconds
              setTimeout(() => {
                this.success = false;
              }, 3000);
            } else {
              throw new Error(response.message || 'Failed to generate description');
            }
          } catch (error) {
            this.error = error.message || 'An error occurred while generating the description';
            console.error('SEO AI Generator Error:', error);
          } finally {
            this.loading = false;
          }
        }
      }
    }
  }
});
