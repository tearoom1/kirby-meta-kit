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
  const _sfc_main$2 = {
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
        updateTimeout: null,
        fieldCheckInterval: null,
        lastFieldValues: {}
      };
    },
    async mounted() {
      await this.load();
      document.addEventListener("seo-field-updated", this.handleSeoFieldUpdate, true);
      document.addEventListener("input", this.handleDOMInput, true);
      document.addEventListener("change", this.handleDOMInput, true);
      this.setupFieldObserver();
    },
    beforeDestroy() {
      document.removeEventListener("seo-field-updated", this.handleSeoFieldUpdate, true);
      document.removeEventListener("input", this.handleDOMInput, true);
      document.removeEventListener("change", this.handleDOMInput, true);
      if (this.fieldCheckInterval) {
        clearInterval(this.fieldCheckInterval);
      }
    },
    methods: {
      setupFieldObserver() {
        const checkFieldValues = () => {
          var _a, _b, _c, _d;
          const currentValues = {
            metatitle: ((_a = document.querySelector('[name="metatitle"]')) == null ? void 0 : _a.value) || "",
            metadescription: ((_b = document.querySelector('[name="metadescription"]')) == null ? void 0 : _b.value) || "",
            ogtitle: ((_c = document.querySelector('[name="ogtitle"]')) == null ? void 0 : _c.value) || "",
            ogdescription: ((_d = document.querySelector('[name="ogdescription"]')) == null ? void 0 : _d.value) || ""
          };
          const valuesChanged = Object.keys(currentValues).some(
            (key) => this.lastFieldValues[key] !== currentValues[key]
          );
          if (valuesChanged && Object.keys(this.lastFieldValues).length > 0) {
            this.updatePreviewFromDOM();
          }
          this.lastFieldValues = currentValues;
        };
        this.fieldCheckInterval = setInterval(checkFieldValues, 500);
        checkFieldValues();
      },
      handleDOMInput(event) {
        const target = event.target;
        const fieldName = target.name || target.getAttribute("name");
        const seoFields = ["metatitle", "metadescription", "ogtitle", "ogdescription"];
        if (fieldName && seoFields.includes(fieldName.toLowerCase())) {
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
        const seoData = {
          metatitle: getFieldValue("metatitle"),
          metadescription: getFieldValue("metadescription"),
          ogtitle: getFieldValue("ogtitle"),
          ogdescription: getFieldValue("ogdescription")
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
        const currentOgImage = ((_d = this.meta) == null ? void 0 : _d.ogImage) || null;
        const metaDesc = seoData.metadescription && seoData.metadescription.trim() ? seoData.metadescription : "No description available";
        const ogDesc = seoData.ogdescription && seoData.ogdescription.trim() ? seoData.ogdescription : metaDesc;
        this.meta = {
          url: window.location.origin,
          title: fullTitle,
          description: metaDesc,
          ogTitle: seoData.ogtitle || pageMetaTitle,
          ogDescription: ogDesc,
          ogImage: currentOgImage
          // Preserve existing image
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
  var _sfc_render$2 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("section", { staticClass: "k-seo-preview-section" }, [_c("header", { staticClass: "k-section-header" }, [_c("h2", { staticClass: "k-headline" }, [_vm._v(_vm._s(_vm.label || "SEO Preview"))])]), _vm.meta ? _c("div", { staticClass: "k-seo-previews" }, [_c("div", { staticClass: "k-seo-preview k-seo-preview--google" }, [_c("h3", { staticClass: "k-seo-preview__title" }, [_vm._v("Google Search Preview")]), _c("div", { staticClass: "k-seo-preview__content" }, [_c("div", { staticClass: "k-google-preview" }, [_c("cite", { staticClass: "k-google-preview__url" }, [_vm._v(_vm._s(_vm.displayUrl(_vm.meta.url)))]), _c("h3", { staticClass: "k-google-preview__title" }, [_vm._v(_vm._s(_vm.meta.title || "Page Title"))]), _c("p", { staticClass: "k-google-preview__description" }, [_vm._v(_vm._s(_vm.meta.description || "No description available"))])])])]), _c("div", { staticClass: "k-seo-preview k-seo-preview--twitter" }, [_c("h3", { staticClass: "k-seo-preview__title" }, [_vm._v("Share / Card Preview")]), _c("div", { staticClass: "k-seo-preview__content" }, [_c("div", { staticClass: "k-twitter-preview" }, [_vm.meta.ogImage ? _c("div", { staticClass: "k-twitter-preview__image", style: { backgroundImage: "url(" + _vm.meta.ogImage + ")" } }) : _vm._e(), _c("div", { staticClass: "k-twitter-preview__body" }, [_c("cite", { staticClass: "k-twitter-preview__url" }, [_vm._v(_vm._s(_vm.displayUrl(_vm.meta.url)))]), _c("h4", { staticClass: "k-twitter-preview__title" }, [_vm._v(_vm._s(_vm.meta.ogTitle || _vm.meta.title || "Page Title"))]), _c("p", { staticClass: "k-twitter-preview__description" }, [_vm._v(_vm._s(_vm.truncate(_vm.meta.ogDescription || _vm.meta.description, 140) || "No description"))])])])])])]) : _c("div", { staticClass: "k-seo-preview-loading" }, [_vm._v(" Loading preview... ")])]);
  };
  var _sfc_staticRenderFns$2 = [];
  _sfc_render$2._withStripped = true;
  var __component__$2 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$2,
    _sfc_render$2,
    _sfc_staticRenderFns$2
  );
  __component__$2.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/kirby-seo-ai/src/sections/seo-preview.vue";
  const SeoPreview = __component__$2.exports;
  const _sfc_main$1 = {
    props: {
      pages: Array,
      language: String,
      languages: Array
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
        searchQuery: ""
      };
    },
    computed: {
      filteredPages() {
        if (!this.searchQuery.trim()) {
          return this.pagesData;
        }
        const query = this.searchQuery.toLowerCase();
        return this.pagesData.filter((page) => {
          return page.title.toLowerCase().includes(query) || page.id.toLowerCase().includes(query) || page.template.toLowerCase().includes(query) || page.metaDescription && page.metaDescription.toLowerCase().includes(query);
        });
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
        return this.pagesData.filter((p) => p.noIndex).length;
      },
      filteredPagesWithDescription() {
        return this.filteredPages.filter((p) => p.hasMetaDescription).length;
      },
      filteredPagesWithOgImage() {
        return this.filteredPages.filter((p) => p.hasOgImage).length;
      },
      filteredPagesNoIndex() {
        return this.filteredPages.filter((p) => p.noIndex).length;
      }
    },
    watch: {
      searchQuery() {
        this.currentPage = 1;
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
      async reloadLegacyData(showLoading = true) {
        if (showLoading) {
          this.isLoadingLegacy = true;
        }
        try {
          const response = await this.$api.get("meta-kit/detect-legacy");
          if (response.status === "success") {
            this.$set(this, "legacyPages", response.pages || []);
            this.legacyPages.forEach((page) => {
              Object.keys(page.fields).forEach((fieldName) => {
                this.setFieldChoice(page.id, fieldName, "legacy");
              });
            });
          }
        } catch (error) {
          window.panel.notification.error("Failed to detect legacy metadata");
        } finally {
          if (showLoading) {
            this.isLoadingLegacy = false;
          }
        }
      },
      async detectLegacyMetadata() {
        this.$refs.legacyDialog.open();
        await this.reloadLegacyData();
      },
      async migrateAllToBlocks() {
        if (!confirm("This will migrate all legacy SEO fields (metatitle, metadescription, etc.) into the seo field for all pages. Continue?")) {
          return;
        }
        this.isMigratingAll = true;
        try {
          const response = await this.$api.post("meta-kit/convert-all-to-blocks");
          if (response.status === "success") {
            window.panel.notification.success(response.message || "Migration completed");
            await this.refreshPages();
            this.fieldChoices = {};
            await this.reloadLegacyData(false);
            this.$forceUpdate();
          } else {
            window.panel.notification.error(response.message || "Migration failed");
          }
        } catch (error) {
          window.panel.notification.error("Migration failed");
        } finally {
          this.isMigratingAll = false;
        }
      },
      async checkLegacyOnLoad() {
        return;
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
        const manualValue = this.getManualValue(pageId, fieldName);
        return manualValue || currentValue || "";
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
            if (this.fieldChoices[pageId] && this.fieldChoices[pageId][fieldName]) {
              this.$set(this.fieldChoices[pageId][fieldName], "manualValue", "");
            }
            this.setFieldChoice(pageId, fieldName, "keep");
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
            const pageInLegacy = this.legacyPages.find((p) => p.id === pageId);
            if (pageInLegacy) {
              if (!pageInLegacy.current) {
                this.$set(pageInLegacy, "current", {});
              }
              this.$set(pageInLegacy.current, fieldName, value);
            }
            this.$forceUpdate();
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
            this.allPagesData.forEach((page) => {
              this.setFieldChoice(page.id, "metaTitle", "keep");
              this.setFieldChoice(page.id, "metaDescription", "keep");
            });
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
            this.setFieldChoice(pageId, "metaTitle", "keep");
            this.setFieldChoice(pageId, "metaDescription", "keep");
          }
        } catch (error) {
          window.panel.notification.error("Failed to load page");
        } finally {
          this.isLoadingSinglePage = false;
        }
      },
      async applySingleFieldAndClose(pageId, fieldName) {
        await this.applySingleField(pageId, fieldName);
        if (this.currentEditPage && this.currentEditPage.id === pageId) {
          try {
            const response = await this.$api.get("meta-kit/single-page", { pageId });
            if (response.status === "success") {
              this.currentEditPage = response.data;
              this.setFieldChoice(pageId, "metaTitle", "keep");
              this.setFieldChoice(pageId, "metaDescription", "keep");
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
            this.allPagesData.forEach((page) => {
              this.setFieldChoice(page.id, "metaTitle", "keep");
              this.setFieldChoice(page.id, "metaDescription", "keep");
            });
          }
        } catch (error) {
          window.panel.notification.error("Failed to load selected pages");
        } finally {
          this.isLoadingAllPages = false;
        }
      }
    }
  };
  var _sfc_render$1 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("k-panel-inside", { staticClass: "k-meta-kit-view" }, [_vm.languages && _vm.languages.length > 1 ? _c("div", { staticClass: "k-meta-kit-language-bar" }, [_c("k-button-group", _vm._l(_vm.languages, function(lang) {
      return _c("k-button", { key: lang.code, attrs: { "theme": lang.code === _vm.language ? "positive" : "", "size": "xs" }, on: { "click": function($event) {
        return _vm.goToLanguage(lang.code);
      } } }, [_vm._v(" " + _vm._s(lang.code.toUpperCase()) + " ")]);
    }), 1)], 1) : _vm._e(), _vm.legacyDetection.show && _vm.legacyDetection.found > 0 ? _c("div", { staticClass: "k-meta-kit-warning" }, [_c("k-box", { attrs: { "theme": "info" } }, [_c("k-icon", { attrs: { "type": "info" } }), _c("span", [_vm._v("Found " + _vm._s(_vm.legacyDetection.found) + " pages with legacy SEO metadata")]), _c("k-button", { attrs: { "icon": "download" }, on: { "click": _vm.showLegacyDialog } }, [_vm._v("View & Convert")]), _c("k-button", { attrs: { "icon": "cancel" }, on: { "click": _vm.dismissLegacyWarning } }, [_vm._v("Dismiss")])], 1)], 1) : _vm._e(), _c("div", { staticClass: "k-meta-kit-stats" }, [_c("div", { staticClass: "k-meta-kit-stats-card" }, [_c("h3", [_vm._v("Total Pages")]), _c("p", [_vm._v(_vm._s(_vm.filteredPages.length)), _vm.searchQuery ? _c("span", { staticClass: "k-meta-kit-stats-total" }, [_vm._v(" / " + _vm._s(_vm.pagesData.length))]) : _vm._e()])]), _c("div", { staticClass: "k-meta-kit-stats-card" }, [_c("h3", [_vm._v("With Description")]), _c("p", [_vm._v(_vm._s(_vm.filteredPagesWithDescription)), _vm.searchQuery ? _c("span", { staticClass: "k-meta-kit-stats-total" }, [_vm._v(" / " + _vm._s(_vm.pagesWithDescription))]) : _vm._e()])]), _c("div", { staticClass: "k-meta-kit-stats-card" }, [_c("h3", [_vm._v("With OG Image")]), _c("p", [_vm._v(_vm._s(_vm.filteredPagesWithOgImage)), _vm.searchQuery ? _c("span", { staticClass: "k-meta-kit-stats-total" }, [_vm._v(" / " + _vm._s(_vm.pagesWithOgImage))]) : _vm._e()])]), _c("div", { staticClass: "k-meta-kit-stats-card" }, [_c("h3", [_vm._v("NoIndex")]), _c("p", [_vm._v(_vm._s(_vm.filteredPagesNoIndex)), _vm.searchQuery ? _c("span", { staticClass: "k-meta-kit-stats-total" }, [_vm._v(" / " + _vm._s(_vm.pagesNoIndex))]) : _vm._e()])])]), _c("div", { staticClass: "k-meta-kit-actions" }, [_c("k-button-group", [_c("k-button", { attrs: { "icon": "edit", "disabled": _vm.selectedPages.length === 0 }, on: { "click": _vm.showSelectedPagesDialog } }, [_vm._v(" Edit Selected (" + _vm._s(_vm.selectedPages.length) + ") ")]), _c("k-button", { attrs: { "icon": "sparkling", "disabled": _vm.isGeneratingAll || _vm.selectedPages.length === 0, "progress": _vm.isGeneratingAll }, on: { "click": _vm.generateAllDescriptions } }, [_vm._v(" Generate Missing (" + _vm._s(_vm.selectedPages.length || _vm.filteredPages.length) + ") ")]), _c("k-button", { attrs: { "icon": "download" }, on: { "click": _vm.detectLegacyMetadata } }, [_vm._v("Legacy Data")]), _c("k-button", { attrs: { "icon": "refresh" }, on: { "click": _vm.refreshPages } })], 1), _c("div", { staticClass: "k-meta-kit-controls" }, [_c("div", { staticClass: "k-meta-kit-search-wrapper" }, [_c("k-search-input", { staticClass: "k-meta-kit-search", attrs: { "icon": "search", "value": _vm.searchQuery, "placeholder": "Filter pages..." }, on: { "input": function($event) {
      _vm.searchQuery = $event;
    } } }), _vm.searchQuery ? _c("button", { staticClass: "k-meta-kit-search-clear", attrs: { "title": "Clear search" }, on: { "click": function($event) {
      _vm.searchQuery = "";
    } } }, [_c("k-icon", { attrs: { "type": "cancel" } })], 1) : _vm._e()], 1), _c("select", { staticClass: "k-meta-kit-pagesize-select", domProps: { "value": _vm.pageSize }, on: { "change": function($event) {
      return _vm.changePageSize($event.target.value);
    } } }, _vm._l(_vm.pageSizeOptions, function(option) {
      return _c("option", { key: option.value, domProps: { "value": option.value } }, [_vm._v(" " + _vm._s(option.text) + " ")]);
    }), 0)])], 1), _c("div", { staticClass: "k-meta-kit-table" }, [_c("table", [_c("thead", [_c("tr", [_c("th", { staticClass: "k-meta-kit-table-checkbox" }, [_c("input", { attrs: { "type": "checkbox" }, domProps: { "checked": _vm.isAllCurrentPageSelected }, on: { "change": _vm.toggleSelectAllCurrentPage } })]), _c("th", [_vm._v("#")]), _c("th", [_vm._v("Page")]), _c("th", [_vm._v("Template")]), _c("th", [_vm._v("Meta Title")]), _c("th", [_vm._v("Description")]), _c("th", [_vm._v("OG Image")]), _c("th", [_vm._v("NoIndex")]), _c("th", [_vm._v("Actions")])])]), _c("tbody", _vm._l(_vm.paginatedPages, function(page, index) {
      return _c("tr", { key: page.id }, [_c("td", { staticClass: "k-meta-kit-table-checkbox" }, [_c("input", { attrs: { "type": "checkbox" }, domProps: { "checked": _vm.isPageSelected(page.id) }, on: { "change": function($event) {
        return _vm.togglePageSelection(page.id);
      } } })]), _c("td", [_vm._v(_vm._s((_vm.currentPage - 1) * _vm.pageSize + index + 1))]), _c("td", [_c("div", { staticClass: "k-meta-kit-table-page" }, [_c("a", { staticClass: "k-link", attrs: { "href": page.panelUrl } }, [_vm._v(_vm._s(page.title))]), _c("span", { staticClass: "k-meta-kit-table-page-id" }, [_vm._v(_vm._s(page.id))])])]), _c("td", [_vm._v(_vm._s(page.template))]), _c("td", { staticClass: "k-meta-kit-table-center" }, [_c("span", { class: _vm.getStatusClass(page.hasMetaTitle, page.metaTitleLength) }, [_vm._v(" " + _vm._s(page.hasMetaTitle ? page.metaTitleLength : "—") + " ")])]), _c("td", { staticClass: "k-meta-kit-table-center" }, [_c("span", { class: _vm.getStatusClass(page.hasMetaDescription, page.metaDescriptionLength) }, [_vm._v(" " + _vm._s(page.hasMetaDescription ? page.metaDescriptionLength : "—") + " ")])]), _c("td", { staticClass: "k-meta-kit-table-center" }, [page.hasOgImage ? _c("k-icon", { staticClass: "k-meta-kit-icon-success", attrs: { "type": "check" } }) : _c("span", [_vm._v("—")])], 1), _c("td", { staticClass: "k-meta-kit-table-center" }, [page.noIndex ? _c("k-icon", { staticClass: "k-meta-kit-icon-warning", attrs: { "type": "check" } }) : _c("span", [_vm._v("—")])], 1), _c("td", { staticClass: "k-meta-kit-table-center" }, [_c("div", { staticClass: "k-meta-kit-table-actions" }, [_c("k-button", { attrs: { "icon": "edit", "size": "sm", "title": "Edit Metadata" }, on: { "click": function($event) {
        return _vm.editSinglePageMetadata(page.id);
      } } }), _c("k-button", { attrs: { "icon": "sparkling", "size": "sm", "disabled": page.hasMetaDescription, "title": "Generate Description" }, on: { "click": function($event) {
        return _vm.generateDescription(page.id);
      } } })], 1)])]);
    }), 0)])]), _vm.totalPages > 1 ? _c("div", { staticClass: "k-meta-kit-pagination" }, [_c("k-button", { attrs: { "icon": "angle-left", "disabled": _vm.currentPage === 1 }, on: { "click": _vm.previousPage } }), _c("span", { staticClass: "k-meta-kit-pagination-info" }, [_vm._v(" Page " + _vm._s(_vm.currentPage) + " of " + _vm._s(_vm.totalPages) + " "), _vm.searchQuery ? [_vm._v("(" + _vm._s(_vm.filteredPages.length) + " of " + _vm._s(_vm.pagesData.length) + ")")] : [_vm._v("(" + _vm._s(_vm.pagesData.length) + " total)")]], 2), _c("k-button", { attrs: { "icon": "angle-right", "disabled": _vm.currentPage === _vm.totalPages }, on: { "click": _vm.nextPage } })], 1) : _vm._e(), _c("k-dialog", { ref: "legacyDialog", attrs: { "size": "huge", "cancelButton": "", "submitButton": "" } }, [_c("k-headline", [_vm._v("Legacy SEO Metadata")]), _vm.isLoadingLegacy ? _c("div", { staticClass: "k-meta-kit-loading" }, [_c("k-icon", { staticClass: "k-meta-kit-spinner", attrs: { "type": "loader" } }), _c("span", [_vm._v("Scanning for legacy metadata...")])], 1) : _vm.legacyPages.length > 0 ? _c("div", { staticClass: "k-meta-kit-legacy-list" }, [_c("div", { staticStyle: { "display": "flex", "justify-content": "space-between", "align-items": "center", "margin-bottom": "1rem" } }, [_c("p", { staticStyle: { "margin": "0" } }, [_vm._v("Found " + _vm._s(_vm.legacyPages.length) + " pages with legacy SEO fields:")]), _c("k-button", { attrs: { "icon": "download", "disabled": _vm.isMigratingAll, "progress": _vm.isMigratingAll, "theme": "positive" }, on: { "click": _vm.migrateAllToBlocks } }, [_vm._v(" Migrate All ")])], 1), _vm._l(_vm.legacyPages, function(page) {
      return _c("div", { key: page.id, staticClass: "k-meta-kit-legacy-item" }, [_c("div", { staticClass: "k-meta-kit-legacy-item-header" }, [_c("strong", [_vm._v(_vm._s(page.title))])]), _c("div", { staticClass: "k-meta-kit-legacy-item-content" }, _vm._l(page.fields, function(value, key) {
        return _c("div", { key, staticClass: "k-meta-kit-legacy-field" }, [_c("span", { staticClass: "k-meta-kit-legacy-field-label" }, [_vm._v(_vm._s(_vm.formatFieldName(key)) + ":")]), _c("div", { staticClass: "k-meta-kit-legacy-field-values" }, [_c("div", { staticClass: "k-meta-kit-legacy-choices" }, [_c("k-button", { attrs: { "size": "xs", "theme": _vm.getFieldChoice(page.id, key) === "legacy" ? "positive" : "" }, on: { "click": function($event) {
          return _vm.setFieldChoice(page.id, key, "legacy");
        } } }, [_vm._v(" Legacy ")]), page.current && page.current[key] ? _c("k-button", { attrs: { "size": "xs", "theme": _vm.getFieldChoice(page.id, key) === "current" || _vm.getFieldChoice(page.id, key) === "keep" ? "positive" : "" }, on: { "click": function($event) {
          return _vm.setFieldChoice(page.id, key, "keep");
        } } }, [_vm._v(" Current ")]) : _vm._e(), key !== "ogImage" ? _c("k-button", { attrs: { "size": "xs", "icon": "sparkling", "theme": _vm.getFieldChoice(page.id, key) === "ai" ? "positive" : "", "disabled": _vm.isGeneratingField(page.id, key) }, on: { "click": function($event) {
          return _vm.generateFieldAI(page.id, key);
        } } }, [_vm._v(" AI Generate ")]) : _vm._e()], 1), _c("div", { staticClass: "k-meta-kit-legacy-field-preview" }, [_vm.getFieldChoice(page.id, key) === "legacy" ? _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge" }, [_vm._v("Legacy Value")]), _c("span", { staticClass: "k-meta-kit-legacy-field-value" }, [_vm._v(_vm._s(_vm.formatFieldValue(value)))])]) : (_vm.getFieldChoice(page.id, key) === "current" || _vm.getFieldChoice(page.id, key) === "keep") && page.current && page.current[key] ? _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge" }, [_vm._v("Current Value (editable)")]), _c("k-input", { attrs: { "value": _vm.getEditableValue(page.id, key, page.current[key]), "placeholder": _vm.formatFieldValue(page.current[key]), "type": "textarea" }, on: { "input": function($event) {
          return _vm.setManualValue(page.id, key, $event);
        } } })], 1) : _vm.getFieldChoice(page.id, key) === "ai" ? _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge k-meta-kit-legacy-badge-ai" }, [_vm._v("AI Generated (editable)")]), _vm.isGeneratingField(page.id, key) ? _c("span", { staticClass: "k-meta-kit-legacy-field-generating" }, [_c("k-icon", { staticClass: "k-meta-kit-spinner", attrs: { "type": "loader" } }), _vm._v(" Generating... ")], 1) : _c("k-input", { attrs: { "value": _vm.getManualValue(page.id, key), "placeholder": `AI generated ${_vm.formatFieldName(key)}`, "type": "textarea" }, on: { "input": function($event) {
          return _vm.setManualValue(page.id, key, $event);
        } } })], 1) : _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge-hint" }, [_vm._v("Select an option above")])])]), _c("div", { staticClass: "k-meta-kit-legacy-field-reference" }, [_c("details", [_c("summary", [_vm._v("View original values")]), _c("div", { staticClass: "k-meta-kit-legacy-field-old" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge-small" }, [_vm._v("Legacy")]), _c("span", { staticClass: "k-meta-kit-legacy-field-value-small" }, [_vm._v(_vm._s(_vm.formatFieldValue(value)))])]), page.current && page.current[key] ? _c("div", { staticClass: "k-meta-kit-legacy-field-new" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge-small" }, [_vm._v("Current")]), _c("span", { staticClass: "k-meta-kit-legacy-field-value-small" }, [_vm._v(_vm._s(_vm.formatFieldValue(page.current[key])))])]) : _vm._e()])]), _vm.hasFieldChanged(page.id, key, page.current ? page.current[key] : null, value) ? _c("k-button", { attrs: { "icon": "check", "size": "sm", "theme": "positive" }, on: { "click": function($event) {
          return _vm.applySingleField(page.id, key);
        } } }, [_vm._v(" Apply " + _vm._s(_vm.formatFieldName(key)) + " ")]) : _vm._e()], 1)]);
      }), 0)]);
    })], 2) : _c("div", { staticClass: "k-meta-kit-empty" }, [_c("k-icon", { attrs: { "type": "check" } }), _c("p", [_vm._v("No legacy SEO metadata found!")])], 1)], 1), _c("k-dialog", { ref: "allPagesDialog", attrs: { "size": "huge", "cancelButton": "Close", "submitButton": "" } }, [_c("k-headline", [_vm._v("Edit All Pages")]), _vm.isLoadingAllPages ? _c("div", { staticClass: "k-meta-kit-loading" }, [_c("k-icon", { staticClass: "k-meta-kit-spinner", attrs: { "type": "loader" } }), _c("span", [_vm._v("Loading pages...")])], 1) : _vm.allPagesData.length > 0 ? _c("div", { staticClass: "k-meta-kit-legacy-list" }, [_c("p", [_vm._v("Found " + _vm._s(_vm.allPagesData.length) + " pages:")]), _vm._l(_vm.allPagesData, function(page) {
      return _c("div", { key: page.id, staticClass: "k-meta-kit-legacy-item" }, [_c("div", { staticClass: "k-meta-kit-legacy-item-header" }, [_c("strong", [_vm._v(_vm._s(page.title))]), _c("k-button", { attrs: { "icon": "edit", "size": "sm" }, on: { "click": function($event) {
        return _vm.editSinglePage(page.id);
      } } }, [_vm._v(" Edit Page ")])], 1), _c("div", { staticClass: "k-meta-kit-legacy-item-content" }, [_c("div", { staticClass: "k-meta-kit-legacy-field" }, [_c("span", { staticClass: "k-meta-kit-legacy-field-label" }, [_vm._v("Meta Title:")]), _c("div", { staticClass: "k-meta-kit-legacy-field-values" }, [_c("div", { staticClass: "k-meta-kit-legacy-choices" }, [page.legacy && page.legacy.metaTitle ? _c("k-button", { attrs: { "size": "xs", "theme": _vm.getFieldChoice(page.id, "metaTitle") === "legacy" ? "positive" : "" }, on: { "click": function($event) {
        return _vm.setFieldChoice(page.id, "metaTitle", "legacy");
      } } }, [_vm._v(" Legacy ")]) : _vm._e(), _c("k-button", { attrs: { "size": "xs", "theme": _vm.getFieldChoice(page.id, "metaTitle") === "keep" ? "positive" : "" }, on: { "click": function($event) {
        return _vm.setFieldChoice(page.id, "metaTitle", "keep");
      } } }, [_vm._v(" Current ")]), _c("k-button", { attrs: { "size": "xs", "icon": "sparkling", "theme": _vm.getFieldChoice(page.id, "metaTitle") === "ai" ? "positive" : "", "disabled": _vm.isGeneratingField(page.id, "metaTitle") }, on: { "click": function($event) {
        return _vm.generateFieldAI(page.id, "metaTitle");
      } } }, [_vm._v(" AI Generate ")])], 1), _c("div", { staticClass: "k-meta-kit-legacy-field-preview" }, [_vm.getFieldChoice(page.id, "metaTitle") === "legacy" ? _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge" }, [_vm._v("Legacy Value")]), _c("span", { staticClass: "k-meta-kit-legacy-field-value" }, [_vm._v(_vm._s(page.legacy.metaTitle))])]) : _vm.getFieldChoice(page.id, "metaTitle") === "keep" || !_vm.getFieldChoice(page.id, "metaTitle") ? _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge" }, [_vm._v("Current Value (editable)")]), _c("k-input", { attrs: { "value": _vm.getEditableValue(page.id, "metaTitle", page.metaTitle), "placeholder": page.metaTitle || "No meta title set", "type": "text" }, on: { "input": function($event) {
        return _vm.setManualValue(page.id, "metaTitle", $event);
      } } }), page.hasMetaTitle ? _c("span", { staticClass: "k-meta-kit-field-length", class: _vm.getStatusClass(page.hasMetaTitle, page.metaTitleLength) }, [_vm._v(" (" + _vm._s(page.metaTitleLength) + " chars) ")]) : _vm._e()], 1) : _vm.getFieldChoice(page.id, "metaTitle") === "ai" ? _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge k-meta-kit-legacy-badge-ai" }, [_vm._v("AI Generated (editable)")]), _vm.isGeneratingField(page.id, "metaTitle") ? _c("span", { staticClass: "k-meta-kit-legacy-field-generating" }, [_c("k-icon", { staticClass: "k-meta-kit-spinner", attrs: { "type": "loader" } }), _vm._v(" Generating... ")], 1) : _c("k-input", { attrs: { "value": _vm.getManualValue(page.id, "metaTitle"), "placeholder": "AI generated Meta Title", "type": "text" }, on: { "input": function($event) {
        return _vm.setManualValue(page.id, "metaTitle", $event);
      } } })], 1) : _vm._e()]), _vm.hasFieldChanged(page.id, "metaTitle", page.metaTitle, page.legacy ? page.legacy.metaTitle : null) ? _c("k-button", { attrs: { "icon": "check", "size": "sm", "theme": "positive" }, on: { "click": function($event) {
        return _vm.applySingleField(page.id, "metaTitle");
      } } }, [_vm._v(" Apply Meta Title ")]) : _vm._e()], 1)]), _c("div", { staticClass: "k-meta-kit-legacy-field" }, [_c("span", { staticClass: "k-meta-kit-legacy-field-label" }, [_vm._v("Meta Description:")]), _c("div", { staticClass: "k-meta-kit-legacy-field-values" }, [_c("div", { staticClass: "k-meta-kit-legacy-choices" }, [page.legacy && page.legacy.metaDescription ? _c("k-button", { attrs: { "size": "xs", "theme": _vm.getFieldChoice(page.id, "metaDescription") === "legacy" ? "positive" : "" }, on: { "click": function($event) {
        return _vm.setFieldChoice(page.id, "metaDescription", "legacy");
      } } }, [_vm._v(" Legacy ")]) : _vm._e(), _c("k-button", { attrs: { "size": "xs", "theme": _vm.getFieldChoice(page.id, "metaDescription") === "keep" ? "positive" : "" }, on: { "click": function($event) {
        return _vm.setFieldChoice(page.id, "metaDescription", "keep");
      } } }, [_vm._v(" Current ")]), _c("k-button", { attrs: { "size": "xs", "icon": "sparkling", "theme": _vm.getFieldChoice(page.id, "metaDescription") === "ai" ? "positive" : "", "disabled": _vm.isGeneratingField(page.id, "metaDescription") }, on: { "click": function($event) {
        return _vm.generateFieldAI(page.id, "metaDescription");
      } } }, [_vm._v(" AI Generate ")])], 1), _c("div", { staticClass: "k-meta-kit-legacy-field-preview" }, [_vm.getFieldChoice(page.id, "metaDescription") === "legacy" ? _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge" }, [_vm._v("Legacy Value")]), _c("span", { staticClass: "k-meta-kit-legacy-field-value" }, [_vm._v(_vm._s(page.legacy.metaDescription))])]) : _vm.getFieldChoice(page.id, "metaDescription") === "keep" || !_vm.getFieldChoice(page.id, "metaDescription") ? _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge" }, [_vm._v("Current Value (editable)")]), _c("k-input", { attrs: { "value": _vm.getEditableValue(page.id, "metaDescription", page.metaDescription), "placeholder": page.metaDescription || "No meta description set", "type": "textarea" }, on: { "input": function($event) {
        return _vm.setManualValue(page.id, "metaDescription", $event);
      } } }), page.hasMetaDescription ? _c("span", { staticClass: "k-meta-kit-field-length", class: _vm.getStatusClass(page.hasMetaDescription, page.metaDescriptionLength) }, [_vm._v(" (" + _vm._s(page.metaDescriptionLength) + " chars) ")]) : _vm._e()], 1) : _vm.getFieldChoice(page.id, "metaDescription") === "ai" ? _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge k-meta-kit-legacy-badge-ai" }, [_vm._v("AI Generated (editable)")]), _vm.isGeneratingField(page.id, "metaDescription") ? _c("span", { staticClass: "k-meta-kit-legacy-field-generating" }, [_c("k-icon", { staticClass: "k-meta-kit-spinner", attrs: { "type": "loader" } }), _vm._v(" Generating... ")], 1) : _c("k-input", { attrs: { "value": _vm.getManualValue(page.id, "metaDescription"), "placeholder": "AI generated Meta Description", "type": "textarea" }, on: { "input": function($event) {
        return _vm.setManualValue(page.id, "metaDescription", $event);
      } } })], 1) : _vm._e()]), _vm.hasFieldChanged(page.id, "metaDescription", page.metaDescription, page.legacy ? page.legacy.metaDescription : null) ? _c("k-button", { attrs: { "icon": "check", "size": "sm", "theme": "positive" }, on: { "click": function($event) {
        return _vm.applySingleField(page.id, "metaDescription");
      } } }, [_vm._v(" Apply Meta Description ")]) : _vm._e()], 1)])])]);
    })], 2) : _c("div", { staticClass: "k-meta-kit-empty" }, [_c("k-icon", { attrs: { "type": "check" } }), _c("p", [_vm._v("No pages found!")])], 1)], 1), _c("k-dialog", { ref: "singlePageDialog", attrs: { "size": "huge", "cancelButton": "Close", "submitButton": "" } }, [_vm.currentEditPage ? _c("k-headline", [_vm._v("Edit: " + _vm._s(_vm.currentEditPage.title))]) : _vm._e(), _vm.isLoadingSinglePage ? _c("div", { staticClass: "k-meta-kit-loading" }, [_c("k-icon", { staticClass: "k-meta-kit-spinner", attrs: { "type": "loader" } }), _c("span", [_vm._v("Loading page...")])], 1) : _vm.currentEditPage ? _c("div", { staticClass: "k-meta-kit-single-edit" }, [_c("div", { staticClass: "k-meta-kit-legacy-field" }, [_c("span", { staticClass: "k-meta-kit-legacy-field-label" }, [_vm._v("Meta Title:")]), _c("div", { staticClass: "k-meta-kit-legacy-field-values" }, [_c("div", { staticClass: "k-meta-kit-legacy-choices" }, [_vm.currentEditPage.legacy && _vm.currentEditPage.legacy.metaTitle ? _c("k-button", { attrs: { "size": "xs", "theme": _vm.getFieldChoice(_vm.currentEditPage.id, "metaTitle") === "legacy" ? "positive" : "" }, on: { "click": function($event) {
      return _vm.setFieldChoice(_vm.currentEditPage.id, "metaTitle", "legacy");
    } } }, [_vm._v(" Legacy ")]) : _vm._e(), _c("k-button", { attrs: { "size": "xs", "theme": _vm.getFieldChoice(_vm.currentEditPage.id, "metaTitle") === "keep" ? "positive" : "" }, on: { "click": function($event) {
      return _vm.setFieldChoice(_vm.currentEditPage.id, "metaTitle", "keep");
    } } }, [_vm._v(" Current ")]), _c("k-button", { attrs: { "size": "xs", "icon": "sparkling", "theme": _vm.getFieldChoice(_vm.currentEditPage.id, "metaTitle") === "ai" ? "positive" : "", "disabled": _vm.isGeneratingField(_vm.currentEditPage.id, "metaTitle") }, on: { "click": function($event) {
      return _vm.generateFieldAI(_vm.currentEditPage.id, "metaTitle");
    } } }, [_vm._v(" AI Generate ")])], 1), _c("div", { staticClass: "k-meta-kit-legacy-field-preview" }, [_vm.getFieldChoice(_vm.currentEditPage.id, "metaTitle") === "legacy" ? _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge" }, [_vm._v("Legacy Value")]), _c("span", { staticClass: "k-meta-kit-legacy-field-value" }, [_vm._v(_vm._s(_vm.currentEditPage.legacy.metaTitle))])]) : _vm.getFieldChoice(_vm.currentEditPage.id, "metaTitle") === "keep" || !_vm.getFieldChoice(_vm.currentEditPage.id, "metaTitle") ? _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge" }, [_vm._v("Current Value (editable)")]), _c("k-input", { attrs: { "value": _vm.getEditableValue(_vm.currentEditPage.id, "metaTitle", _vm.currentEditPage.metaTitle), "placeholder": _vm.currentEditPage.metaTitle || "No meta title set", "type": "text" }, on: { "input": function($event) {
      return _vm.setManualValue(_vm.currentEditPage.id, "metaTitle", $event);
    } } }), _vm.currentEditPage.hasMetaTitle ? _c("span", { staticClass: "k-meta-kit-field-length", class: _vm.getStatusClass(_vm.currentEditPage.hasMetaTitle, _vm.currentEditPage.metaTitleLength) }, [_vm._v(" (" + _vm._s(_vm.currentEditPage.metaTitleLength) + " chars) ")]) : _vm._e()], 1) : _vm.getFieldChoice(_vm.currentEditPage.id, "metaTitle") === "ai" ? _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge k-meta-kit-legacy-badge-ai" }, [_vm._v("AI Generated (editable)")]), _vm.isGeneratingField(_vm.currentEditPage.id, "metaTitle") ? _c("span", { staticClass: "k-meta-kit-legacy-field-generating" }, [_c("k-icon", { staticClass: "k-meta-kit-spinner", attrs: { "type": "loader" } }), _vm._v(" Generating... ")], 1) : _c("k-input", { attrs: { "value": _vm.getManualValue(_vm.currentEditPage.id, "metaTitle"), "placeholder": "AI generated Meta Title", "type": "text" }, on: { "input": function($event) {
      return _vm.setManualValue(_vm.currentEditPage.id, "metaTitle", $event);
    } } })], 1) : _vm._e()]), _vm.hasFieldChanged(_vm.currentEditPage.id, "metaTitle", _vm.currentEditPage.metaTitle, _vm.currentEditPage.legacy ? _vm.currentEditPage.legacy.metaTitle : null) ? _c("k-button", { attrs: { "icon": "check", "size": "sm", "theme": "positive" }, on: { "click": function($event) {
      return _vm.applySingleFieldAndClose(_vm.currentEditPage.id, "metaTitle");
    } } }, [_vm._v(" Apply Meta Title ")]) : _vm._e()], 1)]), _c("div", { staticClass: "k-meta-kit-legacy-field" }, [_c("span", { staticClass: "k-meta-kit-legacy-field-label" }, [_vm._v("Meta Description:")]), _c("div", { staticClass: "k-meta-kit-legacy-field-values" }, [_c("div", { staticClass: "k-meta-kit-legacy-choices" }, [_vm.currentEditPage.legacy && _vm.currentEditPage.legacy.metaDescription ? _c("k-button", { attrs: { "size": "xs", "theme": _vm.getFieldChoice(_vm.currentEditPage.id, "metaDescription") === "legacy" ? "positive" : "" }, on: { "click": function($event) {
      return _vm.setFieldChoice(_vm.currentEditPage.id, "metaDescription", "legacy");
    } } }, [_vm._v(" Legacy ")]) : _vm._e(), _c("k-button", { attrs: { "size": "xs", "theme": _vm.getFieldChoice(_vm.currentEditPage.id, "metaDescription") === "keep" ? "positive" : "" }, on: { "click": function($event) {
      return _vm.setFieldChoice(_vm.currentEditPage.id, "metaDescription", "keep");
    } } }, [_vm._v(" Current ")]), _c("k-button", { attrs: { "size": "xs", "icon": "sparkling", "theme": _vm.getFieldChoice(_vm.currentEditPage.id, "metaDescription") === "ai" ? "positive" : "", "disabled": _vm.isGeneratingField(_vm.currentEditPage.id, "metaDescription") }, on: { "click": function($event) {
      return _vm.generateFieldAI(_vm.currentEditPage.id, "metaDescription");
    } } }, [_vm._v(" AI Generate ")])], 1), _c("div", { staticClass: "k-meta-kit-legacy-field-preview" }, [_vm.getFieldChoice(_vm.currentEditPage.id, "metaDescription") === "legacy" ? _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge" }, [_vm._v("Legacy Value")]), _c("span", { staticClass: "k-meta-kit-legacy-field-value" }, [_vm._v(_vm._s(_vm.currentEditPage.legacy.metaDescription))])]) : _vm.getFieldChoice(_vm.currentEditPage.id, "metaDescription") === "keep" || !_vm.getFieldChoice(_vm.currentEditPage.id, "metaDescription") ? _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge" }, [_vm._v("Current Value (editable)")]), _c("k-input", { attrs: { "value": _vm.getEditableValue(_vm.currentEditPage.id, "metaDescription", _vm.currentEditPage.metaDescription), "placeholder": _vm.currentEditPage.metaDescription || "No meta description set", "type": "textarea", "buttons": "false" }, on: { "input": function($event) {
      return _vm.setManualValue(_vm.currentEditPage.id, "metaDescription", $event);
    } } }), _vm.currentEditPage.hasMetaDescription ? _c("span", { staticClass: "k-meta-kit-field-length", class: _vm.getStatusClass(_vm.currentEditPage.hasMetaDescription, _vm.currentEditPage.metaDescriptionLength) }, [_vm._v(" (" + _vm._s(_vm.currentEditPage.metaDescriptionLength) + " chars) ")]) : _vm._e()], 1) : _vm.getFieldChoice(_vm.currentEditPage.id, "metaDescription") === "ai" ? _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge k-meta-kit-legacy-badge-ai" }, [_vm._v("AI Generated (editable)")]), _vm.isGeneratingField(_vm.currentEditPage.id, "metaDescription") ? _c("span", { staticClass: "k-meta-kit-legacy-field-generating" }, [_c("k-icon", { staticClass: "k-meta-kit-spinner", attrs: { "type": "loader" } }), _vm._v(" Generating... ")], 1) : _c("k-input", { attrs: { "value": _vm.getManualValue(_vm.currentEditPage.id, "metaDescription"), "placeholder": "AI generated Meta Description", "type": "textarea", "buttons": "false" }, on: { "input": function($event) {
      return _vm.setManualValue(_vm.currentEditPage.id, "metaDescription", $event);
    } } })], 1) : _vm._e()]), _vm.hasFieldChanged(_vm.currentEditPage.id, "metaDescription", _vm.currentEditPage.metaDescription, _vm.currentEditPage.legacy ? _vm.currentEditPage.legacy.metaDescription : null) ? _c("k-button", { attrs: { "icon": "check", "size": "sm", "theme": "positive" }, on: { "click": function($event) {
      return _vm.applySingleFieldAndClose(_vm.currentEditPage.id, "metaDescription");
    } } }, [_vm._v(" Apply Meta Description ")]) : _vm._e()], 1)]), _c("div", { staticClass: "k-meta-kit-legacy-field" }, [_c("span", { staticClass: "k-meta-kit-legacy-field-label" }, [_vm._v("OG Image:")]), _c("div", { staticClass: "k-meta-kit-legacy-field-values" }, [_vm.currentEditPage.ogImage ? _c("div", { staticClass: "k-meta-kit-og-image-current" }, [_c("img", { attrs: { "src": _vm.currentEditPage.ogImage.url, "alt": _vm.currentEditPage.ogImage.filename } }), _c("span", { staticClass: "k-meta-kit-og-image-filename" }, [_vm._v(_vm._s(_vm.currentEditPage.ogImage.filename))])]) : _c("div", { staticClass: "k-meta-kit-legacy-field-value-empty" }, [_vm._v(" No OG image set ")]), _c("k-button", { attrs: { "icon": "open", "size": "sm" }, on: { "click": function($event) {
      return _vm.openPageSeoTab(_vm.currentEditPage);
    } } }, [_vm._v(" Edit in Page ")])], 1)])]) : _vm._e()], 1)], 1);
  };
  var _sfc_staticRenderFns$1 = [];
  _sfc_render$1._withStripped = true;
  var __component__$1 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$1,
    _sfc_render$1,
    _sfc_staticRenderFns$1
  );
  __component__$1.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/kirby-seo-ai/js/components/MetaKitView.vue";
  const MetaKitView = __component__$1.exports;
  const _sfc_main = {
    props: {
      fieldName: String,
      fieldLabel: String,
      fieldType: {
        type: String,
        default: "text"
      },
      legacyValue: [String, Object],
      currentValue: [String, Object],
      pageId: String,
      showLegacy: {
        type: Boolean,
        default: false
      },
      showCurrent: {
        type: Boolean,
        default: true
      },
      showAI: {
        type: Boolean,
        default: true
      }
    },
    data() {
      return {
        choice: "keep",
        manualValue: "",
        isGenerating: false
      };
    },
    computed: {
      editableValue() {
        return this.manualValue || this.currentValue || "";
      },
      fieldLength() {
        if (this.editableValue && typeof this.editableValue === "string") {
          return this.editableValue.length;
        }
        return 0;
      },
      lengthClass() {
        if (this.fieldLength < 50 || this.fieldLength > 160) {
          return "k-meta-kit-status-warning";
        }
        return "k-meta-kit-status-success";
      },
      hasChanged() {
        if (!this.choice) return false;
        if (this.choice === "legacy") return true;
        if (this.choice === "keep" || this.choice === "current") {
          return this.manualValue && this.manualValue !== this.currentValue;
        }
        if (this.choice === "ai") {
          return !!this.manualValue;
        }
        return false;
      }
    },
    watch: {
      currentValue: {
        immediate: true,
        handler(newVal) {
          if (!this.manualValue) {
            this.choice = this.showLegacy && this.legacyValue ? "legacy" : "keep";
          }
        }
      }
    },
    methods: {
      setChoice(newChoice) {
        this.choice = newChoice;
        this.$emit("choice-changed", { choice: newChoice });
      },
      updateValue(value) {
        this.manualValue = value;
      },
      formatValue(value) {
        if (typeof value === "string") return value;
        if (Array.isArray(value)) return `${value.length} image(s)`;
        if (typeof value === "object") return "File";
        return String(value);
      },
      async generateAI() {
        this.setChoice("ai");
        this.isGenerating = true;
        try {
          const result = await this.$emit("generate-ai", { fieldName: this.fieldName, pageId: this.pageId });
        } catch (error) {
        } finally {
          this.isGenerating = false;
        }
      },
      apply() {
        let value;
        if (this.choice === "legacy") {
          value = this.legacyValue;
        } else {
          value = this.manualValue || this.currentValue;
        }
        this.$emit("apply", {
          fieldName: this.fieldName,
          value,
          choice: this.choice
        });
        this.manualValue = "";
        this.choice = "keep";
      },
      // Method to be called by parent to set generated AI content
      setGeneratedContent(content) {
        this.manualValue = content;
      },
      // Method to reset the field
      reset() {
        this.manualValue = "";
        this.choice = this.showLegacy && this.legacyValue ? "legacy" : "keep";
      }
    }
  };
  var _sfc_render = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { staticClass: "k-meta-kit-legacy-field" }, [_c("span", { staticClass: "k-meta-kit-legacy-field-label" }, [_vm._v(_vm._s(_vm.fieldLabel) + ":")]), _c("div", { staticClass: "k-meta-kit-legacy-field-values" }, [_c("div", { staticClass: "k-meta-kit-legacy-choices" }, [_vm.showLegacy ? _c("k-button", { attrs: { "size": "xs", "theme": _vm.choice === "legacy" ? "positive" : "" }, on: { "click": function($event) {
      return _vm.setChoice("legacy");
    } } }, [_vm._v(" Legacy ")]) : _vm._e(), _vm.showCurrent ? _c("k-button", { attrs: { "size": "xs", "theme": _vm.choice === "current" || _vm.choice === "keep" ? "positive" : "" }, on: { "click": function($event) {
      return _vm.setChoice("keep");
    } } }, [_vm._v(" Current ")]) : _vm._e(), _vm.showAI && _vm.fieldType !== "image" ? _c("k-button", { attrs: { "size": "xs", "icon": "sparkling", "theme": _vm.choice === "ai" ? "positive" : "", "disabled": _vm.isGenerating }, on: { "click": _vm.generateAI } }, [_vm._v(" AI Generate ")]) : _vm._e()], 1), _c("div", { staticClass: "k-meta-kit-legacy-field-preview" }, [_vm.choice === "legacy" ? _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge" }, [_vm._v("Legacy Value")]), _c("span", { staticClass: "k-meta-kit-legacy-field-value" }, [_vm._v(_vm._s(_vm.formatValue(_vm.legacyValue)))])]) : _vm.choice === "current" || _vm.choice === "keep" ? _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge" }, [_vm._v("Current Value (editable)")]), _c("k-input", { attrs: { "value": _vm.editableValue, "placeholder": _vm.currentValue || "No value set", "type": _vm.fieldType === "textarea" ? "textarea" : "text", "buttons": false }, on: { "input": _vm.updateValue } }), _vm.currentValue && _vm.fieldLength ? _c("span", { staticClass: "k-meta-kit-field-length", class: _vm.lengthClass }, [_vm._v(" (" + _vm._s(_vm.fieldLength) + " chars) ")]) : _vm._e()], 1) : _vm.choice === "ai" ? _c("div", { staticClass: "k-meta-kit-legacy-field-option" }, [_c("span", { staticClass: "k-meta-kit-legacy-badge k-meta-kit-legacy-badge-ai" }, [_vm._v("AI Generated (editable)")]), _vm.isGenerating ? _c("span", { staticClass: "k-meta-kit-legacy-field-generating" }, [_c("k-icon", { staticClass: "k-meta-kit-spinner", attrs: { "type": "loader" } }), _vm._v(" Generating... ")], 1) : _c("k-input", { attrs: { "value": _vm.editableValue, "placeholder": `AI generated ${_vm.fieldLabel}`, "type": _vm.fieldType === "textarea" ? "textarea" : "text", "buttons": false }, on: { "input": _vm.updateValue } })], 1) : _vm._e()]), _vm.hasChanged ? _c("k-button", { attrs: { "icon": "check", "size": "sm", "theme": "positive" }, on: { "click": _vm.apply } }, [_vm._v(" Apply " + _vm._s(_vm.fieldLabel) + " ")]) : _vm._e()], 1)]);
  };
  var _sfc_staticRenderFns = [];
  _sfc_render._withStripped = true;
  var __component__ = /* @__PURE__ */ normalizeComponent(
    _sfc_main,
    _sfc_render,
    _sfc_staticRenderFns
  );
  __component__.options.__file = "/Users/mathis/Work/Basic/kirby-basic/site/plugins/kirby-seo-ai/js/components/FieldEditor.vue";
  const FieldEditor = __component__.exports;
  panel.plugin("tearoom1/meta-kit", {
    components: {
      "meta-kit-view": MetaKitView,
      "field-editor": FieldEditor
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
            <strong>✓ Description generated and filled</strong>
<!--            <div class="k-meta-kit-generator__text">{{ generatedText }}</div>-->
<!--            <small>The description has been added to both Meta Description and OG Description fields below. Scroll down to review and save.</small>-->
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
                  if (!parent.value.seo || !Array.isArray(parent.value.seo)) {
                    this.$set(parent.value, "seo", [{
                      id: "seo-metadata",
                      type: "seo",
                      isHidden: false,
                      content: {}
                    }]);
                  }
                  if (parent.value.seo.length === 0) {
                    parent.value.seo.push({
                      id: "seo-metadata",
                      type: "seo",
                      isHidden: false,
                      content: {}
                    });
                  }
                  const seoBlock = parent.value.seo[0];
                  if (!seoBlock.content) {
                    this.$set(seoBlock, "content", {});
                  }
                  this.$set(seoBlock.content, "metadescription", response.description);
                  this.$set(seoBlock.content, "ogdescription", response.description);
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
