import {cmsCollection} from '@/adapters/cms.js';
import {
  FileSpreadsheet,
  FileText,
  CalendarRange,
  Target,
  BarChart3,
  PenLine,
  Video,
  Wallet,
  LineChart,
  LayoutDashboard,
  ClipboardList,
  Megaphone
} from "lucide-react";
export const templateFilters = [
  { key: "all", labelAr: "\u0643\u0644 \u0627\u0644\u0642\u0648\u0627\u0644\u0628", labelEn: "All templates" },
  { key: "plans", labelAr: "\u062E\u0637\u0637 \u0648\u0627\u0633\u062A\u0631\u0627\u062A\u064A\u062C\u064A\u0627\u062A", labelEn: "Plans & Strategies" },
  { key: "content", labelAr: "\u0635\u0646\u0627\u0639\u0629 \u0645\u062D\u062A\u0648\u0649", labelEn: "Content Creation" },
  { key: "ads", labelAr: "\u0625\u0639\u0644\u0627\u0646\u0627\u062A \u0648\u0623\u062F\u0627\u0621", labelEn: "Paid Ads & Media" },
  { key: "reports", labelAr: "\u062A\u0642\u0627\u0631\u064A\u0631 \u0648\u062A\u062D\u0644\u064A\u0644\u0627\u062A", labelEn: "Reports & Analytics" }
];
export const categoryTag = {
  plans: { ar: "\u062E\u0637\u0637 \u062A\u0633\u0648\u064A\u0642\u064A\u0629", en: "Marketing Plans" },
  content: { ar: "\u0635\u0646\u0627\u0639\u0629 \u0645\u062D\u062A\u0648\u0649", en: "Content Creation" },
  ads: { ar: "\u0625\u0639\u0644\u0627\u0646\u0627\u062A \u0645\u0645\u0648\u0644\u0629", en: "Paid Ads" },
  reports: { ar: "\u062A\u0642\u0627\u0631\u064A\u0631 \u0648\u062A\u062D\u0644\u064A\u0644\u0627\u062A", en: "Reports & Analytics" }
};
const original_marketingTemplates = [
  /* ── خطط واستراتيجيات ── */
  {
    id: "monthly-marketing-plan",
    category: "plans",
    icon: CalendarRange,
    fileType: "Excel",
    titleAr: "\u062E\u0637\u0629 \u062A\u0633\u0648\u064A\u0642 \u0634\u0647\u0631\u064A\u0629 \u062C\u0627\u0647\u0632\u0629",
    titleEn: "Monthly Marketing Plan",
    descAr: "\u0642\u0627\u0644\u0628 \u0645\u062A\u0643\u0627\u0645\u0644 \u0644\u062A\u0646\u0638\u064A\u0645 \u0623\u0647\u062F\u0627\u0641\u0643 \u0648\u062D\u0645\u0644\u0627\u062A\u0643 \u0648\u0645\u064A\u0632\u0627\u0646\u064A\u062A\u0643 \u0639\u0644\u0649 \u0645\u062F\u0627\u0631 \u0627\u0644\u0634\u0647\u0631 \u062E\u0637\u0648\u0629 \u0628\u062E\u0637\u0648\u0629.",
    descEn: "A complete template to organize your goals, campaigns and budget across the month, step by step.",
    fileUrl: null
  },
  {
    id: "content-strategy",
    category: "plans",
    icon: ClipboardList,
    fileType: "PDF",
    titleAr: "\u0627\u0633\u062A\u0631\u0627\u062A\u064A\u062C\u064A\u0629 \u0627\u0644\u0645\u062D\u062A\u0648\u0649 \u0627\u0644\u0631\u0628\u0639\u064A\u0629",
    titleEn: "Quarterly Content Strategy",
    descAr: "\u0625\u0637\u0627\u0631 \u0648\u0627\u0636\u062D \u0644\u0628\u0646\u0627\u0621 \u0631\u0633\u0627\u0626\u0644\u0643 \u0648\u0623\u0639\u0645\u062F\u0629 \u0627\u0644\u0645\u062D\u062A\u0648\u0649 \u0648\u062E\u0637\u0629 \u0627\u0644\u0646\u0634\u0631 \u0639\u0644\u0649 \u062B\u0644\u0627\u062B\u0629 \u0623\u0634\u0647\u0631.",
    descEn: "A clear framework to build your messaging, content pillars and publishing plan over three months.",
    fileUrl: null
  },
  {
    id: "competitor-swot",
    category: "plans",
    icon: Target,
    fileType: "Excel",
    titleAr: "\u062A\u062D\u0644\u064A\u0644 \u0627\u0644\u0645\u0646\u0627\u0641\u0633\u064A\u0646 \u0648\u0646\u0642\u0627\u0637 \u0627\u0644\u0642\u0648\u0629 \u0648\u0627\u0644\u0636\u0639\u0641",
    titleEn: "Competitor & SWOT Analysis",
    descAr: "\u0623\u062F\u0627\u0629 \u0644\u0645\u0642\u0627\u0631\u0646\u0629 \u0645\u0646\u0627\u0641\u0633\u064A\u0643 \u0648\u062A\u062D\u062F\u064A\u062F \u0641\u0631\u0635\u0643 \u0648\u0645\u062E\u0627\u0637\u0631\u0643 \u0642\u0628\u0644 \u0625\u0637\u0644\u0627\u0642 \u0623\u064A \u062D\u0645\u0644\u0629.",
    descEn: "A tool to benchmark competitors and map your opportunities and risks before any campaign.",
    fileUrl: null
  },
  /* ── صناعة محتوى ── */
  {
    id: "social-content-calendar",
    category: "content",
    icon: CalendarRange,
    fileType: "Excel",
    titleAr: "\u062A\u0642\u0648\u064A\u0645 \u0645\u062D\u062A\u0648\u0649 \u0627\u0644\u0633\u0648\u0634\u064A\u0627\u0644 \u0645\u064A\u062F\u064A\u0627",
    titleEn: "Social Media Content Calendar",
    descAr: "\u0646\u0638\u0651\u0645 \u0645\u0646\u0634\u0648\u0631\u0627\u062A\u0643 \u0639\u0644\u0649 \u0643\u0644 \u0627\u0644\u0645\u0646\u0635\u0651\u0627\u062A \u0628\u0645\u0648\u0627\u0639\u064A\u062F \u0648\u0623\u0646\u0648\u0627\u0639 \u0645\u062D\u062A\u0648\u0649 \u062C\u0627\u0647\u0632\u0629 \u0644\u0644\u062A\u0639\u0628\u0626\u0629.",
    descEn: "Plan your posts across every platform with ready-to-fill dates and content types.",
    fileUrl: null
  },
  {
    id: "ad-copywriting",
    category: "content",
    icon: PenLine,
    fileType: "Word",
    titleAr: "\u0642\u0648\u0627\u0644\u0628 \u0643\u062A\u0627\u0628\u0629 \u0627\u0644\u0625\u0639\u0644\u0627\u0646\u0627\u062A (Copywriting)",
    titleEn: "Ad Copywriting Templates",
    descAr: "\u0635\u064A\u063A \u062C\u0627\u0647\u0632\u0629 \u0644\u0639\u0646\u0627\u0648\u064A\u0646 \u0648\u0646\u0635\u0648\u0635 \u0625\u0639\u0644\u0627\u0646\u064A\u0629 \u062A\u0632\u064A\u062F \u0627\u0644\u062A\u0641\u0627\u0639\u0644 \u0648\u0645\u0639\u062F\u0651\u0644 \u0627\u0644\u062A\u062D\u0648\u064A\u0644.",
    descEn: "Ready-made formulas for headlines and ad copy that lift engagement and conversion.",
    fileUrl: null
  },
  {
    id: "short-video-scripts",
    category: "content",
    icon: Video,
    fileType: "Word",
    titleAr: "\u0633\u0643\u0631\u0628\u062A\u0627\u062A \u0641\u064A\u062F\u064A\u0648\u0647\u0627\u062A \u0631\u064A\u0644\u0632 \u0648\u062A\u064A\u0643 \u062A\u0648\u0643",
    titleEn: "Short-Video Script Templates",
    descAr: "\u0647\u064A\u0627\u0643\u0644 \u0633\u0643\u0631\u0628\u062A \u062C\u0627\u0647\u0632\u0629 \u0644\u0641\u064A\u062F\u064A\u0648\u0647\u0627\u062A \u0642\u0635\u064A\u0631\u0629 \u062A\u062C\u0630\u0628 \u0627\u0644\u0627\u0646\u062A\u0628\u0627\u0647 \u0641\u064A \u0623\u0648\u0644 \u062B\u0627\u0646\u064A\u0629.",
    descEn: "Ready script structures for short videos that grab attention in the first second.",
    fileUrl: null
  },
  /* ── إعلانات وأداء ── */
  {
    id: "ad-budget-planner",
    category: "ads",
    icon: Wallet,
    fileType: "Excel",
    titleAr: "\u062D\u0627\u0633\u0628\u0629 \u0645\u064A\u0632\u0627\u0646\u064A\u0629 \u0627\u0644\u0625\u0639\u0644\u0627\u0646\u0627\u062A",
    titleEn: "Ad Budget Planner",
    descAr: "\u0648\u0632\u0651\u0639 \u0645\u064A\u0632\u0627\u0646\u064A\u062A\u0643 \u0639\u0644\u0649 \u0627\u0644\u0645\u0646\u0635\u0651\u0627\u062A \u0648\u0627\u062D\u0633\u0628 \u0627\u0644\u0639\u0627\u0626\u062F \u0627\u0644\u0645\u062A\u0648\u0642\u0651\u0639 \u0642\u0628\u0644 \u0627\u0644\u0635\u0631\u0641.",
    descEn: "Distribute your budget across platforms and forecast the expected return before you spend.",
    fileUrl: null
  },
  {
    id: "audience-targeting",
    category: "ads",
    icon: Megaphone,
    fileType: "PDF",
    titleAr: "\u0642\u0627\u0644\u0628 \u0627\u0633\u062A\u0647\u062F\u0627\u0641 \u0627\u0644\u062C\u0645\u0647\u0648\u0631 (Meta \u0648 Google)",
    titleEn: "Audience Targeting Template",
    descAr: "\u062D\u062F\u0651\u062F \u0634\u0631\u0627\u0626\u062D \u062C\u0645\u0647\u0648\u0631\u0643 \u0648\u0627\u0647\u062A\u0645\u0627\u0645\u0627\u062A\u0647\u0627 \u0644\u0628\u0646\u0627\u0621 \u062D\u0645\u0644\u0627\u062A \u062F\u0642\u064A\u0642\u0629 \u0639\u0644\u0649 Meta \u0648 Google.",
    descEn: "Define your audience segments and interests to build precise campaigns on Meta and Google.",
    fileUrl: null
  },
  {
    id: "campaign-tracker",
    category: "ads",
    icon: LineChart,
    fileType: "Excel",
    titleAr: "\u0645\u062A\u062A\u0628\u0651\u0639 \u0623\u062F\u0627\u0621 \u0627\u0644\u062D\u0645\u0644\u0627\u062A \u0627\u0644\u0625\u0639\u0644\u0627\u0646\u064A\u0629",
    titleEn: "Campaign Performance Tracker",
    descAr: "\u062A\u0627\u0628\u0639 \u0627\u0644\u0646\u0642\u0631\u0627\u062A \u0648\u0627\u0644\u062A\u0643\u0644\u0641\u0629 \u0648\u0627\u0644\u062A\u062D\u0648\u064A\u0644\u0627\u062A \u0644\u0643\u0644 \u062D\u0645\u0644\u0629 \u0641\u064A \u0645\u0643\u0627\u0646 \u0648\u0627\u062D\u062F.",
    descEn: "Track clicks, cost and conversions for every campaign in one place.",
    fileUrl: null
  },
  /* ── تقارير وتحليلات ── */
  {
    id: "monthly-marketing-report",
    category: "reports",
    icon: FileText,
    fileType: "PDF",
    titleAr: "\u062A\u0642\u0631\u064A\u0631 \u0623\u062F\u0627\u0621 \u0627\u0644\u062A\u0633\u0648\u064A\u0642 \u0627\u0644\u0634\u0647\u0631\u064A",
    titleEn: "Monthly Marketing Report",
    descAr: "\u0642\u0627\u0644\u0628 \u062A\u0642\u0631\u064A\u0631 \u0627\u062D\u062A\u0631\u0627\u0641\u064A \u064A\u0644\u062E\u0651\u0635 \u0646\u062A\u0627\u0626\u062C \u0627\u0644\u0634\u0647\u0631 \u0628\u0623\u0631\u0642\u0627\u0645 \u0648\u0627\u0636\u062D\u0629 \u0644\u0635\u0646\u0651\u0627\u0639 \u0627\u0644\u0642\u0631\u0627\u0631.",
    descEn: "A professional report template that summarizes the month's results in clear numbers for decision-makers.",
    fileUrl: null
  },
  {
    id: "kpis-dashboard",
    category: "reports",
    icon: LayoutDashboard,
    fileType: "Excel",
    titleAr: "\u0644\u0648\u062D\u0629 \u0645\u0624\u0634\u0631\u0627\u062A \u0627\u0644\u0623\u062F\u0627\u0621 KPIs",
    titleEn: "Marketing KPIs Dashboard",
    descAr: "\u062A\u062A\u0628\u0651\u0639 \u0623\u0647\u0645 \u0645\u0624\u0634\u0631\u0627\u062A\u0643 \u0627\u0644\u062A\u0633\u0648\u064A\u0642\u064A\u0629 \u0628\u0635\u0631\u064A\u064B\u0627 \u0641\u064A \u0644\u0648\u062D\u0629 \u0648\u0627\u062D\u062F\u0629 \u0633\u0647\u0644\u0629 \u0627\u0644\u062A\u062D\u062F\u064A\u062B.",
    descEn: "Track your key marketing metrics visually in one easy-to-update dashboard.",
    fileUrl: null
  },
  {
    id: "website-analytics-report",
    category: "reports",
    icon: BarChart3,
    fileType: "PDF",
    titleAr: "\u062A\u0642\u0631\u064A\u0631 \u062A\u062D\u0644\u064A\u0644\u0627\u062A \u0627\u0644\u0645\u0648\u0642\u0639",
    titleEn: "Website Analytics Report",
    descAr: "\u0642\u0627\u0644\u0628 \u0644\u0639\u0631\u0636 \u0632\u064A\u0627\u0631\u0627\u062A \u0645\u0648\u0642\u0639\u0643 \u0648\u0645\u0635\u0627\u062F\u0631\u0647\u0627 \u0648\u0633\u0644\u0648\u0643 \u0627\u0644\u0645\u0633\u062A\u062E\u062F\u0645\u064A\u0646 \u0628\u0634\u0643\u0644 \u0645\u0646\u0638\u0651\u0645.",
    descEn: "A template to present your website traffic, sources and user behavior in an organized way.",
    fileUrl: null
  }
];
export const marketingTemplates = cmsCollection('templates',original_marketingTemplates);
export const fileTypeIcon = {
  PDF: FileText,
  Excel: FileSpreadsheet,
  Word: FileText
};
export const templateIconByName = {
  FileSpreadsheet,
  FileText,
  CalendarRange,
  Target,
  BarChart3,
  PenLine,
  Video,
  Wallet,
  LineChart,
  LayoutDashboard,
  ClipboardList,
  Megaphone
};
export const templateIconNames = Object.keys(templateIconByName);
