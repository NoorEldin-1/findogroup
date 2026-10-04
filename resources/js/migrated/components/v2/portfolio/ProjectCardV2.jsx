import { jsx, jsxs } from "react/jsx-runtime";
import { Link } from "react-router-dom";
import { ArrowLeft, ArrowRight } from "lucide-react";
import { Chip } from "@/components/v2/primitives";
import { useLanguage } from "@/contexts/LanguageContext";
import { typeLabels } from "./projectMeta";
const ProjectCardV2 = ({ project }) => {
  const { t, isRTL } = useLanguage();
  const ArrowIcon = isRTL ? ArrowLeft : ArrowRight;
  const label = typeLabels[project.type];
  return /* @__PURE__ */ jsxs(
    Link,
    {
      to: `/portfolio/${project.id}`,
      className: "group flex h-full flex-col overflow-hidden rounded-3xl border border-border bg-popover transition-all duration-200 ease-ds hover:-translate-y-1 hover:border-primary/40",
      children: [
        /* @__PURE__ */ jsxs("div", { className: "relative aspect-[16/10] overflow-hidden", children: [
          /* @__PURE__ */ jsx(
            "img",
            {
              src: project.image,
              alt: project.title,
              loading: "lazy",
              decoding: "async",
              className: "h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
            }
          ),
          label && /* @__PURE__ */ jsx(Chip, { variant: "featured", className: "absolute top-3 start-3 shadow-lg", children: t(label.ar, label.en) })
        ] }),
        /* @__PURE__ */ jsxs("div", { className: "flex grow flex-col p-5", children: [
          /* @__PURE__ */ jsx("h3", { className: "text-lg font-bold text-foreground transition-colors duration-200 ease-ds group-hover:text-primary", children: project.title }),
          /* @__PURE__ */ jsx("p", { className: "mt-2 line-clamp-2 grow text-sm leading-relaxed text-muted-foreground", children: project.description }),
          /* @__PURE__ */ jsxs("span", { className: "mt-4 inline-flex items-center gap-1.5 border-t border-border pt-4 text-sm font-semibold text-primary transition-all duration-200 ease-ds group-hover:gap-3", children: [
            t("\u0639\u0631\u0636 \u0627\u0644\u062A\u0641\u0627\u0635\u064A\u0644", "View Details"),
            /* @__PURE__ */ jsx(ArrowIcon, { className: "h-4 w-4", strokeWidth: 1.75 })
          ] })
        ] })
      ]
    }
  );
};
export default ProjectCardV2;
