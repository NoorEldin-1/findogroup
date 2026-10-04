import { jsx, jsxs } from "react/jsx-runtime";
import { motion } from "framer-motion";
const WhatsAppFloat = () => {
  const phoneNumber = "201100018259";
  const message = "\u0645\u0631\u062D\u0628\u0627\u064B\u060C \u0623\u0631\u064A\u062F \u0627\u0644\u0627\u0633\u062A\u0641\u0633\u0627\u0631 \u0639\u0646 \u062E\u062F\u0645\u0627\u062A\u0643\u0645";
  const whatsappUrl = `https://wa.me/${phoneNumber}?text=${encodeURIComponent(message)}`;
  return /* @__PURE__ */ jsxs(
    motion.a,
    {
      href: whatsappUrl,
      target: "_blank",
      rel: "noopener noreferrer",
      initial: { scale: 0, opacity: 0 },
      animate: { scale: 1, opacity: 1 },
      whileHover: { scale: 1.15 },
      whileTap: { scale: 0.95 },
      transition: { delay: 1, duration: 0.5, type: "spring" },
      className: "fixed bottom-6 md:bottom-6 left-6 z-50 hidden md:flex items-center justify-center w-14 h-14 rounded-full bg-[#25D366] text-white shadow-[0_10px_22px_-10px_rgba(37,211,102,0.55)] hover:shadow-[0_14px_28px_-12px_rgba(37,211,102,0.7)] transition-shadow duration-300 ease-ds group",
      "aria-label": "\u062A\u0648\u0627\u0635\u0644 \u0639\u0628\u0631 \u0648\u0627\u062A\u0633\u0627\u0628",
      children: [
        /* @__PURE__ */ jsx(
          motion.div,
          {
            animate: {
              boxShadow: [
                "0 0 0 0 rgba(37, 211, 102, 0.4)",
                "0 0 0 15px rgba(37, 211, 102, 0)"
              ]
            },
            transition: {
              duration: 1.5,
              repeat: Infinity,
              ease: "easeOut"
            },
            className: "absolute inset-0 rounded-full"
          }
        ),
        /* @__PURE__ */ jsx("i", { className: "pi pi-whatsapp !text-2xl group-hover:rotate-12 transition-transform duration-300", "aria-hidden": "true" })
      ]
    }
  );
};
export default WhatsAppFloat;
