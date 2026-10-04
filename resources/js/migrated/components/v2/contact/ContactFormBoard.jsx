import { Fragment, jsx, jsxs } from "react/jsx-runtime";
import { useState } from "react";
import { Mail, Send } from "lucide-react";
import { Button } from "@/components/ui/button";
import { useToast } from "@/hooks/use-toast";
import { supabase } from "@/integrations/supabase/client";
import { useLanguage } from "@/contexts/LanguageContext";
import { trackLeadConversion } from "@/lib/tracking";
import { Board, BoardHeader, IconBadge, RiseIn, WhatsappIcon } from "@/components/v2/primitives";
import { cn } from "@/lib/utils";
const inputCls = "w-full rounded-2xl border border-border bg-background px-5 py-3.5 text-foreground placeholder:text-muted-foreground transition-all duration-200 ease-ds focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/30";
const ContactFormBoard = () => {
  const { toast } = useToast();
  const { t, isRTL } = useLanguage();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    phone: "",
    message: ""
  });
  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsSubmitting(true);
    try {
      const { error } = await supabase.from("leads").insert({
        name: formData.name,
        email: formData.email || "no-email@contact.com",
        phone: formData.phone,
        description: formData.message,
        source: "contact_page"
      });
      if (error) throw error;
      trackLeadConversion("contact_page");
      toast({
        title: t("\u062A\u0645 \u0625\u0631\u0633\u0627\u0644 \u0631\u0633\u0627\u0644\u062A\u0643 \u0628\u0646\u062C\u0627\u062D!", "Your message has been sent successfully!"),
        description: t("\u0633\u0646\u062A\u0648\u0627\u0635\u0644 \u0645\u0639\u0643 \u0641\u064A \u0623\u0642\u0631\u0628 \u0648\u0642\u062A \u0645\u0645\u0643\u0646", "We will contact you as soon as possible")
      });
      setFormData({ name: "", email: "", phone: "", message: "" });
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
  const waLink = `https://wa.me/201100018259?text=${encodeURIComponent(
    isRTL ? "\u0645\u0631\u062D\u0628\u0627\u064B\u060C \u0623\u0631\u064A\u062F \u0627\u0644\u0627\u0633\u062A\u0641\u0633\u0627\u0631 \u0639\u0646 \u062E\u062F\u0645\u0627\u062A\u0643\u0645" : "Hello, I want to inquire about your services"
  )}`;
  return /* @__PURE__ */ jsx(RiseIn, { children: /* @__PURE__ */ jsx(Board, { dir: isRTL ? "rtl" : "ltr", children: /* @__PURE__ */ jsxs("div", { className: "grid gap-10 lg:grid-cols-2 lg:gap-14", children: [
    /* @__PURE__ */ jsxs("div", { children: [
      /* @__PURE__ */ jsx(
        BoardHeader,
        {
          index: "02",
          eyebrow: `${t("\u062A\u0648\u0627\u0635\u0644", "Contact")} ${t("\u0645\u0639\u0646\u0627", "Us")}`,
          pre: t("\u0623\u0631\u0633\u0644 \u0631\u0633\u0627\u0644\u062A\u0643", "Send Your Message"),
          className: "mb-8"
        }
      ),
      /* @__PURE__ */ jsxs(
        "a",
        {
          href: waLink,
          target: "_blank",
          rel: "noopener noreferrer",
          className: "group inline-flex items-center gap-2 text-sm text-muted-foreground transition-colors duration-200 ease-ds hover:text-success",
          children: [
            /* @__PURE__ */ jsx(WhatsappIcon, { className: "h-4 w-4 text-success transition-transform duration-200 ease-ds group-hover:scale-110" }),
            t("\u0623\u0648 \u062A\u0648\u0627\u0635\u0644 \u0645\u0639\u0646\u0627 \u0639\u0628\u0631 \u0648\u0627\u062A\u0633\u0627\u0628 \u0645\u0628\u0627\u0634\u0631\u0629", "Or contact us directly via WhatsApp")
          ]
        }
      ),
      /* @__PURE__ */ jsx("div", { className: "mt-8 rounded-3xl border border-border bg-popover p-6", children: /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-3", children: [
        /* @__PURE__ */ jsx(IconBadge, { icon: Mail, variant: "gradient" }),
        /* @__PURE__ */ jsxs("div", { className: "min-w-0", children: [
          /* @__PURE__ */ jsx("h3", { className: "text-lg font-bold text-foreground", children: t("\u0627\u0644\u062F\u0639\u0645 \u0627\u0644\u0641\u0646\u064A", "Technical Support") }),
          /* @__PURE__ */ jsx(
            "a",
            {
              href: "mailto:support@findogroup.com",
              dir: "ltr",
              className: "break-all text-sm font-semibold text-primary transition-colors duration-200 ease-ds hover:text-primary/80",
              children: "support@findogroup.com"
            }
          )
        ] })
      ] }) })
    ] }),
    /* @__PURE__ */ jsx("div", { className: "rounded-3xl border border-border bg-popover p-6 md:p-8", children: /* @__PURE__ */ jsxs("form", { onSubmit: handleSubmit, className: "space-y-5", children: [
      /* @__PURE__ */ jsxs("div", { children: [
        /* @__PURE__ */ jsxs("label", { className: "mb-2 block text-sm text-foreground/85", children: [
          t("\u0627\u0644\u0627\u0633\u0645 \u0627\u0644\u0643\u0627\u0645\u0644", "Full Name"),
          " *"
        ] }),
        /* @__PURE__ */ jsx(
          "input",
          {
            required: true,
            type: "text",
            value: formData.name,
            onChange: (e) => setFormData({ ...formData, name: e.target.value }),
            placeholder: t("\u0623\u062F\u062E\u0644 \u0627\u0633\u0645\u0643", "Enter your name"),
            className: inputCls
          }
        )
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "grid grid-cols-1 gap-5", children: [
        /* @__PURE__ */ jsxs("div", { children: [
          /* @__PURE__ */ jsxs("label", { className: "mb-2 block text-sm text-foreground/85", children: [
            t("\u0631\u0642\u0645 \u0627\u0644\u0647\u0627\u062A\u0641", "Phone Number"),
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
          /* @__PURE__ */ jsx("label", { className: "mb-2 block text-sm text-foreground/85", children: t("\u0627\u0644\u0628\u0631\u064A\u062F \u0627\u0644\u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A", "Email") }),
          /* @__PURE__ */ jsx(
            "input",
            {
              type: "email",
              value: formData.email,
              onChange: (e) => setFormData({ ...formData, email: e.target.value }),
              placeholder: "example@email.com",
              className: cn(inputCls, isRTL ? "text-right" : "text-left"),
              dir: "ltr"
            }
          )
        ] })
      ] }),
      /* @__PURE__ */ jsxs("div", { children: [
        /* @__PURE__ */ jsxs("label", { className: "mb-2 block text-sm text-foreground/85", children: [
          t("\u0631\u0633\u0627\u0644\u062A\u0643", "Your Message"),
          " *"
        ] }),
        /* @__PURE__ */ jsx(
          "textarea",
          {
            required: true,
            rows: 5,
            value: formData.message,
            onChange: (e) => setFormData({ ...formData, message: e.target.value }),
            placeholder: t("\u0627\u0643\u062A\u0628 \u0631\u0633\u0627\u0644\u062A\u0643 \u0647\u0646\u0627...", "Write your message here..."),
            className: cn(inputCls, "min-h-[150px] resize-none")
          }
        )
      ] }),
      /* @__PURE__ */ jsx(Button, { type: "submit", size: "lg", disabled: isSubmitting, className: "w-full", children: isSubmitting ? /* @__PURE__ */ jsxs(Fragment, { children: [
        /* @__PURE__ */ jsx("span", { className: "h-5 w-5 animate-spin rounded-full border-2 border-primary-foreground/30 border-t-primary-foreground" }),
        t("\u062C\u0627\u0631\u064A \u0627\u0644\u0625\u0631\u0633\u0627\u0644...", "Sending...")
      ] }) : /* @__PURE__ */ jsxs(Fragment, { children: [
        /* @__PURE__ */ jsx(Send, { strokeWidth: 1.75 }),
        t("\u0625\u0631\u0633\u0627\u0644 \u0627\u0644\u0631\u0633\u0627\u0644\u0629", "Send Message")
      ] }) })
    ] }) })
  ] }) }) });
};
export default ContactFormBoard;
