import { jsx, jsxs } from "react/jsx-runtime";
import { Link } from "react-router-dom";
import { Calendar, Clock, TrendingUp } from "lucide-react";
import { getFeaturedImageUrl, getCategoryNames, formatDate, stripHtml } from "@/lib/wordpress";
import { Board, Chip, Eyebrow, RiseIn } from "@/components/v2/primitives";
import { getReadingTime } from "./PostCard";
import { useLanguage } from "@/contexts/LanguageContext";
const NewsFeaturedBoard = ({ featuredPost, sidebarPosts }) => {
  const { t, isRTL } = useLanguage();
  return /* @__PURE__ */ jsx(RiseIn, { children: /* @__PURE__ */ jsxs(Board, { dir: isRTL ? "rtl" : "ltr", children: [
    /* @__PURE__ */ jsx(Eyebrow, { index: "02", className: "mb-6", children: t("\u0627\u0644\u062E\u0628\u0631 \u0627\u0644\u0631\u0626\u064A\u0633\u064A", "Top Story") }),
    /* @__PURE__ */ jsxs("div", { className: "grid grid-cols-1 gap-4 lg:grid-cols-3", children: [
      featuredPost && /* @__PURE__ */ jsxs(
        Link,
        {
          to: `/blogs/${featuredPost.slug}`,
          className: "group relative block h-[400px] overflow-hidden rounded-3xl border border-border md:h-[500px] lg:col-span-2",
          children: [
            /* @__PURE__ */ jsx(
              "img",
              {
                src: getFeaturedImageUrl(featuredPost),
                alt: stripHtml(featuredPost.title.rendered),
                className: "absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
              }
            ),
            /* @__PURE__ */ jsx("div", { className: "absolute inset-0 bg-gradient-to-t from-background via-background/70 to-transparent" }),
            /* @__PURE__ */ jsx(
              Chip,
              {
                variant: "destructive",
                icon: /* @__PURE__ */ jsx(TrendingUp, { className: "h-4 w-4", strokeWidth: 1.75 }),
                className: "absolute top-5 start-5 animate-pulse border-destructive/50 bg-background/70 backdrop-blur-sm",
                children: t("\u062E\u0628\u0631 \u0631\u0626\u064A\u0633\u064A", "Breaking News")
              }
            ),
            /* @__PURE__ */ jsxs("div", { className: "absolute inset-x-0 bottom-0 p-6 md:p-8", children: [
              /* @__PURE__ */ jsx("div", { className: "mb-4 flex flex-wrap gap-2", children: getCategoryNames(featuredPost).slice(0, 2).map((category) => /* @__PURE__ */ jsx(Chip, { variant: "featured", children: category }, category)) }),
              /* @__PURE__ */ jsx(
                "h2",
                {
                  className: "mb-3 line-clamp-3 font-display text-2xl font-extrabold leading-snug text-foreground transition-colors duration-200 ease-ds group-hover:text-primary md:text-3xl",
                  dangerouslySetInnerHTML: { __html: featuredPost.title.rendered }
                }
              ),
              /* @__PURE__ */ jsx("p", { className: "mb-4 line-clamp-2 max-w-2xl text-base text-muted-foreground", children: stripHtml(featuredPost.excerpt.rendered) }),
              /* @__PURE__ */ jsxs("div", { className: "flex flex-wrap items-center gap-4 text-sm text-muted-foreground", children: [
                /* @__PURE__ */ jsxs("span", { className: "flex items-center gap-2", children: [
                  /* @__PURE__ */ jsx(Calendar, { className: "h-4 w-4 text-primary", strokeWidth: 1.75 }),
                  isRTL ? formatDate(featuredPost.date) : new Date(featuredPost.date).toLocaleDateString("en-US", {
                    year: "numeric",
                    month: "long",
                    day: "numeric"
                  })
                ] }),
                /* @__PURE__ */ jsxs("span", { className: "flex items-center gap-2", children: [
                  /* @__PURE__ */ jsx(Clock, { className: "h-4 w-4 text-primary", strokeWidth: 1.75 }),
                  t(
                    `${getReadingTime(featuredPost.content.rendered)} \u062F\u0642\u0627\u0626\u0642 \u0642\u0631\u0627\u0621\u0629`,
                    `${getReadingTime(featuredPost.content.rendered)} min read`
                  )
                ] })
              ] })
            ] })
          ]
        }
      ),
      /* @__PURE__ */ jsx("div", { className: "flex flex-col gap-4", children: sidebarPosts.map((post) => /* @__PURE__ */ jsxs(
        Link,
        {
          to: `/blogs/${post.slug}`,
          className: "group flex gap-4 rounded-2xl border border-border bg-popover p-3.5 transition-all duration-200 ease-ds hover:-translate-y-0.5 hover:border-primary/40",
          children: [
            /* @__PURE__ */ jsx("div", { className: "h-24 w-24 shrink-0 overflow-hidden rounded-xl", children: /* @__PURE__ */ jsx(
              "img",
              {
                src: getFeaturedImageUrl(post),
                alt: stripHtml(post.title.rendered),
                loading: "lazy",
                decoding: "async",
                className: "h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
              }
            ) }),
            /* @__PURE__ */ jsxs("div", { className: "flex min-w-0 flex-1 flex-col justify-center", children: [
              /* @__PURE__ */ jsx("span", { className: "mb-1 text-xs font-bold text-primary", children: getCategoryNames(post)[0] || t("\u0623\u062E\u0628\u0627\u0631", "News") }),
              /* @__PURE__ */ jsx(
                "h3",
                {
                  className: "mb-2 line-clamp-2 text-sm font-bold leading-snug text-foreground transition-colors duration-200 ease-ds group-hover:text-primary",
                  dangerouslySetInnerHTML: { __html: post.title.rendered }
                }
              ),
              /* @__PURE__ */ jsxs("span", { className: "flex items-center gap-1.5 text-xs text-muted-foreground", children: [
                /* @__PURE__ */ jsx(Calendar, { className: "h-3 w-3 text-primary", strokeWidth: 1.75 }),
                isRTL ? formatDate(post.date) : new Date(post.date).toLocaleDateString("en-US", {
                  year: "numeric",
                  month: "short",
                  day: "numeric"
                })
              ] })
            ] })
          ]
        },
        post.id
      )) })
    ] })
  ] }) });
};
export default NewsFeaturedBoard;
