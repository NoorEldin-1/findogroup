export function blogPaginationUrl(page, { category = null, search = '', date = 'all' } = {}) {
  const query = new URLSearchParams();
  if (category !== null) query.set('category', String(category));
  if (search) query.set('search', search);
  if (date !== 'all') query.set('date', date);
  if (page > 1) query.set('page', String(page));
  const encoded = query.toString().replace(/\+/g, '%20');
  return '/blogs' + (encoded ? '?' + encoded : '');
}
