<template>
  <k-panel-inside class="k-meta-kit-view">
    <!-- Legacy Detection Warning -->
    <div v-if="legacyDetection.show && legacyDetection.found > 0" class="k-meta-kit-warning">
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
        <p>{{ pages.length }}</p>
      </div>
      <div class="k-meta-kit-stats-card">
        <h3>With Description</h3>
        <p>{{ pagesWithDescription }}</p>
      </div>
      <div class="k-meta-kit-stats-card">
        <h3>With OG Image</h3>
        <p>{{ pagesWithOgImage }}</p>
      </div>
      <div class="k-meta-kit-stats-card">
        <h3>NoIndex</h3>
        <p>{{ pagesNoIndex }}</p>
      </div>
    </div>

    <!-- Actions -->
    <div class="k-meta-kit-actions">
      <k-button-group>
        <k-button
          icon="sparkling"
          :disabled="isGeneratingAll"
          :progress="isGeneratingAll"
          @click="generateAllDescriptions"
        >
          Generate All Missing Descriptions
        </k-button>
        <k-button icon="refresh" @click="refreshPages">Refresh</k-button>
        <k-button icon="edit" @click="showAllPagesDialog">Edit All Pages</k-button>
        <k-button icon="download" @click="detectLegacyMetadata">Detect Legacy Data</k-button>
      </k-button-group>
    </div>

    <!-- Pages Table -->
    <div class="k-meta-kit-table">
      <table>
        <thead>
        <tr>
          <th>#</th>
          <th>Page</th>
          <th>Template</th>
          <th>Meta Title</th>
          <th>Description</th>
          <th>OG Image</th>
          <th>NoIndex</th>
          <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="(page, index) in pagesData" :key="page.id">
          <td>{{ index + 1 }}</td>
          <td>
            <a :href="page.panelUrl" class="k-link">{{ page.title }}</a>
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
            <k-icon v-if="page.noIndex" type="check" class="k-meta-kit-icon-warning"/>
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

    <!-- Legacy Data Dialog -->
    <k-dialog ref="legacyDialog" size="huge" cancelButton="" submitButton="">
      <k-headline>Legacy SEO Metadata</k-headline>

      <div v-if="isLoadingLegacy" class="k-meta-kit-loading">
        <k-icon class="k-meta-kit-spinner" type="loader"/>
        <span>Scanning for legacy metadata...</span>
      </div>

      <div v-else-if="legacyPages.length > 0" class="k-meta-kit-legacy-list">
        <p>Found {{ legacyPages.length }} pages with legacy SEO fields:</p>

        <div v-for="page in legacyPages" :key="page.id" class="k-meta-kit-legacy-item">
          <div class="k-meta-kit-legacy-item-header">
            <strong>{{ page.title }}</strong>
            <k-button
              icon="download"
              size="sm"
              @click="convertLegacyPage(page.id)"
            >
              Apply Legacy
            </k-button>
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
                    :theme="getFieldChoice(page.id, key) === 'current' ? 'positive' : ''"
                    @click="setFieldChoice(page.id, key, 'current')"
                  >
                    Current
                  </k-button>
                  <k-button
                    size="xs"
                    :theme="getFieldChoice(page.id, key) === 'manual' ? 'positive' : ''"
                    @click="setFieldChoice(page.id, key, 'manual')"
                  >
                    Manual Edit
                  </k-button>
                  <k-button
                    v-if="key !== 'ogImage'"
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

                  <div v-else-if="getFieldChoice(page.id, key) === 'current' && page.current && page.current[key]"
                       class="k-meta-kit-legacy-field-option">
                    <span class="k-meta-kit-legacy-badge">Current Value</span>
                    <span class="k-meta-kit-legacy-field-value">{{ formatFieldValue(page.current[key]) }}</span>
                  </div>

                  <div v-else-if="getFieldChoice(page.id, key) === 'manual'" class="k-meta-kit-legacy-field-option">
                    <span class="k-meta-kit-legacy-badge">Manual Entry</span>
                    <k-input
                      :value="getManualValue(page.id, key)"
                      @input="setManualValue(page.id, key, $event)"
                      :placeholder="`Enter ${formatFieldName(key)}`"
                      type="textarea"
                    />
                  </div>

                  <div v-else-if="getFieldChoice(page.id, key) === 'ai'" class="k-meta-kit-legacy-field-option">
                    <span class="k-meta-kit-legacy-badge k-meta-kit-legacy-badge-ai">AI Generated</span>
                    <span v-if="isGeneratingField(page.id, key)" class="k-meta-kit-legacy-field-generating">
                      <k-icon class="k-meta-kit-spinner" type="loader"/>
                      Generating...
                    </span>
                    <span v-else-if="getManualValue(page.id, key)" class="k-meta-kit-legacy-field-value">
                      {{ getManualValue(page.id, key) }}
                    </span>
                    <span v-else class="k-meta-kit-legacy-field-value-empty">
                      Click AI Generate to create content
                    </span>
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
                  v-if="getFieldChoice(page.id, key)"
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
                    size="xs"
                    :theme="getFieldChoice(page.id, 'metaTitle') === 'manual' ? 'positive' : ''"
                    @click="setFieldChoice(page.id, 'metaTitle', 'manual')"
                  >
                    Manual Edit
                  </k-button>
                  <k-button
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
                    <span class="k-meta-kit-legacy-badge">Current Value</span>
                    <span v-if="page.hasMetaTitle" class="k-meta-kit-legacy-field-value">
                      {{ page.metaTitle || '—' }}
                      <span class="k-meta-kit-field-length"
                            :class="getStatusClass(page.hasMetaTitle, page.metaTitleLength)">
                        ({{ page.metaTitleLength }} chars)
                      </span>
                    </span>
                    <span v-else class="k-meta-kit-legacy-field-value-empty">No meta title set</span>
                  </div>

                  <div v-else-if="getFieldChoice(page.id, 'metaTitle') === 'manual'"
                       class="k-meta-kit-legacy-field-option">
                    <span class="k-meta-kit-legacy-badge">Manual Entry</span>
                    <k-input
                      :value="getManualValue(page.id, 'metaTitle')"
                      @input="setManualValue(page.id, 'metaTitle', $event)"
                      placeholder="Enter Meta Title"
                      type="text"
                    />
                  </div>

                  <div v-else-if="getFieldChoice(page.id, 'metaTitle') === 'ai'" class="k-meta-kit-legacy-field-option">
                    <span class="k-meta-kit-legacy-badge k-meta-kit-legacy-badge-ai">AI Generated</span>
                    <span v-if="isGeneratingField(page.id, 'metaTitle')" class="k-meta-kit-legacy-field-generating">
                      <k-icon class="k-meta-kit-spinner" type="loader"/>
                      Generating...
                    </span>
                    <span v-else-if="getManualValue(page.id, 'metaTitle')" class="k-meta-kit-legacy-field-value">
                      {{ getManualValue(page.id, 'metaTitle') }}
                    </span>
                    <span v-else class="k-meta-kit-legacy-field-value-empty">
                      Click AI Generate to create content
                    </span>
                  </div>
                </div>

                <!-- Apply button -->
                <k-button
                  v-if="getFieldChoice(page.id, 'metaTitle') && getFieldChoice(page.id, 'metaTitle') !== 'keep'"
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
                    size="xs"
                    :theme="getFieldChoice(page.id, 'metaDescription') === 'manual' ? 'positive' : ''"
                    @click="setFieldChoice(page.id, 'metaDescription', 'manual')"
                  >
                    Manual Edit
                  </k-button>
                  <k-button
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
                    <span class="k-meta-kit-legacy-badge">Current Value</span>
                    <span v-if="page.hasMetaDescription" class="k-meta-kit-legacy-field-value">
                      {{ page.metaDescription || '—' }}
                      <span class="k-meta-kit-field-length"
                            :class="getStatusClass(page.hasMetaDescription, page.metaDescriptionLength)">
                        ({{ page.metaDescriptionLength }} chars)
                      </span>
                    </span>
                    <span v-else class="k-meta-kit-legacy-field-value-empty">No meta description set</span>
                  </div>

                  <div v-else-if="getFieldChoice(page.id, 'metaDescription') === 'manual'"
                       class="k-meta-kit-legacy-field-option">
                    <span class="k-meta-kit-legacy-badge">Manual Entry</span>
                    <k-input
                      :value="getManualValue(page.id, 'metaDescription')"
                      @input="setManualValue(page.id, 'metaDescription', $event)"
                      placeholder="Enter Meta Description"
                      type="textarea"
                    />
                  </div>

                  <div v-else-if="getFieldChoice(page.id, 'metaDescription') === 'ai'"
                       class="k-meta-kit-legacy-field-option">
                    <span class="k-meta-kit-legacy-badge k-meta-kit-legacy-badge-ai">AI Generated</span>
                    <span v-if="isGeneratingField(page.id, 'metaDescription')"
                          class="k-meta-kit-legacy-field-generating">
                      <k-icon class="k-meta-kit-spinner" type="loader"/>
                      Generating...
                    </span>
                    <span v-else-if="getManualValue(page.id, 'metaDescription')" class="k-meta-kit-legacy-field-value">
                      {{ getManualValue(page.id, 'metaDescription') }}
                    </span>
                    <span v-else class="k-meta-kit-legacy-field-value-empty">
                      Click AI Generate to create content
                    </span>
                  </div>
                </div>

                <!-- Apply button -->
                <k-button
                  v-if="getFieldChoice(page.id, 'metaDescription') && getFieldChoice(page.id, 'metaDescription') !== 'keep'"
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
    <k-dialog ref="singlePageDialog" size="large" cancelButton="Close" submitButton="">
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
                size="xs"
                :theme="getFieldChoice(currentEditPage.id, 'metaTitle') === 'manual' ? 'positive' : ''"
                @click="setFieldChoice(currentEditPage.id, 'metaTitle', 'manual')"
              >
                Manual Edit
              </k-button>
              <k-button
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
                <span class="k-meta-kit-legacy-badge">Current Value</span>
                <span v-if="currentEditPage.hasMetaTitle" class="k-meta-kit-legacy-field-value">
                  {{ currentEditPage.metaTitle || '—' }}
                  <span class="k-meta-kit-field-length"
                        :class="getStatusClass(currentEditPage.hasMetaTitle, currentEditPage.metaTitleLength)">
                    ({{ currentEditPage.metaTitleLength }} chars)
                  </span>
                </span>
                <span v-else class="k-meta-kit-legacy-field-value-empty">No meta title set</span>
              </div>

              <div v-else-if="getFieldChoice(currentEditPage.id, 'metaTitle') === 'manual'"
                   class="k-meta-kit-legacy-field-option">
                <span class="k-meta-kit-legacy-badge">Manual Entry</span>
                <k-input
                  :value="getManualValue(currentEditPage.id, 'metaTitle')"
                  @input="setManualValue(currentEditPage.id, 'metaTitle', $event)"
                  placeholder="Enter Meta Title"
                  type="text"
                />
              </div>

              <div v-else-if="getFieldChoice(currentEditPage.id, 'metaTitle') === 'ai'"
                   class="k-meta-kit-legacy-field-option">
                <span class="k-meta-kit-legacy-badge k-meta-kit-legacy-badge-ai">AI Generated</span>
                <span v-if="isGeneratingField(currentEditPage.id, 'metaTitle')"
                      class="k-meta-kit-legacy-field-generating">
                  <k-icon class="k-meta-kit-spinner" type="loader"/>
                  Generating...
                </span>
                <span v-else-if="getManualValue(currentEditPage.id, 'metaTitle')" class="k-meta-kit-legacy-field-value">
                  {{ getManualValue(currentEditPage.id, 'metaTitle') }}
                </span>
                <span v-else class="k-meta-kit-legacy-field-value-empty">
                  Click AI Generate to create content
                </span>
              </div>
            </div>

            <!-- Apply button -->
            <k-button
              v-if="getFieldChoice(currentEditPage.id, 'metaTitle') && getFieldChoice(currentEditPage.id, 'metaTitle') !== 'keep'"
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
                size="xs"
                :theme="getFieldChoice(currentEditPage.id, 'metaDescription') === 'manual' ? 'positive' : ''"
                @click="setFieldChoice(currentEditPage.id, 'metaDescription', 'manual')"
              >
                Manual Edit
              </k-button>
              <k-button
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
                <span class="k-meta-kit-legacy-badge">Current Value</span>
                <span v-if="currentEditPage.hasMetaDescription" class="k-meta-kit-legacy-field-value">
                  {{ currentEditPage.metaDescription || '—' }}
                  <span class="k-meta-kit-field-length"
                        :class="getStatusClass(currentEditPage.hasMetaDescription, currentEditPage.metaDescriptionLength)">
                    ({{ currentEditPage.metaDescriptionLength }} chars)
                  </span>
                </span>
                <span v-else class="k-meta-kit-legacy-field-value-empty">No meta description set</span>
              </div>

              <div v-else-if="getFieldChoice(currentEditPage.id, 'metaDescription') === 'manual'"
                   class="k-meta-kit-legacy-field-option">
                <span class="k-meta-kit-legacy-badge">Manual Entry</span>
                <k-input
                  :value="getManualValue(currentEditPage.id, 'metaDescription')"
                  @input="setManualValue(currentEditPage.id, 'metaDescription', $event)"
                  placeholder="Enter Meta Description"
                  type="textarea"
                  buttons="false"
                />
              </div>

              <div v-else-if="getFieldChoice(currentEditPage.id, 'metaDescription') === 'ai'"
                   class="k-meta-kit-legacy-field-option">
                <span class="k-meta-kit-legacy-badge k-meta-kit-legacy-badge-ai">AI Generated</span>
                <span v-if="isGeneratingField(currentEditPage.id, 'metaDescription')"
                      class="k-meta-kit-legacy-field-generating">
                  <k-icon class="k-meta-kit-spinner" type="loader"/>
                  Generating...
                </span>
                <span v-else-if="getManualValue(currentEditPage.id, 'metaDescription')"
                      class="k-meta-kit-legacy-field-value">
                  {{ getManualValue(currentEditPage.id, 'metaDescription') }}
                </span>
                <span v-else class="k-meta-kit-legacy-field-value-empty">
                  Click AI Generate to create content
                </span>
              </div>
            </div>

            <!-- Apply button -->
            <k-button
              v-if="getFieldChoice(currentEditPage.id, 'metaDescription') && getFieldChoice(currentEditPage.id, 'metaDescription') !== 'keep'"
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
    pages: Array
  },
  data() {
    return {
      isLoadingPages: false,
      isGeneratingAll: false,
      isLoadingLegacy: false,
      isLoadingAllPages: false,
      isLoadingSinglePage: false,
      pagesData: this.pages || [],
      allPagesData: [],
      legacyPages: [],
      currentEditPage: null,
      legacyDetection: {
        show: false,
        found: 0
      },
      fieldChoices: {}, // { pageId: { fieldName: 'legacy|current|manual|ai', manualValue: '...' } }
      generatingFields: {} // { pageId: { fieldName: true } }
    };
  },
  computed: {
    pagesWithDescription() {
      return this.pagesData.filter(p => p.hasMetaDescription).length;
    },
    pagesWithOgImage() {
      return this.pagesData.filter(p => p.hasOgImage).length;
    },
    pagesNoIndex() {
      return this.pagesData.filter(p => p.noIndex).length;
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
          window.panel.notification.success(response.message);
          await this.refreshPages();
        } else {
          window.panel.notification.error(response.message);
        }
      } catch (error) {
        window.panel.notification.error('Failed to generate description');
      }
    },
    async generateAllDescriptions() {
      if (!confirm('This will generate descriptions for all pages without one. Continue?')) {
        return;
      }

      this.isGeneratingAll = true;
      try {
        const response = await this.$api.post('meta-kit/generate-all');
        if (response.status === 'success') {
          window.panel.notification.success(response.message);
          await this.refreshPages();
        } else {
          window.panel.notification.error(response.message);
        }
      } catch (error) {
        window.panel.notification.error('Failed to generate descriptions');
      } finally {
        this.isGeneratingAll = false;
      }
    },
    async detectLegacyMetadata() {
      this.$refs.legacyDialog.open();
      this.isLoadingLegacy = true;
      try {
        const response = await this.$api.get('meta-kit/detect-legacy');
        if (response.status === 'success') {
          this.legacyPages = response.pages || [];
        }
      } catch (error) {
        window.panel.notification.error('Failed to detect legacy metadata');
      } finally {
        this.isLoadingLegacy = false;
      }
    },
    async checkLegacyOnLoad() {
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
      } else if (choice === 'current' || choice === 'keep') {
        // Check if from legacy dialog (has fields property)
        if (page.fields) {
          value = page.current[fieldName];
        }
        // Check if from single page or all pages dialog (values stored directly)
        else if (page[fieldName]) {
          value = page[fieldName];
        } else {
          // Already at current value or no value exists, no change needed
          window.panel.notification.success('Already using current value');
          return;
        }
      } else if (choice === 'manual' || choice === 'ai') {
        value = this.getManualValue(pageId, fieldName);
        if (false && !value) {
          window.panel.notification.error('Please enter a value');
          return;
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

          // Refresh the appropriate dialog
          if (this.legacyPages.find(p => p.id === pageId)) {
            await this.detectLegacyMetadata();
          }
          if (this.allPagesData.find(p => p.id === pageId)) {
            await this.loadAllPages();
          }
        } else {
          window.panel.notification.error(response.message);
        }
      } catch (error) {
        window.panel.notification.error('Failed to update field');
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
        }
      } catch (error) {
        window.panel.notification.error('Failed to load page');
      } finally {
        this.isLoadingSinglePage = false;
      }
    },
    async applySingleFieldAndClose(pageId, fieldName) {
      await this.applySingleField(pageId, fieldName);

      // Reset the choice to 'keep' which hides the apply button
      this.setFieldChoice(pageId, fieldName, 'keep');

      // Clear any manual values
      if (this.fieldChoices[pageId]) {
        this.$set(this.fieldChoices[pageId], fieldName + '_manual', '');
      }

      // Reload the current page data to show updated values
      if (this.currentEditPage && this.currentEditPage.id === pageId) {
        try {
          const response = await this.$api.get('meta-kit/single-page', {pageId});
          if (response.status === 'success') {
            this.currentEditPage = response.data;
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
    }
  }
};
</script>
<style>
.k-meta-kit-view {
  /* Meta Kit View */

  .k-meta-kit-view {
    padding: 1.5rem;
    color: var(--color-text);
  }

  .k-meta-kit-warning {
    margin-bottom: 1.5rem;
  }

  .k-meta-kit-warning .k-box {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
  }

  .k-meta-kit-warning .k-box > span {
    flex: 1;
  }

  .k-meta-kit-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
  }

  .k-meta-kit-stats-card {
    background: var(--color-back);
    border: 1px solid var(--color-border);
    border-radius: var(--rounded);
    padding: 1.5rem;
    text-align: center;
  }

  .k-meta-kit-stats-card h3 {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-gray-600);
    margin: 0 0 0.5rem 0;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  .k-meta-kit-stats-card p {
    font-size: 2rem;
    font-weight: 700;
    color: var(--color-text);
    margin: 0;
  }

  .k-panel[data-color-scheme="dark"] .k-meta-kit-stats-card h3 {
    color: var(--color-gray-400);
  }

  .k-panel[data-color-scheme="dark"] .k-meta-kit-stats-card p {
    color: var(--color-white);
  }

  .k-meta-kit-actions {
    margin-bottom: 1.5rem;
  }

  .k-meta-kit-table {
    background: var(--color-back);
    border: 1px solid var(--color-border);
    border-radius: var(--rounded);
    overflow-x: auto;
  }

  .k-meta-kit-table table {
    width: 100%;
    border-collapse: collapse;
    min-width: 900px;
  }

  .k-meta-kit-table th:first-child,
  .k-meta-kit-table td:first-child {
    width: 50px;
    text-align: center;
  }

  .k-meta-kit-table th:nth-child(2) {
    min-width: 200px;
  }

  .k-meta-kit-table th:nth-child(3) {
    width: 120px;
  }

  .k-meta-kit-table th:nth-child(4),
  .k-meta-kit-table th:nth-child(5) {
    width: 100px;
  }

  .k-meta-kit-table th:nth-child(6),
  .k-meta-kit-table th:nth-child(7) {
    width: 80px;
  }

  .k-meta-kit-table th:nth-child(8) {
    width: 120px;
  }

  .k-meta-kit-table-actions {
    display: flex;
    gap: 0.25rem;
    justify-content: center;
    align-items: center;
  }

  .k-meta-kit-table th {
    background: var(--color-back);
    padding: 0.75rem 1rem;
    text-align: left;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--color-text);
    border-bottom: 1px solid var(--color-border);
  }

  .k-meta-kit-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--color-border);
    font-size: 0.875rem;
    color: var(--color-text);
    background: var(--color-back);
  }

  .k-meta-kit-table tbody tr:last-child td {
    border-bottom: none;
  }

  .k-meta-kit-table tbody tr:hover td {
    background: var(--color-back);
  }

  .k-meta-kit-table-center {
    text-align: center;
  }

  .k-meta-kit-table .k-link {
    color: var(--color-blue-600);
    text-decoration: none;
  }

  .k-meta-kit-table .k-link:hover {
    text-decoration: underline;
  }

  .k-meta-kit-status-success {
    color: var(--color-green-600);
    font-weight: 600;
  }

  .k-meta-kit-status-warning {
    color: var(--color-orange-600);
    font-weight: 600;
  }

  .k-meta-kit-icon-success {
    color: var(--color-green-600);
  }

  .k-meta-kit-icon-warning {
    color: var(--color-orange-600);
  }

  .k-meta-kit-loading,
  .k-meta-kit-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    padding: 3rem;
    text-align: center;
    color: var(--color-gray-600);
  }

  .k-meta-kit-spinner {
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    from {
      transform: rotate(0deg);
    }
    to {
      transform: rotate(360deg);
    }
  }

  .k-meta-kit-legacy-list {
    padding: 1rem;
  }

  .k-meta-kit-legacy-item {
    padding: 1rem;
    margin-bottom: 1rem;
    background: var(--color-back);
    border-radius: var(--rounded);
  }

  .k-meta-kit-legacy-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--color-border);
  }

  .k-meta-kit-legacy-item-content {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .k-meta-kit-legacy-field {
    display: grid;
    grid-template-columns: 140px 1fr;
    gap: 1rem;
    align-items: start;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--color-border);
  }

  .k-meta-kit-legacy-field:last-child {
    border-bottom: none;
    padding-bottom: 0;
  }

  .k-meta-kit-legacy-field-label {
    font-weight: 600;
    color: var(--color-gray-600);
    font-size: 0.875rem;
    padding-top: 0.5rem;
  }

  .k-meta-kit-legacy-field-values {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .k-meta-kit-legacy-field-old,
  .k-meta-kit-legacy-field-new {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .k-meta-kit-legacy-badge {
    display: inline-block;
    padding: 0.125rem 0.5rem;
    background: var(--color-blue-100);
    color: var(--color-blue-700);
    border-radius: var(--rounded-xs);
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    width: fit-content;
  }

  .k-meta-kit-legacy-badge-warning {
    background: var(--color-orange-100);
    color: var(--color-orange-700);
  }

  .k-meta-kit-legacy-badge-new {
    background: var(--color-green-100);
    color: var(--color-green-700);
  }

  .k-meta-kit-legacy-field-value,
  .k-meta-kit-legacy-field-value-current {
    color: var(--color-text);
    font-size: 0.875rem;
    word-break: break-word;
    line-height: 1.5;
    padding: 0.5rem;
    background: var(--color-background);
    border-radius: var(--rounded-xs);
    border: 1px solid var(--color-border);
  }

  .k-meta-kit-legacy-field-value-current {
    text-decoration: line-through;
    opacity: 0.7;
  }

  .k-meta-kit-legacy-field-value-empty {
    color: var(--color-gray-500);
    font-size: 0.875rem;
    font-style: italic;
    padding: 0.5rem;
  }

  .k-tag {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background: var(--color-back);
    color: var(--color-blue-700);
    border-radius: var(--rounded-xs);
    font-size: 0.75rem;
    font-weight: 500;
  }

  /* Dark mode support */

  .k-panel[data-color-scheme="dark"] .k-meta-kit-stats-card {
    background: var(--color-black);
    border-color: var(--color-border);
  }

  .k-panel[data-color-scheme="dark"] .k-meta-kit-legacy-item {
    background: var(--color-black);
  }

  .k-panel[data-color-scheme="dark"] .k-meta-kit-table {
    background: var(--color-black);
  }

  .k-panel[data-color-scheme="dark"] .k-meta-kit-table th {
    background: var(--color-back);
    color: var(--color-text);
  }

  .k-panel[data-color-scheme="dark"] .k-meta-kit-table td {
    background: var(--color-black);
    color: var(--color-text);
  }

  .k-panel[data-color-scheme="dark"] .k-meta-kit-table tbody tr:hover td {
    background: var(--color-back);
  }

  .k-panel[data-color-scheme="dark"] .k-meta-kit-table .k-link {
    color: var(--color-blue-400);
  }

  .k-panel[data-color-scheme="dark"] .k-tag {
    background: var(--color-back);
    color: var(--color-blue-300);
  }

  .k-panel[data-color-scheme="dark"] .k-meta-kit-legacy-field-label {
    color: var(--color-gray-400);
  }

  .k-panel[data-color-scheme="dark"] .k-meta-kit-legacy-field-value,
  .k-panel[data-color-scheme="dark"] .k-meta-kit-legacy-field-value-current {
    color: var(--color-text);
    background: var(--color-black);
  }

  .k-panel[data-color-scheme="dark"] .k-meta-kit-legacy-badge {
    background: var(--color-blue-900);
    color: var(--color-blue-300);
  }

  .k-panel[data-color-scheme="dark"] .k-meta-kit-legacy-badge-warning {
    background: var(--color-orange-900);
    color: var(--color-orange-300);
  }

  .k-panel[data-color-scheme="dark"] .k-meta-kit-legacy-badge-new {
    background: var(--color-green-900);
    color: var(--color-green-300);
  }

  .k-panel[data-color-scheme="dark"] .k-meta-kit-legacy-field-value-empty {
    color: var(--color-gray-500);
  }

  /* New interactive elements */

  .k-meta-kit-legacy-choices {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
  }

  .k-meta-kit-legacy-field-preview {
    padding: 1rem;
    background: var(--color-background);
    border: 1px solid var(--color-border);
    border-radius: var(--rounded);
    margin-bottom: 1rem;
  }

  .k-meta-kit-legacy-field-option {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .k-meta-kit-legacy-badge-ai {
    background: var(--color-purple-100);
    color: var(--color-purple-700);
  }

  .k-meta-kit-legacy-badge-hint {
    color: var(--color-gray-500);
    font-size: 0.875rem;
    font-style: italic;
  }

  .k-meta-kit-legacy-field-generating {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--color-gray-600);
    font-size: 0.875rem;
  }

  .k-meta-kit-legacy-field-reference {
    display: none;
    margin: 1rem 0;
    border-top: 1px solid var(--color-border);
    padding-top: 0.75rem;
  }

  .k-meta-kit-legacy-field-reference details {
    cursor: pointer;
  }

  .k-meta-kit-legacy-field-reference summary {
    font-size: 0.875rem;
    color: var(--color-gray-600);
    user-select: none;
    padding: 0.25rem 0;
  }

  .k-meta-kit-legacy-field-reference summary:hover {
    color: var(--color-text);
  }

  .k-meta-kit-legacy-badge-small {
    display: inline-block;
    padding: 0.125rem 0.375rem;
    background: var(--color-gray-200);
    color: var(--color-gray-700);
    border-radius: var(--rounded-xs);
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 0.25rem;
  }

  .k-meta-kit-legacy-field-value-small {
    color: var(--color-text);
    font-size: 0.8125rem;
    line-height: 1.4;
    padding: 0.375rem;
    background: var(--color-back);
    border-radius: var(--rounded-xs);
    margin-bottom: 0.5rem;
    display: block;
  }

  /* Dark mode for new elements */

  .k-panel[data-color-scheme="dark"] .k-meta-kit-legacy-badge-ai {
    background: var(--color-purple-900);
    color: var(--color-purple-300);
  }

  .k-panel[data-color-scheme="dark"] .k-meta-kit-legacy-field-preview {
    background: var(--color-black);
  }

  .k-panel[data-color-scheme="dark"] .k-meta-kit-legacy-badge-small {
    background: var(--color-gray-800);
    color: var(--color-gray-300);
  }

  .k-panel[data-color-scheme="dark"] .k-meta-kit-legacy-field-value-small {
    background: var(--color-black);
    color: var(--color-text);
  }

  .k-panel[data-color-scheme="dark"] .k-meta-kit-legacy-field-reference summary {
    color: var(--color-gray-400);
  }

  .k-panel[data-color-scheme="dark"] .k-meta-kit-legacy-field-reference summary:hover {
    color: var(--color-text);
  }

  .k-button-group.k-dialog-buttons:has(>.k-button:nth-child(2)) {
    grid-template-columns: 1fr;
  }

  .k-button.k-dialog-button-submit {
    display: none;
  }

  .k-toolbar.k-textarea-toolbar {
    display: none;
  }

  /* Field length indicator */

  .k-meta-kit-field-length {
    display: inline-block;
    margin-left: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
  }

  .k-meta-kit-field-length.k-meta-kit-status-success {
    color: var(--color-green-600);
  }

  .k-meta-kit-field-length.k-meta-kit-status-warning {
    color: var(--color-orange-600);
  }

  /* Single page edit dialog */

  .k-meta-kit-single-edit {
    padding: 1rem;
  }

  .k-meta-kit-single-edit .k-meta-kit-legacy-field {
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid var(--color-border);
  }

  .k-meta-kit-single-edit .k-meta-kit-legacy-field:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
  }

  /* OG Image Display */

  .k-meta-kit-og-image-current {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1rem;
    padding: 1rem;
    background: var(--color-light);
    border-radius: var(--rounded);
  }

  .k-panel[data-color-scheme="dark"] .k-meta-kit-og-image-current {
    background: var(--color-dark);
  }

  .k-meta-kit-og-image-current img {
    max-width: 100%;
    max-height: 200px;
    object-fit: contain;
    border-radius: var(--rounded-sm);
    background: var(--color-white);
  }

  .k-panel[data-color-scheme="dark"] .k-meta-kit-og-image-current img {
    background: var(--color-black);
  }

  .k-meta-kit-og-image-filename {
    font-size: 0.875rem;
    color: var(--color-text-dimmed);
    font-family: var(--font-mono);
  }
}
</style>
