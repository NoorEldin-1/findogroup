import { jsx, jsxs } from "react/jsx-runtime";
import { useLanguage } from "@/contexts/LanguageContext";
import { useTheme } from "@/contexts/ThemeContext";
import WhatsAppFloat from "@/components/WhatsAppFloat";
import SiteHeader from "./SiteHeader";
import SiteFooter from "./SiteFooter";
import BottomNavBar from "./BottomNavBar";
import MinimalHeader from "./MinimalHeader";
import bgTextureDark from "@/assets/bg-texture-dark.jpg.js";
import bgTextureLight from "@/assets/bg-texture-light.jpg.js";
const PageShell = ({ children, seo, chrome = "full", contained = true, waHref }) => {
  const { isRTL } = useLanguage();
  const { isDark } = useTheme();
  return /* @__PURE__ */ jsxs("div", { dir: isRTL ? "rtl" : "ltr", children: [
    seo,
    chrome === "full" && /* @__PURE__ */ jsx(SiteHeader, {}),
    chrome === "minimal" && /* @__PURE__ */ jsx(MinimalHeader, { waHref }),
    /* @__PURE__ */ jsxs("main", { className: "relative min-h-screen overflow-x-clip bg-background pb-24 md:pb-8", children: [
      /* @__PURE__ */ jsx(
        "div",
        {
          "aria-hidden": true,
          className: "pointer-events-none fixed inset-0 z-0 bg-cover bg-center opacity-[0.045] dark:opacity-[0.06]",
          style: { backgroundImage: `url(${isDark ? bgTextureDark : bgTextureLight})` }
        }
      ),
      contained ? (
        // Continuous surface: no inter-section gap card — each Board carries its own
        // vertical rhythm; the container just provides the comfortable side gutter.
        /* @__PURE__ */ jsx("div", { className: "relative z-10 mx-auto w-full max-w-[1600px] px-4 pt-2 sm:px-6 lg:px-10", children })
      ) : /* @__PURE__ */ jsx("div", { className: "relative z-10", children })
    ] }),
    chrome !== "none" && /* @__PURE__ */ jsx(SiteFooter, {}),
    chrome !== "none" && /* @__PURE__ */ jsx(WhatsAppFloat, {}),
    chrome === "full" && /* @__PURE__ */ jsx(BottomNavBar, {})
  ] });
};
export default PageShell;
