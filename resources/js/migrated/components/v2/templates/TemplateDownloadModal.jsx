import { Fragment, jsx, jsxs } from "react/jsx-runtime";
import { useState } from "react";
import { Download, Lock } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from "@/components/ui/dialog";
import { useToast } from "@/hooks/use-toast";
import { supabase } from "@/integrations/supabase/client";
import { useLanguage } from "@/contexts/LanguageContext";
import { trackLeadConversion } from "@/lib/tracking";
import { cn } from "@/lib/utils";
const inputCls = "w-full rounded-2xl border border-border bg-background px-5 py-3.5 text-foreground placeholder:text-muted-foreground transition-all duration-200 ease-ds focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/30";
const TemplateDownloadModal = ({ template, onClose }) => {
  const { toast } = useToast();
  const { t, isRTL } = useLanguage();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [formData, setFormData] = useState({ name: "", email: "", phone: "" });
  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!template) return;
    setIsSubmitting(true);
    try {
      const title = t(template.titleAr, template.titleEn);
      const { error } = await supabase.from("leads").insert({
        name: formData.name,
        email: formData.email || "no-email@contact.com",
        phone: formData.phone,
        description: t(
          `\u062A\u062D\u0645\u064A\u0644 \u0642\u0627\u0644\u0628 \u062A\u0633\u0648\u064A\u0642\u064A: ${title} (\u0646\u0648\u0639 \u0627\u0644\u0645\u0644\u0641: ${template.fileType})`,
          `Marketing template download: ${title} (file type: ${template.fileType})`
        ),
        source: "templates-download"
      });
      if (error) throw error;
      trackLeadConversion("templates-download");
      if (template.fileUrl) {
        window.open(template.fileUrl, "_blank", "noopener,noreferrer");
        toast({
          title: t("\u0628\u062F\u0623 \u0627\u0644\u062A\u062D\u0645\u064A\u0644!", "Download started!"),
          description: t("\u062A\u062D\u0642\u0651\u0642 \u0645\u0646 \u0639\u0644\u0627\u0645\u0629 \u0627\u0644\u062A\u0628\u0648\u064A\u0628 \u0627\u0644\u062C\u062F\u064A\u062F\u0629.", "Check the new browser tab.")
        });
      } else {
        toast({
          title: t("\u062A\u0645 \u0627\u0633\u062A\u0644\u0627\u0645 \u0637\u0644\u0628\u0643!", "We got your request!"),
          description: t("\u0633\u0646\u0631\u0633\u0644 \u0644\u0643 \u0627\u0644\u0645\u0644\u0641 \u0639\u0644\u0649 \u0628\u0631\u064A\u062F\u0643 \u0642\u0631\u064A\u0628\u064B\u0627.", "We'll send the file to your email shortly.")
        });
      }
      setFormData({ name: "", email: "", phone: "" });
      onClose();
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
  return /* @__PURE__ */ jsx(Dialog, { open: !!template, onOpenChange: (open) => !open && onClose(), children: /* @__PURE__ */ jsxs(
    DialogContent,
    {
      dir: isRTL ? "rtl" : "ltr",
      className: "rounded-3xl border-border bg-popover",
      children: [
        /* @__PURE__ */ jsxs(DialogHeader, { className: "text-start sm:text-start", children: [
          /* @__PURE__ */ jsx(DialogTitle, { className: "font-display text-xl font-bold text-foreground", children: t("\u062A\u062D\u0645\u064A\u0644 \u0627\u0644\u0642\u0627\u0644\u0628 \u0645\u062C\u0627\u0646\u064B\u0627", "Download the template for free") }),
          /* @__PURE__ */ jsx(DialogDescription, { className: "text-sm leading-relaxed text-muted-foreground", children: template ? t(
            `\u0623\u062F\u062E\u0644 \u0628\u064A\u0627\u0646\u0627\u062A\u0643 \u0644\u062A\u062D\u0645\u064A\u0644 \xAB${template.titleAr}\xBB. \u0633\u0646\u0631\u0633\u0644 \u0644\u0643 \u0623\u064A\u0636\u064B\u0627 \u0623\u062D\u062F\u062B \u0627\u0644\u0642\u0648\u0627\u0644\u0628 \u0648\u0627\u0644\u0623\u062F\u0648\u0627\u062A.`,
            `Enter your details to download "${template.titleEn}". We'll also share the latest templates and tools.`
          ) : "" })
        ] }),
        /* @__PURE__ */ jsxs("form", { onSubmit: handleSubmit, className: "space-y-4", children: [
          /* @__PURE__ */ jsxs("div", { children: [
            /* @__PURE__ */ jsxs("label", { className: "mb-1.5 block text-sm text-foreground/85", children: [
              t("\u0627\u0644\u0627\u0633\u0645", "Name"),
              " *"
            ] }),
            /* @__PURE__ */ jsx(
              "input",
              {
                required: true,
                type: "text",
                value: formData.name,
                onChange: (e) => setFormData({ ...formData, name: e.target.value }),
                placeholder: t("\u0627\u0633\u0645\u0643", "Your name"),
                className: inputCls
              }
            )
          ] }),
          /* @__PURE__ */ jsxs("div", { children: [
            /* @__PURE__ */ jsxs("label", { className: "mb-1.5 block text-sm text-foreground/85", children: [
              t("\u0628\u0631\u064A\u062F \u0627\u0644\u0639\u0645\u0644", "Business email"),
              " *"
            ] }),
            /* @__PURE__ */ jsx(
              "input",
              {
                required: true,
                type: "email",
                value: formData.email,
                onChange: (e) => setFormData({ ...formData, email: e.target.value }),
                placeholder: "name@company.com",
                className: cn(inputCls, isRTL ? "text-right" : "text-left"),
                dir: "ltr"
              }
            )
          ] }),
          /* @__PURE__ */ jsxs("div", { children: [
            /* @__PURE__ */ jsxs("label", { className: "mb-1.5 block text-sm text-foreground/85", children: [
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
          /* @__PURE__ */ jsx(Button, { type: "submit", size: "lg", disabled: isSubmitting, className: "w-full", children: isSubmitting ? /* @__PURE__ */ jsxs(Fragment, { children: [
            /* @__PURE__ */ jsx("span", { className: "h-5 w-5 animate-spin rounded-full border-2 border-primary-foreground/30 border-t-primary-foreground" }),
            t("\u062C\u0627\u0631\u064A \u0627\u0644\u0645\u0639\u0627\u0644\u062C\u0629...", "Processing...")
          ] }) : /* @__PURE__ */ jsxs(Fragment, { children: [
            /* @__PURE__ */ jsx(Download, { strokeWidth: 1.75 }),
            t("\u062A\u062D\u0645\u064A\u0644 \u0627\u0644\u0622\u0646", "Download now")
          ] }) }),
          /* @__PURE__ */ jsxs("p", { className: "flex items-center justify-center gap-1.5 text-center text-xs text-muted-foreground", children: [
            /* @__PURE__ */ jsx(Lock, { className: "h-3.5 w-3.5", strokeWidth: 1.75 }),
            t("\u0628\u064A\u0627\u0646\u0627\u062A\u0643 \u0622\u0645\u0646\u0629 \u0648\u0644\u0646 \u062A\u064F\u0634\u0627\u0631\u0643 \u0645\u0639 \u0623\u064A \u0637\u0631\u0641.", "Your data is safe and never shared.")
          ] })
        ] })
      ]
    }
  ) });
};
export default TemplateDownloadModal;
