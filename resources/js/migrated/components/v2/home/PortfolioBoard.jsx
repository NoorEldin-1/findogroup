import { jsx, jsxs } from "react/jsx-runtime";
import { Link } from "react-router-dom";
import { ArrowLeft, ArrowRight } from "lucide-react";
import { projects } from "@/data/projects";
import { Button } from "@/components/ui/button";
import { Board, BoardHeader, Chip, CtaRow, RiseIn, StatTile } from "@/components/v2/primitives";
import { Carousel, CarouselContent, CarouselItem, CarouselNext, CarouselPrevious } from "@/components/ui/carousel";
import Autoplay from "embla-carousel-autoplay";
import { useLanguage } from "@/contexts/LanguageContext";
const typeLabels = {
  website: { ar: "\u0627\u0644\u0645\u0648\u0627\u0642\u0639", en: "Websites" },
  app: { ar: "\u0627\u0644\u062A\u0637\u0628\u064A\u0642\u0627\u062A", en: "Apps" },
  design: { ar: "\u0627\u0644\u062A\u0635\u0645\u064A\u0645", en: "Design" }
};
const stats = [
  { number: "+50", labelAr: "\u0645\u0648\u0642\u0639 \u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A", labelEn: "Websites" },
  { number: "+30", labelAr: "\u062A\u0637\u0628\u064A\u0642 \u062C\u0648\u0627\u0644", labelEn: "Mobile Apps" },
  { number: "+100", labelAr: "\u0639\u0645\u064A\u0644 \u0633\u0639\u064A\u062F", labelEn: "Happy Clients" },
  { number: "+5", labelAr: "\u0633\u0646\u0648\u0627\u062A \u062E\u0628\u0631\u0629", labelEn: "Years Experience" }
];
const PortfolioBoard = () => {
  const { t, isRTL } = useLanguage();
  const ArrowIcon = isRTL ? ArrowLeft : ArrowRight;
  const displayedProjects = projects.slice(0, 9);
  return /* @__PURE__ */ jsx(RiseIn, { children: /* @__PURE__ */ jsxs(Board, { id: "portfolio", dir: isRTL ? "rtl" : "ltr", children: [
    /* @__PURE__ */ jsx(
      BoardHeader,
      {
        index: "04",
        eyebrow: t("\u0645\u0639\u0631\u0636 \u0623\u0639\u0645\u0627\u0644\u0646\u0627", "Our Portfolio"),
        pre: t("\u0645\u0634\u0627\u0631\u064A\u0639", "Projects We Are"),
        accent: t("\u0646\u0641\u062A\u062E\u0631 \u0628\u0647\u0627", "Proud Of"),
        subtitle: t(
          "\u0646\u0645\u0627\u0630\u062C \u0645\u0646 \u0623\u0639\u0645\u0627\u0644\u0646\u0627 \u0627\u0644\u0633\u0627\u0628\u0642\u0629 \u0627\u0644\u062A\u064A \u062A\u0639\u0643\u0633 \u062E\u0628\u0631\u062A\u0646\u0627 \u0648\u062C\u0648\u062F\u0629 \u0639\u0645\u0644\u0646\u0627",
          "Examples of our previous work that reflect our expertise and quality"
        )
      }
    ),
    /* @__PURE__ */ jsxs(
      Carousel,
      {
        opts: { align: "start", loop: true, direction: isRTL ? "rtl" : "ltr" },
        plugins: [Autoplay({ delay: 4500, stopOnInteraction: true })],
        className: "w-full",
        children: [
          /* @__PURE__ */ jsx(CarouselContent, { className: isRTL ? "-mr-4" : "-ml-4", children: displayedProjects.map((project) => {
            const label = typeLabels[project.type];
            return /* @__PURE__ */ jsx(CarouselItem, { className: `${isRTL ? "pr-4" : "pl-4"} basis-[85%] sm:basis-1/2 lg:basis-1/3`, children: /* @__PURE__ */ jsxs(
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
            ) }, project.id);
          }) }),
          /* @__PURE__ */ jsx(CarouselPrevious, { className: "hidden md:flex -left-4 lg:-left-8 border-border bg-popover text-foreground hover:border-primary/50 hover:bg-primary/15 hover:text-primary" }),
          /* @__PURE__ */ jsx(CarouselNext, { className: "hidden md:flex -right-4 lg:-right-8 border-border bg-popover text-foreground hover:border-primary/50 hover:bg-primary/15 hover:text-primary" })
        ]
      }
    ),
    /* @__PURE__ */ jsx(CtaRow, { className: "justify-center", children: /* @__PURE__ */ jsx(Button, { asChild: true, size: "lg", children: /* @__PURE__ */ jsxs(Link, { to: "/portfolio", children: [
      t("\u0639\u0631\u0636 \u062C\u0645\u064A\u0639 \u0627\u0644\u0645\u0634\u0627\u0631\u064A\u0639", "View All Projects"),
      /* @__PURE__ */ jsx(ArrowIcon, { strokeWidth: 1.75 })
    ] }) }) }),
    /* @__PURE__ */ jsx("div", { className: "mt-10 grid grid-cols-2 gap-3 md:grid-cols-4", children: stats.map((stat) => /* @__PURE__ */ jsx(StatTile, { value: stat.number, label: t(stat.labelAr, stat.labelEn), accent: true }, stat.number)) })
  ] }) });
};
export default PortfolioBoard;
