export const LanguageProvider = ({ children }) => children;
export function useLanguage() {
  const language = document.documentElement.lang || "ar";
  return { language, isRTL: language === "ar", t: (ar, en) => language === "ar" ? ar : en, toggleLanguage() {
    document.cookie = `findo_language=${language === "ar" ? "en" : "ar"};path=/;max-age=31536000;SameSite=Lax`;
    location.reload();
  } };
}
