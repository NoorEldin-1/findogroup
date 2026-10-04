const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character]));

function withRelatedResources(html, page, data) {
  const match = (page.renderUrl || page.url).match(/^\/(services|systems)\/([^/?#]+)$/);
  if (!match) return html;
  const item = (data[match[1]] || []).find((entry) => String(entry.id) === match[2]);
  const resources = item?.relatedResources || [];
  const seen = new Set();
  const links = resources.filter((resource) => {
    if (!resource || typeof resource.path !== 'string' || !/^\/(?!\/)[^\s<>"'\\?#]+$/.test(resource.path)) return false;
    const label = page.locale === 'ar' ? resource.labelAr : resource.labelEn;
    if (typeof label !== 'string' || !label.trim() || seen.has(resource.path)) return false;
    seen.add(resource.path);
    return true;
  }).slice(0, 8);
  if (!links.length) return html;
  const end = html.lastIndexOf('</main>');
  if (end === -1) return html;
  const heading = page.locale === 'ar' ? 'خدمات وأنظمة مرتبطة باحتياجك' : 'Related services and systems';
  const section = '<section data-related-resources class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8"><div class="rounded-3xl border border-border bg-card p-6 sm:p-8"><h2 class="text-2xl font-bold text-foreground">' + heading + '</h2><ul class="mt-6 flex flex-wrap gap-4">' + links.map((resource) => '<li><a class="inline-flex items-center rounded-xl border border-border px-4 py-3 text-sm font-semibold text-primary hover:underline" href="' + escapeHtml(resource.path) + '">' + escapeHtml(page.locale === 'ar' ? resource.labelAr : resource.labelEn) + '</a></li>').join('') + '</ul></div></section>';
  return html.slice(0, end) + section + html.slice(end);
}

module.exports = { withRelatedResources };
