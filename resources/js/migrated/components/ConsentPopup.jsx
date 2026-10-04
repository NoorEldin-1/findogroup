import { Fragment, jsx, jsxs } from "react/jsx-runtime";
import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { Cookie } from "lucide-react";
import { Button } from "@/components/ui/button";
import { useLanguage } from "@/contexts/LanguageContext";
import { supabase } from "@/integrations/supabase/client";
import {
  CONSENT_STORAGE_KEY,
  getUserAgent,
  readConsent,
  isConsentExpired
} from "@/lib/tracking";
const VISITOR_KEY = "findo_visitor_id";
const getVisitorId = () => {
  try {
    let id = localStorage.getItem(VISITOR_KEY);
    if (!id) {
      id = typeof crypto !== "undefined" && crypto.randomUUID ? crypto.randomUUID() : `v_${Date.now()}_${Math.random().toString(36).slice(2)}`;
      localStorage.setItem(VISITOR_KEY, id);
    }
    return id;
  } catch {
    return "unknown";
  }
};
const applyConsent = (c) => {
  window.gtag?.("consent", "update", {
    analytics_storage: c.analytics ? "granted" : "denied",
    ad_storage: c.marketing ? "granted" : "denied",
    ad_user_data: c.marketing ? "granted" : "denied",
    ad_personalization: c.marketing ? "granted" : "denied"
  });
  if (c.analytics) window.__loadClarity?.();
  if (c.marketing) window.__loadLinkedIn?.();
};
const ConsentPopup = () => {
  const { t, isRTL } = useLanguage();
  const [open, setOpen] = useState(false);
  const [customize, setCustomize] = useState(false);
  const [analytics, setAnalytics] = useState(false);
  const [marketing, setMarketing] = useState(false);
  useEffect(() => {
    const stored = readConsent();
    if (stored && !isConsentExpired(stored)) {
      applyConsent(stored.categories);
    } else {
      setOpen(true);
    }
    const openSettings = () => {
      const current = readConsent();
      if (current) {
        setAnalytics(current.categories.analytics);
        setMarketing(current.categories.marketing);
      }
      setCustomize(true);
      setOpen(true);
    };
    window.addEventListener("findo:open-consent", openSettings);
    return () => window.removeEventListener("findo:open-consent", openSettings);
  }, []);
  const persist = (c) => {
    const prev = readConsent();
    try {
      localStorage.setItem(
        CONSENT_STORAGE_KEY,
        JSON.stringify({ categories: c, at: (/* @__PURE__ */ new Date()).toISOString() })
      );
    } catch {
    }
    applyConsent(c);
    void supabase.from("cookie_consents").insert({
      visitor_id: getVisitorId(),
      necessary: true,
      analytics: c.analytics,
      marketing: c.marketing,
      user_agent: getUserAgent(),
      page: typeof window !== "undefined" ? window.location.pathname : null
    }).then(({ error }) => {
      if (error) console.warn("[consent] insert failed:", error.message);
    });
    setOpen(false);
    const downgraded = !!prev && (prev.categories.analytics && !c.analytics || prev.categories.marketing && !c.marketing);
    if (downgraded && typeof window !== "undefined") {
      window.location.reload();
    }
  };
  const acceptAll = () => persist({ necessary: true, analytics: true, marketing: true });
  const rejectAll = () => persist({ necessary: true, analytics: false, marketing: false });
  const saveCustom = () => persist({ necessary: true, analytics, marketing });
  if (!open) return null;
  const ToggleRow = ({
    title,
    desc,
    checked,
    onToggle,
    locked
  }) => /* @__PURE__ */ jsxs("div", { className: "flex items-start justify-between gap-3 rounded-xl border border-border bg-background/50 p-3", children: [
    /* @__PURE__ */ jsxs("div", { className: "min-w-0", children: [
      /* @__PURE__ */ jsx("p", { className: "text-sm font-bold text-foreground", children: title }),
      /* @__PURE__ */ jsx("p", { className: "mt-0.5 text-xs leading-relaxed text-muted-foreground", children: desc })
    ] }),
    /* @__PURE__ */ jsx(
      "button",
      {
        type: "button",
        role: "switch",
        "aria-checked": checked,
        "aria-label": title,
        disabled: locked,
        onClick: () => !locked && onToggle?.(!checked),
        className: `relative mt-0.5 inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors ${checked ? "bg-primary" : "bg-muted"} ${locked ? "cursor-not-allowed opacity-60" : "cursor-pointer"}`,
        children: /* @__PURE__ */ jsx(
          "span",
          {
            className: `inline-block h-4 w-4 rounded-full bg-white shadow transition-transform ${checked ? "translate-x-[1.375rem]" : "translate-x-1"} ${isRTL ? "-scale-x-100" : ""}`
          }
        )
      }
    )
  ] });
  return /* @__PURE__ */ jsx(
    "div",
    {
      dir: isRTL ? "rtl" : "ltr",
      className: "fixed inset-x-0 bottom-0 z-[100] p-3 sm:p-4",
      role: "dialog",
      "aria-live": "polite",
      "aria-label": t("\u0625\u0639\u062F\u0627\u062F\u0627\u062A \u0627\u0644\u062E\u0635\u0648\u0635\u064A\u0629 \u0648\u0627\u0644\u0643\u0648\u0643\u064A\u0632", "Privacy & cookie settings"),
      children: /* @__PURE__ */ jsxs("div", { className: "mx-auto max-w-2xl rounded-2xl border border-border bg-popover/95 p-4 shadow-2xl backdrop-blur-md sm:p-5", children: [
        /* @__PURE__ */ jsxs("div", { className: "flex items-start gap-3", children: [
          /* @__PURE__ */ jsx("span", { className: "flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/15 text-primary", children: /* @__PURE__ */ jsx(Cookie, { className: "h-5 w-5", strokeWidth: 1.75 }) }),
          /* @__PURE__ */ jsxs("div", { className: "min-w-0 flex-1", children: [
            /* @__PURE__ */ jsx("h2", { className: "text-base font-bold text-foreground", children: t("\u0646\u062D\u0646 \u0646\u0647\u062A\u0645 \u0628\u062E\u0635\u0648\u0635\u064A\u062A\u0643", "We value your privacy") }),
            /* @__PURE__ */ jsxs("p", { className: "mt-1 text-sm leading-relaxed text-muted-foreground", children: [
              t(
                "\u0646\u0633\u062A\u062E\u062F\u0645 \u0627\u0644\u0643\u0648\u0643\u064A\u0632 \u0644\u062A\u062D\u0633\u064A\u0646 \u062A\u062C\u0631\u0628\u062A\u0643 \u0648\u062A\u062D\u0644\u064A\u0644 \u0627\u0644\u0623\u062F\u0627\u0621 \u0648\u0639\u0631\u0636 \u0645\u062D\u062A\u0648\u0649 \u0645\u0646\u0627\u0633\u0628. \u062A\u0642\u062F\u0631 \u062A\u0642\u0628\u0644 \u0627\u0644\u0643\u0644 \u0623\u0648 \u062A\u062A\u062D\u0643\u0645 \u0641\u064A \u0627\u062E\u062A\u064A\u0627\u0631\u0627\u062A\u0643.",
                "We use cookies to improve your experience, analyze performance, and show relevant content. You can accept all or manage your choices."
              ),
              " ",
              /* @__PURE__ */ jsx(
                Link,
                {
                  to: "/privacy-policy",
                  className: "font-semibold text-primary underline-offset-2 hover:underline",
                  children: t("\u0633\u064A\u0627\u0633\u0629 \u0627\u0644\u062E\u0635\u0648\u0635\u064A\u0629", "Privacy Policy")
                }
              )
            ] })
          ] })
        ] }),
        customize && /* @__PURE__ */ jsxs("div", { className: "mt-4 grid gap-2", children: [
          /* @__PURE__ */ jsx(
            ToggleRow,
            {
              title: t("\u0636\u0631\u0648\u0631\u064A\u0629", "Necessary"),
              desc: t(
                "\u0645\u0637\u0644\u0648\u0628\u0629 \u0644\u0639\u0645\u0644 \u0627\u0644\u0645\u0648\u0642\u0639 \u0627\u0644\u0623\u0633\u0627\u0633\u064A\u060C \u0644\u0627 \u064A\u0645\u0643\u0646 \u0625\u064A\u0642\u0627\u0641\u0647\u0627.",
                "Required for the site to work. Always on."
              ),
              checked: true,
              locked: true
            }
          ),
          /* @__PURE__ */ jsx(
            ToggleRow,
            {
              title: t("\u062A\u062D\u0644\u064A\u0644\u0627\u062A", "Analytics"),
              desc: t(
                "\u062A\u0633\u0627\u0639\u062F\u0646\u0627 \u0646\u0641\u0647\u0645 \u0627\u0633\u062A\u062E\u062F\u0627\u0645 \u0627\u0644\u0645\u0648\u0642\u0639 \u0648\u0646\u062D\u0633\u0651\u0646\u0647 (GA4\u060C Clarity).",
                "Help us understand usage and improve the site (GA4, Clarity)."
              ),
              checked: analytics,
              onToggle: setAnalytics
            }
          ),
          /* @__PURE__ */ jsx(
            ToggleRow,
            {
              title: t("\u062A\u0633\u0648\u064A\u0642", "Marketing"),
              desc: t(
                "\u0644\u0639\u0631\u0636 \u0625\u0639\u0644\u0627\u0646\u0627\u062A \u0648\u0639\u0631\u0648\u0636 \u0645\u0646\u0627\u0633\u0628\u0629 \u0644\u0643 (Google Ads\u060C LinkedIn).",
                "For relevant ads and offers (Google Ads, LinkedIn)."
              ),
              checked: marketing,
              onToggle: setMarketing
            }
          )
        ] }),
        /* @__PURE__ */ jsx("div", { className: "mt-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end", children: !customize ? /* @__PURE__ */ jsxs(Fragment, { children: [
          /* @__PURE__ */ jsx(Button, { variant: "ghost", onClick: () => setCustomize(true), className: "sm:me-auto", children: t("\u062A\u062E\u0635\u064A\u0635", "Customize") }),
          /* @__PURE__ */ jsx(Button, { variant: "outline", onClick: rejectAll, children: t("\u0631\u0641\u0636 \u0627\u0644\u0643\u0644", "Reject all") }),
          /* @__PURE__ */ jsx(Button, { onClick: acceptAll, children: t("\u0642\u0628\u0648\u0644 \u0627\u0644\u0643\u0644", "Accept all") })
        ] }) : /* @__PURE__ */ jsxs(Fragment, { children: [
          /* @__PURE__ */ jsx(Button, { variant: "outline", onClick: rejectAll, className: "sm:me-auto", children: t("\u0631\u0641\u0636 \u0627\u0644\u0643\u0644", "Reject all") }),
          /* @__PURE__ */ jsx(Button, { variant: "outline", onClick: saveCustom, children: t("\u062D\u0641\u0638 \u0627\u062E\u062A\u064A\u0627\u0631\u0627\u062A\u064A", "Save choices") }),
          /* @__PURE__ */ jsx(Button, { onClick: acceptAll, children: t("\u0642\u0628\u0648\u0644 \u0627\u0644\u0643\u0644", "Accept all") })
        ] }) })
      ] })
    }
  );
};
export default ConsentPopup;
