import { jsx } from "react/jsx-runtime";
import * as React from "react";
import { Slot } from "@radix-ui/react-slot";
import { cva } from "class-variance-authority";
import { cn } from "@/lib/utils";
const buttonVariants = cva(
  // DS v1.0: 15px radius, ds easing, subtle lift on hover, press returns
  "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-[15px] text-sm font-bold ring-offset-background transition-all duration-200 ease-ds focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0",
  {
    variants: {
      variant: {
        // Primary: pumpkin gradient + dark text + inset highlight + glow (DS controls board)
        default: "bg-gradient-to-b from-[#FF9C50] to-[#F0700D] text-primary-foreground shadow-btn-primary hover:-translate-y-0.5 hover:shadow-glow-coral-lg active:translate-y-px active:shadow-none",
        destructive: "bg-destructive text-destructive-foreground hover:bg-destructive/90",
        // Secondary: pumpkin outline → tinted fill on hover
        outline: "border border-primary/55 bg-transparent text-primary hover:bg-primary/10 hover:border-primary",
        secondary: "bg-secondary text-secondary-foreground hover:bg-secondary/80",
        ghost: "hover:bg-accent/10 hover:text-primary",
        link: "text-primary underline-offset-4 hover:underline"
      },
      size: {
        default: "h-10 px-4 py-2",
        sm: "h-9 rounded-xl px-3",
        lg: "h-12 rounded-[17px] px-8 text-[15px]",
        icon: "h-10 w-10"
      }
    },
    defaultVariants: {
      variant: "default",
      size: "default"
    }
  }
);
const Button = React.forwardRef(
  ({ className, variant, size, asChild = false, ...props }, ref) => {
    const Comp = asChild ? Slot : "button";
    return /* @__PURE__ */ jsx(Comp, { className: cn(buttonVariants({ variant, size, className })), ref, ...props });
  }
);
Button.displayName = "Button";
export { Button, buttonVariants };
