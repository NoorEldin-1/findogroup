import { jsx, jsxs } from "react/jsx-runtime";
import { X } from "lucide-react";
import { Chip } from "@/components/v2/primitives";
import { useLanguage } from "@/contexts/LanguageContext";
const BlogFiltersBar = ({
  categories,
  selectedCategory,
  onCategoryChange,
  dateFilterOptions,
  selectedDateFilter,
  onDateFilterChange,
  hasActiveFilters,
  onClearFilters
}) => {
  const { t } = useLanguage();
  return /* @__PURE__ */ jsxs("div", { className: "mb-8 flex flex-col gap-4", children: [
    /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-2.5 overflow-x-auto pb-2", children: [
      /* @__PURE__ */ jsx("span", { className: "whitespace-nowrap text-sm text-muted-foreground", children: t("\u0627\u0644\u0623\u0642\u0633\u0627\u0645:", "Categories:") }),
      /* @__PURE__ */ jsx(
        Chip,
        {
          interactive: true,
          active: selectedCategory === null,
          onClick: () => onCategoryChange(null),
          className: "whitespace-nowrap",
          children: t("\u0627\u0644\u0643\u0644", "All")
        }
      ),
      categories.map((category) => /* @__PURE__ */ jsxs(
        Chip,
        {
          interactive: true,
          active: selectedCategory === category.id,
          onClick: () => onCategoryChange(category.id),
          className: "whitespace-nowrap",
          children: [
            category.name,
            /* @__PURE__ */ jsxs("span", { className: "text-xs opacity-60", children: [
              "(",
              category.count,
              ")"
            ] })
          ]
        },
        category.id
      ))
    ] }),
    /* @__PURE__ */ jsxs("div", { className: "flex flex-wrap items-center justify-between gap-4", children: [
      /* @__PURE__ */ jsxs("div", { className: "flex flex-wrap items-center gap-2", children: [
        /* @__PURE__ */ jsx("span", { className: "text-sm text-muted-foreground", children: t("\u0627\u0644\u062A\u0627\u0631\u064A\u062E:", "Date:") }),
        dateFilterOptions.slice(0, 4).map((option) => /* @__PURE__ */ jsx(
          Chip,
          {
            interactive: true,
            active: selectedDateFilter === option.value,
            onClick: () => onDateFilterChange(option.value),
            className: "px-3 py-1 text-xs",
            children: option.label
          },
          option.value
        ))
      ] }),
      hasActiveFilters && /* @__PURE__ */ jsxs(
        "button",
        {
          type: "button",
          onClick: onClearFilters,
          className: "inline-flex items-center gap-1.5 rounded-full border border-border px-3.5 py-1.5 text-sm text-muted-foreground transition-all duration-200 ease-ds hover:border-destructive/50 hover:bg-destructive/10 hover:text-destructive",
          children: [
            /* @__PURE__ */ jsx(X, { className: "h-3.5 w-3.5", strokeWidth: 1.75 }),
            t("\u0645\u0633\u062D \u0627\u0644\u0641\u0644\u0627\u062A\u0631", "Clear Filters")
          ]
        }
      )
    ] })
  ] });
};
export default BlogFiltersBar;
