import { jsx, jsxs } from "react/jsx-runtime";
import PartnershipForm from "@/components/PartnershipForm";
import { Board, BoardHeader, RiseIn } from "@/components/v2/primitives";
import { useLanguage } from "@/contexts/LanguageContext";
const PartnerFormBoard = () => {
  const { t, isRTL } = useLanguage();
  return /* @__PURE__ */ jsx(RiseIn, { children: /* @__PURE__ */ jsxs(Board, { id: "partner-form", dir: isRTL ? "rtl" : "ltr", className: "scroll-mt-24", children: [
    /* @__PURE__ */ jsx(
      BoardHeader,
      {
        index: "06",
        eyebrow: t("\u0627\u0646\u0636\u0645 \u0625\u0644\u064A\u0646\u0627", "Join us"),
        pre: t("\u0633\u062C\u0651\u0644 \u0627\u0647\u062A\u0645\u0627\u0645\u0643 \u0628\u0627\u0644\u0634\u0631\u0627\u0643\u0629", "Register your partnership interest"),
        subtitle: t(
          "\u0627\u0645\u0644\u0623 \u0627\u0644\u0646\u0645\u0648\u0630\u062C \u0628\u062A\u0641\u0627\u0635\u064A\u0644 \u0634\u0631\u0643\u062A\u0643 \u0648\u0645\u062C\u0627\u0644 \u0639\u0645\u0644\u0643\u060C \u0648\u0633\u064A\u062A\u0648\u0627\u0635\u0644 \u0645\u0639\u0643 \u0641\u0631\u064A\u0642 \u0627\u0644\u0634\u0631\u0627\u0643\u0627\u062A \u062E\u0644\u0627\u0644 24 \u0633\u0627\u0639\u0629.",
          "Fill out the form with your company details and field of work \u2014 our partnerships team will contact you within 24 hours."
        )
      }
    ),
    /* @__PURE__ */ jsx(PartnershipForm, {})
  ] }) });
};
export default PartnerFormBoard;
