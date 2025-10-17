
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
        extractTextFromValue(value, depth = 0) {
          // Prevent infinite recursion
          if (depth > 5) return '';
          
          // Handle null/undefined
          if (value == null) return '';
          
          // Handle strings
          if (typeof value === 'string') {
            return value;
          }
          
          // Handle arrays
          if (Array.isArray(value)) {
            return value.map(item => this.extractTextFromValue(item, depth + 1)).join(' ');
          }
          
          // Handle objects
          if (typeof value === 'object') {
            let texts = [];
            
            // Common text properties in Kirby blocks
            const textProps = ['text', 'content', 'heading', 'headline', 'subheadline', 'title', 'description', 'caption'];
            
            for (const prop of textProps) {
              if (value[prop]) {
                texts.push(this.extractTextFromValue(value[prop], depth + 1));
              }
            }
            
            // If no text props found, try all properties
            if (texts.length === 0) {
              for (const [key, val] of Object.entries(value)) {
                // Skip internal Vue/Kirby properties
                if (key.startsWith('_') || key.startsWith('$') || key === '__ob__' || key === 'id' || key === 'type') {
                  continue;
                }
                texts.push(this.extractTextFromValue(val, depth + 1));
              }
            }
            
            return texts.filter(t => t && t.trim()).join(' ');
          }
          
          return '';
        },
        
        extractTextFromBlocks(blocks) {
          if (!blocks) return '';
          
          try {
            // If it's a JSON string, parse it
            if (typeof blocks === 'string') {
              try {
                blocks = JSON.parse(blocks);
              } catch {
                // Not JSON, return as is
                return blocks;
              }
            }
            
            return this.extractTextFromValue(blocks);
          } catch (e) {
            console.warn('Could not parse blocks:', e);
            return '';
          }
        },
        
        extractAllText(values) {
          let texts = [];
          
          // Common text field names to check
          const textFields = ['text', 'content', 'body', 'description', 'headline', 'subheadline'];
          
          // Check standard text fields
          for (const field of textFields) {
            if (values[field]) {
              const extracted = this.extractTextFromBlocks(values[field]);
              if (extracted) {
                console.log(`Extracted from ${field}:`, extracted.substring(0, 100));
                texts.push(extracted);
              }
            }
          }
          
          // Check for layout/blocks fields
          for (const [key, value] of Object.entries(values)) {
            if (key.includes('layout') || key.includes('blocks') || key.includes('builder')) {
              console.log(`Found ${key} field:`, value);
              const extracted = this.extractTextFromBlocks(value);
              if (extracted) {
                console.log(`Extracted from ${key}:`, extracted.substring(0, 100));
                texts.push(extracted);
              }
            }
          }
          
          // Join all texts and clean up
          const result = texts
            .filter(t => t && t.trim())
            .join(' ')
            .replace(/\s+/g, ' ')
            .trim();
          
          console.log('Final extracted text:', result.substring(0, 200));
          return result;
        },
        
        async generate() {
          this.loading = true;
          this.error = null;
          this.success = false;

          try {
            // Get the parent form/view to access field values
            let parent = this.$parent;
            while (parent && !parent.value) {
              parent = parent.$parent;
            }

            if (!parent || !parent.value) {
              throw new Error('Cannot access form values');
            }

            // Debug: Log all available field names
            console.log('Available fields:', Object.keys(parent.value));
            console.log('All values:', parent.value);

            // Extract all text content from the page
            const allText = this.extractAllText(parent.value);
            
            console.log('Extracted text length:', allText.length);
            console.log('Extracted text preview:', allText.substring(0, 200));

            if (!allText || allText.trim() === '') {
              // Show user what fields are available
              const availableFields = Object.keys(parent.value).join(', ');
              throw new Error(`No content available to generate description. Available fields: ${availableFields}`);
            }

            // Get current language code
            const language = this.$language?.code || 'en';

            // Call the API with extracted text
            const response = await this.$api.post('seo-ai/generate', {
              text: allText,
              language: language
            });

            if (response.status === 'success' && response.description) {
              // Update the target field value
              this.$emit('input', response.description);
              
              // Also update the parent form
              if (parent.update) {
                parent.update({
                  [this.targetField]: response.description
                });
              }

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
