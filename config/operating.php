<?php

return json_decode(<<<'JSON'
{
  "groups": {
    "governance": {
      "label": {
        "ar": "الإدارة والقرارات",
        "en": "Management and decisions"
      },
      "read": [
        "owner",
        "delivery"
      ],
      "write": [
        "owner",
        "delivery"
      ]
    },
    "delivery": {
      "label": {
        "ar": "المشروعات والتطوير",
        "en": "Projects and development"
      },
      "read": [
        "owner",
        "delivery",
        "tech_lead",
        "developer",
        "qa"
      ],
      "write": [
        "owner",
        "delivery",
        "tech_lead",
        "developer"
      ]
    },
    "quality": {
      "label": {
        "ar": "الجودة والإطلاق",
        "en": "Quality and release"
      },
      "read": [
        "owner",
        "delivery",
        "tech_lead",
        "developer",
        "qa"
      ],
      "write": [
        "owner",
        "delivery",
        "tech_lead",
        "qa",
        "developer"
      ]
    },
    "marketing": {
      "label": {
        "ar": "التسويق والإنتاج",
        "en": "Marketing and production"
      },
      "read": [
        "owner",
        "marketing",
        "media_buyer",
        "designer",
        "video_editor"
      ],
      "write": [
        "owner",
        "marketing",
        "media_buyer"
      ]
    },
    "social": {
      "label": {
        "ar": "السوشيال ميديا",
        "en": "Social media"
      },
      "read": [
        "owner",
        "marketing",
        "media_buyer",
        "designer",
        "video_editor"
      ],
      "write": [
        "owner",
        "marketing"
      ]
    },
    "creative": {
      "label": {
        "ar": "التصميم والفيديو",
        "en": "Design and video"
      },
      "read": [
        "owner",
        "marketing",
        "designer",
        "video_editor",
        "media_buyer"
      ],
      "write": [
        "owner",
        "marketing",
        "designer",
        "video_editor"
      ]
    },
    "advertising": {
      "label": {
        "ar": "الإعلانات",
        "en": "Advertising"
      },
      "read": [
        "owner",
        "marketing",
        "media_buyer",
        "sales",
        "sales_manager"
      ],
      "write": [
        "owner",
        "media_buyer"
      ]
    },
    "sales": {
      "label": {
        "ar": "المبيعات والبيانات",
        "en": "Sales and data"
      },
      "read": [
        "owner",
        "delivery",
        "sales",
        "data_entry",
        "sales_manager"
      ],
      "write": [
        "owner",
        "sales",
        "data_entry",
        "sales_manager"
      ]
    },
    "people": {
      "label": {
        "ar": "الفريق والأداء",
        "en": "Team and performance"
      },
      "read": [
        "owner",
        "delivery",
        "hr",
        "developer",
        "tech_lead",
        "qa",
        "marketing",
        "designer",
        "video_editor",
        "media_buyer",
        "sales",
        "data_entry",
        "finance",
        "employee",
        "sales_manager"
      ],
      "write": [
        "owner",
        "hr",
        "delivery"
      ]
    },
    "finance": {
      "label": {
        "ar": "المالية والربحية",
        "en": "Finance and profitability"
      },
      "read": [
        "owner",
        "finance"
      ],
      "write": [
        "owner",
        "finance"
      ]
    },
    "support": {
      "label": {
        "ar": "الدعم والأصول",
        "en": "Support and assets"
      },
      "read": [
        "owner",
        "delivery",
        "tech_lead",
        "support"
      ],
      "write": [
        "owner",
        "delivery",
        "tech_lead",
        "support"
      ]
    },
    "seo": {
      "label": {
        "ar": "SEO وأخطاء الفهرسة",
        "en": "SEO and indexing errors"
      },
      "read": [
        "owner",
        "editor",
        "seo"
      ],
      "write": [
        "owner",
        "seo"
      ]
    }
  },
  "roles": {
    "owner": {
      "ar": "الإدارة / مالك",
      "en": "Management / owner"
    },
    "delivery": {
      "ar": "إدارة التسليم",
      "en": "Delivery manager"
    },
    "tech_lead": {
      "ar": "مسؤول فني",
      "en": "Technical lead"
    },
    "developer": {
      "ar": "مطور",
      "en": "Developer"
    },
    "qa": {
      "ar": "الجودة والاختبار",
      "en": "Quality assurance"
    },
    "marketing": {
      "ar": "تسويق وسوشيال",
      "en": "Marketing / social"
    },
    "designer": {
      "ar": "مصمم",
      "en": "Designer"
    },
    "video_editor": {
      "ar": "مونتير",
      "en": "Video editor"
    },
    "media_buyer": {
      "ar": "إعلانات",
      "en": "Media buyer"
    },
    "sales": {
      "ar": "مبيعات",
      "en": "Sales"
    },
    "sales_manager": {
      "ar": "مدير المبيعات",
      "en": "Sales manager"
    },
    "data_entry": {
      "ar": "إدخال بيانات",
      "en": "Data entry"
    },
    "finance": {
      "ar": "مالية",
      "en": "Finance"
    },
    "hr": {
      "ar": "شؤون الفريق",
      "en": "People operations"
    },
    "support": {
      "ar": "دعم وتشغيل",
      "en": "Support"
    },
    "employee": {
      "ar": "موظف",
      "en": "Employee"
    },
    "editor": {
      "ar": "محرر الموقع",
      "en": "Website editor"
    },
    "seo": {
      "ar": "مسؤول SEO",
      "en": "SEO specialist"
    }
  },
  "entities": {
    "clients": {
      "label": {
        "ar": "العملاء",
        "en": "Clients"
      },
      "group": "sales",
      "pages": [
        14,
        49
      ],
      "fields": [
        {
          "key": "company",
          "label": {
            "ar": "الشركة",
            "en": "Company"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "decision_maker",
          "label": {
            "ar": "صاحب القرار",
            "en": "Decision maker"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "contact",
          "label": {
            "ar": "وسيلة التواصل",
            "en": "Contact"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "email",
          "label": {
            "ar": "البريد",
            "en": "Email"
          },
          "type": "email",
          "required": false
        },
        {
          "key": "phone",
          "label": {
            "ar": "الهاتف",
            "en": "Phone"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "website",
          "label": {
            "ar": "الموقع الإلكتروني",
            "en": "Website"
          },
          "type": "url",
          "required": false
        },
        {
          "key": "industry",
          "label": {
            "ar": "المجال",
            "en": "Industry"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "country",
          "label": {
            "ar": "الدولة",
            "en": "Country"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "address",
          "label": {
            "ar": "العنوان",
            "en": "Address"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "tax_number",
          "label": {
            "ar": "الرقم الضريبي",
            "en": "Tax number"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "notes",
          "label": {
            "ar": "ملاحظات",
            "en": "Notes"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "draft",
        "active",
        "completed",
        "archived"
      ]
    },
    "contacts": {
      "label": {
        "ar": "جهات اتصال العملاء",
        "en": "Client contacts"
      },
      "group": "sales",
      "pages": [],
      "fields": [
        {
          "key": "client_id",
          "label": {
            "ar": "العميل",
            "en": "Client"
          },
          "type": "relation",
          "required": true,
          "entity": "clients"
        },
        {
          "key": "position",
          "label": {
            "ar": "المنصب",
            "en": "Position"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "phone",
          "label": {
            "ar": "الهاتف",
            "en": "Phone"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "email",
          "label": {
            "ar": "البريد الإلكتروني",
            "en": "Email"
          },
          "type": "email",
          "required": false
        },
        {
          "key": "is_primary",
          "label": {
            "ar": "جهة الاتصال الأساسية",
            "en": "Primary contact"
          },
          "type": "checkbox",
          "required": false
        },
        {
          "key": "notes",
          "label": {
            "ar": "ملاحظات",
            "en": "Notes"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "active",
        "archived"
      ]
    },
    "opportunities": {
      "label": {
        "ar": "الفرص وخط البيع",
        "en": "Sales pipeline"
      },
      "group": "sales",
      "pages": [
        14,
        41,
        55
      ],
      "fields": [
        {
          "key": "client_id",
          "label": {
            "ar": "العميل",
            "en": "Client"
          },
          "type": "relation",
          "required": false,
          "entity": "clients"
        },
        {
          "key": "lead_company",
          "label": {
            "ar": "شركة العميل المحتمل",
            "en": "Lead company"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "lead_contact",
          "label": {
            "ar": "اسم جهة الاتصال",
            "en": "Contact name"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "lead_phone",
          "label": {
            "ar": "الهاتف",
            "en": "Phone"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "lead_email",
          "label": {
            "ar": "البريد الإلكتروني",
            "en": "Email"
          },
          "type": "email",
          "required": false
        },
        {
          "key": "lead_source",
          "label": {
            "ar": "مصدر الفرصة",
            "en": "Lead source"
          },
          "type": "select",
          "required": true,
          "options": {
            "website": {
              "ar": "الموقع",
              "en": "Website"
            },
            "facebook": {
              "ar": "فيسبوك",
              "en": "Facebook"
            },
            "instagram": {
              "ar": "إنستجرام",
              "en": "Instagram"
            },
            "google": {
              "ar": "جوجل",
              "en": "Google"
            },
            "linkedin": {
              "ar": "لينكدإن",
              "en": "LinkedIn"
            },
            "referral": {
              "ar": "ترشيح",
              "en": "Referral"
            },
            "cold_call": {
              "ar": "اتصال مباشر",
              "en": "Cold call"
            },
            "event": {
              "ar": "فعالية",
              "en": "Event"
            },
            "other": {
              "ar": "أخرى",
              "en": "Other"
            }
          }
        },
        {
          "key": "assignee_id",
          "label": {
            "ar": "مندوب المبيعات المسؤول",
            "en": "Sales owner"
          },
          "type": "relation",
          "required": false,
          "entity": "employees"
        },
        {
          "key": "expected_value",
          "label": {
            "ar": "القيمة المتوقعة",
            "en": "Expected value"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "currency",
          "label": {
            "ar": "العملة",
            "en": "Currency"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "probability",
          "label": {
            "ar": "احتمالية الفوز %",
            "en": "Win probability %"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "expected_close",
          "label": {
            "ar": "تاريخ الإغلاق المتوقع",
            "en": "Expected close"
          },
          "type": "date",
          "required": false
        },
        {
          "key": "need",
          "label": {
            "ar": "الاحتياج والنتيجة المطلوبة",
            "en": "Business need and outcome"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "users",
          "label": {
            "ar": "المستخدمون المستهدفون",
            "en": "Target users"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "solution",
          "label": {
            "ar": "نوع الحل",
            "en": "Solution"
          },
          "type": "select",
          "required": true,
          "options": {
            "web": {
              "ar": "ويب",
              "en": "Web"
            },
            "app": {
              "ar": "تطبيق",
              "en": "App"
            },
            "integration": {
              "ar": "تكامل",
              "en": "Integration"
            },
            "support": {
              "ar": "دعم",
              "en": "Support"
            }
          }
        },
        {
          "key": "budget_range",
          "label": {
            "ar": "نطاق الميزانية / غير معلوم",
            "en": "Budget range / unknown"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "requested_date",
          "label": {
            "ar": "الموعد المطلوب",
            "en": "Requested date"
          },
          "type": "date",
          "required": false
        },
        {
          "key": "date_reason",
          "label": {
            "ar": "سبب الموعد",
            "en": "Deadline reason"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "source",
          "label": {
            "ar": "تفاصيل المصدر",
            "en": "Source details"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "next_action",
          "label": {
            "ar": "الخطوة التالية",
            "en": "Next action"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "follow_up",
          "label": {
            "ar": "موعد المتابعة",
            "en": "Follow-up date"
          },
          "type": "date",
          "required": true
        },
        {
          "key": "reason",
          "label": {
            "ar": "سبب التأجيل أو الخسارة",
            "en": "Deferred / lost reason"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "decision_maker",
          "label": {
            "ar": "صاحب القرار",
            "en": "Decision maker"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "handoff_summary",
          "label": {
            "ar": "ملخص التسليم والمراسلات",
            "en": "Handoff summary and correspondence"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "new",
        "contacted",
        "qualified",
        "discovery",
        "proposal",
        "negotiation",
        "won",
        "lost",
        "deferred",
        "unsuitable",
        "archived"
      ]
    },
    "activities": {
      "label": {
        "ar": "المتابعات والأنشطة",
        "en": "Follow-ups and activities"
      },
      "group": "sales",
      "pages": [],
      "assigned": true,
      "immutable": [
        "done",
        "cancelled",
        "archived"
      ],
      "fields": [
        {
          "key": "kind",
          "label": {
            "ar": "النوع",
            "en": "Type"
          },
          "type": "select",
          "required": true,
          "options": {
            "call": {
              "ar": "مكالمة",
              "en": "Call"
            },
            "meeting": {
              "ar": "اجتماع",
              "en": "Meeting"
            },
            "follow_up": {
              "ar": "متابعة",
              "en": "Follow-up"
            },
            "email": {
              "ar": "بريد",
              "en": "Email"
            },
            "whatsapp": {
              "ar": "واتساب",
              "en": "WhatsApp"
            },
            "task": {
              "ar": "مهمة",
              "en": "Task"
            },
            "note": {
              "ar": "ملاحظة",
              "en": "Note"
            }
          }
        },
        {
          "key": "assignee_id",
          "label": {
            "ar": "المسؤول",
            "en": "Owner"
          },
          "type": "relation",
          "required": true,
          "entity": "employees"
        },
        {
          "key": "due_at",
          "label": {
            "ar": "الموعد",
            "en": "Due at"
          },
          "type": "datetime-local",
          "required": true
        },
        {
          "key": "client_id",
          "label": {
            "ar": "العميل",
            "en": "Client"
          },
          "type": "relation",
          "required": false,
          "entity": "clients"
        },
        {
          "key": "opportunity_id",
          "label": {
            "ar": "الفرصة",
            "en": "Lead"
          },
          "type": "relation",
          "required": false,
          "entity": "opportunities"
        },
        {
          "key": "priority",
          "label": {
            "ar": "الأولوية",
            "en": "Priority"
          },
          "type": "select",
          "required": false,
          "options": {
            "low": {
              "ar": "منخفضة",
              "en": "Low"
            },
            "normal": {
              "ar": "عادية",
              "en": "Normal"
            },
            "high": {
              "ar": "عالية",
              "en": "High"
            }
          }
        },
        {
          "key": "agenda",
          "label": {
            "ar": "الهدف / جدول الأعمال",
            "en": "Agenda"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "outcome",
          "label": {
            "ar": "النتيجة",
            "en": "Outcome"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "next_step",
          "label": {
            "ar": "الخطوة التالية",
            "en": "Next step"
          },
          "type": "text",
          "required": false
        }
      ],
      "statuses": [
        "planned",
        "done",
        "cancelled",
        "archived"
      ]
    },
    "quotes": {
      "label": {
        "ar": "العروض التجارية",
        "en": "Quotes"
      },
      "group": "sales",
      "pages": [
        10,
        17
      ],
      "fields": [
        {
          "key": "opportunity_id",
          "label": {
            "ar": "الفرصة",
            "en": "Opportunity"
          },
          "type": "relation",
          "required": true,
          "entity": "opportunities"
        },
        {
          "key": "scope_id",
          "label": {
            "ar": "نسخة النطاق",
            "en": "Scope version"
          },
          "type": "relation",
          "required": false,
          "entity": "scopes"
        },
        {
          "key": "line_items",
          "label": {
            "ar": "بنود العرض",
            "en": "Line items"
          },
          "type": "lines",
          "required": false
        },
        {
          "key": "valid_until",
          "label": {
            "ar": "صالح حتى",
            "en": "Valid until"
          },
          "type": "date",
          "required": false
        },
        {
          "key": "terms",
          "label": {
            "ar": "الشروط والملاحظات",
            "en": "Terms and notes"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "price",
          "label": {
            "ar": "السعر الإجمالي (يُحسب من البنود إن وُجدت)",
            "en": "Total price (computed from line items when present)"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "discount",
          "label": {
            "ar": "خصم إضافي على العرض (مبلغ)",
            "en": "Extra quote discount (amount)"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "currency",
          "label": {
            "ar": "العملة",
            "en": "Currency"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "payment_terms",
          "label": {
            "ar": "شروط الدفع والبدء",
            "en": "Payment and start conditions"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "approval_evidence",
          "label": {
            "ar": "دليل الاعتماد",
            "en": "Approval evidence"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "draft",
        "submitted",
        "approved",
        "rejected",
        "archived"
      ],
      "approve_roles": [
        "owner"
      ],
      "immutable": [
        "approved",
        "archived"
      ]
    },
    "contracts": {
      "label": {
        "ar": "العقود",
        "en": "Contracts"
      },
      "group": "sales",
      "pages": [
        17,
        23
      ],
      "fields": [
        {
          "key": "client_id",
          "label": {
            "ar": "العميل",
            "en": "Client"
          },
          "type": "relation",
          "required": true,
          "entity": "clients"
        },
        {
          "key": "quote_id",
          "label": {
            "ar": "العرض المعتمد",
            "en": "Approved quote"
          },
          "type": "relation",
          "required": true,
          "entity": "quotes"
        },
        {
          "key": "scope_reference",
          "label": {
            "ar": "مرجع النطاق والتسليمات والاستبعادات",
            "en": "Scope, deliverables and exclusions"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "acceptance_policy",
          "label": {
            "ar": "سياسة القبول والمراجعات والتغيير",
            "en": "Acceptance, review and change policy"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "ownership",
          "label": {
            "ar": "الملكية والتسليم",
            "en": "Ownership and handover"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "warranty",
          "label": {
            "ar": "الضمان والصيانة والمدة",
            "en": "Warranty, support and duration"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "signed_at",
          "label": {
            "ar": "تاريخ التوقيع",
            "en": "Signature date"
          },
          "type": "date",
          "required": false
        },
        {
          "key": "client_evidence",
          "label": {
            "ar": "دليل موافقة العميل",
            "en": "Client agreement"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "draft",
        "submitted",
        "approved",
        "closed",
        "archived"
      ],
      "approve_roles": [
        "owner"
      ],
      "immutable": [
        "approved",
        "closed",
        "archived"
      ]
    },
    "handoffs": {
      "label": {
        "ar": "التسليم بين الأقسام",
        "en": "Department handoffs"
      },
      "group": "sales",
      "pages": [
        14,
        17,
        42
      ],
      "fields": [
        {
          "key": "opportunity_id",
          "label": {
            "ar": "الفرصة",
            "en": "Opportunity"
          },
          "type": "relation",
          "required": false,
          "entity": "opportunities"
        },
        {
          "key": "project_id",
          "label": {
            "ar": "المشروع",
            "en": "Project"
          },
          "type": "relation",
          "required": false,
          "entity": "projects"
        },
        {
          "key": "from_department",
          "label": {
            "ar": "من قسم",
            "en": "From department"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "to_department",
          "label": {
            "ar": "إلى قسم",
            "en": "To department"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "package",
          "label": {
            "ar": "ملف التسليم وروابطه",
            "en": "Handoff package and references"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "missing",
          "label": {
            "ar": "النواقص",
            "en": "Missing inputs"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "receiver_id",
          "label": {
            "ar": "المستلم",
            "en": "Receiver"
          },
          "type": "relation",
          "required": false,
          "entity": "employees"
        },
        {
          "key": "receipt_evidence",
          "label": {
            "ar": "دليل قبول الاستلام",
            "en": "Receipt evidence"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "draft",
        "submitted",
        "accepted",
        "returned",
        "archived"
      ]
    },
    "data_batches": {
      "label": {
        "ar": "إدخال البيانات وفحص الدقة",
        "en": "Data entry and validation"
      },
      "group": "sales",
      "pages": [
        41,
        42
      ],
      "fields": [
        {
          "key": "project_id",
          "label": {
            "ar": "المشروع",
            "en": "Project"
          },
          "type": "relation",
          "required": false,
          "entity": "projects"
        },
        {
          "key": "source",
          "label": {
            "ar": "مصدر البيانات",
            "en": "Data source"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "destination",
          "label": {
            "ar": "وجهة البيانات",
            "en": "Destination"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "validation",
          "label": {
            "ar": "معيار الصحة",
            "en": "Validation criteria"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "volume",
          "label": {
            "ar": "حجم البيانات",
            "en": "Volume"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "sample_size",
          "label": {
            "ar": "حجم العينة",
            "en": "Sample size"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "valid_rows",
          "label": {
            "ar": "سجلات صحيحة في العينة",
            "en": "Valid sample rows"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "duplicates",
          "label": {
            "ar": "التكرارات",
            "en": "Duplicates"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "missing_fields",
          "label": {
            "ar": "الحقول الناقصة",
            "en": "Missing fields"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "allocated_hours",
          "label": {
            "ar": "السعة المخصصة",
            "en": "Allocated hours"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "assignee_id",
          "label": {
            "ar": "المسؤول",
            "en": "Assignee"
          },
          "type": "relation",
          "required": true,
          "entity": "employees"
        },
        {
          "key": "evidence",
          "label": {
            "ar": "دليل / رابط التحقق",
            "en": "Evidence / verification reference"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "draft",
        "active",
        "completed",
        "archived"
      ],
      "assigned": true
    },
    "projects": {
      "label": {
        "ar": "المشروعات",
        "en": "Projects"
      },
      "group": "delivery",
      "pages": [
        13,
        17,
        18,
        23,
        47
      ],
      "fields": [
        {
          "key": "client_id",
          "label": {
            "ar": "العميل",
            "en": "Client"
          },
          "type": "relation",
          "required": true,
          "entity": "clients"
        },
        {
          "key": "project_type",
          "label": {
            "ar": "نوع المشروع",
            "en": "Project type"
          },
          "type": "select",
          "required": false,
          "options": {
            "web": {
              "ar": "موقع / نظام ويب",
              "en": "Web"
            },
            "mobile": {
              "ar": "تطبيق جوال",
              "en": "Mobile app"
            },
            "erp": {
              "ar": "ERP / نظام داخلي",
              "en": "ERP / internal system"
            },
            "ecommerce": {
              "ar": "متجر إلكتروني",
              "en": "E-commerce"
            },
            "wordpress": {
              "ar": "ووردبريس",
              "en": "WordPress"
            },
            "maintenance": {
              "ar": "صيانة",
              "en": "Maintenance"
            },
            "other": {
              "ar": "أخرى",
              "en": "Other"
            }
          }
        },
        {
          "key": "priority",
          "label": {
            "ar": "الأولوية",
            "en": "Priority"
          },
          "type": "select",
          "required": false,
          "options": {
            "low": {
              "ar": "منخفضة",
              "en": "Low"
            },
            "medium": {
              "ar": "متوسطة",
              "en": "Medium"
            },
            "high": {
              "ar": "عالية",
              "en": "High"
            },
            "critical": {
              "ar": "حرجة",
              "en": "Critical"
            }
          }
        },
        {
          "key": "contract_id",
          "label": {
            "ar": "العقد",
            "en": "Contract"
          },
          "type": "relation",
          "required": false,
          "entity": "contracts"
        },
        {
          "key": "quote_id",
          "label": {
            "ar": "العرض المعتمد المصدر",
            "en": "Source quote"
          },
          "type": "relation",
          "required": false,
          "entity": "quotes"
        },
        {
          "key": "outcome",
          "label": {
            "ar": "النتيجة المطلوبة",
            "en": "Expected outcome"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "manager_id",
          "label": {
            "ar": "مدير المشروع",
            "en": "Project manager"
          },
          "type": "relation",
          "required": false,
          "entity": "employees"
        },
        {
          "key": "tech_lead_id",
          "label": {
            "ar": "المسؤول الفني",
            "en": "Technical lead"
          },
          "type": "relation",
          "required": false,
          "entity": "employees"
        },
        {
          "key": "deploy_owner_id",
          "label": {
            "ar": "مسؤول النشر",
            "en": "Deployment owner"
          },
          "type": "relation",
          "required": false,
          "entity": "employees"
        },
        {
          "key": "backup_id",
          "label": {
            "ar": "البديل",
            "en": "Backup"
          },
          "type": "relation",
          "required": false,
          "entity": "employees"
        },
        {
          "key": "original_due",
          "label": {
            "ar": "الموعد الأصلي",
            "en": "Original due date"
          },
          "type": "date",
          "required": true
        },
        {
          "key": "approved_due",
          "label": {
            "ar": "الموعد المعتمد",
            "en": "Approved due date"
          },
          "type": "date",
          "required": false
        },
        {
          "key": "client_contact",
          "label": {
            "ar": "مسؤول العميل",
            "en": "Client contact"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "change_id",
          "label": {
            "ar": "تغيير الموعد المعتمد",
            "en": "Approved deadline change"
          },
          "type": "relation",
          "entity": "changes",
          "required": false
        },
        {
          "key": "client_channel",
          "label": {
            "ar": "قناة العميل",
            "en": "Client channel"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "start_checklist",
          "label": {
            "ar": "دليل جاهزية النطاق والدفع والسعة والمدخلات",
            "en": "Start readiness: scope, payment, capacity, inputs"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "support_window",
          "label": {
            "ar": "تغطية الدعم",
            "en": "Support coverage"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "client_acceptance",
          "label": {
            "ar": "قبول العميل",
            "en": "Client acceptance"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "closure",
          "label": {
            "ar": "التسليم والتدريب والدروس",
            "en": "Handover, training and lessons"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "discovery",
        "agreement",
        "planning",
        "execution",
        "acceptance",
        "launch",
        "closed",
        "archived"
      ]
    },
    "project_members": {
      "label": {
        "ar": "فريق المشروع",
        "en": "Project team"
      },
      "group": "delivery",
      "pages": [],
      "fields": [
        {
          "key": "project_id",
          "label": {
            "ar": "المشروع",
            "en": "Project"
          },
          "type": "relation",
          "required": true,
          "entity": "projects"
        },
        {
          "key": "assignee_id",
          "label": {
            "ar": "الموظف",
            "en": "Employee"
          },
          "type": "relation",
          "required": true,
          "entity": "employees"
        },
        {
          "key": "member_role",
          "label": {
            "ar": "الدور في المشروع",
            "en": "Project role"
          },
          "type": "select",
          "required": true,
          "options": {
            "pm": {
              "ar": "مدير المشروع",
              "en": "Project manager"
            },
            "tech_lead": {
              "ar": "قائد تقني",
              "en": "Tech lead"
            },
            "developer": {
              "ar": "مطور",
              "en": "Developer"
            },
            "designer": {
              "ar": "مصمم",
              "en": "Designer"
            },
            "ui_ux": {
              "ar": "مصمم UI/UX",
              "en": "UI/UX designer"
            },
            "qa": {
              "ar": "اختبار جودة",
              "en": "QA"
            },
            "devops": {
              "ar": "DevOps",
              "en": "DevOps"
            },
            "other": {
              "ar": "أخرى",
              "en": "Other"
            }
          }
        },
        {
          "key": "allocation_percent",
          "label": {
            "ar": "نسبة التخصيص %",
            "en": "Allocation %"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "start_date",
          "label": {
            "ar": "من",
            "en": "From"
          },
          "type": "date",
          "required": false
        },
        {
          "key": "end_date",
          "label": {
            "ar": "إلى",
            "en": "To"
          },
          "type": "date",
          "required": false
        }
      ],
      "statuses": [
        "active",
        "ended",
        "archived"
      ]
    },
    "sprints": {
      "label": {
        "ar": "السبرنتات",
        "en": "Sprints"
      },
      "group": "delivery",
      "pages": [],
      "fields": [
        {
          "key": "project_id",
          "label": {
            "ar": "المشروع",
            "en": "Project"
          },
          "type": "relation",
          "required": true,
          "entity": "projects"
        },
        {
          "key": "goal",
          "label": {
            "ar": "هدف السبرنت",
            "en": "Sprint goal"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "start_date",
          "label": {
            "ar": "البداية",
            "en": "Start date"
          },
          "type": "date",
          "required": true
        },
        {
          "key": "end_date",
          "label": {
            "ar": "النهاية",
            "en": "End date"
          },
          "type": "date",
          "required": true
        },
        {
          "key": "capacity_hours",
          "label": {
            "ar": "السعة المتاحة بالساعات",
            "en": "Capacity (hours)"
          },
          "type": "number",
          "required": false
        }
      ],
      "statuses": [
        "planned",
        "active",
        "completed",
        "archived"
      ],
      "immutable": [
        "completed",
        "archived"
      ]
    },
    "scopes": {
      "label": {
        "ar": "إصدارات النطاق",
        "en": "Scope versions"
      },
      "group": "delivery",
      "pages": [
        15,
        17,
        24,
        49
      ],
      "fields": [
        {
          "key": "project_id",
          "label": {
            "ar": "المشروع",
            "en": "Project"
          },
          "type": "relation",
          "required": true,
          "entity": "projects"
        },
        {
          "key": "scope_version",
          "label": {
            "ar": "رقم الإصدار",
            "en": "Version"
          },
          "type": "number",
          "required": true
        },
        {
          "key": "journeys",
          "label": {
            "ar": "الرحلات والأدوار والشاشات",
            "en": "Journeys, roles and screens"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "deliverables",
          "label": {
            "ar": "التسليمات والتكاملات",
            "en": "Deliverables and integrations"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "exclusions",
          "label": {
            "ar": "الاستبعادات",
            "en": "Exclusions"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "acceptance_criteria",
          "label": {
            "ar": "معايير القبول",
            "en": "Acceptance criteria"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "client_inputs",
          "label": {
            "ar": "مدخلات العميل",
            "en": "Client inputs"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "nonfunctional",
          "label": {
            "ar": "الأمن والأداء واللغات والأجهزة",
            "en": "Security, performance, languages, devices"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "assumptions",
          "label": {
            "ar": "الافتراضات والمجهولات",
            "en": "Assumptions and unknowns"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "review_rounds",
          "label": {
            "ar": "جولات المراجعة",
            "en": "Review rounds"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "client_approval",
          "label": {
            "ar": "دليل موافقة العميل",
            "en": "Client approval"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "draft",
        "submitted",
        "approved",
        "superseded",
        "archived"
      ],
      "approve_roles": [
        "owner"
      ],
      "immutable": [
        "approved",
        "superseded",
        "archived"
      ]
    },
    "requirements": {
      "label": {
        "ar": "المتطلبات",
        "en": "Requirements"
      },
      "group": "delivery",
      "pages": [
        15,
        19,
        22
      ],
      "fields": [
        {
          "key": "project_id",
          "label": {
            "ar": "المشروع",
            "en": "Project"
          },
          "type": "relation",
          "required": true,
          "entity": "projects"
        },
        {
          "key": "scope_id",
          "label": {
            "ar": "نسخة النطاق",
            "en": "Scope version"
          },
          "type": "relation",
          "required": true,
          "entity": "scopes"
        },
        {
          "key": "description",
          "label": {
            "ar": "وصف الوظيفة",
            "en": "Requirement"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "acceptance_criteria",
          "label": {
            "ar": "معايير القبول",
            "en": "Acceptance criteria"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "exclusions",
          "label": {
            "ar": "الاستبعادات",
            "en": "Exclusions"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "priority",
          "label": {
            "ar": "الأولوية",
            "en": "Priority"
          },
          "type": "text",
          "required": true
        }
      ],
      "statuses": [
        "draft",
        "active",
        "completed",
        "archived"
      ]
    },
    "milestones": {
      "label": {
        "ar": "محطات التسليم",
        "en": "Milestones"
      },
      "group": "delivery",
      "pages": [
        18,
        28,
        55
      ],
      "fields": [
        {
          "key": "project_id",
          "label": {
            "ar": "المشروع",
            "en": "Project"
          },
          "type": "relation",
          "required": true,
          "entity": "projects"
        },
        {
          "key": "assignee_id",
          "label": {
            "ar": "مسؤول المحطة",
            "en": "Milestone owner"
          },
          "type": "relation",
          "required": false,
          "entity": "employees"
        },
        {
          "key": "deliverable",
          "label": {
            "ar": "المخرج",
            "en": "Deliverable"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "acceptance_criteria",
          "label": {
            "ar": "معيار القبول",
            "en": "Acceptance criteria"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "original_due",
          "label": {
            "ar": "الموعد الأصلي",
            "en": "Original due date"
          },
          "type": "date",
          "required": true
        },
        {
          "key": "approved_due",
          "label": {
            "ar": "الموعد المعتمد",
            "en": "Approved due date"
          },
          "type": "date",
          "required": false
        },
        {
          "key": "change_id",
          "label": {
            "ar": "تغيير معتمد للموعد",
            "en": "Approved deadline change"
          },
          "type": "relation",
          "required": false,
          "entity": "changes"
        },
        {
          "key": "client_acceptance",
          "label": {
            "ar": "دليل قبول العميل",
            "en": "Client acceptance"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "accepted_at",
          "label": {
            "ar": "تاريخ القبول",
            "en": "Accepted date"
          },
          "type": "date",
          "required": false
        }
      ],
      "statuses": [
        "planned",
        "in_progress",
        "submitted",
        "accepted",
        "archived"
      ]
    },
    "deliverables": {
      "label": {
        "ar": "المخرجات",
        "en": "Deliverables"
      },
      "group": "delivery",
      "pages": [],
      "fields": [
        {
          "key": "milestone_id",
          "label": {
            "ar": "المحطة",
            "en": "Milestone"
          },
          "type": "relation",
          "required": true,
          "entity": "milestones"
        },
        {
          "key": "project_id",
          "label": {
            "ar": "المشروع (يُملأ من المحطة)",
            "en": "Project (taken from the milestone)"
          },
          "type": "relation",
          "required": false,
          "entity": "projects"
        },
        {
          "key": "assignee_id",
          "label": {
            "ar": "المسؤول",
            "en": "Owner"
          },
          "type": "relation",
          "required": false,
          "entity": "employees"
        },
        {
          "key": "due_date",
          "label": {
            "ar": "الموعد",
            "en": "Due date"
          },
          "type": "date",
          "required": true
        },
        {
          "key": "description",
          "label": {
            "ar": "الوصف ومعيار التسليم",
            "en": "Description and done criteria"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "delivery_url",
          "label": {
            "ar": "رابط التسليم",
            "en": "Delivery link"
          },
          "type": "url",
          "required": false
        },
        {
          "key": "evidence",
          "label": {
            "ar": "دليل التسليم",
            "en": "Delivery evidence"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "planned",
        "in_progress",
        "delivered",
        "archived"
      ]
    },
    "tasks": {
      "label": {
        "ar": "مهام التطوير",
        "en": "Development tasks"
      },
      "group": "delivery",
      "pages": [
        19,
        20,
        21,
        28,
        56
      ],
      "fields": [
        {
          "key": "project_id",
          "label": {
            "ar": "المشروع",
            "en": "Project"
          },
          "type": "relation",
          "required": true,
          "entity": "projects"
        },
        {
          "key": "requirement_id",
          "label": {
            "ar": "المتطلب",
            "en": "Requirement"
          },
          "type": "relation",
          "required": false,
          "entity": "requirements"
        },
        {
          "key": "sprint_id",
          "label": {
            "ar": "السبرنت",
            "en": "Sprint"
          },
          "type": "relation",
          "required": false,
          "entity": "sprints"
        },
        {
          "key": "assignee_id",
          "label": {
            "ar": "المسؤول",
            "en": "Assignee"
          },
          "type": "relation",
          "required": false,
          "entity": "employees"
        },
        {
          "key": "reviewer_id",
          "label": {
            "ar": "مراجع الكود",
            "en": "Code reviewer"
          },
          "type": "relation",
          "required": false,
          "entity": "employees"
        },
        {
          "key": "tester_id",
          "label": {
            "ar": "مسؤول الاختبار",
            "en": "Tester"
          },
          "type": "relation",
          "required": false,
          "entity": "employees"
        },
        {
          "key": "deliverable",
          "label": {
            "ar": "المخرج",
            "en": "Deliverable"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "acceptance_criteria",
          "label": {
            "ar": "معيار القبول",
            "en": "Acceptance criteria"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "inputs",
          "label": {
            "ar": "المدخلات والروابط",
            "en": "Inputs and references"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "priority",
          "label": {
            "ar": "الأولوية",
            "en": "Priority"
          },
          "type": "select",
          "required": false,
          "options": {
            "low": {
              "ar": "منخفضة",
              "en": "Low"
            },
            "medium": {
              "ar": "متوسطة",
              "en": "Medium"
            },
            "high": {
              "ar": "عالية",
              "en": "High"
            },
            "critical": {
              "ar": "حرجة",
              "en": "Critical"
            }
          }
        },
        {
          "key": "estimate_hours",
          "label": {
            "ar": "التقدير الأصلي بالساعات",
            "en": "Original estimate"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "remaining_hours",
          "label": {
            "ar": "الساعات المتبقية",
            "en": "Remaining hours"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "original_due",
          "label": {
            "ar": "الموعد الأصلي",
            "en": "Original due date"
          },
          "type": "date",
          "required": false
        },
        {
          "key": "approved_due",
          "label": {
            "ar": "الموعد المعتمد",
            "en": "Approved due date"
          },
          "type": "date",
          "required": false
        },
        {
          "key": "change_id",
          "label": {
            "ar": "التغيير المعتمد",
            "en": "Approved change"
          },
          "type": "relation",
          "required": false,
          "entity": "changes"
        },
        {
          "key": "dependency_id",
          "label": {
            "ar": "المهمة السابقة",
            "en": "Dependency"
          },
          "type": "relation",
          "required": false,
          "entity": "tasks"
        },
        {
          "key": "implementation_url",
          "label": {
            "ar": "رابط التنفيذ / PR",
            "en": "Implementation / PR"
          },
          "type": "url",
          "required": false
        },
        {
          "key": "build_version",
          "label": {
            "ar": "إصدار الاختبار",
            "en": "Test build"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "review_evidence",
          "label": {
            "ar": "دليل المراجعة المستقلة",
            "en": "Independent review evidence"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "test_evidence",
          "label": {
            "ar": "دليل الاختبار",
            "en": "Test evidence"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "closure_reason",
          "label": {
            "ar": "سبب الإغلاق",
            "en": "Closure reason"
          },
          "type": "text",
          "required": false
        }
      ],
      "statuses": [
        "backlog",
        "ready",
        "in_progress",
        "code_review",
        "qa",
        "ready_to_release",
        "done",
        "archived"
      ],
      "assigned": true,
      "immutable": [
        "done",
        "archived"
      ]
    },
    "estimates": {
      "label": {
        "ar": "التقديرات الفنية",
        "en": "Technical estimates"
      },
      "group": "delivery",
      "pages": [
        16,
        26
      ],
      "fields": [
        {
          "key": "project_id",
          "label": {
            "ar": "المشروع",
            "en": "Project"
          },
          "type": "relation",
          "required": true,
          "entity": "projects"
        },
        {
          "key": "task_id",
          "label": {
            "ar": "المهمة",
            "en": "Task"
          },
          "type": "relation",
          "required": false,
          "entity": "tasks"
        },
        {
          "key": "development",
          "label": {
            "ar": "التطوير",
            "en": "Development hours"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "review",
          "label": {
            "ar": "المراجعة",
            "en": "Review hours"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "testing",
          "label": {
            "ar": "الاختبار والإصلاح",
            "en": "Testing / fixes hours"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "deployment",
          "label": {
            "ar": "النشر والتنسيق",
            "en": "Deployment / coordination hours"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "low",
          "label": {
            "ar": "الحد الأدنى",
            "en": "Low estimate"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "high",
          "label": {
            "ar": "الحد الأعلى",
            "en": "High estimate"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "confidence",
          "label": {
            "ar": "مستوى الثقة",
            "en": "Confidence"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "assumptions",
          "label": {
            "ar": "الافتراضات والمخاطر والتبعيات",
            "en": "Assumptions, risks, dependencies"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "reviewer_id",
          "label": {
            "ar": "المراجع الفني",
            "en": "Technical reviewer"
          },
          "type": "relation",
          "required": false,
          "entity": "employees"
        },
        {
          "key": "evidence",
          "label": {
            "ar": "دليل / رابط التحقق",
            "en": "Evidence / verification reference"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "draft",
        "active",
        "completed",
        "archived"
      ],
      "approve_roles": [
        "owner",
        "tech_lead"
      ]
    },
    "time_entries": {
      "label": {
        "ar": "الوقت الفعلي",
        "en": "Time entries"
      },
      "group": "delivery",
      "pages": [
        27,
        28,
        32
      ],
      "fields": [
        {
          "key": "project_id",
          "label": {
            "ar": "المشروع",
            "en": "Project"
          },
          "type": "relation",
          "required": true,
          "entity": "projects"
        },
        {
          "key": "task_id",
          "label": {
            "ar": "المهمة",
            "en": "Task"
          },
          "type": "relation",
          "required": false,
          "entity": "tasks"
        },
        {
          "key": "assignee_id",
          "label": {
            "ar": "المسؤول",
            "en": "Assignee"
          },
          "type": "relation",
          "required": true,
          "entity": "employees"
        },
        {
          "key": "work_date",
          "label": {
            "ar": "التاريخ",
            "en": "Work date"
          },
          "type": "date",
          "required": true
        },
        {
          "key": "hours",
          "label": {
            "ar": "الساعات",
            "en": "Hours"
          },
          "type": "number",
          "required": true
        },
        {
          "key": "category",
          "label": {
            "ar": "نوع الوقت",
            "en": "Work type"
          },
          "type": "select",
          "required": true,
          "options": {
            "development": {
              "ar": "تطوير",
              "en": "Development"
            },
            "review": {
              "ar": "مراجعة",
              "en": "Review"
            },
            "qa": {
              "ar": "اختبار",
              "en": "QA"
            },
            "rework": {
              "ar": "إعادة عمل",
              "en": "Rework"
            },
            "change": {
              "ar": "تغيير معتمد",
              "en": "Approved change"
            },
            "courtesy": {
              "ar": "مجاملة",
              "en": "Courtesy"
            },
            "waiting": {
              "ar": "انتظار",
              "en": "Waiting"
            },
            "support": {
              "ar": "دعم",
              "en": "Support"
            }
          }
        },
        {
          "key": "reason",
          "label": {
            "ar": "السبب والدليل",
            "en": "Reason and evidence"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "draft",
        "active",
        "completed",
        "archived"
      ],
      "assigned": true
    },
    "blockers": {
      "label": {
        "ar": "العوائق وأسباب التأخير",
        "en": "Blockers and delay causes"
      },
      "group": "delivery",
      "pages": [
        12,
        27,
        28
      ],
      "fields": [
        {
          "key": "project_id",
          "label": {
            "ar": "المشروع",
            "en": "Project"
          },
          "type": "relation",
          "required": true,
          "entity": "projects"
        },
        {
          "key": "task_id",
          "label": {
            "ar": "المهمة",
            "en": "Task"
          },
          "type": "relation",
          "required": true,
          "entity": "tasks"
        },
        {
          "key": "cause",
          "label": {
            "ar": "سبب التأخير",
            "en": "Delay cause"
          },
          "type": "select",
          "required": true,
          "options": {
            "execution": {
              "ar": "تنفيذ داخلي",
              "en": "Internal execution"
            },
            "estimate": {
              "ar": "تقدير ناقص",
              "en": "Incomplete estimate"
            },
            "client": {
              "ar": "انتظار عميل",
              "en": "Client waiting"
            },
            "scope": {
              "ar": "تغيير نطاق",
              "en": "Scope change"
            },
            "review": {
              "ar": "انتظار مراجعة",
              "en": "Review queue"
            },
            "qa": {
              "ar": "انتظار اختبار",
              "en": "QA queue"
            },
            "priority": {
              "ar": "أولوية أخرى",
              "en": "Priority change"
            },
            "external": {
              "ar": "عطل خارجي",
              "en": "External outage"
            }
          }
        },
        {
          "key": "impact",
          "label": {
            "ar": "الأثر",
            "en": "Impact"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "resolver_id",
          "label": {
            "ar": "صاحب الحل",
            "en": "Resolver"
          },
          "type": "relation",
          "required": false,
          "entity": "employees"
        },
        {
          "key": "paused_stage",
          "label": {
            "ar": "مرحلة التوقف",
            "en": "Paused stage"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "started_at",
          "label": {
            "ar": "بداية العائق",
            "en": "Blocked since"
          },
          "type": "datetime-local",
          "required": true
        },
        {
          "key": "resolved_at",
          "label": {
            "ar": "نهاية العائق",
            "en": "Resolved at"
          },
          "type": "datetime-local",
          "required": false
        },
        {
          "key": "review_date",
          "label": {
            "ar": "موعد المراجعة",
            "en": "Review date"
          },
          "type": "date",
          "required": true
        },
        {
          "key": "evidence",
          "label": {
            "ar": "دليل / رابط التحقق",
            "en": "Evidence / verification reference"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "open",
        "escalated",
        "resolved",
        "archived"
      ]
    },
    "changes": {
      "label": {
        "ar": "طلبات التغيير",
        "en": "Change requests"
      },
      "group": "delivery",
      "pages": [
        24,
        25,
        26,
        56
      ],
      "fields": [
        {
          "key": "project_id",
          "label": {
            "ar": "المشروع",
            "en": "Project"
          },
          "type": "relation",
          "required": true,
          "entity": "projects"
        },
        {
          "key": "scope_id",
          "label": {
            "ar": "النطاق الحالي",
            "en": "Current scope"
          },
          "type": "relation",
          "required": true,
          "entity": "scopes"
        },
        {
          "key": "requester",
          "label": {
            "ar": "صاحب الطلب",
            "en": "Requester"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "requested_at",
          "label": {
            "ar": "تاريخ الطلب",
            "en": "Request date"
          },
          "type": "date",
          "required": true
        },
        {
          "key": "request",
          "label": {
            "ar": "نص الطلب",
            "en": "Request"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "classification",
          "label": {
            "ar": "التصنيف",
            "en": "Classification"
          },
          "type": "select",
          "required": false,
          "options": {
            "defect": {
              "ar": "عيب",
              "en": "Defect"
            },
            "clarification": {
              "ar": "توضيح",
              "en": "Clarification"
            },
            "revision": {
              "ar": "مراجعة متفق عليها",
              "en": "Agreed revision"
            },
            "scope": {
              "ar": "تغيير نطاق",
              "en": "Scope change"
            },
            "courtesy": {
              "ar": "مجاملة تجارية",
              "en": "Commercial courtesy"
            }
          }
        },
        {
          "key": "effort",
          "label": {
            "ar": "الجهد",
            "en": "Effort hours"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "cost",
          "label": {
            "ar": "التكلفة",
            "en": "Cost"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "impact",
          "label": {
            "ar": "أثر الوقت والمحطات والمخاطر والتبعيات",
            "en": "Time, milestone, risk and dependency impact"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "options",
          "label": {
            "ar": "إضافة / استبدال / تأجيل / رفض",
            "en": "Add / replace / defer / reject options"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "effective_date",
          "label": {
            "ar": "تاريخ السريان",
            "en": "Effective date"
          },
          "type": "date",
          "required": false
        },
        {
          "key": "new_due",
          "label": {
            "ar": "الموعد الجديد",
            "en": "New deadline"
          },
          "type": "date",
          "required": false
        },
        {
          "key": "client_approval",
          "label": {
            "ar": "دليل موافقة العميل",
            "en": "Client approval"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "start_conditions",
          "label": {
            "ar": "شروط بدء التنفيذ",
            "en": "Start conditions"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "draft",
        "classified",
        "estimated",
        "submitted",
        "approved",
        "rejected",
        "deferred",
        "implemented",
        "archived"
      ],
      "approve_roles": [
        "owner"
      ],
      "immutable": [
        "approved",
        "implemented",
        "archived"
      ]
    },
    "risks": {
      "label": {
        "ar": "المخاطر والتصعيد",
        "en": "Risks and escalation"
      },
      "group": "governance",
      "pages": [
        12,
        28,
        47,
        57
      ],
      "fields": [
        {
          "key": "project_id",
          "label": {
            "ar": "المشروع",
            "en": "Project"
          },
          "type": "relation",
          "required": false,
          "entity": "projects"
        },
        {
          "key": "problem",
          "label": {
            "ar": "المشكلة والدليل",
            "en": "Problem and evidence"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "severity",
          "label": {
            "ar": "الأثر",
            "en": "Severity"
          },
          "type": "select",
          "required": true,
          "options": {
            "low": {
              "ar": "منخفض",
              "en": "Low"
            },
            "medium": {
              "ar": "متوسط",
              "en": "Medium"
            },
            "high": {
              "ar": "عالٍ",
              "en": "High"
            },
            "critical": {
              "ar": "حرج",
              "en": "Critical"
            }
          }
        },
        {
          "key": "alternatives",
          "label": {
            "ar": "البدائل وأثرها",
            "en": "Alternatives and impact"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "decision_needed",
          "label": {
            "ar": "القرار المطلوب",
            "en": "Decision needed"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "decision_due",
          "label": {
            "ar": "موعد القرار",
            "en": "Decision due"
          },
          "type": "date",
          "required": false
        },
        {
          "key": "resolver_id",
          "label": {
            "ar": "مسؤول المعالجة",
            "en": "Resolver"
          },
          "type": "relation",
          "required": false,
          "entity": "employees"
        },
        {
          "key": "evidence",
          "label": {
            "ar": "دليل / رابط التحقق",
            "en": "Evidence / verification reference"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "open",
        "escalated",
        "mitigated",
        "closed",
        "archived"
      ]
    },
    "reviews": {
      "label": {
        "ar": "مراجعات الكود",
        "en": "Code reviews"
      },
      "group": "quality",
      "pages": [
        19,
        21
      ],
      "fields": [
        {
          "key": "project_id",
          "label": {
            "ar": "المشروع",
            "en": "Project"
          },
          "type": "relation",
          "required": true,
          "entity": "projects"
        },
        {
          "key": "task_id",
          "label": {
            "ar": "المهمة",
            "en": "Task"
          },
          "type": "relation",
          "required": true,
          "entity": "tasks"
        },
        {
          "key": "reviewer_id",
          "label": {
            "ar": "المراجع",
            "en": "Reviewer"
          },
          "type": "relation",
          "required": false,
          "entity": "employees"
        },
        {
          "key": "pr_url",
          "label": {
            "ar": "رابط PR",
            "en": "PR URL"
          },
          "type": "url",
          "required": true
        },
        {
          "key": "build_version",
          "label": {
            "ar": "الإصدار",
            "en": "Build"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "result",
          "label": {
            "ar": "النتيجة",
            "en": "Result"
          },
          "type": "select",
          "required": true,
          "options": {
            "pending": {
              "ar": "بانتظار المراجعة",
              "en": "Pending"
            },
            "pass": {
              "ar": "ناجح",
              "en": "Pass"
            },
            "fail": {
              "ar": "يتطلب تعديلًا",
              "en": "Fail"
            }
          }
        },
        {
          "key": "checks",
          "label": {
            "ar": "فحوص البناء والاختبارات والأمن",
            "en": "Build, test and security checks"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "evidence",
          "label": {
            "ar": "دليل المراجعة",
            "en": "Review evidence"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "notes",
          "label": {
            "ar": "الملاحظات",
            "en": "Notes"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "pending",
        "passed",
        "failed",
        "archived"
      ],
      "assigned": true
    },
    "test_cases": {
      "label": {
        "ar": "حالات الاختبار",
        "en": "Test cases"
      },
      "group": "quality",
      "pages": [
        22
      ],
      "fields": [
        {
          "key": "project_id",
          "label": {
            "ar": "المشروع",
            "en": "Project"
          },
          "type": "relation",
          "required": true,
          "entity": "projects"
        },
        {
          "key": "requirement_id",
          "label": {
            "ar": "المتطلب",
            "en": "Requirement"
          },
          "type": "relation",
          "required": true,
          "entity": "requirements"
        },
        {
          "key": "task_id",
          "label": {
            "ar": "المهمة",
            "en": "Task"
          },
          "type": "relation",
          "required": false,
          "entity": "tasks"
        },
        {
          "key": "tester_id",
          "label": {
            "ar": "المختبر",
            "en": "Tester"
          },
          "type": "relation",
          "required": false,
          "entity": "employees"
        },
        {
          "key": "steps",
          "label": {
            "ar": "الخطوات والبيانات",
            "en": "Steps and data"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "expected",
          "label": {
            "ar": "المتوقع",
            "en": "Expected result"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "actual",
          "label": {
            "ar": "الفعلي",
            "en": "Actual result"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "environment",
          "label": {
            "ar": "البيئة",
            "en": "Environment"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "build_version",
          "label": {
            "ar": "الإصدار",
            "en": "Build"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "result",
          "label": {
            "ar": "النتيجة",
            "en": "Result"
          },
          "type": "select",
          "required": true,
          "options": {
            "untested": {
              "ar": "غير مختبر",
              "en": "Untested"
            },
            "pass": {
              "ar": "ناجح",
              "en": "Pass"
            },
            "fail": {
              "ar": "فاشل",
              "en": "Fail"
            }
          }
        },
        {
          "key": "evidence",
          "label": {
            "ar": "دليل / رابط التحقق",
            "en": "Evidence / verification reference"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "untested",
        "passed",
        "failed",
        "archived"
      ]
    },
    "defects": {
      "label": {
        "ar": "العيوب وإعادة الاختبار",
        "en": "Defects and retests"
      },
      "group": "quality",
      "pages": [
        22,
        23
      ],
      "fields": [
        {
          "key": "project_id",
          "label": {
            "ar": "المشروع",
            "en": "Project"
          },
          "type": "relation",
          "required": true,
          "entity": "projects"
        },
        {
          "key": "task_id",
          "label": {
            "ar": "المهمة",
            "en": "Task"
          },
          "type": "relation",
          "required": false,
          "entity": "tasks"
        },
        {
          "key": "test_id",
          "label": {
            "ar": "حالة الاختبار",
            "en": "Test case"
          },
          "type": "relation",
          "required": false,
          "entity": "test_cases"
        },
        {
          "key": "build_version",
          "label": {
            "ar": "الإصدار",
            "en": "Build"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "environment",
          "label": {
            "ar": "البيئة",
            "en": "Environment"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "steps",
          "label": {
            "ar": "إعادة المشكلة",
            "en": "Reproduction steps"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "expected",
          "label": {
            "ar": "النتيجة المتوقعة",
            "en": "Expected"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "actual",
          "label": {
            "ar": "النتيجة الفعلية",
            "en": "Actual"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "severity",
          "label": {
            "ar": "الشدة",
            "en": "Severity"
          },
          "type": "select",
          "required": true,
          "options": {
            "low": {
              "ar": "منخفض",
              "en": "Low"
            },
            "medium": {
              "ar": "متوسط",
              "en": "Medium"
            },
            "high": {
              "ar": "عالٍ",
              "en": "High"
            },
            "critical": {
              "ar": "حرج",
              "en": "Critical"
            }
          }
        },
        {
          "key": "assignee_id",
          "label": {
            "ar": "المسؤول",
            "en": "Assignee"
          },
          "type": "relation",
          "required": false,
          "entity": "employees"
        },
        {
          "key": "fix_evidence",
          "label": {
            "ar": "دليل الإصلاح",
            "en": "Fix evidence"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "retest_evidence",
          "label": {
            "ar": "دليل إعادة الاختبار",
            "en": "Retest evidence"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "open",
        "in_progress",
        "fixed",
        "verified",
        "reopened",
        "archived"
      ]
    },
    "releases": {
      "label": {
        "ar": "الإصدارات والجاهزية",
        "en": "Releases and readiness"
      },
      "group": "quality",
      "pages": [
        22,
        23,
        55
      ],
      "fields": [
        {
          "key": "project_id",
          "label": {
            "ar": "المشروع",
            "en": "Project"
          },
          "type": "relation",
          "required": true,
          "entity": "projects"
        },
        {
          "key": "build_version",
          "label": {
            "ar": "الإصدار",
            "en": "Build"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "scope",
          "label": {
            "ar": "نطاق الإصدار وروابط المهام",
            "en": "Release scope and task references"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "test_report",
          "label": {
            "ar": "تقرير ناجح / فاشل / غير مختبر",
            "en": "Pass / fail / untested report"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "conditions",
          "label": {
            "ar": "شروط الجاهزية",
            "en": "Readiness conditions"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "qa_owner_id",
          "label": {
            "ar": "مسؤول الجودة",
            "en": "QA owner"
          },
          "type": "relation",
          "required": false,
          "entity": "employees"
        }
      ],
      "statuses": [
        "draft",
        "testing",
        "ready",
        "not_ready",
        "released",
        "archived"
      ]
    },
    "acceptances": {
      "label": {
        "ar": "قبول العميل",
        "en": "Client acceptance"
      },
      "group": "quality",
      "pages": [
        18,
        23
      ],
      "fields": [
        {
          "key": "project_id",
          "label": {
            "ar": "المشروع",
            "en": "Project"
          },
          "type": "relation",
          "required": true,
          "entity": "projects"
        },
        {
          "key": "release_id",
          "label": {
            "ar": "الإصدار",
            "en": "Release"
          },
          "type": "relation",
          "required": true,
          "entity": "releases"
        },
        {
          "key": "approver",
          "label": {
            "ar": "صاحب الموافقة",
            "en": "Approver"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "accepted_at",
          "label": {
            "ar": "تاريخ القبول",
            "en": "Acceptance date"
          },
          "type": "date",
          "required": false
        },
        {
          "key": "evidence",
          "label": {
            "ar": "دليل قبول صريح",
            "en": "Explicit acceptance evidence"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "exceptions",
          "label": {
            "ar": "استثناءات متفق عليها",
            "en": "Agreed exceptions"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "pending",
        "accepted",
        "rejected",
        "archived"
      ]
    },
    "deployments": {
      "label": {
        "ar": "النشر والتسليم",
        "en": "Deployments and handover"
      },
      "group": "quality",
      "pages": [
        23,
        34
      ],
      "fields": [
        {
          "key": "project_id",
          "label": {
            "ar": "المشروع",
            "en": "Project"
          },
          "type": "relation",
          "required": true,
          "entity": "projects"
        },
        {
          "key": "release_id",
          "label": {
            "ar": "الإصدار",
            "en": "Release"
          },
          "type": "relation",
          "required": true,
          "entity": "releases"
        },
        {
          "key": "acceptance_id",
          "label": {
            "ar": "قبول العميل",
            "en": "Client acceptance"
          },
          "type": "relation",
          "required": false,
          "entity": "acceptances"
        },
        {
          "key": "deploy_owner_id",
          "label": {
            "ar": "مسؤول النشر",
            "en": "Deployment owner"
          },
          "type": "relation",
          "required": false,
          "entity": "employees"
        },
        {
          "key": "backup_id",
          "label": {
            "ar": "البديل",
            "en": "Backup"
          },
          "type": "relation",
          "required": false,
          "entity": "employees"
        },
        {
          "key": "backup_evidence",
          "label": {
            "ar": "نسخة احتياطية واختبار استعادة",
            "en": "Backup and restore test"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "rollback",
          "label": {
            "ar": "خطة الرجوع",
            "en": "Rollback plan"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "monitoring",
          "label": {
            "ar": "المراقبة والسجلات",
            "en": "Monitoring and logs"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "smoke_test",
          "label": {
            "ar": "دليل فحص بعد النشر",
            "en": "Post-deploy smoke test"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "handover",
          "label": {
            "ar": "الحسابات والتوثيق والتدريب",
            "en": "Access, docs and training"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "warranty_start",
          "label": {
            "ar": "بداية الضمان",
            "en": "Warranty start"
          },
          "type": "date",
          "required": false
        },
        {
          "key": "warranty_end",
          "label": {
            "ar": "نهاية الضمان",
            "en": "Warranty end"
          },
          "type": "date",
          "required": false
        }
      ],
      "statuses": [
        "draft",
        "approved",
        "deployed",
        "verified",
        "rolled_back",
        "archived"
      ],
      "approve_roles": [
        "owner",
        "tech_lead"
      ],
      "immutable": [
        "verified",
        "rolled_back",
        "archived"
      ]
    },
    "briefs": {
      "label": {
        "ar": "طلبات الإنتاج والتسويق",
        "en": "Marketing production briefs"
      },
      "group": "marketing",
      "pages": [
        35,
        42
      ],
      "fields": [
        {
          "key": "audience",
          "label": {
            "ar": "الجمهور",
            "en": "Audience"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "platform",
          "label": {
            "ar": "المنصة",
            "en": "Platform"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "dimensions",
          "label": {
            "ar": "المقاس",
            "en": "Dimensions"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "objective",
          "label": {
            "ar": "الهدف والرسالة",
            "en": "Objective and message"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "copy",
          "label": {
            "ar": "النص المعتمد",
            "en": "Approved copy"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "assets",
          "label": {
            "ar": "الأصول والمراجع",
            "en": "Assets and references"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "acceptance_criteria",
          "label": {
            "ar": "معيار القبول",
            "en": "Acceptance criteria"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "assignee_id",
          "label": {
            "ar": "المسؤول",
            "en": "Assignee"
          },
          "type": "relation",
          "required": false,
          "entity": "employees"
        },
        {
          "key": "approver_id",
          "label": {
            "ar": "صاحب الموافقة",
            "en": "Approver"
          },
          "type": "relation",
          "required": false,
          "entity": "employees"
        },
        {
          "key": "versions",
          "label": {
            "ar": "عدد النسخ",
            "en": "Versions"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "review_rounds",
          "label": {
            "ar": "جولات المراجعة",
            "en": "Review rounds"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "due_date",
          "label": {
            "ar": "الموعد",
            "en": "Due date"
          },
          "type": "date",
          "required": true
        },
        {
          "key": "project_id",
          "label": {
            "ar": "مشروع العميل المدفوع إن وجد",
            "en": "Paid client project, if any"
          },
          "type": "relation",
          "required": false,
          "entity": "projects"
        },
        {
          "key": "feedback",
          "label": {
            "ar": "ملاحظات مجمعة",
            "en": "Consolidated feedback"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "approval_evidence",
          "label": {
            "ar": "دليل الاعتماد",
            "en": "Approval evidence"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "draft",
        "briefed",
        "production",
        "review",
        "approved",
        "published",
        "learning",
        "archived"
      ],
      "approve_roles": [
        "owner",
        "marketing"
      ]
    },
    "social_posts": {
      "label": {
        "ar": "تقويم السوشيال ميديا",
        "en": "Social media calendar"
      },
      "group": "social",
      "pages": [
        35,
        36
      ],
      "fields": [
        {
          "key": "brief_id",
          "label": {
            "ar": "طلب الإنتاج",
            "en": "Brief"
          },
          "type": "relation",
          "required": true,
          "entity": "briefs"
        },
        {
          "key": "platform",
          "label": {
            "ar": "المنصة",
            "en": "Platform"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "copy",
          "label": {
            "ar": "النص",
            "en": "Copy"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "publish_date",
          "label": {
            "ar": "موعد النشر",
            "en": "Publish date"
          },
          "type": "date",
          "required": true
        },
        {
          "key": "asset_url",
          "label": {
            "ar": "رابط الأصل",
            "en": "Asset reference"
          },
          "type": "url",
          "required": false
        },
        {
          "key": "sensitive",
          "label": {
            "ar": "محتوى حساس / بيانات عميل",
            "en": "Sensitive content / client information"
          },
          "type": "checkbox",
          "required": false
        },
        {
          "key": "client_consent",
          "label": {
            "ar": "موافقة العميل",
            "en": "Client consent"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "approval_evidence",
          "label": {
            "ar": "دليل الموافقة",
            "en": "Approval evidence"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "published_url",
          "label": {
            "ar": "رابط المنشور",
            "en": "Published URL"
          },
          "type": "url",
          "required": false
        },
        {
          "key": "channel",
          "label": {
            "ar": "مدفوع / عضوي",
            "en": "Paid / organic"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "learning",
          "label": {
            "ar": "النتيجة والتعلم",
            "en": "Results and learning"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "draft",
        "review",
        "approved",
        "scheduled",
        "published",
        "archived"
      ],
      "approve_roles": [
        "owner",
        "marketing"
      ]
    },
    "experiments": {
      "label": {
        "ar": "تجارب المحتوى",
        "en": "Content experiments"
      },
      "group": "social",
      "pages": [
        36,
        37
      ],
      "fields": [
        {
          "key": "hypothesis",
          "label": {
            "ar": "الفرضية",
            "en": "Hypothesis"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "variable",
          "label": {
            "ar": "المتغير",
            "en": "Variable"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "metric",
          "label": {
            "ar": "المقياس",
            "en": "Metric"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "baseline",
          "label": {
            "ar": "خط الأساس ومصدره",
            "en": "Baseline and source"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "platform",
          "label": {
            "ar": "المنصة",
            "en": "Platform"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "channel",
          "label": {
            "ar": "مدفوع / عضوي",
            "en": "Paid / organic"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "sample_a",
          "label": {
            "ar": "عينة A",
            "en": "Sample A"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "outcome_a",
          "label": {
            "ar": "نتيجة A",
            "en": "Outcome A"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "sample_b",
          "label": {
            "ar": "عينة B",
            "en": "Sample B"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "outcome_b",
          "label": {
            "ar": "نتيجة B",
            "en": "Outcome B"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "start_date",
          "label": {
            "ar": "البداية",
            "en": "Start date"
          },
          "type": "date",
          "required": false
        },
        {
          "key": "end_date",
          "label": {
            "ar": "النهاية",
            "en": "End date"
          },
          "type": "date",
          "required": false
        },
        {
          "key": "decision",
          "label": {
            "ar": "قرار التعلم",
            "en": "Learning decision"
          },
          "type": "select",
          "required": false,
          "options": {
            "repeat": {
              "ar": "تكرار",
              "en": "Repeat"
            },
            "change": {
              "ar": "تعديل",
              "en": "Change"
            },
            "stop": {
              "ar": "إيقاف",
              "en": "Stop"
            },
            "insufficient": {
              "ar": "بيانات غير كافية",
              "en": "Insufficient data"
            }
          }
        },
        {
          "key": "evidence",
          "label": {
            "ar": "دليل / رابط التحقق",
            "en": "Evidence / verification reference"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "planned",
        "running",
        "review",
        "completed",
        "archived"
      ]
    },
    "listening": {
      "label": {
        "ar": "الاستماع وتحليل المنافسين",
        "en": "Listening and competitor analysis"
      },
      "group": "social",
      "pages": [
        36
      ],
      "fields": [
        {
          "key": "kind",
          "label": {
            "ar": "النوع",
            "en": "Type"
          },
          "type": "select",
          "required": true,
          "options": {
            "audience": {
              "ar": "استماع للجمهور",
              "en": "Audience listening"
            },
            "competitor": {
              "ar": "تحليل منافس",
              "en": "Competitor analysis"
            }
          }
        },
        {
          "key": "source",
          "label": {
            "ar": "المصدر",
            "en": "Source"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "observed_at",
          "label": {
            "ar": "التاريخ",
            "en": "Observed date"
          },
          "type": "date",
          "required": true
        },
        {
          "key": "finding",
          "label": {
            "ar": "الاحتياج أو الملاحظة",
            "en": "Need or finding"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "action",
          "label": {
            "ar": "فكرة قابلة للتنفيذ",
            "en": "Actionable idea"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "evidence",
          "label": {
            "ar": "دليل / رابط التحقق",
            "en": "Evidence / verification reference"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "draft",
        "active",
        "completed",
        "archived"
      ]
    },
    "content_library": {
      "label": {
        "ar": "مكتبة المحتوى والتعلم",
        "en": "Content and learning library"
      },
      "group": "social",
      "pages": [
        36,
        38,
        39
      ],
      "fields": [
        {
          "key": "kind",
          "label": {
            "ar": "نوع الأصل",
            "en": "Asset type"
          },
          "type": "select",
          "required": true,
          "options": {
            "case_study": {
              "ar": "دراسة حالة",
              "en": "Case study"
            },
            "expert": {
              "ar": "فكرة خبير",
              "en": "Expert insight"
            },
            "hook": {
              "ar": "افتتاحية",
              "en": "Hook"
            },
            "template": {
              "ar": "قالب",
              "en": "Template"
            },
            "report": {
              "ar": "تقرير أداء",
              "en": "Performance report"
            }
          }
        },
        {
          "key": "content",
          "label": {
            "ar": "المحتوى",
            "en": "Content"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "source",
          "label": {
            "ar": "مصدر الحقائق",
            "en": "Fact source"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "consent",
          "label": {
            "ar": "موافقة العميل وحقوق الاستخدام",
            "en": "Client consent and usage rights"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "decisions",
          "label": {
            "ar": "القرارات التنفيذية",
            "en": "Action decisions"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "draft",
        "active",
        "completed",
        "archived"
      ]
    },
    "creative_assets": {
      "label": {
        "ar": "التصميم والأصول والنسخ",
        "en": "Design assets and versions"
      },
      "group": "creative",
      "pages": [
        38
      ],
      "fields": [
        {
          "key": "brief_id",
          "label": {
            "ar": "طلب الإنتاج",
            "en": "Brief"
          },
          "type": "relation",
          "required": true,
          "entity": "briefs"
        },
        {
          "key": "assignee_id",
          "label": {
            "ar": "المسؤول",
            "en": "Assignee"
          },
          "type": "relation",
          "required": false,
          "entity": "employees"
        },
        {
          "key": "work_type",
          "label": {
            "ar": "نوع التصميم",
            "en": "Design type"
          },
          "type": "select",
          "required": false,
          "options": {
            "revision": {
              "ar": "تعديل",
              "en": "Revision"
            },
            "new": {
              "ar": "تصميم جديد",
              "en": "New design"
            },
            "campaign": {
              "ar": "حملة / مجموعة",
              "en": "Campaign / set"
            },
            "template": {
              "ar": "قالب",
              "en": "Template"
            }
          }
        },
        {
          "key": "version",
          "label": {
            "ar": "النسخة",
            "en": "Version"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "source_url",
          "label": {
            "ar": "الملف الأصلي",
            "en": "Source file"
          },
          "type": "url",
          "required": false
        },
        {
          "key": "final_url",
          "label": {
            "ar": "النسخة النهائية",
            "en": "Final file"
          },
          "type": "url",
          "required": false
        },
        {
          "key": "rights",
          "label": {
            "ar": "الحقوق والترخيص",
            "en": "Rights / license"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "dimensions_checked",
          "label": {
            "ar": "فحص المقاس",
            "en": "Dimensions checked"
          },
          "type": "checkbox",
          "required": false
        },
        {
          "key": "language_checked",
          "label": {
            "ar": "فحص اللغة والتباين والهوية",
            "en": "Language, contrast, brand checked"
          },
          "type": "checkbox",
          "required": false
        },
        {
          "key": "feedback",
          "label": {
            "ar": "المراجعات وسبب إعادة العمل",
            "en": "Reviews and rework reason"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "approval_evidence",
          "label": {
            "ar": "دليل الموافقة",
            "en": "Approval evidence"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "draft",
        "production",
        "review",
        "approved",
        "delivered",
        "archived"
      ],
      "assigned": true,
      "approve_roles": [
        "owner",
        "marketing"
      ]
    },
    "videos": {
      "label": {
        "ar": "إنتاج الفيديو",
        "en": "Video production"
      },
      "group": "creative",
      "pages": [
        39
      ],
      "fields": [
        {
          "key": "brief_id",
          "label": {
            "ar": "طلب الإنتاج",
            "en": "Brief"
          },
          "type": "relation",
          "required": true,
          "entity": "briefs"
        },
        {
          "key": "assignee_id",
          "label": {
            "ar": "المسؤول",
            "en": "Assignee"
          },
          "type": "relation",
          "required": false,
          "entity": "employees"
        },
        {
          "key": "script",
          "label": {
            "ar": "السيناريو والرسالة",
            "en": "Script and message"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "duration",
          "label": {
            "ar": "المدة بالثواني",
            "en": "Duration in seconds"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "dimensions",
          "label": {
            "ar": "المقاس والمنصة",
            "en": "Dimensions / platform"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "raw_material",
          "label": {
            "ar": "المادة الخام والصوت",
            "en": "Raw material / audio"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "edit_scope",
          "label": {
            "ar": "المونتاج والموشن والترجمة",
            "en": "Edit, motion and subtitles scope"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "versions",
          "label": {
            "ar": "عدد النسخ",
            "en": "Version count"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "source_url",
          "label": {
            "ar": "ملف المشروع",
            "en": "Project source"
          },
          "type": "url",
          "required": false
        },
        {
          "key": "final_url",
          "label": {
            "ar": "النسخة النهائية",
            "en": "Final file"
          },
          "type": "url",
          "required": false
        },
        {
          "key": "audio_checked",
          "label": {
            "ar": "فحص الصوت",
            "en": "Audio checked"
          },
          "type": "checkbox",
          "required": false
        },
        {
          "key": "subtitles_checked",
          "label": {
            "ar": "فحص الترجمة",
            "en": "Subtitles checked"
          },
          "type": "checkbox",
          "required": false
        },
        {
          "key": "export_checked",
          "label": {
            "ar": "فحص التصدير والمقاس",
            "en": "Export / size checked"
          },
          "type": "checkbox",
          "required": false
        },
        {
          "key": "rights",
          "label": {
            "ar": "الحقوق",
            "en": "Rights"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "feedback",
          "label": {
            "ar": "المراجعات وإعادة العمل",
            "en": "Reviews / rework"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "approval_evidence",
          "label": {
            "ar": "دليل الموافقة",
            "en": "Approval evidence"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "draft",
        "production",
        "review",
        "approved",
        "delivered",
        "archived"
      ],
      "assigned": true,
      "approve_roles": [
        "owner",
        "marketing"
      ]
    },
    "campaigns": {
      "label": {
        "ar": "الحملات الإعلانية",
        "en": "Advertising campaigns"
      },
      "group": "advertising",
      "pages": [
        40
      ],
      "fields": [
        {
          "key": "platform",
          "label": {
            "ar": "المنصة",
            "en": "Platform"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "objective",
          "label": {
            "ar": "هدف واحد",
            "en": "Single objective"
          },
          "type": "select",
          "required": true,
          "options": {
            "awareness": {
              "ar": "وعي",
              "en": "Awareness"
            },
            "traffic": {
              "ar": "زيارة",
              "en": "Traffic"
            },
            "leads": {
              "ar": "استفسارات",
              "en": "Leads"
            }
          }
        },
        {
          "key": "daily_budget",
          "label": {
            "ar": "ميزانية يومية",
            "en": "Daily budget"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "budget_cap",
          "label": {
            "ar": "سقف الإنفاق",
            "en": "Spend cap"
          },
          "type": "number",
          "required": true
        },
        {
          "key": "spent",
          "label": {
            "ar": "الإنفاق الحالي",
            "en": "Current spend"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "landing_url",
          "label": {
            "ar": "صفحة الوصول",
            "en": "Landing page"
          },
          "type": "url",
          "required": false
        },
        {
          "key": "offer",
          "label": {
            "ar": "العرض والرسالة",
            "en": "Offer and message"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "asset_approval",
          "label": {
            "ar": "الأصول المعتمدة",
            "en": "Approved assets"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "tracking_test",
          "label": {
            "ar": "دليل فحص التتبع وروابط المصدر",
            "en": "Tracking test and source links"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "sales_ready",
          "label": {
            "ar": "تأكيد قدرة المبيعات على المتابعة",
            "en": "Sales follow-up readiness"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "start_date",
          "label": {
            "ar": "البداية",
            "en": "Start date"
          },
          "type": "date",
          "required": false
        },
        {
          "key": "end_date",
          "label": {
            "ar": "النهاية",
            "en": "End date"
          },
          "type": "date",
          "required": false
        },
        {
          "key": "change_log",
          "label": {
            "ar": "تجارب وتغييرات التشغيل",
            "en": "Experiments and operating changes"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "draft",
        "submitted",
        "approved",
        "running",
        "paused",
        "completed",
        "archived"
      ],
      "approve_roles": [
        "owner"
      ]
    },
    "campaign_metrics": {
      "label": {
        "ar": "نتائج الإعلانات",
        "en": "Campaign metrics"
      },
      "group": "advertising",
      "pages": [
        40,
        45
      ],
      "fields": [
        {
          "key": "campaign_id",
          "label": {
            "ar": "الحملة",
            "en": "Campaign"
          },
          "type": "relation",
          "required": true,
          "entity": "campaigns"
        },
        {
          "key": "metric_date",
          "label": {
            "ar": "التاريخ",
            "en": "Metric date"
          },
          "type": "date",
          "required": true
        },
        {
          "key": "spent",
          "label": {
            "ar": "الإنفاق",
            "en": "Spend"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "impressions",
          "label": {
            "ar": "الظهور",
            "en": "Impressions"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "clicks",
          "label": {
            "ar": "النقرات",
            "en": "Clicks"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "leads",
          "label": {
            "ar": "الاستفسارات",
            "en": "Leads"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "qualified_leads",
          "label": {
            "ar": "الفرص المؤهلة",
            "en": "Qualified leads"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "revenue",
          "label": {
            "ar": "الإيراد المنسوب",
            "en": "Attributed revenue"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "attribution_limits",
          "label": {
            "ar": "حدود الإسناد ومصدر الأرقام",
            "en": "Attribution limits and source"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "evidence",
          "label": {
            "ar": "دليل / رابط التحقق",
            "en": "Evidence / verification reference"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "draft",
        "active",
        "completed",
        "archived"
      ]
    },
    "employees": {
      "label": {
        "ar": "الفريق والهيكل",
        "en": "Team and organization"
      },
      "group": "people",
      "pages": [
        5,
        6,
        8,
        29,
        48
      ],
      "fields": [
        {
          "key": "job_title",
          "label": {
            "ar": "المسمى",
            "en": "Job title"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "department",
          "label": {
            "ar": "القسم",
            "en": "Department"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "manager_id",
          "label": {
            "ar": "المدير الإداري الوحيد",
            "en": "Direct manager"
          },
          "type": "relation",
          "required": false,
          "entity": "employees"
        },
        {
          "key": "jira_account_id",
          "label": {
            "ar": "معرف حساب Jira",
            "en": "Jira account ID"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "user_id",
          "label": {
            "ar": "معرف حساب المستخدم المرتبط",
            "en": "Linked user account ID"
          },
          "type": "user",
          "required": false
        },
        {
          "key": "contract_type",
          "label": {
            "ar": "نوع التعاقد",
            "en": "Contract type"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "work_window",
          "label": {
            "ar": "نافذة العمل",
            "en": "Work window"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "weekly_hours",
          "label": {
            "ar": "ساعات التعاقد الأسبوعية",
            "en": "Weekly contracted hours"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "shift_start",
          "label": {
            "ar": "بداية الوردية",
            "en": "Shift start"
          },
          "type": "time",
          "required": false
        },
        {
          "key": "shift_end",
          "label": {
            "ar": "نهاية الوردية",
            "en": "Shift end"
          },
          "type": "time",
          "required": false
        },
        {
          "key": "conflict_disclosure",
          "label": {
            "ar": "إفصاح التعارض",
            "en": "Conflict disclosure"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "effective_date",
          "label": {
            "ar": "تاريخ السريان",
            "en": "Effective date"
          },
          "type": "date",
          "required": false
        }
      ],
      "statuses": [
        "proposed",
        "active",
        "inactive",
        "archived"
      ]
    },
    "coverage": {
      "label": {
        "ar": "تغطية الوظائف والبدلاء",
        "en": "Responsibility coverage"
      },
      "group": "people",
      "pages": [
        9,
        34,
        59
      ],
      "fields": [
        {
          "key": "function",
          "label": {
            "ar": "الوظيفة المطلوب تغطيتها",
            "en": "Function"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "primary_id",
          "label": {
            "ar": "المسؤول الأساسي",
            "en": "Primary"
          },
          "type": "relation",
          "required": false,
          "entity": "employees"
        },
        {
          "key": "backup_id",
          "label": {
            "ar": "البديل",
            "en": "Backup"
          },
          "type": "relation",
          "required": false,
          "entity": "employees"
        },
        {
          "key": "authority",
          "label": {
            "ar": "الصلاحية والتخصص",
            "en": "Authority and skill"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "available_hours",
          "label": {
            "ar": "الوقت المتاح",
            "en": "Available hours"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "restore_evidence",
          "label": {
            "ar": "معرفة / اختبار الاستعادة",
            "en": "Restore knowledge / test"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "draft",
        "active",
        "completed",
        "archived"
      ]
    },
    "capacity": {
      "label": {
        "ar": "السعة وتقويم العمل",
        "en": "Capacity and work calendar"
      },
      "group": "people",
      "pages": [
        16,
        29,
        41
      ],
      "fields": [
        {
          "key": "assignee_id",
          "label": {
            "ar": "المسؤول",
            "en": "Assignee"
          },
          "type": "relation",
          "required": true,
          "entity": "employees"
        },
        {
          "key": "week_start",
          "label": {
            "ar": "بداية الأسبوع",
            "en": "Week start"
          },
          "type": "date",
          "required": true
        },
        {
          "key": "contract_hours",
          "label": {
            "ar": "ساعات التعاقد",
            "en": "Contract hours"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "meetings_hours",
          "label": {
            "ar": "اجتماعات ومراجعات",
            "en": "Meeting / review hours"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "support_reserve",
          "label": {
            "ar": "احتياطي الدعم والعوائق",
            "en": "Support / blocker reserve"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "planned_hours",
          "label": {
            "ar": "الساعات المخططة",
            "en": "Planned hours"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "sales_hours",
          "label": {
            "ar": "وقت المبيعات",
            "en": "Sales allocation"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "data_hours",
          "label": {
            "ar": "وقت البيانات",
            "en": "Data entry allocation"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "availability",
          "label": {
            "ar": "الإجازات والتوافر",
            "en": "Leave / availability"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "draft",
        "active",
        "completed",
        "archived"
      ]
    },
    "goals": {
      "label": {
        "ar": "الأهداف ومؤشرات الأداء",
        "en": "Goals and KPIs"
      },
      "group": "people",
      "pages": [
        43,
        44,
        45,
        55
      ],
      "fields": [
        {
          "key": "assignee_id",
          "label": {
            "ar": "المسؤول",
            "en": "Assignee"
          },
          "type": "relation",
          "required": true,
          "entity": "employees"
        },
        {
          "key": "period",
          "label": {
            "ar": "الفترة",
            "en": "Period"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "announced_at",
          "label": {
            "ar": "تاريخ إعلان المعيار",
            "en": "Announced date"
          },
          "type": "date",
          "required": true
        },
        {
          "key": "objective",
          "label": {
            "ar": "الهدف المتفق عليه",
            "en": "Agreed goal"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "metric",
          "label": {
            "ar": "المقياس",
            "en": "Metric"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "baseline",
          "label": {
            "ar": "خط الأساس / غير معلوم",
            "en": "Baseline / unknown"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "target",
          "label": {
            "ar": "المستهدف المقترح",
            "en": "Proposed target"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "weight",
          "label": {
            "ar": "الوزن",
            "en": "Weight"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "source",
          "label": {
            "ar": "مصدر الإثبات",
            "en": "Evidence source"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "evidence",
          "label": {
            "ar": "دليل / رابط التحقق",
            "en": "Evidence / verification reference"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "draft",
        "active",
        "completed",
        "archived"
      ],
      "private": true,
      "approve_roles": [
        "owner",
        "hr",
        "delivery"
      ]
    },
    "evaluations": {
      "label": {
        "ar": "التقييمات والاعتراضات",
        "en": "Evaluations and appeals"
      },
      "group": "people",
      "pages": [
        43,
        44,
        45,
        46,
        57
      ],
      "fields": [
        {
          "key": "assignee_id",
          "label": {
            "ar": "المسؤول",
            "en": "Assignee"
          },
          "type": "relation",
          "required": true,
          "entity": "employees"
        },
        {
          "key": "period",
          "label": {
            "ar": "الفترة",
            "en": "Period"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "cycle",
          "label": {
            "ar": "نوع المراجعة",
            "en": "Review cycle"
          },
          "type": "select",
          "required": true,
          "options": {
            "monthly": {
              "ar": "شهرية",
              "en": "Monthly"
            },
            "quarterly": {
              "ar": "ربع سنوية",
              "en": "Quarterly"
            }
          }
        },
        {
          "key": "scorecard_role",
          "label": {
            "ar": "بطاقة الدور",
            "en": "Scorecard role"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "ratings",
          "label": {
            "ar": "الدرجات والأوزان والأدلة (JSON)",
            "en": "Ratings, weights and evidence (JSON)"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "self_review",
          "label": {
            "ar": "التقييم الذاتي",
            "en": "Self review"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "manager_review",
          "label": {
            "ar": "مراجعة المدير ونقاط القوة والتحسين",
            "en": "Manager review, strengths and improvements"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "employee_comment",
          "label": {
            "ar": "تعليق الموظف / اعتراض",
            "en": "Employee comment / appeal"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "next_steps",
          "label": {
            "ar": "خطة الفترة القادمة والدعم",
            "en": "Next-period plan and support"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "review_due",
          "label": {
            "ar": "موعد المراجعة",
            "en": "Review date"
          },
          "type": "date",
          "required": false
        },
        {
          "key": "decision_evidence",
          "label": {
            "ar": "قرار الإدارة المفسر",
            "en": "Explained management decision"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "draft",
        "self_review",
        "manager_review",
        "calibration",
        "finalized",
        "appealed",
        "closed",
        "archived"
      ],
      "private": true,
      "approve_roles": [
        "owner"
      ]
    },
    "improvement_plans": {
      "label": {
        "ar": "خطط تحسين الأداء",
        "en": "Performance improvement plans"
      },
      "group": "people",
      "pages": [
        30,
        57
      ],
      "fields": [
        {
          "key": "assignee_id",
          "label": {
            "ar": "المسؤول",
            "en": "Assignee"
          },
          "type": "relation",
          "required": true,
          "entity": "employees"
        },
        {
          "key": "gap",
          "label": {
            "ar": "الفجوة والأدلة",
            "en": "Gap and evidence"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "behavior_goal",
          "label": {
            "ar": "هدف السلوك",
            "en": "Behavior goal"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "delivery_goal",
          "label": {
            "ar": "هدف التسليم",
            "en": "Delivery goal"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "support",
          "label": {
            "ar": "الدعم المقدم",
            "en": "Support"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "start_date",
          "label": {
            "ar": "البداية",
            "en": "Start date"
          },
          "type": "date",
          "required": true
        },
        {
          "key": "checkpoint_date",
          "label": {
            "ar": "مراجعة وسطية",
            "en": "Checkpoint"
          },
          "type": "date",
          "required": true
        },
        {
          "key": "end_date",
          "label": {
            "ar": "التقييم النهائي",
            "en": "Final review"
          },
          "type": "date",
          "required": true
        },
        {
          "key": "employee_comment",
          "label": {
            "ar": "رأي الموظف",
            "en": "Employee response"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "outcome",
          "label": {
            "ar": "النتيجة والإجراء المفسر",
            "en": "Outcome and explained action"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "draft",
        "active",
        "completed",
        "archived"
      ],
      "private": true
    },
    "leave": {
      "label": {
        "ar": "الإجازات والتوافر",
        "en": "Leave and availability"
      },
      "group": "people",
      "pages": [
        9,
        29
      ],
      "fields": [
        {
          "key": "assignee_id",
          "label": {
            "ar": "المسؤول",
            "en": "Assignee"
          },
          "type": "relation",
          "required": true,
          "entity": "employees"
        },
        {
          "key": "start_date",
          "label": {
            "ar": "البداية",
            "en": "Start date"
          },
          "type": "date",
          "required": true
        },
        {
          "key": "end_date",
          "label": {
            "ar": "النهاية",
            "en": "End date"
          },
          "type": "date",
          "required": true
        },
        {
          "key": "reason",
          "label": {
            "ar": "السبب",
            "en": "Reason"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "handover",
          "label": {
            "ar": "تسليم الأعمال",
            "en": "Work handover"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "requested",
        "approved",
        "rejected",
        "completed",
        "archived"
      ],
      "private": true,
      "approve_roles": [
        "owner",
        "delivery",
        "hr"
      ]
    },
    "onboarding": {
      "label": {
        "ar": "الدخول والمغادرة",
        "en": "Onboarding and offboarding"
      },
      "group": "people",
      "pages": [
        34
      ],
      "fields": [
        {
          "key": "assignee_id",
          "label": {
            "ar": "المسؤول",
            "en": "Assignee"
          },
          "type": "relation",
          "required": true,
          "entity": "employees"
        },
        {
          "key": "kind",
          "label": {
            "ar": "العملية",
            "en": "Process"
          },
          "type": "select",
          "required": true,
          "options": {
            "onboarding": {
              "ar": "انضمام",
              "en": "Onboarding"
            },
            "offboarding": {
              "ar": "مغادرة",
              "en": "Offboarding"
            }
          }
        },
        {
          "key": "effective_date",
          "label": {
            "ar": "التوقيت المعتمد",
            "en": "Effective date"
          },
          "type": "date",
          "required": true
        },
        {
          "key": "accounts",
          "label": {
            "ar": "حسابات وأدوار وإلغاء وصول",
            "en": "Accounts, roles and revocation"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "assets",
          "label": {
            "ar": "تسليم الأصول ونقل الملكية",
            "en": "Assets and ownership transfer"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "training",
          "label": {
            "ar": "السياسات والتدريب والمشروع الأول",
            "en": "Policies, training, first project"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "completion_evidence",
          "label": {
            "ar": "دليل الإكمال",
            "en": "Completion evidence"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "draft",
        "active",
        "completed",
        "archived"
      ],
      "private": true
    },
    "attendance": {
      "label": {
        "ar": "الحضور والانصراف",
        "en": "Attendance"
      },
      "group": "people",
      "pages": [],
      "fields": [
        {
          "key": "assignee_id",
          "label": {
            "ar": "الموظف",
            "en": "Employee"
          },
          "type": "relation",
          "required": true,
          "entity": "employees"
        },
        {
          "key": "work_date",
          "label": {
            "ar": "يوم العمل",
            "en": "Work date"
          },
          "type": "date",
          "required": true
        },
        {
          "key": "check_in",
          "label": {
            "ar": "وقت الحضور",
            "en": "Check-in"
          },
          "type": "datetime-local",
          "required": false
        },
        {
          "key": "check_out",
          "label": {
            "ar": "وقت الانصراف",
            "en": "Check-out"
          },
          "type": "datetime-local",
          "required": false
        },
        {
          "key": "mode",
          "label": {
            "ar": "مكان العمل",
            "en": "Work mode"
          },
          "type": "select",
          "required": true,
          "options": {
            "office": {
              "ar": "من المقر",
              "en": "Office"
            },
            "remote": {
              "ar": "من المنزل",
              "en": "Work from home"
            }
          }
        },
        {
          "key": "source",
          "label": {
            "ar": "مصدر التسجيل",
            "en": "Source"
          },
          "type": "select",
          "required": true,
          "options": {
            "web": {
              "ar": "النظام",
              "en": "Web"
            },
            "device": {
              "ar": "جهاز البصمة",
              "en": "Device"
            },
            "manual": {
              "ar": "تصحيح يدوي",
              "en": "Manual correction"
            },
            "system": {
              "ar": "تلقائي",
              "en": "System"
            }
          }
        },
        {
          "key": "note",
          "label": {
            "ar": "ملاحظة / سبب التصحيح",
            "en": "Note / correction reason"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "open",
        "closed",
        "absent",
        "excused",
        "archived"
      ],
      "private": true
    },
    "holidays": {
      "label": {
        "ar": "العطلات الرسمية",
        "en": "Public holidays"
      },
      "group": "people",
      "pages": [],
      "fields": [
        {
          "key": "holiday_date",
          "label": {
            "ar": "التاريخ",
            "en": "Date"
          },
          "type": "date",
          "required": true
        }
      ],
      "statuses": [
        "active",
        "archived"
      ]
    },
    "delegations": {
      "label": {
        "ar": "التفويضات المكتوبة",
        "en": "Written delegations"
      },
      "group": "governance",
      "pages": [
        7,
        8,
        10,
        11,
        59
      ],
      "fields": [
        {
          "key": "user_id",
          "label": {
            "ar": "المستخدم المفوض",
            "en": "Delegated user"
          },
          "type": "user",
          "required": true
        },
        {
          "key": "entity_type",
          "label": {
            "ar": "نوع السجل المسموح",
            "en": "Permitted record type"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "action",
          "label": {
            "ar": "الإجراء (approve)",
            "en": "Action (approve)"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "budget_limit",
          "label": {
            "ar": "حد مالي إن وجد",
            "en": "Budget limit"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "starts_at",
          "label": {
            "ar": "البداية",
            "en": "Starts at"
          },
          "type": "date",
          "required": true
        },
        {
          "key": "expires_at",
          "label": {
            "ar": "النهاية",
            "en": "Expires at"
          },
          "type": "date",
          "required": true
        },
        {
          "key": "reason",
          "label": {
            "ar": "سبب وحدود التفويض",
            "en": "Delegation reason and limits"
          },
          "type": "textarea",
          "required": true
        }
      ],
      "statuses": [
        "draft",
        "active",
        "revoked",
        "archived"
      ],
      "approve_roles": [
        "owner"
      ]
    },
    "budgets": {
      "label": {
        "ar": "الميزانيات",
        "en": "Budgets"
      },
      "group": "finance",
      "pages": [
        7,
        32
      ],
      "fields": [
        {
          "key": "project_id",
          "label": {
            "ar": "المشروع",
            "en": "Project"
          },
          "type": "relation",
          "required": false,
          "entity": "projects"
        },
        {
          "key": "amount",
          "label": {
            "ar": "الميزانية",
            "en": "Budget"
          },
          "type": "number",
          "required": true
        },
        {
          "key": "currency",
          "label": {
            "ar": "العملة",
            "en": "Currency"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "effort_hours",
          "label": {
            "ar": "ميزانية الجهد",
            "en": "Effort budget"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "hourly_cost",
          "label": {
            "ar": "تكلفة داخلية للساعة",
            "en": "Internal hourly cost"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "contract_value",
          "label": {
            "ar": "قيمة العقد",
            "en": "Contract value"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "target_margin",
          "label": {
            "ar": "الهامش المستهدف %",
            "en": "Target margin %"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "basis",
          "label": {
            "ar": "أساس توزيع التكلفة",
            "en": "Cost allocation basis"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "draft",
        "submitted",
        "approved",
        "closed",
        "archived"
      ],
      "approve_roles": [
        "owner"
      ],
      "immutable": [
        "approved",
        "closed",
        "archived"
      ]
    },
    "costs": {
      "label": {
        "ar": "التكاليف وتوقع الإكمال",
        "en": "Costs and completion forecast"
      },
      "group": "finance",
      "pages": [
        26,
        32
      ],
      "fields": [
        {
          "key": "project_id",
          "label": {
            "ar": "المشروع",
            "en": "Project"
          },
          "type": "relation",
          "required": true,
          "entity": "projects"
        },
        {
          "key": "category",
          "label": {
            "ar": "نوع التكلفة",
            "en": "Cost type"
          },
          "type": "select",
          "required": true,
          "options": {
            "execution": {
              "ar": "تنفيذ",
              "en": "Execution"
            },
            "tools": {
              "ar": "أدوات",
              "en": "Tools"
            },
            "contractor": {
              "ar": "متعاقد",
              "en": "Contractor"
            },
            "rework": {
              "ar": "إعادة عمل",
              "en": "Rework"
            },
            "change": {
              "ar": "تغيير معتمد",
              "en": "Approved change"
            },
            "courtesy": {
              "ar": "مجاملة",
              "en": "Courtesy"
            },
            "support": {
              "ar": "دعم",
              "en": "Support"
            }
          }
        },
        {
          "key": "actual_cost",
          "label": {
            "ar": "تكلفة فعلية",
            "en": "Actual cost"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "remaining_cost",
          "label": {
            "ar": "تكلفة متوقعة متبقية",
            "en": "Expected remaining cost"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "cost_date",
          "label": {
            "ar": "التاريخ",
            "en": "Cost date"
          },
          "type": "date",
          "required": true
        },
        {
          "key": "evidence",
          "label": {
            "ar": "دليل / رابط التحقق",
            "en": "Evidence / verification reference"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "draft",
        "active",
        "completed",
        "archived"
      ]
    },
    "invoices": {
      "label": {
        "ar": "الفواتير",
        "en": "Invoices"
      },
      "group": "finance",
      "pages": [
        17,
        32
      ],
      "fields": [
        {
          "key": "client_id",
          "label": {
            "ar": "العميل",
            "en": "Client"
          },
          "type": "relation",
          "required": true,
          "entity": "clients"
        },
        {
          "key": "project_id",
          "label": {
            "ar": "المشروع",
            "en": "Project"
          },
          "type": "relation",
          "required": false,
          "entity": "projects"
        },
        {
          "key": "invoice_number",
          "label": {
            "ar": "رقم الفاتورة",
            "en": "Invoice number"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "amount",
          "label": {
            "ar": "المبلغ",
            "en": "Amount"
          },
          "type": "number",
          "required": true
        },
        {
          "key": "currency",
          "label": {
            "ar": "العملة",
            "en": "Currency"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "due_date",
          "label": {
            "ar": "الاستحقاق",
            "en": "Due date"
          },
          "type": "date",
          "required": true
        },
        {
          "key": "evidence",
          "label": {
            "ar": "دليل / رابط التحقق",
            "en": "Evidence / verification reference"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "draft",
        "issued",
        "part_paid",
        "paid",
        "overdue",
        "void",
        "archived"
      ],
      "immutable": [
        "issued",
        "part_paid",
        "paid",
        "overdue",
        "void",
        "archived"
      ]
    },
    "receipts": {
      "label": {
        "ar": "التحصيل",
        "en": "Receipts"
      },
      "group": "finance",
      "pages": [
        32
      ],
      "fields": [
        {
          "key": "invoice_id",
          "label": {
            "ar": "الفاتورة",
            "en": "Invoice"
          },
          "type": "relation",
          "required": true,
          "entity": "invoices"
        },
        {
          "key": "amount",
          "label": {
            "ar": "المبلغ المحصل",
            "en": "Collected amount"
          },
          "type": "number",
          "required": true
        },
        {
          "key": "received_at",
          "label": {
            "ar": "التاريخ",
            "en": "Received date"
          },
          "type": "date",
          "required": true
        },
        {
          "key": "reference",
          "label": {
            "ar": "مرجع التحصيل",
            "en": "Payment reference"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "evidence",
          "label": {
            "ar": "دليل / رابط التحقق",
            "en": "Evidence / verification reference"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "draft",
        "active",
        "completed",
        "archived"
      ],
      "immutable": [
        "active",
        "completed",
        "archived"
      ]
    },
    "support": {
      "label": {
        "ar": "الدعم والضمان",
        "en": "Support and warranty"
      },
      "group": "support",
      "pages": [
        33
      ],
      "fields": [
        {
          "key": "project_id",
          "label": {
            "ar": "المشروع",
            "en": "Project"
          },
          "type": "relation",
          "required": true,
          "entity": "projects"
        },
        {
          "key": "kind",
          "label": {
            "ar": "نوع الطلب",
            "en": "Request type"
          },
          "type": "select",
          "required": true,
          "options": {
            "warranty": {
              "ar": "عيب داخل الضمان",
              "en": "Warranty defect"
            },
            "enhancement": {
              "ar": "تحسين جديد",
              "en": "Enhancement"
            },
            "noncritical": {
              "ar": "غير حرج",
              "en": "Noncritical"
            }
          }
        },
        {
          "key": "request",
          "label": {
            "ar": "الطلب",
            "en": "Request"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "support_window",
          "label": {
            "ar": "نافذة الدعم المتفق عليها",
            "en": "Agreed support window"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "response_due",
          "label": {
            "ar": "موعد الاستجابة",
            "en": "Response due"
          },
          "type": "date",
          "required": false
        },
        {
          "key": "change_id",
          "label": {
            "ar": "طلب تغيير للتحسين",
            "en": "Change for enhancement"
          },
          "type": "relation",
          "required": false,
          "entity": "changes"
        },
        {
          "key": "resolution",
          "label": {
            "ar": "دليل الحل والاختبار",
            "en": "Resolution and test"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "new",
        "acknowledged",
        "in_progress",
        "resolved",
        "closed",
        "archived"
      ]
    },
    "incidents": {
      "label": {
        "ar": "الحوادث والاستعادة",
        "en": "Incidents and recovery"
      },
      "group": "support",
      "pages": [
        12,
        33
      ],
      "fields": [
        {
          "key": "project_id",
          "label": {
            "ar": "المشروع",
            "en": "Project"
          },
          "type": "relation",
          "required": true,
          "entity": "projects"
        },
        {
          "key": "impact",
          "label": {
            "ar": "الأثر",
            "en": "Impact"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "severity",
          "label": {
            "ar": "الشدة",
            "en": "Severity"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "detected_at",
          "label": {
            "ar": "وقت الاكتشاف",
            "en": "Detected at"
          },
          "type": "datetime-local",
          "required": true
        },
        {
          "key": "acknowledged_at",
          "label": {
            "ar": "وقت التأكيد",
            "en": "Acknowledged at"
          },
          "type": "datetime-local",
          "required": false
        },
        {
          "key": "containment",
          "label": {
            "ar": "الاحتواء",
            "en": "Containment"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "recovery",
          "label": {
            "ar": "الاستعادة",
            "en": "Recovery"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "analysis",
          "label": {
            "ar": "السبب والدروس",
            "en": "Root cause and lessons"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "evidence",
          "label": {
            "ar": "دليل / رابط التحقق",
            "en": "Evidence / verification reference"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "detected",
        "acknowledged",
        "contained",
        "recovered",
        "closed",
        "archived"
      ]
    },
    "assets": {
      "label": {
        "ar": "أصول الشركة والاشتراكات",
        "en": "Company assets and subscriptions"
      },
      "group": "support",
      "pages": [
        9,
        34
      ],
      "fields": [
        {
          "key": "kind",
          "label": {
            "ar": "النوع",
            "en": "Type"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "primary_id",
          "label": {
            "ar": "المالك",
            "en": "Owner"
          },
          "type": "relation",
          "required": false,
          "entity": "employees"
        },
        {
          "key": "backup_id",
          "label": {
            "ar": "البديل",
            "en": "Backup"
          },
          "type": "relation",
          "required": false,
          "entity": "employees"
        },
        {
          "key": "cost",
          "label": {
            "ar": "التكلفة",
            "en": "Cost"
          },
          "type": "number",
          "required": false
        },
        {
          "key": "renewal_date",
          "label": {
            "ar": "التجديد",
            "en": "Renewal"
          },
          "type": "date",
          "required": false
        },
        {
          "key": "usage",
          "label": {
            "ar": "الاستخدام",
            "en": "Usage"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "reference_url",
          "label": {
            "ar": "مرجع الحساب / الأصل",
            "en": "Account / asset reference"
          },
          "type": "url",
          "required": false
        },
        {
          "key": "vault_reference",
          "label": {
            "ar": "مرجع مخزن الأسرار (دون كلمات مرور)",
            "en": "Secret vault reference (no passwords)"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "license",
          "label": {
            "ar": "دليل الترخيص",
            "en": "License evidence"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "backup_evidence",
          "label": {
            "ar": "النسخ واختبار الاستعادة",
            "en": "Backup and restore evidence"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "draft",
        "active",
        "completed",
        "archived"
      ]
    },
    "meetings": {
      "label": {
        "ar": "الاجتماعات والتقارير",
        "en": "Meetings and reports"
      },
      "group": "governance",
      "pages": [
        31,
        57
      ],
      "fields": [
        {
          "key": "cadence",
          "label": {
            "ar": "الإيقاع",
            "en": "Cadence"
          },
          "type": "select",
          "required": true,
          "options": {
            "daily": {
              "ar": "يومي",
              "en": "Daily"
            },
            "blockers": {
              "ar": "عوائق",
              "en": "Blockers"
            },
            "weekly": {
              "ar": "أسبوعي",
              "en": "Weekly"
            },
            "growth": {
              "ar": "نمو وإنتاج",
              "en": "Growth"
            },
            "monthly": {
              "ar": "شهري",
              "en": "Monthly"
            },
            "quarterly": {
              "ar": "ربع سنوي",
              "en": "Quarterly"
            }
          }
        },
        {
          "key": "participants",
          "label": {
            "ar": "المشاركون",
            "en": "Participants"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "meeting_date",
          "label": {
            "ar": "التاريخ",
            "en": "Meeting date"
          },
          "type": "date",
          "required": true
        },
        {
          "key": "accepted_work",
          "label": {
            "ar": "ما تم قبوله وروابطه",
            "en": "Accepted work and references"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "forecast",
          "label": {
            "ar": "الفعلية والمتبقي والتوقع",
            "en": "Actual, remaining and forecast"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "risks",
          "label": {
            "ar": "أهم المخاطر",
            "en": "Top risks"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "decisions",
          "label": {
            "ar": "قرارات ومسؤول وموعد وروابط",
            "en": "Decisions, owner, due date, links"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "next_steps",
          "label": {
            "ar": "المتابعة",
            "en": "Follow-up"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "draft",
        "active",
        "completed",
        "archived"
      ]
    },
    "decisions": {
      "label": {
        "ar": "القرارات والاعتمادات",
        "en": "Decisions and approvals"
      },
      "group": "governance",
      "pages": [
        7,
        10,
        11,
        47,
        57
      ],
      "fields": [
        {
          "key": "project_id",
          "label": {
            "ar": "المشروع",
            "en": "Project"
          },
          "type": "relation",
          "required": false,
          "entity": "projects"
        },
        {
          "key": "problem",
          "label": {
            "ar": "الموضوع والقرار المطلوب",
            "en": "Issue and requested decision"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "alternatives",
          "label": {
            "ar": "البدائل وأثرها",
            "en": "Alternatives and impact"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "due_date",
          "label": {
            "ar": "موعد القرار",
            "en": "Decision due"
          },
          "type": "date",
          "required": true
        },
        {
          "key": "decision",
          "label": {
            "ar": "القرار المفسر",
            "en": "Explained decision"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "effective_date",
          "label": {
            "ar": "تاريخ السريان",
            "en": "Effective date"
          },
          "type": "date",
          "required": false
        },
        {
          "key": "evidence",
          "label": {
            "ar": "دليل / رابط التحقق",
            "en": "Evidence / verification reference"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "requested",
        "approved",
        "rejected",
        "returned",
        "closed",
        "archived"
      ],
      "approve_roles": [
        "owner"
      ]
    },
    "exceptions": {
      "label": {
        "ar": "الاستثناءات الموثقة",
        "en": "Documented exceptions"
      },
      "group": "governance",
      "pages": [
        21,
        25,
        49
      ],
      "fields": [
        {
          "key": "project_id",
          "label": {
            "ar": "المشروع",
            "en": "Project"
          },
          "type": "relation",
          "required": false,
          "entity": "projects"
        },
        {
          "key": "gate",
          "label": {
            "ar": "البوابة / السياسة",
            "en": "Gate / policy"
          },
          "type": "text",
          "required": true
        },
        {
          "key": "reason",
          "label": {
            "ar": "السبب والدليل",
            "en": "Reason and evidence"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "risk_owner",
          "label": {
            "ar": "صاحب المخاطرة",
            "en": "Risk owner"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "mitigation",
          "label": {
            "ar": "المعالجة وشروط الرجوع",
            "en": "Mitigation and rollback"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "expires_at",
          "label": {
            "ar": "انتهاء الاستثناء",
            "en": "Expiry"
          },
          "type": "date",
          "required": true
        }
      ],
      "statuses": [
        "requested",
        "approved",
        "rejected",
        "expired",
        "archived"
      ],
      "approve_roles": [
        "owner"
      ]
    },
    "policies": {
      "label": {
        "ar": "السياسات وخطة التطبيق",
        "en": "Policies and rollout"
      },
      "group": "governance",
      "pages": [
        2,
        28,
        53,
        54,
        55,
        59
      ],
      "fields": [
        {
          "key": "source_pages",
          "label": {
            "ar": "صفحات المرجع",
            "en": "Reference pages"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "rule",
          "label": {
            "ar": "القاعدة / الخطة",
            "en": "Rule / plan"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "parameters",
          "label": {
            "ar": "الإعدادات المقترحة",
            "en": "Proposed parameters"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "effective_date",
          "label": {
            "ar": "تاريخ السريان المعتمد",
            "en": "Approved effective date"
          },
          "type": "date",
          "required": false
        },
        {
          "key": "review_date",
          "label": {
            "ar": "موعد المعايرة",
            "en": "Calibration date"
          },
          "type": "date",
          "required": false
        },
        {
          "key": "approval_evidence",
          "label": {
            "ar": "دليل الاعتماد",
            "en": "Approval evidence"
          },
          "type": "textarea",
          "required": false
        }
      ],
      "statuses": [
        "proposed",
        "approved",
        "active",
        "retired",
        "archived"
      ],
      "approve_roles": [
        "owner"
      ]
    },
    "seo_issues": {
      "label": {
        "ar": "معالجة أخطاء SEO",
        "en": "SEO remediation"
      },
      "group": "seo",
      "pages": [],
      "fields": [
        {
          "key": "url",
          "label": {
            "ar": "الرابط",
            "en": "URL"
          },
          "type": "url",
          "required": true
        },
        {
          "key": "language",
          "label": {
            "ar": "لغة الصفحة",
            "en": "Page language"
          },
          "type": "select",
          "required": true,
          "options": {
            "ar": {
              "ar": "عربي",
              "en": "Arabic"
            },
            "en": {
              "ar": "إنجليزي",
              "en": "English"
            }
          }
        },
        {
          "key": "source",
          "label": {
            "ar": "مصدر الفحص",
            "en": "Check source"
          },
          "type": "select",
          "required": true,
          "options": {
            "local": {
              "ar": "فحص محلي",
              "en": "Local audit"
            },
            "gsc": {
              "ar": "Search Console",
              "en": "Search Console"
            },
            "manual": {
              "ar": "فحص يدوي",
              "en": "Manual check"
            }
          }
        },
        {
          "key": "severity",
          "label": {
            "ar": "التصنيف",
            "en": "Severity"
          },
          "type": "select",
          "required": true,
          "options": {
            "error": {
              "ar": "خطأ تقني",
              "en": "Technical error"
            },
            "warning": {
              "ar": "تحسين تحريري",
              "en": "Editorial warning"
            }
          }
        },
        {
          "key": "issue",
          "label": {
            "ar": "المشكلة",
            "en": "Issue"
          },
          "type": "textarea",
          "required": true
        },
        {
          "key": "fix",
          "label": {
            "ar": "الإصلاح",
            "en": "Fix"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "recheck_evidence",
          "label": {
            "ar": "دليل إعادة الفحص",
            "en": "Recheck evidence"
          },
          "type": "textarea",
          "required": false
        },
        {
          "key": "gsc_state",
          "label": {
            "ar": "حالة تحقق Google (ليست الفهرسة)",
            "en": "Google validation state (not indexing)"
          },
          "type": "text",
          "required": false
        },
        {
          "key": "checked_at",
          "label": {
            "ar": "آخر تحقق",
            "en": "Last checked"
          },
          "type": "date",
          "required": false
        }
      ],
      "statuses": [
        "open",
        "in_progress",
        "fixed",
        "verified",
        "monitoring",
        "archived"
      ]
    }
  },
  "statuses": {
    "absent": {
      "ar": "غياب / عدم تسجيل",
      "en": "Absent / no check-in"
    },
    "excused": {
      "ar": "غياب بعذر",
      "en": "Excused"
    },
    "draft": {
      "ar": "مسودة",
      "en": "Draft"
    },
    "active": {
      "ar": "نشط",
      "en": "Active"
    },
    "completed": {
      "ar": "مكتمل",
      "en": "Completed"
    },
    "archived": {
      "ar": "مؤرشف",
      "en": "Archived"
    },
    "submitted": {
      "ar": "بانتظار الاعتماد",
      "en": "Submitted"
    },
    "approved": {
      "ar": "معتمد",
      "en": "Approved"
    },
    "rejected": {
      "ar": "مرفوض",
      "en": "Rejected"
    },
    "new": {
      "ar": "جديد",
      "en": "New"
    },
    "contacted": {
      "ar": "تم التواصل",
      "en": "Contacted"
    },
    "qualified": {
      "ar": "مؤهل",
      "en": "Qualified"
    },
    "discovery": {
      "ar": "اكتشاف",
      "en": "Discovery"
    },
    "proposal": {
      "ar": "عرض",
      "en": "Proposal"
    },
    "negotiation": {
      "ar": "تفاوض",
      "en": "Negotiation"
    },
    "won": {
      "ar": "كسب",
      "en": "Won"
    },
    "lost": {
      "ar": "خسارة",
      "en": "Lost"
    },
    "deferred": {
      "ar": "مؤجل",
      "en": "Deferred"
    },
    "unsuitable": {
      "ar": "غير مناسب",
      "en": "Unsuitable"
    },
    "closed": {
      "ar": "مغلق",
      "en": "Closed"
    },
    "accepted": {
      "ar": "مقبول",
      "en": "Accepted"
    },
    "returned": {
      "ar": "معاد بنواقص",
      "en": "Returned"
    },
    "agreement": {
      "ar": "اتفاق",
      "en": "Agreement"
    },
    "planning": {
      "ar": "تخطيط",
      "en": "Planning"
    },
    "execution": {
      "ar": "تنفيذ",
      "en": "Execution"
    },
    "acceptance": {
      "ar": "قبول",
      "en": "Acceptance"
    },
    "launch": {
      "ar": "إطلاق",
      "en": "Launch"
    },
    "superseded": {
      "ar": "نسخة سابقة",
      "en": "Superseded"
    },
    "planned": {
      "ar": "مخطط",
      "en": "Planned"
    },
    "in_progress": {
      "ar": "قيد التنفيذ",
      "en": "In progress"
    },
    "backlog": {
      "ar": "قائمة انتظار",
      "en": "Backlog"
    },
    "ready": {
      "ar": "جاهز",
      "en": "Ready"
    },
    "code_review": {
      "ar": "مراجعة كود",
      "en": "Code review"
    },
    "qa": {
      "ar": "اختبار",
      "en": "QA"
    },
    "ready_to_release": {
      "ar": "جاهز للإصدار",
      "en": "Ready to release"
    },
    "done": {
      "ar": "تم الإغلاق",
      "en": "Done"
    },
    "ended": {
      "ar": "انتهت",
      "en": "Ended"
    },
    "cancelled": {
      "ar": "ملغي",
      "en": "Cancelled"
    },
    "open": {
      "ar": "مفتوح",
      "en": "Open"
    },
    "escalated": {
      "ar": "مصعّد",
      "en": "Escalated"
    },
    "resolved": {
      "ar": "تم الحل",
      "en": "Resolved"
    },
    "classified": {
      "ar": "مصنف",
      "en": "Classified"
    },
    "estimated": {
      "ar": "مقدّر",
      "en": "Estimated"
    },
    "implemented": {
      "ar": "تم التنفيذ",
      "en": "Implemented"
    },
    "mitigated": {
      "ar": "تم تخفيفه",
      "en": "Mitigated"
    },
    "pending": {
      "ar": "بانتظار",
      "en": "Pending"
    },
    "passed": {
      "ar": "ناجح",
      "en": "Passed"
    },
    "failed": {
      "ar": "فاشل",
      "en": "Failed"
    },
    "untested": {
      "ar": "غير مختبر",
      "en": "Untested"
    },
    "fixed": {
      "ar": "تم الإصلاح",
      "en": "Fixed"
    },
    "verified": {
      "ar": "تم التحقق",
      "en": "Verified"
    },
    "reopened": {
      "ar": "أعيد فتحه",
      "en": "Reopened"
    },
    "testing": {
      "ar": "تحت الاختبار",
      "en": "Testing"
    },
    "not_ready": {
      "ar": "غير جاهز",
      "en": "Not ready"
    },
    "released": {
      "ar": "صدر",
      "en": "Released"
    },
    "deployed": {
      "ar": "تم النشر",
      "en": "Deployed"
    },
    "rolled_back": {
      "ar": "تم الرجوع",
      "en": "Rolled back"
    },
    "briefed": {
      "ar": "طلب مكتمل",
      "en": "Briefed"
    },
    "production": {
      "ar": "إنتاج",
      "en": "Production"
    },
    "review": {
      "ar": "مراجعة",
      "en": "Review"
    },
    "published": {
      "ar": "منشور",
      "en": "Published"
    },
    "learning": {
      "ar": "تعلم",
      "en": "Learning"
    },
    "scheduled": {
      "ar": "مجدول",
      "en": "Scheduled"
    },
    "running": {
      "ar": "قيد التشغيل",
      "en": "Running"
    },
    "delivered": {
      "ar": "تم التسليم",
      "en": "Delivered"
    },
    "paused": {
      "ar": "موقوف",
      "en": "Paused"
    },
    "proposed": {
      "ar": "مقترح",
      "en": "Proposed"
    },
    "inactive": {
      "ar": "غير نشط",
      "en": "Inactive"
    },
    "self_review": {
      "ar": "تقييم ذاتي",
      "en": "Self review"
    },
    "manager_review": {
      "ar": "مراجعة المدير",
      "en": "Manager review"
    },
    "calibration": {
      "ar": "معايرة الإدارة",
      "en": "Calibration"
    },
    "finalized": {
      "ar": "نهائي",
      "en": "Finalized"
    },
    "appealed": {
      "ar": "اعتراض",
      "en": "Appealed"
    },
    "requested": {
      "ar": "مطلوب",
      "en": "Requested"
    },
    "revoked": {
      "ar": "مسحوب",
      "en": "Revoked"
    },
    "issued": {
      "ar": "صادرة",
      "en": "Issued"
    },
    "part_paid": {
      "ar": "مدفوعة جزئيًا",
      "en": "Part paid"
    },
    "paid": {
      "ar": "مدفوعة",
      "en": "Paid"
    },
    "overdue": {
      "ar": "متأخرة",
      "en": "Overdue"
    },
    "void": {
      "ar": "ملغاة",
      "en": "Void"
    },
    "acknowledged": {
      "ar": "تم التأكيد",
      "en": "Acknowledged"
    },
    "detected": {
      "ar": "مكتشف",
      "en": "Detected"
    },
    "contained": {
      "ar": "تم الاحتواء",
      "en": "Contained"
    },
    "recovered": {
      "ar": "تمت الاستعادة",
      "en": "Recovered"
    },
    "expired": {
      "ar": "منتهي",
      "en": "Expired"
    },
    "retired": {
      "ar": "متقاعد",
      "en": "Retired"
    },
    "monitoring": {
      "ar": "متابعة إعادة الزحف",
      "en": "Recrawl monitoring"
    }
  },
  "scorecards": {
    "delivery": {
      "planning": 30,
      "scope": 25,
      "risk": 25,
      "communication": 20
    },
    "developer": {
      "quality": 40,
      "commitment": 25,
      "review_documentation": 20,
      "improvement": 15
    },
    "qa": {
      "coverage": 40,
      "evidence": 30,
      "flow": 20,
      "improvement": 10
    },
    "marketing": {
      "plan_content": 35,
      "experiments": 25,
      "listening_library": 20,
      "coordination": 20
    },
    "media_buyer": {
      "operations_tracking": 30,
      "budget": 25,
      "experiments": 25,
      "reporting": 20
    },
    "designer": {
      "quality": 40,
      "delivery": 25,
      "files_templates": 20,
      "collaboration": 15
    },
    "video_editor": {
      "quality": 40,
      "delivery": 25,
      "organization": 20,
      "improvement": 15
    },
    "sales": {
      "followup": 35,
      "qualification": 25,
      "data_quality": 25,
      "improvement": 15
    }
  },
  "thresholds": {
    "review_wip": 3,
    "qa_wip": 3,
    "effort_warning_percent": 70,
    "overrun_warning_percent": 20,
    "budget_warning_percent": 15,
    "blocker_hours": 4
  },
  "field_ownership": {
    "jira": [
      "status",
      "assignee",
      "deadline",
      "summary",
      "source_updated_at"
    ],
    "findo": [
      "scope",
      "change_approval",
      "client_acceptance",
      "finance",
      "evaluations",
      "release_decision"
    ]
  },
  "attendance": {
    "work_start": "09:00",
    "work_end": "17:00",
    "work_days": [0, 1, 2, 3, 4],
    "grace_minutes": 0,
    "bonus_min": 25,
    "bonus_max": 40,
    "bonus_eligibility_percent": 80,
    "absence_alert_days": 2,
    "currency": "USD"
  }
}
JSON, true);
