import { jsx, jsxs } from "react/jsx-runtime";
import { useState } from "react";
import { Copy, Check } from "lucide-react";
export const formatDate = (dateString) => new Intl.DateTimeFormat("ar-SA", {
  year: "numeric",
  month: "long",
  day: "numeric",
  hour: "2-digit",
  minute: "2-digit"
}).format(new Date(dateString));
export const relativeTime = (dateString) => {
  const diff = Date.now() - new Date(dateString).getTime();
  const mins = Math.floor(diff / 6e4);
  if (mins < 1) return "\u0627\u0644\u0622\u0646";
  if (mins < 60) return `\u0645\u0646\u0630 ${mins} \u062F\u0642\u064A\u0642\u0629`;
  const hrs = Math.floor(mins / 60);
  if (hrs < 24) return `\u0645\u0646\u0630 ${hrs} \u0633\u0627\u0639\u0629`;
  const days = Math.floor(hrs / 24);
  if (days < 30) return `\u0645\u0646\u0630 ${days} \u064A\u0648\u0645`;
  const months = Math.floor(days / 30);
  return `\u0645\u0646\u0630 ${months} \u0634\u0647\u0631`;
};
export const exportToCSV = (rows, headers, filename) => {
  const esc = (v) => v ? `"${String(v).replace(/"/g, '""')}"` : "-";
  const csv = [
    headers.join(","),
    ...rows.map((r) => r.map(esc).join(","))
  ].join("\n");
  const blob = new Blob(["\uFEFF" + csv], { type: "text/csv;charset=utf-8;" });
  const a = document.createElement("a");
  a.href = URL.createObjectURL(blob);
  a.download = `${filename}_${(/* @__PURE__ */ new Date()).toISOString().split("T")[0]}.csv`;
  a.click();
};
export const waLink = (phone, defaultCountry = "") => {
  const digits = phone.replace(/\D/g, "");
  const intl = defaultCountry && digits.startsWith("0") ? defaultCountry + digits.slice(1) : digits;
  return `https://wa.me/${intl}`;
};
export const StatCard = ({
  label,
  value,
  color = "text-white",
  icon: Icon
}) => /* @__PURE__ */ jsxs("div", { className: "glass-panel p-4 flex flex-col gap-1", children: [
  /* @__PURE__ */ jsxs("p", { className: "text-white/60 text-xs flex items-center gap-1.5", children: [
    Icon && /* @__PURE__ */ jsx(Icon, { className: "w-3 h-3" }),
    label
  ] }),
  /* @__PURE__ */ jsx("p", { className: `text-xl md:text-2xl font-bold ${color}`, children: value })
] });
export const CopyButton = ({ text }) => {
  const [copied, setCopied] = useState(false);
  const handleCopy = async (e) => {
    e.stopPropagation();
    await navigator.clipboard.writeText(text).catch(() => null);
    setCopied(true);
    setTimeout(() => setCopied(false), 1500);
  };
  return /* @__PURE__ */ jsx(
    "button",
    {
      onClick: handleCopy,
      className: "p-1 rounded text-white/30 hover:text-coral transition-colors shrink-0",
      title: "\u0646\u0633\u062E",
      children: copied ? /* @__PURE__ */ jsx(Check, { className: "w-3.5 h-3.5 text-green-400" }) : /* @__PURE__ */ jsx(Copy, { className: "w-3.5 h-3.5" })
    }
  );
};
