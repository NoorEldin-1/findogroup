import { useState, useEffect } from "react";
const STORAGE_KEY = "findo_post_views";
const getStoredViews = () => {
  try {
    const stored = localStorage.getItem(STORAGE_KEY);
    return stored ? JSON.parse(stored) : {};
  } catch {
    return {};
  }
};
const saveViews = (views) => {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(views));
  } catch {
  }
};
const generateBaseViews = (postId) => {
  const seed = postId * 17 + 123;
  return Math.floor(seed % 500 + 50);
};
export const useViewCounter = (postId) => {
  const [views, setViews] = useState(0);
  useEffect(() => {
    if (!postId) return;
    const storedViews = getStoredViews();
    const postData = storedViews[postId];
    const baseViews = generateBaseViews(postId);
    const now = /* @__PURE__ */ new Date();
    const lastViewed = postData?.lastViewed ? new Date(postData.lastViewed) : null;
    const hourAgo = new Date(now.getTime() - 60 * 60 * 1e3);
    let currentViews = postData?.views || baseViews;
    if (!lastViewed || lastViewed < hourAgo) {
      currentViews += 1;
      storedViews[postId] = {
        views: currentViews,
        lastViewed: now.toISOString()
      };
      saveViews(storedViews);
    }
    setViews(currentViews);
  }, [postId]);
  return { views };
};
export const formatViews = (count) => {
  if (count >= 1e3) {
    return `${(count / 1e3).toFixed(1)}\u0643`;
  }
  return count.toString();
};
