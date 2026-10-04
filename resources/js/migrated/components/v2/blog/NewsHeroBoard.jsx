import { jsx, jsxs } from "react/jsx-runtime";
import { Board, BoardHeader, RiseIn } from "@/components/v2/primitives";
import { useLanguage } from "@/contexts/LanguageContext";
const NewsHeroBoard = () => {
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
    /* @__PURE__ */ jsx("div", { className: "relative", children: /* @__PURE__ */ jsx(
      BoardHeader,
      {
        index: "01",
        as: "h1",
        eyebrow: t("\u0622\u062E\u0631 \u0627\u0644\u062A\u062D\u062F\u064A\u062B\u0627\u062A \u0648\u0627\u0644\u0623\u062E\u0628\u0627\u0631", "Latest Updates & News"),
        pre: t("\u0622\u062E\u0631", "Latest"),
        accent: t("\u0627\u0644\u0623\u062E\u0628\u0627\u0631", "News"),
        headingClassName: "text-4xl md:text-5xl lg:text-6xl leading-tight",
        subtitle: t(
          "\u062A\u0627\u0628\u0639 \u0623\u062D\u062F\u062B \u0623\u062E\u0628\u0627\u0631 \u0648\u0625\u0646\u062C\u0627\u0632\u0627\u062A \u0634\u0631\u0643\u0629 \u0641\u0627\u064A\u0646\u062F\u0648 \u062C\u0631\u0648\u0628",
          "Follow the latest news and achievements of Findo"
        ),
        className: "mb-0"
      }
    ) })
  ] }) });
};
export default NewsHeroBoard;
