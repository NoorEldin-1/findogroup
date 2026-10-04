export async function insertLocal(table, row) {
  if (table === "cookie_consents") return { data: null, error: null };
  if (!["leads", "training_registrations", "partnership_requests", "template_leads", "newsletter_subscribers", "article_comments"].includes(table)) return { data: null, error: new Error("This module is not connected locally yet.") };
  const fields = Object.fromEntries(Object.entries(row).map(([k, v]) => [k, v == null ? "" : typeof v === "string" ? v : JSON.stringify(v)]));
  const email = /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(row.email || "") ? row.email : null;
  const response = await fetch("/forms/submit", { method: "POST", headers: { "Content-Type": "application/json", "Accept": "application/json", "X-CSRF-TOKEN": document.querySelector("meta[name=csrf-token]").content }, body: JSON.stringify({ form_key: table, source_path: location.pathname, name: row.name || row.contact_name || row.company_name || "Website visitor", email, phone: row.phone || "", message: row.description || row.message || "", fields }) });
  const data = await response.json();
  if (!response.ok) return { data: null, error: new Error(Object.values(data.errors || {}).flat().join(" ") || data.message) };
  window.dispatchEvent(new CustomEvent("findo:toast", { detail: data.message }));
  return { data, error: null };
}
export function blockedExternalRequest() {
  return Promise.reject(new Error("External submissions are disabled in this local copy."));
}
