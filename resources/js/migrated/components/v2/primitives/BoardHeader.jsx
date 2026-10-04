import { jsx, jsxs } from "react/jsx-runtime";
import { cn } from "@/lib/utils";
import Eyebrow from "./Eyebrow";
import AccentHeading from "./AccentHeading";
const BoardHeader = ({
  index,
  eyebrow,
  pre,
  accent,
  post,
  as = "h2",
  subtitle,
  aside,
  align = "start",
  className,
  headingClassName
}) => {
  const centered = align === "center";
  return /* @__PURE__ */ jsxs(
    "div",
    {
      className: cn(
        "mb-8 flex flex-wrap gap-5",
        centered ? "flex-col items-center text-center" : "items-end justify-between",
        className
      ),
      children: [
        /* @__PURE__ */ jsxs("div", { className: cn("max-w-2xl", centered && "mx-auto"), children: [
          /* @__PURE__ */ jsx(Eyebrow, { index, className: cn("mb-3", centered && "justify-center"), children: eyebrow }),
          /* @__PURE__ */ jsx(AccentHeading, { as, pre, accent, post, className: headingClassName }),
          subtitle && /* @__PURE__ */ jsx("p", { className: cn("mt-3 text-sm md:text-base leading-relaxed text-muted-foreground", centered && "mx-auto"), children: subtitle })
        ] }),
        aside && /* @__PURE__ */ jsx("div", { className: cn("max-w-md text-sm leading-relaxed text-muted-foreground", centered && "mx-auto text-center"), children: aside })
      ]
    }
  );
};
export default BoardHeader;
