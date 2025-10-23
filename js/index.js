
import './index.css';
import SeoPreview from '../src/sections/seo-preview.vue';
import MetaKitView from './components/MetaKitView.vue';

panel.plugin('tearoom1/meta-kit', {
  components: {
    'meta-kit-view': MetaKitView
  },
  sections: {
    'seo-preview': SeoPreview
  },
  fields: {
    'meta-kit-generator': {
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
          generatedText: null
        };
      },
      template: `
        <k-field v-bind="$props" class="k-meta-kit-generator-field">
          <k-button
            icon="ai"
            :text="buttonText"
            @click="generate"
            :disabled="loading"
            theme="positive"
          />
          <div v-if="loading" class="k-meta-kit-generator__status">
            <k-loader />
            <span>Generating description...</span>
          </div>
          <div v-if="error" class="k-meta-kit-generator__error">
            {{ error }}
          </div>
          <div v-if="generatedText" class="k-meta-kit-generator__result">
            <strong>✓ Description generated and filled</strong>
<!--            <div class="k-meta-kit-generator__text">{{ generatedText }}</div>-->
<!--            <small>The description has been added to both Meta Description and OG Description fields below. Scroll down to review and save.</small>-->
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
            // Skip common metadata values
            const skipValues = ['none', 'full', 'default', '1/1', '1/2', '1/3', '2/3', '1/4', '3/4'];
            if (skipValues.includes(value.trim().toLowerCase())) {
              return '';
            }
            // Only return strings with at least 3 characters
            return value.trim().length > 2 ? value : '';
          }

          // Handle arrays
          if (Array.isArray(value)) {
            return value.map(item => this.extractTextFromValue(item, depth + 1)).join(' ');
          }

          // Handle objects
          if (typeof value === 'object') {
            let texts = [];

            // Common text properties in Kirby blocks
            const textProps = ['text', 'content', 'heading', 'headline', 'subheadline', 'title', 'description', 'caption', 'body'];

            for (const prop of textProps) {
              if (value[prop]) {
                texts.push(this.extractTextFromValue(value[prop], depth + 1));
              }
            }

            // If no text props found, try all properties
            if (texts.length === 0) {
              for (const [key, val] of Object.entries(value)) {
                // Skip internal Vue/Kirby properties and layout metadata
                if (key.startsWith('_') ||
                    key.startsWith('$') ||
                    key === '__ob__' ||
                    key === 'id' ||
                    key === 'type' ||
                    key === 'attrs' ||
                    key === 'width' ||
                    key === 'location') {
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
                // Not JSON, return as is if it's meaningful text
                return blocks.trim().length > 10 ? blocks : '';
              }
            }

            // Handle Kirby layout structure: [{columns: [{blocks: [{content: {...}}]}]}]
            if (Array.isArray(blocks)) {
              let texts = [];

              for (const layout of blocks) {
                // Check if this is a layout with columns
                if (layout.columns && Array.isArray(layout.columns)) {
                  for (const column of layout.columns) {
                    if (column.blocks && Array.isArray(column.blocks)) {
                      for (const block of column.blocks) {
                        if (block.content) {
                          // Extract text from block content
                          const blockText = this.extractTextFromValue(block.content);
                          if (blockText && blockText.length > 3) {
                            texts.push(blockText);
                          }
                        }
                      }
                    }
                  }
                } else {
                  // Not a layout structure, try generic extraction
                  const text = this.extractTextFromValue(layout);
                  if (text && text.length > 3) {
                    texts.push(text);
                  }
                }
              }

              return texts.join(' ');
            }

            return this.extractTextFromValue(blocks);
          } catch (e) {
            console.warn('Could not parse blocks:', e);
            return '';
          }
        },

        extractAllText(values) {
          let texts = [];

          // Skip system and SEO fields
          const skipFields = ['title', 'slug', 'template', 'ogimage'];

          // Add page title if available
          if (values.title && typeof values.title === 'string') {
            texts.push(values.title);
          }

          // Extract from ALL fields, not just predefined ones
          for (const [key, value] of Object.entries(values)) {
            // Skip system fields
            if (skipFields.includes(key)) {
              continue;
            }

            // Skip empty values
            if (!value || (typeof value === 'string' && !value.trim())) {
              continue;
            }

            // Try to extract text from any field
            const extracted = this.extractTextFromBlocks(value);
            if (extracted && extracted.trim().length > 0) {
              texts.push(extracted);
            }
          }

          // Join all texts and clean up
          const result = texts
            .filter(t => t && t.trim())
            .join(' ')
            .replace(/\s+/g, ' ')
            .trim();

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

            // Extract all text content from the page
            const allText = this.extractAllText(parent.value);

            if (!allText || allText.trim() === '') {
              // Show user what fields are available
              const availableFields = Object.keys(parent.value).join(', ');
              throw new Error(`No content available to generate description. Available fields: ${availableFields}`);
            }

            // Get current language code from multiple sources
            // Priority: 1. Content language, 2. Panel language, 3. Default 'en'
            let language = 'en';

            // Try to get language from page context (from URL or content language)
            if (window.panel?.view?.props?.language) {
              language = window.panel.view.props.language;
            } else if (window.panel?.language?.code) {
              language = window.panel.language.code;
            } else if (this.$language?.code) {
              language = this.$language.code;
            }

            // Call the API with extracted text
            const response = await this.$api.post('meta-kit/generate', {
              text: allText,
              language: language
            });

            if (response.status === 'success' && response.description) {
              // Show the generated text
              this.generatedText = response.description;

              // Update the nested blocks field
              if (parent && parent.value) {
                // Ensure seo field exists as an array (Vue reactive array)
                if (!parent.value.seo || !Array.isArray(parent.value.seo)) {
                  this.$set(parent.value, 'seo', [{
                    id: 'seo-metadata',
                    type: 'seo',
                    isHidden: false,
                    content: {}
                  }]);
                }

                // Get or create the first block
                if (parent.value.seo.length === 0) {
                  parent.value.seo.push({
                    id: 'seo-metadata',
                    type: 'seo',
                    isHidden: false,
                    content: {}
                  });
                }

                // Get the first (and only) SEO block
                const seoBlock = parent.value.seo[0];

                // Ensure content object exists
                if (!seoBlock.content) {
                  this.$set(seoBlock, 'content', {});
                }

                // Update descriptions using Vue.set for reactivity (lowercase field names!)
                this.$set(seoBlock.content, 'metadescription', response.description);
                this.$set(seoBlock.content, 'ogdescription', response.description);

                // Trigger form update
                if (parent.update) {
                  parent.update({
                    seo: parent.value.seo
                  });
                }

                // Trigger a change event to notify the preview section
                setTimeout(() => {
                  // Dispatch a custom event with full SEO data for the preview
                  const event = new CustomEvent('seo-field-updated', {
                    bubbles: true,
                    detail: {
                      field: 'metadescription',
                      value: response.description,
                      seoData: seoBlock.content,
                      pageTitle: parent.value.title
                    }
                  });
                  document.dispatchEvent(event);
                }, 100);
              }

              // Clear displayed text after 10 seconds
              // setTimeout(() => {
              //   this.generatedText = null;
              // }, 10000);
            } else {
              console.error('API returned error:', response);
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
