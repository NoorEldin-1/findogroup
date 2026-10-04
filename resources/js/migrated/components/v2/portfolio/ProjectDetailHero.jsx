import { jsx, jsxs } from "react/jsx-runtime";
import { Link } from "react-router-dom";
import { ArrowLeft, ArrowRight, Calendar, Code2, Sparkles, Tag } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Board, Chip, CtaRow, RiseIn, StatTile } from "@/components/v2/primitives";
import { useLanguage } from "@/contexts/LanguageContext";
import { getTypeName, typeIcons } from "./projectMeta";
const ProjectDetailHero = ({ project }) => {
  const { t, isRTL } = useLanguage();
  const ArrowIcon = isRTL ? ArrowRight : ArrowLeft;
  const TypeIcon = typeIcons[project.type];
  const typeName = getTypeName(t, project.type);
  return /* @__PURE__ */ jsx(RiseIn, { mount: true, children: /* @__PURE__ */ jsxs(Board, { dir: isRTL ? "rtl" : "ltr", children: [
    /* @__PURE__ */ jsxs("nav", { className: "mb-8 flex flex-wrap items-center gap-2 text-sm text-muted-foreground", children: [
      /* @__PURE__ */ jsx(Link, { to: "/", className: "transition-colors duration-200 ease-ds hover:text-primary", children: t("\u0627\u0644\u0631\u0626\u064A\u0633\u064A\u0629", "Home") }),
      /* @__PURE__ */ jsx("span", { "aria-hidden": true, className: "opacity-40", children: "/" }),
      /* @__PURE__ */ jsx(Link, { to: "/portfolio", className: "transition-colors duration-200 ease-ds hover:text-primary", children: t("\u0645\u0639\u0631\u0636 \u0627\u0644\u0623\u0639\u0645\u0627\u0644", "Portfolio") }),
      /* @__PURE__ */ jsx("span", { "aria-hidden": true, className: "opacity-40", children: "/" }),
      /* @__PURE__ */ jsx("span", { className: "text-foreground", children: project.title })
    ] }),
    /* @__PURE__ */ jsxs("div", { className: "grid items-center gap-10 lg:grid-cols-2 lg:gap-14", children: [
      /* @__PURE__ */ jsx("div", { className: "rounded-3xl border border-border bg-popover p-3", children: /* @__PURE__ */ jsxs("div", { className: "relative overflow-hidden rounded-2xl", children: [
        /* @__PURE__ */ jsx(
          "img",
          {
            src: project.image,
            alt: project.title,
            decoding: "async",
            className: "aspect-[4/3] w-full object-cover"
          }
        ),
        /* @__PURE__ */ jsx(
          Chip,
          {
            variant: "featured",
            className: "absolute bottom-3 start-3 shadow-lg",
            icon: /* @__PURE__ */ jsx(TypeIcon, { className: "h-4 w-4", strokeWidth: 1.75 }),
            children: typeName
          }
        )
      ] }) }),
      /* @__PURE__ */ jsxs("div", { children: [
        /* @__PURE__ */ jsx(Chip, { className: "mb-6", icon: /* @__PURE__ */ jsx(Tag, { className: "h-4 w-4 text-primary", strokeWidth: 1.75 }), children: project.category }),
        /* @__PURE__ */ jsx("h1", { className: "text-3xl sm:text-4xl md:text-5xl font-bold text-foreground leading-tight", children: project.title }),
        /* @__PURE__ */ jsx("p", { className: "mt-5 max-w-xl text-base leading-relaxed text-muted-foreground md:text-lg", children: project.description }),
        /* @__PURE__ */ jsxs("div", { className: "mt-8 grid grid-cols-2 gap-3", children: [
          /* @__PURE__ */ jsx(
            StatTile,
            {
              icon: /* @__PURE__ */ jsx(TypeIcon, { className: "h-5 w-5", strokeWidth: 1.75 }),
              value: typeName,
              label: t("\u0646\u0648\u0639 \u0627\u0644\u0645\u0634\u0631\u0648\u0639", "Project Type")
            }
          ),
          /* @__PURE__ */ jsx(
            StatTile,
            {
              icon: /* @__PURE__ */ jsx(Calendar, { className: "h-5 w-5", strokeWidth: 1.75 }),
              value: project.category,
              label: t("\u0627\u0644\u062A\u0635\u0646\u064A\u0641", "Category")
            }
          )
        ] }),
        /* @__PURE__ */ jsxs("div", { className: "mt-4 rounded-3xl border border-border bg-popover p-5", children: [
          /* @__PURE__ */ jsxs("div", { className: "mb-4 flex items-center gap-2 text-primary", children: [
            /* @__PURE__ */ jsx(Code2, { className: "h-5 w-5", strokeWidth: 1.75 }),
            /* @__PURE__ */ jsx("span", { className: "font-bold", children: t("\u0627\u0644\u062A\u0642\u0646\u064A\u0627\u062A \u0627\u0644\u0645\u0633\u062A\u062E\u062F\u0645\u0629", "Technologies Used") })
          ] }),
          /* @__PURE__ */ jsx("div", { className: "flex flex-wrap gap-2", children: project.technologies.map((tech) => /* @__PURE__ */ jsx(Chip, { className: "text-muted-foreground", children: tech }, tech)) })
        ] }),
        /* @__PURE__ */ jsxs(CtaRow, { children: [
          /* @__PURE__ */ jsx(Button, { asChild: true, size: "lg", children: /* @__PURE__ */ jsxs(Link, { to: "/contact", children: [
            t("\u0627\u0637\u0644\u0628 \u0645\u0634\u0631\u0648\u0639 \u0645\u0634\u0627\u0628\u0647", "Request Similar Project"),
            /* @__PURE__ */ jsx(Sparkles, { strokeWidth: 1.75 })
          ] }) }),
          /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", size: "lg", children: /* @__PURE__ */ jsxs(Link, { to: "/portfolio", children: [
            /* @__PURE__ */ jsx(ArrowIcon, { strokeWidth: 1.75 }),
            t("\u0639\u0631\u0636 \u062C\u0645\u064A\u0639 \u0627\u0644\u0645\u0634\u0627\u0631\u064A\u0639", "View All Projects")
          ] }) })
        ] })
      ] })
    ] })
  ] }) });
};
export default ProjectDetailHero;
