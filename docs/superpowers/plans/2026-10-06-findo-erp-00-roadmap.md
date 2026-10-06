# Findo ERP — تحليل الفجوات وخارطة التنفيذ (Roadmap)

> **لأي سيشن جديدة تنفّذ هذه الخطة:** هذا الملف هو المرجع الرئيسي، ومكتفٍ بذاته: لا تحتاج أي سياق من المحادثة التي أنتجته.
> اقرأه كاملًا أولًا، ثم نفّذ ملف المرحلة المفصّل:
> `docs/superpowers/plans/2026-10-06-findo-erp-01-foundations-attendance.md` (المرحلتان 0 و1: أساسيات + الحضور والنقاط والبونص).
> باقي المراحل (2 → 11) موصوفة هنا على مستوى "ماذا ولماذا وأين". **قبل تنفيذ أي مرحلة منها اكتب لها ملف خطة مفصّل** بنفس قالب ملف المرحلة 1 (استخدم skill `writing-plans`)، بعد أن تقرأ الكود الفعلي وقتها، لأن الكود سيكون قد تغيّر.
>
> **REQUIRED SUB-SKILL للتنفيذ:** `subagent-driven-development` (مفضّل) أو `executing-plans`. الخطوات مكتوبة كـ checkbox (`- [ ]`) للتتبع.

**تاريخ التحليل:** 2026-10-06 · **الفرع وقتها:** `main` (آخر commit: `05aa56a`)

---

## 1. المطلوب (نص المتطلبات كما وصل، مختصرًا)

10 أقسام + Dashboard + ربط بين الأقسام + مواصفة تفصيلية لنظام الحضور بالنقاط:

1. **HR & Employees:** بيانات وملف الموظف، الأقسام، المسميات الوظيفية، تاريخ التعيين، العقود، الرواتب، البدلات، الخصومات، البونص، السلف، الإجازات وطلباتها وموافقاتها، الحضور، التأخير، الغياب، الإضافي، الخصم حسب التأخير/الغياب، التقييم، سجل الترقيات، سجل الجزاءات، مستندات الموظف.
2. **الحضور والانصراف:** Check-in/out، ساعات العمل، Late/Early، Absence، Overtime، WFH، حساب الساعات، تقارير يومية وشهرية، ربط بالمرتبات والبونص، قابلية ربط جهاز بصمة مستقبلًا.
3. **Marketing:** Dashboard، Campaigns وميزانيتها وحالتها، القنوات (Facebook/Instagram/Google…)، Leads من الحملات، تكلفة الـLead، Conversion Rate، الأداء، Content Calendar، Social Posts، Content Management، مهام الفريق وتوزيعها، التقارير، ROI.
4. **Sales:** Dashboard، Leads ومصادرها وحالتها وتوزيعها، Follow-ups، Tasks/Calls/Meetings، Opportunities، Pipeline (New → Contacted → Qualified → Proposal Sent → Negotiation → Won/Lost)، Deals، Quotations، Proposals، Contracts، Customers وتاريخهم، Expected Revenue، Won/Lost، Targets، Commission، Reports.
5. **Projects:** الأنواع، الحالة، PM، المطورون/المصممون/QA، Tasks، Sprints، Backlog، Milestones، Deadlines، Budget/Cost/Revenue، Timesheets، Task Hours، المستندات، المتطلبات، Change Requests، المصروفات، الربحية، التقارير.
6. **CRM:** قاعدة العملاء، بيانات الشركة، Contacts، مشاريع/عقود/عروض/فواتير/مدفوعات العميل، المتبقي عليه، تاريخ التواصل، Notes، Follow-ups، المستندات، تذاكر الدعم.
7. **Accounting:** Chart of Accounts، Income، Expenses، Cash، Bank Accounts، AR، AP، Invoices، Purchase Invoices، Customer/Supplier Payments، Salaries/Bonuses/Advances، Recurring Expenses، Tax/VAT. التقارير: P&L، Cash Flow، Balance Sheet، Revenue، Expense، Salary، Project Profitability، Customer Outstanding، Monthly Financial.
8. **المشتريات والموردين:** Suppliers، Purchase Requests/Orders، Supplier Invoices، Payments، Software Subscriptions، Hosting، Domains، APIs، Tools & Licenses.
9. **Support & Maintenance:** Tickets، Customer Requests، Bug Reports، Maintenance Contracts، Support Team، Assignment، Priority، SLA، Status، Maintenance Payments، Reports.
10. **Users & Permissions:** Super Admin، HR، Accountant، Sales Manager، Sales Employee، Marketing Manager، Marketing Employee، Project Manager، Developer، UI/UX Designer، QA، Support. لكل دور: View / Create / Edit / Delete / Approve / Export.
11. **ERP Dashboard:** إجمالي الموظفين، الحاضرون/الغائبون/المتأخرون اليوم، Leads (الإجمالي والجديدة)، عدد Deals وقيمتها، Revenue، Expenses، Net Profit، المشاريع الحالية/المتأخرة/القريبة من التسليم، Outstanding Payments، Salaries، Monthly Revenue/Expenses، Sales/Marketing Performance، Project Profitability.
12. **الربط بين الأقسام:** Marketing → Lead → Sales → Customer → Contract → Project → Developers → Timesheet → تكلفة المشروع → دفع العميل → Revenue & Profit.

**مواصفة الحضور بالنقاط (إلزامية حرفيًا):**
- 🟢 **خضراء:** حضور في موعد بداية العمل أو قبله، والموظف مؤهل لبونص شهري **من 25 إلى 40 دولارًا** حسب نسبة الالتزام وسياسة الشركة.
- ⚫ **سوداء:** حضور بعد موعد البداية، مع تسجيل وقت الحضور الفعلي ومدة التأخير.
- 🔴 **حمراء:** لم يسجّل حضورًا خلال يوم العمل، ويُسجَّل اليوم "عدم حضور / عدم تسجيل".
- **يومان متتاليان بدون تسجيل:** تنبيه للـHR أو المدير المسؤول، ويظهر للموظف أن لديه يومين بدون تسجيل، ويُسجَّل ذلك في سجل الحضور.
- **لوحة HR/Admin:** حالة الجميع يوميًا، النقاط، أوقات الحضور والانصراف، المتأخرون، من لم يسجّل، الغياب المتتالي، نسبة الالتزام، المستحقون للبونص، حساب البونص (25–40)، تقارير يومية وشهرية.
- **الإشعارات:** للموظف عند التأخير، وللـHR/المدير عند عدم التسجيل، وعند بلوغ يومين متتاليين، وتقرير شهري بالمستحقين للبونص.

---

## 2. كيف بُني النظام الحالي (قيود يجب أن يلتزم بها كل تاسك)

> هذا القسم هو "طريقة التعامل مع النظام". أي كود جديد يخالفه يُرفض في المراجعة.

### 2.1 المكدّس التقني
- Laravel **13.34** وPHP **8.4** وPHPUnit **12.5** وPint، وقاعدة البيانات **PostgreSQL** محليًا وفي الإنتاج (`DB_CONNECTION=pgsql`).
- الاختبارات تعمل على **SQLite in-memory** (انظر `phpunit.xml`)، والإضافة `pdo_sqlite` مثبتة.
- **مجلد `tests/` غير موجود حاليًا**، وفيه `autoload-dev` بالشكل `Tests\\ → tests/`. المرحلة 0 تنشئه.
- لا توجد Eloquent models إلا `App\Models\User`. كل الكود يستخدم `DB::table(...)` مباشرة.
- الـtimezone هي `Africa/Cairo` (`config/app.php`)، والـQueue هي `sync`، والـMail هو `log` محليًا.
- لا تُضَف أي dependency (composer أو npm) بدون موافقة صريحة. الرسوم البيانية تُبنى بـHTML/CSS أو inline SVG.
- البيئة Windows، والـBash tool هو Git Bash: استخدم `/` في المسارات.

### 2.2 "محرّك التشغيل" (Operating Engine): القلب الذي يجب البناء عليه
النظام **ليس** جداول لكل كيان، بل محرّك عام مدفوع بالإعدادات:

| الجزء | المسؤولية |
|---|---|
| `config/operating.php` | ملف PHP يرجع `json_decode(<<<'JSON' … JSON)`. يعرّف `groups` (الأقسام وصلاحيات القراءة والكتابة لكل دور) و`roles` و`entities` (كل نوع سجل: الحقول والحالات والأعلام) و`statuses` (تسميات الحالات ar/en) و`scorecards` و`thresholds` و`field_ownership`. **أي كيان جديد يُضاف هنا كـJSON.** |
| جدول `operating_records` | كل السجلات من كل الأنواع: `type, title, status, owner_id, created_by, data (JSON), version, source ('findo' أو 'jira'), external_id`. |
| جدول `operating_links` | فهرس للعلاقات: يُعاد بناؤه عند كل حفظ لكل حقل من نوع `relation`. |
| جدول `operating_events` | سجل تدقيق غير قابل للحذف: create و update و transition و comment و attachment، مع `reason` إلزامي. |
| `operating_comments` و`operating_attachments` | تعليقات ومرفقات خاصة على أي سجل (هذه تغطي "مستندات الموظف/العميل/المشروع"). |
| جدول `operating_settings` | إعدادات مُصدَّرة بـ`version` ومُدقَّقة بـevent (المفتاح الحالي: `rules`). |
| `App\Services\OperatingCatalog` | قراءة الـentities والتسميات و`data()` (فك JSON). |
| `App\Services\OperatingAccess` | `can($user, $type, 'read'|'write'|'approve')`، و`query()` (نطاق الرؤية: private/assigned/مدير مباشر)، و`record()`، و`visibleData()`، و`fields()`. |
| `App\Services\OperatingWorkflow` | `save()`: validation مبني من تعريف الحقول، وقواعد عمل لكل type، وقفل تفاؤلي بـ`version`، وimmutable بعد الاعتماد، وevent. و`transition()` → `gate()`: بوابات الانتقال لكل type. و`fail($ar, $en)` يرمي `ValidationException` على المفتاح `workflow`. |
| `App\Services\OperatingReports` | تقرير صحة المشروع. |
| `App\Http\Controllers\OperatingController` | overview و listing و create و show و store و update و transition و comment و attach و settings و reports و permissions و integrations. |
| Views `resources/views/admin/operating/*.blade.php` | `form` و`listing` عامّان لكل الأنواع. الـinput type يُرسم كـ`type="{{ $field['type'] }}"` (أي أن نوع حقل جديد مثل `time` يعمل في الواجهة تلقائيًا، لكنه يحتاج قاعدة validation في `save()`). |

**أنواع الحقول المدعومة:** `text, textarea, number, date, datetime-local, select (options), checkbox, url, email, relation (entity), user`.
**أعلام الكيان:** `private` (يراه المالك والمُسنَد إليه والمدير المباشر وowner وhr)، `assigned` (يراه المُسنَد إليه أو المراجع أو المختبِر)، `approve_roles` (من يعتمد الانتقال إلى approved/active/finalized)، `immutable` (حالات يُمنع بعدها التعديل).
**الحالة الأولى لأي سجل جديد** = أول عنصر في `statuses`.

### 2.3 قواعد ثابتة لا تُكسر
1. **كيان جديد = إدخال في `config/operating.php`** + قواعده في `OperatingWorkflow::save()` و`gate()` + أي استثناء صلاحيات في `OperatingAccess`. لا جداول جديدة لكل كيان. الاستثناء الوحيد المقبول هو جدول مساعد (مثل `operating_links`) عندما يكون التجميع الرقمي مستحيلًا عمليًا على JSON (انظر القرار D3).
2. **لا حذف فعلي أبدًا.** "Delete" = الانتقال إلى `archived` (الموجود في كل كيان). سجل التدقيق لا يُحذف.
3. **كل تعديل يحمل `reason`** ويكتب `operating_events`.
4. **القيم المحسوبة** تُخزَّن داخل `data` بمفاتيح ليست ضمن `fields` (مثال قائم: `calculated_score` في evaluations).
5. **القرارات الإدارية ليست تلقائية** (سياسة مكتوبة في صفحة الصلاحيات): الخصومات والجزاءات تُنشأ كمسودات تنتظر الاعتماد، ولا تُطبَّق تلقائيًا.
6. **ثنائية اللغة:** النصوص العربية مفاتيح `__('…')`، وترجمتها الإنجليزية تُضاف إلى `lang/en.json`. تسميات الـconfig بالشكل `{ "ar": …, "en": … }`. رسائل `fail()` تأخذ النصين.
7. **المسارات:** كل مسار تشغيل جديد يجب أن يبدأ بـ`/admin/operations/` لأن `App\Http\Middleware\AdminAccess` يشترط دور CMS لأي مسار آخر، فيُمنع HR والمطور وغيرهما. **وسجّل المسارات الثابتة قبل** `Route::get('/operations/{type}', …)` و`/operations/{type}/create` في `routes/admin.php`.
8. **Views جديدة** تمد `admin.layout` وتمرّر `collections` و`catalog` و`access` (انظر `OperatingController::view()`)، لأن الـlayout يحتاجها.
9. **أقفال التزامن:** داخل `DB::transaction` استخدم `if (DB::getDriverName() === 'pgsql') { DB::select("SELECT pg_advisory_xact_lock(hashtext('findo-operating-gates'))"); }` ثم `lockForUpdate()`، كما في `save()` و`transition()`.
10. **Pint:** بعد أي تعديل PHP شغّل `vendor/bin/pint --dirty --format agent`.
11. **الاختبارات:** feature tests بـPHPUnit (`php artisan make:test --phpunit Name`)، وتُشغَّل بـ`php artisan test --compact <path>`. اقرأ skill `testing-best-practices` إن كان متاحًا.
12. **Git:** لا تعمل على `main`. أنشئ فرعًا لكل مرحلة (`feature/erp-phase-N-…`)، وcommit لكل تاسك. رسالة الـcommit تنتهي بـ`Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.

### 2.4 الأدوار الحالية (`config('operating.roles')`)
`owner, delivery, tech_lead, developer, qa, marketing, designer, video_editor, media_buyer, sales, data_entry, finance, hr, support, employee, editor, seo`

### 2.5 الأقسام الحالية (`groups`) وصلاحياتها
| group | read | write |
|---|---|---|
| governance | owner, delivery | owner, delivery |
| delivery | owner, delivery, tech_lead, developer, qa | owner, delivery, tech_lead, developer |
| quality | owner, delivery, tech_lead, developer, qa | owner, delivery, tech_lead, qa, developer |
| marketing | owner, marketing, media_buyer, designer, video_editor | owner, marketing, media_buyer |
| social | owner, marketing, media_buyer, designer, video_editor | owner, marketing |
| creative | owner, marketing, designer, video_editor, media_buyer | owner, marketing, designer, video_editor |
| advertising | owner, marketing, media_buyer, sales | owner, media_buyer |
| sales | owner, delivery, sales, data_entry | owner, sales, data_entry |
| people | (كل الأدوار تقريبًا عدا support, editor, seo) | owner, hr, delivery |
| finance | owner, finance | owner, finance |
| support | owner, delivery, tech_lead, support | owner, delivery, tech_lead, support |
| seo | owner, editor, seo | owner, seo |

---

## 3. تحليل الفجوات: الموجود وغير الموجود

الرموز: ✅ موجود ويكفي · 🟡 موجود جزئيًا (يحتاج إضافة) · ❌ غير موجود.
أسماء الكيانات الموجودة بين backticks هي مفاتيح `config/operating.php`.

### 3.1 HR & Employees
| المطلوب | الحالة | الموجود / الناقص |
|---|---|---|
| بيانات الموظفين | 🟡 | `employees`: job_title, department, manager_id, user_id, contract_type, work_window, weekly_hours, effective_date. **ناقص:** الهاتف، البريد الشخصي، الرقم القومي، تاريخ التعيين، جهة الطوارئ. |
| الملف الشخصي | 🟡 | صفحة السجل العامة (form) مع روابط ومرفقات. **ناقص:** صفحة 360° تجمع الإجازات والحضور والتقييمات والعقود والرواتب. |
| الأقسام Departments | 🟡 | `department` نص حر. **ناقص:** كيان `departments`. |
| الوظائف والمسميات | 🟡 | `job_title` نص حر. **ناقص:** كيان `positions`. |
| تاريخ التعيين | ❌ | `effective_date` غامض. يُضاف `hire_date`. |
| العقود (عقد العمل) | 🟡 | `contract_type` نص فقط (كيان `contracts` الموجود خاص بعقود العملاء). **ناقص:** `employment_contracts`. |
| الرواتب، البدلات، الخصومات، البونص، السلف | ❌ | لا يوجد أي payroll. |
| الإجازات وطلباتها وموافقاتها | ✅🟡 | `leave` (requested → approved/rejected، approve_roles: owner, delivery, hr، private). **ناقص:** نوع الإجازة، الرصيد، منع التداخل. |
| الحضور، التأخير، الغياب، الإضافي | ❌ | لا شيء. |
| الخصم حسب التأخير/الغياب | ❌ | لا شيء. |
| تقييم الموظفين | ✅ | `goals` و`evaluations` و`improvement_plans` و`scorecards` مع حساب الدرجة وبوابة 3–5 أهداف. |
| سجل الترقيات والجزاءات | ❌ | لا شيء (سجل events لا يكفي كسجل رسمي). |
| مستندات الموظف | ✅ | `operating_attachments` على سجل الموظف (مخفية عن غير owner/hr والموظف نفسه عبر `restrictedContext`). |

### 3.2 الحضور والانصراف
| المطلوب | الحالة |
|---|---|
| Check-in/out، تحديد ساعات العمل، Late/Early، Absence، Overtime، WFH، حساب الساعات | ❌ |
| النقاط الخضراء/السوداء/الحمراء + قاعدة اليومين + نسبة الالتزام + البونص 25–40 | ❌ |
| لوحة HR اليومية والتقارير اليومية والشهرية | ❌ |
| الإشعارات | ❌ (**لا توجد بنية إشعارات أصلًا**: لا جدول `notifications` ولا أي Notification class) |
| الربط بالمرتبات والبونص | ❌ (يعتمد على المرحلة 3) |
| ربط جهاز بصمة مستقبلًا | ❌ (يُصمَّم له مدخل موحّد `punch()` من الآن) |
| أيام العطلات الرسمية | ❌ |
| مُشغّل مهام مجدولة | ✅ `routes/console.php` فيه `Schedule::command('website:publish-due')`، أي أن الـscheduler مستخدم. |

### 3.3 Marketing
| المطلوب | الحالة | الملاحظة |
|---|---|---|
| Campaigns، الميزانية، الحالة | ✅ | `campaigns` (daily_budget, budget_cap, spent، بوابات الاعتماد وسقف التفويض). |
| Channels | 🟡 | `platform` نص حر. يُضاف `channel` select. |
| Leads من الحملات | 🟡 | `campaign_metrics.leads` رقم يدوي فقط. **لا توجد علاقة** campaign → opportunity. |
| CPL، Conversion، Performance، ROI، Reports | ❌ | البيانات الخام موجودة جزئيًا (spent, leads, qualified_leads, revenue في metrics) ولا يوجد حساب أو عرض. |
| Content Calendar | 🟡 | `social_posts.publish_date` موجود كقائمة. **ناقص:** عرض تقويم. |
| Social Posts، Content Management | ✅ | `social_posts` و`briefs` و`content_library` و`creative_assets` و`videos` و`experiments` و`listening`. |
| Marketing Tasks وتوزيعها | 🟡 | `briefs.assignee_id` فقط، و`tasks` مرتبطة بالمشاريع إلزاميًا. **ناقص:** `team_tasks`. |
| Marketing Team | ✅ | `employees.department`. |
| Marketing Dashboard | ❌ | |

### 3.4 Sales
| المطلوب | الحالة | الملاحظة |
|---|---|---|
| Leads، Status، Pipeline، Opportunities، Won/Lost | ✅ | `opportunities` بحالات new → contacted → qualified → discovery → proposal → negotiation → won/lost/deferred/unsuitable، مع reason إلزامي للخسارة. |
| Lead Sources | 🟡 | `source` نص حر. يصبح select + `campaign_id`. |
| Lead Assignment | ❌ | لا يوجد `assignee_id`. |
| Follow-ups | 🟡 | `next_action` و`follow_up` (موعد واحد، بدون تاريخ). |
| Tasks، Calls، Meetings | ❌ | يُضاف كيان `activities`. |
| Deals وقيمتها، Expected Revenue | ❌ | `budget_range` نص. يُضاف `expected_value` و`probability` و`expected_close`. |
| Quotations، Proposals، Contracts | ✅ | `quotes` و`contracts` ببوابات اعتماد. Proposal = quote + مرفق. |
| Customers و Customer History | 🟡 | `clients` موجود. التاريخ متاح فقط عبر "السجلات المرتبطة". **ناقص:** صفحة 360°. |
| Sales Targets، Commission، Reports، Dashboard | ❌ | |
| **مشكلة ربط:** `opportunities.client_id` **إلزامي** | ⚠️ | يعني أن الـLead يجب أن يكون عميلًا قبل أن يُسجَّل. يجب جعله اختياريًا مع بوابة عند proposal/won (انظر المرحلة 4). |

### 3.5 Projects
| المطلوب | الحالة | الملاحظة |
|---|---|---|
| Projects، Status، PM، Tasks، Backlog، Milestones، Deadlines، Requirements، Change Requests، Timesheets، Task Hours، Documents | ✅ | `projects` و`tasks` و`milestones` و`requirements` و`changes` و`time_entries` و`estimates` و`blockers` و`risks`، مع بوابات جودة صارمة ومزامنة Jira. |
| Project Types | ❌ | يُضاف `project_type`. |
| Assigned Developers/Designers/QA | 🟡 | ضمنيًا عبر assignee/reviewer/tester على المهام. **ناقص:** فريق المشروع `project_members`. |
| Sprints | ❌ | |
| Budget، Cost، Expenses | ✅ | `budgets` و`costs`. |
| Revenue | 🟡 | `invoices.project_id` و`receipts` موجودان، ولا يوجد تجميع. |
| Profitability، Reports | 🟡 | `OperatingController::reports()` يحسب هامشًا من budgets + costs فقط. **ناقص:** تكلفة العمالة (ساعات × تكلفة الساعة) والإيراد الفعلي. |

### 3.6 CRM
| المطلوب | الحالة | الملاحظة |
|---|---|---|
| Customer DB، Notes، Documents، المشاريع/العقود/العروض/الفواتير/المدفوعات | ✅ | عبر العلاقات والمرفقات والتعليقات. |
| Company Information | 🟡 | company, decision_maker, contact, email. **ناقص:** هاتف، عنوان، مجال، رقم ضريبي، موقع. |
| Contacts (أكثر من جهة اتصال) | ❌ | |
| Outstanding Amount | ❌ | حساب = فواتير مُصدرة − تحصيلات. |
| Communication History، Follow-ups | ❌ | كيان `activities` (مشترك مع Sales). |
| Support Tickets للعميل | 🟡 | `support` مرتبط بمشروع فقط بدون client_id. |

### 3.7 Accounting
| المطلوب | الحالة |
|---|---|
| Invoices، Customer Payments | ✅ `invoices` (immutable بعد الإصدار) و`receipts` (منع تجاوز مبلغ الفاتورة). |
| AR | 🟡 البيانات موجودة، ولا يوجد تقرير أعمار ديون. |
| Chart of Accounts، Cash، Bank Accounts، AP، Purchase Invoices، Supplier Payments، Recurring Expenses، Tax/VAT | ❌ |
| Expenses عامة (غير مرتبطة بمشروع) | ❌ (`costs.project_id` إلزامي). |
| Salaries، Bonuses، Advances | ❌ (من المرحلة 3). |
| كل التقارير المالية (P&L، Cash Flow، Balance Sheet، …) | ❌ |

### 3.8 المشتريات والموردين
| المطلوب | الحالة |
|---|---|
| Software Subscriptions، Hosting، Domains، APIs، Tools & Licenses | 🟡 `assets` (kind نص حر، cost، renewal_date، license، vault_reference). **ناقص:** مورّد، دورة فوترة، تنبيه تجديد، توليد مصروف. |
| Suppliers، Purchase Requests/Orders، Supplier Invoices، Payments | ❌ |

### 3.9 Support & Maintenance
| المطلوب | الحالة |
|---|---|
| Tickets، Customer Requests، Status | ✅ `support` (kind، response_due، حالات new → closed، وبوابة التحسين = change معتمد). |
| Bug Reports | ✅ `defects` (QA) و`support.kind`. |
| Incidents | ✅ `incidents`. |
| Assignment، Priority، SLA | ❌ |
| Maintenance Contracts، Maintenance Payments | ❌ (`projects.support_window` نص فقط). |
| Support Team | ✅ دور `support`. |
| Reports | ❌ |

### 3.10 Users & Permissions
| المطلوب | الحالة | التطابق |
|---|---|---|
| Super Admin | ✅ | `owner` |
| HR | ✅ | `hr` |
| Accountant | ✅ | `finance` |
| Sales Manager | ❌ | يُضاف `sales_manager` |
| Sales Employee | ✅ | `sales` (لكنه يرى كل الفرص الآن. يجب حصره في المُسنَد إليه) |
| Marketing Manager | ✅ | `marketing` |
| Marketing Employee | 🟡 | `media_buyer` و`designer` و`video_editor` متخصصون. يُضاف `marketing_employee` عام |
| Project Manager | ✅ | `delivery` |
| Developer | ✅ | `developer` |
| UI/UX Designer | ❌ | `designer` الحالي مصمم جرافيك في قسم التسويق. يُضاف `ui_ux` في قسم delivery |
| QA | ✅ | `qa` |
| Support | ✅ | `support` |
| View / Edit / Approve | ✅ | read / write / approve |
| Create (منفصل عن Edit) | ❌ | |
| Delete | 🟡 | يكافئ archive. القائمة مكتوبة hardcoded في `gate()` |
| Export | ❌ | لا يوجد تصدير CSV للقوائم |
| تعديل المصفوفة من الواجهة | ❌ | المصفوفة config + كود فقط، وصفحة `permissions` للعرض فقط |

### 3.11 ERP Dashboard
`overview` الحالي يعرض 4 أرقام عامة (أقسام متاحة، قرارات معلقة، إشارات، عدد السجلات) + قائمة قرارات + تنبيهات + عدّاد لكل كيان. **كل مؤشرات الـDashboard المطلوبة ❌**.
⚠️ ملاحظة أداء: `overview()` يجلب **كل** سجلات كل نوع (`->get()`) ليحسب `signals()`، وهذا سيتباطأ مع سجلات الحضور اليومية. المرحلة 1 تعالج ذلك للحضور.

### 3.12 سلسلة الربط
| الحلقة | الحالة |
|---|---|
| Campaign → Lead | ❌ لا يوجد `opportunities.campaign_id` |
| Lead → Customer | ⚠️ معكوسة: يجب أن يوجد العميل أولًا |
| Customer → Contract → Project | ✅ `contracts.client_id`، `projects.contract_id` (بوابة execution تشترط عقدًا ونطاقًا معتمدين) |
| Project → Developers → Timesheet | ✅ `tasks` و`time_entries` |
| Timesheet → تكلفة المشروع | ❌ لا توجد تكلفة ساعة للموظف |
| دفع العميل → Revenue & Profit | 🟡 `receipts` موجودة، ولا يوجد قيد أو تقرير ربحية فعلية |

---

## 4. قرارات معمارية (مُتخذة) وقرارات مفتوحة (تحتاج الإدارة)

### 4.1 قرارات مُتخذة (التزم بها)
- **A1:** كل الكيانات الجديدة داخل محرّك التشغيل (config + `operating_records`)، للحفاظ على التدقيق والصلاحيات والواجهة العامة مجانًا.
- **A2:** الشاشات الخاصة (لوحة الحضور، Pipeline board، تقويم المحتوى، Dashboards، صفحات 360°، التقارير المالية) هي **views إضافية** فوق نفس البيانات، مع services جديدة بأسماء `Operating*` في `app/Services/`.
- **A3:** الحسابات (نقاط، بونص، رواتب، ربحية، ROI، …) في services قابلة للاختبار، وليس في Blade.
- **A4:** الإشعارات عبر Laravel Notifications (قناة `database` دائمًا + `mail` للحالات المهمة). يُنشأ جدول `notifications` بـ`php artisan make:notifications-table` (جزء من Laravel، وليس dependency جديدة).
- **A5:** أي خصم أو جزاء أو عمولة تُولَّد كسجل **draft** يعتمده HR/owner، وليس كأثر تلقائي (القاعدة 2.3-5).
- **A6:** جهاز البصمة مستقبلًا يستدعي نفس `OperatingAttendance::punch()` بـ`source = 'device'`، ولا منطق منفصل.
- **A7:** "Delete" في مصفوفة الصلاحيات = صلاحية الأرشفة.

### 4.2 قرارات مفتوحة. الافتراض المكتوب يُنفَّذ ما لم تغيّره الإدارة
| # | السؤال | الافتراض الافتراضي في الخطة |
|---|---|---|
| D1 | أيام العمل وموعد البداية والنهاية | الأحد → الخميس، 09:00 → 17:00، سماحية 0 دقيقة. تُعدَّل من الإعدادات، ويمكن تحديد وردية لكل موظف. |
| D2 | معادلة البونص 25–40$ | نسبة الالتزام = خضراء ÷ (خضراء + سوداء + حمراء) × 100، والأيام بعذر مستبعدة. الحد الأدنى للاستحقاق 80%. البونص = 25 + 15 × (النسبة − 80) ÷ 20، أي 80% ← 25$ و100% ← 40$ وأقل من 80% ← 0. كل الأرقام قابلة للتعديل. |
| D3 | عمق المحاسبة | قيد مزدوج "خفيف": شجرة حسابات + قيود يومية بسطور في جدول مساعد `operating_ledger_lines` (استثناء معماري مبرَّر للتجميع الرقمي)، وقيود تلقائية من الفواتير والتحصيلات والمشتريات والرواتب. البديل الأبسط (cash-basis بلا ميزانية عمومية) يُسقط Balance Sheet. **يحتاج موافقة قبل المرحلة 8.** |
| D4 | عملة النظام | USD للبونص (حسب المتطلب). الرواتب والفواتير بعملة السجل (`currency` موجود في invoices وbudgets وquotes). تقارير متعددة العملات تُجمَّع لكل عملة على حدة، بلا تحويل أسعار. |
| D5 | الخصم حسب التأخير/الغياب | سياسة في الإعدادات: خصم X دقيقة أجر لكل دقيقة تأخير بعد Y دقيقة شهريًا، ويوم أجر لكل غياب بلا عذر، **كمسودة** في المرتب. القيم الافتراضية = 0 (معطّل) حتى تعتمدها الإدارة. |
| D6 | الإضافي (Overtime) | يُحسب تلقائيًا ويظهر في التقارير، لكنه يدخل المرتب فقط بعد اعتماده كـ`payroll_adjustments` من نوع overtime. |
| D7 | حضور يوم إجازة أو عطلة | مسموح، بلا نقطة، وكل الوقت يُحسب overtime. |
| D8 | الورديات الليلية (انصراف بعد منتصف الليل) | خارج النطاق الآن، وتُذكر كقيد معروف. |
| D9 | هل الحضور يتطلب IP أو موقعًا جغرافيًا؟ | لا (بالثقة + سجل التدقيق). يمكن إضافة قيد IP للمكتب لاحقًا. |

---

## 5. خارطة المراحل

كل مرحلة تنتج برنامجًا يعمل ويُختبر وحده. الترتيب مبني على الاعتماديات:

```
0 أساسيات ─┬─> 1 الحضور والنقاط ──> 3 الرواتب ──┐
           ├─> 2 HR core ─────────────┘          │
           ├─> 10a أدوار جديدة ─> 4 Sales/CRM ─> 5 Marketing
           │                         └──> 6 Projects (ربحية)
           ├─> 7 المشتريات ──────────────────────┤
           └─> 9 الدعم والصيانة                  v
                                            8 المحاسبة ─> 11 ERP Dashboard
                                            10b مصفوفة صلاحيات قابلة للتعديل + Export
```

| المرحلة | الملف | الحالة |
|---|---|---|
| 0 + 1 | `2026-10-06-findo-erp-01-foundations-attendance.md` | **مفصّلة وجاهزة للتنفيذ** |
| 2 → 11 | تُكتب لكل منها خطة مفصّلة عند الوصول إليها | موصوفة أدناه |

---

### المرحلة 0: أساسيات مشتركة (مفصّلة في ملف المرحلة 1)
1. بنية الاختبارات: `tests/TestCase.php`، و`tests/Concerns/BuildsOperatingRecords.php`، وsmoke test يثبت أن كل الـmigrations تعمل على SQLite.
2. بنية الإشعارات: جدول `notifications`، و`App\Notifications\OperatingAlert`، و`App\Services\OperatingNotifier`، وصفحة `/admin/operations/notifications` + عدّاد في الـsidebar.
3. نوع حقل جديد `time` في `OperatingWorkflow::save()`.

### المرحلة 1: الحضور والانصراف + النقاط + البونص (مفصّلة)
الكيانات `attendance` و`holidays`، وحقول وردية للموظف، و`OperatingAttendance` service، وCheck-in/out، وإغلاق اليوم المجدول (نقطة حمراء + غياب متتالٍ)، ولوحة HR اليومية، والتقرير الشهري + البونص + CSV، والإشعارات الأربعة، وإعدادات الحضور، وwidget للموظف في الـoverview.

---

### المرحلة 2: HR core
**الهدف:** استكمال بيانات الموظف والهيكل والسجلات الرسمية.
- **كيانات جديدة (group `people`):**
  - `departments`: name_en (text*)، head_id (relation employees)، description. statuses: active, archived.
  - `positions`: department_id (relation departments*)، level (select: junior/mid/senior/lead/manager)، description. statuses: active, archived.
  - `employment_contracts` (private، approve_roles [owner, hr]، immutable [active, archived]): assignee_id*، contract_kind (select: full_time/part_time/freelance/internship)، start_date*، end_date، probation_end، weekly_hours، notice_days، terms (textarea)، signed_evidence. statuses: draft, submitted, active, expired, archived. بوابة active تشترط signed_evidence. وsignal قبل انتهاء العقد بـ30 يومًا.
  - `employee_actions` (private، approve_roles [owner, hr]، immutable [approved, archived]): assignee_id*، kind (select: promotion/transfer/salary_change/warning/sanction/commendation)*، effective_date*، from_value، to_value، position_id، reason*، evidence. statuses: draft, submitted, approved, rejected, archived. (= سجل الترقيات + سجل الجزاءات.)
- **تعديل `employees`:** إضافة hire_date (date)، phone (text)، personal_email (email)، national_id (text)، emergency_contact (textarea)، department_id (relation departments)، position_id (relation positions)، hourly_cost (number، يُستخدم في الربحية)، annual_leave_days (number). يبقى `department` و`job_title` القديمان (لا تكسر البيانات المزروعة)، و`visibleData()` يخفي الحقول الحساسة الجديدة عن غير owner/hr (منطق القائمة البيضاء الحالي يحقق ذلك تلقائيًا).
- **تعديل `leave`:** إضافة leave_type (select: annual/sick/unpaid/emergency/other)*. وبوابات في `save()`: منع تداخل طلبين approved أو requested لنفس الموظف، و`end_date >= start_date`. وفي `gate()` عند approved: الرصيد السنوي (annual_leave_days − مجموع أيام annual المعتمدة في نفس السنة، بأيام العمل حسب `OperatingAttendance::isWorkday`).
- **شاشة ملف الموظف 360°:** `GET /admin/operations/employees/{id}/profile` (قبل `{type}`)، وservice `OperatingPeople::profile(User, int)` يجمع: البيانات المرئية، الإجازات والرصيد، ملخص الحضور للشهر الحالي، التقييمات والأهداف، العقود، الإجراءات، المرفقات. كل جزء محكوم بـ`access->can`.
- **اختبارات:** منع التداخل، الرصيد، immutable بعد الاعتماد، إخفاء الحقول الحساسة، صلاحيات صفحة الملف.

### المرحلة 3: الرواتب (Payroll)
**الهدف:** رواتب، بدلات، خصومات، بونص، سلف، وربطها بالحضور.
- **كيانات (group `people`، كلها private، approve_roles [owner, hr]):**
  - `compensation` (immutable [active, archived]): assignee_id*، base_salary*، currency*، housing_allowance، transport_allowance، other_allowances، effective_from*. statuses: draft, active, superseded, archived. سجل active واحد لكل موظف (تفعيل الجديد يحوّل القديم إلى superseded داخل gate).
  - `payroll_adjustments`: assignee_id*، period (text YYYY-MM)*، kind (select: allowance/deduction/bonus/attendance_bonus/overtime/commission/advance_repayment/late_deduction/absence_deduction)*، amount*، source_record_id (relation، اختياري)، reason*. statuses: draft, approved, rejected, applied, archived. **مصدرها:** يدوي، أو المرحلة 1 (attendance_bonus)، أو سياسة D5 (late/absence deduction)، أو D6 (overtime)، أو المرحلة 4 (commission). كلها draft.
  - `advances` (سلف): assignee_id*، amount*، currency*، installments (number)*، start_period*، reason*. statuses: requested, approved, rejected, repaying, settled, archived. عند approved تُولَّد advance_repayment كمسودات لكل قسط.
  - `payroll_runs` (immutable [approved, paid, archived]): period*، currency*، notes. statuses: draft, calculated, submitted, approved, paid, archived. data المحسوبة: `payslips` (لكل موظف: base، allowances، adjustments مفصّلة، gross، deductions، net).
- **Service `OperatingPayroll`:** `calculate(int $runId)`: compensation active + adjustments approved لنفس period وعملة. `approve` يحوّل الـadjustments إلى applied. `paid` يولّد قيدًا محاسبيًا (بعد المرحلة 8، وقبلها يسجّل event فقط).
- **ربط الحضور:** أمر `operating:attendance-monthly-report` (من المرحلة 1) يُوسَّع لإنشاء `attendance_bonus` كمسودة لكل مستحق، و`late_deduction` و`absence_deduction` حسب سياسة D5 (إن كانت قيمها > 0).
- **شاشات:** قسيمة راتب للموظف (يرى قسيمته فقط)، تقرير الرواتب الشهري (Salary Report) + CSV.
- **اختبارات:** الحساب، العملة، منع تكرار التطبيق، الخصوصية (موظف لا يرى راتب غيره، والمدير المباشر لا يرى الرواتب إلا لو كان owner/hr)، ومسودات البونص من الحضور.
- ⚠️ عدّل `OperatingAccess::query()`: الكيانات المالية للموظفين تُستثنى من رؤية المدير المباشر (`delivery`) رغم كونها private. الأمان أولًا.

### المرحلة 4: Sales & CRM
**يعتمد على:** 10a (دور `sales_manager`).
- **تعديل `opportunities`:** assignee_id (relation employees)، campaign_id (relation campaigns)، `source` يتحول من text إلى select (website/facebook/instagram/google/linkedin/referral/cold_call/event/other)؛ **ترحيل:** command يحوّل القيم القديمة غير المطابقة إلى `other` ويحفظ الأصل في `source_note`. وأيضًا expected_value (number)، currency، probability (number 0–100)، expected_close (date)، lead_company، lead_contact، lead_phone، lead_email. **`client_id` يصبح اختياريًا**، وبوابة في `gate()`: الانتقال إلى proposal أو negotiation أو won يتطلب client_id. وإجراء "تحويل إلى عميل" (`POST /admin/operations/opportunities/{id}/convert`) ينشئ `clients` من بيانات الـlead ويربطها، مع event.
  - `opportunities.assigned = true`، و`OperatingAccess::query()`: `sales` يرى المُسنَد إليه فقط، و`sales_manager` وowner يرون الكل (عدّل قائمة الأدوار المستثناة في فرع assigned).
  - تنبيه أن الحالات المطلوبة (New → Contacted → Qualified → Proposal Sent → Negotiation → Won/Lost) **موجودة**، و`discovery` حالة إضافية اختيارية.
- **تعديل `clients`:** phone، address، industry، tax_number، website، country.
- **كيانات جديدة (group `sales`):**
  - `contacts`: client_id*، name في الـtitle، role، phone، email، is_primary (checkbox).
  - `activities` (assigned): kind (select: call/meeting/task/email/note/follow_up)*، client_id، opportunity_id، assignee_id*، due_at (datetime-local)*، outcome (textarea)، next_step. statuses: planned, done, cancelled, archived. signal عند تجاوز due_at. هذا = Calls + Meetings + Tasks + Follow-ups + Communication History.
  - `sales_targets` (private، approve_roles [owner, sales_manager]): assignee_id*، period (YYYY-MM)*، target_value*، currency*، target_deals (number).
  - العمولة: إعدادات `operating_settings` بمفتاح `commission` (نسبة مئوية لكل دور + أساسها: قيمة الصفقة أو التحصيل). يُولِّد أمر شهري `commission` كمسودات في `payroll_adjustments` من التحصيلات الفعلية على عقود فرص won.
- **`support`:** إضافة client_id (يُستكمل في المرحلة 9).
- **شاشات:** Pipeline Kanban (`/admin/operations/opportunities/board`، مع أعمدة الحالات ونقل البطاقة = transition عادي يتطلب reason)، صفحة العميل 360° (`/admin/operations/clients/{id}/profile`: جهات الاتصال، الفرص، العروض، العقود، المشاريع، الفواتير، التحصيلات، **المتبقي = Σ invoices (issued/part_paid/overdue) − Σ receipts**، الأنشطة، التذاكر)، Sales Dashboard (Leads الجديدة هذا الشهر، حسب المصدر، حسب الحالة، Win rate، Expected Revenue = Σ expected_value × probability للفرص المفتوحة، Won/Lost، الإنجاز مقابل الهدف لكل موظف)، Sales Report + CSV.
- **Service:** `OperatingSales` (pipeline، forecast، targets، outstanding).

### المرحلة 5: Marketing
**يعتمد على:** المرحلة 4 (campaign_id على الفرص).
- **`campaigns`:** إضافة `channel` select (facebook/instagram/google/tiktok/linkedin/snapchat/youtube/email/other) مع إبقاء `platform` النصي.
- **كيان `team_tasks`** (group `marketing`، assigned): assignee_id*، department (select)، priority (select)، due_date*، brief_id، campaign_id، description*. statuses: backlog, in_progress, review, done, archived. (مهام فرق غير البرمجة، ويمكن إتاحته لاحقًا لـsales وsupport عبر نفس الكيان.)
- **Service `OperatingMarketing::campaignPerformance(User, ?period)`** لكل حملة:
  - spend = Σ `campaign_metrics.spent` (وإلا `campaigns.spent`).
  - leads = عدد `opportunities` بـcampaign_id (المصدر الحقيقي)، مع عرض leads اليدوية من metrics للمقارنة.
  - CPL = spend ÷ leads. Conversion = won ÷ leads.
  - revenue = Σ receipts على فواتير عملاء الفرص won من الحملة (أو expected_value للـwon إن لم تُحصَّل بعد، بعمود منفصل).
  - ROI = (revenue − spend) ÷ spend.
- **شاشات:** Marketing Dashboard (إنفاق الشهر، Leads، CPL، Conversion، ROI، أفضل وأسوأ القنوات، منشورات الأسبوع، مهام متأخرة)، Content Calendar شهري (`/admin/operations/social_posts/calendar` من social_posts.publish_date + briefs.due_date)، Campaign Report + CSV.
- **اختبارات:** معادلات CPL وConversion وROI (مع القسمة على صفر)، وعدم احتساب فرص من حملات لا يراها المستخدم.

### المرحلة 6: Projects (اكتمال + ربحية)
- **`projects`:** إضافة project_type (select: web/mobile/erp/ecommerce/wordpress/maintenance/other).
- **كيانات (group `delivery`):**
  - `project_members`: project_id*، assignee_id*، member_role (select: pm/developer/designer/ui_ux/qa/devops)*، allocation_percent، start_date، end_date. منع تكرار نفس الموظف ونفس الدور في المشروع.
  - `sprints`: project_id*، goal*، start_date*، end_date*. statuses: planned, active, completed, archived. بوابة: sprint واحد active لكل مشروع.
- **`tasks`:** إضافة sprint_id (relation sprints)، مع تحقق أن sprint من نفس المشروع (الفحص العام الموجود يغطيه تلقائيًا). ملاحظة: مهام Jira (`source != findo`) للقراءة فقط، كما هو.
- **Service `OperatingProjectFinance::profitability(User, object $project)`:**
  - labor_cost = Σ (time_entries.hours × hourly_cost للموظف، من `employees.hourly_cost`، وإلا من `compensation` ÷ ساعات الشهر).
  - direct_costs = Σ costs.actual_cost + مصروفات المشروع (المرحلة 8).
  - contract_value من budget المعتمد، revenue_invoiced = Σ invoices، revenue_collected = Σ receipts.
  - profit = revenue_collected − (labor + direct)، margin %.
  - يحل محل الحساب الحالي الجزئي في `OperatingController::reports()`.
- **شاشات:** تبويب "الفريق" و"Sprints" في صفحة المشروع، وتقرير ربحية المشاريع + CSV، وBacklog board لكل مشروع (اختياري).

### المرحلة 7: المشتريات والموردين
- **كيانات (group جديد `procurement`، read: owner, finance, delivery، write: owner, finance، approve: owner):**
  - `suppliers`: category (select: hosting/domain/cloud/api/saas/hardware/freelancer/other)، contact، email، phone، tax_number، payment_terms، website.
  - `purchase_requests` (approve_roles [owner]): requester_id (employees)*، supplier_id، description*، estimated_amount*، currency*، needed_by، project_id. statuses: requested, approved, rejected, ordered, archived.
  - `purchase_orders` (immutable [approved, closed, archived]): purchase_request_id، supplier_id*، amount*، currency*، terms. statuses: draft, submitted, approved, closed, archived.
  - `supplier_invoices` (immutable بعد issued): supplier_id*، purchase_order_id، invoice_number*، amount*، tax_amount، currency*، due_date*، expense_account_id (المرحلة 8)، project_id. statuses: draft, issued, part_paid, paid, overdue, void, archived.
  - `supplier_payments` (immutable): supplier_invoice_id*، amount*، paid_at*، reference*، bank_account_id (المرحلة 8). نفس قاعدة منع التجاوز الموجودة في receipts (انسخ النمط مع القفل).
- **`assets` (الاشتراكات):** إضافة supplier_id، category (select: hosting/domain/api/saas/license/plugin/app_store/cloud)، billing_cycle (select: monthly/quarterly/yearly/one_time)، currency، auto_renew (checkbox). signal قبل renewal_date بـ30 و7 أيام، وأمر مجدول `operating:subscriptions-renewals` يرسل إشعارات لـowner وfinance وصاحب الأصل (primary_id).
- **شاشات:** قائمة التجديدات القادمة، وتقرير مصروفات البرمجيات (Hostinger/Google/Apple/AWS/APIs/SaaS/Domains/Plugins) حسب المورّد والفئة.

### المرحلة 8: المحاسبة (تتطلب حسم D3 أولًا)
- **كيانات (group `finance`):**
  - `financial_accounts` (Chart of Accounts): code*، account_kind (select: asset/liability/equity/income/expense)*، parent_id، is_cash_or_bank (checkbox)، bank_name، iban، currency. statuses: active, archived. Seeder أوّلي (command `operating:seed-chart`) بشجرة قياسية: نقدية، بنوك، عملاء AR، موردين AP، ضريبة VAT مستحقة، رواتب مستحقة، سلف موظفين، إيرادات مشاريع، إيرادات صيانة، تكلفة عمالة، اشتراكات برمجيات، إعلانات، إيجار، …
  - `journal_entries` (immutable [posted, archived]): entry_date*، memo*، source_record_id، currency*. statuses: draft, posted, reversed, archived. السطور في **جدول مساعد** `operating_ledger_lines` (journal_id → operating_records، account_id → operating_records، debit، credit، project_id nullable). بوابة posted: Σdebit = Σcredit > 0. العكس (reversed) بقيد معاكس جديد، ولا تعديل.
  - `expenses` (مصروف عام، غير مشروع): expense_account_id*، amount*، tax_amount، currency*، paid_from_account_id*، expense_date*، supplier_id، project_id، recurring (checkbox)، recurrence (select monthly/yearly)، evidence.
  - `invoices`: إضافة tax_rate، tax_amount (محسوب)، subtotal، maintenance_contract_id (المرحلة 9). وإعدادات VAT الافتراضية في `operating_settings`.
- **Service `OperatingLedger`:** ترحيل تلقائي داخل نفس transaction الانتقال:
  - invoice → issued: من حـ/ AR إلى حـ/ الإيراد + حـ/ VAT.
  - receipt → active: من حـ/ البنك أو النقدية إلى حـ/ AR.
  - supplier_invoice → issued: من حـ/ المصروف + VAT مدخلات إلى حـ/ AP.
  - supplier_payment: من حـ/ AP إلى حـ/ البنك.
  - expense → active: من حـ/ المصروف إلى حـ/ البنك.
  - payroll_run → paid: من حـ/ الرواتب إلى حـ/ البنك + حـ/ سلف الموظفين.
- **أمر `operating:recurring-expenses`** (يومي): يولّد مسودات expenses للمتكررة.
- **التقارير (`OperatingFinancialReports`):** P&L، Balance Sheet، Cash Flow (مباشر من حركة حسابات is_cash_or_bank)، Revenue Report، Expense Report، Salary Report (من المرحلة 3)، Project Profitability (من المرحلة 6)، Customer Outstanding / AR aging (0–30، 31–60، 61–90، 90+)، AP aging، Monthly Financial Report. كلها لكل عملة على حدة، مع CSV.
- **اختبارات:** توازن القيود، الترحيل التلقائي لكل مصدر، استحالة تعديل قيد posted، أن الميزانية العمومية متوازنة (الأصول = الخصوم + حقوق الملكية + صافي الربح).

### المرحلة 9: Support & Maintenance
- **`support`:** إضافة client_id، assignee_id، priority (select: low/medium/high/critical)*، channel (select: email/whatsapp/phone/portal)، first_response_at (datetime-local)، resolved_at (datetime-local)، `assigned: true`.
- **SLA:** إعدادات `operating_settings` بمفتاح `sla` (ساعات الاستجابة والحل لكل priority، وتُخصَّص لكل عقد صيانة). Service `OperatingSupport::sla($ticket)` يحسب response_due وresolve_due والحالة (within/at_risk/breached). signals + إشعار للـsupport وdelivery عند at_risk وbreached (أمر مجدول كل 15 دقيقة).
- **كيان `maintenance_contracts`** (group `support`، approve_roles [owner]، immutable [active, expired, archived]): client_id*، project_id، start_date*، end_date*، monthly_fee*، currency*، included_hours، sla_tier (select)، scope (textarea)*. statuses: draft, active, expired, cancelled, archived. signal قبل الانتهاء بـ30 يومًا. والفواتير تُربط عبر `invoices.maintenance_contract_id` (= Maintenance Payments).
- **شاشات:** Support board حسب الحالة والأولوية، وتقرير الدعم (متوسط الاستجابة والحل، ونسبة الالتزام بالـSLA، والتذاكر لكل عميل وموظف) + CSV.

### المرحلة 10: Users & Permissions
**10a (قبل المرحلة 4):** إضافة الأدوار إلى `config('operating.roles')`:
- `sales_manager` ("مدير المبيعات" / "Sales manager"): في groups sales (read/write) وadvertising (read).
- `marketing_employee` ("موظف تسويق" / "Marketing employee"): marketing وsocial وcreative (read) + team_tasks (write على المُسنَد إليه).
- `ui_ux` ("مصمم UI/UX" / "UI/UX designer"): delivery وquality (read) + tasks (write على المُسنَد إليه) مثل developer.
- تحديث كل القوائم الثابتة في `OperatingAccess` و`OperatingWorkflow` حيث يظهر `sales` أو `developer` أو `designer` (ابحث بـgrep عن `'sales'` و`'developer'` و`'designer'`) واتخاذ قرار صريح لكل موضع.
- اختبار لكل دور جديد: ما يراه وما لا يراه.

**10b (بعد المراحل الوظيفية):**
- توسيع كل group في config بمفاتيح `create` و`archive` و`export` (الافتراضي عند الغياب: create = write، وarchive = القائمة الحالية الثابتة في `gate()`، وexport = read لـowner والمديرين فقط).
- `OperatingAccess::can()` يقبل `create|archive|export`، و`save()` يستخدم create عند الإنشاء، و`gate()` يستخدم archive بدل القائمة الثابتة.
- **Overrides قابلة للتعديل:** `operating_settings` بمفتاح `permissions` (role → group → actions)، تُحرَّر من صفحة `permissions` (checkbox matrix)، owner فقط، مع version + reason + event. والقيود الأمنية الصلبة في الكود (private، assigned، onboarding، الرواتب) **لا تُتجاوز** بالـoverride.
- **Export:** `GET /admin/operations/{type}/export` (سجّله قبل `{type}`… ولاحظ أنه مقطعان، فلا يتعارض مع `{type}/create`) يرجع CSV لنتيجة `access->query()` + `visibleData()` فقط، مع BOM لـExcel وevent `export`.
- عرض المصفوفة: View / Create / Edit / Delete / Approve / Export لكل دور × قسم.

### المرحلة 11: ERP Dashboard
- **Service `OperatingDashboard::kpis(User)`**: كل مؤشر محكوم بـ`access->can` على مصدره، ولا يُعرض لمن لا يملك القراءة:

| المؤشر | المصدر |
|---|---|
| إجمالي الموظفين | employees active |
| الحاضرون / المتأخرون / الغائبون / لم يسجّلوا اليوم | attendance اليوم (المرحلة 1) |
| إجمالي Leads / الجديدة (هذا الشهر) | opportunities |
| عدد Deals وقيمتها | opportunities won (expected_value) |
| Revenue / Expenses / Net Profit (الشهر) | ledger (المرحلة 8)، وقبلها receipts − costs كمؤشر مؤقت معنون بوضوح |
| المشاريع الحالية / المتأخرة / القريبة من التسليم (14 يومًا) | projects (status + approved_due أو original_due) |
| Outstanding Payments | invoices − receipts |
| Salaries (الشهر) | payroll_runs |
| Monthly Revenue/Expenses (12 شهرًا) | ledger: رسم أعمدة SVG بسيط |
| Sales / Marketing Performance | `OperatingSales` / `OperatingMarketing` |
| Project Profitability (أعلى وأدنى 5) | `OperatingProjectFinance` |

- **الواجهة:** صف KPI في أعلى `overview.blade.php` فوق المحتوى الحالي (لا تحذف القرارات والتنبيهات)، مقسّم حسب القسم. استخدم skill `dataviz` قبل بناء الرسوم.
- **أداء:** cache لكل مستخدم 5 دقائق (`Cache::remember("dashboard:{$user->id}", 300, …)`)، وإصلاح `overview()` كي لا يجلب كل السجلات لحساب signals (حدّ أقصى أو استعلام مخصص لكل type).

---

## 6. مخاطر وملاحظات للتنفيذ
1. **JSON queries على PostgreSQL:** النمط الحالي `where('data->x', $value)` يعمل. للمقارنات بالتاريخ استخدم صيغة `Y-m-d` النصية (المقارنة المعجمية صحيحة). وفي التجميع الرقمي الكبير (المحاسبة) استخدم الجدول المساعد (D3).
2. **الأداء:** الحضور يضيف حوالي (عدد الموظفين × 22) سجلًا شهريًا، وهذا مقبول، لكن لا تجلب كل السجلات في overview (المرحلة 1 تعالج ذلك).
3. **البيانات المزروعة:** `operating:setup` ينشئ 13 موظفًا بحالة `proposed` بلا `user_id`. الحضور يعمل فقط لموظف **active ومرتبط بحساب**، فاذكر هذا للإدارة.
4. **الـScheduler في الإنتاج:** تأكد أن السيرفر يشغّل `php artisan schedule:run` كل دقيقة (راجع `DEPLOYMENT.md`)، وإلا فلن تُسجَّل النقاط الحمراء ولا الإشعارات.
5. **تعارض المسارات:** أي مسار `/admin/operations/<كلمة>` بمقطع واحد يُفسَّر كـ`{type}` إن سُجّل بعده، فسجّل الثابت دائمًا أولًا.
6. **لا تُغيّر نوع حقل موجود** (مثل text إلى select) بلا command ترحيل، لأن القيم القديمة ستفشل في validation عند أول تعديل.
7. **الخصوصية:** الرواتب والعقود والإجراءات والحضور private. والمدير المباشر (`delivery`) يرى الحضور والإجازات لفريقه فقط، ولا يرى الرواتب.
