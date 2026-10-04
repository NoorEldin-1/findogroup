import { Fragment, jsx, jsxs } from "react/jsx-runtime";
import { useState } from "react";
import { motion } from "framer-motion";
import { Check, Sparkles, Rocket, TrendingUp, Building2 } from "lucide-react";
import WhatsappIcon from "@/components/v2/primitives/WhatsappIcon";
import { useLanguage } from "@/contexts/LanguageContext";
import {
  pricingGroups,
  pricingServices,
  tierMeta
} from "@/data/pricing";
const WHATSAPP_NUMBER = "201100018259";
const tierIcon = {
  startup: Rocket,
  business: TrendingUp,
  enterprise: Building2
};
const PricingTable = () => {
  const { t, isRTL } = useLanguage();
  const [activeGroup, setActiveGroup] = useState(pricingGroups[0]?.id);
  const groupServices = pricingServices.filter((s) => s.group === activeGroup);
  const [activeServiceId, setActiveServiceId] = useState(groupServices[0]?.id);
  const activeService = groupServices.find((s) => s.id === activeServiceId) ?? groupServices[0];
  const changeGroup = (groupId) => {
    setActiveGroup(groupId);
    const first = pricingServices.find((s) => s.group === groupId);
    if (first) setActiveServiceId(first.id);
  };
  const orderLink = (service, tier) => {
    const name = t(service.nameAr, service.nameEn);
    const tierName = tierMeta[tier].nameEn;
    let msg;
    if (tier === "enterprise") {
      msg = t(
        `\u0645\u0631\u062D\u0628\u064B\u0627 \u0641\u0627\u064A\u0646\u062F\u0648 \u062C\u0631\u0648\u0628\u060C \u0645\u0647\u062A\u0645 \u0628\u0628\u0627\u0642\u0629 Enterprise \u0644\u062E\u062F\u0645\u0629 "${name}" \u0648\u0623\u0631\u063A\u0628 \u0641\u064A \u0639\u0631\u0636 \u0633\u0639\u0631 \u0645\u062E\u0635\u0651\u0635.`,
        `Hi Findo Group, I'm interested in the Enterprise plan for "${name}" and would like a custom quote.`
      );
    } else {
      const price = `${service[tier].price}${t(service.noteAr ?? "", service.noteEn ?? "")}`;
      msg = t(
        `\u0645\u0631\u062D\u0628\u064B\u0627 \u0641\u0627\u064A\u0646\u062F\u0648 \u062C\u0631\u0648\u0628\u060C \u0645\u0647\u062A\u0645 \u0628\u0628\u0627\u0642\u0629 ${tierName} \u0644\u062E\u062F\u0645\u0629 "${name}" (${price}).`,
        `Hi Findo Group, I'm interested in the ${tierName} plan for "${name}" (${price}).`
      );
    }
    return `https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(msg)}`;
  };
  if (!activeService) return null;
  const note = t(activeService.noteAr ?? "", activeService.noteEn ?? "");
  const showBaselineNote = !["marketing", "support"].includes(activeService.group);
  const tiers = [
    { key: "startup", price: activeService.startup.price, featuresAr: activeService.startup.featuresAr, featuresEn: activeService.startup.featuresEn },
    { key: "business", price: activeService.business.price, featuresAr: activeService.business.featuresAr, featuresEn: activeService.business.featuresEn, highlighted: true },
    { key: "enterprise", price: "", featuresAr: tierMeta.enterprise.featuresAr, featuresEn: tierMeta.enterprise.featuresEn, custom: true }
  ];
  return /* @__PURE__ */ jsxs("div", { dir: isRTL ? "rtl" : "ltr", children: [
    /* @__PURE__ */ jsx("div", { className: "flex flex-wrap justify-center gap-2 md:gap-2.5 mb-6", children: pricingGroups.map((g) => {
      const GIcon = g.icon;
      const active = g.id === activeGroup;
      return /* @__PURE__ */ jsxs(
        "button",
        {
          onClick: () => changeGroup(g.id),
          className: `inline-flex items-center gap-2 px-4 py-2.5 rounded-full border text-sm font-semibold transition-all duration-200 ease-ds ${active ? "border-transparent text-primary-foreground [background:var(--gradient-coral)] shadow-glow-coral" : "bg-popover border-border text-muted-foreground hover:text-primary hover:border-primary/60"}`,
          children: [
            /* @__PURE__ */ jsx(GIcon, { className: `w-4 h-4 ${active ? "text-primary-foreground" : "text-primary"}` }),
            t(g.nameAr, g.nameEn)
          ]
        },
        g.id
      );
    }) }),
    /* @__PURE__ */ jsx("div", { className: "flex flex-wrap justify-center gap-2 mb-8", children: groupServices.map((s) => {
      const active = s.id === activeService.id;
      const SIcon = s.icon;
      return /* @__PURE__ */ jsxs(
        "button",
        {
          onClick: () => setActiveServiceId(s.id),
          className: `inline-flex items-center gap-2 px-3.5 py-2 rounded-full text-sm transition-all duration-200 ease-ds ${active ? "bg-primary/15 border border-primary text-primary font-semibold" : "bg-popover border border-border text-muted-foreground hover:text-primary hover:border-primary/60"}`,
          children: [
            /* @__PURE__ */ jsx(SIcon, { className: `w-3.5 h-3.5 ${active ? "text-primary" : "text-primary/70"}` }),
            t(s.nameAr, s.nameEn)
          ]
        },
        s.id
      );
    }) }),
    /* @__PURE__ */ jsxs(
      motion.div,
      {
        initial: { opacity: 0, y: 8 },
        animate: { opacity: 1, y: 0 },
        transition: { duration: 0.35 },
        className: "text-center max-w-2xl mx-auto mb-10",
        children: [
          /* @__PURE__ */ jsx("h3", { className: "text-2xl md:text-3xl font-bold text-foreground tracking-tight", children: t(activeService.nameAr, activeService.nameEn) }),
          /* @__PURE__ */ jsx("p", { className: "mt-2.5 text-muted-foreground leading-relaxed", children: t(activeService.descAr, activeService.descEn) })
        ]
      },
      `${activeService.id}-head`
    ),
    /* @__PURE__ */ jsx(
      motion.div,
      {
        initial: { opacity: 0, y: 16 },
        animate: { opacity: 1, y: 0 },
        transition: { duration: 0.4 },
        className: "grid md:grid-cols-3 gap-6 lg:gap-6 max-w-5xl mx-auto items-stretch",
        children: tiers.map((tier) => {
          const meta = tierMeta[tier.key];
          const TierIcon = tierIcon[tier.key];
          const features = isRTL ? tier.featuresAr : tier.featuresEn;
          const priceNum = tier.price.replace(/^\$/, "");
          return /* @__PURE__ */ jsxs(
            "div",
            {
              className: `group relative rounded-3xl p-7 md:p-8 flex flex-col transition-all duration-300 ${tier.highlighted ? "bg-gradient-to-b from-primary/[0.12] via-popover to-popover border border-primary/50 ring-1 ring-primary/25 shadow-glow-coral lg:scale-[1.05] lg:-my-2 z-10" : "bg-popover border border-border hover:border-primary/40 hover:-translate-y-1"}`,
              children: [
                tier.highlighted && /* @__PURE__ */ jsxs(Fragment, { children: [
                  /* @__PURE__ */ jsx("div", { className: "absolute -top-px inset-x-10 h-px bg-gradient-to-r from-transparent via-primary to-transparent", "aria-hidden": true }),
                  /* @__PURE__ */ jsxs("span", { className: "absolute -top-3 start-1/2 -translate-x-1/2 rtl:translate-x-1/2 inline-flex items-center gap-1 px-3 py-1 rounded-full text-primary-foreground [background:var(--gradient-coral)] text-[11px] font-semibold shadow-glow-coral whitespace-nowrap", children: [
                    /* @__PURE__ */ jsx(Sparkles, { className: "w-3 h-3" }),
                    t("\u0627\u0644\u0623\u0643\u062B\u0631 \u0637\u0644\u0628\u064B\u0627", "Most popular")
                  ] })
                ] }),
                /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-3 mb-2", children: [
                  /* @__PURE__ */ jsx("span", { className: `w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 ${tier.highlighted ? "text-primary-foreground [background:var(--gradient-coral)] shadow-glow-coral" : "bg-primary/10 border border-primary/20 text-primary"}`, children: /* @__PURE__ */ jsx(TierIcon, { className: "w-5 h-5" }) }),
                  /* @__PURE__ */ jsx("span", { className: "text-xl font-bold text-foreground font-display", children: meta.nameEn })
                ] }),
                /* @__PURE__ */ jsx("p", { className: "text-muted-foreground text-sm leading-snug min-h-[40px]", children: t(meta.descAr, meta.descEn) }),
                /* @__PURE__ */ jsx("div", { className: "mt-6 mb-6 min-h-[56px] flex items-end", children: tier.custom ? /* @__PURE__ */ jsxs("div", { children: [
                  /* @__PURE__ */ jsx("div", { className: "text-3xl font-extrabold text-foreground font-display leading-none", children: t("\u062D\u0633\u0628 \u0627\u0644\u0637\u0644\u0628", "Custom") }),
                  /* @__PURE__ */ jsx("div", { className: "text-muted-foreground/80 text-xs mt-1.5", children: t("\u0639\u0631\u0636 \u0633\u0639\u0631 \u0645\u062E\u0635\u0651\u0635", "Custom quote") })
                ] }) : /* @__PURE__ */ jsxs("div", { className: "flex items-baseline gap-1", dir: "ltr", children: [
                  /* @__PURE__ */ jsx("span", { className: "text-2xl font-semibold text-foreground/80 font-display", children: "$" }),
                  /* @__PURE__ */ jsx("span", { className: "text-5xl font-extrabold text-foreground font-display tabular-nums leading-none", children: priceNum }),
                  note && /* @__PURE__ */ jsx("span", { className: "text-muted-foreground/80 text-sm ms-1", children: note })
                ] }) }),
                /* @__PURE__ */ jsx("div", { className: "border-t border-border mb-6" }),
                /* @__PURE__ */ jsx("ul", { className: "space-y-3.5 mb-8 flex-1", children: features.map((f, i) => /* @__PURE__ */ jsxs("li", { className: "flex items-start gap-3 text-sm text-foreground/80 leading-snug", children: [
                  /* @__PURE__ */ jsx("span", { className: `mt-0.5 w-5 h-5 rounded-md flex items-center justify-center flex-shrink-0 ${tier.highlighted ? "[background:var(--gradient-coral)]" : "bg-popover border border-border"}`, children: /* @__PURE__ */ jsx(Check, { className: `w-3 h-3 ${tier.highlighted ? "text-primary-foreground" : "text-primary"}`, strokeWidth: 3 }) }),
                  f
                ] }, i)) }),
                /* @__PURE__ */ jsxs(
                  "a",
                  {
                    href: orderLink(activeService, tier.key),
                    target: "_blank",
                    rel: "noopener noreferrer",
                    className: `inline-flex items-center justify-center gap-2 rounded-[17px] py-3.5 text-sm font-bold transition-all duration-200 ease-ds hover:-translate-y-0.5 ${tier.highlighted ? "text-primary-foreground [background:var(--gradient-coral)] shadow-btn-primary hover:shadow-glow-coral-lg" : "border border-primary/55 bg-transparent text-primary hover:bg-primary/10 hover:border-primary"}`,
                    children: [
                      /* @__PURE__ */ jsx(WhatsappIcon, { className: "w-4 h-4 text-green-400" }),
                      tier.custom ? t("\u062A\u0648\u0627\u0635\u0644 \u0645\u0639\u0646\u0627", "Contact us") : t("\u0627\u0637\u0644\u0628 \u0639\u0628\u0631 \u0648\u0627\u062A\u0633\u0627\u0628", "Order via WhatsApp")
                    ]
                  }
                )
              ]
            },
            tier.key
          );
        })
      },
      activeService.id
    ),
    showBaselineNote && /* @__PURE__ */ jsx("p", { className: "text-center text-muted-foreground/80 text-xs md:text-sm mt-8 max-w-2xl mx-auto leading-relaxed", children: t(
      "+ \u0643\u0644 \u0628\u0627\u0642\u0629 \u062A\u0634\u0645\u0644 \u0623\u064A\u0636\u064B\u0627 \u0643\u0644 \u0639\u0646\u0627\u0635\u0631 \xAB\u0643\u0644 \u0645\u0634\u0631\u0648\u0639 \u064A\u0634\u0645\u0644\xBB \u0627\u0644\u0645\u0648\u0636\u0651\u062D\u0629 \u0628\u0627\u0644\u0623\u0633\u0641\u0644 (\u062A\u062D\u0644\u064A\u0644\u060C \u062A\u0635\u0645\u064A\u0645\u060C \u062A\u0637\u0648\u064A\u0631\u060C \u0627\u062E\u062A\u0628\u0627\u0631\u060C \u0623\u0645\u0627\u0646\u060C \u0646\u0634\u0631\u060C \u062A\u062F\u0631\u064A\u0628...).",
      "+ Every plan also includes all the \xABEvery project includes\xBB essentials shown below (analysis, design, development, testing, security, deployment, training\u2026)."
    ) })
  ] });
};
export default PricingTable;
