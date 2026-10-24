import { jsx, jsxs } from "react/jsx-runtime";
import { Link } from "react-router-dom";
import { ArrowLeft, ArrowRight, Calendar, Clock } from "lucide-react";
import { getFeaturedImageUrl, getCategoryNames, stripHtml, formatDate } from "@/lib/wordpress";
import { Skeleton } from "@/components/ui/skeleton";
import { Chip } from "@/components/v2/primitives";
import { useLanguage } from "@/contexts/LanguageContext";
export const getReadingTime = (content) => {
  const wordsPerMinute = 200;
  const wordCount = stripHtml(content || "").split(/\s+/).length;
  return Math.max(1, Math.ceil(wordCount / wordsPerMinute));
};
const PostCard = ({ post }) => {
  const { t, isRTL } = useLanguage();
  const ArrowIcon = isRTL ? ArrowLeft : ArrowRight;
  const imageUrl = getFeaturedImageUrl(post);
  const categories = getCategoryNames(post);
  const excerpt = stripHtml(post.excerpt.rendered);
  const readingTime = post.readingTime ?? getReadingTime(post.content?.rendered || "");
  return /* @__PURE__ */ jsxs(
    Link,
    {
      to: `/blogs/${post.slug}`,
      className: "group flex h-full flex-col overflow-hidden rounded-3xl border border-border bg-popover transition-all duration-200 ease-ds hover:-translate-y-1 hover:border-primary/40",
      children: [
        /* @__PURE__ */ jsxs("div", { className: "relative aspect-[16/10] overflow-hidden", children: [
          /* @__PURE__ */ jsx(
            "img",
            {
              src: imageUrl,
              alt: stripHtml(post.title.rendered),
              loading: "lazy",
              decoding: "async",
              className: "h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
            }
          ),
          categories.length > 0 && /* @__PURE__ */ jsx(Chip, { variant: "featured", className: "absolute top-3 start-3 shadow-lg", children: categories[0] })
        ] }),
        /* @__PURE__ */ jsxs("div", { className: "flex grow flex-col p-5", children: [
          /* @__PURE__ */ jsxs("div", { className: "mb-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground", children: [
            /* @__PURE__ */ jsxs("span", { className: "flex items-center gap-2", children: [
              /* @__PURE__ */ jsx(Calendar, { className: "h-4 w-4 text-primary", strokeWidth: 1.75 }),
              /* @__PURE__ */ jsx("time", { dateTime: post.date, className: "font-display", children: formatDate(post.date) })
            ] }),
            /* @__PURE__ */ jsxs("span", { className: "flex items-center gap-2", children: [
              /* @__PURE__ */ jsx(Clock, { className: "h-4 w-4 text-primary", strokeWidth: 1.75 }),
              t(`${readingTime} \u062F\u0642\u0627\u0626\u0642 \u0642\u0631\u0627\u0621\u0629`, `${readingTime} min read`)
            ] })
          ] }),
          /* @__PURE__ */ jsx(
            "h3",
            {
              className: "line-clamp-2 text-lg font-bold leading-snug text-foreground transition-colors duration-200 ease-ds group-hover:text-primary",
              dangerouslySetInnerHTML: { __html: post.title.rendered }
            }
          ),
          /* @__PURE__ */ jsx("p", { className: "mt-2 line-clamp-2 grow text-sm leading-relaxed text-muted-foreground", children: excerpt }),
          /* @__PURE__ */ jsxs("span", { className: "mt-4 inline-flex items-center gap-1.5 border-t border-border pt-4 text-sm font-semibold text-primary transition-all duration-200 ease-ds group-hover:gap-3", children: [
            t("\u0627\u0642\u0631\u0623 \u0627\u0644\u0645\u0632\u064A\u062F", "Read more"),
            /* @__PURE__ */ jsx(ArrowIcon, { className: "h-4 w-4", strokeWidth: 1.75 })
          ] })
        ] })
      ]
    }
  );
};
export const PostCardSkeleton = () => /* @__PURE__ */ jsxs("div", { className: "overflow-hidden rounded-3xl border border-border bg-popover", children: [
  /* @__PURE__ */ jsx(Skeleton, { className: "aspect-[16/10] w-full rounded-2xl" }),
  /* @__PURE__ */ jsxs("div", { className: "space-y-3 p-5", children: [
    /* @__PURE__ */ jsx(Skeleton, { className: "h-4 w-24" }),
    /* @__PURE__ */ jsx(Skeleton, { className: "h-5 w-full" }),
    /* @__PURE__ */ jsx(Skeleton, { className: "h-5 w-3/4" }),
    /* @__PURE__ */ jsx(Skeleton, { className: "h-4 w-28" })
  ] })
] });
export default PostCard;
