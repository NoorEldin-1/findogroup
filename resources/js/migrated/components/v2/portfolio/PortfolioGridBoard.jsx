import { jsx, jsxs } from "react/jsx-runtime";
import { Monitor, Palette, Search, Smartphone } from "lucide-react";
import { Board, Chip, IconBadge, RiseIn, StatTile } from "@/components/v2/primitives";
import { useLanguage } from "@/contexts/LanguageContext";
import ProjectCardV2 from "./ProjectCardV2";
const PortfolioGridBoard = ({ filter, onFilterChange, projects, stats }) => {
  const { t, isRTL } = useLanguage();
  const filterButtons = [
    { key: "all", label: t("\u062C\u0645\u064A\u0639 \u0627\u0644\u0623\u0639\u0645\u0627\u0644", "All Projects"), icon: null },
    { key: "website", label: t("\u0627\u0644\u0645\u0648\u0627\u0642\u0639", "Websites"), icon: Monitor },
    { key: "app", label: t("\u0627\u0644\u062A\u0637\u0628\u064A\u0642\u0627\u062A", "Apps"), icon: Smartphone },
    { key: "design", label: t("\u0627\u0644\u062A\u0635\u0645\u064A\u0645", "Design"), icon: Palette }
  ];
  const statTiles = [
    { value: stats.total, label: t("\u062C\u0645\u064A\u0639 \u0627\u0644\u0623\u0639\u0645\u0627\u0644", "All Projects") },
    { value: stats.websites, label: t("\u0627\u0644\u0645\u0648\u0627\u0642\u0639", "Websites") },
    { value: stats.apps, label: t("\u0627\u0644\u062A\u0637\u0628\u064A\u0642\u0627\u062A", "Apps") },
    { value: stats.designs, label: t("\u0627\u0644\u062A\u0635\u0645\u064A\u0645", "Design") }
  ];
  return /* @__PURE__ */ jsx(RiseIn, { children: /* @__PURE__ */ jsxs(Board, { dir: isRTL ? "rtl" : "ltr", children: [
    /* @__PURE__ */ jsx("div", { className: "mb-8 flex flex-wrap items-center justify-center gap-2.5", children: filterButtons.map(({ key, label, icon: Icon }) => /* @__PURE__ */ jsx(
      Chip,
      {
        interactive: true,
        active: filter === key,
        "aria-pressed": filter === key,
        onClick: () => onFilterChange(key),
        icon: Icon ? /* @__PURE__ */ jsx(Icon, { className: "h-4 w-4", strokeWidth: 1.75 }) : void 0,
        className: "px-4 py-2 text-sm",
        children: label
      },
      key
    )) }),
    projects.length > 0 && /* @__PURE__ */ jsx("div", { className: "grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4", children: projects.map((project) => /* @__PURE__ */ jsx(ProjectCardV2, { project }, project.id)) }),
    projects.length === 0 && /* @__PURE__ */ jsxs("div", { className: "py-16 text-center", children: [
      /* @__PURE__ */ jsx(IconBadge, { icon: Search, size: "lg", className: "mx-auto mb-6 rounded-full" }),
      /* @__PURE__ */ jsx("h3", { className: "mb-2 text-xl font-bold text-foreground", children: t("\u0644\u0627 \u062A\u0648\u062C\u062F \u0646\u062A\u0627\u0626\u062C", "No Results Found") }),
      /* @__PURE__ */ jsx("p", { className: "text-muted-foreground", children: t("\u062C\u0631\u0628 \u0627\u0644\u0628\u062D\u062B \u0628\u0643\u0644\u0645\u0627\u062A \u0645\u062E\u062A\u0644\u0641\u0629 \u0623\u0648 \u062A\u063A\u064A\u064A\u0631 \u0627\u0644\u0641\u0644\u062A\u0631", "Try searching with different keywords or changing the filter") })
    ] }),
    /* @__PURE__ */ jsx("div", { className: "mt-10 grid grid-cols-2 gap-3 md:grid-cols-4", children: statTiles.map((stat) => /* @__PURE__ */ jsx(StatTile, { value: stat.value, label: stat.label, accent: true }, stat.label)) })
  ] }) });
};
export default PortfolioGridBoard;
