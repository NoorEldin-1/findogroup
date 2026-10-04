import { Fragment, jsx, jsxs } from "react/jsx-runtime";
import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { ClipboardList, Check, Send } from "lucide-react";
import findoLogo from "@/assets/findo-logo.png.js";
import aastmtLogo from "@/assets/training/aastmt-logo.jpeg.js";
import { Button } from "@/components/ui/button";
import { useToast } from "@/hooks/use-toast";
import { supabase } from "@/integrations/supabase/client";
import { useLanguage } from "@/contexts/LanguageContext";
import { Board, Eyebrow, IconBadge, RiseIn } from "@/components/v2/primitives";
import { trainingProgram as p, trainingDepartments } from "@/data/training";
import { cn } from "@/lib/utils";
const inputCls = "w-full rounded-2xl border border-border bg-background px-5 py-3.5 text-foreground placeholder:text-muted-foreground transition-all duration-200 ease-ds focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/30";
const TrainingRegisterBoard = () => {
  const { toast } = useToast();
  const { t, isRTL } = useLanguage();
  const navigate = useNavigate();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    phone: "",
    universityId: "",
    department: ""
  });
  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsSubmitting(true);
    try {
      const { error } = await supabase.from("training_registrations").insert({
        name: formData.name,
        email: formData.email,
        phone: formData.phone,
        university_id: formData.universityId,
        department: formData.department,
        program: "\u0627\u0644\u0628\u0631\u0646\u0627\u0645\u062C \u0627\u0644\u062A\u062F\u0631\u064A\u0628\u064A \u0627\u0644\u0635\u064A\u0641\u064A 2026 (AASTMT \xD7 Findo)",
        source: "aastmt-training"
      });
      if (error) throw error;
      toast({
        title: t("\u062A\u0645 \u062A\u0633\u062C\u064A\u0644\u0643 \u0628\u0646\u062C\u0627\u062D!", "You're registered!"),
        description: t("\u062A\u0645 \u062D\u0641\u0638 \u062A\u0633\u062C\u064A\u0644\u0643 \u0645\u062D\u0644\u064A\u064B\u0627. \u0631\u0628\u0637 \u0627\u0644\u062D\u0633\u0627\u0628\u0627\u062A \u0648\u0627\u0644\u0628\u0631\u064A\u062F \u0641\u064A \u0627\u0644\u0645\u0631\u062D\u0644\u0629 \u0627\u0644\u062A\u0627\u0644\u064A\u0629.", "Your registration was saved locally. Account and email delivery will be connected later.")
      });
      navigate("/training/welcome");
    } catch (error) {
      toast({
        title: t("\u062D\u062F\u062B \u062E\u0637\u0623", "An error occurred"),
        description: t("\u064A\u0631\u062C\u0649 \u0627\u0644\u0645\u062D\u0627\u0648\u0644\u0629 \u0645\u0631\u0629 \u0623\u062E\u0631\u0649", "Please try again"),
        variant: "destructive"
      });
    } finally {
      setIsSubmitting(false);
    }
  };
  return /* @__PURE__ */ jsx(RiseIn, { mount: true, children: /* @__PURE__ */ jsxs(Board, { dir: isRTL ? "rtl" : "ltr", className: "lg:p-16", children: [
    /* @__PURE__ */ jsxs("nav", { className: "mb-8 flex flex-wrap items-center gap-2 text-sm text-muted-foreground", children: [
      /* @__PURE__ */ jsx(Link, { to: "/", className: "transition-colors duration-200 ease-ds hover:text-foreground", children: t("\u0627\u0644\u0631\u0626\u064A\u0633\u064A\u0629", "Home") }),
      /* @__PURE__ */ jsx("span", { "aria-hidden": true, className: "text-muted-foreground/50", children: "/" }),
      /* @__PURE__ */ jsx(Link, { to: "/training", className: "transition-colors duration-200 ease-ds hover:text-foreground", children: t("\u0627\u0644\u062A\u062F\u0631\u064A\u0628", "Training") }),
      /* @__PURE__ */ jsx("span", { "aria-hidden": true, className: "text-muted-foreground/50", children: "/" }),
      /* @__PURE__ */ jsx("span", { className: "text-foreground/80", children: t("\u0627\u0644\u062A\u0633\u062C\u064A\u0644", "Register") })
    ] }),
    /* @__PURE__ */ jsxs("div", { className: "grid gap-10 lg:grid-cols-2 lg:gap-14", children: [
      /* @__PURE__ */ jsxs("div", { children: [
        /* @__PURE__ */ jsx(Eyebrow, { index: "01", className: "mb-4", children: t("\u0627\u0644\u062A\u0633\u062C\u064A\u0644 \u0641\u064A \u0627\u0644\u0628\u0631\u0646\u0627\u0645\u062C", "Program registration") }),
        /* @__PURE__ */ jsx("h1", { className: "font-display text-3xl font-extrabold leading-tight tracking-tight text-foreground md:text-4xl", children: t("\u0633\u062C\u0651\u0644 \u0628\u064A\u0627\u0646\u0627\u062A\u0643 \u0644\u0644\u0627\u0644\u062A\u062D\u0627\u0642 \u0628\u0627\u0644\u0628\u0631\u0646\u0627\u0645\u062C", "Register to join the program") }),
        /* @__PURE__ */ jsx("p", { className: "mt-4 max-w-xl text-base leading-relaxed text-muted-foreground", children: t(p.registrationNoteAr, p.registrationNoteEn) }),
        /* @__PURE__ */ jsxs("div", { className: "mt-7 flex flex-wrap items-center gap-4", children: [
          /* @__PURE__ */ jsx("div", { className: "grid place-items-center rounded-2xl border border-border bg-white px-4 py-3 shadow-card", children: /* @__PURE__ */ jsx("img", { src: findoLogo, alt: "Findo Group", className: "h-8 w-auto object-contain" }) }),
          /* @__PURE__ */ jsx("span", { "aria-hidden": true, className: "text-xl font-light text-muted-foreground", children: "\xD7" }),
          /* @__PURE__ */ jsx("div", { className: "grid place-items-center rounded-2xl border border-border bg-white px-4 py-2.5 shadow-card", children: /* @__PURE__ */ jsx("img", { src: aastmtLogo, alt: t(p.academyAr, p.academyEn), className: "h-11 w-auto object-contain" }) })
        ] }),
        /* @__PURE__ */ jsxs("div", { className: "mt-8 rounded-3xl border border-border bg-popover p-6", children: [
          /* @__PURE__ */ jsxs("div", { className: "mb-4 flex items-center gap-3", children: [
            /* @__PURE__ */ jsx(IconBadge, { icon: ClipboardList, variant: "gradient" }),
            /* @__PURE__ */ jsx("h2", { className: "text-lg font-bold text-foreground", children: t("\u0628\u0639\u062F \u0627\u0644\u062A\u0633\u062C\u064A\u0644", "What happens next") })
          ] }),
          /* @__PURE__ */ jsx("ul", { className: "space-y-3", children: [
            t("\u0645\u0631\u0627\u062C\u0639\u0629 \u0628\u064A\u0627\u0646\u0627\u062A\u0643 \u0648\u062A\u0623\u0643\u064A\u062F \u0642\u0628\u0648\u0644\u0643 \u0641\u064A \u0627\u0644\u0628\u0631\u0646\u0627\u0645\u062C.", "We review your details and confirm your acceptance."),
            t("\u0625\u0631\u0633\u0627\u0644 \u0628\u064A\u0627\u0646\u0627\u062A \u0627\u0644\u062F\u062E\u0648\u0644 \u0625\u0644\u0649 \u0645\u0646\u0635\u0629 Findo Group Academy.", "We send your Findo Group Academy login."),
            t("\u0627\u0633\u062A\u0644\u0627\u0645 \u0627\u0644\u0643\u062A\u064A\u0628 \u0627\u0644\u062A\u062F\u0631\u064A\u0628\u064A \u0645\u0646 \u0645\u0642\u0631 \u0627\u0644\u0634\u0631\u0643\u0629.", "You collect the training handbook from our office.")
          ].map((step, i) => /* @__PURE__ */ jsxs("li", { className: "flex items-start gap-2.5", children: [
            /* @__PURE__ */ jsx(Check, { className: "mt-0.5 h-4 w-4 shrink-0 text-primary", strokeWidth: 2.5 }),
            /* @__PURE__ */ jsx("span", { className: "text-sm leading-relaxed text-foreground/90", children: step })
          ] }, i)) })
        ] })
      ] }),
      /* @__PURE__ */ jsx("div", { className: "rounded-3xl border border-border bg-popover p-6 md:p-8", children: /* @__PURE__ */ jsxs("form", { onSubmit: handleSubmit, className: "space-y-5", children: [
        /* @__PURE__ */ jsxs("div", { children: [
          /* @__PURE__ */ jsxs("label", { className: "mb-2 block text-sm text-foreground/85", children: [
            t("\u0627\u0644\u0627\u0633\u0645 \u0628\u0627\u0644\u0643\u0627\u0645\u0644", "Full name"),
            " *"
          ] }),
          /* @__PURE__ */ jsx(
            "input",
            {
              required: true,
              type: "text",
              value: formData.name,
              onChange: (e) => setFormData({ ...formData, name: e.target.value }),
              placeholder: t("\u0623\u062F\u062E\u0644 \u0627\u0633\u0645\u0643 \u0628\u0627\u0644\u0643\u0627\u0645\u0644", "Enter your full name"),
              className: inputCls
            }
          )
        ] }),
        /* @__PURE__ */ jsxs("div", { children: [
          /* @__PURE__ */ jsxs("label", { className: "mb-2 block text-sm text-foreground/85", children: [
            t("\u0631\u0642\u0645 \u0627\u0644\u0647\u0627\u062A\u0641", "Phone number"),
            " *"
          ] }),
          /* @__PURE__ */ jsx(
            "input",
            {
              required: true,
              type: "tel",
              value: formData.phone,
              onChange: (e) => setFormData({ ...formData, phone: e.target.value }),
              placeholder: "01xxxxxxxxx",
              className: cn(inputCls, isRTL ? "text-right" : "text-left"),
              dir: "ltr"
            }
          )
        ] }),
        /* @__PURE__ */ jsxs("div", { children: [
          /* @__PURE__ */ jsxs("label", { className: "mb-2 block text-sm text-foreground/85", children: [
            t("\u0627\u0644\u0628\u0631\u064A\u062F \u0627\u0644\u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A", "Email"),
            " *"
          ] }),
          /* @__PURE__ */ jsx(
            "input",
            {
              required: true,
              type: "email",
              value: formData.email,
              onChange: (e) => setFormData({ ...formData, email: e.target.value }),
              placeholder: "example@email.com",
              className: cn(inputCls, isRTL ? "text-right" : "text-left"),
              dir: "ltr"
            }
          )
        ] }),
        /* @__PURE__ */ jsxs("div", { children: [
          /* @__PURE__ */ jsxs("label", { className: "mb-2 block text-sm text-foreground/85", children: [
            t("\u0631\u0642\u0645 \u0627\u0644\u062A\u0633\u062C\u064A\u0644 \u0627\u0644\u062C\u0627\u0645\u0639\u064A", "University registration number"),
            " *"
          ] }),
          /* @__PURE__ */ jsx(
            "input",
            {
              required: true,
              type: "text",
              value: formData.universityId,
              onChange: (e) => setFormData({ ...formData, universityId: e.target.value }),
              placeholder: t("\u0631\u0642\u0645\u0643 \u0627\u0644\u062C\u0627\u0645\u0639\u064A \u0628\u0627\u0644\u0623\u0643\u0627\u062F\u064A\u0645\u064A\u0629", "Your academy ID"),
              className: cn(inputCls, isRTL ? "text-right" : "text-left"),
              dir: "ltr"
            }
          )
        ] }),
        /* @__PURE__ */ jsxs("div", { children: [
          /* @__PURE__ */ jsxs("label", { className: "mb-2 block text-sm text-foreground/85", children: [
            t("\u0627\u0644\u0642\u0633\u0645", "Department"),
            " *"
          ] }),
          /* @__PURE__ */ jsxs(
            "select",
            {
              required: true,
              value: formData.department,
              onChange: (e) => setFormData({ ...formData, department: e.target.value }),
              className: inputCls,
              children: [
                /* @__PURE__ */ jsx("option", { value: "", disabled: true, children: t("\u0627\u062E\u062A\u0631 \u0642\u0633\u0645\u0643", "Select your department") }),
                trainingDepartments.map((d, i) => /* @__PURE__ */ jsx("option", { value: t(d.ar, d.en), children: t(d.ar, d.en) }, i))
              ]
            }
          )
        ] }),
        /* @__PURE__ */ jsx(Button, { type: "submit", size: "lg", disabled: isSubmitting, className: "w-full", children: isSubmitting ? /* @__PURE__ */ jsxs(Fragment, { children: [
          /* @__PURE__ */ jsx("span", { className: "h-5 w-5 animate-spin rounded-full border-2 border-primary-foreground/30 border-t-primary-foreground" }),
          t("\u062C\u0627\u0631\u064A \u0627\u0644\u0625\u0631\u0633\u0627\u0644...", "Submitting...")
        ] }) : /* @__PURE__ */ jsxs(Fragment, { children: [
          /* @__PURE__ */ jsx(Send, { strokeWidth: 1.75 }),
          t("\u0625\u0631\u0633\u0627\u0644 \u0637\u0644\u0628 \u0627\u0644\u062A\u0633\u062C\u064A\u0644", "Submit registration")
        ] }) }),
        /* @__PURE__ */ jsx("p", { className: "text-center text-xs leading-relaxed text-muted-foreground", children: t("\u0628\u0627\u0644\u062A\u0633\u062C\u064A\u0644 \u0623\u0646\u062A \u062A\u0648\u0627\u0641\u0642 \u0639\u0644\u0649 \u0644\u0648\u0627\u0626\u062D \u0627\u0644\u0628\u0631\u0646\u0627\u0645\u062C \u0648\u0634\u0631\u0648\u0637 \u0627\u0644\u062D\u0636\u0648\u0631.", "By registering you agree to the program rules and attendance requirements.") })
      ] }) })
    ] })
  ] }) });
};
export default TrainingRegisterBoard;
