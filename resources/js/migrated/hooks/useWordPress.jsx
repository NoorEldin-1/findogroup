import { useEffect, useRef, useState } from "react";
import { insertLocal } from "@/adapters/local-api.jsx";
function useLocalData(endpoint, initialData = null) {
  const initialPending = useRef(initialData !== null);
  const [state, setState] = useState(() => ({ posts: [], categories: [], comments: [], total: 0, totalPages: 1, ...initialData, isLoading: initialData === null, error: null }));
  useEffect(() => {
    if (initialPending.current) {
      initialPending.current = false;
      return;
    }
    setState((previous) => ({ ...previous, isLoading: true, error: null }));
    const controller = new AbortController();
    fetch(endpoint, { signal: controller.signal }).then((r) => {
      if (!r.ok) throw new Error("Unable to load local content");
      return r.json();
    }).then((data) => setState({ ...data, isLoading: false, error: null })).catch((e) => {
      if (e.name !== "AbortError") setState((s) => ({ ...s, isLoading: false, error: e.message }));
    });
    return () => controller.abort();
  }, [endpoint]);
  return state;
}
export const usePosts = (params = {}, initialData = null) => useLocalData("/local-api/posts?" + new URLSearchParams(Object.entries(params).filter(([, v]) => v !== void 0)), initialData);
export const useCategories = (initialCategories = null) => useLocalData("/local-api/categories", initialCategories === null ? null : { categories: initialCategories });
export const usePost = (slug) => useLocalData("/local-api/posts?slug=" + encodeURIComponent(slug));
export const useComments = (postId) => ({ ...useLocalData("/local-api/comments?post=" + postId), refetch() {
}, async addComment(data) {
  const result = await insertLocal("article_comments", { name: data.author_name, email: data.author_email, message: data.content, post_id: postId });
  if (result.error) throw result.error;
  return result.data;
} });
