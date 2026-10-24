import test from 'node:test';
import assert from 'node:assert/strict';
import { blogPaginationUrl } from '../resources/js/migrated/lib/blogPagination.js';

test('the first page uses the root while subsequent pages have independent URLs', () => {
  assert.equal(blogPaginationUrl(1), '/blogs');
  assert.equal(blogPaginationUrl(2), '/blogs?page=2');
});

test('paging preserves filters in the same canonical order as the server', () => {
  assert.equal(blogPaginationUrl(3, { category: 4, search: 'CRM & ERP', date: 'week' }), '/blogs?category=4&search=CRM%20%26%20ERP&date=week&page=3');
  assert.equal(blogPaginationUrl(1, { search: '0' }), '/blogs?search=0');
});

test('Arabic search and reserved characters remain query values rather than URL fragments', () => {
  const path = blogPaginationUrl(2, { search: 'برمجة #1?' });
  const parsed = new URL(path, 'https://findogroup.com');
  assert.equal(parsed.hash, '');
  assert.equal(parsed.searchParams.get('search'), 'برمجة #1?');
  assert.equal(parsed.searchParams.get('page'), '2');
});
