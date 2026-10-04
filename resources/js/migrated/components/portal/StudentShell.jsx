import { jsx, jsxs } from "react/jsx-runtime";
import { useState } from "react";
import { Link } from "react-router-dom";
import { GraduationCap, LogOut, User, Video, Award, RefreshCw } from "lucide-react";
import ProfilePanel from "./panels/ProfilePanel";
import LecturesPanel from "./panels/LecturesPanel";
import CertificatePanel from "./panels/CertificatePanel";
const TABS = [
  { key: "profile", label: "\u0646\u0638\u0631\u0629 \u0639\u0627\u0645\u0629", icon: User },
  { key: "lectures", label: "\u0627\u0644\u0645\u062D\u0627\u0636\u0631\u0627\u062A", icon: Video },
  { key: "certificate", label: "\u0627\u0644\u0634\u0647\u0627\u062F\u0629", icon: Award }
];
const StudentShell = ({
  data,
  onLogout,
  onRefresh
}) => {
  const [tab, setTab] = useState("profile");
  const [refreshing, setRefreshing] = useState(false);
  const refresh = async () => {
    setRefreshing(true);
    await onRefresh();
    setTimeout(() => setRefreshing(false), 400);
  };
  return /* @__PURE__ */ jsxs("div", { dir: "rtl", className: "min-h-screen bg-background", children: [
    /* @__PURE__ */ jsxs("header", { className: "sticky top-0 z-20 border-b border-border bg-popover/80 backdrop-blur-md", children: [
      /* @__PURE__ */ jsxs("div", { className: "mx-auto flex max-w-4xl items-center justify-between gap-3 px-4 py-3.5", children: [
        /* @__PURE__ */ jsxs(Link, { to: "/", className: "flex items-center gap-2.5", children: [
          /* @__PURE__ */ jsx("span", { className: "grid h-9 w-9 place-items-center rounded-xl bg-primary text-primary-foreground", children: /* @__PURE__ */ jsx(GraduationCap, { className: "h-5 w-5", strokeWidth: 1.75 }) }),
          /* @__PURE__ */ jsxs("div", { className: "leading-tight", children: [
            /* @__PURE__ */ jsx("p", { className: "text-sm font-bold text-foreground", children: "\u0623\u0643\u0627\u062F\u064A\u0645\u064A\u0629 Findo" }),
            /* @__PURE__ */ jsx("p", { className: "text-[11px] text-muted-foreground", children: data.profile.name })
          ] })
        ] }),
        /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-1.5", children: [
          /* @__PURE__ */ jsx(
            "button",
            {
              onClick: refresh,
              className: "grid h-9 w-9 place-items-center rounded-xl border border-border text-muted-foreground transition-colors hover:text-primary",
              title: "\u062A\u062D\u062F\u064A\u062B",
              children: /* @__PURE__ */ jsx(RefreshCw, { className: `h-4 w-4 ${refreshing ? "animate-spin" : ""}` })
            }
          ),
          /* @__PURE__ */ jsxs(
            "button",
            {
              onClick: onLogout,
              className: "inline-flex items-center gap-1.5 rounded-xl border border-border px-3 py-2 text-sm text-muted-foreground transition-colors hover:border-destructive/40 hover:text-destructive",
              children: [
                /* @__PURE__ */ jsx(LogOut, { className: "h-4 w-4" }),
                /* @__PURE__ */ jsx("span", { className: "hidden sm:inline", children: "\u062E\u0631\u0648\u062C" })
              ]
            }
          )
        ] })
      ] }),
      /* @__PURE__ */ jsx("div", { className: "mx-auto max-w-4xl overflow-x-auto px-4", children: /* @__PURE__ */ jsx("nav", { className: "flex gap-1", children: TABS.map((t) => {
        const active = tab === t.key;
        return /* @__PURE__ */ jsxs(
          "button",
          {
            onClick: () => setTab(t.key),
            className: `flex items-center gap-2 whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium transition-colors ${active ? "border-primary text-primary" : "border-transparent text-muted-foreground hover:text-foreground"}`,
            children: [
              /* @__PURE__ */ jsx(t.icon, { className: "h-4 w-4" }),
              t.label
            ]
          },
          t.key
        );
      }) }) })
    ] }),
    /* @__PURE__ */ jsxs("main", { className: "mx-auto max-w-4xl px-4 py-6", children: [
      tab === "profile" && /* @__PURE__ */ jsx(ProfilePanel, { data }),
      tab === "lectures" && /* @__PURE__ */ jsx(LecturesPanel, { lectures: data.lectures }),
      tab === "certificate" && /* @__PURE__ */ jsx(CertificatePanel, { profile: data.profile })
    ] })
  ] });
};
export default StudentShell;
