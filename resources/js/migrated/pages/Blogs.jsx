import { jsx, jsxs } from "react/jsx-runtime";
import { useState, useEffect, useMemo, useRef } from "react";
import SEO from "@/components/SEO";
import { PageShell } from "@/components/v2/layout";
import { BlogsHeroBoard, BlogFiltersBar, PostsGridBoard } from "@/components/v2/blog";
import { usePosts, useCategories } from "@/hooks/useWordPress";
import { getDateFilterOptions } from "@/lib/wordpress";
import { blogPaginationUrl } from "@/lib/blogPagination";
const Blogs = ({ initialData = null, initialCategories = null, initialFilters = {}, initialDateFilterOptions = null, initialSeo = null }) => {
  const [searchQuery, setSearchQuery] = useState(initialFilters.search || "");
  const [debouncedSearch, setDebouncedSearch] = useState(initialFilters.search || "");
  const [selectedCategory, setSelectedCategory] = useState(initialFilters.category ?? null);
  const [selectedDateFilter, setSelectedDateFilter] = useState(initialFilters.date || "all");
  const [currentPage, setCurrentPage] = useState(initialFilters.page || 1);
  const mounted = useRef(false);
  const dateFilterOptions = initialDateFilterOptions || getDateFilterOptions();
  useEffect(() => {
    if (searchQuery === debouncedSearch) return;
    const timer = setTimeout(() => {
      setDebouncedSearch(searchQuery);
      setCurrentPage(1);
    }, 500);
    return () => clearTimeout(timer);
  }, [searchQuery, debouncedSearch]);
  useEffect(() => {
    if (!mounted.current) { mounted.current = true; return; }
    const path = blogPaginationUrl(currentPage, { category: selectedCategory, search: debouncedSearch, date: selectedDateFilter });
    history.replaceState(history.state, '', path);
    if (initialSeo) {
      const canonical = initialSeo.baseUrl + path;
      const title = initialSeo.title + (currentPage > 1 ? (document.documentElement.lang === 'ar' ? ' — الصفحة ' : ' — Page ') + currentPage : '');
      const filtered = selectedCategory !== null || debouncedSearch !== '' || selectedDateFilter !== 'all';
      document.title = title;
      document.querySelector('link[rel="canonical"]')?.setAttribute('href', canonical);
      document.querySelector('meta[property="og:url"]')?.setAttribute('content', canonical);
      for (const selector of ['meta[property="og:title"]', 'meta[name="twitter:title"]']) document.querySelector(selector)?.setAttribute('content', title);
      document.querySelector('meta[name="robots"]')?.setAttribute('content', filtered || !initialSeo.indexable ? 'noindex, follow' : initialSeo.robots);
    }
  }, [currentPage, selectedCategory, debouncedSearch, selectedDateFilter]);
  const dateFilter = dateFilterOptions.find((opt) => opt.value === selectedDateFilter);
  const postsParams = useMemo(() => ({
    page: currentPage,
    perPage: 9,
    category: selectedCategory || void 0,
    search: debouncedSearch || void 0,
    after: dateFilter?.after
  }), [currentPage, selectedCategory, debouncedSearch, dateFilter?.after]);
  const { posts, totalPages, isLoading, error } = usePosts(postsParams, initialData);
  const { categories } = useCategories(initialCategories);
  const handleCategoryChange = (categoryId) => {
    setSelectedCategory(categoryId);
    setCurrentPage(1);
  };
  const handleDateFilterChange = (value) => {
    setSelectedDateFilter(value);
    setCurrentPage(1);
  };
  const clearFilters = () => {
    setSelectedCategory(null);
    setSelectedDateFilter("all");
    setSearchQuery("");
    setCurrentPage(1);
  };
  const hasActiveFilters = selectedCategory !== null || selectedDateFilter !== "all" || searchQuery !== "";
  return /* @__PURE__ */ jsxs(
    PageShell,
    {
      seo: /* @__PURE__ */ jsx(
        SEO,
        {
          title: "\u0645\u062F\u0648\u0646\u0629 \u0641\u0627\u064A\u0646\u062F\u0648 \u062C\u0631\u0648\u0628 - \u0645\u0642\u0627\u0644\u0627\u062A \u062A\u0642\u0646\u064A\u0629 \u0648\u0628\u0631\u0645\u062C\u064A\u0629",
          description: "\u0627\u0643\u062A\u0634\u0641 \u0623\u062D\u062F\u062B \u0627\u0644\u0645\u0642\u0627\u0644\u0627\u062A \u0648\u0627\u0644\u0631\u0624\u0649 \u0627\u0644\u062A\u0642\u0646\u064A\u0629 \u0641\u064A \u0639\u0627\u0644\u0645 \u0627\u0644\u0628\u0631\u0645\u062C\u0629 \u0648\u062A\u0637\u0648\u064A\u0631 \u0627\u0644\u062A\u0637\u0628\u064A\u0642\u0627\u062A \u0648\u0627\u0644\u062A\u0633\u0648\u064A\u0642 \u0627\u0644\u0631\u0642\u0645\u064A. \u0645\u062D\u062A\u0648\u0649 \u062D\u0635\u0631\u064A \u0645\u0646 \u062E\u0628\u0631\u0627\u0621 \u0641\u0627\u064A\u0646\u062F\u0648 \u062C\u0631\u0648\u0628 \u0644\u062A\u0637\u0648\u064A\u0631 \u0645\u0647\u0627\u0631\u0627\u062A\u0643 \u0627\u0644\u062A\u0642\u0646\u064A\u0629.",
          keywords: "\u0645\u062F\u0648\u0646\u0629 \u062A\u0642\u0646\u064A\u0629, \u0645\u0642\u0627\u0644\u0627\u062A \u0628\u0631\u0645\u062C\u0629, \u062A\u0637\u0648\u064A\u0631 \u062A\u0637\u0628\u064A\u0642\u0627\u062A, \u062A\u0635\u0645\u064A\u0645 \u0645\u0648\u0627\u0642\u0639, \u062A\u0633\u0648\u064A\u0642 \u0631\u0642\u0645\u064A, \u062F\u0631\u0648\u0633 \u0628\u0631\u0645\u062C\u064A\u0629, \u0646\u0635\u0627\u0626\u062D \u062A\u0642\u0646\u064A\u0629, \u0623\u062E\u0628\u0627\u0631 \u0627\u0644\u062A\u0642\u0646\u064A\u0629",
          canonical: "/blogs"
        }
      ),
      children: [
        /* @__PURE__ */ jsx(BlogsHeroBoard, { searchQuery, onSearchChange: setSearchQuery }),
        /* @__PURE__ */ jsx(
          PostsGridBoard,
          {
            posts,
            isLoading,
            error,
            totalPages,
            currentPage,
            getPageHref: (page) => blogPaginationUrl(page, { category: selectedCategory, search: debouncedSearch, date: selectedDateFilter }),
            hasActiveFilters,
            onClearFilters: clearFilters,
            filters: /* @__PURE__ */ jsx(
              BlogFiltersBar,
              {
                categories,
                selectedCategory,
                onCategoryChange: handleCategoryChange,
                dateFilterOptions,
                selectedDateFilter,
                onDateFilterChange: handleDateFilterChange,
                hasActiveFilters,
                onClearFilters: clearFilters
              }
            )
          }
        )
      ]
    }
  );
};
export default Blogs;
