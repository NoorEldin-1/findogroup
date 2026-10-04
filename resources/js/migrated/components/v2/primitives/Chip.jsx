import { jsxs } from "react/jsx-runtime";
import { cn } from "@/lib/utils";
const variantClasses = {
  default: "border border-border bg-popover text-foreground",
  featured: "border border-transparent text-primary-foreground [background:var(--gradient-coral)]",
  success: "border border-success/40 bg-success/15 text-success",
  info: "border border-info/40 bg-info/15 text-info",
  warning: "border border-warning/40 bg-warning/15 text-warning",
  destructive: "border border-destructive/40 bg-destructive/15 text-destructive",
  outline: "border-[1.5px] border-dashed border-primary/50 bg-transparent text-primary"
};
const Chip = ({ variant = "default", active, icon, interactive, className, children, ...props }) => {
  const Tag = interactive ? "button" : "span";
  return /* @__PURE__ */ jsxs(
    Tag,
    {
      ...interactive ? { type: "button" } : {},
      className: cn(
        "inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-[13px] font-semibold transition-all duration-200 ease-ds",
        variantClasses[variant],
        active && "border-primary bg-primary/15 text-primary",
        interactive && "cursor-pointer hover:border-primary/60 hover:text-primary",
        className
      ),
      ...props,
      children: [
        icon,
        children
      ]
    }
  );
};
export default Chip;
