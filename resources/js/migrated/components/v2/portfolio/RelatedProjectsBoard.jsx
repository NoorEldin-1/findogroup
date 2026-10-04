import { jsx, jsxs } from "react/jsx-runtime";
import { Link } from "react-router-dom";
import { ExternalLink } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Board, BoardHeader, CtaRow, RiseIn } from "@/components/v2/primitives";
import { useLanguage } from "@/contexts/LanguageContext";
import ProjectCardV2 from "./ProjectCardV2";
const RelatedProjectsBoard = ({ projects }) => {
  const { t, isRTL } = useLanguage();
  return /* @__PURE__ */ jsx(RiseIn, { children: /* @__PURE__ */ jsxs(Board, { dir: isRTL ? "rtl" : "ltr", children: [
    /* @__PURE__ */ jsx(
      BoardHeader,
      {
        index: "03",
        eyebrow: t("\u0645\u0634\u0627\u0631\u064A\u0639 \u0645\u0634\u0627\u0628\u0647\u0629", "Similar Projects"),
        pre: t("\u0642\u062F \u062A\u0639\u062C\u0628\u0643", "You May"),
        accent: t("\u0623\u064A\u0636\u0627\u064B", "Also Like")
      }
    ),
    /* @__PURE__ */ jsx("div", { className: "grid gap-4 sm:grid-cols-2 lg:grid-cols-4", children: projects.map((project) => /* @__PURE__ */ jsx(ProjectCardV2, { project }, project.id)) }),
    /* @__PURE__ */ jsx(CtaRow, { className: "justify-center", children: /* @__PURE__ */ jsx(Button, { asChild: true, size: "lg", children: /* @__PURE__ */ jsxs(Link, { to: "/portfolio", children: [
      t("\u0639\u0631\u0636 \u062C\u0645\u064A\u0639 \u0627\u0644\u0645\u0634\u0627\u0631\u064A\u0639", "View All Projects"),
      /* @__PURE__ */ jsx(ExternalLink, { strokeWidth: 1.75 })
    ] }) }) })
  ] }) });
};
export default RelatedProjectsBoard;
