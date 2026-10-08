import { t } from './i18n.js';

export async function applySingleFieldUpdate(api, { pageId, fieldName, value }) {
  const response = await api.post('meta-kit/apply-single-field', {
    pageId,
    fieldName,
    value
  });

  if (response?.status !== 'success') {
    throw new Error(response?.message || t('error.update', { field: fieldName }));
  }

  return response;
}
