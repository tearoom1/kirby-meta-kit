export async function applySingleFieldUpdate(api, { pageId, fieldName, value }) {
  const response = await api.post('meta-kit/apply-single-field', {
    pageId,
    fieldName,
    value
  });

  if (response?.status !== 'success') {
    throw new Error(response?.message || `Failed to update ${fieldName}`);
  }

  return response;
}
