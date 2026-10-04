const WP_API_URL = "https://findogroup.com/wp-json/wp/v2";
export async function fetchPosts(params) {
  const searchParams = new URLSearchParams({
    _embed: "true",
    per_page: String(params?.perPage || 12),
    page: String(params?.page || 1)
  });
  if (params?.category) {
    searchParams.append("categories", String(params.category));
  }
  if (params?.search) {
    searchParams.append("search", params.search);
  }
  if (params?.before) {
    searchParams.append("before", params.before);
  }
  if (params?.after) {
    searchParams.append("after", params.after);
  }
  if (params?.orderby) {
    searchParams.append("orderby", params.orderby);
  }
  if (params?.order) {
    searchParams.append("order", params.order);
  }
  const response = await fetch(`${WP_API_URL}/posts?${searchParams}`);
  if (!response.ok) {
    throw new Error("Failed to fetch posts");
  }
  const posts = await response.json();
  const totalPages = parseInt(response.headers.get("X-WP-TotalPages") || "1");
  const total = parseInt(response.headers.get("X-WP-Total") || "0");
  return { posts, totalPages, total };
}
export async function fetchPostBySlug(slug) {
  const response = await fetch(`${WP_API_URL}/posts?slug=${slug}&_embed=true`);
  if (!response.ok) {
    throw new Error("Failed to fetch post");
  }
  const posts = await response.json();
  return posts.length > 0 ? posts[0] : null;
}
export async function fetchPostById(id) {
  const response = await fetch(`${WP_API_URL}/posts/${id}?_embed=true`);
  if (!response.ok) {
    return null;
  }
  return response.json();
}
export async function fetchCategories() {
  const response = await fetch(`${WP_API_URL}/categories?per_page=100`);
  if (!response.ok) {
    throw new Error("Failed to fetch categories");
  }
  return response.json();
}
export async function fetchComments(postId) {
  const response = await fetch(`${WP_API_URL}/comments?post=${postId}&per_page=100`);
  if (!response.ok) {
    throw new Error("Failed to fetch comments");
  }
  return response.json();
}
export async function submitComment(data) {
  const response = await fetch(`${WP_API_URL}/comments`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify(data)
  });
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || "Failed to submit comment");
  }
  return response.json();
}
export function getFeaturedImageUrl(post) {
  return post._embedded?.["wp:featuredmedia"]?.[0]?.source_url || "/placeholder.svg";
}
export function getCategoryNames(post) {
  return post._embedded?.["wp:term"]?.[0]?.map((cat) => cat.name) || [];
}
export function stripHtml(html) {
  return html.replace(/<[^>]*>/g, "").replace(/&hellip;/g, "...").replace(/&nbsp;/g, " ").trim();
}
export function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString("ar-EG", {
    year: "numeric",
    month: "long",
    day: "numeric"
  });
}
export function getDateFilterOptions() {
  const now = /* @__PURE__ */ new Date();
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  const weekAgo = new Date(today);
  weekAgo.setDate(weekAgo.getDate() - 7);
  const monthAgo = new Date(today);
  monthAgo.setMonth(monthAgo.getMonth() - 1);
  const threeMonthsAgo = new Date(today);
  threeMonthsAgo.setMonth(threeMonthsAgo.getMonth() - 3);
  const yearAgo = new Date(today);
  yearAgo.setFullYear(yearAgo.getFullYear() - 1);
  return [
    { label: "\u0627\u0644\u0643\u0644", value: "all" },
    { label: "\u0647\u0630\u0627 \u0627\u0644\u0623\u0633\u0628\u0648\u0639", value: "week", after: weekAgo.toISOString() },
    { label: "\u0647\u0630\u0627 \u0627\u0644\u0634\u0647\u0631", value: "month", after: monthAgo.toISOString() },
    { label: "\u0622\u062E\u0631 3 \u0623\u0634\u0647\u0631", value: "3months", after: threeMonthsAgo.toISOString() },
    { label: "\u0647\u0630\u0627 \u0627\u0644\u0639\u0627\u0645", value: "year", after: yearAgo.toISOString() }
  ];
}
