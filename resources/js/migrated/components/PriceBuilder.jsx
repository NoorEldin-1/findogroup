import { Fragment, jsx, jsxs } from "react/jsx-runtime";
import { useMemo, useState } from "react";
import { motion } from "framer-motion";
import { Check, ChevronDown, Minus, Plus } from "lucide-react";
import WhatsappIcon from "@/components/v2/primitives/WhatsappIcon";
import { useLanguage } from "@/contexts/LanguageContext";
import {
  builderAddOns,
  builderServices,
  formatUSD,
  WHATSAPP_NUMBER
} from "@/data/priceBuilder";
const fadeUp = {
  initial: { opacity: 0, y: 20 },
  whileInView: { opacity: 1, y: 0 },
  viewport: { once: true, margin: "0px 0px -80px 0px" },
  transition: { duration: 0.55, ease: [0.22, 1, 0.36, 1] }
};
const optionDefault = (o) => {
  if (o.type === "counter") return o.included ?? o.min ?? 0;
  if (o.type === "select") return o.defaultChoiceId ?? o.choices?.[0]?.id ?? "";
  return o.defaultOn ?? false;
};
const defaultsFor = (svc) => {
  const st = {};
  svc.options.forEach((o) => {
    st[o.id] = optionDefault(o);
  });
  return st;
};
const PriceBuilder = () => {
  const { t, isRTL } = useLanguage();
  const [serviceId, setServiceId] = useState(builderServices[0].id);
  const service = builderServices.find((s) => s.id === serviceId) ?? builderServices[0];
  const [opts, setOpts] = useState(() => defaultsFor(service));
  const [addOns, setAddOns] = useState(() => /* @__PURE__ */ new Set());
  const [open, setOpen] = useState(false);
  const selectService = (id) => {
    const svc = builderServices.find((s) => s.id === id);
    if (!svc) return;
    setServiceId(id);
    setOpts(defaultsFor(svc));
    setOpen(false);
  };
  const setCounter = (o, delta) => setOpts((prev) => {
    const cur = prev[o.id] ?? o.included ?? 0;
    const next = Math.min(o.max ?? 999, Math.max(o.min ?? 0, cur + delta));
    return { ...prev, [o.id]: next };
  });
  const setSelect = (o, choiceId) => setOpts((prev) => ({ ...prev, [o.id]: choiceId }));
  const toggleOpt = (o) => setOpts((prev) => ({ ...prev, [o.id]: !prev[o.id] }));
  const toggleAddOn = (id) => setAddOns((prev) => {
    const next = new Set(prev);
    next.has(id) ? next.delete(id) : next.add(id);
    return next;
  });
  const { oneTime, monthly } = useMemo(() => {
    let one = service.basePrice;
    let mo = 0;
    service.options.forEach((o) => {
      const v = opts[o.id];
      if (o.type === "counter") {
        one += Math.max(0, v - (o.included ?? 0)) * (o.unitPrice ?? 0);
      } else if (o.type === "select") {
        one += o.choices?.find((c) => c.id === v)?.price ?? 0;
      } else if (o.type === "toggle" && v === true) {
        one += o.price ?? 0;
      }
    });
    builderAddOns.forEach((a) => {
      if (!addOns.has(a.id)) return;
      if (a.recurring) mo += a.price;
      else one += a.price;
    });
    return { oneTime: one, monthly: mo };
  }, [service, opts, addOns]);
  const waLink = useMemo(() => {
    const parts = [
      t("\u0645\u0631\u062D\u0628\u064B\u0627 \u0641\u0627\u064A\u0646\u062F\u0648 \u062C\u0631\u0648\u0628\u060C \u062D\u0627\u0628\u0628 \u0623\u0637\u0644\u0628 \u0639\u0631\u0636 \u0633\u0639\u0631 \u0645\u062E\u0635\u0651\u0635:", "Hi Findo Group, I'd like a custom quote:"),
      `\u2022 ${t("\u0627\u0644\u062E\u062F\u0645\u0629", "Service")}: ${t(service.nameAr, service.nameEn)}`
    ];
    service.options.forEach((o) => {
      const v = opts[o.id];
      if (o.type === "counter") {
        parts.push(`\u2022 ${t(o.labelAr, o.labelEn)}: ${v} ${t(o.unitLabelAr ?? "", o.unitLabelEn ?? "")}`.trimEnd());
      } else if (o.type === "select") {
        const ch = o.choices?.find((c) => c.id === v);
        if (ch) parts.push(`\u2022 ${t(o.labelAr, o.labelEn)}: ${t(ch.labelAr, ch.labelEn)}`);
      } else if (o.type === "toggle" && v === true) {
        parts.push(`\u2022 ${t(o.labelAr, o.labelEn)}: ${t("\u0646\u0639\u0645", "Yes")}`);
      }
    });
    const chosen = builderAddOns.filter((a) => addOns.has(a.id));
    if (chosen.length) {
      parts.push(`\u2022 ${t("\u0625\u0636\u0627\u0641\u0627\u062A", "Add-ons")}: ${chosen.map((a) => t(a.labelAr, a.labelEn)).join(t("\u060C ", ", "))}`);
    }
    parts.push(`\u2022 ${t("\u0627\u0644\u0625\u062C\u0645\u0627\u0644\u064A \u0627\u0644\u062A\u0642\u062F\u064A\u0631\u064A", "Estimated total")}: ${formatUSD(oneTime)}`);
    if (monthly > 0) parts.push(`\u2022 ${t("\u0627\u0634\u062A\u0631\u0627\u0643 \u0634\u0647\u0631\u064A", "Monthly")}: ${formatUSD(monthly)}`);
    return `https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(parts.join("\n"))}`;
  }, [service, opts, addOns, oneTime, monthly, t]);
  const ServiceIcon = service.icon;
  return /* @__PURE__ */ jsxs("div", { dir: isRTL ? "rtl" : "ltr", children: [
    /* @__PURE__ */ jsxs(motion.div, { ...fadeUp, className: "text-center max-w-xl mx-auto mb-8", children: [
      /* @__PURE__ */ jsx("h2", { className: "text-3xl md:text-4xl font-bold text-foreground tracking-tight", children: t("\u062D\u062F\u0651\u062F \u0633\u0639\u0631 \u062E\u062F\u0645\u062A\u0643", "Build your price") }),
      /* @__PURE__ */ jsx("p", { className: "mt-3 text-muted-foreground leading-relaxed", children: t(
        "\u0627\u062E\u062A\u0631 \u062E\u062F\u0645\u062A\u0643\u060C \u0627\u0638\u0628\u0637 \u0627\u0644\u062A\u0641\u0627\u0635\u064A\u0644\u060C \u0648\u0634\u0648\u0641 \u0627\u0644\u0633\u0639\u0631 \u0627\u0644\u062A\u0642\u062F\u064A\u0631\u064A \u0641\u0648\u0631\u064B\u0627.",
        "Pick your service, adjust the details, and see an instant estimate."
      ) })
    ] }),
    /* @__PURE__ */ jsxs(motion.div, { ...fadeUp, className: "max-w-2xl mx-auto rounded-[28px] border border-border bg-popover shadow-board p-6 md:p-8", children: [
      /* @__PURE__ */ jsx("label", { className: "block text-muted-foreground text-xs font-medium mb-2", children: t("\u0627\u0644\u062E\u062F\u0645\u0629", "Service") }),
      /* @__PURE__ */ jsxs("div", { className: "relative", children: [
        /* @__PURE__ */ jsxs(
          "button",
          {
            type: "button",
            onClick: () => setOpen((o) => !o),
            className: "w-full flex items-center justify-between gap-3 rounded-2xl border border-border bg-background px-4 py-3.5 hover:border-primary/50 transition-colors duration-200 ease-ds",
            children: [
              /* @__PURE__ */ jsxs("span", { className: "flex items-center gap-3 min-w-0", children: [
                /* @__PURE__ */ jsx("span", { className: "w-9 h-9 rounded-xl bg-primary/15 text-primary flex items-center justify-center flex-shrink-0", children: /* @__PURE__ */ jsx(ServiceIcon, { className: "w-4 h-4" }) }),
                /* @__PURE__ */ jsx("span", { className: "text-foreground font-medium truncate", children: t(service.nameAr, service.nameEn) })
              ] }),
              /* @__PURE__ */ jsx(ChevronDown, { className: `w-5 h-5 text-muted-foreground transition-transform flex-shrink-0 ${open ? "rotate-180" : ""}` })
            ]
          }
        ),
        open && /* @__PURE__ */ jsxs(Fragment, { children: [
          /* @__PURE__ */ jsx("div", { className: "fixed inset-0 z-20", onClick: () => setOpen(false), "aria-hidden": true }),
          /* @__PURE__ */ jsx("div", { className: "absolute z-30 mt-2 inset-x-0 max-h-72 overflow-y-auto rounded-2xl border border-border bg-card shadow-board p-1.5", children: builderServices.map((s) => {
            const Icon = s.icon;
            const active = s.id === serviceId;
            return /* @__PURE__ */ jsxs(
              "button",
              {
                type: "button",
                onClick: () => selectService(s.id),
                className: `w-full flex items-center gap-3 rounded-xl px-3 py-2.5 text-start transition-colors ${active ? "bg-primary/15 text-foreground" : "text-muted-foreground hover:bg-muted/60 hover:text-foreground"}`,
                children: [
                  /* @__PURE__ */ jsx(Icon, { className: `w-4 h-4 flex-shrink-0 ${active ? "text-primary" : "text-muted-foreground"}` }),
                  /* @__PURE__ */ jsx("span", { className: "text-sm truncate", children: t(s.nameAr, s.nameEn) }),
                  active && /* @__PURE__ */ jsx(Check, { className: "w-4 h-4 text-primary ms-auto flex-shrink-0" })
                ]
              },
              s.id
            );
          }) })
        ] })
      ] }),
      /* @__PURE__ */ jsx("div", { className: "mt-5 divide-y divide-border/70", children: service.options.map((o) => /* @__PURE__ */ jsxs("div", { className: "flex items-center justify-between gap-3 py-3.5 flex-wrap", children: [
        /* @__PURE__ */ jsx("span", { className: "text-foreground/90 text-sm", children: t(o.labelAr, o.labelEn) }),
        o.type === "counter" && /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-3", children: [
          /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground/70 text-xs", children: [
            "+",
            formatUSD(o.unitPrice ?? 0),
            "/",
            t(o.unitLabelAr ?? "", o.unitLabelEn ?? "")
          ] }),
          /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-1.5", children: [
            /* @__PURE__ */ jsx(
              "button",
              {
                type: "button",
                "aria-label": "decrease",
                onClick: () => setCounter(o, -1),
                className: "w-8 h-8 rounded-full border border-border text-muted-foreground hover:border-primary hover:text-foreground flex items-center justify-center transition-colors duration-200 ease-ds",
                children: /* @__PURE__ */ jsx(Minus, { className: "w-3.5 h-3.5" })
              }
            ),
            /* @__PURE__ */ jsx("span", { className: "min-w-[2.5ch] text-center text-foreground font-semibold text-sm tabular-nums", children: opts[o.id] }),
            /* @__PURE__ */ jsx(
              "button",
              {
                type: "button",
                "aria-label": "increase",
                onClick: () => setCounter(o, 1),
                className: "w-8 h-8 rounded-full border border-border text-muted-foreground hover:border-primary hover:text-foreground flex items-center justify-center transition-colors duration-200 ease-ds",
                children: /* @__PURE__ */ jsx(Plus, { className: "w-3.5 h-3.5" })
              }
            )
          ] })
        ] }),
        o.type === "select" && /* @__PURE__ */ jsx("div", { className: "flex items-center gap-1 bg-background border border-border rounded-full p-1 flex-wrap", children: o.choices?.map((c) => {
          const active = opts[o.id] === c.id;
          return /* @__PURE__ */ jsxs(
            "button",
            {
              type: "button",
              onClick: () => setSelect(o, c.id),
              className: `rounded-full px-3 py-1.5 text-xs font-semibold transition-all duration-200 ease-ds ${active ? "text-primary-foreground [background:var(--gradient-coral)]" : "text-muted-foreground hover:text-foreground"}`,
              children: [
                t(c.labelAr, c.labelEn),
                c.price > 0 && /* @__PURE__ */ jsxs("span", { className: "opacity-80", children: [
                  " +",
                  formatUSD(c.price)
                ] })
              ]
            },
            c.id
          );
        }) }),
        o.type === "toggle" && /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-3", children: [
          (o.price ?? 0) > 0 && /* @__PURE__ */ jsxs("span", { className: "text-muted-foreground/70 text-xs", children: [
            "+",
            formatUSD(o.price ?? 0)
          ] }),
          /* @__PURE__ */ jsx(
            "button",
            {
              type: "button",
              role: "switch",
              "aria-checked": opts[o.id] === true,
              onClick: () => toggleOpt(o),
              className: `relative w-12 h-7 rounded-full transition-colors duration-200 ease-ds flex-shrink-0 ${opts[o.id] === true ? "bg-primary" : "bg-muted"}`,
              children: /* @__PURE__ */ jsx(
                "span",
                {
                  className: `absolute top-1 w-5 h-5 rounded-full bg-white shadow transition-all ${opts[o.id] === true ? "start-6" : "start-1"}`
                }
              )
            }
          )
        ] })
      ] }, o.id)) }),
      /* @__PURE__ */ jsxs("div", { className: "mt-6", children: [
        /* @__PURE__ */ jsx("div", { className: "text-muted-foreground text-xs font-medium mb-3", children: t("\u0625\u0636\u0627\u0641\u0627\u062A (\u0627\u062E\u062A\u064A\u0627\u0631\u064A)", "Add-ons (optional)") }),
        /* @__PURE__ */ jsx("div", { className: "flex flex-wrap gap-2", children: builderAddOns.map((a) => {
          const on = addOns.has(a.id);
          return /* @__PURE__ */ jsxs(
            "button",
            {
              type: "button",
              onClick: () => toggleAddOn(a.id),
              className: `inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-semibold transition-all duration-200 ease-ds ${on ? "bg-primary/15 border-primary text-primary" : "bg-popover border-border text-muted-foreground hover:text-primary hover:border-primary/60"}`,
              children: [
                on ? /* @__PURE__ */ jsx(Check, { className: "w-3.5 h-3.5 text-primary" }) : /* @__PURE__ */ jsx(Plus, { className: "w-3.5 h-3.5" }),
                t(a.labelAr, a.labelEn),
                /* @__PURE__ */ jsxs("span", { className: on ? "text-primary/80" : "text-primary/70", children: [
                  "+",
                  formatUSD(a.price),
                  a.recurring ? t("/\u0634", "/mo") : ""
                ] })
              ]
            },
            a.id
          );
        }) })
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "mt-7 pt-5 border-t border-border", children: [
        /* @__PURE__ */ jsxs("div", { className: "flex items-end justify-between gap-4", children: [
          /* @__PURE__ */ jsxs("div", { children: [
            /* @__PURE__ */ jsx("div", { className: "text-muted-foreground text-xs", children: t("\u0627\u0644\u0625\u062C\u0645\u0627\u0644\u064A \u0627\u0644\u062A\u0642\u062F\u064A\u0631\u064A", "Estimated total") }),
            monthly > 0 && /* @__PURE__ */ jsxs("div", { className: "text-primary text-xs mt-0.5 font-medium", children: [
              "+ ",
              formatUSD(monthly),
              t("/\u0634\u0647\u0631\u064A\u064B\u0627", "/mo")
            ] })
          ] }),
          /* @__PURE__ */ jsx("div", { className: "font-display text-4xl font-extrabold text-foreground tabular-nums", children: formatUSD(oneTime) })
        ] }),
        /* @__PURE__ */ jsxs(
          "a",
          {
            href: waLink,
            target: "_blank",
            rel: "noopener noreferrer",
            className: "mt-4 w-full inline-flex items-center justify-center gap-2 rounded-[17px] py-3.5 text-sm font-bold text-primary-foreground [background:var(--gradient-coral)] shadow-btn-primary hover:-translate-y-0.5 hover:shadow-glow-coral-lg active:translate-y-px transition-all duration-200 ease-ds",
            children: [
              /* @__PURE__ */ jsx(WhatsappIcon, { className: "w-4 h-4" }),
              t("\u0627\u0637\u0644\u0628 \u0639\u0631\u0636\u0643 \u0627\u0644\u0645\u062E\u0635\u0651\u0635", "Request your quote")
            ]
          }
        ),
        /* @__PURE__ */ jsx("p", { className: "text-muted-foreground/80 text-[11px] text-center mt-3", children: t("\u0633\u0639\u0631 \u062A\u0642\u062F\u064A\u0631\u064A \u0645\u0628\u062F\u0626\u064A \u2014 \u0646\u0623\u0643\u0651\u062F\u0647 \u0645\u0639\u0643 \u0639\u0644\u0649 \u0648\u0627\u062A\u0633\u0627\u0628.", "Preliminary estimate \u2014 confirmed on WhatsApp.") })
      ] })
    ] })
  ] });
};
export default PriceBuilder;
