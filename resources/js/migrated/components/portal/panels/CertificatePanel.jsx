import { jsx, jsxs } from "react/jsx-runtime";
import { Award, Lock, Printer } from "lucide-react";
import { formatDate } from "@/lib/admin";
const certificateHTML = (p) => {
  const dateStr = p.completed_at ? formatDate(p.completed_at) : "";
  return `<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8">
<title>\u0634\u0647\u0627\u062F\u0629 \u0625\u062A\u0645\u0627\u0645 \u2014 ${p.name}</title>
<style>
  @page { size: A4 landscape; margin: 0; }
  body { margin:0; font-family: Tahoma, Arial, sans-serif; background:#0a1f2e; }
  .wrap { width:1123px; height:794px; margin:auto; box-sizing:border-box; padding:26px;
          background:#0a1f2e; display:flex; }
  .card { flex:1; border:2px solid #f2795d; border-radius:18px; background:linear-gradient(135deg,#0d2d42,#0a1f2e);
          padding:54px 60px; box-sizing:border-box; text-align:center; color:#e2e8f0;
          display:flex; flex-direction:column; align-items:center; justify-content:center; position:relative; }
  .brand { position:absolute; top:34px; right:60px; font-family:Arial; font-weight:bold; color:#fff; font-size:20px; letter-spacing:1px; }
  .brand small { display:block; color:#f2795d; font-size:9px; letter-spacing:3px; margin-top:2px; }
  .eyebrow { color:#f2795d; letter-spacing:4px; font-size:14px; font-weight:bold; }
  .title { color:#fff; font-size:44px; font-weight:800; margin:10px 0 26px; }
  .sub { color:rgba(255,255,255,.65); font-size:16px; }
  .name { color:#f2795d; font-size:40px; font-weight:800; margin:14px 0; border-bottom:2px solid rgba(242,121,93,.4); padding-bottom:10px; }
  .prog { color:#fff; font-size:20px; margin-top:8px; }
  .meta { margin-top:40px; display:flex; gap:60px; color:rgba(255,255,255,.7); font-size:13px; }
  .meta b { color:#fff; display:block; font-size:15px; margin-top:3px; }
</style></head>
<body onload="window.print()">
  <div class="wrap"><div class="card">
    <div class="brand">FINDO GROUP<small>DREAM AND WATCH</small></div>
    <div class="eyebrow">\u0634\u0647\u0627\u062F\u0629 \u0625\u062A\u0645\u0627\u0645</div>
    <div class="title">Certificate of Completion</div>
    <div class="sub">\u062A\u0634\u0647\u062F \u0623\u0643\u0627\u062F\u064A\u0645\u064A\u0629 Findo \u0628\u0623\u0646</div>
    <div class="name">${p.name}</div>
    <div class="sub">\u0642\u062F \u0623\u062A\u0645\u0651 \u0628\u0646\u062C\u0627\u062D</div>
    <div class="prog">${p.program || "\u0627\u0644\u0628\u0631\u0646\u0627\u0645\u062C \u0627\u0644\u062A\u062F\u0631\u064A\u0628\u064A"}</div>
    <div class="meta">
      <div>\u062A\u0627\u0631\u064A\u062E \u0627\u0644\u0625\u062A\u0645\u0627\u0645<b>${dateStr}</b></div>
      <div>\u0631\u0642\u0645 \u0627\u0644\u0634\u0647\u0627\u062F\u0629<b dir="ltr">${p.certificate_id || ""}</b></div>
    </div>
  </div></div>
</body></html>`;
};
const CertificatePanel = ({ profile }) => {
  if (!profile.completed) {
    return /* @__PURE__ */ jsxs("div", { className: "flex flex-col items-center gap-4 rounded-3xl border border-border bg-popover/40 py-16 text-center", children: [
      /* @__PURE__ */ jsx("span", { className: "grid h-16 w-16 place-items-center rounded-2xl bg-muted text-muted-foreground", children: /* @__PURE__ */ jsx(Lock, { className: "h-7 w-7" }) }),
      /* @__PURE__ */ jsxs("div", { children: [
        /* @__PURE__ */ jsx("p", { className: "text-base font-bold text-foreground", children: "\u0634\u0647\u0627\u062F\u062A\u0643 \u0644\u0633\u0647 \u0645\u0634 \u062C\u0627\u0647\u0632\u0629" }),
        /* @__PURE__ */ jsx("p", { className: "mt-1 max-w-sm text-sm text-muted-foreground", children: "\u0647\u062A\u0644\u0627\u0642\u064A \u0634\u0647\u0627\u062F\u0629 \u0627\u0644\u0625\u062A\u0645\u0627\u0645 \u0647\u0646\u0627 \u0623\u0648\u0644 \u0645\u0627 \u062A\u062E\u0644\u0651\u0635 \u0627\u0644\u0628\u0631\u0646\u0627\u0645\u062C \u0627\u0644\u062A\u062F\u0631\u064A\u0628\u064A \u0648\u062A\u062A\u0639\u0644\u0651\u0645 \u0643\u0645\u0643\u062A\u0645\u0644. \u0627\u0633\u062A\u0645\u0631 \u0641\u064A \u062D\u0636\u0648\u0631 \u0627\u0644\u0645\u062D\u0627\u0636\u0631\u0627\u062A." })
      ] })
    ] });
  }
  const print = () => {
    const w = window.open("", "_blank");
    if (!w) return;
    w.document.write(certificateHTML(profile));
    w.document.close();
  };
  return /* @__PURE__ */ jsxs("div", { className: "space-y-5", children: [
    /* @__PURE__ */ jsxs("div", { className: "overflow-hidden rounded-3xl border-2 border-primary/40 bg-gradient-to-br from-[#0d2d42] to-[#0a1f2e] p-8 text-center sm:p-12", children: [
      /* @__PURE__ */ jsx("div", { className: "mb-1 text-xs font-bold tracking-[0.3em] text-primary", children: "\u0634\u0647\u0627\u062F\u0629 \u0625\u062A\u0645\u0627\u0645" }),
      /* @__PURE__ */ jsx("div", { className: "mb-6 text-lg font-extrabold text-white", children: "Certificate of Completion" }),
      /* @__PURE__ */ jsx("span", { className: "mx-auto mb-5 grid h-16 w-16 place-items-center rounded-full bg-primary/15 text-primary", children: /* @__PURE__ */ jsx(Award, { className: "h-8 w-8" }) }),
      /* @__PURE__ */ jsx("p", { className: "text-sm text-white/60", children: "\u062A\u0634\u0647\u062F \u0623\u0643\u0627\u062F\u064A\u0645\u064A\u0629 Findo \u0628\u0623\u0646" }),
      /* @__PURE__ */ jsx("p", { className: "my-2 text-2xl font-extrabold text-primary sm:text-3xl", children: profile.name }),
      /* @__PURE__ */ jsx("p", { className: "text-sm text-white/60", children: "\u0642\u062F \u0623\u062A\u0645\u0651 \u0628\u0646\u062C\u0627\u062D" }),
      /* @__PURE__ */ jsx("p", { className: "mt-1 text-base font-bold text-white", children: profile.program || "\u0627\u0644\u0628\u0631\u0646\u0627\u0645\u062C \u0627\u0644\u062A\u062F\u0631\u064A\u0628\u064A" }),
      /* @__PURE__ */ jsxs("div", { className: "mx-auto mt-7 flex max-w-xs flex-wrap justify-center gap-x-10 gap-y-3 text-xs text-white/60", children: [
        /* @__PURE__ */ jsxs("div", { children: [
          "\u062A\u0627\u0631\u064A\u062E \u0627\u0644\u0625\u062A\u0645\u0627\u0645",
          /* @__PURE__ */ jsx("span", { className: "mt-0.5 block text-sm font-semibold text-white", dir: "auto", children: profile.completed_at ? formatDate(profile.completed_at) : "-" })
        ] }),
        /* @__PURE__ */ jsxs("div", { children: [
          "\u0631\u0642\u0645 \u0627\u0644\u0634\u0647\u0627\u062F\u0629",
          /* @__PURE__ */ jsx("span", { className: "mt-0.5 block text-sm font-semibold text-white", dir: "ltr", children: profile.certificate_id })
        ] })
      ] })
    ] }),
    /* @__PURE__ */ jsxs(
      "button",
      {
        onClick: print,
        className: "inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-primary px-5 py-3 text-sm font-semibold text-primary-foreground transition-opacity hover:opacity-90",
        children: [
          /* @__PURE__ */ jsx(Printer, { className: "h-4 w-4" }),
          "\u0637\u0628\u0627\u0639\u0629 / \u062D\u0641\u0638 PDF"
        ]
      }
    )
  ] });
};
export default CertificatePanel;
