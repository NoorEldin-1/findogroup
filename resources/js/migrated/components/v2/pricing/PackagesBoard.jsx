import { jsx, jsxs } from "react/jsx-runtime";
import PricingTable from "@/components/PricingTable";
import { Board, BoardHeader, RiseIn } from "@/components/v2/primitives";
import { useLanguage } from "@/contexts/LanguageContext";
const PackagesBoard = () => {
  const { t, isRTL } = useLanguage();
  return /* @__PURE__ */ jsx(RiseIn, { children: /* @__PURE__ */ jsxs(Board, { id: "ready-packages", dir: isRTL ? "rtl" : "ltr", className: "scroll-mt-24", children: [
    /* @__PURE__ */ jsx(
      BoardHeader,
      {
        index: "03",
        eyebrow: t("\u0628\u0627\u0642\u0627\u062A \u0648\u0623\u0633\u0639\u0627\u0631 \u0641\u0627\u064A\u0646\u062F\u0648 \u062C\u0631\u0648\u0628", "Findo Group Pricing"),
        pre: t("\u0627\u0644\u0628\u0627\u0642\u0627\u062A", "Ready-made"),
        accent: t("\u0627\u0644\u062C\u0627\u0647\u0632\u0629", "packages")
      }
    ),
    /* @__PURE__ */ jsx(PricingTable, {})
  ] }) });
};
export default PackagesBoard;
