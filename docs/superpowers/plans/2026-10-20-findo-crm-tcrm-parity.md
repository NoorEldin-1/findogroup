# Findo CRM — تطوير نظام الـCRM الحالي إلى مستوى TCRM — خطة التنفيذ

> **For agentic workers:** REQUIRED SUB-SKILL: Use `subagent-driven-development` (recommended) or `executing-plans` to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.
>
> **قبل أي شيء:**
> 1. اقرأ `docs/superpowers/plans/2026-10-06-findo-erp-00-roadmap.md` **القسم 2 كاملًا** (القواعد الثابتة للمحرّك). هذه الخطة تلتزم به حرفيًا.
> 2. اقرأ `CLAUDE.md`. مجلد `.ai/rules` غير موجود (تم التحقق في 2026-10-20).
> 3. اقرأ `docs/superpowers/plans/2026-10-10-findo-erp-02-sales-crm-projects.md` (قسم "تحليل" و"قرارات") لأن هذه الخطة تبني فوق ما نفّذته.
> 4. نفّذ التاسكات **بالترتيب**. أسماء الدوال وتواقيعها في بلوك **Interfaces** لكل تاسك، والتاسكات اللاحقة تعتمد عليها حرفيًا.
> 5. إن اختلفت أرقام الأسطر عن المقتطفات، ابحث بالنص المميز المذكور وطبّق نفس المعنى.

**Goal:** رفع وحدة المبيعات/CRM الحالية في Findo (الفرص، المتابعات، العملاء، خط البيع) إلى نفس مستوى نظام TCRM (`tcrmdemo.tamiyouz.com`) في الجزء الخاص بالـCRM: داشبورد CRM بالمؤشرات، صفحة Leads بفلاتر وأعمدة وتصدير Excel، ملف عميل محتمل كامل، SLA للتواصل، توزيع تلقائي، قمع مبيعات، لوحة فريق، لوحة مهام وSLA، تقويم، استيراد Excel بربط أعمدة تلقائي، سلة محذوفات باسترجاع، سجل عمليات، وصندوق وارد مُصنّف.

**Architecture:** نفس "محرّك التشغيل": كل الحقول الجديدة تضاف إلى `config/operating.php`، وكل حفظ يمر عبر `OperatingWorkflow::save()`/`transition()` (validation + gates + links + audit). القيم المحسوبة (`phone_key`، `sla_due_at`، `first_contact_at`، `closed_at`، `archived_from`...) تُخزَّن داخل `data` بمفاتيح ليست ضمن `fields` (القاعدة 2.3-4). المنطق الجديد في 5 services: `OperatingCrm` (إعدادات، SLA، توزيع، هاتف، وصول المراحل)، `OperatingLeads` (فلترة، صفوف، ملف العميل المحتمل)، `OperatingCrmReports` (الداشبورد، القمع، الفريق، SLA، التقويم)، `OperatingLeadImport` (استيراد)، و`Spreadsheet` (قراءة/كتابة CSV وXLSX بدون مكتبات). كنترولران جديدان: `CrmController` و`AuditController`. **لا migrations جديدة ولا dependencies جديدة.**

**Tech Stack:** Laravel 13.34، PHP 8.4 (امتدادات `zip` و`SimpleXML` و`xmlreader` و`mbstring` و`intl` متاحة محليًا)، PHPUnit 12.5 (SQLite in-memory)، PostgreSQL 17، Blade، JS عادي في `public/admin-assets/operating.js` (ليس Vite)، CSS في `public/admin-assets/operating.css`.

**Spec:** هذه الخطة نفسها، القسم 1 (تحليل TCRM) هو المرجع الوظيفي. رسالة المطلوب (ميرنا، جروب Findo Programmers، 2026-10-20): "احنا هنعمل الـERP بتاعنا زي ده. اللي في الصورة مركز أكتر عن الـCRM، خلينا نعمله، وبعد كده نكمل نشتغل على السايد بار واللاي أوت، وهيكون فيه شات AI، وكل تاب في السايد بار هقولك هنمشي فيها إزاي."

---

## 1. تحليل TCRM (ما تم استكشافه فعليًا في 2026-10-20)

**طريقة الاستكشاف:** تسجيل دخول بحساب `mernaamir@tamiyouzalrowad.com` (دور: مدير النظام) عبر headless Chrome، وزيارة الصفحات للقراءة فقط، ثم تحليل الـJS bundle (`/assets/index-CiswFLb5.js` و256 chunk) لاستخراج كل الـroutes وأسماء إجراءات الـAPI (tRPC) والنصوص. **الحساب التجريبي فارغ من البيانات**، فكل ما يلي مأخوذ من الشاشات والكود وليس من بيانات حقيقية.

**التقنية عندهم:** React SPA + tRPC + socket.io، RTL بخط Cairo، وضع فاتح/داكن، تبديل AR/EN، Google Analytics.

### 1.1 الشاشات (routes) الخاصة بالـCRM عندهم

| Route عندهم | الشاشة | ما تحتويه |
|---|---|---|
| `/dashboard` | لوحة التحكم | فلتر نطاق تاريخ + زر "عملائي"، 8 بطاقات: إجمالي العملاء، إجمالي الأنشطة، الصفقات المكتسبة، إجمالي الإيرادات (صفقات مقفولة حسب تاريخ الإغلاق)، الإيراد المحتمل (صفقات مفتوحة)، تنبيهات SLA، تحويل التواصل→الاجتماع %، تحويل الاجتماع→الإغلاق %. ثم تبويبات Leads (الليدز / مشترك معي / متابعة)، مهام اليوم، تقويم التذكيرات الشهري، أنشطتي. |
| `/inbox` | مركز الإشعارات | عدادات (غير مقروء، SLA، عملاء جدد)، تبويبات (صندوق الوارد، غير مقروء، تنبيهات SLA، عملاء جدد، التذكيرات، الحملات)، فلاتر (النوع، الوقت، المصدر، المسؤول)، قائمة + لوحة تفاصيل (سياق العميل: الاسم، الهاتف، المسؤول، المرحلة، آخر نشاط). |
| `/leads` | العملاء المحتملون | عدّاد إجمالي، تصدير Excel، عميل جديد، فلاتر: بحث (اسم/هاتف/شركة)، نطاق زمني، المرحلة، جودة العميل، حالة الملاءمة، التصنيف، الحملة، المسؤول، أضيف بواسطة، قناة الوارد، رقم/حساب المصدر، SLA. أعمدة قابلة للإخفاء/الإظهار. إضافة نشاط سريع من الصف. |
| `/leads` (نافذة "عميل جديد") | إنشاء lead | معلومات التواصل (الاسم، الهاتف بكود الدولة + فحص تكرار "هذا الرقم مسجل بالفعل بتاريخ X بواسطة Y")، تفاصيل العميل (النشاط، الجودة، الملاءمة ✅/❌/⏳، الحملة إلزامية، الإعلان)، التعيين، ملاحظات. |
| (نافذة "مركز التصدير") | تصدير Excel | فلتر تاريخ عام (الإنشاء، آخر تحديث، دخول العميل، التواصل، إرسال العرض، إنشاء/إغلاق الصفقة)، فلاتر إضافية، حد صفوف (5,000)، ورقة ملخص، إخفاء أرقام الهاتف، ملاحظات/سبب التصدير. |
| `/leads/:id` | ملف العميل المحتمل | مراحل (Stage Journey)، Mark Won/Lost بملاحظة إغلاق، إشارات (Lead Score، SLA Status، Last Contact، Next Action، Current Stage)، معلومات التواصل والنشاط، الجودة والملاءمة، خط زمني للأنشطة، ملاحظات داخلية، الصفقة (القيمة، المدفوع، الأقساط، العملة SAR/EGP/USD، الخدمات المتعاقد عليها)، الملكية والتعيين، الفريق (متعاونون، مراقب)، المهام والمتابعات القادمة، التذكيرات، المرفقات، سجل التسليم (Handover)، "تحويل إلى صفقة"، "تسليم lead لزميل" بملاحظات. |
| `/sales-funnel` | قمع المبيعات | إجمالي العملاء، الصفقات المكتسبة، الإغلاقات المحتملة، متوسط وقت التواصل، اتجاه العملاء، الصفقات المكتسبة شهريًا، الإيراد، أداء الحملات (تفصيلي)، معدلات التحويل بين المراحل. |
| `/team-dashboard` | لوحة الفريق (للمدير) | العملاء لكل وكيل، العملاء حسب المرحلة، الصفقات والإيراد لكل وكيل، توزيع جودة العملاء، مؤشرات أداء لكل وكيل. |
| `/task-sla` | المهام وSLA | نسبة الالتزام بـSLA، المخالفات، متوسط وقت الاستجابة، إجمالي الأنشطة، اتجاه الالتزام، اتجاه الأنشطة، الأنشطة حسب النوع والنتيجة، أداء SLA لكل وكيل، قائمة العملاء المخالفين (بدون تواصل) مع الوقت المنقضي. |
| `/calendar` | التقويم | شهر، اجتماعات (عنوان، بداية/نهاية، حضور بالإيميل، ربط بـLead)، فتح في Google Calendar. |
| `/import` | استيراد البيانات | 4 خطوات: رفع ← ربط الأعمدة (تلقائي بمرادفات عربية/إنجليزية، "تجاهل هذا العمود") ← معاينة ← اكتمل (عملاء جدد / تكرارات). إعدادات توزيع: الحملة، بدون توزيع / توزيع دوري تلقائي. أعمدة: Client Name, Phone, Country, Business Type, Lead Quality, Campaign Name, Ad Creative, Stage, Notes, Service Introduced, Media Buyer Notes, Lead Time, Sales Agent. |
| `/trash` | سلة المحذوفات | للأدمن فقط. أنواع: العملاء المحتملون، المستخدمون، الحملات، الأنشطة، الصفقات، الملاحظات، المتابعات... استعادة إلى المكان الأصلي، حذف نهائي بكلمة مرور بعد مهلة. |
| `/audit-log` | سجل العمليات | خط زمني يومي، فلاتر (العملية، القسم، الكيان)، معاينة التغييرات (قبل → بعد)، البيانات الخام، تراجع سريع، 50 لكل صفحة. |
| `/settings` | الإعدادات | المستخدمون، مراحل المبيعات (اسم/اسم عربي/لون/ترتيب/تفعيل)، حقول مخصصة، المظهر، **SLA** (تفعيل + "عدد الساعات" 1–720: "نبّه إذا لم يوجد نشاط خلال X ساعة من إنشاء العميل" + "تشغيل فحص SLA الآن")، **توزيع العملاء** (Round Robin، ساعات العمل، أيام العمل، المنطقة الزمنية)، مصادر الـLeads (تكاملات)، الإشعارات. |

**إجراءات الـAPI المرتبطة (من الـbundle):** `leads.{list,byId,create,update,delete,import,checkPhoneConflict,dismissSla,dismissAllSla,markSlaSeen,slaUnseenCount}`، `activities.{byLead,byUser,create,update,delete}`، `deals.{list,byLead,create,update,cancel,addPayment...}`، `dashboard.{stats,agentStats,teamStats,salesFunnel,taskSla}`، `sla.{get,update,check}`، `leadAssignment.{get,save}`، `leadReminders.{create,getToday,getCalendar,markDone}`، `assignments.{addCollaborator,myCollaborated,myWatching}`، `trash.{items,restore,permanentDelete,stats}`، `auditLogs.{list,undo,byLeadStageChanges}`، `inbox.{list,counts,markRead,markAllRead}`، `calendar.{list,create,update,delete}`، `notes.{byLead,create,delete}`.

**مراحل الـLead عندهم:** New Lead → Contacted → Meeting Scheduled → Offer Sent / Proposal Delivered → Follow Up → Won / Lost، و Not Fit، و Contact Again. **جودة العميل:** Hot، Warm، Cold، Bad، Unknown. **نتيجة النشاط:** مهتم، غير مهتم، لا يرد، طلب معاودة الاتصال. **نوع النشاط:** مكالمة، اجتماع، بريد، ملاحظة. **نوع الصفقة:** عقد، تجديد، بيع إضافي. **الأدوار:** Admin، SalesManager، SalesAgent، ColdSalesAgent، MediaBuyer، TechnicalAccountManager، BusinessDeveloper.

### 1.2 الموجود عندنا مقابل TCRM (فجوات)

الرموز: ✅ موجود ويكفي · 🟡 جزئي · ❌ غير موجود.

| ميزة TCRM | عندنا قبل الخطة | التاسك |
|---|---|---|
| Lead (اسم، هاتف، شركة، بريد، مصدر، حملة، مسؤول، قيمة، احتمالية) | ✅ `opportunities` | — |
| الدولة، نوع النشاط، جودة العميل، الملاءمة، قناة الوارد، الإعلان، الخدمة المقدمة، ملاحظات الميديا باير، تاريخ دخول العميل، نوع الصفقة، ملاحظات | ❌ | T1 |
| مرحلة "اجتماع محدد" | ❌ | T1 |
| فحص تكرار الهاتف | ❌ | T1 |
| إنشاء lead سريع بدون (الاحتياج، الحل، الإجراء التالي، المتابعة) | ❌ (إلزامية اليوم) | T1 |
| أول تواصل، تاريخ الإغلاق | ❌ | T1 |
| إعدادات CRM (SLA، التوزيع) | ❌ | T2 |
| SLA (مهلة، تنبيه، إخفاء التنبيه) | ❌ | T3 |
| توزيع دوري Round Robin | ❌ | T4 |
| قراءة/كتابة Excel | ❌ | T5 |
| صفحة Leads بفلاتر وأعمدة وتصدير | 🟡 القائمة العامة (بحث بالعنوان + حالة) | T6 |
| نشاط سريع بنتيجة + متابعة قادمة | 🟡 `activities` بدون نتيجة | T7 |
| ملف العميل المحتمل | 🟡 الفورم العام | T8 |
| Pipeline بالسحب والإفلات | 🟡 أعمدة بلا سحب | T9 |
| داشبورد CRM بالـ8 مؤشرات | ❌ | T10 |
| قمع المبيعات | ❌ | T11 |
| لوحة الفريق | ❌ | T12 |
| المهام وSLA | 🟡 لوحة المتابعات فقط | T13 |
| التقويم | ❌ | T14 |
| استيراد Excel | ❌ | T15 |
| سلة المحذوفات + استعادة | 🟡 الأرشفة موجودة ولا يمكن الخروج منها | T16 |
| سجل العمليات | 🟡 `operating_events` بلا شاشة | T17 |
| صندوق وارد مُصنَّف + إشعار عميل جديد | 🟡 صفحة إشعارات بسيطة | T18 |
| قسم CRM في السايد بار | 🟡 رابطان منفصلان | T19 |

### 1.3 خارج نطاق هذه الخطة (مع السبب)

| ميزة TCRM | السبب |
|---|---|
| شات AI (راكان، تارا، درويش...) | ميرنا حددته كمرحلة لاحقة بعد السايد بار واللاي أوت. |
| إعادة تصميم السايد بار واللاي أوت بالكامل | ميرنا قالت إنها ستحدد كل تاب. هنا نضيف قسم CRM فقط (T19) بدون المساس بباقي القائمة. |
| WhatsApp gateway، Messenger، InnoCall (اتصال)، Tamara/Paymob (دفع)، مزامنة Meta/TikTok/Google/Snap/LinkedIn، Lead sources webhooks | تكاملات خارجية تحتاج حسابات ومفاتيح وموافقة على dependencies. يُخطط لها كل واحدة على حدة. |
| THRS، TWS، TMail، مركز الدعم، Help Center، الجولة الإرشادية | ليست CRM. الحضور موجود عندنا بالفعل. |
| BD module، Renewals، AM/TAM dashboards، Client portal، CSAT | وحدات ما بعد البيع. مرحلة لاحقة. |
| كيان "صفقات" مستقل بمدفوعات وأقساط | عندنا `quotes` و`contracts` و`invoices` و`receipts` تغطي ذلك. الصفقة = الفرصة المكسوبة بقيمتها (القرار Q3). |
| متعاونون/مراقبون على الـLead، حقول مخصصة، مراحل قابلة للتعديل من الواجهة، ألوان المراحل | YAGNI للإصدار الأول. المراحل في `config/operating.php`. |
| "تراجع" في سجل العمليات، والحذف النهائي | يخالف القاعدة 2.3-2 (لا حذف فعلي) و2.3-3. التراجع = تعديل جديد بسبب. |
| بحث شامل ⌘K | مرحلة السايد بار/اللاي أوت. |

## 2. قرارات (محسومة: فوّض المستخدم القرارات المفتوحة لأفضل نتيجة منطقية)

| # | القرار | المحسوم والسبب |
|---|---|---|
| Q1 | أين نبني؟ | داخل المحرّك (`opportunities` هو الـLead). **السبب:** التدقيق والصلاحيات والأرشفة والروابط تعمل تلقائيًا، وكل الاختبارات الحالية (142) تبقى صالحة. |
| Q2 | المراحل | نضيف حالة `meeting` ("اجتماع محدد") بعد `contacted`. تبقى الحالات الأخرى كما هي. المقابلة: Offer Sent = `proposal`، Follow Up = `negotiation`، Not Fit = `unsuitable`، Contact Again = `deferred`. **السبب:** إضافة حالة لا تكسر بيانات قديمة. تغيير/حذف حالات يكسرها. |
| Q3 | الصفقة | لا كيان جديد. "صفقة مكتسبة" = فرصة `won`، وقيمتها `expected_value` بعملتها، وتاريخها `closed_at` (يُسجَّل تلقائيًا عند الانتقال لـ won/lost). نضيف `deal_type` (عقد جديد/تجديد/بيع إضافي). |
| Q4 | الحقول الإلزامية | `need` و`solution` و`next_action` و`follow_up` تصبح **اختيارية عند الإنشاء**، وتُشترط `need` و`solution` عند الانتقال إلى `qualified` وما بعدها (gate). **السبب:** TCRM يسجّل lead بالاسم والهاتف فقط، والاستيراد مستحيل بدونها. تغيير required ليس تغيير نوع حقل (لا يخالف 6.6). |
| Q5 | تكرار الهاتف | `phone_key` = آخر 9 أرقام من الهاتف بعد حذف كل ما ليس رقمًا (يلتقط `+20 100…` و`0100…` كرقم واحد). الحفظ **يُرفض** إن وُجدت فرصة غير مؤرشفة بنفس المفتاح، مع اسمها وتاريخها. الاستيراد يتخطاها ويعدّها "تكرارات". السجلات القديمة تُملأ بأمر `operating:crm-backfill`. |
| Q6 | تعريف SLA | مثل TCRM: مهلة بالساعات (افتراضي 24، من 1 إلى 720) من **وقت إنشاء الـlead**. يُحسب `sla_due_at` عند الإنشاء (لو SLA مفعّل). "أول تواصل" = أول نشاط مكتمل من نوع مكالمة/اجتماع/بريد/واتساب/متابعة مرتبط بالـlead، **أو** نقل الـlead من `new` إلى أي مرحلة غير `unsuitable`/`archived`. الحالات: `pending` (قبل المهلة)، `met` (تواصل قبل المهلة)، `late` (تواصل بعدها)، `breached` (لا تواصل والمهلة انتهت والـlead مفتوح). لا نحسب ساعات العمل في الإصدار الأول (YAGNI، ونوثّقه في صفحة الإعدادات). |
| Q7 | تنبيه SLA | أمر مجدول كل 15 دقيقة `operating:crm-sla` يرسل **مرة واحدة** لكل lead مخالف إلى: المسؤول، ومديره المباشر، وكل `sales_manager`. المدير (owner/sales_manager) يستطيع "إخفاء التنبيه" بسبب، ولا يُحذف أي بيانات. |
| Q8 | التوزيع الدوري | إعداد (تفعيل + قائمة موظفين). يُطبَّق عند الإنشاء **فقط** لو لم يُحدَّد مسؤول **والمُنشئ مدير** (owner/sales_manager) أو عبر الاستيراد. الـsales rep الذي ينشئ lead بلا مسؤول يُسند لنفسه. المؤشر محفوظ في `operating_settings` بالمفتاح `crm_round_robin` (منفصل عن الإعدادات كي لا يرفع نسختها). الموظف غير النشط يُتخطى. |
| Q9 | Excel | بلا مكتبات: قراءة **CSV وXLSX** (ZipArchive + SimpleXML)، وكتابة **XLSX** (ورقة واحدة، inline strings، اتجاه RTL للعربية). **السبب:** القاعدة 2.1 تمنع dependencies بلا موافقة، والامتدادات متاحة. **تحقق قبل النشر** أن `zip` مفعّل على السيرفر (T20). |
| Q10 | حدود الاستيراد والتصدير | الاستيراد: ملف حتى 5MB و**2000 صف**، المعالجة متزامنة (`queue=sync`) مع `set_time_limit(300)`. التصدير: حتى 5000 صف. **السبب:** كل صف يمر بالـworkflow الكامل (validation + event)، و2000 صف ≈ دقيقة. |
| Q11 | سلة المحذوفات | = السجلات المؤرشفة من الأنواع `opportunities`، `activities`، `clients`، `contacts`. عند الأرشفة يُحفظ `archived_from`. **الاستعادة** (owner/sales_manager فقط) تعيد السجل لحالته السابقة بسبب وevent `restore`. **لا حذف نهائي.** |
| Q12 | سجل العمليات | owner يرى كل الأحداث. sales_manager يرى أحداث أنواع المبيعات فقط (`AUDITED_TYPES`) وأحداث `configure_crm` و`export_leads`. باقي الأدوار 403. لا تراجع. |
| Q13 | نطاق الرؤية في اللوحات | sales يرى ما يخصه فقط (`OperatingAccess::query` يفرضه بالفعل). owner/sales_manager يرى الفريق افتراضيًا، ومعه زر "عملائي". لوحة الفريق للمديرين فقط. |
| Q14 | التواريخ | بداية الأسبوع في التقويم **السبت** (`Africa/Cairo`). المفاتيح المحسوبة بصيغة `Y-m-d\TH:i` (مثل `due_at`)، فالمقارنة النصية صحيحة. |
| Q15 | الأداء | اللوحات تحمّل فرص النطاق في الذاكرة (المحرّك JSON). مقبول حتى ~20 ألف فرصة. تجميع الأحداث يتم على دفعات 500 id. التحسين (جدول مساعد) مؤجل حتى يظهر احتياج. |
| Q16 | التصميم | نفس نظام التصميم الحالي (`admin.css` + `operating.css`، tokens `--admin-*`). بطاقات المؤشرات بنفس روح صورة TCRM (أيقونة + رقم + وصف) بدون مكتبات. الرسوم بـCSS/inline SVG (القاعدة 2.1). |
| Q17 | الفرع والدمج | فرع `feature/crm-tcrm-parity` من `main`، commit لكل تاسك، ودمج `--ff-only`. |

## Global Constraints
- لا dependencies جديدة (composer/npm) ولا migrations جديدة.
- PHP المحلي: **`/c/dev-tools/php84/php.exe`** (الـ`php` في PATH هو 8.2 ويفشل). اكتب في كل أمر: `PHP=/c/dev-tools/php84/php.exe` ثم `$PHP artisan …`.
- كل مسار جديد يبدأ بـ`/admin/operations/` ويُسجَّل **قبل** `Route::get('/operations/{type}/create', …)` في `routes/admin.php`.
- لا حذف فعلي، وكل تعديل يكتب `operating_events` بـreason. الأخطاء عبر `$this->workflow->fail($ar, $en)` (أو `$this->fail` داخل `OperatingWorkflow`).
- كل نص عربي في الواجهة يمر عبر `__('…')`، وترجمته تُضاف إلى `lang/en.json` **في نفس التاسك** (الملف JSON بمفاتيح عربية، حافظ على صحة الـJSON).
- التواريخ المحسوبة `Y-m-d\TH:i`، والأيام `Y-m-d`. `created_at` في الجداول `Y-m-d H:i:s`.
- العملة الافتراضية لو فارغة: `USD` (`OperatingSales::currency()`). الإجماليات لكل عملة على حدة، بلا تحويل.
- الاستعلام عن علاقات بقائمة IDs عبر `operating_links` (`field` + `target_id`).
- بعد أي تعديل PHP: `$PHP vendor/bin/pint --dirty --format agent`.
- الاختبارات: `$PHP artisan test --compact <path>`. **خط الأساس قبل البدء: 142 اختبارًا ناجحًا** (2026-10-20).
- Views جديدة تمد `admin.layout` وتستقبل `collections` و`catalog` و`access` (عبر `CrmController::view()`).
- Commit لكل تاسك على الفرع `feature/crm-tcrm-parity`، والرسالة تنتهي بـ:
  `Co-Authored-By: Claude Opus 5.5 (1M context) <noreply@anthropic.com>`

---

## File Structure

| الملف | جديد/تعديل | المسؤولية |
|---|---|---|
| `config/operating.php` | تعديل | حقول `opportunities` الجديدة، حالة `meeting`، حقل `result` في `activities`، تسمية الحالة |
| `app/Services/OperatingCrm.php` | جديد | ثوابت CRM، الإعدادات، الهاتف والتكرار، SLA، التوزيع الدوري، وصول المراحل، hooks الانتقال، الاستعادة |
| `app/Services/OperatingLeads.php` | جديد | فلترة الـLeads، الأعمدة، الصفوف، التصدير، ملف العميل المحتمل |
| `app/Services/OperatingCrmReports.php` | جديد | الداشبورد، القمع، الفريق، SLA، التقويم |
| `app/Services/OperatingLeadImport.php` | جديد | رفع، تخمين ربط الأعمدة، معاينة، تنفيذ الاستيراد |
| `app/Services/Spreadsheet.php` | جديد | قراءة CSV/XLSX وكتابة XLSX |
| `app/Services/OperatingSales.php` | تعديل | `OPEN_STAGES` + `meeting`، `transitionAs()`، `logActivity()`، `version` في بطاقات الـpipeline |
| `app/Services/OperatingWorkflow.php` | تعديل | قواعد حفظ الفرص (هاتف، SLA، توزيع)، gate `qualified+`، hooks الانتقال، `restore()`، إشعار إسناد الـlead |
| `app/Http/Controllers/CrmController.php` | جديد | كل شاشات CRM |
| `app/Http/Controllers/AuditController.php` | جديد | سجل العمليات |
| `app/Http/Controllers/OperatingController.php` | تعديل | `restore` endpoint، prefill لحقول التاريخ |
| `app/Http/Controllers/NotificationController.php` | تعديل | تبويبات صندوق الوارد |
| `app/Console/Commands/CheckCrmSla.php` | جديد | `operating:crm-sla` |
| `app/Console/Commands/BackfillCrm.php` | جديد | `operating:crm-backfill` |
| `routes/admin.php`، `routes/console.php` | تعديل | المسارات والجدولة |
| `resources/views/admin/operating/crm-*.blade.php` | جديد | `crm-settings`، `crm-leads`، `crm-lead`، `crm-dashboard`، `crm-funnel`، `crm-team`، `crm-sla`، `crm-calendar`، `crm-import`، `crm-import-map`، `crm-trash`، `crm-nav` (partial) |
| `resources/views/admin/operating/audit-log.blade.php` | جديد | سجل العمليات |
| `resources/views/admin/operating/opportunities-pipeline.blade.php` | تعديل | عمود الاجتماع + السحب والإفلات |
| `resources/views/admin/operating/notifications.blade.php` | تعديل | التبويبات |
| `resources/views/admin/operating/form.blade.php` | تعديل | رابط "ملف العميل المحتمل" |
| `resources/views/admin/layout.blade.php` | تعديل | قسم CRM في السايد بار |
| `public/admin-assets/operating.css`، `operating.js` | تعديل | مكونات CRM، تذكّر الأعمدة، السحب والإفلات |
| `lang/en.json` | تعديل | الترجمات |
| `tests/Feature/Operating/Crm*Test.php` | جديد | اختبار لكل تاسك (الأسماء في كل تاسك) |
| `tests/Concerns/BuildsOperatingRecords.php` | تعديل | helper `transitionEvent()` |

---

### Task 0: التجهيز

- [ ] **Step 1:** تأكد أن `git status` نظيف وأنك على `main` ومطابق لـ`origin/main`:

```bash
cd /c/work/projects/findogroup && git status --short && git fetch && git status -sb | head -1
git checkout -b feature/crm-tcrm-parity
```

- [ ] **Step 2:** خط الأساس:

```bash
PHP=/c/dev-tools/php84/php.exe; $PHP artisan test --compact
```
Expected: `142 passed`. إن اختلف الرقم، سجّل الرقم الجديد واعتبره الأساس.

- [ ] **Step 3:** تحقق من الامتدادات اللازمة لـExcel:

```bash
$PHP -m | grep -iE '^(zip|SimpleXML|xmlreader|mbstring)$'
```
Expected: الأربعة موجودة.

- [ ] **Step 4:** أضف helper للأحداث في `tests/Concerns/BuildsOperatingRecords.php` (قبل `recordData`)، لأن تاسكات اللوحات تحتاج تسجيل "وصول مرحلة" بتاريخ محدد:

```php
    /**
     * Record a stage change the way OperatingWorkflow::transition() does, so stage-reach metrics can be tested without walking every gate.
     */
    protected function transitionEvent(int $recordId, string $status, string $at = '2026-10-15 10:00:00'): void
    {
        DB::table('operating_events')->insert(['record_id' => $recordId, 'user_id' => null, 'action' => 'transition', 'reason' => 'test', 'before' => null, 'after' => json_encode(['status' => $status]), 'created_at' => $at]);
    }
```

- [ ] **Step 5:** Commit:

```bash
git add tests/Concerns/BuildsOperatingRecords.php
git commit -m "test(crm): add stage transition event helper

Co-Authored-By: Claude Opus 5.5 (1M context) <noreply@anthropic.com>"
```

---

### Task 1: نموذج الـLead الموسّع، حالة الاجتماع، الهاتف، أول تواصل وتاريخ الإغلاق

**Files:**
- Modify: `config/operating.php` (كيان `opportunities` السطر ~498، `activities` السطر ~800، خريطة `statuses` السطر ~7648)
- Create: `app/Services/OperatingCrm.php`
- Modify: `app/Services/OperatingSales.php` (`OPEN_STAGES`)
- Modify: `app/Services/OperatingWorkflow.php` (`save()` كتلة `opportunities` ~173، `transition()` ~586، `gate()` ~986)
- Create: `app/Console/Commands/BackfillCrm.php`
- Modify: `tests/Feature/Operating/LeadsPipelineTest.php` (اختبار `test_proposal_requires_a_client_and_won_requires_a_value`)
- Modify: `lang/en.json`
- Test: `tests/Feature/Operating/CrmLeadModelTest.php`

**Interfaces:**
- Produces:
  - `OperatingCrm::MANAGERS = ['owner', 'sales_manager']`
  - `OperatingCrm::RESTORABLE = ['opportunities', 'activities', 'clients', 'contacts']`
  - `OperatingCrm::FUNNEL = ['new', 'contacted', 'meeting', 'qualified', 'discovery', 'proposal', 'negotiation', 'won']`
  - `OperatingCrm::CONTACT_KINDS = ['call', 'meeting', 'follow_up', 'email', 'whatsapp']`
  - `OperatingCrm::AUDITED_TYPES = ['opportunities', 'activities', 'clients', 'contacts', 'quotes', 'contracts', 'campaigns']`
  - `OperatingCrm::isManager(User $user): bool`
  - `OperatingCrm::phoneKey(?string $phone): ?string`
  - `OperatingCrm::phoneOwner(string $phoneKey, ?int $exceptId = null): ?object`
  - `OperatingCrm::onStageChange(array $data, string $from, string $target): array`
  - `OperatingCrm::touchFirstContact(int $leadId, string $at): void`
  - `OperatingCrm::stageReach(array $ids): array<int, list<string>>`
  - `OperatingCrm::reached(array $statuses, string $stage): bool`
  - `OperatingSales::OPEN_STAGES = ['new', 'contacted', 'meeting', 'qualified', 'discovery', 'proposal', 'negotiation']`
  - مفاتيح `data` المحسوبة للفرص: `phone_key`، `first_contact_at`، `closed_at`، `archived_from`.
  - حقول جديدة للفرص: `country`، `business_profile`، `lead_quality` (hot/warm/cold/bad/unknown)، `fit_status` (fit/not_fit/review)، `lead_channel` (whatsapp/messenger/instagram_dm/facebook_comments/instagram_comments/tiktok/website_chat/phone/email/walk_in/manual)، `ad_creative`، `service_introduced`، `lead_date` (datetime-local)، `deal_type` (contract/renewal/upsell)، `notes`، `media_buyer_notes`.
  - حقل جديد للأنشطة: `result` (interested/not_interested/no_answer/callback).

- [ ] **Step 1: الاختبار الفاشل.** أنشئه:

```bash
$PHP artisan make:test --phpunit CrmLeadModelTest --no-interaction
mv tests/Feature/CrmLeadModelTest.php tests/Feature/Operating/CrmLeadModelTest.php
```

واستبدل محتواه بـ:

```php
<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingCrm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmLeadModelTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function quickLead(array $data = []): array
    {
        return ['title' => 'Omar Hassan', 'reason' => 'Inbound WhatsApp', 'data' => array_merge(['lead_phone' => '+20 100 123 4567', 'lead_source' => 'facebook', 'lead_quality' => 'hot', 'fit_status' => 'review', 'lead_channel' => 'whatsapp', 'country' => 'Egypt', 'business_profile' => 'Restaurant'], $data)];
    }

    public function test_a_lead_can_be_created_with_contact_details_only(): void
    {
        $sales = $this->operatingUser('sales');
        $this->employeeFor($sales);

        $this->actingAs($sales)->post('/admin/operations/opportunities', $this->quickLead())->assertSessionHasNoErrors();

        $lead = DB::table('operating_records')->where('type', 'opportunities')->sole();
        $data = json_decode($lead->data, true);
        $this->assertSame('new', $lead->status);
        $this->assertSame('001234567', $data['phone_key']);
        $this->assertSame('hot', $data['lead_quality']);
    }

    public function test_the_same_phone_in_another_format_is_rejected_as_a_duplicate(): void
    {
        $sales = $this->operatingUser('sales');
        $this->employeeFor($sales);
        $this->actingAs($sales)->post('/admin/operations/opportunities', $this->quickLead())->assertSessionHasNoErrors();

        $this->actingAs($sales)->post('/admin/operations/opportunities', $this->quickLead(['lead_phone' => '01001234567']))->assertSessionHasErrors('workflow');
        $this->assertSame(1, DB::table('operating_records')->where('type', 'opportunities')->count());
    }

    public function test_an_archived_lead_does_not_block_its_phone(): void
    {
        $this->operatingRecord('opportunities', 'archived', ['lead_company' => 'Old', 'lead_phone' => '01001234567', 'phone_key' => '001234567'], 'Old lead');
        $sales = $this->operatingUser('sales');
        $this->employeeFor($sales);

        $this->actingAs($sales)->post('/admin/operations/opportunities', $this->quickLead())->assertSessionHasNoErrors();
    }

    public function test_qualifying_requires_need_and_solution(): void
    {
        $owner = $this->operatingUser('owner');
        $id = $this->operatingRecord('opportunities', 'contacted', ['lead_company' => 'Nile Foods'], 'Lead');

        $this->actingAs($owner)->post("/admin/operations/record/{$id}/transition", ['target' => 'qualified', 'version' => 1, 'reason' => 'Fit'])->assertSessionHasErrors('workflow');

        DB::table('operating_records')->where('id', $id)->update(['data' => json_encode(['lead_company' => 'Nile Foods', 'need' => 'ERP', 'solution' => 'web'])]);
        $this->actingAs($owner)->post("/admin/operations/record/{$id}/transition", ['target' => 'qualified', 'version' => 1, 'reason' => 'Fit'])->assertSessionHasNoErrors();
    }

    public function test_leaving_new_records_first_contact_and_closing_records_closed_at(): void
    {
        $owner = $this->operatingUser('owner');
        $client = $this->operatingRecord('clients', 'active', ['company' => 'Nile Foods', 'decision_maker' => 'Omar', 'contact' => '0100'], 'Nile Foods');
        $id = $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Nile Foods', 'client_id' => $client, 'need' => 'ERP', 'solution' => 'web', 'expected_value' => 1000], 'Lead');

        $this->actingAs($owner)->post("/admin/operations/record/{$id}/transition", ['target' => 'meeting', 'version' => 1, 'reason' => 'Booked'])->assertSessionHasNoErrors();
        $this->assertNotEmpty($this->recordData($id)['first_contact_at']);
        $this->assertArrayNotHasKey('closed_at', $this->recordData($id));

        $this->actingAs($owner)->post("/admin/operations/record/{$id}/transition", ['target' => 'won', 'version' => 2, 'reason' => 'Signed'])->assertSessionHasNoErrors();
        $this->assertNotEmpty($this->recordData($id)['closed_at']);
    }

    public function test_a_completed_call_counts_as_first_contact_but_a_note_does_not(): void
    {
        $owner = $this->operatingUser('owner');
        $employee = $this->employeeFor($owner);
        $lead = $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Nile Foods'], 'Lead');
        $note = $this->operatingRecord('activities', 'planned', ['kind' => 'note', 'assignee_id' => $employee, 'due_at' => '2026-10-20T10:00', 'opportunity_id' => $lead, 'outcome' => 'Internal'], 'Note');
        $call = $this->operatingRecord('activities', 'planned', ['kind' => 'call', 'assignee_id' => $employee, 'due_at' => '2026-10-20T10:00', 'opportunity_id' => $lead, 'outcome' => 'Interested'], 'Call');

        $this->actingAs($owner)->post("/admin/operations/record/{$note}/transition", ['target' => 'done', 'version' => 1, 'reason' => 'Logged'])->assertSessionHasNoErrors();
        $this->assertArrayNotHasKey('first_contact_at', $this->recordData($lead));

        $this->actingAs($owner)->post("/admin/operations/record/{$call}/transition", ['target' => 'done', 'version' => 1, 'reason' => 'Logged'])->assertSessionHasNoErrors();
        $this->assertNotEmpty($this->recordData($lead)['first_contact_at']);
    }

    public function test_stage_reach_counts_later_stages_as_passing_earlier_ones(): void
    {
        $crm = app(OperatingCrm::class);
        $jumped = $this->operatingRecord('opportunities', 'won', ['lead_company' => 'A'], 'A');
        $lostAfterMeeting = $this->operatingRecord('opportunities', 'lost', ['lead_company' => 'B'], 'B');
        $this->transitionEvent($lostAfterMeeting, 'meeting');
        $fresh = $this->operatingRecord('opportunities', 'new', ['lead_company' => 'C'], 'C');

        $reach = $crm->stageReach([$jumped, $lostAfterMeeting, $fresh]);

        $this->assertTrue($crm->reached($reach[$jumped], 'meeting'));
        $this->assertTrue($crm->reached($reach[$lostAfterMeeting], 'contacted'));
        $this->assertFalse($crm->reached($reach[$lostAfterMeeting], 'won'));
        $this->assertFalse($crm->reached($reach[$fresh], 'contacted'));
    }

    public function test_backfill_fills_phone_keys_for_legacy_leads(): void
    {
        $id = $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Legacy', 'lead_phone' => '0100 123 4567'], 'Legacy');

        $this->artisan('operating:crm-backfill')->assertSuccessful();

        $this->assertSame('001234567', $this->recordData($id)['phone_key']);
    }
}
```

> مفتاح الهاتف = آخر 9 أرقام من `201001234567` = `001234567`، ومن `01001234567` = `001234567` أيضًا، وهذا ما يجعل الاختبار الثاني يكتشف التكرار.

- [ ] **Step 2: شغّل الاختبار وتأكد أنه يفشل**

Run: `$PHP artisan test --compact tests/Feature/Operating/CrmLeadModelTest.php`
Expected: FAIL (حقول غير معروفة / `OperatingCrm` غير موجود / الأمر غير موجود).

- [ ] **Step 3: عدّل `config/operating.php`**

3a. في كيان `opportunities`، غيّر `"required": true` إلى `"required": false` للحقول `need`، `solution`، `next_action`، `follow_up` (ابحث عن `"key": "need"` إلخ داخل كتلة `opportunities` فقط، السطور 498–799). **لا تغيّر `lead_source`.**

3b. في نفس الكيان، أضف بعد كائن الحقل `"key": "lead_email"` مباشرة (قبل `"key": "lead_source"`):

```json
        {
          "key": "country",
          "label": { "ar": "الدولة", "en": "Country" },
          "type": "text",
          "required": false
        },
        {
          "key": "business_profile",
          "label": { "ar": "نوع النشاط", "en": "Business profile" },
          "type": "text",
          "required": false
        },
        {
          "key": "lead_quality",
          "label": { "ar": "جودة العميل", "en": "Lead quality" },
          "type": "select",
          "required": false,
          "options": {
            "hot": { "ar": "ساخن", "en": "Hot" },
            "warm": { "ar": "دافئ", "en": "Warm" },
            "cold": { "ar": "بارد", "en": "Cold" },
            "bad": { "ar": "سيئ", "en": "Bad" },
            "unknown": { "ar": "غير محدد", "en": "Unknown" }
          }
        },
        {
          "key": "fit_status",
          "label": { "ar": "مدى الملاءمة", "en": "Fit status" },
          "type": "select",
          "required": false,
          "options": {
            "fit": { "ar": "مناسب", "en": "Fit" },
            "not_fit": { "ar": "غير مناسب", "en": "Not fit" },
            "review": { "ar": "قيد المراجعة", "en": "Under review" }
          }
        },
        {
          "key": "lead_channel",
          "label": { "ar": "قناة الوارد", "en": "Inbound channel" },
          "type": "select",
          "required": false,
          "options": {
            "whatsapp": { "ar": "واتساب", "en": "WhatsApp" },
            "messenger": { "ar": "ماسنجر", "en": "Messenger" },
            "instagram_dm": { "ar": "رسائل إنستجرام", "en": "Instagram DM" },
            "facebook_comments": { "ar": "تعليقات فيسبوك", "en": "Facebook comments" },
            "instagram_comments": { "ar": "تعليقات إنستجرام", "en": "Instagram comments" },
            "tiktok": { "ar": "تيك توك", "en": "TikTok" },
            "website_chat": { "ar": "شات الموقع", "en": "Website chat" },
            "phone": { "ar": "اتصال هاتفي", "en": "Phone call" },
            "email": { "ar": "بريد إلكتروني", "en": "Email" },
            "walk_in": { "ar": "زيارة", "en": "Walk-in" },
            "manual": { "ar": "يدوي", "en": "Manual" }
          }
        },
        {
          "key": "ad_creative",
          "label": { "ar": "الإعلان", "en": "Ad creative" },
          "type": "text",
          "required": false
        },
        {
          "key": "service_introduced",
          "label": { "ar": "الخدمة المقدمة", "en": "Service introduced" },
          "type": "text",
          "required": false
        },
        {
          "key": "lead_date",
          "label": { "ar": "تاريخ دخول العميل", "en": "Lead date" },
          "type": "datetime-local",
          "required": false
        },
```

3c. في نفس الكيان، أضف بعد كائن الحقل `"key": "probability"` (قبل `"key": "expected_close"`):

```json
        {
          "key": "deal_type",
          "label": { "ar": "نوع الصفقة", "en": "Deal type" },
          "type": "select",
          "required": false,
          "options": {
            "contract": { "ar": "عقد جديد", "en": "New contract" },
            "renewal": { "ar": "تجديد", "en": "Renewal" },
            "upsell": { "ar": "بيع إضافي", "en": "Upsell" }
          }
        },
```

3d. في نفس الكيان، أضف بعد كائن الحقل `"key": "handoff_summary"` (آخر حقل، انتبه للفاصلة):

```json
        ,{
          "key": "notes",
          "label": { "ar": "ملاحظات", "en": "Notes" },
          "type": "textarea",
          "required": false
        },
        {
          "key": "media_buyer_notes",
          "label": { "ar": "ملاحظات الميديا باير", "en": "Media buyer notes" },
          "type": "textarea",
          "required": false
        }
```

(احذف الفاصلة الافتتاحية `,{` لو وضعت الفاصلة بعد قوس `handoff_summary` يدويًا. المهم أن يبقى الـJSON صالحًا.)

3e. في `"statuses": [` الخاصة بـ`opportunities` (السطر ~786)، أضف `"meeting",` بعد `"contacted",`.

3f. في كيان `activities`، أضف بعد كائن الحقل `"key": "outcome"`:

```json
        {
          "key": "result",
          "label": { "ar": "نتيجة التواصل", "en": "Contact result" },
          "type": "select",
          "required": false,
          "options": {
            "interested": { "ar": "مهتم", "en": "Interested" },
            "not_interested": { "ar": "غير مهتم", "en": "Not interested" },
            "no_answer": { "ar": "لا يرد", "en": "No answer" },
            "callback": { "ar": "طلب معاودة الاتصال", "en": "Callback requested" }
          }
        },
```

3g. في خريطة `"statuses": {` العامة (السطر ~7648)، أضف بعد `"contacted": {…},`:

```json
    "meeting": {
      "ar": "اجتماع محدد",
      "en": "Meeting scheduled"
    },
```

3h. تحقق من صحة الـJSON:

```bash
$PHP -r '$c = require "config/operating.php"; echo is_array($c) || is_object($c) ? "ok\n" : "broken\n"; $c = json_decode(json_encode($c), true); echo implode(",", $c["entities"]["opportunities"]["statuses"]), "\n";'
```
Expected: `ok` ثم `new,contacted,meeting,qualified,discovery,proposal,negotiation,won,lost,deferred,unsuitable,archived`.

- [ ] **Step 4: أنشئ `app/Services/OperatingCrm.php`**

```bash
$PHP artisan make:class Services/OperatingCrm --no-interaction
```

واستبدل محتواه بـ:

```php
<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class OperatingCrm
{
    /**
     * Roles that run the sales team: they see the whole team, assign, dismiss SLA alerts and restore archived records.
     */
    public const MANAGERS = ['owner', 'sales_manager'];

    /**
     * Types whose archived records appear in the trash and can be restored to the stage they were archived from.
     */
    public const RESTORABLE = ['opportunities', 'activities', 'clients', 'contacts'];

    /**
     * Progress stages in order; reaching a later stage counts as having passed every earlier one.
     */
    public const FUNNEL = ['new', 'contacted', 'meeting', 'qualified', 'discovery', 'proposal', 'negotiation', 'won'];

    /**
     * Activity kinds that count as actually reaching the lead (notes and internal tasks do not).
     */
    public const CONTACT_KINDS = ['call', 'meeting', 'follow_up', 'email', 'whatsapp'];

    /**
     * Record types a sales manager may review in the audit log.
     */
    public const AUDITED_TYPES = ['opportunities', 'activities', 'clients', 'contacts', 'quotes', 'contracts', 'campaigns'];

    public function __construct(private OperatingCatalog $catalog, private OperatingWorkflow $workflow) {}

    public function isManager(User $user): bool
    {
        return in_array($user->role, self::MANAGERS, true);
    }

    /**
     * The last nine digits identify a number regardless of country prefix or leading zero.
     */
    public function phoneKey(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return strlen($digits) >= 6 ? substr($digits, -9) : null;
    }

    public function phoneOwner(string $phoneKey, ?int $exceptId = null): ?object
    {
        return DB::table('operating_records')->where('type', 'opportunities')->where('status', '!=', 'archived')->where('data->phone_key', $phoneKey)->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))->orderBy('id')->first();
    }

    /**
     * Derived lead data written with a stage change: first contact when the lead leaves "new", close date on win or loss.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function onStageChange(array $data, string $from, string $target): array
    {
        $now = now()->format('Y-m-d\TH:i');
        if ($from === 'new' && ! in_array($target, ['unsuitable', 'archived'], true) && empty($data['first_contact_at'])) {
            $data['first_contact_at'] = $now;
        }
        if (in_array($target, ['won', 'lost'], true)) {
            $data['closed_at'] = $now;
        }

        return $data;
    }

    public function touchFirstContact(int $leadId, string $at): void
    {
        $lead = DB::table('operating_records')->where('type', 'opportunities')->find($leadId);
        if ($lead && empty($this->catalog->data($lead)['first_contact_at'])) {
            $this->workflow->mark($lead, 'first_contact_at', $at, 'First contact recorded');
        }
    }

    /**
     * Every status each lead has held: its current one plus every transition target in the audit log.
     *
     * @param  list<int>  $ids
     * @return array<int, list<string>>
     */
    public function stageReach(array $ids): array
    {
        $reach = [];
        foreach (array_chunk($ids, 500) as $chunk) {
            foreach (DB::table('operating_records')->whereIn('id', $chunk)->select('id', 'status')->get() as $record) {
                $reach[$record->id][] = $record->status;
            }
            foreach (DB::table('operating_events')->whereIn('record_id', $chunk)->where('action', 'transition')->select('record_id', 'after')->get() as $event) {
                $status = json_decode($event->after ?? 'null', true)['status'] ?? null;
                if ($status) {
                    $reach[$event->record_id][] = $status;
                }
            }
        }

        return $reach;
    }

    /**
     * @param  list<string>  $statuses
     */
    public function reached(array $statuses, string $stage): bool
    {
        $target = array_search($stage, self::FUNNEL, true);
        foreach ($statuses as $status) {
            $index = array_search($status, self::FUNNEL, true);
            if ($index !== false && $index >= $target) {
                return true;
            }
        }

        return false;
    }
}
```

- [ ] **Step 5: `OperatingSales::OPEN_STAGES`**

في `app/Services/OperatingSales.php` السطر 12:

```php
    public const OPEN_STAGES = ['new', 'contacted', 'meeting', 'qualified', 'discovery', 'proposal', 'negotiation'];
```

- [ ] **Step 6: قواعد الحفظ في `OperatingWorkflow::save()`**

استبدل كتلة `if ($type === 'opportunities') {` (السطر ~173) بـ:

```php
        if ($type === 'opportunities') {
            if (empty($data['client_id']) && empty($data['lead_company']) && empty($data['lead_phone'])) {
                $this->fail('اربط الفرصة بعميل أو سجّل اسم الشركة أو رقم الهاتف.', 'Link a client or enter the lead company or phone.');
            }
            $this->limitAssignment($user, $data, $existing);
            $crm = app(OperatingCrm::class);
            $data['phone_key'] = $crm->phoneKey($data['lead_phone'] ?? null);
            if ($data['phone_key'] && ($duplicate = $crm->phoneOwner($data['phone_key'], $existing?->id))) {
                $since = substr((string) $duplicate->created_at, 0, 10);
                $this->fail('هذا الرقم مسجل بالفعل في الفرصة "'.$duplicate->title.'" منذ '.$since.'.', 'This phone is already registered on lead "'.$duplicate->title.'" since '.$since.'.');
            }
        }
```

> لاحظ أن الشرط الأول اتسع ليقبل lead بالهاتف فقط (Q4). الاختبار القديم `test_a_lead_needs_a_client_or_a_company_but_not_an_existing_client` يرسل `lead_company => null` **مع** `lead_phone` فسيصبح مقبولًا. عدّل سطره الثاني في `LeadsPipelineTest.php` ليرسل الاثنين فارغين:
>
> ```php
>         $this->actingAs($sales)->post('/admin/operations/opportunities', $this->lead(['lead_company' => null, 'lead_phone' => null]))->assertSessionHasErrors('workflow');
> ```

وأضف `use App\Services\OperatingCrm;` غير لازم لأن الكلاسين في نفس الـnamespace `App\Services`.

- [ ] **Step 7: hooks الانتقال في `OperatingWorkflow::transition()`**

بعد السطر `if ($record->type === 'stock_movements' && $target === 'completed') { … }` (قبل `DB::table('operating_records')->where('id', $record->id)->update($changes);`) أضف:

```php
            $derived = $record->type === 'opportunities' ? app(OperatingCrm::class)->onStageChange($data, $record->status, $target) : $data;
            if ($target === 'archived' && in_array($record->type, OperatingCrm::RESTORABLE, true)) {
                $derived['archived_from'] = $record->status;
            }
            if ($derived !== $data && ! isset($changes['data'])) {
                $changes['data'] = json_encode($derived, JSON_UNESCAPED_UNICODE);
            }
```

وبعد سطر `DB::table('operating_records')->where('id', $record->id)->update($changes);` أضف:

```php
            if ($record->type === 'activities' && $target === 'done' && ! empty($data['opportunity_id']) && in_array($data['kind'] ?? null, OperatingCrm::CONTACT_KINDS, true)) {
                app(OperatingCrm::class)->touchFirstContact((int) $data['opportunity_id'], now()->format('Y-m-d\TH:i'));
            }
```

- [ ] **Step 8: gate التأهيل**

في `gate()` داخل `if ($record->type === 'opportunities') {` (السطر ~986)، أضف أول سطر داخل الكتلة:

```php
            if (in_array($target, ['qualified', 'discovery', 'proposal', 'negotiation', 'won'], true)) {
                $this->requireFields($data, ['need', 'solution']);
            }
```

ثم في `tests/Feature/Operating/LeadsPipelineTest.php` داخل `test_proposal_requires_a_client_and_won_requires_a_value`، أضف `'need' => 'ERP', 'solution' => 'web'` إلى بيانات الفرصة في **الموضعين** (`operatingRecord(...)` و`DB::table(...)->update(...)`)، لأن الاختبار يقصد شرطي العميل والقيمة فقط.

- [ ] **Step 9: أمر الملء الرجعي**

```bash
$PHP artisan make:command BackfillCrm --no-interaction
```

واستبدل محتواه بـ:

```php
<?php

namespace App\Console\Commands;

use App\Services\OperatingCatalog;
use App\Services\OperatingCrm;
use App\Services\OperatingWorkflow;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillCrm extends Command
{
    protected $signature = 'operating:crm-backfill';

    protected $description = 'Fill phone keys on leads saved before duplicate detection existed';

    public function handle(OperatingCatalog $catalog, OperatingCrm $crm, OperatingWorkflow $workflow): int
    {
        $filled = 0;
        foreach (DB::table('operating_records')->where('type', 'opportunities')->orderBy('id')->cursor() as $lead) {
            $data = $catalog->data($lead);
            $key = $crm->phoneKey($data['lead_phone'] ?? null);
            if ($key && ($data['phone_key'] ?? null) !== $key) {
                $workflow->mark($lead, 'phone_key', $key, 'Phone key backfilled');
                $filled++;
            }
        }
        $this->info($filled.' leads updated.');

        return self::SUCCESS;
    }
}
```

- [ ] **Step 10: الترجمات** — أضف إلى `lang/en.json` (داخل الكائن، مع الحفاظ على الفواصل):

```json
"هذا الرقم مسجل بالفعل في الفرصة": "This phone is already registered on lead",
"اجتماع محدد": "Meeting scheduled"
```

(نصوص `fail()` تأتي بالنسختين ولا تحتاج مفاتيح. تسميات الـconfig ثنائية بالفعل.)

- [ ] **Step 11: شغّل الاختبارات**

```bash
$PHP artisan test --compact tests/Feature/Operating/CrmLeadModelTest.php tests/Feature/Operating/LeadsPipelineTest.php tests/Feature/Operating/FollowUpsTest.php tests/Feature/Operating/CampaignsTest.php tests/Feature/Operating/QuoteDocumentTest.php
```
Expected: PASS. ثم الحزمة كاملة: `$PHP artisan test --compact` → كلها ناجحة.

- [ ] **Step 12: Pint + Commit**

```bash
$PHP vendor/bin/pint --dirty --format agent
git add config/operating.php app/Services/OperatingCrm.php app/Services/OperatingSales.php app/Services/OperatingWorkflow.php app/Console/Commands/BackfillCrm.php lang/en.json tests/Feature/Operating/CrmLeadModelTest.php tests/Feature/Operating/LeadsPipelineTest.php
git commit -m "feat(crm): richer lead model, meeting stage, phone duplicates, first contact and close dates

Co-Authored-By: Claude Opus 5.5 (1M context) <noreply@anthropic.com>"
```

---

### Task 2: إعدادات CRM (SLA والتوزيع) وصفحتها

**Files:**
- Modify: `app/Services/OperatingCrm.php`
- Create: `app/Http/Controllers/CrmController.php`
- Create: `resources/views/admin/operating/crm-settings.blade.php`
- Modify: `routes/admin.php`
- Modify: `lang/en.json`
- Test: `tests/Feature/Operating/CrmSettingsTest.php`

**Interfaces:**
- Consumes: `OperatingCrm::isManager()` (T1).
- Produces:
  - `OperatingCrm::DEFAULTS = ['sla_enabled' => true, 'sla_hours' => 24, 'round_robin_enabled' => false, 'round_robin_pool' => []]`
  - `OperatingCrm::settings(): array{sla_enabled: bool, sla_hours: int, round_robin_enabled: bool, round_robin_pool: list<int>}`
  - `OperatingCrm::settingsVersion(): int`
  - `OperatingCrm::saveSettings(User $user, array $input): void` (المدخل بعد validation: `version, sla_enabled, sla_hours, round_robin_enabled, round_robin_pool, reason`)
  - `CrmController::view(string $page, array $data): Response` (private) و`CrmController::authorizeCrm(Request $request, bool $manager = false): void` (private).
  - المسارات: `GET /admin/operations/crm/settings`، `POST /admin/operations/crm/settings`.
  - event action: `configure_crm` (record_id = null).

- [ ] **Step 1: الاختبار الفاشل** — `tests/Feature/Operating/CrmSettingsTest.php` (أنشئه بـ`make:test --phpunit CrmSettingsTest` ثم انقله إلى `tests/Feature/Operating/` مثل T1):

```php
<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingCrm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmSettingsTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_defaults_apply_before_anything_is_saved(): void
    {
        $this->assertSame(['sla_enabled' => true, 'sla_hours' => 24, 'round_robin_enabled' => false, 'round_robin_pool' => []], app(OperatingCrm::class)->settings());
    }

    public function test_a_sales_manager_saves_settings_with_an_audited_reason(): void
    {
        $rep = $this->employeeFor($this->operatingUser('sales'));
        $manager = $this->operatingUser('sales_manager');

        $this->actingAs($manager)->get('/admin/operations/crm/settings')->assertOk()->assertSee(__('مهلة التواصل (SLA)'));
        $this->actingAs($manager)->post('/admin/operations/crm/settings', ['version' => 0, 'sla_enabled' => '1', 'sla_hours' => 4, 'round_robin_enabled' => '1', 'round_robin_pool' => [$rep], 'reason' => 'Faster response'])->assertSessionHasNoErrors();

        $settings = app(OperatingCrm::class)->settings();
        $this->assertSame(4, $settings['sla_hours']);
        $this->assertSame([$rep], $settings['round_robin_pool']);
        $this->assertTrue(DB::table('operating_events')->where('action', 'configure_crm')->where('reason', 'Faster response')->exists());
    }

    public function test_a_stale_version_is_rejected(): void
    {
        $manager = $this->operatingUser('sales_manager');
        $payload = ['version' => 0, 'sla_enabled' => '1', 'sla_hours' => 8, 'reason' => 'First'];
        $this->actingAs($manager)->post('/admin/operations/crm/settings', $payload)->assertSessionHasNoErrors();

        $this->actingAs($manager)->post('/admin/operations/crm/settings', $payload)->assertSessionHasErrors('workflow');
    }

    public function test_the_pool_only_accepts_active_employees_and_hours_are_bounded(): void
    {
        $manager = $this->operatingUser('sales_manager');
        $client = $this->operatingRecord('clients', 'active', ['company' => 'X', 'decision_maker' => 'Y', 'contact' => 'Z'], 'X');

        $this->actingAs($manager)->post('/admin/operations/crm/settings', ['version' => 0, 'sla_hours' => 900, 'reason' => 'Bad'])->assertSessionHasErrors('sla_hours');
        $this->actingAs($manager)->post('/admin/operations/crm/settings', ['version' => 0, 'sla_hours' => 24, 'round_robin_pool' => [$client], 'reason' => 'Bad'])->assertSessionHasErrors('workflow');
    }

    public function test_reps_and_other_roles_cannot_open_settings(): void
    {
        $this->actingAs($this->operatingUser('sales'))->get('/admin/operations/crm/settings')->assertForbidden();
        $this->actingAs($this->operatingUser('marketing'))->post('/admin/operations/crm/settings', ['version' => 0, 'sla_hours' => 4, 'reason' => 'x'])->assertForbidden();
    }
}
```

- [ ] **Step 2:** Run: `$PHP artisan test --compact tests/Feature/Operating/CrmSettingsTest.php` → FAIL (404/method missing).

- [ ] **Step 3: أضف إلى `OperatingCrm`** (بعد `isManager()`)، وأضف `use Illuminate\Support\Facades\DB;` موجود بالفعل:

```php
    public const DEFAULTS = ['sla_enabled' => true, 'sla_hours' => 24, 'round_robin_enabled' => false, 'round_robin_pool' => []];

    /**
     * @return array{sla_enabled: bool, sla_hours: int, round_robin_enabled: bool, round_robin_pool: list<int>}
     */
    public function settings(): array
    {
        $saved = DB::table('operating_settings')->where('key', 'crm')->value('data');
        $settings = array_replace(self::DEFAULTS, $saved ? json_decode($saved, true) : []);

        return ['sla_enabled' => (bool) $settings['sla_enabled'], 'sla_hours' => (int) $settings['sla_hours'], 'round_robin_enabled' => (bool) $settings['round_robin_enabled'], 'round_robin_pool' => array_values(array_map('intval', (array) $settings['round_robin_pool']))];
    }

    public function settingsVersion(): int
    {
        return (int) (DB::table('operating_settings')->where('key', 'crm')->value('version') ?? 0);
    }

    /**
     * @param  array{version: int, sla_enabled?: bool|string|null, sla_hours: int, round_robin_enabled?: bool|string|null, round_robin_pool?: list<int|string>|null, reason: string}  $input
     */
    public function saveSettings(User $user, array $input): void
    {
        abort_unless($this->isManager($user), 403);
        $pool = array_values(array_unique(array_map('intval', $input['round_robin_pool'] ?? [])));
        if ($pool && DB::table('operating_records')->where('type', 'employees')->where('status', 'active')->whereIn('id', $pool)->count() !== count($pool)) {
            $this->workflow->fail('قائمة التوزيع تقبل الموظفين النشطين فقط.', 'The rotation only accepts active employees.');
        }
        $data = ['sla_enabled' => (bool) ($input['sla_enabled'] ?? false), 'sla_hours' => (int) $input['sla_hours'], 'round_robin_enabled' => (bool) ($input['round_robin_enabled'] ?? false), 'round_robin_pool' => $pool];
        DB::transaction(function () use ($input, $data, $user): void {
            $old = DB::table('operating_settings')->where('key', 'crm')->lockForUpdate()->first();
            if ((int) ($old?->version ?? 0) !== (int) $input['version']) {
                $this->workflow->fail('تم تعديل الإعدادات في نافذة أخرى. حدّث الصفحة.', 'Settings changed in another window. Refresh first.');
            }
            DB::table('operating_settings')->updateOrInsert(['key' => 'crm'], ['data' => json_encode($data), 'version' => ($old?->version ?? 0) + 1, 'created_at' => $old?->created_at ?? now(), 'updated_at' => now()]);
            $this->workflow->event(null, 'configure_crm', $input['reason'], $old ? json_decode($old->data, true) : null, $data, $user->id);
        });
    }
```

- [ ] **Step 4: أنشئ `CrmController`**

```bash
$PHP artisan make:controller CrmController --no-interaction
```

واستبدل محتواه بـ:

```php
<?php

namespace App\Http\Controllers;

use App\Services\Cms;
use App\Services\OperatingAccess;
use App\Services\OperatingCatalog;
use App\Services\OperatingCrm;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CrmController extends Controller
{
    public function __construct(private OperatingCrm $crm, private OperatingAccess $access, private OperatingCatalog $catalog) {}

    public function settings(Request $request): Response
    {
        $this->authorizeCrm($request, manager: true);

        return $this->view('crm-settings', ['settings' => $this->crm->settings(), 'version' => $this->crm->settingsVersion(), 'employees' => $this->access->query($request->user(), 'employees')->where('status', 'active')->orderBy('title')->pluck('title', 'id')]);
    }

    public function saveSettings(Request $request): Response
    {
        $this->authorizeCrm($request, manager: true);
        $input = $request->validate(['version' => 'required|integer|min:0', 'sla_enabled' => 'nullable|boolean', 'sla_hours' => 'required|integer|min:1|max:720', 'round_robin_enabled' => 'nullable|boolean', 'round_robin_pool' => 'nullable|array|max:100', 'round_robin_pool.*' => 'integer', 'reason' => 'required|string|max:2000']);
        $this->crm->saveSettings($request->user(), $input);

        return back()->with('success', __('تم حفظ إعدادات CRM.'));
    }

    /**
     * Every CRM screen needs read access to leads; manager-only screens also need a sales manager or the owner.
     */
    private function authorizeCrm(Request $request, bool $manager = false): void
    {
        abort_unless($this->access->can($request->user(), 'opportunities'), 403);
        abort_if($manager && ! $this->crm->isManager($request->user()), 403);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function view(string $page, array $data): Response
    {
        return response()->view('admin.operating.'.$page, $data + ['collections' => Cms::COLLECTIONS, 'catalog' => $this->catalog, 'access' => $this->access, 'crm' => $this->crm]);
    }
}
```

> `access->query($user, 'employees')` يعمل لـsales_manager لأن مجموعة `people` تقرأها (تحقق: `config('operating.groups.people.read')` يحتوي `sales_manager`؛ إن لم يحتوه فأضفه في `config/operating.php` في نفس الـcommit).

- [ ] **Step 5: المسارات** — في `routes/admin.php` أضف `use App\Http\Controllers\CrmController as C;` مع باقي الـuse (بالترتيب الأبجدي بعد `AttendanceController as T;`)، ثم أضف قبل سطر `Route::get('/operations/opportunities/pipeline', …)`:

```php
        Route::get('/operations/crm/settings', [C::class, 'settings']);
        Route::post('/operations/crm/settings', [C::class, 'saveSettings']);
```

- [ ] **Step 6: الـview** — `resources/views/admin/operating/crm-settings.blade.php`:

```blade
@extends('admin.layout')
@section('title',__('إعدادات CRM'))
@section('content')
<div class="page-heading"><div><span class="eyebrow">CRM</span><h1>{{ __('إعدادات CRM') }}</h1><p class="muted">{{ __('مهلة التواصل مع العملاء الجدد، والتوزيع الدوري على فريق المبيعات. كل تغيير يُسجَّل بسببه في سجل العمليات.') }}</p></div></div>
<form method="post" action="/admin/operations/crm/settings" class="card narrow">@csrf<input type="hidden" name="version" value="{{ $version }}">
<h2>{{ __('مهلة التواصل (SLA)') }}</h2>
<label class="checkbox"><input type="checkbox" name="sla_enabled" value="1" @checked($settings['sla_enabled'])> {{ __('تفعيل SLA') }}</label>
<label>{{ __('عدد الساعات') }}<input type="number" name="sla_hours" min="1" max="720" value="{{ old('sla_hours',$settings['sla_hours']) }}" dir="ltr" required></label>
<p class="help">{{ __('يظهر تنبيه إذا لم يتم التواصل مع العميل خلال هذه المدة من إنشائه. المدة بالساعات الكاملة ولا تستثني العطلات.') }}</p>
<h2>{{ __('التوزيع الدوري') }}</h2>
<label class="checkbox"><input type="checkbox" name="round_robin_enabled" value="1" @checked($settings['round_robin_enabled'])> {{ __('توزيع العملاء الجدد غير المسندين بالتناوب') }}</label>
<fieldset class="check-grid"><legend>{{ __('الموظفون المشاركون في التوزيع') }}</legend>@forelse($employees as $id=>$name)<label class="checkbox"><input type="checkbox" name="round_robin_pool[]" value="{{ $id }}" @checked(in_array($id,$settings['round_robin_pool'],true))> {{ $name }}</label>@empty<p class="muted">{{ __('لا يوجد موظفون نشطون.') }}</p>@endforelse</fieldset>
<p class="help">{{ __('يُطبَّق على العملاء الذين ينشئهم المدير بدون مسؤول وعلى الاستيراد. مندوب المبيعات الذي ينشئ عميلًا يُسند إليه تلقائيًا.') }}</p>
<label>{{ __('سبب التغيير') }}<textarea name="reason" required></textarea></label>
<button class="button">{{ __('حفظ') }}</button></form>
@endsection
```

- [ ] **Step 7: الترجمات** — أضف إلى `lang/en.json`:

```json
"إعدادات CRM": "CRM settings",
"مهلة التواصل مع العملاء الجدد، والتوزيع الدوري على فريق المبيعات. كل تغيير يُسجَّل بسببه في سجل العمليات.": "Response deadline for new leads and round-robin distribution. Every change is recorded with its reason in the audit log.",
"مهلة التواصل (SLA)": "Response SLA",
"تفعيل SLA": "Enable SLA",
"عدد الساعات": "Hours",
"يظهر تنبيه إذا لم يتم التواصل مع العميل خلال هذه المدة من إنشائه. المدة بالساعات الكاملة ولا تستثني العطلات.": "An alert appears when a lead is not contacted within this time of creation. Counted in full hours, including weekends.",
"التوزيع الدوري": "Round robin",
"توزيع العملاء الجدد غير المسندين بالتناوب": "Rotate new unassigned leads across the team",
"الموظفون المشاركون في التوزيع": "Employees in the rotation",
"لا يوجد موظفون نشطون.": "No active employees.",
"يُطبَّق على العملاء الذين ينشئهم المدير بدون مسؤول وعلى الاستيراد. مندوب المبيعات الذي ينشئ عميلًا يُسند إليه تلقائيًا.": "Applies to leads a manager creates without an owner and to imports. A rep who creates a lead owns it automatically.",
"سبب التغيير": "Reason for change",
"تم حفظ إعدادات CRM.": "CRM settings saved."
```

(تأكد أن مفتاح `حفظ` موجود مسبقًا: `grep -c '"حفظ"' lang/en.json`. إن لم يوجد أضف `"حفظ": "Save"`.)

- [ ] **Step 8: CSS** — أضف في نهاية `public/admin-assets/operating.css`:

```css
.check-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:.4rem;border:1px solid var(--admin-border);border-radius:12px;padding:.75rem;margin:.5rem 0}
.check-grid legend{padding:0 .4rem;font-weight:600}
```

- [ ] **Step 9:** Run: `$PHP artisan test --compact tests/Feature/Operating/CrmSettingsTest.php` → PASS.

- [ ] **Step 10: Pint + Commit**

```bash
$PHP vendor/bin/pint --dirty --format agent
git add app/Services/OperatingCrm.php app/Http/Controllers/CrmController.php resources/views/admin/operating/crm-settings.blade.php routes/admin.php lang/en.json public/admin-assets/operating.css tests/Feature/Operating/CrmSettingsTest.php config/operating.php
git commit -m "feat(crm): versioned CRM settings for SLA and round-robin assignment

Co-Authored-By: Claude Opus 5.5 (1M context) <noreply@anthropic.com>"
```

---

### Task 3: محرّك SLA (المهلة، الحالة، التنبيه المجدول، إخفاء التنبيه)

**Files:**
- Modify: `app/Services/OperatingCrm.php`
- Modify: `app/Services/OperatingWorkflow.php` (كتلة `opportunities` في `save()`)
- Create: `app/Console/Commands/CheckCrmSla.php`
- Modify: `routes/console.php`
- Modify: `app/Http/Controllers/CrmController.php`، `routes/admin.php`
- Modify: `lang/en.json`
- Test: `tests/Feature/Operating/CrmSlaTest.php`

**Interfaces:**
- Consumes: `settings()` (T2)، `OperatingSales::OPEN_STAGES` (T1)، `OperatingNotifier`، `OperatingWorkflow::mark()`.
- Produces:
  - `OperatingCrm::slaDueAt(\Carbon\CarbonInterface $from): ?string`
  - `OperatingCrm::slaState(object $lead, ?string $now = null): ?string` ← `'pending'|'met'|'late'|'breached'|null`
  - `OperatingCrm::openAlert(object $lead, ?string $now = null): bool`
  - `OperatingCrm::responseHours(object $lead): ?float`
  - `OperatingCrm::sendSlaAlerts(\Carbon\CarbonImmutable $now): int`
  - `OperatingCrm::dismissSla(User $user, int $leadId, string $reason): void`
  - مفاتيح `data`: `sla_due_at`، `sla_breached_at`، `sla_dismissed_at`.
  - مسار: `POST /admin/operations/crm/leads/{id}/sla-dismiss` → `CrmController::dismissSla`.
  - نوع الإشعار: `crm_sla`. رابط الإشعار: `/admin/operations/crm/leads/{id}`.
  - أمر: `operating:crm-sla` كل 15 دقيقة.

- [ ] **Step 1: الاختبار الفاشل** — `tests/Feature/Operating/CrmSlaTest.php`:

```php
<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingCrm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmSlaTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_a_new_lead_gets_a_due_time_from_the_configured_hours(): void
    {
        $this->travelTo('2026-10-20 09:00:00');
        $sales = $this->operatingUser('sales');
        $this->employeeFor($sales);

        $this->actingAs($sales)->post('/admin/operations/opportunities', ['title' => 'Lead', 'reason' => 'Inbound', 'data' => ['lead_phone' => '01001234567', 'lead_source' => 'facebook']])->assertSessionHasNoErrors();

        $this->assertSame('2026-10-21T09:00', $this->recordData(DB::table('operating_records')->where('type', 'opportunities')->value('id'))['sla_due_at']);
    }

    public function test_no_due_time_is_set_when_sla_is_disabled(): void
    {
        DB::table('operating_settings')->insert(['key' => 'crm', 'data' => json_encode(['sla_enabled' => false]), 'version' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $sales = $this->operatingUser('sales');
        $this->employeeFor($sales);

        $this->actingAs($sales)->post('/admin/operations/opportunities', ['title' => 'Lead', 'reason' => 'Inbound', 'data' => ['lead_phone' => '01001234567', 'lead_source' => 'facebook']]);

        $this->assertNull($this->recordData(DB::table('operating_records')->where('type', 'opportunities')->value('id'))['sla_due_at'] ?? null);
    }

    public function test_states_cover_pending_met_late_and_breached(): void
    {
        $crm = app(OperatingCrm::class);
        $lead = fn (string $status, array $data): object => DB::table('operating_records')->find($this->operatingRecord('opportunities', $status, $data + ['lead_company' => 'X'], 'X'));
        $now = '2026-10-20T12:00';

        $this->assertSame('pending', $crm->slaState($lead('new', ['sla_due_at' => '2026-10-20T13:00']), $now));
        $this->assertSame('breached', $crm->slaState($lead('new', ['sla_due_at' => '2026-10-20T11:00']), $now));
        $this->assertSame('met', $crm->slaState($lead('contacted', ['sla_due_at' => '2026-10-20T11:00', 'first_contact_at' => '2026-10-20T10:30']), $now));
        $this->assertSame('late', $crm->slaState($lead('contacted', ['sla_due_at' => '2026-10-20T11:00', 'first_contact_at' => '2026-10-20T11:30']), $now));
        $this->assertNull($crm->slaState($lead('lost', ['sla_due_at' => '2026-10-20T11:00']), $now));
        $this->assertNull($crm->slaState($lead('new', []), $now));
    }

    public function test_the_alert_goes_once_to_the_owner_their_manager_and_sales_managers(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $managerUser = $this->operatingUser('delivery');
        $managerEmployee = $this->employeeFor($managerUser);
        $repUser = $this->operatingUser('sales');
        $rep = $this->employeeFor($repUser, ['manager_id' => $managerEmployee]);
        $salesManager = $this->operatingUser('sales_manager');
        $breached = $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Late Co', 'assignee_id' => $rep, 'sla_due_at' => '2026-10-20T11:00'], 'Late Co');
        $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Fine Co', 'assignee_id' => $rep, 'sla_due_at' => '2026-10-20T13:00'], 'Fine Co');
        $this->operatingRecord('opportunities', 'contacted', ['lead_company' => 'Done Co', 'assignee_id' => $rep, 'sla_due_at' => '2026-10-20T11:00', 'first_contact_at' => '2026-10-20T10:00'], 'Done Co');

        $this->artisan('operating:crm-sla')->assertSuccessful();
        $this->artisan('operating:crm-sla')->assertSuccessful();

        foreach ([$repUser, $managerUser, $salesManager] as $user) {
            $this->assertSame(1, $user->notifications()->count());
            $this->assertSame('crm_sla', $user->notifications()->first()->data['kind']);
        }
        $this->assertSame('2026-10-20T12:00', $this->recordData($breached)['sla_breached_at']);
    }

    public function test_only_managers_dismiss_an_open_alert_with_a_reason(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $lead = $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Late Co', 'sla_due_at' => '2026-10-20T11:00'], 'Late Co');

        $this->actingAs($this->operatingUser('sales'))->post("/admin/operations/crm/leads/{$lead}/sla-dismiss", ['reason' => 'x'])->assertForbidden();
        $this->actingAs($this->operatingUser('sales_manager'))->post("/admin/operations/crm/leads/{$lead}/sla-dismiss", ['reason' => 'Client asked to call next week'])->assertSessionHasNoErrors();

        $this->assertFalse(app(OperatingCrm::class)->openAlert(DB::table('operating_records')->find($lead)));
        $this->assertTrue(DB::table('operating_events')->where('record_id', $lead)->where('reason', 'like', '%Client asked%')->exists());
    }
}
```

- [ ] **Step 2:** Run: `$PHP artisan test --compact tests/Feature/Operating/CrmSlaTest.php` → FAIL.

- [ ] **Step 3: أضف إلى `OperatingCrm`.** غيّر الـconstructor ليحقن الـnotifier، وأضف الـimports:

```php
use App\Notifications\OperatingAlert;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
```

```php
    public function __construct(private OperatingCatalog $catalog, private OperatingWorkflow $workflow, private OperatingNotifier $notifier) {}
```

ثم الدوال:

```php
    public function slaDueAt(CarbonInterface $from): ?string
    {
        $settings = $this->settings();

        return $settings['sla_enabled'] ? $from->copy()->addHours($settings['sla_hours'])->format('Y-m-d\TH:i') : null;
    }

    /**
     * pending: still inside the deadline; met / late: first contact before / after it; breached: open, never contacted, deadline passed.
     */
    public function slaState(object $lead, ?string $now = null): ?string
    {
        $data = $this->catalog->data($lead);
        $due = $data['sla_due_at'] ?? null;
        if (! $due) {
            return null;
        }
        if (! empty($data['first_contact_at'])) {
            return $data['first_contact_at'] <= $due ? 'met' : 'late';
        }
        if (! in_array($lead->status, OperatingSales::OPEN_STAGES, true)) {
            return null;
        }

        return ($now ?? now()->format('Y-m-d\TH:i')) > $due ? 'breached' : 'pending';
    }

    public function openAlert(object $lead, ?string $now = null): bool
    {
        return $this->slaState($lead, $now) === 'breached' && empty($this->catalog->data($lead)['sla_dismissed_at']);
    }

    public function responseHours(object $lead): ?float
    {
        $first = $this->catalog->data($lead)['first_contact_at'] ?? null;

        return $first ? round(max(0, CarbonImmutable::parse($lead->created_at)->diffInMinutes(CarbonImmutable::parse($first))) / 60, 1) : null;
    }

    /**
     * One alert per breached lead; the marker keeps reruns of the schedule from repeating it.
     */
    public function sendSlaAlerts(CarbonImmutable $now): int
    {
        $current = $now->format('Y-m-d\TH:i');
        $sent = 0;
        foreach (DB::table('operating_records')->where('type', 'opportunities')->whereIn('status', OperatingSales::OPEN_STAGES)->where('data->sla_due_at', '<', $current)->get() as $lead) {
            $data = $this->catalog->data($lead);
            if (! empty($data['first_contact_at']) || ! empty($data['sla_breached_at'])) {
                continue;
            }
            $assignee = (int) ($data['assignee_id'] ?? 0);
            $this->notifier->send([$assignee ? $this->notifier->employeeUser($assignee) : null, $assignee ? $this->notifier->managerUser($assignee) : null, ...$this->notifier->roles(['sales_manager'])], new OperatingAlert(
                'crm_sla',
                ['ar' => 'تجاوز مهلة التواصل', 'en' => 'Lead response SLA breached'],
                ['ar' => 'لم يتم التواصل مع "'.$lead->title.'" قبل '.str_replace('T', ' ', $data['sla_due_at']).'.', 'en' => '"'.$lead->title.'" was not contacted before '.str_replace('T', ' ', $data['sla_due_at']).'.'],
                '/admin/operations/crm/leads/'.$lead->id,
            ));
            $this->workflow->mark($lead, 'sla_breached_at', $current, 'Lead response SLA breached');
            $sent++;
        }

        return $sent;
    }

    public function dismissSla(User $user, int $leadId, string $reason): void
    {
        abort_unless($this->isManager($user), 403);
        $lead = DB::table('operating_records')->where('type', 'opportunities')->find($leadId);
        abort_unless($lead, 404);
        if (! $this->openAlert($lead)) {
            $this->workflow->fail('لا يوجد تنبيه SLA مفتوح لهذا العميل.', 'This lead has no open SLA alert.');
        }
        $this->workflow->mark($lead, 'sla_dismissed_at', now()->format('Y-m-d\TH:i'), 'SLA alert dismissed: '.$reason);
    }
```

> `mark()` يستخدم `auth()->id()` كمنفّذ للحدث، فيُسجَّل المدير الذي أخفى التنبيه تلقائيًا.

- [ ] **Step 4: احسب المهلة عند الإنشاء.** في كتلة `opportunities` في `save()` (T1) أضف بعد فحص التكرار:

```php
            if (! $existing) {
                $data['sla_due_at'] = $crm->slaDueAt(now());
            }
```

- [ ] **Step 5: الأمر**

```bash
$PHP artisan make:command CheckCrmSla --no-interaction
```

```php
<?php

namespace App\Console\Commands;

use App\Services\OperatingCrm;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class CheckCrmSla extends Command
{
    protected $signature = 'operating:crm-sla';

    protected $description = 'Alert the owner, their manager and sales managers once when a new lead passes its response deadline uncontacted';

    public function handle(OperatingCrm $crm): int
    {
        $this->info($crm->sendSlaAlerts(CarbonImmutable::now()).' SLA alerts sent.');

        return self::SUCCESS;
    }
}
```

وفي `routes/console.php` بعد سطر `operating:follow-up-reminders`:

```php
Schedule::command('operating:crm-sla')->everyFifteenMinutes()->withoutOverlapping();
```

- [ ] **Step 6: endpoint الإخفاء** — في `CrmController`:

```php
    public function dismissSla(int $id, Request $request): Response
    {
        $this->authorizeCrm($request, manager: true);
        $this->access->record($request->user(), $id, 'write');
        $input = $request->validate(['reason' => 'required|string|max:2000']);
        $this->crm->dismissSla($request->user(), $id, $input['reason']);

        return back()->with('success', __('تم إخفاء تنبيه SLA.'));
    }
```

وفي `routes/admin.php` (بجوار مسارات crm/settings):

```php
        Route::post('/operations/crm/leads/{id}/sla-dismiss', [C::class, 'dismissSla'])->whereNumber('id');
```

- [ ] **Step 7: الترجمات**

```json
"تم إخفاء تنبيه SLA.": "SLA alert dismissed."
```

- [ ] **Step 8:** Run: `$PHP artisan test --compact tests/Feature/Operating/CrmSlaTest.php tests/Feature/Operating/CrmLeadModelTest.php` → PASS.

- [ ] **Step 9: Pint + Commit**

```bash
$PHP vendor/bin/pint --dirty --format agent
git add app/Services/OperatingCrm.php app/Services/OperatingWorkflow.php app/Console/Commands/CheckCrmSla.php routes/console.php routes/admin.php app/Http/Controllers/CrmController.php lang/en.json tests/Feature/Operating/CrmSlaTest.php
git commit -m "feat(crm): lead response SLA with scheduled one-time alerts and manager dismissal

Co-Authored-By: Claude Opus 5.5 (1M context) <noreply@anthropic.com>"
```

---

### Task 4: التوزيع الدوري (Round Robin) والإسناد الافتراضي

**Files:**
- Modify: `app/Services/OperatingCrm.php`
- Modify: `app/Services/OperatingWorkflow.php` (كتلة `opportunities` في `save()`)
- Test: `tests/Feature/Operating/CrmAssignmentTest.php`

**Interfaces:**
- Consumes: `settings()` (T2).
- Produces: `OperatingCrm::nextAssignee(): ?int` (يحرّك المؤشر المحفوظ في `operating_settings.key = 'crm_round_robin'` بالشكل `{"last": <employee id>}`).
- سلوك: إنشاء فرصة بلا `assignee_id` ← مدير: `nextAssignee()` (أو بلا مسؤول لو التوزيع معطل)، غير مدير: موظف المُنشئ.

- [ ] **Step 1: الاختبار الفاشل** — `tests/Feature/Operating/CrmAssignmentTest.php`:

```php
<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmAssignmentTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    private function lead(string $phone): array
    {
        return ['title' => 'Lead '.$phone, 'reason' => 'Inbound', 'data' => ['lead_phone' => $phone, 'lead_source' => 'facebook']];
    }

    private function enableRotation(array $pool): void
    {
        DB::table('operating_settings')->insert(['key' => 'crm', 'data' => json_encode(['round_robin_enabled' => true, 'round_robin_pool' => $pool]), 'version' => 1, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function assigneeOf(string $phone): ?int
    {
        return json_decode(DB::table('operating_records')->where('title', 'Lead '.$phone)->value('data'), true)['assignee_id'] ?? null;
    }

    public function test_manager_leads_rotate_across_the_pool_and_skip_inactive_employees(): void
    {
        $first = $this->employeeFor($this->operatingUser('sales'));
        $inactive = $this->employeeFor($this->operatingUser('sales'), [], 'archived');
        $second = $this->employeeFor($this->operatingUser('sales'));
        $this->enableRotation([$first, $inactive, $second]);
        $manager = $this->operatingUser('sales_manager');

        foreach (['01000000001', '01000000002', '01000000003'] as $phone) {
            $this->actingAs($manager)->post('/admin/operations/opportunities', $this->lead($phone))->assertSessionHasNoErrors();
        }

        $this->assertSame([$first, $second, $first], [$this->assigneeOf('01000000001'), $this->assigneeOf('01000000002'), $this->assigneeOf('01000000003')]);
    }

    public function test_a_rep_owns_the_lead_they_create_even_when_rotation_is_on(): void
    {
        $other = $this->employeeFor($this->operatingUser('sales'));
        $this->enableRotation([$other]);
        $rep = $this->operatingUser('sales');
        $own = $this->employeeFor($rep);

        $this->actingAs($rep)->post('/admin/operations/opportunities', $this->lead('01000000009'))->assertSessionHasNoErrors();

        $this->assertSame($own, $this->assigneeOf('01000000009'));
    }

    public function test_without_rotation_a_manager_lead_stays_unassigned_and_an_explicit_owner_wins(): void
    {
        $rep = $this->employeeFor($this->operatingUser('sales'));
        $manager = $this->operatingUser('sales_manager');

        $this->actingAs($manager)->post('/admin/operations/opportunities', $this->lead('01000000011'));
        $this->actingAs($manager)->post('/admin/operations/opportunities', ['title' => 'Lead 01000000012', 'reason' => 'x', 'data' => ['lead_phone' => '01000000012', 'lead_source' => 'facebook', 'assignee_id' => $rep]]);

        $this->assertNull($this->assigneeOf('01000000011'));
        $this->assertSame($rep, $this->assigneeOf('01000000012'));
    }
}
```

- [ ] **Step 2:** Run → FAIL.

- [ ] **Step 3: `nextAssignee()`** في `OperatingCrm`:

```php
    /**
     * Next active employee in the rotation; the cursor lives in its own settings row so it never bumps the settings version.
     */
    public function nextAssignee(): ?int
    {
        $settings = $this->settings();
        if (! $settings['round_robin_enabled'] || ! $settings['round_robin_pool']) {
            return null;
        }
        $active = DB::table('operating_records')->where('type', 'employees')->where('status', 'active')->whereIn('id', $settings['round_robin_pool'])->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $pool = array_values(array_filter($settings['round_robin_pool'], fn (int $id): bool => in_array($id, $active, true)));
        if (! $pool) {
            return null;
        }

        return DB::transaction(function () use ($pool): int {
            $row = DB::table('operating_settings')->where('key', 'crm_round_robin')->lockForUpdate()->first();
            $index = array_search((int) ($row ? (json_decode($row->data, true)['last'] ?? 0) : 0), $pool, true);
            $next = $pool[$index === false ? 0 : ($index + 1) % count($pool)];
            DB::table('operating_settings')->updateOrInsert(['key' => 'crm_round_robin'], ['data' => json_encode(['last' => $next]), 'version' => ($row?->version ?? 0) + 1, 'created_at' => $row?->created_at ?? now(), 'updated_at' => now()]);

            return $next;
        });
    }
```

> تنبيه: لو `index` يشير لموظف أصبح غير نشط وخرج من `$pool`، يعود `false` فنبدأ من أول القائمة. مقبول.

- [ ] **Step 4: طبّقه عند الإنشاء** — في كتلة `opportunities` في `save()` داخل `if (! $existing) {` (من T3) أضف قبل سطر `sla_due_at`:

```php
                if (empty($data['assignee_id'])) {
                    $data['assignee_id'] = $crm->isManager($user) ? $crm->nextAssignee() : $this->access->employeeId($user);
                }
```

> الإسناد التلقائي يأتي **بعد** `limitAssignment()` عن قصد (القاعدة تمنع غير المدير من الإسناد لغيره، والتلقائي ليس اختيار المستخدم). رابط `operating_links` للـ`assignee_id` يُبنى تلقائيًا من `$data` داخل الـtransaction.

- [ ] **Step 5:** Run: `$PHP artisan test --compact tests/Feature/Operating/CrmAssignmentTest.php tests/Feature/Operating/LeadsPipelineTest.php` → PASS.

> لو فشل `test_reps_cannot_assign_leads_to_others_but_the_manager_can` بسبب الإسناد الذاتي التلقائي، فالخلل في ترتيب الخطوات: تأكد أن الإسناد التلقائي داخل `if (! $existing)` وبعد `limitAssignment`.

- [ ] **Step 6: Pint + Commit**

```bash
$PHP vendor/bin/pint --dirty --format agent
git add app/Services/OperatingCrm.php app/Services/OperatingWorkflow.php tests/Feature/Operating/CrmAssignmentTest.php
git commit -m "feat(crm): round-robin lead rotation for manager-created leads, reps own their own

Co-Authored-By: Claude Opus 5.5 (1M context) <noreply@anthropic.com>"
```

---

### Task 5: خدمة Excel (قراءة CSV/XLSX وكتابة XLSX بدون مكتبات)

**Files:**
- Create: `app/Services/Spreadsheet.php`
- Test: `tests/Feature/Operating/CrmSpreadsheetTest.php`

**Interfaces:**
- Produces:
  - `Spreadsheet::read(string $path, string $extension): list<list<string>>` (يحذف الصفوف الفارغة تمامًا، ويقصّ المسافات، ويرمي `RuntimeException` برسالة `encoding` لو الـCSV ليس UTF-8، و`unreadable` لو الملف تالف).
  - `Spreadsheet::xlsx(array $rows, string $sheetName = 'Sheet1', bool $rightToLeft = false): string` (يرجع محتوى الملف الثنائي، الصف الأول عناوين، الأرقام تُكتب كأرقام والباقي نص).

- [ ] **Step 1: الاختبار الفاشل** — `tests/Feature/Operating/CrmSpreadsheetTest.php`:

```php
<?php

namespace Tests\Feature\Operating;

use App\Services\Spreadsheet;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class CrmSpreadsheetTest extends TestCase
{
    private function temp(string $content, string $extension): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sheet').'.'.$extension;
        file_put_contents($path, $content);

        return $path;
    }

    public function test_written_workbooks_read_back_with_arabic_text_and_numbers(): void
    {
        $sheet = app(Spreadsheet::class);
        $binary = $sheet->xlsx([['الاسم', 'Value'], ['عمر حسن', 1500], ['=cmd()', null]], 'Leads', true);

        $rows = $sheet->read($this->temp($binary, 'xlsx'), 'xlsx');

        $this->assertSame([['الاسم', 'Value'], ['عمر حسن', '1500'], ['=cmd()']], $rows);
    }

    public function test_csv_with_bom_and_semicolons_is_parsed_and_blank_rows_dropped(): void
    {
        $rows = app(Spreadsheet::class)->read($this->temp("\xEF\xBB\xBFname;phone\n\"Omar; Jr\";0100\n;\nSara;0111\n", 'csv'), 'csv');

        $this->assertSame([['name', 'phone'], ['Omar; Jr', '0100'], ['Sara', '0111']], $rows);
    }

    public function test_shared_strings_from_excel_files_are_resolved(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'excel').'.xlsx';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $zip->addFromString('xl/sharedStrings.xml', '<?xml version="1.0"?><sst xmlns="'.$ns.'"><si><t>Client Name</t></si><si><r><t>نور </t></r><r><t>الدين</t></r></si></sst>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0"?><worksheet xmlns="'.$ns.'"><sheetData><row r="1"><c r="A1" t="s"><v>0</v></c><c r="C1"><v>7</v></c></row><row r="2"><c r="A2" t="s"><v>1</v></c></row></sheetData></worksheet>');
        $zip->close();

        $this->assertSame([['Client Name', '', '7'], ['نور الدين']], app(Spreadsheet::class)->read($path, 'xlsx'));
    }

    public function test_non_utf8_csv_is_refused_with_a_clear_reason(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('encoding');

        app(Spreadsheet::class)->read($this->temp("name\n\xC7\xE1\xD3\xE3\n", 'csv'), 'csv');
    }
}
```

- [ ] **Step 2:** Run: `$PHP artisan test --compact tests/Feature/Operating/CrmSpreadsheetTest.php` → FAIL.

- [ ] **Step 3: التنفيذ** — `$PHP artisan make:class Services/Spreadsheet --no-interaction` ثم:

```php
<?php

namespace App\Services;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class Spreadsheet
{
    private const MAIN_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    private const REL_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    /**
     * @return list<list<string>>
     */
    public function read(string $path, string $extension): array
    {
        $rows = strtolower($extension) === 'xlsx' ? $this->readXlsx($path) : $this->readCsv($path);

        return array_values(array_filter($rows, fn (array $row): bool => implode('', $row) !== ''));
    }

    /**
     * Text cells are written as inline strings, so a value such as "=cmd()" stays text and never becomes a formula.
     *
     * @param  list<list<string|int|float|null>>  $rows
     */
    public function xlsx(array $rows, string $sheetName = 'Sheet1', bool $rightToLeft = false): string
    {
        $sheet = '';
        foreach (array_values($rows) as $r => $row) {
            $cells = '';
            foreach (array_values($row) as $c => $value) {
                $ref = $this->column($c).($r + 1);
                if (is_int($value) || is_float($value)) {
                    $cells .= '<c r="'.$ref.'"><v>'.$value.'</v></c>';
                } elseif ($value !== null && $value !== '') {
                    $cells .= '<c r="'.$ref.'" t="inlineStr"><is><t xml:space="preserve">'.$this->escape((string) $value).'</t></is></c>';
                }
            }
            $sheet .= '<row r="'.($r + 1).'">'.$cells.'</row>';
        }
        $head = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('unwritable');
        }
        $zip->addFromString('[Content_Types].xml', $head.'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', $head.'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', $head.'<workbook xmlns="'.self::MAIN_NS.'" xmlns:r="'.self::REL_NS.'"><sheets><sheet name="'.$this->escape(mb_substr($sheetName, 0, 31)).'" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', $head.'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml', $head.'<worksheet xmlns="'.self::MAIN_NS.'"><sheetViews><sheetView workbookViewId="0"'.($rightToLeft ? ' rightToLeft="1"' : '').'/></sheetViews><sheetData>'.$sheet.'</sheetData></worksheet>');
        $zip->close();
        $binary = (string) file_get_contents($path);
        unlink($path);

        return $binary;
    }

    /**
     * @return list<list<string>>
     */
    private function readCsv(string $path): array
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', (string) file_get_contents($path));
        if (! mb_check_encoding($content, 'UTF-8')) {
            throw new RuntimeException('encoding');
        }
        $firstLine = strtok($content, "\n") ?: '';
        $delimiter = collect([',', ';', "\t"])->sortByDesc(fn (string $candidate): int => substr_count($firstLine, $candidate))->first();
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);
        $rows = [];
        while (($row = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
            $rows[] = array_map(fn (mixed $cell): string => trim((string) $cell), $row);
        }
        fclose($handle);

        return $rows;
    }

    /**
     * @return list<list<string>>
     */
    private function readXlsx(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('unreadable');
        }
        $shared = [];
        if (($strings = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            foreach ($this->xml($strings)->si as $item) {
                $shared[] = isset($item->t) ? (string) $item->t : implode('', array_map(fn (SimpleXMLElement $run): string => (string) $run->t, iterator_to_array($item->r, false)));
            }
        }
        $sheetXml = $zip->getFromName($this->firstSheet($zip));
        $zip->close();
        if ($sheetXml === false) {
            throw new RuntimeException('unreadable');
        }
        $rows = [];
        foreach ($this->xml($sheetXml)->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $cell) {
                $ref = preg_replace('/\d+/', '', (string) $cell['r']);
                $index = $ref !== '' ? $this->columnIndex($ref) : count($cells);
                $cells[$index] = trim(match ((string) $cell['t']) {
                    's' => $shared[(int) $cell->v] ?? '',
                    'inlineStr' => (string) $cell->is->t,
                    'b' => (string) $cell->v === '1' ? 'TRUE' : 'FALSE',
                    default => (string) $cell->v,
                });
            }
            $rows[] = $cells ? array_map(fn (int $i): string => $cells[$i] ?? '', range(0, max(array_keys($cells)))) : [];
        }

        return $rows;
    }

    private function firstSheet(ZipArchive $zip): string
    {
        $workbook = $zip->getFromName('xl/workbook.xml');
        $relations = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbook !== false && $relations !== false) {
            $sheet = $this->xml($workbook)->sheets->sheet[0] ?? null;
            $id = $sheet ? (string) $sheet->attributes(self::REL_NS)['id'] : '';
            foreach ($this->xml($relations)->Relationship as $relation) {
                if ((string) $relation['Id'] === $id) {
                    $target = ltrim((string) $relation['Target'], '/');

                    return str_starts_with($target, 'xl/') ? $target : 'xl/'.$target;
                }
            }
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private function xml(string $content): SimpleXMLElement
    {
        $xml = simplexml_load_string($content, SimpleXMLElement::class, LIBXML_NONET);
        if ($xml === false) {
            throw new RuntimeException('unreadable');
        }

        return $xml;
    }

    private function column(int $index): string
    {
        $name = '';
        for ($index++; $index > 0; $index = intdiv($index - 1, 26)) {
            $name = chr(65 + ($index - 1) % 26).$name;
        }

        return $name;
    }

    private function columnIndex(string $letters): int
    {
        $index = 0;
        foreach (str_split(strtoupper($letters)) as $letter) {
            $index = $index * 26 + ord($letter) - 64;
        }

        return $index - 1;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars(preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $value) ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
```

> `LIBXML_NONET` يمنع جلب كيانات خارجية من الشبكة (حماية XXE). PHP 8 يعطّل تحميل الكيانات الخارجية افتراضيًا أيضًا.

- [ ] **Step 4:** Run → PASS. لو فشل اختبار الـshared strings بسبب الـnamespace الافتراضي، استبدل `$this->xml($strings)->si` بـ`$this->xml($strings)->children(self::MAIN_NS)->si` وطبّق نفس الشيء على `sheetData` و`sheets` (SimpleXML يحتاجه في بعض بنيات libxml).

- [ ] **Step 5: Pint + Commit**

```bash
$PHP vendor/bin/pint --dirty --format agent
git add app/Services/Spreadsheet.php tests/Feature/Operating/CrmSpreadsheetTest.php
git commit -m "feat(crm): dependency-free CSV/XLSX reader and XLSX writer

Co-Authored-By: Claude Opus 5.5 (1M context) <noreply@anthropic.com>"
```

---

### Task 6: صفحة العملاء المحتملين (فلاتر، أعمدة، تصدير Excel) + مكونات CSS لكل شاشات CRM

**Files:**
- Create: `app/Services/OperatingLeads.php`
- Modify: `app/Services/OperatingCrm.php` (ثابت `SLA_LABELS`)
- Modify: `app/Http/Controllers/CrmController.php`
- Create: `resources/views/admin/operating/crm-leads.blade.php`
- Modify: `routes/admin.php`، `public/admin-assets/operating.css`، `public/admin-assets/operating.js`، `lang/en.json`
- Test: `tests/Feature/Operating/CrmLeadsListTest.php`

**Interfaces:**
- Consumes: `OperatingCrm::slaState()` (T3)، `Spreadsheet::xlsx()` (T5)، `OperatingSales::titles()`، `OperatingSales::currency()`.
- Produces:
  - `OperatingCrm::SLA_LABELS = ['pending' => 'ضمن المهلة', 'met' => 'في الموعد', 'late' => 'متأخر', 'breached' => 'متجاوز']`
  - `OperatingLeads::COLUMNS` (مفتاح ← عنوان عربي)، `DEFAULT_COLUMNS`، `PER_PAGE = [25, 50, 100]`، `EXPORT_LIMIT = 5000`
  - `OperatingLeads::query(User $user, array $filters): \Illuminate\Database\Query\Builder` (مفاتيح الفلاتر: `q, date_field (created|contact|closed), from, to, stage, quality, fit, source, channel, campaign, assignee, added_by, sla (breached)`)
  - `OperatingLeads::rows(iterable $records): list<array<string, mixed>>` (كل صف فيه: `id, version, title, lead_phone, lead_company, country, business_profile, status, status_key, lead_quality, lead_quality_key, fit_status, campaign, ad_creative, assignee, lead_source, lead_channel, expected_value, sla, created_at, first_contact_at, closed_at, added_by`)
  - `OperatingLeads::export(User $user, array $filters, array $columns, bool $maskPhones, string $reason): string`
  - `CrmController::leadFilters(Request $request): array` و`CrmController::leadOptions(Request $request): array` (private، يستخدمهما T10–T13 أيضًا).
  - مسارات: `GET /admin/operations/crm/leads`، `POST /admin/operations/crm/leads/export`.
  - event action: `export_leads` (record_id = null).

- [ ] **Step 1: الاختبار الفاشل** — `tests/Feature/Operating/CrmLeadsListTest.php`:

```php
<?php

namespace Tests\Feature\Operating;

use App\Services\Spreadsheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmLeadsListTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    private int $rep;

    private int $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-20 12:00:00');
        $this->rep = $this->employeeFor($this->operatingUser('sales'));
        $this->other = $this->employeeFor($this->operatingUser('sales'));
        $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Hot Foods', 'lead_phone' => '01001112223', 'phone_key' => '001112223', 'lead_quality' => 'hot', 'assignee_id' => $this->rep, 'sla_due_at' => '2026-10-20T10:00'], 'Hot Foods');
        $this->operatingRecord('opportunities', 'meeting', ['lead_company' => 'Cold Tech', 'lead_phone' => '01009998887', 'phone_key' => '009998887', 'lead_quality' => 'cold', 'assignee_id' => $this->other, 'first_contact_at' => '2026-10-19T09:00', 'sla_due_at' => '2026-10-19T10:00'], 'Cold Tech');
        $this->operatingRecord('opportunities', 'archived', ['lead_company' => 'Gone'], 'Gone');
    }

    public function test_managers_filter_by_stage_quality_phone_and_breached_sla(): void
    {
        $manager = $this->operatingUser('sales_manager');

        $this->actingAs($manager)->get('/admin/operations/crm/leads')->assertOk()->assertSee('Hot Foods')->assertSee('Cold Tech')->assertDontSee('Gone');
        $this->actingAs($manager)->get('/admin/operations/crm/leads?stage=meeting')->assertSee('Cold Tech')->assertDontSee('Hot Foods');
        $this->actingAs($manager)->get('/admin/operations/crm/leads?quality=hot')->assertSee('Hot Foods')->assertDontSee('Cold Tech');
        $this->actingAs($manager)->get('/admin/operations/crm/leads?q=+20 100 999 8887')->assertSee('Cold Tech')->assertDontSee('Hot Foods');
        $this->actingAs($manager)->get('/admin/operations/crm/leads?sla=breached')->assertSee('Hot Foods')->assertDontSee('Cold Tech');
        $this->actingAs($manager)->get('/admin/operations/crm/leads?assignee='.$this->other)->assertSee('Cold Tech')->assertDontSee('Hot Foods');
    }

    public function test_a_rep_only_sees_their_own_leads_and_chosen_columns(): void
    {
        $rep = DB::table('users')->where('role', 'sales')->orderBy('id')->first();
        $user = \App\Models\User::find($rep->id);

        $this->actingAs($user)->get('/admin/operations/crm/leads?columns[]=title&columns[]=lead_quality')->assertOk()->assertSee('Hot Foods')->assertDontSee('Cold Tech')->assertSee(__('جودة العميل'))->assertDontSee('<th>'.__('الهاتف').'</th>', false);
    }

    public function test_export_writes_an_audited_workbook_with_masked_phones(): void
    {
        $manager = $this->operatingUser('sales_manager');

        $response = $this->actingAs($manager)->post('/admin/operations/crm/leads/export', ['columns' => ['title', 'lead_phone', 'sla'], 'mask_phones' => '1', 'stage' => 'new', 'reason' => 'Weekly review'])->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'export').'.xlsx';
        file_put_contents($path, $response->getContent());
        $rows = app(Spreadsheet::class)->read($path, 'xlsx');
        $this->assertSame([__('اسم العميل'), __('الهاتف'), 'SLA'], $rows[0]);
        $this->assertSame(['Hot Foods', '•••••••2223', __('متجاوز')], $rows[1]);
        $this->assertCount(2, $rows);
        $this->assertTrue(DB::table('operating_events')->where('action', 'export_leads')->where('reason', 'Weekly review')->exists());
    }

    public function test_roles_without_lead_access_are_refused(): void
    {
        $this->actingAs($this->operatingUser('designer'))->get('/admin/operations/crm/leads')->assertForbidden();
        $this->actingAs($this->operatingUser('designer'))->post('/admin/operations/crm/leads/export', ['columns' => ['title']])->assertForbidden();
    }
}
```

- [ ] **Step 2:** Run → FAIL.

- [ ] **Step 3:** في `OperatingCrm` أضف تحت `AUDITED_TYPES`:

```php
    /**
     * Arabic keys for __(); the English text lives in lang/en.json.
     */
    public const SLA_LABELS = ['pending' => 'ضمن المهلة', 'met' => 'في الموعد', 'late' => 'متأخر', 'breached' => 'متجاوز'];
```

- [ ] **Step 4: `app/Services/OperatingLeads.php`** (`make:class Services/OperatingLeads`):

```php
<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class OperatingLeads
{
    /**
     * Column key => Arabic label key for __().
     */
    public const COLUMNS = [
        'title' => 'اسم العميل', 'lead_phone' => 'الهاتف', 'lead_company' => 'الشركة', 'country' => 'الدولة', 'business_profile' => 'نوع النشاط',
        'status' => 'المرحلة', 'lead_quality' => 'جودة العميل', 'fit_status' => 'مدى الملاءمة', 'campaign' => 'الحملة', 'ad_creative' => 'الإعلان',
        'assignee' => 'المسؤول', 'lead_source' => 'المصدر', 'lead_channel' => 'قناة الوارد', 'expected_value' => 'القيمة المتوقعة', 'sla' => 'SLA',
        'created_at' => 'تاريخ الإنشاء', 'first_contact_at' => 'أول تواصل', 'closed_at' => 'تاريخ الإغلاق', 'added_by' => 'أضيف بواسطة',
    ];

    public const DEFAULT_COLUMNS = ['title', 'lead_phone', 'status', 'lead_quality', 'campaign', 'assignee', 'sla', 'created_at'];

    public const PER_PAGE = [25, 50, 100];

    public const EXPORT_LIMIT = 5000;

    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access, private OperatingCrm $crm, private OperatingSales $sales, private OperatingWorkflow $workflow, private Spreadsheet $spreadsheet) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function query(User $user, array $filters): Builder
    {
        $query = $this->access->query($user, 'opportunities')->where('status', '!=', 'archived');
        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $digits = preg_replace('/\D+/', '', $search);
            $query->where(function (Builder $scope) use ($search, $digits): void {
                $scope->where('title', 'like', '%'.$search.'%')->orWhere('data->lead_company', 'like', '%'.$search.'%')->orWhere('data->lead_contact', 'like', '%'.$search.'%');
                if (strlen($digits) >= 4) {
                    $scope->orWhere('data->phone_key', 'like', '%'.substr($digits, -9).'%');
                }
            });
        }
        $dateColumn = ['contact' => 'data->first_contact_at', 'closed' => 'data->closed_at'][$filters['date_field'] ?? 'created'] ?? null;
        if (! empty($filters['from'])) {
            $dateColumn ? $query->where($dateColumn, '>=', $filters['from']) : $query->where('created_at', '>=', $filters['from'].' 00:00:00');
        }
        if (! empty($filters['to'])) {
            $dateColumn ? $query->where($dateColumn, '<=', $filters['to'].'T23:59') : $query->where('created_at', '<=', $filters['to'].' 23:59:59');
        }
        foreach (['stage' => 'status', 'quality' => 'data->lead_quality', 'fit' => 'data->fit_status', 'source' => 'data->lead_source', 'channel' => 'data->lead_channel'] as $key => $column) {
            if (! empty($filters[$key])) {
                $query->where($column, $filters[$key]);
            }
        }
        foreach (['campaign' => 'data->campaign_id', 'assignee' => 'data->assignee_id', 'added_by' => 'created_by'] as $key => $column) {
            if (! empty($filters[$key])) {
                $query->where($column, (int) $filters[$key]);
            }
        }
        if (($filters['sla'] ?? null) === 'breached') {
            $query->whereIn('status', OperatingSales::OPEN_STAGES)->where('data->sla_due_at', '<', now()->format('Y-m-d\TH:i'))->whereNull('data->first_contact_at');
        }

        return $query;
    }

    /**
     * @param  iterable<object>  $records
     * @return list<array<string, mixed>>
     */
    public function rows(iterable $records): array
    {
        $records = collect($records);
        $ids = [];
        foreach ($records as $record) {
            $data = $this->catalog->data($record);
            array_push($ids, $data['campaign_id'] ?? null, $data['assignee_id'] ?? null);
        }
        $titles = $this->sales->titles(array_values(array_unique(array_filter($ids))));
        $users = DB::table('users')->whereIn('id', $records->pluck('created_by')->filter()->unique()->all())->pluck('name', 'id')->all();
        $fields = collect($this->catalog->entity('opportunities')['fields'])->keyBy('key');
        $option = fn (string $key, mixed $value): ?string => $value !== null && isset($fields[$key]['options'][$value]) ? $this->catalog->label($fields[$key]['options'][$value]) : ($value === null ? null : (string) $value);
        $moment = fn (?string $value): ?string => $value ? str_replace('T', ' ', $value) : null;

        return $records->map(function (object $record) use ($titles, $users, $option, $moment): array {
            $data = $this->catalog->data($record);

            return [
                'id' => $record->id, 'version' => $record->version, 'title' => $record->title,
                'lead_phone' => $data['lead_phone'] ?? null, 'lead_company' => $data['lead_company'] ?? null, 'country' => $data['country'] ?? null, 'business_profile' => $data['business_profile'] ?? null,
                'status' => $this->catalog->status($record->status), 'status_key' => $record->status,
                'lead_quality' => $option('lead_quality', $data['lead_quality'] ?? null), 'lead_quality_key' => $data['lead_quality'] ?? null,
                'fit_status' => $option('fit_status', $data['fit_status'] ?? null), 'campaign' => $titles[$data['campaign_id'] ?? 0] ?? null, 'ad_creative' => $data['ad_creative'] ?? null,
                'assignee' => $titles[$data['assignee_id'] ?? 0] ?? null, 'lead_source' => $option('lead_source', $data['lead_source'] ?? null), 'lead_channel' => $option('lead_channel', $data['lead_channel'] ?? null),
                'expected_value' => isset($data['expected_value']) && $data['expected_value'] !== '' ? number_format((float) $data['expected_value'], 2).' '.$this->sales->currency($data) : null,
                'sla' => $this->crm->slaState($record), 'created_at' => substr((string) $record->created_at, 0, 16),
                'first_contact_at' => $moment($data['first_contact_at'] ?? null), 'closed_at' => $moment($data['closed_at'] ?? null), 'added_by' => $users[$record->created_by] ?? null,
            ];
        })->values()->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  list<string>  $columns
     */
    public function export(User $user, array $filters, array $columns, bool $maskPhones, string $reason): string
    {
        $records = $this->query($user, $filters)->latest('created_at')->limit(self::EXPORT_LIMIT)->get();
        $sheet = [array_map(fn (string $column): string => __(self::COLUMNS[$column]), $columns)];
        foreach ($this->rows($records) as $row) {
            if ($maskPhones && $row['lead_phone']) {
                $row['lead_phone'] = str_repeat('•', max(0, mb_strlen($row['lead_phone']) - 4)).mb_substr($row['lead_phone'], -4);
            }
            $row['sla'] = $row['sla'] ? __(OperatingCrm::SLA_LABELS[$row['sla']]) : null;
            $sheet[] = array_map(fn (string $column): mixed => $row[$column] ?? null, $columns);
        }
        $this->workflow->event(null, 'export_leads', $reason, null, ['filters' => array_filter($filters), 'columns' => $columns, 'rows' => $records->count(), 'masked' => $maskPhones], $user->id);

        return $this->spreadsheet->xlsx($sheet, 'Leads', app()->getLocale() === 'ar');
    }
}
```

> الهاتف `01001112223` طوله 11، فالقناع 7 نقاط + `2223`، وهذا ما يتوقعه الاختبار.

- [ ] **Step 5: الكنترولر** — في `CrmController` أضف للـconstructor `private OperatingLeads $leads` (و`use App\Services\OperatingLeads;` و`use Illuminate\Support\Facades\DB;`)، ثم:

```php
    public function leads(Request $request): Response
    {
        $this->authorizeCrm($request);
        $filters = $this->leadFilters($request);
        $columns = array_values(array_intersect((array) $request->input('columns', OperatingLeads::DEFAULT_COLUMNS), array_keys(OperatingLeads::COLUMNS))) ?: OperatingLeads::DEFAULT_COLUMNS;
        $perPage = in_array($request->integer('per_page'), OperatingLeads::PER_PAGE, true) ? $request->integer('per_page') : 25;
        $page = $this->leads->query($request->user(), $filters)->latest('created_at')->paginate($perPage)->withQueryString();

        return $this->view('crm-leads', ['filters' => $filters, 'columns' => $columns, 'perPage' => $perPage, 'page' => $page, 'rows' => $this->leads->rows($page->items()), 'options' => $this->leadOptions($request)]);
    }

    public function exportLeads(Request $request): Response
    {
        $this->authorizeCrm($request);
        $filters = $this->leadFilters($request);
        $input = $request->validate(['columns' => 'required|array|min:1', 'columns.*' => 'in:'.implode(',', array_keys(OperatingLeads::COLUMNS)), 'mask_phones' => 'nullable|boolean', 'reason' => 'nullable|string|max:2000']);
        $binary = $this->leads->export($request->user(), $filters, array_values($input['columns']), (bool) ($input['mask_phones'] ?? false), $input['reason'] ?? 'Leads export');

        return response($binary, 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="leads-'.now()->format('Ymd-His').'.xlsx"']);
    }

    /**
     * @return array<string, mixed>
     */
    private function leadFilters(Request $request): array
    {
        return $request->validate(['q' => 'nullable|string|max:200', 'date_field' => 'nullable|in:created,contact,closed', 'from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from', 'stage' => 'nullable|string|max:40', 'quality' => 'nullable|string|max:40', 'fit' => 'nullable|string|max:40', 'source' => 'nullable|string|max:40', 'channel' => 'nullable|string|max:40', 'campaign' => 'nullable|integer', 'assignee' => 'nullable|integer', 'added_by' => 'nullable|integer', 'sla' => 'nullable|in:breached']);
    }

    /**
     * Choices for the lead filters; the people lists are only offered to managers because a rep already sees just their own leads.
     *
     * @return array<string, array<int|string, string>>
     */
    private function leadOptions(Request $request): array
    {
        $user = $request->user();
        $entity = $this->catalog->entity('opportunities');
        $fields = collect($entity['fields'])->keyBy('key');
        $labels = fn (string $key): array => array_map(fn (array $label): string => $this->catalog->label($label), (array) $fields[$key]['options']);

        return [
            'stages' => collect($entity['statuses'])->reject(fn (string $status): bool => $status === 'archived')->mapWithKeys(fn (string $status): array => [$status => $this->catalog->status($status)])->all(),
            'quality' => $labels('lead_quality'), 'fit' => $labels('fit_status'), 'source' => $labels('lead_source'), 'channel' => $labels('lead_channel'),
            'campaigns' => $this->access->can($user, 'campaigns') ? $this->access->query($user, 'campaigns')->where('status', '!=', 'archived')->orderBy('title')->pluck('title', 'id')->all() : [],
            'assignees' => $this->crm->isManager($user) ? $this->access->query($user, 'employees')->where('status', 'active')->orderBy('title')->pluck('title', 'id')->all() : [],
            'users' => $this->crm->isManager($user) ? DB::table('users')->where('active', true)->orderBy('name')->pluck('name', 'id')->all() : [],
        ];
    }
```

> `(array) $fields[$key]['options']`: الخيارات في الـconfig كائن JSON يتحول لمصفوفة عبر `OperatingCatalog`. لو وجدتها `stdClass` استخدم `json_decode(json_encode(...), true)`.

- [ ] **Step 6: المسارات** (قبل مسار `crm/leads/{id}/sla-dismiss`):

```php
        Route::get('/operations/crm/leads', [C::class, 'leads']);
        Route::post('/operations/crm/leads/export', [C::class, 'exportLeads']);
```

- [ ] **Step 7: الـview** — `resources/views/admin/operating/crm-leads.blade.php`:

```blade
@extends('admin.layout')
@section('title',__('العملاء المحتملون'))
@section('content')
@php $canWrite=$access->can(auth()->user(),'opportunities','write'); $q=request()->query(); @endphp
<div class="page-heading"><div><span class="eyebrow">CRM</span><h1>{{ __('العملاء المحتملون') }}</h1><p class="muted">{{ __('إدارة ومتابعة جميع العملاء المحتملين') }} · <b>{{ number_format($page->total()) }}</b> {{ __('عميل') }}</p></div><div class="row-actions"><a class="button secondary" href="/admin/operations/opportunities/pipeline">{{ __('خط البيع') }}</a>@if($crm->isManager(auth()->user()))<a class="button secondary" href="/admin/operations/crm/import">{{ __('استيراد') }}</a>@endif @if($canWrite)<a class="button" href="/admin/operations/opportunities/create">+ {{ __('عميل جديد') }}</a>@endif</div></div>
<form class="card filters crm-filters" method="get" data-crm-columns-form>
<input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('ابحث بالاسم أو الهاتف أو الشركة...') }}" aria-label="{{ __('بحث') }}">
<select name="date_field" aria-label="{{ __('نوع التاريخ') }}">@foreach(['created'=>'تاريخ الإنشاء','contact'=>'أول تواصل','closed'=>'تاريخ الإغلاق'] as $key=>$label)<option value="{{ $key }}" @selected(($filters['date_field']??'created')===$key)>{{ __($label) }}</option>@endforeach</select>
<input type="date" name="from" value="{{ $filters['from'] ?? '' }}" aria-label="{{ __('من') }}"><input type="date" name="to" value="{{ $filters['to'] ?? '' }}" aria-label="{{ __('إلى') }}">
@foreach(['stage'=>['stages','المرحلة'],'quality'=>['quality','جودة العميل'],'fit'=>['fit','مدى الملاءمة'],'source'=>['source','المصدر'],'channel'=>['channel','قناة الوارد'],'campaign'=>['campaigns','الحملة'],'assignee'=>['assignees','المسؤول'],'added_by'=>['users','أضيف بواسطة']] as $name=>[$list,$label])@if($options[$list])<select name="{{ $name }}" aria-label="{{ __($label) }}"><option value="">{{ __($label) }}: {{ __('الكل') }}</option>@foreach($options[$list] as $value=>$text)<option value="{{ $value }}" @selected((string)($filters[$name]??'')===(string)$value)>{{ $text }}</option>@endforeach</select>@endif @endforeach
<label class="checkbox"><input type="checkbox" name="sla" value="breached" @checked(($filters['sla']??null)==='breached')> {{ __('تجاوز SLA فقط') }}</label>
<select name="per_page" aria-label="{{ __('لكل صفحة') }}">@foreach(\App\Services\OperatingLeads::PER_PAGE as $size)<option value="{{ $size }}" @selected($perPage===$size)>{{ $size }}</option>@endforeach</select>
<details class="crm-columns-picker"><summary>{{ __('الأعمدة') }}</summary><div class="crm-columns">@foreach(\App\Services\OperatingLeads::COLUMNS as $key=>$label)<label class="checkbox"><input type="checkbox" name="columns[]" value="{{ $key }}" @checked(in_array($key,$columns,true)) @disabled($key==='title')> {{ __($label) }}</label>@endforeach<input type="hidden" name="columns[]" value="title"></div></details>
<button class="button secondary">{{ __('تصفية') }}</button><a class="text-button" href="/admin/operations/crm/leads" data-crm-columns-reset>{{ __('إعادة ضبط') }}</a>
</form>
<details class="card"><summary><b>{{ __('تصدير Excel') }}</b> <small class="muted">{{ __('يُطبَّق على الفلاتر الحالية، حتى 5,000 صف، ويُسجَّل في سجل العمليات.') }}</small></summary>
<form method="post" action="/admin/operations/crm/leads/export" class="inline-form">@csrf @foreach($filters as $key=>$value)@if($value!==null && $value!=='')<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif @endforeach @foreach($columns as $column)<input type="hidden" name="columns[]" value="{{ $column }}">@endforeach
<label class="checkbox"><input type="checkbox" name="mask_phones" value="1" checked> {{ __('إخفاء أرقام الهاتف (آخر 4 أرقام فقط)') }}</label><input name="reason" placeholder="{{ __('سبب التصدير (اختياري)') }}" maxlength="2000"><button class="button">{{ __('تنزيل Excel') }}</button></form></details>
<section class="card table-card crm-table-wrap"><table><thead><tr>@foreach($columns as $column)<th>{{ __(\App\Services\OperatingLeads::COLUMNS[$column]) }}</th>@endforeach</tr></thead><tbody>
@forelse($rows as $row)<tr>@foreach($columns as $column)<td>@switch($column)
@case('title')<a class="item-title" href="/admin/operations/crm/leads/{{ $row['id'] }}">{{ $row['title'] }}</a>@break
@case('lead_phone')<span dir="ltr">{{ $row['lead_phone'] ?? '—' }}</span>@break
@case('status')<span class="badge">{{ $row['status'] }}</span>@break
@case('sla')@if($row['sla'])<span class="sla-badge {{ $row['sla'] }}">{{ __(\App\Services\OperatingCrm::SLA_LABELS[$row['sla']]) }}</span>@else — @endif @break
@case('expected_value')<span dir="ltr">{{ $row['expected_value'] ?? '—' }}</span>@break
@default{{ $row[$column] ?? '—' }}
@endswitch</td>@endforeach</tr>@empty<tr><td colspan="{{ count($columns) }}" class="muted">{{ __('لا توجد بيانات') }}</td></tr>@endforelse
</tbody></table></section>
<div class="pagination">{{ $page->links() }}</div>
@endsection
```

- [ ] **Step 8: CSS لكل مكونات CRM** (مرة واحدة، تستخدمها التاسكات التالية) — أضف في نهاية `public/admin-assets/operating.css`:

```css
/* CRM */
.crm-filters{display:flex;flex-wrap:wrap;gap:.5rem;align-items:center}
.crm-filters input[type=search]{flex:1 1 16rem}
.crm-columns{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:.35rem;padding:.75rem 0}
.crm-columns-picker{flex-basis:100%}
.crm-table-wrap{overflow-x:auto}
.crm-table-wrap table{min-width:760px}
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:.85rem;margin-bottom:1.25rem}
.kpi{background:var(--admin-card);border:1px solid var(--admin-border);border-radius:18px;padding:1rem;display:flex;flex-direction:column;gap:.3rem;color:inherit;text-decoration:none}
.kpi .kpi-icon{width:2.4rem;height:2.4rem;border-radius:12px;display:grid;place-items:center;background:var(--admin-accent-soft);color:var(--admin-accent);font-weight:700}
.kpi strong{font-size:1.6rem;line-height:1.15;font-variant-numeric:tabular-nums}
.kpi span{font-weight:600}
.kpi small{color:var(--admin-muted)}
.kpi.warn .kpi-icon{background:color-mix(in srgb,var(--admin-danger) 14%,transparent);color:var(--admin-danger)}
.sla-badge{display:inline-block;border-radius:999px;padding:.1rem .55rem;font-size:.78rem;font-weight:600;background:var(--admin-surface);color:var(--admin-muted);white-space:nowrap}
.sla-badge.met{background:color-mix(in srgb,#16a34a 14%,transparent);color:#15803d}
.sla-badge.late{background:color-mix(in srgb,#d97706 16%,transparent);color:#b45309}
.sla-badge.breached{background:color-mix(in srgb,var(--admin-danger) 14%,transparent);color:var(--admin-danger)}
.crm-grid{display:grid;grid-template-columns:minmax(0,2fr) minmax(0,1fr);gap:1rem;align-items:start}
.crm-grid>div{display:grid;gap:1rem}
@media (max-width:900px){.crm-grid{grid-template-columns:1fr}}
.stepper{display:flex;flex-wrap:wrap;gap:.35rem;list-style:none;padding:0;margin:.75rem 0}
.stepper li{flex:1 1 6.5rem;text-align:center;padding:.45rem .5rem;border-radius:10px;background:var(--admin-surface);color:var(--admin-muted);font-size:.85rem}
.stepper li.done{background:var(--admin-accent-soft);color:var(--admin-accent)}
.stepper li.current{background:var(--admin-accent);color:var(--admin-accent-foreground,#fff);font-weight:700}
.timeline{list-style:none;padding:0;margin:0;display:grid;gap:.6rem}
.timeline li{border-inline-start:3px solid var(--admin-border);padding:.15rem .75rem}
.timeline li small{display:block;color:var(--admin-muted)}
.bar-list{display:grid;gap:.45rem;list-style:none;padding:0;margin:0}
.bar-list li{display:grid;grid-template-columns:minmax(6rem,11rem) 1fr auto;gap:.6rem;align-items:center;font-size:.9rem}
.bar-list i{display:block;height:.6rem;border-radius:999px;background:var(--admin-accent);min-width:2px}
.cal-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:.35rem}
.cal-grid .cal-head{font-weight:700;text-align:center;font-size:.8rem;color:var(--admin-muted)}
.cal-day{min-height:6.5rem;border:1px solid var(--admin-border);border-radius:12px;padding:.35rem;display:flex;flex-direction:column;gap:.2rem;font-size:.8rem;background:var(--admin-card)}
.cal-day.outside{opacity:.45}
.cal-day.today{border-color:var(--admin-accent);box-shadow:0 0 0 1px var(--admin-accent)}
.cal-day .cal-item{display:block;border-radius:6px;padding:.1rem .3rem;background:var(--admin-accent-soft);color:inherit;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;text-decoration:none}
.cal-day .cal-item.overdue{background:color-mix(in srgb,var(--admin-danger) 14%,transparent)}
.cal-day .cal-item.done{opacity:.6;text-decoration:line-through}
@media (max-width:700px){.cal-grid{grid-template-columns:1fr}.cal-grid .cal-head{display:none}.cal-day{min-height:auto}.cal-day.outside{display:none}}
.pipeline-card[draggable=true]{cursor:grab}
.workflow-board section.drop-target{outline:2px dashed var(--admin-accent);outline-offset:4px}
.crm-tabs{display:flex;flex-wrap:wrap;gap:.35rem;margin-bottom:1rem}
.crm-tabs a{padding:.35rem .8rem;border-radius:999px;background:var(--admin-surface);color:inherit;text-decoration:none;font-size:.9rem}
.crm-tabs a.active{background:var(--admin-accent);color:var(--admin-accent-foreground,#fff)}
.crm-tabs a b{margin-inline-start:.35rem}
dl.crm-facts{display:grid;grid-template-columns:minmax(7rem,auto) 1fr;gap:.35rem .9rem;margin:0}
dl.crm-facts dt{color:var(--admin-muted)}
dl.crm-facts dd{margin:0}
```

- [ ] **Step 9: JS لتذكّر الأعمدة** — أضف في نهاية `public/admin-assets/operating.js`:

```js
(() => {
    const form = document.querySelector('[data-crm-columns-form]');
    if (!form) return;
    const key = 'findo.crm.columns';
    const params = new URLSearchParams(location.search);
    try {
        if (!params.has('columns[]')) {
            const saved = JSON.parse(localStorage.getItem(key) || 'null');
            if (Array.isArray(saved) && saved.length) {
                saved.forEach((column) => params.append('columns[]', column));
                location.replace(location.pathname + '?' + params.toString());
                return;
            }
        }
        form.addEventListener('submit', () => {
            const columns = [...form.querySelectorAll('input[name="columns[]"]:checked')].map((input) => input.value);
            localStorage.setItem(key, JSON.stringify(columns));
        });
        form.querySelector('[data-crm-columns-reset]')?.addEventListener('click', () => localStorage.removeItem(key));
    } catch (error) { /* storage blocked: columns simply fall back to the defaults */ }
})();
```

- [ ] **Step 10: الترجمات** — أضف إلى `lang/en.json`:

```json
"العملاء المحتملون": "Leads",
"إدارة ومتابعة جميع العملاء المحتملين": "Manage and track all your leads",
"عميل": "leads",
"استيراد": "Import",
"عميل جديد": "New lead",
"ابحث بالاسم أو الهاتف أو الشركة...": "Search by name, phone or company...",
"نوع التاريخ": "Date field",
"تاريخ الإنشاء": "Created date",
"أول تواصل": "First contact",
"تاريخ الإغلاق": "Closed date",
"من": "From",
"إلى": "To",
"المرحلة": "Stage",
"جودة العميل": "Lead quality",
"مدى الملاءمة": "Fit status",
"المصدر": "Source",
"قناة الوارد": "Inbound channel",
"الحملة": "Campaign",
"المسؤول": "Owner",
"أضيف بواسطة": "Added by",
"الكل": "All",
"تجاوز SLA فقط": "SLA breached only",
"لكل صفحة": "Per page",
"الأعمدة": "Columns",
"تصفية": "Filter",
"إعادة ضبط": "Reset",
"تصدير Excel": "Export Excel",
"يُطبَّق على الفلاتر الحالية، حتى 5,000 صف، ويُسجَّل في سجل العمليات.": "Uses the current filters, up to 5,000 rows, and is recorded in the audit log.",
"إخفاء أرقام الهاتف (آخر 4 أرقام فقط)": "Mask phone numbers (show last 4 only)",
"سبب التصدير (اختياري)": "Reason for export (optional)",
"تنزيل Excel": "Download Excel",
"اسم العميل": "Lead name",
"الهاتف": "Phone",
"الشركة": "Company",
"الدولة": "Country",
"نوع النشاط": "Business profile",
"الإعلان": "Ad creative",
"القيمة المتوقعة": "Expected value",
"لا توجد بيانات": "No data",
"ضمن المهلة": "Within SLA",
"في الموعد": "On time",
"متأخر": "Late",
"متجاوز": "Breached"
```

> قبل الإضافة افحص المفاتيح الموجودة لتجنب التكرار (مفتاح مكرر يجعل آخر قيمة هي المعتمدة، لكنه يربك المراجعة): `for k in "الكل" "من" "إلى" "تصفية" "المرحلة" "الحملة" "المصدر"; do grep -c "\"$k\":" lang/en.json; done`. أضف فقط ما نتيجته 0.

- [ ] **Step 11:** Run: `$PHP artisan test --compact tests/Feature/Operating/CrmLeadsListTest.php` → PASS.

- [ ] **Step 12: Pint + Commit**

```bash
$PHP vendor/bin/pint --dirty --format agent
git add app/Services/OperatingLeads.php app/Services/OperatingCrm.php app/Http/Controllers/CrmController.php resources/views/admin/operating/crm-leads.blade.php routes/admin.php public/admin-assets/operating.css public/admin-assets/operating.js lang/en.json tests/Feature/Operating/CrmLeadsListTest.php
git commit -m "feat(crm): leads table with filters, column picker and audited Excel export

Co-Authored-By: Claude Opus 5.5 (1M context) <noreply@anthropic.com>"
```

---

### Task 7: تسجيل نشاط سريع على الـLead (بنتيجة ومتابعة قادمة)

**Files:**
- Modify: `app/Services/OperatingSales.php`
- Modify: `app/Http/Controllers/CrmController.php`، `routes/admin.php`، `lang/en.json`
- Test: `tests/Feature/Operating/CrmQuickActivityTest.php`

**Interfaces:**
- Consumes: `OperatingSales::saveAs()`، `touchFirstContact` عبر hook الانتقال (T1).
- Produces:
  - `OperatingSales::transitionAs(User $user, int $id, string $target, string $reason): object`
  - `OperatingSales::logActivity(User $user, object $lead, array $input): object` — `$input`: `kind` (مفتاح من خيارات activities.kind)، `result` (nullable)، `outcome` (نص)، `next_step` (nullable)، `follow_up_at` (nullable `Y-m-d\TH:i`). يُنشئ نشاطًا ويغلقه `done` فورًا، ولو `follow_up_at` موجود يُنشئ نشاط `follow_up` مخطط.
  - مسار: `POST /admin/operations/crm/leads/{id}/activity` → `CrmController::logActivity`.

- [ ] **Step 1: الاختبار الفاشل** — `tests/Feature/Operating/CrmQuickActivityTest.php`:

```php
<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmQuickActivityTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_a_rep_logs_a_call_with_result_and_a_next_follow_up(): void
    {
        $this->travelTo('2026-10-20 11:00:00');
        $rep = $this->operatingUser('sales');
        $employee = $this->employeeFor($rep);
        $lead = $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Nile Foods', 'assignee_id' => $employee, 'sla_due_at' => '2026-10-20T12:00'], 'Nile Foods', $rep->id);

        $this->actingAs($rep)->post("/admin/operations/crm/leads/{$lead}/activity", ['kind' => 'call', 'result' => 'interested', 'outcome' => 'Wants a demo', 'next_step' => 'Send deck', 'follow_up_at' => '2026-10-22T10:00'])->assertSessionHasNoErrors()->assertRedirect();

        $done = DB::table('operating_records')->where('type', 'activities')->where('status', 'done')->sole();
        $doneData = json_decode($done->data, true);
        $this->assertSame(['call', 'interested', $lead, $employee], [$doneData['kind'], $doneData['result'], $doneData['opportunity_id'], $doneData['assignee_id']]);
        $planned = json_decode(DB::table('operating_records')->where('type', 'activities')->where('status', 'planned')->sole()->data, true);
        $this->assertSame(['follow_up', '2026-10-22T10:00'], [$planned['kind'], $planned['due_at']]);
        $this->assertSame('2026-10-20T11:00', $this->recordData($lead)['first_contact_at']);
    }

    public function test_an_outcome_is_required_and_strangers_are_refused(): void
    {
        $rep = $this->operatingUser('sales');
        $this->employeeFor($rep);
        $lead = $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Nile Foods'], 'Nile Foods', $rep->id);

        $this->actingAs($rep)->post("/admin/operations/crm/leads/{$lead}/activity", ['kind' => 'call'])->assertSessionHasErrors('outcome');
        $stranger = $this->operatingUser('sales');
        $this->employeeFor($stranger);
        $this->actingAs($stranger)->post("/admin/operations/crm/leads/{$lead}/activity", ['kind' => 'call', 'outcome' => 'x'])->assertForbidden();
    }

    public function test_an_owner_without_an_employee_file_logs_on_behalf_of_the_lead_owner(): void
    {
        $owner = $this->operatingUser('owner');
        $employee = $this->employeeFor($this->operatingUser('sales'));
        $lead = $this->operatingRecord('opportunities', 'contacted', ['lead_company' => 'Nile Foods', 'assignee_id' => $employee], 'Nile Foods');

        $this->actingAs($owner)->post("/admin/operations/crm/leads/{$lead}/activity", ['kind' => 'note', 'outcome' => 'Budget approved'])->assertSessionHasNoErrors();

        $this->assertSame($employee, json_decode(DB::table('operating_records')->where('type', 'activities')->sole()->data, true)['assignee_id']);
    }
}
```

- [ ] **Step 2:** Run → FAIL.

- [ ] **Step 3: `OperatingSales`** — أضف بعد `saveAs()`:

```php
    public function transitionAs(User $user, int $id, string $target, string $reason): object
    {
        $record = DB::table('operating_records')->find($id);
        $request = Request::create('/', 'POST', ['target' => $target, 'version' => $record->version, 'reason' => $reason]);
        $request->setUserResolver(fn (): User => $user);

        return $this->workflow->transition($id, $request);
    }

    /**
     * A touchpoint logged after the fact: the activity is created and closed in one step, with an optional planned follow-up.
     *
     * @param  array{kind: string, result?: string|null, outcome: string, next_step?: string|null, follow_up_at?: string|null}  $input
     */
    public function logActivity(User $user, object $lead, array $input): object
    {
        $leadData = $this->catalog->data($lead);
        $employee = $this->access->employeeId($user) ?? (int) ($leadData['assignee_id'] ?? 0);
        if (! $employee) {
            $this->workflow->fail('اربط حسابك بملف موظف أو أسند العميل لمسؤول قبل تسجيل النشاط.', 'Link your account to an employee file or assign the lead before logging activity.');
        }
        $kinds = collect($this->catalog->entity('activities')['fields'])->firstWhere('key', 'kind')['options'];
        $label = $this->catalog->label($kinds[$input['kind']]);
        $link = ['opportunity_id' => $lead->id, 'client_id' => $leadData['client_id'] ?? null, 'assignee_id' => $employee];

        return DB::transaction(function () use ($user, $lead, $input, $label, $link): object {
            $activity = $this->saveAs($user, 'activities', ['title' => $label.' — '.$lead->title, 'reason' => 'Logged from lead profile', 'data' => $link + ['kind' => $input['kind'], 'due_at' => now()->format('Y-m-d\TH:i'), 'result' => $input['result'] ?? null, 'outcome' => $input['outcome'], 'next_step' => $input['next_step'] ?? null, 'priority' => 'normal']]);
            $this->transitionAs($user, $activity->id, 'done', 'Logged from lead profile');
            if (! empty($input['follow_up_at'])) {
                $this->saveAs($user, 'activities', ['title' => __('متابعة').' — '.$lead->title, 'reason' => 'Follow-up planned from lead profile', 'data' => $link + ['kind' => 'follow_up', 'due_at' => $input['follow_up_at'], 'agenda' => $input['next_step'] ?? null, 'priority' => 'normal']]);
            }

            return $activity;
        });
    }
```

> `priority` قيمته `normal` (من خيارات `low,normal,high`). لو حقل `priority` ليس select إلزامي فلا ضرر من إرساله.

- [ ] **Step 4: الكنترولر**

```php
    public function logActivity(int $id, Request $request): Response
    {
        $this->authorizeCrm($request);
        $lead = $this->access->record($request->user(), $id);
        abort_unless($lead->type === 'opportunities', 404);
        abort_unless($this->access->can($request->user(), 'activities', 'write'), 403);
        $kinds = array_keys(collect($this->catalog->entity('activities')['fields'])->firstWhere('key', 'kind')['options']);
        $results = array_keys(collect($this->catalog->entity('activities')['fields'])->firstWhere('key', 'result')['options']);
        $input = $request->validate(['kind' => 'required|in:'.implode(',', $kinds), 'result' => 'nullable|in:'.implode(',', $results), 'outcome' => 'required|string|max:5000', 'next_step' => 'nullable|string|max:500', 'follow_up_at' => 'nullable|date_format:Y-m-d\TH:i|after:now']);
        app(OperatingSales::class)->logActivity($request->user(), $lead, $input);

        return back()->with('success', __('تم تسجيل النشاط.'));
    }
```

(أضف `use App\Services\OperatingSales;`.)

> الـrep الغريب يحصل على 403 من `access->record()` لأن نطاق `sales` لا يرى فرص غيره.

- [ ] **Step 5: المسار**

```php
        Route::post('/operations/crm/leads/{id}/activity', [C::class, 'logActivity'])->whereNumber('id');
```

- [ ] **Step 6: الترجمات**

```json
"تم تسجيل النشاط.": "Activity logged.",
"متابعة": "Follow-up"
```

- [ ] **Step 7:** Run → PASS. ثم `tests/Feature/Operating/FollowUpsTest.php` → PASS.

- [ ] **Step 8: Pint + Commit**

```bash
$PHP vendor/bin/pint --dirty --format agent
git add app/Services/OperatingSales.php app/Http/Controllers/CrmController.php routes/admin.php lang/en.json tests/Feature/Operating/CrmQuickActivityTest.php
git commit -m "feat(crm): one-step activity logging with contact result and next follow-up

Co-Authored-By: Claude Opus 5.5 (1M context) <noreply@anthropic.com>"
```

---

### Task 8: ملف العميل المحتمل (Lead profile) + التسليم لزميل

**Files:**
- Modify: `app/Services/OperatingLeads.php`
- Modify: `app/Http/Controllers/CrmController.php`، `routes/admin.php`
- Create: `resources/views/admin/operating/crm-lead.blade.php`
- Modify: `resources/views/admin/operating/form.blade.php` (رابط الملف)
- Modify: `lang/en.json`
- Test: `tests/Feature/Operating/CrmLeadProfileTest.php`

**Interfaces:**
- Consumes: T1 (`FUNNEL`، `stageReach`)، T3 (`slaState`، `openAlert`، `responseHours`)، T6 (`SLA_LABELS`)، T7 (`logActivity` route).
- Produces:
  - `OperatingLeads::profile(User $user, object $lead): array` يرجع: `lead, data, fields (list<array>), titles (client, campaign, assignee), sla, slaOpen, responseHours, journey (list<array{status: string, label: string, at: string, by: ?string, reason: ?string}>), timeline (list<array{at: string, kind: string, title: string, body: ?string, by: ?string}>), upcoming (Collection), quotes (Collection), attachments (Collection), stats (array{last_contact: ?string, days_in_stage: int, next: ?string}), canWrite, isManager, nextStages (array<string, string>)`
  - `OperatingLeads::handover(User $user, object $lead, int $employeeId, string $note): object`
  - مسارات: `GET /admin/operations/crm/leads/{id}`، `POST /admin/operations/crm/leads/{id}/handover`.

- [ ] **Step 1: الاختبار الفاشل** — `tests/Feature/Operating/CrmLeadProfileTest.php`:

```php
<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmLeadProfileTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_the_owner_rep_sees_journey_activity_result_and_sla(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $rep = $this->operatingUser('sales');
        $employee = $this->employeeFor($rep);
        $lead = $this->operatingRecord('opportunities', 'meeting', ['lead_company' => 'Nile Foods', 'lead_phone' => '01001234567', 'lead_quality' => 'hot', 'assignee_id' => $employee, 'sla_due_at' => '2026-10-20T10:00', 'first_contact_at' => '2026-10-20T09:00'], 'Nile Foods', $rep->id);
        $this->transitionEvent($lead, 'contacted', '2026-10-20 09:00:00');
        $this->transitionEvent($lead, 'meeting', '2026-10-20 09:30:00');
        $this->operatingRecord('activities', 'done', ['kind' => 'call', 'result' => 'interested', 'outcome' => 'Wants a demo', 'assignee_id' => $employee, 'due_at' => '2026-10-20T09:00', 'completed_at' => '2026-10-20T09:00', 'opportunity_id' => $lead], 'Call — Nile Foods');

        $this->actingAs($rep)->get("/admin/operations/crm/leads/{$lead}")->assertOk()
            ->assertSee('Nile Foods')->assertSee(__('اجتماع محدد'))->assertSee('Wants a demo')->assertSee(__('مهتم'))
            ->assertSee(__('في الموعد'))->assertSee(__('ساخن'))->assertSee('01001234567');
    }

    public function test_other_reps_cannot_open_it_and_non_leads_404(): void
    {
        $lead = $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Nile Foods'], 'Nile Foods');
        $client = $this->operatingRecord('clients', 'active', ['company' => 'X', 'decision_maker' => 'Y', 'contact' => 'Z'], 'X');
        $rep = $this->operatingUser('sales');
        $this->employeeFor($rep);

        $this->actingAs($rep)->get("/admin/operations/crm/leads/{$lead}")->assertForbidden();
        $this->actingAs($this->operatingUser('owner'))->get("/admin/operations/crm/leads/{$client}")->assertNotFound();
    }

    public function test_a_manager_hands_a_lead_over_with_a_note_and_a_rep_cannot(): void
    {
        $first = $this->employeeFor($this->operatingUser('sales'));
        $second = $this->employeeFor($this->operatingUser('sales'));
        $lead = $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Nile Foods', 'lead_source' => 'facebook', 'assignee_id' => $first], 'Nile Foods');

        $this->actingAs($this->operatingUser('sales'))->post("/admin/operations/crm/leads/{$lead}/handover", ['assignee_id' => $second, 'note' => 'x'])->assertForbidden();
        $this->actingAs($this->operatingUser('sales_manager'))->post("/admin/operations/crm/leads/{$lead}/handover", ['assignee_id' => $second, 'note' => 'Speaks German'])->assertSessionHasNoErrors();

        $this->assertSame($second, $this->recordData($lead)['assignee_id']);
        $this->assertTrue(DB::table('operating_events')->where('record_id', $lead)->where('reason', 'like', '%Speaks German%')->exists());
    }
}
```

- [ ] **Step 2:** Run → FAIL.

- [ ] **Step 3: `OperatingLeads::profile()` و`handover()`** — أضف في نهاية الكلاس:

```php
    /**
     * @return array<string, mixed>
     */
    public function profile(User $user, object $lead): array
    {
        $data = $this->access->visibleData($user, $lead);
        $titles = $this->sales->titles(array_values(array_filter([$data['client_id'] ?? null, $data['campaign_id'] ?? null, $data['assignee_id'] ?? null])));
        $linkedIds = DB::table('operating_links')->where('field', 'opportunity_id')->where('target_id', $lead->id)->pluck('record_id');
        $activities = $this->access->can($user, 'activities') ? $this->access->query($user, 'activities')->whereIn('id', $linkedIds)->where('status', '!=', 'archived')->get() : collect();
        $quotes = $this->access->can($user, 'quotes') ? $this->access->query($user, 'quotes')->whereIn('id', $linkedIds)->where('status', '!=', 'archived')->latest('updated_at')->get() : collect();
        $activityFields = collect($this->catalog->entity('activities')['fields'])->keyBy('key');
        $optionLabel = fn (string $field, ?string $value): ?string => $value && isset($activityFields[$field]['options'][$value]) ? $this->catalog->label($activityFields[$field]['options'][$value]) : null;
        $assigneeTitles = $this->sales->titles($activities->map(fn (object $activity): mixed => $this->catalog->data($activity)['assignee_id'] ?? null)->filter()->unique()->values()->all());

        $events = DB::table('operating_events')->leftJoin('users', 'users.id', '=', 'operating_events.user_id')->where('record_id', $lead->id)->whereIn('action', ['create', 'transition'])->select('operating_events.action', 'operating_events.after', 'operating_events.reason', 'operating_events.created_at', 'users.name')->orderBy('operating_events.id')->get();
        $journey = [];
        foreach ($events as $event) {
            $status = json_decode($event->after ?? 'null', true)['status'] ?? null;
            if ($status) {
                $journey[] = ['status' => $status, 'label' => $this->catalog->status($status), 'at' => substr((string) $event->created_at, 0, 16), 'by' => $event->name, 'reason' => $event->reason];
            }
        }

        $timeline = [];
        foreach ($activities->where('status', 'done') as $activity) {
            $activityData = $this->catalog->data($activity);
            $timeline[] = ['at' => str_replace('T', ' ', $activityData['completed_at'] ?? $activityData['due_at'] ?? ''), 'kind' => $optionLabel('kind', $activityData['kind'] ?? null) ?? __('نشاط'), 'title' => trim(($optionLabel('result', $activityData['result'] ?? null) ?? '').' '), 'body' => $activityData['outcome'] ?? null, 'by' => $assigneeTitles[$activityData['assignee_id'] ?? 0] ?? null];
        }
        foreach (DB::table('operating_comments')->join('users', 'users.id', '=', 'operating_comments.user_id')->where('record_id', $lead->id)->select('operating_comments.body', 'operating_comments.created_at', 'users.name')->get() as $comment) {
            $timeline[] = ['at' => substr((string) $comment->created_at, 0, 16), 'kind' => __('ملاحظة داخلية'), 'title' => '', 'body' => $comment->body, 'by' => $comment->name];
        }
        foreach ($journey as $step) {
            $timeline[] = ['at' => $step['at'], 'kind' => __('تغيير المرحلة'), 'title' => $step['label'], 'body' => $step['reason'], 'by' => $step['by']];
        }
        usort($timeline, fn (array $a, array $b): int => strcmp($b['at'], $a['at']));

        $contacts = $activities->where('status', 'done')->filter(fn (object $activity): bool => in_array($this->catalog->data($activity)['kind'] ?? null, OperatingCrm::CONTACT_KINDS, true));
        $lastContact = $contacts->map(fn (object $activity): string => $this->catalog->data($activity)['completed_at'] ?? '')->filter()->max() ?: ($data['first_contact_at'] ?? null);
        $upcoming = $activities->where('status', 'planned')->sortBy(fn (object $activity): string => $this->catalog->data($activity)['due_at'] ?? '')->values();
        $nextActivity = $upcoming->first();
        $stageSince = collect($journey)->last()['at'] ?? substr((string) $lead->created_at, 0, 16);
        $entity = $this->catalog->entity('opportunities');

        return [
            'lead' => $lead, 'data' => $data, 'fields' => $this->access->fields($user, 'opportunities', $lead),
            'titles' => ['client' => $titles[$data['client_id'] ?? 0] ?? null, 'campaign' => $titles[$data['campaign_id'] ?? 0] ?? null, 'assignee' => $titles[$data['assignee_id'] ?? 0] ?? null],
            'sla' => $this->crm->slaState($lead), 'slaOpen' => $this->crm->openAlert($lead), 'responseHours' => $this->crm->responseHours($lead),
            'journey' => $journey, 'timeline' => $timeline, 'upcoming' => $upcoming, 'quotes' => $quotes,
            'attachments' => DB::table('operating_attachments')->where('record_id', $lead->id)->latest('id')->get(),
            'stats' => ['last_contact' => $lastContact ? str_replace('T', ' ', $lastContact) : null, 'days_in_stage' => (int) floor(abs(now()->diffInDays($stageSince))), 'next' => $nextActivity ? str_replace('T', ' ', $this->catalog->data($nextActivity)['due_at'] ?? '').' · '.$nextActivity->title : ($data['next_action'] ?? null)],
            'canWrite' => $this->access->can($user, 'opportunities', 'write'), 'isManager' => $this->crm->isManager($user),
            'nextStages' => collect($entity['statuses'])->reject(fn (string $status): bool => in_array($status, [$lead->status, 'archived'], true))->mapWithKeys(fn (string $status): array => [$status => $this->catalog->status($status)])->all(),
        ];
    }

    /**
     * Reassignment by a manager goes through the normal save so the change, its note and the new owner's notification are all recorded.
     */
    public function handover(User $user, object $lead, int $employeeId, string $note): object
    {
        abort_unless($this->crm->isManager($user), 403);

        return $this->sales->saveAs($user, 'opportunities', ['title' => $lead->title, 'version' => $lead->version, 'reason' => __('تسليم العميل').': '.$note, 'data' => array_merge($this->catalog->data($lead), ['assignee_id' => $employeeId])], $lead->id);
    }
```

> `now()->diffInDays($stageSince)` في Carbon 3 يرجع float **موقّعًا** (سالب لأن التاريخ في الماضي)، لذلك `abs` ثم `floor`.
> `saveAs` يمرر كل `data` كما هي. الحقول خارج `fields` (المحسوبة) تُتجاهل في الـvalidation وتبقى محفوظة لأن `save()` يبدأ من بيانات السجل الحالي.

- [ ] **Step 4: الكنترولر**

```php
    public function lead(int $id, Request $request): Response
    {
        $this->authorizeCrm($request);
        $lead = $this->access->record($request->user(), $id);
        abort_unless($lead->type === 'opportunities', 404);
        $activityFields = collect($this->catalog->entity('activities')['fields'])->keyBy('key');

        return $this->view('crm-lead', $this->leads->profile($request->user(), $lead) + [
            'kinds' => array_map(fn (array $label): string => $this->catalog->label($label), (array) $activityFields['kind']['options']),
            'results' => array_map(fn (array $label): string => $this->catalog->label($label), (array) $activityFields['result']['options']),
            'employees' => $this->crm->isManager($request->user()) ? $this->access->query($request->user(), 'employees')->where('status', 'active')->orderBy('title')->pluck('title', 'id') : collect(),
        ]);
    }

    public function handover(int $id, Request $request): Response
    {
        $this->authorizeCrm($request, manager: true);
        $lead = $this->access->record($request->user(), $id, 'write');
        abort_unless($lead->type === 'opportunities', 404);
        $input = $request->validate(['assignee_id' => 'required|integer', 'note' => 'required|string|max:2000']);
        abort_unless(DB::table('operating_records')->where('type', 'employees')->where('status', 'active')->where('id', $input['assignee_id'])->exists(), 422);
        $this->leads->handover($request->user(), $lead, (int) $input['assignee_id'], $input['note']);

        return back()->with('success', __('تم تسليم العميل للزميل.'));
    }
```

- [ ] **Step 5: المسارات** (بعد `crm/leads/export` وقبل `crm/leads/{id}/sla-dismiss`):

```php
        Route::get('/operations/crm/leads/{id}', [C::class, 'lead'])->whereNumber('id');
        Route::post('/operations/crm/leads/{id}/handover', [C::class, 'handover'])->whereNumber('id');
```

- [ ] **Step 6: الـview** — `resources/views/admin/operating/crm-lead.blade.php`:

```blade
@extends('admin.layout')
@section('title',$lead->title)
@section('content')
@php
$fieldMap=collect($fields)->keyBy('key');
$show=function(string $key) use ($fieldMap,$data,$catalog,$titles){$value=$data[$key]??null;if($value===null||$value===''){return null;}$field=$fieldMap[$key]??null;if(!$field){return $value;}if(($field['type']??'')==='select'){return isset($field['options'][$value])?$catalog->label($field['options'][$value]):$value;}if(($field['type']??'')==='relation'){return ['client_id'=>$titles['client'],'campaign_id'=>$titles['campaign'],'assignee_id'=>$titles['assignee']][$key]??('#'.$value);}return is_string($value)?str_replace('T',' ',$value):$value;};
$funnel=\App\Services\OperatingCrm::FUNNEL; $current=array_search($lead->status,$funnel,true);
@endphp
<div class="page-heading"><div><span class="eyebrow">{{ __('ملف العميل المحتمل') }} · #{{ $lead->id }}</span><h1>{{ $lead->title }}</h1><p class="muted">{{ $data['lead_company'] ?? $titles['client'] ?? '' }} @if(!empty($data['lead_phone']))· <a dir="ltr" href="tel:{{ preg_replace('/[^\d+]/','',$data['lead_phone']) }}">{{ $data['lead_phone'] }}</a> · <a href="https://wa.me/{{ preg_replace('/\D/','',$data['lead_phone']) }}" target="_blank" rel="noopener">WhatsApp ↗</a>@endif</p></div>
<div class="row-actions"><a class="button secondary" href="/admin/operations/crm/leads">{{ __('العملاء المحتملون') }}</a>@if($canWrite)<a class="button secondary" href="/admin/operations/record/{{ $lead->id }}">{{ __('تعديل البيانات') }}</a>@endif</div></div>

<ol class="stepper" aria-label="{{ __('مراحل العميل') }}">@foreach($funnel as $index=>$stage)<li class="{{ $current!==false && $index<$current ? 'done' : ($stage===$lead->status ? 'current' : '') }}">{{ $catalog->status($stage) }}</li>@endforeach @if($current===false)<li class="current">{{ $catalog->status($lead->status) }}</li>@endif</ol>

<div class="kpi-grid">
<div class="kpi {{ $sla==='breached'?'warn':'' }}"><span class="kpi-icon">⏱</span><span>SLA</span><strong>@if($sla)<span class="sla-badge {{ $sla }}">{{ __(\App\Services\OperatingCrm::SLA_LABELS[$sla]) }}</span>@else — @endif</strong><small>@if($responseHours!==null){{ __('زمن الاستجابة') }}: {{ $responseHours }} {{ __('ساعة') }}@elseif(!empty($data['sla_due_at'])){{ __('المهلة حتى') }} {{ str_replace('T',' ',$data['sla_due_at']) }}@endif</small></div>
<div class="kpi"><span class="kpi-icon">☎</span><span>{{ __('آخر تواصل') }}</span><strong>{{ $stats['last_contact'] ?? '—' }}</strong></div>
<div class="kpi"><span class="kpi-icon">➜</span><span>{{ __('الإجراء التالي') }}</span><strong style="font-size:1rem">{{ $stats['next'] ?? '—' }}</strong></div>
<div class="kpi"><span class="kpi-icon">⧗</span><span>{{ __('أيام في المرحلة') }}</span><strong>{{ $stats['days_in_stage'] }}</strong></div>
<div class="kpi"><span class="kpi-icon">$</span><span>{{ __('القيمة المتوقعة') }}</span><strong dir="ltr">{{ isset($data['expected_value']) && $data['expected_value']!=='' ? number_format((float)$data['expected_value'],2).' '.(($data['currency'] ?? '') ?: 'USD') : '—' }}</strong><small>{{ isset($data['probability']) ? $data['probability'].'%' : '' }}</small></div>
</div>

@if($slaOpen && $isManager)<form method="post" action="/admin/operations/crm/leads/{{ $lead->id }}/sla-dismiss" class="notice error inline-form">@csrf<span>{{ __('هذا العميل تجاوز مهلة التواصل.') }}</span><input name="reason" required placeholder="{{ __('سبب إخفاء التنبيه') }}"><button class="button small secondary">{{ __('إخفاء التنبيه') }}</button></form>@elseif($slaOpen)<div class="notice error">{{ __('هذا العميل تجاوز مهلة التواصل. سجّل أول تواصل الآن.') }}</div>@endif

<div class="crm-grid"><div>
@if($access->can(auth()->user(),'activities','write'))<section class="card"><h2>{{ __('تسجيل نشاط') }}</h2><form method="post" action="/admin/operations/crm/leads/{{ $lead->id }}/activity" class="field-grid">@csrf
<label>{{ __('نوع النشاط') }}<select name="kind" required>@foreach($kinds as $key=>$label)<option value="{{ $key }}" @selected($key==='call')>{{ $label }}</option>@endforeach</select></label>
<label>{{ __('نتيجة التواصل') }}<select name="result"><option value="">—</option>@foreach($results as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
<label class="long">{{ __('ماذا حدث؟') }}<textarea name="outcome" required maxlength="5000"></textarea></label>
<label>{{ __('الخطوة التالية') }}<input name="next_step" maxlength="500"></label>
<label>{{ __('موعد المتابعة القادمة') }}<input type="datetime-local" name="follow_up_at"></label>
<button class="button">{{ __('حفظ النشاط') }}</button></form></section>@endif

<section class="card"><h2>{{ __('الخط الزمني') }}</h2>@if($timeline)<ol class="timeline">@foreach($timeline as $item)<li><b>{{ $item['kind'] }}@if($item['title']) · {{ $item['title'] }}@endif</b>@if($item['body'])<p>{{ $item['body'] }}</p>@endif<small>{{ $item['at'] }}@if($item['by']) · {{ $item['by'] }}@endif</small></li>@endforeach</ol>@else<p class="muted">{{ __('لا يوجد نشاط مسجل بعد. ابدأ بتسجيل أول تواصل.') }}</p>@endif</section>

<section class="card"><h2>{{ __('ملاحظة داخلية') }}</h2><form method="post" action="/admin/operations/record/{{ $lead->id }}/comments">@csrf<textarea name="body" required maxlength="10000" placeholder="{{ __('اكتب ملاحظة سريعة...') }}"></textarea><button class="button secondary">{{ __('إضافة ملاحظة') }}</button></form></section>
</div><div>
@if($canWrite)<section class="card"><h2>{{ __('نقل المرحلة') }}</h2><form method="post" action="/admin/operations/record/{{ $lead->id }}/transition">@csrf<input type="hidden" name="version" value="{{ $lead->version }}"><label>{{ __('المرحلة الجديدة') }}<select name="target" required>@foreach($nextStages as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label><label>{{ __('السبب') }}<textarea name="reason" required maxlength="2000"></textarea></label><button class="button">{{ __('تأكيد') }}</button></form><p class="help">{{ __('الخسارة والتأجيل يحتاجان سبب الخسارة في بيانات العميل، والتأهيل يحتاج الاحتياج والحل، والعرض والفوز يحتاجان تحويله لعميل.') }}</p>
@if(empty($data['client_id']) && $access->can(auth()->user(),'clients','write'))<form method="post" action="/admin/operations/opportunities/{{ $lead->id }}/convert-client" class="inline-form">@csrf<input name="reason" required placeholder="{{ __('سبب التحويل') }}"><button class="button secondary">{{ __('تحويل إلى عميل') }}</button></form>@endif</section>@endif

<section class="card"><h2>{{ __('بيانات العميل') }}</h2><dl class="crm-facts">@foreach(['lead_contact','lead_email','country','business_profile','lead_quality','fit_status','lead_source','lead_channel','campaign_id','ad_creative','service_introduced','lead_date','client_id','need','solution','deal_type','expected_close','notes','media_buyer_notes'] as $key)@php $value=$show($key); @endphp @if($value!==null)<dt>{{ $catalog->label($fieldMap[$key]['label'] ?? ['ar'=>$key,'en'=>$key]) }}</dt><dd>{{ $value }}</dd>@endif @endforeach</dl></section>

<section class="card"><h2>{{ __('الملكية والتعيين') }}</h2><p>{{ __('المسؤول') }}: <b>{{ $titles['assignee'] ?? __('غير مسند') }}</b></p>@if($isManager && $employees->isNotEmpty())<form method="post" action="/admin/operations/crm/leads/{{ $lead->id }}/handover">@csrf<label>{{ __('تسليم إلى') }}<select name="assignee_id" required>@foreach($employees as $id=>$name)<option value="{{ $id }}" @selected((int)($data['assignee_id']??0)===(int)$id)>{{ $name }}</option>@endforeach</select></label><label>{{ __('ملاحظات للزميل') }}<textarea name="note" required maxlength="2000"></textarea></label><button class="button secondary">{{ __('تسليم') }}</button></form>@endif</section>

<section class="card"><h2>{{ __('المهام والمتابعات القادمة') }}</h2>@forelse($upcoming as $activity)@php $activityData=$catalog->data($activity); @endphp<a class="operation-row" href="/admin/operations/record/{{ $activity->id }}"><b>{{ $activity->title }}</b><span class="{{ ($activityData['due_at']??'9999')<now()->format('Y-m-d\TH:i')?'danger':'' }}">{{ str_replace('T',' ',$activityData['due_at'] ?? '') }}</span></a>@empty<p class="muted">{{ __('لا توجد متابعات قادمة.') }}</p>@endforelse</section>

<section class="card"><h2>{{ __('رحلة المراحل') }}</h2><ol class="timeline">@foreach($journey as $step)<li><b>{{ $step['label'] }}</b><small>{{ $step['at'] }}@if($step['by']) · {{ $step['by'] }}@endif</small></li>@endforeach</ol></section>

@if($quotes->isNotEmpty())<section class="card"><h2>{{ __('العروض') }}</h2>@foreach($quotes as $quote)<a class="operation-row" href="/admin/operations/record/{{ $quote->id }}"><b>{{ $quote->title }}</b><span>{{ $catalog->status($quote->status) }}</span></a>@endforeach</section>@endif

<section class="card"><h2>{{ __('المرفقات') }}</h2>@forelse($attachments as $file)<a class="operation-row" href="/admin/operations/attachments/{{ $file->id }}">{{ $file->name }}</a>@empty<p class="muted">{{ __('لا توجد مرفقات.') }}</p>@endforelse @if($canWrite)<form method="post" action="/admin/operations/record/{{ $lead->id }}/attachments" enctype="multipart/form-data" class="upload-form">@csrf<input type="file" name="file" required><button class="button small secondary">{{ __('رفع') }}</button></form>@endif</section>
</div></div>
@endsection
```

> أعمدة `operating_attachments`: `name, path, mime` (تم التحقق). و`OperatingController::attach()` يستقبل الحقل `file`، و`comment()` يستقبل `body`.

- [ ] **Step 7: رابط من الفورم العام** — في `resources/views/admin/operating/form.blade.php` السطر 5، استبدل:

```blade
</div><a class="button secondary" href="/admin/operations/{{ $type }}">{{ __('العودة للقسم') }}</a></div>
```

بـ:

```blade
</div><div class="row-actions">@if($record && $type==='opportunities')<a class="button" href="/admin/operations/crm/leads/{{ $record->id }}">{{ __('ملف العميل المحتمل') }}</a>@endif<a class="button secondary" href="/admin/operations/{{ $type }}">{{ __('العودة للقسم') }}</a></div></div>
```

- [ ] **Step 8: الترجمات**

```json
"ملف العميل المحتمل": "Lead profile",
"تعديل البيانات": "Edit details",
"مراحل العميل": "Lead stages",
"زمن الاستجابة": "Response time",
"ساعة": "h",
"المهلة حتى": "Due by",
"آخر تواصل": "Last contact",
"الإجراء التالي": "Next action",
"أيام في المرحلة": "Days in stage",
"هذا العميل تجاوز مهلة التواصل.": "This lead breached the response SLA.",
"سبب إخفاء التنبيه": "Reason for dismissing",
"إخفاء التنبيه": "Dismiss alert",
"هذا العميل تجاوز مهلة التواصل. سجّل أول تواصل الآن.": "This lead breached the response SLA. Log the first contact now.",
"تسجيل نشاط": "Log activity",
"نوع النشاط": "Activity type",
"نتيجة التواصل": "Contact result",
"ماذا حدث؟": "What happened?",
"الخطوة التالية": "Next step",
"موعد المتابعة القادمة": "Next follow-up",
"حفظ النشاط": "Save activity",
"الخط الزمني": "Timeline",
"لا يوجد نشاط مسجل بعد. ابدأ بتسجيل أول تواصل.": "No activity yet. Start by logging the first contact.",
"ملاحظة داخلية": "Internal note",
"اكتب ملاحظة سريعة...": "Write a quick note...",
"إضافة ملاحظة": "Add note",
"نقل المرحلة": "Move stage",
"المرحلة الجديدة": "New stage",
"السبب": "Reason",
"تأكيد": "Confirm",
"الخسارة والتأجيل يحتاجان سبب الخسارة في بيانات العميل، والتأهيل يحتاج الاحتياج والحل، والعرض والفوز يحتاجان تحويله لعميل.": "Lost and deferred need a loss reason on the lead, qualifying needs the need and solution, and proposal or win need the lead converted to a client.",
"سبب التحويل": "Conversion reason",
"تحويل إلى عميل": "Convert to client",
"بيانات العميل": "Lead details",
"الملكية والتعيين": "Ownership",
"غير مسند": "Unassigned",
"تسليم إلى": "Hand over to",
"ملاحظات للزميل": "Notes for your colleague",
"تسليم": "Hand over",
"تسليم العميل": "Lead handover",
"تم تسليم العميل للزميل.": "Lead handed over.",
"المهام والمتابعات القادمة": "Upcoming tasks and follow-ups",
"لا توجد متابعات قادمة.": "No upcoming follow-ups.",
"رحلة المراحل": "Stage journey",
"العروض": "Quotes",
"المرفقات": "Attachments",
"لا توجد مرفقات.": "No attachments.",
"رفع": "Upload",
"نشاط": "Activity",
"تغيير المرحلة": "Stage change"
```

(افحص التكرار قبل الإضافة كما في T6.)

- [ ] **Step 9:** Run: `$PHP artisan test --compact tests/Feature/Operating/CrmLeadProfileTest.php` → PASS.

- [ ] **Step 10: Pint + Commit**

```bash
$PHP vendor/bin/pint --dirty --format agent
git add app/Services/OperatingLeads.php app/Http/Controllers/CrmController.php routes/admin.php resources/views/admin/operating/crm-lead.blade.php resources/views/admin/operating/form.blade.php lang/en.json tests/Feature/Operating/CrmLeadProfileTest.php
git commit -m "feat(crm): lead profile with stage journey, timeline, SLA signals and manager handover

Co-Authored-By: Claude Opus 5.5 (1M context) <noreply@anthropic.com>"
```

---

### Task 9: خط البيع: عمود الاجتماع، البطاقات تفتح الملف، والسحب والإفلات

**Files:**
- Modify: `app/Services/OperatingSales.php` (`pipeline()` يضيف `version`)
- Modify: `resources/views/admin/operating/opportunities-pipeline.blade.php`
- Modify: `public/admin-assets/operating.js`
- Modify: `lang/en.json`
- Test: `tests/Feature/Operating/LeadsPipelineTest.php` (اختبار جديد)

**Interfaces:**
- Consumes: `OPEN_STAGES` (T1)، مسار الانتقال الحالي بـJSON (`OperatingController::transition` يرجع JSON لو `Accept: application/json`).
- Produces: بطاقات `pipeline()` فيها `version`. الـHTML: `<section data-stage="…">` و`<a class="pipeline-card" draggable="true" data-id data-version>` وحاوية `[data-pipeline-dnd]`.

- [ ] **Step 1: الاختبار الفاشل** — أضف إلى `LeadsPipelineTest`:

```php
    public function test_the_board_has_a_meeting_column_and_draggable_cards_that_open_the_profile(): void
    {
        $manager = $this->operatingUser('sales_manager');
        $id = $this->operatingRecord('opportunities', 'meeting', ['lead_company' => 'Nile Foods'], 'Nile Foods');

        $this->actingAs($manager)->get('/admin/operations/opportunities/pipeline')->assertOk()
            ->assertSee('data-stage="meeting"', false)
            ->assertSee('href="/admin/operations/crm/leads/'.$id.'"', false)
            ->assertSee('data-version="1"', false)
            ->assertSee('draggable="true"', false);
    }

    public function test_cards_are_not_draggable_for_read_only_roles(): void
    {
        $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Nile Foods'], 'Nile Foods');

        $this->actingAs($this->operatingUser('delivery'))->get('/admin/operations/opportunities/pipeline')->assertOk()->assertDontSee('draggable="true"', false);
    }
```

- [ ] **Step 2:** Run → FAIL.

- [ ] **Step 3:** في `OperatingSales::pipeline()` داخل مصفوفة البطاقة أضف `'version' => $record->version,` بعد `'id' => $record->id,`.

- [ ] **Step 4: الـview** — في `opportunities-pipeline.blade.php` استبدل السطور 12–16 (من `<div class="workflow-board">` حتى `<p class="help">…</p>`) بـ:

```blade
@php $canMove=$access->can(auth()->user(),'opportunities','write'); @endphp
<div class="workflow-board" @if($canMove) data-pipeline-dnd data-confirm="{{ __('سبب نقل المرحلة:') }}" @endif>@foreach($columns as $stage=>$column)<section data-stage="{{ $stage }}"><h2>{{ $catalog->status($stage) }} <small class="muted">{{ $column['count'] }}</small></h2>@foreach($column['value'] as $currency=>$amount)<p class="muted" dir="ltr">{{ number_format($amount,2) }} {{ $currency }}</p>@endforeach
@forelse($column['cards'] as $card)<a class="card operation-row pipeline-card" href="/admin/operations/crm/leads/{{ $card['id'] }}" data-id="{{ $card['id'] }}" data-version="{{ $card['version'] }}" @if($canMove) draggable="true" @endif><b>{{ $card['title'] }}</b><small>{{ $card['company'] ?? '—' }} · {{ $card['assignee'] ?? __('غير مسند') }}</small>@if($card['value'])<small dir="ltr">{{ number_format($card['value'],2) }} {{ $card['currency'] }} · {{ $card['probability'] ?? 0 }}%</small>@endif @if($card['follow_up'])<small class="{{ $card['overdue']?'danger':'' }}">{{ __('المتابعة') }}: {{ $card['follow_up'] }}@if($card['overdue']) · {{ __('متأخرة') }}@endif</small>@endif</a>@empty<p class="muted">{{ __('لا شيء هنا.') }}</p>@endforelse
</section>@endforeach
@if($canMove)<section data-stage="won" class="drop-only"><h2>{{ $catalog->status('won') }}</h2><p class="muted">{{ __('اسحب هنا لتسجيل الفوز') }}</p></section><section data-stage="lost" class="drop-only"><h2>{{ $catalog->status('lost') }}</h2><p class="muted">{{ __('اسحب هنا لتسجيل الخسارة') }}</p></section>@endif</div>
<p class="help">{{ __('اسحب البطاقة إلى مرحلة أخرى واكتب السبب. الخادم يتحقق من شروط كل مرحلة، وأي رفض يظهر برسالته.') }}</p>
```

- [ ] **Step 5: JS** — أضف في نهاية `operating.js`:

```js
(() => {
    const board = document.querySelector('[data-pipeline-dnd]');
    if (!board) return;
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    let dragged = null;
    board.addEventListener('dragstart', (event) => {
        dragged = event.target.closest('.pipeline-card');
        if (dragged) event.dataTransfer.setData('text/plain', dragged.dataset.id);
    });
    board.querySelectorAll('section[data-stage]').forEach((column) => {
        column.addEventListener('dragover', (event) => { if (dragged) { event.preventDefault(); column.classList.add('drop-target'); } });
        column.addEventListener('dragleave', () => column.classList.remove('drop-target'));
        column.addEventListener('drop', async (event) => {
            event.preventDefault();
            column.classList.remove('drop-target');
            const card = dragged;
            dragged = null;
            if (!card || card.closest('section') === column) return;
            const reason = prompt(board.dataset.confirm);
            if (!reason) return;
            const response = await fetch('/admin/operations/record/' + card.dataset.id + '/transition', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token},
                body: JSON.stringify({target: column.dataset.stage, version: Number(card.dataset.version), reason}),
            });
            if (response.ok) { location.reload(); return; }
            const body = await response.json().catch(() => ({}));
            alert(body.errors?.workflow?.[0] || body.errors?.target?.[0] || body.message || response.status);
        });
    });
})();
```

> تحقق يدويًا في T20 (السحب لا يُختبر بـPHPUnit).

- [ ] **Step 6: الترجمات**

```json
"سبب نقل المرحلة:": "Reason for the stage change:",
"اسحب هنا لتسجيل الفوز": "Drop here to mark won",
"اسحب هنا لتسجيل الخسارة": "Drop here to mark lost",
"اسحب البطاقة إلى مرحلة أخرى واكتب السبب. الخادم يتحقق من شروط كل مرحلة، وأي رفض يظهر برسالته.": "Drag a card to another stage and give a reason. The server checks each stage's requirements and shows any refusal."
```

- [ ] **Step 7:** Run: `$PHP artisan test --compact tests/Feature/Operating/LeadsPipelineTest.php` → PASS.

- [ ] **Step 8: Pint + Commit**

```bash
$PHP vendor/bin/pint --dirty --format agent
git add app/Services/OperatingSales.php resources/views/admin/operating/opportunities-pipeline.blade.php public/admin-assets/operating.js lang/en.json tests/Feature/Operating/LeadsPipelineTest.php
git commit -m "feat(crm): drag-and-drop pipeline with meeting column, won/lost drop zones and profile links

Co-Authored-By: Claude Opus 5.5 (1M context) <noreply@anthropic.com>"
```

---

### Task 10: داشبورد CRM (الصورة التي أرسلتها ميرنا)

**Files:**
- Create: `app/Services/OperatingCrmReports.php`
- Modify: `app/Http/Controllers/CrmController.php`، `routes/admin.php`
- Create: `resources/views/admin/operating/crm-dashboard.blade.php`
- Create: `resources/views/admin/operating/crm-nav.blade.php` (شريط تبويبات CRM العلوي، تستخدمه كل صفحات CRM من هنا فصاعدًا)
- Modify: `lang/en.json`
- Test: `tests/Feature/Operating/CrmDashboardTest.php`

**Interfaces:**
- Consumes: `OperatingCrm::{stageReach, reached, openAlert, isManager}`، `OperatingLeads::rows()`، `OperatingSales::{currency, OPEN_STAGES}`.
- Produces (`OperatingCrmReports`):
  - `leads(User $user, string $scope): Collection<int, object>` — فرص غير مؤرشفة ضمن رؤية المستخدم. `scope`: `team` (للمدير فقط) أو `mine` (المالك أو المسؤول).
  - `activityQuery(User $user, string $scope): \Illuminate\Database\Query\Builder`
  - `dashboard(User $user, string $from, string $to, string $scope): array{kpis: array{total_leads: int, activities: int, won: int, revenue: array<string, float>, potential: array<string, float>, sla_alerts: int, contact_to_meeting: ?float, meeting_to_close: ?float}, recent: list<array>, breached: list<array>, today: Collection, mine: Collection}`
  - `calendar(User $user, string $month, string $scope = 'team', ?int $assignee = null): array{month: string, label: string, prev: string, next: string, weeks: list<list<array{date: string, day: int, inMonth: bool, today: bool, items: list<array{id: int, title: string, time: string, kind: ?string, status: string, overdue: bool, lead: ?int}>}>>}` (الأسبوع يبدأ السبت).
  - helpers عامة: `between(object $lead, string $from, string $to): bool`، `closedBetween(array $data, string $from, string $to): bool`، `percent(int $part, int $whole): ?float`.
- Produces (`CrmController`): `period(Request $request): array{0: string, 1: string, 2: string}` (from، to، scope؛ الافتراضي الشهر الحالي، والـscope `mine` إجباري لغير المدير).
- تعريفات المؤشرات (ثابتة، تُكتب في الـview كتلميح):
  1. **إجمالي العملاء**: فرص أُنشئت في الفترة.
  2. **إجمالي الأنشطة**: أنشطة `done` بـ`completed_at` في الفترة.
  3. **الصفقات المكتسبة**: فرص `won` بـ`closed_at` في الفترة.
  4. **إجمالي الإيرادات**: مجموع `expected_value` للبند 3 لكل عملة.
  5. **الإيراد المحتمل**: مجموع `expected_value` لكل الفرص المفتوحة (`OPEN_STAGES`) الآن، لكل عملة.
  6. **تنبيهات SLA**: فرص تنبيهها مفتوح الآن (`openAlert`).
  7. **تحويل التواصل→الاجتماع**: من فرص الفترة: (وصلت `meeting` أو بعدها) ÷ (وصلت `contacted` أو بعدها).
  8. **تحويل الاجتماع→الإغلاق**: من فرص الفترة: (حالتها `won`) ÷ (وصلت `meeting` أو بعدها).
- مسار: `GET /admin/operations/crm` → `CrmController::dashboard`.

- [ ] **Step 1: الاختبار الفاشل** — `tests/Feature/Operating/CrmDashboardTest.php`:

```php
<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingCrmReports;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmDashboardTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    /**
     * @param  array<string, mixed>  $data
     */
    private function lead(string $status, array $data, string $createdAt, ?int $owner = null): int
    {
        $id = $this->operatingRecord('opportunities', $status, $data + ['lead_company' => 'Co'], 'Lead '.$status.' '.$createdAt, $owner);
        DB::table('operating_records')->where('id', $id)->update(['created_at' => $createdAt]);

        return $id;
    }

    public function test_the_eight_indicators_follow_their_definitions(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $manager = $this->operatingUser('sales_manager');
        $employee = $this->employeeFor($this->operatingUser('sales'));
        $won = $this->lead('won', ['expected_value' => 1000, 'currency' => 'USD', 'closed_at' => '2026-10-10T10:00'], '2026-10-02 10:00:00');
        $this->transitionEvent($won, 'meeting');
        $lostAfterMeeting = $this->lead('lost', [], '2026-10-03 10:00:00');
        $this->transitionEvent($lostAfterMeeting, 'meeting');
        $this->lead('contacted', ['expected_value' => 500, 'currency' => 'EGP'], '2026-10-04 10:00:00');
        $this->lead('new', ['expected_value' => 200, 'currency' => 'USD', 'sla_due_at' => '2026-10-05T10:00'], '2026-10-05 09:00:00');
        $this->lead('won', ['expected_value' => 9000, 'currency' => 'USD', 'closed_at' => '2026-09-20T10:00'], '2026-09-01 10:00:00');
        $this->operatingRecord('activities', 'done', ['kind' => 'call', 'assignee_id' => $employee, 'due_at' => '2026-10-06T10:00', 'completed_at' => '2026-10-06T10:00', 'opportunity_id' => $won], 'Call');
        $this->operatingRecord('activities', 'done', ['kind' => 'call', 'assignee_id' => $employee, 'due_at' => '2026-09-06T10:00', 'completed_at' => '2026-09-06T10:00', 'opportunity_id' => $won], 'Old call');

        $kpis = app(OperatingCrmReports::class)->dashboard($manager, '2026-10-01', '2026-10-31', 'team')['kpis'];

        $this->assertSame(4, $kpis['total_leads']);
        $this->assertSame(1, $kpis['activities']);
        $this->assertSame(1, $kpis['won']);
        $this->assertEquals(['USD' => 1000.0], $kpis['revenue']);
        $this->assertEquals(['EGP' => 500.0, 'USD' => 200.0], $kpis['potential']);
        $this->assertSame(1, $kpis['sla_alerts']);
        $this->assertSame(66.7, $kpis['contact_to_meeting']);
        $this->assertSame(50.0, $kpis['meeting_to_close']);
    }

    public function test_a_rep_only_counts_their_own_leads_and_managers_can_switch_to_mine(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $repUser = $this->operatingUser('sales');
        $rep = $this->employeeFor($repUser);
        $this->lead('new', ['assignee_id' => $rep], '2026-10-02 10:00:00');
        $this->lead('new', [], '2026-10-03 10:00:00');
        $manager = $this->operatingUser('sales_manager');

        $this->assertSame(1, app(OperatingCrmReports::class)->dashboard($repUser, '2026-10-01', '2026-10-31', 'team')['kpis']['total_leads']);
        $this->assertSame(2, app(OperatingCrmReports::class)->dashboard($manager, '2026-10-01', '2026-10-31', 'team')['kpis']['total_leads']);
        $this->assertSame(0, app(OperatingCrmReports::class)->dashboard($manager, '2026-10-01', '2026-10-31', 'mine')['kpis']['total_leads']);
    }

    public function test_the_page_renders_for_sales_and_is_refused_to_other_roles(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $rep = $this->operatingUser('sales');
        $this->employeeFor($rep);

        $this->actingAs($rep)->get('/admin/operations/crm')->assertOk()->assertSee(__('الإيراد المحتمل'))->assertSee(__('تحويل التواصل للاجتماع'))->assertSee('2026-10-01');
        $this->actingAs($rep)->get('/admin/operations/crm?from=2026-10-10&to=2026-10-01')->assertSessionHasErrors('to');
        $this->actingAs($this->operatingUser('designer'))->get('/admin/operations/crm')->assertForbidden();
    }
}
```

- [ ] **Step 2:** Run → FAIL.

- [ ] **Step 3: `app/Services/OperatingCrmReports.php`** (`make:class Services/OperatingCrmReports`):

```php
<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

class OperatingCrmReports
{
    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access, private OperatingCrm $crm, private OperatingSales $sales, private OperatingLeads $leadRows) {}

    /**
     * @return Collection<int, object>
     */
    public function leads(User $user, string $scope): Collection
    {
        return $this->narrow($this->access->query($user, 'opportunities')->where('status', '!=', 'archived'), $user, $scope)->orderBy('id')->get();
    }

    public function activityQuery(User $user, string $scope): Builder
    {
        return $this->narrow($this->access->query($user, 'activities')->where('status', '!=', 'archived'), $user, $scope);
    }

    /**
     * Managers see the team unless they ask for their own; everyone else is limited to records they own or are assigned.
     */
    private function narrow(Builder $query, User $user, string $scope): Builder
    {
        if ($scope === 'team' && $this->crm->isManager($user)) {
            return $query;
        }
        $employee = $this->access->employeeId($user);

        return $query->where(fn (Builder $mine) => $mine->where('owner_id', $user->id)->when($employee, fn (Builder $assigned) => $assigned->orWhere('data->assignee_id', $employee)));
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboard(User $user, string $from, string $to, string $scope): array
    {
        $leads = $this->leads($user, $scope);
        $created = $leads->filter(fn (object $lead): bool => $this->between($lead, $from, $to));
        $reach = $this->crm->stageReach($created->pluck('id')->all());
        $contacted = $meetings = $closedFromMeeting = 0;
        foreach ($created as $lead) {
            $stages = $reach[$lead->id] ?? [$lead->status];
            $contacted += (int) $this->crm->reached($stages, 'contacted');
            if ($this->crm->reached($stages, 'meeting')) {
                $meetings++;
                $closedFromMeeting += (int) ($lead->status === 'won');
            }
        }
        $won = $leads->filter(fn (object $lead): bool => $lead->status === 'won' && $this->closedBetween($this->catalog->data($lead), $from, $to));
        $revenue = $potential = [];
        foreach ($won as $lead) {
            $this->addValue($revenue, $this->catalog->data($lead));
        }
        foreach ($leads->whereIn('status', OperatingSales::OPEN_STAGES) as $lead) {
            $this->addValue($potential, $this->catalog->data($lead));
        }
        $alerts = $leads->filter(fn (object $lead): bool => $this->crm->openAlert($lead));
        $canActivities = $this->access->can($user, 'activities');
        $employee = (int) ($this->access->employeeId($user) ?? 0);

        return [
            'kpis' => [
                'total_leads' => $created->count(),
                'activities' => $canActivities ? $this->activityQuery($user, $scope)->where('status', 'done')->where('data->completed_at', '>=', $from)->where('data->completed_at', '<=', $to.'T23:59')->count() : 0,
                'won' => $won->count(), 'revenue' => $revenue, 'potential' => $potential, 'sla_alerts' => $alerts->count(),
                'contact_to_meeting' => $this->percent($meetings, $contacted), 'meeting_to_close' => $this->percent($closedFromMeeting, $meetings),
            ],
            'recent' => $this->leadRows->rows($leads->sortByDesc('created_at')->take(6)),
            'breached' => $this->leadRows->rows($alerts->sortBy(fn (object $lead): string => $this->catalog->data($lead)['sla_due_at'] ?? '')->take(6)),
            'today' => $canActivities ? $this->access->query($user, 'activities')->where('status', 'planned')->where('data->assignee_id', $employee)->where('data->due_at', '<=', today()->format('Y-m-d').'T23:59')->get()->sortBy(fn (object $row): string => $this->catalog->data($row)['due_at'] ?? '')->values() : collect(),
            'mine' => $canActivities ? $this->access->query($user, 'activities')->where('status', 'done')->where('data->assignee_id', $employee)->latest('updated_at')->limit(6)->get() : collect(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function calendar(User $user, string $month, string $scope = 'team', ?int $assignee = null): array
    {
        $start = CarbonImmutable::createFromFormat('Y-m-d', $month.'-01')->startOfDay();
        $gridStart = $start->subDays(($start->dayOfWeek + 1) % 7);
        $end = $start->endOfMonth()->startOfDay();
        $gridEnd = $end->addDays((12 - $end->dayOfWeek) % 7);
        $items = [];
        if ($this->access->can($user, 'activities')) {
            $now = now()->format('Y-m-d\TH:i');
            $rows = $this->activityQuery($user, $scope)->whereIn('status', ['planned', 'done'])->where('data->due_at', '>=', $gridStart->format('Y-m-d'))->where('data->due_at', '<=', $gridEnd->format('Y-m-d').'T23:59')->when($assignee, fn (Builder $query) => $query->where('data->assignee_id', $assignee))->get();
            foreach ($rows as $row) {
                $data = $this->catalog->data($row);
                $items[substr($data['due_at'], 0, 10)][] = ['id' => $row->id, 'title' => $row->title, 'time' => substr($data['due_at'], 11, 5), 'kind' => $data['kind'] ?? null, 'status' => $row->status, 'overdue' => $row->status === 'planned' && $data['due_at'] < $now, 'lead' => isset($data['opportunity_id']) ? (int) $data['opportunity_id'] : null];
            }
        }
        $weeks = [];
        $today = today()->format('Y-m-d');
        for ($day = $gridStart, $index = 0; $day <= $gridEnd; $day = $day->addDay(), $index++) {
            $date = $day->format('Y-m-d');
            $list = $items[$date] ?? [];
            usort($list, fn (array $a, array $b): int => strcmp($a['time'], $b['time']));
            $weeks[intdiv($index, 7)][] = ['date' => $date, 'day' => (int) $day->format('j'), 'inMonth' => $day->month === $start->month, 'today' => $date === $today, 'items' => $list];
        }

        return ['month' => $month, 'label' => $start->locale(app()->getLocale())->translatedFormat('F Y'), 'prev' => $start->subMonth()->format('Y-m'), 'next' => $start->addMonth()->format('Y-m'), 'weeks' => $weeks];
    }

    public function between(object $lead, string $from, string $to): bool
    {
        $at = substr((string) $lead->created_at, 0, 10);

        return $at >= $from && $at <= $to;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function closedBetween(array $data, string $from, string $to): bool
    {
        $at = substr((string) ($data['closed_at'] ?? ''), 0, 10);

        return $at !== '' && $at >= $from && $at <= $to;
    }

    public function percent(int $part, int $whole): ?float
    {
        return $whole ? round($part / $whole * 100, 1) : null;
    }

    /**
     * @param  array<string, float>  $sums
     * @param  array<string, mixed>  $data
     */
    private function addValue(array &$sums, array $data): void
    {
        $currency = $this->sales->currency($data);
        $sums[$currency] = ($sums[$currency] ?? 0) + (float) ($data['expected_value'] ?? 0);
    }
}
```

> `$gridEnd`: يوم الجمعة = 5. `(12 - dayOfWeek) % 7` يعطي 0 للجمعة، و6 للسبت، و1 للخميس. `$gridStart`: السبت = 6 → `(6+1)%7 = 0`، الأحد → 1.

- [ ] **Step 4: الكنترولر** — أضف `private OperatingCrmReports $reports` للـconstructor (و`use App\Services\OperatingCrmReports;`)، ثم:

```php
    public function dashboard(Request $request): Response
    {
        $this->authorizeCrm($request);
        [$from, $to, $scope] = $this->period($request);

        return $this->view('crm-dashboard', $this->reports->dashboard($request->user(), $from, $to, $scope) + ['from' => $from, 'to' => $to, 'scope' => $scope, 'calendar' => $this->reports->calendar($request->user(), today()->format('Y-m'), $scope)]);
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function period(Request $request): array
    {
        $input = $request->validate(['from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from', 'scope' => 'nullable|in:team,mine']);

        return [$input['from'] ?? today()->startOfMonth()->format('Y-m-d'), $input['to'] ?? today()->endOfMonth()->format('Y-m-d'), $this->crm->isManager($request->user()) ? ($input['scope'] ?? 'team') : 'mine'];
    }
```

- [ ] **Step 5: المسار** — قبل `Route::get('/operations/crm/settings', …)`:

```php
        Route::get('/operations/crm', [C::class, 'dashboard']);
```

- [ ] **Step 6: partial التبويبات** — `resources/views/admin/operating/crm-nav.blade.php`:

```blade
@php $manager=$crm->isManager(auth()->user()); $links=['/admin/operations/crm'=>'لوحة CRM','/admin/operations/crm/leads'=>'العملاء المحتملون','/admin/operations/opportunities/pipeline'=>'خط البيع','/admin/operations/crm/funnel'=>'قمع المبيعات','/admin/operations/crm/sla'=>'المهام وSLA','/admin/operations/crm/calendar'=>'التقويم']; if($manager){$links['/admin/operations/crm/team']='لوحة الفريق';} @endphp
<nav class="crm-tabs" aria-label="CRM">@foreach($links as $url=>$label)<a href="{{ $url }}" class="{{ '/'.request()->path()===$url?'active':'' }}">{{ __($label) }}</a>@endforeach</nav>
```

> مسارات `funnel` و`sla` و`calendar` و`team` تُبنى في T11–T14. حتى ذلك الحين الروابط ترجع 404، وهذا مقبول داخل الفرع (لا تدمج قبل T14).

- [ ] **Step 7: الـview** — `resources/views/admin/operating/crm-dashboard.blade.php`:

```blade
@extends('admin.layout')
@section('title',__('لوحة CRM'))
@section('content')
@php $money=fn(array $sums)=>$sums?collect($sums)->map(fn($amount,$currency)=>number_format($amount).' '.$currency)->implode(' · '):'0'; $pct=fn($value)=>$value===null?'—':$value.'%'; $leadsUrl=fn(array $query=[])=>'/admin/operations/crm/leads?'.http_build_query($query); @endphp
<div class="page-heading"><div><span class="eyebrow">CRM</span><h1>{{ __('لوحة CRM') }}</h1><p class="muted">{{ __('مرحبًا') }} {{ auth()->user()->name }}، {{ __('هذا ملخص أنشطتك') }}</p></div>
<form class="inline-form" method="get"><input type="date" name="from" value="{{ $from }}" aria-label="{{ __('من') }}"><span>→</span><input type="date" name="to" value="{{ $to }}" aria-label="{{ __('إلى') }}">@if($crm->isManager(auth()->user()))<select name="scope" aria-label="{{ __('النطاق') }}"><option value="team" @selected($scope==='team')>{{ __('الفريق') }}</option><option value="mine" @selected($scope==='mine')>{{ __('عملائي') }}</option></select>@endif<button class="button secondary">{{ __('تطبيق') }}</button></form></div>
@include('admin.operating.crm-nav')
<div class="kpi-grid">
<a class="kpi" href="{{ $leadsUrl(['from'=>$from,'to'=>$to]) }}"><span class="kpi-icon">👥</span><strong>{{ number_format($kpis['total_leads']) }}</strong><span>{{ __('إجمالي العملاء') }}</span><small>{{ __('أُنشئوا في الفترة') }}</small></a>
<div class="kpi"><span class="kpi-icon">⚡</span><strong>{{ number_format($kpis['activities']) }}</strong><span>{{ __('إجمالي الأنشطة') }}</span><small>{{ __('أنشطة مكتملة في الفترة') }}</small></div>
<a class="kpi" href="{{ $leadsUrl(['stage'=>'won','date_field'=>'closed','from'=>$from,'to'=>$to]) }}"><span class="kpi-icon">✓</span><strong>{{ number_format($kpis['won']) }}</strong><span>{{ __('الصفقات المكتسبة') }}</span><small>{{ __('حسب تاريخ الإغلاق') }}</small></a>
<div class="kpi"><span class="kpi-icon">$</span><strong dir="ltr">{{ $money($kpis['revenue']) }}</strong><span>{{ __('إجمالي الإيرادات') }}</span><small>{{ __('صفقات مقفولة فقط حسب تاريخ الإغلاق') }}</small></div>
<div class="kpi"><span class="kpi-icon">↗</span><strong dir="ltr">{{ $money($kpis['potential']) }}</strong><span>{{ __('الإيراد المحتمل') }}</span><small>{{ __('صفقات مفتوحة لم تُقفل بعد') }}</small></div>
<a class="kpi {{ $kpis['sla_alerts']?'warn':'' }}" href="{{ $leadsUrl(['sla'=>'breached']) }}"><span class="kpi-icon">⚠</span><strong>{{ number_format($kpis['sla_alerts']) }}</strong><span>{{ __('تنبيهات SLA') }}</span><small>{{ __('عملاء تجاوزوا مهلة التواصل') }}</small></a>
<div class="kpi"><span class="kpi-icon">☎</span><strong>{{ $pct($kpis['contact_to_meeting']) }}</strong><span>{{ __('تحويل التواصل للاجتماع') }}</span><small>{{ __('من تم التواصل معهم ووصلوا لاجتماع') }}</small></div>
<div class="kpi"><span class="kpi-icon">🤝</span><strong>{{ $pct($kpis['meeting_to_close']) }}</strong><span>{{ __('تحويل الاجتماع للإغلاق') }}</span><small>{{ __('من وصلوا لاجتماع وتم كسبهم') }}</small></div>
</div>
<div class="crm-grid"><div>
<section class="card"><div class="section-heading"><h2>{{ __('أحدث العملاء') }}</h2><a href="/admin/operations/crm/leads">{{ __('عرض الكل') }} ↗</a></div>@forelse($recent as $row)<a class="operation-row" href="/admin/operations/crm/leads/{{ $row['id'] }}"><b>{{ $row['title'] }}</b><span><span class="badge">{{ $row['status'] }}</span> {{ $row['assignee'] ?? __('غير مسند') }} · {{ $row['created_at'] }}</span></a>@empty<p class="muted">{{ __('لا توجد بيانات') }}</p>@endforelse</section>
<section class="card"><div class="section-heading"><h2>{{ __('عملاء تجاوزوا SLA') }}</h2><a href="{{ $leadsUrl(['sla'=>'breached']) }}">{{ __('عرض الكل') }} ↗</a></div>@forelse($breached as $row)<a class="operation-row" href="/admin/operations/crm/leads/{{ $row['id'] }}"><b>{{ $row['title'] }}</b><span><span class="sla-badge breached">{{ __('متجاوز') }}</span> {{ $row['assignee'] ?? __('غير مسند') }}</span></a>@empty<p class="muted">{{ __('لا يوجد عملاء تجاوزوا المهلة.') }}</p>@endforelse</section>
</div><div>
<section class="card"><h2>{{ __('مهام اليوم') }}</h2>@forelse($today as $task)@php $taskData=$catalog->data($task); @endphp<a class="operation-row" href="{{ !empty($taskData['opportunity_id']) ? '/admin/operations/crm/leads/'.$taskData['opportunity_id'] : '/admin/operations/record/'.$task->id }}"><b>{{ $task->title }}</b><span class="{{ ($taskData['due_at']??'')<now()->format('Y-m-d\TH:i')?'danger':'' }}">{{ str_replace('T',' ',$taskData['due_at'] ?? '') }}</span></a>@empty<p class="muted">{{ __('لا توجد مهام لليوم. كل شيء تحت السيطرة ✨') }}</p>@endforelse</section>
<section class="card"><div class="section-heading"><h2>{{ __('التذكيرات') }} · {{ $calendar['label'] }}</h2><a href="/admin/operations/crm/calendar">{{ __('التقويم') }} ↗</a></div><div class="cal-grid">@foreach(['سبت','أحد','اثنين','ثلاثاء','أربعاء','خميس','جمعة'] as $dayName)<span class="cal-head">{{ __($dayName) }}</span>@endforeach @foreach($calendar['weeks'] as $week)@foreach($week as $day)<div class="cal-day {{ $day['inMonth']?'':'outside' }} {{ $day['today']?'today':'' }}" style="min-height:2.6rem"><b>{{ $day['day'] }}</b>@if($day['items'])<small class="count-badge">{{ count($day['items']) }}</small>@endif</div>@endforeach @endforeach</div></section>
<section class="card"><h2>{{ __('أنشطتي') }}</h2>@forelse($mine as $activity)@php $activityData=$catalog->data($activity); @endphp<a class="operation-row" href="/admin/operations/record/{{ $activity->id }}"><b>{{ $activity->title }}</b><span>{{ str_replace('T',' ',$activityData['completed_at'] ?? '') }}</span></a>@empty<p class="muted">{{ __('لا توجد أنشطة بعد') }}</p>@endforelse</section>
</div></div>
@endsection
```

- [ ] **Step 8: الترجمات**

```json
"لوحة CRM": "CRM dashboard",
"مرحبًا": "Welcome",
"هذا ملخص أنشطتك": "here is your activity summary",
"النطاق": "Scope",
"الفريق": "Team",
"عملائي": "My leads",
"تطبيق": "Apply",
"إجمالي العملاء": "Total leads",
"أُنشئوا في الفترة": "Created in the period",
"إجمالي الأنشطة": "Total activities",
"أنشطة مكتملة في الفترة": "Completed in the period",
"الصفقات المكتسبة": "Won deals",
"حسب تاريخ الإغلاق": "By close date",
"إجمالي الإيرادات": "Total revenue",
"صفقات مقفولة فقط حسب تاريخ الإغلاق": "Closed deals only, by close date",
"الإيراد المحتمل": "Potential revenue",
"صفقات مفتوحة لم تُقفل بعد": "Open deals not closed yet",
"تنبيهات SLA": "SLA alerts",
"عملاء تجاوزوا مهلة التواصل": "Leads past the response deadline",
"تحويل التواصل للاجتماع": "Contact to meeting",
"من تم التواصل معهم ووصلوا لاجتماع": "Contacted leads that reached a meeting",
"تحويل الاجتماع للإغلاق": "Meeting to close",
"من وصلوا لاجتماع وتم كسبهم": "Leads with a meeting that were won",
"أحدث العملاء": "Latest leads",
"عرض الكل": "View all",
"عملاء تجاوزوا SLA": "Leads past SLA",
"لا يوجد عملاء تجاوزوا المهلة.": "No leads past the deadline.",
"مهام اليوم": "Today's tasks",
"لا توجد مهام لليوم. كل شيء تحت السيطرة ✨": "No tasks for today. You're all caught up ✨",
"التذكيرات": "Reminders",
"التقويم": "Calendar",
"سبت": "Sat",
"أحد": "Sun",
"اثنين": "Mon",
"ثلاثاء": "Tue",
"أربعاء": "Wed",
"خميس": "Thu",
"جمعة": "Fri",
"أنشطتي": "My activities",
"لا توجد أنشطة بعد": "No activities yet",
"قمع المبيعات": "Sales funnel",
"المهام وSLA": "Tasks & SLA",
"لوحة الفريق": "Team dashboard"
```

- [ ] **Step 9:** Run: `$PHP artisan test --compact tests/Feature/Operating/CrmDashboardTest.php` → PASS.

- [ ] **Step 10: Pint + Commit**

```bash
$PHP vendor/bin/pint --dirty --format agent
git add app/Services/OperatingCrmReports.php app/Http/Controllers/CrmController.php routes/admin.php resources/views/admin/operating/crm-dashboard.blade.php resources/views/admin/operating/crm-nav.blade.php lang/en.json tests/Feature/Operating/CrmDashboardTest.php
git commit -m "feat(crm): CRM dashboard with the eight sales indicators, today's tasks and reminders

Co-Authored-By: Claude Opus 5.5 (1M context) <noreply@anthropic.com>"
```

---

### Task 11: قمع المبيعات (Sales funnel)

**Files:**
- Modify: `app/Services/OperatingCrmReports.php`
- Modify: `app/Http/Controllers/CrmController.php`، `routes/admin.php`
- Create: `resources/views/admin/operating/crm-funnel.blade.php`
- Modify: `lang/en.json`
- Test: `tests/Feature/Operating/CrmFunnelTest.php`

**Interfaces:**
- Consumes: T10 (`leads`، `between`، `closedBetween`، `percent`، `addValue`).
- Produces: `OperatingCrmReports::funnel(User $user, string $from, string $to, string $scope): array{total: int, won: int, won_value: array<string, float>, potential: array{count: int, value: array<string, float>}, avg_response: ?float, stages: list<array{stage: string, label: string, count: int, rate: ?float, share: ?float}>, trend: array<string, int>, months: array<string, array{count: int, value: array<string, float>}>, campaigns: list<array{name: string, leads: int, contacted: int, meetings: int, won: int, revenue: array<string, float>, conversion: ?float}>}`
  - `trend`: مفاتيح `Y-m-d` لو الفترة ≤ 62 يومًا، وإلا `Y-m`، وكل المفاتيح موجودة حتى لو صفر.
  - `months`: آخر 12 شهرًا حتى شهر `to` (مفتاح `Y-m`)، من فرص `won` حسب `closed_at`.
- مسار: `GET /admin/operations/crm/funnel`.

- [ ] **Step 1: الاختبار الفاشل** — `tests/Feature/Operating/CrmFunnelTest.php`:

```php
<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingCrmReports;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmFunnelTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    private function lead(string $status, array $data, string $createdAt): int
    {
        $id = $this->operatingRecord('opportunities', $status, $data + ['lead_company' => 'Co'], 'Lead '.$createdAt);
        DB::table('operating_records')->where('id', $id)->update(['created_at' => $createdAt]);

        return $id;
    }

    public function test_stage_counts_rates_response_time_and_campaigns(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $campaign = $this->operatingRecord('campaigns', 'running', ['objective' => 'leads', 'budget_cap' => 1000], 'Ramadan');
        $this->lead('new', ['campaign_id' => $campaign], '2026-10-01 10:00:00');
        $this->lead('contacted', ['campaign_id' => $campaign, 'first_contact_at' => '2026-10-02T12:00'], '2026-10-02 10:00:00');
        $won = $this->lead('won', ['campaign_id' => $campaign, 'expected_value' => 3000, 'currency' => 'USD', 'closed_at' => '2026-10-12T10:00', 'first_contact_at' => '2026-10-03T14:00'], '2026-10-03 10:00:00');
        $this->transitionEvent($won, 'meeting');
        $this->lead('meeting', [], '2026-10-04 10:00:00');

        $funnel = app(OperatingCrmReports::class)->funnel($this->operatingUser('sales_manager'), '2026-10-01', '2026-10-31', 'team');

        $stages = collect($funnel['stages'])->keyBy('stage');
        $this->assertSame([4, 3, 2, 1], [$stages['new']['count'], $stages['contacted']['count'], $stages['meeting']['count'], $stages['won']['count']]);
        $this->assertSame(75.0, $stages['contacted']['rate']);
        $this->assertSame(66.7, $stages['meeting']['rate']);
        $this->assertSame(3.0, $funnel['avg_response']);
        $this->assertSame(1, $funnel['won']);
        $this->assertEquals(['USD' => 3000.0], $funnel['won_value']);
        $this->assertSame(3, $funnel['potential']['count']);
        $this->assertCount(31, $funnel['trend']);
        $this->assertSame(1, $funnel['trend']['2026-10-03']);
        $this->assertCount(12, $funnel['months']);
        $this->assertSame(1, $funnel['months']['2026-10']['count']);
        $ramadan = collect($funnel['campaigns'])->firstWhere('name', 'Ramadan');
        $this->assertSame([3, 2, 1, 1, 33.3], [$ramadan['leads'], $ramadan['contacted'], $ramadan['meetings'], $ramadan['won'], $ramadan['conversion']]);
    }

    public function test_the_page_renders(): void
    {
        $this->actingAs($this->operatingUser('sales_manager'))->get('/admin/operations/crm/funnel')->assertOk()->assertSee(__('معدلات التحويل بين المراحل'));
    }
}
```

> متوسط الاستجابة: (2 ساعة + 4 ساعات) ÷ 2 = 3.0. Contacted rate = 3/4 = 75.0، Meeting = 2/3 = 66.7. الحملة: 3 فرص، 2 وصلوا contacted+ (contacted، won)، 1 اجتماع+ (won)، 1 مكسوب، تحويل 1/3 = 33.3.

- [ ] **Step 2:** Run → FAIL.

- [ ] **Step 3: `funnel()`** — أضف إلى `OperatingCrmReports`:

```php
    /**
     * @return array<string, mixed>
     */
    public function funnel(User $user, string $from, string $to, string $scope): array
    {
        $leads = $this->leads($user, $scope);
        $created = $leads->filter(fn (object $lead): bool => $this->between($lead, $from, $to));
        $reach = $this->crm->stageReach($created->pluck('id')->all());
        $reached = fn (object $lead, string $stage): bool => $this->crm->reached($reach[$lead->id] ?? [$lead->status], $stage);

        $stages = [];
        $previous = null;
        foreach (OperatingCrm::FUNNEL as $stage) {
            $count = $created->filter(fn (object $lead): bool => $reached($lead, $stage))->count();
            $stages[] = ['stage' => $stage, 'label' => $this->catalog->status($stage), 'count' => $count, 'rate' => $previous === null ? null : $this->percent($count, $previous), 'share' => $this->percent($count, $created->count())];
            $previous = $count;
        }

        $hours = $created->map(fn (object $lead): ?float => $this->crm->responseHours($lead))->filter(fn (?float $value): bool => $value !== null);
        $won = $leads->filter(fn (object $lead): bool => $lead->status === 'won' && $this->closedBetween($this->catalog->data($lead), $from, $to));
        $wonValue = $openValue = [];
        foreach ($won as $lead) {
            $this->addValue($wonValue, $this->catalog->data($lead));
        }
        $open = $leads->whereIn('status', OperatingSales::OPEN_STAGES);
        foreach ($open as $lead) {
            $this->addValue($openValue, $this->catalog->data($lead));
        }

        $start = CarbonImmutable::parse($from);
        $daily = $start->diffInDays(CarbonImmutable::parse($to)) <= 62;
        $trend = [];
        for ($day = $start; $day->format('Y-m-d') <= $to; $day = $daily ? $day->addDay() : $day->addMonth()->startOfMonth()) {
            $trend[$day->format($daily ? 'Y-m-d' : 'Y-m')] = 0;
        }
        foreach ($created as $lead) {
            $trend[substr((string) $lead->created_at, 0, $daily ? 10 : 7)]++;
        }

        $months = [];
        $lastMonth = CarbonImmutable::parse($to)->startOfMonth();
        for ($i = 11; $i >= 0; $i--) {
            $months[$lastMonth->subMonths($i)->format('Y-m')] = ['count' => 0, 'value' => []];
        }
        foreach ($leads->where('status', 'won') as $lead) {
            $data = $this->catalog->data($lead);
            $key = substr((string) ($data['closed_at'] ?? ''), 0, 7);
            if (isset($months[$key])) {
                $months[$key]['count']++;
                $this->addValue($months[$key]['value'], $data);
            }
        }

        $campaignTitles = $this->sales->titles($created->map(fn (object $lead): mixed => $this->catalog->data($lead)['campaign_id'] ?? null)->filter()->unique()->values()->all());
        $campaigns = [];
        foreach ($created as $lead) {
            $data = $this->catalog->data($lead);
            $name = $campaignTitles[$data['campaign_id'] ?? 0] ?? __('بدون حملة');
            $campaigns[$name] ??= ['name' => $name, 'leads' => 0, 'contacted' => 0, 'meetings' => 0, 'won' => 0, 'revenue' => [], 'conversion' => null];
            $campaigns[$name]['leads']++;
            $campaigns[$name]['contacted'] += (int) $reached($lead, 'contacted');
            $campaigns[$name]['meetings'] += (int) $reached($lead, 'meeting');
            if ($lead->status === 'won') {
                $campaigns[$name]['won']++;
                $this->addValue($campaigns[$name]['revenue'], $data);
            }
        }
        foreach ($campaigns as &$campaign) {
            $campaign['conversion'] = $this->percent($campaign['won'], $campaign['leads']);
        }
        unset($campaign);

        return [
            'total' => $created->count(), 'won' => $won->count(), 'won_value' => $wonValue,
            'potential' => ['count' => $open->count(), 'value' => $openValue],
            'avg_response' => $hours->isNotEmpty() ? round($hours->avg(), 1) : null,
            'stages' => $stages, 'trend' => $trend, 'months' => $months,
            'campaigns' => collect($campaigns)->sortByDesc('leads')->values()->all(),
        ];
    }
```

> `diffInDays` موقّع في Carbon 3: `from->diffInDays(to)` موجب لأن `to` بعد `from`.

- [ ] **Step 4: الكنترولر + المسار**

```php
    public function funnel(Request $request): Response
    {
        $this->authorizeCrm($request);
        [$from, $to, $scope] = $this->period($request);

        return $this->view('crm-funnel', $this->reports->funnel($request->user(), $from, $to, $scope) + ['from' => $from, 'to' => $to, 'scope' => $scope]);
    }
```

```php
        Route::get('/operations/crm/funnel', [C::class, 'funnel']);
```

- [ ] **Step 5: الـview** — `resources/views/admin/operating/crm-funnel.blade.php`:

```blade
@extends('admin.layout')
@section('title',__('قمع المبيعات'))
@section('content')
@php $money=fn(array $sums)=>$sums?collect($sums)->map(fn($amount,$currency)=>number_format($amount).' '.$currency)->implode(' · '):'0'; $pct=fn($value)=>$value===null?'—':$value.'%'; $maxTrend=max(1,...array_values($trend)); $maxMonth=max(1,...array_column($months,'count')); @endphp
<div class="page-heading"><div><span class="eyebrow">CRM</span><h1>{{ __('قمع المبيعات') }}</h1><p class="muted">{{ __('تحليل تحويل العملاء عبر المراحل للفترة المختارة.') }}</p></div>
<form class="inline-form" method="get"><input type="date" name="from" value="{{ $from }}"><span>→</span><input type="date" name="to" value="{{ $to }}">@if($crm->isManager(auth()->user()))<select name="scope"><option value="team" @selected($scope==='team')>{{ __('الفريق') }}</option><option value="mine" @selected($scope==='mine')>{{ __('عملائي') }}</option></select>@endif<button class="button secondary">{{ __('تطبيق') }}</button></form></div>
@include('admin.operating.crm-nav')
<div class="kpi-grid">
<div class="kpi"><span class="kpi-icon">👥</span><strong>{{ number_format($total) }}</strong><span>{{ __('إجمالي العملاء') }}</span></div>
<div class="kpi"><span class="kpi-icon">✓</span><strong>{{ number_format($won) }}</strong><span>{{ __('الصفقات المكتسبة') }}</span><small dir="ltr">{{ $money($won_value) }}</small></div>
<div class="kpi"><span class="kpi-icon">↗</span><strong>{{ number_format($potential['count']) }}</strong><span>{{ __('إغلاقات محتملة') }}</span><small dir="ltr">{{ $money($potential['value']) }}</small></div>
<div class="kpi"><span class="kpi-icon">⏱</span><strong>{{ $avg_response===null?'—':$avg_response.' '.__('ساعة') }}</strong><span>{{ __('متوسط وقت التواصل') }}</span></div>
</div>
<section class="card"><h2>{{ __('معدلات التحويل بين المراحل') }}</h2><ul class="bar-list">@foreach($stages as $step)<li><span>{{ $step['label'] }}</span><i style="width:{{ $total ? max(1,round($step['count']/$total*100)) : 0 }}%"></i><b>{{ $step['count'] }} <small class="muted">{{ $step['rate']===null ? '' : '('.$pct($step['rate']).')' }}</small></b></li>@endforeach</ul><p class="help">{{ __('النسبة بين القوسين = من وصلوا لهذه المرحلة مقارنة بالمرحلة السابقة. العميل الذي قفز لمرحلة متقدمة يُحسب أنه مر بما قبلها.') }}</p></section>
<div class="crm-grid"><section class="card"><h2>{{ __('اتجاه العملاء') }}</h2><ul class="bar-list">@foreach($trend as $period=>$count)<li><span dir="ltr">{{ $period }}</span><i style="width:{{ round($count/$maxTrend*100) }}%"></i><b>{{ $count }}</b></li>@endforeach</ul></section>
<section class="card"><h2>{{ __('الصفقات المكتسبة شهريًا') }}</h2><ul class="bar-list">@foreach($months as $month=>$row)<li><span dir="ltr">{{ $month }}</span><i style="width:{{ round($row['count']/$maxMonth*100) }}%"></i><b>{{ $row['count'] }} <small class="muted" dir="ltr">{{ $row['value'] ? $money($row['value']) : '' }}</small></b></li>@endforeach</ul></section></div>
<section class="card table-card crm-table-wrap"><h2>{{ __('أداء الحملات') }}</h2><table><thead><tr><th>{{ __('الحملة') }}</th><th>{{ __('العملاء') }}</th><th>{{ __('تم التواصل') }}</th><th>{{ __('اجتماعات') }}</th><th>{{ __('مكسوب') }}</th><th>{{ __('الإيراد') }}</th><th>{{ __('معدل التحويل') }}</th></tr></thead><tbody>@forelse($campaigns as $row)<tr><td>{{ $row['name'] }}</td><td>{{ $row['leads'] }}</td><td>{{ $row['contacted'] }}</td><td>{{ $row['meetings'] }}</td><td>{{ $row['won'] }}</td><td dir="ltr">{{ $money($row['revenue']) }}</td><td>{{ $pct($row['conversion']) }}</td></tr>@empty<tr><td colspan="7" class="muted">{{ __('لا توجد بيانات') }}</td></tr>@endforelse</tbody></table></section>
@endsection
```

> `max(1, ...[])` لمصفوفة فارغة يعمل لأن `1` عنصر أول. `$trend` لن تكون فارغة لأن الفترة ≥ يوم.

- [ ] **Step 6: الترجمات**

```json
"تحليل تحويل العملاء عبر المراحل للفترة المختارة.": "Lead conversion across stages for the selected period.",
"إغلاقات محتملة": "Potential closings",
"متوسط وقت التواصل": "Average contact time",
"معدلات التحويل بين المراحل": "Stage conversion rates",
"النسبة بين القوسين = من وصلوا لهذه المرحلة مقارنة بالمرحلة السابقة. العميل الذي قفز لمرحلة متقدمة يُحسب أنه مر بما قبلها.": "The figure in brackets compares each stage with the one before. A lead that skipped ahead counts as having passed the earlier stages.",
"اتجاه العملاء": "Lead trend",
"الصفقات المكتسبة شهريًا": "Won deals by month",
"أداء الحملات": "Campaign performance",
"العملاء": "Leads",
"تم التواصل": "Contacted",
"اجتماعات": "Meetings",
"مكسوب": "Won",
"الإيراد": "Revenue",
"معدل التحويل": "Conversion rate",
"بدون حملة": "No campaign"
```

- [ ] **Step 7:** Run → PASS. ثم Pint + Commit:

```bash
$PHP vendor/bin/pint --dirty --format agent
git add app/Services/OperatingCrmReports.php app/Http/Controllers/CrmController.php routes/admin.php resources/views/admin/operating/crm-funnel.blade.php lang/en.json tests/Feature/Operating/CrmFunnelTest.php
git commit -m "feat(crm): sales funnel with stage conversion, response time, trends and campaign performance

Co-Authored-By: Claude Opus 5.5 (1M context) <noreply@anthropic.com>"
```

---

### Task 12: لوحة الفريق (للمديرين)

**Files:**
- Modify: `app/Services/OperatingCrmReports.php`، `app/Http/Controllers/CrmController.php`، `routes/admin.php`
- Create: `resources/views/admin/operating/crm-team.blade.php`
- Modify: `lang/en.json`
- Test: `tests/Feature/Operating/CrmTeamTest.php`

**Interfaces:**
- Consumes: T10 helpers.
- Produces: `OperatingCrmReports::team(User $user, string $from, string $to): array{agents: list<array{name: string, leads: int, open: int, won: int, lost: int, win_rate: ?float, revenue: array<string, float>, sla_breaches: int, avg_response: ?float, activities: int}>, stages: array<string, int>, quality: array<string, int>, max: int}` (يرمي 403 لغير المدير).
- مسار: `GET /admin/operations/crm/team`.

- [ ] **Step 1: الاختبار الفاشل** — `tests/Feature/Operating/CrmTeamTest.php`:

```php
<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingCrmReports;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmTeamTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_each_agent_gets_leads_wins_revenue_sla_and_activity_counts(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $amira = $this->employeeFor($this->operatingUser('sales'));
        DB::table('operating_records')->where('id', $amira)->update(['title' => 'Amira']);
        $lead = function (string $status, array $data) {
            $id = $this->operatingRecord('opportunities', $status, $data + ['lead_company' => 'Co'], 'Lead');
            DB::table('operating_records')->where('id', $id)->update(['created_at' => '2026-10-05 10:00:00']);
        };
        $lead('won', ['assignee_id' => $amira, 'expected_value' => 2000, 'currency' => 'USD', 'lead_quality' => 'hot']);
        $lead('lost', ['assignee_id' => $amira, 'lead_quality' => 'cold']);
        $lead('new', ['assignee_id' => $amira, 'sla_due_at' => '2026-10-05T12:00']);
        $lead('new', []);
        $this->operatingRecord('activities', 'done', ['kind' => 'call', 'assignee_id' => $amira, 'due_at' => '2026-10-06T10:00', 'completed_at' => '2026-10-06T10:00', 'client_id' => null, 'opportunity_id' => null], 'Call');

        $team = app(OperatingCrmReports::class)->team($this->operatingUser('sales_manager'), '2026-10-01', '2026-10-31');

        $row = collect($team['agents'])->firstWhere('name', 'Amira');
        $this->assertSame([3, 1, 1, 1, 50.0, 1, 1], [$row['leads'], $row['open'], $row['won'], $row['lost'], $row['win_rate'], $row['sla_breaches'], $row['activities']]);
        $this->assertEquals(['USD' => 2000.0], $row['revenue']);
        $this->assertSame(1, collect($team['agents'])->firstWhere('name', __('غير مسند'))['leads']);
        $this->assertSame(2, $team['stages'][__('جديد')]);
        $this->assertSame(['hot' => 1, 'cold' => 1, 'unknown' => 2], array_intersect_key($team['quality'], array_flip(['hot', 'cold', 'unknown'])));
    }

    public function test_reps_cannot_open_the_team_board(): void
    {
        $this->actingAs($this->operatingUser('sales'))->get('/admin/operations/crm/team')->assertForbidden();
        $this->actingAs($this->operatingUser('sales_manager'))->get('/admin/operations/crm/team')->assertOk()->assertSee(__('أداء الفريق'));
    }
}
```

> `quality` مفاتيحها مفاتيح الخيارات (الـview يترجمها)، و`stages` مفاتيحها تسميات الحالات. `__('جديد')` = تسمية `new` العربية (تحقق: `config statuses.new.ar = جديد`).

- [ ] **Step 2:** Run → FAIL.

- [ ] **Step 3: `team()`**:

```php
    /**
     * @return array<string, mixed>
     */
    public function team(User $user, string $from, string $to): array
    {
        abort_unless($this->crm->isManager($user), 403);
        $created = $this->leads($user, 'team')->filter(fn (object $lead): bool => $this->between($lead, $from, $to));
        $names = $this->sales->titles($created->map(fn (object $lead): mixed => $this->catalog->data($lead)['assignee_id'] ?? null)->filter()->unique()->values()->all());
        $agents = [];
        $stages = [];
        $quality = ['hot' => 0, 'warm' => 0, 'cold' => 0, 'bad' => 0, 'unknown' => 0];
        foreach ($created as $lead) {
            $data = $this->catalog->data($lead);
            $key = (int) ($data['assignee_id'] ?? 0);
            $agents[$key] ??= ['name' => $names[$key] ?? __('غير مسند'), 'leads' => 0, 'open' => 0, 'won' => 0, 'lost' => 0, 'win_rate' => null, 'revenue' => [], 'sla_breaches' => 0, 'response' => [], 'avg_response' => null, 'activities' => 0];
            $agents[$key]['leads']++;
            $agents[$key]['open'] += (int) in_array($lead->status, OperatingSales::OPEN_STAGES, true);
            if (in_array($lead->status, ['won', 'lost'], true)) {
                $agents[$key][$lead->status]++;
            }
            if ($lead->status === 'won') {
                $this->addValue($agents[$key]['revenue'], $data);
            }
            $agents[$key]['sla_breaches'] += (int) in_array($this->crm->slaState($lead), ['breached', 'late'], true);
            if (($hours = $this->crm->responseHours($lead)) !== null) {
                $agents[$key]['response'][] = $hours;
            }
            $label = $this->catalog->status($lead->status);
            $stages[$label] = ($stages[$label] ?? 0) + 1;
            $quality[$data['lead_quality'] ?? 'unknown'] = ($quality[$data['lead_quality'] ?? 'unknown'] ?? 0) + 1;
        }
        if ($this->access->can($user, 'activities')) {
            foreach ($this->activityQuery($user, 'team')->where('status', 'done')->where('data->completed_at', '>=', $from)->where('data->completed_at', '<=', $to.'T23:59')->get() as $activity) {
                $key = (int) ($this->catalog->data($activity)['assignee_id'] ?? 0);
                if (isset($agents[$key])) {
                    $agents[$key]['activities']++;
                }
            }
        }
        foreach ($agents as &$agent) {
            $agent['win_rate'] = $this->percent($agent['won'], $agent['won'] + $agent['lost']);
            $agent['avg_response'] = $agent['response'] ? round(array_sum($agent['response']) / count($agent['response']), 1) : null;
            unset($agent['response']);
        }
        unset($agent);
        $agents = collect($agents)->sortByDesc('leads')->values()->all();

        return ['agents' => $agents, 'stages' => $stages, 'quality' => $quality, 'max' => max(1, ...array_column($agents, 'leads'))];
    }
```

> ملاحظة: نشاط لموظف ليس له فرص في الفترة لا يظهر في اللوحة (اللوحة مبنية على الفرص). مقبول ومذكور في الـhelp.

- [ ] **Step 4: الكنترولر + المسار**

```php
    public function team(Request $request): Response
    {
        $this->authorizeCrm($request, manager: true);
        [$from, $to] = $this->period($request);

        return $this->view('crm-team', $this->reports->team($request->user(), $from, $to) + ['from' => $from, 'to' => $to, 'qualityLabels' => array_map(fn (array $label): string => $this->catalog->label($label), (array) collect($this->catalog->entity('opportunities')['fields'])->firstWhere('key', 'lead_quality')['options'])]);
    }
```

```php
        Route::get('/operations/crm/team', [C::class, 'team']);
```

- [ ] **Step 5: الـview** — `resources/views/admin/operating/crm-team.blade.php`:

```blade
@extends('admin.layout')
@section('title',__('لوحة الفريق'))
@section('content')
@php $money=fn(array $sums)=>$sums?collect($sums)->map(fn($amount,$currency)=>number_format($amount).' '.$currency)->implode(' · '):'0'; $pct=fn($value)=>$value===null?'—':$value.'%'; $stageMax=max(1,...array_values($stages ?: [0])); $qualityMax=max(1,...array_values($quality)); @endphp
<div class="page-heading"><div><span class="eyebrow">CRM</span><h1>{{ __('لوحة الفريق') }}</h1><p class="muted">{{ __('أداء الفريق') }} · {{ __('العملاء الذين أُنشئوا في الفترة') }}</p></div>
<form class="inline-form" method="get"><input type="date" name="from" value="{{ $from }}"><span>→</span><input type="date" name="to" value="{{ $to }}"><button class="button secondary">{{ __('تطبيق') }}</button></form></div>
@include('admin.operating.crm-nav')
<section class="card"><h2>{{ __('العملاء لكل مسؤول') }}</h2><ul class="bar-list">@foreach($agents as $agent)<li><span>{{ $agent['name'] }}</span><i style="width:{{ round($agent['leads']/$max*100) }}%"></i><b>{{ $agent['leads'] }}</b></li>@endforeach</ul></section>
<section class="card table-card crm-table-wrap"><h2>{{ __('مؤشرات الأداء لكل مسؤول') }}</h2><table><thead><tr><th>{{ __('المسؤول') }}</th><th>{{ __('العملاء') }}</th><th>{{ __('مفتوح') }}</th><th>{{ __('مكسوب') }}</th><th>{{ __('خسارة') }}</th><th>{{ __('نسبة الفوز') }}</th><th>{{ __('الإيراد') }}</th><th>{{ __('مخالفات SLA') }}</th><th>{{ __('متوسط الاستجابة') }}</th><th>{{ __('الأنشطة') }}</th></tr></thead><tbody>@forelse($agents as $agent)<tr><td>{{ $agent['name'] }}</td><td>{{ $agent['leads'] }}</td><td>{{ $agent['open'] }}</td><td>{{ $agent['won'] }}</td><td>{{ $agent['lost'] }}</td><td>{{ $pct($agent['win_rate']) }}</td><td dir="ltr">{{ $money($agent['revenue']) }}</td><td>{{ $agent['sla_breaches'] }}</td><td>{{ $agent['avg_response']===null?'—':$agent['avg_response'].' '.__('ساعة') }}</td><td>{{ $agent['activities'] }}</td></tr>@empty<tr><td colspan="10" class="muted">{{ __('لا توجد بيانات') }}</td></tr>@endforelse</tbody></table></section>
<div class="crm-grid"><section class="card"><h2>{{ __('العملاء حسب المرحلة') }}</h2><ul class="bar-list">@foreach($stages as $label=>$count)<li><span>{{ $label }}</span><i style="width:{{ round($count/$stageMax*100) }}%"></i><b>{{ $count }}</b></li>@endforeach</ul></section>
<section class="card"><h2>{{ __('توزيع جودة العملاء') }}</h2><ul class="bar-list">@foreach($quality as $key=>$count)<li><span>{{ $qualityLabels[$key] ?? $key }}</span><i style="width:{{ round($count/$qualityMax*100) }}%"></i><b>{{ $count }}</b></li>@endforeach</ul></section></div>
<p class="help">{{ __('الأنشطة تُحسب لمن لديه عملاء في الفترة. الإيراد = قيمة الفرص المكسوبة بعملتها.') }}</p>
@endsection
```

- [ ] **Step 6: الترجمات**

```json
"أداء الفريق": "Team performance",
"العملاء الذين أُنشئوا في الفترة": "Leads created in the period",
"العملاء لكل مسؤول": "Leads per owner",
"مؤشرات الأداء لكل مسؤول": "Performance per owner",
"مفتوح": "Open",
"خسارة": "Lost",
"نسبة الفوز": "Win rate",
"مخالفات SLA": "SLA breaches",
"متوسط الاستجابة": "Avg. response",
"الأنشطة": "Activities",
"العملاء حسب المرحلة": "Leads by stage",
"توزيع جودة العملاء": "Lead quality distribution",
"الأنشطة تُحسب لمن لديه عملاء في الفترة. الإيراد = قيمة الفرص المكسوبة بعملتها.": "Activities count for owners with leads in the period. Revenue is the value of won leads in their currency."
```

- [ ] **Step 7:** Run → PASS. Pint + Commit:

```bash
$PHP vendor/bin/pint --dirty --format agent
git add app/Services/OperatingCrmReports.php app/Http/Controllers/CrmController.php routes/admin.php resources/views/admin/operating/crm-team.blade.php lang/en.json tests/Feature/Operating/CrmTeamTest.php
git commit -m "feat(crm): manager team board with per-owner performance, stages and lead quality

Co-Authored-By: Claude Opus 5.5 (1M context) <noreply@anthropic.com>"
```

---

### Task 13: لوحة المهام وSLA

**Files:**
- Modify: `app/Services/OperatingCrmReports.php`، `app/Http/Controllers/CrmController.php`، `routes/admin.php`
- Create: `resources/views/admin/operating/crm-sla.blade.php`
- Modify: `lang/en.json`
- Test: `tests/Feature/Operating/CrmSlaBoardTest.php`

**Interfaces:**
- Consumes: T3 (`slaState`، `openAlert`، `responseHours`)، T10 helpers.
- Produces: `OperatingCrmReports::sla(User $user, string $from, string $to, string $scope, ?int $assignee = null): array{states: array{pending: int, met: int, late: int, breached: int}, compliance: ?float, avg_response: ?float, activities: int, trend: array<string, ?float>, kinds: array<string, int>, results: array<string, int>, agents: list<array{name: string, total: int, met: int, late: int, breached: int, compliance: ?float, avg_response: ?float}>, breached: list<array{id: int, title: string, assignee: ?string, due: string, hours: float}>}`
  - الالتزام = `met ÷ (met + late + breached)`. `pending` لا يدخل.
  - `kinds` و`results`: مفتاح الخيار ← العدد، للأنشطة المكتملة في الفترة.
- مسار: `GET /admin/operations/crm/sla`.

- [ ] **Step 1: الاختبار الفاشل** — `tests/Feature/Operating/CrmSlaBoardTest.php`:

```php
<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingCrmReports;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmSlaBoardTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_compliance_breaches_activity_mix_and_the_breached_list(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $rep = $this->employeeFor($this->operatingUser('sales'));
        $lead = function (string $status, array $data, string $title) use ($rep): int {
            $id = $this->operatingRecord('opportunities', $status, $data + ['lead_company' => 'Co', 'assignee_id' => $rep], $title);
            DB::table('operating_records')->where('id', $id)->update(['created_at' => '2026-10-10 08:00:00']);

            return $id;
        };
        $lead('contacted', ['sla_due_at' => '2026-10-10T10:00', 'first_contact_at' => '2026-10-10T09:00'], 'Met');
        $lead('contacted', ['sla_due_at' => '2026-10-10T10:00', 'first_contact_at' => '2026-10-10T11:00'], 'Late');
        $lead('new', ['sla_due_at' => '2026-10-10T10:00'], 'Breached');
        $lead('new', ['sla_due_at' => '2026-10-21T10:00'], 'Pending');
        $this->operatingRecord('activities', 'done', ['kind' => 'call', 'result' => 'no_answer', 'assignee_id' => $rep, 'due_at' => '2026-10-10T09:00', 'completed_at' => '2026-10-10T09:00'], 'Call');
        $this->operatingRecord('activities', 'done', ['kind' => 'whatsapp', 'result' => 'interested', 'assignee_id' => $rep, 'due_at' => '2026-10-10T11:00', 'completed_at' => '2026-10-10T11:00'], 'WA');

        $board = app(OperatingCrmReports::class)->sla($this->operatingUser('sales_manager'), '2026-10-01', '2026-10-31', 'team');

        $this->assertSame(['pending' => 1, 'met' => 1, 'late' => 1, 'breached' => 1], $board['states']);
        $this->assertSame(33.3, $board['compliance']);
        $this->assertSame(2.0, $board['avg_response']);
        $this->assertSame(2, $board['activities']);
        $this->assertSame(['call' => 1, 'whatsapp' => 1], $board['kinds']);
        $this->assertSame(['no_answer' => 1, 'interested' => 1], $board['results']);
        $this->assertSame(['Breached'], array_column($board['breached'], 'title'));
        $this->assertSame(242.0, $board['breached'][0]['hours']);
        $this->assertSame(33.3, $board['trend']['2026-10-10']);
    }

    public function test_the_page_renders_for_reps_with_their_own_numbers(): void
    {
        $rep = $this->operatingUser('sales');
        $this->employeeFor($rep);

        $this->actingAs($rep)->get('/admin/operations/crm/sla')->assertOk()->assertSee(__('نسبة الالتزام بـSLA'));
    }
}
```

> متوسط الاستجابة: (1 + 3) ÷ 2 = 2.0 ساعة. ساعات المخالفة: من 2026-10-10T10:00 إلى 2026-10-20T12:00 = 242 ساعة.

- [ ] **Step 2:** Run → FAIL.

- [ ] **Step 3: `sla()`**:

```php
    /**
     * @return array<string, mixed>
     */
    public function sla(User $user, string $from, string $to, string $scope, ?int $assignee = null): array
    {
        $leads = $this->leads($user, $scope)->when($assignee, fn (Collection $all) => $all->filter(fn (object $lead): bool => (int) ($this->catalog->data($lead)['assignee_id'] ?? 0) === $assignee));
        $created = $leads->filter(fn (object $lead): bool => $this->between($lead, $from, $to));
        $names = $this->sales->titles($leads->map(fn (object $lead): mixed => $this->catalog->data($lead)['assignee_id'] ?? null)->filter()->unique()->values()->all());
        $states = ['pending' => 0, 'met' => 0, 'late' => 0, 'breached' => 0];
        $byDay = [];
        $agents = [];
        $hours = [];
        foreach ($created as $lead) {
            $state = $this->crm->slaState($lead);
            if (! $state) {
                continue;
            }
            $states[$state]++;
            $day = substr((string) $lead->created_at, 0, 10);
            $byDay[$day][$state] = ($byDay[$day][$state] ?? 0) + 1;
            $key = (int) ($this->catalog->data($lead)['assignee_id'] ?? 0);
            $agents[$key] ??= ['name' => $names[$key] ?? __('غير مسند'), 'total' => 0, 'met' => 0, 'late' => 0, 'breached' => 0, 'compliance' => null, 'response' => [], 'avg_response' => null];
            $agents[$key]['total']++;
            if ($state !== 'pending') {
                $agents[$key][$state]++;
            }
            if (($response = $this->crm->responseHours($lead)) !== null) {
                $hours[] = $response;
                $agents[$key]['response'][] = $response;
            }
        }
        foreach ($agents as &$agent) {
            $agent['compliance'] = $this->percent($agent['met'], $agent['met'] + $agent['late'] + $agent['breached']);
            $agent['avg_response'] = $agent['response'] ? round(array_sum($agent['response']) / count($agent['response']), 1) : null;
            unset($agent['response']);
        }
        unset($agent);
        $trend = [];
        for ($day = CarbonImmutable::parse($from); $day->format('Y-m-d') <= $to; $day = $day->addDay()) {
            $counts = $byDay[$day->format('Y-m-d')] ?? [];
            $trend[$day->format('Y-m-d')] = $this->percent($counts['met'] ?? 0, ($counts['met'] ?? 0) + ($counts['late'] ?? 0) + ($counts['breached'] ?? 0));
        }
        $kinds = $results = [];
        $activityCount = 0;
        if ($this->access->can($user, 'activities')) {
            $done = $this->activityQuery($user, $scope)->where('status', 'done')->where('data->completed_at', '>=', $from)->where('data->completed_at', '<=', $to.'T23:59')->when($assignee, fn (Builder $query) => $query->where('data->assignee_id', $assignee))->orderBy('id')->get();
            $activityCount = $done->count();
            foreach ($done as $activity) {
                $data = $this->catalog->data($activity);
                $kinds[$data['kind'] ?? 'other'] = ($kinds[$data['kind'] ?? 'other'] ?? 0) + 1;
                if (! empty($data['result'])) {
                    $results[$data['result']] = ($results[$data['result']] ?? 0) + 1;
                }
            }
        }
        $now = CarbonImmutable::now();
        $breached = $leads->filter(fn (object $lead): bool => $this->crm->openAlert($lead))->map(function (object $lead) use ($names, $now): array {
            $data = $this->catalog->data($lead);

            return ['id' => $lead->id, 'title' => $lead->title, 'assignee' => $names[$data['assignee_id'] ?? 0] ?? null, 'due' => str_replace('T', ' ', $data['sla_due_at']), 'hours' => round(CarbonImmutable::parse($data['sla_due_at'])->diffInMinutes($now) / 60, 1)];
        })->sortByDesc('hours')->take(50)->values()->all();

        return [
            'states' => $states, 'compliance' => $this->percent($states['met'], $states['met'] + $states['late'] + $states['breached']),
            'avg_response' => $hours ? round(array_sum($hours) / count($hours), 1) : null, 'activities' => $activityCount,
            'trend' => $trend, 'kinds' => $kinds, 'results' => $results,
            'agents' => collect($agents)->sortByDesc('total')->values()->all(), 'breached' => $breached,
        ];
    }
```

- [ ] **Step 4: الكنترولر + المسار**

```php
    public function sla(Request $request): Response
    {
        $this->authorizeCrm($request);
        [$from, $to, $scope] = $this->period($request);
        $assignee = $this->crm->isManager($request->user()) ? ($request->integer('assignee') ?: null) : null;
        $activityFields = collect($this->catalog->entity('activities')['fields'])->keyBy('key');
        $labels = fn (string $key): array => array_map(fn (array $label): string => $this->catalog->label($label), (array) $activityFields[$key]['options']);

        return $this->view('crm-sla', $this->reports->sla($request->user(), $from, $to, $scope, $assignee) + ['from' => $from, 'to' => $to, 'scope' => $scope, 'assignee' => $assignee, 'kindLabels' => $labels('kind'), 'resultLabels' => $labels('result'), 'options' => $this->leadOptions($request)]);
    }
```

```php
        Route::get('/operations/crm/sla', [C::class, 'sla']);
```

- [ ] **Step 5: الـview** — `resources/views/admin/operating/crm-sla.blade.php`:

```blade
@extends('admin.layout')
@section('title',__('المهام وSLA'))
@section('content')
@php $pct=fn($value)=>$value===null?'—':$value.'%'; $kindMax=max(1,...array_values($kinds ?: [0])); $resultMax=max(1,...array_values($results ?: [0])); $manager=$crm->isManager(auth()->user()); @endphp
<div class="page-heading"><div><span class="eyebrow">CRM</span><h1>{{ __('المهام وSLA') }}</h1><p class="muted">{{ __('مراقبة الأنشطة والالتزام بمهلة التواصل مع العملاء الجدد.') }}</p></div>
<form class="inline-form" method="get"><input type="date" name="from" value="{{ $from }}"><span>→</span><input type="date" name="to" value="{{ $to }}">@if($manager)<select name="scope"><option value="team" @selected($scope==='team')>{{ __('الفريق') }}</option><option value="mine" @selected($scope==='mine')>{{ __('عملائي') }}</option></select><select name="assignee"><option value="">{{ __('كل المسؤولين') }}</option>@foreach($options['assignees'] as $id=>$name)<option value="{{ $id }}" @selected($assignee===(int)$id)>{{ $name }}</option>@endforeach</select>@endif<button class="button secondary">{{ __('تطبيق') }}</button></form></div>
@include('admin.operating.crm-nav')
<div class="kpi-grid">
<div class="kpi"><span class="kpi-icon">✓</span><strong>{{ $pct($compliance) }}</strong><span>{{ __('نسبة الالتزام بـSLA') }}</span><small>{{ __('في الموعد من إجمالي من انتهت مهلتهم') }}</small></div>
<div class="kpi {{ $states['breached']?'warn':'' }}"><span class="kpi-icon">⚠</span><strong>{{ $states['breached'] + $states['late'] }}</strong><span>{{ __('مخالفات SLA') }}</span><small>{{ $states['breached'] }} {{ __('بدون تواصل') }} · {{ $states['late'] }} {{ __('متأخر') }}</small></div>
<div class="kpi"><span class="kpi-icon">⏱</span><strong>{{ $avg_response===null?'—':$avg_response.' '.__('ساعة') }}</strong><span>{{ __('متوسط وقت الاستجابة') }}</span></div>
<div class="kpi"><span class="kpi-icon">⚡</span><strong>{{ $activities }}</strong><span>{{ __('إجمالي الأنشطة') }}</span></div>
<div class="kpi"><span class="kpi-icon">⧗</span><strong>{{ $states['pending'] }}</strong><span>{{ __('ضمن المهلة') }}</span><small>{{ __('لم تنتهِ مهلتهم بعد') }}</small></div>
</div>
<section class="card"><h2>{{ __('اتجاه الالتزام') }}</h2><ul class="bar-list">@foreach($trend as $day=>$value)@if($value!==null)<li><span dir="ltr">{{ $day }}</span><i style="width:{{ max(1,$value) }}%"></i><b>{{ $pct($value) }}</b></li>@endif @endforeach</ul>@if(!array_filter($trend,fn($value)=>$value!==null))<p class="muted">{{ __('لا توجد بيانات') }}</p>@endif</section>
<div class="crm-grid"><section class="card"><h2>{{ __('الأنشطة حسب النوع') }}</h2><ul class="bar-list">@forelse($kinds as $key=>$count)<li><span>{{ $kindLabels[$key] ?? $key }}</span><i style="width:{{ round($count/$kindMax*100) }}%"></i><b>{{ $count }}</b></li>@empty<li class="muted">{{ __('لا توجد بيانات') }}</li>@endforelse</ul></section>
<section class="card"><h2>{{ __('نتائج الأنشطة') }}</h2><ul class="bar-list">@forelse($results as $key=>$count)<li><span>{{ $resultLabels[$key] ?? $key }}</span><i style="width:{{ round($count/$resultMax*100) }}%"></i><b>{{ $count }}</b></li>@empty<li class="muted">{{ __('لا توجد بيانات') }}</li>@endforelse</ul></section></div>
@if($manager)<section class="card table-card crm-table-wrap"><h2>{{ __('أداء SLA لكل مسؤول') }}</h2><table><thead><tr><th>{{ __('المسؤول') }}</th><th>{{ __('العملاء') }}</th><th>{{ __('في الموعد') }}</th><th>{{ __('متأخر') }}</th><th>{{ __('متجاوز') }}</th><th>{{ __('نسبة الالتزام') }}</th><th>{{ __('متوسط الاستجابة') }}</th></tr></thead><tbody>@forelse($agents as $agent)<tr><td>{{ $agent['name'] }}</td><td>{{ $agent['total'] }}</td><td>{{ $agent['met'] }}</td><td>{{ $agent['late'] }}</td><td>{{ $agent['breached'] }}</td><td>{{ $pct($agent['compliance']) }}</td><td>{{ $agent['avg_response']===null?'—':$agent['avg_response'].' '.__('ساعة') }}</td></tr>@empty<tr><td colspan="7" class="muted">{{ __('لا توجد بيانات') }}</td></tr>@endforelse</tbody></table></section>@endif
<section class="card table-card crm-table-wrap"><h2>{{ __('عملاء تجاوزوا المهلة (بدون تواصل)') }}</h2><table><thead><tr><th>{{ __('اسم العميل') }}</th><th>{{ __('المسؤول') }}</th><th>{{ __('المهلة') }}</th><th>{{ __('الوقت المنقضي') }}</th></tr></thead><tbody>@forelse($breached as $row)<tr><td><a href="/admin/operations/crm/leads/{{ $row['id'] }}">{{ $row['title'] }}</a></td><td>{{ $row['assignee'] ?? __('غير مسند') }}</td><td dir="ltr">{{ $row['due'] }}</td><td class="danger">{{ $row['hours'] }} {{ __('ساعة') }}</td></tr>@empty<tr><td colspan="4" class="muted">{{ __('لا يوجد عملاء تجاوزوا المهلة.') }}</td></tr>@endforelse</tbody></table></section>
@endsection
```

- [ ] **Step 6: الترجمات**

```json
"مراقبة الأنشطة والالتزام بمهلة التواصل مع العملاء الجدد.": "Activity monitoring and response SLA compliance for new leads.",
"كل المسؤولين": "All owners",
"نسبة الالتزام بـSLA": "SLA compliance",
"في الموعد من إجمالي من انتهت مهلتهم": "On time out of all leads whose deadline passed",
"بدون تواصل": "no contact",
"متوسط وقت الاستجابة": "Average response time",
"لم تنتهِ مهلتهم بعد": "Deadline not reached yet",
"اتجاه الالتزام": "Compliance trend",
"الأنشطة حسب النوع": "Activities by type",
"نتائج الأنشطة": "Activity outcomes",
"أداء SLA لكل مسؤول": "SLA performance per owner",
"نسبة الالتزام": "Compliance",
"عملاء تجاوزوا المهلة (بدون تواصل)": "Leads past the deadline (no contact)",
"المهلة": "Deadline",
"الوقت المنقضي": "Time elapsed"
```

- [ ] **Step 7:** Run → PASS. Pint + Commit:

```bash
$PHP vendor/bin/pint --dirty --format agent
git add app/Services/OperatingCrmReports.php app/Http/Controllers/CrmController.php routes/admin.php resources/views/admin/operating/crm-sla.blade.php lang/en.json tests/Feature/Operating/CrmSlaBoardTest.php
git commit -m "feat(crm): tasks and SLA board with compliance trend, activity mix and breached leads

Co-Authored-By: Claude Opus 5.5 (1M context) <noreply@anthropic.com>"
```

---

### Task 14: التقويم (الاجتماعات والمتابعات) + تعبئة الموعد عند الإنشاء

**Files:**
- Modify: `app/Http/Controllers/CrmController.php`، `routes/admin.php`
- Modify: `app/Http/Controllers/OperatingController.php` (`create()`: prefill لحقول التاريخ)
- Create: `resources/views/admin/operating/crm-calendar.blade.php`
- Modify: `lang/en.json`
- Test: `tests/Feature/Operating/CrmCalendarTest.php`

**Interfaces:**
- Consumes: `OperatingCrmReports::calendar()` (T10).
- Produces: مسار `GET /admin/operations/crm/calendar?month=Y-m&assignee=`. و`/admin/operations/{type}/create?prefill[due_at]=Y-m-dTH:i` يقبل الآن حقول `datetime-local` و`date`.

- [ ] **Step 1: الاختبار الفاشل** — `tests/Feature/Operating/CrmCalendarTest.php`:

```php
<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingCrmReports;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmCalendarTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_the_grid_starts_on_saturday_and_places_items_with_overdue_flags(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $repUser = $this->operatingUser('sales');
        $rep = $this->employeeFor($repUser);
        $other = $this->employeeFor($this->operatingUser('sales'));
        $lead = $this->operatingRecord('opportunities', 'meeting', ['lead_company' => 'Co', 'assignee_id' => $rep], 'Co');
        $this->operatingRecord('activities', 'planned', ['kind' => 'meeting', 'assignee_id' => $rep, 'due_at' => '2026-10-19T10:00', 'opportunity_id' => $lead], 'Demo');
        $this->operatingRecord('activities', 'planned', ['kind' => 'call', 'assignee_id' => $rep, 'due_at' => '2026-10-22T09:30'], 'Call back');
        $this->operatingRecord('activities', 'planned', ['kind' => 'call', 'assignee_id' => $other, 'due_at' => '2026-10-22T11:00'], 'Not mine');

        $calendar = app(OperatingCrmReports::class)->calendar($repUser, '2026-10');

        $this->assertSame('2026-09-26', $calendar['weeks'][0][0]['date']);
        $this->assertSame('2026-11-06', last(last($calendar['weeks']))['date']);
        $days = collect($calendar['weeks'])->flatten(1)->keyBy('date');
        $this->assertTrue($days['2026-10-19']['items'][0]['overdue']);
        $this->assertSame($lead, $days['2026-10-19']['items'][0]['lead']);
        $this->assertSame(['Call back'], array_column($days['2026-10-22']['items'], 'title'));
        $this->assertTrue($days['2026-10-20']['today']);
    }

    public function test_the_page_renders_and_new_activity_links_prefill_the_due_time(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $rep = $this->operatingUser('sales');
        $this->employeeFor($rep);

        $this->actingAs($rep)->get('/admin/operations/crm/calendar?month=2026-10')->assertOk()->assertSee('prefill[due_at]=2026-10-22T10:00', false);
        $this->actingAs($rep)->get('/admin/operations/activities/create?prefill[due_at]=2026-10-22T10:00')->assertOk()->assertSee('value="2026-10-22T10:00"', false);
        $this->actingAs($rep)->get('/admin/operations/crm/calendar?month=2026-13')->assertSessionHasErrors('month');
    }
}
```

> 2026-10-01 يوم خميس، فالسبت السابق 2026-09-26. 2026-10-31 سبت، فالجمعة التالية 2026-11-06.

- [ ] **Step 2:** Run → FAIL.

- [ ] **Step 3: prefill التواريخ** — في `OperatingController::create()` استبدل سطر `$prefillable = …` والـforeach بعده بـ:

```php
        $prefillable = collect($this->catalog->entity($type)['fields'])->whereIn('type', ['relation', 'select', 'datetime-local', 'date'])->keyBy('key');
        foreach ((array) $request->input('prefill', []) as $key => $value) {
            if (($prefillable[$key]['type'] ?? null) === 'relation' && is_numeric($value)) {
                $target = $this->access->record($request->user(), (int) $value);
                abort_unless($target->type === $prefillable[$key]['entity'], 404);
                $initial[$key] = (int) $value;
            }
            if (($prefillable[$key]['type'] ?? null) === 'select' && is_string($value) && isset($prefillable[$key]['options'][$value])) {
                $initial[$key] = $value;
            }
            if (($prefillable[$key]['type'] ?? null) === 'datetime-local' && is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value)) {
                $initial[$key] = $value;
            }
            if (($prefillable[$key]['type'] ?? null) === 'date' && is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                $initial[$key] = $value;
            }
        }
```

- [ ] **Step 4: الكنترولر + المسار**

```php
    public function calendar(Request $request): Response
    {
        $this->authorizeCrm($request);
        $input = $request->validate(['month' => 'nullable|date_format:Y-m', 'assignee' => 'nullable|integer', 'scope' => 'nullable|in:team,mine']);
        $manager = $this->crm->isManager($request->user());
        $scope = $manager ? ($input['scope'] ?? 'team') : 'mine';
        $assignee = $manager ? ($input['assignee'] ?? null) : null;

        return $this->view('crm-calendar', ['calendar' => $this->reports->calendar($request->user(), $input['month'] ?? today()->format('Y-m'), $scope, $assignee), 'assignee' => $assignee, 'scope' => $scope, 'options' => $this->leadOptions($request), 'canCreate' => $this->access->canCreate($request->user(), 'activities')]);
    }
```

```php
        Route::get('/operations/crm/calendar', [C::class, 'calendar']);
```

- [ ] **Step 5: الـview** — `resources/views/admin/operating/crm-calendar.blade.php`:

```blade
@extends('admin.layout')
@section('title',__('التقويم'))
@section('content')
@php $query=fn(array $extra)=>'?'.http_build_query(array_filter(['month'=>$calendar['month'],'assignee'=>$assignee,'scope'=>$scope]+$extra)); @endphp
<div class="page-heading"><div><span class="eyebrow">CRM</span><h1>{{ __('التقويم') }}</h1><p class="muted">{{ __('الاجتماعات والمتابعات المجدولة. اضغط على يوم لإضافة متابعة فيه.') }}</p></div>
<div class="row-actions"><a class="button secondary" href="{{ $query(['month'=>$calendar['prev']]) }}" aria-label="{{ __('الشهر السابق') }}">→</a><b>{{ $calendar['label'] }}</b><a class="button secondary" href="{{ $query(['month'=>$calendar['next']]) }}" aria-label="{{ __('الشهر التالي') }}">←</a><a class="button secondary" href="?month={{ today()->format('Y-m') }}">{{ __('اليوم') }}</a></div></div>
@include('admin.operating.crm-nav')
@if($options['assignees'])<form class="filters" method="get"><input type="hidden" name="month" value="{{ $calendar['month'] }}"><select name="scope"><option value="team" @selected($scope==='team')>{{ __('الفريق') }}</option><option value="mine" @selected($scope==='mine')>{{ __('عملائي') }}</option></select><select name="assignee"><option value="">{{ __('كل المسؤولين') }}</option>@foreach($options['assignees'] as $id=>$name)<option value="{{ $id }}" @selected((int)$assignee===(int)$id)>{{ $name }}</option>@endforeach</select><button class="button secondary">{{ __('تصفية') }}</button></form>@endif
<section class="card"><div class="cal-grid">@foreach(['سبت','أحد','اثنين','ثلاثاء','أربعاء','خميس','جمعة'] as $dayName)<span class="cal-head">{{ __($dayName) }}</span>@endforeach
@foreach($calendar['weeks'] as $week)@foreach($week as $day)<div class="cal-day {{ $day['inMonth']?'':'outside' }} {{ $day['today']?'today':'' }}"><div class="section-heading"><b>{{ $day['day'] }}</b>@if($canCreate)<a class="text-button" href="/admin/operations/activities/create?prefill[due_at]={{ $day['date'] }}T10:00" aria-label="{{ __('إضافة متابعة في') }} {{ $day['date'] }}">+</a>@endif</div>
@foreach(array_slice($day['items'],0,4) as $item)<a class="cal-item {{ $item['overdue']?'overdue':'' }} {{ $item['status']==='done'?'done':'' }}" href="{{ $item['lead'] ? '/admin/operations/crm/leads/'.$item['lead'] : '/admin/operations/record/'.$item['id'] }}" title="{{ $item['time'] }} {{ $item['title'] }}">{{ $item['time'] }} {{ $item['title'] }}</a>@endforeach @if(count($day['items'])>4)<small class="muted">+{{ count($day['items'])-4 }}</small>@endif</div>@endforeach @endforeach</div></section>
@endsection
```

> في RTL، السهم `→` يشير لليمين = الماضي، و`←` للمستقبل. هذا مقصود.

- [ ] **Step 6: الترجمات**

```json
"الاجتماعات والمتابعات المجدولة. اضغط على يوم لإضافة متابعة فيه.": "Scheduled meetings and follow-ups. Use + on a day to add one.",
"الشهر السابق": "Previous month",
"الشهر التالي": "Next month",
"اليوم": "Today",
"إضافة متابعة في": "Add a follow-up on"
```

- [ ] **Step 7:** Run: `$PHP artisan test --compact tests/Feature/Operating/CrmCalendarTest.php tests/Feature/Operating/FollowUpsTest.php` → PASS. Pint + Commit:

```bash
$PHP vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/CrmController.php app/Http/Controllers/OperatingController.php routes/admin.php resources/views/admin/operating/crm-calendar.blade.php lang/en.json tests/Feature/Operating/CrmCalendarTest.php
git commit -m "feat(crm): Saturday-first activity calendar with day quick-add and date prefill

Co-Authored-By: Claude Opus 5.5 (1M context) <noreply@anthropic.com>"
```

---

### Task 15: استيراد العملاء من Excel/CSV (رفع ← ربط أعمدة تلقائي ← معاينة ← تنفيذ)

**Files:**
- Create: `app/Services/OperatingLeadImport.php`
- Modify: `app/Http/Controllers/CrmController.php`، `routes/admin.php`
- Create: `resources/views/admin/operating/crm-import.blade.php`، `resources/views/admin/operating/crm-import-map.blade.php`
- Modify: `lang/en.json`
- Test: `tests/Feature/Operating/CrmImportTest.php`

**Interfaces:**
- Consumes: `Spreadsheet::read()` (T5)، `OperatingSales::{saveAs, transitionAs}` (T7)، `OperatingCrm::phoneKey()`، `OperatingWorkflow::{fail, mark, event}`.
- Produces:
  - `OperatingLeadImport::MAX_ROWS = 2000`، `FIELDS` (مفتاح ← عنوان عربي)، `SYNONYMS`.
  - `stage(User $user, UploadedFile $file): string` (token = UUID، الملف المؤقت `storage/app/private/crm-imports/{token}.json` على قرص `local`)
  - `load(User $user, string $token): array{user_id: int, file: string, header: list<string>, rows: list<list<string>>}` (403 لغير صاحب الرفع، 404 لو غير موجود)
  - `normalize(string $text): string`
  - `guessMapping(array $header): array<int, ?string>`
  - `preview(User $user, string $token, array $mapping, array $options, int $limit = 50): array{rows: list<array>, total: int, duplicates: int}`
  - `commit(User $user, string $token, array $mapping, array $options): array{created: int, duplicates: int, failed: list<array{row: int, error: string}>, warnings: list<array{row: int, warning: string}>}`
  - `$options`: `array{source: string, campaign_id: ?int, assignee_id: ?int, skip_sla: bool}`
  - event action: `import_leads` (record_id = null).
  - مسارات: `GET|POST /admin/operations/crm/import`، `GET|POST /admin/operations/crm/import/{token}`.
- قواعد: الاستيراد للمديرين فقط. التكرار (بالهاتف في القاعدة أو داخل نفس الملف) يُتخطى ويُعدّ. كل صف يمر بـ`saveAs` (validation + event). المرحلة غير `new` تُطبَّق بانتقال حقيقي، ولو رفضتها البوابة يبقى الصف `new` مع تحذير. `skip_sla` (افتراضي مفعّل) يلغي مهلة SLA للمستورد لأنه غالبًا بيانات قديمة.

- [ ] **Step 1: الاختبار الفاشل** — `tests/Feature/Operating/CrmImportTest.php`:

```php
<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingLeadImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmImportTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_headers_map_automatically_in_english_and_arabic(): void
    {
        $import = app(OperatingLeadImport::class);

        $this->assertSame(['title', 'lead_phone', 'country', 'business_profile', 'lead_quality', 'campaign', 'ad_creative', 'status', 'notes', 'lead_date', 'assignee', 'media_buyer_notes', null], $import->guessMapping(['Client Name', 'Phone', 'Country', 'Business Type', 'Lead Quality', 'Campaign Name', 'Ad Creative', 'Stage', 'Notes', 'Lead Time', 'Sales Agent', 'Media Buyer Notes', 'Random']));
        $this->assertSame(['title', 'lead_phone', 'campaign', 'status', 'media_buyer_notes'], $import->guessMapping(['اسم العميل', 'رقم الجوال', 'الحملة', 'الحالة', 'ملاحظات ميديا باير']));
    }

    public function test_a_manager_imports_with_duplicates_skipped_stages_applied_and_an_audit_event(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $manager = $this->operatingUser('sales_manager');
        $sara = $this->employeeFor($this->operatingUser('sales'));
        DB::table('operating_records')->where('id', $sara)->update(['title' => 'Sara Ali']);
        $campaign = $this->operatingRecord('campaigns', 'running', ['objective' => 'leads', 'budget_cap' => 1000], 'Ramadan Offer');
        $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Existing', 'lead_phone' => '01005550000', 'phone_key' => '005550000'], 'Existing');
        $csv = "Client Name,Phone,Lead Quality,Campaign Name,Stage,Sales Agent,Lead Time\n"
            ."Omar,01001112223,Hot,Ramadan Offer,Contacted,Sara Ali,2026-10-01 09:30\n"
            ."Mona,+20 100 555 0000,Warm,Ramadan Offer,New,,\n"
            ."Omar again,0100 111 2223,Cold,,,,\n"
            ."Hany,01007778889,ساخن,Unknown Campaign,Won,,\n";

        $this->actingAs($manager)->post('/admin/operations/crm/import', ['file' => UploadedFile::fake()->createWithContent('leads.csv', $csv)])->assertRedirect();
        $token = basename(collect(Storage::disk('local')->files('crm-imports'))->sole(), '.json');

        $this->actingAs($manager)->get("/admin/operations/crm/import/{$token}")->assertOk()->assertSee('Omar')->assertSee(__('مكرر'));

        $map = ['title', 'lead_phone', 'lead_quality', 'campaign', 'status', 'assignee', 'lead_date'];
        $this->actingAs($manager)->post("/admin/operations/crm/import/{$token}", ['map' => $map, 'source' => 'facebook', 'skip_sla' => '1'])->assertRedirect('/admin/operations/crm/import')->assertSessionHas('import_result');

        $result = session('import_result');
        $this->assertSame(2, $result['created']);
        $this->assertSame(2, $result['duplicates']);
        $this->assertSame([], $result['failed']);
        $omar = DB::table('operating_records')->where('type', 'opportunities')->where('title', 'Omar')->sole();
        $omarData = json_decode($omar->data, true);
        $this->assertSame(['contacted', 'hot', $campaign, $sara, 'facebook', '2026-10-01T09:30'], [$omar->status, $omarData['lead_quality'], $omarData['campaign_id'], $omarData['assignee_id'], $omarData['lead_source'], $omarData['lead_date']]);
        $this->assertEmpty($omarData['sla_due_at'] ?? null);
        $hany = DB::table('operating_records')->where('title', 'Hany')->sole();
        $this->assertSame('new', $hany->status);
        $this->assertSame('hot', json_decode($hany->data, true)['lead_quality']);
        $this->assertNotEmpty(array_filter($result['warnings'], fn (array $warning): bool => $warning['row'] === 5));
        $this->assertTrue(DB::table('operating_events')->where('action', 'import_leads')->exists());
        $this->assertSame([], Storage::disk('local')->files('crm-imports'));
    }

    public function test_reps_cannot_import_and_tokens_belong_to_their_uploader(): void
    {
        $this->actingAs($this->operatingUser('sales'))->post('/admin/operations/crm/import', ['file' => UploadedFile::fake()->createWithContent('a.csv', "Name\nA\n")])->assertForbidden();

        $manager = $this->operatingUser('sales_manager');
        $this->actingAs($manager)->post('/admin/operations/crm/import', ['file' => UploadedFile::fake()->createWithContent('a.csv', "Name,Phone\nA,0100\n")]);
        $token = basename(collect(Storage::disk('local')->files('crm-imports'))->sole(), '.json');

        $this->actingAs($this->operatingUser('sales_manager'))->get("/admin/operations/crm/import/{$token}")->assertForbidden();
    }

    public function test_oversized_or_unmapped_files_are_refused(): void
    {
        $manager = $this->operatingUser('sales_manager');
        $rows = "Name,Phone\n".str_repeat("A,0100\n", 2001);

        $this->actingAs($manager)->post('/admin/operations/crm/import', ['file' => UploadedFile::fake()->createWithContent('big.csv', $rows)])->assertSessionHasErrors('workflow');

        $this->actingAs($manager)->post('/admin/operations/crm/import', ['file' => UploadedFile::fake()->createWithContent('a.csv', "Name,Phone\nA,0100\n")]);
        $token = basename(collect(Storage::disk('local')->files('crm-imports'))->sole(), '.json');
        $this->actingAs($manager)->post("/admin/operations/crm/import/{$token}", ['map' => ['country', null], 'source' => 'other'])->assertSessionHasErrors('map');
    }
}
```

> الصف 3 (Mona) مكرر مع سجل موجود، والصف 4 (Omar again) مكرر مع الصف 2 داخل الملف. الصف 5 (Hany): `Won` يرفضه الـgate (لا عميل ولا need)، فيبقى `new` مع تحذير، والحملة "Unknown Campaign" غير موجودة فتظهر كتحذير آخر.

- [ ] **Step 2:** Run → FAIL.

- [ ] **Step 3: `app/Services/OperatingLeadImport.php`**:

```php
<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class OperatingLeadImport
{
    public const MAX_ROWS = 2000;

    /**
     * Importable lead fields; key => Arabic label key for __().
     */
    public const FIELDS = [
        'title' => 'اسم العميل', 'lead_company' => 'الشركة', 'lead_contact' => 'جهة الاتصال', 'lead_phone' => 'الهاتف', 'lead_email' => 'البريد الإلكتروني',
        'country' => 'الدولة', 'business_profile' => 'نوع النشاط', 'lead_quality' => 'جودة العميل', 'campaign' => 'الحملة', 'ad_creative' => 'الإعلان',
        'status' => 'المرحلة', 'notes' => 'ملاحظات', 'service_introduced' => 'الخدمة المقدمة', 'media_buyer_notes' => 'ملاحظات الميديا باير',
        'lead_date' => 'تاريخ دخول العميل', 'assignee' => 'المسؤول', 'lead_source' => 'المصدر',
    ];

    /**
     * Header words recognised per field; matching is done on normalize()d text, exact first, then the longest contained word.
     */
    public const SYNONYMS = [
        'title' => ['client name', 'lead name', 'full name', 'name', 'اسم العميل', 'الاسم', 'اسم', 'العميل'],
        'lead_company' => ['company', 'company name', 'الشركة', 'اسم الشركة', 'شركة'],
        'lead_contact' => ['contact person', 'contact name', 'جهة الاتصال', 'الشخص المسؤول'],
        'lead_phone' => ['phone', 'phone number', 'mobile', 'whatsapp', 'رقم الهاتف', 'الهاتف', 'هاتف', 'جوال', 'موبايل', 'رقم', 'واتساب'],
        'lead_email' => ['email', 'e-mail', 'البريد', 'البريد الإلكتروني', 'ايميل'],
        'country' => ['country', 'الدولة', 'البلد', 'دولة'],
        'business_profile' => ['business type', 'business profile', 'industry', 'نوع النشاط', 'النشاط', 'القطاع', 'نشاط'],
        'lead_quality' => ['lead quality', 'quality', 'جودة العميل', 'الجودة', 'جودة', 'نوعية'],
        'campaign' => ['campaign', 'campaign name', 'الحملة', 'اسم الحملة', 'حملة'],
        'ad_creative' => ['ad creative', 'creative', 'ad', 'الإعلان', 'إعلان'],
        'status' => ['stage', 'status', 'lead stage', 'المرحلة', 'الحالة', 'مرحلة'],
        'media_buyer_notes' => ['media buyer notes', 'ملاحظات ميديا باير', 'ملاحظات الميديا باير'],
        'service_introduced' => ['service introduced', 'service', 'الخدمة المقدمة', 'الخدمة', 'خدمة'],
        'notes' => ['notes', 'note', 'comments', 'ملاحظات', 'ملاحظة', 'تعليق'],
        'lead_date' => ['lead time', 'lead date', 'date', 'created at', 'وقت العميل', 'تاريخ', 'الوقت', 'وقت'],
        'assignee' => ['sales agent', 'agent', 'owner', 'assigned to', 'المسؤول', 'مسؤول', 'موظف', 'مندوب'],
        'lead_source' => ['lead source', 'source', 'المصدر', 'مصدر'],
    ];

    private const QUALITY_ALIASES = ['hot' => 'hot', 'ساخن' => 'hot', 'warm' => 'warm', 'دافئ' => 'warm', 'cold' => 'cold', 'بارد' => 'cold', 'bad' => 'bad', 'سيئ' => 'bad', 'سيء' => 'bad', 'unknown' => 'unknown', 'غير محدد' => 'unknown'];

    private const STAGE_ALIASES = ['new lead' => 'new', 'meeting scheduled' => 'meeting', 'offer sent' => 'proposal', 'proposal delivered' => 'proposal', 'follow up' => 'contacted', 'contact again' => 'deferred', 'not fit' => 'unsuitable', 'متابعة' => 'contacted', 'تم إرسال العرض' => 'proposal', 'غير مناسب' => 'unsuitable'];

    private const TEXT_FIELDS = ['lead_company', 'lead_contact', 'lead_phone', 'lead_email', 'country', 'business_profile', 'ad_creative', 'notes', 'service_introduced', 'media_buyer_notes'];

    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access, private OperatingCrm $crm, private OperatingSales $sales, private OperatingWorkflow $workflow, private Spreadsheet $spreadsheet) {}

    public function stage(User $user, UploadedFile $file): string
    {
        try {
            $rows = $this->spreadsheet->read($file->getRealPath(), $file->getClientOriginalExtension());
        } catch (RuntimeException $exception) {
            $encoding = $exception->getMessage() === 'encoding';
            $this->workflow->fail($encoding ? 'احفظ ملف CSV بترميز UTF-8 أو ارفعه بصيغة xlsx.' : 'تعذرت قراءة الملف. تأكد أنه CSV أو xlsx سليم.', $encoding ? 'Save the CSV as UTF-8 or upload it as xlsx.' : 'The file could not be read. Make sure it is a valid CSV or xlsx.');
        }
        if (count($rows) < 2) {
            $this->workflow->fail('الملف لا يحتوي صفوف بيانات بعد صف العناوين.', 'The file has no data rows after the header.');
        }
        if (count($rows) - 1 > self::MAX_ROWS) {
            $this->workflow->fail('الحد الأقصى '.self::MAX_ROWS.' صف في الملف الواحد. قسّم الملف.', 'The limit is '.self::MAX_ROWS.' rows per file. Split the file.');
        }
        $token = (string) Str::uuid();
        $header = array_shift($rows);
        Storage::disk('local')->put('crm-imports/'.$token.'.json', json_encode(['user_id' => $user->id, 'file' => $file->getClientOriginalName(), 'header' => $header, 'rows' => $rows], JSON_UNESCAPED_UNICODE));

        return $token;
    }

    /**
     * @return array{user_id: int, file: string, header: list<string>, rows: list<list<string>>}
     */
    public function load(User $user, string $token): array
    {
        abort_unless(Str::isUuid($token) && Storage::disk('local')->exists('crm-imports/'.$token.'.json'), 404);
        $staged = json_decode((string) Storage::disk('local')->get('crm-imports/'.$token.'.json'), true);
        abort_unless((int) $staged['user_id'] === $user->id, 403);

        return $staged;
    }

    public function normalize(string $text): string
    {
        $text = strtr(mb_strtolower(trim($text)), ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ة' => 'ه', 'ى' => 'ي', 'ئ' => 'ي', 'ؤ' => 'و', 'ـ' => '']);

        return (string) preg_replace('/[\s_\-\/\.:]+/u', '', $text);
    }

    /**
     * @param  list<string>  $header
     * @return array<int, string|null>
     */
    public function guessMapping(array $header): array
    {
        $words = [];
        foreach (self::SYNONYMS as $field => $list) {
            foreach ($list as $word) {
                $words[$field][] = $this->normalize($word);
            }
        }
        $mapping = array_fill(0, count($header), null);
        $used = [];
        foreach ([true, false] as $exact) {
            foreach ($header as $index => $title) {
                $name = $this->normalize((string) $title);
                if ($mapping[$index] !== null || $name === '') {
                    continue;
                }
                $best = null;
                $length = 0;
                foreach ($words as $field => $list) {
                    if (isset($used[$field])) {
                        continue;
                    }
                    foreach ($list as $word) {
                        $hit = $exact ? $name === $word : (mb_strlen($word) >= 3 && str_contains($name, $word));
                        if ($hit && mb_strlen($word) > $length) {
                            [$best, $length] = [$field, mb_strlen($word)];
                        }
                    }
                }
                if ($best) {
                    $mapping[$index] = $best;
                    $used[$best] = true;
                }
            }
        }

        return $mapping;
    }

    /**
     * @param  array<int, string|null>  $mapping
     * @param  array{source: string, campaign_id: ?int, assignee_id: ?int, skip_sla: bool}  $options
     * @return array{rows: list<array<string, mixed>>, total: int, duplicates: int}
     */
    public function preview(User $user, string $token, array $mapping, array $options, int $limit = 50): array
    {
        $staged = $this->load($user, $token);
        $lookups = $this->lookups($user);
        $known = $this->knownPhones();
        $titles = array_flip($lookups['campaigns']) + array_flip($lookups['employees']);
        $rows = [];
        $duplicates = 0;
        foreach ($staged['rows'] as $index => $row) {
            $item = $this->convert($row, $mapping, $options, $lookups);
            $key = $this->crm->phoneKey($item['data']['lead_phone'] ?? null);
            $duplicate = $key !== null && isset($known[$key]);
            if ($key) {
                $known[$key] = true;
            }
            $duplicates += (int) $duplicate;
            if (count($rows) < $limit) {
                $rows[] = ['line' => $index + 2, 'title' => $item['title'], 'phone' => $item['data']['lead_phone'] ?? null, 'quality' => $item['data']['lead_quality'] ?? null, 'stage' => $this->catalog->status($item['stage']), 'campaign' => $this->titleOf($item['data']['campaign_id'] ?? null), 'assignee' => $this->titleOf($item['data']['assignee_id'] ?? null), 'duplicate' => $duplicate, 'warnings' => $item['warnings']];
            }
        }

        return ['rows' => $rows, 'total' => count($staged['rows']), 'duplicates' => $duplicates];
    }

    /**
     * Rows are saved one by one through the normal workflow, so each one is validated and audited and one bad row never blocks the rest.
     *
     * @param  array<int, string|null>  $mapping
     * @param  array{source: string, campaign_id: ?int, assignee_id: ?int, skip_sla: bool}  $options
     * @return array{created: int, duplicates: int, failed: list<array{row: int, error: string}>, warnings: list<array{row: int, warning: string}>}
     */
    public function commit(User $user, string $token, array $mapping, array $options): array
    {
        $staged = $this->load($user, $token);
        set_time_limit(300);
        $lookups = $this->lookups($user);
        $known = $this->knownPhones();
        $reason = __('استيراد من ملف').' '.$staged['file'];
        $result = ['created' => 0, 'duplicates' => 0, 'failed' => [], 'warnings' => []];
        foreach ($staged['rows'] as $index => $row) {
            $line = $index + 2;
            $item = $this->convert($row, $mapping, $options, $lookups);
            $key = $this->crm->phoneKey($item['data']['lead_phone'] ?? null);
            if ($key !== null && isset($known[$key])) {
                $result['duplicates']++;

                continue;
            }
            try {
                $lead = $this->sales->saveAs($user, 'opportunities', ['title' => mb_substr($item['title'], 0, 500), 'reason' => $reason, 'data' => $item['data']]);
            } catch (ValidationException $exception) {
                $result['failed'][] = ['row' => $line, 'error' => (string) collect($exception->errors())->flatten()->first()];

                continue;
            }
            if ($key) {
                $known[$key] = true;
            }
            $result['created']++;
            if ($options['skip_sla']) {
                $this->workflow->mark($lead, 'sla_due_at', '', 'Imported without SLA');
            }
            if ($item['stage'] !== 'new') {
                try {
                    $this->sales->transitionAs($user, $lead->id, $item['stage'], $reason);
                } catch (ValidationException $exception) {
                    $item['warnings'][] = __('بقيت المرحلة "جديد"').': '.collect($exception->errors())->flatten()->first();
                }
            }
            foreach ($item['warnings'] as $warning) {
                $result['warnings'][] = ['row' => $line, 'warning' => $warning];
            }
        }
        Storage::disk('local')->delete('crm-imports/'.$token.'.json');
        $this->workflow->event(null, 'import_leads', $reason, null, ['created' => $result['created'], 'duplicates' => $result['duplicates'], 'failed' => count($result['failed'])], $user->id);

        return $result;
    }

    /**
     * @param  list<string>  $row
     * @param  array<int, string|null>  $mapping
     * @param  array{source: string, campaign_id: ?int, assignee_id: ?int, skip_sla: bool}  $options
     * @param  array{campaigns: array<string, int>, employees: array<string, int>}  $lookups
     * @return array{title: string, data: array<string, mixed>, stage: string, warnings: list<string>}
     */
    private function convert(array $row, array $mapping, array $options, array $lookups): array
    {
        $values = [];
        foreach ($mapping as $index => $field) {
            if ($field && trim((string) ($row[$index] ?? '')) !== '') {
                $values[$field] = trim((string) $row[$index]);
            }
        }
        $warnings = [];
        $data = array_intersect_key($values, array_flip(self::TEXT_FIELDS));
        if (isset($data['lead_email']) && ! filter_var($data['lead_email'], FILTER_VALIDATE_EMAIL)) {
            $warnings[] = __('بريد غير صالح تم تجاهله').': '.$data['lead_email'];
            unset($data['lead_email']);
        }
        $data['lead_source'] = $this->optionKey('lead_source', $values['lead_source'] ?? null) ?? $options['source'];
        if (isset($values['lead_quality'])) {
            $quality = collect(self::QUALITY_ALIASES)->mapWithKeys(fn (string $key, string $alias): array => [$this->normalize($alias) => $key])->get($this->normalize($values['lead_quality']));
            $data['lead_quality'] = $quality ?? 'unknown';
            if (! $quality) {
                $warnings[] = __('جودة غير معروفة').': '.$values['lead_quality'];
            }
        }
        $data['campaign_id'] = $options['campaign_id'] ?: ($lookups['campaigns'][$this->normalize($values['campaign'] ?? '')] ?? null);
        if (! $options['campaign_id'] && isset($values['campaign']) && ! $data['campaign_id']) {
            $warnings[] = __('حملة غير موجودة').': '.$values['campaign'];
        }
        $data['assignee_id'] = isset($values['assignee']) ? ($lookups['employees'][$this->normalize($values['assignee'])] ?? null) : null;
        if (isset($values['assignee']) && ! $data['assignee_id']) {
            $warnings[] = __('مسؤول غير موجود').': '.$values['assignee'];
        }
        $data['assignee_id'] ??= $options['assignee_id'];
        if (isset($values['lead_date'])) {
            $data['lead_date'] = $this->moment($values['lead_date']);
            if (! $data['lead_date']) {
                $warnings[] = __('تاريخ غير مفهوم').': '.$values['lead_date'];
            }
        }
        $stage = 'new';
        if (isset($values['status'])) {
            $stage = $this->stageKey($values['status']) ?? 'new';
            if (! $this->stageKey($values['status'])) {
                $warnings[] = __('مرحلة غير معروفة').': '.$values['status'];
            }
        }

        return ['title' => $values['title'] ?? $values['lead_company'] ?? $values['lead_phone'] ?? __('عميل مستورد'), 'data' => array_filter($data, fn (mixed $value): bool => $value !== null && $value !== ''), 'stage' => $stage, 'warnings' => $warnings];
    }

    /**
     * @return array{campaigns: array<string, int>, employees: array<string, int>}
     */
    private function lookups(User $user): array
    {
        $index = fn ($rows): array => collect($rows)->mapWithKeys(fn (string $title, int|string $id): array => [$this->normalize($title) => (int) $id])->all();

        return [
            'campaigns' => $this->access->can($user, 'campaigns') ? $index($this->access->query($user, 'campaigns')->where('status', '!=', 'archived')->pluck('title', 'id')) : [],
            'employees' => $index($this->access->query($user, 'employees')->where('status', 'active')->pluck('title', 'id')),
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function knownPhones(): array
    {
        $known = [];
        foreach (DB::table('operating_records')->where('type', 'opportunities')->where('status', '!=', 'archived')->pluck('data') as $data) {
            $key = json_decode($data, true)['phone_key'] ?? null;
            if ($key) {
                $known[$key] = true;
            }
        }

        return $known;
    }

    private function optionKey(string $field, ?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $wanted = $this->normalize($value);
        foreach (collect($this->catalog->entity('opportunities')['fields'])->firstWhere('key', $field)['options'] as $key => $label) {
            if (in_array($wanted, [$this->normalize($key), $this->normalize($label['ar']), $this->normalize($label['en'])], true)) {
                return $key;
            }
        }

        return null;
    }

    private function stageKey(string $value): ?string
    {
        $wanted = $this->normalize($value);
        foreach (self::STAGE_ALIASES as $alias => $status) {
            if ($this->normalize($alias) === $wanted) {
                return $status;
            }
        }
        foreach ($this->catalog->entity('opportunities')['statuses'] as $status) {
            $label = config('operating.statuses.'.$status, []);
            if ($status !== 'archived' && in_array($wanted, [$this->normalize($status), $this->normalize($label['ar'] ?? ''), $this->normalize($label['en'] ?? '')], true)) {
                return $status;
            }
        }

        return null;
    }

    /**
     * Excel stores dates as day serials since 1899-12-30; text dates are parsed as written.
     */
    private function moment(string $value): ?string
    {
        try {
            if (is_numeric($value) && (float) $value > 20000 && (float) $value < 80000) {
                return CarbonImmutable::create(1899, 12, 30)->addSeconds((int) round((float) $value * 86400))->format('Y-m-d\TH:i');
            }

            return CarbonImmutable::parse($value)->format('Y-m-d\TH:i');
        } catch (Throwable) {
            return null;
        }
    }

    private function titleOf(mixed $id): ?string
    {
        return $id ? ($this->sales->titles([(int) $id])[(int) $id] ?? null) : null;
    }
}
```

> `$titles` في `preview()` غير مستخدم، احذف السطر عند التنفيذ (Pint لن يحذفه). `config('operating.statuses.x')` يعمل لأن الـconfig مصفوفة بعد `json_decode(..., true)`؛ لو كانت كائنات فاستخدم `$this->catalog->status()` مع اللغتين.
> `workflow->mark($lead, 'sla_due_at', '', …)` يحفظ نصًا فارغًا، و`slaState()` يرجع `null` لأن `! $due`.

- [ ] **Step 4: الكنترولر** — أضف `private OperatingLeadImport $import` للـconstructor (و`use App\Services\OperatingLeadImport;` و`use Illuminate\Validation\ValidationException;`)، ثم:

```php
    public function import(Request $request): Response
    {
        $this->authorizeCrm($request, manager: true);

        return $this->view('crm-import', ['result' => session('import_result'), 'fields' => OperatingLeadImport::FIELDS]);
    }

    public function uploadImport(Request $request): Response
    {
        $this->authorizeCrm($request, manager: true);
        $request->validate(['file' => 'required|file|max:5120|extensions:csv,txt,xlsx']);

        return redirect('/admin/operations/crm/import/'.$this->import->stage($request->user(), $request->file('file')));
    }

    public function mapImport(string $token, Request $request): Response
    {
        $this->authorizeCrm($request, manager: true);
        $staged = $this->import->load($request->user(), $token);
        $mapping = $request->has('map') ? $this->importMapping($request, count($staged['header'])) : $this->import->guessMapping($staged['header']);
        $options = $this->importOptions($request);

        return $this->view('crm-import-map', ['token' => $token, 'file' => $staged['file'], 'header' => $staged['header'], 'mapping' => $mapping, 'options' => $options, 'preview' => $this->import->preview($request->user(), $token, $mapping, $options), 'fields' => OperatingLeadImport::FIELDS, 'choices' => $this->leadOptions($request)]);
    }

    public function commitImport(string $token, Request $request): Response
    {
        $this->authorizeCrm($request, manager: true);
        $staged = $this->import->load($request->user(), $token);
        $result = $this->import->commit($request->user(), $token, $this->importMapping($request, count($staged['header'])), $this->importOptions($request));

        return redirect('/admin/operations/crm/import')->with('import_result', $result);
    }

    /**
     * @return array<int, string|null>
     */
    private function importMapping(Request $request, int $columns): array
    {
        $input = $request->validate(['map' => 'required|array', 'map.*' => 'nullable|in:'.implode(',', array_keys(OperatingLeadImport::FIELDS))]);
        $mapping = [];
        for ($index = 0; $index < $columns; $index++) {
            $mapping[$index] = $input['map'][$index] ?? null;
        }
        $chosen = array_values(array_filter($mapping));
        if (count($chosen) !== count(array_unique($chosen))) {
            throw ValidationException::withMessages(['map' => __('لا يمكن ربط نفس الحقل بعمودين.')]);
        }
        if (! array_intersect($chosen, ['title', 'lead_phone', 'lead_company'])) {
            throw ValidationException::withMessages(['map' => __('اربط عمود الاسم أو الهاتف أو الشركة على الأقل.')]);
        }

        return $mapping;
    }

    /**
     * @return array{source: string, campaign_id: ?int, assignee_id: ?int, skip_sla: bool}
     */
    private function importOptions(Request $request): array
    {
        $sources = array_keys(collect($this->catalog->entity('opportunities')['fields'])->firstWhere('key', 'lead_source')['options']);
        $input = $request->validate(['source' => 'nullable|in:'.implode(',', $sources), 'campaign_id' => 'nullable|integer', 'assignee_id' => 'nullable|integer', 'skip_sla' => 'nullable|boolean']);

        return ['source' => $input['source'] ?? 'other', 'campaign_id' => isset($input['campaign_id']) ? (int) $input['campaign_id'] : null, 'assignee_id' => isset($input['assignee_id']) ? (int) $input['assignee_id'] : null, 'skip_sla' => $request->has('map') ? (bool) ($input['skip_sla'] ?? false) : true];
    }
```

> `campaign_id` و`assignee_id` يُتحقق منهما داخل `saveAs` (relation موجود، غير مؤرشف، يراه المستخدم)، فلا حاجة لتكرار الفحص.

- [ ] **Step 5: المسارات** (قبل `crm/leads`):

```php
        Route::get('/operations/crm/import', [C::class, 'import']);
        Route::post('/operations/crm/import', [C::class, 'uploadImport']);
        Route::get('/operations/crm/import/{token}', [C::class, 'mapImport'])->whereUuid('token');
        Route::post('/operations/crm/import/{token}', [C::class, 'commitImport'])->whereUuid('token');
```

- [ ] **Step 6: صفحة الرفع** — `resources/views/admin/operating/crm-import.blade.php`:

```blade
@extends('admin.layout')
@section('title',__('استيراد العملاء'))
@section('content')
<div class="page-heading"><div><span class="eyebrow">CRM</span><h1>{{ __('استيراد العملاء') }}</h1><p class="muted">{{ __('ارفع ملف Excel أو CSV، وسنربط الأعمدة تلقائيًا ثم تعاين قبل التنفيذ.') }}</p></div></div>
@include('admin.operating.crm-nav')
<ol class="stepper"><li class="current">{{ __('رفع الملف') }}</li><li>{{ __('ربط الأعمدة') }}</li><li>{{ __('معاينة') }}</li><li class="{{ $result?'current':'' }}">{{ __('اكتمل') }}</li></ol>
@if($result)<section class="card"><h2>{{ __('اكتمل الاستيراد') }}</h2><div class="kpi-grid"><div class="kpi"><strong>{{ $result['created'] }}</strong><span>{{ __('عملاء جدد') }}</span></div><div class="kpi"><strong>{{ $result['duplicates'] }}</strong><span>{{ __('تكرارات تم تخطيها') }}</span></div><div class="kpi {{ $result['failed']?'warn':'' }}"><strong>{{ count($result['failed']) }}</strong><span>{{ __('صفوف مرفوضة') }}</span></div></div>
@if($result['failed'])<h3>{{ __('الصفوف المرفوضة') }}</h3><ul>@foreach($result['failed'] as $row)<li>{{ __('صف') }} {{ $row['row'] }}: {{ $row['error'] }}</li>@endforeach</ul>@endif
@if($result['warnings'])<details><summary>{{ __('ملاحظات على الصفوف') }} ({{ count($result['warnings']) }})</summary><ul>@foreach($result['warnings'] as $row)<li>{{ __('صف') }} {{ $row['row'] }}: {{ $row['warning'] }}</li>@endforeach</ul></details>@endif
<a class="button" href="/admin/operations/crm/leads">{{ __('عرض العملاء') }}</a></section>@endif
<form method="post" action="/admin/operations/crm/import" enctype="multipart/form-data" class="card upload-form">@csrf<label>{{ __('الملف') }} (.xlsx · .csv)<input type="file" name="file" accept=".xlsx,.csv,.txt" required></label><button class="button">{{ __('رفع ومتابعة') }}</button>
<p class="help">{{ __('الحد الأقصى 2000 صف و5MB. الأعمدة المدعومة:') }} {{ collect($fields)->map(fn($label)=>__($label))->implode('، ') }}</p></form>
@endsection
```

- [ ] **Step 7: صفحة الربط والمعاينة** — `resources/views/admin/operating/crm-import-map.blade.php`:

```blade
@extends('admin.layout')
@section('title',__('ربط الأعمدة'))
@section('content')
<div class="page-heading"><div><span class="eyebrow">CRM · {{ $file }}</span><h1>{{ __('ربط الأعمدة والمعاينة') }}</h1><p class="muted">{{ $preview['total'] }} {{ __('صف') }} · {{ $preview['duplicates'] }} {{ __('مكرر سيتم تخطيه') }}</p></div></div>
<ol class="stepper"><li class="done">{{ __('رفع الملف') }}</li><li class="current">{{ __('ربط الأعمدة') }}</li><li class="current">{{ __('معاينة') }}</li><li>{{ __('اكتمل') }}</li></ol>
<form method="get" class="card" id="import-form"><h2>{{ __('ربط الأعمدة') }}</h2><div class="field-grid">@foreach($header as $index=>$title)<label>{{ $title ?: __('عمود').' '.($index+1) }}<select name="map[{{ $index }}]"><option value="">{{ __('تجاهل هذا العمود') }}</option>@foreach($fields as $key=>$label)<option value="{{ $key }}" @selected(($mapping[$index] ?? null)===$key)>{{ __($label) }}</option>@endforeach</select></label>@endforeach</div>
<h2>{{ __('إعدادات التوزيع') }}</h2><div class="field-grid">
<label>{{ __('المصدر الافتراضي') }}<select name="source">@foreach($choices['source'] as $key=>$label)<option value="{{ $key }}" @selected($options['source']===$key)>{{ $label }}</option>@endforeach</select></label>
<label>{{ __('الحملة الإعلانية') }}<select name="campaign_id"><option value="">{{ __('من عمود الملف') }}</option>@foreach($choices['campaigns'] as $id=>$name)<option value="{{ $id }}" @selected($options['campaign_id']===(int)$id)>{{ $name }}</option>@endforeach</select></label>
<label>{{ __('توزيع العملاء') }}<select name="assignee_id"><option value="">{{ __('من عمود الملف أو التوزيع الدوري') }}</option>@foreach($choices['assignees'] as $id=>$name)<option value="{{ $id }}" @selected($options['assignee_id']===(int)$id)>{{ $name }}</option>@endforeach</select></label>
<label class="checkbox"><input type="checkbox" name="skip_sla" value="1" @checked($options['skip_sla'])> {{ __('لا تطبّق SLA على العملاء المستوردين (بيانات قديمة)') }}</label></div>
<div class="row-actions"><button class="button secondary">{{ __('تحديث المعاينة') }}</button><button class="button" formmethod="post" formaction="/admin/operations/crm/import/{{ $token }}">{{ __('بدء الاستيراد') }}</button></div>@csrf</form>
<section class="card table-card crm-table-wrap"><h2>{{ __('معاينة البيانات') }} <small class="muted">({{ __('أول 50 صف') }})</small></h2><table><thead><tr><th>#</th><th>{{ __('اسم العميل') }}</th><th>{{ __('الهاتف') }}</th><th>{{ __('جودة العميل') }}</th><th>{{ __('المرحلة') }}</th><th>{{ __('الحملة') }}</th><th>{{ __('المسؤول') }}</th><th>{{ __('ملاحظات') }}</th></tr></thead><tbody>@foreach($preview['rows'] as $row)<tr class="{{ $row['duplicate']?'muted':'' }}"><td>{{ $row['line'] }}</td><td>{{ $row['title'] }}</td><td dir="ltr">{{ $row['phone'] ?? '—' }}</td><td>{{ $row['quality'] ?? '—' }}</td><td>{{ $row['stage'] }}</td><td>{{ $row['campaign'] ?? '—' }}</td><td>{{ $row['assignee'] ?? '—' }}</td><td>@if($row['duplicate'])<span class="badge archived">{{ __('مكرر') }}</span>@endif {{ implode(' · ',$row['warnings']) }}</td></tr>@endforeach</tbody></table>
@if($preview['total']>count($preview['rows']))<p class="muted">… {{ __('و') }} {{ $preview['total']-count($preview['rows']) }} {{ __('سجل آخر') }}</p>@endif</section>
@endsection
```

> `@csrf` داخل form الـGET يضيف `_token` للـquery string عند "تحديث المعاينة"، وهو غير ضار. زر "بدء الاستيراد" يرسل نفس الحقول POST.

- [ ] **Step 8: الترجمات**

```json
"استيراد العملاء": "Import leads",
"ارفع ملف Excel أو CSV، وسنربط الأعمدة تلقائيًا ثم تعاين قبل التنفيذ.": "Upload an Excel or CSV file. Columns are mapped automatically and you preview before importing.",
"رفع الملف": "Upload file",
"ربط الأعمدة": "Map columns",
"معاينة": "Preview",
"اكتمل": "Done",
"اكتمل الاستيراد": "Import complete",
"عملاء جدد": "New leads",
"تكرارات تم تخطيها": "Duplicates skipped",
"صفوف مرفوضة": "Rejected rows",
"الصفوف المرفوضة": "Rejected rows",
"صف": "Row",
"ملاحظات على الصفوف": "Row notes",
"عرض العملاء": "View leads",
"الملف": "File",
"رفع ومتابعة": "Upload and continue",
"الحد الأقصى 2000 صف و5MB. الأعمدة المدعومة:": "Up to 2,000 rows and 5MB. Supported columns:",
"ربط الأعمدة والمعاينة": "Map columns and preview",
"مكرر سيتم تخطيه": "duplicates will be skipped",
"عمود": "Column",
"تجاهل هذا العمود": "Skip this column",
"إعدادات التوزيع": "Assignment settings",
"المصدر الافتراضي": "Default source",
"الحملة الإعلانية": "Campaign",
"من عمود الملف": "From the file column",
"توزيع العملاء": "Lead assignment",
"من عمود الملف أو التوزيع الدوري": "From the file column or round robin",
"لا تطبّق SLA على العملاء المستوردين (بيانات قديمة)": "Do not apply SLA to imported leads (historical data)",
"تحديث المعاينة": "Refresh preview",
"بدء الاستيراد": "Start import",
"معاينة البيانات": "Data preview",
"أول 50 صف": "first 50 rows",
"مكرر": "Duplicate",
"و": "and",
"سجل آخر": "more records",
"جهة الاتصال": "Contact",
"البريد الإلكتروني": "Email",
"الخدمة المقدمة": "Service introduced",
"ملاحظات الميديا باير": "Media buyer notes",
"تاريخ دخول العميل": "Lead date",
"استيراد من ملف": "Imported from file",
"بقيت المرحلة \"جديد\"": "Stage kept as \"New\"",
"بريد غير صالح تم تجاهله": "Invalid email ignored",
"جودة غير معروفة": "Unknown quality",
"حملة غير موجودة": "Campaign not found",
"مسؤول غير موجود": "Owner not found",
"تاريخ غير مفهوم": "Unreadable date",
"مرحلة غير معروفة": "Unknown stage",
"عميل مستورد": "Imported lead",
"لا يمكن ربط نفس الحقل بعمودين.": "A field can only be mapped to one column.",
"اربط عمود الاسم أو الهاتف أو الشركة على الأقل.": "Map at least the name, phone or company column."
```

- [ ] **Step 9:** Run: `$PHP artisan test --compact tests/Feature/Operating/CrmImportTest.php` → PASS.

> لو فشل `extensions:` (القاعدة موجودة منذ Laravel 11)، تحقق من رسالة الخطأ. البديل: `mimes:csv,txt,xlsx,zip`.

- [ ] **Step 10: Pint + Commit**

```bash
$PHP vendor/bin/pint --dirty --format agent
git add app/Services/OperatingLeadImport.php app/Http/Controllers/CrmController.php routes/admin.php resources/views/admin/operating/crm-import.blade.php resources/views/admin/operating/crm-import-map.blade.php lang/en.json tests/Feature/Operating/CrmImportTest.php
git commit -m "feat(crm): lead import from Excel/CSV with auto column mapping, preview, duplicates and audit

Co-Authored-By: Claude Opus 5.5 (1M context) <noreply@anthropic.com>"
```

---

### Task 16: سلة المحذوفات والاستعادة

**Files:**
- Modify: `app/Services/OperatingWorkflow.php` (دالة `restore()` جديدة)
- Modify: `app/Http/Controllers/OperatingController.php` (endpoint `restore`)
- Modify: `app/Http/Controllers/CrmController.php` (`trash`)
- Create: `resources/views/admin/operating/crm-trash.blade.php`
- Modify: `routes/admin.php`، `lang/en.json`
- Test: `tests/Feature/Operating/CrmTrashTest.php`

**Interfaces:**
- Consumes: `archived_from` (يُكتب عند الأرشفة منذ T1)، `OperatingCrm::{RESTORABLE, MANAGERS}`.
- Produces:
  - `OperatingWorkflow::restore(int $id, Request $request): object` (المدخل: `version`، `reason`). owner/sales_manager فقط، النوع ضمن `RESTORABLE`، والحالة `archived`. يعيد الحالة إلى `archived_from` (أو أول حالة للكيان لو مفقودة)، يحذف `archived_from`، يرفع النسخة، ويكتب event `restore`.
  - مسارات: `POST /admin/operations/record/{id}/restore`، `GET /admin/operations/crm/trash?type=`.

- [ ] **Step 1: الاختبار الفاشل** — `tests/Feature/Operating/CrmTrashTest.php`:

```php
<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmTrashTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_archiving_lands_in_the_trash_and_restore_returns_the_previous_stage(): void
    {
        $owner = $this->operatingUser('owner');
        $lead = $this->operatingRecord('opportunities', 'meeting', ['lead_company' => 'Nile Foods'], 'Nile Foods');

        $this->actingAs($owner)->post("/admin/operations/record/{$lead}/transition", ['target' => 'archived', 'version' => 1, 'reason' => 'Duplicate entry'])->assertSessionHasNoErrors();
        $this->assertSame('meeting', $this->recordData($lead)['archived_from']);

        $manager = $this->operatingUser('sales_manager');
        $this->actingAs($manager)->get('/admin/operations/crm/trash')->assertOk()->assertSee('Nile Foods')->assertSee('Duplicate entry');
        $this->actingAs($manager)->post("/admin/operations/record/{$lead}/restore", ['version' => 2, 'reason' => 'Not a duplicate'])->assertSessionHasNoErrors();

        $record = DB::table('operating_records')->find($lead);
        $this->assertSame(['meeting', 3], [$record->status, $record->version]);
        $this->assertArrayNotHasKey('archived_from', json_decode($record->data, true));
        $this->assertTrue(DB::table('operating_events')->where('record_id', $lead)->where('action', 'restore')->where('reason', 'Not a duplicate')->exists());
    }

    public function test_only_managers_restore_and_only_archived_restorable_records(): void
    {
        $lead = $this->operatingRecord('opportunities', 'archived', ['lead_company' => 'Nile Foods', 'archived_from' => 'new'], 'Nile Foods');
        $open = $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Open'], 'Open');
        $project = $this->operatingRecord('projects', 'archived', [], 'Old project');
        $rep = $this->operatingUser('sales');

        $this->actingAs($rep)->get('/admin/operations/crm/trash')->assertForbidden();
        $this->actingAs($rep)->post("/admin/operations/record/{$lead}/restore", ['version' => 1, 'reason' => 'x'])->assertForbidden();
        $owner = $this->operatingUser('owner');
        $this->actingAs($owner)->post("/admin/operations/record/{$open}/restore", ['version' => 1, 'reason' => 'x'])->assertNotFound();
        $this->actingAs($owner)->post("/admin/operations/record/{$project}/restore", ['version' => 1, 'reason' => 'x'])->assertNotFound();
        $this->actingAs($owner)->post("/admin/operations/record/{$lead}/restore", ['version' => 9, 'reason' => 'x'])->assertSessionHasErrors('workflow');
    }
}
```

- [ ] **Step 2:** Run → FAIL.

- [ ] **Step 3: `OperatingWorkflow::restore()`** — أضف بعد `transition()`:

```php
    /**
     * Bring an archived sales record back to the stage it was archived from; nothing is ever deleted, so this is the trash's only exit.
     */
    public function restore(int $id, Request $request): object
    {
        $user = $request->user();
        abort_unless(in_array($user->role, OperatingCrm::MANAGERS, true), 403);
        $input = $request->validate(['version' => 'required|integer', 'reason' => 'required|string|max:2000']);
        $record = $this->access->record($user, $id, 'write');
        abort_unless(in_array($record->type, OperatingCrm::RESTORABLE, true) && $record->status === 'archived', 404);

        return DB::transaction(function () use ($record, $input, $user): object {
            if (DB::getDriverName() === 'pgsql') {
                DB::select("SELECT pg_advisory_xact_lock(hashtext('findo-operating-gates'))");
            }
            $locked = DB::table('operating_records')->where('id', $record->id)->lockForUpdate()->first();
            if ($locked->version != $input['version']) {
                $this->fail('السجل تغير. حدّث الصفحة.', 'Record changed. Refresh first.');
            }
            $data = $this->catalog->data($locked);
            $target = $data['archived_from'] ?? $this->catalog->entity($locked->type)['statuses'][0];
            unset($data['archived_from']);
            DB::table('operating_records')->where('id', $locked->id)->update(['status' => $target, 'data' => json_encode($data, JSON_UNESCAPED_UNICODE), 'version' => $locked->version + 1, 'updated_at' => now()]);
            $next = DB::table('operating_records')->find($locked->id);
            $this->event($next, 'restore', $input['reason'], (array) $locked, (array) $next, $user->id);

            return $next;
        });
    }
```

- [ ] **Step 4: endpoint** — في `OperatingController` بعد `transition()`:

```php
    public function restore(int $id, Request $request): Response
    {
        $this->workflow->restore($id, $request);

        return back()->with('success', __('تمت استعادة السجل إلى حالته السابقة.'));
    }
```

- [ ] **Step 5: صفحة السلة** — في `CrmController`:

```php
    public function trash(Request $request): Response
    {
        $this->authorizeCrm($request, manager: true);
        $types = array_values(array_filter(OperatingCrm::RESTORABLE, fn (string $type): bool => $this->access->can($request->user(), $type)));
        $type = in_array($request->input('type'), $types, true) ? $request->input('type') : null;
        $page = DB::table('operating_records')->whereIn('type', $type ? [$type] : $types)->where('status', 'archived')->latest('updated_at')->paginate(30)->withQueryString();
        $archivedBy = DB::table('operating_events')->leftJoin('users', 'users.id', '=', 'operating_events.user_id')->whereIn('record_id', collect($page->items())->pluck('id'))->where('action', 'transition')->where('after', 'like', '%"status":"archived"%')->orderBy('operating_events.id')->get(['record_id', 'operating_events.created_at', 'reason', 'users.name'])->keyBy('record_id');
        $counts = DB::table('operating_records')->whereIn('type', $types)->where('status', 'archived')->groupBy('type')->selectRaw('type, count(*) as total')->pluck('total', 'type');

        return $this->view('crm-trash', ['page' => $page, 'archivedBy' => $archivedBy, 'counts' => $counts, 'types' => $types, 'type' => $type]);
    }
```

> `keyBy('record_id')` على قائمة مرتبة تصاعديًا يحتفظ بآخر أرشفة لكل سجل. الـLIKE يطابق `json_encode` الافتراضي (`"status":"archived"` بلا مسافات) المستخدم في `event()`. **صلاحيات الرؤية:** owner وsales_manager يرون كل سجلات هذه الأنواع أصلًا (`OperatingAccess::query`)، فالاستعلام المباشر لا يكشف شيئًا إضافيًا.

- [ ] **Step 6: المسارات**

```php
        Route::post('/operations/record/{id}/restore', [O::class, 'restore'])->whereNumber('id');
```
(بجوار مسار `/operations/record/{id}/transition`)، و:

```php
        Route::get('/operations/crm/trash', [C::class, 'trash']);
```

- [ ] **Step 7: الـview** — `resources/views/admin/operating/crm-trash.blade.php`:

```blade
@extends('admin.layout')
@section('title',__('سلة المحذوفات'))
@section('content')
<div class="page-heading"><div><span class="eyebrow">CRM</span><h1>{{ __('سلة المحذوفات') }}</h1><p class="muted">{{ __('السجلات المؤرشفة محفوظة هنا ويمكن استعادتها إلى حالتها السابقة. لا يوجد حذف نهائي في النظام.') }}</p></div></div>
@include('admin.operating.crm-nav')
<nav class="crm-tabs"><a href="/admin/operations/crm/trash" class="{{ $type?'':'active' }}">{{ __('الكل') }} <b>{{ $counts->sum() }}</b></a>@foreach($types as $key)<a href="?type={{ $key }}" class="{{ $type===$key?'active':'' }}">{{ $catalog->label($catalog->entity($key)['label']) }} <b>{{ $counts[$key] ?? 0 }}</b></a>@endforeach</nav>
<section class="card table-card crm-table-wrap"><table><thead><tr><th>{{ __('النوع') }}</th><th>{{ __('الاسم') }}</th><th>{{ __('حُذف بواسطة') }}</th><th>{{ __('تاريخ الحذف') }}</th><th>{{ __('السبب') }}</th><th>{{ __('الإجراءات') }}</th></tr></thead><tbody>
@forelse($page as $record)@php $by=$archivedBy[$record->id] ?? null; $from=$catalog->data($record)['archived_from'] ?? null; @endphp<tr><td>{{ $catalog->label($catalog->entity($record->type)['label']) }}</td><td><a href="/admin/operations/record/{{ $record->id }}">{{ $record->title }}</a></td><td>{{ $by?->name ?? '—' }}</td><td>{{ $by ? substr((string)$by->created_at,0,16) : substr((string)$record->updated_at,0,16) }}</td><td>{{ $by?->reason ?? '—' }}</td>
<td><form method="post" action="/admin/operations/record/{{ $record->id }}/restore" class="inline-form">@csrf<input type="hidden" name="version" value="{{ $record->version }}"><input name="reason" required placeholder="{{ __('سبب الاستعادة') }}"><button class="button small">{{ __('استعادة') }}@if($from) ← {{ $catalog->status($from) }}@endif</button></form></td></tr>
@empty<tr><td colspan="6" class="muted">{{ __('سلة المحذوفات فارغة') }}</td></tr>@endforelse</tbody></table></section>
<div class="pagination">{{ $page->links() }}</div>
@endsection
```

- [ ] **Step 8: الترجمات**

```json
"سلة المحذوفات": "Trash",
"السجلات المؤرشفة محفوظة هنا ويمكن استعادتها إلى حالتها السابقة. لا يوجد حذف نهائي في النظام.": "Archived records are kept here and can be restored to their previous stage. Nothing is permanently deleted.",
"النوع": "Type",
"الاسم": "Name",
"حُذف بواسطة": "Deleted by",
"تاريخ الحذف": "Deleted at",
"الإجراءات": "Actions",
"سبب الاستعادة": "Reason for restoring",
"استعادة": "Restore",
"سلة المحذوفات فارغة": "Trash is empty",
"تمت استعادة السجل إلى حالته السابقة.": "Record restored to its previous stage."
```

- [ ] **Step 9:** Run: `$PHP artisan test --compact tests/Feature/Operating/CrmTrashTest.php` → PASS. Pint + Commit:

```bash
$PHP vendor/bin/pint --dirty --format agent
git add app/Services/OperatingWorkflow.php app/Http/Controllers/OperatingController.php app/Http/Controllers/CrmController.php resources/views/admin/operating/crm-trash.blade.php routes/admin.php lang/en.json tests/Feature/Operating/CrmTrashTest.php
git commit -m "feat(crm): trash for archived sales records with audited restore to the previous stage

Co-Authored-By: Claude Opus 5.5 (1M context) <noreply@anthropic.com>"
```

---

### Task 17: سجل العمليات (Audit log)

**Files:**
- Create: `app/Http/Controllers/AuditController.php`
- Create: `resources/views/admin/operating/audit-log.blade.php`
- Modify: `routes/admin.php`، `lang/en.json`
- Test: `tests/Feature/Operating/CrmAuditLogTest.php`

**Interfaces:**
- Consumes: `operating_events`، `OperatingCrm::AUDITED_TYPES`.
- Produces:
  - `GET /admin/operations/audit-log?action=&type=&user=&from=&to=&q=` → `AuditController::index` (50 لكل صفحة).
  - `AuditController::changes(object $event): list<array{field: string, before: mixed, after: mixed}>` (private): يقارن `status` و`title` ومفاتيح `data` (المفكوكة من JSON)، وفي أحداث الإعدادات يقارن المفاتيح العليا.
- الصلاحيات (Q12): owner يرى الكل. sales_manager يرى أحداث سجلات `AUDITED_TYPES` + الأحداث بلا سجل من النوع `configure_crm`/`export_leads`/`import_leads`. غيرهما 403.

- [ ] **Step 1: الاختبار الفاشل** — `tests/Feature/Operating/CrmAuditLogTest.php`:

```php
<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmAuditLogTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_managers_see_sales_events_with_field_changes_but_not_hr_events(): void
    {
        $owner = $this->operatingUser('owner');
        $lead = $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Nile Foods', 'lead_source' => 'facebook'], 'Nile Foods');
        $this->actingAs($owner)->post("/admin/operations/record/{$lead}", ['title' => 'Nile Foods', 'version' => 1, 'reason' => 'Fix quality', 'data' => ['lead_company' => 'Nile Foods', 'lead_source' => 'facebook', 'lead_quality' => 'hot']])->assertSessionHasNoErrors();
        $employee = $this->operatingRecord('employees', 'active', ['job_title' => 'HR'], 'Secret Salary Person');
        DB::table('operating_events')->insert(['record_id' => $employee, 'user_id' => $owner->id, 'action' => 'update', 'reason' => 'Salary change', 'before' => null, 'after' => null, 'created_at' => now()]);
        DB::table('operating_events')->insert(['record_id' => null, 'user_id' => $owner->id, 'action' => 'export_leads', 'reason' => 'Weekly export', 'before' => null, 'after' => null, 'created_at' => now()]);

        $manager = $this->operatingUser('sales_manager');
        $this->actingAs($manager)->get('/admin/operations/audit-log')->assertOk()->assertSee('Fix quality')->assertSee(__('جودة العميل'))->assertSee('hot')->assertSee('Weekly export')->assertDontSee('Salary change');
        $this->actingAs($owner)->get('/admin/operations/audit-log')->assertOk()->assertSee('Salary change');
        $this->actingAs($owner)->get('/admin/operations/audit-log?type=employees')->assertSee('Salary change')->assertDontSee('Fix quality');
        $this->actingAs($owner)->get('/admin/operations/audit-log?q=Nile')->assertSee('Fix quality')->assertDontSee('Salary change');
    }

    public function test_reps_and_other_roles_are_refused(): void
    {
        $this->actingAs($this->operatingUser('sales'))->get('/admin/operations/audit-log')->assertForbidden();
        $this->actingAs($this->operatingUser('hr'))->get('/admin/operations/audit-log')->assertForbidden();
    }
}
```

- [ ] **Step 2:** Run → FAIL.

- [ ] **Step 3: `AuditController`** (`make:controller AuditController`):

```php
<?php

namespace App\Http\Controllers;

use App\Services\Cms;
use App\Services\OperatingAccess;
use App\Services\OperatingCatalog;
use App\Services\OperatingCrm;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AuditController extends Controller
{
    /**
     * Record-less events a sales manager may see: their own CRM configuration, exports and imports.
     */
    private const MANAGER_SYSTEM_ACTIONS = ['configure_crm', 'export_leads', 'import_leads'];

    /**
     * Engine bookkeeping columns that are not business changes.
     */
    private const IGNORED = ['id', 'uuid', 'type', 'owner_id', 'created_by', 'version', 'created_at', 'updated_at', 'source', 'external_id', 'synced_at'];

    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless(in_array($user->role, OperatingCrm::MANAGERS, true), 403);
        $filters = $request->validate(['action' => 'nullable|string|max:40', 'type' => 'nullable|string|max:60', 'user' => 'nullable|integer', 'from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from', 'q' => 'nullable|string|max:200']);
        $query = DB::table('operating_events as e')->leftJoin('operating_records as r', 'r.id', '=', 'e.record_id')->leftJoin('users as u', 'u.id', '=', 'e.user_id')->select('e.*', 'r.title as record_title', 'r.type as record_type', 'u.name as user_name');
        if ($user->role !== 'owner') {
            $query->where(fn (Builder $scope) => $scope->whereIn('r.type', OperatingCrm::AUDITED_TYPES)->orWhere(fn (Builder $system) => $system->whereNull('e.record_id')->whereIn('e.action', self::MANAGER_SYSTEM_ACTIONS)));
        }
        $query->when($filters['action'] ?? null, fn (Builder $scope, string $action) => $scope->where('e.action', $action))
            ->when($filters['type'] ?? null, fn (Builder $scope, string $type) => $scope->where('r.type', $type))
            ->when($filters['user'] ?? null, fn (Builder $scope, int $id) => $scope->where('e.user_id', $id))
            ->when($filters['from'] ?? null, fn (Builder $scope, string $from) => $scope->where('e.created_at', '>=', $from.' 00:00:00'))
            ->when($filters['to'] ?? null, fn (Builder $scope, string $to) => $scope->where('e.created_at', '<=', $to.' 23:59:59'))
            ->when($filters['q'] ?? null, fn (Builder $scope, string $search) => $scope->where(fn (Builder $text) => $text->where('r.title', 'like', '%'.$search.'%')->orWhere('e.reason', 'like', '%'.$search.'%')));
        $page = $query->orderByDesc('e.id')->paginate(50)->withQueryString();
        $events = collect($page->items())->map(fn (object $event): object => tap($event, fn (object $row) => $row->changes = $this->changes($row)));
        $types = $user->role === 'owner' ? array_keys($this->catalog->entities()) : OperatingCrm::AUDITED_TYPES;

        return response()->view('admin.operating.audit-log', [
            'page' => $page, 'events' => $events->groupBy(fn (object $event): string => substr((string) $event->created_at, 0, 10)), 'filters' => $filters,
            'types' => collect($types)->mapWithKeys(fn (string $type): array => [$type => $this->catalog->label($this->catalog->entity($type)['label'])])->all(),
            'actions' => DB::table('operating_events')->distinct()->orderBy('action')->pluck('action')->all(),
            'users' => DB::table('users')->orderBy('name')->pluck('name', 'id')->all(),
            'collections' => Cms::COLLECTIONS, 'catalog' => $this->catalog, 'access' => $this->access,
        ]);
    }

    /**
     * @return list<array{field: string, before: mixed, after: mixed}>
     */
    private function changes(object $event): array
    {
        $flatten = function (?string $json): array {
            $side = json_decode($json ?? 'null', true) ?: [];
            $data = is_string($side['data'] ?? null) ? (json_decode($side['data'], true) ?: []) : (is_array($side['data'] ?? null) ? $side['data'] : []);

            return array_intersect_key($side, array_flip(['status', 'title'])) + $data + array_diff_key($side, array_flip([...self::IGNORED, 'status', 'title', 'data']));
        };
        $before = $flatten($event->before);
        $after = $flatten($event->after);
        $changes = [];
        foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $key) {
            if (($before[$key] ?? null) != ($after[$key] ?? null)) {
                $changes[] = ['field' => (string) $key, 'before' => $before[$key] ?? null, 'after' => $after[$key] ?? null];
            }
        }

        return array_slice($changes, 0, 30);
    }
}
```

- [ ] **Step 4: المسار** (بعد `crm/trash`):

```php
        Route::get('/operations/audit-log', [\App\Http\Controllers\AuditController::class, 'index']);
```

(أو أضف `use App\Http\Controllers\AuditController as U;` واستخدم `[U::class, 'index']`، اتساقًا مع باقي الملف.)

- [ ] **Step 5: الـview** — `resources/views/admin/operating/audit-log.blade.php`:

```blade
@extends('admin.layout')
@section('title',__('سجل العمليات'))
@section('content')
@php $label=function(?string $type,string $field) use ($catalog){if($field==='status'){return __('الحالة');}if($field==='title'){return __('العنوان');}$entityField=$type?collect($catalog->entity($type)['fields'])->firstWhere('key',$field):null;return $entityField?$catalog->label($entityField['label']):$field;}; $show=fn($value)=>is_array($value)?json_encode($value,JSON_UNESCAPED_UNICODE):(is_bool($value)?($value?'✓':'✗'):(string)($value ?? '—')); @endphp
<div class="page-heading"><div><span class="eyebrow">{{ __('التدقيق') }}</span><h1>{{ __('سجل العمليات') }}</h1><p class="muted">{{ __('عرض زمني لكل العمليات مع التغييرات قبل وبعد. السجل غير قابل للتعديل أو الحذف.') }}</p></div></div>
<form class="card filters" method="get"><select name="action"><option value="">{{ __('كل العمليات') }}</option>@foreach($actions as $action)<option value="{{ $action }}" @selected(($filters['action']??'')===$action)>{{ $action }}</option>@endforeach</select><select name="type"><option value="">{{ __('كل الكيانات') }}</option>@foreach($types as $key=>$name)<option value="{{ $key }}" @selected(($filters['type']??'')===$key)>{{ $name }}</option>@endforeach</select><select name="user"><option value="">{{ __('كل المستخدمين') }}</option>@foreach($users as $id=>$name)<option value="{{ $id }}" @selected((int)($filters['user']??0)===(int)$id)>{{ $name }}</option>@endforeach</select><input type="date" name="from" value="{{ $filters['from'] ?? '' }}"><input type="date" name="to" value="{{ $filters['to'] ?? '' }}"><input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('بحث بالسجل أو السبب') }}"><button class="button secondary">{{ __('تصفية') }}</button><a class="text-button" href="/admin/operations/audit-log">{{ __('إعادة ضبط') }}</a></form>
@forelse($events as $day=>$dayEvents)<section class="card"><h2 dir="ltr">{{ $day }}</h2><ol class="timeline">@foreach($dayEvents as $event)<li><b>{{ $event->user_name ?? __('النظام') }}</b> · <span class="badge">{{ $event->action }}</span> @if($event->record_id)<a href="/admin/operations/record/{{ $event->record_id }}">{{ $event->record_title }}</a> <small class="muted">({{ $event->record_type ? $catalog->label($catalog->entity($event->record_type)['label']) : '' }})</small>@endif
<p>{{ $event->reason }}</p>@if($event->changes)<details><summary>{{ __('معاينة التغييرات') }} ({{ count($event->changes) }})</summary><table><thead><tr><th>{{ __('الحقل') }}</th><th>{{ __('قبل') }}</th><th>{{ __('بعد') }}</th></tr></thead><tbody>@foreach($event->changes as $change)<tr><td>{{ $label($event->record_type,$change['field']) }}</td><td>{{ \Illuminate\Support\Str::limit($show($change['before']),120) }}</td><td>{{ \Illuminate\Support\Str::limit($show($change['after']),120) }}</td></tr>@endforeach</tbody></table></details>@endif<small>{{ substr((string)$event->created_at,11,5) }}</small></li>@endforeach</ol></section>@empty<section class="card"><p class="muted">{{ __('لا توجد عمليات مطابقة.') }}</p></section>@endforelse
<div class="pagination">{{ $page->links() }}</div>
@endsection
```

> لو كان `$event->record_type` لكيان حُذف من الـconfig فإن `$catalog->entity()` قد يرمي. الكيانات لا تُحذف في هذا المشروع، لكن لو ظهر خطأ فلُفّه بـ`isset(config('operating.entities')[$type])`.

- [ ] **Step 6: الترجمات**

```json
"التدقيق": "Audit",
"سجل العمليات": "Audit log",
"عرض زمني لكل العمليات مع التغييرات قبل وبعد. السجل غير قابل للتعديل أو الحذف.": "A timeline of every operation with before and after values. The log cannot be edited or deleted.",
"كل العمليات": "All actions",
"كل الكيانات": "All entities",
"كل المستخدمين": "All users",
"بحث بالسجل أو السبب": "Search record or reason",
"النظام": "System",
"معاينة التغييرات": "Preview changes",
"الحقل": "Field",
"قبل": "Before",
"بعد": "After",
"الحالة": "Status",
"العنوان": "Title",
"لا توجد عمليات مطابقة.": "No matching operations."
```

- [ ] **Step 7:** Run → PASS. Pint + Commit:

```bash
$PHP vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/AuditController.php resources/views/admin/operating/audit-log.blade.php routes/admin.php lang/en.json tests/Feature/Operating/CrmAuditLogTest.php
git commit -m "feat(crm): audit log timeline with before/after changes, scoped for sales managers

Co-Authored-By: Claude Opus 5.5 (1M context) <noreply@anthropic.com>"
```

---

### Task 18: صندوق الوارد المصنّف + إشعار إسناد عميل جديد

**Files:**
- Modify: `app/Services/OperatingWorkflow.php` (`notifyAssignees()`)
- Modify: `app/Http/Controllers/NotificationController.php`
- Modify: `resources/views/admin/operating/notifications.blade.php`
- Modify: `lang/en.json`
- Test: `tests/Feature/Operating/CrmInboxTest.php`

**Interfaces:**
- Produces:
  - نوع إشعار `lead_assigned` (عند إسناد فرصة لموظف غير المنفّذ)، رابطه `/admin/operations/crm/leads/{id}`. الأنشطة المسندة لغيرك تأخذ `work_assigned` الموجود.
  - `NotificationController::TABS = ['all' => [], 'unread' => [], 'sla' => ['crm_sla'], 'leads' => ['lead_assigned'], 'reminders' => ['follow_up_due', 'follow_up_overdue']]` و`?tab=` على `/admin/operations/notifications`.

- [ ] **Step 1: الاختبار الفاشل** — `tests/Feature/Operating/CrmInboxTest.php`:

```php
<?php

namespace Tests\Feature\Operating;

use App\Notifications\OperatingAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmInboxTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_assigning_a_lead_to_someone_else_notifies_them_but_self_assignment_does_not(): void
    {
        $repUser = $this->operatingUser('sales');
        $rep = $this->employeeFor($repUser);

        $this->actingAs($this->operatingUser('sales_manager'))->post('/admin/operations/opportunities', ['title' => 'Nile Foods', 'reason' => 'Inbound', 'data' => ['lead_phone' => '01001234567', 'lead_source' => 'facebook', 'assignee_id' => $rep]])->assertSessionHasNoErrors();
        $this->actingAs($repUser)->post('/admin/operations/opportunities', ['title' => 'Own lead', 'reason' => 'Inbound', 'data' => ['lead_phone' => '01007654321', 'lead_source' => 'facebook']])->assertSessionHasNoErrors();

        $this->assertSame(1, $repUser->notifications()->count());
        $notification = $repUser->notifications()->first();
        $this->assertSame('lead_assigned', $notification->data['kind']);
        $this->assertStringStartsWith('/admin/operations/crm/leads/', $notification->data['url']);
    }

    public function test_tabs_filter_by_kind_and_show_counts(): void
    {
        $user = $this->operatingUser('sales');
        $user->notify(new OperatingAlert('crm_sla', ['ar' => 'SLA breach', 'en' => 'SLA breach'], ['ar' => 'Late lead', 'en' => 'Late lead'], '/x'));
        $user->notify(new OperatingAlert('lead_assigned', ['ar' => 'New lead', 'en' => 'New lead'], ['ar' => 'Omar', 'en' => 'Omar'], '/y'));

        $this->actingAs($user)->get('/admin/operations/notifications?tab=sla')->assertOk()->assertSee('Late lead')->assertDontSee('Omar');
        $this->actingAs($user)->get('/admin/operations/notifications?tab=leads')->assertSee('Omar')->assertDontSee('Late lead');
        $this->actingAs($user)->get('/admin/operations/notifications')->assertSee('Omar')->assertSee('Late lead');
    }
}
```

- [ ] **Step 2:** Run → FAIL.

- [ ] **Step 3: `notifyAssignees()`** — استبدل الدالة بـ:

```php
    private function notifyAssignees(string $type, object $record, array $before, User $user): void
    {
        $fields = ['tasks' => ['assignee_id', 'reviewer_id', 'tester_id'], 'team_tasks' => ['assignee_id'], 'opportunities' => ['assignee_id'], 'activities' => ['assignee_id']][$type] ?? [];
        $after = $this->catalog->data($record);
        $self = (int) $this->access->employeeId($user);
        foreach ($fields as $field) {
            $employee = (int) ($after[$field] ?? 0);
            if (! $employee || $employee === (int) ($before[$field] ?? 0) || $employee === $self) {
                continue;
            }
            $alert = $type === 'opportunities'
                ? new OperatingAlert('lead_assigned', ['ar' => 'عميل محتمل جديد مسند إليك', 'en' => 'New lead assigned to you'], ['ar' => 'أُسند إليك العميل "'.$record->title.'".', 'en' => 'The lead "'.$record->title.'" was assigned to you.'], '/admin/operations/crm/leads/'.$record->id)
                : new OperatingAlert('work_assigned', ['ar' => 'عمل جديد مسند إليك', 'en' => 'New work assigned to you'], ['ar' => 'أُسند إليك "'.$record->title.'".', 'en' => 'You were assigned "'.$record->title.'".'], '/admin/operations/record/'.$record->id);
            $this->notifier->send([$this->notifier->employeeUser($employee)], $alert);
        }
    }
```

> بعد هذا التغيير شغّل **كل** اختبارات `tests/Feature/Operating/` لأن اختبارات قديمة قد تعدّ الإشعارات (مثل `NotificationsTest`، `FollowUpsTest`). لو اختبار يعدّ إشعارات مدير بعد إنشاء نشاط مسند لغيره، فحدّث العدد المتوقع **مع تعليق في الـcommit message** أن الإشعار الجديد مقصود.

- [ ] **Step 4: `NotificationController::index()`**:

```php
    /**
     * Inbox tabs: the notification kinds each one collects; "all" and "unread" are not filtered by kind.
     */
    public const TABS = ['all' => [], 'unread' => [], 'sla' => ['crm_sla'], 'leads' => ['lead_assigned'], 'reminders' => ['follow_up_due', 'follow_up_overdue']];

    public function index(Request $request): Response
    {
        $tab = array_key_exists($request->input('tab'), self::TABS) ? $request->input('tab') : 'all';
        $scoped = function (string $name) use ($request) {
            $query = $request->user()->notifications();
            if ($name === 'unread') {
                $query->whereNull('read_at');
            }
            if (self::TABS[$name]) {
                $query->where(fn ($kinds) => collect(self::TABS[$name])->each(fn (string $kind) => $kinds->orWhere('data', 'like', '%"kind":"'.$kind.'"%')));
            }

            return $query;
        };

        return response()->view('admin.operating.notifications', [
            'notifications' => $scoped($tab)->latest()->paginate(30)->withQueryString(),
            'tab' => $tab,
            'counts' => collect(array_keys(self::TABS))->mapWithKeys(fn (string $name): array => [$name => $name === 'all' ? $request->user()->unreadNotifications()->count() : $scoped($name)->whereNull('read_at')->count()])->all(),
            'collections' => Cms::COLLECTIONS,
            'catalog' => $this->catalog,
            'access' => $this->access,
        ]);
    }
```

> الـLIKE يعمل لأن Laravel يخزّن `data` بـ`json_encode` بلا مسافات. العدّاد لكل تبويب = غير المقروء فيه.

- [ ] **Step 5: الـview** — في `notifications.blade.php` أضف بعد سطر `page-heading`:

```blade
<nav class="crm-tabs">@foreach(['all'=>'صندوق الوارد','unread'=>'غير مقروء','sla'=>'تنبيهات SLA','leads'=>'عملاء جدد','reminders'=>'التذكيرات'] as $key=>$label)<a href="?tab={{ $key }}" class="{{ $tab===$key?'active':'' }}">{{ __($label) }}@if($counts[$key]) <b class="count-badge">{{ $counts[$key] }}</b>@endif</a>@endforeach</nav>
```

وغيّر عنوان الصفحة إلى `__('صندوق الوارد')` في `@section('title')` و`<h1>`.

- [ ] **Step 6: الترجمات**

```json
"صندوق الوارد": "Inbox",
"غير مقروء": "Unread",
"عملاء جدد": "New leads"
```

- [ ] **Step 7:** Run: `$PHP artisan test --compact tests/Feature/Operating` → PASS (كل المجلد).

- [ ] **Step 8: Pint + Commit**

```bash
$PHP vendor/bin/pint --dirty --format agent
git add app/Services/OperatingWorkflow.php app/Http/Controllers/NotificationController.php resources/views/admin/operating/notifications.blade.php lang/en.json tests/Feature/Operating/CrmInboxTest.php
git commit -m "feat(crm): inbox tabs for SLA, new leads and reminders; notify reps of assigned leads

Co-Authored-By: Claude Opus 5.5 (1M context) <noreply@anthropic.com>"
```

---

### Task 19: قسم CRM في السايد بار

**Files:**
- Modify: `resources/views/admin/layout.blade.php` (السطران 16–17)
- Modify: `lang/en.json`
- Test: `tests/Feature/Operating/CrmNavigationTest.php`

**Interfaces:**
- Consumes: كل مسارات T2–T18.
- Produces: `<details class="crm-nav">` في السايد بار يظهر لمن يقرأ `opportunities`. روابط المديرين (لوحة الفريق، الاستيراد، سلة المحذوفات، سجل العمليات، إعدادات CRM) تظهر لـowner/sales_manager فقط. **لا تغيير آخر في السايد بار** (Q: ميرنا ستحدد باقي التابات).

- [ ] **Step 1: الاختبار الفاشل** — `tests/Feature/Operating/CrmNavigationTest.php`:

```php
<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmNavigationTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_reps_get_the_crm_section_without_manager_links(): void
    {
        $rep = $this->operatingUser('sales');
        $this->employeeFor($rep);

        $this->actingAs($rep)->get('/admin')->assertOk()
            ->assertSee('href="/admin/operations/crm"', false)->assertSee('href="/admin/operations/crm/leads"', false)->assertSee('href="/admin/operations/crm/calendar"', false)
            ->assertDontSee('href="/admin/operations/crm/team"', false)->assertDontSee('href="/admin/operations/crm/import"', false)->assertDontSee('href="/admin/operations/audit-log"', false);
    }

    public function test_managers_get_every_crm_link_and_other_roles_none(): void
    {
        $this->actingAs($this->operatingUser('sales_manager'))->get('/admin')->assertOk()
            ->assertSee('href="/admin/operations/crm/team"', false)->assertSee('href="/admin/operations/crm/import"', false)->assertSee('href="/admin/operations/crm/trash"', false)->assertSee('href="/admin/operations/audit-log"', false)->assertSee('href="/admin/operations/crm/settings"', false);
        $this->actingAs($this->operatingUser('designer'))->get('/admin')->assertOk()->assertDontSee('href="/admin/operations/crm"', false);
    }
}
```

- [ ] **Step 2:** Run → FAIL.

- [ ] **Step 3: السايد بار** — في `resources/views/admin/layout.blade.php` احذف السطرين 16 و17 (روابط `خط البيع` و`المتابعات والتذكيرات`) وضع مكانهما:

```blade
@if($access->can(auth()->user(),'opportunities'))@php $crmManager=in_array(auth()->user()->role,\App\Services\OperatingCrm::MANAGERS,true); $crmLinks=['/admin/operations/crm'=>'لوحة CRM','/admin/operations/crm/leads'=>'العملاء المحتملون','/admin/operations/opportunities/pipeline'=>'خط البيع','/admin/operations/crm/funnel'=>'قمع المبيعات','/admin/operations/crm/sla'=>'المهام وSLA','/admin/operations/crm/calendar'=>'التقويم','/admin/operations/activities/follow-ups'=>'المتابعات والتذكيرات']; if($crmManager){$crmLinks+=['/admin/operations/crm/team'=>'لوحة الفريق','/admin/operations/crm/import'=>'استيراد البيانات','/admin/operations/crm/trash'=>'سلة المحذوفات','/admin/operations/audit-log'=>'سجل العمليات','/admin/operations/crm/settings'=>'إعدادات CRM'];} @endphp
<details class="crm-nav" {{ request()->is('admin/operations/crm*','admin/operations/opportunities/pipeline','admin/operations/activities/follow-ups','admin/operations/audit-log')?'open':'' }}><summary>CRM</summary>@foreach($crmLinks as $url=>$label)<a href="{{ $url }}" class="{{ '/'.request()->path()===$url?'active':'' }}">{{ __($label) }}</a>@endforeach</details>@endif
```

> تحقق أن `layout.blade.php` لا يحتوي رابطًا آخر لـ`/admin/operations/opportunities/pipeline` (سيظهر مرتين). مجموعة "المبيعات والبيانات" التلقائية تبقى كما هي (روابط الجداول العامة).

- [ ] **Step 4: الترجمات**

```json
"استيراد البيانات": "Import data"
```

- [ ] **Step 5:** Run → PASS. ثم الحزمة كاملة: `$PHP artisan test --compact` → كلها ناجحة.

- [ ] **Step 6: Pint (لا PHP هنا) + Commit**

```bash
git add resources/views/admin/layout.blade.php lang/en.json tests/Feature/Operating/CrmNavigationTest.php
git commit -m "feat(crm): CRM section in the sidebar with manager-only tools

Co-Authored-By: Claude Opus 5.5 (1M context) <noreply@anthropic.com>"
```

---

### Task 20: التحقق النهائي (اختبارات، تشغيل محلي، صور، ترجمة) والتسليم

**Files:** لا ملفات جديدة في المشروع. السكربتات المؤقتة في مجلد الـscratchpad فقط.

- [ ] **Step 1: الحزمة كاملة + Pint**

```bash
PHP=/c/dev-tools/php84/php.exe
$PHP artisan test --compact
$PHP vendor/bin/pint --format agent
git status --short
```
Expected: كل الاختبارات ناجحة (142 + الجديدة ≈ 185+). Pint بلا تغييرات (لو غيّر شيئًا: commit `style: pint`).

- [ ] **Step 2: صحة `lang/en.json` وعدم وجود نص عربي غير مترجم**

```bash
$PHP -r 'json_decode(file_get_contents("lang/en.json"), true, 512, JSON_THROW_ON_ERROR); echo "json ok\n";'
LC_ALL=C.UTF-8 grep -ohP "__\('\K[^']*[\x{0600}-\x{06FF}][^']*(?='\))" resources/views/admin/operating/crm-*.blade.php resources/views/admin/operating/audit-log.blade.php resources/views/admin/layout.blade.php app/Services/Operating{Crm,Leads,CrmReports,LeadImport}.php app/Http/Controllers/{Crm,Audit,Notification}Controller.php | sort -u > /tmp/crm-keys.txt
$PHP -r '$en = json_decode(file_get_contents("lang/en.json"), true); foreach (file("/tmp/crm-keys.txt", FILE_IGNORE_NEW_LINES) as $key) { if (! isset($en[$key])) echo "MISSING: $key\n"; }'
```
Expected: `json ok` ولا سطور `MISSING`. (الـ`__($label)` المتغيرة تأتي من الثوابت `COLUMNS`/`FIELDS`/`SLA_LABELS` وقوائم الـviews؛ افحصها بنفس الطريقة باستخراج القيم العربية من تلك الثوابت.)

- [ ] **Step 3: تشغيل محلي بقاعدة PostgreSQL**

```bash
/c/dev-tools/pgsql17/bin/pg_isready -h 127.0.0.1 -p 5432 || /c/dev-tools/pgsql17/bin/pg_ctl -D /c/dev-tools/pgdata17 -l /c/dev-tools/pgdata17/server.log start
$PHP artisan migrate --no-interaction
$PHP artisan operating:crm-backfill
$PHP artisan serve --port=8080
```

(في الخلفية.) استخدم حسابات QA المحلية `qa-qasalesrep@findo.local` و`qa-qasales_manager@…` (راجع أسماءها الفعلية في جدول `users` محليًا) وكلمة المرور المحلية المشتركة. **لا تنشئ أي حساب على السيرفر.**

- [ ] **Step 4: سيناريو يدوي كامل (Pass/Fail لكل بند)**
  1. مدير: إعدادات CRM ← SLA = 1 ساعة، تفعيل التوزيع لموظفين اثنين.
  2. مدير: عميل جديد بلا مسؤول ← يُسند دوريًا، والموظف يرى إشعار "عميل محتمل جديد" في صندوق الوارد (تبويب عملاء جدد).
  3. نفس الرقم بصيغة أخرى ← رسالة التكرار باسم الفرصة وتاريخها.
  4. Rep: صفحة العملاء المحتملين ← يرى عملاءه فقط. الفلاتر، اختيار أعمدة، إعادة تحميل الصفحة ← الأعمدة محفوظة.
  5. Rep: ملف العميل ← تسجيل مكالمة "مهتم" + متابعة بعد يومين ← تظهر في الخط الزمني، وSLA = "في الموعد"، والمتابعة في التقويم.
  6. خط البيع: سحب بطاقة من "جديد" إلى "اجتماع محدد" مع سبب ← تنتقل. سحبها إلى "كسب" بلا عميل ← رسالة الرفض.
  7. `$PHP artisan operating:crm-sla` بعد إنشاء عميل وتغيير `sla_due_at` يدويًا للماضي (tinker محلي فقط) ← إشعار للمسؤول والمدير. المدير يخفي التنبيه بسبب.
  8. تصدير Excel مع إخفاء الأرقام ← افتح الملف في Excel: عربي سليم، اتجاه RTL، الأرقام مخفية. السجل يظهر في سجل العمليات.
  9. استيراد ملف xlsx حقيقي (احفظ ملفًا من Excel بالأعمدة العربية) ← ربط تلقائي صحيح، معاينة، تكرارات، تنفيذ، ملخص.
  10. أرشفة عميل ← يظهر في السلة ← استعادة ← يعود لمرحلته.
  11. داشبورد CRM: الأرقام تطابق ما أُنشئ. زر "عملائي" للمدير. القمع والفريق والـSLA تعرض بيانات منطقية.
  12. تبديل اللغة EN: كل الشاشات بالإنجليزية بلا نص عربي متبقٍ (عدا البيانات).
  13. الوضع الداكن: البطاقات والشارات مقروءة.

- [ ] **Step 5: صور ولقطات (headless Chrome عبر CDP، كما في `reference: local-qa-setup`)** — لكل من: `/admin/operations/crm`، `/crm/leads`، `/crm/leads/{id}`، `/opportunities/pipeline`، `/crm/funnel`، `/crm/team`، `/crm/sla`، `/crm/calendar`، `/crm/import`، `/crm/trash`، `/audit-log`، `/notifications`، `/crm/settings`: لقطة عند 1366px و375px، وتحقق `document.documentElement.scrollWidth <= clientWidth` (لا تمرير أفقي إلا داخل `.crm-table-wrap`)، ولا أخطاء console. احفظ اللقطات في الـscratchpad وراجعها بصريًا.

- [ ] **Step 6: الأداء** — بقاعدة محلية فيها 2000 فرصة (استيراد ملف تجريبي من T15): زمن تحميل `/admin/operations/crm` و`/crm/funnel` أقل من ثانية ونصف. لو أبطأ: سجّل الرقم في ملاحظة الـPR (Q15 يقبل ذلك مؤقتًا) ولا تضف كاش بدون اختبار.

- [ ] **Step 7: الدمج (بعد موافقة المستخدم)**

```bash
git checkout main && git pull --ff-only && git merge --ff-only feature/crm-tcrm-parity
```
**لا push ولا نشر بدون طلب صريح من المستخدم.**

- [ ] **Step 8: ملاحظات النشر (تُعطى للمستخدم، لا تُنفَّذ تلقائيًا)**
  1. على السيرفر: `php -m | grep -i zip` (مطلوب للـExcel، Q9).
  2. بعد النشر: `php artisan config:clear && php artisan view:clear` ثم `php artisan operating:crm-backfill` مرة واحدة.
  3. تأكد أن `schedule:run` يعمل كل دقيقة (أمر `operating:crm-sla` الجديد كل 15 دقيقة).
  4. أبلغ الإدارة: SLA مفعّل افتراضيًا بـ24 ساعة **للعملاء الجدد فقط** (القدامى بلا مهلة)، والتوزيع الدوري معطّل حتى يفعّله مدير المبيعات.

---

## Self-Review (تمت مراجعته عند كتابة الخطة)

**1. تغطية المطلوب (القسم 1.2):** كل صف في جدول الفجوات له تاسك: الحقول/المرحلة/التكرار/أول تواصل (T1)، الإعدادات (T2)، SLA (T3)، التوزيع (T4)، Excel (T5)، الـLeads (T6)، النشاط السريع (T7)، الملف (T8)، الـPipeline (T9)، الداشبورد بالـ8 مؤشرات (T10)، القمع (T11)، الفريق (T12)، المهام وSLA (T13)، التقويم (T14)، الاستيراد (T15)، السلة (T16)، سجل العمليات (T17)، صندوق الوارد (T18)، السايد بار (T19). ما هو خارج النطاق مذكور في 1.3 بسببه.

**2. Placeholders:** لا يوجد TBD. التنبيهات المكتوبة كـ"تحقق من…" مرفقة بالأمر الدقيق وبالبديل.

**3. اتساق الأسماء:** `OperatingCrm::{MANAGERS, RESTORABLE, FUNNEL, CONTACT_KINDS, AUDITED_TYPES, SLA_LABELS, DEFAULTS}`، `slaState/openAlert/responseHours/nextAssignee/stageReach/reached/phoneKey/phoneOwner/onStageChange/touchFirstContact/settings/settingsVersion/saveSettings/sendSlaAlerts/dismissSla`، `OperatingSales::{transitionAs, logActivity}`، `OperatingLeads::{query, rows, export, profile, handover}`، `OperatingCrmReports::{leads, activityQuery, dashboard, calendar, funnel, team, sla, between, closedBetween, percent}`، `OperatingLeadImport::{stage, load, normalize, guessMapping, preview, commit}`، `Spreadsheet::{read, xlsx}`، `OperatingWorkflow::restore`. مفاتيح `data`: `phone_key, sla_due_at, sla_breached_at, sla_dismissed_at, first_contact_at, closed_at, archived_from`.

**4. ترتيب الاعتماديات:** T10 يضيف `crm-nav` بروابط لصفحات T11–T14، فلا يُدمج الفرع قبل T14 (مذكور في T10). T18 يغيّر إشعارات قد تعدّها اختبارات قديمة (مذكور مع طريقة المعالجة).

## Execution Handoff

بعد اعتماد الخطة، خياران للتنفيذ:
1. **Subagent-Driven (موصى به):** subagent جديد لكل تاسك، ومراجعة بين التاسكات (`subagent-driven-development`).
2. **Inline:** التنفيذ في نفس الجلسة مع نقاط مراجعة (`executing-plans`).
