import { jsx, jsxs } from "react/jsx-runtime";
import { Link } from "react-router-dom";
import { ArrowLeft, ArrowRight, Download } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Board, Chip, CrossfadeVideo, CtaRow, RiseIn, WhatsappIcon } from "@/components/v2/primitives";
import { Carousel, CarouselContent, CarouselItem } from "@/components/ui/carousel";
import Autoplay from "embla-carousel-autoplay";
import { useReducedMotion } from "framer-motion";
import { useLanguage } from "@/contexts/LanguageContext";
import { useTheme } from "@/contexts/ThemeContext";
import heroDark from "@/assets/hero-ai-dark.jpg.js";
import heroLight from "@/assets/hero-ai-light.jpg.js";
import heroVideoDark from "@/assets/hero-ai-dark.mp4.js";
import heroVideoLight from "@/assets/hero-ai-light.mp4.js";
const arabCountries = [
  "\u0627\u0644\u0633\u0639\u0648\u062F\u064A\u0629",
  "\u0627\u0644\u0625\u0645\u0627\u0631\u0627\u062A",
  "\u0645\u0635\u0631",
  "\u0642\u0637\u0631",
  "\u0627\u0644\u0643\u0648\u064A\u062A",
  "\u0627\u0644\u0628\u062D\u0631\u064A\u0646",
  "\u0639\u0645\u0627\u0646",
  "\u0627\u0644\u0639\u0631\u0627\u0642",
  "\u0627\u0644\u0623\u0631\u062F\u0646",
  "\u0644\u0628\u0646\u0627\u0646",
  "\u0633\u0648\u0631\u064A\u0627",
  "\u0641\u0644\u0633\u0637\u064A\u0646",
  "\u0627\u0644\u064A\u0645\u0646",
  "\u0627\u0644\u0633\u0648\u062F\u0627\u0646",
  "\u0644\u064A\u0628\u064A\u0627",
  "\u062A\u0648\u0646\u0633",
  "\u0627\u0644\u062C\u0632\u0627\u0626\u0631",
  "\u0627\u0644\u0645\u063A\u0631\u0628",
  "\u0645\u0648\u0631\u064A\u062A\u0627\u0646\u064A\u0627",
  "\u0627\u0644\u0635\u0648\u0645\u0627\u0644",
  "\u062C\u064A\u0628\u0648\u062A\u064A",
  "\u062C\u0632\u0631 \u0627\u0644\u0642\u0645\u0631"
];
const HeroBoard = () => {
  const { t, isRTL } = useLanguage();
  const { isDark } = useTheme();
  const reducedMotion = useReducedMotion();
  const ArrowIcon = isRTL ? ArrowLeft : ArrowRight;
  const originalPoster = isDark ? heroDark : heroLight;
  const heroPoster = originalPoster.replace(".jpg", "-optimized-1280.webp");
  const heroPosterSrcSet = [640, 1280, 1920].map(width => `${originalPoster.replace(".jpg", `-optimized-${width}.webp`)} ${width}w`).join(", ");
  const heroVideo = isDark ? heroVideoDark : heroVideoLight;
  const whatsappNumber = "201100018259";
  const whatsappMessage = t(
    "\u0645\u0631\u062D\u0628\u0627\u064B\u060C \u0623\u0631\u064A\u062F \u0627\u0644\u0627\u0633\u062A\u0641\u0633\u0627\u0631 \u0639\u0646 \u062E\u062F\u0645\u0627\u062A\u0643\u0645",
    "Hello, I want to inquire about your services"
  );
  const whatsappUrl = `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(whatsappMessage)}`;
  return /* @__PURE__ */ jsxs(
    Board,
    {
      bare: true,
      dir: isRTL ? "rtl" : "ltr",
      className: "relative flex min-h-[72vh] items-center px-4 sm:px-6 lg:px-10 sm:min-h-[86vh]",
      style: { width: "100vw", marginLeft: "calc(50% - 50vw)", marginRight: "calc(50% - 50vw)" },
      children: [
        /* @__PURE__ */ jsxs("div", { "aria-hidden": true, className: "absolute inset-0 z-0 overflow-hidden", children: [
          reducedMotion ? /* @__PURE__ */ jsx(
            "img",
            {
              src: heroPoster,
              srcSet: heroPosterSrcSet,
              sizes: "100vw",
              alt: "",
              fetchPriority: "high",
              className: "h-full w-full object-cover"
            }
          ) : /* @__PURE__ */ jsx(
            CrossfadeVideo,
            {
              src: heroVideo,
              poster: heroPoster,
              posterSrcSet: heroPosterSrcSet,
              className: "h-full w-full"
            },
            heroVideo
          ),
          /* @__PURE__ */ jsx(
            "div",
            {
              className: "absolute inset-0",
              style: {
                background: "linear-gradient(180deg, hsl(var(--background) / 0.5) 0%, hsl(var(--background) / 0.34) 42%, hsl(var(--background) / 0.88) 100%)"
              }
            }
          ),
          /* @__PURE__ */ jsx(
            "div",
            {
              className: "absolute inset-0",
              style: {
                background: "radial-gradient(62% 56% at 50% 46%, hsl(var(--background) / 0.5) 0%, transparent 76%)"
              }
            }
          )
        ] }),
        /* @__PURE__ */ jsxs("div", { className: "relative z-10 mx-auto w-full max-w-4xl py-12 text-center sm:py-28", children: [
          /* @__PURE__ */ jsx(RiseIn, { mount: true, children: /* @__PURE__ */ jsx("div", { className: "flex justify-center", children: /* @__PURE__ */ jsx(
            Chip,
            {
              icon: /* @__PURE__ */ jsx("span", { "aria-hidden": true, className: "h-2 w-2 rounded-full bg-primary animate-pulse" }),
              className: "mb-7 max-w-full text-muted-foreground [&]:whitespace-normal",
              children: t(
                "\u0641\u0627\u064A\u0646\u062F\u0648 \u062C\u0631\u0648\u0628 \u2014 \u0634\u0631\u0643\u0629 \u0628\u0631\u0645\u062C\u0629 \u0648\u062A\u0637\u0648\u064A\u0631 \u062A\u0637\u0628\u064A\u0642\u0627\u062A \u0648\u0623\u0646\u0638\u0645\u0629 \u0628\u0627\u0644\u0648\u0637\u0646 \u0627\u0644\u0639\u0631\u0628\u064A",
                "Findo Group \u2014 Software, App & Systems Development across the Arab World"
              )
            }
          ) }) }),
          /* @__PURE__ */ jsx(RiseIn, { mount: true, delay: 0.08, children: /* @__PURE__ */ jsxs("h1", { className: "font-display text-4xl font-extrabold leading-tight tracking-tight text-foreground sm:text-5xl lg:text-6xl xl:text-7xl", children: [
            t("\u062D\u0648\u0651\u0644 \u0641\u0643\u0631\u062A\u0643 \u0625\u0644\u0649", "Transform Your Idea Into"),
            /* @__PURE__ */ jsx("br", {}),
            /* @__PURE__ */ jsx("span", { className: "text-gradient-coral", children: t("\u0625\u0645\u0628\u0631\u0627\u0637\u0648\u0631\u064A\u0629 \u0631\u0642\u0645\u064A\u0629", "A Digital Empire") })
          ] }) }),
          /* @__PURE__ */ jsx(RiseIn, { mount: true, delay: 0.16, children: /* @__PURE__ */ jsxs("p", { className: "mx-auto mt-6 max-w-2xl text-base leading-relaxed text-foreground/80 sm:text-lg", children: [
            t(
              "\u0628\u0631\u0645\u062C\u064A\u0627\u062A \u0630\u0643\u064A\u0629\u060C \u0627\u0644\u062A\u0632\u0627\u0645 \u0635\u0627\u0631\u0645 \u0628\u0627\u0644\u0645\u0648\u0627\u0639\u064A\u062F, \u0648\u062F\u0639\u0645 \u0641\u0646\u064A \u0644\u0627 \u064A\u062A\u0648\u0642\u0641.",
              "Smart software, strict deadline commitment, and non-stop technical support."
            ),
            /* @__PURE__ */ jsx("span", { className: "hidden sm:inline", children: /* @__PURE__ */ jsx("br", {}) }),
            /* @__PURE__ */ jsx("span", { className: "sm:hidden", children: " " }),
            t(
              "\u0641\u0627\u064A\u0646\u062F\u0648 \u062C\u0631\u0648\u0628 \u062A\u0637\u0648\u0651\u0631 \u062A\u0637\u0628\u064A\u0642\u0627\u062A \u0627\u0644\u062C\u0648\u0627\u0644 \u0648\u0627\u0644\u0645\u0648\u0627\u0642\u0639 \u0648\u0627\u0644\u0645\u062A\u0627\u062C\u0631 \u0627\u0644\u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A\u0629 \u0648\u0623\u0646\u0638\u0645\u0629 ERP \u0648 POS \u0628\u0641\u0647\u0645 \u0639\u0631\u0628\u064A \u0639\u0645\u064A\u0642 \u0648\u062C\u0648\u062F\u0629 \u0639\u0627\u0644\u0645\u064A\u0629.",
              "Findo Group builds mobile apps, websites, online stores and ERP/POS systems with deep Arab insight and global quality."
            )
          ] }) }),
          /* @__PURE__ */ jsx(RiseIn, { mount: true, delay: 0.24, children: /* @__PURE__ */ jsxs(CtaRow, { className: "justify-center", children: [
            /* @__PURE__ */ jsx(Button, { asChild: true, size: "lg", className: "max-sm:w-full", children: /* @__PURE__ */ jsxs(Link, { to: "/start-project", children: [
              t("\u0627\u0628\u062F\u0623 \u0645\u0634\u0631\u0648\u0639\u0643 \u0627\u0644\u0622\u0646", "Start Your Project Now"),
              /* @__PURE__ */ jsx(ArrowIcon, { strokeWidth: 1.75 })
            ] }) }),
            /* @__PURE__ */ jsx(
              Button,
              {
                asChild: true,
                variant: "outline",
                size: "lg",
                className: "max-sm:w-full border-success/50 text-success hover:border-success hover:bg-success/10",
                children: /* @__PURE__ */ jsxs("a", { href: whatsappUrl, target: "_blank", rel: "noopener noreferrer", children: [
                  /* @__PURE__ */ jsx(WhatsappIcon, {}),
                  t("\u062A\u0648\u0627\u0635\u0644 \u0639\u0628\u0631 \u0648\u0627\u062A\u0633\u0627\u0628", "Chat on WhatsApp")
                ] })
              }
            ),
            /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", size: "lg", className: "max-sm:w-full", children: /* @__PURE__ */ jsxs("a", { href: "/findo-company-profile.pdf", download: "Findo_Group_Company_Profile.pdf", children: [
              /* @__PURE__ */ jsx(Download, { strokeWidth: 1.75 }),
              t("\u0628\u0631\u0648\u0641\u0627\u064A\u0644 \u0627\u0644\u0634\u0631\u0643\u0629", "Company Profile")
            ] }) })
          ] }) }),
          /* @__PURE__ */ jsx(RiseIn, { mount: true, delay: 0.32, children: /* @__PURE__ */ jsxs("div", { className: "mt-10", children: [
            /* @__PURE__ */ jsx("div", { className: "mb-3 text-xs text-foreground/70 sm:text-sm", children: t("\u0646\u062E\u062F\u0645 \u0639\u0645\u0644\u0627\u0621\u0646\u0627 \u0641\u064A", "Serving Our Clients in") }),
            /* @__PURE__ */ jsx(
              Carousel,
              {
                opts: { align: "start", loop: true, direction: isRTL ? "rtl" : "ltr" },
                plugins: [Autoplay({ delay: 2e3, stopOnInteraction: false, stopOnMouseEnter: true })],
                className: "mx-auto max-w-sm",
                children: /* @__PURE__ */ jsx(CarouselContent, { className: isRTL ? "-mr-2" : "-ml-2", children: arabCountries.map((country) => /* @__PURE__ */ jsx(CarouselItem, { className: `basis-1/3 ${isRTL ? "pr-2" : "pl-2"}`, children: /* @__PURE__ */ jsx(Chip, { className: "w-full justify-center whitespace-nowrap", children: country }) }, country)) })
              }
            )
          ] }) })
        ] })
      ]
    }
  );
};
export default HeroBoard;
