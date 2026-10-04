import {cmsCollection} from '@/adapters/cms.js';
import { jsx, jsxs } from "react/jsx-runtime";
import { useState } from "react";
import { Link } from "react-router-dom";
import { Phone, Mail, Clock, MapPin, Building2, Heart, User, Facebook, Instagram, Send } from "lucide-react";
import findoLogo from "@/assets/findo-logo.png.js";
import { useLanguage } from "@/contexts/LanguageContext";
import { Board, Eyebrow, StatTile, WhatsappIcon } from "@/components/v2/primitives";
import { Button } from "@/components/ui/button";
import { useToast } from "@/hooks/use-toast";
import { supabase } from "@/integrations/supabase/client";
const original_quickLinks = [
  { nameAr: "\u0627\u0644\u0631\u0626\u064A\u0633\u064A\u0629", nameEn: "Home", path: "/" },
  { nameAr: "\u062E\u062F\u0645\u0627\u062A\u0646\u0627", nameEn: "Services", path: "/services" },
  { nameAr: "\u0627\u0644\u0623\u0633\u0639\u0627\u0631 \u0648\u0627\u0644\u0628\u0627\u0642\u0627\u062A", nameEn: "Pricing", path: "/pricing" },
  { nameAr: "\u0628\u0627\u0642\u0627\u062A \u0627\u0644\u062F\u0639\u0645 Findo Care", nameEn: "Findo Care", path: "/findo-care" },
  { nameAr: "\u0623\u0639\u0645\u0627\u0644\u0646\u0627 \u0627\u0644\u0633\u0627\u0628\u0642\u0629", nameEn: "Portfolio", path: "/portfolio" },
  { nameAr: "\u0645\u0646 \u0646\u062D\u0646", nameEn: "About Us", path: "/about" },
  { nameAr: "\u0627\u0644\u062A\u062F\u0631\u064A\u0628 \u0627\u0644\u0635\u064A\u0641\u064A", nameEn: "Summer Training", path: "/training" },
  { nameAr: "\u0642\u0648\u0627\u0644\u0628 \u0627\u0644\u062A\u0633\u0648\u064A\u0642", nameEn: "Marketing Templates", path: "/marketing-templates" },
  { nameAr: "\u0622\u062E\u0631 \u0627\u0644\u0623\u062E\u0628\u0627\u0631", nameEn: "Latest News", path: "/news" },
  { nameAr: "\u0627\u0644\u0645\u062F\u0648\u0646\u0629", nameEn: "Blog", path: "/blogs" },
  { nameAr: "\u0627\u0646\u0636\u0645 \u0625\u0644\u064A\u0646\u0627", nameEn: "Careers", path: "/careers" },
  { nameAr: "\u062A\u0648\u0627\u0635\u0644 \u0645\u0639\u0646\u0627", nameEn: "Contact Us", path: "/contact" }
];
const quickLinks=cmsCollection("footerQuickLinks",original_quickLinks);
const original_industriesLinks = [
  { nameAr: "\u062D\u0644\u0648\u0644 \u0627\u0644\u062A\u062C\u0632\u0626\u0629", nameEn: "Retail Solutions", path: "/retail" },
  { nameAr: "\u062D\u0644\u0648\u0644 \u0627\u0644\u0631\u0639\u0627\u064A\u0629 \u0627\u0644\u0635\u062D\u064A\u0629", nameEn: "Healthcare Solutions", path: "/healthcare" }
];
const industriesLinks=cmsCollection("footerIndustriesLinks",original_industriesLinks);
const original_servicesLinks = [
  { ar: "\u062A\u0637\u0648\u064A\u0631 \u062A\u0637\u0628\u064A\u0642\u0627\u062A \u0627\u0644\u062C\u0648\u0627\u0644 iOS \u0648 Android", en: "Mobile App Development", path: "/services/mobile-apps" },
  { ar: "\u062A\u0635\u0645\u064A\u0645 \u0648\u0628\u0631\u0645\u062C\u0629 \u0627\u0644\u0645\u0648\u0627\u0642\u0639 \u0648\u0627\u0644\u0645\u062A\u0627\u062C\u0631 \u0627\u0644\u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A\u0629", en: "Web & E-commerce Development", path: "/services/web-ecommerce" },
  { ar: "\u062A\u0635\u0645\u064A\u0645 \u0648\u062A\u0637\u0648\u064A\u0631 \u0623\u0646\u0638\u0645\u0629 \u0625\u062F\u0627\u0631\u0629 \u0627\u0644\u0634\u0631\u0643\u0627\u062A", en: "Enterprise Systems", path: "/services/enterprise-systems" },
  { ar: "\u062A\u0635\u0645\u064A\u0645 \u0648\u0627\u062C\u0647\u0629 \u0648\u062A\u062C\u0631\u0628\u0629 \u0627\u0644\u0645\u0633\u062A\u062E\u062F\u0645 UI/UX", en: "UI/UX Design", path: "/services/ui-ux" },
  { ar: "\u0627\u0644\u062A\u0633\u0648\u064A\u0642 \u0627\u0644\u0631\u0642\u0645\u064A \u0648\u062A\u062D\u0644\u064A\u0644 \u0627\u0644\u0633\u0648\u0642", en: "Digital Marketing", path: "/services/marketing" },
  { ar: "\u0627\u0644\u0630\u0643\u0627\u0621 \u0627\u0644\u0627\u0635\u0637\u0646\u0627\u0639\u064A \u0648\u0627\u0644\u0623\u062A\u0645\u062A\u0629", en: "AI & Automation", path: "/services/ai-automation" },
  { ar: "\u0627\u0644\u062A\u0635\u0645\u064A\u0645 \u0627\u0644\u062C\u0631\u0627\u0641\u064A\u0643\u064A \u0648\u0627\u0644\u0647\u0648\u064A\u0629 \u0627\u0644\u0628\u0635\u0631\u064A\u0629", en: "Graphic Design", path: "/services/graphic-design" },
  { ar: "\u0625\u062F\u0627\u0631\u0629 \u0648\u0633\u0627\u0626\u0644 \u0627\u0644\u062A\u0648\u0627\u0635\u0644 \u0627\u0644\u0627\u062C\u062A\u0645\u0627\u0639\u064A", en: "Social Media Management", path: "/services/social-media" },
  { ar: "\u0627\u0644\u0645\u0631\u0627\u062C\u0639\u0629 \u0648\u0627\u0644\u0627\u0633\u062A\u0634\u0627\u0631\u0627\u062A \u0627\u0644\u062A\u0642\u0646\u064A\u0629", en: "Technical Consulting", path: "/services/tech-review" },
  { ar: "\u0627\u0644\u0635\u064A\u0627\u0646\u0629 \u0648\u0627\u0644\u062F\u0639\u0645 \u0627\u0644\u0641\u0646\u064A", en: "Maintenance & Support", path: "/services/maintenance" }
];
const servicesLinks=cmsCollection("footerServicesLinks",original_servicesLinks);
const original_systemsLinks = [
  { ar: "\u0646\u0638\u0627\u0645 \u062A\u062E\u0637\u064A\u0637 \u0645\u0648\u0627\u0631\u062F \u0627\u0644\u0645\u0624\u0633\u0633\u0627\u062A ERP", en: "ERP System", path: "/systems/erp" },
  { ar: "\u0646\u0638\u0627\u0645 \u0625\u062F\u0627\u0631\u0629 \u0639\u0644\u0627\u0642\u0627\u062A \u0627\u0644\u0639\u0645\u0644\u0627\u0621 CRM", en: "CRM System", path: "/systems/crm" },
  { ar: "\u0646\u0638\u0627\u0645 \u0646\u0642\u0627\u0637 \u0627\u0644\u0628\u064A\u0639 \u0627\u0644\u0630\u0643\u064A\u0629 POS", en: "POS System", path: "/systems/pos" },
  { ar: "\u0646\u0638\u0627\u0645 \u0625\u062F\u0627\u0631\u0629 \u0627\u0644\u0645\u0633\u062A\u0634\u0641\u064A\u0627\u062A HMS", en: "Hospital Management", path: "/systems/hms" },
  { ar: "\u0645\u0646\u0635\u0629 \u0627\u0644\u062A\u0639\u0644\u0645 \u0627\u0644\u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A LMS", en: "LMS Platform", path: "/systems/lms" }
];
const systemsLinks=cmsCollection("footerSystemsLinks",original_systemsLinks);
const original_branches = [
  {
    cityAr: "\u0627\u0644\u0625\u0633\u0643\u0646\u062F\u0631\u064A\u0629",
    cityEn: "Alexandria",
    areaAr: "\u0644\u0648\u0631\u0627\u0646",
    areaEn: "Louran",
    addressAr: "\u0645\u0628\u0646\u0649 696\u060C \u0634\u0627\u0631\u0639 \u0627\u0644\u0634\u0639\u0631\u0627\u0648\u064A\u060C \u0628\u0627\u0644\u0642\u0631\u0628 \u0645\u0646 \u0634\u0627\u0631\u0639 \u0623\u0628\u0648 \u0642\u064A\u0631 \u0627\u0644\u0631\u0626\u064A\u0633\u064A\u060C \u0641\u0648\u0642 \u0628\u064A \u062A\u0643\u060C \u0627\u0644\u0637\u0627\u0628\u0642 \u0627\u0644\u062A\u0627\u0633\u0639",
    addressEn: "Building 696, Al-Shaarawy St., Near Abu Qir Main St., Above B.Tech, 9th Floor",
    landmarkAr: "\u0627\u0644\u0644\u0648\u0631\u0627\u0646\u060C \u0645\u0635\u0631",
    landmarkEn: "Louran, Egypt"
  },
  {
    cityAr: "\u0627\u0644\u0642\u0627\u0647\u0631\u0629",
    cityEn: "Cairo",
    areaAr: "\u0627\u0644\u0645\u0647\u0646\u062F\u0633\u064A\u0646",
    areaEn: "Mohandessin",
    addressAr: "\u0645\u0628\u0646\u0649 63\u060C \u0634\u0627\u0631\u0639 \u0633\u0648\u0631\u064A\u0627\u060C \u0627\u0644\u0645\u0647\u0646\u062F\u0633\u064A\u0646",
    addressEn: "Building 63, Syria St., Mohandessin",
    landmarkAr: "\u0645\u0635\u0631",
    landmarkEn: "Egypt"
  }
];
const branches=cmsCollection("footerBranches",original_branches);
const TikTokIcon = ({ className }) => /* @__PURE__ */ jsx("svg", { className, viewBox: "0 0 24 24", fill: "currentColor", "aria-hidden": "true", children: /* @__PURE__ */ jsx("path", { d: "M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z" }) });
const socialLinks = [
  { icon: WhatsappIcon, href: "https://wa.me/201100018259", label: "WhatsApp" },
  { icon: Facebook, href: "https://www.facebook.com/findogroupeg", label: "Facebook" },
  { icon: Instagram, href: "https://www.instagram.com/findo.group/", label: "Instagram" }
];
const original_contactItems = [
  { icon: Phone, labelAr: "\u0647\u0627\u062A\u0641 / \u0648\u0627\u062A\u0633\u0627\u0628", labelEn: "Phone / WhatsApp", value: "+20 110 001 8259", href: "tel:+201100018259" },
  { icon: Mail, labelAr: "\u0627\u0644\u0628\u0631\u064A\u062F \u0627\u0644\u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A", labelEn: "Email", value: "info@findogroup.com", href: "mailto:info@findogroup.com" },
  { icon: Clock, labelAr: "\u0633\u0627\u0639\u0627\u062A \u0627\u0644\u0639\u0645\u0644", labelEn: "Working Hours", valueAr: "\u0627\u0644\u0633\u0628\u062A - \u0627\u0644\u062E\u0645\u064A\u0633 | 10:00 \u0635 - 10:00 \u0645", valueEn: "Sat - Thu | 10:00 AM - 10:00 PM" }
];
const contactItems=cmsCollection("footerContactItems",original_contactItems);
const footerLinkClass = "text-sm text-muted-foreground transition-colors duration-200 ease-ds hover:text-primary";
const NewsletterSignup = () => {
  const { t } = useLanguage();
  const { toast } = useToast();
  const [email, setEmail] = useState("");
  const [busy, setBusy] = useState(false);
  const submit = async (e) => {
    e.preventDefault();
    const value = email.trim().toLowerCase();
    if (!value || busy) return;
    setBusy(true);
    try {
      const { error } = await supabase.from("newsletter_subscribers").upsert({ email: value, source: "footer-newsletter" }, { onConflict: "email", ignoreDuplicates: true });
      if (error) throw error;
      toast({
        title: t("\u062A\u0645 \u0627\u0634\u062A\u0631\u0627\u0643\u0643 \u0628\u0646\u062C\u0627\u062D", "You're subscribed"),
        description: t("\u0647\u064A\u0648\u0635\u0644\u0643 \u0623\u062D\u062F\u062B \u0627\u0644\u0639\u0631\u0648\u0636 \u0648\u0627\u0644\u0646\u0635\u0627\u0626\u062D \u0639\u0644\u0649 \u0628\u0631\u064A\u062F\u0643.", "You'll get our latest offers and tips by email.")
      });
      setEmail("");
    } catch {
      toast({
        title: t("\u062D\u062F\u062B \u062E\u0637\u0623", "Something went wrong"),
        description: t("\u064A\u0631\u062C\u0649 \u0627\u0644\u0645\u062D\u0627\u0648\u0644\u0629 \u0645\u0631\u0629 \u0623\u062E\u0631\u0649.", "Please try again."),
        variant: "destructive"
      });
    } finally {
      setBusy(false);
    }
  };
  return /* @__PURE__ */ jsxs("div", { className: "mx-auto max-w-sm sm:mx-0", children: [
    /* @__PURE__ */ jsx(Eyebrow, { className: "mb-3 justify-center sm:justify-start", children: t("\u0627\u0644\u0646\u0634\u0631\u0629 \u0627\u0644\u0628\u0631\u064A\u062F\u064A\u0629", "Newsletter") }),
    /* @__PURE__ */ jsx("p", { className: "mb-3 text-sm leading-relaxed text-muted-foreground", children: t(
      "\u0627\u0634\u062A\u0631\u0643 \u0644\u064A\u0635\u0644\u0643 \u0623\u062D\u062F\u062B \u0627\u0644\u0639\u0631\u0648\u0636 \u0648\u0627\u0644\u0646\u0635\u0627\u0626\u062D \u0627\u0644\u062A\u0642\u0646\u064A\u0629 \u0648\u0627\u0644\u062A\u0633\u0648\u064A\u0642\u064A\u0629.",
      "Subscribe for our latest offers and tech & marketing tips."
    ) }),
    /* @__PURE__ */ jsxs("form", { onSubmit: submit, className: "flex gap-2", children: [
      /* @__PURE__ */ jsx(
        "input",
        {
          type: "email",
          required: true,
          dir: "ltr",
          value: email,
          onChange: (e) => setEmail(e.target.value),
          placeholder: t("\u0628\u0631\u064A\u062F\u0643 \u0627\u0644\u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A", "Your email"),
          "aria-label": t("\u0628\u0631\u064A\u062F\u0643 \u0627\u0644\u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A", "Your email"),
          className: "w-full rounded-2xl border border-border bg-background px-4 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
        }
      ),
      /* @__PURE__ */ jsx(Button, { type: "submit", disabled: busy, className: "shrink-0", "aria-label": t("\u0627\u0634\u062A\u0631\u0643", "Subscribe"), children: busy ? "..." : /* @__PURE__ */ jsx(Send, { className: "h-4 w-4", strokeWidth: 1.75 }) })
    ] })
  ] });
};
const SiteFooter = () => {
  const { t, isRTL } = useLanguage();
  return /* @__PURE__ */ jsxs("footer", { dir: isRTL ? "rtl" : "ltr", className: "mx-auto w-full max-w-[1600px] px-3 pb-24 sm:px-5 lg:px-8", children: [
    /* @__PURE__ */ jsx(Board, { as: "div", className: "p-6 sm:p-10 lg:p-14", children: /* @__PURE__ */ jsxs("div", { className: "grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-12 lg:gap-8", children: [
      /* @__PURE__ */ jsxs("div", { className: "space-y-6 text-center sm:col-span-2 sm:text-start lg:col-span-4", children: [
        /* @__PURE__ */ jsx(Link, { to: "/", className: "inline-block", children: /* @__PURE__ */ jsx("img", { src: findoLogo, alt: "Findo Group", className: "h-14 w-auto object-contain md:h-16" }) }),
        /* @__PURE__ */ jsx("p", { className: "mx-auto max-w-sm text-sm leading-relaxed text-muted-foreground sm:mx-0", children: t(
          "\u0634\u0631\u064A\u0643\u0643 \u0627\u0644\u062A\u0642\u0646\u064A \u0627\u0644\u0645\u0648\u062B\u0648\u0642 \u0641\u064A \u0631\u062D\u0644\u0629 \u0627\u0644\u062A\u062D\u0648\u0644 \u0627\u0644\u0631\u0642\u0645\u064A. \u0646\u0628\u0646\u064A \u062D\u0644\u0648\u0644 \u0628\u0631\u0645\u062C\u064A\u0629 \u0645\u0628\u062A\u0643\u0631\u0629 \u062A\u0646\u0627\u0633\u0628 \u0627\u0644\u0634\u0631\u0643\u0627\u062A \u0628\u0627\u0644\u0648\u0637\u0646 \u0627\u0644\u0639\u0631\u0628\u064A \u0645\u0646\u0630 \u0623\u0643\u062B\u0631 \u0645\u0646 5 \u0633\u0646\u0648\u0627\u062A.",
          "Your trusted technology partner in the digital transformation journey. We build innovative software solutions for the Arab and Gulf markets for over 5 years."
        ) }),
        /* @__PURE__ */ jsxs("div", { className: "flex justify-center gap-3 sm:justify-start", children: [
          socialLinks.map((social) => /* @__PURE__ */ jsx(
            "a",
            {
              href: social.href,
              target: "_blank",
              rel: "noopener noreferrer",
              "aria-label": social.label,
              className: "flex h-11 w-11 items-center justify-center rounded-xl border border-border text-muted-foreground transition-colors duration-200 ease-ds hover:border-primary hover:text-primary",
              children: /* @__PURE__ */ jsx(social.icon, { className: "h-[18px] w-[18px]", strokeWidth: 1.75 })
            },
            social.label
          )),
          /* @__PURE__ */ jsx(
            "a",
            {
              href: "https://www.tiktok.com/@findogroup",
              target: "_blank",
              rel: "noopener noreferrer",
              "aria-label": "TikTok",
              className: "flex h-11 w-11 items-center justify-center rounded-xl border border-border text-muted-foreground transition-colors duration-200 ease-ds hover:border-primary hover:text-primary",
              children: /* @__PURE__ */ jsx(TikTokIcon, { className: "h-[18px] w-[18px]" })
            }
          )
        ] }),
        /* @__PURE__ */ jsx(NewsletterSignup, {}),
        /* @__PURE__ */ jsxs("div", { className: "mx-auto grid max-w-sm grid-cols-2 gap-3 sm:mx-0", children: [
          /* @__PURE__ */ jsx(
            StatTile,
            {
              icon: /* @__PURE__ */ jsx(Building2, { className: "h-5 w-5", strokeWidth: 1.75 }),
              value: /* @__PURE__ */ jsx("span", { dir: "ltr", children: "35537" }),
              label: t("\u0633\u062C\u0644 \u062A\u062C\u0627\u0631\u064A:", "Commercial Reg:")
            }
          ),
          /* @__PURE__ */ jsx(
            StatTile,
            {
              icon: /* @__PURE__ */ jsx(Building2, { className: "h-5 w-5", strokeWidth: 1.75 }),
              value: /* @__PURE__ */ jsx("span", { dir: "ltr", children: "356-456-760" }),
              label: t("\u0627\u0644\u0628\u0637\u0627\u0642\u0629 \u0627\u0644\u0636\u0631\u064A\u0628\u064A\u0629:", "Tax Card:")
            }
          )
        ] })
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "text-center sm:text-start lg:col-span-2", children: [
        /* @__PURE__ */ jsx(Eyebrow, { className: "mb-5 justify-center sm:justify-start", children: t("\u0631\u0648\u0627\u0628\u0637 \u0633\u0631\u064A\u0639\u0629", "Quick Links") }),
        /* @__PURE__ */ jsx("ul", { className: "space-y-2.5 text-center sm:text-start", children: quickLinks.map((link) => /* @__PURE__ */ jsx("li", { children: /* @__PURE__ */ jsx(Link, { to: link.path, className: footerLinkClass, children: t(link.nameAr, link.nameEn) }) }, link.path)) })
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "text-center sm:text-start lg:col-span-3", children: [
        /* @__PURE__ */ jsx(Eyebrow, { className: "mb-5 justify-center sm:justify-start", children: t("\u062E\u062F\u0645\u0627\u062A\u0646\u0627", "Our Services") }),
        /* @__PURE__ */ jsx("ul", { className: "space-y-2.5 text-center sm:text-start", children: servicesLinks.map((service) => /* @__PURE__ */ jsx("li", { children: /* @__PURE__ */ jsx(Link, { to: service.path, className: footerLinkClass, children: t(service.ar, service.en) }) }, service.path)) }),
        /* @__PURE__ */ jsx(Eyebrow, { className: "mb-3 mt-7 justify-center sm:justify-start", children: t("\u0623\u0646\u0638\u0645\u062A\u0646\u0627 \u0627\u0644\u0628\u0631\u0645\u062C\u064A\u0629", "Our Systems") }),
        /* @__PURE__ */ jsx("ul", { className: "space-y-2.5 text-center sm:text-start", children: systemsLinks.map((system) => /* @__PURE__ */ jsx("li", { children: /* @__PURE__ */ jsx(Link, { to: system.path, className: footerLinkClass, children: t(system.ar, system.en) }) }, system.path)) }),
        /* @__PURE__ */ jsx(Eyebrow, { className: "mb-3 mt-7 justify-center sm:justify-start", children: t("\u062D\u0644\u0648\u0644 \u0627\u0644\u0642\u0637\u0627\u0639\u0627\u062A", "Industry Solutions") }),
        /* @__PURE__ */ jsx("ul", { className: "space-y-2.5 text-center sm:text-start", children: industriesLinks.map((link) => /* @__PURE__ */ jsx("li", { children: /* @__PURE__ */ jsx(Link, { to: link.path, className: footerLinkClass, children: t(link.nameAr, link.nameEn) }) }, link.path)) })
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "space-y-7 text-center sm:col-span-2 sm:text-start lg:col-span-3", children: [
        /* @__PURE__ */ jsxs("div", { children: [
          /* @__PURE__ */ jsx(Eyebrow, { className: "mb-5 justify-center sm:justify-start", children: t("\u062A\u0648\u0627\u0635\u0644 \u0645\u0639\u0646\u0627", "Contact Us") }),
          /* @__PURE__ */ jsx("div", { className: "space-y-3", children: contactItems.map((item, index) => /* @__PURE__ */ jsxs(
            "div",
            {
              className: "flex items-center justify-center gap-3 rounded-2xl border border-border p-3 shadow-[inset_0_1px_0_rgba(255,255,255,0.04)] sm:justify-start",
              style: { background: "hsl(var(--popover))" },
              children: [
                /* @__PURE__ */ jsx("span", { className: "grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-primary/12 text-primary", children: /* @__PURE__ */ jsx(item.icon, { className: "h-[18px] w-[18px]", strokeWidth: 1.75 }) }),
                /* @__PURE__ */ jsxs("div", { className: "min-w-0", children: [
                  /* @__PURE__ */ jsx("span", { className: "block text-[11.5px] text-[#5C6774]", children: t(item.labelAr, item.labelEn) }),
                  "href" in item && item.href ? /* @__PURE__ */ jsx("a", { href: item.href, className: "text-sm text-foreground/90 transition-colors duration-200 ease-ds hover:text-primary", dir: "ltr", children: item.value }) : /* @__PURE__ */ jsx("span", { className: "text-sm text-foreground/90", children: t("valueAr" in item && item.valueAr || "", "valueEn" in item && item.valueEn || "") })
                ] })
              ]
            },
            index
          )) })
        ] }),
        /* @__PURE__ */ jsxs("div", { children: [
          /* @__PURE__ */ jsx(Eyebrow, { className: "mb-4 justify-center sm:justify-start", children: t("\u0641\u0631\u0648\u0639\u0646\u0627", "Our Branches") }),
          /* @__PURE__ */ jsx("div", { className: "grid grid-cols-1 gap-3", children: branches.map((branch, index) => /* @__PURE__ */ jsxs(
            "div",
            {
              className: "rounded-2xl border border-border p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.04)] transition-colors duration-200 ease-ds hover:border-primary/40",
              style: { background: "hsl(var(--popover))" },
              children: [
                /* @__PURE__ */ jsxs("div", { className: "mb-2 flex items-center justify-center gap-2.5 sm:justify-start", children: [
                  /* @__PURE__ */ jsx("span", { className: "grid h-8 w-8 shrink-0 place-items-center rounded-lg text-primary-foreground [background:var(--gradient-coral)]", children: /* @__PURE__ */ jsx(Building2, { className: "h-4 w-4", strokeWidth: 1.75 }) }),
                  /* @__PURE__ */ jsxs("div", { children: [
                    /* @__PURE__ */ jsx("span", { className: "font-display text-sm font-bold text-primary", children: t(branch.cityAr, branch.cityEn) }),
                    /* @__PURE__ */ jsx("span", { className: "mx-1 text-xs text-muted-foreground", children: "\u2022" }),
                    /* @__PURE__ */ jsx("span", { className: "text-sm text-foreground/80", children: t(branch.areaAr, branch.areaEn) })
                  ] })
                ] }),
                /* @__PURE__ */ jsx("p", { className: "mb-1 ps-0 sm:ps-[42px] text-xs leading-relaxed text-muted-foreground", children: t(branch.addressAr, branch.addressEn) }),
                /* @__PURE__ */ jsxs("p", { className: "flex items-center justify-center gap-1 ps-0 text-[10px] text-muted-foreground/70 sm:justify-start sm:ps-[42px]", children: [
                  /* @__PURE__ */ jsx(MapPin, { className: "h-3 w-3", strokeWidth: 1.75 }),
                  t(branch.landmarkAr, branch.landmarkEn)
                ] })
              ]
            },
            index
          )) })
        ] })
      ] })
    ] }) }),
    /* @__PURE__ */ jsxs("div", { className: "mt-5 flex flex-col items-center justify-between gap-3 px-2 text-center sm:flex-row sm:text-start", children: [
      /* @__PURE__ */ jsxs("p", { className: "flex flex-wrap items-center justify-center gap-1 text-xs text-muted-foreground sm:text-sm", children: [
        "\xA9 ",
        (/* @__PURE__ */ new Date()).getFullYear(),
        " Findo Group. ",
        t("\u0635\u064F\u0646\u0639 \u0628\u0640", "Made with"),
        /* @__PURE__ */ jsx(Heart, { className: "h-3.5 w-3.5 text-primary", fill: "currentColor", strokeWidth: 1.75, "aria-hidden": true }),
        t("\u062C\u0645\u064A\u0639 \u0627\u0644\u062D\u0642\u0648\u0642 \u0645\u062D\u0641\u0648\u0638\u0629.", "All Rights Reserved.")
      ] }),
      /* @__PURE__ */ jsx("span", { "aria-hidden": true, className: "hidden font-display text-[10px] font-bold uppercase tracking-[0.32em] text-muted-foreground/60 md:block", children: "DREAM AND WATCH" }),
      /* @__PURE__ */ jsxs("div", { className: "flex flex-wrap items-center justify-center gap-5", children: [
        jsx("a", { href: "/admin", className: "flex items-center gap-1.5 text-xs text-muted-foreground transition-colors duration-200 ease-ds hover:text-primary sm:text-sm", children: jsxs("span", { className: "flex items-center gap-1.5", children: [jsx(User, { className: "h-3.5 w-3.5", strokeWidth: 1.75 }), t("تسجيل الدخول", "Login")] }) }),
        /* @__PURE__ */ jsx(Link, { to: "/privacy-policy", className: "text-xs text-muted-foreground transition-colors duration-200 ease-ds hover:text-primary sm:text-sm", children: t("\u0633\u064A\u0627\u0633\u0629 \u0627\u0644\u062E\u0635\u0648\u0635\u064A\u0629", "Privacy Policy") }),
        /* @__PURE__ */ jsx(Link, { to: "/terms-conditions", className: "text-xs text-muted-foreground transition-colors duration-200 ease-ds hover:text-primary sm:text-sm", children: t("\u0627\u0644\u0634\u0631\u0648\u0637 \u0648\u0627\u0644\u0623\u062D\u0643\u0627\u0645", "Terms & Conditions") }),
        /* @__PURE__ */ jsx(Link, { to: "/refund-policy", className: "text-xs text-muted-foreground transition-colors duration-200 ease-ds hover:text-primary sm:text-sm", children: t("\u0633\u064A\u0627\u0633\u0629 \u0627\u0644\u0625\u0631\u062C\u0627\u0639", "Refund Policy") }),
        /* @__PURE__ */ jsx(
          "button",
          {
            type: "button",
            onClick: () => window.dispatchEvent(new CustomEvent("findo:open-consent")),
            className: "text-xs text-muted-foreground transition-colors duration-200 ease-ds hover:text-primary sm:text-sm",
            children: t("\u0625\u0639\u062F\u0627\u062F\u0627\u062A \u0627\u0644\u0643\u0648\u0643\u064A\u0632", "Cookie Settings")
          }
        )
      ] })
    ] })
  ] });
};
export default SiteFooter;
