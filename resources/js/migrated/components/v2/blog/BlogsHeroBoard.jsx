import { jsx, jsxs } from "react/jsx-runtime";
import { Search, X } from "lucide-react";
import { Board, BoardHeader, RiseIn } from "@/components/v2/primitives";
import { useLanguage } from "@/contexts/LanguageContext";
const BlogsHeroBoard = ({ searchQuery, onSearchChange }) => {
  const { t, isRTL } = useLanguage();
  return /* @__PURE__ */ jsx(RiseIn, { mount: true, children: /* @__PURE__ */ jsxs(Board, { dir: isRTL ? "rtl" : "ltr", className: "lg:p-16", children: [
    /* @__PURE__ */ jsx(
      "div",
      {
        "aria-hidden": true,
        className: "pointer-events-none absolute -top-40 start-1/4 h-[420px] w-[600px] opacity-70",
        style: { background: "radial-gradient(closest-side, rgba(253,128,46,0.14), transparent 70%)" }
      }
    ),
    /* @__PURE__ */ jsxs("div", { className: "relative", children: [
      /* @__PURE__ */ jsx(
        BoardHeader,
        {
          index: "01",
          as: "h1",
          eyebrow: t("\u0645\u0642\u0627\u0644\u0627\u062A \u0648\u0631\u0624\u0649 \u062A\u0642\u0646\u064A\u0629", "Tech Articles & Insights"),
          accent: t("\u0627\u0644\u0645\u062F\u0648\u0646\u0629", "Blog"),
          headingClassName: "text-4xl md:text-5xl lg:text-6xl leading-tight",
          subtitle: t(
            "\u0627\u0643\u062A\u0634\u0641 \u0623\u062D\u062F\u062B \u0627\u0644\u0645\u0642\u0627\u0644\u0627\u062A \u0641\u064A \u0639\u0627\u0644\u0645 \u0627\u0644\u062A\u0642\u0646\u064A\u0629 \u0648\u0627\u0644\u062A\u0637\u0648\u064A\u0631",
            "Discover the latest articles in technology and development"
          ),
          className: "mb-0"
        }
      ),
      /* @__PURE__ */ jsxs("div", { className: "relative mt-8 max-w-lg", children: [
        /* @__PURE__ */ jsx(
          Search,
          {
            className: "absolute start-4 top-1/2 h-5 w-5 -translate-y-1/2 text-muted-foreground",
            strokeWidth: 1.75
          }
        ),
        /* @__PURE__ */ jsx(
          "input",
          {
            type: "text",
            placeholder: t("\u0627\u0628\u062D\u062B \u0639\u0646 \u0645\u0642\u0627\u0644...", "Search for an article..."),
            value: searchQuery,
            onChange: (e) => onSearchChange(e.target.value),
            className: "w-full rounded-2xl border border-border bg-popover py-3.5 pe-12 ps-12 text-foreground placeholder:text-muted-foreground transition-colors duration-200 ease-ds focus:border-primary/60 focus:outline-none"
          }
        ),
        searchQuery && /* @__PURE__ */ jsx(
          "button",
          {
            type: "button",
            onClick: () => onSearchChange(""),
            "aria-label": t("\u0645\u0633\u062D \u0627\u0644\u0628\u062D\u062B", "Clear search"),
            className: "absolute end-4 top-1/2 -translate-y-1/2 text-muted-foreground transition-colors duration-200 ease-ds hover:text-foreground",
            children: /* @__PURE__ */ jsx(X, { className: "h-5 w-5", strokeWidth: 1.75 })
          }
        )
      ] })
    ] })
  ] }) });
};
export default BlogsHeroBoard;
