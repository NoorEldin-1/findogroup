import { jsx, jsxs } from "react/jsx-runtime";
import { Link } from "react-router-dom";
import findoLogo from "@/assets/findo-logo.png.js";
import { useLanguage } from "@/contexts/LanguageContext";
import { Button } from "@/components/ui/button";
import { WhatsappIcon } from "@/components/v2/primitives";
const DEFAULT_WA_HREF = `https://wa.me/201100018259?text=${encodeURIComponent("\u0645\u0631\u062D\u0628\u0627\u064B\u060C \u0623\u0631\u064A\u062F \u0627\u0644\u0627\u0633\u062A\u0641\u0633\u0627\u0631 \u0639\u0646 \u062E\u062F\u0645\u0627\u062A\u0643\u0645")}`;
const MinimalHeader = ({ waHref = DEFAULT_WA_HREF }) => {
  const { t, isRTL } = useLanguage();
  return /* @__PURE__ */ jsx(
    "header",
    {
      dir: isRTL ? "rtl" : "ltr",
      className: "sticky top-0 z-50 border-b border-border backdrop-blur-xl",
      style: { background: "hsl(var(--background) / 0.62)" },
      children: /* @__PURE__ */ jsxs("div", { className: "mx-auto flex h-16 max-w-[1600px] items-center justify-between gap-3 px-4 lg:px-8", children: [
        /* @__PURE__ */ jsx(Link, { to: "/", className: "flex-shrink-0", children: /* @__PURE__ */ jsx(
          "img",
          {
            src: findoLogo,
            alt: "Findo Group",
            width: 200,
            height: 53,
            fetchPriority: "high",
            className: "h-9 w-auto object-contain md:h-10"
          }
        ) }),
        /* @__PURE__ */ jsx(Button, { asChild: true, children: /* @__PURE__ */ jsxs("a", { href: waHref, target: "_blank", rel: "noopener noreferrer", children: [
          /* @__PURE__ */ jsx(WhatsappIcon, {}),
          t("\u062A\u0648\u0627\u0635\u0644 \u0627\u0644\u0622\u0646", "Chat now")
        ] }) })
      ] })
    }
  );
};
export default MinimalHeader;
