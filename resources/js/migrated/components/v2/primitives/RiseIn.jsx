import { jsx } from "react/jsx-runtime";
import { motion, useReducedMotion } from "framer-motion";
const RiseIn = ({ children, delay = 0, mount = false, className }) => {
  const reduced = useReducedMotion();
  if (reduced || mount) return /* @__PURE__ */ jsx("div", { className, children });
  const visible = { opacity: 1, y: 0, scale: 1 };
  return /* @__PURE__ */ jsx(
    motion.div,
    {
      className,
      initial: { opacity: 0, y: 24, scale: 0.985 },
      ...mount ? { animate: visible } : { whileInView: visible, viewport: { once: true, margin: "0px 0px -64px 0px" } },
      transition: { duration: 0.7, delay, ease: [0.2, 0.7, 0.2, 1] },
      children
    }
  );
};
export default RiseIn;
