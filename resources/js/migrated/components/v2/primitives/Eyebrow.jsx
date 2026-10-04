import { jsx, jsxs } from "react/jsx-runtime";
import { cn } from "@/lib/utils";
import { useLanguage } from "@/contexts/LanguageContext";
const Eyebrow = ({ index, children, className }) => {
  const { isRTL } = useLanguage();
  return /* @__PURE__ */ jsxs("div", { className: cn("flex items-center gap-3 text-primary", className), children: [
    /* @__PURE__ */ jsx("span", { "aria-hidden": true, className: "inline-block h-px w-[30px] bg-gradient-to-r from-primary to-primary/0" }),
    /* @__PURE__ */ jsxs(
      "span",
      {
        className: cn(
          "font-display text-xs font-bold uppercase",
          isRTL ? "tracking-normal" : "tracking-[0.26em]"
        ),
        children: [
          index && /* @__PURE__ */ jsxs("span", { className: "tracking-[0.26em]", children: [
            index,
            " \xB7 "
          ] }),
          children
        ]
      }
    )
  ] });
};
export default Eyebrow;
