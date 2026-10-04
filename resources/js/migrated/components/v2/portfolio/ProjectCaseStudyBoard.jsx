import { jsx, jsxs } from "react/jsx-runtime";
import { AlertCircle, CheckCircle2, Lightbulb } from "lucide-react";
import { cn } from "@/lib/utils";
import { Board, BoardHeader, RiseIn } from "@/components/v2/primitives";
import { useLanguage } from "@/contexts/LanguageContext";
const ProjectCaseStudyBoard = ({ caseStudy }) => {
  const { t, isRTL } = useLanguage();
  if (!caseStudy) return null;
  const cards = [
    {
      icon: AlertCircle,
      label: t("\u0627\u0644\u0645\u0634\u0643\u0644\u0629", "The Problem"),
      text: caseStudy.problem,
      badge: "border-destructive/40 bg-destructive/15 text-destructive",
      title: "text-destructive"
    },
    {
      icon: Lightbulb,
      label: t("\u0627\u0644\u062D\u0644", "Our Solution"),
      text: caseStudy.solution,
      badge: "border-warning/40 bg-warning/15 text-warning",
      title: "text-warning"
    },
    {
      icon: CheckCircle2,
      label: t("\u0627\u0644\u0646\u062A\u064A\u062C\u0629", "The Result"),
      text: caseStudy.results,
      badge: "border-success/40 bg-success/15 text-success",
      title: "text-success"
    }
  ];
  return /* @__PURE__ */ jsx(RiseIn, { children: /* @__PURE__ */ jsxs(Board, { dir: isRTL ? "rtl" : "ltr", children: [
    /* @__PURE__ */ jsx(
      BoardHeader,
      {
        index: "02",
        eyebrow: t("\u0642\u0635\u0629 \u0627\u0644\u0645\u0634\u0631\u0648\u0639", "Case study"),
        pre: t("\u0625\u0632\u0627\u064A \u0627\u0634\u062A\u063A\u0644\u0646\u0627", "How we"),
        accent: t("\u0639\u0644\u0649 \u0627\u0644\u0645\u0634\u0631\u0648\u0639", "approached it")
      }
    ),
    /* @__PURE__ */ jsx("div", { className: "grid gap-4 md:grid-cols-3", children: cards.map((card) => {
      const Icon = card.icon;
      return /* @__PURE__ */ jsxs("div", { className: "rounded-3xl border border-border bg-popover p-5 md:p-6", children: [
        /* @__PURE__ */ jsx(
          "span",
          {
            className: cn(
              "mb-4 inline-flex h-11 w-11 items-center justify-center rounded-2xl border",
              card.badge
            ),
            children: /* @__PURE__ */ jsx(Icon, { className: "h-[22px] w-[22px]", strokeWidth: 1.75 })
          }
        ),
        /* @__PURE__ */ jsx("h3", { className: cn("mb-2 font-bold", card.title), children: card.label }),
        /* @__PURE__ */ jsx("p", { className: "text-sm leading-relaxed text-muted-foreground md:text-[15px]", children: card.text })
      ] }, card.label);
    }) })
  ] }) });
};
export default ProjectCaseStudyBoard;
