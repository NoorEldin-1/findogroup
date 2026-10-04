import { jsx, jsxs } from "react/jsx-runtime";
import { Link } from "react-router-dom";
import { Sparkles } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Board, CtaRow, IconBadge, RiseIn } from "@/components/v2/primitives";
import { useLanguage } from "@/contexts/LanguageContext";
const ProjectCtaBoard = () => {
  const { t, isRTL } = useLanguage();
  return /* @__PURE__ */ jsx(RiseIn, { children: /* @__PURE__ */ jsx(Board, { dir: isRTL ? "rtl" : "ltr", children: /* @__PURE__ */ jsxs("div", { className: "mx-auto max-w-2xl text-center", children: [
    /* @__PURE__ */ jsx(IconBadge, { icon: Sparkles, variant: "gradient", size: "lg", className: "mx-auto mb-6 rounded-full" }),
    /* @__PURE__ */ jsxs("h2", { className: "font-display text-3xl font-extrabold tracking-tight text-foreground md:text-4xl", children: [
      t("\u0647\u0644 \u062A\u0631\u064A\u062F", "Want a"),
      " ",
      /* @__PURE__ */ jsx("span", { className: "text-primary", children: t("\u0645\u0634\u0631\u0648\u0639 \u0645\u0634\u0627\u0628\u0647\u061F", "Similar Project?") })
    ] }),
    /* @__PURE__ */ jsx("p", { className: "mx-auto mt-4 max-w-xl leading-relaxed text-muted-foreground", children: t(
      "\u062A\u0648\u0627\u0635\u0644 \u0645\u0639\u0646\u0627 \u0627\u0644\u0622\u0646 \u0648\u0633\u0646\u0633\u0627\u0639\u062F\u0643 \u0641\u064A \u062A\u062D\u0648\u064A\u0644 \u0641\u0643\u0631\u062A\u0643 \u0625\u0644\u0649 \u0648\u0627\u0642\u0639 \u0631\u0642\u0645\u064A \u0645\u0645\u064A\u0632",
      "Contact us now and we'll help you transform your idea into an outstanding digital reality"
    ) }),
    /* @__PURE__ */ jsx(CtaRow, { className: "justify-center", children: /* @__PURE__ */ jsx(Button, { asChild: true, size: "lg", children: /* @__PURE__ */ jsxs(Link, { to: "/contact", children: [
      t("\u062A\u0648\u0627\u0635\u0644 \u0645\u0639\u0646\u0627 \u0627\u0644\u0622\u0646", "Contact Us Now"),
      /* @__PURE__ */ jsx(Sparkles, { strokeWidth: 1.75 })
    ] }) }) })
  ] }) }) });
};
export default ProjectCtaBoard;
