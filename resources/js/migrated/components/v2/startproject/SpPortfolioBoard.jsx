import { jsx, jsxs } from "react/jsx-runtime";
import { useRef, useState } from "react";
import { Link } from "react-router-dom";
import { ArrowLeft, ArrowRight, Smartphone, Monitor, Palette, ExternalLink } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Board, BoardHeader, RiseIn } from "@/components/v2/primitives";
import { useLanguage } from "@/contexts/LanguageContext";
import { projects } from "@/data/projects";
const FEATURED_IDS = [3, 7, 9, 16, 50, 33];
const SpPortfolioBoard = () => {
  const { t, isRTL } = useLanguage();
  const trackRef = useRef(null);
  const [isDragging, setIsDragging] = useState(false);
  const dragState = useRef({ startX: 0, scrollLeft: 0, moved: false });
  const featured = FEATURED_IDS.map((id) => projects.find((p) => p.id === id)).filter((p) => Boolean(p));
  const getTypeIcon = (type) => {
    if (type === "app") return Smartphone;
    if (type === "design") return Palette;
    return Monitor;
  };
  const scroll = (dir, e) => {
    e?.stopPropagation();
    const el = trackRef.current;
    if (!el) return;
    const cardW = el.firstElementChild?.clientWidth ?? 340;
    const gap = 20;
    const step = cardW + gap;
    const target = el.scrollLeft + (dir === "next" ? step : -step) * (isRTL ? -1 : 1);
    el.scrollTo({ left: target, behavior: "smooth" });
  };
  const onMouseDown = (e) => {
    const el = trackRef.current;
    if (!el) return;
    setIsDragging(true);
    dragState.current = {
      startX: e.pageX - el.offsetLeft,
      scrollLeft: el.scrollLeft,
      moved: false
    };
    el.style.scrollSnapType = "none";
  };
  const onMouseMove = (e) => {
    if (!isDragging) return;
    const el = trackRef.current;
    if (!el) return;
    e.preventDefault();
    const x = e.pageX - el.offsetLeft;
    const walk = (x - dragState.current.startX) * (isRTL ? 1 : -1) * 1.5;
    el.scrollLeft = dragState.current.scrollLeft + walk;
    if (Math.abs(walk) > 5) dragState.current.moved = true;
  };
  const onMouseUp = () => {
    const el = trackRef.current;
    if (el) el.style.scrollSnapType = "x mandatory";
    setIsDragging(false);
  };
  const onLinkClick = (e) => {
    if (dragState.current.moved) e.preventDefault();
  };
  const arrowBtn = "grid h-10 w-10 place-items-center rounded-full border border-border bg-popover text-foreground transition-all duration-200 ease-ds hover:border-primary/50 hover:bg-primary/15 hover:text-primary cursor-pointer";
  return /* @__PURE__ */ jsx(RiseIn, { children: /* @__PURE__ */ jsxs(Board, { id: "featured-portfolio", dir: isRTL ? "rtl" : "ltr", children: [
    /* @__PURE__ */ jsx(
      BoardHeader,
      {
        index: "03",
        eyebrow: t("\u0623\u0639\u0645\u0627\u0644\u0646\u0627", "Our Work"),
        pre: t("\u0645\u0634\u0627\u0631\u064A\u0639", "Projects We're"),
        accent: t("\u0646\u0641\u062A\u062E\u0631 \u0628\u0647\u0627", "Proud Of"),
        subtitle: t(
          "\u0646\u0645\u0627\u0630\u062C \u0645\u0646 \u0623\u0639\u0645\u0627\u0644\u0646\u0627 \u0627\u0644\u062A\u064A \u062A\u0639\u0643\u0633 \u062E\u0628\u0631\u062A\u0646\u0627 \u0648\u062C\u0648\u062F\u0629 \u0639\u0645\u0644\u0646\u0627",
          "A glimpse of our work reflecting our expertise."
        ),
        aside: (
          /* Arrow controls — desktop */
          /* @__PURE__ */ jsxs("div", { className: "hidden shrink-0 items-center gap-2 md:flex", children: [
            /* @__PURE__ */ jsx("button", { type: "button", onClick: (e) => scroll("prev", e), "aria-label": t("\u0627\u0644\u0633\u0627\u0628\u0642", "Previous"), className: arrowBtn, children: isRTL ? /* @__PURE__ */ jsx(ArrowRight, { className: "h-4 w-4", strokeWidth: 1.75 }) : /* @__PURE__ */ jsx(ArrowLeft, { className: "h-4 w-4", strokeWidth: 1.75 }) }),
            /* @__PURE__ */ jsx("button", { type: "button", onClick: (e) => scroll("next", e), "aria-label": t("\u0627\u0644\u062A\u0627\u0644\u064A", "Next"), className: arrowBtn, children: isRTL ? /* @__PURE__ */ jsx(ArrowLeft, { className: "h-4 w-4", strokeWidth: 1.75 }) : /* @__PURE__ */ jsx(ArrowRight, { className: "h-4 w-4", strokeWidth: 1.75 }) })
          ] })
        )
      }
    ),
    /* @__PURE__ */ jsx(
      "div",
      {
        ref: trackRef,
        onMouseDown,
        onMouseMove,
        onMouseUp,
        onMouseLeave: onMouseUp,
        className: `flex select-none gap-5 overflow-x-auto scroll-smooth pb-4 ${isDragging ? "cursor-grabbing" : "cursor-grab"}`,
        style: { scrollSnapType: "x mandatory", WebkitOverflowScrolling: "touch", scrollbarWidth: "none", msOverflowStyle: "none" },
        children: featured.map((project) => {
          const TypeIcon = getTypeIcon(project.type);
          return /* @__PURE__ */ jsx(
            "div",
            {
              style: { scrollSnapAlign: "start" },
              className: "w-[76vw] max-w-[360px] shrink-0 sm:w-[44vw] md:w-[36vw] lg:w-[28vw] xl:w-[24vw]",
              children: /* @__PURE__ */ jsxs(
                Link,
                {
                  to: `/portfolio/${project.id}`,
                  onClick: onLinkClick,
                  className: "group block h-full overflow-hidden rounded-3xl border border-border bg-popover transition-all duration-200 ease-ds hover:-translate-y-1.5 hover:border-primary/40",
                  children: [
                    /* @__PURE__ */ jsxs("div", { className: "relative aspect-[4/3] overflow-hidden", children: [
                      /* @__PURE__ */ jsx(
                        "img",
                        {
                          src: project.image,
                          alt: project.title,
                          loading: "lazy",
                          decoding: "async",
                          className: "h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                        }
                      ),
                      /* @__PURE__ */ jsx("div", { className: "absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100" }),
                      /* @__PURE__ */ jsxs("div", { className: "absolute end-3 top-3 flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold text-primary-foreground [background:var(--gradient-coral)]", children: [
                        /* @__PURE__ */ jsx(TypeIcon, { className: "h-3 w-3", strokeWidth: 1.75 }),
                        project.category
                      ] }),
                      /* @__PURE__ */ jsx("div", { className: "absolute bottom-3 start-3 opacity-0 transition-opacity duration-300 group-hover:opacity-100", children: /* @__PURE__ */ jsxs("span", { className: "inline-flex items-center gap-1.5 text-xs font-medium text-white", children: [
                        /* @__PURE__ */ jsx(ExternalLink, { className: "h-3.5 w-3.5", strokeWidth: 1.75 }),
                        t("\u0639\u0631\u0636 \u0627\u0644\u062A\u0641\u0627\u0635\u064A\u0644", "View Details")
                      ] }) })
                    ] }),
                    /* @__PURE__ */ jsxs("div", { className: "p-4", children: [
                      /* @__PURE__ */ jsx("h3", { className: "mb-1.5 line-clamp-1 text-base font-bold text-foreground transition-colors duration-200 ease-ds group-hover:text-primary", children: project.title }),
                      /* @__PURE__ */ jsx("p", { className: "line-clamp-2 text-xs leading-relaxed text-muted-foreground", children: project.description })
                    ] })
                  ]
                }
              )
            },
            project.id
          );
        })
      }
    ),
    /* @__PURE__ */ jsxs("div", { className: "mt-6 flex items-center justify-between", children: [
      /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-2 md:hidden", children: [
        /* @__PURE__ */ jsx("button", { type: "button", onClick: (e) => scroll("prev", e), "aria-label": t("\u0627\u0644\u0633\u0627\u0628\u0642", "Previous"), className: `${arrowBtn} h-9 w-9`, children: isRTL ? /* @__PURE__ */ jsx(ArrowRight, { className: "h-4 w-4", strokeWidth: 1.75 }) : /* @__PURE__ */ jsx(ArrowLeft, { className: "h-4 w-4", strokeWidth: 1.75 }) }),
        /* @__PURE__ */ jsx("button", { type: "button", onClick: (e) => scroll("next", e), "aria-label": t("\u0627\u0644\u062A\u0627\u0644\u064A", "Next"), className: `${arrowBtn} h-9 w-9`, children: isRTL ? /* @__PURE__ */ jsx(ArrowLeft, { className: "h-4 w-4", strokeWidth: 1.75 }) : /* @__PURE__ */ jsx(ArrowRight, { className: "h-4 w-4", strokeWidth: 1.75 }) })
      ] }),
      /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", className: "ms-auto", children: /* @__PURE__ */ jsxs(Link, { to: "/portfolio", children: [
        t("\u0639\u0631\u0636 \u0643\u0644 \u0627\u0644\u0645\u0634\u0627\u0631\u064A\u0639", "View All Projects"),
        /* @__PURE__ */ jsx(ArrowLeft, { className: isRTL ? "" : "rotate-180", strokeWidth: 1.75 })
      ] }) })
    ] })
  ] }) });
};
export default SpPortfolioBoard;
