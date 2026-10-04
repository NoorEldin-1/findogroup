import { jsx, jsxs } from "react/jsx-runtime";
import { Link } from "react-router-dom";
import { Calendar, Clock, Copy, Eye, Facebook, Linkedin, MessageCircle, Tag, Twitter, User } from "lucide-react";
import { getFeaturedImageUrl, getCategoryNames, formatDate, stripHtml } from "@/lib/wordpress";
import { formatViews } from "@/hooks/useViewCounter";
import { Board, Chip, RiseIn } from "@/components/v2/primitives";
const PostHeroBoard = ({ post, readingTime, views, commentsCount, onShare }) => {
  const imageUrl = getFeaturedImageUrl(post);
  const categories = getCategoryNames(post);
  const shareButtons = [
    { icon: Facebook, platform: "facebook", label: "\u0641\u064A\u0633\u0628\u0648\u0643" },
    { icon: Twitter, platform: "twitter", label: "\u062A\u0648\u064A\u062A\u0631" },
    { icon: Linkedin, platform: "linkedin", label: "\u0644\u064A\u0646\u0643\u062F\u0625\u0646" },
    { icon: Copy, platform: "copy", label: "\u0646\u0633\u062E \u0627\u0644\u0631\u0627\u0628\u0637" }
  ];
  return /* @__PURE__ */ jsx(RiseIn, { mount: true, children: /* @__PURE__ */ jsxs(Board, { as: "header", dir: "rtl", className: "mx-auto w-full max-w-5xl", children: [
    /* @__PURE__ */ jsx(
      "div",
      {
        "aria-hidden": true,
        className: "pointer-events-none absolute -top-40 start-1/4 h-[380px] w-[540px] opacity-70",
        style: { background: "radial-gradient(closest-side, rgba(253,128,46,0.14), transparent 70%)" }
      }
    ),
    /* @__PURE__ */ jsxs("div", { className: "relative", children: [
      /* @__PURE__ */ jsxs("nav", { className: "mb-6 flex flex-wrap items-center gap-2 text-sm text-muted-foreground", children: [
        /* @__PURE__ */ jsx(Link, { to: "/", className: "transition-colors duration-200 ease-ds hover:text-foreground", children: "\u0627\u0644\u0631\u0626\u064A\u0633\u064A\u0629" }),
        /* @__PURE__ */ jsx("span", { "aria-hidden": true, className: "text-muted-foreground/50", children: "/" }),
        /* @__PURE__ */ jsx(Link, { to: "/blogs", className: "transition-colors duration-200 ease-ds hover:text-foreground", children: "\u0627\u0644\u0645\u062F\u0648\u0646\u0629" }),
        /* @__PURE__ */ jsx("span", { "aria-hidden": true, className: "text-muted-foreground/50", children: "/" }),
        /* @__PURE__ */ jsx(
          "span",
          {
            className: "line-clamp-1 text-foreground/80",
            dangerouslySetInnerHTML: { __html: post.title.rendered }
          }
        )
      ] }),
      /* @__PURE__ */ jsx("div", { className: "mb-5 flex flex-wrap gap-2", children: categories.map((category) => /* @__PURE__ */ jsx(Chip, { variant: "featured", icon: /* @__PURE__ */ jsx(Tag, { className: "h-3.5 w-3.5", strokeWidth: 1.75 }), children: category }, category)) }),
      /* @__PURE__ */ jsx(
        "h1",
        {
          className: "mb-6 font-display text-3xl font-extrabold leading-tight tracking-tight text-foreground md:text-5xl",
          dangerouslySetInnerHTML: { __html: post.title.rendered }
        }
      ),
      /* @__PURE__ */ jsxs("div", { className: "mb-6 flex flex-wrap items-center gap-x-6 gap-y-3 text-sm text-muted-foreground", children: [
        /* @__PURE__ */ jsxs("span", { className: "flex items-center gap-2", children: [
          /* @__PURE__ */ jsx(User, { className: "h-4 w-4 text-primary", strokeWidth: 1.75 }),
          "\u0641\u0631\u064A\u0642 \u0641\u0627\u064A\u0646\u062F\u0648 \u062C\u0631\u0648\u0628"
        ] }),
        /* @__PURE__ */ jsxs("span", { className: "flex items-center gap-2", children: [
          /* @__PURE__ */ jsx(Calendar, { className: "h-4 w-4 text-primary", strokeWidth: 1.75 }),
          /* @__PURE__ */ jsx("time", { dateTime: post.date, className: "font-display", children: formatDate(post.date) })
        ] }),
        /* @__PURE__ */ jsxs("span", { className: "flex items-center gap-2", children: [
          /* @__PURE__ */ jsx(Clock, { className: "h-4 w-4 text-primary", strokeWidth: 1.75 }),
          readingTime,
          " \u062F\u0642\u0627\u0626\u0642 \u0642\u0631\u0627\u0621\u0629"
        ] }),
        /* @__PURE__ */ jsxs("span", { className: "flex items-center gap-2", children: [
          /* @__PURE__ */ jsx(Eye, { className: "h-4 w-4 text-primary", strokeWidth: 1.75 }),
          formatViews(views),
          " \u0645\u0634\u0627\u0647\u062F\u0629"
        ] }),
        /* @__PURE__ */ jsxs("span", { className: "flex items-center gap-2", children: [
          /* @__PURE__ */ jsx(MessageCircle, { className: "h-4 w-4 text-primary", strokeWidth: 1.75 }),
          commentsCount,
          " \u062A\u0639\u0644\u064A\u0642\u0627\u062A"
        ] })
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "mb-8 flex items-center gap-2.5", children: [
        /* @__PURE__ */ jsx("span", { className: "text-sm text-muted-foreground", children: "\u0645\u0634\u0627\u0631\u0643\u0629:" }),
        shareButtons.map(({ icon: Icon, platform, label }) => /* @__PURE__ */ jsx(
          "button",
          {
            type: "button",
            onClick: () => onShare(platform),
            "aria-label": label,
            className: "grid h-10 w-10 place-items-center rounded-xl border border-border bg-popover text-muted-foreground transition-all duration-200 ease-ds hover:border-primary/60 hover:text-primary",
            children: /* @__PURE__ */ jsx(Icon, { className: "h-4 w-4", strokeWidth: 1.75 })
          },
          platform
        ))
      ] }),
      /* @__PURE__ */ jsx("div", { className: "overflow-hidden rounded-2xl border border-border", children: /* @__PURE__ */ jsx(
        "img",
        {
          src: imageUrl,
          alt: stripHtml(post.title.rendered),
          className: "aspect-[16/9] w-full object-cover"
        }
      ) })
    ] })
  ] }) });
};
export default PostHeroBoard;
