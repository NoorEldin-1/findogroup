import { jsx } from "react/jsx-runtime";
import { cn } from "@/lib/utils";
const sizes = { sm: "h-9 w-9 rounded-xl", md: "h-11 w-11 rounded-2xl", lg: "h-14 w-14 rounded-2xl" };
const iconSizes = { sm: "h-4 w-4", md: "h-[22px] w-[22px]", lg: "h-7 w-7" };
const IconBadge = ({ icon: Icon, variant = "tinted", size = "md", className }) => /* @__PURE__ */ jsx(
  "span",
  {
    className: cn(
      "grid shrink-0 place-items-center",
      sizes[size],
      variant === "tinted" && "bg-primary/12 text-primary",
      variant === "gradient" && "text-primary-foreground [background:var(--gradient-coral)]",
      variant === "outline" && "border border-primary/45 text-primary",
      className
    ),
    children: /* @__PURE__ */ jsx(Icon, { className: iconSizes[size], strokeWidth: 1.75 })
  }
);
export default IconBadge;
