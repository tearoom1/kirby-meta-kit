<template>
  <k-dialog ref="dialog" size="huge" :cancel-button="false" submitButton="">
    <k-headline>{{ headline }}</k-headline>

    <div v-if="isLoading" class="k-meta-kit-loading">
      <k-icon class="k-meta-kit-spinner" type="loader"/>
      <span>Reviewing content…</span>
    </div>

    <div v-else-if="error" class="k-meta-kit-review-dialog">
      <p class="k-meta-kit-review-error">{{ error }}</p>
    </div>

    <div v-else-if="reviewData" class="k-meta-kit-review-dialog">

      <!-- Summary card -->
      <div class="k-meta-kit-review-summary-card">
        <div class="k-meta-kit-review-summary-header">
          <p class="k-meta-kit-review-summary-text">{{ reviewData.review?.summary || fallbackSummary }}</p>
          <span
            v-if="reviewData.review?.overallQuality"
            class="k-meta-kit-review-quality-badge"
            :data-quality="(reviewData.review.overallQuality || '').toLowerCase()"
          >{{ reviewData.review.overallQuality }}</span>
        </div>
        <div v-if="reviewData.review?.verdict || reviewData.review?.searchIntent" class="k-meta-kit-review-summary-meta">
          <div v-if="reviewData.review?.verdict" class="k-meta-kit-review-meta-row">
            <span class="k-meta-kit-review-meta-label">Verdict</span>
            <span>{{ reviewData.review.verdict }}</span>
          </div>
          <div v-if="reviewData.review?.searchIntent" class="k-meta-kit-review-meta-row">
            <span class="k-meta-kit-review-meta-label">Search intent</span>
            <span>{{ reviewData.review.searchIntent }}</span>
          </div>
        </div>
        <div v-if="reviewData.review?.needsRewrite" class="k-meta-kit-review-warning">
          Rewrite recommended before targeting SEO keywords
        </div>
      </div>

      <!-- Keyphrases -->
      <div v-if="reviewData.review?.keyphrases?.length" class="k-meta-kit-review-block">
        <h3 class="k-meta-kit-review-block-title">Suggested keyphrases</h3>
        <div class="k-meta-kit-review-keyphrases">
          <div
            v-for="(item, index) in reviewData.review.keyphrases"
            :key="`phrase-${index}`"
            class="k-meta-kit-review-keyphrase"
          >
            <div class="k-meta-kit-review-keyphrase-header">
              <strong>{{ item.phrase }}</strong>
              <span
                v-if="item.fit"
                class="k-meta-kit-review-fit-badge"
                :data-fit="(item.fit || '').toLowerCase()"
              >{{ item.fit }}</span>
            </div>
            <p class="k-meta-kit-review-keyphrase-reason">{{ item.reason }}</p>
          </div>
        </div>
      </div>

      <!-- Strengths + Problems side by side -->
      <div class="k-meta-kit-review-grid">
        <div class="k-meta-kit-review-block k-meta-kit-review-block--strengths">
          <h3 class="k-meta-kit-review-block-title">What works</h3>
          <ul v-if="reviewData.review?.strengths?.length" class="k-meta-kit-review-list">
            <li v-for="(item, index) in reviewData.review.strengths" :key="`strength-${index}`">{{ item }}</li>
          </ul>
          <p v-else class="k-meta-kit-review-empty">No strengths returned.</p>
        </div>

        <div class="k-meta-kit-review-block k-meta-kit-review-block--problems">
          <h3 class="k-meta-kit-review-block-title">Content problems</h3>
          <ul v-if="reviewData.review?.contentProblems?.length" class="k-meta-kit-review-list">
            <li v-for="(item, index) in reviewData.review.contentProblems" :key="`problem-${index}`">{{ item }}</li>
          </ul>
          <p v-else class="k-meta-kit-review-empty">No major content problems returned.</p>
        </div>
      </div>

      <!-- Improvements – full width -->
      <div v-if="reviewData.review?.improvements?.length" class="k-meta-kit-review-block k-meta-kit-review-block--improvements">
        <h3 class="k-meta-kit-review-block-title">What to improve</h3>
        <ol class="k-meta-kit-review-list k-meta-kit-review-list--ordered">
          <li v-for="(item, index) in reviewData.review.improvements" :key="`improvement-${index}`">{{ item }}</li>
        </ol>
      </div>

      <!-- Metadata fit -->
      <div v-if="reviewData.review?.metadataFit?.length" class="k-meta-kit-review-block k-meta-kit-review-block--metadata">
        <h3 class="k-meta-kit-review-block-title">Metadata fit</h3>
        <ul class="k-meta-kit-review-list">
          <li v-for="(item, index) in reviewData.review.metadataFit" :key="`metadata-${index}`">{{ item }}</li>
        </ul>
      </div>

      <!-- Next steps -->
      <div v-if="reviewData.review?.nextSteps?.length" class="k-meta-kit-review-block k-meta-kit-review-block--nextsteps">
        <h3 class="k-meta-kit-review-block-title">Next steps</h3>
        <ol class="k-meta-kit-review-list k-meta-kit-review-list--ordered">
          <li v-for="(item, index) in reviewData.review.nextSteps" :key="`next-${index}`">{{ item }}</li>
        </ol>
      </div>

      <!-- Footer -->
      <div class="k-meta-kit-dialog-footer">
        <div class="k-meta-kit-dialog-footer-actions k-meta-kit-dialog-footer-actions-start">
          <a
            v-if="reviewData.scope === 'page' && reviewData.page?.panelUrl"
            :href="reviewData.page.panelUrl"
            class="k-link k-meta-kit-dialog-panel-link"
          >
            Edit in Panel
          </a>
          <button class="k-link k-meta-kit-dialog-panel-link" @click="print">Print</button>
        </div>
        <div class="k-meta-kit-dialog-footer-meta"></div>
        <div class="k-meta-kit-dialog-footer-actions k-meta-kit-dialog-footer-actions-end">
          <k-button @click="close">Close</k-button>
        </div>
      </div>
    </div>
  </k-dialog>
</template>

<script>
export default {
  props: {
    api: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      reviewData: null,
      isLoading: false,
      error: '',
      headline: 'AI Content Review'
    };
  },
  computed: {
    fallbackSummary() {
      return 'Review the page topic, search intent, and whether the content clearly supports a strong SEO angle.';
    }
  },
  methods: {
    async openPage(pageId, title = 'Page Content Review') {
      await this.fetchReview('meta-kit/review-page', { pageId }, title);
    },
    async fetchReview(route, payload, title) {
      this.headline = title;
      this.reviewData = null;
      this.error = '';
      this.isLoading = true;
      this.$refs.dialog.open();

      try {
        const response = await this.api.post(route, payload);
        if (response.status === 'success') {
          this.reviewData = response.data;
        } else {
          this.error = response.message || 'Failed to load review.';
        }
      } catch (error) {
        this.error = error?.message || 'Failed to load review.';
      } finally {
        this.isLoading = false;
      }
    },
    close() {
      this.$refs.dialog.close();
      this.reviewData = null;
      this.error = '';
    },
    print() {
      const win = window.open('', '_blank', 'width=900,height=700');
      if (!win) return;
      win.document.write(this.buildPrintHTML());
      win.document.close();
    },
    buildPrintHTML() {
      const review = this.reviewData?.review || {};
      const esc = (s) => String(s ?? '')
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');

      const qualityColors = {
        low:    ['#fee2e2', '#dc2626'],
        medium: ['#ffedd5', '#ea580c'],
        high:   ['#dcfce7', '#16a34a'],
      };
      const fitColors = {
        strong: ['#dcfce7', '#16a34a'],
        medium: ['#ffedd5', '#ea580c'],
        weak:   ['#fee2e2', '#dc2626'],
      };

      const badge = (label, colors) => {
        const [bg, color] = colors || ['#f3f4f6', '#6b7280'];
        return `<span style="display:inline-block;padding:.2em .55em;border-radius:2em;font-size:.7rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;background:${bg};color:${color}">${esc(label)}</span>`;
      };

      const ul = (items) =>
        `<ul style="margin:0;padding-left:1.25rem">${items.map(i => `<li style="line-height:1.5;margin-top:.35rem">${esc(i)}</li>`).join('')}</ul>`;

      const ol = (items) =>
        `<ol style="margin:0;padding-left:1.25rem">${items.map(i => `<li style="line-height:1.5;margin-top:.35rem">${esc(i)}</li>`).join('')}</ol>`;

      const block = (title, accentColor, content) =>
        `<div style="border:1px solid #d1d5db;border-left:3px solid ${accentColor};border-radius:4px;padding:.875rem 1rem;break-inside:avoid">
          <h3 style="margin:0 0 .5rem;font-size:.7rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#6b7280">${esc(title)}</h3>
          ${content}
        </div>`;

      let sections = '';

      // Summary card
      const qc = qualityColors[(review.overallQuality || '').toLowerCase()];
      let meta = '';
      if (review.verdict) meta += `<div style="display:flex;gap:.5rem;font-size:.8125rem;line-height:1.45"><span style="flex-shrink:0;width:6.5rem;color:#6b7280;font-weight:500">Verdict</span><span>${esc(review.verdict)}</span></div>`;
      if (review.searchIntent) meta += `<div style="display:flex;gap:.5rem;font-size:.8125rem;line-height:1.45"><span style="flex-shrink:0;width:6.5rem;color:#6b7280;font-weight:500">Search intent</span><span>${esc(review.searchIntent)}</span></div>`;
      sections += `<div style="border:1px solid #d1d5db;border-radius:4px;overflow:hidden;break-inside:avoid">
        <div style="display:flex;align-items:flex-start;gap:.75rem;padding:.875rem 1rem">
          <p style="flex:1;margin:0;font-weight:500;line-height:1.5">${esc(review.summary || this.fallbackSummary)}</p>
          ${review.overallQuality ? badge(review.overallQuality, qc) : ''}
        </div>
        ${meta ? `<div style="border-top:1px solid #d1d5db;padding:.625rem 1rem;display:flex;flex-direction:column;gap:.375rem">${meta}</div>` : ''}
        ${review.needsRewrite ? `<div style="border-top:1px solid #fed7aa;background:#ffedd5;color:#9a3412;padding:.5rem 1rem;font-size:.8125rem;font-weight:600">Rewrite recommended before targeting SEO keywords</div>` : ''}
      </div>`;

      // Keyphrases
      if (review.keyphrases?.length) {
        const phrases = review.keyphrases.map((item, i) => {
          const fc = fitColors[(item.fit || '').toLowerCase()];
          return `<div style="padding:.625rem 0;${i > 0 ? 'border-top:1px solid #d1d5db' : ''}">
            <div style="display:flex;align-items:center;gap:.5rem"><strong>${esc(item.phrase)}</strong>${item.fit ? badge(item.fit, fc) : ''}</div>
            <p style="margin:.25rem 0 0;font-size:.8125rem;color:#6b7280;line-height:1.45">${esc(item.reason)}</p>
          </div>`;
        }).join('');
        sections += block('Suggested keyphrases', '#9ca3af', phrases);
      }

      // Strengths + Problems
      const strengthsHTML = review.strengths?.length ? ul(review.strengths) : '<p style="color:#6b7280;font-size:.875rem;margin:0">No strengths returned.</p>';
      const problemsHTML = review.contentProblems?.length ? ul(review.contentProblems) : '<p style="color:#6b7280;font-size:.875rem;margin:0">No content problems returned.</p>';
      sections += `<div style="display:grid;grid-template-columns:1fr 1fr;gap:.875rem">
        ${block('What works', '#16a34a', strengthsHTML)}
        ${block('Content problems', '#dc2626', problemsHTML)}
      </div>`;

      if (review.improvements?.length) sections += block('What to improve', '#ea580c', ol(review.improvements));
      if (review.metadataFit?.length)  sections += block('Metadata fit', '#9ca3af', ul(review.metadataFit));

      if (review.nextSteps?.length) sections += block('Next steps', '#2563eb', ol(review.nextSteps));

      return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>${esc(this.headline)}</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 14px; line-height: 1.5; color: #111; background: white; margin: 0; padding: 1.5rem 2rem; }
    h1 { font-size: 1.1rem; font-weight: 700; margin: 0 0 1rem; }
    @media print { body { padding: 0; } }
  </style>
</head>
<body>
  <h1>${esc(this.headline)}</h1>
  <div style="display:flex;flex-direction:column;gap:.875rem">${sections}</div>
  <script>
    window.addEventListener('load', function () { window.print(); });
    window.addEventListener('afterprint', function () { window.close(); });
  <\/script>
</body>
</html>`;
    }
  }
};
</script>
