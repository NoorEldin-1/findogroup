import { Monitor, Palette, Smartphone } from "lucide-react";
export const typeLabels = {
  website: { ar: "\u0627\u0644\u0645\u0648\u0627\u0642\u0639", en: "Websites" },
  app: { ar: "\u0627\u0644\u062A\u0637\u0628\u064A\u0642\u0627\u062A", en: "Apps" },
  design: { ar: "\u0627\u0644\u062A\u0635\u0645\u064A\u0645", en: "Design" }
};
export const typeIcons = {
  website: Monitor,
  app: Smartphone,
  design: Palette
};
export const getTypeName = (t, type) => {
  switch (type) {
    case "app":
      return t("\u062A\u0637\u0628\u064A\u0642 \u062C\u0648\u0627\u0644", "Mobile App");
    case "design":
      return t("\u062A\u0635\u0645\u064A\u0645", "Design");
    default:
      return t("\u0645\u0648\u0642\u0639 \u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A", "Website");
  }
};
