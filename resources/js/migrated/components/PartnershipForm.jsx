import { Fragment, jsx, jsxs } from "react/jsx-runtime";
import { blockedExternalRequest } from "@/adapters/local-api";
import { useState } from "react";
import { motion } from "framer-motion";
import {
  User,
  Phone,
  Mail,
  Building2,
  Globe,
  Send,
  CheckCircle,
  Handshake,
  Megaphone,
  Store,
  Rocket,
  Briefcase,
  Users,
  Monitor,
  Smartphone,
  LayoutDashboard,
  CalendarCheck,
  ShoppingCart,
  Palette
} from "lucide-react";
import WhatsappIcon from "@/components/v2/primitives/WhatsappIcon";
import { toast } from "@/hooks/use-toast";
import { z } from "zod";
import { supabase } from "@/integrations/supabase/client";
import { useLanguage } from "@/contexts/LanguageContext";
import { trackLeadConversion } from "@/lib/tracking";
import { sendLeadEmail, sendToWPForms } from "@/lib/emailService";
const getFormSchema = (t) => z.object({
  name: z.string().trim().min(2, t("\u0627\u0644\u0627\u0633\u0645 \u064A\u062C\u0628 \u0623\u0646 \u064A\u0643\u0648\u0646 \u062D\u0631\u0641\u064A\u0646 \u0639\u0644\u0649 \u0627\u0644\u0623\u0642\u0644", "Name must be at least 2 characters")).max(100),
  company: z.string().trim().min(2, t("\u064A\u0631\u062C\u0649 \u0625\u062F\u062E\u0627\u0644 \u0627\u0633\u0645 \u0627\u0644\u0634\u0631\u0643\u0629 / \u0627\u0644\u062C\u0647\u0629", "Please enter your company name")).max(120),
  email: z.string().trim().email(t("\u0627\u0644\u0628\u0631\u064A\u062F \u0627\u0644\u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A \u063A\u064A\u0631 \u0635\u062D\u064A\u062D", "Invalid email address")).optional().or(z.literal("")),
  phone: z.string().trim().min(8, t("\u0631\u0642\u0645 \u0627\u0644\u0647\u0627\u062A\u0641 \u063A\u064A\u0631 \u0635\u062D\u064A\u062D", "Invalid phone number")).max(15),
  partnerType: z.string().min(1, t("\u064A\u0631\u062C\u0649 \u0627\u062E\u062A\u064A\u0627\u0631 \u0646\u0648\u0639 \u0627\u0644\u062C\u0647\u0629", "Please select your entity type")),
  interests: z.array(z.string()).min(1, t("\u0627\u062E\u062A\u0631 \u0645\u062C\u0627\u0644\u0627\u064B \u0648\u0627\u062D\u062F\u0627\u064B \u0639\u0644\u0649 \u0627\u0644\u0623\u0642\u0644", "Select at least one area")),
  website: z.string().optional(),
  message: z.string().optional()
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
const getPartnerTypes = (t) => [
  { id: "agency", label: t("\u0648\u0643\u0627\u0644\u0629 \u062A\u0633\u0648\u064A\u0642", "Marketing agency"), icon: Megaphone },
  { id: "company", label: t("\u0634\u0631\u0643\u0629 / \u0628\u064A\u062A \u062E\u0628\u0631\u0629", "Company / consultancy"), icon: Building2 },
  { id: "business", label: t("\u0635\u0627\u062D\u0628 \u0645\u062A\u062C\u0631 \u0648\u0623\u0639\u0645\u0627\u0644", "Store / business owner"), icon: Store },
  { id: "startup", label: t("\u0633\u062A\u0627\u0631\u062A \u0623\u0628", "Startup"), icon: Rocket },
  { id: "provider", label: t("\u0645\u0632\u0648\u0651\u062F \u062E\u062F\u0645\u0627\u062A", "Service provider"), icon: Briefcase },
  { id: "freelancer", label: t("\u0645\u0633\u062A\u0642\u0644 / \u0645\u062D\u062A\u0631\u0641", "Freelancer / professional"), icon: Users }
];
const getInterests = (t) => [
  { id: "web", label: t("\u0645\u0648\u0627\u0642\u0639 \u0648\u0645\u062A\u0627\u062C\u0631", "Websites & stores"), icon: Monitor },
  { id: "mobile", label: t("\u062A\u0637\u0628\u064A\u0642\u0627\u062A \u0645\u0648\u0628\u0627\u064A\u0644", "Mobile apps"), icon: Smartphone },
  { id: "erp", label: t("\u0623\u0646\u0638\u0645\u0629 ERP", "ERP systems"), icon: LayoutDashboard },
  { id: "booking", label: t("\u0623\u0646\u0638\u0645\u0629 \u062D\u062C\u0648\u0632\u0627\u062A", "Booking systems"), icon: CalendarCheck },
  { id: "pos", label: t("\u0646\u0642\u0627\u0637 \u0628\u064A\u0639", "POS"), icon: ShoppingCart },
  { id: "design", label: t("\u062A\u0635\u0645\u064A\u0645 \u0648\u0647\u0648\u064A\u0629", "Design & branding"), icon: Palette }
];
const dsInput = "w-full rounded-[15px] border bg-background px-5 text-foreground placeholder:text-muted-foreground transition-all duration-200 ease-ds focus:outline-none focus:border-primary focus:shadow-[0_0_0_3px_rgba(253,128,46,0.18)]";
const dsSubmit = "w-full flex items-center justify-center gap-3 rounded-[17px] px-8 py-4 font-bold text-primary-foreground [background:var(--gradient-coral)] shadow-btn-primary transition-all duration-200 ease-ds hover:shadow-glow-coral-lg";
const PartnershipForm = () => {
  const { t, isRTL } = useLanguage();
  const [formData, setFormData] = useState({
    name: "",
    company: "",
    email: "",
    countryCode: "+20",
    phone: "",
    partnerType: "",
    interests: [],
    website: "",
    message: ""
  });
  const [showCountryDropdown, setShowCountryDropdown] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errors, setErrors] = useState({});
  const [isSubmitted, setIsSubmitted] = useState(false);
  const formSchema = getFormSchema(t);
  const countryCodes = getCountryCodes(t);
  const partnerTypes = getPartnerTypes(t);
  const interestOptions = getInterests(t);
  const toggleInterest = (id) => {
    setFormData((prev) => ({
      ...prev,
      interests: prev.interests.includes(id) ? prev.interests.filter((s) => s !== id) : [...prev.interests, id]
    }));
    setErrors((prev) => ({ ...prev, interests: "" }));
  };
  const whatsappNumber = "201100018259";
  const whatsappLink = `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(
    t("\u0645\u0631\u062D\u0628\u0627\u064B\u060C \u0623\u0631\u063A\u0628 \u0641\u064A \u0645\u0639\u0631\u0641\u0629 \u062A\u0641\u0627\u0635\u064A\u0644 \u0628\u0631\u0646\u0627\u0645\u062C \u0627\u0644\u0634\u0631\u0627\u0643\u0629 \u0644\u0644\u0634\u0631\u0643\u0627\u062A", "Hello, I'd like to know more about your business partnership program")
  )}`;
  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    const result = formSchema.safeParse(formData);
    if (!result.success) {
      const fieldErrors = {};
      result.error.errors.forEach((err) => {
        if (err.path[0]) fieldErrors[err.path[0]] = err.message;
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
      const partnerTypeLabel = partnerTypes.find((p) => p.id === formData.partnerType)?.label || formData.partnerType;
      const interestLabels = formData.interests.map((id) => interestOptions.find((o) => o.id === id)?.label || id);
      const descriptionParts = [
        formData.message || "",
        formData.website ? `${t("\u0627\u0644\u0645\u0648\u0642\u0639/\u0627\u0644\u062D\u0633\u0627\u0628", "Website/Profile")}: ${formData.website}` : ""
      ].filter(Boolean);
      const leadData = {
        name: formData.name,
        email: formData.email || t("\u063A\u064A\u0631 \u0645\u062D\u062F\u062F", "Not specified"),
        phone: `${formData.countryCode}${formData.phone}`,
        company: formData.company,
        project_type: `${t("\u0634\u0631\u0627\u0643\u0629", "Partnership")} \u2014 ${partnerTypeLabel}`,
        project_age: null,
        services: interestLabels,
        description: descriptionParts.join(" | ") || null,
        budget: null,
        timeline: null,
        source: "partnership-page"
      };
      let savedToDatabase = false;
      try {
        const { error: dbError } = await supabase.from("leads").insert({ ...leadData, services: interestLabels });
        savedToDatabase = !dbError;
      } catch {
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
          body: JSON.stringify({ ...leadData, services: interestLabels.join(", "), submitted_at: (/* @__PURE__ */ new Date()).toISOString() })
        });
      } catch {
      }
      if (!savedToDatabase && !emailSent) {
        throw new Error(t("\u0641\u0634\u0644 \u0641\u064A \u0625\u0631\u0633\u0627\u0644 \u0627\u0644\u0637\u0644\u0628", "Failed to send request"));
      }
      trackLeadConversion("partnership-page");
      setIsSubmitted(true);
      setFormData({
        name: "",
        company: "",
        email: "",
        countryCode: "+20",
        phone: "",
        partnerType: "",
        interests: [],
        website: "",
        message: ""
      });
    } catch (error) {
      console.error("Partnership form error:", error);
      toast({
        title: t("\u062D\u062F\u062B \u062E\u0637\u0623", "An error occurred"),
        description: t("\u064A\u0631\u062C\u0649 \u0627\u0644\u0645\u062D\u0627\u0648\u0644\u0629 \u0645\u0631\u0629 \u0623\u062E\u0631\u0649 \u0623\u0648 \u0627\u0644\u062A\u0648\u0627\u0635\u0644 \u0645\u0639\u0646\u0627 \u0639\u0628\u0631 \u0627\u0644\u0648\u0627\u062A\u0633\u0627\u0628", "Please try again or contact us via WhatsApp"),
        variant: "destructive"
      });
    } finally {
      setIsSubmitting(false);
    }
  };
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
          /* @__PURE__ */ jsx("h2", { className: "text-2xl sm:text-3xl md:text-4xl font-bold text-foreground mb-4", children: t("\u0634\u0643\u0631\u0627\u064B \u0644\u0627\u0647\u062A\u0645\u0627\u0645\u0643 \u0628\u0627\u0644\u0634\u0631\u0627\u0643\u0629! \u{1F91D}", "Thanks for your interest! \u{1F91D}") }),
          /* @__PURE__ */ jsx("p", { className: "text-muted-foreground text-base md:text-lg mb-6 leading-relaxed", children: t("\u062A\u0645 \u0627\u0633\u062A\u0644\u0627\u0645 \u0637\u0644\u0628 \u0627\u0644\u0634\u0631\u0627\u0643\u0629 \u0628\u0646\u062C\u0627\u062D. \u0633\u064A\u062A\u0648\u0627\u0635\u0644 \u0645\u0639\u0643 \u0641\u0631\u064A\u0642 \u0627\u0644\u0634\u0631\u0627\u0643\u0627\u062A \u062E\u0644\u0627\u0644 24 \u0633\u0627\u0639\u0629 \u0644\u0645\u0646\u0627\u0642\u0634\u0629 \u0627\u0644\u062A\u0641\u0627\u0635\u064A\u0644.", "Your partnership request has been received. Our partnerships team will contact you within 24 hours.") }),
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
                  /* @__PURE__ */ jsx(WhatsappIcon, { className: "w-5 h-5" }),
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
              children: t("\u0625\u0631\u0633\u0627\u0644 \u0637\u0644\u0628 \u0622\u062E\u0631", "Send another request")
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
          children: /* @__PURE__ */ jsx(Handshake, { className: "w-6 h-6 md:w-8 md:h-8 text-primary" })
        }
      ),
      /* @__PURE__ */ jsxs("h2", { className: "text-2xl sm:text-3xl md:text-4xl font-bold text-foreground mb-2 md:mb-3", children: [
        t("\u0633\u062C\u0651\u0644 \u0627\u0647\u062A\u0645\u0627\u0645\u0643", "Register your interest"),
        " ",
        /* @__PURE__ */ jsx("span", { className: "text-gradient-coral", children: t("\u0643\u0634\u0631\u064A\u0643", "as a partner") })
      ] }),
      /* @__PURE__ */ jsx("p", { className: "text-muted-foreground text-sm md:text-base px-2", children: t("\u0627\u062A\u0631\u0643 \u0628\u064A\u0627\u0646\u0627\u062A \u0634\u0631\u0643\u062A\u0643 \u0648\u0633\u064A\u062A\u0648\u0627\u0635\u0644 \u0645\u0639\u0643 \u0641\u0631\u064A\u0642 \u0627\u0644\u0634\u0631\u0627\u0643\u0627\u062A \u062E\u0644\u0627\u0644 24 \u0633\u0627\u0639\u0629", "Leave your company details and our partnerships team will contact you within 24 hours") })
    ] }),
    /* @__PURE__ */ jsxs("form", { onSubmit: handleSubmit, className: "space-y-5 md:space-y-6", children: [
      /* @__PURE__ */ jsxs("div", { className: "grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-6", children: [
        /* @__PURE__ */ jsxs("div", { className: "relative", children: [
          /* @__PURE__ */ jsx(User, { className: `absolute ${isRTL ? "right-3 md:right-4" : "left-3 md:left-4"} top-1/2 -translate-y-1/2 w-4 h-4 md:w-5 md:h-5 text-muted-foreground` }),
          /* @__PURE__ */ jsx(
            "input",
            {
              type: "text",
              placeholder: t("\u0627\u0633\u0645 \u0627\u0644\u0645\u0633\u0624\u0648\u0644 *", "Contact name *"),
              value: formData.name,
              onChange: (e) => {
                setFormData({ ...formData, name: e.target.value });
                setErrors((p) => ({ ...p, name: "" }));
              },
              className: `${dsInput} ${isRTL ? "pr-10 md:pr-12" : "pl-10 md:pl-12"} text-sm md:text-base py-3 md:py-4 ${errors.name ? "border-destructive" : "border-input"}`
            }
          ),
          errors.name && /* @__PURE__ */ jsx("span", { className: "text-destructive text-xs mt-1 block", children: errors.name })
        ] }),
        /* @__PURE__ */ jsxs("div", { className: "relative", children: [
          /* @__PURE__ */ jsx(Building2, { className: `absolute ${isRTL ? "right-3 md:right-4" : "left-3 md:left-4"} top-1/2 -translate-y-1/2 w-4 h-4 md:w-5 md:h-5 text-muted-foreground` }),
          /* @__PURE__ */ jsx(
            "input",
            {
              type: "text",
              placeholder: t("\u0627\u0633\u0645 \u0627\u0644\u0634\u0631\u0643\u0629 / \u0627\u0644\u062C\u0647\u0629 *", "Company name *"),
              value: formData.company,
              onChange: (e) => {
                setFormData({ ...formData, company: e.target.value });
                setErrors((p) => ({ ...p, company: "" }));
              },
              className: `${dsInput} ${isRTL ? "pr-10 md:pr-12" : "pl-10 md:pl-12"} text-sm md:text-base py-3 md:py-4 ${errors.company ? "border-destructive" : "border-input"}`
            }
          ),
          errors.company && /* @__PURE__ */ jsx("span", { className: "text-destructive text-xs mt-1 block", children: errors.company })
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
                  placeholder: t("\u0631\u0642\u0645 \u0627\u0644\u062C\u0648\u0627\u0644 *", "Phone number *"),
                  dir: "ltr",
                  value: formData.phone,
                  onChange: (e) => {
                    const value = e.target.value.replace(/[^0-9]/g, "");
                    setFormData({ ...formData, phone: value });
                    setErrors((p) => ({ ...p, phone: "" }));
                  },
                  className: `${dsInput} ${isRTL ? "pr-10 md:pr-12" : "pl-10 md:pl-12"} text-sm md:text-base py-3 md:py-4 ${isRTL ? "text-right" : "text-left"} ${errors.phone ? "border-destructive" : "border-input"}`
                }
              )
            ] })
          ] }),
          errors.phone && /* @__PURE__ */ jsx("span", { className: "text-destructive text-xs mt-1 block", children: errors.phone })
        ] }),
        /* @__PURE__ */ jsxs("div", { className: "relative", children: [
          /* @__PURE__ */ jsx(Mail, { className: `absolute ${isRTL ? "right-3 md:right-4" : "left-3 md:left-4"} top-1/2 -translate-y-1/2 w-4 h-4 md:w-5 md:h-5 text-muted-foreground` }),
          /* @__PURE__ */ jsx(
            "input",
            {
              type: "email",
              placeholder: t("\u0627\u0644\u0628\u0631\u064A\u062F \u0627\u0644\u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A (\u0627\u062E\u062A\u064A\u0627\u0631\u064A)", "Email (optional)"),
              value: formData.email,
              onChange: (e) => {
                setFormData({ ...formData, email: e.target.value });
                setErrors((p) => ({ ...p, email: "" }));
              },
              className: `${dsInput} ${isRTL ? "pr-10 md:pr-12" : "pl-10 md:pl-12"} text-sm md:text-base py-3 md:py-4 ${errors.email ? "border-destructive" : "border-input"}`
            }
          ),
          errors.email && /* @__PURE__ */ jsx("span", { className: "text-destructive text-xs mt-1 block", children: errors.email })
        ] })
      ] }),
      /* @__PURE__ */ jsxs("div", { children: [
        /* @__PURE__ */ jsx("label", { className: "block text-foreground/90 text-sm md:text-base font-medium mb-3", children: t("\u0646\u0648\u0639 \u0627\u0644\u062C\u0647\u0629 *", "Entity type *") }),
        /* @__PURE__ */ jsx("div", { className: "grid grid-cols-2 sm:grid-cols-3 gap-2 md:gap-3", children: partnerTypes.map((type) => {
          const TypeIcon = type.icon;
          const active = formData.partnerType === type.id;
          return /* @__PURE__ */ jsxs(
            "button",
            {
              type: "button",
              onClick: () => {
                setFormData({ ...formData, partnerType: type.id });
                setErrors((p) => ({ ...p, partnerType: "" }));
              },
              className: `flex items-center gap-2 p-3 rounded-2xl border text-xs md:text-sm transition-all duration-200 ease-ds ${active ? "bg-primary/15 border-primary text-primary" : "bg-card/60 border-border text-muted-foreground hover:border-primary/40 hover:text-foreground"}`,
              children: [
                /* @__PURE__ */ jsx(TypeIcon, { className: "w-4 h-4 flex-shrink-0" }),
                /* @__PURE__ */ jsx("span", { className: "text-start leading-tight", children: type.label })
              ]
            },
            type.id
          );
        }) }),
        errors.partnerType && /* @__PURE__ */ jsx("span", { className: "text-destructive text-xs mt-2 block", children: errors.partnerType })
      ] }),
      /* @__PURE__ */ jsxs("div", { children: [
        /* @__PURE__ */ jsx("label", { className: "block text-foreground/90 text-sm md:text-base font-medium mb-3", children: t("\u0645\u062C\u0627\u0644\u0627\u062A \u0627\u0644\u0627\u0647\u062A\u0645\u0627\u0645 * (\u0627\u062E\u062A\u0631 \u0648\u0627\u062D\u062F\u0627\u064B \u0623\u0648 \u0623\u0643\u062B\u0631)", "Areas of interest * (select one or more)") }),
        /* @__PURE__ */ jsx("div", { className: "flex flex-wrap gap-2 md:gap-3", children: interestOptions.map((opt) => /* @__PURE__ */ jsxs(
          "button",
          {
            type: "button",
            onClick: () => toggleInterest(opt.id),
            className: `flex items-center gap-2 rounded-full border px-3 md:px-4 py-2 md:py-2.5 font-semibold cursor-pointer transition-all duration-200 ease-ds ${formData.interests.includes(opt.id) ? "bg-primary/15 border-primary text-primary" : "bg-popover border-border text-muted-foreground hover:border-primary/60 hover:text-primary"}`,
            children: [
              /* @__PURE__ */ jsx(opt.icon, { className: "w-3.5 h-3.5 md:w-4 md:h-4" }),
              /* @__PURE__ */ jsx("span", { className: "text-xs md:text-sm", children: opt.label })
            ]
          },
          opt.id
        )) }),
        errors.interests && /* @__PURE__ */ jsx("span", { className: "text-destructive text-xs mt-2 block", children: errors.interests })
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "relative", children: [
        /* @__PURE__ */ jsx(Globe, { className: `absolute ${isRTL ? "right-3 md:right-4" : "left-3 md:left-4"} top-1/2 -translate-y-1/2 w-4 h-4 md:w-5 md:h-5 text-muted-foreground` }),
        /* @__PURE__ */ jsx(
          "input",
          {
            type: "text",
            dir: "ltr",
            placeholder: t("\u0627\u0644\u0645\u0648\u0642\u0639 \u0627\u0644\u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A / \u0627\u0644\u062D\u0633\u0627\u0628 (\u0627\u062E\u062A\u064A\u0627\u0631\u064A)", "Website / profile (optional)"),
            value: formData.website,
            onChange: (e) => setFormData({ ...formData, website: e.target.value }),
            className: `${dsInput} border-input ${isRTL ? "pr-10 md:pr-12 text-right" : "pl-10 md:pl-12 text-left"} text-sm md:text-base py-3 md:py-4`
          }
        )
      ] }),
      /* @__PURE__ */ jsxs("div", { children: [
        /* @__PURE__ */ jsx("label", { className: "block text-foreground/90 text-sm md:text-base font-medium mb-3", children: t("\u0646\u0628\u0630\u0629 \u0639\u0646 \u0634\u0631\u0643\u062A\u0643 \u0648\u0646\u0648\u0639 \u0627\u0644\u0634\u0631\u0627\u0643\u0629 \u0627\u0644\u0645\u0637\u0644\u0648\u0628\u0629", "About your company & the partnership you want") }),
        /* @__PURE__ */ jsx(
          "textarea",
          {
            placeholder: t("\u0627\u0643\u062A\u0628 \u0646\u0628\u0630\u0629 \u0645\u062E\u062A\u0635\u0631\u0629 \u0647\u0646\u0627...", "Write a short note here..."),
            rows: 4,
            value: formData.message,
            onChange: (e) => setFormData({ ...formData, message: e.target.value }),
            className: `${dsInput} border-input py-4 resize-none text-sm md:text-base`
          }
        )
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
            t("\u0623\u0631\u0633\u0644 \u0637\u0644\u0628 \u0627\u0644\u0634\u0631\u0627\u0643\u0629", "Send partnership request")
          ] })
        }
      )
    ] })
  ] }) });
};
export default PartnershipForm;
