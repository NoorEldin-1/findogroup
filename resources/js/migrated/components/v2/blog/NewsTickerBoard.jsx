import { jsx, jsxs } from "react/jsx-runtime";
import { Link } from "react-router-dom";
import { motion } from "framer-motion";
import { Newspaper } from "lucide-react";
import { Board, Chip, RiseIn } from "@/components/v2/primitives";
import { useLanguage } from "@/contexts/LanguageContext";
const NewsTickerBoard = ({ posts }) => {
  const { t, isRTL } = useLanguage();
  return /* @__PURE__ */ jsx(RiseIn, { children: /* @__PURE__ */ jsx(Board, { dir: isRTL ? "rtl" : "ltr", className: "p-4 sm:p-5 lg:p-6", children: /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-4", children: [
    /* @__PURE__ */ jsx(
      Chip,
      {
        variant: "featured",
        icon: /* @__PURE__ */ jsx(Newspaper, { className: "h-4 w-4", strokeWidth: 1.75 }),
        className: "shrink-0",
        children: t("\u0622\u062E\u0631 \u0627\u0644\u062A\u062D\u062F\u064A\u062B\u0627\u062A", "Latest Updates")
      }
    ),
    /* @__PURE__ */ jsx("div", { className: "flex-1 overflow-hidden", children: /* @__PURE__ */ jsx(
      motion.div,
      {
        animate: { x: ["100%", "-100%"] },
        transition: { duration: 20, repeat: Infinity, ease: "linear" },
        className: "flex gap-12 whitespace-nowrap",
        children: posts.slice(0, 5).map((post) => /* @__PURE__ */ jsxs(
          Link,
          {
            to: `/blogs/${post.slug}`,
            className: "inline-flex items-center gap-2 text-sm text-foreground transition-colors duration-200 ease-ds hover:text-primary",
            children: [
              /* @__PURE__ */ jsx("span", { "aria-hidden": true, className: "h-2 w-2 rounded-full bg-primary" }),
              /* @__PURE__ */ jsx("span", { dangerouslySetInnerHTML: { __html: post.title.rendered } })
            ]
          },
          post.id
        ))
      }
    ) })
  ] }) }) });
};
export default NewsTickerBoard;
