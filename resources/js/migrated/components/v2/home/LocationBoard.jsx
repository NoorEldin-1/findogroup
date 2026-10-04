import { jsx, jsxs } from "react/jsx-runtime";
import { useState } from "react";
import { MapPin, Navigation } from "lucide-react";
import { branches, mapEmbedSrc, mapDirectionsUrl } from "@/data/branches";
import { Button } from "@/components/ui/button";
import { Board, BoardHeader, Chip, IconBadge, RiseIn } from "@/components/v2/primitives";
import { useLanguage } from "@/contexts/LanguageContext";
const LocationBoard = () => {
  const { t, isRTL } = useLanguage();
  const [active, setActive] = useState(0);
  const branch = branches[active] ?? branches[0];
  return /* @__PURE__ */ jsx(RiseIn, { children: /* @__PURE__ */ jsxs(Board, { dir: isRTL ? "rtl" : "ltr", children: [
    /* @__PURE__ */ jsx(
      BoardHeader,
      {
        index: "10",
        eyebrow: t("\u0645\u0648\u0642\u0639\u0646\u0627", "Our location"),
        pre: t("\u062A\u0641\u0636\u0651\u0644", "Come"),
        accent: t("\u0628\u0632\u064A\u0627\u0631\u062A\u0646\u0627", "visit us"),
        subtitle: t(
          "\u064A\u0633\u0639\u062F\u0646\u0627 \u0627\u0633\u062A\u0642\u0628\u0627\u0644\u0643 \u0641\u064A \u0623\u0642\u0631\u0628 \u0641\u0631\u0639 \u0644\u0643 \u2014 \u0627\u062E\u062A\u0631 \u0627\u0644\u0641\u0631\u0639 \u0644\u0639\u0631\u0636 \u0645\u0648\u0642\u0639\u0647 \u0639\u0644\u0649 \u0627\u0644\u062E\u0631\u064A\u0637\u0629.",
          "We'd love to welcome you at your nearest branch \u2014 pick a branch to see it on the map."
        )
      }
    ),
    /* @__PURE__ */ jsx("div", { className: "mb-6 flex flex-wrap gap-2.5", children: branches.map((b, i) => /* @__PURE__ */ jsx(
      Chip,
      {
        interactive: true,
        active: i === active,
        onClick: () => setActive(i),
        icon: /* @__PURE__ */ jsx(MapPin, { className: "h-4 w-4", strokeWidth: 1.75 }),
        className: "px-4 py-2 text-sm",
        children: t(b.cityAr, b.cityEn)
      },
      b.cityEn
    )) }),
    /* @__PURE__ */ jsxs("div", { className: "grid gap-4 lg:grid-cols-[1fr_340px]", children: [
      /* @__PURE__ */ jsx("div", { className: "overflow-hidden rounded-2xl border border-border", children: /* @__PURE__ */ jsx(
        "iframe",
        {
          title: t(branch.cityAr, branch.cityEn),
          src: mapEmbedSrc(branch.mapQuery),
          className: "block h-[360px] w-full sm:h-[420px] md:h-[480px]",
          style: { border: 0 },
          allowFullScreen: true,
          loading: "lazy",
          referrerPolicy: "no-referrer-when-downgrade"
        },
        branch.mapQuery
      ) }),
      /* @__PURE__ */ jsxs("div", { className: "flex flex-col justify-between rounded-2xl border border-border bg-popover p-6", children: [
        /* @__PURE__ */ jsx("div", { children: /* @__PURE__ */ jsxs("div", { className: "flex items-start gap-3", children: [
          /* @__PURE__ */ jsx(IconBadge, { icon: MapPin, variant: "gradient" }),
          /* @__PURE__ */ jsxs("div", { className: "min-w-0", children: [
            /* @__PURE__ */ jsxs("div", { className: "font-bold text-foreground", children: [
              t("\u0641\u0631\u0639", "Branch"),
              " ",
              t(branch.cityAr, branch.cityEn)
            ] }),
            /* @__PURE__ */ jsx("p", { className: "mt-1 text-sm leading-relaxed text-muted-foreground", children: t(branch.addressAr, branch.addressEn) })
          ] })
        ] }) }),
        /* @__PURE__ */ jsx(Button, { asChild: true, variant: "outline", className: "mt-6 w-full", children: /* @__PURE__ */ jsxs("a", { href: mapDirectionsUrl(branch.mapQuery), target: "_blank", rel: "noopener noreferrer", children: [
          /* @__PURE__ */ jsx(Navigation, { strokeWidth: 1.75 }),
          t("\u0627\u0644\u0627\u062A\u062C\u0627\u0647\u0627\u062A \u0639\u0644\u0649 \u0627\u0644\u062E\u0631\u064A\u0637\u0629", "Get directions")
        ] }) })
      ] })
    ] })
  ] }) });
};
export default LocationBoard;
