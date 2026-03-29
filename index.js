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
  const _sfc_main$e = {
    props: {
      label: String,
      parent: String,
      name: String
    },
    computed: {
      currentLanguage() {
        var _a, _b, _c, _d, _e, _f;
        return ((_a = this.$language) == null ? void 0 : _a.code) || ((_d = (_c = (_b = window.panel) == null ? void 0 : _b.view) == null ? void 0 : _c.props) == null ? void 0 : _d.language) || ((_f = (_e = window.panel) == null ? void 0 : _e.language) == null ? void 0 : _f.code) || null;
      }
    },
    watch: {
      parent() {
        this.handleContextChange();
      },
      name() {
        this.handleContextChange();
      },
      "$route.fullPath"() {
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
        separator: "|",
        siteOgImage: null,
        siteOgImageDetermined: false,
        updateTimeout: null,
        fieldCheckInterval: null,
        lastFieldValues: {},
        filesObserver: null,
        contextChangeTimeout: null
      };
    },
    async mounted() {
      await this.load();
      document.addEventListener("seo-field-updated", this.handleSeoFieldUpdate, true);
      document.addEventListener("meta-kit-field-change", this.handleMetaKitFieldChange, true);
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
      document.removeEventListener("meta-kit-field-change", this.handleMetaKitFieldChange, true);
      document.removeEventListener("input", this.handleDOMInput, true);
      document.removeEventListener("change", this.handleDOMInput, true);
      if (this.fieldCheckInterval) {
        clearInterval(this.fieldCheckInterval);
      }
      if (this.filesObserver) {
        this.filesObserver.disconnect();
      }
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
          this.meta = null;
          this.siteName = null;
          this.separator = "|";
          this.siteOgImage = null;
          this.siteOgImageDetermined = false;
          this.lastFieldValues = {};
          await this.load();
          this.$nextTick(() => {
            setTimeout(() => {
              this.updatePreviewFromDOM();
              this.determineSiteDefaultImage();
            }, 800);
          });
        }, 50);
      },
      setupFieldObserver() {
        const checkFieldValues = () => {
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
          const getFieldOrNull = (name) => {
            const input = document.querySelector(`[name="${name}"]`);
            if (!input) return null;
            return input.value ?? "";
          };
          const currentValues = {
            metatitle: getFieldOrNull("metaTitle"),
            metadescription: getFieldOrNull("metaDescription"),
            ogtitle: getFieldOrNull("ogTitle"),
            ogdescription: getFieldOrNull("ogDescription"),
            ogimage: getImageSrc()
          };
          const anyMissing = Object.values(currentValues).some((v) => v === null);
          if (anyMissing) {
            return;
          }
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
        if (!target) return;
        const fieldName = target.name || target.getAttribute("name");
        const seoFields = ["metaTitle", "metaDescription", "ogTitle", "ogDescription"];
        if (fieldName && seoFields.includes(fieldName)) {
          clearTimeout(this.updateTimeout);
          this.updateTimeout = setTimeout(() => {
            this.updatePreviewFromDOM();
          }, 300);
          return;
        }
        if (target.closest(".k-field-name-ogimage")) {
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
      handleMetaKitFieldChange(event) {
        if (event.detail) {
          clearTimeout(this.updateTimeout);
          this.updateTimeout = setTimeout(() => {
            this.updatePreviewFromDOM();
          }, 200);
        }
      },
      updatePreviewFromDOM() {
        const getFieldValue = (name) => {
          const input = document.querySelector(`[name="${name}"]`);
          if (!input) return null;
          return input.value ?? "";
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
          metatitle: getFieldValue("metaTitle"),
          metadescription: getFieldValue("metaDescription"),
          ogtitle: getFieldValue("ogTitle"),
          ogdescription: getFieldValue("ogDescription"),
          ogimage: getOgImage()
        };
        const values = Object.values(seoData);
        if (values.some((v) => v === null)) {
          return;
        }
        const pageTitleValue = getFieldValue("title");
        const pageTitle = pageTitleValue || "Page Title";
        const allEmpty = [
          seoData.metatitle,
          seoData.metadescription,
          seoData.ogtitle,
          seoData.ogdescription
        ].every((v) => !v || !String(v).trim());
        const isPlaceholderTitle = !pageTitleValue;
        if (allEmpty && isPlaceholderTitle && this.meta) {
          return;
        }
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
          const baseUrl = this.parent + "/sections/" + this.name;
          const lang = this.currentLanguage;
          const url = lang ? `${baseUrl}?language=${encodeURIComponent(lang)}` : baseUrl;
          const response = await this.$api.get(url);
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
  var _sfc_render$e = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("section", { staticClass: "k-seo-preview-section" }, [_c("header", { staticClass: "k-section-header" }, [_c("h2", { staticClass: "k-headline" }, [_vm._v(_vm._s(_vm.label || "SEO Preview"))])]), _vm.meta ? _c("div", { staticClass: "k-seo-previews" }, [_c("div", { staticClass: "k-seo-preview k-seo-preview--google" }, [_c("h3", { staticClass: "k-seo-preview__title" }, [_vm._v("Google Search Preview")]), _c("div", { staticClass: "k-seo-preview__content" }, [_c("div", { staticClass: "k-google-preview" }, [_c("cite", { staticClass: "k-google-preview__url" }, [_vm._v(_vm._s(_vm.displayUrl(_vm.meta.url)))]), _c("h3", { staticClass: "k-google-preview__title" }, [_vm._v(_vm._s(_vm.meta.title || "Page Title"))]), _c("p", { staticClass: "k-google-preview__description" }, [_vm._v(_vm._s(_vm.meta.description || "No description available"))])])])]), _c("div", { staticClass: "k-seo-preview k-seo-preview--twitter" }, [_c("h3", { staticClass: "k-seo-preview__title" }, [_vm._v("Share / Card Preview")]), _c("div", { staticClass: "k-seo-preview__content" }, [_c("div", { staticClass: "k-twitter-preview" }, [_vm.meta.ogImage ? _c("div", { staticClass: "k-twitter-preview__image", style: { backgroundImage: "url(" + _vm.meta.ogImage + ")" } }) : _vm._e(), _c("div", { staticClass: "k-twitter-preview__body" }, [_c("cite", { staticClass: "k-twitter-preview__url" }, [_vm._v(_vm._s(_vm.displayUrl(_vm.meta.url)))]), _c("h4", { staticClass: "k-twitter-preview__title" }, [_vm._v(_vm._s(_vm.meta.ogTitle || _vm.meta.title || "Page Title"))]), _c("p", { staticClass: "k-twitter-preview__description" }, [_vm._v(_vm._s(_vm.truncate(_vm.meta.ogDescription || _vm.meta.description, 140) || "No description"))])])])])])]) : _c("div", { staticClass: "k-seo-preview-loading" }, [_vm._v(" Loading preview... ")])]);
  };
  var _sfc_staticRenderFns$e = [];
  _sfc_render$e._withStripped = true;
  var __component__$e = /* @__PURE__ */ normalizeComponent(
    _sfc_main$e,
    _sfc_render$e,
    _sfc_staticRenderFns$e
  );
  __component__$e.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/sections/seo-preview.vue";
  const SeoPreview = __component__$e.exports;
  const _sfc_main$d = {
    props: {
      filteredCount: { type: Number, required: true },
      totalCount: { type: Number, required: true },
      filteredCustomTitle: { type: Number, required: true },
      totalCustomTitle: { type: Number, required: true },
      filteredPageFallback: { type: Number, required: true },
      totalPageFallback: { type: Number, required: true },
      filteredWithDescription: { type: Number, required: true },
      totalWithDescription: { type: Number, required: true },
      filteredDescriptionFromSite: { type: Number, required: true },
      totalDescriptionFromSite: { type: Number, required: true },
      filteredMissingDescription: { type: Number, required: true },
      totalMissingDescription: { type: Number, required: true },
      filteredWithImage: { type: Number, required: true },
      totalWithImage: { type: Number, required: true },
      filteredImageFromSite: { type: Number, required: true },
      totalImageFromSite: { type: Number, required: true },
      filteredMissingImage: { type: Number, required: true },
      totalMissingImage: { type: Number, required: true },
      filteredNoIndex: { type: Number, required: true },
      totalNoIndex: { type: Number, required: true },
      searchActive: { type: Boolean, default: false }
    },
    computed: {
      denominator() {
        return this.searchActive ? this.filteredCount : this.totalCount;
      }
    },
    methods: {
      percent(count, total) {
        if (!total) return 0;
        return Math.round(count / total * 100);
      },
      getPercentClass(count, total) {
        const p = this.percent(count, total);
        if (p >= 80) return "k-meta-kit-stats-green";
        if (p >= 50) return "k-meta-kit-stats-amber";
        return "k-meta-kit-stats-red";
      }
    }
  };
  var _sfc_render$d = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { staticClass: "k-meta-kit-stats" }, [_c("div", { staticClass: "k-meta-kit-stats-card" }, [_c("div", { staticClass: "k-meta-kit-stats-label" }, [_vm._v("Total Pages")]), _c("div", { staticClass: "k-meta-kit-stats-row" }, [_c("span", { staticClass: "k-meta-kit-stats-value" }, [_vm._v(" " + _vm._s(_vm.filteredCount)), _vm.searchActive ? _c("span", { staticClass: "k-meta-kit-stats-sub" }, [_vm._v(" / " + _vm._s(_vm.totalCount))]) : _vm._e()])]), _vm._m(0)]), _c("div", { staticClass: "k-meta-kit-stats-card" }, [_c("div", { staticClass: "k-meta-kit-stats-label" }, [_vm._v("Meta Title")]), _c("div", { staticClass: "k-meta-kit-stats-row" }, [_c("span", { staticClass: "k-meta-kit-stats-value" }, [_vm._v(" " + _vm._s(_vm.filteredCustomTitle)), _vm.searchActive ? _c("span", { staticClass: "k-meta-kit-stats-sub" }, [_vm._v(" / " + _vm._s(_vm.totalCustomTitle))]) : _vm._e()]), _vm.denominator > 0 ? _c("span", { staticClass: "k-meta-kit-stats-pct", class: _vm.getPercentClass(_vm.filteredCustomTitle, _vm.denominator) }, [_vm._v(" " + _vm._s(_vm.percent(_vm.filteredCustomTitle, _vm.denominator)) + "% ")]) : _vm._e()]), _c("div", { staticClass: "k-meta-kit-stats-bar-track" }, [_c("div", { staticClass: "k-meta-kit-stats-bar-fill k-meta-kit-stats-green", style: { width: _vm.percent(_vm.filteredCustomTitle, _vm.denominator) + "%" } }), _vm.filteredPageFallback > 0 ? _c("div", { staticClass: "k-meta-kit-stats-bar-fill k-meta-kit-stats-amber", style: { width: _vm.percent(_vm.filteredPageFallback, _vm.denominator) + "%" } }) : _vm._e()]), _vm.filteredPageFallback > 0 ? _c("div", { staticClass: "k-meta-kit-stats-hint" }, [_c("span", { staticClass: "k-meta-kit-stats-amber" }, [_vm._v(_vm._s(_vm.filteredPageFallback) + " from page title")])]) : _vm._e()]), _c("div", { staticClass: "k-meta-kit-stats-card" }, [_c("div", { staticClass: "k-meta-kit-stats-label" }, [_vm._v("Meta Description")]), _c("div", { staticClass: "k-meta-kit-stats-row" }, [_c("span", { staticClass: "k-meta-kit-stats-value" }, [_vm._v(" " + _vm._s(_vm.filteredWithDescription)), _vm.searchActive ? _c("span", { staticClass: "k-meta-kit-stats-sub" }, [_vm._v(" / " + _vm._s(_vm.totalWithDescription))]) : _vm._e()]), _vm.denominator > 0 ? _c("span", { staticClass: "k-meta-kit-stats-pct", class: _vm.getPercentClass(_vm.filteredWithDescription, _vm.denominator) }, [_vm._v(" " + _vm._s(_vm.percent(_vm.filteredWithDescription, _vm.denominator)) + "% ")]) : _vm._e()]), _c("div", { staticClass: "k-meta-kit-stats-bar-track" }, [_c("div", { staticClass: "k-meta-kit-stats-bar-fill k-meta-kit-stats-green", style: { width: _vm.percent(_vm.filteredWithDescription, _vm.denominator) + "%" } }), _vm.filteredDescriptionFromSite > 0 ? _c("div", { staticClass: "k-meta-kit-stats-bar-fill k-meta-kit-stats-amber", style: { width: _vm.percent(_vm.filteredDescriptionFromSite, _vm.denominator) + "%" } }) : _vm._e(), _vm.filteredMissingDescription > 0 ? _c("div", { staticClass: "k-meta-kit-stats-bar-fill k-meta-kit-stats-red", style: { width: _vm.percent(_vm.filteredMissingDescription, _vm.denominator) + "%" } }) : _vm._e()]), _vm.filteredDescriptionFromSite > 0 || _vm.filteredMissingDescription > 0 ? _c("div", { staticClass: "k-meta-kit-stats-hint" }, [_vm.filteredDescriptionFromSite > 0 ? _c("span", { staticClass: "k-meta-kit-stats-amber" }, [_vm._v(_vm._s(_vm.filteredDescriptionFromSite) + " from site")]) : _vm._e(), _vm.filteredDescriptionFromSite > 0 && _vm.filteredMissingDescription > 0 ? _c("span", [_vm._v(" · ")]) : _vm._e(), _vm.filteredMissingDescription > 0 ? _c("span", { staticClass: "k-meta-kit-stats-red" }, [_vm._v(_vm._s(_vm.filteredMissingDescription) + " missing")]) : _vm._e()]) : _vm._e()]), _c("div", { staticClass: "k-meta-kit-stats-card" }, [_c("div", { staticClass: "k-meta-kit-stats-label" }, [_vm._v("OG Image")]), _c("div", { staticClass: "k-meta-kit-stats-row" }, [_c("span", { staticClass: "k-meta-kit-stats-value" }, [_vm._v(" " + _vm._s(_vm.filteredWithImage)), _vm.searchActive ? _c("span", { staticClass: "k-meta-kit-stats-sub" }, [_vm._v(" / " + _vm._s(_vm.totalWithImage))]) : _vm._e()]), _vm.denominator > 0 ? _c("span", { staticClass: "k-meta-kit-stats-pct", class: _vm.getPercentClass(_vm.filteredWithImage, _vm.denominator) }, [_vm._v(" " + _vm._s(_vm.percent(_vm.filteredWithImage, _vm.denominator)) + "% ")]) : _vm._e()]), _c("div", { staticClass: "k-meta-kit-stats-bar-track" }, [_c("div", { staticClass: "k-meta-kit-stats-bar-fill k-meta-kit-stats-green", style: { width: _vm.percent(_vm.filteredWithImage, _vm.denominator) + "%" } }), _vm.filteredImageFromSite > 0 ? _c("div", { staticClass: "k-meta-kit-stats-bar-fill k-meta-kit-stats-amber", style: { width: _vm.percent(_vm.filteredImageFromSite, _vm.denominator) + "%" } }) : _vm._e(), _vm.filteredMissingImage > 0 ? _c("div", { staticClass: "k-meta-kit-stats-bar-fill k-meta-kit-stats-red", style: { width: _vm.percent(_vm.filteredMissingImage, _vm.denominator) + "%" } }) : _vm._e()]), _vm.filteredImageFromSite > 0 || _vm.filteredMissingImage > 0 ? _c("div", { staticClass: "k-meta-kit-stats-hint" }, [_vm.filteredImageFromSite > 0 ? _c("span", { staticClass: "k-meta-kit-stats-amber" }, [_vm._v(_vm._s(_vm.filteredImageFromSite) + " from site")]) : _vm._e(), _vm.filteredImageFromSite > 0 && _vm.filteredMissingImage > 0 ? _c("span", [_vm._v(" · ")]) : _vm._e(), _vm.filteredMissingImage > 0 ? _c("span", { staticClass: "k-meta-kit-stats-red" }, [_vm._v(_vm._s(_vm.filteredMissingImage) + " missing")]) : _vm._e()]) : _vm._e()]), _c("div", { staticClass: "k-meta-kit-stats-card" }, [_c("div", { staticClass: "k-meta-kit-stats-label" }, [_vm._v("Noindex Pages")]), _c("div", { staticClass: "k-meta-kit-stats-row" }, [_c("span", { staticClass: "k-meta-kit-stats-value" }, [_vm._v(" " + _vm._s(_vm.filteredNoIndex)), _vm.searchActive ? _c("span", { staticClass: "k-meta-kit-stats-sub" }, [_vm._v(" / " + _vm._s(_vm.totalNoIndex))]) : _vm._e()]), _vm.denominator > 0 && _vm.filteredNoIndex > 0 ? _c("span", { staticClass: "k-meta-kit-stats-pct k-meta-kit-stats-amber" }, [_vm._v(" " + _vm._s(_vm.percent(_vm.filteredNoIndex, _vm.denominator)) + "% ")]) : _vm._e()]), _c("div", { staticClass: "k-meta-kit-stats-bar-track" }, [_c("div", { staticClass: "k-meta-kit-stats-bar-fill k-meta-kit-stats-amber", style: { width: _vm.percent(_vm.filteredNoIndex, _vm.denominator) + "%" } })])])]);
  };
  var _sfc_staticRenderFns$d = [function() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { staticClass: "k-meta-kit-stats-bar-track" }, [_c("div", { staticClass: "k-meta-kit-stats-bar-fill k-meta-kit-stats-neutral", staticStyle: { "width": "100%" } })]);
  }];
  _sfc_render$d._withStripped = true;
  var __component__$d = /* @__PURE__ */ normalizeComponent(
    _sfc_main$d,
    _sfc_render$d,
    _sfc_staticRenderFns$d
  );
  __component__$d.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/components/parts/table/MetaKitStats.vue";
  const MetaKitStats = __component__$d.exports;
  const _sfc_main$c = {
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
      this._outsideClickHandler = (e) => {
        if (!this.$el.contains(e.target)) {
          this.isDropdownOpen = false;
        }
      };
      document.addEventListener("click", this._outsideClickHandler);
    },
    beforeDestroy() {
      document.removeEventListener("click", this._outsideClickHandler);
    }
  };
  var _sfc_render$c = function render() {
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
    } } }), _c("span", [_vm._v("Complete Metadata")])]), _c("label", { staticClass: "k-meta-kit-filter-option" }, [_c("input", { attrs: { "type": "checkbox", "value": "noindex" }, domProps: { "checked": _vm.isFilterActive("noindex") }, on: { "change": function($event) {
      return _vm.toggleFilter("noindex");
    } } }), _c("span", [_vm._v("Noindex")])])]), _c("div", { staticClass: "k-meta-kit-filter-group" }, [_c("div", { staticClass: "k-meta-kit-filter-group-title" }, [_vm._v("Status")]), _c("label", { staticClass: "k-meta-kit-filter-option" }, [_c("input", { attrs: { "type": "checkbox", "value": "listed" }, domProps: { "checked": _vm.isFilterActive("listed") }, on: { "change": function($event) {
      return _vm.toggleFilter("listed");
    } } }), _c("span", [_vm._v("Listed")])]), _c("label", { staticClass: "k-meta-kit-filter-option" }, [_c("input", { attrs: { "type": "checkbox", "value": "unlisted" }, domProps: { "checked": _vm.isFilterActive("unlisted") }, on: { "change": function($event) {
      return _vm.toggleFilter("unlisted");
    } } }), _c("span", [_vm._v("Unlisted")])]), _c("label", { staticClass: "k-meta-kit-filter-option" }, [_c("input", { attrs: { "type": "checkbox", "value": "drafts" }, domProps: { "checked": _vm.isFilterActive("drafts") }, on: { "change": function($event) {
      return _vm.toggleFilter("drafts");
    } } }), _c("span", [_vm._v("Drafts")])])]), _vm.activeFilters.length > 0 ? _c("div", { staticClass: "k-meta-kit-filter-actions" }, [_c("button", { staticClass: "k-meta-kit-filter-clear", on: { "click": _vm.clearFilters } }, [_vm._v(" Clear all ")])]) : _vm._e()]) : _vm._e()])], 1);
  };
  var _sfc_staticRenderFns$c = [];
  _sfc_render$c._withStripped = true;
  var __component__$c = /* @__PURE__ */ normalizeComponent(
    _sfc_main$c,
    _sfc_render$c,
    _sfc_staticRenderFns$c
  );
  __component__$c.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/components/parts/table/MetaKitFilters.vue";
  const MetaKitFilters = __component__$c.exports;
  const _sfc_main$b = {
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
  var _sfc_render$b = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { staticClass: "k-meta-kit-actions" }, [_c("k-button-group", [_c("k-button", { attrs: { "icon": "edit", "disabled": _vm.selectedCount === 0 }, on: { "click": function($event) {
      return _vm.$emit("edit-selected");
    } } }, [_vm._v(" Edit Selected"), _vm.selectedCount > 0 ? _c("span", [_vm._v(" (" + _vm._s(_vm.selectedCount) + ")")]) : _vm._e()]), _vm.aiEnabled ? _c("k-button", { attrs: { "icon": "sparkling", "disabled": _vm.isGenerating || _vm.selectedCount === 0, "progress": _vm.isGenerating }, on: { "click": function($event) {
      return _vm.$emit("generate-missing");
    } } }, [_vm._v(" Generate Missing"), _vm.selectedCount > 0 ? _c("span", [_vm._v(" (" + _vm._s(_vm.selectedCount) + ")")]) : _vm._e()]) : _vm._e(), _c("k-button", { attrs: { "icon": "refresh" }, on: { "click": function($event) {
      return _vm.$emit("refresh");
    } } })], 1), _vm._t("filters")], 2);
  };
  var _sfc_staticRenderFns$b = [];
  _sfc_render$b._withStripped = true;
  var __component__$b = /* @__PURE__ */ normalizeComponent(
    _sfc_main$b,
    _sfc_render$b,
    _sfc_staticRenderFns$b
  );
  __component__$b.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/components/parts/table/MetaKitActions.vue";
  const MetaKitActions = __component__$b.exports;
  const _sfc_main$a = {
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
  var _sfc_render$a = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { staticClass: "k-meta-kit-tooltip-wrapper", on: { "mouseenter": _vm.show, "mouseleave": _vm.hide } }, [_vm._t("default"), _vm.isVisible && _vm.content ? _c("div", { staticClass: "k-meta-kit-tooltip-content", style: _vm.tooltipStyle, domProps: { "innerHTML": _vm._s(_vm.formattedContent) } }) : _vm._e()], 2);
  };
  var _sfc_staticRenderFns$a = [];
  _sfc_render$a._withStripped = true;
  var __component__$a = /* @__PURE__ */ normalizeComponent(
    _sfc_main$a,
    _sfc_render$a,
    _sfc_staticRenderFns$a
  );
  __component__$a.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/components/parts/common/Tooltip.vue";
  const Tooltip = __component__$a.exports;
  const DEFAULT_SEO_RANGES = {
    title: { optimal: { min: 20, max: 60 }, warning: { min: 15, max: 75 } },
    ogTitle: { optimal: { min: 20, max: 60 }, warning: { min: 15, max: 75 } },
    description: { optimal: { min: 140, max: 160 }, warning: { min: 126, max: 176 } },
    ogDescription: { optimal: { min: 150, max: 250 }, warning: { min: 135, max: 300 } }
  };
  const DEFAULT_SLUG_RANGES = {
    depth: { optimal: { min: 0, max: 2 }, warning: { min: 0, max: 3 } },
    words: { optimal: { min: 1, max: 8 }, warning: { min: 1, max: 10 } },
    length: { optimal: { min: 1, max: 60 }, warning: { min: 1, max: 70 } },
    wordLength: { optimal: { min: 1, max: 15 }, warning: { min: 1, max: 20 } }
  };
  const STATUS_CLASSES = {
    optimal: "k-meta-kit-status-optimal",
    warning: "k-meta-kit-status-warning",
    error: "k-meta-kit-status-error"
  };
  function getRangesForPageAndType(page, type, validationSettings = {}) {
    const defaults = (validationSettings == null ? void 0 : validationSettings.ranges) || {};
    const templates = (validationSettings == null ? void 0 : validationSettings.templates) || {};
    const templateName = page == null ? void 0 : page.template;
    const templateConfig = templateName && templates[templateName] ? templates[templateName] : {};
    const templateRanges = (templateConfig == null ? void 0 : templateConfig.ranges) || templateConfig || {};
    const merged = {
      ...DEFAULT_SEO_RANGES,
      ...defaults,
      ...templateRanges
    };
    return merged == null ? void 0 : merged[type];
  }
  function getSlugValidationConfig(page, validationSettings = {}) {
    const defaults = (validationSettings == null ? void 0 : validationSettings.slug) || {};
    const templates = (validationSettings == null ? void 0 : validationSettings.templates) || {};
    const templateName = page == null ? void 0 : page.template;
    const templateConfig = templateName && templates[templateName] ? templates[templateName] : {};
    const templateSlug = (templateConfig == null ? void 0 : templateConfig.slug) || {};
    const mergeRule = (key, fallbackOptimal, fallbackWarning) => {
      const raw = { ...defaults[key] || {}, ...templateSlug[key] || {} };
      if (Object.keys(raw).length === 0) {
        return {
          optimal: { ...fallbackOptimal },
          warning: { ...fallbackWarning }
        };
      }
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
      depth: mergeRule("depth", DEFAULT_SLUG_RANGES.depth.optimal, DEFAULT_SLUG_RANGES.depth.warning),
      words: mergeRule("words", DEFAULT_SLUG_RANGES.words.optimal, DEFAULT_SLUG_RANGES.words.warning),
      length: mergeRule("length", DEFAULT_SLUG_RANGES.length.optimal, DEFAULT_SLUG_RANGES.length.warning),
      wordLength: mergeRule("wordLength", DEFAULT_SLUG_RANGES.wordLength.optimal, DEFAULT_SLUG_RANGES.wordLength.warning)
    };
  }
  function isOutsideRange(value, range) {
    if (!range) return false;
    if (typeof range.min === "number" && value < range.min) return true;
    if (typeof range.max === "number" && value > range.max) return true;
    return false;
  }
  function getStatusClass(page, length, type, validationSettings = {}) {
    if (!length || length === 0) return "";
    const ranges = getRangesForPageAndType(page, type, validationSettings);
    if (!ranges) return "";
    if (length >= ranges.optimal.min && length <= ranges.optimal.max) {
      return STATUS_CLASSES.optimal;
    }
    if (length >= ranges.warning.min && length <= ranges.warning.max) {
      return STATUS_CLASSES.warning;
    }
    return STATUS_CLASSES.error;
  }
  function getStatusValue(statusClass) {
    if (!statusClass) return "";
    const match = statusClass.match(/k-meta-kit-status-(\w+)/);
    return match ? match[1] : "";
  }
  function getLengthValidationReason(page, type, length, validationSettings = {}) {
    const ranges = getRangesForPageAndType(page, type, validationSettings);
    if (!ranges || !length) return "";
    const statusClass = getStatusClass(page, length, type, validationSettings);
    if (!statusClass || statusClass === STATUS_CLASSES.optimal) return "";
    const optimal = `${ranges.optimal.min}-${ranges.optimal.max}`;
    const warning = `${ranges.warning.min}-${ranges.warning.max}`;
    if (statusClass === STATUS_CLASSES.warning) {
      return `Why warning:
Length ${length} is outside optimal (${optimal}), but within warning (${warning}).`;
    }
    return `Why error:
Length ${length} is outside warning (${warning}). Optimal is ${optimal}.`;
  }
  function getSlugValidationIssues({ numSlashes, wordCount, length, avgWordLength, cfg }) {
    var _a, _b, _c, _d, _e, _f;
    const checks = [
      { key: "Depth", value: numSlashes, optimal: (_a = cfg.depth) == null ? void 0 : _a.optimal, warning: (_b = cfg.depth) == null ? void 0 : _b.warning },
      { key: "Words", value: wordCount, optimal: (_c = cfg.words) == null ? void 0 : _c.optimal, warning: (_d = cfg.words) == null ? void 0 : _d.warning },
      { key: "Length", value: length, optimal: (_e = cfg.length) == null ? void 0 : _e.optimal, warning: (_f = cfg.length) == null ? void 0 : _f.warning }
    ];
    const issues = [];
    for (const check of checks) {
      const optimal = `${check.optimal.min}-${check.optimal.max}`;
      const warning = `${check.warning.min}-${check.warning.max}`;
      if (isOutsideRange(check.value, check.warning)) {
        issues.push({
          severity: "error",
          key: check.key,
          value: check.value,
          optimal,
          warning
        });
        continue;
      }
      if (isOutsideRange(check.value, check.optimal)) {
        issues.push({
          severity: "warning",
          key: check.key,
          value: check.value,
          optimal,
          warning
        });
      }
    }
    return issues;
  }
  function isTitleInherited(page) {
    var _a;
    if (page.id === "site") return false;
    return ((_a = page.metaTitleInheritance) == null ? void 0 : _a.inherited) || !page.hasMetaTitle;
  }
  function isDescriptionInherited(page, siteSettings) {
    var _a;
    return ((_a = page.metaDescriptionInheritance) == null ? void 0 : _a.inherited) === true || !page.hasMetaDescription && !!(siteSettings == null ? void 0 : siteSettings.siteMetaDescription);
  }
  function isOgTitleInherited(page) {
    var _a;
    if (page.id === "site") return false;
    return ((_a = page.ogTitleInheritance) == null ? void 0 : _a.inherited) || !page.hasOgTitle;
  }
  function isOgDescriptionInherited(page, siteSettings) {
    var _a;
    if ((_a = page.ogDescriptionInheritance) == null ? void 0 : _a.inherited) return true;
    if (page.hasOgDescription) return false;
    return page.hasMetaDescription || !!(siteSettings == null ? void 0 : siteSettings.siteMetaDescription);
  }
  function getEffectiveTitle(page, type = "meta") {
    const isOg = type === "og";
    const inheritance = isOg ? page.ogTitleInheritance : page.metaTitleInheritance;
    if ((inheritance == null ? void 0 : inheritance.inherited) && inheritance.inheritedValue) {
      return inheritance.inheritedValue;
    }
    if (isOg) {
      return page.hasOgTitle ? page.ogTitle : page.hasMetaTitle ? page.metaTitle : page.title;
    }
    return page.hasMetaTitle ? page.metaTitle : page.title;
  }
  function getEffectiveDescription(page, type = "meta", siteSettings = {}) {
    const isOg = type === "og";
    const inheritance = isOg ? page.ogDescriptionInheritance : page.metaDescriptionInheritance;
    if ((inheritance == null ? void 0 : inheritance.inherited) && inheritance.inheritedValue) {
      return inheritance.inheritedValue;
    }
    if (isOg) {
      if (page.hasOgDescription) return page.ogDescription;
      if (page.hasMetaDescription) return page.metaDescription;
      return (siteSettings == null ? void 0 : siteSettings.siteMetaDescription) || null;
    }
    return page.hasMetaDescription ? page.metaDescription : (siteSettings == null ? void 0 : siteSettings.siteMetaDescription) || null;
  }
  function getInheritanceSource(page, fieldType, siteSettings = {}) {
    const inheritanceMap = {
      metaTitle: page.metaTitleInheritance,
      metaDescription: page.metaDescriptionInheritance,
      ogTitle: page.ogTitleInheritance,
      ogDescription: page.ogDescriptionInheritance
    };
    const inheritance = inheritanceMap[fieldType];
    if ((inheritance == null ? void 0 : inheritance.inherited) && inheritance.inheritedFrom) {
      return inheritance.inheritedFrom;
    }
    switch (fieldType) {
      case "metaTitle":
        return !page.hasMetaTitle ? "page title" : false;
      case "metaDescription":
        if (!page.hasMetaDescription && (siteSettings == null ? void 0 : siteSettings.siteMetaDescription)) {
          return "site";
        }
        return false;
      case "ogTitle":
        if (!page.hasOgTitle) {
          return page.hasMetaTitle ? "meta title" : "page title";
        }
        return false;
      case "ogDescription":
        if (!page.hasOgDescription) {
          if (page.hasMetaDescription) return "meta description";
          if (siteSettings == null ? void 0 : siteSettings.siteMetaDescription) return "site";
        }
        return false;
      default:
        return false;
    }
  }
  function buildTooltipText(content, inheritanceSource, showContent = true, maxLength = 200) {
    let text = content || "";
    let prefix = "";
    if (text && text.length > maxLength) {
      text = text.substring(0, maxLength) + "...";
    }
    if (inheritanceSource) {
      prefix = "Inherited from " + inheritanceSource;
    }
    if (showContent) {
      text = (inheritanceSource ? ":\n\n" : "") + text;
      return prefix + text;
    }
    return prefix;
  }
  function shouldAppendSiteName$1(siteSettings = {}, type = "meta") {
    const appendSiteName = !!siteSettings.appendSiteName;
    const appendTargets = siteSettings.appendSiteNameTo;
    if (appendTargets === void 0 || appendTargets === null || appendTargets === "") {
      return appendSiteName;
    }
    return appendTargets.split(",").map((s) => s.trim()).includes(type);
  }
  function buildTitleWithSiteName(title, siteSettings = {}, type = "meta") {
    if (!title) {
      return title || "";
    }
    if (!shouldAppendSiteName$1(siteSettings, type)) {
      return title;
    }
    const siteName = siteSettings.siteMetaTitle || "";
    if (!siteName) {
      return title;
    }
    const separator = siteSettings.titleSeparator || "|";
    return `${title} ${separator} ${siteName}`;
  }
  function getFieldEffectiveTitle({
    value = "",
    metaTitle = "",
    pageTitle = "",
    type = "meta"
  } = {}) {
    if (type === "og") {
      return value || metaTitle || pageTitle || "";
    }
    return value || pageTitle || "";
  }
  function getFieldTitleDisplay({
    value = "",
    metaTitle = "",
    pageTitle = "",
    type = "meta",
    pageId = "",
    siteSettings = {}
  } = {}) {
    const effective = getFieldEffectiveTitle({ value, metaTitle, pageTitle, type });
    const isSitePage = pageId === "site";
    const withSite = buildTitleWithSiteName(effective, siteSettings, type);
    const showPreview = !isSitePage && !!effective && withSite !== effective;
    const charCount = isSitePage ? effective.length : (withSite || effective).length;
    return {
      effectiveTitle: effective,
      fullTitle: withSite || effective,
      showPreview,
      charCount
    };
  }
  function getTableTitleDisplay(page, siteSettings = {}, type = "meta") {
    const effective = getEffectiveTitle(page, type);
    const full = buildTitleWithSiteName(effective, siteSettings, type);
    return {
      effectiveTitle: effective,
      fullTitle: full || effective || "",
      charCount: (full || effective || "").length
    };
  }
  const _sfc_main$9 = {
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
      siteSettings: {
        type: Object,
        default: () => ({})
      },
      validationSettings: {
        type: Object,
        default: () => ({})
      }
    },
    data() {
      return {
        // Status mappings
        statusMappings: {
          listed: { label: "Listed", dotClass: "k-meta-kit-status-dot-listed" },
          unlisted: { label: "Unlisted", dotClass: "k-meta-kit-status-dot-unlisted" },
          draft: { label: "Draft", dotClass: "k-meta-kit-status-dot-draft" }
        }
      };
    },
    methods: {
      shouldAppendSiteName(type) {
        return shouldAppendSiteName$1(this.siteSettings, type);
      },
      isPageSelected(pageId) {
        return this.selectedPages.includes(pageId);
      },
      // Delegate to composables with proper context
      getStatusClass(page, length, type) {
        return getStatusClass(page, length, type, this.validationSettings);
      },
      getStatusValue(statusClass) {
        return getStatusValue(statusClass);
      },
      getLengthValidationReason(page, type, length) {
        return getLengthValidationReason(page, type, length, this.validationSettings);
      },
      // Inheritance helpers - delegate to composables
      isTitleInherited(page) {
        return isTitleInherited(page);
      },
      isDescriptionInherited(page) {
        return isDescriptionInherited(page, this.siteSettings);
      },
      isOgTitleInherited(page) {
        return isOgTitleInherited(page);
      },
      isOgDescriptionInherited(page) {
        return isOgDescriptionInherited(page, this.siteSettings);
      },
      // Title length calculator with site name appending
      getTitleLength(page, type = "meta") {
        return getTableTitleDisplay(page, this.siteSettings, type).charCount;
      },
      getFullTitlePreview(page, type) {
        const preview = getTableTitleDisplay(page, this.siteSettings, type).fullTitle;
        return preview || "—";
      },
      getTableTitleStatusClass(page) {
        return this.getStatusClass(page, this.getTitleLength(page, "meta"), "title");
      },
      getTableOgTitleStatusClass(page) {
        return this.getStatusClass(page, this.getTitleLength(page, "og"), "ogTitle");
      },
      // Tooltip methods
      tooltipText(content, inheritanceSource, showContent) {
        return buildTooltipText(content, inheritanceSource, showContent);
      },
      // Join tooltip parts with newlines only when both have content
      joinTooltipParts(base, reason) {
        if (!base && !reason) return "";
        if (!base) return reason;
        if (!reason) return base;
        return `${base}

${reason}`;
      },
      getTitleTooltip(page, showContent = true) {
        if (!page.title && !page.metaTitle) return "No title";
        if (page.id === "site") {
          return showContent ? page.hasMetaTitle ? page.metaTitle : page.title : "";
        }
        const source = getInheritanceSource(page, "metaTitle", this.siteSettings);
        const tooltip = buildTitleWithSiteName(
          getTableTitleDisplay(page, this.siteSettings, "meta").effectiveTitle,
          this.siteSettings,
          "meta"
        );
        const base = this.tooltipText(tooltip, source, showContent);
        const reason = this.getLengthValidationReason(page, "title", this.getTitleLength(page, "meta"));
        return this.joinTooltipParts(base, reason);
      },
      getDescriptionTooltip(page, showContent = true) {
        const text = getEffectiveDescription(page, "meta", this.siteSettings);
        if (!text) return "No meta description";
        const source = getInheritanceSource(page, "metaDescription", this.siteSettings);
        const base = this.tooltipText(text, source, showContent);
        const reason = this.getLengthValidationReason(page, "description", text.length);
        return this.joinTooltipParts(base, reason);
      },
      getOgTitleTooltip(page, showContent = true) {
        if (!page.title && !page.ogTitle && !page.metaTitle) return "No OG title";
        if (page.id === "site") {
          return showContent ? page.hasOgTitle ? page.ogTitle : page.title : "";
        }
        const source = getInheritanceSource(page, "ogTitle", this.siteSettings);
        const tooltip = buildTitleWithSiteName(
          getTableTitleDisplay(page, this.siteSettings, "og").effectiveTitle,
          this.siteSettings,
          "og"
        );
        const base = this.tooltipText(tooltip, source, showContent);
        const reason = this.getLengthValidationReason(page, "ogTitle", this.getTitleLength(page, "og"));
        return this.joinTooltipParts(base, reason);
      },
      getOgDescriptionTooltip(page, showContent = true) {
        const text = getEffectiveDescription(page, "og", this.siteSettings);
        if (!text) return "No OG description";
        const source = getInheritanceSource(page, "ogDescription", this.siteSettings);
        const base = this.tooltipText(text, source, showContent);
        const reason = this.getLengthValidationReason(page, "ogDescription", text.length);
        return this.joinTooltipParts(base, reason);
      },
      getInheritanceBadgeLabel(page, fieldType) {
        const source = getInheritanceSource(page, fieldType, this.siteSettings);
        switch (source) {
          case "site":
            return "s";
          case "page title":
            return "t";
          case "meta title":
            return "m";
          case "meta description":
            return "m";
          default:
            return source ? source.charAt(0).toLowerCase() : "";
        }
      },
      // Display methods
      getTitleDisplay(page) {
        const length = this.getTitleLength(page, "meta");
        return length ? this.isTitleInherited(page) ? `${length}` : length : "—";
      },
      getDescriptionDisplay(page) {
        const desc = getEffectiveDescription(page, "meta", this.siteSettings);
        return desc ? desc.length : "—";
      },
      getDescriptionStatusClass(page) {
        const desc = getEffectiveDescription(page, "meta", this.siteSettings);
        return this.getStatusClass(page, (desc == null ? void 0 : desc.length) || 0, "description");
      },
      getOgTitleDisplay(page) {
        const length = this.getTitleLength(page, "og");
        return length ? this.isOgTitleInherited(page) ? `${length}` : length : "—";
      },
      getOgDescriptionDisplay(page) {
        const desc = getEffectiveDescription(page, "og", this.siteSettings);
        return desc ? desc.length : "—";
      },
      getOgDescriptionStatusClass(page) {
        const desc = getEffectiveDescription(page, "og", this.siteSettings);
        return this.getStatusClass(page, (desc == null ? void 0 : desc.length) || 0, "ogDescription");
      },
      // Slug methods
      getSlug(page) {
        if (page.id === "site") return "";
        const parts = page.id.split("/");
        return parts[parts.length - 1];
      },
      getSlugWordCount(slug) {
        if (!slug) return 0;
        return slug.split(/[-_]/).filter((word) => word.length > 0).length;
      },
      getSlugStatusClass(page) {
        if (page.id === "site") return "k-meta-kit-status-optimal";
        const slug = this.getSlug(page);
        const wordCount = this.getSlugWordCount(slug);
        const length = slug.length;
        const numSlashes = page.id.split("/").length - 1;
        const cfg = getSlugValidationConfig(page, this.validationSettings);
        const avgWordLength = wordCount > 0 ? Math.ceil(length / wordCount) : length;
        const issues = getSlugValidationIssues({ numSlashes, wordCount, length, avgWordLength, cfg });
        if (issues.some((issue) => issue.severity === "error")) return "k-meta-kit-status-error";
        if (issues.some((issue) => issue.severity === "warning")) return "k-meta-kit-status-warning";
        return "k-meta-kit-status-optimal";
      },
      getSlugTooltip(page) {
        if (page.id === "site") return "Site root";
        const slug = this.getSlug(page);
        const wordCount = this.getSlugWordCount(slug);
        const length = slug.length;
        const numSlashes = page.id.split("/").length - 1;
        const cfg = getSlugValidationConfig(page, this.validationSettings);
        const avgWordLength = wordCount > 0 ? Math.ceil(length / wordCount) : length;
        const issues = getSlugValidationIssues({ numSlashes, wordCount, length, avgWordLength, cfg });
        const statusClass = this.getSlugStatusClass(page);
        const status = statusClass === "k-meta-kit-status-error" ? "error" : statusClass === "k-meta-kit-status-warning" ? "warning" : "ok";
        const reasons = issues.length ? `

Why ${status}:
` + issues.map(
          (i) => i.severity === "warning" ? `${i.key} ${i.value} is outside optimal (${i.optimal}), but within warning (${i.warning}).` : `${i.key} ${i.value} is outside warning (${i.warning}). Optimal is ${i.optimal}.`
        ).join("\n") : "";
        return `Slug: ${slug}

Depth: ${numSlashes}
Words: ${wordCount}
Length: ${length} characters

Ranges (optimal / warning):

Depth: ${cfg.depth.optimal.min}-${cfg.depth.optimal.max} / ${cfg.depth.warning.min}-${cfg.depth.warning.max}
Words: ${cfg.words.optimal.min}-${cfg.words.optimal.max} / ${cfg.words.warning.min}-${cfg.words.warning.max}
Length: ${cfg.length.optimal.min}-${cfg.length.optimal.max} / ${cfg.length.warning.min}-${cfg.length.warning.max}
Avg word length: ${cfg.wordLength.optimal.min}-${cfg.wordLength.optimal.max} / ${cfg.wordLength.warning.min}-${cfg.wordLength.warning.max}${reasons}`;
      },
      // Status display helpers
      getStatusLabel(page) {
        var _a;
        if (!page.status) return "—";
        return ((_a = this.statusMappings[page.status]) == null ? void 0 : _a.label) || page.status.charAt(0).toUpperCase() + page.status.slice(1);
      },
      getStatusDotClass(page) {
        var _a;
        return page.status ? ((_a = this.statusMappings[page.status]) == null ? void 0 : _a.dotClass) || "" : "";
      }
    }
  };
  var _sfc_render$9 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { staticClass: "k-meta-kit-table", class: { "k-meta-kit-table-preview": _vm.showPreview } }, [_c("table", [_c("thead", [_c("tr", [_c("th", { staticClass: "k-meta-kit-table-checkbox" }, [_c("input", { attrs: { "type": "checkbox" }, domProps: { "checked": _vm.isAllSelected }, on: { "change": function($event) {
      return _vm.$emit("toggle-select-all");
    } } })]), _c("th", [_vm._v("#")]), _c("th", [_vm._v("Page")]), !_vm.showPreview ? _c("th", [_vm._v("Slug")]) : _vm._e(), _vm.showPreview ? _c("th", [_vm._v(_vm._s(_vm.previewMode === "og" ? "OG Title" : "Meta Title"))]) : _vm._e(), _vm.showPreview ? _c("th", [_vm._v(_vm._s(_vm.previewMode === "og" ? "OG Desc." : "Meta Desc."))]) : _vm._e(), !_vm.showPreview ? _c("th", [_vm._v("Meta Title")]) : _vm._e(), !_vm.showPreview ? _c("th", [_vm._v("Meta Desc.")]) : _vm._e(), !_vm.showPreview ? _c("th", [_vm._v("OG Title")]) : _vm._e(), !_vm.showPreview ? _c("th", [_vm._v("OG Desc.")]) : _vm._e(), _c("th", [_vm._v("OG Image")]), !_vm.showPreview && _vm.previewMode === "meta" ? _c("th", [_vm._v("Robots")]) : _vm._e(), _c("th", [_vm._v("Actions")])])]), _c("tbody", _vm._l(_vm.pages, function(page, index) {
      return _c("tr", { key: page.id, class: { "k-meta-kit-row-selected": _vm.isPageSelected(page.id) } }, [_c("td", { staticClass: "k-meta-kit-table-checkbox" }, [_c("input", { attrs: { "type": "checkbox" }, domProps: { "checked": _vm.isPageSelected(page.id) }, on: { "change": function($event) {
        return _vm.$emit("toggle-page", page.id);
      } } })]), _c("td", [_vm._v(_vm._s(_vm.startIndex + index + 1))]), _c("td", [_c("div", { staticClass: "k-meta-kit-table-page" }, [_c("a", { staticClass: "k-link", attrs: { "href": page.panelUrl } }, [_vm._v(_vm._s(page.title))]), _c("div", { staticClass: "k-meta-kit-page-title-wrapper" }, [_c("span", { staticClass: "k-meta-kit-table-page-id" }, [_vm._v(_vm._s(page.template))]), _c("span", { class: ["k-meta-kit-status-dot", _vm.getStatusDotClass(page)], attrs: { "title": _vm.getStatusLabel(page) } })])])]), !_vm.showPreview ? _c("td", [_c("Tooltip", { attrs: { "content": _vm.getSlugTooltip(page) } }, [_c("span", { class: [_vm.getSlugStatusClass(page), "k-meta-kit-table-tooltip"] }, [_vm._v(" " + _vm._s(page.id) + " ")])])], 1) : _vm._e(), _vm.showPreview ? _c("td", [_vm.previewMode === "meta" ? [_c("Tooltip", { attrs: { "content": _vm.getTitleTooltip(page, false) } }, [_c("span", { class: [
        "k-meta-kit-table-preview-indicator",
        "k-meta-kit-table-tooltip"
      ], attrs: { "data-status": _vm.getStatusValue(_vm.getTableTitleStatusClass(page)) } }, [_c("span", { class: _vm.isTitleInherited(page) ? "k-meta-kit-inherited-preview" : "" }, [_vm._v(" " + _vm._s(_vm.getFullTitlePreview(page, "meta")) + " ")])])])] : [_c("Tooltip", { attrs: { "content": _vm.getOgTitleTooltip(page, false) } }, [_c("span", { class: [
        "k-meta-kit-table-preview-indicator",
        "k-meta-kit-table-tooltip",
        _vm.isOgTitleInherited(page) ? "k-meta-kit-inherited-preview" : ""
      ], attrs: { "data-status": _vm.getStatusValue(_vm.getTableOgTitleStatusClass(page)) } }, [page.hasOgTitle ? [_vm._v(" " + _vm._s(_vm.getFullTitlePreview(page, "og")) + " ")] : [_c("span", { staticClass: "k-meta-kit-table-preview-fallback" }, [_vm._v(" " + _vm._s(_vm.getFullTitlePreview(page, "og")) + " ")])]], 2)])]], 2) : _vm._e(), _vm.showPreview ? _c("td", [_vm.previewMode === "meta" ? [_c("Tooltip", { attrs: { "content": _vm.getDescriptionTooltip(page, false) } }, [_c("span", { staticClass: "k-meta-kit-table-preview-indicator k-meta-kit-table-tooltip", attrs: { "data-status": _vm.getStatusValue(_vm.getDescriptionStatusClass(page)) } }, [page.hasMetaDescription ? [_vm._v(" " + _vm._s(page.metaDescription) + " ")] : _vm.siteSettings.siteMetaDescription ? [_c("span", { staticClass: "k-meta-kit-table-preview-fallback" }, [_vm._v(" " + _vm._s(_vm.siteSettings.siteMetaDescription) + " ")])] : [_vm._v(" — ")]], 2)])] : [_c("Tooltip", { attrs: { "content": _vm.getOgDescriptionTooltip(page, false) } }, [_c("span", { staticClass: "k-meta-kit-table-preview-indicator k-meta-kit-table-tooltip", attrs: { "data-status": _vm.getStatusValue(_vm.getOgDescriptionStatusClass(page)) } }, [page.hasOgDescription ? [_vm._v(" " + _vm._s(page.ogDescription) + " ")] : page.hasMetaDescription ? [_c("span", { staticClass: "k-meta-kit-table-preview-fallback" }, [_vm._v(" " + _vm._s(page.metaDescription) + " ")])] : _vm.siteSettings.siteMetaDescription ? [_c("span", { staticClass: "k-meta-kit-table-preview-fallback" }, [_vm._v(" " + _vm._s(_vm.siteSettings.siteMetaDescription) + " ")])] : [_vm._v(" — ")]], 2)])]], 2) : _vm._e(), !_vm.showPreview ? _c("td", { staticClass: "k-meta-kit-table-center" }, [_c("Tooltip", { attrs: { "content": _vm.getTitleTooltip(page) } }, [_c("span", { staticClass: "k-meta-kit-table-value-group k-meta-kit-table-tooltip" }, [_c("span", { class: [
        _vm.getTableTitleStatusClass(page),
        _vm.getTableTitleStatusClass(page) === "k-meta-kit-status-optimal" ? "k-meta-kit-table-value-muted" : "",
        _vm.isTitleInherited(page) ? "k-meta-kit-inherited" : ""
      ] }, [_vm._v(" " + _vm._s(_vm.getTitleDisplay(page)) + " ")]), _vm.getInheritanceBadgeLabel(page, "metaTitle") ? _c("span", { staticClass: "k-meta-kit-table-source-marker" }, [_vm._v(" " + _vm._s(_vm.getInheritanceBadgeLabel(page, "metaTitle")) + " ")]) : _vm._e()])])], 1) : _vm._e(), !_vm.showPreview ? _c("td", { staticClass: "k-meta-kit-table-center" }, [_c("Tooltip", { attrs: { "content": _vm.getDescriptionTooltip(page) } }, [_c("span", { staticClass: "k-meta-kit-table-value-group k-meta-kit-table-tooltip" }, [_c("span", { class: [
        _vm.getDescriptionStatusClass(page),
        _vm.getDescriptionStatusClass(page) === "k-meta-kit-status-optimal" ? "k-meta-kit-table-value-muted" : "",
        _vm.isDescriptionInherited(page) ? "k-meta-kit-inherited" : ""
      ] }, [_vm._v(" " + _vm._s(_vm.getDescriptionDisplay(page)) + " ")]), _vm.getInheritanceBadgeLabel(page, "metaDescription") ? _c("span", { staticClass: "k-meta-kit-table-source-marker" }, [_vm._v(" " + _vm._s(_vm.getInheritanceBadgeLabel(page, "metaDescription")) + " ")]) : _vm._e()])])], 1) : _vm._e(), !_vm.showPreview ? _c("td", { staticClass: "k-meta-kit-table-center" }, [_c("Tooltip", { attrs: { "content": _vm.getOgTitleTooltip(page) } }, [_c("span", { staticClass: "k-meta-kit-table-value-group k-meta-kit-table-tooltip" }, [_c("span", { class: [
        _vm.getTableOgTitleStatusClass(page),
        _vm.getTableOgTitleStatusClass(page) === "k-meta-kit-status-optimal" ? "k-meta-kit-table-value-muted" : "",
        _vm.isOgTitleInherited(page) ? "k-meta-kit-inherited" : ""
      ] }, [_vm._v(" " + _vm._s(_vm.getOgTitleDisplay(page)) + " ")]), _vm.getInheritanceBadgeLabel(page, "ogTitle") ? _c("span", { staticClass: "k-meta-kit-table-source-marker" }, [_vm._v(" " + _vm._s(_vm.getInheritanceBadgeLabel(page, "ogTitle")) + " ")]) : _vm._e()])])], 1) : _vm._e(), !_vm.showPreview ? _c("td", { staticClass: "k-meta-kit-table-center" }, [_c("Tooltip", { attrs: { "content": _vm.getOgDescriptionTooltip(page) } }, [_c("span", { staticClass: "k-meta-kit-table-value-group k-meta-kit-table-tooltip" }, [_c("span", { class: [
        _vm.getOgDescriptionStatusClass(page),
        _vm.getOgDescriptionStatusClass(page) === "k-meta-kit-status-optimal" ? "k-meta-kit-table-value-muted" : "",
        _vm.isOgDescriptionInherited(page) ? "k-meta-kit-inherited" : ""
      ] }, [_vm._v(" " + _vm._s(_vm.getOgDescriptionDisplay(page)) + " ")]), _vm.getInheritanceBadgeLabel(page, "ogDescription") ? _c("span", { staticClass: "k-meta-kit-table-source-marker" }, [_vm._v(" " + _vm._s(_vm.getInheritanceBadgeLabel(page, "ogDescription")) + " ")]) : _vm._e()])])], 1) : _vm._e(), _c("td", { staticClass: "k-meta-kit-table-center" }, [page.hasOgImage ? [_c("Tooltip", { attrs: { "content": "Has OG image" } }, [_c("span", { staticClass: "k-meta-kit-og-image-indicator" }, [_c("k-icon", { staticClass: "k-meta-kit-icon-success", attrs: { "type": "check" } })], 1)])] : !page.hasOgImage && _vm.siteSettings.siteHasOgImage ? [_c("Tooltip", { attrs: { "content": "OG image inherited from site" } }, [_c("span", { staticClass: "k-meta-kit-og-image-indicator k-meta-kit-inherited" }, [_c("k-icon", { staticClass: "k-meta-kit-icon-success", attrs: { "type": "check" } })], 1)])] : [_c("Tooltip", { attrs: { "content": "No OG image" } }, [_c("span", [_vm._v("—")])])]], 2), !_vm.showPreview && _vm.previewMode === "meta" ? _c("td", { staticClass: "k-meta-kit-table-center" }, [page.robots && page.robots.includes("noindex") ? _c("span", { staticClass: "k-meta-kit-robots-noindex" }, [_vm._v("noindex")]) : _c("span", [_vm._v("—")])]) : _vm._e(), _c("td", { staticClass: "k-meta-kit-table-center" }, [_c("div", { staticClass: "k-meta-kit-table-actions" }, [_c("k-button", { attrs: { "icon": "edit", "size": "sm", "title": "Edit Metadata" }, on: { "click": function($event) {
        return _vm.$emit("edit-page", page.id);
      } } }), _vm.aiEnabled ? _c("k-button", { attrs: { "icon": "sparkling", "size": "sm", "title": "Generate with AI" }, on: { "click": function($event) {
        return _vm.$emit("generate-page", page.id);
      } } }) : _vm._e()], 1)])]);
    }), 0)])]);
  };
  var _sfc_staticRenderFns$9 = [];
  _sfc_render$9._withStripped = true;
  var __component__$9 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$9,
    _sfc_render$9,
    _sfc_staticRenderFns$9
  );
  __component__$9.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/components/parts/table/MetaKitTable.vue";
  const MetaKitTable = __component__$9.exports;
  const _sfc_main$8 = {
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
  var _sfc_render$8 = function render() {
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
  var _sfc_staticRenderFns$8 = [];
  _sfc_render$8._withStripped = true;
  var __component__$8 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$8,
    _sfc_render$8,
    _sfc_staticRenderFns$8
  );
  __component__$8.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/components/parts/edit/MetaKitBulkGenerateDialog.vue";
  const MetaKitBulkGenerateDialog = __component__$8.exports;
  const _sfc_main$7 = {
    props: {
      value: String,
      label: {
        type: String,
        default: ""
      },
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
        return shouldAppendSiteName$1(this.siteSettings, this.type);
      },
      showPreview() {
        return getFieldTitleDisplay({
          value: this.value,
          metaTitle: this.metaTitle,
          pageTitle: this.pageTitle,
          type: this.type,
          pageId: this.pageId,
          siteSettings: this.siteSettings
        }).showPreview;
      },
      fullTitle() {
        return getFieldTitleDisplay({
          value: this.value,
          metaTitle: this.metaTitle,
          pageTitle: this.pageTitle,
          type: this.type,
          pageId: this.pageId,
          siteSettings: this.siteSettings
        }).fullTitle;
      },
      charCount() {
        return getFieldTitleDisplay({
          value: this.value,
          metaTitle: this.metaTitle,
          pageTitle: this.pageTitle,
          type: this.type,
          pageId: this.pageId,
          siteSettings: this.siteSettings
        }).charCount;
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
  var _sfc_render$7 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { class: _vm.fieldClass }, [_vm.label || _vm.aiEnabled ? _c("div", { staticClass: "k-meta-kit-dialog-field-header" }, [_vm.label ? _c("label", { staticClass: "k-meta-kit-dialog-field-label" }, [_vm._v(_vm._s(_vm.label))]) : _vm._e(), _vm.aiEnabled ? _c("k-button", { attrs: { "icon": "sparkling", "size": _vm.buttonSize, "disabled": _vm.isGenerating, "title": _vm.buttonSize === "xs" ? "AI Generate" : void 0 }, on: { "click": function($event) {
      return _vm.$emit("generate");
    } } }, [_vm.buttonSize !== "xs" ? [_vm._v("AI Generate")] : _vm._e()], 2) : _vm._e()], 1) : _vm._e(), _c("k-input", { attrs: { "value": _vm.value, "placeholder": _vm.placeholder, "type": "text" }, on: { "input": function($event) {
      return _vm.$emit("input", $event);
    } } }), _vm.showPreview ? _c("div", { staticClass: "k-meta-kit-title-preview" }, [_vm._v(" " + _vm._s(_vm.fullTitle) + " ")]) : _vm._e(), _c("div", { staticClass: "k-meta-kit-dialog-field-meta" }, [_c("span", [_vm.value ? _c("span", { staticClass: "k-meta-kit-field-length", class: _vm.statusClass }, [_vm._v(" " + _vm._s(_vm.charCount) + " chars ")]) : _vm._e()])]), _vm.isGenerating ? _c("div", { staticClass: "k-meta-kit-dialog-generating" }, [_c("k-icon", { staticClass: "k-meta-kit-spinner", attrs: { "type": "loader" } }), _c("span", [_vm._v("Generating...")])], 1) : _vm._e()], 1);
  };
  var _sfc_staticRenderFns$7 = [];
  _sfc_render$7._withStripped = true;
  var __component__$7 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$7,
    _sfc_render$7,
    _sfc_staticRenderFns$7
  );
  __component__$7.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/components/parts/field/MetaKitTitleField.vue";
  const MetaKitTitleField = __component__$7.exports;
  const _sfc_main$6 = {
    props: {
      value: String,
      label: {
        type: String,
        default: ""
      },
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
  var _sfc_render$6 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { class: _vm.fieldClass }, [_vm.label || _vm.aiEnabled ? _c("div", { staticClass: "k-meta-kit-dialog-field-header" }, [_vm.label ? _c("label", { staticClass: "k-meta-kit-dialog-field-label" }, [_vm._v(_vm._s(_vm.label))]) : _vm._e(), _vm.aiEnabled ? _c("k-button", { attrs: { "icon": "sparkling", "size": _vm.buttonSize, "disabled": _vm.isGenerating, "title": _vm.buttonSize === "xs" ? "AI Generate" : void 0 }, on: { "click": function($event) {
      return _vm.$emit("generate");
    } } }, [_vm.buttonSize !== "xs" ? [_vm._v("AI Generate")] : _vm._e()], 2) : _vm._e()], 1) : _vm._e(), _c("k-input", { attrs: { "value": _vm.value, "placeholder": _vm.placeholder, "type": "textarea", "rows": _vm.rows, "buttons": _vm.buttons }, on: { "input": function($event) {
      return _vm.$emit("input", $event);
    } } }), _c("div", { staticClass: "k-meta-kit-dialog-field-meta" }, [_c("span", [_vm.value ? _c("span", { staticClass: "k-meta-kit-field-length", class: _vm.statusClass }, [_vm._v(" " + _vm._s(_vm.value.length) + " chars ")]) : _vm._e()])]), _vm.isGenerating ? _c("div", { staticClass: "k-meta-kit-dialog-generating" }, [_c("k-icon", { staticClass: "k-meta-kit-spinner", attrs: { "type": "loader" } }), _c("span", [_vm._v("Generating...")])], 1) : _vm._e()], 1);
  };
  var _sfc_staticRenderFns$6 = [];
  _sfc_render$6._withStripped = true;
  var __component__$6 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$6,
    _sfc_render$6,
    _sfc_staticRenderFns$6
  );
  __component__$6.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/components/parts/field/MetaKitDescriptionField.vue";
  const MetaKitDescriptionField = __component__$6.exports;
  async function applySingleFieldUpdate(api, { pageId, fieldName, value }) {
    const response = await api.post("meta-kit/apply-single-field", {
      pageId,
      fieldName,
      value
    });
    if ((response == null ? void 0 : response.status) !== "success") {
      throw new Error((response == null ? void 0 : response.message) || `Failed to update ${fieldName}`);
    }
    return response;
  }
  const _sfc_main$5 = {
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
      changedFields() {
        if (!this.page) return [];
        const fields = ["metaTitle", "metaDescription", "ogTitle", "ogDescription"];
        return fields.filter((f) => this.editedFields[f] !== (this.page[f] || ""));
      },
      changedFieldCount() {
        return this.changedFields.length;
      },
      hasChanges() {
        return this.changedFieldCount > 0;
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
        var _a, _b;
        if (!this.page || !this.hasChanges) return;
        const changedFields = this.changedFields.map((name) => ({
          name,
          value: this.editedFields[name]
        }));
        const results = await Promise.allSettled(
          changedFields.map(async (field) => {
            const response = await applySingleFieldUpdate(this.api, {
              pageId: this.page.id,
              fieldName: field.name,
              value: field.value
            });
            return response;
          })
        );
        const savedCount = results.filter((result) => result.status === "fulfilled").length;
        const failedResults = results.filter((result) => result.status === "rejected");
        if (failedResults.length > 0) {
          const firstError = (_b = (_a = failedResults[0]) == null ? void 0 : _a.reason) == null ? void 0 : _b.message;
          window.panel.notification.error(
            firstError || `Failed to update ${failedResults.length} field${failedResults.length > 1 ? "s" : ""}`
          );
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
  var _sfc_render$5 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("k-dialog", { ref: "dialog", attrs: { "size": "large", "cancelButton": "Close", "submitButton": "" }, on: { "submit": function($event) {
      $event.preventDefault();
      return _vm.save.apply(null, arguments);
    } } }, [_vm.page ? _c("k-headline", [_vm._v("Edit: " + _vm._s(_vm.page.title))]) : _vm._e(), _vm.isLoading ? _c("div", { staticClass: "k-meta-kit-loading" }, [_c("k-icon", { staticClass: "k-meta-kit-spinner", attrs: { "type": "loader" } }), _c("span", [_vm._v("Loading page...")])], 1) : _vm.page ? _c("div", { staticClass: "k-meta-kit-single-edit" }, [_c("div", { staticClass: "k-meta-kit-single-field" }, [_c("meta-kit-title-field", { attrs: { "label": "Meta Title", "value": _vm.editedFields.metaTitle, "page-id": _vm.page.id, "page-title": _vm.page.title, "site-settings": _vm.siteSettings, "ai-enabled": _vm.aiEnabled, "is-generating": _vm.generating.metaTitle, "placeholder": _vm.page.metaTitle || _vm.page.title || "No meta title set", "button-size": "sm", "field-class": "k-meta-kit-single-field-content" }, on: { "input": function($event) {
      _vm.editedFields.metaTitle = $event;
    }, "generate": function($event) {
      return _vm.generate("metaTitle");
    } } })], 1), _c("div", { staticClass: "k-meta-kit-single-field" }, [_c("meta-kit-description-field", { attrs: { "label": "Meta Description", "value": _vm.editedFields.metaDescription, "ai-enabled": _vm.aiEnabled, "is-generating": _vm.generating.metaDescription, "placeholder": _vm.page.metaDescription || _vm.siteSettings.siteMetaDescription || "No meta description set", "button-size": "sm", "rows": 3, "buttons": "false", "field-class": "k-meta-kit-single-field-content" }, on: { "input": function($event) {
      _vm.editedFields.metaDescription = $event;
    }, "generate": function($event) {
      return _vm.generate("metaDescription");
    } } })], 1), _c("div", { staticClass: "k-meta-kit-single-field" }, [_c("meta-kit-title-field", { attrs: { "label": "OG Title", "value": _vm.editedFields.ogTitle, "page-id": _vm.page.id, "page-title": _vm.page.title, "meta-title": _vm.page.metaTitle || _vm.editedFields.metaTitle, "site-settings": _vm.siteSettings, "ai-enabled": _vm.aiEnabled, "is-generating": _vm.generating.ogTitle, "placeholder": _vm.page.ogTitle || _vm.page.metaTitle || _vm.page.title || "No OG title", "type": "og", "button-size": "sm", "field-class": "k-meta-kit-single-field-content" }, on: { "input": function($event) {
      _vm.editedFields.ogTitle = $event;
    }, "generate": function($event) {
      return _vm.generate("ogTitle");
    } } })], 1), _c("div", { staticClass: "k-meta-kit-single-field" }, [_c("meta-kit-description-field", { attrs: { "label": "OG Description", "value": _vm.editedFields.ogDescription, "ai-enabled": _vm.aiEnabled, "is-generating": _vm.generating.ogDescription, "placeholder": _vm.page.ogDescription || _vm.page.metaDescription || _vm.siteSettings.siteMetaDescription || "No OG description", "type": "og", "button-size": "sm", "rows": 3, "buttons": "false", "field-class": "k-meta-kit-single-field-content" }, on: { "input": function($event) {
      _vm.editedFields.ogDescription = $event;
    }, "generate": function($event) {
      return _vm.generate("ogDescription");
    } } })], 1), _c("div", { staticClass: "k-meta-kit-single-field" }, [_c("label", { staticClass: "k-meta-kit-dialog-field-label" }, [_vm._v("OG Image")]), _c("div", { staticClass: "k-meta-kit-single-field-content" }, [_vm.page.ogImage ? _c("div", { staticClass: "k-meta-kit-og-image-current" }, [_c("img", { attrs: { "src": _vm.page.ogImage.url, "alt": _vm.page.ogImage.filename } }), _c("span", { staticClass: "k-meta-kit-og-image-filename" }, [_vm._v(_vm._s(_vm.page.ogImage.filename))])]) : _c("div", { staticClass: "k-meta-kit-og-image-empty" }, [_vm._v(" No OG image set ")])])]), _c("div", { staticClass: "k-meta-kit-single-actions" }, [_c("k-button", { attrs: { "icon": "open" }, on: { "click": _vm.editInPanel } }, [_vm._v("Edit in Panel")]), _vm.hasChanges ? _c("k-button", { attrs: { "icon": "check", "theme": "positive" }, on: { "click": _vm.save } }, [_vm._v(" Save " + _vm._s(_vm.changedFieldCount) + " " + _vm._s(_vm.changedFieldCount === 1 ? "Field" : "Fields") + " ")]) : _vm._e()], 1)]) : _vm._e()], 1);
  };
  var _sfc_staticRenderFns$5 = [];
  _sfc_render$5._withStripped = true;
  var __component__$5 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$5,
    _sfc_render$5,
    _sfc_staticRenderFns$5
  );
  __component__$5.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/components/parts/edit/MetaKitSinglePageDialog.vue";
  const MetaKitSinglePageDialog = __component__$5.exports;
  const METADATA_FILTERS = /* @__PURE__ */ new Set([
    "missing-title",
    "missing-description",
    "missing-og-title",
    "missing-og-description",
    "missing-og-image",
    "complete",
    "noindex"
  ]);
  const STATUS_FILTERS = /* @__PURE__ */ new Set(["listed", "unlisted", "drafts"]);
  function matchesMetadataFilter(page, filter) {
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
      case "noindex":
        return !!(page.robots && page.robots.includes("noindex"));
      default:
        return true;
    }
  }
  function matchesStatusFilter(page, filter) {
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
  }
  function filterPages(pages = [], activeFilters = [], searchQuery = "") {
    let filtered = pages;
    if (activeFilters.length > 0) {
      const metadataFilters = activeFilters.filter((f) => METADATA_FILTERS.has(f));
      const statusFilters = activeFilters.filter((f) => STATUS_FILTERS.has(f));
      filtered = filtered.filter((page) => {
        const matchesMetadata = metadataFilters.length === 0 || metadataFilters.every((filter) => matchesMetadataFilter(page, filter));
        const matchesStatus = statusFilters.length === 0 || statusFilters.some((filter) => matchesStatusFilter(page, filter));
        return matchesMetadata && matchesStatus;
      });
    }
    const query = searchQuery.trim().toLowerCase();
    if (!query) {
      return filtered;
    }
    return filtered.filter((page) => {
      return page.title.toLowerCase().includes(query) || page.id.toLowerCase().includes(query) || page.template.toLowerCase().includes(query) || page.metaDescription && page.metaDescription.toLowerCase().includes(query);
    });
  }
  function paginatePages(filteredPages = [], currentPage = 1, pageSize = 25) {
    if (pageSize >= 99999) {
      return filteredPages;
    }
    const start = (currentPage - 1) * pageSize;
    return filteredPages.slice(start, start + pageSize);
  }
  function getTotalPages(filteredPages = [], pageSize = 25) {
    if (pageSize >= 99999) {
      return 1;
    }
    return Math.ceil(filteredPages.length / pageSize);
  }
  function isAllCurrentPageSelected(paginatedPages = [], selectedPages = []) {
    if (paginatedPages.length === 0) {
      return false;
    }
    return paginatedPages.every((page) => selectedPages.includes(page.id));
  }
  function toggleSelectAllCurrentPage(paginatedPages = [], selectedPages = []) {
    const allSelected = isAllCurrentPageSelected(paginatedPages, selectedPages);
    const next = new Set(selectedPages);
    for (const page of paginatedPages) {
      if (allSelected) {
        next.delete(page.id);
      } else {
        next.add(page.id);
      }
    }
    return Array.from(next);
  }
  function hasPageFieldChanges(page, edited) {
    if (!page || !edited) {
      return false;
    }
    return edited.metaTitle !== (page.metaTitle || "") || edited.metaDescription !== (page.metaDescription || "") || edited.ogTitle !== (page.ogTitle || "") || edited.ogDescription !== (page.ogDescription || "");
  }
  function hasAnyBulkChanges(pages = [], editedFields = {}) {
    return pages.some((page) => hasPageFieldChanges(page, editedFields[page.id]));
  }
  const _sfc_main$4 = {
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
        return hasAnyBulkChanges(this.pages, this.editedFields);
      }
    },
    methods: {
      async open(pageIds) {
        this.isLoading = true;
        this.activeTab = "meta";
        this.$refs.dialog.open();
        try {
          const response = await this.api.get("meta-kit/pages-with-content", {
            pageIds: Array.isArray(pageIds) ? pageIds.join(",") : pageIds
          });
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
                await applySingleFieldUpdate(this.api, {
                  pageId: page.id,
                  fieldName: field.name,
                  value: field.value
                });
                totalSaved++;
              } catch (error) {
                window.panel.notification.error((error == null ? void 0 : error.message) || `Failed to update ${field.name} for ${page.title}`);
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
  var _sfc_render$4 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("k-dialog", { ref: "dialog", attrs: { "size": "huge", "cancelButton": "Close", "submitButton": "" }, on: { "submit": function($event) {
      $event.preventDefault();
      return _vm.saveAll.apply(null, arguments);
    } } }, [_c("k-headline", [_vm._v("Edit Selected Pages (" + _vm._s(_vm.pages.length) + ")")]), _vm.isLoading ? _c("div", { staticClass: "k-meta-kit-loading" }, [_c("k-icon", { staticClass: "k-meta-kit-spinner", attrs: { "type": "loader" } }), _c("span", [_vm._v("Loading pages...")])], 1) : _vm.pages.length > 0 ? _c("div", [_c("div", { staticClass: "k-meta-kit-tabs" }, [_c("k-button", { attrs: { "theme": _vm.activeTab === "meta" ? "positive" : "", "size": "sm" }, on: { "click": function($event) {
      _vm.activeTab = "meta";
    } } }, [_vm._v(" Meta Tags ")]), _c("k-button", { attrs: { "theme": _vm.activeTab === "og" ? "positive" : "", "size": "sm" }, on: { "click": function($event) {
      _vm.activeTab = "og";
    } } }, [_vm._v(" Social Media (OG) ")])], 1), _vm.activeTab === "meta" ? _c("div", { staticClass: "k-meta-kit-dialog-table-wrapper" }, _vm._l(_vm.pages, function(page) {
      return _c("div", { key: `meta-${page.id}`, staticClass: "k-meta-kit-dialog-table-page" }, [_c("div", { staticClass: "k-meta-kit-dialog-page-info" }, [_c("a", { staticClass: "k-link", attrs: { "href": page.panelUrl } }, [_vm._v(_vm._s(page.title))]), _c("a", { staticClass: "k-link k-meta-kit-page-id", attrs: { "href": page.panelUrl } }, [_vm._v(_vm._s(page.id))])]), _c("meta-kit-title-field", { attrs: { "label": "Meta Title", "value": _vm.editedFields[page.id].metaTitle, "page-id": page.id, "page-title": page.title, "site-settings": _vm.siteSettings, "ai-enabled": _vm.aiEnabled, "is-generating": _vm.generating[page.id].metaTitle, "placeholder": page.metaTitle || page.title || "No meta title", "type": "meta" }, on: { "input": function($event) {
        _vm.editedFields[page.id].metaTitle = $event;
      }, "generate": function($event) {
        return _vm.generate(page.id, "metaTitle");
      } } }), _c("meta-kit-description-field", { attrs: { "label": "Meta Description", "value": _vm.editedFields[page.id].metaDescription, "ai-enabled": _vm.aiEnabled, "is-generating": _vm.generating[page.id].metaDescription, "placeholder": page.metaDescription || _vm.siteSettings.siteMetaDescription || "No meta description", "rows": 3 }, on: { "input": function($event) {
        _vm.editedFields[page.id].metaDescription = $event;
      }, "generate": function($event) {
        return _vm.generate(page.id, "metaDescription");
      } } })], 1);
    }), 0) : _vm._e(), _vm.activeTab === "og" ? _c("div", { staticClass: "k-meta-kit-dialog-table-wrapper" }, _vm._l(_vm.pages, function(page) {
      return _c("div", { key: `og-${page.id}`, staticClass: "k-meta-kit-dialog-table-page" }, [_c("div", { staticClass: "k-meta-kit-dialog-page-info" }, [_c("a", { staticClass: "k-link", attrs: { "href": page.panelUrl } }, [_vm._v(_vm._s(page.title))]), _c("a", { staticClass: "k-link k-meta-kit-page-id", attrs: { "href": page.panelUrl } }, [_vm._v(_vm._s(page.id))])]), _c("meta-kit-title-field", { attrs: { "label": "OG Title", "value": _vm.editedFields[page.id].ogTitle, "page-id": page.id, "page-title": page.title, "meta-title": page.metaTitle || _vm.editedFields[page.id].metaTitle, "site-settings": _vm.siteSettings, "ai-enabled": _vm.aiEnabled, "is-generating": _vm.generating[page.id].ogTitle, "placeholder": page.ogTitle || page.metaTitle || page.title || "No OG title", "type": "og" }, on: { "input": function($event) {
        _vm.editedFields[page.id].ogTitle = $event;
      }, "generate": function($event) {
        return _vm.generate(page.id, "ogTitle");
      } } }), _c("meta-kit-description-field", { attrs: { "label": "OG Description", "value": _vm.editedFields[page.id].ogDescription, "ai-enabled": _vm.aiEnabled, "is-generating": _vm.generating[page.id].ogDescription, "placeholder": page.ogDescription || page.metaDescription || _vm.siteSettings.siteMetaDescription || "No OG description", "type": "og", "rows": 3 }, on: { "input": function($event) {
        _vm.editedFields[page.id].ogDescription = $event;
      }, "generate": function($event) {
        return _vm.generate(page.id, "ogDescription");
      } } })], 1);
    }), 0) : _vm._e(), _vm.hasAnyChanges ? _c("div", { staticClass: "k-meta-kit-single-actions" }, [_c("k-button", { attrs: { "icon": "check", "theme": "positive" }, on: { "click": _vm.saveAll } }, [_vm._v("Apply All Changes")])], 1) : _vm._e()]) : _c("div", { staticClass: "k-meta-kit-empty" }, [_c("k-icon", { attrs: { "type": "check" } }), _c("p", [_vm._v("No pages selected!")])], 1)], 1);
  };
  var _sfc_staticRenderFns$4 = [];
  _sfc_render$4._withStripped = true;
  var __component__$4 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$4,
    _sfc_render$4,
    _sfc_staticRenderFns$4
  );
  __component__$4.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/components/parts/edit/MetaKitBulkEditDialog.vue";
  const MetaKitBulkEditDialog = __component__$4.exports;
  const _sfc_main$3 = {
    components: {
      MetaKitTable,
      MetaKitBulkGenerateDialog,
      MetaKitSinglePageDialog,
      MetaKitBulkEditDialog,
      MetaKitStats,
      MetaKitFilters,
      MetaKitActions
    },
    props: {
      pages: Array,
      language: String,
      languages: Array,
      validationSettings: {
        type: Object,
        default: () => ({})
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
        pagesData: this.pages || [],
        siteSettingsData: this.siteSettings || {},
        validationSettingsData: this.validationSettings || {},
        // Single-page AI generate: null = bulk mode, string = single page ID
        singleGeneratePageId: null,
        // Pagination & Selection
        selectedPages: [],
        currentPage: 1,
        pageSize: 10,
        pageSizeOptions: [
          { value: 10, text: "10/page" },
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
        return filterPages(this.pagesData, this.activeFilters, this.searchQuery);
      },
      paginatedPages() {
        return paginatePages(this.filteredPages, this.currentPage, this.pageSize);
      },
      totalPages() {
        return getTotalPages(this.filteredPages, this.pageSize);
      },
      isAllCurrentPageSelected() {
        return isAllCurrentPageSelected(this.paginatedPages, this.selectedPages);
      },
      // Title: custom = explicitly set for this language; pageFallback = no meta title (page.title used)
      pagesWithCustomTitle() {
        return this.pagesData.filter((p) => {
          var _a;
          return p.hasMetaTitle && !((_a = p.metaTitleInheritance) == null ? void 0 : _a.inherited);
        }).length;
      },
      pagesWithPageFallback() {
        return this.pagesData.filter((p) => !p.hasMetaTitle).length;
      },
      filteredPagesWithCustomTitle() {
        return this.filteredPages.filter((p) => {
          var _a;
          return p.hasMetaTitle && !((_a = p.metaTitleInheritance) == null ? void 0 : _a.inherited);
        }).length;
      },
      filteredPagesWithPageFallback() {
        return this.filteredPages.filter((p) => !p.hasMetaTitle).length;
      },
      // Description tiers: custom (hasMetaDescription) | from site | truly missing
      pagesWithDescription() {
        return this.pagesData.filter((p) => p.hasMetaDescription).length;
      },
      pagesDescriptionFromSite() {
        var _a;
        if (!((_a = this.siteSettings) == null ? void 0 : _a.siteMetaDescription)) return 0;
        return this.pagesData.filter((p) => !p.hasMetaDescription).length;
      },
      pagesMissingDescription() {
        var _a;
        if ((_a = this.siteSettings) == null ? void 0 : _a.siteMetaDescription) return 0;
        return this.pagesData.filter((p) => !p.hasMetaDescription).length;
      },
      filteredPagesWithDescription() {
        return this.filteredPages.filter((p) => p.hasMetaDescription).length;
      },
      filteredPagesDescriptionFromSite() {
        var _a;
        if (!((_a = this.siteSettings) == null ? void 0 : _a.siteMetaDescription)) return 0;
        return this.filteredPages.filter((p) => !p.hasMetaDescription).length;
      },
      filteredPagesMissingDescription() {
        var _a;
        if ((_a = this.siteSettings) == null ? void 0 : _a.siteMetaDescription) return 0;
        return this.filteredPages.filter((p) => !p.hasMetaDescription).length;
      },
      // OG image tiers: page image | from site | truly missing
      pagesWithOgImage() {
        return this.pagesData.filter((p) => p.hasOgImage).length;
      },
      pagesOgImageFromSite() {
        var _a;
        if (!((_a = this.siteSettings) == null ? void 0 : _a.siteHasOgImage)) return 0;
        return this.pagesData.filter((p) => !p.hasOgImage).length;
      },
      pagesMissingOgImage() {
        var _a;
        if ((_a = this.siteSettings) == null ? void 0 : _a.siteHasOgImage) return 0;
        return this.pagesData.filter((p) => !p.hasOgImage).length;
      },
      filteredPagesWithOgImage() {
        return this.filteredPages.filter((p) => p.hasOgImage).length;
      },
      filteredPagesOgImageFromSite() {
        var _a;
        if (!((_a = this.siteSettings) == null ? void 0 : _a.siteHasOgImage)) return 0;
        return this.filteredPages.filter((p) => !p.hasOgImage).length;
      },
      filteredPagesMissingOgImage() {
        var _a;
        if ((_a = this.siteSettings) == null ? void 0 : _a.siteHasOgImage) return 0;
        return this.filteredPages.filter((p) => !p.hasOgImage).length;
      },
      pagesNoIndex() {
        return this.pagesData.filter((p) => p.robots && p.robots.includes("noindex")).length;
      },
      filteredPagesNoIndex() {
        return this.filteredPages.filter((p) => p.robots && p.robots.includes("noindex")).length;
      }
    },
    watch: {
      searchQuery() {
        this.currentPage = 1;
      },
      activeFilters() {
        this.currentPage = 1;
      }
    },
    methods: {
      async refreshPages() {
        this.isLoadingPages = true;
        try {
          const response = await this.$api.get("meta-kit/pages", {
            _ts: Date.now()
          });
          if (response.status === "success") {
            this.pagesData = response.data;
            if (response.siteSettings) {
              this.siteSettingsData = response.siteSettings;
            }
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
      // Open the field-selection dialog for a single page's AI generation
      openSinglePageGenerate(pageId) {
        this.singleGeneratePageId = pageId;
        this.$refs.bulkGenerateDialog.open();
      },
      // Open the field-selection dialog for bulk (selected pages) generation
      generateAllDescriptions() {
        this.singleGeneratePageId = null;
        this.$refs.bulkGenerateDialog.open();
      },
      async performBulkGeneration(options) {
        if (!options.title && !options.description && !options.ogTitle && !options.ogDescription) {
          window.panel.notification.error("Please select at least one field to generate");
          return;
        }
        this.isGeneratingAll = true;
        const pageIds = this.singleGeneratePageId ? [this.singleGeneratePageId] : this.selectedPages;
        this.singleGeneratePageId = null;
        try {
          const response = await this.$api.post("meta-kit/generate-all", {
            generateTitle: options.title,
            generateDescription: options.description,
            generateOgTitle: options.ogTitle,
            generateOgDescription: options.ogDescription,
            pageIds
          });
          if (response.status === "success") {
            const details = `Generated: ${response.generated || 0}, Skipped: ${response.skipped || 0}, Failed: ${response.failed || 0}`;
            window.panel.notification.success(`${response.message || "Generation completed!"} ${details}`);
            await this.refreshPages();
          } else {
            window.panel.notification.error(response.message || "Generation failed");
          }
        } catch (error) {
          let errorMessage = "Failed to generate metadata";
          if (error.message) {
            errorMessage += `: ${error.message}`;
          } else if (error.error) {
            errorMessage += `: ${error.error}`;
          }
          window.panel.notification.error(errorMessage);
          console.error("Generation error details:", error);
        } finally {
          this.isGeneratingAll = false;
          this.loadingProgress = "";
        }
      },
      async editSinglePageMetadata(pageId) {
        this.$refs.singlePageDialog.open(pageId);
      },
      goToLanguage(langCode) {
        if (langCode === this.language) return;
        const baseUrl = window.location.origin + window.location.pathname.split("?")[0];
        window.location.href = baseUrl + "?language=" + langCode;
      },
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
        this.selectedPages = toggleSelectAllCurrentPage(this.paginatedPages, this.selectedPages);
      },
      async showSelectedPagesDialog() {
        if (this.selectedPages.length === 0) return;
        this.$refs.allPagesDialog.open(this.selectedPages);
      }
    }
  };
  var _sfc_render$3 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("k-panel-inside", { staticClass: "k-meta-kit-view" }, [!_vm.hasValidLicense ? _c("div", { staticClass: "k-meta-kit-warning" }, [_c("k-box", { attrs: { "theme": "negative" } }, [_c("k-icon", { attrs: { "type": "alert" } }), _c("span", [_c("strong", [_vm._v("No valid license:")]), _vm._v(" AI generation and saving changes are disabled. Meta tags are limited to 20 characters. Please activate your license to use all features.")])], 1)], 1) : _vm._e(), _vm.languages && _vm.languages.length > 1 ? _c("div", { staticClass: "k-button-group k-language-selector k-meta-kit-language-bar", attrs: { "data-layout": "collapsed", "aria-label": "Translations" } }, _vm._l(_vm.languages, function(lang) {
      return _c("k-button", { key: lang.code, attrs: { "aria-current": lang.code === _vm.language ? "true" : void 0, "aria-label": lang.code, "title": lang.name, "theme": lang.code === _vm.language ? "dark" : "empty", "variant": "filled", "size": "sm", "responsive": "true" }, on: { "click": function($event) {
        return _vm.goToLanguage(lang.code);
      } } }, [_vm._v(" " + _vm._s(lang.code) + " ")]);
    }), 1) : _vm._e(), _c("meta-kit-stats", { attrs: { "filtered-count": _vm.filteredPages.length, "total-count": _vm.pagesData.length, "filtered-custom-title": _vm.filteredPagesWithCustomTitle, "total-custom-title": _vm.pagesWithCustomTitle, "filtered-page-fallback": _vm.filteredPagesWithPageFallback, "total-page-fallback": _vm.pagesWithPageFallback, "filtered-with-description": _vm.filteredPagesWithDescription, "total-with-description": _vm.pagesWithDescription, "filtered-description-from-site": _vm.filteredPagesDescriptionFromSite, "total-description-from-site": _vm.pagesDescriptionFromSite, "filtered-missing-description": _vm.filteredPagesMissingDescription, "total-missing-description": _vm.pagesMissingDescription, "filtered-with-image": _vm.filteredPagesWithOgImage, "total-with-image": _vm.pagesWithOgImage, "filtered-image-from-site": _vm.filteredPagesOgImageFromSite, "total-image-from-site": _vm.pagesOgImageFromSite, "filtered-missing-image": _vm.filteredPagesMissingOgImage, "total-missing-image": _vm.pagesMissingOgImage, "filtered-no-index": _vm.filteredPagesNoIndex, "total-no-index": _vm.pagesNoIndex, "search-active": !!(_vm.searchQuery || _vm.activeFilters.length) } }), _c("meta-kit-actions", { attrs: { "selected-count": _vm.selectedPages.length, "ai-enabled": _vm.aiEnabled, "is-generating": _vm.isGeneratingAll }, on: { "edit-selected": _vm.showSelectedPagesDialog, "generate-missing": _vm.generateAllDescriptions, "refresh": _vm.refreshPages }, scopedSlots: _vm._u([{ key: "filters", fn: function() {
      return [_c("meta-kit-filters", { attrs: { "show-preview": _vm.showPreviewInTable, "preview-mode": _vm.previewMode, "search-query": _vm.searchQuery, "active-filters": _vm.activeFilters }, on: { "update:showPreview": function($event) {
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
      } } })];
    }, proxy: true }]) }), _c("meta-kit-table", { attrs: { "pages": _vm.paginatedPages, "start-index": (_vm.currentPage - 1) * _vm.pageSize, "selected-pages": _vm.selectedPages, "is-all-selected": _vm.isAllCurrentPageSelected, "show-preview": _vm.showPreviewInTable, "preview-mode": _vm.previewMode, "ai-enabled": _vm.aiEnabled, "site-settings": _vm.siteSettingsData, "validation-settings": _vm.validationSettingsData }, on: { "toggle-select-all": _vm.toggleSelectAllCurrentPage, "toggle-page": _vm.togglePageSelection, "edit-page": _vm.editSinglePageMetadata, "generate-page": _vm.openSinglePageGenerate } }), _c("div", { staticClass: "k-meta-kit-pagination" }, [_c("div"), _c("div", { staticClass: "k-meta-kit-pagination-nav" }, [_vm.totalPages > 1 ? [_c("k-button", { attrs: { "icon": "angle-left", "disabled": _vm.currentPage === 1 }, on: { "click": _vm.previousPage } }), _c("span", { staticClass: "k-meta-kit-pagination-info" }, [_vm._v(" Page " + _vm._s(_vm.currentPage) + " of " + _vm._s(_vm.totalPages) + " "), _vm.searchQuery || _vm.activeFilters.length ? [_vm._v("(" + _vm._s(_vm.filteredPages.length) + " of " + _vm._s(_vm.pagesData.length) + ")")] : [_vm._v("(" + _vm._s(_vm.pagesData.length) + " total)")]], 2), _c("k-button", { attrs: { "icon": "angle-right", "disabled": _vm.currentPage === _vm.totalPages }, on: { "click": _vm.nextPage } })] : _vm._e()], 2), _c("div", { staticClass: "k-meta-kit-pagination-end" }, [_c("select", { staticClass: "k-meta-kit-pagesize-select", domProps: { "value": _vm.pageSize }, on: { "change": function($event) {
      return _vm.changePageSize($event.target.value);
    } } }, _vm._l(_vm.pageSizeOptions, function(option) {
      return _c("option", { key: option.value, domProps: { "value": option.value } }, [_vm._v(" " + _vm._s(option.text) + " ")]);
    }), 0)])]), _c("meta-kit-bulk-edit-dialog", { ref: "allPagesDialog", attrs: { "api": _vm.$api, "site-settings": _vm.siteSettingsData, "ai-enabled": _vm.aiEnabled }, on: { "saved": _vm.refreshPages } }), _c("meta-kit-single-page-dialog", { ref: "singlePageDialog", attrs: { "api": _vm.$api, "site-settings": _vm.siteSettingsData, "ai-enabled": _vm.aiEnabled }, on: { "saved": _vm.refreshPages } }), _c("meta-kit-bulk-generate-dialog", { ref: "bulkGenerateDialog", attrs: { "selected-count": _vm.singleGeneratePageId ? 1 : _vm.selectedPages.length }, on: { "generate": _vm.performBulkGeneration } }), _vm.isGeneratingAll || _vm.isLoadingPages ? _c("div", { staticClass: "k-meta-kit-loading-overlay" }, [_c("div", { staticClass: "k-meta-kit-loading-content" }, [_c("div", { staticClass: "k-meta-kit-loading-spinner" }, [_c("k-icon", { attrs: { "type": "loader" } })], 1), _c("div", { staticClass: "k-meta-kit-loading-text" }, [_vm.isGeneratingAll ? [_vm._v("Generating metadata with AI...")] : _vm.isLoadingPages ? [_vm._v("Refreshing pages...")] : _vm._e()], 2), _vm.loadingProgress ? _c("div", { staticClass: "k-meta-kit-loading-progress" }, [_vm._v(" " + _vm._s(_vm.loadingProgress) + " ")]) : _vm._e()])]) : _vm._e()], 1);
  };
  var _sfc_staticRenderFns$3 = [];
  _sfc_render$3._withStripped = true;
  var __component__$3 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$3,
    _sfc_render$3,
    _sfc_staticRenderFns$3
  );
  __component__$3.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/components/MetaKitView.vue";
  const MetaKitView = __component__$3.exports;
  function getLanguageCode() {
    var _a, _b, _c, _d, _e;
    if (typeof window === "undefined") return "en";
    return ((_c = (_b = (_a = window.panel) == null ? void 0 : _a.view) == null ? void 0 : _b.props) == null ? void 0 : _c.language) || ((_e = (_d = window.panel) == null ? void 0 : _d.language) == null ? void 0 : _e.code) || "en";
  }
  function getFieldName(fieldType, contentType) {
    var _a;
    const fieldMap = {
      meta: { title: "metaTitle", description: "metaDescription" },
      og: { title: "ogTitle", description: "ogDescription" }
    };
    return ((_a = fieldMap[fieldType]) == null ? void 0 : _a[contentType]) || fieldMap.meta[contentType];
  }
  async function generateAiContent(api, pageId, fieldName, language = null) {
    try {
      const response = await api.post("meta-kit/generate-field", {
        pageId,
        fieldName,
        language: language || getLanguageCode()
      });
      if (response.status !== "success" || !response.content) {
        return {
          success: false,
          error: response.message || "Failed to generate"
        };
      }
      return {
        success: true,
        content: response.content
      };
    } catch (e) {
      return {
        success: false,
        error: e.message || "AI generation failed"
      };
    }
  }
  const DEFAULT_RANGES = {
    title: { optimal: { min: 20, max: 60 }, warning: { min: 15, max: 75 } },
    description: { optimal: { min: 140, max: 160 }, warning: { min: 126, max: 176 } }
  };
  function getFieldValidation(length, ranges = {}, suffix = "") {
    if (!length) {
      return { status: "", theme: "", message: "" };
    }
    const optimal = ranges.optimal || DEFAULT_RANGES.title.optimal;
    const warning = ranges.warning || DEFAULT_RANGES.title.warning;
    const rangeText = `${optimal.min}-${optimal.max}`;
    const suffixText = suffix ? ` ${suffix}` : "";
    if (length >= optimal.min && length <= optimal.max) {
      return {
        status: "optimal",
        theme: "positive",
        message: `Optimal length. ${rangeText} characters recommended.${suffixText}`
      };
    }
    if (length >= warning.min && length < optimal.min) {
      return {
        status: "warning",
        theme: "notice",
        message: `Too short. ${rangeText} recommended.${suffixText}`
      };
    }
    if (length > optimal.max && length <= warning.max) {
      return {
        status: "warning",
        theme: "notice",
        message: `Slightly too long. ${rangeText} recommended.${suffixText}`
      };
    }
    if (length < warning.min) {
      return {
        status: "error",
        theme: "negative",
        message: `Much too short! ${rangeText} recommended.${suffixText}`
      };
    }
    return {
      status: "error",
      theme: "negative",
      message: `Too long! ${rangeText} recommended.${suffixText}`
    };
  }
  function getTitleValidation(length, settings = {}, includesSiteName = false) {
    const ranges = settings.ranges || DEFAULT_RANGES.title;
    const suffix = includesSiteName ? "(Includes length of site name)" : "";
    return getFieldValidation(length, ranges, suffix);
  }
  function getDescriptionValidation(length, settings = {}) {
    const ranges = settings.ranges || DEFAULT_RANGES.description;
    return getFieldValidation(length, ranges);
  }
  function shouldAppendSiteName(settings = {}, fieldType = "meta") {
    if (settings.pageId === "site") {
      return false;
    }
    if (!settings.appendSiteName) {
      return false;
    }
    const appendTo = settings.appendSiteNameTo;
    if (!appendTo) {
      return true;
    }
    return appendTo.split(",").map((s) => s.trim()).includes(fieldType);
  }
  function getCharCountWithSiteName(value, settings = {}, fieldType = "meta") {
    if (!value) return 0;
    const baseLength = value.length;
    if (shouldAppendSiteName(settings, fieldType) && settings.siteMetaTitle) {
      const separator = settings.titleSeparator || "|";
      return `${value} ${separator} ${settings.siteMetaTitle}`.length;
    }
    return baseLength;
  }
  function getTitlePreview(value, settings = {}, fieldType = "meta") {
    if (!value) return "";
    if (shouldAppendSiteName(settings, fieldType) && settings.siteMetaTitle) {
      const separator = settings.titleSeparator || "|";
      return `${value} ${separator} ${settings.siteMetaTitle}`;
    }
    return value;
  }
  const _sfc_main$2 = {
    inheritAttrs: false,
    props: {
      value: String,
      fieldType: {
        type: String,
        default: "meta"
      },
      pageId: String,
      disabled: Boolean,
      label: String,
      help: String,
      placeholder: String,
      validationSettings: {
        type: Object,
        default: () => ({})
      }
    },
    data() {
      return {
        isGenerating: false,
        aiError: null
      };
    },
    computed: {
      charCount() {
        return getCharCountWithSiteName(this.value, this.validationSettings, this.fieldType);
      },
      shouldAppendSiteName() {
        return shouldAppendSiteName(this.validationSettings, this.fieldType);
      },
      titlePreview() {
        return getTitlePreview(this.value, this.validationSettings, this.fieldType);
      },
      validation() {
        return getTitleValidation(this.charCount, this.validationSettings, this.shouldAppendSiteName);
      }
    },
    methods: {
      onInput(value) {
        this.$emit("input", value);
        this.$nextTick(() => {
          document.dispatchEvent(new CustomEvent("meta-kit-field-change", {
            detail: { field: getFieldName(this.fieldType, "title"), value }
          }));
        });
      },
      async generateWithAi() {
        this.isGenerating = true;
        this.aiError = null;
        const pageId = this.pageId || this.validationSettings.pageId;
        const fieldName = getFieldName(this.fieldType, "title");
        const result = await generateAiContent(this.$api, pageId, fieldName);
        if (result.success) {
          this.$emit("input", result.content);
        } else {
          this.aiError = result.error;
        }
        this.isGenerating = false;
      }
    }
  };
  var _sfc_render$2 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("k-field", _vm._b({ staticClass: "k-mk-title-field", scopedSlots: _vm._u([{ key: "options", fn: function() {
      return [_c("k-field-options")];
    }, proxy: true }, { key: "footer", fn: function() {
      return [_vm.titlePreview && _vm.shouldAppendSiteName ? _c("div", { staticClass: "k-mk-title-preview" }, [_vm._v(" Preview: " + _vm._s(_vm.titlePreview) + " ")]) : _vm._e(), _c("k-text", { attrs: { "theme": _vm.validation.theme } }, [_c("span", { staticClass: "k-mk-validation-row" }, [_c("span", [_vm.validation.message ? _c("span", { staticClass: "k-mk-validation-message k-mk-validation-left", attrs: { "theme": _vm.validation.theme } }, [_c("span", { class: "k-mk-validation-status-" + _vm.validation.status }, [_vm._v(_vm._s(_vm.charCount))]), _vm._v(" - " + _vm._s(_vm.validation.message) + " ")]) : _vm._e()]), _c("k-button", { staticClass: "k-mk-ai-button", attrs: { "size": "xs", "icon": "ai", "text": _vm.isGenerating ? "Generating…" : "Generate", "disabled": _vm.disabled || _vm.isGenerating }, on: { "click": _vm.generateWithAi } })], 1), _vm.aiError ? _c("span", { staticClass: "k-mk-ai-error" }, [_vm._v(_vm._s(_vm.aiError))]) : _vm._e()])];
    }, proxy: true }]) }, "k-field", _vm.$props, false), [_c("k-input", { attrs: { "value": _vm.value, "type": "text", "placeholder": _vm.placeholder, "disabled": _vm.disabled, "name": _vm.fieldType === "og" ? "ogTitle" : "metaTitle" }, on: { "input": _vm.onInput } })], 1);
  };
  var _sfc_staticRenderFns$2 = [];
  _sfc_render$2._withStripped = true;
  var __component__$2 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$2,
    _sfc_render$2,
    _sfc_staticRenderFns$2
  );
  __component__$2.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/fields/mk-title/index.vue";
  const MkTitle = __component__$2.exports;
  const _sfc_main$1 = {
    inheritAttrs: false,
    props: {
      value: String,
      fieldType: {
        type: String,
        default: "meta"
      },
      pageId: String,
      maxlength: Number,
      disabled: Boolean,
      label: String,
      help: String,
      placeholder: String,
      validationSettings: {
        type: Object,
        default: () => ({})
      }
    },
    data() {
      return {
        isGenerating: false,
        aiError: null
      };
    },
    computed: {
      charCount() {
        return this.value ? this.value.length : 0;
      },
      validation() {
        return getDescriptionValidation(this.charCount, this.validationSettings);
      }
    },
    methods: {
      onInput(value) {
        this.$emit("input", value);
        this.$nextTick(() => {
          document.dispatchEvent(new CustomEvent("meta-kit-field-change", {
            detail: { field: getFieldName(this.fieldType, "description"), value }
          }));
        });
      },
      async generateWithAi() {
        this.isGenerating = true;
        this.aiError = null;
        const pageId = this.pageId || this.validationSettings.pageId;
        const fieldName = getFieldName(this.fieldType, "description");
        const result = await generateAiContent(this.$api, pageId, fieldName);
        if (result.success) {
          this.$emit("input", result.content);
        } else {
          this.aiError = result.error;
        }
        this.isGenerating = false;
      }
    }
  };
  var _sfc_render$1 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("k-field", _vm._b({ staticClass: "k-mk-description-field", scopedSlots: _vm._u([{ key: "options", fn: function() {
      return [_c("k-field-options")];
    }, proxy: true }, { key: "footer", fn: function() {
      return [_c("k-text", [_c("span", { staticClass: "k-mk-validation-row" }, [_c("span", [_vm.validation.message ? _c("span", { staticClass: "k-mk-validation-message k-mk-validation-left", attrs: { "theme": _vm.validation.theme } }, [_c("span", { class: "k-mk-validation-status-" + _vm.validation.status }, [_vm._v(_vm._s(_vm.charCount))]), _vm._v(" - " + _vm._s(_vm.validation.message) + " ")]) : _vm._e()]), _c("k-button", { staticClass: "k-mk-ai-button", attrs: { "size": "xs", "icon": "ai", "text": _vm.isGenerating ? "Generating…" : "Generate", "disabled": _vm.disabled || _vm.isGenerating }, on: { "click": _vm.generateWithAi } })], 1), _vm.aiError ? _c("span", { staticClass: "k-mk-ai-error" }, [_vm._v(_vm._s(_vm.aiError))]) : _vm._e()])];
    }, proxy: true }]) }, "k-field", _vm.$props, false), [_c("k-input", { staticClass: "k-mk-description-textarea", attrs: { "type": "textarea", "value": _vm.value, "placeholder": _vm.placeholder, "disabled": _vm.disabled, "buttons": false, "maxlength": _vm.maxlength, "counter": false, "name": _vm.fieldType === "og" ? "ogDescription" : "metaDescription" }, on: { "input": _vm.onInput } })], 1);
  };
  var _sfc_staticRenderFns$1 = [];
  _sfc_render$1._withStripped = true;
  var __component__$1 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$1,
    _sfc_render$1,
    _sfc_staticRenderFns$1
  );
  __component__$1.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/fields/mk-description/index.vue";
  const MkDescription = __component__$1.exports;
  const _sfc_main = {
    inheritAttrs: false,
    props: {
      currentSlug: String,
      disabled: Boolean,
      label: String,
      help: String,
      validationSettings: {
        type: Object,
        default: () => ({})
      }
    },
    computed: {
      displaySlug() {
        return this.currentSlug || "—";
      },
      wordCount() {
        if (!this.currentSlug) return 0;
        const lastPart = this.getLastSlugPart();
        return lastPart.split(/[-_]/).filter((word) => word.length > 0).length;
      },
      slugLength() {
        if (!this.currentSlug) return 0;
        return this.getLastSlugPart().length;
      },
      depth() {
        if (!this.currentSlug) return 0;
        return (this.currentSlug.match(/\//g) || []).length;
      },
      templateInfo() {
        const template = this.validationSettings.template;
        return template ? ` (${template})` : "";
      },
      wordsGuideline() {
        const ranges = this.validationSettings.words;
        if (!ranges) return "1-8";
        const min = ranges.optimal.min;
        const max = ranges.optimal.max;
        return min === max ? `${min}` : `${min}-${max}`;
      },
      lengthGuideline() {
        const ranges = this.validationSettings.length;
        if (!ranges) return "1-60";
        const min = ranges.optimal.min;
        const max = ranges.optimal.max;
        return min === max ? `${min}` : `${min}-${max}`;
      },
      depthGuideline() {
        const ranges = this.validationSettings.depth;
        if (!ranges) return "2";
        return ranges.optimal.max;
      },
      validation() {
        if (!this.currentSlug) {
          return {
            status: "",
            theme: "info",
            message: "No slug available yet"
          };
        }
        const settings = this.validationSettings;
        const depth = this.depth;
        const words = this.wordCount;
        const length = this.slugLength;
        let messages = [];
        let overallStatus = "optimal";
        let theme = "positive";
        const depthStatus = this.getStatus(depth, settings.depth);
        if (depthStatus === "warning") {
          messages.push(`Consider reducing nesting depth (currently ${depth} levels)`);
          overallStatus = "warning";
          theme = "notice";
        } else if (depthStatus === "error") {
          messages.push(`Too deeply nested! Reduce to ${settings.depth.optimal.max} levels or less`);
          overallStatus = "error";
          theme = "negative";
        }
        const wordsStatus = this.getStatus(words, settings.words);
        if (wordsStatus === "warning") {
          if (words < settings.words.optimal.min) {
            messages.push(`Consider adding more descriptive words`);
          } else {
            messages.push(`Consider shortening the slug (${words} words)`);
          }
          if (overallStatus === "optimal") {
            overallStatus = "warning";
            theme = "notice";
          }
        } else if (wordsStatus === "error") {
          messages.push(`Slug too long! Reduce to ${settings.words.optimal.max} words or less`);
          overallStatus = "error";
          theme = "negative";
        }
        const lengthStatus = this.getStatus(length, settings.length);
        if (lengthStatus === "warning") {
          messages.push(`Slug is ${length} characters (${settings.length.optimal.max} recommended)`);
          if (overallStatus === "optimal") {
            overallStatus = "warning";
            theme = "notice";
          }
        } else if (lengthStatus === "error") {
          messages.push(`Slug too long! Reduce to ${settings.length.optimal.max} characters or less`);
          overallStatus = "error";
          theme = "negative";
        }
        return {
          status: overallStatus,
          theme,
          message: messages.length > 0 ? messages.join(". ") : "Slug fulfills the validation rules",
          depthStatus,
          wordsStatus,
          lengthStatus
        };
      }
    },
    methods: {
      getLastSlugPart() {
        if (!this.currentSlug) return "";
        const parts = this.currentSlug.split("/");
        return parts[parts.length - 1];
      },
      getStatus(value, ranges) {
        if (!ranges) return "optimal";
        const optimal = ranges.optimal || {};
        const warning = ranges.warning || {};
        if (value >= optimal.min && value <= optimal.max) {
          return "optimal";
        }
        if (value >= warning.min && value <= warning.max) {
          return "warning";
        }
        return "error";
      }
    }
  };
  var _sfc_render = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("k-field", _vm._b({ staticClass: "k-mk-slug-info-field", scopedSlots: _vm._u([{ key: "default", fn: function() {
      return [_c("k-box", { staticClass: "k-mk-slug-validation-box", attrs: { "theme": _vm.validation.theme } }, [_c("div", { staticClass: "k-mk-slug-stats" }, [_c("div", { staticClass: "k-mk-slug-stat k-mk-slug-stat-slug" }, [_c("span", { staticClass: "k-mk-slug-stat-label" }, [_vm._v("Slug:")]), _c("span", { class: "k-mk-slug-stat-value k-mk-validation-status-" + _vm.validation.status }, [_vm._v(_vm._s(_vm.displaySlug))])]), _c("div", { staticClass: "k-mk-slug-stat" }, [_c("span", { staticClass: "k-mk-slug-stat-label" }, [_vm._v("Words:")]), _c("span", { class: "k-mk-slug-stat-value k-mk-validation-status-" + _vm.validation.wordsStatus }, [_vm._v(_vm._s(_vm.wordCount))])]), _c("div", { staticClass: "k-mk-slug-stat" }, [_c("span", { staticClass: "k-mk-slug-stat-label" }, [_vm._v("Length:")]), _c("span", { class: "k-mk-slug-stat-value k-mk-validation-status-" + _vm.validation.lengthStatus }, [_vm._v(_vm._s(_vm.slugLength) + " chars")])]), _c("div", { staticClass: "k-mk-slug-stat" }, [_c("span", { staticClass: "k-mk-slug-stat-label" }, [_vm._v("Depth:")]), _c("span", { class: "k-mk-slug-stat-value k-mk-validation-status-" + _vm.validation.depthStatus }, [_vm._v(_vm._s(_vm.depth) + " levels")])])]), _vm.validation.message ? _c("div", { staticClass: "k-mk-slug-info" }, [_vm.validation.message ? _c("div", { staticClass: "k-mk-slug-message" }, [_vm._v(" " + _vm._s(_vm.validation.message) + " ")]) : _vm._e(), _c("details", { staticClass: "k-mk-slug-guidelines" }, [_c("summary", [_vm._v("SEO Guidelines" + _vm._s(_vm.templateInfo))]), _c("ul", [_c("li", [_c("strong", [_vm._v("Words:")]), _vm._v(" " + _vm._s(_vm.wordsGuideline))]), _c("li", [_c("strong", [_vm._v("Length:")]), _vm._v(" " + _vm._s(_vm.lengthGuideline) + " characters")]), _c("li", [_c("strong", [_vm._v("Nesting:")]), _vm._v(" ≤ " + _vm._s(_vm.depthGuideline) + " levels deep for best crawling")]), _c("li", [_c("strong", [_vm._v("Best practices:")]), _vm._v(" Use hyphens, lowercase, descriptive keywords")])])])]) : _vm._e()])];
    }, proxy: true }]) }, "k-field", _vm.$props, false));
  };
  var _sfc_staticRenderFns = [];
  _sfc_render._withStripped = true;
  var __component__ = /* @__PURE__ */ normalizeComponent(
    _sfc_main,
    _sfc_render,
    _sfc_staticRenderFns
  );
  __component__.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/meta-kit/js/fields/mk-slug-info/index.vue";
  const MkSlugInfo = __component__.exports;
  panel.plugin("tearoom1/meta-kit", {
    components: {
      "meta-kit-view": MetaKitView
    },
    sections: {
      "seo-preview": SeoPreview
    },
    fields: {
      "mk-title": MkTitle,
      "mk-description": MkDescription,
      "mk-slug-info": MkSlugInfo
    }
  });
})();
