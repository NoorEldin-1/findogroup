import { Fragment, jsx, jsxs } from "react/jsx-runtime";
import { blockedExternalRequest } from "@/adapters/local-api";
import { useState } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { User, Phone, Calendar, Building2, Send, Smartphone, Globe, Cloud, LayoutDashboard, ShoppingCart, Mail, Briefcase, CheckCircle, Video, MessageSquare } from "lucide-react";
import { toast } from "@/hooks/use-toast";
import { z } from "zod";
import { supabase } from "@/integrations/supabase/client";
import { useLanguage } from "@/contexts/LanguageContext";
import { sendLeadEmail, sendToWPForms } from "@/lib/emailService";
import { trackLeadConversion } from "@/lib/tracking";
const getFormSchema = (t) => z.object({
  name: z.string().trim().min(2, t("\u0627\u0644\u0627\u0633\u0645 \u064A\u062C\u0628 \u0623\u0646 \u064A\u0643\u0648\u0646 \u062D\u0631\u0641\u064A\u0646 \u0639\u0644\u0649 \u0627\u0644\u0623\u0642\u0644", "Name must be at least 2 characters")).max(100),
  email: z.string().trim().email(t("\u0627\u0644\u0628\u0631\u064A\u062F \u0627\u0644\u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A \u063A\u064A\u0631 \u0635\u062D\u064A\u062D", "Invalid email address")).optional().or(z.literal("")),
  phone: z.string().trim().min(10, t("\u0631\u0642\u0645 \u0627\u0644\u0647\u0627\u062A\u0641 \u063A\u064A\u0631 \u0635\u062D\u064A\u062D", "Invalid phone number")).max(15),
  company: z.string().optional(),
  projectType: z.string().min(1, t("\u064A\u0631\u062C\u0649 \u0627\u062E\u062A\u064A\u0627\u0631 \u0646\u0648\u0639 \u0627\u0644\u0645\u0634\u0631\u0648\u0639", "Please select project type")),
  services: z.array(z.string()).min(1, t("\u064A\u0631\u062C\u0649 \u0627\u062E\u062A\u064A\u0627\u0631 \u062E\u062F\u0645\u0629 \u0648\u0627\u062D\u062F\u0629 \u0639\u0644\u0649 \u0627\u0644\u0623\u0642\u0644", "Please select at least one service")),
  description: z.string().optional(),
  preferMeeting: z.boolean().optional(),
  timeline: z.string().min(1, t("\u064A\u0631\u062C\u0649 \u0627\u062E\u062A\u064A\u0627\u0631 \u0645\u0648\u0639\u062F \u0627\u0644\u062A\u0633\u0644\u064A\u0645", "Please select delivery time"))
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
const getServiceOptions = (t) => [
  { id: "erp", label: t("\u0623\u0646\u0638\u0645\u0629 ERP", "ERP Systems"), icon: LayoutDashboard },
  { id: "security", label: t("\u0627\u0644\u0623\u0645\u0627\u0646 \u0648\u0627\u0644\u0627\u0633\u062A\u0634\u0627\u0631\u0627\u062A", "Security & Consulting"), icon: Briefcase },
  { id: "website", label: t("\u0645\u0648\u0627\u0642\u0639 \u0648\u0645\u062A\u0627\u062C\u0631", "Websites & Stores"), icon: Globe },
  { id: "mobile", label: t("\u062A\u0637\u0628\u064A\u0642\u0627\u062A \u0645\u0648\u0628\u0627\u064A\u0644", "Mobile Apps"), icon: Smartphone },
  { id: "ux", label: t("\u062A\u062C\u0631\u0628\u0629 \u0627\u0644\u0645\u0633\u062A\u062E\u062F\u0645", "User Experience"), icon: Cloud },
  { id: "support", label: t("\u0627\u0644\u062F\u0639\u0645 \u0627\u0644\u0641\u0646\u064A", "Technical Support"), icon: MessageSquare },
  { id: "marketing", label: t("\u0627\u0644\u062A\u0633\u0648\u064A\u0642", "Marketing"), icon: ShoppingCart },
  { id: "social", label: t("\u0627\u0644\u0633\u0648\u0634\u064A\u0627\u0644 \u0645\u064A\u062F\u064A\u0627", "Social Media"), icon: Globe },
  { id: "design", label: t("\u0627\u0644\u062A\u0635\u0645\u064A\u0645 \u0627\u0644\u062C\u0631\u0627\u0641\u064A\u0643\u064A", "Graphic Design"), icon: Cloud }
];
const dsInput = "w-full rounded-[15px] border bg-background px-5 text-foreground placeholder:text-muted-foreground transition-all duration-200 ease-ds focus:outline-none focus:border-primary focus:shadow-[0_0_0_3px_rgba(253,128,46,0.18)]";
const dsSubmit = "w-full flex items-center justify-center gap-3 rounded-[17px] px-8 py-4 font-bold text-primary-foreground [background:var(--gradient-coral)] shadow-btn-primary transition-all duration-200 ease-ds hover:shadow-glow-coral-lg";
const ServiceRequestForm = ({ source = "services-page", defaultServices = [] } = {}) => {
  const { t, isRTL } = useLanguage();
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    countryCode: "+20",
    phone: "",
    company: "",
    projectType: "",
    services: defaultServices,
    description: "",
    preferMeeting: false,
    timeline: ""
  });
  const [showCountryDropdown, setShowCountryDropdown] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errors, setErrors] = useState({});
  const [isSubmitted, setIsSubmitted] = useState(false);
  const formSchema = getFormSchema(t);
  const countryCodes = getCountryCodes(t);
  const serviceOptions = getServiceOptions(t);
  const toggleService = (serviceId) => {
    setFormData((prev) => ({
      ...prev,
      services: prev.services.includes(serviceId) ? prev.services.filter((s) => s !== serviceId) : [...prev.services, serviceId]
    }));
    setErrors((prev) => ({ ...prev, services: "" }));
  };
  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    const result = formSchema.safeParse(formData);
    if (!result.success) {
      const fieldErrors = {};
      result.error.errors.forEach((err) => {
        if (err.path[0]) {
          fieldErrors[err.path[0]] = err.message;
        }
      });
      setErrors(fieldErrors);
      toast({
        title: t("\u064A\u0631\u062C\u0649 \u062A\u0635\u062D\u064A\u062D \u0627\u0644\u0623\u062E\u0637\u0627\u0621", "Please fix the errors"),
        description: t("\u062A\u0623\u0643\u062F \u0645\u0646 \u0645\u0644\u0621 \u062C\u0645\u064A\u0639 \u0627\u0644\u062D\u0642\u0648\u0644 \u0627\u0644\u0645\u0637\u0644\u0648\u0628\u0629 \u0628\u0634\u0643\u0644 \u0635\u062D\u064A\u062D", "Make sure all required fields are filled correctly"),
        variant: "destructive"
      });
      return;
    }
    setIsSubmitting(true);
    try {
      const servicesLabels = formData.services.map((s) => serviceOptions.find((o) => o.id === s)?.label || s);
      const leadData = {
        name: formData.name,
        email: formData.email || t("\u063A\u064A\u0631 \u0645\u062D\u062F\u062F", "Not specified"),
        phone: `${formData.countryCode}${formData.phone}`,
        company: formData.company || null,
        project_type: formData.projectType === "new" ? t("\u0645\u0634\u0631\u0648\u0639 \u062C\u062F\u064A\u062F", "New Project") : t("\u0645\u0634\u0631\u0648\u0639 \u0642\u0627\u0626\u0645", "Existing Project"),
        project_age: null,
        services: servicesLabels,
        description: formData.preferMeeting ? t("\u0623\u0641\u0636\u0644 \u0625\u062E\u0628\u0627\u0631\u0643\u0645 \u0628\u0627\u0644\u062A\u0641\u0627\u0635\u064A\u0644 \u0641\u064A \u0627\u062C\u062A\u0645\u0627\u0639", "I prefer to share details in a meeting") : formData.description || null,
        budget: null,
        timeline: formData.timeline,
        source
      };
      let savedToDatabase = false;
      try {
        const { error: dbError } = await supabase.from("leads").insert({
          ...leadData,
          services: servicesLabels
        });
        savedToDatabase = !dbError;
      } catch (dbError) {
        console.log("Supabase not available, continuing with email...");
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
          body: JSON.stringify({
            ...leadData,
            services: servicesLabels.join(", "),
            submitted_at: (/* @__PURE__ */ new Date()).toISOString()
          })
        });
      } catch {
      }
      if (!savedToDatabase && !emailSent) {
        throw new Error(t("\u0641\u0634\u0644 \u0641\u064A \u0625\u0631\u0633\u0627\u0644 \u0627\u0644\u0637\u0644\u0628", "Failed to send request"));
      }
      trackLeadConversion(source);
      setIsSubmitted(true);
      setFormData({
        name: "",
        email: "",
        countryCode: "+20",
        phone: "",
        company: "",
        projectType: "",
        services: [],
        description: "",
        preferMeeting: false,
        timeline: ""
      });
    } catch (error) {
      console.error("Form submission error:", error);
      toast({
        title: t("\u062D\u062F\u062B \u062E\u0637\u0623", "An error occurred"),
        description: t("\u064A\u0631\u062C\u0649 \u0627\u0644\u0645\u062D\u0627\u0648\u0644\u0629 \u0645\u0631\u0629 \u0623\u062E\u0631\u0649 \u0623\u0648 \u0627\u0644\u062A\u0648\u0627\u0635\u0644 \u0645\u0639\u0646\u0627 \u0639\u0628\u0631 \u0627\u0644\u0648\u0627\u062A\u0633\u0627\u0628", "Please try again or contact us via WhatsApp"),
        variant: "destructive"
      });
    } finally {
      setIsSubmitting(false);
    }
  };
  const whatsappNumber = "201100018259";
  const whatsappMessage = encodeURIComponent(t("\u0645\u0631\u062D\u0628\u0627\u064B\u060C \u0623\u0631\u063A\u0628 \u0641\u064A \u0627\u0644\u0627\u0633\u062A\u0641\u0633\u0627\u0631 \u0639\u0646 \u062E\u062F\u0645\u0627\u062A\u0643\u0645", "Hello, I would like to inquire about your services"));
  const whatsappLink = `https://wa.me/${whatsappNumber}?text=${whatsappMessage}`;
  if (isSubmitted) {
    return /* @__PURE__ */ jsx(
      motion.div,
      {
        initial: { opacity: 0, scale: 0.9 },
        animate: { opacity: 1, scale: 1 },
        transition: { duration: 0.5 },
        className: "max-w-2xl mx-auto",
        children: /* @__PURE__ */ jsxs("div", { className: "rounded-3xl border border-border bg-popover shadow-board p-8 md:p-12 text-center", children: [
          /* @__PURE__ */ jsx(
            motion.div,
            {
              initial: { scale: 0 },
              animate: { scale: 1 },
              transition: { delay: 0.2, type: "spring", stiffness: 200 },
              className: "inline-flex p-4 md:p-6 rounded-full bg-success/15 border border-success/30 mb-6",
              children: /* @__PURE__ */ jsx(CheckCircle, { className: "w-12 h-12 md:w-16 md:h-16 text-success" })
            }
          ),
          /* @__PURE__ */ jsx("h2", { className: "text-2xl sm:text-3xl md:text-4xl font-bold text-foreground mb-4", children: t("\u0634\u0643\u0631\u0627\u064B \u0644\u0643! \u{1F389}", "Thank You! \u{1F389}") }),
          /* @__PURE__ */ jsx("p", { className: "text-muted-foreground text-base md:text-lg mb-6 leading-relaxed", children: t("\u062A\u0645 \u0627\u0633\u062A\u0644\u0627\u0645 \u0637\u0644\u0628\u0643 \u0628\u0646\u062C\u0627\u062D. \u0633\u064A\u062A\u0648\u0627\u0635\u0644 \u0645\u0639\u0643 \u0623\u062D\u062F \u0645\u0633\u062A\u0634\u0627\u0631\u064A\u0646\u0627 \u062E\u0644\u0627\u0644 24 \u0633\u0627\u0639\u0629.", "Your request has been received successfully. One of our consultants will contact you within 24 hours.") }),
          /* @__PURE__ */ jsxs("div", { className: "bg-card/60 border border-border rounded-2xl p-4 md:p-6 mb-6", children: [
            /* @__PURE__ */ jsx("p", { className: "text-muted-foreground text-sm md:text-base mb-4", children: t("\u064A\u0645\u0643\u0646\u0643 \u0627\u0644\u062A\u0648\u0627\u0635\u0644 \u0645\u0639\u0646\u0627 \u0623\u0633\u0631\u0639 \u0639\u0646 \u0637\u0631\u064A\u0642 \u0627\u0644\u0648\u0627\u062A\u0633\u0627\u0628", "You can reach us faster via WhatsApp") }),
            /* @__PURE__ */ jsxs(
              "a",
              {
                href: whatsappLink,
                target: "_blank",
                rel: "noopener noreferrer",
                className: "inline-flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white font-bold px-6 py-3 rounded-xl transition-all",
                children: [
                  /* @__PURE__ */ jsx("svg", { viewBox: "0 0 24 24", fill: "currentColor", className: "w-5 h-5", children: /* @__PURE__ */ jsx("path", { d: "M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" }) }),
                  t("\u062A\u0648\u0627\u0635\u0644 \u0639\u0628\u0631 \u0648\u0627\u062A\u0633\u0627\u0628", "Contact via WhatsApp")
                ]
              }
            )
          ] }),
          /* @__PURE__ */ jsx(
            "button",
            {
              onClick: () => setIsSubmitted(false),
              className: "text-primary hover:text-primary/80 text-sm font-medium transition-colors",
              children: t("\u0625\u0631\u0633\u0627\u0644 \u0637\u0644\u0628 \u0622\u062E\u0631", "Send Another Request")
            }
          )
        ] })
      }
    );
  }
  return /* @__PURE__ */ jsx("div", { className: "max-w-4xl mx-auto", children: /* @__PURE__ */ jsxs("div", { className: "rounded-3xl border border-border bg-popover shadow-board p-5 sm:p-6 md:p-8 lg:p-12", children: [
    /* @__PURE__ */ jsxs("div", { className: "text-center mb-6 md:mb-10", children: [
      /* @__PURE__ */ jsx(
        motion.div,
        {
          initial: { scale: 0 },
          whileInView: { scale: 1 },
          viewport: { once: true },
          className: "inline-flex p-3 md:p-4 rounded-xl md:rounded-2xl bg-primary/10 border border-primary/20 mb-4 md:mb-6",
          children: /* @__PURE__ */ jsx(Send, { className: "w-6 h-6 md:w-8 md:h-8 text-primary" })
        }
      ),
      /* @__PURE__ */ jsxs("h2", { className: "text-2xl sm:text-3xl md:text-4xl font-bold text-foreground mb-2 md:mb-3", children: [
        t("\u0627\u0637\u0644\u0628", "Request"),
        " ",
        /* @__PURE__ */ jsx("span", { className: "text-gradient-coral", children: t("\u062E\u062F\u0645\u062A\u0643 \u0627\u0644\u0622\u0646", "Your Service Now") })
      ] }),
      /* @__PURE__ */ jsx("p", { className: "text-muted-foreground text-sm md:text-base px-2", children: t("\u0627\u062A\u0631\u0643 \u062A\u0641\u0627\u0635\u064A\u0644 \u0645\u0634\u0631\u0648\u0639\u0643 \u0648\u0633\u0646\u062A\u0648\u0627\u0635\u0644 \u0645\u0639\u0643 \u062E\u0644\u0627\u0644 24 \u0633\u0627\u0639\u0629", "Leave your project details and we will contact you within 24 hours") })
    ] }),
    /* @__PURE__ */ jsxs("form", { onSubmit: handleSubmit, className: "space-y-5 md:space-y-6", children: [
      /* @__PURE__ */ jsxs("div", { className: "grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-6", children: [
        /* @__PURE__ */ jsxs("div", { className: "relative", children: [
          /* @__PURE__ */ jsx(User, { className: `absolute ${isRTL ? "right-3 md:right-4" : "left-3 md:left-4"} top-1/2 -translate-y-1/2 w-4 h-4 md:w-5 md:h-5 text-muted-foreground` }),
          /* @__PURE__ */ jsx(
            "input",
            {
              type: "text",
              placeholder: t("\u0627\u0644\u0627\u0633\u0645 \u0627\u0644\u0643\u0631\u064A\u0645 *", "Full Name *"),
              required: true,
              value: formData.name,
              onChange: (e) => {
                setFormData({ ...formData, name: e.target.value });
                setErrors((prev) => ({ ...prev, name: "" }));
              },
              className: `${dsInput} ${isRTL ? "pr-10 md:pr-12" : "pl-10 md:pl-12"} text-sm md:text-base py-3 md:py-4 ${errors.name ? "border-destructive" : "border-input"}`
            }
          ),
          errors.name && /* @__PURE__ */ jsx("span", { className: "text-destructive text-xs mt-1 block", children: errors.name })
        ] }),
        /* @__PURE__ */ jsxs("div", { className: "relative", children: [
          /* @__PURE__ */ jsx(Mail, { className: `absolute ${isRTL ? "right-3 md:right-4" : "left-3 md:left-4"} top-1/2 -translate-y-1/2 w-4 h-4 md:w-5 md:h-5 text-muted-foreground` }),
          /* @__PURE__ */ jsx(
            "input",
            {
              type: "email",
              placeholder: t("\u0627\u0644\u0628\u0631\u064A\u062F \u0627\u0644\u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A (\u0627\u062E\u062A\u064A\u0627\u0631\u064A)", "Email (Optional)"),
              value: formData.email,
              onChange: (e) => {
                setFormData({ ...formData, email: e.target.value });
                setErrors((prev) => ({ ...prev, email: "" }));
              },
              className: `${dsInput} ${isRTL ? "pr-10 md:pr-12" : "pl-10 md:pl-12"} text-sm md:text-base py-3 md:py-4 ${errors.email ? "border-destructive" : "border-input"}`
            }
          ),
          errors.email && /* @__PURE__ */ jsx("span", { className: "text-destructive text-xs mt-1 block", children: errors.email })
        ] })
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-6", children: [
        /* @__PURE__ */ jsxs("div", { children: [
          /* @__PURE__ */ jsxs("div", { className: "flex gap-2", children: [
            /* @__PURE__ */ jsxs("div", { className: "relative", children: [
              /* @__PURE__ */ jsxs(
                "button",
                {
                  type: "button",
                  onClick: () => setShowCountryDropdown(!showCountryDropdown),
                  className: `${dsInput} border-input flex items-center gap-2 px-3 py-3 md:py-4 text-sm md:text-base min-w-[110px] justify-center hover:border-primary/50`,
                  children: [
                    /* @__PURE__ */ jsx("span", { className: "text-xl", children: countryCodes.find((c) => c.code === formData.countryCode)?.flag }),
                    /* @__PURE__ */ jsx("span", { className: "text-foreground font-medium", dir: "ltr", children: formData.countryCode })
                  ]
                }
              ),
              showCountryDropdown && /* @__PURE__ */ jsxs(Fragment, { children: [
                /* @__PURE__ */ jsx("div", { className: "fixed inset-0 z-40", onClick: () => setShowCountryDropdown(false) }),
                /* @__PURE__ */ jsx("div", { className: `absolute top-full mt-2 ${isRTL ? "right-0" : "left-0"} bg-card border border-border rounded-2xl shadow-board z-50 w-56 overflow-hidden max-h-64 overflow-y-auto`, children: countryCodes.map((country) => /* @__PURE__ */ jsxs(
                  "button",
                  {
                    type: "button",
                    onClick: () => {
                      setFormData({ ...formData, countryCode: country.code });
                      setShowCountryDropdown(false);
                    },
                    className: `w-full flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-muted/70 transition-colors ${formData.countryCode === country.code ? "bg-primary/15 text-primary" : "text-foreground"}`,
                    children: [
                      /* @__PURE__ */ jsx("span", { className: "text-xl", children: country.flag }),
                      /* @__PURE__ */ jsx("span", { className: `flex-1 ${isRTL ? "text-right" : "text-left"}`, children: country.name }),
                      /* @__PURE__ */ jsx("span", { className: "text-muted-foreground text-xs", dir: "ltr", children: country.code })
                    ]
                  },
                  country.code
                )) })
              ] })
            ] }),
            /* @__PURE__ */ jsxs("div", { className: "relative flex-1", children: [
              /* @__PURE__ */ jsx(Phone, { className: `absolute ${isRTL ? "right-3 md:right-4" : "left-3 md:left-4"} top-1/2 -translate-y-1/2 w-4 h-4 md:w-5 md:h-5 text-muted-foreground` }),
              /* @__PURE__ */ jsx(
                "input",
                {
                  type: "tel",
                  placeholder: t("\u0631\u0642\u0645 \u0627\u0644\u062C\u0648\u0627\u0644 *", "Phone Number *"),
                  required: true,
                  dir: "ltr",
                  value: formData.phone,
                  onChange: (e) => {
                    const value = e.target.value.replace(/[^0-9]/g, "");
                    setFormData({ ...formData, phone: value });
                    setErrors((prev) => ({ ...prev, phone: "" }));
                  },
                  className: `${dsInput} ${isRTL ? "pr-10 md:pr-12" : "pl-10 md:pl-12"} text-sm md:text-base py-3 md:py-4 ${isRTL ? "text-right" : "text-left"} ${errors.phone ? "border-destructive" : "border-input"}`
                }
              )
            ] })
          ] }),
          errors.phone && /* @__PURE__ */ jsx("span", { className: "text-destructive text-xs mt-1 block", children: errors.phone })
        ] }),
        /* @__PURE__ */ jsxs("div", { className: "relative", children: [
          /* @__PURE__ */ jsx(Building2, { className: `absolute ${isRTL ? "right-3 md:right-4" : "left-3 md:left-4"} top-1/2 -translate-y-1/2 w-4 h-4 md:w-5 md:h-5 text-muted-foreground` }),
          /* @__PURE__ */ jsx(
            "input",
            {
              type: "text",
              placeholder: t("\u0627\u0633\u0645 \u0627\u0644\u0634\u0631\u0643\u0629 / \u0627\u0644\u0645\u0624\u0633\u0633\u0629 (\u0627\u062E\u062A\u064A\u0627\u0631\u064A)", "Company Name (Optional)"),
              value: formData.company,
              onChange: (e) => setFormData({ ...formData, company: e.target.value }),
              className: `${dsInput} border-input ${isRTL ? "pr-10 md:pr-12" : "pl-10 md:pl-12"} text-sm md:text-base py-3 md:py-4`
            }
          )
        ] })
      ] }),
      /* @__PURE__ */ jsxs("div", { children: [
        /* @__PURE__ */ jsx("label", { className: "block text-foreground/90 text-sm md:text-base font-medium mb-3", children: t("\u0646\u0648\u0639 \u0627\u0644\u0645\u0634\u0631\u0648\u0639 *", "Project Type *") }),
        /* @__PURE__ */ jsxs("div", { className: "grid grid-cols-2 gap-3 md:gap-4", children: [
          /* @__PURE__ */ jsxs(
            "button",
            {
              type: "button",
              onClick: () => {
                setFormData({ ...formData, projectType: "new" });
                setErrors((prev) => ({ ...prev, projectType: "" }));
              },
              className: `p-3 md:p-4 rounded-2xl border transition-all duration-200 ease-ds text-sm md:text-base ${formData.projectType === "new" ? "bg-primary/15 border-primary text-primary" : "bg-card/60 border-border text-muted-foreground hover:border-primary/40 hover:text-foreground"}`,
              children: [
                /* @__PURE__ */ jsx(Briefcase, { className: "w-5 h-5 md:w-6 md:h-6 mx-auto mb-2" }),
                t("\u0645\u0634\u0631\u0648\u0639 \u062C\u062F\u064A\u062F", "New Project")
              ]
            }
          ),
          /* @__PURE__ */ jsxs(
            "button",
            {
              type: "button",
              onClick: () => {
                setFormData({ ...formData, projectType: "existing" });
                setErrors((prev) => ({ ...prev, projectType: "" }));
              },
              className: `p-3 md:p-4 rounded-2xl border transition-all duration-200 ease-ds text-sm md:text-base ${formData.projectType === "existing" ? "bg-primary/15 border-primary text-primary" : "bg-card/60 border-border text-muted-foreground hover:border-primary/40 hover:text-foreground"}`,
              children: [
                /* @__PURE__ */ jsx(Building2, { className: "w-5 h-5 md:w-6 md:h-6 mx-auto mb-2" }),
                t("\u0645\u0634\u0631\u0648\u0639 \u0642\u0627\u0626\u0645", "Existing Project")
              ]
            }
          )
        ] }),
        errors.projectType && /* @__PURE__ */ jsx("span", { className: "text-destructive text-xs mt-2 block", children: errors.projectType })
      ] }),
      /* @__PURE__ */ jsxs("div", { children: [
        /* @__PURE__ */ jsx("label", { className: "block text-foreground/90 text-sm md:text-base font-medium mb-3", children: t("\u0627\u0644\u062E\u062F\u0645\u0627\u062A \u0627\u0644\u0645\u0637\u0644\u0648\u0628\u0629 * (\u0627\u062E\u062A\u0631 \u0648\u0627\u062D\u062F\u0629 \u0623\u0648 \u0623\u0643\u062B\u0631)", "Required Services * (Select one or more)") }),
        /* @__PURE__ */ jsx("div", { className: "flex flex-wrap gap-2 md:gap-3", children: serviceOptions.map((service) => /* @__PURE__ */ jsxs(
          "button",
          {
            type: "button",
            onClick: () => toggleService(service.id),
            className: `flex items-center gap-2 rounded-full border px-3 md:px-4 py-2 md:py-2.5 font-semibold cursor-pointer transition-all duration-200 ease-ds ${formData.services.includes(service.id) ? "bg-primary/15 border-primary text-primary" : "bg-popover border-border text-muted-foreground hover:border-primary/60 hover:text-primary"}`,
            children: [
              /* @__PURE__ */ jsx(service.icon, { className: "w-3.5 h-3.5 md:w-4 md:h-4" }),
              /* @__PURE__ */ jsx("span", { className: "text-xs md:text-sm", children: service.label })
            ]
          },
          service.id
        )) }),
        errors.services && /* @__PURE__ */ jsx("span", { className: "text-destructive text-xs mt-2 block", children: errors.services })
      ] }),
      /* @__PURE__ */ jsxs("div", { children: [
        /* @__PURE__ */ jsxs("div", { className: "flex items-center justify-between mb-3", children: [
          /* @__PURE__ */ jsx("label", { className: "text-foreground/90 text-sm md:text-base font-medium", children: t("\u0648\u0635\u0641 \u0627\u0644\u0645\u0634\u0631\u0648\u0639", "Project Description") }),
          /* @__PURE__ */ jsxs(
            "button",
            {
              type: "button",
              onClick: () => setFormData({ ...formData, preferMeeting: !formData.preferMeeting }),
              className: `flex items-center gap-2 text-xs md:text-sm transition-colors ${formData.preferMeeting ? "text-primary" : "text-muted-foreground hover:text-foreground/80"}`,
              children: [
                /* @__PURE__ */ jsx(Video, { className: "w-3.5 h-3.5 md:w-4 md:h-4" }),
                t("\u0623\u0641\u0636\u0644 \u0625\u062E\u0628\u0627\u0631\u0643\u0645 \u0641\u064A \u0627\u062C\u062A\u0645\u0627\u0639", "I prefer to discuss in a meeting")
              ]
            }
          )
        ] }),
        /* @__PURE__ */ jsx(AnimatePresence, { mode: "wait", children: !formData.preferMeeting ? /* @__PURE__ */ jsx(
          motion.div,
          {
            initial: { opacity: 0, height: 0 },
            animate: { opacity: 1, height: "auto" },
            exit: { opacity: 0, height: 0 },
            children: /* @__PURE__ */ jsx(
              "textarea",
              {
                placeholder: t("\u0627\u0643\u062A\u0628 \u062A\u0641\u0627\u0635\u064A\u0644 \u0645\u0634\u0631\u0648\u0639\u0643 \u0647\u0646\u0627...", "Write your project details here..."),
                rows: 4,
                value: formData.description,
                onChange: (e) => setFormData({ ...formData, description: e.target.value }),
                className: `${dsInput} border-input py-4 resize-none text-sm md:text-base`
              }
            )
          },
          "textarea"
        ) : /* @__PURE__ */ jsxs(
          motion.div,
          {
            initial: { opacity: 0, height: 0 },
            animate: { opacity: 1, height: "auto" },
            exit: { opacity: 0, height: 0 },
            className: "bg-primary/10 border border-primary/20 rounded-2xl p-4 text-center",
            children: [
              /* @__PURE__ */ jsx(Video, { className: "w-8 h-8 text-primary mx-auto mb-2" }),
              /* @__PURE__ */ jsx("p", { className: "text-foreground/85 text-sm", children: t("\u0633\u0646\u062A\u0648\u0627\u0635\u0644 \u0645\u0639\u0643 \u0644\u062A\u062D\u062F\u064A\u062F \u0645\u0648\u0639\u062F \u0627\u062C\u062A\u0645\u0627\u0639 \u0644\u0645\u0646\u0627\u0642\u0634\u0629 \u062A\u0641\u0627\u0635\u064A\u0644 \u0627\u0644\u0645\u0634\u0631\u0648\u0639", "We will contact you to schedule a meeting to discuss project details") })
            ]
          },
          "meeting-notice"
        ) })
      ] }),
      /* @__PURE__ */ jsxs("div", { children: [
        /* @__PURE__ */ jsxs("label", { className: "block text-foreground/90 text-sm md:text-base font-medium mb-3", children: [
          /* @__PURE__ */ jsx(Calendar, { className: `inline w-4 h-4 ${isRTL ? "ml-2" : "mr-2"}` }),
          t("\u0645\u062A\u0649 \u062A\u062D\u062A\u0627\u062C \u0627\u0644\u0645\u0634\u0631\u0648\u0639\u061F *", "When do you need the project? *")
        ] }),
        /* @__PURE__ */ jsx("div", { className: "grid grid-cols-2 sm:grid-cols-4 gap-2 md:gap-3", children: [
          { ar: "\u0641\u0648\u0631\u064A", en: "Urgent" },
          { ar: "\u062E\u0644\u0627\u0644 \u0623\u0633\u0628\u0648\u0639", en: "Within a Week" },
          { ar: "\u062E\u0644\u0627\u0644 \u0634\u0647\u0631", en: "Within a Month" },
          { ar: "\u0645\u0631\u0646", en: "Flexible" }
        ].map((option) => /* @__PURE__ */ jsx(
          "button",
          {
            type: "button",
            onClick: () => {
              setFormData({ ...formData, timeline: option.ar });
              setErrors((prev) => ({ ...prev, timeline: "" }));
            },
            className: `p-2 md:p-3 rounded-xl border text-xs md:text-sm transition-all duration-200 ease-ds ${formData.timeline === option.ar ? "bg-primary/15 border-primary text-primary" : "bg-card/60 border-border text-muted-foreground hover:border-primary/40 hover:text-foreground"}`,
            children: t(option.ar, option.en)
          },
          option.ar
        )) }),
        errors.timeline && /* @__PURE__ */ jsx("span", { className: "text-destructive text-xs mt-2 block", children: errors.timeline })
      ] }),
      /* @__PURE__ */ jsx(
        motion.button,
        {
          type: "submit",
          disabled: isSubmitting,
          whileHover: { scale: 1.02 },
          whileTap: { scale: 0.98 },
          className: `${dsSubmit} text-base md:text-lg disabled:opacity-50 disabled:cursor-not-allowed`,
          children: isSubmitting ? /* @__PURE__ */ jsxs(Fragment, { children: [
            /* @__PURE__ */ jsx("div", { className: "w-5 h-5 border-2 border-primary-foreground/30 border-t-primary-foreground rounded-full animate-spin" }),
            t("\u062C\u0627\u0631\u064A \u0627\u0644\u0625\u0631\u0633\u0627\u0644...", "Sending...")
          ] }) : /* @__PURE__ */ jsxs(Fragment, { children: [
            /* @__PURE__ */ jsx(Send, { className: "w-5 h-5" }),
            t("\u0623\u0631\u0633\u0644 \u0637\u0644\u0628\u0643 \u0627\u0644\u0622\u0646", "Send Your Request Now")
          ] })
        }
      )
    ] })
  ] }) });
};
export default ServiceRequestForm;
