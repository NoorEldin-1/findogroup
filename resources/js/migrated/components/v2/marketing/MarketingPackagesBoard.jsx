import { jsx, jsxs } from "react/jsx-runtime";
import { Award, Check, Sparkles } from "lucide-react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { Board, BoardHeader, Chip, IconBadge, RiseIn, WhatsappIcon } from "@/components/v2/primitives";
import { useLanguage } from "@/contexts/LanguageContext";
const packages = [
  {
    id: "silver",
    name: "Silver Package",
    nameAr: "\u0627\u0644\u0628\u0627\u0642\u0629 \u0627\u0644\u0641\u0636\u064A\u0629",
    price: "$500",
    priceAr: "500$",
    period: "\u0634\u0647\u0631\u064A\u064B\u0627",
    periodEn: "Monthly",
    popular: false,
    features: [
      { text: "\u062A\u0642\u0631\u064A\u0631 \u0627\u0633\u0628\u0648\u0639\u064A \u0644\u0623\u062F\u0627\u0621 \u0627\u0644\u062D\u0633\u0627\u0628\u0627\u062A", textEn: "Weekly account performance report" },
      { text: "\u0639\u062F\u062F \u0627\u0644\u0645\u0646\u0635\u0627\u062A: 2", textEn: "Number of platforms: 2" },
      { text: "\u0639\u062F\u062F \u0627\u0644\u062A\u0635\u0645\u064A\u0645\u0627\u062A \u0644\u0644\u0645\u0646\u0634\u0648\u0631\u0627\u062A / \u0627\u0644\u0634\u0647\u0631: 10", textEn: "Number of post designs/month: 10" },
      { text: "Stories: 10", textEn: "Stories: 10" },
      { text: "Reels: 3", textEn: "Reels: 3" },
      { text: "\u062A\u0635\u0645\u064A\u0645 \u0627\u0644\u0623\u063A\u0644\u0641\u0629 \u0648\u0627\u0644\u062E\u0644\u0641\u064A\u0627\u062A \u0627\u0644\u062E\u0627\u0635\u0629 \u0628\u062C\u0645\u064A\u0639 \u0627\u0644\u062D\u0633\u0627\u0628\u0627\u062A", textEn: "Design covers and backgrounds for all accounts" },
      { text: "\u0643\u062A\u0627\u0628\u0629 \u0648\u0635\u0641 \u0644\u062D\u0633\u0627\u0628\u0627\u062A \u0645\u0648\u0627\u0642\u0639 \u0627\u0644\u062A\u0648\u0627\u0635\u0644 \u0627\u0644\u0627\u062C\u062A\u0645\u0627\u0639\u064A \u0645\u062A\u0648\u0627\u0641\u0642 \u0645\u0639 \u0645\u062D\u0631\u0643\u0627\u062A \u0627\u0644\u0628\u062D\u062B", textEn: "SEO-optimized social media bio writing" },
      { text: "\u062E\u0637\u0629 \u0645\u062D\u062A\u0648\u0649", textEn: "Content plan" },
      { text: "\u0635\u064A\u0627\u063A\u0629 \u0627\u0644\u0645\u062D\u062A\u0648\u0649 \u0627\u0644\u062E\u0627\u0635 \u0628\u0627\u0644\u0645\u0646\u0634\u0648\u0631\u0627\u062A", textEn: "Post content writing" },
      { text: "\u062F\u0631\u0627\u0633\u0629 \u0627\u0644\u0633\u0648\u0642", textEn: "Market research" }
    ]
  },
  {
    id: "gold",
    name: "Gold Package",
    nameAr: "\u0627\u0644\u0628\u0627\u0642\u0629 \u0627\u0644\u0630\u0647\u0628\u064A\u0629",
    price: "$1,000",
    priceAr: "1,000$",
    period: "\u0634\u0647\u0631\u064A\u064B\u0627",
    periodEn: "Monthly",
    popular: true,
    features: [
      { text: "\u062A\u0642\u0631\u064A\u0631 \u0627\u0633\u0628\u0648\u0639\u064A \u0644\u0623\u062F\u0627\u0621 \u0627\u0644\u062D\u0633\u0627\u0628\u0627\u062A", textEn: "Weekly account performance report" },
      { text: "\u0639\u062F\u062F \u0627\u0644\u0645\u0646\u0635\u0627\u062A: 2", textEn: "Number of platforms: 2" },
      { text: "\u0639\u062F\u062F \u0627\u0644\u062A\u0635\u0645\u064A\u0645\u0627\u062A \u0644\u0644\u0645\u0646\u0634\u0648\u0631\u0627\u062A / \u0627\u0644\u0634\u0647\u0631: 20", textEn: "Number of post designs/month: 20" },
      { text: "Stories: 20", textEn: "Stories: 20" },
      { text: "Reels: 6", textEn: "Reels: 6" },
      { text: "\u062A\u0635\u0645\u064A\u0645 \u0627\u0644\u0623\u063A\u0644\u0641\u0629 \u0648\u0627\u0644\u062E\u0644\u0641\u064A\u0627\u062A \u0627\u0644\u062E\u0627\u0635\u0629 \u0628\u062C\u0645\u064A\u0639 \u0627\u0644\u062D\u0633\u0627\u0628\u0627\u062A", textEn: "Design covers and backgrounds for all accounts" },
      { text: "\u0643\u062A\u0627\u0628\u0629 \u0648\u0635\u0641 \u0644\u062D\u0633\u0627\u0628\u0627\u062A \u0645\u0648\u0627\u0642\u0639 \u0627\u0644\u062A\u0648\u0627\u0635\u0644 \u0627\u0644\u0627\u062C\u062A\u0645\u0627\u0639\u064A \u0645\u062A\u0648\u0627\u0641\u0642 \u0645\u0639 \u0645\u062D\u0631\u0643\u0627\u062A \u0627\u0644\u0628\u062D\u062B", textEn: "SEO-optimized social media bio writing" },
      { text: "\u062E\u0637\u0629 \u0645\u062D\u062A\u0648\u0649", textEn: "Content plan" },
      { text: "\u0635\u064A\u0627\u063A\u0629 \u0627\u0644\u0645\u062D\u062A\u0648\u0649 \u0627\u0644\u062E\u0627\u0635 \u0628\u0627\u0644\u0645\u0646\u0634\u0648\u0631\u0627\u062A", textEn: "Post content writing" },
      { text: "\u062F\u0631\u0627\u0633\u0629 \u0627\u0644\u0633\u0648\u0642", textEn: "Market research" },
      { text: "\u0628\u0631\u064A\u062F \u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A \u0631\u0633\u0645\u064A \u0644\u0644\u0634\u0631\u0643\u0629: \u0627\u0644\u062E\u0637\u0629 \u0627\u0644\u0646\u0635\u0641 \u0633\u0646\u0648\u064A\u0629 \u0648\u0627\u0644\u0633\u0646\u0648\u064A\u0629", textEn: "Official company email: Half-yearly and annual plans" }
    ]
  },
  {
    id: "platinum",
    name: "Platinum Package",
    nameAr: "\u0627\u0644\u0628\u0627\u0642\u0629 \u0627\u0644\u0628\u0644\u0627\u062A\u064A\u0646\u064A\u0629",
    price: "$1,500",
    priceAr: "1,500$",
    period: "\u0634\u0647\u0631\u064A\u064B\u0627",
    periodEn: "Monthly",
    popular: false,
    features: [
      { text: "\u062A\u0642\u0631\u064A\u0631 \u0627\u0633\u0628\u0648\u0639\u064A \u0644\u0623\u062F\u0627\u0621 \u0627\u0644\u062D\u0633\u0627\u0628\u0627\u062A", textEn: "Weekly account performance report" },
      { text: "\u0639\u062F\u062F \u0627\u0644\u0645\u0646\u0635\u0627\u062A: 4", textEn: "Number of platforms: 4" },
      { text: "\u0639\u062F\u062F \u0627\u0644\u062A\u0635\u0645\u064A\u0645\u0627\u062A \u0644\u0644\u0645\u0646\u0634\u0648\u0631\u0627\u062A / \u0627\u0644\u0634\u0647\u0631: 30", textEn: "Number of post designs/month: 30" },
      { text: "Stories: 90", textEn: "Stories: 90" },
      { text: "Reels: 8", textEn: "Reels: 8" },
      { text: "\u062A\u0635\u0645\u064A\u0645 \u0627\u0644\u0623\u063A\u0644\u0641\u0629 \u0648\u0627\u0644\u062E\u0644\u0641\u064A\u0627\u062A \u0627\u0644\u062E\u0627\u0635\u0629 \u0628\u062C\u0645\u064A\u0639 \u0627\u0644\u062D\u0633\u0627\u0628\u0627\u062A", textEn: "Design covers and backgrounds for all accounts" },
      { text: "\u0643\u062A\u0627\u0628\u0629 \u0648\u0635\u0641 \u0644\u062D\u0633\u0627\u0628\u0627\u062A \u0645\u0648\u0627\u0642\u0639 \u0627\u0644\u062A\u0648\u0627\u0635\u0644 \u0627\u0644\u0627\u062C\u062A\u0645\u0627\u0639\u064A \u0645\u062A\u0648\u0627\u0641\u0642 \u0645\u0639 \u0645\u062D\u0631\u0643\u0627\u062A \u0627\u0644\u0628\u062D\u062B", textEn: "SEO-optimized social media bio writing" },
      { text: "\u062E\u0637\u0629 \u0645\u062D\u062A\u0648\u0649", textEn: "Content plan" },
      { text: "\u0635\u064A\u0627\u063A\u0629 \u0627\u0644\u0645\u062D\u062A\u0648\u0649 \u0627\u0644\u062E\u0627\u0635 \u0628\u0627\u0644\u0645\u0646\u0634\u0648\u0631\u0627\u062A", textEn: "Post content writing" },
      { text: "\u062F\u0631\u0627\u0633\u0629 \u0627\u0644\u0633\u0648\u0642", textEn: "Market research" },
      { text: "\u0628\u0631\u064A\u062F \u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A \u0631\u0633\u0645\u064A \u0644\u0644\u0634\u0631\u0643\u0629: \u0627\u0644\u062E\u0637\u0629 \u0627\u0644\u0646\u0635\u0641 \u0633\u0646\u0648\u064A\u0629 \u0648\u0627\u0644\u0633\u0646\u0648\u064A\u0629", textEn: "Official company email: Half-yearly and annual plans" },
      { text: "\u0625\u0646\u0634\u0627\u0621 \u0645\u0648\u0642\u0639 \u062A\u0639\u0631\u064A\u0641\u064A \u0644\u0644\u0634\u0631\u0643\u0629: \u0627\u0644\u062E\u0637\u0629 \u0627\u0644\u0633\u0646\u0648\u064A\u0629", textEn: "Company website creation: Annual plan" },
      { text: "\u062E\u0637\u0629 SEO: \u0627\u0644\u062E\u0637\u0629 \u0627\u0644\u0633\u0646\u0648\u064A\u0629", textEn: "SEO plan: Annual plan" },
      { text: "\u0627\u0644\u0631\u062F \u0639\u0644\u0649 \u0627\u0644\u062A\u0639\u0644\u064A\u0642\u0627\u062A \u0648\u0627\u0644\u0627\u0633\u062A\u0641\u0633\u0627\u0631\u0627\u062A: \u0627\u0644\u062E\u0637\u0629 \u0627\u0644\u0633\u0646\u0648\u064A\u0629", textEn: "Respond to comments and inquiries: Annual plan" }
    ]
  }
];
const MarketingPackagesBoard = () => {
  const { t, isRTL } = useLanguage();
  return /* @__PURE__ */ jsx(RiseIn, { children: /* @__PURE__ */ jsxs(Board, { dir: isRTL ? "rtl" : "ltr", children: [
    /* @__PURE__ */ jsx(
      BoardHeader,
      {
        index: "03",
        eyebrow: t("\u0628\u0627\u0642\u0627\u062A \u0648\u0623\u0633\u0639\u0627\u0631 \u0641\u0627\u064A\u0646\u062F\u0648 \u062C\u0631\u0648\u0628", "Findo Group Pricing"),
        pre: t("\u0627\u062E\u062A\u0631 \u0627\u0644\u0628\u0627\u0642\u0629 \u0627\u0644\u0645\u0646\u0627\u0633\u0628\u0629", "Choose the Right Package"),
        accent: t("\u0644\u0627\u062D\u062A\u064A\u0627\u062C\u0627\u062A \u0639\u0645\u0644\u0643", "for Your Business Needs")
      }
    ),
    /* @__PURE__ */ jsx("div", { className: "grid items-stretch gap-4 lg:grid-cols-3", children: packages.map((pkg) => {
      const waHref = `https://wa.me/201100018259?text=${encodeURIComponent(
        isRTL ? `\u0645\u0631\u062D\u0628\u0627\u064B\u060C \u0623\u0646\u0627 \u0645\u0647\u062A\u0645 \u0628\u0628\u0627\u0642\u0629 ${pkg.nameAr} \u0644\u0644\u062A\u0633\u0648\u064A\u0642 \u0627\u0644\u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A. \u0623\u0631\u063A\u0628 \u0641\u064A \u0645\u0639\u0631\u0641\u0629 \u0627\u0644\u0645\u0632\u064A\u062F \u0645\u0646 \u0627\u0644\u062A\u0641\u0627\u0635\u064A\u0644.` : `Hello, I'm interested in the ${pkg.name} for digital marketing. I would like to know more details.`
      )}`;
      return /* @__PURE__ */ jsxs(
        "div",
        {
          className: cn(
            "relative flex h-full flex-col rounded-3xl border bg-popover p-6 transition-all duration-200 ease-ds md:p-8",
            pkg.popular ? "border-primary/60 shadow-glow-coral" : "border-border hover:border-primary/40"
          ),
          children: [
            /* @__PURE__ */ jsxs("div", { className: "flex items-start justify-between gap-3", children: [
              /* @__PURE__ */ jsx(IconBadge, { icon: Sparkles, variant: pkg.popular ? "gradient" : "tinted", size: "lg" }),
              pkg.popular && /* @__PURE__ */ jsx(Chip, { variant: "featured", icon: /* @__PURE__ */ jsx(Award, { className: "h-3.5 w-3.5", strokeWidth: 2 }), children: t("\u0627\u0644\u0623\u0643\u062B\u0631 \u0634\u0639\u0628\u064A\u0629", "Most Popular") })
            ] }),
            /* @__PURE__ */ jsx("h3", { className: "mt-5 font-display text-xl font-extrabold text-foreground md:text-2xl", children: isRTL ? pkg.nameAr : pkg.name }),
            /* @__PURE__ */ jsxs("div", { className: "mt-2 flex items-baseline gap-2", children: [
              /* @__PURE__ */ jsx("span", { className: "font-display text-4xl font-extrabold tracking-tight text-primary", children: isRTL ? pkg.priceAr : pkg.price }),
              /* @__PURE__ */ jsxs("span", { className: "text-sm text-muted-foreground", children: [
                "/ ",
                isRTL ? pkg.period : pkg.periodEn
              ] })
            ] }),
            /* @__PURE__ */ jsx("ul", { className: "mt-6 flex-1 space-y-3.5", children: pkg.features.map((feature, fIndex) => /* @__PURE__ */ jsxs("li", { className: "flex items-start gap-3", children: [
              /* @__PURE__ */ jsx("span", { className: "mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full text-primary-foreground [background:var(--gradient-coral)]", children: /* @__PURE__ */ jsx(Check, { className: "h-3 w-3", strokeWidth: 3 }) }),
              /* @__PURE__ */ jsx("span", { className: "text-sm leading-relaxed text-foreground/85", children: isRTL ? feature.text : feature.textEn })
            ] }, fIndex)) }),
            /* @__PURE__ */ jsx(Button, { asChild: true, size: "lg", variant: pkg.popular ? "default" : "outline", className: "mt-8 w-full", children: /* @__PURE__ */ jsxs("a", { href: waHref, target: "_blank", rel: "noopener noreferrer", children: [
              /* @__PURE__ */ jsx(WhatsappIcon, {}),
              t("\u0627\u062E\u062A\u0631 \u0647\u0630\u0647 \u0627\u0644\u0628\u0627\u0642\u0629", "Choose This Package")
            ] }) })
          ]
        },
        pkg.id
      );
    }) })
  ] }) });
};
export default MarketingPackagesBoard;
