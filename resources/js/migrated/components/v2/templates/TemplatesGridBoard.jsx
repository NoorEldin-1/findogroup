import { jsx, jsxs } from "react/jsx-runtime";
import { useMemo, useState } from "react";
import { Search } from "lucide-react";
import { Board, Chip, IconBadge, RiseIn } from "@/components/v2/primitives";
import { useLanguage } from "@/contexts/LanguageContext";
import {
  templateFilters
} from "@/data/templates";
import { useMarketingTemplates } from "@/hooks/useMarketingTemplates";
import TemplateCard from "./TemplateCard";
import TemplateDownloadModal from "./TemplateDownloadModal";
const TemplatesGridBoard = ({ search }) => {
  const { t, isRTL } = useLanguage();
  const templates = useMarketingTemplates();
  const [filter, setFilter] = useState("all");
  const [activeTemplate, setActiveTemplate] = useState(null);
  const query = search.trim().toLowerCase();
  const filtered = useMemo(() => {
    return templates.filter((tpl) => {
      const matchesCategory = filter === "all" || tpl.category === filter;
      if (!matchesCategory) return false;
      if (!query) return true;
      const haystack = `${tpl.titleAr} ${tpl.titleEn} ${tpl.descAr} ${tpl.descEn}`.toLowerCase();
      return haystack.includes(query);
    });
  }, [templates, filter, query]);
  return /* @__PURE__ */ jsx(RiseIn, { children: /* @__PURE__ */ jsxs(Board, { dir: isRTL ? "rtl" : "ltr", children: [
    /* @__PURE__ */ jsx("div", { className: "mb-8 flex flex-wrap items-center justify-center gap-2.5", children: templateFilters.map((f) => /* @__PURE__ */ jsx(
      Chip,
      {
        interactive: true,
        active: filter === f.key,
        "aria-pressed": filter === f.key,
        onClick: () => setFilter(f.key),
        className: "px-4 py-2 text-sm",
        children: t(f.labelAr, f.labelEn)
      },
      f.key
    )) }),
    filtered.length > 0 ? /* @__PURE__ */ jsx("div", { className: "grid gap-4 sm:grid-cols-2 lg:grid-cols-3", children: filtered.map((tpl) => /* @__PURE__ */ jsx(TemplateCard, { template: tpl, onDownload: setActiveTemplate }, tpl.id)) }) : /* @__PURE__ */ jsxs("div", { className: "py-16 text-center", children: [
      /* @__PURE__ */ jsx(IconBadge, { icon: Search, size: "lg", className: "mx-auto mb-6 rounded-full" }),
      /* @__PURE__ */ jsx("h3", { className: "mb-2 text-xl font-bold text-foreground", children: t("\u0644\u0627 \u062A\u0648\u062C\u062F \u0642\u0648\u0627\u0644\u0628 \u0645\u0637\u0627\u0628\u0642\u0629", "No matching templates") }),
      /* @__PURE__ */ jsx("p", { className: "text-muted-foreground", children: t("\u062C\u0631\u0651\u0628 \u0643\u0644\u0645\u0629 \u0628\u062D\u062B \u0645\u062E\u062A\u0644\u0641\u0629 \u0623\u0648 \u063A\u064A\u0651\u0631 \u0627\u0644\u062A\u0635\u0646\u064A\u0641.", "Try a different keyword or change the category.") })
    ] }),
    /* @__PURE__ */ jsx(TemplateDownloadModal, { template: activeTemplate, onClose: () => setActiveTemplate(null) })
  ] }) });
};
export default TemplatesGridBoard;
