import { Fragment, jsx, jsxs } from "react/jsx-runtime";
import { blockedExternalRequest } from "@/adapters/local-api";
import { useState } from "react";
import { User, Phone, Mail, Calendar, Send, CheckCircle, ShieldCheck, Clock, Headphones } from "lucide-react";
import { toast } from "@/hooks/use-toast";
import { z } from "zod";
import { supabase } from "@/integrations/supabase/client";
import { useLanguage } from "@/contexts/LanguageContext";
import { trackLeadConversion } from "@/lib/tracking";
import { sendLeadEmail, sendToWPForms } from "@/lib/emailService";
import { Button } from "@/components/ui/button";
import { Board, BoardHeader, IconBadge, RiseIn, WhatsappIcon } from "@/components/v2/primitives";
import { cn } from "@/lib/utils";
const getSchema = (t) => z.object({
  name: z.string().trim().min(2, t("\u0627\u0644\u0627\u0633\u0645 \u064A\u062C\u0628 \u0623\u0646 \u064A\u0643\u0648\u0646 \u062D\u0631\u0641\u064A\u0646 \u0639\u0644\u0649 \u0627\u0644\u0623\u0642\u0644", "Name must be at least 2 characters")).max(100),
  email: z.string().trim().email(t("\u0627\u0644\u0628\u0631\u064A\u062F \u0627\u0644\u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A \u063A\u064A\u0631 \u0635\u062D\u064A\u062D", "Invalid email")).optional().or(z.literal("")),
  phone: z.string().trim().min(8, t("\u0631\u0642\u0645 \u0627\u0644\u0647\u0627\u062A\u0641 \u063A\u064A\u0631 \u0635\u062D\u064A\u062D", "Invalid phone number")).max(15),
  day: z.string().min(1, t("\u0627\u062E\u062A\u0631 \u0627\u0644\u064A\u0648\u0645 \u0627\u0644\u0645\u0641\u0636\u0651\u0644", "Pick a preferred day")),
  slot: z.string().min(1, t("\u0627\u062E\u062A\u0631 \u0627\u0644\u0648\u0642\u062A \u0627\u0644\u0645\u0641\u0636\u0651\u0644", "Pick a preferred time")),
  topic: z.string().optional()
});
const getCountryCodes = (t) => [
  { code: "+20", flag: "\u{1F1EA}\u{1F1EC}", name: t("\u0645\u0635\u0631", "Egypt") },
  { code: "+966", flag: "\u{1F1F8}\u{1F1E6}", name: t("\u0627\u0644\u0633\u0639\u0648\u062F\u064A\u0629", "Saudi Arabia") },
  { code: "+971", flag: "\u{1F1E6}\u{1F1EA}", name: t("\u0627\u0644\u0625\u0645\u0627\u0631\u0627\u062A", "UAE") },
  { code: "+965", flag: "\u{1F1F0}\u{1F1FC}", name: t("\u0627\u0644\u0643\u0648\u064A\u062A", "Kuwait") },
  { code: "+973", flag: "\u{1F1E7}\u{1F1ED}", name: t("\u0627\u0644\u0628\u062D\u0631\u064A\u0646", "Bahrain") },
  { code: "+974", flag: "\u{1F1F6}\u{1F1E6}", name: t("\u0642\u0637\u0631", "Qatar") },
  { code: "+968", flag: "\u{1F1F4}\u{1F1F2}", name: t("\u0639\u0645\u0627\u0646", "Oman") },
  { code: "+962", flag: "\u{1F1EF}\u{1F1F4}", name: t("\u0627\u0644\u0623\u0631\u062F\u0646", "Jordan") },
  { code: "+961", flag: "\u{1F1F1}\u{1F1E7}", name: t("\u0644\u0628\u0646\u0627\u0646", "Lebanon") },
  { code: "+964", flag: "\u{1F1EE}\u{1F1F6}", name: t("\u0627\u0644\u0639\u0631\u0627\u0642", "Iraq") },
  { code: "+212", flag: "\u{1F1F2}\u{1F1E6}", name: t("\u0627\u0644\u0645\u063A\u0631\u0628", "Morocco") }
];
const getSlots = (t) => [
  { id: "morning", label: t("\u0635\u0628\u0627\u062D\u0627\u064B (9\u201312)", "Morning (9\u201312)") },
  { id: "noon", label: t("\u0638\u0647\u0631\u0627\u064B (12\u20133)", "Noon (12\u20133)") },
  { id: "evening", label: t("\u0645\u0633\u0627\u0621\u064B (3\u20136)", "Evening (3\u20136)") },
  { id: "any", label: t("\u0623\u064A \u0648\u0642\u062A", "Anytime") }
];
const inputCls = "w-full rounded-2xl border border-border bg-popover px-5 py-3.5 text-foreground placeholder:text-muted-foreground transition-all duration-200 ease-ds focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/30";
const ConsultationBoard = () => {
  const { t, isRTL } = useLanguage();
  const [form, setForm] = useState({
    name: "",
    email: "",
    countryCode: "+20",
    phone: "",
    day: "",
    slot: "",
    topic: ""
  });
  const [showCountry, setShowCountry] = useState(false);
  const [errors, setErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);
  const [done, setDone] = useState(false);
  const schema = getSchema(t);
  const countryCodes = getCountryCodes(t);
  const slots = getSlots(t);
  const today = (/* @__PURE__ */ new Date()).toISOString().split("T")[0];
  const waLink = `https://wa.me/201100018259?text=${encodeURIComponent(
    t("\u0645\u0631\u062D\u0628\u0627\u064B\u060C \u0623\u0631\u063A\u0628 \u0641\u064A \u062D\u062C\u0632 \u0627\u0633\u062A\u0634\u0627\u0631\u0629 \u0645\u062C\u0627\u0646\u064A\u0629", "Hello, I'd like to book a free consultation")
  )}`;
  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    const result = schema.safeParse(form);
    if (!result.success) {
      const fe = {};
      result.error.errors.forEach((err) => {
        if (err.path[0]) fe[err.path[0]] = err.message;
      });
      setErrors(fe);
      toast({ title: t("\u064A\u0631\u062C\u0649 \u062A\u0635\u062D\u064A\u062D \u0627\u0644\u0623\u062E\u0637\u0627\u0621", "Please fix the errors"), variant: "destructive" });
      return;
    }
    setSubmitting(true);
    try {
      const slotLabel = slots.find((s) => s.id === form.slot)?.label || form.slot;
      const description = [
        `${t("\u0627\u0644\u064A\u0648\u0645 \u0627\u0644\u0645\u0641\u0636\u0651\u0644", "Preferred day")}: ${form.day}`,
        `${t("\u0627\u0644\u0648\u0642\u062A \u0627\u0644\u0645\u0641\u0636\u0651\u0644", "Preferred time")}: ${slotLabel}`,
        form.topic ? `${t("\u0627\u0644\u0645\u0648\u0636\u0648\u0639", "Topic")}: ${form.topic}` : ""
      ].filter(Boolean).join(" | ");
      const leadData = {
        name: form.name,
        email: form.email || t("\u063A\u064A\u0631 \u0645\u062D\u062F\u062F", "Not specified"),
        phone: `${form.countryCode}${form.phone}`,
        company: null,
        project_type: t("\u0627\u0633\u062A\u0634\u0627\u0631\u0629 \u0645\u062C\u0627\u0646\u064A\u0629", "Free Consultation"),
        project_age: null,
        services: [t("\u0627\u0633\u062A\u0634\u0627\u0631\u0629", "Consultation")],
        description,
        budget: null,
        timeline: form.day,
        source: "consultation"
      };
      let saved = false;
      try {
        const { error } = await supabase.from("leads").insert({ ...leadData, services: leadData.services });
        saved = !error;
      } catch {
      }
      const emailSent = await sendLeadEmail(leadData);
      try {
        await sendToWPForms(leadData);
      } catch {
      }
      try {
        await blockedExternalRequest("https://findogroup.com/wp-json/findo/v1/lead", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ ...leadData, services: leadData.services.join(", "), submitted_at: (/* @__PURE__ */ new Date()).toISOString() })
        });
      } catch {
      }
      if (!saved && !emailSent) throw new Error("failed");
      trackLeadConversion("consultation");
      setDone(true);
      setForm({ name: "", email: "", countryCode: "+20", phone: "", day: "", slot: "", topic: "" });
    } catch {
      toast({ title: t("\u062D\u062F\u062B \u062E\u0637\u0623", "An error occurred"), description: t("\u062D\u0627\u0648\u0644 \u0645\u0631\u0629 \u0623\u062E\u0631\u0649 \u0623\u0648 \u0631\u0627\u0633\u0644\u0646\u0627 \u0639\u0644\u0649 \u0648\u0627\u062A\u0633\u0627\u0628", "Try again or message us on WhatsApp"), variant: "destructive" });
    } finally {
      setSubmitting(false);
    }
  };
  return /* @__PURE__ */ jsx(RiseIn, { children: /* @__PURE__ */ jsx(Board, { band: true, dir: isRTL ? "rtl" : "ltr", children: /* @__PURE__ */ jsxs("div", { className: "grid gap-10 lg:grid-cols-2 lg:gap-14", children: [
    /* @__PURE__ */ jsxs("div", { children: [
      /* @__PURE__ */ jsx(
        BoardHeader,
        {
          index: "09",
          eyebrow: t("\u0627\u0633\u062A\u0634\u0627\u0631\u0629 \u0645\u062C\u0627\u0646\u064A\u0629", "Free consultation"),
          pre: t("\u0627\u062D\u062C\u0632 \u0627\u0633\u062A\u0634\u0627\u0631\u062A\u0643", "Book your free"),
          accent: t("\u0627\u0644\u0645\u062C\u0627\u0646\u064A\u0629", "consultation"),
          subtitle: t(
            "\u0627\u062D\u062C\u0632 \u0645\u0648\u0639\u062F\u0627\u064B \u064A\u0646\u0627\u0633\u0628\u0643\u060C \u0648\u0646\u062A\u0648\u0627\u0635\u0644 \u0645\u0639\u0643 \u0644\u0645\u0646\u0627\u0642\u0634\u0629 \u0641\u0643\u0631\u062A\u0643 \u0648\u0623\u0641\u0636\u0644 \u062D\u0644 \u062A\u0642\u0646\u064A \u0644\u0647\u0627 \u2014 \u0628\u062F\u0648\u0646 \u0623\u064A \u0627\u0644\u062A\u0632\u0627\u0645.",
            "Pick a time that suits you and we'll reach out to discuss your idea and the best tech solution \u2014 with no commitment."
          ),
          className: "mb-10"
        }
      ),
      /* @__PURE__ */ jsx("ul", { className: "space-y-5", children: [
        { icon: Clock, ar: "\u0646\u0631\u062F \u0639\u0644\u064A\u0643 \u062E\u0644\u0627\u0644 24 \u0633\u0627\u0639\u0629", en: "We respond within 24 hours" },
        { icon: ShieldCheck, ar: "\u0628\u062F\u0648\u0646 \u0623\u064A \u0627\u0644\u062A\u0632\u0627\u0645 \u0623\u0648 \u0631\u0633\u0648\u0645", en: "No commitment, no fees" },
        { icon: Headphones, ar: "\u0627\u0633\u062A\u0634\u0627\u0631\u0629 \u0645\u0639 \u0645\u0647\u0646\u062F\u0633 \u0645\u062E\u062A\u0635", en: "A talk with a specialist engineer" }
      ].map((b, i) => /* @__PURE__ */ jsxs("li", { className: "flex items-center gap-3 text-foreground/85", children: [
        /* @__PURE__ */ jsx(IconBadge, { icon: b.icon, size: "sm" }),
        t(b.ar, b.en)
      ] }, i)) }),
      /* @__PURE__ */ jsxs(
        "a",
        {
          href: waLink,
          target: "_blank",
          rel: "noopener noreferrer",
          className: "mt-10 inline-flex items-center gap-2 text-sm text-muted-foreground transition-colors duration-200 ease-ds hover:text-success",
          children: [
            /* @__PURE__ */ jsx(WhatsappIcon, { className: "h-4 w-4 text-success" }),
            t("\u0623\u0648 \u0627\u062D\u062C\u0632 \u0645\u0628\u0627\u0634\u0631\u0629 \u0639\u0628\u0631 \u0648\u0627\u062A\u0633\u0627\u0628", "Or book directly on WhatsApp")
          ]
        }
      )
    ] }),
    /* @__PURE__ */ jsx("div", { className: "rounded-3xl border border-border bg-popover p-6 md:p-8", children: done ? /* @__PURE__ */ jsxs("div", { className: "flex h-full flex-col items-center justify-center py-8 text-center", children: [
      /* @__PURE__ */ jsx("span", { className: "mb-5 inline-flex rounded-full border border-success/30 bg-success/15 p-4", children: /* @__PURE__ */ jsx(CheckCircle, { className: "h-12 w-12 text-success", strokeWidth: 1.75 }) }),
      /* @__PURE__ */ jsx("h3", { className: "mb-2 text-2xl font-bold text-foreground", children: t("\u062A\u0645 \u0627\u0633\u062A\u0644\u0627\u0645 \u0637\u0644\u0628\u0643!", "Request received!") }),
      /* @__PURE__ */ jsx("p", { className: "mb-6 leading-relaxed text-muted-foreground", children: t("\u0633\u064A\u062A\u0648\u0627\u0635\u0644 \u0645\u0639\u0643 \u0641\u0631\u064A\u0642\u0646\u0627 \u0641\u064A \u0627\u0644\u0645\u0648\u0639\u062F \u0627\u0644\u0645\u0641\u0636\u0651\u0644 \u0644\u062A\u0623\u0643\u064A\u062F \u0627\u0644\u0627\u0633\u062A\u0634\u0627\u0631\u0629.", "Our team will contact you at your preferred time to confirm.") }),
      /* @__PURE__ */ jsx("button", { onClick: () => setDone(false), className: "text-sm font-medium text-primary transition-colors hover:text-primary/80", children: t("\u062D\u062C\u0632 \u0645\u0648\u0639\u062F \u0622\u062E\u0631", "Book another") })
    ] }) : /* @__PURE__ */ jsxs("form", { onSubmit: handleSubmit, className: "space-y-5", children: [
      /* @__PURE__ */ jsxs("div", { className: "relative", children: [
        /* @__PURE__ */ jsx(User, { className: "pointer-events-none absolute start-4 top-[27px] h-5 w-5 -translate-y-1/2 text-muted-foreground", strokeWidth: 1.75 }),
        /* @__PURE__ */ jsx(
          "input",
          {
            type: "text",
            "aria-label": t("\u0627\u0644\u0627\u0633\u0645", "Name"),
            placeholder: t("\u0627\u0644\u0627\u0633\u0645 *", "Name *"),
            value: form.name,
            onChange: (e) => {
              setForm({ ...form, name: e.target.value });
              setErrors((p) => ({ ...p, name: "" }));
            },
            className: cn(inputCls, "ps-12", errors.name && "border-destructive")
          }
        ),
        errors.name && /* @__PURE__ */ jsx("span", { className: "mt-1 block text-xs text-destructive", children: errors.name })
      ] }),
      /* @__PURE__ */ jsxs("div", { children: [
        /* @__PURE__ */ jsxs("div", { className: "flex gap-2", children: [
          /* @__PURE__ */ jsxs("div", { className: "relative", children: [
            /* @__PURE__ */ jsxs(
              "button",
              {
                type: "button",
                "aria-label": t("\u0643\u0648\u062F \u0627\u0644\u062F\u0648\u0644\u0629", "Country code"),
                onClick: () => setShowCountry(!showCountry),
                className: cn(inputCls, "flex min-w-[110px] items-center justify-center gap-2 px-3 hover:border-primary/50"),
                children: [
                  /* @__PURE__ */ jsx("span", { className: "text-xl", children: countryCodes.find((c) => c.code === form.countryCode)?.flag }),
                  /* @__PURE__ */ jsx("span", { className: "font-display font-medium text-foreground", dir: "ltr", children: form.countryCode })
                ]
              }
            ),
            showCountry && /* @__PURE__ */ jsxs(Fragment, { children: [
              /* @__PURE__ */ jsx("div", { className: "fixed inset-0 z-40", onClick: () => setShowCountry(false) }),
              /* @__PURE__ */ jsx("div", { className: "absolute start-0 top-full z-50 mt-2 max-h-64 w-56 overflow-y-auto rounded-2xl border border-border bg-popover shadow-board", children: countryCodes.map((c) => /* @__PURE__ */ jsxs(
                "button",
                {
                  type: "button",
                  onClick: () => {
                    setForm({ ...form, countryCode: c.code });
                    setShowCountry(false);
                  },
                  className: cn(
                    "flex w-full items-center gap-3 px-4 py-2.5 text-sm transition-colors duration-200 ease-ds hover:bg-primary/10",
                    form.countryCode === c.code ? "bg-primary/15 text-primary" : "text-foreground"
                  ),
                  children: [
                    /* @__PURE__ */ jsx("span", { className: "text-xl", children: c.flag }),
                    /* @__PURE__ */ jsx("span", { className: "flex-1 text-start", children: c.name }),
                    /* @__PURE__ */ jsx("span", { className: "font-display text-xs text-muted-foreground", dir: "ltr", children: c.code })
                  ]
                },
                c.code
              )) })
            ] })
          ] }),
          /* @__PURE__ */ jsxs("div", { className: "relative flex-1", children: [
            /* @__PURE__ */ jsx(Phone, { className: cn("pointer-events-none absolute top-[27px] h-5 w-5 -translate-y-1/2 text-muted-foreground", isRTL ? "right-4" : "left-4"), strokeWidth: 1.75 }),
            /* @__PURE__ */ jsx(
              "input",
              {
                type: "tel",
                dir: "ltr",
                "aria-label": t("\u0631\u0642\u0645 \u0627\u0644\u062C\u0648\u0627\u0644", "Phone number"),
                placeholder: t("\u0631\u0642\u0645 \u0627\u0644\u062C\u0648\u0627\u0644 *", "Phone *"),
                value: form.phone,
                onChange: (e) => {
                  setForm({ ...form, phone: e.target.value.replace(/[^0-9]/g, "") });
                  setErrors((p) => ({ ...p, phone: "" }));
                },
                className: cn(inputCls, isRTL ? "pr-12 text-right" : "pl-12 text-left", errors.phone && "border-destructive")
              }
            )
          ] })
        ] }),
        errors.phone && /* @__PURE__ */ jsx("span", { className: "mt-1 block text-xs text-destructive", children: errors.phone })
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "relative", children: [
        /* @__PURE__ */ jsx(Mail, { className: "pointer-events-none absolute start-4 top-[27px] h-5 w-5 -translate-y-1/2 text-muted-foreground", strokeWidth: 1.75 }),
        /* @__PURE__ */ jsx(
          "input",
          {
            type: "email",
            "aria-label": t("\u0627\u0644\u0628\u0631\u064A\u062F \u0627\u0644\u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A", "Email"),
            placeholder: t("\u0627\u0644\u0628\u0631\u064A\u062F \u0627\u0644\u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A (\u0627\u062E\u062A\u064A\u0627\u0631\u064A)", "Email (optional)"),
            value: form.email,
            onChange: (e) => setForm({ ...form, email: e.target.value }),
            className: cn(inputCls, "ps-12")
          }
        )
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "relative", children: [
        /* @__PURE__ */ jsx(Calendar, { className: "pointer-events-none absolute start-4 top-[27px] h-5 w-5 -translate-y-1/2 text-muted-foreground", strokeWidth: 1.75 }),
        /* @__PURE__ */ jsx(
          "input",
          {
            type: "date",
            "aria-label": t("\u0627\u0644\u064A\u0648\u0645 \u0627\u0644\u0645\u0641\u0636\u0651\u0644", "Preferred day"),
            min: today,
            value: form.day,
            onChange: (e) => {
              setForm({ ...form, day: e.target.value });
              setErrors((p) => ({ ...p, day: "" }));
            },
            className: cn(inputCls, "ps-12 [color-scheme:dark]", errors.day && "border-destructive")
          }
        ),
        errors.day && /* @__PURE__ */ jsx("span", { className: "mt-1 block text-xs text-destructive", children: errors.day })
      ] }),
      /* @__PURE__ */ jsxs("div", { children: [
        /* @__PURE__ */ jsx("label", { className: "mb-2.5 block text-sm font-medium text-foreground/85", children: t("\u0627\u0644\u0648\u0642\u062A \u0627\u0644\u0645\u0641\u0636\u0651\u0644 *", "Preferred time *") }),
        /* @__PURE__ */ jsx("div", { className: "grid grid-cols-2 gap-2.5", children: slots.map((s) => /* @__PURE__ */ jsx(
          "button",
          {
            type: "button",
            onClick: () => {
              setForm({ ...form, slot: s.id });
              setErrors((p) => ({ ...p, slot: "" }));
            },
            className: cn(
              "rounded-2xl border p-2.5 text-sm transition-all duration-200 ease-ds",
              form.slot === s.id ? "border-primary bg-primary/15 text-primary" : "border-border bg-popover text-muted-foreground hover:border-primary/40 hover:text-foreground"
            ),
            children: s.label
          },
          s.id
        )) }),
        errors.slot && /* @__PURE__ */ jsx("span", { className: "mt-1 block text-xs text-destructive", children: errors.slot })
      ] }),
      /* @__PURE__ */ jsx(
        "textarea",
        {
          rows: 2,
          "aria-label": t("\u0645\u0648\u0636\u0648\u0639 \u0627\u0644\u0627\u0633\u062A\u0634\u0627\u0631\u0629", "Consultation topic"),
          placeholder: t("\u0645\u0648\u0636\u0648\u0639 \u0627\u0644\u0627\u0633\u062A\u0634\u0627\u0631\u0629 (\u0627\u062E\u062A\u064A\u0627\u0631\u064A)", "Consultation topic (optional)"),
          value: form.topic,
          onChange: (e) => setForm({ ...form, topic: e.target.value }),
          className: cn(inputCls, "resize-none")
        }
      ),
      /* @__PURE__ */ jsx(Button, { type: "submit", size: "lg", disabled: submitting, className: "w-full", children: submitting ? /* @__PURE__ */ jsxs(Fragment, { children: [
        /* @__PURE__ */ jsx("span", { className: "h-5 w-5 animate-spin rounded-full border-2 border-primary-foreground/30 border-t-primary-foreground" }),
        t("\u062C\u0627\u0631\u064A \u0627\u0644\u062D\u062C\u0632...", "Booking...")
      ] }) : /* @__PURE__ */ jsxs(Fragment, { children: [
        /* @__PURE__ */ jsx(Send, { strokeWidth: 1.75 }),
        t("\u0627\u062D\u062C\u0632 \u0627\u0633\u062A\u0634\u0627\u0631\u062A\u064A \u0627\u0644\u0645\u062C\u0627\u0646\u064A\u0629", "Book my free consultation")
      ] }) })
    ] }) })
  ] }) }) });
};
export default ConsultationBoard;
