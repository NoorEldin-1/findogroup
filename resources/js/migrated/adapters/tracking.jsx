export const CONSENT_STORAGE_KEY = "findo_consent_v1";
export const readConsent = () => {
  try {
    return JSON.parse(localStorage.getItem(CONSENT_STORAGE_KEY));
  } catch {
    return null;
  }
};
export const isConsentExpired = (c) => Date.now() - Date.parse(c.at) > 180 * 864e5;
export const hasAnalyticsConsent = () => false;
export const hasMarketingConsent = () => false;
export const trackLeadConversion = () => {
};
export const pushDataLayer = () => {
};
export const pushDataLayerWithAttribution = () => {
};
export const getUserAgent = () => navigator.userAgent;
export const getStoredTracking = () => null;
export const captureUTMParams = () => ({});
export const getDeviceType = () => innerWidth < 768 ? "mobile" : "desktop";
export const scrollToSection = (id) => document.getElementById(id)?.scrollIntoView({ behavior: "smooth" });
