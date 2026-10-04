import { jsx } from "react/jsx-runtime";
import { cn } from "@/lib/utils";
const CtaRow = ({ children, className }) => /* @__PURE__ */ jsx("div", { className: cn("mt-8 flex flex-wrap items-center gap-3.5", className), children });
export default CtaRow;
