import { jsx, jsxs } from "react/jsx-runtime";
import { useState } from "react";
import SEO from "@/components/SEO";
import { PageShell } from "@/components/v2/layout";
import {
  PortfolioCtaBoard,
  PortfolioGridBoard,
  PortfolioHeroBoard
} from "@/components/v2/portfolio";
import { projects } from "@/data/projects";
const Portfolio = () => {
  const [filter, setFilter] = useState("all");
  const [searchQuery, setSearchQuery] = useState("");
  const filteredProjects = projects.filter((project) => {
    const matchesFilter = filter === "all" || project.type === filter;
    const matchesSearch = searchQuery === "" || project.title.toLowerCase().includes(searchQuery.toLowerCase()) || project.description.toLowerCase().includes(searchQuery.toLowerCase()) || project.category.toLowerCase().includes(searchQuery.toLowerCase());
    return matchesFilter && matchesSearch;
  });
  const stats = {
    websites: projects.filter((p) => p.type === "website").length,
    apps: projects.filter((p) => p.type === "app").length,
    designs: projects.filter((p) => p.type === "design").length,
    total: projects.length
  };
  return /* @__PURE__ */ jsxs(
    PageShell,
    {
      seo: /* @__PURE__ */ jsx(
        SEO,
        {
          title: "\u0645\u0639\u0631\u0636 \u0623\u0639\u0645\u0627\u0644\u0646\u0627 \u0648\u0645\u0634\u0627\u0631\u064A\u0639\u0646\u0627 \u0627\u0644\u0646\u0627\u062C\u062D\u0629",
          description: "\u0627\u0633\u062A\u0639\u0631\u0636 \u0645\u062C\u0645\u0648\u0639\u0629 \u0645\u0645\u064A\u0632\u0629 \u0645\u0646 \u0645\u0634\u0627\u0631\u064A\u0639\u0646\u0627 \u0627\u0644\u0646\u0627\u062C\u062D\u0629 \u0641\u064A \u062A\u0637\u0648\u064A\u0631 \u0627\u0644\u062A\u0637\u0628\u064A\u0642\u0627\u062A \u0648\u0627\u0644\u0645\u0648\u0627\u0642\u0639 \u0648\u0627\u0644\u062A\u0635\u0627\u0645\u064A\u0645. \u0623\u0643\u062B\u0631 \u0645\u0646 150 \u0645\u0634\u0631\u0648\u0639 \u062A\u0645 \u062A\u0646\u0641\u064A\u0630\u0647 \u0628\u0646\u062C\u0627\u062D \u0644\u0639\u0645\u0644\u0627\u0626\u0646\u0627 \u0628\u0627\u0644\u0648\u0637\u0646 \u0627\u0644\u0639\u0631\u0628\u064A.",
          keywords: "\u0645\u0639\u0631\u0636 \u0623\u0639\u0645\u0627\u0644, \u0645\u0634\u0627\u0631\u064A\u0639 \u0628\u0631\u0645\u062C\u064A\u0629, \u062A\u0637\u0628\u064A\u0642\u0627\u062A \u0633\u0627\u0628\u0642\u0629, \u0645\u0648\u0627\u0642\u0639 \u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A\u0629, \u062A\u0635\u0627\u0645\u064A\u0645 \u0627\u062D\u062A\u0631\u0627\u0641\u064A\u0629, \u0623\u0639\u0645\u0627\u0644 \u0641\u0627\u064A\u0646\u062F\u0648 \u062C\u0631\u0648\u0628, portfolio",
          canonical: "/portfolio"
        }
      ),
      children: [
        /* @__PURE__ */ jsx(PortfolioHeroBoard, { searchQuery, onSearchChange: setSearchQuery }),
        /* @__PURE__ */ jsx(
          PortfolioGridBoard,
          {
            filter,
            onFilterChange: setFilter,
            projects: filteredProjects,
            stats
          }
        ),
        /* @__PURE__ */ jsx(PortfolioCtaBoard, {})
      ]
    }
  );
};
export default Portfolio;
