import {
  Search,
  Wrench,
  Server,
  PenLine,
  Share2,
  Palette,
  Plug
} from "lucide-react";
import { pricingServices } from "./pricing";
export const WHATSAPP_NUMBER = "201100018259";
export const formatUSD = (n) => `$${n.toLocaleString("en-US")}`;
const parsePrice = (p) => Number(p.replace(/[^0-9]/g, "")) || 0;
const languages = (extra) => ({
  id: "languages",
  labelAr: "\u0627\u0644\u0644\u063A\u0627\u062A",
  labelEn: "Languages",
  type: "select",
  defaultChoiceId: "one",
  choices: [
    { id: "one", labelAr: "\u0644\u063A\u0629 \u0648\u0627\u062D\u062F\u0629", labelEn: "One language", price: 0 },
    { id: "two", labelAr: "\u0644\u063A\u062A\u0627\u0646", labelEn: "Two languages", price: extra }
  ]
});
const design = (custom) => ({
  id: "design",
  labelAr: "\u0627\u0644\u062A\u0635\u0645\u064A\u0645",
  labelEn: "Design",
  type: "select",
  defaultChoiceId: "template",
  choices: [
    { id: "template", labelAr: "\u0642\u0627\u0644\u0628 \u0627\u062D\u062A\u0631\u0627\u0641\u064A", labelEn: "Professional template", price: 0 },
    { id: "custom", labelAr: "\u0645\u062E\u0635\u0651\u0635 \u0628\u0627\u0644\u0643\u0627\u0645\u0644", labelEn: "Fully custom", price: custom }
  ]
});
const serviceOptions = {
  // ── Web & Stores ──
  website: [
    { id: "pages", labelAr: "\u0639\u062F\u062F \u0627\u0644\u0635\u0641\u062D\u0627\u062A", labelEn: "Number of pages", type: "counter", included: 5, unitPrice: 70, min: 1, max: 50, unitLabelAr: "\u0635\u0641\u062D\u0629", unitLabelEn: "pages" },
    design(500),
    languages(300),
    { id: "blog", labelAr: "\u0645\u062F\u0648\u0646\u0629 + \u0644\u0648\u062D\u0629 \u062A\u062D\u0643\u0645 (CMS)", labelEn: "Blog + CMS", type: "toggle", price: 400 },
    { id: "admin", labelAr: "\u0644\u0648\u062D\u0629 \u062A\u062D\u0643\u0645 \u0625\u062F\u0627\u0631\u064A\u0629", labelEn: "Admin dashboard", type: "toggle", price: 300 }
  ],
  ecommerce: [
    design(700),
    { id: "payments", labelAr: "\u0628\u0648\u0627\u0628\u0627\u062A \u0627\u0644\u062F\u0641\u0639", labelEn: "Payment gateways", type: "select", defaultChoiceId: "single", choices: [
      { id: "single", labelAr: "\u0628\u0648\u0627\u0628\u0629 \u0648\u0627\u062D\u062F\u0629", labelEn: "Single gateway", price: 0 },
      { id: "multi", labelAr: "\u0628\u0648\u0627\u0628\u0627\u062A \u0645\u062A\u0639\u062F\u062F\u0629", labelEn: "Multiple gateways", price: 300 }
    ] },
    { id: "coupons", labelAr: "\u0643\u0648\u0628\u0648\u0646\u0627\u062A \u0648\u0639\u0631\u0648\u0636", labelEn: "Coupons & offers", type: "toggle", price: 250 },
    { id: "inventory", labelAr: "\u062A\u0643\u0627\u0645\u0644 \u0645\u062E\u0632\u0648\u0646", labelEn: "Inventory sync", type: "toggle", price: 350 },
    { id: "app", labelAr: "\u062A\u0637\u0628\u064A\u0642 \u062C\u0648\u0627\u0644 \u0645\u0635\u0627\u062D\u0628", labelEn: "Companion mobile app", type: "toggle", price: 1500 },
    languages(300)
  ],
  landing: [
    design(300),
    { id: "tracking", labelAr: "\u062A\u062A\u0628\u0651\u0639 \u062A\u062D\u0648\u064A\u0644\u0627\u062A + \u0627\u062E\u062A\u0628\u0627\u0631 A/B", labelEn: "Conversion tracking + A/B", type: "toggle", price: 150 },
    { id: "marketing", labelAr: "\u0631\u0628\u0637 \u0623\u062F\u0648\u0627\u062A \u0627\u0644\u062A\u0633\u0648\u064A\u0642", labelEn: "Marketing tools integration", type: "toggle", price: 120 },
    languages(150)
  ],
  // ── Mobile Apps ──
  "mobile-single": [
    { id: "platform", labelAr: "\u0627\u0644\u0645\u0646\u0635\u0651\u0629", labelEn: "Platform", type: "select", defaultChoiceId: "android", choices: [
      { id: "android", labelAr: "Android", labelEn: "Android", price: 0 },
      { id: "ios", labelAr: "iOS", labelEn: "iOS", price: 0 }
    ] },
    { id: "screens", labelAr: "\u0639\u062F\u062F \u0627\u0644\u0634\u0627\u0634\u0627\u062A", labelEn: "Number of screens", type: "counter", included: 6, unitPrice: 200, min: 3, max: 40, unitLabelAr: "\u0634\u0627\u0634\u0629", unitLabelEn: "screens" },
    { id: "push", labelAr: "\u0625\u0634\u0639\u0627\u0631\u0627\u062A Push", labelEn: "Push notifications", type: "toggle", price: 400 },
    { id: "pay", labelAr: "\u0628\u0648\u0627\u0628\u0629 \u062F\u0641\u0639", labelEn: "Payment gateway", type: "toggle", price: 600 },
    { id: "admin", labelAr: "\u0644\u0648\u062D\u0629 \u062A\u062D\u0643\u0645 \u0643\u0627\u0645\u0644\u0629", labelEn: "Full admin panel", type: "toggle", price: 700 }
  ],
  "mobile-dual": [
    { id: "screens", labelAr: "\u0639\u062F\u062F \u0627\u0644\u0634\u0627\u0634\u0627\u062A", labelEn: "Number of screens", type: "counter", included: 6, unitPrice: 220, min: 3, max: 40, unitLabelAr: "\u0634\u0627\u0634\u0629", unitLabelEn: "screens" },
    { id: "push", labelAr: "\u0625\u0634\u0639\u0627\u0631\u0627\u062A Push \u0645\u062A\u0642\u062F\u0651\u0645\u0629", labelEn: "Advanced push notifications", type: "toggle", price: 500 },
    { id: "pay", labelAr: "\u0628\u0648\u0627\u0628\u0629 \u062F\u0641\u0639", labelEn: "Payment gateway", type: "toggle", price: 700 },
    { id: "analytics", labelAr: "\u0644\u0648\u062D\u0629 \u062A\u062D\u0643\u0645 + \u062A\u062D\u0644\u064A\u0644\u0627\u062A", labelEn: "Admin panel + analytics", type: "toggle", price: 800 }
  ],
  // ── Systems & Platforms ──
  dashboard: [
    { id: "modules", labelAr: "\u0639\u062F\u062F \u0627\u0644\u0648\u062D\u062F\u0627\u062A", labelEn: "Number of modules", type: "counter", included: 3, unitPrice: 250, min: 1, max: 30, unitLabelAr: "\u0648\u062D\u062F\u0629", unitLabelEn: "modules" },
    { id: "roles", labelAr: "\u0635\u0644\u0627\u062D\u064A\u0627\u062A \u0645\u062A\u0642\u062F\u0651\u0645\u0629 \u0628\u0627\u0644\u0623\u062F\u0648\u0627\u0631", labelEn: "Advanced role permissions", type: "toggle", price: 300 },
    { id: "reports", labelAr: "\u0631\u0633\u0648\u0645 \u0628\u064A\u0627\u0646\u064A\u0629 \u0648\u062A\u0642\u0627\u0631\u064A\u0631", labelEn: "Charts & reports", type: "toggle", price: 350 },
    { id: "export", labelAr: "\u062A\u0635\u062F\u064A\u0631 Excel/PDF", labelEn: "Excel/PDF export", type: "toggle", price: 150 }
  ],
  crm: [
    { id: "users", labelAr: "\u0639\u062F\u062F \u0627\u0644\u0645\u0633\u062A\u062E\u062F\u0645\u064A\u0646", labelEn: "Number of users", type: "counter", included: 5, unitPrice: 60, min: 1, max: 200, unitLabelAr: "\u0645\u0633\u062A\u062E\u062F\u0645", unitLabelEn: "users" },
    { id: "automation", labelAr: "\u0623\u062A\u0645\u062A\u0629 \u0627\u0644\u0645\u062A\u0627\u0628\u0639\u0627\u062A", labelEn: "Follow-up automation", type: "toggle", price: 500 },
    { id: "channels", labelAr: "\u0631\u0628\u0637 \u0648\u0627\u062A\u0633\u0627\u0628/\u0628\u0631\u064A\u062F/\u0645\u0643\u0627\u0644\u0645\u0627\u062A", labelEn: "WhatsApp/email/calls integration", type: "toggle", price: 400 },
    { id: "app", labelAr: "\u062A\u0637\u0628\u064A\u0642 \u062C\u0648\u0627\u0644", labelEn: "Mobile app", type: "toggle", price: 1200 }
  ],
  erp: [
    { id: "modules", labelAr: "\u0639\u062F\u062F \u0627\u0644\u0648\u062D\u062F\u0627\u062A", labelEn: "Number of modules", type: "counter", included: 3, unitPrice: 400, min: 1, max: 25, unitLabelAr: "\u0648\u062D\u062F\u0629", unitLabelEn: "modules" },
    { id: "users", labelAr: "\u0639\u062F\u062F \u0627\u0644\u0645\u0633\u062A\u062E\u062F\u0645\u064A\u0646", labelEn: "Number of users", type: "counter", included: 10, unitPrice: 50, min: 1, max: 500, unitLabelAr: "\u0645\u0633\u062A\u062E\u062F\u0645", unitLabelEn: "users" },
    { id: "billing", labelAr: "\u062A\u0643\u0627\u0645\u0644 \u0641\u0648\u062A\u0631\u0629 \u0648\u062F\u0641\u0639", labelEn: "Invoicing & payment integration", type: "toggle", price: 600 },
    { id: "training", labelAr: "\u062A\u062F\u0631\u064A\u0628 \u0627\u0644\u0641\u0631\u0642", labelEn: "Team training", type: "toggle", price: 300 }
  ],
  hr: [
    { id: "employees", labelAr: "\u0639\u062F\u062F \u0627\u0644\u0645\u0648\u0638\u0641\u064A\u0646", labelEn: "Number of employees", type: "counter", included: 25, unitPrice: 8, min: 5, max: 2e3, unitLabelAr: "\u0645\u0648\u0638\u0641", unitLabelEn: "employees" },
    { id: "payroll", labelAr: "\u0631\u0648\u0627\u062A\u0628 \u0648\u0645\u0633\u064A\u0651\u0631\u0627\u062A", labelEn: "Payroll & payslips", type: "toggle", price: 500 },
    { id: "performance", labelAr: "\u062A\u0642\u064A\u064A\u0645 \u0627\u0644\u0623\u062F\u0627\u0621", labelEn: "Performance reviews", type: "toggle", price: 300 },
    { id: "biometric", labelAr: "\u062A\u0643\u0627\u0645\u0644 \u0628\u0635\u0645\u0629/\u062D\u0636\u0648\u0631", labelEn: "Biometric/attendance integration", type: "toggle", price: 350 }
  ],
  pos: [
    { id: "branches", labelAr: "\u0639\u062F\u062F \u0627\u0644\u0641\u0631\u0648\u0639", labelEn: "Number of branches", type: "counter", included: 1, unitPrice: 400, min: 1, max: 100, unitLabelAr: "\u0641\u0631\u0639", unitLabelEn: "branches" },
    { id: "loyalty", labelAr: "\u0628\u0631\u0646\u0627\u0645\u062C \u0648\u0644\u0627\u0621", labelEn: "Loyalty program", type: "toggle", price: 300 },
    { id: "accounting", labelAr: "\u062A\u0643\u0627\u0645\u0644 \u0645\u062D\u0627\u0633\u0628\u0629", labelEn: "Accounting integration", type: "toggle", price: 400 },
    { id: "shifts", labelAr: "\u0625\u062F\u0627\u0631\u0629 \u0648\u0631\u062F\u064A\u0627\u062A \u0648\u0643\u0627\u0634\u064A\u0631", labelEn: "Shifts & cashier management", type: "toggle", price: 250 }
  ],
  booking: [
    { id: "staff", labelAr: "\u0639\u062F\u062F \u0627\u0644\u0645\u0648\u0638\u0641\u064A\u0646", labelEn: "Number of staff", type: "counter", included: 3, unitPrice: 120, min: 1, max: 100, unitLabelAr: "\u0645\u0648\u0638\u0641", unitLabelEn: "staff" },
    { id: "reminders", labelAr: "\u062A\u0630\u0643\u064A\u0631\u0627\u062A \u062A\u0644\u0642\u0627\u0626\u064A\u0629 (SMS/\u0648\u0627\u062A\u0633\u0627\u0628)", labelEn: "Auto reminders (SMS/WhatsApp)", type: "toggle", price: 250 },
    { id: "prepay", labelAr: "\u062F\u0641\u0639 \u0645\u0642\u062F\u0651\u0645 \u0623\u0648\u0646\u0644\u0627\u064A\u0646", labelEn: "Online prepayment", type: "toggle", price: 300 },
    { id: "calendar", labelAr: "\u062A\u0643\u0627\u0645\u0644 \u062A\u0642\u0648\u064A\u0645", labelEn: "Calendar integration", type: "toggle", price: 150 }
  ],
  lms: [
    { id: "courses", labelAr: "\u0639\u062F\u062F \u0627\u0644\u0643\u0648\u0631\u0633\u0627\u062A", labelEn: "Number of courses", type: "counter", included: 10, unitPrice: 40, min: 1, max: 500, unitLabelAr: "\u0643\u0648\u0631\u0633", unitLabelEn: "courses" },
    { id: "certificates", labelAr: "\u0634\u0647\u0627\u062F\u0627\u062A \u0648\u0645\u0633\u0627\u0631\u0627\u062A \u062A\u0639\u0644\u0651\u0645", labelEn: "Certificates & learning paths", type: "toggle", price: 500 },
    { id: "subscriptions", labelAr: "\u0628\u0648\u0627\u0628\u0627\u062A \u062F\u0641\u0639 \u0644\u0644\u0627\u0634\u062A\u0631\u0627\u0643\u0627\u062A", labelEn: "Subscription payments", type: "toggle", price: 400 },
    { id: "forums", labelAr: "\u0645\u0646\u062A\u062F\u064A\u0627\u062A / \u062A\u0641\u0627\u0639\u0644", labelEn: "Forums / engagement", type: "toggle", price: 300 }
  ],
  marketplace: [
    { id: "wallets", labelAr: "\u0645\u062D\u0627\u0641\u0638 \u0648\u062A\u0633\u0648\u064A\u0627\u062A \u0628\u0627\u0626\u0639\u064A\u0646", labelEn: "Vendor wallets & payouts", type: "toggle", price: 700 },
    { id: "reviews", labelAr: "\u062A\u0642\u064A\u064A\u0645\u0627\u062A \u0648\u0645\u0631\u0627\u062C\u0639\u0627\u062A", labelEn: "Ratings & reviews", type: "toggle", price: 300 },
    { id: "shipping", labelAr: "\u0634\u062D\u0646 \u0648\u062A\u062A\u0628\u0651\u0639", labelEn: "Shipping & tracking", type: "toggle", price: 400 },
    { id: "app", labelAr: "\u062A\u0637\u0628\u064A\u0642 \u062C\u0648\u0627\u0644 \u0645\u0635\u0627\u062D\u0628", labelEn: "Companion mobile app", type: "toggle", price: 1500 }
  ],
  saas: [
    { id: "billing", labelAr: "\u0641\u0648\u062A\u0631\u0629 \u0627\u0634\u062A\u0631\u0627\u0643\u0627\u062A \u062A\u0644\u0642\u0627\u0626\u064A\u0629", labelEn: "Automated subscription billing", type: "toggle", price: 700 },
    { id: "webhooks", labelAr: "\u062A\u0643\u0627\u0645\u0644\u0627\u062A \u0648 Webhooks", labelEn: "Integrations & webhooks", type: "toggle", price: 500 },
    { id: "usage", labelAr: "\u062A\u062D\u0644\u064A\u0644\u0627\u062A \u0627\u0633\u062A\u062E\u062F\u0627\u0645", labelEn: "Usage analytics", type: "toggle", price: 400 },
    { id: "api", labelAr: "API \u0639\u0627\u0645", labelEn: "Public API", type: "toggle", price: 400 }
  ],
  // ── AI & Tech ──
  ai: [
    { id: "scope", labelAr: "\u0646\u0637\u0627\u0642 \u0627\u0644\u062D\u0644", labelEn: "Solution scope", type: "select", defaultChoiceId: "single", choices: [
      { id: "single", labelAr: "\u062D\u0644 \u0648\u0627\u062D\u062F (Chatbot/\u0646\u0645\u0648\u0630\u062C)", labelEn: "Single solution", price: 0 },
      { id: "multi", labelAr: "\u062D\u0644\u0648\u0644 \u0645\u062A\u0639\u062F\u062F\u0629", labelEn: "Multiple solutions", price: 1e3 }
    ] },
    { id: "integration", labelAr: "\u062A\u0643\u0627\u0645\u0644 \u0645\u0639 \u0623\u0646\u0638\u0645\u062A\u0643", labelEn: "Integration with your systems", type: "toggle", price: 700 },
    { id: "forecast", labelAr: "\u062A\u062D\u0644\u064A\u0644\u0627\u062A \u0648\u062A\u0646\u0628\u0651\u0624", labelEn: "Analytics & forecasting", type: "toggle", price: 600 },
    { id: "automation", labelAr: "\u0623\u062A\u0645\u062A\u0629 \u0633\u064A\u0631 \u0627\u0644\u0639\u0645\u0644", labelEn: "Workflow automation", type: "toggle", price: 500 }
  ],
  api: [
    { id: "integrations", labelAr: "\u0639\u062F\u062F \u0627\u0644\u062A\u0643\u0627\u0645\u0644\u0627\u062A", labelEn: "Number of integrations", type: "counter", included: 1, unitPrice: 250, min: 1, max: 30, unitLabelAr: "\u062A\u0643\u0627\u0645\u0644", unitLabelEn: "integrations" },
    { id: "webhooks", labelAr: "Webhooks \u0648\u0645\u0632\u0627\u0645\u0646\u0629", labelEn: "Webhooks & sync", type: "toggle", price: 300 },
    { id: "monitoring", labelAr: "\u0645\u0631\u0627\u0642\u0628\u0629", labelEn: "Monitoring", type: "toggle", price: 200 }
  ],
  cloud: [
    { id: "cicd", labelAr: "CI/CD", labelEn: "CI/CD", type: "toggle", price: 400 },
    { id: "monitoring", labelAr: "\u0645\u0631\u0627\u0642\u0628\u0629 \u0648\u062A\u0646\u0628\u064A\u0647\u0627\u062A", labelEn: "Monitoring & alerts", type: "toggle", price: 300 },
    { id: "backups", labelAr: "\u0646\u0633\u062E \u0627\u062D\u062A\u064A\u0627\u0637\u064A \u062A\u0644\u0642\u0627\u0626\u064A", labelEn: "Automated backups", type: "toggle", price: 200 },
    { id: "loadbalancing", labelAr: "\u0645\u0648\u0627\u0632\u0646\u0629 \u062D\u0650\u0645\u0644", labelEn: "Load balancing", type: "toggle", price: 300 }
  ],
  security: [
    { id: "depth", labelAr: "\u0646\u0648\u0639 \u0627\u0644\u0641\u062D\u0635", labelEn: "Audit depth", type: "select", defaultChoiceId: "basic", choices: [
      { id: "basic", labelAr: "\u0641\u062D\u0635 \u062B\u063A\u0631\u0627\u062A \u0623\u0633\u0627\u0633\u064A", labelEn: "Basic vulnerability scan", price: 0 },
      { id: "full", labelAr: "\u0627\u062E\u062A\u0628\u0627\u0631 \u0627\u062E\u062A\u0631\u0627\u0642 \u0634\u0627\u0645\u0644", labelEn: "Full penetration testing", price: 2e3 }
    ] },
    { id: "codereview", labelAr: "\u0645\u0631\u0627\u062C\u0639\u0629 \u0643\u0648\u062F \u0648\u0628\u0646\u064A\u0629", labelEn: "Code & architecture review", type: "toggle", price: 500 },
    { id: "rescan", labelAr: "\u0625\u0639\u0627\u062F\u0629 \u0641\u062D\u0635 \u0628\u0639\u062F \u0627\u0644\u0625\u0635\u0644\u0627\u062D", labelEn: "Re-scan after fixes", type: "toggle", price: 300 }
  ],
  // ── Design & Branding ──
  uiux: [
    { id: "screens", labelAr: "\u0639\u062F\u062F \u0627\u0644\u0634\u0627\u0634\u0627\u062A", labelEn: "Number of screens", type: "counter", included: 5, unitPrice: 60, min: 1, max: 60, unitLabelAr: "\u0634\u0627\u0634\u0629", unitLabelEn: "screens" },
    { id: "research", labelAr: "\u0628\u062D\u062B \u0648\u062A\u062C\u0631\u0628\u0629 \u0645\u0633\u062A\u062E\u062F\u0645", labelEn: "UX research", type: "toggle", price: 300 },
    { id: "designsystem", labelAr: "Design System \u0645\u062E\u0635\u0651\u0635", labelEn: "Custom design system", price: 400, type: "toggle" },
    { id: "prototype", labelAr: "\u0646\u0645\u0648\u0630\u062C \u062A\u0641\u0627\u0639\u0644\u064A (Prototype)", labelEn: "Interactive prototype", type: "toggle", price: 250 }
  ],
  branding: [
    { id: "brandbook", labelAr: "\u062F\u0644\u064A\u0644 \u0647\u0648\u064A\u0629 (Brand Book)", labelEn: "Brand book", type: "toggle", price: 400 },
    { id: "templates", labelAr: "\u0642\u0648\u0627\u0644\u0628 \u0633\u0648\u0634\u064A\u0627\u0644 + \u0645\u0637\u0628\u0648\u0639\u0627\u062A", labelEn: "Social + print templates", type: "toggle", price: 300 },
    { id: "stationery", labelAr: "\u0628\u0637\u0627\u0642\u0627\u062A \u0648\u0648\u062B\u0627\u0626\u0642", labelEn: "Cards & stationery", type: "toggle", price: 200 },
    { id: "mockups", labelAr: "\u0645\u0648\u0643 \u0623\u0628 \u062A\u0637\u0628\u064A\u0642\u064A", labelEn: "Applied mockups", type: "toggle", price: 150 }
  ]
};
export const builderServices = pricingServices.filter((s) => serviceOptions[s.id]).map((s) => ({
  id: s.id,
  icon: s.icon,
  nameAr: s.nameAr,
  nameEn: s.nameEn,
  descAr: s.descAr,
  descEn: s.descEn,
  basePrice: parsePrice(s.startup.price),
  options: serviceOptions[s.id]
}));
export const builderAddOns = [
  { id: "seo", icon: Search, labelAr: "SEO \u0627\u062D\u062A\u0631\u0627\u0641\u064A", labelEn: "Professional SEO", descAr: "\u062A\u0647\u064A\u0626\u0629 \u0634\u0627\u0645\u0644\u0629 \u0644\u0645\u062D\u0631\u0643\u0627\u062A \u0627\u0644\u0628\u062D\u062B + \u0643\u0644\u0645\u0627\u062A \u0645\u0641\u062A\u0627\u062D\u064A\u0629.", descEn: "Full search-engine optimization + keywords.", price: 499 },
  { id: "maintenance", icon: Wrench, labelAr: "\u0628\u0627\u0642\u0629 \u0635\u064A\u0627\u0646\u0629 \u0634\u0647\u0631\u064A\u0629", labelEn: "Monthly maintenance", descAr: "\u062A\u062D\u062F\u064A\u062B\u0627\u062A \u0623\u0645\u0627\u0646 \u0648\u0646\u0633\u062E \u0627\u062D\u062A\u064A\u0627\u0637\u064A \u0648\u0625\u0635\u0644\u0627\u062D \u0623\u0639\u0637\u0627\u0644.", descEn: "Security updates, backups & bug fixes.", price: 99, recurring: true },
  { id: "hosting", icon: Server, labelAr: "\u0627\u0633\u062A\u0636\u0627\u0641\u0629 + \u0646\u0637\u0627\u0642 (\u0633\u0646\u0648\u064A)", labelEn: "Hosting + domain (yearly)", descAr: "\u0627\u0633\u062A\u0636\u0627\u0641\u0629 \u0633\u0631\u064A\u0639\u0629 + SSL + \u0646\u0637\u0627\u0642 \u0644\u0645\u062F\u0629 \u0633\u0646\u0629.", descEn: "Fast hosting + SSL + domain for a year.", price: 199 },
  { id: "content", icon: PenLine, labelAr: "\u0643\u062A\u0627\u0628\u0629 \u0645\u062D\u062A\u0648\u0649", labelEn: "Content writing", descAr: "\u0646\u0635\u0648\u0635 \u0627\u062D\u062A\u0631\u0627\u0641\u064A\u0629 \u0644\u0635\u0641\u062D\u0627\u062A\u0643 \u0628\u0644\u063A\u0629 \u062A\u0633\u0648\u064A\u0642\u064A\u0629.", descEn: "Professional marketing copy for your pages.", price: 299 },
  { id: "social", icon: Share2, labelAr: "\u0625\u062F\u0627\u0631\u0629 \u0633\u0648\u0634\u064A\u0627\u0644 \u0645\u064A\u062F\u064A\u0627", labelEn: "Social media management", descAr: "\u0645\u062D\u062A\u0648\u0649 \u0648\u062A\u0635\u0645\u064A\u0645 \u0648\u0646\u0634\u0631 \u0648\u062A\u0641\u0627\u0639\u0644 \u0634\u0647\u0631\u064A\u064B\u0627.", descEn: "Monthly content, design, posting & engagement.", price: 199, recurring: true },
  { id: "brand", icon: Palette, labelAr: "\u0647\u0648\u064A\u0629 \u0628\u0635\u0631\u064A\u0629", labelEn: "Brand identity", descAr: "\u0634\u0639\u0627\u0631 \u0648\u0623\u0644\u0648\u0627\u0646 \u0648\u062E\u0637\u0648\u0637 \u0648\u062F\u0644\u064A\u0644 \u0647\u0648\u064A\u0629.", descEn: "Logo, colors, fonts & brand guidelines.", price: 499 },
  { id: "apilink", icon: Plug, labelAr: "\u0631\u0628\u0637 API \u062E\u0627\u0631\u062C\u064A", labelEn: "External API integration", descAr: "\u0631\u0628\u0637 \u062E\u062F\u0645\u0629 \u0623\u0648 \u0646\u0638\u0627\u0645 \u062E\u0627\u0631\u062C\u064A \u0628\u0645\u0634\u0631\u0648\u0639\u0643.", descEn: "Connect an external service or system.", price: 399 }
];
