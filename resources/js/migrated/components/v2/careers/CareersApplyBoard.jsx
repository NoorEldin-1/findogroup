import { Fragment, jsx, jsxs } from "react/jsx-runtime";
import { blockedExternalRequest } from "@/adapters/local-api";
import { useState } from "react";
import {
  Award,
  Briefcase,
  CheckCircle,
  ChevronDown,
  Code,
  FileText,
  Loader2,
  Mail,
  Phone,
  Send,
  User
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { useToast } from "@/hooks/use-toast";
import { supabase } from "@/integrations/supabase/client";
import { Board, BoardHeader, RiseIn } from "@/components/v2/primitives";
import { useLanguage } from "@/contexts/LanguageContext";
const sendJobApplicationEmail = async (data) => {
  try {
    const response = await blockedExternalRequest("https://findogroup.com/wp-json/findo/v1/send-lead-email", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        to: "dodboy77@gmail.com",
        subject: `\u0637\u0644\u0628 \u062A\u0648\u0638\u064A\u0641 \u062C\u062F\u064A\u062F - ${data.positionLabel} - ${data.name}`,
        message: formatJobApplicationEmail(data),
        lead_data: data
      })
    });
    if (response.ok) return true;
    throw new Error("WordPress email failed");
  } catch (error) {
    console.error("WordPress email error:", error);
    try {
      await blockedExternalRequest("https://formsubmit.co/ajax/dodboy77@gmail.com", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "Accept": "application/json"
        },
        body: JSON.stringify({
          name: data.name,
          email: data.email,
          phone: data.phone,
          position: data.positionLabel,
          experience: data.experienceLabel,
          portfolio: data.portfolio || "\u063A\u064A\u0631 \u0645\u062D\u062F\u062F",
          message: data.message || "\u063A\u064A\u0631 \u0645\u062D\u062F\u062F",
          _subject: `\u0637\u0644\u0628 \u062A\u0648\u0638\u064A\u0641 \u062C\u062F\u064A\u062F - ${data.positionLabel} - ${data.name}`,
          _template: "table"
        })
      });
      return true;
    } catch (fallbackError) {
      console.error("Fallback email error:", fallbackError);
      return false;
    }
  }
};
const formatJobApplicationEmail = (data) => {
  return `
===========================================
\u{1F4BC} \u0637\u0644\u0628 \u062A\u0648\u0638\u064A\u0641 \u062C\u062F\u064A\u062F - Findo Group
===========================================

\u{1F464} \u0645\u0639\u0644\u0648\u0645\u0627\u062A \u0627\u0644\u0645\u062A\u0642\u062F\u0645:
\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501
\u0627\u0644\u0627\u0633\u0645: ${data.name}
\u0627\u0644\u0628\u0631\u064A\u062F \u0627\u0644\u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A: ${data.email}
\u0631\u0642\u0645 \u0627\u0644\u0647\u0627\u062A\u0641: ${data.phone}

\u{1F4BC} \u062A\u0641\u0627\u0635\u064A\u0644 \u0627\u0644\u0648\u0638\u064A\u0641\u0629:
\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501
\u0627\u0644\u0648\u0638\u064A\u0641\u0629 \u0627\u0644\u0645\u0637\u0644\u0648\u0628\u0629: ${data.positionLabel}
\u0645\u0633\u062A\u0648\u0649 \u0627\u0644\u062E\u0628\u0631\u0629: ${data.experienceLabel}
Portfolio/LinkedIn: ${data.portfolio || "\u063A\u064A\u0631 \u0645\u062D\u062F\u062F"}

\u{1F4DD} \u0646\u0628\u0630\u0629 \u0639\u0646 \u0627\u0644\u0645\u062A\u0642\u062F\u0645:
\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501\u2501
${data.message || "\u0644\u0645 \u064A\u062A\u0645 \u062A\u0642\u062F\u064A\u0645 \u0646\u0628\u0630\u0629"}

\u{1F4CD} \u0627\u0644\u0645\u0635\u062F\u0631: \u0635\u0641\u062D\u0629 \u0627\u0644\u062A\u0648\u0638\u064A\u0641 (Careers Page)
\u23F0 \u0627\u0644\u062A\u0627\u0631\u064A\u062E: ${(/* @__PURE__ */ new Date()).toLocaleString("ar-EG", { timeZone: "Africa/Cairo" })}

===========================================
\u2705 \u064A\u0631\u062C\u0649 \u0645\u0631\u0627\u062C\u0639\u0629 \u0627\u0644\u0637\u0644\u0628 \u0648\u0627\u0644\u062A\u0648\u0627\u0635\u0644 \u0645\u0639 \u0627\u0644\u0645\u062A\u0642\u062F\u0645
===========================================
  `;
};
const dsSelect = "h-11 w-full cursor-pointer appearance-none rounded-[15px] border border-input bg-background px-3.5 py-2 pe-10 text-base text-foreground transition-[border-color,box-shadow] duration-200 focus-visible:outline-none focus-visible:border-primary focus-visible:shadow-[0_0_0_3px_rgba(253,128,46,0.18)] md:text-sm";
const CareersApplyBoard = () => {
  const { t, isRTL } = useLanguage();
  const { toast } = useToast();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitted, setSubmitted] = useState(false);
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    phone: "",
    position: "",
    experience: "",
    portfolio: "",
    message: ""
  });
  const positions = [
    { value: "frontend", labelAr: "\u0645\u0637\u0648\u0631 \u0648\u0627\u062C\u0647\u0627\u062A \u0623\u0645\u0627\u0645\u064A\u0629 (Frontend)", labelEn: "Frontend Developer" },
    { value: "backend", labelAr: "\u0645\u0637\u0648\u0631 \u062E\u0644\u0641\u064A\u0627\u062A (Backend)", labelEn: "Backend Developer" },
    { value: "fullstack", labelAr: "\u0645\u0637\u0648\u0631 \u0645\u062A\u0643\u0627\u0645\u0644 (Full Stack)", labelEn: "Full Stack Developer" },
    { value: "mobile", labelAr: "\u0645\u0637\u0648\u0631 \u062A\u0637\u0628\u064A\u0642\u0627\u062A \u0645\u0648\u0628\u0627\u064A\u0644", labelEn: "Mobile App Developer" },
    { value: "ui-ux", labelAr: "\u0645\u0635\u0645\u0645 UI/UX", labelEn: "UI/UX Designer" },
    { value: "graphic", labelAr: "\u0645\u0635\u0645\u0645 \u062C\u0631\u0627\u0641\u064A\u0643", labelEn: "Graphic Designer" },
    { value: "marketing", labelAr: "\u0623\u062E\u0635\u0627\u0626\u064A \u062A\u0633\u0648\u064A\u0642 \u0631\u0642\u0645\u064A", labelEn: "Digital Marketing Specialist" },
    { value: "sales", labelAr: "\u0645\u0633\u0624\u0648\u0644 \u0645\u0628\u064A\u0639\u0627\u062A", labelEn: "Sales Representative" },
    { value: "pm", labelAr: "\u0645\u062F\u064A\u0631 \u0645\u0634\u0627\u0631\u064A\u0639", labelEn: "Project Manager" },
    { value: "other", labelAr: "\u0623\u062E\u0631\u0649", labelEn: "Other" }
  ];
  const experienceLevels = [
    { value: "junior", labelAr: "\u0645\u0628\u062A\u062F\u0626 (0-2 \u0633\u0646\u0648\u0627\u062A)", labelEn: "Junior (0-2 years)" },
    { value: "mid", labelAr: "\u0645\u062A\u0648\u0633\u0637 (2-5 \u0633\u0646\u0648\u0627\u062A)", labelEn: "Mid-level (2-5 years)" },
    { value: "senior", labelAr: "\u062E\u0628\u064A\u0631 (5+ \u0633\u0646\u0648\u0627\u062A)", labelEn: "Senior (5+ years)" }
  ];
  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };
  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsSubmitting(true);
    const positionLabel = positions.find((p) => p.value === formData.position)?.labelAr || formData.position;
    const experienceLabel = experienceLevels.find((e2) => e2.value === formData.experience)?.labelAr || formData.experience;
    try {
      const { error: dbError } = await supabase.from("leads").insert({
        name: formData.name,
        email: formData.email,
        phone: formData.phone,
        project_type: "job_application",
        description: `\u0627\u0644\u0648\u0638\u064A\u0641\u0629: ${positionLabel}
\u0627\u0644\u062E\u0628\u0631\u0629: ${experienceLabel}
Portfolio: ${formData.portfolio || "\u063A\u064A\u0631 \u0645\u062D\u062F\u062F"}

\u0627\u0644\u0631\u0633\u0627\u0644\u0629: ${formData.message || "\u0644\u0645 \u064A\u062A\u0645 \u062A\u0642\u062F\u064A\u0645 \u0631\u0633\u0627\u0644\u0629"}`,
        source: "careers_page"
      });
      if (dbError) {
        console.error("Supabase error:", dbError);
      }
      const emailData = {
        name: formData.name,
        email: formData.email,
        phone: formData.phone,
        position: formData.position,
        positionLabel,
        experience: formData.experience,
        experienceLabel,
        portfolio: formData.portfolio,
        message: formData.message
      };
      await sendJobApplicationEmail(emailData);
      try {
        await blockedExternalRequest("https://findogroup.com/wp-json/findo/v1/lead", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            name: formData.name,
            email: formData.email,
            phone: formData.phone,
            project_type: "job_application",
            position: positionLabel,
            experience: experienceLabel,
            portfolio: formData.portfolio || "\u063A\u064A\u0631 \u0645\u062D\u062F\u062F",
            description: formData.message || "\u0644\u0645 \u064A\u062A\u0645 \u062A\u0642\u062F\u064A\u0645 \u0631\u0633\u0627\u0644\u0629",
            submitted_at: (/* @__PURE__ */ new Date()).toISOString(),
            source: "careers_page"
          })
        });
      } catch {
      }
      setSubmitted(true);
      toast({
        title: t("\u062A\u0645 \u0625\u0631\u0633\u0627\u0644 \u0637\u0644\u0628\u0643 \u0628\u0646\u062C\u0627\u062D!", "Application Submitted Successfully!"),
        description: t("\u0633\u0646\u062A\u0648\u0627\u0635\u0644 \u0645\u0639\u0643 \u0642\u0631\u064A\u0628\u0627\u064B", "We'll contact you soon")
      });
    } catch (error) {
      console.error("Form submission error:", error);
      toast({
        title: t("\u062D\u062F\u062B \u062E\u0637\u0623", "An error occurred"),
        description: t("\u064A\u0631\u062C\u0649 \u0627\u0644\u0645\u062D\u0627\u0648\u0644\u0629 \u0645\u0631\u0629 \u0623\u062E\u0631\u0649", "Please try again"),
        variant: "destructive"
      });
    } finally {
      setIsSubmitting(false);
    }
  };
  return /* @__PURE__ */ jsx(RiseIn, { children: /* @__PURE__ */ jsxs(Board, { id: "apply", dir: isRTL ? "rtl" : "ltr", className: "scroll-mt-24", children: [
    /* @__PURE__ */ jsx(
      BoardHeader,
      {
        index: "03",
        eyebrow: t("\u0627\u0628\u062F\u0623 \u0627\u0644\u0622\u0646", "Get started"),
        pre: t("\u0642\u062F\u0645 \u0637\u0644\u0628\u0643", "Apply"),
        accent: t("\u0627\u0644\u0622\u0646", "Now"),
        subtitle: t(
          "\u0627\u0645\u0644\u0623 \u0627\u0644\u0646\u0645\u0648\u0630\u062C \u0623\u062F\u0646\u0627\u0647 \u0648\u0633\u0646\u062A\u0648\u0627\u0635\u0644 \u0645\u0639\u0643 \u0641\u064A \u0623\u0642\u0631\u0628 \u0648\u0642\u062A",
          "Fill out the form below and we'll get back to you soon"
        )
      }
    ),
    /* @__PURE__ */ jsx("div", { className: "mx-auto max-w-3xl", children: submitted ? /* @__PURE__ */ jsxs("div", { className: "rounded-3xl border border-primary/30 bg-popover px-8 py-16 text-center", children: [
      /* @__PURE__ */ jsx("div", { className: "mx-auto mb-6 grid h-20 w-20 place-items-center rounded-full border border-success/30 bg-success/15", children: /* @__PURE__ */ jsx(CheckCircle, { className: "h-10 w-10 text-success", strokeWidth: 1.75 }) }),
      /* @__PURE__ */ jsx("h3", { className: "mb-3 font-display text-2xl font-extrabold text-foreground", children: t("\u0634\u0643\u0631\u0627\u064B \u0644\u062A\u0642\u062F\u064A\u0645 \u0637\u0644\u0628\u0643!", "Thank You for Applying!") }),
      /* @__PURE__ */ jsx("p", { className: "mx-auto max-w-md leading-relaxed text-muted-foreground", children: t(
        "\u0644\u0642\u062F \u0627\u0633\u062A\u0644\u0645\u0646\u0627 \u0637\u0644\u0628\u0643 \u0648\u0633\u064A\u0642\u0648\u0645 \u0641\u0631\u064A\u0642 \u0627\u0644\u0645\u0648\u0627\u0631\u062F \u0627\u0644\u0628\u0634\u0631\u064A\u0629 \u0628\u0645\u0631\u0627\u062C\u0639\u062A\u0647 \u0648\u0627\u0644\u062A\u0648\u0627\u0635\u0644 \u0645\u0639\u0643 \u062E\u0644\u0627\u0644 \u0627\u0644\u0623\u064A\u0627\u0645 \u0627\u0644\u0642\u0627\u062F\u0645\u0629.",
        "We've received your application. Our HR team will review it and contact you within the next few days."
      ) })
    ] }) : /* @__PURE__ */ jsxs("form", { onSubmit: handleSubmit, className: "space-y-6 rounded-3xl border border-border bg-popover p-6 md:p-8", children: [
      /* @__PURE__ */ jsxs("div", { className: "grid grid-cols-1 gap-6 md:grid-cols-2", children: [
        /* @__PURE__ */ jsxs("div", { className: "space-y-2", children: [
          /* @__PURE__ */ jsxs(Label, { htmlFor: "name", className: "flex items-center gap-2 font-medium text-foreground", children: [
            /* @__PURE__ */ jsx(User, { className: "h-4 w-4 text-primary", strokeWidth: 1.75 }),
            t("\u0627\u0644\u0627\u0633\u0645 \u0627\u0644\u0643\u0627\u0645\u0644", "Full Name"),
            " *"
          ] }),
          /* @__PURE__ */ jsx(
            Input,
            {
              id: "name",
              name: "name",
              value: formData.name,
              onChange: handleInputChange,
              placeholder: t("\u0623\u062F\u062E\u0644 \u0627\u0633\u0645\u0643 \u0627\u0644\u0643\u0627\u0645\u0644", "Enter your full name"),
              required: true
            }
          )
        ] }),
        /* @__PURE__ */ jsxs("div", { className: "space-y-2", children: [
          /* @__PURE__ */ jsxs(Label, { htmlFor: "email", className: "flex items-center gap-2 font-medium text-foreground", children: [
            /* @__PURE__ */ jsx(Mail, { className: "h-4 w-4 text-primary", strokeWidth: 1.75 }),
            t("\u0627\u0644\u0628\u0631\u064A\u062F \u0627\u0644\u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A", "Email"),
            " *"
          ] }),
          /* @__PURE__ */ jsx(
            Input,
            {
              id: "email",
              name: "email",
              type: "email",
              value: formData.email,
              onChange: handleInputChange,
              placeholder: t("\u0623\u062F\u062E\u0644 \u0628\u0631\u064A\u062F\u0643 \u0627\u0644\u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A", "Enter your email"),
              required: true
            }
          )
        ] })
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "grid grid-cols-1 gap-6 md:grid-cols-2", children: [
        /* @__PURE__ */ jsxs("div", { className: "space-y-2", children: [
          /* @__PURE__ */ jsxs(Label, { htmlFor: "phone", className: "flex items-center gap-2 font-medium text-foreground", children: [
            /* @__PURE__ */ jsx(Phone, { className: "h-4 w-4 text-primary", strokeWidth: 1.75 }),
            t("\u0631\u0642\u0645 \u0627\u0644\u0647\u0627\u062A\u0641", "Phone Number"),
            " *"
          ] }),
          /* @__PURE__ */ jsx(
            Input,
            {
              id: "phone",
              name: "phone",
              type: "tel",
              value: formData.phone,
              onChange: handleInputChange,
              placeholder: t("\u0623\u062F\u062E\u0644 \u0631\u0642\u0645 \u0647\u0627\u062A\u0641\u0643", "Enter your phone number"),
              required: true,
              dir: "ltr",
              className: "text-start"
            }
          )
        ] }),
        /* @__PURE__ */ jsxs("div", { className: "space-y-2", children: [
          /* @__PURE__ */ jsxs(Label, { htmlFor: "position", className: "flex items-center gap-2 font-medium text-foreground", children: [
            /* @__PURE__ */ jsx(Briefcase, { className: "h-4 w-4 text-primary", strokeWidth: 1.75 }),
            t("\u0627\u0644\u0648\u0638\u064A\u0641\u0629 \u0627\u0644\u0645\u0637\u0644\u0648\u0628\u0629", "Position"),
            " *"
          ] }),
          /* @__PURE__ */ jsxs("div", { className: "relative", children: [
            /* @__PURE__ */ jsxs(
              "select",
              {
                id: "position",
                name: "position",
                value: formData.position,
                onChange: handleInputChange,
                required: true,
                className: dsSelect,
                children: [
                  /* @__PURE__ */ jsx("option", { value: "", children: t("\u0627\u062E\u062A\u0631 \u0627\u0644\u0648\u0638\u064A\u0641\u0629", "Select Position") }),
                  positions.map((pos) => /* @__PURE__ */ jsx("option", { value: pos.value, children: t(pos.labelAr, pos.labelEn) }, pos.value))
                ]
              }
            ),
            /* @__PURE__ */ jsx(ChevronDown, { className: "pointer-events-none absolute end-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" })
          ] })
        ] })
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "grid grid-cols-1 gap-6 md:grid-cols-2", children: [
        /* @__PURE__ */ jsxs("div", { className: "space-y-2", children: [
          /* @__PURE__ */ jsxs(Label, { htmlFor: "experience", className: "flex items-center gap-2 font-medium text-foreground", children: [
            /* @__PURE__ */ jsx(Award, { className: "h-4 w-4 text-primary", strokeWidth: 1.75 }),
            t("\u0645\u0633\u062A\u0648\u0649 \u0627\u0644\u062E\u0628\u0631\u0629", "Experience Level"),
            " *"
          ] }),
          /* @__PURE__ */ jsxs("div", { className: "relative", children: [
            /* @__PURE__ */ jsxs(
              "select",
              {
                id: "experience",
                name: "experience",
                value: formData.experience,
                onChange: handleInputChange,
                required: true,
                className: dsSelect,
                children: [
                  /* @__PURE__ */ jsx("option", { value: "", children: t("\u0627\u062E\u062A\u0631 \u0645\u0633\u062A\u0648\u0649 \u0627\u0644\u062E\u0628\u0631\u0629", "Select Experience Level") }),
                  experienceLevels.map((level) => /* @__PURE__ */ jsx("option", { value: level.value, children: t(level.labelAr, level.labelEn) }, level.value))
                ]
              }
            ),
            /* @__PURE__ */ jsx(ChevronDown, { className: "pointer-events-none absolute end-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" })
          ] })
        ] }),
        /* @__PURE__ */ jsxs("div", { className: "space-y-2", children: [
          /* @__PURE__ */ jsxs(Label, { htmlFor: "portfolio", className: "flex items-center gap-2 font-medium text-foreground", children: [
            /* @__PURE__ */ jsx(FileText, { className: "h-4 w-4 text-primary", strokeWidth: 1.75 }),
            t("\u0631\u0627\u0628\u0637 \u0627\u0644\u0645\u0644\u0641 \u0627\u0644\u0634\u062E\u0635\u064A / Portfolio", "Portfolio / LinkedIn URL")
          ] }),
          /* @__PURE__ */ jsx(
            Input,
            {
              id: "portfolio",
              name: "portfolio",
              type: "url",
              value: formData.portfolio,
              onChange: handleInputChange,
              placeholder: t("\u0631\u0627\u0628\u0637 LinkedIn \u0623\u0648 Portfolio", "LinkedIn or Portfolio URL"),
              dir: "ltr",
              className: "text-start"
            }
          )
        ] })
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "space-y-2", children: [
        /* @__PURE__ */ jsxs(Label, { htmlFor: "message", className: "flex items-center gap-2 font-medium text-foreground", children: [
          /* @__PURE__ */ jsx(Code, { className: "h-4 w-4 text-primary", strokeWidth: 1.75 }),
          t("\u0631\u0633\u0627\u0644\u0629 \u0623\u0648 \u0646\u0628\u0630\u0629 \u0639\u0646\u0643", "About You / Cover Letter")
        ] }),
        /* @__PURE__ */ jsx(
          Textarea,
          {
            id: "message",
            name: "message",
            value: formData.message,
            onChange: handleInputChange,
            placeholder: t(
              "\u0623\u062E\u0628\u0631\u0646\u0627 \u0639\u0646 \u0646\u0641\u0633\u0643\u060C \u062E\u0628\u0631\u0627\u062A\u0643 \u0627\u0644\u0633\u0627\u0628\u0642\u0629\u060C \u0648\u0644\u0645\u0627\u0630\u0627 \u062A\u0631\u063A\u0628 \u0641\u064A \u0627\u0644\u0627\u0646\u0636\u0645\u0627\u0645 \u0625\u0644\u064A\u0646\u0627...",
              "Tell us about yourself, your experience, and why you want to join us..."
            ),
            rows: 5,
            className: "resize-none"
          }
        )
      ] }),
      /* @__PURE__ */ jsx(Button, { type: "submit", size: "lg", disabled: isSubmitting, className: "w-full", children: isSubmitting ? /* @__PURE__ */ jsxs(Fragment, { children: [
        /* @__PURE__ */ jsx(Loader2, { className: "h-5 w-5 animate-spin" }),
        t("\u062C\u0627\u0631\u064A \u0627\u0644\u0625\u0631\u0633\u0627\u0644...", "Submitting...")
      ] }) : /* @__PURE__ */ jsxs(Fragment, { children: [
        /* @__PURE__ */ jsx(Send, { className: "h-5 w-5", strokeWidth: 1.75 }),
        t("\u0625\u0631\u0633\u0627\u0644 \u0627\u0644\u0637\u0644\u0628", "Submit Application")
      ] }) }),
      /* @__PURE__ */ jsx("p", { className: "text-center text-sm text-muted-foreground", children: t(
        "\u0628\u0627\u0644\u0646\u0642\u0631 \u0639\u0644\u0649 \u0625\u0631\u0633\u0627\u0644\u060C \u0623\u0646\u062A \u062A\u0648\u0627\u0641\u0642 \u0639\u0644\u0649 \u0645\u0639\u0627\u0644\u062C\u0629 \u0628\u064A\u0627\u0646\u0627\u062A\u0643 \u0648\u0641\u0642\u0627\u064B \u0644\u0633\u064A\u0627\u0633\u0629 \u0627\u0644\u062E\u0635\u0648\u0635\u064A\u0629",
        "By clicking submit, you agree to our privacy policy for processing your data"
      ) })
    ] }) })
  ] }) });
};
export default CareersApplyBoard;
