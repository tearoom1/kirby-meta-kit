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
  const _sfc_main$3 = {
    props: {
      label: String,
      parent: String,
      name: String
    },
    data() {
      return {
        meta: null,
        siteName: null,
        separator: "|",
        siteOgImage: null,
        siteOgImageDetermined: false,
        updateTimeout: null,
        fieldCheckInterval: null,
        lastFieldValues: {},
        filesObserver: null
      };
    },
    async mounted() {
      await this.load();
      document.addEventListener("seo-field-updated", this.handleSeoFieldUpdate, true);
      document.addEventListener("input", this.handleDOMInput, true);
      document.addEventListener("change", this.handleDOMInput, true);
      this.setupFieldObserver();
      this.setupFilesObserver();
      this.$nextTick(() => {
        setTimeout(() => {
          this.determineSiteDefaultImage();
        }, 1e3);
      });
    },
    beforeDestroy() {
      document.removeEventListener("seo-field-updated", this.handleSeoFieldUpdate, true);
      document.removeEventListener("input", this.handleDOMInput, true);
      document.removeEventListener("change", this.handleDOMInput, true);
      if (this.fieldCheckInterval) {
        clearInterval(this.fieldCheckInterval);
      }
      if (this.filesObserver) {
        this.filesObserver.disconnect();
      }
    },
    methods: {
      setupFieldObserver() {
        const checkFieldValues = () => {
          var _a, _b, _c, _d;
          const getImageSrc = () => {
            const ogImageField = document.querySelector(".k-field-name-ogimage");
            if (ogImageField) {
              const img = ogImageField.querySelector("img");
              if (img && img.srcset) {
                const firstUrl = img.srcset.split(",")[0].trim().split(" ")[0];
                if (firstUrl) {
                  return firstUrl;
                }
              }
            }
            return "";
          };
          const currentValues = {
            metatitle: ((_a = document.querySelector('[name="metatitle"]')) == null ? void 0 : _a.value) || "",
            metadescription: ((_b = document.querySelector('[name="metadescription"]')) == null ? void 0 : _b.value) || "",
            ogtitle: ((_c = document.querySelector('[name="ogtitle"]')) == null ? void 0 : _c.value) || "",
            ogdescription: ((_d = document.querySelector('[name="ogdescription"]')) == null ? void 0 : _d.value) || "",
            ogimage: getImageSrc()
          };
          const valuesChanged = Object.keys(currentValues).some(
            (key) => this.lastFieldValues[key] !== currentValues[key]
          );
          if (valuesChanged && Object.keys(this.lastFieldValues).length > 0 && this.siteOgImageDetermined) {
            this.updatePreviewFromDOM();
          }
          this.lastFieldValues = currentValues;
        };
        this.fieldCheckInterval = setInterval(checkFieldValues, 500);
        checkFieldValues();
      },
      setupFilesObserver() {
        const observeFiles = () => {
          setTimeout(() => {
            const ogImageField = document.querySelector(".k-field-name-ogimage");
            if (ogImageField) {
              this.filesObserver = new MutationObserver((mutations) => {
                clearTimeout(this.updateTimeout);
                this.updateTimeout = setTimeout(() => {
                  this.updatePreviewFromDOM();
                }, 800);
              });
              this.filesObserver.observe(ogImageField, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ["src", "style"]
              });
            }
          }, 1e3);
        };
        observeFiles();
      },
      handleDOMInput(event) {
        const target = event.target;
        const fieldName = target.name || target.getAttribute("name");
        const seoFields = ["metatitle", "metadescription", "ogtitle", "ogdescription", "ogimage"];
        if (fieldName && seoFields.includes(fieldName.toLowerCase())) {
          clearTimeout(this.updateTimeout);
          this.updateTimeout = setTimeout(() => {
            this.updatePreviewFromDOM();
          }, 500);
        }
        if (target.closest(".k-files-field") || target.closest(".k-upload")) {
          clearTimeout(this.updateTimeout);
          this.updateTimeout = setTimeout(() => {
            this.updatePreviewFromDOM();
          }, 500);
        }
      },
      handleSeoFieldUpdate(event) {
        if (event.detail && event.detail.seoData) {
          this.updatePreviewFromData(event.detail.seoData, event.detail.pageTitle || "Page Title");
        }
      },
      updatePreviewFromDOM() {
        const getFieldValue = (name) => {
          const input = document.querySelector(`[name="${name}"], [name="${name.toLowerCase()}"]`);
          return input ? input.value : "";
        };
        const getOgImage = () => {
          const ogImageField = document.querySelector(".k-field-name-ogimage");
          if (ogImageField) {
            const img = ogImageField.querySelector("img");
            if (img && img.srcset) {
              const firstUrl = img.srcset.split(",")[0].trim().split(" ")[0];
              if (firstUrl) {
                return firstUrl;
              }
            }
          }
          return "";
        };
        const seoData = {
          metatitle: getFieldValue("metatitle"),
          metadescription: getFieldValue("metadescription"),
          ogtitle: getFieldValue("ogtitle"),
          ogdescription: getFieldValue("ogdescription"),
          ogimage: getOgImage()
        };
        const pageTitle = getFieldValue("title") || "Page Title";
        this.updatePreviewFromData(seoData, pageTitle);
      },
      updatePreviewFromData(seoData, pageTitle) {
        var _a, _b, _c, _d;
        const siteName = this.siteName || ((_c = (_b = (_a = this.$store) == null ? void 0 : _a.state) == null ? void 0 : _b.system) == null ? void 0 : _c.title) || "Site Name";
        const separator = this.separator || "|";
        const pageMetaTitle = seoData.metatitle || pageTitle || "Page Title";
        const fullTitle = pageMetaTitle + " " + separator + " " + siteName;
        let ogImage;
        if (seoData.ogimage !== void 0) {
          ogImage = seoData.ogimage === "" ? this.siteOgImage || null : seoData.ogimage;
        } else {
          ogImage = ((_d = this.meta) == null ? void 0 : _d.ogImage) || null;
        }
        const metaDesc = seoData.metadescription && seoData.metadescription.trim() ? seoData.metadescription : "No description available";
        const ogDesc = seoData.ogdescription && seoData.ogdescription.trim() ? seoData.ogdescription : metaDesc;
        this.meta = {
          url: window.location.origin,
          title: fullTitle,
          description: metaDesc,
          ogTitle: seoData.ogtitle || pageMetaTitle,
          ogDescription: ogDesc,
          ogImage
        };
      },
      async load() {
        try {
          const response = await this.$api.get(this.parent + "/sections/" + this.name);
          let newMeta = null;
          if (response.meta) {
            newMeta = response.meta;
          } else if (response.data && response.data.meta) {
            newMeta = response.data.meta;
          }
          if (newMeta) {
            this.meta = newMeta;
            if (!this.siteName) {
              this.extractSiteInfo();
            }
          }
        } catch (error) {
        }
      },
      determineSiteDefaultImage() {
        var _a;
        const ogImageField = document.querySelector(".k-field-name-ogimage");
        const pageImage = ogImageField == null ? void 0 : ogImageField.querySelector("img");
        const hasPageImage = pageImage && pageImage.srcset;
        if (!hasPageImage && ((_a = this.meta) == null ? void 0 : _a.ogImage)) {
          this.siteOgImage = this.meta.ogImage;
        } else if (hasPageImage) {
          this.siteOgImage = null;
        }
        this.siteOgImageDetermined = true;
      },
      extractSiteInfo() {
        var _a, _b, _c;
        if (this.meta && this.meta.title) {
          const separators = ["|", "–", "—", "-", "•", "/"];
          for (const sep of separators) {
            const searchPattern = ` ${sep} `;
            if (this.meta.title.includes(searchPattern)) {
              this.separator = sep;
              const parts = this.meta.title.split(searchPattern);
              if (parts.length > 1) {
                this.siteName = parts[parts.length - 1].trim();
              }
              break;
            }
          }
        }
        if (!this.siteName) {
          this.siteName = ((_c = (_b = (_a = this.$store) == null ? void 0 : _a.state) == null ? void 0 : _b.system) == null ? void 0 : _c.title) || "Site Name";
        }
        if (!this.separator) {
          this.separator = "|";
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
  var _sfc_render$3 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("section", { staticClass: "k-seo-preview-section" }, [_c("header", { staticClass: "k-section-header" }, [_c("h2", { staticClass: "k-headline" }, [_vm._v(_vm._s(_vm.label || "SEO Preview"))])]), _vm.meta ? _c("div", { staticClass: "k-seo-previews" }, [_c("div", { staticClass: "k-seo-preview k-seo-preview--google" }, [_c("h3", { staticClass: "k-seo-preview__title" }, [_vm._v("Google Search Preview")]), _c("div", { staticClass: "k-seo-preview__content" }, [_c("div", { staticClass: "k-google-preview" }, [_c("cite", { staticClass: "k-google-preview__url" }, [_vm._v(_vm._s(_vm.displayUrl(_vm.meta.url)))]), _c("h3", { staticClass: "k-google-preview__title" }, [_vm._v(_vm._s(_vm.meta.title || "Page Title"))]), _c("p", { staticClass: "k-google-preview__description" }, [_vm._v(_vm._s(_vm.meta.description || "No description available"))])])])]), _c("div", { staticClass: "k-seo-preview k-seo-preview--twitter" }, [_c("h3", { staticClass: "k-seo-preview__title" }, [_vm._v("Share / Card Preview")]), _c("div", { staticClass: "k-seo-preview__content" }, [_c("div", { staticClass: "k-twitter-preview" }, [_vm.meta.ogImage ? _c("div", { staticClass: "k-twitter-preview__image", style: { backgroundImage: "url(" + _vm.meta.ogImage + ")" } }) : _vm._e(), _c("div", { staticClass: "k-twitter-preview__body" }, [_c("cite", { staticClass: "k-twitter-preview__url" }, [_vm._v(_vm._s(_vm.displayUrl(_vm.meta.url)))]), _c("h4", { staticClass: "k-twitter-preview__title" }, [_vm._v(_vm._s(_vm.meta.ogTitle || _vm.meta.title || "Page Title"))]), _c("p", { staticClass: "k-twitter-preview__description" }, [_vm._v(_vm._s(_vm.truncate(_vm.meta.ogDescription || _vm.meta.description, 140) || "No description"))])])])])])]) : _c("div", { staticClass: "k-seo-preview-loading" }, [_vm._v(" Loading preview... ")])]);
  };
  var _sfc_staticRenderFns$3 = [];
  _sfc_render$3._withStripped = true;
  var __component__$3 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$3,
    _sfc_render$3,
    _sfc_staticRenderFns$3
  );
  __component__$3.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/sections/seo-preview.vue";
  const SeoPreview = __component__$3.exports;
  const _sfc_main$2 = {
    props: {
      value: String,
      placeholder: {
        type: String,
        default: "No meta title"
      },
      pageId: {
        type: String,
        required: true
      },
      pageTitle: {
        type: String,
        default: ""
      },
      siteSettings: {
        type: Object,
        required: true
      },
      aiEnabled: {
        type: Boolean,
        default: true
      },
      isGenerating: {
        type: Boolean,
        default: false
      },
      buttonSize: {
        type: String,
        default: "xs"
      },
      fieldClass: {
        type: String,
        default: "k-meta-kit-dialog-table-field-title"
      }
    },
    computed: {
      isSitePage() {
        return this.pageId === "site";
      },
      // The title to use - either the meta title or fallback to page title
      effectiveTitle() {
        return this.value || this.pageTitle || "";
      },
      showPreview() {
        return !this.isSitePage && this.effectiveTitle && this.siteSettings.appendSiteName && this.siteSettings.siteMetaTitle;
      },
      fullTitle() {
        const titleToUse = this.effectiveTitle;
        if (!titleToUse || !this.siteSettings.appendSiteName) {
          return titleToUse;
        }
        const separator = this.siteSettings.titleSeparator || "|";
        const siteName = this.siteSettings.siteMetaTitle || "";
        if (!siteName) {
          return titleToUse;
        }
        return `${titleToUse} ${separator} ${siteName}`;
      },
      charCount() {
        const titleToUse = this.effectiveTitle;
        if (!titleToUse) return 0;
        if (this.isSitePage) {
          return titleToUse.length;
        }
        if (this.siteSettings.appendSiteName && this.siteSettings.siteMetaTitle) {
          return this.fullTitle.length;
        }
        return titleToUse.length;
      },
      statusClass() {
        const titleToUse = this.effectiveTitle;
        if (!titleToUse) return "";
        if (this.isSitePage) {
          return "";
        }
        let finalLength = titleToUse.length;
        if (this.siteSettings.appendSiteName) {
          finalLength = this.fullTitle.length;
        }
        const optimal = { min: 50, max: 60 };
        const warning = { min: 45, max: 66 };
        if (finalLength >= optimal.min && finalLength <= optimal.max) {
          return "k-meta-kit-status-success";
        }
        if (finalLength >= warning.min && finalLength <= warning.max) {
          return "k-meta-kit-status-warning";
        }
        return "k-meta-kit-status-error";
      }
    }
  };
  var _sfc_render$2 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { class: _vm.fieldClass }, [_c("k-input", { attrs: { "value": _vm.value, "placeholder": _vm.placeholder, "type": "text" }, on: { "input": function($event) {
      return _vm.$emit("input", $event);
    } } }), _vm.showPreview ? _c("div", { staticClass: "k-meta-kit-title-preview" }, [_vm._v(" " + _vm._s(_vm.fullTitle) + " ")]) : _vm._e(), _c("div", { staticClass: "k-meta-kit-dialog-field-meta" }, [_c("span", [_vm.value ? _c("span", { staticClass: "k-meta-kit-field-length", class: _vm.statusClass }, [_vm._v(" " + _vm._s(_vm.charCount) + " chars ")]) : _vm._e()]), _vm.aiEnabled ? _c("k-button", { attrs: { "icon": "sparkling", "size": _vm.buttonSize, "disabled": _vm.isGenerating, "title": _vm.buttonSize === "xs" ? "AI Generate" : void 0 }, on: { "click": function($event) {
      return _vm.$emit("generate");
    } } }, [_vm.buttonSize !== "xs" ? [_vm._v("AI Generate")] : _vm._e()], 2) : _vm._e()], 1), _vm.isGenerating ? _c("div", { staticClass: "k-meta-kit-dialog-generating" }, [_c("k-icon", { staticClass: "k-meta-kit-spinner", attrs: { "type": "loader" } }), _c("span", [_vm._v("Generating...")])], 1) : _vm._e()], 1);
  };
  var _sfc_staticRenderFns$2 = [];
  _sfc_render$2._withStripped = true;
  var __component__$2 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$2,
    _sfc_render$2,
    _sfc_staticRenderFns$2
  );
  __component__$2.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/components/parts/MetaKitTitleField.vue";
  const MetaKitTitleField = __component__$2.exports;
  const _sfc_main$1 = {
    props: {
      value: String,
      placeholder: {
        type: String,
        default: "No meta description"
      },
      aiEnabled: {
        type: Boolean,
        default: true
      },
      isGenerating: {
        type: Boolean,
        default: false
      },
      buttonSize: {
        type: String,
        default: "xs"
      },
      rows: {
        type: Number,
        default: 3
      },
      buttons: {
        type: [Boolean, String],
        default: true
      },
      fieldClass: {
        type: String,
        default: "k-meta-kit-dialog-table-field-desc"
      }
    },
    computed: {
      statusClass() {
        if (!this.value) return "";
        const length = this.value.length;
        const optimal = { min: 140, max: 160 };
        const warning = { min: 126, max: 176 };
        if (length >= optimal.min && length <= optimal.max) {
          return "k-meta-kit-status-success";
        }
        if (length >= warning.min && length <= warning.max) {
          return "k-meta-kit-status-warning";
        }
        return "k-meta-kit-status-error";
      }
    }
  };
  var _sfc_render$1 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { class: _vm.fieldClass }, [_c("k-input", { attrs: { "value": _vm.value, "placeholder": _vm.placeholder, "type": "textarea", "rows": _vm.rows, "buttons": _vm.buttons }, on: { "input": function($event) {
      return _vm.$emit("input", $event);
    } } }), _c("div", { staticClass: "k-meta-kit-dialog-field-meta" }, [_c("span", [_vm.value ? _c("span", { staticClass: "k-meta-kit-field-length", class: _vm.statusClass }, [_vm._v(" " + _vm._s(_vm.value.length) + " chars ")]) : _vm._e()]), _vm.aiEnabled ? _c("k-button", { attrs: { "icon": "sparkling", "size": _vm.buttonSize, "disabled": _vm.isGenerating, "title": _vm.buttonSize === "xs" ? "AI Generate" : void 0 }, on: { "click": function($event) {
      return _vm.$emit("generate");
    } } }, [_vm.buttonSize !== "xs" ? [_vm._v("AI Generate")] : _vm._e()], 2) : _vm._e()], 1), _vm.isGenerating ? _c("div", { staticClass: "k-meta-kit-dialog-generating" }, [_c("k-icon", { staticClass: "k-meta-kit-spinner", attrs: { "type": "loader" } }), _c("span", [_vm._v("Generating...")])], 1) : _vm._e()], 1);
  };
  var _sfc_staticRenderFns$1 = [];
  _sfc_render$1._withStripped = true;
  var __component__$1 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$1,
    _sfc_render$1,
    _sfc_staticRenderFns$1
  );
  __component__$1.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/components/parts/MetaKitDescriptionField.vue";
  const MetaKitDescriptionField = __component__$1.exports;
  const _sfc_main = {
    components: {
      MetaKitTitleField,
      MetaKitDescriptionField
    },
    props: {
      pages: Array,
      language: String,
      languages: Array,
      legacyMigration: {
        type: Boolean,
        default: false
      },
      aiEnabled: {
        type: Boolean,
        default: true
      },
      hasValidLicense: {
        type: Boolean,
        default: false
      },
      siteSettings: {
        type: Object,
        default: () => ({
          appendSiteName: true,
          siteMetaTitle: "",
          titleSeparator: "|"
        })
      }
    },
    data() {
      return {
        isLoadingPages: false,
        isGeneratingAll: false,
        isLoadingLegacy: false,
        isLoadingAllPages: false,
        isLoadingSinglePage: false,
        isMigratingAll: false,
        pagesData: this.pages || [],
        allPagesData: [],
        legacyPages: [],
        legacySummary: { total: 0, byLanguage: [] },
        currentEditPage: null,
        legacyDetection: {
          show: false,
          found: 0
        },
        fieldChoices: {},
        // { pageId: { fieldName: 'legacy|current|manual|ai', manualValue: '...' } }
        generatingFields: {},
        // { pageId: { fieldName: true } }
        // Pagination & Selection
        selectedPages: [],
        currentPage: 1,
        pageSize: 25,
        pageSizeOptions: [
          { value: 25, text: "25 per page" },
          { value: 50, text: "50 per page" },
          { value: 100, text: "100 per page" },
          { value: 99999, text: "All" }
        ],
        searchQuery: "",
        metadataFilter: "all",
        showPreviewInTable: false,
        // Bulk generation options
        bulkGenerateOptions: {
          title: true,
          description: true
        }
      };
    },
    computed: {
      filteredPages() {
        let pages = this.pagesData;
        if (this.metadataFilter !== "all") {
          pages = pages.filter((page) => {
            switch (this.metadataFilter) {
              case "missing-title":
                return !page.hasMetaTitle;
              case "missing-description":
                return !page.hasMetaDescription;
              case "missing-image":
                return !page.hasOgImage;
              case "complete":
                return page.hasMetaTitle && page.hasMetaDescription && page.hasOgImage;
              default:
                return true;
            }
          });
        }
        if (this.searchQuery.trim()) {
          const query = this.searchQuery.toLowerCase();
          pages = pages.filter((page) => {
            return page.title.toLowerCase().includes(query) || page.id.toLowerCase().includes(query) || page.template.toLowerCase().includes(query) || page.metaDescription && page.metaDescription.toLowerCase().includes(query);
          });
        }
        return pages;
      },
      paginatedPages() {
        if (this.pageSize >= 99999) {
          return this.filteredPages;
        }
        const start = (this.currentPage - 1) * this.pageSize;
        const end = start + this.pageSize;
        return this.filteredPages.slice(start, end);
      },
      totalPages() {
        if (this.pageSize >= 99999) {
          return 1;
        }
        return Math.ceil(this.filteredPages.length / this.pageSize);
      },
      isAllCurrentPageSelected() {
        if (this.paginatedPages.length === 0) return false;
        return this.paginatedPages.every((page) => this.selectedPages.includes(page.id));
      },
      pagesWithDescription() {
        return this.pagesData.filter((p) => p.hasMetaDescription).length;
      },
      pagesWithOgImage() {
        return this.pagesData.filter((p) => p.hasOgImage).length;
      },
      pagesNoIndex() {
        return this.pagesData.filter((p) => p.robots && p.robots.includes("noindex")).length;
      },
      filteredPagesWithDescription() {
        return this.filteredPages.filter((p) => p.hasMetaDescription).length;
      },
      filteredPagesWithOgImage() {
        return this.filteredPages.filter((p) => p.hasOgImage).length;
      },
      filteredPagesNoIndex() {
        return this.filteredPages.filter((p) => p.robots && p.robots.includes("noindex")).length;
      }
    },
    watch: {
      searchQuery() {
        this.currentPage = 1;
      }
    },
    created() {
    },
    methods: {
      getFullTitle(pageTitle) {
        if (!pageTitle || !this.siteSettings.appendSiteName) {
          return pageTitle || "";
        }
        const separator = this.siteSettings.titleSeparator || "|";
        const siteName = this.siteSettings.siteMetaTitle || "";
        if (!siteName) {
          return pageTitle;
        }
        return `${pageTitle} ${separator} ${siteName}`;
      },
      getStatusClass(hasField, length, fieldType = "description", pageTitle = null) {
        if (!hasField) return "";
        let optimal, warning;
        if (fieldType === "title") {
          if (pageTitle === null) {
            return "";
          }
          let finalLength = length;
          if (pageTitle && this.siteSettings.appendSiteName) {
            finalLength = this.getFullTitle(pageTitle).length;
          }
          optimal = { min: 50, max: 60 };
          warning = { min: 45, max: 66 };
          if (finalLength >= optimal.min && finalLength <= optimal.max) {
            return "k-meta-kit-status-success";
          }
          if (finalLength >= warning.min && finalLength <= warning.max) {
            return "k-meta-kit-status-warning";
          }
          return "k-meta-kit-status-error";
        } else {
          optimal = { min: 140, max: 160 };
          warning = { min: 126, max: 176 };
          if (length >= optimal.min && length <= optimal.max) {
            return "k-meta-kit-status-success";
          }
          if (length >= warning.min && length <= warning.max) {
            return "k-meta-kit-status-warning";
          }
          return "k-meta-kit-status-error";
        }
      },
      async refreshPages() {
        this.isLoadingPages = true;
        try {
          const response = await this.$api.get("meta-kit/pages");
          if (response.status === "success") {
            this.pagesData = response.data;
          }
        } catch (error) {
          window.panel.notification.error("Failed to refresh pages");
        } finally {
          this.isLoadingPages = false;
        }
      },
      async generateDescription(pageId) {
        try {
          const response = await this.$api.post("meta-kit/generate-description", { pageId });
          if (response.status === "success") {
            window.panel.notification.success(response.message || "Description generated");
            await this.refreshPages();
          } else {
            window.panel.notification.error(response.message || "Failed to generate description");
          }
        } catch (error) {
          let errorMessage = "Failed to generate description";
          if (error.message) {
            errorMessage += `: ${error.message}`;
          } else if (error.error) {
            errorMessage += `: ${error.error}`;
          }
          window.panel.notification.error(errorMessage);
          console.error("Generation error:", error);
        }
      },
      generateAllDescriptions() {
        this.bulkGenerateOptions.title = true;
        this.bulkGenerateOptions.description = true;
        this.$refs.bulkGenerateDialog.open();
      },
      async performBulkGeneration() {
        this.$refs.bulkGenerateDialog.close();
        if (!this.bulkGenerateOptions.title && !this.bulkGenerateOptions.description) {
          window.panel.notification.error("Please select at least one field to generate");
          return;
        }
        this.isGeneratingAll = true;
        const fields = [];
        if (this.bulkGenerateOptions.title) fields.push("titles");
        if (this.bulkGenerateOptions.description) fields.push("descriptions");
        const fieldText = fields.join(" and ");
        const loadingNotification = window.panel.notification.open({
          message: `Generating ${fieldText} with AI...`,
          type: "info",
          timeout: 0
          // Don't auto-close
        });
        try {
          const response = await this.$api.post("meta-kit/generate-all", {
            generateTitle: this.bulkGenerateOptions.title,
            generateDescription: this.bulkGenerateOptions.description,
            pageIds: this.selectedPages
          });
          if (loadingNotification && loadingNotification.close) {
            loadingNotification.close();
          }
          if (response.status === "success") {
            const details = `Generated: ${response.generated || 0}, Skipped: ${response.skipped || 0}, Failed: ${response.failed || 0}`;
            window.panel.notification.success(`${response.message || "Generation completed!"} ${details}`);
            await this.refreshPages();
          } else {
            window.panel.notification.error(response.message || "Generation failed");
          }
        } catch (error) {
          if (loadingNotification && loadingNotification.close) {
            loadingNotification.close();
          }
          let errorMessage = `Failed to generate ${fieldText}`;
          if (error.message) {
            errorMessage += `: ${error.message}`;
          } else if (error.error) {
            errorMessage += `: ${error.error}`;
          } else if (typeof error === "string") {
            errorMessage += `: ${error}`;
          }
          window.panel.notification.error(errorMessage);
          console.error("Generation error details:", error);
        } finally {
          this.isGeneratingAll = false;
        }
      },
      async loadLegacySummary() {
        this.isLoadingLegacy = true;
        try {
          const res = await this.$api.get("meta-kit/legacy-summary");
          if (res && res.status === "success") {
            this.$set(this, "legacySummary", res);
          } else {
            this.$set(this, "legacySummary", { total: 0, byLanguage: [] });
          }
        } catch (e) {
          window.panel.notification.error("Failed to load legacy summary");
          this.$set(this, "legacySummary", { total: 0, byLanguage: [] });
        } finally {
          this.isLoadingLegacy = false;
        }
      },
      async showLegacyDialog() {
        this.$refs.legacyDialog.open();
        await this.loadLegacySummary();
      },
      async migrateAllLanguages() {
        if (this.legacySummary.total === 0) return;
        if (!confirm("This will migrate legacy SEO fields across all languages (default first). Continue?")) {
          return;
        }
        this.isMigratingAll = true;
        const loading = window.panel.notification.open({
          message: "Migrating legacy fields across all languages...",
          type: "info",
          timeout: 0
        });
        try {
          const res = await this.$api.post("meta-kit/convert-legacy-all-languages");
          if (loading && loading.close) loading.close();
          if (res && res.status === "success") {
            window.panel.notification.success(res.message || "Migration completed");
            await this.refreshPages();
            await this.loadLegacySummary();
          } else {
            window.panel.notification.error(res.message || "Migration failed");
          }
        } catch (e) {
          if (loading && loading.close) loading.close();
          window.panel.notification.error("Migration failed");
        } finally {
          this.isMigratingAll = false;
        }
      },
      dismissLegacyWarning() {
        this.legacyDetection.show = false;
        sessionStorage.setItem("metaKitLegacyDismissed", "true");
      },
      formatFieldName(fieldName) {
        const names = {
          "metaTitle": "Meta Title",
          "metaDescription": "Meta Description",
          "ogImage": "OG Image"
        };
        return names[fieldName] || fieldName;
      },
      formatFieldValue(value) {
        if (typeof value === "string") {
          return value;
        }
        if (Array.isArray(value)) {
          return `${value.length} image(s)`;
        }
        if (typeof value === "object") {
          return "File";
        }
        return String(value);
      },
      getFieldChoice(pageId, fieldName) {
        var _a, _b;
        return ((_b = (_a = this.fieldChoices[pageId]) == null ? void 0 : _a[fieldName]) == null ? void 0 : _b.choice) || null;
      },
      setFieldChoice(pageId, fieldName, choice) {
        if (!this.fieldChoices[pageId]) {
          this.$set(this.fieldChoices, pageId, {});
        }
        if (!this.fieldChoices[pageId][fieldName]) {
          this.$set(this.fieldChoices[pageId], fieldName, {});
        }
        this.$set(this.fieldChoices[pageId][fieldName], "choice", choice);
        if (choice === "manual") {
          const existingManualValue = this.getManualValue(pageId, fieldName);
          if (!existingManualValue) {
            let page = this.legacyPages.find((p) => p.id === pageId);
            if (!page) {
              page = this.allPagesData.find((p) => p.id === pageId);
            }
            if (!page) {
              page = this.currentEditPage;
            }
            if (page) {
              let prefillValue = "";
              if (fieldName === "metaTitle" && page.metaTitle) {
                prefillValue = page.metaTitle;
              } else if (fieldName === "metaDescription" && page.metaDescription) {
                prefillValue = page.metaDescription;
              } else if (page.legacy && page.legacy[fieldName]) {
                prefillValue = page.legacy[fieldName];
              }
              if (prefillValue) {
                this.setManualValue(pageId, fieldName, prefillValue);
              }
            }
          }
        }
      },
      getManualValue(pageId, fieldName) {
        var _a, _b;
        return ((_b = (_a = this.fieldChoices[pageId]) == null ? void 0 : _a[fieldName]) == null ? void 0 : _b.manualValue) || "";
      },
      setManualValue(pageId, fieldName, value) {
        if (!this.fieldChoices[pageId]) {
          this.$set(this.fieldChoices, pageId, {});
        }
        if (!this.fieldChoices[pageId][fieldName]) {
          this.$set(this.fieldChoices[pageId], fieldName, {});
        }
        this.$set(this.fieldChoices[pageId][fieldName], "manualValue", value);
      },
      getEditableValue(pageId, fieldName, currentValue) {
        var _a, _b;
        if ((_b = (_a = this.fieldChoices[pageId]) == null ? void 0 : _a[fieldName]) == null ? void 0 : _b.hasOwnProperty("manualValue")) {
          return this.fieldChoices[pageId][fieldName].manualValue;
        }
        return currentValue || "";
      },
      // Helper methods for title field rendering
      isSitePage(pageId) {
        return pageId === "site";
      },
      shouldShowTitlePreview(pageId, value) {
        return !this.isSitePage(pageId) && value && this.siteSettings.appendSiteName && this.siteSettings.siteMetaTitle;
      },
      getTitleCharCount(pageId, value) {
        if (!value) return 0;
        if (this.isSitePage(pageId)) {
          return value.length;
        }
        if (this.siteSettings.appendSiteName && this.siteSettings.siteMetaTitle) {
          return this.getFullTitle(value).length;
        }
        return value.length;
      },
      getTitleStatusClass(pageId, value) {
        if (!value) return "";
        const pageTitle = this.isSitePage(pageId) ? null : value;
        return this.getStatusClass(true, value.length, "title", pageTitle);
      },
      // Get all title field data at once to avoid multiple getEditableValue calls
      getTitleFieldData(pageId, currentValue) {
        const value = this.getEditableValue(pageId, "metaTitle", currentValue);
        return {
          value,
          showPreview: this.shouldShowTitlePreview(pageId, value),
          fullTitle: this.getFullTitle(value),
          charCount: this.getTitleCharCount(pageId, value),
          statusClass: this.getTitleStatusClass(pageId, value)
        };
      },
      // Helper for description status class
      getDescriptionStatusClass(value) {
        if (!value) return "";
        return this.getStatusClass(true, value.length, "description");
      },
      // Helper for table title status (uses page object)
      getFullTitleLength(page) {
        if (page.id === "site") {
          if (page.hasMetaTitle) {
            return page.metaTitleLength;
          }
          return page.title ? page.title.length : 0;
        }
        const titleToUse = page.hasMetaTitle ? page.metaTitle : page.title;
        if (!titleToUse) return 0;
        if (this.siteSettings.appendSiteName && this.siteSettings.siteMetaTitle) {
          const separator = this.siteSettings.titleSeparator || "|";
          const siteName = this.siteSettings.siteMetaTitle || "";
          const fullTitle = `${titleToUse} ${separator} ${siteName}`;
          return fullTitle.length;
        }
        return titleToUse.length;
      },
      getFullTitlePreview(page) {
        if (page.id === "site") {
          return page.hasMetaTitle ? page.metaTitle : page.title;
        }
        const titleToUse = page.hasMetaTitle ? page.metaTitle : page.title;
        if (!titleToUse) return "—";
        if (this.siteSettings.appendSiteName && this.siteSettings.siteMetaTitle) {
          const separator = this.siteSettings.titleSeparator || "|";
          const siteName = this.siteSettings.siteMetaTitle || "";
          return `${titleToUse} ${separator} ${siteName}`;
        }
        return titleToUse;
      },
      getTableTitleStatusClass(page) {
        if (page.id === "site") {
          return "";
        }
        const fullLength = this.getFullTitleLength(page);
        const titleToUse = page.hasMetaTitle ? page.metaTitle : page.title;
        return this.getStatusClass(true, fullLength, "title", titleToUse || "");
      },
      getTitleTooltip(page) {
        const titleToUse = page.hasMetaTitle ? page.metaTitle : page.title;
        if (!titleToUse) {
          return "No title";
        }
        if (page.id === "site") {
          if (page.hasMetaTitle && page.metaTitle) {
            return `${page.metaTitle}`;
          }
          return `${page.title}`;
        }
        let tooltip = "";
        if (page.hasMetaTitle && page.metaTitle) {
          tooltip = `${page.metaTitle}`;
        } else {
          tooltip = `${page.title}`;
        }
        if (this.siteSettings.appendSiteName && this.siteSettings.siteMetaTitle) {
          const separator = this.siteSettings.titleSeparator || "|";
          const siteName = this.siteSettings.siteMetaTitle || "";
          const preview = `${titleToUse} ${separator} ${siteName}`;
          tooltip = `${preview}`;
        }
        return tooltip;
      },
      getDescriptionTooltip(page) {
        if (!page.hasMetaDescription || !page.metaDescription) {
          return "No meta description";
        }
        const desc = page.metaDescription;
        if (desc.length > 200) {
          return desc.substring(0, 200) + "...";
        }
        return desc;
      },
      hasFieldChanged(pageId, fieldName, currentValue, legacyValue) {
        const choice = this.getFieldChoice(pageId, fieldName);
        if (!choice) return false;
        if (choice === "legacy") return true;
        const manualValue = this.getManualValue(pageId, fieldName);
        if (choice === "keep" || choice === "current") {
          return manualValue && manualValue !== currentValue;
        }
        if (choice === "ai") {
          return !!manualValue;
        }
        return false;
      },
      hasAnyFieldChanged(pageId, page) {
        var _a, _b, _c, _d;
        const hasTitleChange = ((_b = (_a = this.fieldChoices[pageId]) == null ? void 0 : _a.metaTitle) == null ? void 0 : _b.manualValue) !== void 0 && this.fieldChoices[pageId].metaTitle.manualValue !== page.metaTitle;
        const hasDescChange = ((_d = (_c = this.fieldChoices[pageId]) == null ? void 0 : _c.metaDescription) == null ? void 0 : _d.manualValue) !== void 0 && this.fieldChoices[pageId].metaDescription.manualValue !== page.metaDescription;
        return hasTitleChange || hasDescChange;
      },
      async applyAllFields(pageId, page) {
        var _a, _b, _c, _d;
        let appliedCount = 0;
        const hasTitleChange = ((_b = (_a = this.fieldChoices[pageId]) == null ? void 0 : _a.metaTitle) == null ? void 0 : _b.manualValue) !== void 0 && this.fieldChoices[pageId].metaTitle.manualValue !== page.metaTitle;
        const hasDescChange = ((_d = (_c = this.fieldChoices[pageId]) == null ? void 0 : _c.metaDescription) == null ? void 0 : _d.manualValue) !== void 0 && this.fieldChoices[pageId].metaDescription.manualValue !== page.metaDescription;
        if (hasTitleChange) {
          const titleValue = this.fieldChoices[pageId].metaTitle.manualValue;
          try {
            await this.$api.post("meta-kit/apply-single-field", {
              pageId,
              fieldName: "metaTitle",
              value: titleValue
            });
            appliedCount++;
          } catch (error) {
            window.panel.notification.error("Failed to update meta title");
          }
        }
        if (hasDescChange) {
          const descValue = this.fieldChoices[pageId].metaDescription.manualValue;
          try {
            await this.$api.post("meta-kit/apply-single-field", {
              pageId,
              fieldName: "metaDescription",
              value: descValue
            });
            appliedCount++;
          } catch (error) {
            window.panel.notification.error("Failed to update meta description");
          }
        }
        if (appliedCount > 0) {
          window.panel.notification.success(`Updated ${appliedCount} field${appliedCount > 1 ? "s" : ""}`);
          await this.refreshPages();
          const pageInAllPages = this.allPagesData.find((p) => p.id === pageId);
          if (pageInAllPages) {
            if (hasTitleChange) {
              const titleValue = this.fieldChoices[pageId].metaTitle.manualValue;
              this.$set(pageInAllPages, "metaTitle", titleValue);
              this.$set(pageInAllPages, "hasMetaTitle", titleValue && titleValue.length > 0);
              this.$set(pageInAllPages, "metaTitleLength", titleValue ? titleValue.length : 0);
            }
            if (hasDescChange) {
              const descValue = this.fieldChoices[pageId].metaDescription.manualValue;
              this.$set(pageInAllPages, "metaDescription", descValue);
              this.$set(pageInAllPages, "hasMetaDescription", descValue && descValue.length > 0);
              this.$set(pageInAllPages, "metaDescriptionLength", descValue ? descValue.length : 0);
            }
          }
          if (this.fieldChoices[pageId]) {
            this.$delete(this.fieldChoices, pageId);
          }
          this.$forceUpdate();
        }
      },
      isGeneratingField(pageId, fieldName) {
        var _a;
        return ((_a = this.generatingFields[pageId]) == null ? void 0 : _a[fieldName]) || false;
      },
      async generateFieldAI(pageId, fieldName) {
        this.setFieldChoice(pageId, fieldName, "ai");
        if (!this.generatingFields[pageId]) {
          this.$set(this.generatingFields, pageId, {});
        }
        this.$set(this.generatingFields[pageId], fieldName, true);
        try {
          const response = await this.$api.post("meta-kit/generate-field", {
            pageId,
            fieldName
          });
          if (response.status === "success" && response.content) {
            this.setManualValue(pageId, fieldName, response.content);
            window.panel.notification.success("AI content generated successfully");
          } else {
            window.panel.notification.error(response.message || "Failed to generate content");
          }
        } catch (error) {
          window.panel.notification.error("Failed to generate content");
        } finally {
          this.$set(this.generatingFields[pageId], fieldName, false);
        }
      },
      async applySingleField(pageId, fieldName) {
        let page = this.legacyPages.find((p) => p.id === pageId);
        if (!page) {
          page = this.allPagesData.find((p) => p.id === pageId);
        }
        if (!page && this.currentEditPage && this.currentEditPage.id === pageId) {
          page = this.currentEditPage;
        }
        if (!page) return;
        const choice = this.getFieldChoice(pageId, fieldName);
        if (!choice) {
          window.panel.notification.error("Please select an option first");
          return;
        }
        let value;
        if (choice === "legacy") {
          if (page.fields && page.fields[fieldName]) {
            value = page.fields[fieldName];
          } else if (page.legacy && page.legacy[fieldName]) {
            value = page.legacy[fieldName];
          }
          if (!value) {
            window.panel.notification.error("Legacy value not found");
            return;
          }
        } else if (choice === "current" || choice === "keep" || choice === "ai") {
          const manualValue = this.getManualValue(pageId, fieldName);
          if (manualValue) {
            value = manualValue;
          } else {
            if (page.fields && page.current) {
              value = page.current[fieldName];
            } else if (page[fieldName]) {
              value = page[fieldName];
            } else {
              window.panel.notification.success("No changes to apply");
              return;
            }
          }
        }
        try {
          const response = await this.$api.post("meta-kit/apply-single-field", {
            pageId,
            fieldName,
            value
          });
          if (response.status === "success") {
            window.panel.notification.success(`${this.formatFieldName(fieldName)} updated successfully`);
            await this.refreshPages();
            const pageInAllPages = this.allPagesData.find((p) => p.id === pageId);
            if (pageInAllPages) {
              this.$set(pageInAllPages, fieldName, value);
              if (fieldName === "metaTitle") {
                this.$set(pageInAllPages, "hasMetaTitle", value && value.length > 0);
                this.$set(pageInAllPages, "metaTitleLength", value ? value.length : 0);
              } else if (fieldName === "metaDescription") {
                this.$set(pageInAllPages, "hasMetaDescription", value && value.length > 0);
                this.$set(pageInAllPages, "metaDescriptionLength", value ? value.length : 0);
              } else if (fieldName === "ogImage") {
                this.$set(pageInAllPages, "hasOgImage", value && value.length > 0);
              }
              if (pageInAllPages.legacy && pageInAllPages.legacy[fieldName]) {
                delete pageInAllPages.legacy[fieldName];
              }
            }
            if (this.fieldChoices[pageId] && this.fieldChoices[pageId][fieldName]) {
              this.$delete(this.fieldChoices[pageId], fieldName);
            }
            const pageInLegacy = this.legacyPages.find((p) => p.id === pageId);
            if (pageInLegacy) {
              if (!pageInLegacy.current) {
                this.$set(pageInLegacy, "current", {});
              }
              this.$set(pageInLegacy.current, fieldName, value);
            }
            this.$forceUpdate();
          } else {
            window.panel.notification.error(response.message || "Failed to update field");
          }
        } catch (error) {
          let errorMessage = "Failed to update field";
          if (error.message) {
            errorMessage += `: ${error.message}`;
          } else if (error.error) {
            errorMessage += `: ${error.error}`;
          }
          window.panel.notification.error(errorMessage);
          console.error("Field update error:", error);
        }
      },
      async showAllPagesDialog() {
        this.$refs.allPagesDialog.open();
        await this.loadAllPages();
      },
      async loadAllPages() {
        this.isLoadingAllPages = true;
        try {
          const response = await this.$api.get("meta-kit/pages-with-content");
          if (response.status === "success") {
            this.allPagesData = response.data;
          }
        } catch (error) {
          window.panel.notification.error("Failed to load pages");
        } finally {
          this.isLoadingAllPages = false;
        }
      },
      editSinglePage(pageId) {
        const page = this.allPagesData.find((p) => p.id === pageId);
        if (page && page.panelUrl) {
          window.panel.view.open(page.panelUrl);
        }
      },
      editPageInPanel(panelUrl) {
        if (panelUrl) {
          window.panel.view.open(panelUrl);
        }
      },
      async editSinglePageMetadata(pageId) {
        this.$refs.singlePageDialog.open();
        this.isLoadingSinglePage = true;
        try {
          const response = await this.$api.get("meta-kit/single-page", { pageId });
          if (response.status === "success") {
            this.currentEditPage = response.data;
          }
        } catch (error) {
          window.panel.notification.error("Failed to load page");
        } finally {
          this.isLoadingSinglePage = false;
        }
      },
      async applySingleFieldAndClose(pageId, fieldName) {
        if (fieldName === "all") {
          await this.applyAllFields(pageId, this.currentEditPage);
        } else {
          await this.applySingleField(pageId, fieldName);
        }
        if (this.currentEditPage && this.currentEditPage.id === pageId) {
          try {
            const response = await this.$api.get("meta-kit/single-page", { pageId });
            if (response.status === "success") {
              this.currentEditPage = response.data;
            }
          } catch (error) {
          }
        }
      },
      openPageSeoTab(page) {
        if (!page) return;
        this.$refs.singlePageDialog.close();
        const pageUrl = page.panelUrl;
        window.panel.view.open(pageUrl);
      },
      goToLanguage(langCode) {
        if (langCode === this.language) return;
        const baseUrl = window.location.origin + window.location.pathname.split("?")[0];
        window.location.href = baseUrl + "?language=" + langCode;
      },
      // Pagination methods
      changePageSize(newSize) {
        this.pageSize = parseInt(newSize);
        this.currentPage = 1;
      },
      nextPage() {
        if (this.currentPage < this.totalPages) {
          this.currentPage++;
        }
      },
      previousPage() {
        if (this.currentPage > 1) {
          this.currentPage--;
        }
      },
      // Selection methods
      isPageSelected(pageId) {
        return this.selectedPages.includes(pageId);
      },
      togglePageSelection(pageId) {
        const index = this.selectedPages.indexOf(pageId);
        if (index > -1) {
          this.selectedPages.splice(index, 1);
        } else {
          this.selectedPages.push(pageId);
        }
      },
      toggleSelectAllCurrentPage() {
        const allSelected = this.isAllCurrentPageSelected;
        this.paginatedPages.forEach((page) => {
          const index = this.selectedPages.indexOf(page.id);
          if (allSelected) {
            if (index > -1) {
              this.selectedPages.splice(index, 1);
            }
          } else {
            if (index === -1) {
              this.selectedPages.push(page.id);
            }
          }
        });
      },
      async showSelectedPagesDialog() {
        if (this.selectedPages.length === 0) return;
        this.$refs.allPagesDialog.open();
        await this.reloadSelectedPages();
      },
      async reloadSelectedPages() {
        if (this.selectedPages.length === 0) return;
        this.isLoadingAllPages = true;
        try {
          const response = await this.$api.get("meta-kit/pages-with-content", {
            pageIds: this.selectedPages
          });
          if (response.status === "success") {
            this.allPagesData = response.data.filter((p) => this.selectedPages.includes(p.id));
          }
        } catch (error) {
          window.panel.notification.error("Failed to load selected pages");
        } finally {
          this.isLoadingAllPages = false;
        }
      }
    }
  };
  var _sfc_render = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("k-panel-inside", { staticClass: "k-meta-kit-view" }, [!_vm.hasValidLicense ? _c("div", { staticClass: "k-meta-kit-warning" }, [_c("k-box", { attrs: { "theme": "negative" } }, [_c("k-icon", { attrs: { "type": "alert" } }), _c("span", [_c("strong", [_vm._v("No valid license:")]), _vm._v(" AI generation and saving changes are disabled. Meta tags are limited to 20 characters. Please activate your license to use all features.")])], 1)], 1) : _vm._e(), _vm.languages && _vm.languages.length > 1 ? _c("div", { staticClass: "k-meta-kit-language-bar" }, [_c("k-button-group", _vm._l(_vm.languages, function(lang) {
      return _c("k-button", { key: lang.code, attrs: { "theme": lang.code === _vm.language ? "positive" : "", "size": "xs" }, on: { "click": function($event) {
        return _vm.goToLanguage(lang.code);
      } } }, [_vm._v(" " + _vm._s(lang.code.toUpperCase()) + " ")]);
    }), 1), _vm.legacyMigration ? _c("k-button", { attrs: { "icon": "download", "size": "xs" }, on: { "click": _vm.showLegacyDialog } }, [_vm._v("Legacy Migration")]) : _vm._e()], 1) : _vm._e(), _vm.legacyMigration && _vm.legacyDetection.show && _vm.legacyDetection.found > 0 ? _c("div", { staticClass: "k-meta-kit-warning" }, [_c("k-box", { attrs: { "theme": "info" } }, [_c("k-icon", { attrs: { "type": "info" } }), _c("span", [_vm._v("Found " + _vm._s(_vm.legacyDetection.found) + " pages with legacy SEO metadata")]), _c("k-button", { attrs: { "icon": "download" }, on: { "click": _vm.showLegacyDialog } }, [_vm._v("View & Convert")]), _c("k-button", { attrs: { "icon": "cancel" }, on: { "click": _vm.dismissLegacyWarning } }, [_vm._v("Dismiss")])], 1)], 1) : _vm._e(), _c("div", { staticClass: "k-meta-kit-stats" }, [_c("div", { staticClass: "k-meta-kit-stats-card" }, [_c("h3", [_vm._v("Total Pages")]), _c("p", [_vm._v(_vm._s(_vm.filteredPages.length)), _vm.searchQuery ? _c("span", { staticClass: "k-meta-kit-stats-total" }, [_vm._v(" / " + _vm._s(_vm.pagesData.length))]) : _vm._e()])]), _c("div", { staticClass: "k-meta-kit-stats-card" }, [_c("h3", [_vm._v("With Description")]), _c("p", [_vm._v(_vm._s(_vm.filteredPagesWithDescription)), _vm.searchQuery ? _c("span", { staticClass: "k-meta-kit-stats-total" }, [_vm._v(" / " + _vm._s(_vm.pagesWithDescription))]) : _vm._e()])]), _c("div", { staticClass: "k-meta-kit-stats-card" }, [_c("h3", [_vm._v("With OG Image")]), _c("p", [_vm._v(_vm._s(_vm.filteredPagesWithOgImage)), _vm.searchQuery ? _c("span", { staticClass: "k-meta-kit-stats-total" }, [_vm._v(" / " + _vm._s(_vm.pagesWithOgImage))]) : _vm._e()])]), _c("div", { staticClass: "k-meta-kit-stats-card" }, [_c("h3", [_vm._v("No Index")]), _c("p", [_vm._v(_vm._s(_vm.filteredPagesNoIndex)), _vm.searchQuery ? _c("span", { staticClass: "k-meta-kit-stats-total" }, [_vm._v(" / " + _vm._s(_vm.pagesNoIndex))]) : _vm._e()])])]), _c("div", { staticClass: "k-meta-kit-actions" }, [_c("k-button-group", [_c("k-button", { attrs: { "icon": "edit", "disabled": _vm.selectedPages.length === 0 }, on: { "click": _vm.showSelectedPagesDialog } }, [_vm._v(" Edit Selected (" + _vm._s(_vm.selectedPages.length) + ") ")]), _vm.aiEnabled ? _c("k-button", { attrs: { "icon": "sparkling", "disabled": _vm.isGeneratingAll || _vm.selectedPages.length === 0, "progress": _vm.isGeneratingAll }, on: { "click": _vm.generateAllDescriptions } }, [_vm._v(" Generate Missing (" + _vm._s(_vm.selectedPages.length) + ") ")]) : _vm._e(), _c("k-button", { attrs: { "icon": "refresh" }, on: { "click": _vm.refreshPages } })], 1), _c("div", { staticClass: "k-meta-kit-controls" }, [_c("k-button", { attrs: { "size": "sm", "title": _vm.showPreviewInTable ? "Show character counts" : "Show preview text" }, on: { "click": function($event) {
      _vm.showPreviewInTable = !_vm.showPreviewInTable;
    } } }, [_vm._v(" " + _vm._s(_vm.showPreviewInTable ? "Count" : "Preview") + " ")]), _c("div", { staticClass: "k-meta-kit-search-wrapper" }, [_c("k-search-input", { staticClass: "k-meta-kit-search", attrs: { "icon": "search", "value": _vm.searchQuery, "placeholder": "Filter pages..." }, on: { "input": function($event) {
      _vm.searchQuery = $event;
    } } }), _vm.searchQuery ? _c("button", { staticClass: "k-meta-kit-search-clear", attrs: { "title": "Clear search" }, on: { "click": function($event) {
      _vm.searchQuery = "";
    } } }, [_c("k-icon", { attrs: { "type": "cancel" } })], 1) : _vm._e()], 1), _c("select", { staticClass: "k-meta-kit-metadata-filter", domProps: { "value": _vm.metadataFilter }, on: { "change": function($event) {
      _vm.metadataFilter = $event.target.value;
    } } }, [_c("option", { attrs: { "value": "all" } }, [_vm._v("All Pages")]), _c("option", { attrs: { "value": "missing-title" } }, [_vm._v("Missing Title")]), _c("option", { attrs: { "value": "missing-description" } }, [_vm._v("Missing Description")]), _c("option", { attrs: { "value": "missing-image" } }, [_vm._v("Missing OG Image")]), _c("option", { attrs: { "value": "complete" } }, [_vm._v("Complete Metadata")])]), _c("select", { staticClass: "k-meta-kit-pagesize-select", domProps: { "value": _vm.pageSize }, on: { "change": function($event) {
      return _vm.changePageSize($event.target.value);
    } } }, _vm._l(_vm.pageSizeOptions, function(option) {
      return _c("option", { key: option.value, domProps: { "value": option.value } }, [_vm._v(" " + _vm._s(option.text) + " ")]);
    }), 0)], 1)], 1), _c("div", { staticClass: "k-meta-kit-table", class: { "k-meta-kit-table-preview": _vm.showPreviewInTable } }, [_c("table", [_c("thead", [_c("tr", [_c("th", { staticClass: "k-meta-kit-table-checkbox" }, [_c("input", { attrs: { "type": "checkbox" }, domProps: { "checked": _vm.isAllCurrentPageSelected }, on: { "change": _vm.toggleSelectAllCurrentPage } })]), _c("th", [_vm._v("#")]), _c("th", [_vm._v("Page")]), !_vm.showPreviewInTable ? _c("th", [_vm._v("Template")]) : _vm._e(), _c("th", { staticClass: "k-meta-kit-table-center" }, [_vm._v("Title")]), _c("th", [_vm._v("Description")]), _c("th", [_vm._v("Image")]), !_vm.showPreviewInTable ? _c("th", [_vm._v("Robots")]) : _vm._e(), _c("th", [_vm._v("Actions")])])]), _c("tbody", _vm._l(_vm.paginatedPages, function(page, index) {
      return _c("tr", { key: page.id }, [_c("td", { staticClass: "k-meta-kit-table-checkbox" }, [_c("input", { attrs: { "type": "checkbox" }, domProps: { "checked": _vm.isPageSelected(page.id) }, on: { "change": function($event) {
        return _vm.togglePageSelection(page.id);
      } } })]), _c("td", [_vm._v(_vm._s((_vm.currentPage - 1) * _vm.pageSize + index + 1))]), _c("td", [_c("div", { staticClass: "k-meta-kit-table-page" }, [_c("a", { staticClass: "k-link", attrs: { "href": page.panelUrl } }, [_vm._v(_vm._s(page.title))]), _c("span", { staticClass: "k-meta-kit-table-page-id" }, [_vm._v(_vm._s(page.id))])])]), !_vm.showPreviewInTable ? _c("td", [_vm._v(_vm._s(page.template))]) : _vm._e(), _c("td", { class: _vm.showPreviewInTable ? "" : "k-meta-kit-table-center" }, [_c("span", { staticClass: "k-meta-kit-table-tooltip", class: _vm.getTableTitleStatusClass(page), attrs: { "title": _vm.getTitleTooltip(page) } }, [_vm.showPreviewInTable ? [_vm._v(" " + _vm._s(_vm.getFullTitlePreview(page)) + " ")] : [_vm._v(" " + _vm._s(_vm.getFullTitleLength(page)) + " ")]], 2)]), _c("td", { class: _vm.showPreviewInTable ? "" : "k-meta-kit-table-center" }, [_c("span", { staticClass: "k-meta-kit-table-tooltip", class: _vm.getStatusClass(page.hasMetaDescription, page.metaDescriptionLength, "description"), attrs: { "title": _vm.getDescriptionTooltip(page) } }, [_vm.showPreviewInTable ? [_vm._v(" " + _vm._s(page.metaDescription || "—") + " ")] : [_vm._v(" " + _vm._s(page.hasMetaDescription ? page.metaDescriptionLength : "—") + " ")]], 2)]), _c("td", { staticClass: "k-meta-kit-table-center" }, [page.hasOgImage ? _c("k-icon", { staticClass: "k-meta-kit-icon-success", attrs: { "type": "check" } }) : _c("span", [_vm._v("—")])], 1), !_vm.showPreviewInTable ? _c("td", { staticClass: "k-meta-kit-table-center" }, [page.robots && page.robots.includes("noindex") ? _c("span", { staticClass: "k-meta-kit-robots-noindex" }, [_vm._v("noindex")]) : _c("span", [_vm._v("—")])]) : _vm._e(), _c("td", { staticClass: "k-meta-kit-table-center" }, [_c("div", { staticClass: "k-meta-kit-table-actions" }, [_c("k-button", { attrs: { "icon": "edit", "size": "sm", "title": "Edit Metadata" }, on: { "click": function($event) {
        return _vm.editSinglePageMetadata(page.id);
      } } }), _vm.aiEnabled ? _c("k-button", { attrs: { "icon": "sparkling", "size": "sm", "disabled": page.hasMetaDescription, "title": "Generate Description" }, on: { "click": function($event) {
        return _vm.generateDescription(page.id);
      } } }) : _vm._e()], 1)])]);
    }), 0)])]), _vm.totalPages > 1 ? _c("div", { staticClass: "k-meta-kit-pagination" }, [_c("k-button", { attrs: { "icon": "angle-left", "disabled": _vm.currentPage === 1 }, on: { "click": _vm.previousPage } }), _c("span", { staticClass: "k-meta-kit-pagination-info" }, [_vm._v(" Page " + _vm._s(_vm.currentPage) + " of " + _vm._s(_vm.totalPages) + " "), _vm.searchQuery ? [_vm._v("(" + _vm._s(_vm.filteredPages.length) + " of " + _vm._s(_vm.pagesData.length) + ")")] : [_vm._v("(" + _vm._s(_vm.pagesData.length) + " total)")]], 2), _c("k-button", { attrs: { "icon": "angle-right", "disabled": _vm.currentPage === _vm.totalPages }, on: { "click": _vm.nextPage } })], 1) : _vm._e(), _c("k-dialog", { ref: "legacyDialog", attrs: { "size": "medium", "cancelButton": "Close", "submitButton": "" } }, [_c("k-headline", [_vm._v("Legacy SEO Migration")]), _vm.isLoadingLegacy ? _c("div", { staticClass: "k-meta-kit-loading" }, [_c("k-icon", { staticClass: "k-meta-kit-spinner", attrs: { "type": "loader" } }), _c("span", [_vm._v("Scanning all languages for legacy metadata...")])], 1) : _c("div", [_c("div", { staticClass: "k-meta-kit-legacy-list" }, [_c("p", [_vm._v("Summary of legacy fields by language:")]), _c("ul", _vm._l(_vm.legacySummary.byLanguage, function(item) {
      return _c("li", { key: item.code }, [_c("strong", [_vm._v(_vm._s(item.code.toUpperCase()))]), _vm._v(": " + _vm._s(item.count) + " item(s) ")]);
    }), 0), _c("p", [_c("strong", [_vm._v("Total:")]), _vm._v(" " + _vm._s(_vm.legacySummary.total) + " item(s) across all languages")]), _c("k-box", { attrs: { "theme": "negative" } }, [_c("k-icon", { attrs: { "type": "alert" } }), _c("span", [_vm._v("Warning: Legacy metadata will be migrated to the new meta kit fields. The old fields will be removed.")])], 1), _c("k-button", { attrs: { "icon": "download", "disabled": _vm.isMigratingAll || _vm.legacySummary.total === 0, "progress": _vm.isMigratingAll, "theme": "positive" }, on: { "click": _vm.migrateAllLanguages } }, [_vm._v(" Migrate All Languages ")])], 1)])], 1), _c("k-dialog", { ref: "allPagesDialog", attrs: { "size": "huge", "cancelButton": "Close", "submitButton": "" } }, [_c("k-headline", [_vm._v("Edit Selected Pages (" + _vm._s(_vm.allPagesData.length) + ")")]), _vm.isLoadingAllPages ? _c("div", { staticClass: "k-meta-kit-loading" }, [_c("k-icon", { staticClass: "k-meta-kit-spinner", attrs: { "type": "loader" } }), _c("span", [_vm._v("Loading pages...")])], 1) : _vm.allPagesData.length > 0 ? _c("div", { staticClass: "k-meta-kit-dialog-table-wrapper" }, _vm._l(_vm.allPagesData, function(page) {
      return _c("div", { key: page.id, staticClass: "k-meta-kit-dialog-table-page" }, [_c("div", { staticClass: "k-meta-kit-dialog-page-info" }, [_c("a", { staticClass: "k-link", attrs: { "href": page.panelUrl } }, [_vm._v(_vm._s(page.title))]), _c("a", { staticClass: "k-link k-meta-kit-page-id", attrs: { "href": page.panelUrl } }, [_vm._v(_vm._s(page.id))])]), _c("meta-kit-title-field", { attrs: { "value": _vm.getEditableValue(page.id, "metaTitle", page.metaTitle), "page-id": page.id, "page-title": page.title, "site-settings": _vm.siteSettings, "ai-enabled": _vm.aiEnabled, "is-generating": _vm.isGeneratingField(page.id, "metaTitle"), "placeholder": page.metaTitle || "No meta title", "button-size": "xs", "field-class": "k-meta-kit-dialog-table-field-title" }, on: { "input": function($event) {
        return _vm.setManualValue(page.id, "metaTitle", $event);
      }, "generate": function($event) {
        return _vm.generateFieldAI(page.id, "metaTitle");
      } } }), _c("meta-kit-description-field", { attrs: { "value": _vm.getEditableValue(page.id, "metaDescription", page.metaDescription), "ai-enabled": _vm.aiEnabled, "is-generating": _vm.isGeneratingField(page.id, "metaDescription"), "placeholder": page.metaDescription || "No meta description", "button-size": "xs", "rows": 3, "field-class": "k-meta-kit-dialog-table-field-desc" }, on: { "input": function($event) {
        return _vm.setManualValue(page.id, "metaDescription", $event);
      }, "generate": function($event) {
        return _vm.generateFieldAI(page.id, "metaDescription");
      } } }), _c("div", { staticClass: "k-meta-kit-dialog-table-actions" }, [_vm.hasAnyFieldChanged(page.id, page) ? _c("k-button", { attrs: { "icon": "check", "size": "sm", "theme": "positive" }, on: { "click": function($event) {
        return _vm.applyAllFields(page.id, page);
      } } }, [_vm._v(" Apply ")]) : _vm._e()], 1)], 1);
    }), 0) : _c("div", { staticClass: "k-meta-kit-empty" }, [_c("k-icon", { attrs: { "type": "check" } }), _c("p", [_vm._v("No pages found!")])], 1)], 1), _c("k-dialog", { ref: "singlePageDialog", attrs: { "size": "large", "cancelButton": "Close", "submitButton": "" } }, [_vm.currentEditPage ? _c("k-headline", [_vm._v("Edit: " + _vm._s(_vm.currentEditPage.title))]) : _vm._e(), _vm.isLoadingSinglePage ? _c("div", { staticClass: "k-meta-kit-loading" }, [_c("k-icon", { staticClass: "k-meta-kit-spinner", attrs: { "type": "loader" } }), _c("span", [_vm._v("Loading page...")])], 1) : _vm.currentEditPage ? _c("div", { staticClass: "k-meta-kit-single-edit" }, [_c("div", { staticClass: "k-meta-kit-single-field" }, [_c("label", { staticClass: "k-meta-kit-single-field-label" }, [_vm._v("Meta Title")]), _c("meta-kit-title-field", { attrs: { "value": _vm.getEditableValue(_vm.currentEditPage.id, "metaTitle", _vm.currentEditPage.metaTitle), "page-id": _vm.currentEditPage.id, "page-title": _vm.currentEditPage.title, "site-settings": _vm.siteSettings, "ai-enabled": _vm.aiEnabled, "is-generating": _vm.isGeneratingField(_vm.currentEditPage.id, "metaTitle"), "placeholder": _vm.currentEditPage.metaTitle || "No meta title set", "button-size": "sm", "field-class": "k-meta-kit-single-field-content" }, on: { "input": function($event) {
      return _vm.setManualValue(_vm.currentEditPage.id, "metaTitle", $event);
    }, "generate": function($event) {
      return _vm.generateFieldAI(_vm.currentEditPage.id, "metaTitle");
    } } })], 1), _c("div", { staticClass: "k-meta-kit-single-field" }, [_c("label", { staticClass: "k-meta-kit-single-field-label" }, [_vm._v("Meta Description")]), _c("meta-kit-description-field", { attrs: { "value": _vm.getEditableValue(_vm.currentEditPage.id, "metaDescription", _vm.currentEditPage.metaDescription), "ai-enabled": _vm.aiEnabled, "is-generating": _vm.isGeneratingField(_vm.currentEditPage.id, "metaDescription"), "placeholder": _vm.currentEditPage.metaDescription || "No meta description set", "button-size": "sm", "rows": 4, "buttons": "false", "field-class": "k-meta-kit-single-field-content" }, on: { "input": function($event) {
      return _vm.setManualValue(_vm.currentEditPage.id, "metaDescription", $event);
    }, "generate": function($event) {
      return _vm.generateFieldAI(_vm.currentEditPage.id, "metaDescription");
    } } })], 1), _c("div", { staticClass: "k-meta-kit-single-field" }, [_c("label", { staticClass: "k-meta-kit-single-field-label" }, [_vm._v("OG Image")]), _c("div", { staticClass: "k-meta-kit-single-field-content" }, [_vm.currentEditPage.ogImage ? _c("div", { staticClass: "k-meta-kit-og-image-current" }, [_c("img", { attrs: { "src": _vm.currentEditPage.ogImage.url, "alt": _vm.currentEditPage.ogImage.filename } }), _c("span", { staticClass: "k-meta-kit-og-image-filename" }, [_vm._v(_vm._s(_vm.currentEditPage.ogImage.filename))])]) : _c("div", { staticClass: "k-meta-kit-og-image-empty" }, [_vm._v(" No OG image set ")])])]), _c("div", { staticClass: "k-meta-kit-single-actions" }, [_c("k-button", { attrs: { "icon": "open" }, on: { "click": function($event) {
      return _vm.openPageSeoTab(_vm.currentEditPage);
    } } }, [_vm._v(" Edit in Panel ")]), _vm.hasAnyFieldChanged(_vm.currentEditPage.id, _vm.currentEditPage) ? _c("k-button", { attrs: { "icon": "check", "theme": "positive" }, on: { "click": function($event) {
      return _vm.applySingleFieldAndClose(_vm.currentEditPage.id, "all");
    } } }, [_vm._v(" Apply Changes ")]) : _vm._e()], 1)]) : _vm._e()], 1), _c("k-dialog", { ref: "bulkGenerateDialog", attrs: { "size": "medium" }, scopedSlots: _vm._u([{ key: "footer", fn: function() {
      return [_c("k-button-group", { staticClass: "k-meta-kit-bulk-buttons" }, [_c("k-button", { on: { "click": function($event) {
        return _vm.$refs.bulkGenerateDialog.close();
      } } }, [_vm._v("Cancel")]), _c("k-button", { attrs: { "icon": "sparkling", "theme": "positive", "disabled": !_vm.bulkGenerateOptions.title && !_vm.bulkGenerateOptions.description }, on: { "click": _vm.performBulkGeneration } }, [_vm._v(" Generate ")])], 1)];
    }, proxy: true }]) }, [_c("k-headline", [_vm._v("Generate Missing Metadata")]), _c("k-text", [_vm._v("Select which fields to generate for " + _vm._s(_vm.selectedPages.length) + " selected page(s):")]), _c("div", { staticClass: "k-meta-kit-bulk-options" }, [_c("label", { staticClass: "k-meta-kit-bulk-option" }, [_c("input", { directives: [{ name: "model", rawName: "v-model", value: _vm.bulkGenerateOptions.title, expression: "bulkGenerateOptions.title" }], attrs: { "type": "checkbox" }, domProps: { "checked": Array.isArray(_vm.bulkGenerateOptions.title) ? _vm._i(_vm.bulkGenerateOptions.title, null) > -1 : _vm.bulkGenerateOptions.title }, on: { "change": function($event) {
      var $$a = _vm.bulkGenerateOptions.title, $$el = $event.target, $$c = $$el.checked ? true : false;
      if (Array.isArray($$a)) {
        var $$v = null, $$i = _vm._i($$a, $$v);
        if ($$el.checked) {
          $$i < 0 && _vm.$set(_vm.bulkGenerateOptions, "title", $$a.concat([$$v]));
        } else {
          $$i > -1 && _vm.$set(_vm.bulkGenerateOptions, "title", $$a.slice(0, $$i).concat($$a.slice($$i + 1)));
        }
      } else {
        _vm.$set(_vm.bulkGenerateOptions, "title", $$c);
      }
    } } }), _c("div", { staticClass: "k-meta-kit-bulk-option-content" }, [_c("strong", [_vm._v("Meta Title")]), _c("span", [_vm._v("Generate meta titles for pages without one")])])]), _c("label", { staticClass: "k-meta-kit-bulk-option" }, [_c("input", { directives: [{ name: "model", rawName: "v-model", value: _vm.bulkGenerateOptions.description, expression: "bulkGenerateOptions.description" }], attrs: { "type": "checkbox" }, domProps: { "checked": Array.isArray(_vm.bulkGenerateOptions.description) ? _vm._i(_vm.bulkGenerateOptions.description, null) > -1 : _vm.bulkGenerateOptions.description }, on: { "change": function($event) {
      var $$a = _vm.bulkGenerateOptions.description, $$el = $event.target, $$c = $$el.checked ? true : false;
      if (Array.isArray($$a)) {
        var $$v = null, $$i = _vm._i($$a, $$v);
        if ($$el.checked) {
          $$i < 0 && _vm.$set(_vm.bulkGenerateOptions, "description", $$a.concat([$$v]));
        } else {
          $$i > -1 && _vm.$set(_vm.bulkGenerateOptions, "description", $$a.slice(0, $$i).concat($$a.slice($$i + 1)));
        }
      } else {
        _vm.$set(_vm.bulkGenerateOptions, "description", $$c);
      }
    } } }), _c("div", { staticClass: "k-meta-kit-bulk-option-content" }, [_c("strong", [_vm._v("Meta Description")]), _c("span", [_vm._v("Generate meta descriptions for pages without one")])])])])], 1)], 1);
  };
  var _sfc_staticRenderFns = [];
  _sfc_render._withStripped = true;
  var __component__ = /* @__PURE__ */ normalizeComponent(
    _sfc_main,
    _sfc_render,
    _sfc_staticRenderFns
  );
  __component__.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/components/MetaKitView.vue";
  const MetaKitView = __component__.exports;
  panel.plugin("tearoom1/meta-kit", {
    components: {
      "meta-kit-view": MetaKitView
    },
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
            generatedText: null,
            aiEnabled: true
          };
        },
        async created() {
          try {
            const response = await this.$api.get("meta-kit/pages");
            this.aiEnabled = response.aiEnabled ?? true;
          } catch (error) {
            console.warn("Could not check AI status:", error);
            this.aiEnabled = false;
          }
        },
        template: `
        <k-field v-bind="$props" class="k-meta-kit-generator-field">
          <div v-if="!aiEnabled" class="k-meta-kit-generator__disabled">
            <k-box theme="info">
              <k-text>AI generation is disabled.</k-text>
            </k-box>
          </div>
          <template v-else>
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
          </template>
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
            const skipFields = ["title", "slug", "template", "ogimage"];
            if (values.title && typeof values.title === "string") {
              texts.push(values.title);
            }
            for (const [key, value] of Object.entries(values)) {
              if (skipFields.includes(key)) {
                continue;
              }
              if (!value || typeof value === "string" && !value.trim()) {
                continue;
              }
              const extracted = this.extractTextFromBlocks(value);
              if (extracted && extracted.trim().length > 0) {
                texts.push(extracted);
              }
            }
            const result = texts.filter((t) => t && t.trim()).join(" ").replace(/\s+/g, " ").trim();
            return result;
          },
          async generate() {
            var _a, _b, _c, _d, _e, _f;
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
              const allText = this.extractAllText(parent.value);
              if (!allText || allText.trim() === "") {
                const availableFields = Object.keys(parent.value).join(", ");
                throw new Error(`No content available to generate description. Available fields: ${availableFields}`);
              }
              let language = "en";
              if ((_c = (_b = (_a = window.panel) == null ? void 0 : _a.view) == null ? void 0 : _b.props) == null ? void 0 : _c.language) {
                language = window.panel.view.props.language;
              } else if ((_e = (_d = window.panel) == null ? void 0 : _d.language) == null ? void 0 : _e.code) {
                language = window.panel.language.code;
              } else if ((_f = this.$language) == null ? void 0 : _f.code) {
                language = this.$language.code;
              }
              const response = await this.$api.post("meta-kit/generate", {
                text: allText,
                language
              });
              if (response.status === "success" && response.description) {
                this.generatedText = response.description;
                if (parent && parent.value) {
                  if (!parent.value.metakitseo || !Array.isArray(parent.value.metakitseo)) {
                    this.$set(parent.value, "seo", [{
                      id: "seo-metadata",
                      type: "mk-page-seo",
                      isHidden: false,
                      content: {}
                    }]);
                  }
                  if (parent.value.metakitseo.length === 0) {
                    parent.value.metakitseo.push({
                      id: "seo-metadata",
                      type: "mk-page-seo",
                      isHidden: false,
                      content: {}
                    });
                  }
                  const seoBlock = parent.value.metakitseo[0];
                  if (!seoBlock.content) {
                    this.$set(seoBlock, "content", {});
                  }
                  this.$set(seoBlock.content, "metadescription", response.description);
                  this.$set(seoBlock.content, "ogdescription", response.description);
                  if (parent.update) {
                    parent.update({
                      seo: parent.value.metakitseo
                    });
                  }
                  setTimeout(() => {
                    const event = new CustomEvent("seo-field-updated", {
                      bubbles: true,
                      detail: {
                        field: "metadescription",
                        value: response.description,
                        seoData: seoBlock.content,
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
