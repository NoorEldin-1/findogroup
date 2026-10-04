import { Fragment, jsx, jsxs } from "react/jsx-runtime";
import { ChevronLeft, ChevronRight, Search } from "lucide-react";
import { cn } from "@/lib/utils";
import { Board, BoardHeader, RiseIn } from "@/components/v2/primitives";
import PostCard, { PostCardSkeleton } from "./PostCard";
import { useLanguage } from "@/contexts/LanguageContext";
const PostsGridBoard = ({
  posts,
  isLoading,
  error,
  totalPages,
  currentPage,
  onPageChange,
  hasActiveFilters,
  onClearFilters,
  filters
}) => {
  const { t, isRTL } = useLanguage();
  return /* @__PURE__ */ jsx(RiseIn, { children: /* @__PURE__ */ jsxs(Board, { dir: isRTL ? "rtl" : "ltr", children: [
    /* @__PURE__ */ jsx(
      BoardHeader,
      {
        index: "02",
        eyebrow: t("\u062A\u0635\u0641\u062D \u0627\u0644\u0645\u0642\u0627\u0644\u0627\u062A", "Browse Articles"),
        pre: t("\u0623\u062D\u062F\u062B", "Latest"),
        accent: t("\u0627\u0644\u0645\u0642\u0627\u0644\u0627\u062A", "Articles")
      }
    ),
    filters,
    !isLoading && posts.length > 0 && /* @__PURE__ */ jsxs("p", { className: "mb-6 text-sm text-muted-foreground", children: [
      t(`\u0639\u0631\u0636 ${posts.length} \u0645\u0642\u0627\u0644`, `Showing ${posts.length} articles`),
      hasActiveFilters && t(" (\u0645\u0639 \u0627\u0644\u0641\u0644\u0627\u062A\u0631)", " (with filters)")
    ] }),
    isLoading && /* @__PURE__ */ jsx("div", { className: "grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3", children: Array.from({ length: 9 }).map((_, i) => /* @__PURE__ */ jsx(PostCardSkeleton, {}, i)) }),
    error && !isLoading && /* @__PURE__ */ jsx("div", { className: "py-24 text-center", children: /* @__PURE__ */ jsx("p", { className: "text-lg text-destructive", children: error }) }),
    !isLoading && !error && /* @__PURE__ */ jsxs(Fragment, { children: [
      /* @__PURE__ */ jsx("div", { className: "grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3", children: posts.map((post) => /* @__PURE__ */ jsx(PostCard, { post }, post.id)) }),
      posts.length === 0 && /* @__PURE__ */ jsxs("div", { className: "py-24 text-center", children: [
        /* @__PURE__ */ jsx("div", { className: "mx-auto mb-6 grid h-20 w-20 place-items-center rounded-full border border-border bg-popover", children: /* @__PURE__ */ jsx(Search, { className: "h-10 w-10 text-muted-foreground", strokeWidth: 1.75 }) }),
        /* @__PURE__ */ jsx("h3", { className: "mb-2 text-xl font-bold text-foreground", children: t("\u0644\u0627 \u062A\u0648\u062C\u062F \u0645\u0642\u0627\u0644\u0627\u062A", "No Articles Found") }),
        /* @__PURE__ */ jsx("p", { className: "mb-4 text-muted-foreground", children: t("\u062C\u0631\u0628 \u062A\u063A\u064A\u064A\u0631 \u0627\u0644\u0641\u0644\u0627\u062A\u0631 \u0623\u0648 \u0627\u0644\u0628\u062D\u062B \u0628\u0643\u0644\u0645\u0627\u062A \u0645\u062E\u062A\u0644\u0641\u0629", "Try changing filters or search with different keywords") }),
        hasActiveFilters && /* @__PURE__ */ jsx("button", { type: "button", onClick: onClearFilters, className: "text-primary hover:underline", children: t("\u0645\u0633\u062D \u062C\u0645\u064A\u0639 \u0627\u0644\u0641\u0644\u0627\u062A\u0631", "Clear All Filters") })
      ] }),
      totalPages > 1 && /* @__PURE__ */ jsxs("div", { className: "mt-12 flex items-center justify-center gap-2", children: [
        /* @__PURE__ */ jsx(
          "button",
          {
            type: "button",
            onClick: () => onPageChange(Math.max(1, currentPage - 1)),
            disabled: currentPage === 1,
            "aria-label": t("\u0627\u0644\u0635\u0641\u062D\u0629 \u0627\u0644\u0633\u0627\u0628\u0642\u0629", "Previous page"),
            className: "grid h-10 w-10 place-items-center rounded-full border border-border bg-popover text-muted-foreground transition-all duration-200 ease-ds hover:border-primary/60 hover:text-primary disabled:cursor-not-allowed disabled:opacity-40",
            children: /* @__PURE__ */ jsx(ChevronRight, { className: "h-5 w-5", strokeWidth: 1.75 })
          }
        ),
        /* @__PURE__ */ jsx("div", { className: "flex gap-1", children: Array.from({ length: Math.min(5, totalPages) }, (_, i) => {
          let pageNum;
          if (totalPages <= 5) {
            pageNum = i + 1;
          } else if (currentPage <= 3) {
            pageNum = i + 1;
          } else if (currentPage >= totalPages - 2) {
            pageNum = totalPages - 4 + i;
          } else {
            pageNum = currentPage - 2 + i;
          }
          return /* @__PURE__ */ jsx(
            "button",
            {
              type: "button",
              onClick: () => onPageChange(pageNum),
              className: cn(
                "h-10 w-10 rounded-full border font-display font-semibold transition-all duration-200 ease-ds",
                currentPage === pageNum ? "border-primary bg-primary/15 text-primary" : "border-border bg-popover text-muted-foreground hover:border-primary/60 hover:text-primary"
              ),
              children: pageNum
            },
            pageNum
          );
        }) }),
        /* @__PURE__ */ jsx(
          "button",
          {
            type: "button",
            onClick: () => onPageChange(Math.min(totalPages, currentPage + 1)),
            disabled: currentPage === totalPages,
            "aria-label": t("\u0627\u0644\u0635\u0641\u062D\u0629 \u0627\u0644\u062A\u0627\u0644\u064A\u0629", "Next page"),
            className: "grid h-10 w-10 place-items-center rounded-full border border-border bg-popover text-muted-foreground transition-all duration-200 ease-ds hover:border-primary/60 hover:text-primary disabled:cursor-not-allowed disabled:opacity-40",
            children: /* @__PURE__ */ jsx(ChevronLeft, { className: "h-5 w-5", strokeWidth: 1.75 })
          }
        )
      ] })
    ] })
  ] }) });
};
export default PostsGridBoard;
