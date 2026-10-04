import { jsx, jsxs } from "react/jsx-runtime";
import { cn } from "@/lib/utils";
const StatTile = ({ value, label, icon, accent, className }) => /* @__PURE__ */ jsxs(
  "div",
  {
    className: cn(
      "rounded-2xl border border-border p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.04)]",
      className
    ),
    style: { background: "hsl(var(--popover))" },
    children: [
      icon && /* @__PURE__ */ jsx("div", { className: "mb-2 text-primary", children: icon }),
      /* @__PURE__ */ jsx("div", { className: cn("font-display text-[22px] font-extrabold leading-tight", accent ? "text-primary" : "text-foreground"), children: value }),
      /* @__PURE__ */ jsx("div", { className: "mt-0.5 text-[11.5px] text-[#5C6774]", children: label })
    ]
  }
);
export default StatTile;
