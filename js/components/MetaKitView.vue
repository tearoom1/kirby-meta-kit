<template>
  <k-panel-inside class="k-meta-kit-view">
    <!-- Language Switcher -->
    <div v-if="languages && languages.length > 1" class="k-meta-kit-language-bar">
      <k-button-group>
        <k-button
          v-for="lang in languages"
          :key="lang.code"
          :theme="lang.code === language ? 'positive' : ''"
          size="xs"
          @click="goToLanguage(lang.code)"
        >
          {{ lang.code.toUpperCase() }}
        </k-button>
      </k-button-group>
    </div>

    <!-- Legacy Detection Warning -->
    <div v-if="legacyMigration && legacyDetection.show && legacyDetection.found > 0" class="k-meta-kit-warning">
      <k-box theme="info">
        <k-icon type="info"/>
        <span>Found {{ legacyDetection.found }} pages with legacy SEO metadata</span>
        <k-button icon="download" @click="showLegacyDialog">View & Convert</k-button>
        <k-button icon="cancel" @click="dismissLegacyWarning">Dismiss</k-button>
      </k-box>
    </div>

    <!-- Stats Cards -->
    <div class="k-meta-kit-stats">
      <div class="k-meta-kit-stats-card">
        <h3>Total Pages</h3>
        <p>{{ filteredPages.length }}<span v-if="searchQuery" class="k-meta-kit-stats-total"> / {{ pagesData.length }}</span></p>
      </div>
      <div class="k-meta-kit-stats-card">
        <h3>With Description</h3>
        <p>{{ filteredPagesWithDescription }}<span v-if="searchQuery" class="k-meta-kit-stats-total"> / {{ pagesWithDescription }}</span></p>
      </div>
      <div class="k-meta-kit-stats-card">
        <h3>With OG Image</h3>
        <p>{{ filteredPagesWithOgImage }}<span v-if="searchQuery" class="k-meta-kit-stats-total"> / {{ pagesWithOgImage }}</span></p>
      </div>
      <div class="k-meta-kit-stats-card">
        <h3>No Index</h3>
        <p>{{ filteredPagesNoIndex }}<span v-if="searchQuery" class="k-meta-kit-stats-total"> / {{ pagesNoIndex }}</span></p>
      </div>
    </div>

    <!-- Actions & Filters -->
    <div class="k-meta-kit-actions">
      <k-button-group>
        <k-button
          icon="edit"
          :disabled="selectedPages.length === 0"
          @click="showSelectedPagesDialog"
        >
          Edit Selected ({{ selectedPages.length }})
        </k-button>
        <k-button
          v-if="aiEnabled"
          icon="sparkling"
          :disabled="isGeneratingAll || selectedPages.length === 0"
          :progress="isGeneratingAll"
          @click="generateAllDescriptions"
        >
          Generate Missing ({{ selectedPages.length || filteredPages.length }})
        </k-button>
        <k-button v-if="legacyMigration" icon="download" @click="detectLegacyMetadata">Legacy Data</k-button>
        <k-button icon="refresh" @click="refreshPages"></k-button>
      </k-button-group>

      <div class="k-meta-kit-controls">
        <div class="k-meta-kit-search-wrapper">
          <k-search-input
            icon="search"
            :value="searchQuery"
            @input="searchQuery = $event"
            placeholder="Filter pages..."
            class="k-meta-kit-search"
          />
          <button
            v-if="searchQuery"
            class="k-meta-kit-search-clear"
            @click="searchQuery = ''"
            title="Clear search"
          >
            <k-icon type="cancel" />
          </button>
        </div>
        <select
          class="k-meta-kit-pagesize-select"
          :value="pageSize"
          @change="changePageSize($event.target.value)"
        >
          <option v-for="option in pageSizeOptions" :key="option.value" :value="option.value">
            {{ option.text }}
          </option>
        </select>
      </div>
    </div>

    <!-- Pages Table -->
    <div class="k-meta-kit-table">
      <table>
        <thead>
        <tr>
          <th class="k-meta-kit-table-checkbox">
            <input
              type="checkbox"
              :checked="isAllCurrentPageSelected"
              @change="toggleSelectAllCurrentPage"
            />
          </th>
          <th>#</th>
          <th>Page</th>
          <th>Template</th>
          <th>Meta Title</th>
          <th>Description</th>
          <th>OG Image</th>
          <th>Robots</th>
          <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="(page, index) in paginatedPages" :key="page.id">
          <td class="k-meta-kit-table-checkbox">
            <input
              type="checkbox"
              :checked="isPageSelected(page.id)"
              @change="togglePageSelection(page.id)"
            />
          </td>
          <td>{{ (currentPage - 1) * pageSize + index + 1 }}</td>
          <td>
            <div class="k-meta-kit-table-page">
              <a :href="page.panelUrl" class="k-link">{{ page.title }}</a>
              <span class="k-meta-kit-table-page-id">{{ page.id }}</span>
            </div>
          </td>
          <td>{{ page.template }}</td>
          <td class="k-meta-kit-table-center">
              <span :class="getStatusClass(page.hasMetaTitle, page.metaTitleLength)">
                {{ page.hasMetaTitle ? page.metaTitleLength : '—' }}
              </span>
          </td>
          <td class="k-meta-kit-table-center">
              <span :class="getStatusClass(page.hasMetaDescription, page.metaDescriptionLength)">
                {{ page.hasMetaDescription ? page.metaDescriptionLength : '—' }}
              </span>
          </td>
          <td class="k-meta-kit-table-center">
            <k-icon v-if="page.hasOgImage" type="check" class="k-meta-kit-icon-success"/>
            <span v-else>—</span>
          </td>
          <td class="k-meta-kit-table-center">
            <span v-if="page.robots && page.robots.includes('noindex')" class="k-meta-kit-robots-noindex">noindex</span>
            <span v-else>—</span>
          </td>
          <td class="k-meta-kit-table-center">
            <div class="k-meta-kit-table-actions">
              <k-button
                icon="edit"
                size="sm"
                @click="editSinglePageMetadata(page.id)"
                title="Edit Metadata"
              />
              <k-button
                v-if="aiEnabled"
                icon="sparkling"
                size="sm"
                :disabled="page.hasMetaDescription"
                @click="generateDescription(page.id)"
                title="Generate Description"
              />
            </div>
          </td>
        </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="k-meta-kit-pagination">
      <k-button
        icon="angle-left"
        :disabled="currentPage === 1"
        @click="previousPage"
      />
      <span class="k-meta-kit-pagination-info">
        Page {{ currentPage }} of {{ totalPages }}
        <template v-if="searchQuery">({{ filteredPages.length }} of {{ pagesData.length }})</template>
        <template v-else>({{ pagesData.length }} total)</template>
      </span>
      <k-button
        icon="angle-right"
        :disabled="currentPage === totalPages"
        @click="nextPage"
      />
    </div>

    <!-- Legacy Data Dialog -->
    <k-dialog ref="legacyDialog" size="huge" cancelButton="Close" submitButton="">
      <k-headline>Legacy SEO Metadata</k-headline>

      <div v-if="isLoadingLegacy" class="k-meta-kit-loading">
        <k-icon class="k-meta-kit-spinner" type="loader"/>
        <span>Scanning for legacy metadata...</span>
      </div>

      <div v-else-if="legacyPages.length > 0" class="k-meta-kit-legacy-list">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
          <p style="margin: 0;">Found {{ legacyPages.length }} pages with legacy SEO fields:</p>
          <k-button
            icon="download"
            :disabled="isMigratingAll"
            :progress="isMigratingAll"
            @click="migrateAllToBlocks"
            theme="positive"
          >
            Migrate All
          </k-button>
        </div>

        <div v-for="page in legacyPages" :key="page.id" class="k-meta-kit-legacy-item">
          <div class="k-meta-kit-legacy-item-header">
            <strong>{{ page.title }}</strong>
          </div>

          <div class="k-meta-kit-legacy-item-content">
            <div v-for="(value, key) in page.fields" :key="key" class="k-meta-kit-legacy-field">
              <span class="k-meta-kit-legacy-field-label">{{ formatFieldName(key) }}:</span>
              <div class="k-meta-kit-legacy-field-values">

                <!-- Choice Buttons -->
                <div class="k-meta-kit-legacy-choices">
                  <k-button
                    size="xs"
                    :theme="getFieldChoice(page.id, key) === 'legacy' ? 'positive' : ''"
                    @click="setFieldChoice(page.id, key, 'legacy')"
                  >
                    Legacy
                  </k-button>
                  <k-button
                    v-if="page.current && page.current[key]"
                    size="xs"
                    :theme="(getFieldChoice(page.id, key) === 'current' || getFieldChoice(page.id, key) === 'keep') ? 'positive' : ''"
                    @click="setFieldChoice(page.id, key, 'keep')"
                  >
                    Current
                  </k-button>
                  <k-button
                    v-if="aiEnabled && key !== 'ogImage'"
                    size="xs"
                    icon="sparkling"
                    :theme="getFieldChoice(page.id, key) === 'ai' ? 'positive' : ''"
                    :disabled="isGeneratingField(page.id, key)"
                    @click="generateFieldAI(page.id, key)"
                  >
                    AI Generate
                  </k-button>
                </div>

                <!-- Preview based on choice -->
                <div class="k-meta-kit-legacy-field-preview">
                  <div v-if="getFieldChoice(page.id, key) === 'legacy'" class="k-meta-kit-legacy-field-option">
                    <span class="k-meta-kit-legacy-badge">Legacy Value</span>
                    <span class="k-meta-kit-legacy-field-value">{{ formatFieldValue(value) }}</span>
                  </div>

                  <div v-else-if="(getFieldChoice(page.id, key) === 'current' || getFieldChoice(page.id, key) === 'keep') && page.current && page.current[key]"
                       class="k-meta-kit-legacy-field-option">
                    <span class="k-meta-kit-legacy-badge">Current Value (editable)</span>
                    <k-input
                      :value="getEditableValue(page.id, key, page.current[key])"
                      @input="setManualValue(page.id, key, $event)"
                      :placeholder="formatFieldValue(page.current[key])"
                      type="textarea"
                    />
                  </div>

                  <div v-else-if="getFieldChoice(page.id, key) === 'ai'" class="k-meta-kit-legacy-field-option">
                    <span class="k-meta-kit-legacy-badge k-meta-kit-legacy-badge-ai">AI Generated (editable)</span>
                    <span v-if="isGeneratingField(page.id, key)" class="k-meta-kit-legacy-field-generating">
                      <k-icon class="k-meta-kit-spinner" type="loader"/>
                      Generating...
                    </span>
                    <k-input
                      v-else
                      :value="getManualValue(page.id, key)"
                      @input="setManualValue(page.id, key, $event)"
                      :placeholder="`AI generated ${formatFieldName(key)}`"
                      type="textarea"
                    />
                  </div>

                  <div v-else class="k-meta-kit-legacy-field-option">
                    <span class="k-meta-kit-legacy-badge-hint">Select an option above</span>
                  </div>
                </div>

                <!-- Original values for reference -->
                <div class="k-meta-kit-legacy-field-reference">
                  <details>
                    <summary>View original values</summary>
                    <div class="k-meta-kit-legacy-field-old">
                      <span class="k-meta-kit-legacy-badge-small">Legacy</span>
                      <span class="k-meta-kit-legacy-field-value-small">{{ formatFieldValue(value) }}</span>
                    </div>
                    <div v-if="page.current && page.current[key]" class="k-meta-kit-legacy-field-new">
                      <span class="k-meta-kit-legacy-badge-small">Current</span>
                      <span class="k-meta-kit-legacy-field-value-small">{{ formatFieldValue(page.current[key]) }}</span>
                    </div>
                  </details>
                </div>

                <!-- Apply button for single field -->
                <k-button
                  v-if="hasFieldChanged(page.id, key, page.current ? page.current[key] : null, value)"
                  icon="check"
                  size="sm"
                  theme="positive"
                  @click="applySingleField(page.id, key)"
                >
                  Apply {{ formatFieldName(key) }}
                </k-button>

              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="k-meta-kit-empty">
        <k-icon type="check"/>
        <p>No legacy SEO metadata found!</p>
      </div>
    </k-dialog>

    <!-- All Pages Dialog -->
    <k-dialog ref="allPagesDialog" size="huge" cancelButton="Close" submitButton="">
      <k-headline>Edit All Pages</k-headline>

      <div v-if="isLoadingAllPages" class="k-meta-kit-loading">
        <k-icon class="k-meta-kit-spinner" type="loader"/>
        <span>Loading pages...</span>
      </div>

      <div v-else-if="allPagesData.length > 0" class="k-meta-kit-legacy-list">
        <p>Found {{ allPagesData.length }} pages:</p>

        <div v-for="page in allPagesData" :key="page.id" class="k-meta-kit-legacy-item">
          <div class="k-meta-kit-legacy-item-header">
            <strong>{{ page.title }}</strong>
            <k-button
              icon="edit"
              size="sm"
              @click="editSinglePage(page.id)"
            >
              Edit Page
            </k-button>
          </div>

          <div class="k-meta-kit-legacy-item-content">
            <!-- Meta Title -->
            <div class="k-meta-kit-legacy-field">
              <span class="k-meta-kit-legacy-field-label">Meta Title:</span>
              <div class="k-meta-kit-legacy-field-values">

                <!-- Choice Buttons -->
                <div class="k-meta-kit-legacy-choices">
                  <k-button
                    v-if="page.legacy && page.legacy.metaTitle"
                    size="xs"
                    :theme="getFieldChoice(page.id, 'metaTitle') === 'legacy' ? 'positive' : ''"
                    @click="setFieldChoice(page.id, 'metaTitle', 'legacy')"
                  >
                    Legacy
                  </k-button>
                  <k-button
                    size="xs"
                    :theme="getFieldChoice(page.id, 'metaTitle') === 'keep' ? 'positive' : ''"
                    @click="setFieldChoice(page.id, 'metaTitle', 'keep')"
                  >
                    Current
                  </k-button>
                  <k-button
                    v-if="aiEnabled"
                    size="xs"
                    icon="sparkling"
                    :theme="getFieldChoice(page.id, 'metaTitle') === 'ai' ? 'positive' : ''"
                    :disabled="isGeneratingField(page.id, 'metaTitle')"
                    @click="generateFieldAI(page.id, 'metaTitle')"
                  >
                    AI Generate
                  </k-button>
                </div>

                <!-- Preview -->
                <div class="k-meta-kit-legacy-field-preview">
                  <div v-if="getFieldChoice(page.id, 'metaTitle') === 'legacy'" class="k-meta-kit-legacy-field-option">
                    <span class="k-meta-kit-legacy-badge">Legacy Value</span>
                    <span class="k-meta-kit-legacy-field-value">{{ page.legacy.metaTitle }}</span>
                  </div>

                  <div
                    v-else-if="getFieldChoice(page.id, 'metaTitle') === 'keep' || !getFieldChoice(page.id, 'metaTitle')"
                    class="k-meta-kit-legacy-field-option">
                    <span class="k-meta-kit-legacy-badge">Current Value (editable)</span>
                    <k-input
                      :value="getEditableValue(page.id, 'metaTitle', page.metaTitle)"
                      @input="setManualValue(page.id, 'metaTitle', $event)"
                      :placeholder="page.metaTitle || 'No meta title set'"
                      type="text"
                    />
                    <span v-if="page.hasMetaTitle" class="k-meta-kit-field-length"
                          :class="getStatusClass(page.hasMetaTitle, page.metaTitleLength)">
                      ({{ page.metaTitleLength }} chars)
                    </span>
                  </div>

                  <div v-else-if="getFieldChoice(page.id, 'metaTitle') === 'ai'" class="k-meta-kit-legacy-field-option">
                    <span class="k-meta-kit-legacy-badge k-meta-kit-legacy-badge-ai">AI Generated (editable)</span>
                    <span v-if="isGeneratingField(page.id, 'metaTitle')" class="k-meta-kit-legacy-field-generating">
                      <k-icon class="k-meta-kit-spinner" type="loader"/>
                      Generating...
                    </span>
                    <k-input
                      v-else
                      :value="getManualValue(page.id, 'metaTitle')"
                      @input="setManualValue(page.id, 'metaTitle', $event)"
                      placeholder="AI generated Meta Title"
                      type="text"
                    />
                  </div>
                </div>

                <!-- Apply button -->
                <k-button
                  v-if="hasFieldChanged(page.id, 'metaTitle', page.metaTitle, page.legacy ? page.legacy.metaTitle : null)"
                  icon="check"
                  size="sm"
                  theme="positive"
                  @click="applySingleField(page.id, 'metaTitle')"
                >
                  Apply Meta Title
                </k-button>
              </div>
            </div>

            <!-- Meta Description -->
            <div class="k-meta-kit-legacy-field">
              <span class="k-meta-kit-legacy-field-label">Meta Description:</span>
              <div class="k-meta-kit-legacy-field-values">

                <!-- Choice Buttons -->
                <div class="k-meta-kit-legacy-choices">
                  <k-button
                    v-if="page.legacy && page.legacy.metaDescription"
                    size="xs"
                    :theme="getFieldChoice(page.id, 'metaDescription') === 'legacy' ? 'positive' : ''"
                    @click="setFieldChoice(page.id, 'metaDescription', 'legacy')"
                  >
                    Legacy
                  </k-button>
                  <k-button
                    size="xs"
                    :theme="getFieldChoice(page.id, 'metaDescription') === 'keep' ? 'positive' : ''"
                    @click="setFieldChoice(page.id, 'metaDescription', 'keep')"
                  >
                    Current
                  </k-button>
                  <k-button
                    v-if="aiEnabled"
                    size="xs"
                    icon="sparkling"
                    :theme="getFieldChoice(page.id, 'metaDescription') === 'ai' ? 'positive' : ''"
                    :disabled="isGeneratingField(page.id, 'metaDescription')"
                    @click="generateFieldAI(page.id, 'metaDescription')"
                  >
                    AI Generate
                  </k-button>
                </div>

                <!-- Preview -->
                <div class="k-meta-kit-legacy-field-preview">
                  <div v-if="getFieldChoice(page.id, 'metaDescription') === 'legacy'"
                       class="k-meta-kit-legacy-field-option">
                    <span class="k-meta-kit-legacy-badge">Legacy Value</span>
                    <span class="k-meta-kit-legacy-field-value">{{ page.legacy.metaDescription }}</span>
                  </div>

                  <div
                    v-else-if="getFieldChoice(page.id, 'metaDescription') === 'keep' || !getFieldChoice(page.id, 'metaDescription')"
                    class="k-meta-kit-legacy-field-option">
                    <span class="k-meta-kit-legacy-badge">Current Value (editable)</span>
                    <k-input
                      :value="getEditableValue(page.id, 'metaDescription', page.metaDescription)"
                      @input="setManualValue(page.id, 'metaDescription', $event)"
                      :placeholder="page.metaDescription || 'No meta description set'"
                      type="textarea"
                    />
                    <span v-if="page.hasMetaDescription" class="k-meta-kit-field-length"
                          :class="getStatusClass(page.hasMetaDescription, page.metaDescriptionLength)">
                      ({{ page.metaDescriptionLength }} chars)
                    </span>
                  </div>

                  <div v-else-if="getFieldChoice(page.id, 'metaDescription') === 'ai'"
                       class="k-meta-kit-legacy-field-option">
                    <span class="k-meta-kit-legacy-badge k-meta-kit-legacy-badge-ai">AI Generated (editable)</span>
                    <span v-if="isGeneratingField(page.id, 'metaDescription')"
                          class="k-meta-kit-legacy-field-generating">
                      <k-icon class="k-meta-kit-spinner" type="loader"/>
                      Generating...
                    </span>
                    <k-input
                      v-else
                      :value="getManualValue(page.id, 'metaDescription')"
                      @input="setManualValue(page.id, 'metaDescription', $event)"
                      placeholder="AI generated Meta Description"
                      type="textarea"
                    />
                  </div>
                </div>

                <!-- Apply button -->
                <k-button
                  v-if="hasFieldChanged(page.id, 'metaDescription', page.metaDescription, page.legacy ? page.legacy.metaDescription : null)"
                  icon="check"
                  size="sm"
                  theme="positive"
                  @click="applySingleField(page.id, 'metaDescription')"
                >
                  Apply Meta Description
                </k-button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="k-meta-kit-empty">
        <k-icon type="check"/>
        <p>No pages found!</p>
      </div>
    </k-dialog>

    <!-- Single Page Edit Dialog -->
    <k-dialog ref="singlePageDialog" size="huge" cancelButton="Close" submitButton="">
      <k-headline v-if="currentEditPage">Edit: {{ currentEditPage.title }}</k-headline>

      <div v-if="isLoadingSinglePage" class="k-meta-kit-loading">
        <k-icon class="k-meta-kit-spinner" type="loader"/>
        <span>Loading page...</span>
      </div>

      <div v-else-if="currentEditPage" class="k-meta-kit-single-edit">
        <!-- Meta Title -->
        <div class="k-meta-kit-legacy-field">
          <span class="k-meta-kit-legacy-field-label">Meta Title:</span>
          <div class="k-meta-kit-legacy-field-values">

            <!-- Choice Buttons -->
            <div class="k-meta-kit-legacy-choices">
              <k-button
                v-if="currentEditPage.legacy && currentEditPage.legacy.metaTitle"
                size="xs"
                :theme="getFieldChoice(currentEditPage.id, 'metaTitle') === 'legacy' ? 'positive' : ''"
                @click="setFieldChoice(currentEditPage.id, 'metaTitle', 'legacy')"
              >
                Legacy
              </k-button>
              <k-button
                size="xs"
                :theme="getFieldChoice(currentEditPage.id, 'metaTitle') === 'keep' ? 'positive' : ''"
                @click="setFieldChoice(currentEditPage.id, 'metaTitle', 'keep')"
              >
                Current
              </k-button>
              <k-button
                v-if="aiEnabled"
                size="xs"
                icon="sparkling"
                :theme="getFieldChoice(currentEditPage.id, 'metaTitle') === 'ai' ? 'positive' : ''"
                :disabled="isGeneratingField(currentEditPage.id, 'metaTitle')"
                @click="generateFieldAI(currentEditPage.id, 'metaTitle')"
              >
                AI Generate
              </k-button>
            </div>

            <!-- Preview -->
            <div class="k-meta-kit-legacy-field-preview">
              <div v-if="getFieldChoice(currentEditPage.id, 'metaTitle') === 'legacy'"
                   class="k-meta-kit-legacy-field-option">
                <span class="k-meta-kit-legacy-badge">Legacy Value</span>
                <span class="k-meta-kit-legacy-field-value">{{ currentEditPage.legacy.metaTitle }}</span>
              </div>

              <div
                v-else-if="getFieldChoice(currentEditPage.id, 'metaTitle') === 'keep' || !getFieldChoice(currentEditPage.id, 'metaTitle')"
                class="k-meta-kit-legacy-field-option">
                <span class="k-meta-kit-legacy-badge">Current Value (editable)</span>
                <k-input
                  :value="getEditableValue(currentEditPage.id, 'metaTitle', currentEditPage.metaTitle)"
                  @input="setManualValue(currentEditPage.id, 'metaTitle', $event)"
                  :placeholder="currentEditPage.metaTitle || 'No meta title set'"
                  type="text"
                />
                <span v-if="currentEditPage.hasMetaTitle" class="k-meta-kit-field-length"
                      :class="getStatusClass(currentEditPage.hasMetaTitle, currentEditPage.metaTitleLength)">
                  ({{ currentEditPage.metaTitleLength }} chars)
                </span>
              </div>

              <div v-else-if="getFieldChoice(currentEditPage.id, 'metaTitle') === 'ai'"
                   class="k-meta-kit-legacy-field-option">
                <span class="k-meta-kit-legacy-badge k-meta-kit-legacy-badge-ai">AI Generated (editable)</span>
                <span v-if="isGeneratingField(currentEditPage.id, 'metaTitle')"
                      class="k-meta-kit-legacy-field-generating">
                  <k-icon class="k-meta-kit-spinner" type="loader"/>
                  Generating...
                </span>
                <k-input
                  v-else
                  :value="getManualValue(currentEditPage.id, 'metaTitle')"
                  @input="setManualValue(currentEditPage.id, 'metaTitle', $event)"
                  placeholder="AI generated Meta Title"
                  type="text"
                />
              </div>
            </div>

            <!-- Apply button -->
            <k-button
              v-if="hasFieldChanged(currentEditPage.id, 'metaTitle', currentEditPage.metaTitle, currentEditPage.legacy ? currentEditPage.legacy.metaTitle : null)"
              icon="check"
              size="sm"
              theme="positive"
              @click="applySingleFieldAndClose(currentEditPage.id, 'metaTitle')"
            >
              Apply Meta Title
            </k-button>
          </div>
        </div>

        <!-- Meta Description -->
        <div class="k-meta-kit-legacy-field">
          <span class="k-meta-kit-legacy-field-label">Meta Description:</span>
          <div class="k-meta-kit-legacy-field-values">

            <!-- Choice Buttons -->
            <div class="k-meta-kit-legacy-choices">
              <k-button
                v-if="currentEditPage.legacy && currentEditPage.legacy.metaDescription"
                size="xs"
                :theme="getFieldChoice(currentEditPage.id, 'metaDescription') === 'legacy' ? 'positive' : ''"
                @click="setFieldChoice(currentEditPage.id, 'metaDescription', 'legacy')"
              >
                Legacy
              </k-button>
              <k-button
                size="xs"
                :theme="getFieldChoice(currentEditPage.id, 'metaDescription') === 'keep' ? 'positive' : ''"
                @click="setFieldChoice(currentEditPage.id, 'metaDescription', 'keep')"
              >
                Current
              </k-button>
              <k-button
                v-if="aiEnabled"
                size="xs"
                icon="sparkling"
                :theme="getFieldChoice(currentEditPage.id, 'metaDescription') === 'ai' ? 'positive' : ''"
                :disabled="isGeneratingField(currentEditPage.id, 'metaDescription')"
                @click="generateFieldAI(currentEditPage.id, 'metaDescription')"
              >
                AI Generate
              </k-button>
            </div>

            <!-- Preview -->
            <div class="k-meta-kit-legacy-field-preview">
              <div v-if="getFieldChoice(currentEditPage.id, 'metaDescription') === 'legacy'"
                   class="k-meta-kit-legacy-field-option">
                <span class="k-meta-kit-legacy-badge">Legacy Value</span>
                <span class="k-meta-kit-legacy-field-value">{{ currentEditPage.legacy.metaDescription }}</span>
              </div>

              <div
                v-else-if="getFieldChoice(currentEditPage.id, 'metaDescription') === 'keep' || !getFieldChoice(currentEditPage.id, 'metaDescription')"
                class="k-meta-kit-legacy-field-option">
                <span class="k-meta-kit-legacy-badge">Current Value (editable)</span>
                <k-input
                  :value="getEditableValue(currentEditPage.id, 'metaDescription', currentEditPage.metaDescription)"
                  @input="setManualValue(currentEditPage.id, 'metaDescription', $event)"
                  :placeholder="currentEditPage.metaDescription || 'No meta description set'"
                  type="textarea"
                  buttons="false"
                />
                <span v-if="currentEditPage.hasMetaDescription" class="k-meta-kit-field-length"
                      :class="getStatusClass(currentEditPage.hasMetaDescription, currentEditPage.metaDescriptionLength)">
                  ({{ currentEditPage.metaDescriptionLength }} chars)
                </span>
              </div>

              <div v-else-if="getFieldChoice(currentEditPage.id, 'metaDescription') === 'ai'"
                   class="k-meta-kit-legacy-field-option">
                <span class="k-meta-kit-legacy-badge k-meta-kit-legacy-badge-ai">AI Generated (editable)</span>
                <span v-if="isGeneratingField(currentEditPage.id, 'metaDescription')"
                      class="k-meta-kit-legacy-field-generating">
                  <k-icon class="k-meta-kit-spinner" type="loader"/>
                  Generating...
                </span>
                <k-input
                  v-else
                  :value="getManualValue(currentEditPage.id, 'metaDescription')"
                  @input="setManualValue(currentEditPage.id, 'metaDescription', $event)"
                  placeholder="AI generated Meta Description"
                  type="textarea"
                  buttons="false"
                />
              </div>
            </div>

            <!-- Apply button -->
            <k-button
              v-if="hasFieldChanged(currentEditPage.id, 'metaDescription', currentEditPage.metaDescription, currentEditPage.legacy ? currentEditPage.legacy.metaDescription : null)"
              icon="check"
              size="sm"
              theme="positive"
              @click="applySingleFieldAndClose(currentEditPage.id, 'metaDescription')"
            >
              Apply Meta Description
            </k-button>
          </div>
        </div>

        <!-- OG Image -->
        <div class="k-meta-kit-legacy-field">
          <span class="k-meta-kit-legacy-field-label">OG Image:</span>
          <div class="k-meta-kit-legacy-field-values">

            <!-- Current Image Display -->
            <div v-if="currentEditPage.ogImage" class="k-meta-kit-og-image-current">
              <img :src="currentEditPage.ogImage.url" :alt="currentEditPage.ogImage.filename"/>
              <span class="k-meta-kit-og-image-filename">{{ currentEditPage.ogImage.filename }}</span>
            </div>
            <div v-else class="k-meta-kit-legacy-field-value-empty">
              No OG image set
            </div>

            <!-- Edit Page Button -->
            <k-button
              icon="open"
              size="sm"
              @click="openPageSeoTab(currentEditPage)"
            >
              Edit in Page
            </k-button>
          </div>
        </div>
      </div>
    </k-dialog>
  </k-panel-inside>
</template>

<script>
export default {
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
      currentEditPage: null,
      legacyDetection: {
        show: false,
        found: 0
      },
      fieldChoices: {}, // { pageId: { fieldName: 'legacy|current|manual|ai', manualValue: '...' } }
      generatingFields: {}, // { pageId: { fieldName: true } }

      // Pagination & Selection
      selectedPages: [],
      currentPage: 1,
      pageSize: 25,
      pageSizeOptions: [
        { value: 25, text: '25 per page' },
        { value: 50, text: '50 per page' },
        { value: 100, text: '100 per page' },
        { value: 99999, text: 'All' }
      ],
      searchQuery: ''
    };
  },
  computed: {
    filteredPages() {
      if (!this.searchQuery.trim()) {
        return this.pagesData;
      }

      const query = this.searchQuery.toLowerCase();
      return this.pagesData.filter(page => {
        return page.title.toLowerCase().includes(query) ||
               page.id.toLowerCase().includes(query) ||
               page.template.toLowerCase().includes(query) ||
               (page.metaDescription && page.metaDescription.toLowerCase().includes(query));
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
      return this.paginatedPages.every(page => this.selectedPages.includes(page.id));
    },
    pagesWithDescription() {
      return this.pagesData.filter(p => p.hasMetaDescription).length;
    },
    pagesWithOgImage() {
      return this.pagesData.filter(p => p.hasOgImage).length;
    },
    pagesNoIndex() {
      return this.pagesData.filter(p => p.robots && p.robots.includes('noindex')).length;
    },
    filteredPagesWithDescription() {
      return this.filteredPages.filter(p => p.hasMetaDescription).length;
    },
    filteredPagesWithOgImage() {
      return this.filteredPages.filter(p => p.hasOgImage).length;
    },
    filteredPagesNoIndex() {
      return this.filteredPages.filter(p => p.robots && p.robots.includes('noindex')).length;
    }
  },
  watch: {
    searchQuery() {
      // Reset to first page when search changes
      this.currentPage = 1;
    }
  },
  created() {
    this.checkLegacyOnLoad();
  },
  methods: {
    getStatusClass(hasField, length) {
      if (!hasField) return '';
      if (length < 50 || length > 160) {
        return 'k-meta-kit-status-warning';
      }
      return 'k-meta-kit-status-success';
    },
    async refreshPages() {
      this.isLoadingPages = true;
      try {
        const response = await this.$api.get('meta-kit/pages');
        if (response.status === 'success') {
          this.pagesData = response.data;
        }
      } catch (error) {
        window.panel.notification.error('Failed to refresh pages');
      } finally {
        this.isLoadingPages = false;
      }
    },
    async generateDescription(pageId) {
      try {
        const response = await this.$api.post('meta-kit/generate-description', {pageId});
        if (response.status === 'success') {
          window.panel.notification.success(response.message || 'Description generated');
          await this.refreshPages();
        } else {
          window.panel.notification.error(response.message || 'Failed to generate description');
        }
      } catch (error) {
        let errorMessage = 'Failed to generate description';
        if (error.message) {
          errorMessage += `: ${error.message}`;
        } else if (error.error) {
          errorMessage += `: ${error.error}`;
        }
        window.panel.notification.error(errorMessage);
        console.error('Generation error:', error);
      }
    },
    async generateAllDescriptions() {
      if (!confirm('This will generate descriptions for all pages without one. Continue?')) {
        return;
      }

      this.isGeneratingAll = true;
      
      // Show loading notification
      const loadingNotification = window.panel.notification.open({
        message: 'Generating descriptions with AI...',
        type: 'info',
        timeout: 0 // Don't auto-close
      });
      
      try {
        const response = await this.$api.post('meta-kit/generate-all');
        
        // Close loading notification
        if (loadingNotification && loadingNotification.close) {
          loadingNotification.close();
        }
        
        if (response.status === 'success') {
          const details = `Generated: ${response.generated || 0}, Skipped: ${response.skipped || 0}, Failed: ${response.failed || 0}`;
          window.panel.notification.success(`${response.message || 'Generation completed!'} ${details}`);
          await this.refreshPages();
        } else {
          window.panel.notification.error(response.message || 'Generation failed');
        }
      } catch (error) {
        // Close loading notification
        if (loadingNotification && loadingNotification.close) {
          loadingNotification.close();
        }
        
        // Extract detailed error message
        let errorMessage = 'Failed to generate descriptions';
        if (error.message) {
          errorMessage += `: ${error.message}`;
        } else if (error.error) {
          errorMessage += `: ${error.error}`;
        } else if (typeof error === 'string') {
          errorMessage += `: ${error}`;
        }
        
        window.panel.notification.error(errorMessage);
        console.error('Generation error details:', error);
      } finally {
        this.isGeneratingAll = false;
      }
    },
    async reloadLegacyData(showLoading = true) {
      if (showLoading) {
        this.isLoadingLegacy = true;
      }
      try {
        const response = await this.$api.get('meta-kit/detect-legacy');
        if (response.status === 'success') {
          // Use $set to ensure Vue reactivity picks up the change
          this.$set(this, 'legacyPages', response.pages || []);

          // Pre-select 'legacy' for all fields in legacy pages
          this.legacyPages.forEach(page => {
            Object.keys(page.fields).forEach(fieldName => {
              this.setFieldChoice(page.id, fieldName, 'legacy');
            });
          });
        }
      } catch (error) {
        window.panel.notification.error('Failed to detect legacy metadata');
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
      if (!confirm('This will migrate all legacy SEO fields (metatitle, metadescription, etc.) into the seo field for all pages. Continue?')) {
        return;
      }
      
      this.isMigratingAll = true;
      
      // Show loading notification
      const loadingNotification = window.panel.notification.open({
        message: 'Migrating legacy fields to blocks...',
        type: 'info',
        timeout: 0 // Don't auto-close
      });
      
      try {
        const response = await this.$api.post('meta-kit/convert-all-to-blocks');
        
        // Close loading notification
        if (loadingNotification && loadingNotification.close) {
          loadingNotification.close();
        }
        
        if (response.status === 'success') {
          const details = `Converted: ${response.converted || 0}, Skipped: ${response.skipped || 0}, Errors: ${response.errors || 0}`;
          window.panel.notification.success(`Migration completed! ${details}`);

          // Refresh the main pages list
          await this.refreshPages();

          // Refresh the legacy dialog to show updated current values
          // Clear field choices so they reset to default (legacy selected)
          this.fieldChoices = {};

          // Reload legacy data without showing loading spinner (keeps dialog content visible)
          await this.reloadLegacyData(false);

          // Force Vue to re-render the dialog
          this.$forceUpdate();
        } else {
          const errorMessage = response.message || 'Migration failed';
          const errorDetails = response.errors ? ` (${response.errors} errors)` : '';
          window.panel.notification.error(errorMessage + errorDetails);
        }
      } catch (error) {
        // Close loading notification
        if (loadingNotification && loadingNotification.close) {
          loadingNotification.close();
        }
        
        // Extract detailed error message
        let errorMessage = 'Migration failed';
        if (error.message) {
          errorMessage += `: ${error.message}`;
        } else if (error.error) {
          errorMessage += `: ${error.error}`;
        } else if (typeof error === 'string') {
          errorMessage += `: ${error}`;
        }
        
        window.panel.notification.error(errorMessage);
        console.error('Migration error details:', error);
      } finally {
        this.isMigratingAll = false;
      }
    },
    async checkLegacyOnLoad() {
      return; // disabled for now
      const dismissed = sessionStorage.getItem('metaKitLegacyDismissed');
      if (dismissed) return;

      try {
        const response = await this.$api.get('meta-kit/detect-legacy');
        if (response.status === 'success' && response.found > 0) {
          this.legacyDetection.show = true;
          this.legacyDetection.found = response.found;
        }
      } catch (error) {
        // Silent fail
      }
    },
    dismissLegacyWarning() {
      this.legacyDetection.show = false;
      sessionStorage.setItem('metaKitLegacyDismissed', 'true');
    },
    showLegacyDialog() {
      this.detectLegacyMetadata();
    },
    async convertLegacyPage(pageId) {
      try {
        const response = await this.$api.post('meta-kit/convert-legacy', {pageId});
        if (response.status === 'success' || response.status === 'info') {
          window.panel.notification.success(response.message);
          await this.detectLegacyMetadata();
          await this.refreshPages();
        } else {
          window.panel.notification.error(response.message);
        }
      } catch (error) {
        window.panel.notification.error('Failed to convert legacy data');
      }
    },
    formatFieldName(fieldName) {
      const names = {
        'metaTitle': 'Meta Title',
        'metaDescription': 'Meta Description',
        'ogImage': 'OG Image'
      };
      return names[fieldName] || fieldName;
    },
    formatFieldValue(value) {
      if (typeof value === 'string') {
        return value;
      }
      if (Array.isArray(value)) {
        return `${value.length} image(s)`;
      }
      if (typeof value === 'object') {
        return 'File';
      }
      return String(value);
    },
    getFieldChoice(pageId, fieldName) {
      return this.fieldChoices[pageId]?.[fieldName]?.choice || null;
    },
    setFieldChoice(pageId, fieldName, choice) {
      if (!this.fieldChoices[pageId]) {
        this.$set(this.fieldChoices, pageId, {});
      }
      if (!this.fieldChoices[pageId][fieldName]) {
        this.$set(this.fieldChoices[pageId], fieldName, {});
      }
      this.$set(this.fieldChoices[pageId][fieldName], 'choice', choice);

      // If switching to manual edit, prefill with current or legacy value if no manual value exists
      if (choice === 'manual') {
        const existingManualValue = this.getManualValue(pageId, fieldName);

        // Only prefill if there's no existing manual value
        if (!existingManualValue) {
          // Find the page
          let page = this.legacyPages.find(p => p.id === pageId);
          if (!page) {
            page = this.allPagesData.find(p => p.id === pageId);
          }
          if (!page) {
            page = this.currentEditPage;
          }

          if (page) {
            let prefillValue = '';

            // Try current value first
            if (fieldName === 'metaTitle' && page.metaTitle) {
              prefillValue = page.metaTitle;
            } else if (fieldName === 'metaDescription' && page.metaDescription) {
              prefillValue = page.metaDescription;
            }
            // If no current value, try legacy value
            else if (page.legacy && page.legacy[fieldName]) {
              prefillValue = page.legacy[fieldName];
            }

            // Set the prefill value
            if (prefillValue) {
              this.setManualValue(pageId, fieldName, prefillValue);
            }
          }
        }
      }
    },
    getManualValue(pageId, fieldName) {
      return this.fieldChoices[pageId]?.[fieldName]?.manualValue || '';
    },
    setManualValue(pageId, fieldName, value) {
      if (!this.fieldChoices[pageId]) {
        this.$set(this.fieldChoices, pageId, {});
      }
      if (!this.fieldChoices[pageId][fieldName]) {
        this.$set(this.fieldChoices[pageId], fieldName, {});
      }
      this.$set(this.fieldChoices[pageId][fieldName], 'manualValue', value);
    },
    getEditableValue(pageId, fieldName, currentValue) {
      const manualValue = this.getManualValue(pageId, fieldName);
      return manualValue || currentValue || '';
    },
    hasFieldChanged(pageId, fieldName, currentValue, legacyValue) {
      const choice = this.getFieldChoice(pageId, fieldName);

      // No choice selected, no change
      if (!choice) return false;

      // Legacy choice always counts as a change
      if (choice === 'legacy') return true;

      // For keep/current/ai, check if manual value exists and differs from current
      const manualValue = this.getManualValue(pageId, fieldName);
      if (choice === 'keep' || choice === 'current') {
        // Only changed if there's a manual value that differs from current
        return manualValue && manualValue !== currentValue;
      }

      // AI choice - changed if there's generated content
      if (choice === 'ai') {
        return !!manualValue;
      }

      return false;
    },
    isGeneratingField(pageId, fieldName) {
      return this.generatingFields[pageId]?.[fieldName] || false;
    },
    async generateFieldAI(pageId, fieldName) {
      // Set AI as the choice
      this.setFieldChoice(pageId, fieldName, 'ai');

      // Mark as generating
      if (!this.generatingFields[pageId]) {
        this.$set(this.generatingFields, pageId, {});
      }
      this.$set(this.generatingFields[pageId], fieldName, true);

      try {
        const response = await this.$api.post('meta-kit/generate-field', {
          pageId,
          fieldName
        });
        if (response.status === 'success' && response.content) {
          this.setManualValue(pageId, fieldName, response.content);
          window.panel.notification.success('AI content generated successfully');
        } else {
          window.panel.notification.error(response.message || 'Failed to generate content');
        }
      } catch (error) {
        window.panel.notification.error('Failed to generate content');
      } finally {
        this.$set(this.generatingFields[pageId], fieldName, false);
      }
    },
    async applySingleField(pageId, fieldName) {
      // Find page in either legacy, all pages, or current edit dialog
      let page = this.legacyPages.find(p => p.id === pageId);
      if (!page) {
        page = this.allPagesData.find(p => p.id === pageId);
      }
      if (!page && this.currentEditPage && this.currentEditPage.id === pageId) {
        page = this.currentEditPage;
      }
      if (!page) return;

      const choice = this.getFieldChoice(pageId, fieldName);
      if (!choice) {
        window.panel.notification.error('Please select an option first');
        return;
      }

      let value;
      if (choice === 'legacy') {
        // Check both formats (legacy dialog and all pages dialog)
        if (page.fields && page.fields[fieldName]) {
          value = page.fields[fieldName];
        } else if (page.legacy && page.legacy[fieldName]) {
          value = page.legacy[fieldName];
        }

        // Ensure we got a value
        if (!value) {
          window.panel.notification.error('Legacy value not found');
          return;
        }
      } else if (choice === 'current' || choice === 'keep' || choice === 'ai') {
        // For editable fields (current/keep/ai), check if there's an edited value
        const manualValue = this.getManualValue(pageId, fieldName);
        if (manualValue) {
          // Use the edited/AI generated value
          value = manualValue;
        } else {
          // No manual value - use existing current value
          if (page.fields && page.current) {
            value = page.current[fieldName];
          } else if (page[fieldName]) {
            value = page[fieldName];
          } else {
            window.panel.notification.success('No changes to apply');
            return;
          }
        }
      }

      try {
        const response = await this.$api.post('meta-kit/apply-single-field', {
          pageId,
          fieldName,
          value
        });

        if (response.status === 'success') {
          window.panel.notification.success(`${this.formatFieldName(fieldName)} updated successfully`);
          await this.refreshPages();

          // Clear the manual value completely
          if (this.fieldChoices[pageId] && this.fieldChoices[pageId][fieldName]) {
            this.$set(this.fieldChoices[pageId][fieldName], 'manualValue', '');
          }

          // Reset the choice to 'keep' to show current value
          this.setFieldChoice(pageId, fieldName, 'keep');

          // Update the field in-place without reloading the whole dialog
          const pageInAllPages = this.allPagesData.find(p => p.id === pageId);
          if (pageInAllPages) {
            // Update the specific field with the new value
            this.$set(pageInAllPages, fieldName, value);

            // Update the "has" flags
            if (fieldName === 'metaTitle') {
              this.$set(pageInAllPages, 'hasMetaTitle', value && value.length > 0);
              this.$set(pageInAllPages, 'metaTitleLength', value ? value.length : 0);
            } else if (fieldName === 'metaDescription') {
              this.$set(pageInAllPages, 'hasMetaDescription', value && value.length > 0);
              this.$set(pageInAllPages, 'metaDescriptionLength', value ? value.length : 0);
            } else if (fieldName === 'ogImage') {
              this.$set(pageInAllPages, 'hasOgImage', value && value.length > 0);
            }

            // Clear legacy data for this field if it exists
            if (pageInAllPages.legacy && pageInAllPages.legacy[fieldName]) {
              delete pageInAllPages.legacy[fieldName];
            }
          }

          // Also update legacy pages data if this is from the legacy dialog
          const pageInLegacy = this.legacyPages.find(p => p.id === pageId);
          if (pageInLegacy) {
            // Update the current value to match what was just applied
            if (!pageInLegacy.current) {
              this.$set(pageInLegacy, 'current', {});
            }
            this.$set(pageInLegacy.current, fieldName, value);

            // Don't remove the field - just let the choice switch to 'keep'
            // and the button will disappear since hasFieldChanged will be false
          }

          // Force Vue to re-evaluate the button state
          this.$forceUpdate();

          // Don't reload the legacy dialog - just update in place
        } else {
          window.panel.notification.error(response.message || 'Failed to update field');
        }
      } catch (error) {
        let errorMessage = 'Failed to update field';
        if (error.message) {
          errorMessage += `: ${error.message}`;
        } else if (error.error) {
          errorMessage += `: ${error.error}`;
        }
        window.panel.notification.error(errorMessage);
        console.error('Field update error:', error);
      }
    },
    async showAllPagesDialog() {
      this.$refs.allPagesDialog.open();
      await this.loadAllPages();
    },
    async loadAllPages() {
      this.isLoadingAllPages = true;
      try {
        const response = await this.$api.get('meta-kit/pages-with-content');
        if (response.status === 'success') {
          this.allPagesData = response.data;
          // Pre-select 'keep' (current) for all fields
          this.allPagesData.forEach(page => {
            this.setFieldChoice(page.id, 'metaTitle', 'keep');
            this.setFieldChoice(page.id, 'metaDescription', 'keep');
          });
        }
      } catch (error) {
        window.panel.notification.error('Failed to load pages');
      } finally {
        this.isLoadingAllPages = false;
      }
    },
    editSinglePage(pageId) {
      // Navigate to the page editor in the panel
      const page = this.allPagesData.find(p => p.id === pageId);
      if (page && page.panelUrl) {
        window.panel.view.open(page.panelUrl);
      }
    },
    editPageInPanel(panelUrl) {
      // Navigate to the page editor in the panel
      if (panelUrl) {
        window.panel.view.open(panelUrl);
      }
    },
    async editSinglePageMetadata(pageId) {
      this.$refs.singlePageDialog.open();
      this.isLoadingSinglePage = true;

      try {
        const response = await this.$api.get('meta-kit/single-page', {pageId});
        if (response.status === 'success') {
          this.currentEditPage = response.data;
          // Pre-select 'keep' (current) for all fields
          this.setFieldChoice(pageId, 'metaTitle', 'keep');
          this.setFieldChoice(pageId, 'metaDescription', 'keep');
        }
      } catch (error) {
        window.panel.notification.error('Failed to load page');
      } finally {
        this.isLoadingSinglePage = false;
      }
    },
    async applySingleFieldAndClose(pageId, fieldName) {
      await this.applySingleField(pageId, fieldName);

      // Reload the current page data to show updated values
      if (this.currentEditPage && this.currentEditPage.id === pageId) {
        try {
          const response = await this.$api.get('meta-kit/single-page', {pageId});
          if (response.status === 'success') {
            this.currentEditPage = response.data;
            // Re-apply the 'keep' choice for the updated page
            this.setFieldChoice(pageId, 'metaTitle', 'keep');
            this.setFieldChoice(pageId, 'metaDescription', 'keep');
          }
        } catch (error) {
          // Silent fail
        }
      }
    },
    openPageSeoTab(page) {
      if (!page) return;

      // Close the dialog
      this.$refs.singlePageDialog.close();

      // Navigate to the page with SEO tab
      // Panel URL format: /panel/pages/{id}
      const pageUrl = page.panelUrl;

      // Open the page - Kirby will handle the tab navigation
      window.panel.view.open(pageUrl);
    },
    goToLanguage(langCode) {
      if (langCode === this.language) return;

      // Force full page reload with new language
      const baseUrl = window.location.origin + window.location.pathname.split('?')[0];
      window.location.href = baseUrl + '?language=' + langCode;
    },

    // Pagination methods
    changePageSize(newSize) {
      this.pageSize = parseInt(newSize);
      this.currentPage = 1; // Reset to first page
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
      this.paginatedPages.forEach(page => {
        const index = this.selectedPages.indexOf(page.id);
        if (allSelected) {
          // Deselect all on current page
          if (index > -1) {
            this.selectedPages.splice(index, 1);
          }
        } else {
          // Select all on current page
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

      // Load full data for selected pages from API
      this.isLoadingAllPages = true;
      try {
        const response = await this.$api.get('meta-kit/pages-with-content', {
          pageIds: this.selectedPages
        });
        if (response.status === 'success') {
          // Filter to only selected pages
          this.allPagesData = response.data.filter(p => this.selectedPages.includes(p.id));
          // Pre-select 'keep' (current) for all fields
          this.allPagesData.forEach(page => {
            this.setFieldChoice(page.id, 'metaTitle', 'keep');
            this.setFieldChoice(page.id, 'metaDescription', 'keep');
          });
        }
      } catch (error) {
        window.panel.notification.error('Failed to load selected pages');
      } finally {
        this.isLoadingAllPages = false;
      }
    }
  }
};
</script>
