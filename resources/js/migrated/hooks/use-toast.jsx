export function toast({ title = "", description = "", variant } = {}) {
  window.dispatchEvent(new CustomEvent("findo:toast", { detail: variant === "destructive" ? [title, description].filter(Boolean).join(" \u2014 ") : document.documentElement.lang === "ar" ? "\u062A\u0645 \u062D\u0641\u0638 \u0627\u0644\u0637\u0644\u0628 \u0645\u062D\u0644\u064A\u064B\u0627 \u0628\u0646\u062C\u0627\u062D." : "Saved successfully in the local copy." }));
  return { dismiss() {
  }, update() {
  } };
}
export const useToast = () => ({ toast, toasts: [], dismiss() {
} });
