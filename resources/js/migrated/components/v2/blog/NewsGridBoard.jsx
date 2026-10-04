import { jsx, jsxs } from "react/jsx-runtime";
import { Link } from "react-router-dom";
import { ArrowLeft, ArrowRight, Calendar, Clock } from "lucide-react";
import { getFeaturedImageUrl, getCategoryNames, formatDate, stripHtml } from "@/lib/wordpress";
import { Board, BoardHeader, Chip, RiseIn } from "@/components/v2/primitives";
import { getReadingTime } from "./PostCard";
import { useLanguage } from "@/contexts/LanguageContext";
const NewsGridBoard = ({ posts }) => {
  const { t, isRTL } = useLanguage();
  const ArrowIcon = isRTL ? ArrowLeft : ArrowRight;
  return /* @__PURE__ */ jsx(RiseIn, { children: /* @__PURE__ */ jsxs(Board, { dir: isRTL ? "rtl" : "ltr", children: [
    /* @__PURE__ */ jsx(
      BoardHeader,
      {
        index: "03",
        eyebrow: t("\u0627\u0644\u0645\u0632\u064A\u062F \u0645\u0646 \u0627\u0644\u0623\u062E\u0628\u0627\u0631", "More News"),
        pre: t("\u0623\u062E\u0628\u0627\u0631", "Various"),
        accent: t("\u0645\u062A\u0646\u0648\u0639\u0629", "News"),
        aside: /* @__PURE__ */ jsxs(
          Link,
          {
            to: "/blogs",
            className: "inline-flex items-center gap-2 text-sm font-semibold text-primary transition-all duration-200 ease-ds hover:gap-3",
            children: [
              t("\u062C\u0645\u064A\u0639 \u0627\u0644\u0645\u0642\u0627\u0644\u0627\u062A", "All Articles"),
              /* @__PURE__ */ jsx(ArrowIcon, { className: "h-4 w-4", strokeWidth: 1.75 })
            ]
          }
        )
      }
    ),
    /* @__PURE__ */ jsx("div", { className: "grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4", children: posts.map((post) => /* @__PURE__ */ jsxs(
      Link,
      {
        to: `/blogs/${post.slug}`,
        className: "group flex h-full flex-col overflow-hidden rounded-3xl border border-border bg-popover transition-all duration-200 ease-ds hover:-translate-y-1 hover:border-primary/40",
        children: [
          /* @__PURE__ */ jsxs("div", { className: "relative aspect-[16/10] overflow-hidden", children: [
            /* @__PURE__ */ jsx(
              "img",
              {
                src: getFeaturedImageUrl(post),
                alt: stripHtml(post.title.rendered),
                loading: "lazy",
                decoding: "async",
                className: "h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
              }
            ),
            /* @__PURE__ */ jsx(Chip, { variant: "featured", className: "absolute top-3 start-3 shadow-lg", children: getCategoryNames(post)[0] || t("\u0623\u062E\u0628\u0627\u0631", "News") })
          ] }),
          /* @__PURE__ */ jsxs("div", { className: "flex grow flex-col p-4", children: [
            /* @__PURE__ */ jsxs("div", { className: "mb-2 flex flex-wrap items-center gap-2 text-xs text-muted-foreground", children: [
              /* @__PURE__ */ jsx(Calendar, { className: "h-3.5 w-3.5 text-primary", strokeWidth: 1.75 }),
              /* @__PURE__ */ jsx("time", { dateTime: post.date, className: "font-display", children: isRTL ? formatDate(post.date) : new Date(post.date).toLocaleDateString("en-US", {
                month: "short",
                day: "numeric",
                year: "numeric"
              }) }),
              /* @__PURE__ */ jsx("span", { "aria-hidden": true, children: "\u2022" }),
              /* @__PURE__ */ jsx(Clock, { className: "h-3.5 w-3.5 text-primary", strokeWidth: 1.75 }),
              /* @__PURE__ */ jsx("span", { children: t(`${getReadingTime(post.content.rendered)} \u062F`, `${getReadingTime(post.content.rendered)} min`) })
            ] }),
            /* @__PURE__ */ jsx(
              "h3",
              {
                className: "line-clamp-2 grow text-base font-bold leading-snug text-foreground transition-colors duration-200 ease-ds group-hover:text-primary",
                dangerouslySetInnerHTML: { __html: post.title.rendered }
              }
            )
          ] })
        ]
      },
      post.id
    )) })
  ] }) });
};
export default NewsGridBoard;
