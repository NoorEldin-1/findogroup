import {cmsCollection} from '@/adapters/cms.js';
import { Fragment, jsx, jsxs } from "react/jsx-runtime";
import { useRef, useState } from "react";
import { Play, Star, BadgeCheck } from "lucide-react";
import {
  Carousel,
  CarouselContent,
  CarouselItem,
  CarouselNext,
  CarouselPrevious
} from "@/components/ui/carousel";
import { Board, BoardHeader, RiseIn } from "@/components/v2/primitives";
import { useLanguage } from "@/contexts/LanguageContext";
const VIDEO_BASE = "/wp-content/uploads/testimonials";
const originalVids = [
  { id: "t3", aspect: "portrait", duration: "PT46S" },
  // striped man — front (user-pinned layout)
  { id: "t5", aspect: "portrait", duration: "PT1M5S" },
  // thobe + AC — front
  { id: "t6", aspect: "wide", duration: "PT33S" },
  // red headdress (near-square) — front
  { id: "t4", aspect: "portrait", duration: "PT34S" },
  // hijab woman — front
  { id: "t1", aspect: "portrait", duration: "PT37S" },
  // woman — reached by scroll
  { id: "t2", aspect: "portrait", duration: "PT47S" }
  // young man, suit — reached by scroll
];
const VIDS=cmsCollection("testimonials",originalVids);
const srcOf = (id) => (VIDS.find(v=>v.id===id)?.video || `${VIDEO_BASE}/${id}.mp4`);
const posterOf = (id) => (VIDS.find(v=>v.id===id)?.poster || `${VIDEO_BASE}/${id}.jpg`);
const videoSchema = {
  "@context": "https://schema.org",
  "@graph": VIDS.map((v, i) => ({
    "@type": "VideoObject",
    name: `\u0631\u0623\u064A \u0639\u0645\u064A\u0644 \u0641\u0627\u064A\u0646\u062F\u0648 \u062C\u0631\u0648\u0628 \u0631\u0642\u0645 ${i + 1}`,
    description: "\u0634\u0647\u0627\u062F\u0629 \u0639\u0645\u064A\u0644 \u062D\u0642\u064A\u0642\u064A \u0639\u0646 \u062A\u062C\u0631\u0628\u062A\u0647 \u0645\u0639 \u0641\u0627\u064A\u0646\u062F\u0648 \u062C\u0631\u0648\u0628 \u0641\u064A \u062A\u0646\u0641\u064A\u0630 \u0645\u0634\u0631\u0648\u0639\u0647 \u0627\u0644\u062A\u0642\u0646\u064A \u0628\u0627\u0644\u0648\u0637\u0646 \u0627\u0644\u0639\u0631\u0628\u064A.",
    thumbnailUrl: posterOf(v.id),
    contentUrl: srcOf(v.id),
    // full ISO-8601 datetime WITH timezone — Google flags date-only / no-tz values
    uploadDate: "2026-07-17T09:00:00+02:00",
    duration: v.duration
  }))
};
const Stars = () => /* @__PURE__ */ jsx("span", { className: "flex gap-0.5", children: Array.from({ length: 5 }).map((_, i) => /* @__PURE__ */ jsx(Star, { className: "h-3 w-3 text-primary", fill: "currentColor", strokeWidth: 0 }, i)) });
const VideoCard = ({
  vid,
  label,
  onPlay
}) => {
  const ref = useRef(null);
  const [started, setStarted] = useState(false);
  const start = () => {
    const el = ref.current;
    if (!el) return;
    setStarted(true);
    el.play().catch(() => {
    });
    onPlay(el);
  };
  const reset = (e) => {
    e.currentTarget.currentTime = 0;
    setStarted(false);
  };
  return /* @__PURE__ */ jsx("div", { className: "group relative flex h-full flex-col overflow-hidden rounded-3xl border-2 border-primary/70 bg-black shadow-[0_18px_45px_-14px_rgba(253,128,46,0.4)] transition-shadow duration-300 ease-ds hover:border-primary hover:shadow-[0_22px_55px_-12px_rgba(253,128,46,0.55)]", children: /* @__PURE__ */ jsxs("div", { className: "relative aspect-[9/16] w-full overflow-hidden bg-black", children: [
    vid.aspect === "wide" && /* @__PURE__ */ jsx(
      "div",
      {
        "aria-hidden": true,
        className: "absolute inset-0 scale-125 bg-cover bg-center opacity-45 blur-2xl",
        style: { backgroundImage: `url(${posterOf(vid.id)})` }
      }
    ),
    /* @__PURE__ */ jsx(
      "video",
      {
        ref,
        src: srcOf(vid.id),
        poster: posterOf(vid.id),
        preload: "none",
        playsInline: true,
        controls: started,
        controlsList: "nodownload noremoteplayback",
        disablePictureInPicture: true,
        onContextMenu: (e) => e.preventDefault(),
        onEnded: reset,
        className: `relative h-full w-full ${vid.aspect === "wide" ? "object-contain" : "object-cover"}`
      }
    ),
    !started && /* @__PURE__ */ jsxs(Fragment, { children: [
      /* @__PURE__ */ jsxs("div", { className: "pointer-events-none absolute inset-x-0 top-0 flex items-center gap-2 bg-gradient-to-b from-black/55 to-transparent px-3 py-2.5", children: [
        /* @__PURE__ */ jsx("span", { className: "grid h-7 w-7 flex-shrink-0 place-items-center rounded-full bg-primary text-primary-foreground", children: /* @__PURE__ */ jsx(BadgeCheck, { className: "h-4 w-4", strokeWidth: 2 }) }),
        /* @__PURE__ */ jsx("span", { className: "truncate text-xs font-semibold text-white/95", children: label }),
        /* @__PURE__ */ jsx("span", { className: "ms-auto flex-shrink-0", children: /* @__PURE__ */ jsx(Stars, {}) })
      ] }),
      /* @__PURE__ */ jsx(
        "button",
        {
          type: "button",
          onClick: start,
          "aria-label": "\u062A\u0634\u063A\u064A\u0644 \u0627\u0644\u0641\u064A\u062F\u064A\u0648",
          className: "absolute inset-0 flex items-center justify-center bg-gradient-to-t from-black/45 via-transparent to-transparent",
          children: /* @__PURE__ */ jsx("span", { className: "grid h-16 w-16 place-items-center rounded-full bg-primary text-primary-foreground shadow-xl ring-4 ring-primary/25 transition-transform duration-300 ease-ds group-hover:scale-110", children: /* @__PURE__ */ jsx(Play, { className: "h-7 w-7 translate-x-0.5", fill: "currentColor", strokeWidth: 0 }) })
        }
      )
    ] })
  ] }) });
};
const TestimonialsBoard = () => {
  const { t, isRTL } = useLanguage();
  const rootRef = useRef(null);
  const handlePlay = (el) => {
    rootRef.current?.querySelectorAll("video").forEach((v) => {
      if (v !== el) v.pause();
    });
  };
  return /* @__PURE__ */ jsxs(RiseIn, { children: [
    /* @__PURE__ */ jsx(
      "script",
      {
        type: "application/ld+json",
        dangerouslySetInnerHTML: { __html: JSON.stringify(videoSchema) }
      }
    ),
    /* @__PURE__ */ jsxs(Board, { band: true, dir: isRTL ? "rtl" : "ltr", children: [
      /* @__PURE__ */ jsx(
        BoardHeader,
        {
          index: "07",
          eyebrow: t("\u0634\u0647\u0627\u062F\u0627\u062A \u0627\u0644\u0639\u0645\u0644\u0627\u0621", "Testimonials"),
          pre: t("\u0645\u0627\u0630\u0627 \u064A\u0642\u0648\u0644", "What Our"),
          accent: t("\u0639\u0645\u0644\u0627\u0624\u0646\u0627", "Clients Say"),
          className: "mb-10"
        }
      ),
      /* @__PURE__ */ jsx("div", { ref: rootRef, children: /* @__PURE__ */ jsxs(
        Carousel,
        {
          opts: { align: "start", loop: true, direction: isRTL ? "rtl" : "ltr" },
          className: "w-full",
          children: [
            /* @__PURE__ */ jsx(CarouselContent, { className: `items-stretch ${isRTL ? "-mr-4" : "-ml-4"}`, children: VIDS.map((vid) => /* @__PURE__ */ jsx(
              CarouselItem,
              {
                className: `${isRTL ? "pr-4" : "pl-4"} basis-4/5 sm:basis-1/2 lg:basis-1/3 xl:basis-1/4`,
                children: /* @__PURE__ */ jsx(VideoCard, { vid, label: t("\u0639\u0645\u064A\u0644 \u0641\u0627\u064A\u0646\u062F\u0648", "Findo Client"), onPlay: handlePlay })
              },
              vid.id
            )) }),
            /* @__PURE__ */ jsx(CarouselPrevious, { className: "hidden md:flex -left-4 lg:-left-8 border-border bg-popover text-foreground hover:border-primary/50 hover:bg-primary/15 hover:text-primary" }),
            /* @__PURE__ */ jsx(CarouselNext, { className: "hidden md:flex -right-4 lg:-right-8 border-border bg-popover text-foreground hover:border-primary/50 hover:bg-primary/15 hover:text-primary" })
          ]
        }
      ) })
    ] })
  ] });
};
export default TestimonialsBoard;
