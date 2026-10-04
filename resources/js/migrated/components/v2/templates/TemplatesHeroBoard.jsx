import { jsx, jsxs } from "react/jsx-runtime";
import { Search, LibraryBig } from "lucide-react";
import { Board, Eyebrow, RiseIn } from "@/components/v2/primitives";
import { useLanguage } from "@/contexts/LanguageContext";
const TemplatesHeroBoard = ({ search, onSearch }) => {
  const { t, isRTL } = useLanguage();
  return /* @__PURE__ */ jsx(RiseIn, { mount: true, children: /* @__PURE__ */ jsxs(Board, { dir: isRTL ? "rtl" : "ltr", className: "text-center lg:py-16", children: [
    /* @__PURE__ */ jsx(
      "div",
      {
        "aria-hidden": true,
        className: "pointer-events-none absolute -top-40 start-1/2 h-[420px] w-[640px] -translate-x-1/2 opacity-70 rtl:translate-x-1/2",
        style: { background: "radial-gradient(closest-side, rgba(253,128,46,0.14), transparent 70%)" }
      }
    ),
    /* @__PURE__ */ jsxs("div", { className: "relative mx-auto max-w-3xl", children: [
      /* @__PURE__ */ jsx(Eyebrow, { className: "mb-4 justify-center", children: t("\u0645\u0648\u0627\u0631\u062F \u0645\u062C\u0627\u0646\u064A\u0629", "Free resources") }),
      /* @__PURE__ */ jsxs("h1", { className: "font-display text-3xl font-extrabold leading-tight tracking-tight text-foreground md:text-5xl", children: [
        t("\u0645\u0643\u062A\u0628\u0629 \u0642\u0648\u0627\u0644\u0628 \u0648\u0623\u062F\u0648\u0627\u062A \u0627\u0644\u062A\u0633\u0648\u064A\u0642", "Marketing Templates & Tools Hub"),
        /* @__PURE__ */ jsx("span", { className: "mt-1 block text-2xl text-primary md:text-3xl", children: t("\u0645\u0646 \u0641\u0627\u064A\u0646\u062F\u0648 \u062C\u0631\u0648\u0628", "by Findo Group") })
      ] }),
      /* @__PURE__ */ jsx("p", { className: "mx-auto mt-5 max-w-2xl text-base leading-relaxed text-muted-foreground md:text-lg", children: t(
        "\u0642\u0648\u0627\u0644\u0628 \u062C\u0627\u0647\u0632\u0629\u060C \u0627\u0633\u062A\u0631\u0627\u062A\u064A\u062C\u064A\u0627\u062A\u060C \u0648\u0645\u0644\u0641\u0627\u062A \u0627\u062D\u062A\u0631\u0627\u0641\u064A\u0629 \u0642\u0627\u0628\u0644\u0629 \u0644\u0644\u062A\u062D\u0645\u064A\u0644 \u0644\u0645\u0633\u0627\u0639\u062F\u062A\u0643 \u0641\u064A \u0646\u0645\u0648 \u0623\u0639\u0645\u0627\u0644\u0643.",
        "Ready-made templates, strategies and professional files, downloadable to help you grow your business."
      ) }),
      /* @__PURE__ */ jsxs("div", { className: "relative mx-auto mt-8 max-w-xl", children: [
        /* @__PURE__ */ jsx(
          Search,
          {
            className: "pointer-events-none absolute top-1/2 h-5 w-5 -translate-y-1/2 text-muted-foreground start-4",
            strokeWidth: 1.75
          }
        ),
        /* @__PURE__ */ jsx(
          "input",
          {
            type: "search",
            value: search,
            onChange: (e) => onSearch(e.target.value),
            placeholder: t("\u0627\u0628\u062D\u062B \u0639\u0646 \u0642\u0627\u0644\u0628 \u0628\u0627\u0644\u0627\u0633\u0645...", "Search templates by name..."),
            "aria-label": t("\u0627\u0628\u062D\u062B \u0639\u0646 \u0642\u0627\u0644\u0628", "Search templates"),
            className: "w-full rounded-full border border-border bg-popover py-3.5 ps-12 pe-5 text-foreground placeholder:text-muted-foreground shadow-card transition-all duration-200 ease-ds focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/30"
          }
        )
      ] }),
      /* @__PURE__ */ jsxs("p", { className: "mt-4 inline-flex items-center gap-2 text-xs text-muted-foreground", children: [
        /* @__PURE__ */ jsx(LibraryBig, { className: "h-4 w-4 text-primary", strokeWidth: 1.75 }),
        t("\u0642\u0648\u0627\u0644\u0628 \u062C\u062F\u064A\u062F\u0629 \u062A\u064F\u0636\u0627\u0641 \u0628\u0627\u0633\u062A\u0645\u0631\u0627\u0631", "New templates added regularly")
      ] })
    ] })
  ] }) });
};
export default TemplatesHeroBoard;
