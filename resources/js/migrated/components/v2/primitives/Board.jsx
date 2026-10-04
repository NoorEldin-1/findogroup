import { jsx } from "react/jsx-runtime";
import { forwardRef } from "react";
import { cn } from "@/lib/utils";
const Board = forwardRef(
  ({ as: Tag = "section", bare = false, band = false, className, style, children, ...props }, ref) => /* @__PURE__ */ jsx(
    Tag,
    {
      ref,
      className: cn(
        // De-carded: transparent, no rounded frame/border/shadow — sections flow as one
        // continuous surface. overflow-hidden still clips decorative glows to the section.
        "relative overflow-hidden",
        !bare && "py-9 sm:py-11 lg:py-14",
        // Tonal band → a soft, fully-rounded panel (no hard full-width divider lines).
        // Theme-aware tint (foreground opacity) + hairline border; inset within the gutter.
        // my-* gives each visible panel breathing room from its neighbours (fixes the
        // "sections stuck together" seams — hero↔services, testimonials↔consultation, …).
        band && "my-5 sm:my-7 rounded-[2.25rem] border border-border bg-foreground/[0.03] px-5 sm:px-8 lg:px-12",
        className
      ),
      style,
      ...props,
      children
    }
  )
);
Board.displayName = "Board";
export default Board;
