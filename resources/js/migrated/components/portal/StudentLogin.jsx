import { jsx, jsxs } from "react/jsx-runtime";
import { useState } from "react";
import { Link } from "react-router-dom";
import { GraduationCap, Loader2 } from "lucide-react";
import { Button } from "@/components/ui/button";
const inputCls = "w-full rounded-2xl border border-border bg-background px-5 py-3.5 text-foreground placeholder:text-muted-foreground transition-all duration-200 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/30";
const StudentLogin = ({
  onSubmit,
  error
}) => {
  const [phone, setPhone] = useState("");
  const [password, setPassword] = useState("");
  const [busy, setBusy] = useState(false);
  const submit = async (e) => {
    e.preventDefault();
    if (busy) return;
    setBusy(true);
    await onSubmit(phone, password);
    setBusy(false);
  };
  return /* @__PURE__ */ jsx("div", { dir: "rtl", className: "min-h-screen flex items-center justify-center bg-background px-4 py-12", children: /* @__PURE__ */ jsxs("div", { className: "w-full max-w-md", children: [
    /* @__PURE__ */ jsxs("div", { className: "rounded-3xl border border-border bg-popover/60 p-8 shadow-2xl backdrop-blur-sm sm:p-10", children: [
      /* @__PURE__ */ jsxs("div", { className: "mb-7 flex flex-col items-center text-center", children: [
        /* @__PURE__ */ jsx("span", { className: "mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/15 text-primary", children: /* @__PURE__ */ jsx(GraduationCap, { className: "h-8 w-8", strokeWidth: 1.75 }) }),
        /* @__PURE__ */ jsx("h1", { className: "text-xl font-bold text-foreground", children: "\u0623\u0643\u0627\u062F\u064A\u0645\u064A\u0629 Findo" }),
        /* @__PURE__ */ jsx("p", { className: "mt-1.5 text-sm text-muted-foreground", children: "\u0627\u062F\u062E\u0644 \u0628\u0628\u064A\u0627\u0646\u0627\u062A\u0643 \u0627\u0644\u0644\u064A \u0648\u0635\u0644\u062A\u0643 \u0639\u0644\u0649 \u0627\u0644\u0625\u064A\u0645\u064A\u0644 \u0628\u0639\u062F \u0627\u0644\u062A\u0633\u062C\u064A\u0644" })
      ] }),
      /* @__PURE__ */ jsxs("form", { onSubmit: submit, className: "space-y-3.5", children: [
        /* @__PURE__ */ jsxs("div", { children: [
          /* @__PURE__ */ jsx("label", { className: "mb-1.5 block text-sm font-medium text-foreground", children: "\u0631\u0642\u0645 \u0627\u0644\u062A\u0644\u064A\u0641\u0648\u0646" }),
          /* @__PURE__ */ jsx(
            "input",
            {
              type: "tel",
              dir: "ltr",
              required: true,
              value: phone,
              onChange: (e) => setPhone(e.target.value),
              placeholder: "01xxxxxxxxx",
              className: inputCls,
              autoComplete: "username"
            }
          )
        ] }),
        /* @__PURE__ */ jsxs("div", { children: [
          /* @__PURE__ */ jsx("label", { className: "mb-1.5 block text-sm font-medium text-foreground", children: "\u0643\u0644\u0645\u0629 \u0627\u0644\u0633\u0631" }),
          /* @__PURE__ */ jsx(
            "input",
            {
              type: "password",
              dir: "ltr",
              required: true,
              value: password,
              onChange: (e) => setPassword(e.target.value),
              placeholder: "\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022",
              className: inputCls,
              autoComplete: "current-password"
            }
          )
        ] }),
        error && /* @__PURE__ */ jsx("p", { className: "rounded-xl bg-destructive/10 px-4 py-2.5 text-center text-sm text-destructive", children: error }),
        /* @__PURE__ */ jsx(Button, { type: "submit", disabled: busy, className: "h-12 w-full rounded-2xl text-base", children: busy ? /* @__PURE__ */ jsx(Loader2, { className: "h-5 w-5 animate-spin" }) : "\u062F\u062E\u0648\u0644" })
      ] }),
      /* @__PURE__ */ jsxs("p", { className: "mt-6 text-center text-xs text-muted-foreground", children: [
        "\u0644\u0633\u0647 \u0645\u0627 \u0633\u062C\u0651\u0644\u062A\u0634\u061F",
        " ",
        /* @__PURE__ */ jsx(Link, { to: "/training/register", className: "font-semibold text-primary hover:underline", children: "\u0633\u062C\u0651\u0644 \u0641\u064A \u0627\u0644\u0628\u0631\u0646\u0627\u0645\u062C \u0627\u0644\u062A\u062F\u0631\u064A\u0628\u064A" })
      ] })
    ] }),
    /* @__PURE__ */ jsx(
      Link,
      {
        to: "/",
        className: "mt-5 block text-center text-sm text-muted-foreground transition-colors hover:text-primary",
        children: "\u2190 \u0627\u0644\u0639\u0648\u062F\u0629 \u0644\u0644\u0645\u0648\u0642\u0639"
      }
    )
  ] }) });
};
export default StudentLogin;
