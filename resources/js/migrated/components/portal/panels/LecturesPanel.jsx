import { jsx, jsxs } from "react/jsx-runtime";
import { useMemo } from "react";
import { Video, CalendarClock, FileText, Clock, ExternalLink } from "lucide-react";
import { formatDate } from "@/lib/admin";
const LectureCard = ({ lecture, past }) => /* @__PURE__ */ jsxs(
  "div",
  {
    className: `rounded-2xl border p-4 sm:p-5 ${past ? "border-border bg-background/40 opacity-80" : "border-primary/25 bg-primary/[0.04]"}`,
    children: [
      /* @__PURE__ */ jsx("div", { className: "flex flex-wrap items-start justify-between gap-3", children: /* @__PURE__ */ jsxs("div", { className: "min-w-0 flex-1", children: [
        /* @__PURE__ */ jsxs("div", { className: "mb-1 flex items-center gap-2", children: [
          /* @__PURE__ */ jsx("span", { className: "grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-primary/12 text-primary", children: /* @__PURE__ */ jsx(Video, { className: "h-4 w-4" }) }),
          /* @__PURE__ */ jsx("h3", { className: "text-base font-bold text-foreground", children: lecture.title })
        ] }),
        lecture.description && /* @__PURE__ */ jsx("p", { className: "mt-1 text-sm leading-relaxed text-muted-foreground", dir: "auto", children: lecture.description }),
        /* @__PURE__ */ jsxs("div", { className: "mt-2.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground", children: [
          /* @__PURE__ */ jsxs("span", { className: "inline-flex items-center gap-1.5", children: [
            /* @__PURE__ */ jsx(CalendarClock, { className: "h-3.5 w-3.5" }),
            /* @__PURE__ */ jsx("span", { dir: "auto", children: formatDate(lecture.scheduled_at) })
          ] }),
          /* @__PURE__ */ jsxs("span", { className: "inline-flex items-center gap-1.5", children: [
            /* @__PURE__ */ jsx(Clock, { className: "h-3.5 w-3.5" }),
            lecture.duration_min,
            " \u062F\u0642\u064A\u0642\u0629"
          ] })
        ] })
      ] }) }),
      /* @__PURE__ */ jsxs("div", { className: "mt-4 flex flex-wrap items-center gap-2", children: [
        /* @__PURE__ */ jsxs(
          "a",
          {
            href: lecture.session_link,
            target: "_blank",
            rel: "noopener noreferrer",
            className: `inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition ${past ? "border border-border text-muted-foreground hover:text-primary" : "bg-primary text-primary-foreground hover:opacity-90"}`,
            children: [
              /* @__PURE__ */ jsx(Video, { className: "h-4 w-4" }),
              past ? "\u0631\u0627\u0628\u0637 \u0627\u0644\u062C\u0644\u0633\u0629" : "\u0627\u0646\u0636\u0645 \u0644\u0644\u062C\u0644\u0633\u0629"
            ]
          }
        ),
        lecture.materials_link && /* @__PURE__ */ jsxs(
          "a",
          {
            href: lecture.materials_link,
            target: "_blank",
            rel: "noopener noreferrer",
            className: "inline-flex items-center gap-2 rounded-xl border border-border px-4 py-2 text-sm font-medium text-muted-foreground transition-colors hover:text-primary",
            children: [
              /* @__PURE__ */ jsx(FileText, { className: "h-4 w-4" }),
              "\u0627\u0644\u0645\u0648\u0627\u062F"
            ]
          }
        )
      ] })
    ]
  }
);
const LecturesPanel = ({ lectures }) => {
  const { upcoming, past } = useMemo(() => {
    const now = Date.now();
    const up = [];
    const pa = [];
    for (const l of lectures) {
      (new Date(l.scheduled_at).getTime() >= now ? up : pa).push(l);
    }
    up.sort((a, b) => new Date(a.scheduled_at).getTime() - new Date(b.scheduled_at).getTime());
    pa.sort((a, b) => new Date(b.scheduled_at).getTime() - new Date(a.scheduled_at).getTime());
    return { upcoming: up, past: pa };
  }, [lectures]);
  if (lectures.length === 0) {
    return /* @__PURE__ */ jsxs("div", { className: "flex flex-col items-center gap-3 rounded-3xl border border-border bg-popover/40 py-16 text-center", children: [
      /* @__PURE__ */ jsx(Video, { className: "h-10 w-10 text-muted-foreground/40" }),
      /* @__PURE__ */ jsx("p", { className: "text-muted-foreground", children: "\u0644\u0633\u0647 \u0645\u0641\u064A\u0634 \u0645\u062D\u0627\u0636\u0631\u0627\u062A \u0645\u0636\u0627\u0641\u0629. \u0647\u064A\u0648\u0635\u0644\u0643 \u0625\u0634\u0639\u0627\u0631 \u0623\u0648\u0644 \u0645\u0627 \u062A\u062A\u0636\u0627\u0641 \u0645\u062D\u0627\u0636\u0631\u0629." })
    ] });
  }
  return /* @__PURE__ */ jsxs("div", { className: "space-y-6", children: [
    upcoming.length > 0 && /* @__PURE__ */ jsxs("section", { children: [
      /* @__PURE__ */ jsxs("h2", { className: "mb-3 flex items-center gap-2 text-sm font-bold text-foreground", children: [
        /* @__PURE__ */ jsx("span", { className: "h-2 w-2 rounded-full bg-primary" }),
        "\u0645\u062D\u0627\u0636\u0631\u0627\u062A \u0642\u0627\u062F\u0645\u0629 (",
        upcoming.length,
        ")"
      ] }),
      /* @__PURE__ */ jsx("div", { className: "space-y-3", children: upcoming.map((l) => /* @__PURE__ */ jsx(LectureCard, { lecture: l, past: false }, l.id)) })
    ] }),
    past.length > 0 && /* @__PURE__ */ jsxs("section", { children: [
      /* @__PURE__ */ jsxs("h2", { className: "mb-3 flex items-center gap-2 text-sm font-bold text-muted-foreground", children: [
        /* @__PURE__ */ jsx(ExternalLink, { className: "h-3.5 w-3.5" }),
        "\u0645\u062D\u0627\u0636\u0631\u0627\u062A \u0633\u0627\u0628\u0642\u0629 (",
        past.length,
        ")"
      ] }),
      /* @__PURE__ */ jsx("div", { className: "space-y-3", children: past.map((l) => /* @__PURE__ */ jsx(LectureCard, { lecture: l, past: true }, l.id)) })
    ] })
  ] });
};
export default LecturesPanel;
