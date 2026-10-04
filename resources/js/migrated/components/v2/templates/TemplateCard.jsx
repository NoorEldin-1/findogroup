import { jsx, jsxs } from "react/jsx-runtime";
import { Download } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Chip } from "@/components/v2/primitives";
import { useLanguage } from "@/contexts/LanguageContext";
import { categoryTag, fileTypeIcon } from "@/data/templates";
const TemplateCard = ({ template, onDownload }) => {
  const { t } = useLanguage();
  const Icon = template.icon;
  const FileIcon = fileTypeIcon[template.fileType];
  const tag = categoryTag[template.category];
  const fileBadge = /* @__PURE__ */ jsxs("span", { className: "absolute end-3 top-3 inline-flex items-center gap-1 rounded-full bg-black/30 px-2.5 py-1 text-[11px] font-bold text-white backdrop-blur-sm", children: [
    /* @__PURE__ */ jsx(FileIcon, { className: "h-3.5 w-3.5", strokeWidth: 1.75 }),
    template.fileType
  ] });
  return /* @__PURE__ */ jsxs("div", { className: "group flex h-full flex-col overflow-hidden rounded-3xl border border-border bg-popover shadow-card transition-all duration-200 ease-ds hover:-translate-y-0.5 hover:border-primary/40", children: [
    template.imageUrl ? /* @__PURE__ */ jsxs("div", { className: "relative h-40 overflow-hidden border-b border-border bg-white", children: [
      /* @__PURE__ */ jsx(
        "img",
        {
          src: template.imageUrl,
          alt: t(template.titleAr, template.titleEn),
          loading: "lazy",
          decoding: "async",
          className: "h-full w-full object-cover transition-transform duration-300 ease-ds group-hover:scale-105"
        }
      ),
      fileBadge
    ] }) : /* @__PURE__ */ jsxs("div", { className: "relative flex h-40 items-center justify-center overflow-hidden border-b border-border bg-gradient-to-br from-primary via-[#FF9C50] to-[#E86F1E]", children: [
      /* @__PURE__ */ jsx("div", { "aria-hidden": true, className: "pointer-events-none absolute -right-8 -top-10 h-32 w-32 rounded-full bg-white/15 blur-xl" }),
      /* @__PURE__ */ jsx("div", { "aria-hidden": true, className: "pointer-events-none absolute -left-6 -bottom-8 h-24 w-24 rounded-full bg-white/10" }),
      /* @__PURE__ */ jsx(
        "div",
        {
          "aria-hidden": true,
          className: "pointer-events-none absolute inset-0",
          style: { background: "radial-gradient(120% 100% at 50% 0%, rgba(255,255,255,0.22), transparent 55%)" }
        }
      ),
      /* @__PURE__ */ jsx(
        Icon,
        {
          className: "relative h-16 w-16 text-white drop-shadow-sm transition-transform duration-300 ease-ds group-hover:scale-110",
          strokeWidth: 1.5
        }
      ),
      fileBadge
    ] }),
    /* @__PURE__ */ jsxs("div", { className: "flex flex-1 flex-col p-5", children: [
      /* @__PURE__ */ jsx(Chip, { className: "mb-3 self-start text-muted-foreground", children: t(tag.ar, tag.en) }),
      /* @__PURE__ */ jsx("h3", { className: "mb-2 font-display text-base font-bold leading-snug text-foreground", children: t(template.titleAr, template.titleEn) }),
      /* @__PURE__ */ jsx("p", { className: "mb-5 line-clamp-2 text-sm leading-relaxed text-muted-foreground", children: t(template.descAr, template.descEn) }),
      /* @__PURE__ */ jsxs(Button, { type: "button", variant: "outline", onClick: () => onDownload(template), className: "mt-auto w-full", children: [
        /* @__PURE__ */ jsx(Download, { strokeWidth: 1.75 }),
        t("\u062A\u062D\u0645\u064A\u0644 \u0645\u062C\u0627\u0646\u064A", "Download free")
      ] })
    ] })
  ] });
};
export default TemplateCard;
