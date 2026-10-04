import { jsx, jsxs } from "react/jsx-runtime";
import ServiceRequestForm from "@/components/ServiceRequestForm";
import { Board, BoardHeader, RiseIn } from "@/components/v2/primitives";
import { useLanguage } from "@/contexts/LanguageContext";
const QuoteBoard = () => {
  const { t, isRTL } = useLanguage();
  return /* @__PURE__ */ jsx(RiseIn, { children: /* @__PURE__ */ jsxs(Board, { id: "request-form", dir: isRTL ? "rtl" : "ltr", className: "scroll-mt-24", children: [
    /* @__PURE__ */ jsx(
      BoardHeader,
      {
        index: "07",
        eyebrow: t("\u0645\u0634\u0631\u0648\u0639 \u0643\u0628\u064A\u0631\u061F", "Bigger project?"),
        pre: t("\u0627\u0637\u0644\u0628 \u0639\u0631\u0636 \u0633\u0639\u0631", "Request a"),
        accent: t("\u0645\u062E\u0635\u0651\u0635", "custom"),
        post: t("", "quote"),
        subtitle: t(
          "\u0644\u0644\u0645\u0634\u0627\u0631\u064A\u0639 \u0627\u0644\u0643\u0628\u064A\u0631\u0629 \u0623\u0648 Enterprise \u2014 \u0627\u062A\u0631\u0643 \u0628\u064A\u0627\u0646\u0627\u062A\u0643 \u0648\u0633\u0646\u0631\u0633\u0644 \u0644\u0643 \u0639\u0631\u0636\u064B\u0627 \u0648\u0627\u0636\u062D\u064B\u0627 \u0628\u0627\u0644\u0646\u0637\u0627\u0642 \u0648\u0627\u0644\u0645\u062F\u0629 \u0648\u0627\u0644\u062A\u0643\u0644\u0641\u0629 \u062E\u0644\u0627\u0644 24 \u0633\u0627\u0639\u0629.",
          "For large or Enterprise projects \u2014 leave your details and we'll send a clear quote (scope, timeline and cost) within 24 hours."
        )
      }
    ),
    /* @__PURE__ */ jsx(ServiceRequestForm, { source: "pricing-page" })
  ] }) });
};
export default QuoteBoard;
