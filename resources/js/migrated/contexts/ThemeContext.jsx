import { useSyncExternalStore } from "react";
const subscribe = (cb) => {
  window.addEventListener("findo:theme", cb);
  return () => window.removeEventListener("findo:theme", cb);
};
const snapshot = () => document.documentElement.classList.contains("dark");
export function useTheme() {
  const isDark = useSyncExternalStore(subscribe, snapshot, () => false);
  return { isDark, theme: isDark ? "dark" : "light", toggleTheme() {
    const next = !snapshot();
    document.documentElement.classList.toggle("dark", next);
    localStorage.setItem("findo-theme", next ? "dark" : "light");
    window.dispatchEvent(new Event("findo:theme"));
  } };
}
