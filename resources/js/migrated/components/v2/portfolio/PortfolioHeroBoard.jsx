import { jsx, jsxs } from "react/jsx-runtime";
import { Search, X } from "lucide-react";
import { Board, BoardHeader, RiseIn } from "@/components/v2/primitives";
import { useLanguage } from "@/contexts/LanguageContext";
const PortfolioHeroBoard = ({ searchQuery, onSearchChange }) => {
  const { t, isRTL } = useLanguage();
  return /* @__PURE__ */ jsx(RiseIn, { mount: true, children: /* @__PURE__ */ jsxs(Board, { dir: isRTL ? "rtl" : "ltr", className: "lg:p-16", children: [
    /* @__PURE__ */ jsx(
      "div",
      {
        "aria-hidden": true,
        className: "pointer-events-none absolute -top-40 start-1/4 h-[440px] w-[620px] opacity-70",
        style: { background: "radial-gradient(closest-side, rgba(253,128,46,0.15), transparent 70%)" }
      }
    ),
    /* @__PURE__ */ jsx(
      "div",
      {
        "aria-hidden": true,
        className: "pointer-events-none absolute -bottom-48 end-0 h-[380px] w-[480px] opacity-60",
        style: { background: "radial-gradient(closest-side, rgba(253,128,46,0.09), transparent 70%)" }
      }
    ),
    /* @__PURE__ */ jsxs("div", { className: "relative", children: [
      /* @__PURE__ */ jsx(
        BoardHeader,
        {
          index: "01",
          as: "h1",
          align: "center",
          eyebrow: t("\u0645\u0634\u0627\u0631\u064A\u0639 \u0645\u0645\u064A\u0632\u0629", "Featured Projects"),
          pre: t("\u0645\u0639\u0631\u0636", "Our"),
          accent: t("\u0623\u0639\u0645\u0627\u0644\u0646\u0627", "Portfolio"),
          post: t("\u0627\u0644\u0645\u0645\u064A\u0632\u0629", "Showcase"),
          headingClassName: "text-4xl md:text-5xl lg:text-6xl leading-tight",
          subtitle: t(
            "\u0646\u0645\u0627\u0630\u062C \u062D\u0642\u064A\u0642\u064A\u0629 \u0645\u0646 \u0625\u0646\u062C\u0627\u0632\u0627\u062A\u0646\u0627 \u0627\u0644\u062A\u064A \u062A\u0639\u0643\u0633 \u062C\u0648\u062F\u0629 \u0639\u0645\u0644\u0646\u0627 \u0648\u062E\u0628\u0631\u062A\u0646\u0627 \u0641\u064A \u062A\u062D\u0648\u064A\u0644 \u0627\u0644\u0623\u0641\u0643\u0627\u0631 \u0625\u0644\u0649 \u0648\u0627\u0642\u0639 \u0631\u0642\u0645\u064A \u0645\u0645\u064A\u0632",
            "Real examples of our achievements that reflect the quality of our work and expertise in transforming ideas into outstanding digital solutions"
          ),
          className: "mb-0"
        }
      ),
      /* @__PURE__ */ jsxs("div", { className: "relative mx-auto mt-8 max-w-md", children: [
        /* @__PURE__ */ jsx(
          Search,
          {
            className: "pointer-events-none absolute start-4 top-1/2 h-5 w-5 -translate-y-1/2 text-muted-foreground",
            strokeWidth: 1.75
          }
        ),
        /* @__PURE__ */ jsx(
          "input",
          {
            type: "text",
            placeholder: t("\u0627\u0628\u062D\u062B \u0639\u0646 \u0645\u0634\u0631\u0648\u0639...", "Search for a project..."),
            value: searchQuery,
            onChange: (e) => onSearchChange(e.target.value),
            className: "w-full rounded-full border border-border bg-popover py-3.5 ps-12 pe-12 text-sm text-foreground placeholder:text-muted-foreground transition-all duration-200 ease-ds focus:border-primary/60 focus:outline-none focus:ring-2 focus:ring-primary/20"
          }
        ),
        searchQuery && /* @__PURE__ */ jsx(
          "button",
          {
            type: "button",
            onClick: () => onSearchChange(""),
            className: "absolute end-4 top-1/2 -translate-y-1/2 text-muted-foreground transition-colors duration-200 ease-ds hover:text-foreground",
            children: /* @__PURE__ */ jsx(X, { className: "h-5 w-5", strokeWidth: 1.75 })
          }
        )
      ] })
    ] })
  ] }) });
};
export default PortfolioHeroBoard;
