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
  const _sfc_main$c = {
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
  var _sfc_render$c = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("section", { staticClass: "k-seo-preview-section" }, [_c("header", { staticClass: "k-section-header" }, [_c("h2", { staticClass: "k-headline" }, [_vm._v(_vm._s(_vm.label || "SEO Preview"))])]), _vm.meta ? _c("div", { staticClass: "k-seo-previews" }, [_c("div", { staticClass: "k-seo-preview k-seo-preview--google" }, [_c("h3", { staticClass: "k-seo-preview__title" }, [_vm._v("Google Search Preview")]), _c("div", { staticClass: "k-seo-preview__content" }, [_c("div", { staticClass: "k-google-preview" }, [_c("cite", { staticClass: "k-google-preview__url" }, [_vm._v(_vm._s(_vm.displayUrl(_vm.meta.url)))]), _c("h3", { staticClass: "k-google-preview__title" }, [_vm._v(_vm._s(_vm.meta.title || "Page Title"))]), _c("p", { staticClass: "k-google-preview__description" }, [_vm._v(_vm._s(_vm.meta.description || "No description available"))])])])]), _c("div", { staticClass: "k-seo-preview k-seo-preview--twitter" }, [_c("h3", { staticClass: "k-seo-preview__title" }, [_vm._v("Share / Card Preview")]), _c("div", { staticClass: "k-seo-preview__content" }, [_c("div", { staticClass: "k-twitter-preview" }, [_vm.meta.ogImage ? _c("div", { staticClass: "k-twitter-preview__image", style: { backgroundImage: "url(" + _vm.meta.ogImage + ")" } }) : _vm._e(), _c("div", { staticClass: "k-twitter-preview__body" }, [_c("cite", { staticClass: "k-twitter-preview__url" }, [_vm._v(_vm._s(_vm.displayUrl(_vm.meta.url)))]), _c("h4", { staticClass: "k-twitter-preview__title" }, [_vm._v(_vm._s(_vm.meta.ogTitle || _vm.meta.title || "Page Title"))]), _c("p", { staticClass: "k-twitter-preview__description" }, [_vm._v(_vm._s(_vm.truncate(_vm.meta.ogDescription || _vm.meta.description, 140) || "No description"))])])])])])]) : _c("div", { staticClass: "k-seo-preview-loading" }, [_vm._v(" Loading preview... ")])]);
  };
  var _sfc_staticRenderFns$c = [];
  _sfc_render$c._withStripped = true;
  var __component__$c = /* @__PURE__ */ normalizeComponent(
    _sfc_main$c,
    _sfc_render$c,
    _sfc_staticRenderFns$c
  );
  __component__$c.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/sections/seo-preview.vue";
  const SeoPreview = __component__$c.exports;
  const _sfc_main$b = {
    props: {
      value: String,
      placeholder: {
        type: String,
        default: "No meta title"
      },
      pageId: {
        type: String,
        required: false
      },
      pageTitle: {
        type: String,
        default: ""
      },
      metaTitle: {
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
      },
      type: {
        type: String,
        default: "meta",
        validator: (value) => ["meta", "og"].includes(value)
      }
    },
    computed: {
      isSitePage() {
        return this.pageId === "site";
      },
      // The title to use - with proper fallback chain based on type
      effectiveTitle() {
        if (this.type === "og") {
          return this.value || this.metaTitle || this.pageTitle || "";
        }
        return this.value || this.pageTitle || "";
      },
      // Check if site name should be appended based on type and settings
      shouldAppendSiteName() {
        if (this.siteSettings.appendSiteNameTo === void 0 || this.siteSettings.appendSiteNameTo === null || this.siteSettings.appendSiteNameTo === "") {
          return !!this.siteSettings.appendSiteName;
        }
        const setting = this.siteSettings.appendSiteNameTo;
        return setting.split(",").map((s) => s.trim()).includes(this.type);
      },
      showPreview() {
        return !this.isSitePage && this.effectiveTitle && this.shouldAppendSiteName && this.siteSettings.siteMetaTitle;
      },
      fullTitle() {
        const titleToUse = this.effectiveTitle;
        if (!titleToUse || !this.shouldAppendSiteName) {
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
        if (this.shouldAppendSiteName && this.siteSettings.siteMetaTitle) {
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
        if (this.shouldAppendSiteName) {
          finalLength = this.fullTitle.length;
        }
        let optimal, warning;
        if (this.type === "og") {
          optimal = { min: 40, max: 60 };
          warning = { min: 35, max: 70 };
        } else {
          optimal = { min: 50, max: 60 };
          warning = { min: 45, max: 66 };
        }
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
  var _sfc_render$b = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { class: _vm.fieldClass }, [_c("k-input", { attrs: { "value": _vm.value, "placeholder": _vm.placeholder, "type": "text" }, on: { "input": function($event) {
      return _vm.$emit("input", $event);
    } } }), _vm.showPreview ? _c("div", { staticClass: "k-meta-kit-title-preview" }, [_vm._v(" " + _vm._s(_vm.fullTitle) + " ")]) : _vm._e(), _c("div", { staticClass: "k-meta-kit-dialog-field-meta" }, [_c("span", [_vm.value ? _c("span", { staticClass: "k-meta-kit-field-length", class: _vm.statusClass }, [_vm._v(" " + _vm._s(_vm.charCount) + " chars ")]) : _vm._e()]), _vm.aiEnabled ? _c("k-button", { attrs: { "icon": "sparkling", "size": _vm.buttonSize, "disabled": _vm.isGenerating, "title": _vm.buttonSize === "xs" ? "AI Generate" : void 0 }, on: { "click": function($event) {
      return _vm.$emit("generate");
    } } }, [_vm.buttonSize !== "xs" ? [_vm._v("AI Generate")] : _vm._e()], 2) : _vm._e()], 1), _vm.isGenerating ? _c("div", { staticClass: "k-meta-kit-dialog-generating" }, [_c("k-icon", { staticClass: "k-meta-kit-spinner", attrs: { "type": "loader" } }), _c("span", [_vm._v("Generating...")])], 1) : _vm._e()], 1);
  };
  var _sfc_staticRenderFns$b = [];
  _sfc_render$b._withStripped = true;
  var __component__$b = /* @__PURE__ */ normalizeComponent(
    _sfc_main$b,
    _sfc_render$b,
    _sfc_staticRenderFns$b
  );
  __component__$b.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/components/parts/field/MetaKitTitleField.vue";
  const MetaKitTitleField = __component__$b.exports;
  const _sfc_main$a = {
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
      },
      type: {
        type: String,
        default: "meta",
        validator: (value) => ["meta", "og"].includes(value)
      }
    },
    computed: {
      statusClass() {
        if (!this.value) return "";
        const length = this.value.length;
        let optimal, warning;
        if (this.type === "og") {
          optimal = { min: 150, max: 185 };
          warning = { min: 135, max: 200 };
        } else {
          optimal = { min: 140, max: 160 };
          warning = { min: 126, max: 176 };
        }
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
  var _sfc_render$a = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { class: _vm.fieldClass }, [_c("k-input", { attrs: { "value": _vm.value, "placeholder": _vm.placeholder, "type": "textarea", "rows": _vm.rows, "buttons": _vm.buttons }, on: { "input": function($event) {
      return _vm.$emit("input", $event);
    } } }), _c("div", { staticClass: "k-meta-kit-dialog-field-meta" }, [_c("span", [_vm.value ? _c("span", { staticClass: "k-meta-kit-field-length", class: _vm.statusClass }, [_vm._v(" " + _vm._s(_vm.value.length) + " chars ")]) : _vm._e()]), _vm.aiEnabled ? _c("k-button", { attrs: { "icon": "sparkling", "size": _vm.buttonSize, "disabled": _vm.isGenerating, "title": _vm.buttonSize === "xs" ? "AI Generate" : void 0 }, on: { "click": function($event) {
      return _vm.$emit("generate");
    } } }, [_vm.buttonSize !== "xs" ? [_vm._v("AI Generate")] : _vm._e()], 2) : _vm._e()], 1), _vm.isGenerating ? _c("div", { staticClass: "k-meta-kit-dialog-generating" }, [_c("k-icon", { staticClass: "k-meta-kit-spinner", attrs: { "type": "loader" } }), _c("span", [_vm._v("Generating...")])], 1) : _vm._e()], 1);
  };
  var _sfc_staticRenderFns$a = [];
  _sfc_render$a._withStripped = true;
  var __component__$a = /* @__PURE__ */ normalizeComponent(
    _sfc_main$a,
    _sfc_render$a,
    _sfc_staticRenderFns$a
  );
  __component__$a.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/components/parts/field/MetaKitDescriptionField.vue";
  const MetaKitDescriptionField = __component__$a.exports;
  const _sfc_main$9 = {
    props: {
      filteredCount: {
        type: Number,
        required: true
      },
      totalCount: {
        type: Number,
        required: true
      },
      filteredWithDescription: {
        type: Number,
        required: true
      },
      totalWithDescription: {
        type: Number,
        required: true
      },
      filteredWithImage: {
        type: Number,
        required: true
      },
      totalWithImage: {
        type: Number,
        required: true
      },
      filteredNoIndex: {
        type: Number,
        required: true
      },
      totalNoIndex: {
        type: Number,
        required: true
      },
      searchActive: {
        type: Boolean,
        default: false
      }
    }
  };
  var _sfc_render$9 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { staticClass: "k-meta-kit-stats" }, [_c("div", { staticClass: "k-meta-kit-stats-card" }, [_c("h3", [_vm._v("Total Pages")]), _c("p", [_vm._v(_vm._s(_vm.filteredCount)), _vm.searchActive ? _c("span", { staticClass: "k-meta-kit-stats-total" }, [_vm._v(" / " + _vm._s(_vm.totalCount))]) : _vm._e()])]), _c("div", { staticClass: "k-meta-kit-stats-card" }, [_c("h3", [_vm._v("With Description")]), _c("p", [_vm._v(_vm._s(_vm.filteredWithDescription)), _vm.searchActive ? _c("span", { staticClass: "k-meta-kit-stats-total" }, [_vm._v(" / " + _vm._s(_vm.totalWithDescription))]) : _vm._e()])]), _c("div", { staticClass: "k-meta-kit-stats-card" }, [_c("h3", [_vm._v("With OG Image")]), _c("p", [_vm._v(_vm._s(_vm.filteredWithImage)), _vm.searchActive ? _c("span", { staticClass: "k-meta-kit-stats-total" }, [_vm._v(" / " + _vm._s(_vm.totalWithImage))]) : _vm._e()])]), _c("div", { staticClass: "k-meta-kit-stats-card" }, [_c("h3", [_vm._v("No Index")]), _c("p", [_vm._v(_vm._s(_vm.filteredNoIndex)), _vm.searchActive ? _c("span", { staticClass: "k-meta-kit-stats-total" }, [_vm._v(" / " + _vm._s(_vm.totalNoIndex))]) : _vm._e()])])]);
  };
  var _sfc_staticRenderFns$9 = [];
  _sfc_render$9._withStripped = true;
  var __component__$9 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$9,
    _sfc_render$9,
    _sfc_staticRenderFns$9
  );
  __component__$9.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/components/parts/table/MetaKitStats.vue";
  const MetaKitStats = __component__$9.exports;
  const _sfc_main$8 = {
    props: {
      showPreview: {
        type: Boolean,
        default: false
      },
      previewMode: {
        type: String,
        default: "meta",
        validator: (value) => ["meta", "og"].includes(value)
      },
      searchQuery: {
        type: String,
        default: ""
      },
      activeFilters: {
        type: Array,
        default: () => []
      },
      pageSize: {
        type: Number,
        default: 25
      },
      pageSizeOptions: {
        type: Array,
        default: () => [
          { value: 25, text: "25/page" },
          { value: 50, text: "50/page" },
          { value: 100, text: "100/page" },
          { value: 99999, text: "All" }
        ]
      }
    },
    data() {
      return {
        isDropdownOpen: false
      };
    },
    methods: {
      toggleFilterDropdown() {
        this.isDropdownOpen = !this.isDropdownOpen;
      },
      isFilterActive(filter) {
        return this.activeFilters.includes(filter);
      },
      toggleFilter(filter) {
        const filters = [...this.activeFilters];
        const index = filters.indexOf(filter);
        if (index > -1) {
          filters.splice(index, 1);
        } else {
          filters.push(filter);
        }
        this.$emit("update:active-filters", filters);
      },
      clearFilters() {
        this.$emit("update:active-filters", []);
        this.isDropdownOpen = false;
      }
    },
    mounted() {
      document.addEventListener("click", (e) => {
        if (!this.$el.contains(e.target)) {
          this.isDropdownOpen = false;
        }
      });
    }
  };
  var _sfc_render$8 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { staticClass: "k-meta-kit-controls" }, [_c("k-button-group", [_vm.showPreview ? _c("k-button", { attrs: { "size": "sm", "theme": _vm.previewMode === "meta" ? "positive" : "", "title": "Show meta title and description" }, on: { "click": function($event) {
      return _vm.$emit("update:preview-mode", "meta");
    } } }, [_vm._v(" Meta ")]) : _vm._e(), _vm.showPreview ? _c("k-button", { attrs: { "size": "sm", "theme": _vm.previewMode === "og" ? "positive" : "", "title": "Show OG title and description" }, on: { "click": function($event) {
      return _vm.$emit("update:preview-mode", "og");
    } } }, [_vm._v(" OG ")]) : _vm._e(), _c("k-button", { attrs: { "size": "sm", "title": _vm.showPreview ? "Show character counts" : "Show preview text" }, on: { "click": function($event) {
      return _vm.$emit("update:show-preview", !_vm.showPreview);
    } } }, [_vm._v(" " + _vm._s(_vm.showPreview ? "Overview" : "Preview") + " ")])], 1), _c("div", { staticClass: "k-meta-kit-search-wrapper" }, [_c("k-search-input", { staticClass: "k-meta-kit-search", attrs: { "icon": "search", "value": _vm.searchQuery, "placeholder": "Filter pages..." }, on: { "input": function($event) {
      return _vm.$emit("update:search-query", $event);
    } } }), _vm.searchQuery ? _c("button", { staticClass: "k-meta-kit-search-clear", attrs: { "title": "Clear search" }, on: { "click": function($event) {
      return _vm.$emit("update:search-query", "");
    } } }, [_c("k-icon", { attrs: { "type": "cancel" } })], 1) : _vm._e()], 1), _c("div", { staticClass: "k-meta-kit-filter-dropdown" }, [_c("button", { staticClass: "k-meta-kit-filter-button", class: { "active": _vm.isDropdownOpen || _vm.activeFilters.length > 0 }, on: { "click": _vm.toggleFilterDropdown } }, [_c("k-icon", { attrs: { "type": "filter" } }), _c("span", [_vm._v("Filters")]), _vm.activeFilters.length > 0 ? _c("span", { staticClass: "k-meta-kit-filter-count" }, [_vm._v(_vm._s(_vm.activeFilters.length))]) : _vm._e(), _c("k-icon", { attrs: { "type": _vm.isDropdownOpen ? "angle-up" : "angle-down" } })], 1), _vm.isDropdownOpen ? _c("div", { staticClass: "k-meta-kit-filter-dropdown-content" }, [_c("div", { staticClass: "k-meta-kit-filter-group" }, [_c("div", { staticClass: "k-meta-kit-filter-group-title" }, [_vm._v("Metadata")]), _c("label", { staticClass: "k-meta-kit-filter-option" }, [_c("input", { attrs: { "type": "checkbox", "value": "missing-title" }, domProps: { "checked": _vm.isFilterActive("missing-title") }, on: { "change": function($event) {
      return _vm.toggleFilter("missing-title");
    } } }), _c("span", [_vm._v("Missing Title")])]), _c("label", { staticClass: "k-meta-kit-filter-option" }, [_c("input", { attrs: { "type": "checkbox", "value": "missing-description" }, domProps: { "checked": _vm.isFilterActive("missing-description") }, on: { "change": function($event) {
      return _vm.toggleFilter("missing-description");
    } } }), _c("span", [_vm._v("Missing Description")])]), _c("label", { staticClass: "k-meta-kit-filter-option" }, [_c("input", { attrs: { "type": "checkbox", "value": "missing-og-title" }, domProps: { "checked": _vm.isFilterActive("missing-og-title") }, on: { "change": function($event) {
      return _vm.toggleFilter("missing-og-title");
    } } }), _c("span", [_vm._v("Missing OG Title")])]), _c("label", { staticClass: "k-meta-kit-filter-option" }, [_c("input", { attrs: { "type": "checkbox", "value": "missing-og-description" }, domProps: { "checked": _vm.isFilterActive("missing-og-description") }, on: { "change": function($event) {
      return _vm.toggleFilter("missing-og-description");
    } } }), _c("span", [_vm._v("Missing OG Description")])]), _c("label", { staticClass: "k-meta-kit-filter-option" }, [_c("input", { attrs: { "type": "checkbox", "value": "missing-og-image" }, domProps: { "checked": _vm.isFilterActive("missing-og-image") }, on: { "change": function($event) {
      return _vm.toggleFilter("missing-og-image");
    } } }), _c("span", [_vm._v("Missing OG Image")])]), _c("label", { staticClass: "k-meta-kit-filter-option" }, [_c("input", { attrs: { "type": "checkbox", "value": "complete" }, domProps: { "checked": _vm.isFilterActive("complete") }, on: { "change": function($event) {
      return _vm.toggleFilter("complete");
    } } }), _c("span", [_vm._v("Complete Metadata")])])]), _c("div", { staticClass: "k-meta-kit-filter-group" }, [_c("div", { staticClass: "k-meta-kit-filter-group-title" }, [_vm._v("Status")]), _c("label", { staticClass: "k-meta-kit-filter-option" }, [_c("input", { attrs: { "type": "checkbox", "value": "listed" }, domProps: { "checked": _vm.isFilterActive("listed") }, on: { "change": function($event) {
      return _vm.toggleFilter("listed");
    } } }), _c("span", [_vm._v("Listed")])]), _c("label", { staticClass: "k-meta-kit-filter-option" }, [_c("input", { attrs: { "type": "checkbox", "value": "unlisted" }, domProps: { "checked": _vm.isFilterActive("unlisted") }, on: { "change": function($event) {
      return _vm.toggleFilter("unlisted");
    } } }), _c("span", [_vm._v("Unlisted")])]), _c("label", { staticClass: "k-meta-kit-filter-option" }, [_c("input", { attrs: { "type": "checkbox", "value": "drafts" }, domProps: { "checked": _vm.isFilterActive("drafts") }, on: { "change": function($event) {
      return _vm.toggleFilter("drafts");
    } } }), _c("span", [_vm._v("Drafts")])])]), _vm.activeFilters.length > 0 ? _c("div", { staticClass: "k-meta-kit-filter-actions" }, [_c("button", { staticClass: "k-meta-kit-filter-clear", on: { "click": _vm.clearFilters } }, [_vm._v(" Clear all ")])]) : _vm._e()]) : _vm._e()]), _c("select", { staticClass: "k-meta-kit-pagesize-select", domProps: { "value": _vm.pageSize }, on: { "change": function($event) {
      return _vm.$emit("change-page-size", $event.target.value);
    } } }, _vm._l(_vm.pageSizeOptions, function(option) {
      return _c("option", { key: option.value, domProps: { "value": option.value } }, [_vm._v(" " + _vm._s(option.text) + " ")]);
    }), 0)], 1);
  };
  var _sfc_staticRenderFns$8 = [];
  _sfc_render$8._withStripped = true;
  var __component__$8 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$8,
    _sfc_render$8,
    _sfc_staticRenderFns$8
  );
  __component__$8.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/components/parts/table/MetaKitFilters.vue";
  const MetaKitFilters = __component__$8.exports;
  const _sfc_main$7 = {
    props: {
      selectedCount: {
        type: Number,
        default: 0
      },
      aiEnabled: {
        type: Boolean,
        default: true
      },
      isGenerating: {
        type: Boolean,
        default: false
      }
    }
  };
  var _sfc_render$7 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { staticClass: "k-meta-kit-actions" }, [_c("k-button-group", [_c("k-button", { attrs: { "icon": "edit", "disabled": _vm.selectedCount === 0 }, on: { "click": function($event) {
      return _vm.$emit("edit-selected");
    } } }, [_vm._v(" Edit Selected (" + _vm._s(_vm.selectedCount) + ") ")]), _vm.aiEnabled ? _c("k-button", { attrs: { "icon": "sparkling", "disabled": _vm.isGenerating || _vm.selectedCount === 0, "progress": _vm.isGenerating }, on: { "click": function($event) {
      return _vm.$emit("generate-missing");
    } } }, [_vm._v(" Generate Missing (" + _vm._s(_vm.selectedCount) + ") ")]) : _vm._e(), _c("k-button", { attrs: { "icon": "refresh" }, on: { "click": function($event) {
      return _vm.$emit("refresh");
    } } })], 1), _vm._t("filters")], 2);
  };
  var _sfc_staticRenderFns$7 = [];
  _sfc_render$7._withStripped = true;
  var __component__$7 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$7,
    _sfc_render$7,
    _sfc_staticRenderFns$7
  );
  __component__$7.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/components/parts/table/MetaKitActions.vue";
  const MetaKitActions = __component__$7.exports;
  const _sfc_main$6 = {
    props: {
      content: {
        type: String,
        default: ""
      }
    },
    data() {
      return {
        isVisible: false,
        tooltipStyle: {}
      };
    },
    computed: {
      formattedContent() {
        return this.content.replace(/\n/g, "<br>");
      }
    },
    methods: {
      show(event) {
        if (!this.content) return;
        this.isVisible = true;
        this.$nextTick(() => {
          this.updatePosition(event);
        });
      },
      hide() {
        this.isVisible = false;
      },
      updatePosition(event) {
        const tooltip = this.$el.querySelector(".k-meta-kit-tooltip-content");
        if (!tooltip) return;
        const rect = this.$el.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        let left = rect.left + rect.width / 2 - tooltipRect.width / 2;
        let top = rect.bottom + 8;
        if (left + tooltipRect.width > viewportWidth - 10) {
          left = viewportWidth - tooltipRect.width - 10;
        }
        if (left < 10) {
          left = 10;
        }
        if (top + tooltipRect.height > viewportHeight - 10) {
          top = rect.top - tooltipRect.height - 8;
        }
        this.tooltipStyle = {
          left: `${left}px`,
          top: `${top}px`
        };
      }
    }
  };
  var _sfc_render$6 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { staticClass: "k-meta-kit-tooltip-wrapper", on: { "mouseenter": _vm.show, "mouseleave": _vm.hide } }, [_vm._t("default"), _vm.isVisible && _vm.content ? _c("div", { staticClass: "k-meta-kit-tooltip-content", style: _vm.tooltipStyle, domProps: { "innerHTML": _vm._s(_vm.formattedContent) } }) : _vm._e()], 2);
  };
  var _sfc_staticRenderFns$6 = [];
  _sfc_render$6._withStripped = true;
  var __component__$6 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$6,
    _sfc_render$6,
    _sfc_staticRenderFns$6
  );
  __component__$6.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/components/parts/common/Tooltip.vue";
  const Tooltip = __component__$6.exports;
  const _sfc_main$5 = {
    components: {
      Tooltip
    },
    props: {
      pages: {
        type: Array,
        required: true
      },
      startIndex: {
        type: Number,
        default: 0
      },
      selectedPages: {
        type: Array,
        default: () => []
      },
      isAllSelected: {
        type: Boolean,
        default: false
      },
      showPreview: {
        type: Boolean,
        default: false
      },
      previewMode: {
        type: String,
        default: "meta",
        validator: (value) => ["meta", "og"].includes(value)
      },
      aiEnabled: {
        type: Boolean,
        default: true
      },
      validationSettings: {
        type: Object,
        default: () => ({})
      }
    },
    inject: ["siteSettings"],
    data() {
      return {
        // SEO length ranges for different field types
        seoRanges: {
          title: { optimal: { min: 20, max: 60 }, warning: { min: 15, max: 75 } },
          ogTitle: { optimal: { min: 20, max: 60 }, warning: { min: 15, max: 75 } },
          description: { optimal: { min: 140, max: 160 }, warning: { min: 126, max: 176 } },
          ogDescription: { optimal: { min: 150, max: 250 }, warning: { min: 135, max: 300 } }
        },
        // Status mappings
        statusMappings: {
          listed: { label: "Listed", dotClass: "k-meta-kit-status-dot-listed" },
          unlisted: { label: "Unlisted", dotClass: "k-meta-kit-status-dot-unlisted" },
          draft: { label: "Draft", dotClass: "k-meta-kit-status-dot-draft" }
        }
      };
    },
    methods: {
      getRangeConfigForPage(page) {
        var _a, _b;
        const defaults = ((_a = this.validationSettings) == null ? void 0 : _a.ranges) || {};
        const templates = ((_b = this.validationSettings) == null ? void 0 : _b.templates) || {};
        const templateName = page == null ? void 0 : page.template;
        const templateConfig = templateName && templates[templateName] ? templates[templateName] : {};
        const templateRanges = (templateConfig == null ? void 0 : templateConfig.ranges) || {};
        return {
          ...this.seoRanges,
          ...defaults,
          ...templateRanges
        };
      },
      getRangesForPageAndType(page, type) {
        const merged = this.getRangeConfigForPage(page);
        return merged == null ? void 0 : merged[type];
      },
      // Helper method to check if site name should be appended based on type and settings
      shouldAppendSiteName(type) {
        if (this.siteSettings.appendSiteNameTo === void 0 || this.siteSettings.appendSiteNameTo === null || this.siteSettings.appendSiteNameTo === "") {
          return !!this.siteSettings.appendSiteName;
        }
        const setting = this.siteSettings.appendSiteNameTo;
        return setting.split(",").map((s) => s.trim()).includes(type);
      },
      isPageSelected(pageId) {
        return this.selectedPages.includes(pageId);
      },
      // Unified title length calculator
      getTitleLength(page, type = "meta") {
        const isOg = type === "og";
        if (page.id === "site") {
          if (isOg && page.hasOgTitle) return page.ogTitleLength;
          if (!isOg && page.hasMetaTitle) return page.metaTitleLength;
          return page.title ? page.title.length : 0;
        }
        const titleToUse = isOg ? page.hasOgTitle ? page.ogTitle : page.hasMetaTitle ? page.metaTitle : page.title : page.hasMetaTitle ? page.metaTitle : page.title;
        if (!titleToUse) return 0;
        if (this.shouldAppendSiteName(type) && this.siteSettings.siteMetaTitle) {
          const separator = this.siteSettings.titleSeparator || "|";
          const siteName = this.siteSettings.siteMetaTitle || "";
          return `${titleToUse} ${separator} ${siteName}`.length;
        }
        return titleToUse.length;
      },
      getFullTitlePreview(page, type) {
        if (page.id === "site") {
          return page.hasMetaTitle ? page.metaTitle : page.title;
        }
        var titleToUse = page.hasMetaTitle ? page.metaTitle : page.title;
        if (type === "og" && page.hasOgTitle) {
          titleToUse = page.ogTitle;
        }
        if (!titleToUse) return "—";
        if (this.shouldAppendSiteName(type) && this.siteSettings.siteMetaTitle) {
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
        const fullLength = this.getTitleLength(page, "meta");
        return this.getStatusClass(page, fullLength, "title");
      },
      getTableOgTitleStatusClass(page) {
        if (page.id === "site") {
          return "";
        }
        const fullLength = this.getTitleLength(page, "og");
        return this.getStatusClass(page, fullLength, "ogTitle");
      },
      getStatusClass(page, length, type) {
        if (!length || length === 0) return "";
        const ranges = this.getRangesForPageAndType(page, type);
        if (!ranges) return "";
        if (length >= ranges.optimal.min && length <= ranges.optimal.max) {
          return "";
        }
        if (length >= ranges.warning.min && length <= ranges.warning.max) {
          return "k-meta-kit-status-warning";
        }
        return "k-meta-kit-status-error";
      },
      getStatusValue(statusClass) {
        if (!statusClass) return "";
        const match = statusClass.match(/k-meta-kit-status-(\w+)/);
        return match ? match[1] : "";
      },
      getTitleTooltip(page, showContent = true) {
        const titleToUse = page.hasMetaTitle ? page.metaTitle : page.title;
        if (!titleToUse) {
          return "No title";
        }
        if (page.id === "site") {
          if (!showContent) {
            return "";
          }
          if (page.hasMetaTitle && page.metaTitle) {
            return `${page.metaTitle}`;
          }
          return `${page.title}`;
        }
        let tooltip = "";
        let prefix = "";
        if (page.hasMetaTitle && page.metaTitle) {
          tooltip = page.metaTitle;
        } else {
          prefix = "page title";
          tooltip = page.title;
        }
        if (this.shouldAppendSiteName("meta") && this.siteSettings.siteMetaTitle) {
          const separator = this.siteSettings.titleSeparator || "|";
          const siteName = this.siteSettings.siteMetaTitle || "";
          tooltip = `${titleToUse} ${separator} ${siteName}`;
        }
        return this.tooltipText(tooltip, prefix, showContent);
      },
      getDescriptionTooltip(page, showContent = true) {
        if (page.hasMetaDescription && page.metaDescription) {
          return this.tooltipText(page.metaDescription, false, showContent);
        } else if (this.siteSettings.siteMetaDescription) {
          return this.tooltipText(this.siteSettings.siteMetaDescription, "site", showContent);
        } else {
          return "No meta description";
        }
      },
      getOgTitleTooltip(page, showContent = true) {
        const titleToUse = page.hasOgTitle ? page.ogTitle : page.hasMetaTitle ? page.metaTitle : page.title;
        if (!titleToUse) {
          return "No OG title";
        }
        if (page.id === "site") {
          if (!showContent) {
            return "";
          }
          if (page.hasOgTitle && page.ogTitle) {
            return `${page.ogTitle}`;
          }
          return `${page.title}`;
        }
        let tooltip = "";
        let prefix = "";
        if (page.hasOgTitle && page.ogTitle) {
          tooltip = page.ogTitle;
        } else if (page.hasMetaTitle && page.metaTitle) {
          prefix = "meta title";
          tooltip = page.metaTitle;
        } else {
          prefix = "page title";
          tooltip = page.title;
        }
        if (this.shouldAppendSiteName("og") && this.siteSettings.siteMetaTitle) {
          const separator = this.siteSettings.titleSeparator || "|";
          const siteName = this.siteSettings.siteMetaTitle || "";
          tooltip = `${titleToUse} ${separator} ${siteName}`;
        }
        return this.tooltipText(tooltip, prefix, showContent);
      },
      getOgDescriptionTooltip(page, showContent = true) {
        if (page.hasOgDescription && page.ogDescription) {
          return this.tooltipText(page.ogDescription, false, showContent);
        } else if (page.hasMetaDescription && page.metaDescription) {
          return this.tooltipText(page.metaDescription, "meta description", showContent);
        } else if (this.siteSettings.siteMetaDescription) {
          return this.tooltipText(this.siteSettings.siteMetaDescription, "site", showContent);
        } else {
          return "No OG description";
        }
      },
      tooltipText(desc, inheritance, showContent) {
        let prefix = "";
        if (desc && desc.length > 200) {
          desc = desc.substring(0, 200) + "...";
        }
        if (inheritance) {
          prefix = "Inherited from " + inheritance;
        }
        if (showContent) {
          desc = (inheritance ? ":\n\n" : "") + desc;
          return prefix + desc;
        }
        return prefix;
      },
      getSlug(page) {
        if (page.id === "site") {
          return "";
        }
        const parts = page.id.split("/");
        return parts[parts.length - 1];
      },
      getSlugWordCount(slug) {
        if (!slug) return 0;
        return slug.split(/[-_]/).filter((word) => word.length > 0).length;
      },
      getSlugValidationConfigForPage(page) {
        var _a, _b;
        const defaults = ((_a = this.validationSettings) == null ? void 0 : _a.slug) || {};
        const templates = ((_b = this.validationSettings) == null ? void 0 : _b.templates) || {};
        const templateName = page == null ? void 0 : page.template;
        const templateConfig = templateName && templates[templateName] ? templates[templateName] : {};
        const templateSlug = (templateConfig == null ? void 0 : templateConfig.slug) || {};
        const mergeRule = (key, fallbackOptimal, fallbackWarning) => {
          const raw = { ...defaults[key] || {}, ...templateSlug[key] || {} };
          if (raw.optimal && raw.warning) {
            return {
              optimal: { ...fallbackOptimal, ...raw.optimal || {} },
              warning: { ...fallbackWarning, ...raw.warning || {} }
            };
          }
          const warningMax = typeof raw.warningMax === "number" ? raw.warningMax : fallbackWarning.max;
          const errorMax = typeof raw.errorMax === "number" ? raw.errorMax : fallbackWarning.max;
          return {
            optimal: { ...fallbackOptimal, max: warningMax },
            warning: { ...fallbackWarning, max: errorMax }
          };
        };
        return {
          depth: mergeRule("depth", { min: 0, max: 2 }, { min: 0, max: 3 }),
          words: mergeRule("words", { min: 1, max: 8 }, { min: 1, max: 10 }),
          length: mergeRule("length", { min: 1, max: 60 }, { min: 1, max: 70 }),
          wordLength: mergeRule("wordLength", { min: 1, max: 15 }, { min: 1, max: 20 })
        };
      },
      getSlugStatusClass(page) {
        var _a, _b, _c, _d, _e, _f, _g, _h;
        if (page.id === "site") {
          return "";
        }
        const slug = this.getSlug(page);
        const wordCount = this.getSlugWordCount(slug);
        const length = slug.length;
        const numSlashes = page.id.split("/").length - 1;
        const cfg = this.getSlugValidationConfigForPage(page);
        const avgWordLength = wordCount > 0 ? Math.ceil(length / wordCount) : length;
        const isOutsideRange = (value, range) => {
          if (!range) return false;
          if (typeof range.min === "number" && value < range.min) return true;
          if (typeof range.max === "number" && value > range.max) return true;
          return false;
        };
        const isError = isOutsideRange(numSlashes, (_a = cfg.depth) == null ? void 0 : _a.warning) || isOutsideRange(wordCount, (_b = cfg.words) == null ? void 0 : _b.warning) || isOutsideRange(length, (_c = cfg.length) == null ? void 0 : _c.warning) || isOutsideRange(avgWordLength, (_d = cfg.wordLength) == null ? void 0 : _d.warning);
        if (isError) return "k-meta-kit-status-error";
        const isWarning = isOutsideRange(numSlashes, (_e = cfg.depth) == null ? void 0 : _e.optimal) || isOutsideRange(wordCount, (_f = cfg.words) == null ? void 0 : _f.optimal) || isOutsideRange(length, (_g = cfg.length) == null ? void 0 : _g.optimal) || isOutsideRange(avgWordLength, (_h = cfg.wordLength) == null ? void 0 : _h.optimal);
        if (isWarning) return "k-meta-kit-status-warning";
        return "";
      },
      getSlugTooltip(page) {
        if (page.id === "site") {
          return "Site root";
        }
        const slug = this.getSlug(page);
        const wordCount = this.getSlugWordCount(slug);
        const length = slug.length;
        const cfg = this.getSlugValidationConfigForPage(page);
        return `Slug: ${slug}
Words: ${wordCount}
Length: ${length} characters

Ranges (optimal / warning):

Depth: ${cfg.depth.optimal.min}-${cfg.depth.optimal.max} / ${cfg.depth.warning.min}-${cfg.depth.warning.max}
Words: ${cfg.words.optimal.min}-${cfg.words.optimal.max} / ${cfg.words.warning.min}-${cfg.words.warning.max}
Length: ${cfg.length.optimal.min}-${cfg.length.optimal.max} / ${cfg.length.warning.min}-${cfg.length.warning.max}
Avg word length: ${cfg.wordLength.optimal.min}-${cfg.wordLength.optimal.max} / ${cfg.wordLength.warning.min}-${cfg.wordLength.warning.max}`;
      },
      getStatusLabel(page) {
        var _a;
        if (!page.status) return "—";
        return ((_a = this.statusMappings[page.status]) == null ? void 0 : _a.label) || page.status.charAt(0).toUpperCase() + page.status.slice(1);
      },
      getStatusDotClass(page) {
        var _a;
        if (!page.status) return "";
        return ((_a = this.statusMappings[page.status]) == null ? void 0 : _a.dotClass) || "";
      },
      // Title display and inheritance
      getTitleDisplay(page) {
        const length = this.getTitleLength(page, "meta");
        if (!length) return "—";
        return this.isTitleInherited(page) ? `${length}` : length;
      },
      isTitleInherited(page) {
        if (page.id === "site") return false;
        return !page.hasMetaTitle;
      },
      // Description display and inheritance
      getDescriptionDisplay(page) {
        if (page.hasMetaDescription) {
          return page.metaDescriptionLength;
        }
        if (this.siteSettings.siteMetaDescription) {
          return `${this.siteSettings.siteMetaDescription.length}`;
        }
        return "—";
      },
      isDescriptionInherited(page) {
        return !page.hasMetaDescription && this.siteSettings.siteMetaDescription;
      },
      getDescriptionStatusClass(page) {
        const length = page.hasMetaDescription ? page.metaDescriptionLength : this.siteSettings.siteMetaDescription ? this.siteSettings.siteMetaDescription.length : 0;
        return this.getStatusClass(page, length, "description");
      },
      // OG Title display and inheritance
      getOgTitleDisplay(page) {
        const length = this.getTitleLength(page, "og");
        if (!length) return "—";
        return this.isOgTitleInherited(page) ? `${length}` : length;
      },
      isOgTitleInherited(page) {
        if (page.id === "site") return false;
        return !page.hasOgTitle;
      },
      // OG Description display and inheritance
      getOgDescriptionDisplay(page) {
        if (page.hasOgDescription) {
          return page.ogDescriptionLength;
        }
        if (page.hasMetaDescription) {
          return `${page.metaDescriptionLength}`;
        }
        if (this.siteSettings.siteMetaDescription) {
          return `${this.siteSettings.siteMetaDescription.length}`;
        }
        return "—";
      },
      isOgDescriptionInherited(page) {
        if (page.hasOgDescription) return false;
        return page.hasMetaDescription || this.siteSettings.siteMetaDescription;
      },
      getOgDescriptionStatusClass(page) {
        let length = 0;
        if (page.hasOgDescription) {
          length = page.ogDescriptionLength;
        } else if (page.hasMetaDescription) {
          length = page.metaDescriptionLength;
        } else if (this.siteSettings.siteMetaDescription) {
          length = this.siteSettings.siteMetaDescription.length;
        }
        return this.getStatusClass(page, length, "ogDescription");
      }
    }
  };
  var _sfc_render$5 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { staticClass: "k-meta-kit-table", class: { "k-meta-kit-table-preview": _vm.showPreview } }, [_c("table", [_c("thead", [_c("tr", [_c("th", { staticClass: "k-meta-kit-table-checkbox" }, [_c("input", { attrs: { "type": "checkbox" }, domProps: { "checked": _vm.isAllSelected }, on: { "change": function($event) {
      return _vm.$emit("toggle-select-all");
    } } })]), _c("th", [_vm._v("#")]), _c("th", [_vm._v("Page")]), !_vm.showPreview ? _c("th", [_vm._v("Slug")]) : _vm._e(), _vm.showPreview ? _c("th", [_vm._v(_vm._s(_vm.previewMode === "og" ? "OG Title" : "Title"))]) : _vm._e(), _vm.showPreview ? _c("th", [_vm._v(_vm._s(_vm.previewMode === "og" ? "OG Description" : "Description"))]) : _vm._e(), !_vm.showPreview ? _c("th", [_vm._v("Title")]) : _vm._e(), !_vm.showPreview ? _c("th", [_vm._v("Desc.")]) : _vm._e(), !_vm.showPreview ? _c("th", [_vm._v("OG Title")]) : _vm._e(), !_vm.showPreview ? _c("th", [_vm._v("OG Desc.")]) : _vm._e(), _c("th", [_vm._v("OG Img")]), !_vm.showPreview && _vm.previewMode === "meta" ? _c("th", [_vm._v("Robots")]) : _vm._e(), _c("th", [_vm._v("Actions")])])]), _c("tbody", _vm._l(_vm.pages, function(page, index) {
      return _c("tr", { key: page.id }, [_c("td", { staticClass: "k-meta-kit-table-checkbox" }, [_c("input", { attrs: { "type": "checkbox" }, domProps: { "checked": _vm.isPageSelected(page.id) }, on: { "change": function($event) {
        return _vm.$emit("toggle-page", page.id);
      } } })]), _c("td", [_vm._v(_vm._s(_vm.startIndex + index + 1))]), _c("td", [_c("div", { staticClass: "k-meta-kit-table-page" }, [_c("a", { staticClass: "k-link", attrs: { "href": page.panelUrl } }, [_vm._v(_vm._s(page.title))]), _c("div", { staticClass: "k-meta-kit-page-title-wrapper" }, [_c("span", { staticClass: "k-meta-kit-table-page-id" }, [_vm._v(_vm._s(page.template))]), _c("span", { class: ["k-meta-kit-status-dot", _vm.getStatusDotClass(page)], attrs: { "title": _vm.getStatusLabel(page) } })])])]), !_vm.showPreview ? _c("td", [_c("Tooltip", { attrs: { "content": _vm.getSlugTooltip(page) } }, [_c("span", { class: [_vm.getSlugStatusClass(page), "k-meta-kit-table-tooltip"] }, [_vm._v(" " + _vm._s(page.id) + " ")])])], 1) : _vm._e(), _vm.showPreview ? _c("td", [_vm.previewMode === "meta" ? [_c("Tooltip", { attrs: { "content": _vm.getTitleTooltip(page, false) } }, [_c("span", { class: [
        "k-meta-kit-table-preview-indicator",
        "k-meta-kit-table-tooltip",
        _vm.isTitleInherited(page) ? "k-meta-kit-inherited-preview" : ""
      ], attrs: { "data-status": _vm.getStatusValue(_vm.getTableTitleStatusClass(page)) } }, [_vm._v(" " + _vm._s(_vm.getFullTitlePreview(page, "meta")) + " ")])])] : [_c("Tooltip", { attrs: { "content": _vm.getOgTitleTooltip(page, false) } }, [_c("span", { class: [
        "k-meta-kit-table-preview-indicator",
        "k-meta-kit-table-tooltip",
        _vm.isOgTitleInherited(page) ? "k-meta-kit-inherited-preview" : ""
      ], attrs: { "data-status": _vm.getStatusValue(_vm.getTableOgTitleStatusClass(page)) } }, [page.hasOgTitle ? [_vm._v(" " + _vm._s(_vm.getFullTitlePreview(page, "og")) + " ")] : [_c("span", { staticClass: "k-meta-kit-table-preview-fallback" }, [_vm._v(" " + _vm._s(_vm.getFullTitlePreview(page, "og")) + " ")])]], 2)])]], 2) : _vm._e(), _vm.showPreview ? _c("td", [_vm.previewMode === "meta" ? [_c("Tooltip", { attrs: { "content": _vm.getDescriptionTooltip(page, false) } }, [_c("span", { staticClass: "k-meta-kit-table-preview-indicator k-meta-kit-table-tooltip", attrs: { "data-status": _vm.getStatusValue(_vm.getDescriptionStatusClass(page)) } }, [page.hasMetaDescription ? [_vm._v(" " + _vm._s(page.metaDescription) + " ")] : _vm.siteSettings.siteMetaDescription ? [_c("span", { staticClass: "k-meta-kit-table-preview-fallback" }, [_vm._v(" " + _vm._s(_vm.siteSettings.siteMetaDescription) + " ")])] : [_vm._v(" — ")]], 2)])] : [_c("Tooltip", { attrs: { "content": _vm.getOgDescriptionTooltip(page, false) } }, [_c("span", { staticClass: "k-meta-kit-table-preview-indicator k-meta-kit-table-tooltip", attrs: { "data-status": _vm.getStatusValue(_vm.getOgDescriptionStatusClass(page)) } }, [page.hasOgDescription ? [_vm._v(" " + _vm._s(page.ogDescription) + " ")] : page.hasMetaDescription ? [_c("span", { staticClass: "k-meta-kit-table-preview-fallback" }, [_vm._v(" " + _vm._s(page.metaDescription) + " ")])] : _vm.siteSettings.siteMetaDescription ? [_c("span", { staticClass: "k-meta-kit-table-preview-fallback" }, [_vm._v(" " + _vm._s(_vm.siteSettings.siteMetaDescription) + " ")])] : [_vm._v(" — ")]], 2)])]], 2) : _vm._e(), !_vm.showPreview ? _c("td", { staticClass: "k-meta-kit-table-center" }, [_c("Tooltip", { attrs: { "content": _vm.getTitleTooltip(page) } }, [_c("span", { class: [
        _vm.getTableTitleStatusClass(page),
        _vm.isTitleInherited(page) ? "k-meta-kit-inherited" : "",
        "k-meta-kit-table-tooltip"
      ] }, [_vm._v(" " + _vm._s(_vm.getTitleDisplay(page)) + " ")])])], 1) : _vm._e(), !_vm.showPreview ? _c("td", { staticClass: "k-meta-kit-table-center" }, [_c("Tooltip", { attrs: { "content": _vm.getDescriptionTooltip(page) } }, [_c("span", { class: [
        _vm.getDescriptionStatusClass(page),
        _vm.isDescriptionInherited(page) ? "k-meta-kit-inherited" : "",
        "k-meta-kit-table-tooltip"
      ] }, [_vm._v(" " + _vm._s(_vm.getDescriptionDisplay(page)) + " ")])])], 1) : _vm._e(), !_vm.showPreview ? _c("td", { staticClass: "k-meta-kit-table-center" }, [_c("Tooltip", { attrs: { "content": _vm.getOgTitleTooltip(page) } }, [_c("span", { class: [
        _vm.getTableOgTitleStatusClass(page),
        _vm.isOgTitleInherited(page) ? "k-meta-kit-inherited" : "",
        "k-meta-kit-table-tooltip"
      ] }, [_vm._v(" " + _vm._s(_vm.getOgTitleDisplay(page)) + " ")])])], 1) : _vm._e(), !_vm.showPreview ? _c("td", { staticClass: "k-meta-kit-table-center" }, [_c("Tooltip", { attrs: { "content": _vm.getOgDescriptionTooltip(page) } }, [_c("span", { class: [
        _vm.getOgDescriptionStatusClass(page),
        _vm.isOgDescriptionInherited(page) ? "k-meta-kit-inherited" : "",
        "k-meta-kit-table-tooltip"
      ] }, [_vm._v(" " + _vm._s(_vm.getOgDescriptionDisplay(page)) + " ")])])], 1) : _vm._e(), _c("td", { staticClass: "k-meta-kit-table-center" }, [page.hasOgImage ? [_c("Tooltip", { attrs: { "content": "Has OG image" } }, [_c("span", { staticClass: "k-meta-kit-og-image-indicator" }, [_c("k-icon", { staticClass: "k-meta-kit-icon-success", attrs: { "type": "check" } })], 1)])] : !page.hasOgImage && _vm.siteSettings.siteHasOgImage ? [_c("Tooltip", { attrs: { "content": "OG image inherited from site" } }, [_c("span", { staticClass: "k-meta-kit-og-image-indicator k-meta-kit-inherited" }, [_c("k-icon", { staticClass: "k-meta-kit-icon-success", attrs: { "type": "check" } })], 1)])] : [_c("Tooltip", { attrs: { "content": "No OG image" } }, [_c("span", [_vm._v("—")])])]], 2), !_vm.showPreview && _vm.previewMode === "meta" ? _c("td", { staticClass: "k-meta-kit-table-center" }, [page.robots && page.robots.includes("noindex") ? _c("span", { staticClass: "k-meta-kit-robots-noindex" }, [_vm._v("noindex")]) : _c("span", [_vm._v("—")])]) : _vm._e(), _c("td", { staticClass: "k-meta-kit-table-center" }, [_c("div", { staticClass: "k-meta-kit-table-actions" }, [_c("k-button", { attrs: { "icon": "edit", "size": "sm", "title": "Edit Metadata" }, on: { "click": function($event) {
        return _vm.$emit("edit-page", page.id);
      } } }), _vm.aiEnabled && false ? _c("k-button", { attrs: { "icon": "sparkling", "size": "sm", "disabled": page.hasMetaDescription, "title": "Generate Description" }, on: { "click": function($event) {
        return _vm.$emit("generate-description", page.id);
      } } }) : _vm._e()], 1)])]);
    }), 0)])]);
  };
  var _sfc_staticRenderFns$5 = [];
  _sfc_render$5._withStripped = true;
  var __component__$5 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$5,
    _sfc_render$5,
    _sfc_staticRenderFns$5
  );
  __component__$5.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/components/parts/table/MetaKitTable.vue";
  const MetaKitTable = __component__$5.exports;
  const _sfc_main$4 = {
    props: {
      selectedCount: {
        type: Number,
        default: 0
      }
    },
    data() {
      return {
        options: {
          title: false,
          description: true,
          ogTitle: false,
          ogDescription: false
        }
      };
    },
    computed: {
      hasAnySelected() {
        return this.options.title || this.options.description || this.options.ogTitle || this.options.ogDescription;
      }
    },
    methods: {
      open() {
        this.options.title = false;
        this.options.description = true;
        this.options.ogTitle = false;
        this.options.ogDescription = false;
        this.$refs.dialog.open();
      },
      close() {
        this.$refs.dialog.close();
      },
      generate() {
        this.$emit("generate", { ...this.options });
        this.close();
      }
    }
  };
  var _sfc_render$4 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("k-dialog", { ref: "dialog", attrs: { "size": "medium" }, scopedSlots: _vm._u([{ key: "footer", fn: function() {
      return [_c("k-button-group", { staticClass: "k-meta-kit-bulk-buttons" }, [_c("k-button", { on: { "click": function($event) {
        return _vm.close();
      } } }, [_vm._v("Cancel")]), _c("k-button", { attrs: { "icon": "sparkling", "theme": "positive", "disabled": !_vm.hasAnySelected }, on: { "click": _vm.generate } }, [_vm._v(" Generate ")])], 1)];
    }, proxy: true }]) }, [_c("k-headline", [_vm._v("Generate Missing Metadata")]), _c("k-text", [_vm._v("Select which fields to generate for " + _vm._s(_vm.selectedCount) + " selected page(s):")]), _c("div", { staticClass: "k-meta-kit-bulk-options" }, [_c("label", { staticClass: "k-meta-kit-bulk-option" }, [_c("input", { directives: [{ name: "model", rawName: "v-model", value: _vm.options.title, expression: "options.title" }], attrs: { "type": "checkbox" }, domProps: { "checked": Array.isArray(_vm.options.title) ? _vm._i(_vm.options.title, null) > -1 : _vm.options.title }, on: { "change": function($event) {
      var $$a = _vm.options.title, $$el = $event.target, $$c = $$el.checked ? true : false;
      if (Array.isArray($$a)) {
        var $$v = null, $$i = _vm._i($$a, $$v);
        if ($$el.checked) {
          $$i < 0 && _vm.$set(_vm.options, "title", $$a.concat([$$v]));
        } else {
          $$i > -1 && _vm.$set(_vm.options, "title", $$a.slice(0, $$i).concat($$a.slice($$i + 1)));
        }
      } else {
        _vm.$set(_vm.options, "title", $$c);
      }
    } } }), _c("div", { staticClass: "k-meta-kit-bulk-option-content" }, [_c("strong", [_vm._v("Meta Title")]), _c("span", [_vm._v("Generate meta titles for search engines (pages without one)")])])]), _c("label", { staticClass: "k-meta-kit-bulk-option" }, [_c("input", { directives: [{ name: "model", rawName: "v-model", value: _vm.options.description, expression: "options.description" }], attrs: { "type": "checkbox" }, domProps: { "checked": Array.isArray(_vm.options.description) ? _vm._i(_vm.options.description, null) > -1 : _vm.options.description }, on: { "change": function($event) {
      var $$a = _vm.options.description, $$el = $event.target, $$c = $$el.checked ? true : false;
      if (Array.isArray($$a)) {
        var $$v = null, $$i = _vm._i($$a, $$v);
        if ($$el.checked) {
          $$i < 0 && _vm.$set(_vm.options, "description", $$a.concat([$$v]));
        } else {
          $$i > -1 && _vm.$set(_vm.options, "description", $$a.slice(0, $$i).concat($$a.slice($$i + 1)));
        }
      } else {
        _vm.$set(_vm.options, "description", $$c);
      }
    } } }), _c("div", { staticClass: "k-meta-kit-bulk-option-content" }, [_c("strong", [_vm._v("Meta Description")]), _c("span", [_vm._v("Generate meta descriptions for search engines (pages without one)")])])]), _c("label", { staticClass: "k-meta-kit-bulk-option" }, [_c("input", { directives: [{ name: "model", rawName: "v-model", value: _vm.options.ogTitle, expression: "options.ogTitle" }], attrs: { "type": "checkbox" }, domProps: { "checked": Array.isArray(_vm.options.ogTitle) ? _vm._i(_vm.options.ogTitle, null) > -1 : _vm.options.ogTitle }, on: { "change": function($event) {
      var $$a = _vm.options.ogTitle, $$el = $event.target, $$c = $$el.checked ? true : false;
      if (Array.isArray($$a)) {
        var $$v = null, $$i = _vm._i($$a, $$v);
        if ($$el.checked) {
          $$i < 0 && _vm.$set(_vm.options, "ogTitle", $$a.concat([$$v]));
        } else {
          $$i > -1 && _vm.$set(_vm.options, "ogTitle", $$a.slice(0, $$i).concat($$a.slice($$i + 1)));
        }
      } else {
        _vm.$set(_vm.options, "ogTitle", $$c);
      }
    } } }), _c("div", { staticClass: "k-meta-kit-bulk-option-content" }, [_c("strong", [_vm._v("OG Title")]), _c("span", [_vm._v("Generate social media titles (pages without one)")])])]), _c("label", { staticClass: "k-meta-kit-bulk-option" }, [_c("input", { directives: [{ name: "model", rawName: "v-model", value: _vm.options.ogDescription, expression: "options.ogDescription" }], attrs: { "type": "checkbox" }, domProps: { "checked": Array.isArray(_vm.options.ogDescription) ? _vm._i(_vm.options.ogDescription, null) > -1 : _vm.options.ogDescription }, on: { "change": function($event) {
      var $$a = _vm.options.ogDescription, $$el = $event.target, $$c = $$el.checked ? true : false;
      if (Array.isArray($$a)) {
        var $$v = null, $$i = _vm._i($$a, $$v);
        if ($$el.checked) {
          $$i < 0 && _vm.$set(_vm.options, "ogDescription", $$a.concat([$$v]));
        } else {
          $$i > -1 && _vm.$set(_vm.options, "ogDescription", $$a.slice(0, $$i).concat($$a.slice($$i + 1)));
        }
      } else {
        _vm.$set(_vm.options, "ogDescription", $$c);
      }
    } } }), _c("div", { staticClass: "k-meta-kit-bulk-option-content" }, [_c("strong", [_vm._v("OG Description")]), _c("span", [_vm._v("Generate social media descriptions (pages without one)")])])])])], 1);
  };
  var _sfc_staticRenderFns$4 = [];
  _sfc_render$4._withStripped = true;
  var __component__$4 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$4,
    _sfc_render$4,
    _sfc_staticRenderFns$4
  );
  __component__$4.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/components/parts/edit/MetaKitBulkGenerateDialog.vue";
  const MetaKitBulkGenerateDialog = __component__$4.exports;
  const _sfc_main$3 = {
    components: {
      MetaKitTitleField,
      MetaKitDescriptionField
    },
    props: {
      api: {
        type: Object,
        required: true
      },
      siteSettings: {
        type: Object,
        required: true
      },
      aiEnabled: {
        type: Boolean,
        default: true
      }
    },
    data() {
      return {
        page: null,
        isLoading: false,
        editedFields: {
          metaTitle: "",
          metaDescription: "",
          ogTitle: "",
          ogDescription: ""
        },
        generating: {
          metaTitle: false,
          metaDescription: false,
          ogTitle: false,
          ogDescription: false
        }
      };
    },
    computed: {
      hasChanges() {
        if (!this.page) return false;
        return this.editedFields.metaTitle !== (this.page.metaTitle || "") || this.editedFields.metaDescription !== (this.page.metaDescription || "") || this.editedFields.ogTitle !== (this.page.ogTitle || "") || this.editedFields.ogDescription !== (this.page.ogDescription || "");
      }
    },
    methods: {
      async open(pageId) {
        this.isLoading = true;
        this.$refs.dialog.open();
        try {
          const response = await this.api.get("meta-kit/single-page", { pageId });
          if (response.status === "success") {
            this.page = response.data;
            this.editedFields.metaTitle = this.page.metaTitle || "";
            this.editedFields.metaDescription = this.page.metaDescription || "";
            this.editedFields.ogTitle = this.page.ogTitle || "";
            this.editedFields.ogDescription = this.page.ogDescription || "";
          }
        } catch (error) {
          window.panel.notification.error("Failed to load page");
        } finally {
          this.isLoading = false;
        }
      },
      close() {
        this.$refs.dialog.close();
        this.page = null;
      },
      async generate(fieldName) {
        if (!this.page) return;
        this.generating[fieldName] = true;
        try {
          const response = await this.api.post("meta-kit/generate-field", {
            pageId: this.page.id,
            fieldName
          });
          if (response.status === "success" && response.content) {
            this.editedFields[fieldName] = response.content;
            window.panel.notification.success("AI content generated successfully");
          } else {
            window.panel.notification.error(response.message || "Failed to generate content");
          }
        } catch (error) {
          window.panel.notification.error("Failed to generate content");
        } finally {
          this.generating[fieldName] = false;
        }
      },
      async save() {
        if (!this.page || !this.hasChanges) return;
        const fields = [
          { name: "metaTitle", value: this.editedFields.metaTitle, original: this.page.metaTitle || "" },
          { name: "metaDescription", value: this.editedFields.metaDescription, original: this.page.metaDescription || "" },
          { name: "ogTitle", value: this.editedFields.ogTitle, original: this.page.ogTitle || "" },
          { name: "ogDescription", value: this.editedFields.ogDescription, original: this.page.ogDescription || "" }
        ];
        let savedCount = 0;
        for (const field of fields) {
          if (field.value !== field.original) {
            try {
              await this.api.post("meta-kit/apply-single-field", {
                pageId: this.page.id,
                fieldName: field.name,
                value: field.value
              });
              savedCount++;
            } catch (error) {
              window.panel.notification.error(`Failed to update ${field.name}`);
            }
          }
        }
        if (savedCount > 0) {
          window.panel.notification.success(`Updated ${savedCount} field${savedCount > 1 ? "s" : ""}`);
          this.$emit("saved");
          try {
            const response = await this.api.get("meta-kit/single-page", { pageId: this.page.id });
            if (response.status === "success") {
              this.page = response.data;
              this.editedFields.metaTitle = this.page.metaTitle || "";
              this.editedFields.metaDescription = this.page.metaDescription || "";
              this.editedFields.ogTitle = this.page.ogTitle || "";
              this.editedFields.ogDescription = this.page.ogDescription || "";
            }
          } catch (error) {
          }
        }
      },
      editInPanel() {
        if (this.page && this.page.panelUrl) {
          this.close();
          window.panel.view.open(this.page.panelUrl);
        }
      }
    }
  };
  var _sfc_render$3 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("k-dialog", { ref: "dialog", attrs: { "size": "large", "cancelButton": "Close", "submitButton": "" } }, [_vm.page ? _c("k-headline", [_vm._v("Edit: " + _vm._s(_vm.page.title))]) : _vm._e(), _vm.isLoading ? _c("div", { staticClass: "k-meta-kit-loading" }, [_c("k-icon", { staticClass: "k-meta-kit-spinner", attrs: { "type": "loader" } }), _c("span", [_vm._v("Loading page...")])], 1) : _vm.page ? _c("div", { staticClass: "k-meta-kit-single-edit" }, [_c("div", { staticClass: "k-meta-kit-single-field" }, [_c("label", { staticClass: "k-meta-kit-single-field-label" }, [_vm._v("Meta Title")]), _c("meta-kit-title-field", { attrs: { "value": _vm.editedFields.metaTitle, "page-id": _vm.page.id, "page-title": _vm.page.title, "site-settings": _vm.siteSettings, "ai-enabled": _vm.aiEnabled, "is-generating": _vm.generating.metaTitle, "placeholder": _vm.page.metaTitle || _vm.page.title || "No meta title set", "button-size": "sm", "field-class": "k-meta-kit-single-field-content" }, on: { "input": function($event) {
      _vm.editedFields.metaTitle = $event;
    }, "generate": function($event) {
      return _vm.generate("metaTitle");
    } } })], 1), _c("div", { staticClass: "k-meta-kit-single-field" }, [_c("label", { staticClass: "k-meta-kit-single-field-label" }, [_vm._v("Meta Description")]), _c("meta-kit-description-field", { attrs: { "value": _vm.editedFields.metaDescription, "ai-enabled": _vm.aiEnabled, "is-generating": _vm.generating.metaDescription, "placeholder": _vm.page.metaDescription || _vm.siteSettings.siteMetaDescription || "No meta description set", "button-size": "sm", "rows": 4, "buttons": "false", "field-class": "k-meta-kit-single-field-content" }, on: { "input": function($event) {
      _vm.editedFields.metaDescription = $event;
    }, "generate": function($event) {
      return _vm.generate("metaDescription");
    } } })], 1), _c("div", { staticClass: "k-meta-kit-single-field" }, [_c("label", { staticClass: "k-meta-kit-single-field-label" }, [_vm._v("OG Title")]), _c("meta-kit-title-field", { attrs: { "value": _vm.editedFields.ogTitle, "page-id": _vm.page.id, "page-title": _vm.page.title, "meta-title": _vm.page.metaTitle || _vm.editedFields.metaTitle, "site-settings": _vm.siteSettings, "ai-enabled": _vm.aiEnabled, "is-generating": _vm.generating.ogTitle, "placeholder": _vm.page.ogTitle || _vm.page.metaTitle || _vm.page.title || "No OG title", "type": "og", "button-size": "sm", "field-class": "k-meta-kit-single-field-content" }, on: { "input": function($event) {
      _vm.editedFields.ogTitle = $event;
    }, "generate": function($event) {
      return _vm.generate("ogTitle");
    } } })], 1), _c("div", { staticClass: "k-meta-kit-single-field" }, [_c("label", { staticClass: "k-meta-kit-single-field-label" }, [_vm._v("OG Description")]), _c("meta-kit-description-field", { attrs: { "value": _vm.editedFields.ogDescription, "ai-enabled": _vm.aiEnabled, "is-generating": _vm.generating.ogDescription, "placeholder": _vm.page.ogDescription || _vm.page.metaDescription || _vm.siteSettings.siteMetaDescription || "No OG description", "type": "og", "button-size": "sm", "rows": 4, "buttons": "false", "field-class": "k-meta-kit-single-field-content" }, on: { "input": function($event) {
      _vm.editedFields.ogDescription = $event;
    }, "generate": function($event) {
      return _vm.generate("ogDescription");
    } } })], 1), _c("div", { staticClass: "k-meta-kit-single-field" }, [_c("label", { staticClass: "k-meta-kit-single-field-label" }, [_vm._v("OG Image")]), _c("div", { staticClass: "k-meta-kit-single-field-content" }, [_vm.page.ogImage ? _c("div", { staticClass: "k-meta-kit-og-image-current" }, [_c("img", { attrs: { "src": _vm.page.ogImage.url, "alt": _vm.page.ogImage.filename } }), _c("span", { staticClass: "k-meta-kit-og-image-filename" }, [_vm._v(_vm._s(_vm.page.ogImage.filename))])]) : _c("div", { staticClass: "k-meta-kit-og-image-empty" }, [_vm._v(" No OG image set ")])])]), _c("div", { staticClass: "k-meta-kit-single-actions" }, [_c("k-button", { attrs: { "icon": "open" }, on: { "click": _vm.editInPanel } }, [_vm._v("Edit in Panel")]), _vm.hasChanges ? _c("k-button", { attrs: { "icon": "check", "theme": "positive" }, on: { "click": _vm.save } }, [_vm._v("Apply Changes")]) : _vm._e()], 1)]) : _vm._e()], 1);
  };
  var _sfc_staticRenderFns$3 = [];
  _sfc_render$3._withStripped = true;
  var __component__$3 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$3,
    _sfc_render$3,
    _sfc_staticRenderFns$3
  );
  __component__$3.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/components/parts/edit/MetaKitSinglePageDialog.vue";
  const MetaKitSinglePageDialog = __component__$3.exports;
  const _sfc_main$2 = {
    components: {
      MetaKitTitleField,
      MetaKitDescriptionField
    },
    props: {
      api: {
        type: Object,
        required: true
      },
      siteSettings: {
        type: Object,
        required: true
      },
      aiEnabled: {
        type: Boolean,
        default: true
      }
    },
    data() {
      return {
        pages: [],
        isLoading: false,
        activeTab: "meta",
        editedFields: {},
        generating: {}
      };
    },
    computed: {
      hasAnyChanges() {
        return this.pages.some((page) => {
          const edited = this.editedFields[page.id];
          if (!edited) return false;
          return edited.metaTitle !== (page.metaTitle || "") || edited.metaDescription !== (page.metaDescription || "") || edited.ogTitle !== (page.ogTitle || "") || edited.ogDescription !== (page.ogDescription || "");
        });
      }
    },
    methods: {
      async open(pageIds) {
        this.isLoading = true;
        this.activeTab = "meta";
        this.$refs.dialog.open();
        try {
          const response = await this.api.get("meta-kit/pages-with-content", { pageIds });
          if (response.status === "success" && response.data) {
            this.pages = response.data;
            this.editedFields = {};
            this.generating = {};
            this.pages.forEach((page) => {
              this.$set(this.editedFields, page.id, {
                metaTitle: page.metaTitle || "",
                metaDescription: page.metaDescription || "",
                ogTitle: page.ogTitle || "",
                ogDescription: page.ogDescription || ""
              });
              this.$set(this.generating, page.id, {
                metaTitle: false,
                metaDescription: false,
                ogTitle: false,
                ogDescription: false
              });
            });
          }
        } catch (error) {
          window.panel.notification.error("Failed to load pages");
        } finally {
          this.isLoading = false;
        }
      },
      close() {
        this.$refs.dialog.close();
        this.pages = [];
        this.editedFields = {};
        this.generating = {};
      },
      async generate(pageId, fieldName) {
        if (!this.generating[pageId]) return;
        this.generating[pageId][fieldName] = true;
        try {
          const response = await this.api.post("meta-kit/generate-field", {
            pageId,
            fieldName
          });
          if (response.status === "success" && response.content) {
            this.editedFields[pageId][fieldName] = response.content;
            window.panel.notification.success("AI content generated successfully");
          } else {
            window.panel.notification.error(response.message || "Failed to generate content");
          }
        } catch (error) {
          window.panel.notification.error("Failed to generate content");
        } finally {
          this.generating[pageId][fieldName] = false;
        }
      },
      async saveAll() {
        if (!this.hasAnyChanges) return;
        let totalSaved = 0;
        for (const page of this.pages) {
          const edited = this.editedFields[page.id];
          const fields = [
            { name: "metaTitle", value: edited.metaTitle, original: page.metaTitle || "" },
            { name: "metaDescription", value: edited.metaDescription, original: page.metaDescription || "" },
            { name: "ogTitle", value: edited.ogTitle, original: page.ogTitle || "" },
            { name: "ogDescription", value: edited.ogDescription, original: page.ogDescription || "" }
          ];
          for (const field of fields) {
            if (field.value !== field.original) {
              try {
                await this.api.post("meta-kit/apply-single-field", {
                  pageId: page.id,
                  fieldName: field.name,
                  value: field.value
                });
                totalSaved++;
              } catch (error) {
                window.panel.notification.error(`Failed to update ${field.name} for ${page.title}`);
              }
            }
          }
        }
        if (totalSaved > 0) {
          window.panel.notification.success(`Updated ${totalSaved} field${totalSaved > 1 ? "s" : ""} across ${this.pages.length} page${this.pages.length > 1 ? "s" : ""}`);
          this.$emit("saved");
          this.close();
        }
      }
    }
  };
  var _sfc_render$2 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("k-dialog", { ref: "dialog", attrs: { "size": "huge", "cancelButton": "Close", "submitButton": "" } }, [_c("k-headline", [_vm._v("Edit Selected Pages (" + _vm._s(_vm.pages.length) + ")")]), _vm.isLoading ? _c("div", { staticClass: "k-meta-kit-loading" }, [_c("k-icon", { staticClass: "k-meta-kit-spinner", attrs: { "type": "loader" } }), _c("span", [_vm._v("Loading pages...")])], 1) : _vm.pages.length > 0 ? _c("div", [_c("div", { staticClass: "k-meta-kit-tabs" }, [_c("k-button", { attrs: { "theme": _vm.activeTab === "meta" ? "positive" : "", "size": "sm" }, on: { "click": function($event) {
      _vm.activeTab = "meta";
    } } }, [_vm._v(" Meta Tags ")]), _c("k-button", { attrs: { "theme": _vm.activeTab === "og" ? "positive" : "", "size": "sm" }, on: { "click": function($event) {
      _vm.activeTab = "og";
    } } }, [_vm._v(" Social Media (OG) ")])], 1), _vm.activeTab === "meta" ? _c("div", { staticClass: "k-meta-kit-dialog-table-wrapper" }, _vm._l(_vm.pages, function(page) {
      return _c("div", { key: `meta-${page.id}`, staticClass: "k-meta-kit-dialog-table-page" }, [_c("div", { staticClass: "k-meta-kit-dialog-page-info" }, [_c("a", { staticClass: "k-link", attrs: { "href": page.panelUrl } }, [_vm._v(_vm._s(page.title))]), _c("a", { staticClass: "k-link k-meta-kit-page-id", attrs: { "href": page.panelUrl } }, [_vm._v(_vm._s(page.id))])]), _c("meta-kit-title-field", { attrs: { "value": _vm.editedFields[page.id].metaTitle, "page-id": page.id, "page-title": page.title, "site-settings": _vm.siteSettings, "ai-enabled": _vm.aiEnabled, "is-generating": _vm.generating[page.id].metaTitle, "placeholder": page.metaTitle || page.title || "No meta title", "type": "meta" }, on: { "input": function($event) {
        _vm.editedFields[page.id].metaTitle = $event;
      }, "generate": function($event) {
        return _vm.generate(page.id, "metaTitle");
      } } }), _c("meta-kit-description-field", { attrs: { "value": _vm.editedFields[page.id].metaDescription, "ai-enabled": _vm.aiEnabled, "is-generating": _vm.generating[page.id].metaDescription, "placeholder": page.metaDescription || _vm.siteSettings.siteMetaDescription || "No meta description" }, on: { "input": function($event) {
        _vm.editedFields[page.id].metaDescription = $event;
      }, "generate": function($event) {
        return _vm.generate(page.id, "metaDescription");
      } } })], 1);
    }), 0) : _vm._e(), _vm.activeTab === "og" ? _c("div", { staticClass: "k-meta-kit-dialog-table-wrapper" }, _vm._l(_vm.pages, function(page) {
      return _c("div", { key: `og-${page.id}`, staticClass: "k-meta-kit-dialog-table-page" }, [_c("div", { staticClass: "k-meta-kit-dialog-page-info" }, [_c("a", { staticClass: "k-link", attrs: { "href": page.panelUrl } }, [_vm._v(_vm._s(page.title))]), _c("a", { staticClass: "k-link k-meta-kit-page-id", attrs: { "href": page.panelUrl } }, [_vm._v(_vm._s(page.id))])]), _c("meta-kit-title-field", { attrs: { "value": _vm.editedFields[page.id].ogTitle, "page-id": page.id, "page-title": page.title, "meta-title": page.metaTitle || _vm.editedFields[page.id].metaTitle, "site-settings": _vm.siteSettings, "ai-enabled": _vm.aiEnabled, "is-generating": _vm.generating[page.id].ogTitle, "placeholder": page.ogTitle || page.metaTitle || page.title || "No OG title", "type": "og" }, on: { "input": function($event) {
        _vm.editedFields[page.id].ogTitle = $event;
      }, "generate": function($event) {
        return _vm.generate(page.id, "ogTitle");
      } } }), _c("meta-kit-description-field", { attrs: { "value": _vm.editedFields[page.id].ogDescription, "ai-enabled": _vm.aiEnabled, "is-generating": _vm.generating[page.id].ogDescription, "placeholder": page.ogDescription || page.metaDescription || _vm.siteSettings.siteMetaDescription || "No OG description", "type": "og" }, on: { "input": function($event) {
        _vm.editedFields[page.id].ogDescription = $event;
      }, "generate": function($event) {
        return _vm.generate(page.id, "ogDescription");
      } } })], 1);
    }), 0) : _vm._e(), _vm.hasAnyChanges ? _c("div", { staticClass: "k-meta-kit-single-actions" }, [_c("k-button", { attrs: { "icon": "check", "theme": "positive" }, on: { "click": _vm.saveAll } }, [_vm._v("Apply All Changes")])], 1) : _vm._e()]) : _c("div", { staticClass: "k-meta-kit-empty" }, [_c("k-icon", { attrs: { "type": "check" } }), _c("p", [_vm._v("No pages selected!")])], 1)], 1);
  };
  var _sfc_staticRenderFns$2 = [];
  _sfc_render$2._withStripped = true;
  var __component__$2 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$2,
    _sfc_render$2,
    _sfc_staticRenderFns$2
  );
  __component__$2.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/components/parts/edit/MetaKitBulkEditDialog.vue";
  const MetaKitBulkEditDialog = __component__$2.exports;
  const _sfc_main$1 = {
    props: {
      summary: {
        type: Object,
        default: () => ({ total: 0, byLanguage: [] })
      },
      isLoading: {
        type: Boolean,
        default: false
      },
      isMigrating: {
        type: Boolean,
        default: false
      }
    },
    methods: {
      open() {
        this.$refs.dialog.open();
        this.$emit("load-summary");
      },
      close() {
        this.$refs.dialog.close();
      }
    }
  };
  var _sfc_render$1 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("k-dialog", { ref: "dialog", attrs: { "size": "medium", "cancelButton": "Close", "submitButton": "" } }, [_c("k-headline", [_vm._v("Legacy SEO Migration")]), _vm.isLoading ? _c("div", { staticClass: "k-meta-kit-loading" }, [_c("k-icon", { staticClass: "k-meta-kit-spinner", attrs: { "type": "loader" } }), _c("span", [_vm._v("Scanning all languages for legacy metadata...")])], 1) : _c("div", [_c("div", { staticClass: "k-meta-kit-legacy-list" }, [_c("p", [_vm._v("Summary of legacy fields by language:")]), _c("ul", _vm._l(_vm.summary.byLanguage, function(item) {
      return _c("li", { key: item.code }, [_c("strong", [_vm._v(_vm._s(item.code.toUpperCase()))]), _vm._v(": " + _vm._s(item.count) + " item(s) ")]);
    }), 0), _c("p", [_c("strong", [_vm._v("Total:")]), _vm._v(" " + _vm._s(_vm.summary.total) + " item(s) across all languages")]), _c("k-box", { attrs: { "theme": "negative" } }, [_c("k-icon", { attrs: { "type": "alert" } }), _c("span", [_vm._v("Warning: Legacy metadata will be migrated to the new meta kit fields. The old fields will be removed.")])], 1), _c("k-button", { attrs: { "icon": "download", "disabled": _vm.isMigrating || _vm.summary.total === 0, "progress": _vm.isMigrating, "theme": "positive" }, on: { "click": function($event) {
      return _vm.$emit("migrate");
    } } }, [_vm._v(" Migrate All Languages ")])], 1)])], 1);
  };
  var _sfc_staticRenderFns$1 = [];
  _sfc_render$1._withStripped = true;
  var __component__$1 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$1,
    _sfc_render$1,
    _sfc_staticRenderFns$1
  );
  __component__$1.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/components/parts/edit/MetaKitLegacyDialog.vue";
  const MetaKitLegacyDialog = __component__$1.exports;
  const _sfc_main = {
    components: {
      MetaKitTitleField,
      MetaKitDescriptionField,
      MetaKitTable,
      MetaKitBulkGenerateDialog,
      MetaKitSinglePageDialog,
      MetaKitBulkEditDialog,
      MetaKitStats,
      MetaKitFilters,
      MetaKitActions,
      MetaKitLegacyDialog
    },
    provide() {
      return {
        siteSettings: this.siteSettings
      };
    },
    props: {
      pages: Array,
      language: String,
      languages: Array,
      validationSettings: {
        type: Object,
        default: () => ({})
      },
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
        isMigratingAll: false,
        pagesData: this.pages || [],
        validationSettingsData: this.validationSettings || {},
        legacyPages: [],
        legacySummary: { total: 0, byLanguage: [] },
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
          { value: 25, text: "25/page" },
          { value: 50, text: "50/page" },
          { value: 100, text: "100/page" },
          { value: 99999, text: "All" }
        ],
        searchQuery: "",
        activeFilters: [],
        showPreviewInTable: false,
        previewMode: "meta",
        loadingProgress: ""
      };
    },
    computed: {
      filteredPages() {
        let pages = this.pagesData;
        if (this.activeFilters.length > 0) {
          const metadataFilters = this.activeFilters.filter(
            (f) => [
              "missing-title",
              "missing-description",
              "missing-og-title",
              "missing-og-description",
              "missing-og-image",
              "complete"
            ].includes(f)
          );
          const statusFilters = this.activeFilters.filter(
            (f) => ["listed", "unlisted", "drafts"].includes(f)
          );
          pages = pages.filter((page) => {
            const matchesMetadata = metadataFilters.length === 0 || metadataFilters.every((filter) => {
              switch (filter) {
                case "missing-title":
                  return !page.hasMetaTitle;
                case "missing-description":
                  return !page.hasMetaDescription;
                case "missing-og-title":
                  return !page.hasOgTitle;
                case "missing-og-description":
                  return !page.hasOgDescription;
                case "missing-og-image":
                  return !page.hasOgImage;
                case "complete":
                  return page.hasMetaTitle && page.hasMetaDescription && page.hasOgImage;
                default:
                  return true;
              }
            });
            const matchesStatus = statusFilters.length === 0 || statusFilters.some((filter) => {
              switch (filter) {
                case "listed":
                  return page.status === "listed" || page.status === "published";
                case "unlisted":
                  return page.status === "unlisted";
                case "drafts":
                  return page.status === "draft";
                default:
                  return false;
              }
            });
            return matchesMetadata && matchesStatus;
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
            if (response.validationSettings) {
              this.validationSettingsData = response.validationSettings;
            }
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
        this.$refs.bulkGenerateDialog.open();
      },
      async performBulkGeneration(options) {
        if (!options.title && !options.description && !options.ogTitle && !options.ogDescription) {
          window.panel.notification.error("Please select at least one field to generate");
          return;
        }
        this.isGeneratingAll = true;
        const fields = [];
        if (options.title) fields.push("meta titles");
        if (options.description) fields.push("meta descriptions");
        if (options.ogTitle) fields.push("OG titles");
        if (options.ogDescription) fields.push("OG descriptions");
        const fieldText = fields.join(", ");
        try {
          const response = await this.$api.post("meta-kit/generate-all", {
            generateTitle: options.title,
            generateDescription: options.description,
            generateOgTitle: options.ogTitle,
            generateOgDescription: options.ogDescription,
            pageIds: this.selectedPages
          });
          if (response.status === "success") {
            const details = `Generated: ${response.generated || 0}, Skipped: ${response.skipped || 0}, Failed: ${response.failed || 0}`;
            window.panel.notification.success(`${response.message || "Generation completed!"} ${details}`);
            await this.refreshPages();
          } else {
            window.panel.notification.error(response.message || "Generation failed");
          }
        } catch (error) {
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
          this.loadingProgress = "";
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
        try {
          const res = await this.$api.post("meta-kit/convert-legacy-all-languages");
          if (res && res.status === "success") {
            window.panel.notification.success(res.message || "Migration completed");
            await this.refreshPages();
            await this.loadLegacySummary();
          } else {
            window.panel.notification.error(res.message || "Migration failed");
          }
        } catch (e) {
          window.panel.notification.error("Migration failed");
        } finally {
          this.isMigratingAll = false;
        }
      },
      dismissLegacyWarning() {
        this.legacyDetection.show = false;
        sessionStorage.setItem("metaKitLegacyDismissed", "true");
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
      getStatusValue(statusClass) {
        if (!statusClass) return "";
        const match = statusClass.match(/k-meta-kit-status-(\w+)/);
        return match ? match[1] : "";
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
      async editSinglePageMetadata(pageId) {
        this.$refs.singlePageDialog.open(pageId);
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
        this.$refs.allPagesDialog.open(this.selectedPages);
      },
      refreshPages() {
        if (window.panel && window.panel.$reload) {
          window.panel.$reload();
        } else {
          window.location.reload();
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
    }), 1), _vm.legacyMigration ? _c("k-button", { attrs: { "icon": "download", "size": "xs" }, on: { "click": _vm.showLegacyDialog } }, [_vm._v("Legacy Migration")]) : _vm._e()], 1) : _vm._e(), _vm.legacyMigration && _vm.legacyDetection.show && _vm.legacyDetection.found > 0 ? _c("div", { staticClass: "k-meta-kit-warning" }, [_c("k-box", { attrs: { "theme": "info" } }, [_c("k-icon", { attrs: { "type": "info" } }), _c("span", [_vm._v("Found " + _vm._s(_vm.legacyDetection.found) + " pages with legacy SEO metadata")]), _c("k-button", { attrs: { "icon": "download" }, on: { "click": _vm.showLegacyDialog } }, [_vm._v("View & Convert")]), _c("k-button", { attrs: { "icon": "cancel" }, on: { "click": _vm.dismissLegacyWarning } }, [_vm._v("Dismiss")])], 1)], 1) : _vm._e(), _c("meta-kit-stats", { attrs: { "filtered-count": _vm.filteredPages.length, "total-count": _vm.pagesData.length, "filtered-with-description": _vm.filteredPagesWithDescription, "total-with-description": _vm.pagesWithDescription, "filtered-with-image": _vm.filteredPagesWithOgImage, "total-with-image": _vm.pagesWithOgImage, "filtered-no-index": _vm.filteredPagesNoIndex, "total-no-index": _vm.pagesNoIndex, "search-active": !!_vm.searchQuery } }), _c("meta-kit-actions", { attrs: { "selected-count": _vm.selectedPages.length, "ai-enabled": _vm.aiEnabled, "is-generating": _vm.isGeneratingAll }, on: { "edit-selected": _vm.showSelectedPagesDialog, "generate-missing": _vm.generateAllDescriptions, "refresh": _vm.refreshPages }, scopedSlots: _vm._u([{ key: "filters", fn: function() {
      return [_c("meta-kit-filters", { attrs: { "show-preview": _vm.showPreviewInTable, "preview-mode": _vm.previewMode, "search-query": _vm.searchQuery, "active-filters": _vm.activeFilters, "page-size": _vm.pageSize, "page-size-options": _vm.pageSizeOptions }, on: { "update:showPreview": function($event) {
        _vm.showPreviewInTable = $event;
      }, "update:show-preview": function($event) {
        _vm.showPreviewInTable = $event;
      }, "update:previewMode": function($event) {
        _vm.previewMode = $event;
      }, "update:preview-mode": function($event) {
        _vm.previewMode = $event;
      }, "update:searchQuery": function($event) {
        _vm.searchQuery = $event;
      }, "update:search-query": function($event) {
        _vm.searchQuery = $event;
      }, "update:activeFilters": function($event) {
        _vm.activeFilters = $event;
      }, "update:active-filters": function($event) {
        _vm.activeFilters = $event;
      }, "change-page-size": _vm.changePageSize } })];
    }, proxy: true }]) }), _c("meta-kit-table", { attrs: { "pages": _vm.paginatedPages, "start-index": (_vm.currentPage - 1) * _vm.pageSize, "selected-pages": _vm.selectedPages, "is-all-selected": _vm.isAllCurrentPageSelected, "show-preview": _vm.showPreviewInTable, "preview-mode": _vm.previewMode, "ai-enabled": _vm.aiEnabled, "validation-settings": _vm.validationSettingsData }, on: { "toggle-select-all": _vm.toggleSelectAllCurrentPage, "toggle-page": _vm.togglePageSelection, "edit-page": _vm.editSinglePageMetadata, "generate-description": _vm.generateDescription } }), _vm.totalPages > 1 ? _c("div", { staticClass: "k-meta-kit-pagination" }, [_c("k-button", { attrs: { "icon": "angle-left", "disabled": _vm.currentPage === 1 }, on: { "click": _vm.previousPage } }), _c("span", { staticClass: "k-meta-kit-pagination-info" }, [_vm._v(" Page " + _vm._s(_vm.currentPage) + " of " + _vm._s(_vm.totalPages) + " "), _vm.searchQuery ? [_vm._v("(" + _vm._s(_vm.filteredPages.length) + " of " + _vm._s(_vm.pagesData.length) + ")")] : [_vm._v("(" + _vm._s(_vm.pagesData.length) + " total)")]], 2), _c("k-button", { attrs: { "icon": "angle-right", "disabled": _vm.currentPage === _vm.totalPages }, on: { "click": _vm.nextPage } })], 1) : _vm._e(), _c("meta-kit-legacy-dialog", { ref: "legacyDialog", attrs: { "summary": _vm.legacySummary, "is-loading": _vm.isLoadingLegacy, "is-migrating": _vm.isMigratingAll }, on: { "load-summary": _vm.loadLegacySummary, "migrate": _vm.migrateAllLanguages } }), _c("meta-kit-bulk-edit-dialog", { ref: "allPagesDialog", attrs: { "api": _vm.$api, "site-settings": _vm.siteSettings, "ai-enabled": _vm.aiEnabled }, on: { "saved": _vm.refreshPages } }), _c("meta-kit-single-page-dialog", { ref: "singlePageDialog", attrs: { "api": _vm.$api, "site-settings": _vm.siteSettings, "ai-enabled": _vm.aiEnabled }, on: { "saved": _vm.refreshPages } }), _c("meta-kit-bulk-generate-dialog", { ref: "bulkGenerateDialog", attrs: { "selected-count": _vm.selectedPages.length }, on: { "generate": _vm.performBulkGeneration } }), _vm.isGeneratingAll || _vm.isLoadingPages || _vm.isMigratingAll ? _c("div", { staticClass: "k-meta-kit-loading-overlay" }, [_c("div", { staticClass: "k-meta-kit-loading-content" }, [_c("div", { staticClass: "k-meta-kit-loading-spinner" }, [_c("k-icon", { attrs: { "type": "loader" } })], 1), _c("div", { staticClass: "k-meta-kit-loading-text" }, [_vm.isGeneratingAll ? [_vm._v("Generating metadata with AI...")] : _vm.isLoadingPages ? [_vm._v("Refreshing pages...")] : _vm.isMigratingAll ? [_vm._v("Migrating legacy fields...")] : _vm._e()], 2), _vm.loadingProgress ? _c("div", { staticClass: "k-meta-kit-loading-progress" }, [_vm._v(" " + _vm._s(_vm.loadingProgress) + " ")]) : _vm._e()])]) : _vm._e()], 1);
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
