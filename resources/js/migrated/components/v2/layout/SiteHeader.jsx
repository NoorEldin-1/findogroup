import {cmsCollection} from '@/adapters/cms.js';
import { Fragment, jsx, jsxs } from "react/jsx-runtime";
import { useEffect, useRef, useState } from "react";
import { motion, AnimatePresence } from "framer-motion";
import {
  Menu,
  X,
  Globe,
  Phone,
  Mail,
  Clock,
  Sun,
  Moon,
  Home,
  Briefcase,
  Users,
  FolderOpen,
  Newspaper,
  BookOpen,
  MessageCircle,
  User,
  Handshake,
  GraduationCap,
  Facebook,
  Instagram,
  Twitter,
  Linkedin,
  ChevronDown,
  Monitor,
  Smartphone,
  Palette,
  TrendingUp,
  Tag,
  LibraryBig
} from "lucide-react";
import { Link, useLocation } from "react-router-dom";
import findoLogo from "@/assets/findo-logo.png.js";
import { services } from "@/data/services";
import { useLanguage } from "@/contexts/LanguageContext";
import { useTheme } from "@/contexts/ThemeContext";
import { Button } from "@/components/ui/button";
import { IconBadge } from "@/components/v2/primitives";
import { cn } from "@/lib/utils";
const original_navLinks = [
  { href: "/", label: "Home", labelAr: "\u0627\u0644\u0631\u0626\u064A\u0633\u064A\u0629", icon: Home },
  { href: "/services", label: "Services", labelAr: "\u0627\u0644\u062E\u062F\u0645\u0627\u062A", icon: Briefcase, hasDropdown: "services" },
  { href: "/portfolio", label: "Portfolio", labelAr: "\u0623\u0639\u0645\u0627\u0644\u0646\u0627", icon: FolderOpen, hasDropdown: "portfolio" },
  { href: "/pricing", label: "Pricing", labelAr: "\u0627\u0644\u0623\u0633\u0639\u0627\u0631", icon: Tag },
  { href: "/about", label: "About", labelAr: "\u0645\u0646 \u0646\u062D\u0646", icon: Users },
  { href: "/training", label: "Training", labelAr: "\u0627\u0644\u062A\u062F\u0631\u064A\u0628", icon: GraduationCap },
  { href: "/news", label: "News", labelAr: "\u0627\u0644\u0623\u062E\u0628\u0627\u0631", icon: Newspaper, hasDropdown: "content" },
  { href: "/careers", label: "Join Us", labelAr: "\u0627\u0646\u0636\u0645 \u0625\u0644\u064A\u0646\u0627", icon: Handshake, hasDropdown: "join" },
  { href: "/contact", label: "Contact", labelAr: "\u062A\u0648\u0627\u0635\u0644 \u0645\u0639\u0646\u0627", icon: MessageCircle }
];
const navLinks=cmsCollection("headerNavLinks",original_navLinks);
const portfolioCategories = [
  { id: "website", label: "Websites", labelAr: "\u0645\u0648\u0627\u0642\u0639 \u0627\u0644\u0648\u064A\u0628", icon: Monitor },
  { id: "app", label: "Mobile Apps", labelAr: "\u062A\u0637\u0628\u064A\u0642\u0627\u062A \u0627\u0644\u0645\u0648\u0628\u0627\u064A\u0644", icon: Smartphone },
  { id: "design", label: "Graphic Design", labelAr: "\u0627\u0644\u062A\u0635\u0645\u064A\u0645 \u0627\u0644\u062C\u0631\u0627\u0641\u064A\u0643\u064A", icon: Palette }
];
const joinLinks = [
  { href: "/careers", label: "Join our team", labelAr: "\u0627\u0646\u0636\u0645 \u0644\u0641\u0631\u064A\u0642\u0646\u0627", descEn: "Open roles & careers", descAr: "\u0627\u0644\u0648\u0638\u0627\u0626\u0641 \u0627\u0644\u0645\u062A\u0627\u062D\u0629", icon: Briefcase },
  { href: "/partnership", label: "Become a partner", labelAr: "\u0643\u0646 \u0634\u0631\u064A\u0643\u0646\u0627", descEn: "Business partnership", descAr: "\u0634\u0631\u0627\u0643\u0629 \u0644\u0644\u0634\u0631\u0643\u0627\u062A", icon: Handshake }
];
const contentLinks = [
  { href: "/news", label: "News", labelAr: "\u0627\u0644\u0623\u062E\u0628\u0627\u0631", descEn: "Latest news", descAr: "\u0622\u062E\u0631 \u0627\u0644\u0623\u062E\u0628\u0627\u0631", icon: Newspaper },
  { href: "/blogs", label: "Blogs", labelAr: "\u0627\u0644\u0645\u062F\u0648\u0646\u0627\u062A", descEn: "Latest articles", descAr: "\u0623\u062D\u062F\u062B \u0627\u0644\u0645\u0642\u0627\u0644\u0627\u062A", icon: BookOpen },
  { href: "/marketing-templates", label: "Marketing Templates", labelAr: "\u0642\u0648\u0627\u0644\u0628 \u0627\u0644\u062A\u0633\u0648\u064A\u0642", descEn: "Free downloadable templates", descAr: "\u0642\u0648\u0627\u0644\u0628 \u0645\u062C\u0627\u0646\u064A\u0629 \u0644\u0644\u062A\u062D\u0645\u064A\u0644", icon: LibraryBig }
];
const original_socialLinks = [
  { icon: Facebook, href: "https://www.facebook.com/findogroupeg", label: "Facebook" },
  { icon: Instagram, href: "https://www.instagram.com/findo.group/", label: "Instagram" },
  { icon: Twitter, href: "https://www.tiktok.com/@findogroup", label: "TikTok" },
  { icon: Linkedin, href: "https://www.linkedin.com/company/findo-group/", label: "LinkedIn" }
];
const socialLinks=cmsCollection("headerSocialLinks",original_socialLinks);
const dropdownMotion = {
  initial: { opacity: 0, y: 8 },
  animate: { opacity: 1, y: 0 },
  exit: { opacity: 0, y: 8 },
  transition: { duration: 0.18, ease: [0.2, 0.7, 0.2, 1] }
};
const SiteHeader = () => {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [activeDropdown, setActiveDropdown] = useState(null);
  const [mobileServicesOpen, setMobileServicesOpen] = useState(false);
  const [mobilePortfolioOpen, setMobilePortfolioOpen] = useState(false);
  const [mobileJoinOpen, setMobileJoinOpen] = useState(false);
  const [mobileContentOpen, setMobileContentOpen] = useState(false);
  const { language, toggleLanguage, t, isRTL } = useLanguage();
  const { isDark, toggleTheme } = useTheme();
  const location = useLocation();
  const progressRef = useRef(null);
  const isActive = (href) => {
    if (href === "/") return location.pathname === "/";
    return location.pathname.startsWith(href);
  };
  useEffect(() => {
    let raf = 0;
    const update = () => {
      raf = 0;
      const doc = document.documentElement;
      const max = doc.scrollHeight - doc.clientHeight;
      const p = max > 0 ? Math.min(1, Math.max(0, window.scrollY / max)) : 0;
      if (progressRef.current) progressRef.current.style.width = `${p * 100}%`;
    };
    const onScroll = () => {
      if (!raf) raf = requestAnimationFrame(update);
    };
    update();
    window.addEventListener("scroll", onScroll, { passive: true });
    window.addEventListener("resize", onScroll, { passive: true });
    return () => {
      window.removeEventListener("scroll", onScroll);
      window.removeEventListener("resize", onScroll);
      if (raf) cancelAnimationFrame(raf);
    };
  }, []);
  const closeMobile = () => {
    setMobileMenuOpen(false);
    setMobileServicesOpen(false);
    setMobilePortfolioOpen(false);
    setMobileJoinOpen(false);
    setMobileContentOpen(false);
  };
  const renderDropdownPanel = (key) => {
    const isServices = key === "services";
    const isPortfolio = key === "portfolio";
    const isJoin = key === "join";
    const isContent = key === "content";
    return /* @__PURE__ */ jsxs(
      motion.div,
      {
        ...dropdownMotion,
        className: cn(
          "overflow-hidden rounded-2xl border border-border bg-popover shadow-board",
          isServices ? "w-80" : "w-72"
        ),
        children: [
          isServices && /* @__PURE__ */ jsxs(Fragment, { children: [
            /* @__PURE__ */ jsxs("div", { className: "max-h-[60vh] overflow-y-auto p-2", children: [
              services.map((service) => /* @__PURE__ */ jsxs(
                Link,
                {
                  to: `/services/${service.id}`,
                  onClick: () => setActiveDropdown(null),
                  className: "flex items-center gap-3 rounded-xl px-3 py-2.5 transition-colors duration-200 ease-ds hover:bg-primary/10",
                  children: [
                    /* @__PURE__ */ jsx(IconBadge, { icon: service.icon, size: "sm" }),
                    /* @__PURE__ */ jsxs("span", { className: "min-w-0 flex-1", children: [
                      /* @__PURE__ */ jsx("span", { className: "block truncate text-sm font-medium text-foreground", children: language === "ar" ? service.titleAr : service.titleEn }),
                      /* @__PURE__ */ jsx("span", { className: "block truncate text-xs text-muted-foreground", children: language === "ar" ? service.subtitleAr : service.subtitleEn })
                    ] })
                  ]
                },
                service.id
              )),
              /* @__PURE__ */ jsxs(
                Link,
                {
                  to: "/digital-marketing",
                  onClick: () => setActiveDropdown(null),
                  className: "flex items-center gap-3 rounded-xl px-3 py-2.5 transition-colors duration-200 ease-ds hover:bg-primary/10",
                  children: [
                    /* @__PURE__ */ jsx(IconBadge, { icon: TrendingUp, size: "sm" }),
                    /* @__PURE__ */ jsxs("span", { className: "min-w-0 flex-1", children: [
                      /* @__PURE__ */ jsx("span", { className: "block truncate text-sm font-medium text-foreground", children: language === "ar" ? "\u0627\u0644\u062A\u0633\u0648\u064A\u0642 \u0627\u0644\u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A" : "Digital Marketing" }),
                      /* @__PURE__ */ jsx("span", { className: "block truncate text-xs text-muted-foreground", children: language === "ar" ? "\u0628\u0627\u0642\u0627\u062A \u062A\u0633\u0648\u064A\u0642\u064A\u0629 \u0627\u062D\u062A\u0631\u0627\u0641\u064A\u0629" : "Professional Marketing Packages" })
                    ] })
                  ]
                }
              )
            ] }),
            /* @__PURE__ */ jsx("div", { className: "border-t border-border p-2", children: /* @__PURE__ */ jsx(
              Link,
              {
                to: "/services",
                onClick: () => setActiveDropdown(null),
                className: "flex items-center justify-center gap-2 rounded-xl bg-primary/10 px-3 py-2.5 text-sm font-medium text-primary transition-colors duration-200 ease-ds hover:bg-primary/15",
                children: language === "ar" ? "\u0639\u0631\u0636 \u062C\u0645\u064A\u0639 \u0627\u0644\u062E\u062F\u0645\u0627\u062A" : "View All Services"
              }
            ) })
          ] }),
          isPortfolio && /* @__PURE__ */ jsxs(Fragment, { children: [
            /* @__PURE__ */ jsx("div", { className: "p-2", children: portfolioCategories.map((category) => /* @__PURE__ */ jsxs(
              Link,
              {
                to: `/portfolio?category=${category.id}`,
                onClick: () => setActiveDropdown(null),
                className: "flex items-center gap-3 rounded-xl px-3 py-3 transition-colors duration-200 ease-ds hover:bg-primary/10",
                children: [
                  /* @__PURE__ */ jsx(IconBadge, { icon: category.icon, size: "sm" }),
                  /* @__PURE__ */ jsx("span", { className: "text-sm font-medium text-foreground", children: language === "en" ? category.label : category.labelAr })
                ]
              },
              category.id
            )) }),
            /* @__PURE__ */ jsx("div", { className: "border-t border-border p-2", children: /* @__PURE__ */ jsx(
              Link,
              {
                to: "/portfolio",
                onClick: () => setActiveDropdown(null),
                className: "flex items-center justify-center gap-2 rounded-xl bg-primary/10 px-3 py-2.5 text-sm font-medium text-primary transition-colors duration-200 ease-ds hover:bg-primary/15",
                children: language === "ar" ? "\u0639\u0631\u0636 \u062C\u0645\u064A\u0639 \u0627\u0644\u0623\u0639\u0645\u0627\u0644" : "View All Projects"
              }
            ) })
          ] }),
          isJoin && /* @__PURE__ */ jsx("div", { className: "p-2", children: joinLinks.map((item) => /* @__PURE__ */ jsxs(
            Link,
            {
              to: item.href,
              onClick: () => setActiveDropdown(null),
              className: "flex items-center gap-3 rounded-xl px-3 py-3 transition-colors duration-200 ease-ds hover:bg-primary/10",
              children: [
                /* @__PURE__ */ jsx(IconBadge, { icon: item.icon, size: "sm" }),
                /* @__PURE__ */ jsxs("span", { className: "min-w-0 flex-1", children: [
                  /* @__PURE__ */ jsx("span", { className: "block text-sm font-medium text-foreground", children: language === "en" ? item.label : item.labelAr }),
                  /* @__PURE__ */ jsx("span", { className: "block text-xs text-muted-foreground", children: language === "en" ? item.descEn : item.descAr })
                ] })
              ]
            },
            item.href
          )) }),
          isContent && /* @__PURE__ */ jsx("div", { className: "p-2", children: contentLinks.map((item) => /* @__PURE__ */ jsxs(
            Link,
            {
              to: item.href,
              onClick: () => setActiveDropdown(null),
              className: "flex items-center gap-3 rounded-xl px-3 py-3 transition-colors duration-200 ease-ds hover:bg-primary/10",
              children: [
                /* @__PURE__ */ jsx(IconBadge, { icon: item.icon, size: "sm" }),
                /* @__PURE__ */ jsxs("span", { className: "min-w-0 flex-1", children: [
                  /* @__PURE__ */ jsx("span", { className: "block text-sm font-medium text-foreground", children: language === "en" ? item.label : item.labelAr }),
                  /* @__PURE__ */ jsx("span", { className: "block text-xs text-muted-foreground", children: language === "en" ? item.descEn : item.descAr })
                ] })
              ]
            },
            item.href
          )) })
        ]
      }
    );
  };
  return /* @__PURE__ */ jsxs(
    "header",
    {
      className: "sticky top-0 z-50 border-b border-border backdrop-blur-xl",
      style: { background: "hsl(var(--background) / 0.62)" },
      dir: isRTL ? "rtl" : "ltr",
      children: [
        /* @__PURE__ */ jsx("div", { className: "hidden border-b border-border/60 lg:block", children: /* @__PURE__ */ jsxs("div", { className: "mx-auto flex h-9 max-w-[1600px] items-center justify-between px-4 text-xs lg:px-8", children: [
          /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-5", children: [
            /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-1.5 text-muted-foreground", children: [
              /* @__PURE__ */ jsx(Clock, { className: "h-3 w-3 text-primary/80", strokeWidth: 1.75 }),
              /* @__PURE__ */ jsx("span", { children: language === "ar" ? "\u062E\u062F\u0645\u0629 24/7" : "24/7 Service" })
            ] }),
            /* @__PURE__ */ jsx("div", { className: "flex items-center gap-1.5", children: socialLinks.map((social) => /* @__PURE__ */ jsx(
              "a",
              {
                href: social.href,
                target: "_blank",
                rel: "noopener noreferrer",
                "aria-label": social.label,
                className: "flex h-6 w-6 items-center justify-center rounded-full border border-border text-muted-foreground transition-colors duration-200 ease-ds hover:border-primary hover:text-primary",
                children: /* @__PURE__ */ jsx(social.icon, { className: "h-3 w-3", strokeWidth: 1.75 })
              },
              social.label
            )) }),
            /* @__PURE__ */ jsxs(
              "a",
              {
                href: "tel:+201100018259",
                className: "flex items-center gap-1.5 text-muted-foreground transition-colors duration-200 ease-ds hover:text-primary",
                children: [
                  /* @__PURE__ */ jsx(Phone, { className: "h-3 w-3", strokeWidth: 1.75 }),
                  /* @__PURE__ */ jsx("span", { dir: "ltr", children: "+20 110 001 8259" })
                ]
              }
            ),
            /* @__PURE__ */ jsxs(
              "a",
              {
                href: "mailto:info@findogroup.com",
                className: "flex items-center gap-1.5 text-muted-foreground transition-colors duration-200 ease-ds hover:text-primary",
                children: [
                  /* @__PURE__ */ jsx(Mail, { className: "h-3 w-3", strokeWidth: 1.75 }),
                  /* @__PURE__ */ jsx("span", { children: "info@findogroup.com" })
                ]
              }
            )
          ] }),
          /* @__PURE__ */ jsx("div", { className: "flex items-center gap-2", children: /* @__PURE__ */ jsxs(
            "a",
            {
              href: "/admin",
              target: "_blank",
              rel: "noopener noreferrer",
              className: "flex items-center gap-1.5 rounded-full border border-border px-3 py-1 text-xs font-medium text-muted-foreground transition-colors duration-200 ease-ds hover:border-primary/60 hover:text-foreground",
              children: [
                /* @__PURE__ */ jsx(User, { className: "h-3 w-3", strokeWidth: 1.75 }),
                language === "ar" ? "\u062A\u0633\u062C\u064A\u0644 \u0627\u0644\u062F\u062E\u0648\u0644" : "Login"
              ]
            }
          ) })
        ] }) }),
        /* @__PURE__ */ jsxs("div", { className: "mx-auto flex h-16 max-w-[1600px] items-center justify-between gap-3 px-4 lg:px-8", children: [
          /* @__PURE__ */ jsx(Link, { to: "/", className: "flex-shrink-0", children: /* @__PURE__ */ jsx(
            "img",
            {
              src: findoLogo,
              alt: "Findo Group",
              width: 263,
              height: 70,
              fetchPriority: "high",
              className: "h-11 w-auto object-contain md:h-12"
            }
          ) }),
          /* @__PURE__ */ jsx("nav", { className: "hidden h-full min-w-0 flex-1 items-center justify-center lg:flex", children: /* @__PURE__ */ jsx("div", { className: "flex h-full items-center", children: navLinks.map((link) => {
            const active = isActive(link.href);
            const linkClasses = cn(
              "relative flex h-full items-center gap-1.5 px-2.5 xl:px-3.5 text-sm font-medium whitespace-nowrap transition-colors duration-200 ease-ds",
              active ? "text-foreground" : "text-muted-foreground hover:text-foreground"
            );
            const underline = active && /* @__PURE__ */ jsx(
              motion.span,
              {
                "aria-hidden": true,
                initial: { scaleX: 0 },
                animate: { scaleX: 1 },
                transition: { duration: 0.35, ease: [0.2, 0.7, 0.2, 1] },
                className: "absolute inset-x-2 bottom-0 h-0.5 origin-left rounded-full bg-gradient-to-r from-primary to-primary/20 rtl:origin-right"
              }
            );
            if (link.hasDropdown) {
              const isOpen = activeDropdown === link.hasDropdown;
              return /* @__PURE__ */ jsxs(
                "div",
                {
                  className: "relative h-full",
                  onMouseEnter: () => setActiveDropdown(link.hasDropdown),
                  onMouseLeave: () => setActiveDropdown(null),
                  children: [
                    /* @__PURE__ */ jsxs(Link, { to: link.href, className: linkClasses, children: [
                      /* @__PURE__ */ jsx("span", { children: language === "en" ? link.label : link.labelAr }),
                      /* @__PURE__ */ jsx(
                        ChevronDown,
                        {
                          className: cn("h-3.5 w-3.5 transition-transform duration-300", isOpen && "rotate-180"),
                          strokeWidth: 1.75
                        }
                      ),
                      underline
                    ] }),
                    /* @__PURE__ */ jsx(AnimatePresence, { children: isOpen && /* @__PURE__ */ jsx("div", { className: "absolute top-full start-0 z-50 pt-2", children: renderDropdownPanel(link.hasDropdown) }) })
                  ]
                },
                link.href
              );
            }
            return /* @__PURE__ */ jsxs(Link, { to: link.href, className: linkClasses, children: [
              /* @__PURE__ */ jsx("span", { children: language === "en" ? link.label : link.labelAr }),
              underline
            ] }, link.href);
          }) }) }),
          /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-2", children: [
            /* @__PURE__ */ jsx(
              "button",
              {
                type: "button",
                onClick: toggleTheme,
                "aria-label": isDark ? t("\u0627\u0644\u0648\u0636\u0639 \u0627\u0644\u0641\u0627\u062A\u062D", "Light mode") : t("\u0627\u0644\u0648\u0636\u0639 \u0627\u0644\u062F\u0627\u0643\u0646", "Dark mode"),
                title: isDark ? t("\u0627\u0644\u0648\u0636\u0639 \u0627\u0644\u0641\u0627\u062A\u062D", "Light mode") : t("\u0627\u0644\u0648\u0636\u0639 \u0627\u0644\u062F\u0627\u0643\u0646", "Dark mode"),
                className: "hidden h-9 w-9 items-center justify-center rounded-full border border-border text-muted-foreground transition-colors duration-200 ease-ds hover:border-primary/60 hover:text-foreground lg:flex",
                children: isDark ? /* @__PURE__ */ jsx(Sun, { className: "h-4 w-4", strokeWidth: 1.75 }) : /* @__PURE__ */ jsx(Moon, { className: "h-4 w-4", strokeWidth: 1.75 })
              }
            ),
            /* @__PURE__ */ jsxs(
              "button",
              {
                type: "button",
                onClick: toggleLanguage,
                className: "hidden items-center gap-1.5 rounded-full border border-border px-3 py-1.5 text-sm font-medium text-muted-foreground transition-colors duration-200 ease-ds hover:border-primary/60 hover:text-foreground lg:flex",
                children: [
                  /* @__PURE__ */ jsx(Globe, { className: "h-4 w-4", strokeWidth: 1.75 }),
                  /* @__PURE__ */ jsx("span", { children: language === "en" ? "\u0627\u0644\u0639\u0631\u0628\u064A\u0629" : "English" })
                ]
              }
            ),
            /* @__PURE__ */ jsx(Button, { asChild: true, className: "hidden lg:inline-flex", children: /* @__PURE__ */ jsx(Link, { to: "/start-project", children: language === "ar" ? "\u0627\u0628\u062F\u0623 \u0645\u0634\u0631\u0648\u0639\u0643" : "Start Project" }) }),
            /* @__PURE__ */ jsx(
              "button",
              {
                type: "button",
                onClick: () => setMobileMenuOpen(!mobileMenuOpen),
                "aria-label": "\u0627\u0644\u0642\u0627\u0626\u0645\u0629",
                className: "flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full border border-border text-foreground transition-colors duration-200 ease-ds hover:border-primary/60 lg:hidden",
                children: mobileMenuOpen ? /* @__PURE__ */ jsx(X, { className: "h-5 w-5", strokeWidth: 1.75 }) : /* @__PURE__ */ jsx(Menu, { className: "h-5 w-5", strokeWidth: 1.75 })
              }
            )
          ] })
        ] }),
        /* @__PURE__ */ jsx(
          "div",
          {
            ref: progressRef,
            "aria-hidden": true,
            className: "absolute bottom-0 start-0 h-0.5 w-0",
            style: {
              background: "linear-gradient(90deg,#FD802E,#FFB877)",
              boxShadow: "0 0 12px rgba(253,128,46,0.55)"
            }
          }
        ),
        /* @__PURE__ */ jsx(AnimatePresence, { children: mobileMenuOpen && /* @__PURE__ */ jsx(
          motion.div,
          {
            initial: { opacity: 0, height: 0 },
            animate: { opacity: 1, height: "auto" },
            exit: { opacity: 0, height: 0 },
            transition: { duration: 0.25, ease: [0.2, 0.7, 0.2, 1] },
            className: "absolute inset-x-0 top-full overflow-hidden rounded-b-[28px] border-b border-border bg-popover shadow-board lg:hidden",
            children: /* @__PURE__ */ jsxs("div", { className: "mx-auto max-h-[70vh] max-w-[1600px] space-y-2 overflow-y-auto px-4 py-4", children: [
              navLinks.map((link) => {
                const IconComponent = link.icon;
                const isServices = link.hasDropdown === "services";
                const isPortfolio = link.hasDropdown === "portfolio";
                const isJoin = link.hasDropdown === "join";
                const isContent = link.hasDropdown === "content";
                if (link.hasDropdown) {
                  const isOpen = isServices ? mobileServicesOpen : isPortfolio ? mobilePortfolioOpen : isJoin ? mobileJoinOpen : mobileContentOpen;
                  const setOpen = isServices ? setMobileServicesOpen : isPortfolio ? setMobilePortfolioOpen : isJoin ? setMobileJoinOpen : setMobileContentOpen;
                  return /* @__PURE__ */ jsxs("div", { children: [
                    /* @__PURE__ */ jsxs(
                      "button",
                      {
                        type: "button",
                        onClick: () => setOpen(!isOpen),
                        className: cn(
                          "flex w-full items-center justify-between rounded-xl px-4 py-3 transition-colors duration-200 ease-ds",
                          isActive(link.href) ? "bg-primary/10 text-foreground" : "text-muted-foreground hover:bg-primary/10 hover:text-foreground"
                        ),
                        children: [
                          /* @__PURE__ */ jsxs("span", { className: "flex items-center gap-3", children: [
                            /* @__PURE__ */ jsx(IconComponent, { className: "h-5 w-5", strokeWidth: 1.75 }),
                            /* @__PURE__ */ jsx("span", { className: "font-medium", children: language === "en" ? link.label : link.labelAr })
                          ] }),
                          /* @__PURE__ */ jsx(
                            ChevronDown,
                            {
                              className: cn("h-4 w-4 transition-transform duration-300", isOpen && "rotate-180"),
                              strokeWidth: 1.75
                            }
                          )
                        ]
                      }
                    ),
                    /* @__PURE__ */ jsx(AnimatePresence, { children: isOpen && /* @__PURE__ */ jsx(
                      motion.div,
                      {
                        initial: { opacity: 0, height: 0 },
                        animate: { opacity: 1, height: "auto" },
                        exit: { opacity: 0, height: 0 },
                        transition: { duration: 0.2 },
                        className: "overflow-hidden",
                        children: /* @__PURE__ */ jsxs("div", { className: "ms-4 space-y-1 border-s border-border ps-3 py-2", children: [
                          isServices && services.map((service) => {
                            const ServiceIcon = service.icon;
                            return /* @__PURE__ */ jsxs(
                              Link,
                              {
                                to: `/services/${service.id}`,
                                onClick: closeMobile,
                                className: "flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-muted-foreground transition-colors duration-200 ease-ds hover:bg-primary/10 hover:text-foreground",
                                children: [
                                  /* @__PURE__ */ jsx(ServiceIcon, { className: "h-4 w-4 text-primary/80", strokeWidth: 1.75 }),
                                  /* @__PURE__ */ jsx("span", { children: language === "ar" ? service.titleAr : service.titleEn })
                                ]
                              },
                              service.id
                            );
                          }),
                          isServices && /* @__PURE__ */ jsxs(
                            Link,
                            {
                              to: "/digital-marketing",
                              onClick: closeMobile,
                              className: "flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-muted-foreground transition-colors duration-200 ease-ds hover:bg-primary/10 hover:text-foreground",
                              children: [
                                /* @__PURE__ */ jsx(TrendingUp, { className: "h-4 w-4 text-primary/80", strokeWidth: 1.75 }),
                                /* @__PURE__ */ jsx("span", { children: language === "ar" ? "\u0627\u0644\u062A\u0633\u0648\u064A\u0642 \u0627\u0644\u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A" : "Digital Marketing" })
                              ]
                            }
                          ),
                          isPortfolio && portfolioCategories.map((category) => {
                            const CategoryIcon = category.icon;
                            return /* @__PURE__ */ jsxs(
                              Link,
                              {
                                to: `/portfolio?category=${category.id}`,
                                onClick: closeMobile,
                                className: "flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-muted-foreground transition-colors duration-200 ease-ds hover:bg-primary/10 hover:text-foreground",
                                children: [
                                  /* @__PURE__ */ jsx(CategoryIcon, { className: "h-4 w-4 text-primary/80", strokeWidth: 1.75 }),
                                  /* @__PURE__ */ jsx("span", { children: language === "en" ? category.label : category.labelAr })
                                ]
                              },
                              category.id
                            );
                          }),
                          isJoin && joinLinks.map((item) => {
                            const ItemIcon = item.icon;
                            return /* @__PURE__ */ jsxs(
                              Link,
                              {
                                to: item.href,
                                onClick: closeMobile,
                                className: "flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-muted-foreground transition-colors duration-200 ease-ds hover:bg-primary/10 hover:text-foreground",
                                children: [
                                  /* @__PURE__ */ jsx(ItemIcon, { className: "h-4 w-4 text-primary/80", strokeWidth: 1.75 }),
                                  /* @__PURE__ */ jsx("span", { children: language === "en" ? item.label : item.labelAr })
                                ]
                              },
                              item.href
                            );
                          }),
                          isContent && contentLinks.map((item) => {
                            const ItemIcon = item.icon;
                            return /* @__PURE__ */ jsxs(
                              Link,
                              {
                                to: item.href,
                                onClick: closeMobile,
                                className: "flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-muted-foreground transition-colors duration-200 ease-ds hover:bg-primary/10 hover:text-foreground",
                                children: [
                                  /* @__PURE__ */ jsx(ItemIcon, { className: "h-4 w-4 text-primary/80", strokeWidth: 1.75 }),
                                  /* @__PURE__ */ jsx("span", { children: language === "en" ? item.label : item.labelAr })
                                ]
                              },
                              item.href
                            );
                          }),
                          !isJoin && !isContent && /* @__PURE__ */ jsxs(
                            Link,
                            {
                              to: link.href,
                              onClick: closeMobile,
                              className: "flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-primary transition-colors duration-200 ease-ds hover:bg-primary/10",
                              children: [
                                /* @__PURE__ */ jsx(IconComponent, { className: "h-4 w-4", strokeWidth: 1.75 }),
                                /* @__PURE__ */ jsx("span", { children: language === "ar" ? "\u0639\u0631\u0636 \u0627\u0644\u0643\u0644" : "View All" })
                              ]
                            }
                          )
                        ] })
                      }
                    ) })
                  ] }, link.href);
                }
                return /* @__PURE__ */ jsxs(
                  Link,
                  {
                    to: link.href,
                    onClick: closeMobile,
                    className: cn(
                      "flex items-center gap-3 rounded-xl px-4 py-3 transition-colors duration-200 ease-ds",
                      isActive(link.href) ? "bg-primary/10 text-foreground" : "text-muted-foreground hover:bg-primary/10 hover:text-foreground"
                    ),
                    children: [
                      /* @__PURE__ */ jsx(IconComponent, { className: "h-5 w-5", strokeWidth: 1.75 }),
                      /* @__PURE__ */ jsx("span", { className: "font-medium", children: language === "en" ? link.label : link.labelAr })
                    ]
                  },
                  link.href
                );
              }),
              /* @__PURE__ */ jsxs("div", { className: "space-y-2 border-t border-border pt-4", children: [
                /* @__PURE__ */ jsxs(
                  "button",
                  {
                    type: "button",
                    onClick: toggleLanguage,
                    className: "flex w-full items-center gap-3 rounded-xl px-4 py-3 text-muted-foreground transition-colors duration-200 ease-ds hover:bg-primary/10 hover:text-foreground",
                    children: [
                      /* @__PURE__ */ jsx(Globe, { className: "h-5 w-5", strokeWidth: 1.75 }),
                      /* @__PURE__ */ jsx("span", { className: "font-medium", children: language === "en" ? "\u0627\u0644\u0639\u0631\u0628\u064A\u0629" : "English" })
                    ]
                  }
                ),
                /* @__PURE__ */ jsxs(
                  "button",
                  {
                    type: "button",
                    onClick: toggleTheme,
                    className: "flex w-full items-center gap-3 rounded-xl px-4 py-3 text-muted-foreground transition-colors duration-200 ease-ds hover:bg-primary/10 hover:text-foreground",
                    children: [
                      isDark ? /* @__PURE__ */ jsx(Sun, { className: "h-5 w-5", strokeWidth: 1.75 }) : /* @__PURE__ */ jsx(Moon, { className: "h-5 w-5", strokeWidth: 1.75 }),
                      /* @__PURE__ */ jsx("span", { className: "font-medium", children: isDark ? t("\u0627\u0644\u0648\u0636\u0639 \u0627\u0644\u0641\u0627\u062A\u062D", "Light mode") : t("\u0627\u0644\u0648\u0636\u0639 \u0627\u0644\u062F\u0627\u0643\u0646", "Dark mode") })
                    ]
                  }
                ),
                /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", className: "w-full", children: /* @__PURE__ */ jsxs(Link, { to: "/admin", onClick: closeMobile, children: [
                  /* @__PURE__ */ jsx(User, { strokeWidth: 1.75 }),
                  language === "ar" ? "\u062A\u0633\u062C\u064A\u0644 \u0627\u0644\u062F\u062E\u0648\u0644" : "Login"
                ] }) }),
                /* @__PURE__ */ jsx(Button, { asChild: true, className: "w-full", children: /* @__PURE__ */ jsx(Link, { to: "/start-project", onClick: closeMobile, children: language === "ar" ? "\u0627\u0628\u062F\u0623 \u0645\u0634\u0631\u0648\u0639\u0643" : "Start Project" }) })
              ] }),
              /* @__PURE__ */ jsx("div", { className: "flex items-center justify-center gap-3 border-t border-border pt-4", children: socialLinks.map((social) => /* @__PURE__ */ jsx(
                "a",
                {
                  href: social.href,
                  target: "_blank",
                  rel: "noopener noreferrer",
                  "aria-label": social.label,
                  className: "flex h-10 w-10 items-center justify-center rounded-xl border border-border text-muted-foreground transition-colors duration-200 ease-ds hover:border-primary hover:text-primary",
                  children: /* @__PURE__ */ jsx(social.icon, { className: "h-4 w-4", strokeWidth: 1.75 })
                },
                social.label
              )) })
            ] })
          }
        ) })
      ]
    }
  );
};
export default SiteHeader;
