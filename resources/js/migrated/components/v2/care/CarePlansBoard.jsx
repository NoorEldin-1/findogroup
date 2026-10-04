import { Fragment, jsx, jsxs } from "react/jsx-runtime";
import { Check, Sparkles } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Board, Chip, Eyebrow, RiseIn } from "@/components/v2/primitives";
import { useLanguage } from "@/contexts/LanguageContext";
const plans = [
  {
    id: "basic",
    nameAr: "Basic",
    nameEn: "Basic",
    price: "$150",
    // TODO: سعر حقيقي
    periodAr: "/\u0634\u0647\u0631\u064A\u064B\u0627",
    periodEn: "/mo",
    taglineAr: "\u0627\u0644\u0623\u0633\u0627\u0633\u064A\u0627\u062A \u0644\u0625\u0628\u0642\u0627\u0621 \u0645\u0646\u062A\u062C\u0643 \u064A\u0639\u0645\u0644 \u0628\u0623\u0645\u0627\u0646",
    taglineEn: "The essentials to keep your product running safely",
    highlighted: false,
    featuresAr: [
      "\u062A\u062D\u062F\u064A\u062B\u0627\u062A \u0623\u0645\u0627\u0646 \u062F\u0648\u0631\u064A\u0629",
      "\u0646\u0633\u062E \u0627\u062D\u062A\u064A\u0627\u0637\u064A \u0623\u0633\u0628\u0648\u0639\u064A",
      "\u0625\u0635\u0644\u0627\u062D \u0627\u0644\u0623\u0639\u0637\u0627\u0644 \u0627\u0644\u062D\u0631\u062C\u0629",
      "\u062F\u0639\u0645 \u0639\u0628\u0631 \u0627\u0644\u0628\u0631\u064A\u062F \u0648\u0648\u0627\u062A\u0633\u0627\u0628"
    ],
    featuresEn: [
      "Regular security updates",
      "Weekly backups",
      "Critical bug fixes",
      "Email & WhatsApp support"
    ]
  },
  {
    id: "standard",
    nameAr: "Standard",
    nameEn: "Standard",
    price: "$390",
    // TODO: سعر حقيقي
    periodAr: "/\u0634\u0647\u0631\u064A\u064B\u0627",
    periodEn: "/mo",
    taglineAr: "\u0627\u0644\u0623\u0643\u062B\u0631 \u0637\u0644\u0628\u064B\u0627 \u2014 \u062F\u0639\u0645 \u0648\u0627\u0633\u062A\u062C\u0627\u0628\u0629 \u0623\u0633\u0631\u0639",
    taglineEn: "Most popular \u2014 faster support & response",
    highlighted: true,
    featuresAr: [
      "\u0643\u0644 \u0645\u0632\u0627\u064A\u0627 Basic",
      "\u0646\u0633\u062E \u0627\u062D\u062A\u064A\u0627\u0637\u064A \u064A\u0648\u0645\u064A",
      "\u062A\u062D\u0633\u064A\u0646\u0627\u062A \u0623\u062F\u0627\u0621 \u062F\u0648\u0631\u064A\u0629",
      "\u062A\u0639\u062F\u064A\u0644\u0627\u062A \u0635\u063A\u064A\u0631\u0629 \u0634\u0647\u0631\u064A\u0629 (\u0633\u0627\u0639\u0627\u062A \u0645\u062D\u062F\u0651\u062F\u0629)",
      "\u062A\u0642\u0627\u0631\u064A\u0631 \u0634\u0647\u0631\u064A\u0629 \u0639\u0646 \u0627\u0644\u062D\u0627\u0644\u0629"
    ],
    featuresEn: [
      "Everything in Basic",
      "Daily backups",
      "Periodic performance tuning",
      "Monthly minor changes (capped hours)",
      "Monthly status reports"
    ]
  },
  {
    id: "premium",
    nameAr: "Premium",
    nameEn: "Premium",
    price: "$790",
    // TODO: سعر حقيقي
    periodAr: "/\u0634\u0647\u0631\u064A\u064B\u0627",
    periodEn: "/mo",
    taglineAr: "\u062F\u0639\u0645 \u0623\u0648\u0644\u0648\u064A\u0629 \u0648\u0645\u0631\u0627\u0642\u0628\u0629 \u0645\u0633\u062A\u0645\u0631\u0629 \u0644\u0644\u0645\u0624\u0633\u0633\u0627\u062A",
    taglineEn: "Priority support & monitoring for enterprises",
    highlighted: false,
    featuresAr: [
      "\u0643\u0644 \u0645\u0632\u0627\u064A\u0627 Standard",
      "\u0645\u0631\u0627\u0642\u0628\u0629 24/7 \u0644\u0644\u0623\u0646\u0638\u0645\u0629",
      "\u062F\u0639\u0645 \u0623\u0648\u0644\u0648\u064A\u0629 \u0628\u0623\u0633\u0631\u0639 \u0627\u0633\u062A\u062C\u0627\u0628\u0629",
      "\u0645\u062F\u064A\u0631 \u062D\u0633\u0627\u0628 \u0645\u062E\u0635\u0651\u0635",
      "\u0627\u062A\u0641\u0627\u0642\u064A\u0629 \u0645\u0633\u062A\u0648\u0649 \u062E\u062F\u0645\u0629 (SLA) \u0645\u0648\u062B\u0651\u0642\u0629"
    ],
    featuresEn: [
      "Everything in Standard",
      "24/7 system monitoring",
      "Priority support, fastest response",
      "Dedicated account manager",
      "Documented SLA"
    ]
  }
];
const CarePlansBoard = () => {
  const { t, isRTL } = useLanguage();
  const scrollToForm = (e) => {
    e.preventDefault();
    document.getElementById("request-form")?.scrollIntoView({ behavior: "smooth", block: "start" });
  };
  return /* @__PURE__ */ jsx(RiseIn, { children: /* @__PURE__ */ jsxs(Board, { dir: isRTL ? "rtl" : "ltr", children: [
    /* @__PURE__ */ jsx(Eyebrow, { index: "02", className: "mb-8", children: t("\u0628\u0627\u0642\u0627\u062A \u0627\u0644\u062F\u0639\u0645 Findo Care", "Findo Care Support Plans") }),
    /* @__PURE__ */ jsx("div", { className: "grid items-stretch gap-6 pt-2 md:grid-cols-3", children: plans.map((plan) => /* @__PURE__ */ jsxs(
      "div",
      {
        className: `group relative flex flex-col rounded-3xl p-7 transition-all duration-300 md:p-8 ${plan.highlighted ? "z-10 border border-primary/50 bg-gradient-to-b from-primary/[0.12] via-popover to-popover shadow-glow-coral ring-1 ring-primary/25 lg:-my-2 lg:scale-[1.05]" : "border border-border bg-popover hover:-translate-y-1 hover:border-primary/40"}`,
        children: [
          plan.highlighted && /* @__PURE__ */ jsxs(Fragment, { children: [
            /* @__PURE__ */ jsx("div", { className: "absolute -top-px inset-x-10 h-px bg-gradient-to-r from-transparent via-primary to-transparent", "aria-hidden": true }),
            /* @__PURE__ */ jsx(
              Chip,
              {
                variant: "featured",
                icon: /* @__PURE__ */ jsx(Sparkles, { className: "h-3.5 w-3.5" }),
                className: "absolute -top-4 start-1/2 -translate-x-1/2 whitespace-nowrap shadow-glow-coral rtl:translate-x-1/2",
                children: t("\u0627\u0644\u0623\u0643\u062B\u0631 \u0637\u0644\u0628\u064B\u0627", "Most popular")
              }
            )
          ] }),
          /* @__PURE__ */ jsx("h2", { className: "font-display text-xl font-bold text-foreground", children: t(plan.nameAr, plan.nameEn) }),
          /* @__PURE__ */ jsx("p", { className: "mt-1.5 min-h-[40px] text-sm leading-relaxed text-muted-foreground", children: t(plan.taglineAr, plan.taglineEn) }),
          /* @__PURE__ */ jsxs("div", { className: "mb-6 mt-5 flex items-baseline gap-1", dir: "ltr", children: [
            /* @__PURE__ */ jsx("span", { className: "font-display text-4xl font-extrabold tabular-nums leading-none text-foreground", children: plan.price }),
            /* @__PURE__ */ jsx("span", { className: "text-sm text-muted-foreground/80", children: t(plan.periodAr, plan.periodEn) })
          ] }),
          /* @__PURE__ */ jsx("div", { className: "mb-6 border-t border-border" }),
          /* @__PURE__ */ jsx("ul", { className: "mb-8 flex-1 space-y-3.5", children: (isRTL ? plan.featuresAr : plan.featuresEn).map((f, fi) => /* @__PURE__ */ jsxs("li", { className: "flex items-start gap-3 text-sm leading-snug text-foreground/80", children: [
            /* @__PURE__ */ jsx(
              "span",
              {
                className: `mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-md ${plan.highlighted ? "[background:var(--gradient-coral)]" : "border border-border bg-popover"}`,
                children: /* @__PURE__ */ jsx(
                  Check,
                  {
                    className: `h-3 w-3 ${plan.highlighted ? "text-primary-foreground" : "text-primary"}`,
                    strokeWidth: 3
                  }
                )
              }
            ),
            f
          ] }, fi)) }),
          /* @__PURE__ */ jsx(Button, { asChild: true, variant: plan.highlighted ? "default" : "outline", size: "lg", className: "w-full", children: /* @__PURE__ */ jsx("a", { href: "#request-form", onClick: scrollToForm, children: t("\u0627\u0634\u062A\u0631\u0643 \u0627\u0644\u0622\u0646", "Subscribe now") }) })
        ]
      },
      plan.id
    )) })
  ] }) });
};
export default CarePlansBoard;
