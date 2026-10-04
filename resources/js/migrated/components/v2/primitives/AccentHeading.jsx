import { Fragment, jsx, jsxs } from "react/jsx-runtime";
import { cn } from "@/lib/utils";
import { useLanguage } from "@/contexts/LanguageContext";
const AccentHeading = ({ as: Tag = "h2", pre, accent, post, className }) => {
  const { isRTL } = useLanguage();
  return /* @__PURE__ */ jsxs(Tag, { className: cn("font-display text-3xl md:text-4xl font-extrabold tracking-tight text-foreground", className), children: [
    pre && /* @__PURE__ */ jsxs(Fragment, { children: [
      pre,
      " "
    ] }),
    accent && /* @__PURE__ */ jsx("span", { className: cn("text-primary", !isRTL && "font-serif-accent italic font-normal"), children: accent }),
    post && /* @__PURE__ */ jsxs(Fragment, { children: [
      " ",
      post
    ] })
  ] });
};
export default AccentHeading;
