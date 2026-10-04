import { jsx, jsxs } from "react/jsx-runtime";
import { Helmet } from "react-helmet";
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion";
import { useLanguage } from "@/contexts/LanguageContext";
import Board from "./Board";
import BoardHeader from "./BoardHeader";
import RiseIn from "./RiseIn";
const FaqBoard = ({ items, index, eyebrow, pre, accent, post, id }) => {
  const { t } = useLanguage();
  const jsonLd = {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    mainEntity: items.map((it) => ({
      "@type": "Question",
      name: it.q,
      acceptedAnswer: { "@type": "Answer", text: it.a }
    }))
  };
  return /* @__PURE__ */ jsx(RiseIn, { children: /* @__PURE__ */ jsxs(Board, { id, children: [
    /* @__PURE__ */ jsx(Helmet, { children: /* @__PURE__ */ jsx("script", { type: "application/ld+json", children: JSON.stringify(jsonLd) }) }),
    /* @__PURE__ */ jsx(
      BoardHeader,
      {
        index,
        eyebrow: eyebrow ?? t("\u0627\u0644\u0623\u0633\u0626\u0644\u0629 \u0627\u0644\u0634\u0627\u0626\u0639\u0629", "FAQ"),
        pre: pre ?? t("\u0623\u0633\u0626\u0644\u0629", "Frequently asked"),
        accent: accent ?? t("\u064A\u062A\u0643\u0631\u0651\u0631 \u0637\u0631\u062D\u0647\u0627", "questions"),
        post
      }
    ),
    /* @__PURE__ */ jsx(Accordion, { type: "single", collapsible: true, className: "space-y-3", children: items.map((it, i) => /* @__PURE__ */ jsxs(
      AccordionItem,
      {
        value: `faq-${i}`,
        className: "rounded-2xl border border-border bg-popover px-5 transition-colors data-[state=open]:border-primary/40",
        children: [
          /* @__PURE__ */ jsx(AccordionTrigger, { className: "gap-3 py-5 text-start text-base font-semibold text-foreground hover:no-underline [&>svg]:text-primary", children: it.q }),
          /* @__PURE__ */ jsx(AccordionContent, { className: "pb-5 leading-relaxed text-muted-foreground", children: it.a })
        ]
      },
      i
    )) })
  ] }) });
};
export default FaqBoard;
