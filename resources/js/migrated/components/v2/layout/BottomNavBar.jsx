import { jsx, jsxs } from "react/jsx-runtime";
import { Link, useLocation } from "react-router-dom";
import { Home, Briefcase, FolderOpen, Send } from "lucide-react";
import { useLanguage } from "@/contexts/LanguageContext";
import { cn } from "@/lib/utils";
import { WhatsappIcon } from "@/components/v2/primitives";
const phoneNumber = "201100018259";
const whatsappMessage = "\u0645\u0631\u062D\u0628\u0627\u064B\u060C \u0623\u0631\u064A\u062F \u0627\u0644\u0627\u0633\u062A\u0641\u0633\u0627\u0631 \u0639\u0646 \u062E\u062F\u0645\u0627\u062A\u0643\u0645";
const whatsappUrl = `https://wa.me/${phoneNumber}?text=${encodeURIComponent(whatsappMessage)}`;
const navItems = [
  { icon: Home, labelAr: "\u0627\u0644\u0631\u0626\u064A\u0633\u064A\u0629", labelEn: "Home", path: "/" },
  { icon: Briefcase, labelAr: "\u0627\u0644\u062E\u062F\u0645\u0627\u062A", labelEn: "Services", path: "/services" },
  { icon: FolderOpen, labelAr: "\u0623\u0639\u0645\u0627\u0644\u0646\u0627", labelEn: "Portfolio", path: "/portfolio" },
  { icon: Send, labelAr: "\u0627\u0628\u062F\u0623 \u0645\u0634\u0631\u0648\u0639\u0643", labelEn: "Start Project", path: "/start-project" }
];
const BottomNavBar = () => {
  const location = useLocation();
  const { t, isRTL } = useLanguage();
  return /* @__PURE__ */ jsx(
    "nav",
    {
      dir: isRTL ? "rtl" : "ltr",
      className: "fixed bottom-0 inset-x-0 z-50 border-t border-border backdrop-blur-xl md:hidden",
      style: { background: "hsl(var(--background) / 0.75)", paddingBottom: "env(safe-area-inset-bottom)" },
      children: /* @__PURE__ */ jsxs("div", { className: "grid grid-cols-5 gap-1 px-2 py-2", children: [
        navItems.map((item) => {
          const isActive = location.pathname === item.path;
          const Icon = item.icon;
          return /* @__PURE__ */ jsxs(
            Link,
            {
              to: item.path,
              className: cn(
                "flex flex-col items-center justify-center gap-1 rounded-xl py-1.5 transition-colors duration-200 ease-ds",
                isActive ? "text-primary" : "text-muted-foreground hover:text-foreground"
              ),
              children: [
                /* @__PURE__ */ jsx(Icon, { className: "h-[22px] w-[22px]", strokeWidth: 1.75 }),
                /* @__PURE__ */ jsx("span", { className: "text-[10px] font-medium leading-none", children: t(item.labelAr, item.labelEn) }),
                /* @__PURE__ */ jsx(
                  "span",
                  {
                    "aria-hidden": true,
                    className: cn(
                      "h-1 w-1 rounded-full bg-primary transition-opacity duration-200",
                      isActive ? "opacity-100" : "opacity-0"
                    )
                  }
                )
              ]
            },
            item.path
          );
        }),
        /* @__PURE__ */ jsxs(
          "a",
          {
            href: whatsappUrl,
            target: "_blank",
            rel: "noopener noreferrer",
            className: "flex flex-col items-center justify-center gap-1 rounded-xl py-1.5 text-[#25D366] transition-opacity duration-200 ease-ds hover:opacity-80",
            children: [
              /* @__PURE__ */ jsx(WhatsappIcon, { className: "h-[22px] w-[22px]" }),
              /* @__PURE__ */ jsx("span", { className: "text-[10px] font-medium leading-none", children: t("\u0648\u0627\u062A\u0633\u0627\u0628", "WhatsApp") }),
              /* @__PURE__ */ jsx("span", { "aria-hidden": true, className: "h-1 w-1 rounded-full opacity-0" })
            ]
          }
        )
      ] })
    }
  );
};
export default BottomNavBar;
