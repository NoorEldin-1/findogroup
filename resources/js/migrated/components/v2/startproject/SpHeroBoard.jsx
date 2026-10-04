import { jsx, jsxs } from "react/jsx-runtime";
import { useEffect } from "react";
import { motion, useAnimation } from "framer-motion";
import { ArrowLeft, Sparkles, Zap, Shield, Star, Palette, Smartphone, Clock, LifeBuoy } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Board, Chip, CtaRow, IconBadge, RiseIn } from "@/components/v2/primitives";
import { useLanguage } from "@/contexts/LanguageContext";
import { pushDataLayer, scrollToSection } from "@/lib/tracking";
const TRUST_FEATURES = [
  { icon: Palette, ar: "\u062A\u0635\u0645\u064A\u0645 UX/UI \u0627\u062D\u062A\u0631\u0627\u0641\u064A", en: "Professional UX/UI" },
  { icon: Smartphone, ar: "\u062A\u0637\u0628\u064A\u0642\u0627\u062A iOS \u0648 Android", en: "iOS & Android Apps" },
  { icon: Clock, ar: "\u062A\u0633\u0644\u064A\u0645 \u0641\u064A \u0627\u0644\u0645\u0648\u0639\u062F", en: "On-Time Delivery" },
  { icon: LifeBuoy, ar: "\u062F\u0639\u0645 \u0628\u0639\u062F \u0627\u0644\u0625\u0637\u0644\u0627\u0642", en: "Post-Launch Support" }
];
const HIGHLIGHT_PILLS = [
  { icon: Zap, ar: "\u062A\u0635\u0645\u064A\u0645 \u062A\u0641\u0627\u0639\u0644\u064A", en: "Interactive Design" },
  { icon: Shield, ar: "\u0643\u0648\u062F \u0622\u0645\u0646", en: "Secure Code" },
  { icon: Star, ar: "\u0623\u062F\u0627\u0621 \u0639\u0627\u0644\u064A", en: "High Performance" }
];
const SpHeroBoard = () => {
  const { t, isRTL } = useLanguage();
  const ctaShake = useAnimation();
  useEffect(() => {
    const id = setInterval(() => {
      ctaShake.start({
        x: [0, -5, 5, -4, 4, -2, 2, 0],
        transition: { duration: 0.45, ease: "easeInOut" }
      });
    }, 5e3);
    return () => clearInterval(id);
  }, [ctaShake]);
  const handleCTA = (location, target) => {
    pushDataLayer("cta_click", { cta_location: location, cta_target: target, page: "/start-project" });
    scrollToSection(target);
  };
  return /* @__PURE__ */ jsxs(Board, { id: "hero", dir: isRTL ? "rtl" : "ltr", className: "lg:p-16", children: [
    /* @__PURE__ */ jsx(
      "div",
      {
        "aria-hidden": true,
        className: "pointer-events-none absolute -top-44 start-1/2 h-[480px] w-[720px] -translate-x-1/2 opacity-70 rtl:translate-x-1/2",
        style: { background: "radial-gradient(closest-side, rgba(253,128,46,0.16), transparent 70%)" }
      }
    ),
    /* @__PURE__ */ jsx(
      "div",
      {
        "aria-hidden": true,
        className: "pointer-events-none absolute -bottom-52 end-0 h-[420px] w-[520px] opacity-60",
        style: { background: "radial-gradient(closest-side, rgba(253,128,46,0.10), transparent 70%)" }
      }
    ),
    /* @__PURE__ */ jsxs("div", { className: "relative flex flex-col items-center text-center", children: [
      /* @__PURE__ */ jsx(RiseIn, { mount: true, children: /* @__PURE__ */ jsxs("div", { className: "mb-8 inline-flex max-w-full flex-wrap items-center justify-center gap-2 rounded-full border border-border bg-popover px-4 py-2.5 md:px-5 md:py-3", children: [
        /* @__PURE__ */ jsx("span", { "aria-hidden": true, className: "h-2 w-2 rounded-full bg-primary animate-pulse" }),
        /* @__PURE__ */ jsx("span", { className: "whitespace-nowrap text-xs text-muted-foreground md:text-sm", children: t("\u0627\u0633\u062A\u0634\u0627\u0631\u0629 \u0641\u0646\u064A\u0629 \u0645\u062C\u0627\u0646\u064A\u0629 + \u0639\u0631\u0636 \u0633\u0639\u0631 \u062E\u0644\u0627\u0644 24 \u0633\u0627\u0639\u0629", "Free Consultation + Quote within 24 hours") }),
        /* @__PURE__ */ jsxs(
          "button",
          {
            type: "button",
            onClick: () => handleCTA("hero_pill", "lead-form"),
            className: "flex items-center gap-1 whitespace-nowrap text-xs font-semibold text-primary transition-all duration-200 ease-ds hover:text-primary/80 active:scale-95 md:text-sm",
            children: [
              t("\u0627\u0628\u062F\u0623 \u0627\u0644\u0622\u0646", "Start now"),
              /* @__PURE__ */ jsx(ArrowLeft, { className: `h-3 w-3 md:h-4 md:w-4 ${isRTL ? "" : "rotate-180"}`, strokeWidth: 1.75 })
            ]
          }
        )
      ] }) }),
      /* @__PURE__ */ jsx(RiseIn, { mount: true, delay: 0.08, children: /* @__PURE__ */ jsxs("h1", { className: "max-w-5xl font-display text-4xl font-extrabold leading-tight tracking-tight text-foreground sm:text-5xl lg:text-6xl xl:text-7xl", children: [
        /* @__PURE__ */ jsx("span", { className: "block", children: t("\u062D\u0648\u0651\u0644 \u0641\u0643\u0631\u062A\u0643 \u0625\u0644\u0649 \u0645\u0648\u0642\u0639", "Give your idea") }),
        /* @__PURE__ */ jsx("span", { className: "block text-gradient-coral", children: t("\u0623\u0648 \u062A\u0637\u0628\u064A\u0642 \u064A\u062D\u0642\u0642 \u0646\u062A\u0627\u0626\u062C", "the website it deserves") })
      ] }) }),
      /* @__PURE__ */ jsx(RiseIn, { mount: true, delay: 0.16, children: /* @__PURE__ */ jsxs("p", { className: "mt-6 max-w-2xl text-sm leading-relaxed text-muted-foreground md:text-base lg:text-lg", children: [
        t("\u0646\u0635\u0645\u0645 \u0648\u0646\u0637\u0648\u0631 \u0645\u0648\u0627\u0642\u0639 \u0648\u062A\u0637\u0628\u064A\u0642\u0627\u062A iOS \u0648 Android \u0645\u062E\u0635\u0635\u0629 \u0644\u0634\u0631\u0643\u062A\u0643\u060C", "We design and develop custom websites and iOS/Android apps,"),
        /* @__PURE__ */ jsx("br", { className: "hidden sm:block" }),
        t(" \u0628\u0623\u0639\u0644\u0649 \u062C\u0648\u062F\u0629 \u0648\u062A\u0633\u0644\u064A\u0645 \u0641\u064A \u0627\u0644\u0645\u0648\u0639\u062F.", " top quality, delivered on time.")
      ] }) }),
      /* @__PURE__ */ jsx(RiseIn, { mount: true, delay: 0.24, children: /* @__PURE__ */ jsx("ul", { className: "mt-8 flex flex-wrap items-center justify-center gap-3", children: HIGHLIGHT_PILLS.map((pill) => {
        const Icon = pill.icon;
        return /* @__PURE__ */ jsx("li", { children: /* @__PURE__ */ jsx(
          Chip,
          {
            icon: /* @__PURE__ */ jsx(Icon, { className: "h-3.5 w-3.5 md:h-4 md:w-4", strokeWidth: 1.75 }),
            className: "text-muted-foreground transition-colors duration-200 ease-ds hover:border-primary/40 hover:text-primary",
            children: t(pill.ar, pill.en)
          }
        ) }, pill.ar);
      }) }) }),
      /* @__PURE__ */ jsx(RiseIn, { mount: true, delay: 0.32, children: /* @__PURE__ */ jsxs(CtaRow, { className: "justify-center max-sm:w-full max-sm:flex-col max-sm:items-stretch", children: [
        /* @__PURE__ */ jsx(motion.div, { animate: ctaShake, className: "max-sm:w-full", children: /* @__PURE__ */ jsxs(
          Button,
          {
            type: "button",
            size: "lg",
            onClick: () => handleCTA("hero_primary", "lead-form"),
            className: "max-sm:w-full",
            children: [
              /* @__PURE__ */ jsx(Sparkles, { strokeWidth: 1.75 }),
              t("\u0627\u0637\u0644\u0628 \u0639\u0631\u0636 \u0633\u0639\u0631 \u0645\u062C\u0627\u0646\u064A", "Get a Free Quote"),
              /* @__PURE__ */ jsx(ArrowLeft, { className: isRTL ? "" : "rotate-180", strokeWidth: 1.75 })
            ]
          }
        ) }),
        /* @__PURE__ */ jsx(
          Button,
          {
            type: "button",
            variant: "outline",
            size: "lg",
            onClick: () => handleCTA("hero_secondary", "featured-portfolio"),
            className: "max-sm:w-full",
            children: t("\u0634\u0627\u0647\u062F \u0623\u0639\u0645\u0627\u0644\u0646\u0627", "See Our Work")
          }
        ),
        /* @__PURE__ */ jsx(
          Button,
          {
            asChild: true,
            variant: "outline",
            size: "lg",
            className: "max-sm:w-full border-success/50 text-success hover:border-success hover:bg-success/10",
            children: /* @__PURE__ */ jsxs(
              "a",
              {
                href: `https://wa.me/201100018259?text=${encodeURIComponent(t("\u0645\u0631\u062D\u0628\u0627\u064B\u060C \u0623\u0631\u064A\u062F \u0627\u0644\u0627\u0633\u062A\u0641\u0633\u0627\u0631 \u0639\u0646 \u062E\u062F\u0645\u0627\u062A\u0643\u0645", "Hello, I'd like to inquire about your services"))}`,
                target: "_blank",
                rel: "noopener noreferrer",
                onClick: () => handleCTA("hero_whatsapp", "whatsapp"),
                children: [
                  /* @__PURE__ */ jsx("svg", { viewBox: "0 0 24 24", fill: "currentColor", className: "h-4 w-4", "aria-hidden": "true", children: /* @__PURE__ */ jsx("path", { d: "M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" }) }),
                  t("\u0648\u0627\u062A\u0633\u0627\u0628", "WhatsApp")
                ]
              }
            )
          }
        )
      ] }) }),
      /* @__PURE__ */ jsx(RiseIn, { delay: 0.1, className: "mt-14 w-full lg:mt-16", children: /* @__PURE__ */ jsx("div", { className: "grid w-full grid-cols-2 gap-3 md:grid-cols-4 md:gap-4", children: TRUST_FEATURES.map((feature) => {
        const Icon = feature.icon;
        return /* @__PURE__ */ jsxs(
          "div",
          {
            className: "group flex flex-col items-center gap-3 rounded-2xl border border-border bg-popover p-4 transition-all duration-200 ease-ds hover:-translate-y-1 hover:border-primary/40 md:flex-row md:p-5",
            children: [
              /* @__PURE__ */ jsx(IconBadge, { icon: Icon, className: "transition-transform duration-200 ease-ds group-hover:scale-110" }),
              /* @__PURE__ */ jsx("p", { className: "text-center text-xs font-medium leading-tight text-foreground/80 md:text-start md:text-sm", children: t(feature.ar, feature.en) })
            ]
          },
          feature.ar
        );
      }) }) })
    ] })
  ] });
};
export default SpHeroBoard;
