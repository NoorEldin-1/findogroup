import { jsx, jsxs } from "react/jsx-runtime";
import { User, Mail, Phone, BookOpen, CalendarClock, Award, CheckCircle2 } from "lucide-react";
import { formatDate } from "@/lib/admin";
const nextLecture = (lectures) => {
  const now = Date.now();
  const upcoming = lectures.filter((l) => new Date(l.scheduled_at).getTime() >= now).sort((a, b) => new Date(a.scheduled_at).getTime() - new Date(b.scheduled_at).getTime());
  return upcoming[0] ?? null;
};
const Row = ({ icon: Icon, label, value }) => /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-3 rounded-2xl border border-border bg-background/50 p-3.5", children: [
  /* @__PURE__ */ jsx("span", { className: "grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-primary/12 text-primary", children: /* @__PURE__ */ jsx(Icon, { className: "h-5 w-5" }) }),
  /* @__PURE__ */ jsxs("div", { className: "min-w-0", children: [
    /* @__PURE__ */ jsx("p", { className: "text-[11.5px] text-muted-foreground", children: label }),
    /* @__PURE__ */ jsx("p", { className: "truncate text-sm font-medium text-foreground", dir: "auto", children: value })
  ] })
] });
const ProfilePanel = ({ data }) => {
  const { profile, lectures } = data;
  const next = nextLecture(lectures);
  return /* @__PURE__ */ jsxs("div", { className: "space-y-5", children: [
    /* @__PURE__ */ jsxs("div", { className: "rounded-3xl border border-border bg-gradient-to-br from-primary/10 to-transparent p-6", children: [
      /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-2 text-primary", children: [
        profile.completed ? /* @__PURE__ */ jsx(Award, { className: "h-5 w-5" }) : /* @__PURE__ */ jsx(BookOpen, { className: "h-5 w-5" }),
        /* @__PURE__ */ jsx("span", { className: "text-sm font-semibold", children: profile.completed ? "\u0623\u062A\u0645\u0645\u062A \u0627\u0644\u0628\u0631\u0646\u0627\u0645\u062C" : "\u0645\u0644\u062A\u062D\u0642 \u0628\u0627\u0644\u0628\u0631\u0646\u0627\u0645\u062C" })
      ] }),
      /* @__PURE__ */ jsxs("h2", { className: "mt-2 text-xl font-bold text-foreground", children: [
        "\u0623\u0647\u0644\u0627\u064B ",
        profile.name
      ] }),
      /* @__PURE__ */ jsx("p", { className: "mt-1 text-sm text-muted-foreground", children: profile.program || "\u0627\u0644\u0628\u0631\u0646\u0627\u0645\u062C \u0627\u0644\u062A\u062F\u0631\u064A\u0628\u064A" })
    ] }),
    /* @__PURE__ */ jsxs("div", { className: "grid gap-3 sm:grid-cols-2", children: [
      /* @__PURE__ */ jsx(Row, { icon: User, label: "\u0627\u0644\u0627\u0633\u0645", value: profile.name }),
      /* @__PURE__ */ jsx(Row, { icon: Phone, label: "\u0631\u0642\u0645 \u0627\u0644\u062A\u0644\u064A\u0641\u0648\u0646", value: profile.phone }),
      /* @__PURE__ */ jsx(Row, { icon: Mail, label: "\u0627\u0644\u0628\u0631\u064A\u062F \u0627\u0644\u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A", value: profile.email }),
      /* @__PURE__ */ jsx(Row, { icon: CheckCircle2, label: "\u062A\u0627\u0631\u064A\u062E \u0627\u0644\u0627\u0644\u062A\u062D\u0627\u0642", value: formatDate(profile.created_at) })
    ] }),
    next && /* @__PURE__ */ jsxs("div", { className: "rounded-3xl border border-border bg-popover/50 p-5", children: [
      /* @__PURE__ */ jsxs("div", { className: "mb-2 flex items-center gap-2 text-muted-foreground", children: [
        /* @__PURE__ */ jsx(CalendarClock, { className: "h-4 w-4" }),
        /* @__PURE__ */ jsx("span", { className: "text-xs font-medium", children: "\u0623\u0642\u0631\u0628 \u0645\u062D\u0627\u0636\u0631\u0629 \u0642\u0627\u062F\u0645\u0629" })
      ] }),
      /* @__PURE__ */ jsx("p", { className: "text-base font-bold text-foreground", children: next.title }),
      /* @__PURE__ */ jsx("p", { className: "mt-1 text-sm text-muted-foreground", dir: "auto", children: formatDate(next.scheduled_at) }),
      /* @__PURE__ */ jsx(
        "a",
        {
          href: next.session_link,
          target: "_blank",
          rel: "noopener noreferrer",
          className: "mt-3 inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition-opacity hover:opacity-90",
          children: "\u0627\u0646\u0636\u0645 \u0644\u0644\u062C\u0644\u0633\u0629"
        }
      )
    ] })
  ] });
};
export default ProfilePanel;
