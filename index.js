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
  const _sfc_main$1 = {
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
        separator: "|"
      };
    },
    async mounted() {
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
        if (event.detail && event.detail.seoData) {
          this.updatePreviewFromData(event.detail.seoData, event.detail.pageTitle);
        } else {
          this.loadFromFormState() || this.load();
        }
      },
      handleUpdate() {
        clearTimeout(this.updateTimeout);
        this.updateTimeout = setTimeout(() => {
          this.loadFromFormState() || this.load();
        }, 1e3);
      },
      updatePreviewFromData(seoData, pageTitle) {
        var _a, _b, _c, _d;
        const siteName = this.siteName || ((_c = (_b = (_a = this.$store) == null ? void 0 : _a.state) == null ? void 0 : _b.system) == null ? void 0 : _c.title) || "Site Name";
        const separator = this.separator || "|";
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
      },
      loadFromFormState() {
        try {
          let parent = this.$parent;
          while (parent && !parent.value) {
            parent = parent.$parent;
          }
          if (!parent || !parent.value) {
            return false;
          }
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
            this.extractSiteInfo();
          } else if (response.data && response.data.meta) {
            this.meta = response.data.meta;
            this.extractSiteInfo();
          } else {
            console.warn("No meta found in response");
          }
        } catch (error) {
          console.error("Error loading section:", error);
        }
      },
      extractSiteInfo() {
        if (this.meta && this.meta.title) {
          const separators = ["|", "-", "–", "—", "•", "/"];
          for (const sep of separators) {
            if (this.meta.title.includes(` ${sep} `)) {
              this.separator = sep;
              const parts = this.meta.title.split(` ${sep} `);
              if (parts.length > 1) {
                this.siteName = parts[parts.length - 1].trim();
              }
              break;
            }
          }
        }
        if (!this.siteName) {
          this.siteName = "Site Name";
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
  var _sfc_render$1 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("section", { staticClass: "k-seo-preview-section" }, [_c("header", { staticClass: "k-section-header" }, [_c("h2", { staticClass: "k-headline" }, [_vm._v(_vm._s(_vm.label || "SEO Preview"))])]), _vm.meta ? _c("div", { staticClass: "k-seo-previews" }, [_c("div", { staticClass: "k-seo-preview k-seo-preview--google" }, [_c("h3", { staticClass: "k-seo-preview__title" }, [_vm._v("Google Search Preview")]), _c("div", { staticClass: "k-seo-preview__content" }, [_c("div", { staticClass: "k-google-preview" }, [_c("cite", { staticClass: "k-google-preview__url" }, [_vm._v(_vm._s(_vm.displayUrl(_vm.meta.url)))]), _c("h3", { staticClass: "k-google-preview__title" }, [_vm._v(_vm._s(_vm.meta.title || "Page Title"))]), _c("p", { staticClass: "k-google-preview__description" }, [_vm._v(_vm._s(_vm.meta.description || "No description available"))])])])]), _c("div", { staticClass: "k-seo-preview k-seo-preview--twitter" }, [_c("h3", { staticClass: "k-seo-preview__title" }, [_vm._v("Share / Card Preview")]), _c("div", { staticClass: "k-seo-preview__content" }, [_c("div", { staticClass: "k-twitter-preview" }, [_vm.meta.ogImage ? _c("div", { staticClass: "k-twitter-preview__image", style: { backgroundImage: "url(" + _vm.meta.ogImage + ")" } }) : _vm._e(), _c("div", { staticClass: "k-twitter-preview__body" }, [_c("cite", { staticClass: "k-twitter-preview__url" }, [_vm._v(_vm._s(_vm.displayUrl(_vm.meta.url)))]), _c("h4", { staticClass: "k-twitter-preview__title" }, [_vm._v(_vm._s(_vm.meta.ogTitle || _vm.meta.title || "Page Title"))]), _c("p", { staticClass: "k-twitter-preview__description" }, [_vm._v(_vm._s(_vm.truncate(_vm.meta.ogDescription || _vm.meta.description, 140) || "No description"))])])])])])]) : _c("div", { staticClass: "k-seo-preview-loading" }, [_vm._v(" Loading preview... ")])]);
  };
  var _sfc_staticRenderFns$1 = [];
  _sfc_render$1._withStripped = true;
  var __component__$1 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$1,
    _sfc_render$1,
    _sfc_staticRenderFns$1
  );
  __component__$1.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/kirby-seo-ai/src/sections/seo-preview.vue";
  const SeoPreview = __component__$1.exports;
  const _sfc_main = {
    props: {
      pages: Array
    },
    data() {
      return {
        isLoadingPages: false,
        isGeneratingAll: false,
        isLoadingLegacy: false,
        isLoadingAllPages: false,
        pagesData: this.pages || [],
        allPagesData: [],
        legacyPages: [],
        legacyDetection: {
          show: false,
          found: 0
        },
        fieldChoices: {},
        // { pageId: { fieldName: 'legacy|current|manual|ai', manualValue: '...' } }
        generatingFields: {}
        // { pageId: { fieldName: true } }
      };
    },
    computed: {
      pagesWithDescription() {
        return this.pagesData.filter((p) => p.hasMetaDescription).length;
      },
      pagesWithOgImage() {
        return this.pagesData.filter((p) => p.hasOgImage).length;
      },
      pagesNoIndex() {
        return this.pagesData.filter((p) => p.noIndex).length;
      }
    },
    created() {
      this.checkLegacyOnLoad();
    },
    methods: {
      getStatusClass(hasField, length) {
        if (!hasField) return "";
        if (length < 50 || length > 160) {
          return "k-meta-kit-status-warning";
        }
        return "k-meta-kit-status-success";
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
            window.panel.notification.success(response.message);
            await this.refreshPages();
          } else {
            window.panel.notification.error(response.message);
          }
        } catch (error) {
          window.panel.notification.error("Failed to generate description");
        }
      },
      async generateAllDescriptions() {
        if (!confirm("This will generate descriptions for all pages without one. Continue?")) {
          return;
        }
        this.isGeneratingAll = true;
        try {
          const response = await this.$api.post("meta-kit/generate-all");
          if (response.status === "success") {
            window.panel.notification.success(response.message);
            await this.refreshPages();
          } else {
            window.panel.notification.error(response.message);
          }
        } catch (error) {
          window.panel.notification.error("Failed to generate descriptions");
        } finally {
          this.isGeneratingAll = false;
        }
      },
      async detectLegacyMetadata() {
        this.$refs.legacyDialog.open();
        this.isLoadingLegacy = true;
        try {
          const response = await this.$api.get("meta-kit/detect-legacy");
          if (response.status === "success") {
            this.legacyPages = response.pages || [];
          }
        } catch (error) {
          window.panel.notification.error("Failed to detect legacy metadata");
        } finally {
          this.isLoadingLegacy = false;
        }
      },
      async checkLegacyOnLoad() {
        const dismissed = sessionStorage.getItem("metaKitLegacyDismissed");
        if (dismissed) return;
        try {
          const response = await this.$api.get("meta-kit/detect-legacy");
          if (response.status === "success" && response.found > 0) {
            this.legacyDetection.show = true;
            this.legacyDetection.found = response.found;
          }
        } catch (error) {
        }
      },
      dismissLegacyWarning() {
        this.legacyDetection.show = false;
        sessionStorage.setItem("metaKitLegacyDismissed", "true");
      },
      showLegacyDialog() {
        this.detectLegacyMetadata();
      },
      async convertLegacyPage(pageId) {
        try {
          const response = await this.$api.post("meta-kit/convert-legacy", { pageId });
          if (response.status === "success" || response.status === "info") {
            window.panel.notification.success(response.message);
            await this.detectLegacyMetadata();
            await this.refreshPages();
          } else {
            window.panel.notification.error(response.message);
          }
        } catch (error) {
          window.panel.notification.error("Failed to convert legacy data");
        }
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
          const response = await this.$api.post("meta-kit/generate-description", { pageId });
          if (response.status === "success" && response.description) {
            this.setManualValue(pageId, fieldName, response.description);
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
        } else if (choice === "current" || choice === "keep") {
          if (page.fields) {
            value = page.current[fieldName];
          } else {
            window.panel.notification.success("Already using current value");
            return;
          }
        } else if (choice === "manual" || choice === "ai") {
          value = this.getManualValue(pageId, fieldName);
          if (!value) {
            window.panel.notification.error("Please enter a value");
            return;
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
            if (this.legacyPages.find((p) => p.id === pageId)) {
              await this.detectLegacyMetadata();
            }
            if (this.allPagesData.find((p) => p.id === pageId)) {
              await this.loadAllPages();
            }
          } else {
            window.panel.notification.error(response.message);
          }
        } catch (error) {
          window.panel.notification.error("Failed to update field");
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
      }
    }
  };
  var _sfc_render = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("k-panel-inside", { staticClass: "k-meta-kit-view" }, [_vm.legacyDetection.show && _vm.legacyDetection.found > 0 ? _c("div", { staticClass: "k-meta-kit-warning" }, [_c("k-box", { attrs: { "theme": "info" } }, [_c("k-icon", { attrs: { "type": "info" } }), _c("span", [_vm._v("Found " + _vm._s(_vm.legacyDetection.found) + " pages with legacy SEO metadata")]), _c("k-button", { attrs: { "icon": "download" }, on: { "click": _vm.showLegacyDialog } }, [_vm._v("View & Convert")]), _c("k-button", { attrs: { "icon": "cancel" }, on: { "click": _vm.dismissLegacyWarning } }, [_vm._v("Dismiss")])], 1)], 1) : _vm._e(), _c("div", { staticClass: "k-meta-kit-stats" }, [_c("div", { staticClass: "k-meta-kit-stats-card" }, [_c("h3", [_vm._v("Total Pages")]), _c("p", [_vm._v(_vm._s(_vm.pages.length))])]), _c("div", { staticClass: "k-meta-kit-stats-card" }, [_c("h3", [_vm._v("With Description")]), _c("p", [_vm._v(_vm._s(_vm.pagesWithDescription))])]), _c("div", { staticClass: "k-meta-kit-stats-card" }, [_c("h3", [_vm._v("With OG Image")]), _c("p", [_vm._v(_vm._s(_vm.pagesWithOgImage))])]), _c("div", { staticClass: "k-meta-kit-stats-card" }, [_c("h3", [_vm._v("NoIndex")]), _c("p", [_vm._v(_vm._s(_vm.pagesNoIndex))])])]), _c("div", { staticClass: "k-meta-kit-actions" }, [_c("k-button-group", [_c("k-button", { attrs: { "icon": "sparkling", "disabled": _vm.isGeneratingAll, "progress": _vm.isGeneratingAll }, on: { "click": _vm.generateAllDescriptions } }, [_vm._v(" Generate All Missing Descriptions ")]), _c("k-button", { attrs: { "icon": "refresh" }, on: { "click": _vm.refreshPages } }, [_vm._v("Refresh")]), _c("k-button", { attrs: { "icon": "edit" }, on: { "click": _vm.showAllPagesDialog } }, [_vm._v("Edit All Pages")]), _c("k-button", { attrs: { "icon": "download" }, on: { "click": _vm.detectLegacyMetadata } }, [_vm._v("Detect Legacy Data")])], 1)], 1), _c("div", { staticClass: "k-meta-kit-table" }, [_c("table", [_c("thead", [_c("tr", [_c("th", [_vm._v("#")]), _c("th", [_vm._v("Page")]), _c("th", [_vm._v("Template")]), _c("th", [_vm._v("Meta Title")]), _c("th", [_vm._v("Description")]), _c("th", [_vm._v("OG Image")]), _c("th", [_vm._v("NoIndex")]), _c("th", [_vm._v("Actions")])])]), _c("tbody", _vm._l(_vm.pagesData, function(page, index) {
      return _c("tr", { key: page.id }, [_c("td", [_vm._v(_vm._s(index + 1))]), _c("td", [_c("a", { staticClass: "k-link", attrs: { "href": page.panelUrl } }, [_vm._v(_vm._s(page.title))])]), _c("td", [_vm._v(_vm._s(page.template))]), _c("td", { staticClass: "k-meta-kit-table-center" }, [_c("span", { class: _vm.getStatusClass(page.hasMetaTitle, page.metaTitleLength) }, [_vm._v(" " + _vm._s(page.hasMetaTitle ? page.metaTitleLength : "—") + " ")])]), _c("td", { staticClass: "k-meta-kit-table-center" }, [_c("span", { class: _vm.getStatusClass(page.hasMetaDescription, page.metaDescriptionLength) }, [_vm._v(" " + _vm._s(page.hasMetaDescription ? page.metaDescriptionLength : "—") + " ")])]), _c("td", { staticClass: "k-meta-kit-table-center" }, [page.hasOgImage ? _c("k-icon", { staticClass: "k-meta-kit-icon-success", attrs: { "type": "check" } }) : _c("span", [_vm._v("—")])], 1), _c("td", { staticClass: "k-meta-kit-table-center" }, [page.noIndex ? _c("k-icon", { staticClass: "k-meta-kit-icon-warning", attrs: { "type": "check" } }) : _c("span", [_vm._v("—")])], 1), _c("td", { staticClass: "k-meta-kit-table-center" }, [_c("k-button", { attrs: { "icon": "magic", "size": "sm", "disabled": page.hasMetaDescription }, on: { "click": function($event) {
        return _vm.generateDescription(page.id);
      } } })], 1)]);
    }), 0)])]), _c("k-dialog", { ref: "legacyDialog", attrs: { "size": "huge", "cancelButton": "", "submitButton": "" } }, [_c("k-headline", [_vm._v("Legacy SEO Metadata")]), _vm.isLoadingLegacy ? _c("div", { staticClass: "k-meta-kit-loading" }, [_c("k-icon", { staticClass: "k-meta-kit-spinner", attrs: { "type": "loader" } }), _c("span", [_vm._v("Scanning for legacy metadata...")])], 1) : _vm.legacyPages.length > 0 ? _c("div", { staticClass: "k-meta-kit-legacy-list" }, [_c("p", [_vm._v("Found " + _vm._s(_vm.legacyPages.length) + " pages with legacy SEO fields:")]), _vm._l(_vm.legacyPages, function(page) {
      return _c("div", { key: page.id, staticClass: "k-meta-kit-legacy-item" }, [_c("div", { staticClass: "k-meta-kit-legacy-item-header" }, [_c("strong", [_vm._v(_vm._s(page.title))]), _c("k-button", { attrs: { "icon": "download", "size": "sm" }, on: { "click": function($event) {
        return _vm.convertLegacyPage(page.id);
      } } }, [_vm._v(" Apply Legacy ")])], 1), _c("div", { staticClass: "k-meta-kit-legacy-item-content" }, _vm._l(page.fields, function(value, key) {
        return _c("div", { key, staticClass: "k-meta-kit-legacy-field" }, [_c("span", { staticClass: "k-meta-kit-legacy-field-label" }, [_vm._v(_vm._s(_vm.formatFieldName(key)) + ":")]), _c("div", { staticClass: "k-meta-kit-legacy-field-values" }, [_c("div", { staticClass: "k-meta-kit-legacy-choices" }, [_c("k-button", { attrs: { "size": "xs", "theme": _vm.getFieldChoice(page.id, key) === "legacy" ? "positive" : "" }, on: { "click": function($event) {
          return _vm.setFieldChoice(page.id, key, "legacy");
        } } }, [_vm._v(" Legacy ")]), page.current && page.current[key] ? _c("k-button", { attrs: { "size": "xs", "theme": _vm.getFieldChoice(page.id, key) === "current" ? "positive" : "" }, on: { "click": function($event) {
          return _vm.setFieldChoice(page.id, key, "current");
        } } }, [_vm._v(" Current ")]) : _vm._e(), _c("k-button", { attrs: { "size": "xs", "theme": _vm.getFieldChoice(page.id, key) === "manual" ? "positive" : "" }, on: { "click": function($event) {
          return _vm.setFieldChoice(page.id, key, "manual");
        } } }, [_vm._v(" Manual Edit ")]), key !== "ogImage" ? _c("k-button", { attrs: { "size": "xs", "icon": "sparkling", "theme": _vm.getFieldChoice(page.id, key) === "ai" ? "positive" : "", "disabled": _vm.isGeneratingField(page.id, key) }, on: { "click": function($event) {
          return _vm.generateFieldAI(page.id, key);
        } } }, [_vm._v(" AI Generate ")]) : _vm._e()], 1), _c("div", { staticClass: "k-meta-kit-legacy-field-preview" }, [_vm.getFieldChoice(page.id, key) === "legacy" ? _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge" }, [_vm._v("Legacy Value")]), _c("span", { staticClass: "k-meta-kit-legacy-field-value" }, [_vm._v(_vm._s(_vm.formatFieldValue(value)))])]) : _vm.getFieldChoice(page.id, key) === "current" && page.current && page.current[key] ? _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge" }, [_vm._v("Current Value")]), _c("span", { staticClass: "k-meta-kit-legacy-field-value" }, [_vm._v(_vm._s(_vm.formatFieldValue(page.current[key])))])]) : _vm.getFieldChoice(page.id, key) === "manual" ? _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge" }, [_vm._v("Manual Entry")]), _c("k-input", { attrs: { "value": _vm.getManualValue(page.id, key), "placeholder": `Enter ${_vm.formatFieldName(key)}`, "type": "textarea" }, on: { "input": function($event) {
          return _vm.setManualValue(page.id, key, $event);
        } } })], 1) : _vm.getFieldChoice(page.id, key) === "ai" ? _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge k-meta-kit-legacy-badge-ai" }, [_vm._v("AI Generated")]), _vm.isGeneratingField(page.id, key) ? _c("span", { staticClass: "k-meta-kit-legacy-field-generating" }, [_c("k-icon", { staticClass: "k-meta-kit-spinner", attrs: { "type": "loader" } }), _vm._v(" Generating... ")], 1) : _vm.getManualValue(page.id, key) ? _c("span", { staticClass: "k-meta-kit-legacy-field-value" }, [_vm._v(" " + _vm._s(_vm.getManualValue(page.id, key)) + " ")]) : _c("span", { staticClass: "k-meta-kit-legacy-field-value-empty" }, [_vm._v(" Click AI Generate to create content ")])]) : _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge-hint" }, [_vm._v("Select an option above")])])]), _c("div", { staticClass: "k-meta-kit-legacy-field-reference" }, [_c("details", [_c("summary", [_vm._v("View original values")]), _c("div", { staticClass: "k-meta-kit-legacy-field-old" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge-small" }, [_vm._v("Legacy")]), _c("span", { staticClass: "k-meta-kit-legacy-field-value-small" }, [_vm._v(_vm._s(_vm.formatFieldValue(value)))])]), page.current && page.current[key] ? _c("div", { staticClass: "k-meta-kit-legacy-field-new" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge-small" }, [_vm._v("Current")]), _c("span", { staticClass: "k-meta-kit-legacy-field-value-small" }, [_vm._v(_vm._s(_vm.formatFieldValue(page.current[key])))])]) : _vm._e()])]), _vm.getFieldChoice(page.id, key) ? _c("k-button", { attrs: { "icon": "check", "size": "sm", "theme": "positive" }, on: { "click": function($event) {
          return _vm.applySingleField(page.id, key);
        } } }, [_vm._v(" Apply " + _vm._s(_vm.formatFieldName(key)) + " ")]) : _vm._e()], 1)]);
      }), 0)]);
    })], 2) : _c("div", { staticClass: "k-meta-kit-empty" }, [_c("k-icon", { attrs: { "type": "check" } }), _c("p", [_vm._v("No legacy SEO metadata found!")])], 1)], 1), _c("k-dialog", { ref: "allPagesDialog", attrs: { "size": "huge", "cancelButton": "", "submitButton": "" } }, [_c("k-headline", [_vm._v("Edit All Pages")]), _vm.isLoadingAllPages ? _c("div", { staticClass: "k-meta-kit-loading" }, [_c("k-icon", { staticClass: "k-meta-kit-spinner", attrs: { "type": "loader" } }), _c("span", [_vm._v("Loading pages...")])], 1) : _vm.allPagesData.length > 0 ? _c("div", { staticClass: "k-meta-kit-legacy-list" }, [_c("p", [_vm._v("Found " + _vm._s(_vm.allPagesData.length) + " pages:")]), _vm._l(_vm.allPagesData, function(page) {
      return _c("div", { key: page.id, staticClass: "k-meta-kit-legacy-item" }, [_c("div", { staticClass: "k-meta-kit-legacy-item-header" }, [_c("strong", [_vm._v(_vm._s(page.title))]), _c("k-button", { attrs: { "icon": "edit", "size": "sm" }, on: { "click": function($event) {
        return _vm.editSinglePage(page.id);
      } } }, [_vm._v(" Edit Page ")])], 1), _c("div", { staticClass: "k-meta-kit-legacy-item-content" }, [_c("div", { staticClass: "k-meta-kit-legacy-field" }, [_c("span", { staticClass: "k-meta-kit-legacy-field-label" }, [_vm._v("Meta Title:")]), _c("div", { staticClass: "k-meta-kit-legacy-field-values" }, [_c("div", { staticClass: "k-meta-kit-legacy-choices" }, [page.legacy && page.legacy.metaTitle ? _c("k-button", { attrs: { "size": "xs", "theme": _vm.getFieldChoice(page.id, "metaTitle") === "legacy" ? "positive" : "" }, on: { "click": function($event) {
        return _vm.setFieldChoice(page.id, "metaTitle", "legacy");
      } } }, [_vm._v(" Legacy ")]) : _vm._e(), _c("k-button", { attrs: { "size": "xs", "theme": _vm.getFieldChoice(page.id, "metaTitle") === "keep" ? "positive" : "" }, on: { "click": function($event) {
        return _vm.setFieldChoice(page.id, "metaTitle", "keep");
      } } }, [_vm._v(" Current ")]), _c("k-button", { attrs: { "size": "xs", "theme": _vm.getFieldChoice(page.id, "metaTitle") === "manual" ? "positive" : "" }, on: { "click": function($event) {
        return _vm.setFieldChoice(page.id, "metaTitle", "manual");
      } } }, [_vm._v(" Manual Edit ")]), _c("k-button", { attrs: { "size": "xs", "icon": "sparkling", "theme": _vm.getFieldChoice(page.id, "metaTitle") === "ai" ? "positive" : "", "disabled": _vm.isGeneratingField(page.id, "metaTitle") }, on: { "click": function($event) {
        return _vm.generateFieldAI(page.id, "metaTitle");
      } } }, [_vm._v(" AI Generate ")])], 1), _c("div", { staticClass: "k-meta-kit-legacy-field-preview" }, [_vm.getFieldChoice(page.id, "metaTitle") === "legacy" ? _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge" }, [_vm._v("Legacy Value")]), _c("span", { staticClass: "k-meta-kit-legacy-field-value" }, [_vm._v(_vm._s(page.legacy.metaTitle))])]) : _vm.getFieldChoice(page.id, "metaTitle") === "keep" || !_vm.getFieldChoice(page.id, "metaTitle") ? _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge" }, [_vm._v("Current Value")]), page.hasMetaTitle ? _c("span", { staticClass: "k-meta-kit-legacy-field-value" }, [_vm._v(" " + _vm._s(page.metaTitle || "—") + " "), _c("span", { staticClass: "k-meta-kit-field-length", class: _vm.getStatusClass(page.hasMetaTitle, page.metaTitleLength) }, [_vm._v(" (" + _vm._s(page.metaTitleLength) + " chars) ")])]) : _c("span", { staticClass: "k-meta-kit-legacy-field-value-empty" }, [_vm._v("No meta title set")])]) : _vm.getFieldChoice(page.id, "metaTitle") === "manual" ? _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge" }, [_vm._v("Manual Entry")]), _c("k-input", { attrs: { "value": _vm.getManualValue(page.id, "metaTitle"), "placeholder": "Enter Meta Title", "type": "text" }, on: { "input": function($event) {
        return _vm.setManualValue(page.id, "metaTitle", $event);
      } } })], 1) : _vm.getFieldChoice(page.id, "metaTitle") === "ai" ? _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge k-meta-kit-legacy-badge-ai" }, [_vm._v("AI Generated")]), _vm.isGeneratingField(page.id, "metaTitle") ? _c("span", { staticClass: "k-meta-kit-legacy-field-generating" }, [_c("k-icon", { staticClass: "k-meta-kit-spinner", attrs: { "type": "loader" } }), _vm._v(" Generating... ")], 1) : _vm.getManualValue(page.id, "metaTitle") ? _c("span", { staticClass: "k-meta-kit-legacy-field-value" }, [_vm._v(" " + _vm._s(_vm.getManualValue(page.id, "metaTitle")) + " ")]) : _c("span", { staticClass: "k-meta-kit-legacy-field-value-empty" }, [_vm._v(" Click AI Generate to create content ")])]) : _vm._e()]), _vm.getFieldChoice(page.id, "metaTitle") && _vm.getFieldChoice(page.id, "metaTitle") !== "keep" ? _c("k-button", { attrs: { "icon": "check", "size": "sm", "theme": "positive" }, on: { "click": function($event) {
        return _vm.applySingleField(page.id, "metaTitle");
      } } }, [_vm._v(" Apply Meta Title ")]) : _vm._e()], 1)]), _c("div", { staticClass: "k-meta-kit-legacy-field" }, [_c("span", { staticClass: "k-meta-kit-legacy-field-label" }, [_vm._v("Meta Description:")]), _c("div", { staticClass: "k-meta-kit-legacy-field-values" }, [_c("div", { staticClass: "k-meta-kit-legacy-choices" }, [page.legacy && page.legacy.metaDescription ? _c("k-button", { attrs: { "size": "xs", "theme": _vm.getFieldChoice(page.id, "metaDescription") === "legacy" ? "positive" : "" }, on: { "click": function($event) {
        return _vm.setFieldChoice(page.id, "metaDescription", "legacy");
      } } }, [_vm._v(" Legacy ")]) : _vm._e(), _c("k-button", { attrs: { "size": "xs", "theme": _vm.getFieldChoice(page.id, "metaDescription") === "keep" ? "positive" : "" }, on: { "click": function($event) {
        return _vm.setFieldChoice(page.id, "metaDescription", "keep");
      } } }, [_vm._v(" Current ")]), _c("k-button", { attrs: { "size": "xs", "theme": _vm.getFieldChoice(page.id, "metaDescription") === "manual" ? "positive" : "" }, on: { "click": function($event) {
        return _vm.setFieldChoice(page.id, "metaDescription", "manual");
      } } }, [_vm._v(" Manual Edit ")]), _c("k-button", { attrs: { "size": "xs", "icon": "sparkling", "theme": _vm.getFieldChoice(page.id, "metaDescription") === "ai" ? "positive" : "", "disabled": _vm.isGeneratingField(page.id, "metaDescription") }, on: { "click": function($event) {
        return _vm.generateFieldAI(page.id, "metaDescription");
      } } }, [_vm._v(" AI Generate ")])], 1), _c("div", { staticClass: "k-meta-kit-legacy-field-preview" }, [_vm.getFieldChoice(page.id, "metaDescription") === "legacy" ? _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge" }, [_vm._v("Legacy Value")]), _c("span", { staticClass: "k-meta-kit-legacy-field-value" }, [_vm._v(_vm._s(page.legacy.metaDescription))])]) : _vm.getFieldChoice(page.id, "metaDescription") === "keep" || !_vm.getFieldChoice(page.id, "metaDescription") ? _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge" }, [_vm._v("Current Value")]), page.hasMetaDescription ? _c("span", { staticClass: "k-meta-kit-legacy-field-value" }, [_vm._v(" " + _vm._s(page.metaDescription || "—") + " "), _c("span", { staticClass: "k-meta-kit-field-length", class: _vm.getStatusClass(page.hasMetaDescription, page.metaDescriptionLength) }, [_vm._v(" (" + _vm._s(page.metaDescriptionLength) + " chars) ")])]) : _c("span", { staticClass: "k-meta-kit-legacy-field-value-empty" }, [_vm._v("No meta description set")])]) : _vm.getFieldChoice(page.id, "metaDescription") === "manual" ? _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge" }, [_vm._v("Manual Entry")]), _c("k-input", { attrs: { "value": _vm.getManualValue(page.id, "metaDescription"), "placeholder": "Enter Meta Description", "type": "textarea" }, on: { "input": function($event) {
        return _vm.setManualValue(page.id, "metaDescription", $event);
      } } })], 1) : _vm.getFieldChoice(page.id, "metaDescription") === "ai" ? _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge k-meta-kit-legacy-badge-ai" }, [_vm._v("AI Generated")]), _vm.isGeneratingField(page.id, "metaDescription") ? _c("span", { staticClass: "k-meta-kit-legacy-field-generating" }, [_c("k-icon", { staticClass: "k-meta-kit-spinner", attrs: { "type": "loader" } }), _vm._v(" Generating... ")], 1) : _vm.getManualValue(page.id, "metaDescription") ? _c("span", { staticClass: "k-meta-kit-legacy-field-value" }, [_vm._v(" " + _vm._s(_vm.getManualValue(page.id, "metaDescription")) + " ")]) : _c("span", { staticClass: "k-meta-kit-legacy-field-value-empty" }, [_vm._v(" Click AI Generate to create content ")])]) : _vm._e()]), _vm.getFieldChoice(page.id, "metaDescription") && _vm.getFieldChoice(page.id, "metaDescription") !== "keep" ? _c("k-button", { attrs: { "icon": "check", "size": "sm", "theme": "positive" }, on: { "click": function($event) {
        return _vm.applySingleField(page.id, "metaDescription");
      } } }, [_vm._v(" Apply Meta Description ")]) : _vm._e()], 1)])])]);
    })], 2) : _c("div", { staticClass: "k-meta-kit-empty" }, [_c("k-icon", { attrs: { "type": "check" } }), _c("p", [_vm._v("No pages found!")])], 1)], 1)], 1);
  };
  var _sfc_staticRenderFns = [];
  _sfc_render._withStripped = true;
  var __component__ = /* @__PURE__ */ normalizeComponent(
    _sfc_main,
    _sfc_render,
    _sfc_staticRenderFns
  );
  __component__.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/kirby-seo-ai/js/components/MetaKitView.vue";
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
                  texts.push(extracted);
                }
              }
            }
            for (const [key, value] of Object.entries(values)) {
              if (key.includes("layout") || key.includes("blocks") || key.includes("builder")) {
                const extracted = this.extractTextFromBlocks(value);
                if (extracted) {
                  texts.push(extracted);
                }
              }
            }
            const result = texts.filter((t) => t && t.trim()).join(" ").replace(/\s+/g, " ").trim();
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
              const allText = this.extractAllText(parent.value);
              if (!allText || allText.trim() === "") {
                const availableFields = Object.keys(parent.value).join(", ");
                throw new Error(`No content available to generate description. Available fields: ${availableFields}`);
              }
              const language = ((_a = this.$language) == null ? void 0 : _a.code) || "en";
              const response = await this.$api.post("meta-kit/generate", {
                text: allText,
                language
              });
              if (response.status === "success" && response.description) {
                this.generatedText = response.description;
                if (parent && parent.value) {
                  if (!parent.value.seo) {
                    this.$set(parent.value, "seo", {});
                  }
                  this.$set(parent.value.seo, "metadescription", response.description);
                  this.$set(parent.value.seo, "ogdescription", response.description);
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
