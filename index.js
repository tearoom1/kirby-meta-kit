(function() {
  "use strict";
  function normalizeComponent(scriptExports, render, staticRenderFns, functionalTemplate, injectStyles, scopeId, moduleIdentifier, shadowMode) {
    var options = typeof scriptExports === "function" ? scriptExports.options : scriptExports;
    if (render) {
      options.render = render;
      options.staticRenderFns = staticRenderFns;
      options._compiled = true;
    }
    return {
      exports: scriptExports,
      options
    };
  }
  const _sfc_main = {
    props: {
      label: String,
      parent: String,
      name: String
    },
    data() {
      return {
        meta: null,
        updateTimeout: null
      };
    },
    async mounted() {
      console.log("SEO Preview Section Mounted");
      await this.load();
      document.addEventListener("input", this.handleInputChange, true);
      document.addEventListener("change", this.handleInputChange, true);
      document.addEventListener("seo-field-updated", this.handleSeoFieldUpdate, true);
    },
    beforeDestroy() {
      document.removeEventListener("input", this.handleInputChange, true);
      document.removeEventListener("change", this.handleInputChange, true);
      document.removeEventListener("seo-field-updated", this.handleSeoFieldUpdate, true);
    },
    methods: {
      handleInputChange(event) {
        this.handleUpdate();
      },
      handleSeoFieldUpdate(event) {
        console.log("SEO field updated:", event.detail);
        if (event.detail && event.detail.seoData) {
          this.updatePreviewFromData(event.detail.seoData, event.detail.pageTitle);
        } else {
          this.loadFromFormState() || this.load();
        }
      },
      handleUpdate() {
        clearTimeout(this.updateTimeout);
        this.updateTimeout = setTimeout(() => {
          console.log("Updating preview...");
          this.loadFromFormState() || this.load();
        }, 1e3);
      },
      updatePreviewFromData(seoData, pageTitle) {
        var _a, _b, _c, _d;
        console.log("Updating preview from data:", seoData, pageTitle);
        const siteName = ((_c = (_b = (_a = this.$store) == null ? void 0 : _a.state) == null ? void 0 : _b.system) == null ? void 0 : _c.title) || "Site Name";
        const separator = "|";
        const metaTitle = seoData.metatitle || pageTitle || "Page Title";
        const fullTitle = metaTitle + " " + separator + " " + siteName;
        const currentOgImage = ((_d = this.meta) == null ? void 0 : _d.ogImage) || null;
        this.meta = {
          url: window.location.origin,
          title: fullTitle,
          description: seoData.metadescription || "No description",
          ogTitle: seoData.ogtitle || fullTitle,
          ogDescription: seoData.ogdescription || seoData.metadescription || "No description",
          ogImage: currentOgImage
          // Preserve existing image
        };
        console.log("Preview updated:", this.meta);
      },
      loadFromFormState() {
        try {
          let parent = this.$parent;
          while (parent && !parent.value) {
            parent = parent.$parent;
          }
          if (!parent || !parent.value) {
            console.log("Could not find parent form values");
            return false;
          }
          console.log("Loading from form state:", parent.value);
          const seoData = parent.value.seo || {};
          const pageTitle = parent.value.title || "Page Title";
          this.updatePreviewFromData(seoData, pageTitle);
          return true;
        } catch (error) {
          console.error("Error loading from form state:", error);
          return false;
        }
      },
      async load() {
        try {
          const response = await this.$api.get(this.parent + "/sections/" + this.name);
          if (response.meta) {
            this.meta = response.meta;
          } else if (response.data && response.data.meta) {
            this.meta = response.data.meta;
          } else {
            console.warn("No meta found in response");
          }
        } catch (error) {
          console.error("Error loading section:", error);
        }
      },
      truncate(text, length) {
        if (!text) return "";
        return text.length > length ? text.substring(0, length) + "..." : text;
      },
      displayUrl(url) {
        if (!url) return "example.com";
        try {
          const urlObj = new URL(url);
          return urlObj.hostname + urlObj.pathname;
        } catch {
          return url;
        }
      }
    }
  };
  var _sfc_render = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("section", { staticClass: "k-seo-preview-section" }, [_c("header", { staticClass: "k-section-header" }, [_c("h2", { staticClass: "k-headline" }, [_vm._v(_vm._s(_vm.label || "SEO Preview"))])]), _vm.meta ? _c("div", { staticClass: "k-seo-previews" }, [_c("div", { staticClass: "k-seo-preview k-seo-preview--google" }, [_c("h3", { staticClass: "k-seo-preview__title" }, [_vm._v("Google Search Preview")]), _c("div", { staticClass: "k-seo-preview__content" }, [_c("div", { staticClass: "k-google-preview" }, [_c("cite", { staticClass: "k-google-preview__url" }, [_vm._v(_vm._s(_vm.displayUrl(_vm.meta.url)))]), _c("h3", { staticClass: "k-google-preview__title" }, [_vm._v(_vm._s(_vm.meta.title || "Page Title"))]), _c("p", { staticClass: "k-google-preview__description" }, [_vm._v(_vm._s(_vm.meta.description || "No description available"))])])])]), _c("div", { staticClass: "k-seo-preview k-seo-preview--twitter" }, [_c("h3", { staticClass: "k-seo-preview__title" }, [_vm._v("Share / Card Preview")]), _c("div", { staticClass: "k-seo-preview__content" }, [_c("div", { staticClass: "k-twitter-preview" }, [_vm.meta.ogImage ? _c("div", { staticClass: "k-twitter-preview__image", style: { backgroundImage: "url(" + _vm.meta.ogImage + ")" } }) : _vm._e(), _c("div", { staticClass: "k-twitter-preview__body" }, [_c("cite", { staticClass: "k-twitter-preview__url" }, [_vm._v(_vm._s(_vm.displayUrl(_vm.meta.url)))]), _c("h4", { staticClass: "k-twitter-preview__title" }, [_vm._v(_vm._s(_vm.meta.ogTitle || _vm.meta.title || "Page Title"))]), _c("p", { staticClass: "k-twitter-preview__description" }, [_vm._v(_vm._s(_vm.truncate(_vm.meta.ogDescription || _vm.meta.description, 140) || "No description"))])])])])])]) : _c("div", { staticClass: "k-seo-preview-loading" }, [_vm._v(" Loading preview... ")])]);
  };
  var _sfc_staticRenderFns = [];
  _sfc_render._withStripped = true;
  var __component__ = /* @__PURE__ */ normalizeComponent(
    _sfc_main,
    _sfc_render,
    _sfc_staticRenderFns
  );
  __component__.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/kirby-seo-ai/src/sections/seo-preview.vue";
  const SeoPreview = __component__.exports;
  panel.plugin("tearoom1/meta-kit", {
    sections: {
      "seo-preview": SeoPreview
    },
    fields: {
      "meta-kit-generator": {
        props: {
          label: String,
          help: String,
          sourceField: {
            type: String,
            default: "text"
          },
          targetField: {
            type: String,
            default: "metaDescription"
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
            <strong>✓ Description generated and filled:</strong>
            <div class="k-meta-kit-generator__text">{{ generatedText }}</div>
            <small>The description has been added to both Meta Description and OG Description fields below. Scroll down to review and save.</small>
          </div>
        </k-field>
      `,
        computed: {
          buttonText() {
            return this.loading ? "Generating..." : "Generate with AI";
          }
        },
        methods: {
          extractTextFromValue(value, depth = 0) {
            if (depth > 5) return "";
            if (value == null) return "";
            if (typeof value === "string") {
              const skipValues = ["none", "full", "default", "1/1", "1/2", "1/3", "2/3", "1/4", "3/4"];
              if (skipValues.includes(value.trim().toLowerCase())) {
                return "";
              }
              return value.trim().length > 2 ? value : "";
            }
            if (Array.isArray(value)) {
              return value.map((item) => this.extractTextFromValue(item, depth + 1)).join(" ");
            }
            if (typeof value === "object") {
              let texts = [];
              const textProps = ["text", "content", "heading", "headline", "subheadline", "title", "description", "caption", "body"];
              for (const prop of textProps) {
                if (value[prop]) {
                  texts.push(this.extractTextFromValue(value[prop], depth + 1));
                }
              }
              if (texts.length === 0) {
                for (const [key, val] of Object.entries(value)) {
                  if (key.startsWith("_") || key.startsWith("$") || key === "__ob__" || key === "id" || key === "type" || key === "attrs" || key === "width" || key === "location") {
                    continue;
                  }
                  texts.push(this.extractTextFromValue(val, depth + 1));
                }
              }
              return texts.filter((t) => t && t.trim()).join(" ");
            }
            return "";
          },
          extractTextFromBlocks(blocks) {
            if (!blocks) return "";
            try {
              if (typeof blocks === "string") {
                try {
                  blocks = JSON.parse(blocks);
                } catch {
                  return blocks.trim().length > 10 ? blocks : "";
                }
              }
              if (Array.isArray(blocks)) {
                let texts = [];
                for (const layout of blocks) {
                  if (layout.columns && Array.isArray(layout.columns)) {
                    for (const column of layout.columns) {
                      if (column.blocks && Array.isArray(column.blocks)) {
                        for (const block of column.blocks) {
                          if (block.content) {
                            const blockText = this.extractTextFromValue(block.content);
                            if (blockText && blockText.length > 3) {
                              texts.push(blockText);
                            }
                          }
                        }
                      }
                    }
                  } else {
                    const text = this.extractTextFromValue(layout);
                    if (text && text.length > 3) {
                      texts.push(text);
                    }
                  }
                }
                return texts.join(" ");
              }
              return this.extractTextFromValue(blocks);
            } catch (e) {
              console.warn("Could not parse blocks:", e);
              return "";
            }
          },
          extractAllText(values) {
            let texts = [];
            const textFields = ["text", "content", "body", "description", "headline", "subheadline"];
            for (const field of textFields) {
              if (values[field]) {
                const extracted = this.extractTextFromBlocks(values[field]);
                if (extracted) {
                  console.log(`Extracted from ${field}:`, extracted.substring(0, 100));
                  texts.push(extracted);
                }
              }
            }
            for (const [key, value] of Object.entries(values)) {
              if (key.includes("layout") || key.includes("blocks") || key.includes("builder")) {
                console.log(`Found ${key} field:`, value);
                if (value && value[0]) {
                  console.log("First block structure:", JSON.stringify(value[0], (k, v) => {
                    if (k === "__ob__") return void 0;
                    return v;
                  }, 2));
                }
                const extracted = this.extractTextFromBlocks(value);
                console.log(`Extracted from ${key} (${extracted.length} chars):`, extracted);
                if (extracted) {
                  texts.push(extracted);
                }
              }
            }
            const result = texts.filter((t) => t && t.trim()).join(" ").replace(/\s+/g, " ").trim();
            console.log("Final extracted text:", result.substring(0, 200));
            return result;
          },
          async generate() {
            var _a;
            this.loading = true;
            this.error = null;
            this.success = false;
            try {
              let parent = this.$parent;
              while (parent && !parent.value) {
                parent = parent.$parent;
              }
              if (!parent || !parent.value) {
                throw new Error("Cannot access form values");
              }
              console.log("Available fields:", Object.keys(parent.value));
              console.log("All values:", parent.value);
              const allText = this.extractAllText(parent.value);
              console.log("Extracted text length:", allText.length);
              console.log("Extracted text preview:", allText.substring(0, 200));
              if (!allText || allText.trim() === "") {
                const availableFields = Object.keys(parent.value).join(", ");
                throw new Error(`No content available to generate description. Available fields: ${availableFields}`);
              }
              const language = ((_a = this.$language) == null ? void 0 : _a.code) || "en";
              console.log("Calling API with text:", allText.substring(0, 100));
              console.log("Language:", language);
              const response = await this.$api.post("meta-kit/generate", {
                text: allText,
                language
              });
              console.log("API Response:", response);
              if (response.status === "success" && response.description) {
                this.generatedText = response.description;
                console.log("Generated description:", response.description);
                if (parent && parent.value) {
                  if (!parent.value.seo) {
                    this.$set(parent.value, "seo", {});
                  }
                  this.$set(parent.value.seo, "metadescription", response.description);
                  this.$set(parent.value.seo, "ogdescription", response.description);
                  console.log("Updated SEO object to:", parent.value.seo);
                  console.log("metadescription value:", parent.value.seo.metadescription);
                  if (parent.update) {
                    parent.update({
                      seo: parent.value.seo
                    });
                  }
                  setTimeout(() => {
                    const event = new CustomEvent("seo-field-updated", {
                      bubbles: true,
                      detail: {
                        field: "metadescription",
                        value: response.description,
                        seoData: parent.value.seo,
                        pageTitle: parent.value.title
                      }
                    });
                    document.dispatchEvent(event);
                  }, 100);
                }
              } else {
                console.error("API returned error:", response);
                throw new Error(response.message || "Failed to generate description");
              }
            } catch (error) {
              this.error = error.message || "An error occurred while generating the description";
              console.error("SEO AI Generator Error:", error);
            } finally {
              this.loading = false;
            }
          }
        }
      }
    }
  });
})();
