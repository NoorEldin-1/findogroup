# Findo ERP — تاسكات 12 أكتوبر (FIN-17، FIN-18): الموردون والمشتريات، الأصول والمخزون — خطة التنفيذ

> **For agentic workers:** REQUIRED SUB-SKILL: `executing-plans` (التنفيذ في نفس الجلسة). الخطوات checkbox (`- [ ]`).
>
> **قبل أي شيء:**
> 1. اقرأ `docs/superpowers/plans/2026-10-06-findo-erp-00-roadmap.md`: **القسم 2 كاملًا** (القواعد الثابتة)، والقسم 3.8، والمرحلة 7.
> 2. اقرأ `CLAUDE.md`. مجلد `.ai/rules` غير موجود (تم التحقق في 2026-10-11).
> 3. الخطة السابقة `2026-10-11-findo-erp-03-delivery-marketing.md` نُفّذت بالكامل (آخر commit هو `0eef15e`، وهو منشور على `production`). هذه الخطة تبني عليها وتعيد استخدام أنماطها: `mark()`، و`prefill`، و`canCreate()`، ولوحات السجل داخل `form.blade.php`، والأوامر اليومية.

**Goal:** تنفيذ تاسكات Jira المستحقة 2026-10-12 والمسندة لـNE (FIN-17 وFIN-18) داخل محرّك التشغيل، مع اختبارات كاملة، وتشغيل محلي حقيقي، ونشر آمن.

**Architecture:** كل كيان أو حقل جديد يُضاف كـJSON في `config/operating.php`، فتعمل تلقائيًا الصلاحيات والتدقيق والقوائم والنماذج. القواعد في `OperatingWorkflow::save()` و`gate()` و`transition()`، والحسابات والآثار الجانبية في servicesين جديدين: `OperatingProcurement` (أرقام أوامر الشراء، والتزام أمر الشراء، وحالة الفاتورة من الدفعات، والتكلفة على المشروع، ولوحة المشتريات، والتنبيهات)، و`OperatingAssets` (رقم الأصل، والعهدة، والحركات، والمخزون، ونقص المخزون، ولوحة الأصول، والتنبيهات). **لا migrations ولا dependencies جديدة.**

**Tech Stack:** Laravel 13.34، PHP 8.4، PHPUnit 12.5 (SQLite in-memory)، PostgreSQL 17، Blade، وJS عادي.

**Spec:** صورتا Jira لـFIN-17 وFIN-18 (منقولتان حرفيًا أدناه)، والـroadmap في القسم 3.8 والمرحلة 7.

## نص التاسكات (من صور Jira، الموعد 2026-10-12، المسند إليه NE)

- **FIN-17 Manage Suppliers & Procurement:** إنشاء وإدارة ملفات الموردين. تسجيل بيانات الاتصال والاتفاقيات وشروط الدفع. إنشاء طلبات الشراء وأوامر الشراء. تتبع حالة الاعتماد ومواعيد التوريد وفواتير الموردين. ربط معاملات المشتريات بقسم الحسابات والمالية.
- **FIN-18 Manage Company Assets & Inventory:** تسجيل أصول الشركة والمعدات وأصناف المخزون. تتبع الأرقام التسلسلية وتواريخ الشراء والتكلفة والموظفين المستلمين. تسجيل نقل الأصول وإرجاعها وصيانتها. متابعة كميات المخزون وتنبيهات انخفاضه.

## تحليل: الموجود والناقص

الرموز: ✅ موجود ويكفي · 🟡 موجود جزئيًا · ❌ غير موجود.

| التاسك | البند | الحالة قبل الخطة | التاسك المنفّذ |
|---|---|---|---|
| **FIN-17** | ملفات الموردين | ❌ | T1 (كيان `suppliers`) |
| | الاتصال والاتفاقيات وشروط الدفع | ❌ (المرفقات العامة موجودة ✅) | T1 (حقول الاتصال، وملخص الاتفاقية ورابطها وتاريخ انتهائها، وشروط الدفع) |
| | طلبات الشراء | ❌ | T1 (`purchase_requests` بطلب من مديري الأقسام واعتماد owner) |
| | أوامر الشراء | ❌ | T2 (`purchase_orders` برقم تلقائي واعتماد) |
| | حالة الاعتماد | ❌ | T1 وT2 (بوابات الاعتماد، ويتحوّل الطلب إلى "تم الطلب" تلقائيًا عند اعتماد أمر الشراء) |
| | مواعيد التوريد | ❌ | T2 (`expected_delivery` و`delivered_at`، واستلام بعد الاعتماد) وT4 (تنبيه التأخير) |
| | فواتير الموردين | ❌ | T3 (`supplier_invoices` و`supplier_payments` في قسم المالية) |
| | الربط بالحسابات والمالية | 🟡 قسم `finance` فيه `budgets` و`costs` و`invoices` و`receipts` للعملاء فقط | T3 (الفواتير والدفعات في قسم المالية، وتكلفة تلقائية على المشروع، وحالة الفاتورة من الدفعات) وT4 (الذمم الدائنة وأعمارها) |
| **FIN-18** | تسجيل الأصول والمعدات | 🟡 `assets` موجود لكنه للأصول الرقمية والاشتراكات (نوع نص حر، ومالك، وتكلفة، وتجديد). **لا بيانات له على الإنتاج** (تم التحقق: 0 سجل) | T5 |
| | أصناف المخزون | ❌ | T7 (`inventory_items`) |
| | الرقم التسلسلي، وتاريخ الشراء، والتكلفة | 🟡 التكلفة فقط | T5 |
| | الموظف المستلم | 🟡 `primary_id` = المسؤول، لا عهدة | T5 وT6 (عهدة محسوبة من الحركات، والموظف يرى عهدته) |
| | النقل والإرجاع والصيانة | ❌ | T6 (`asset_movements`) |
| | الكميات وتنبيه الانخفاض | ❌ | T7 (`stock_movements`، وحد إعادة الطلب، وتنبيه فوري ويومي) |

## قرارات (محسومة: فوّض المستخدم كل القرارات لأفضل نتيجة منطقية، 2026-10-12)

| # | القرار | المحسوم والسبب |
|---|---|---|
| D1 | أين تعيش الكيانات | group جديد `procurement` ("المشتريات والموردون") فيه `suppliers` و`purchase_requests` و`purchase_orders`. أما `supplier_invoices` و`supplier_payments` فتُضاف إلى group `finance` الموجود. **السبب:** المتطلب يقول "اربطها بقسم الحسابات والمالية"، فالذمم الدائنة تعيش مع الفواتير والتحصيلات وتحت نفس الصلاحيات (owner وfinance). وgroup جديد `assets` ("الأصول والمخزون") فيه `assets` (يُنقل من `support`) و`asset_movements` و`inventory_items` و`stock_movements`. |
| D2 | من يطلب الشراء | مديرو الأقسام: `delivery` و`tech_lead` و`hr` و`marketing` و`sales_manager` و`support`، إضافة إلى owner وfinance. غير owner وfinance يرون الموردين (دليل بلا بيانات بنكية) وطلباتهم فقط، ولا يرون أوامر الشراء ولا الفواتير. الطالب يسجّل الطلب باسمه فقط (`requester_id` = موظفه). owner وfinance يسجّلان لأي موظف. |
| D3 | الاعتماد | `purchase_requests` و`purchase_orders` يعتمدهما **owner** (أو من عنده تفويض مكتوب `delegations`، وهي آلية موجودة). الرفض يحتاج نفس صلاحية الاعتماد. `supplier_invoices` يعتمدها owner أو finance (اعتماد للدفع). **السبب:** فصل المهام: الطالب لا يعتمد طلبه، والإنفاق يعتمده صاحب القرار. |
| D4 | دورة طلب الشراء | `requested → approved / rejected`، ثم `ordered` **تلقائيًا** عند اعتماد أمر شراء مربوط به (داخل نفس الـtransaction، مع event). immutable بعد `approved`. |
| D5 | دورة أمر الشراء | `draft → submitted → approved → received → closed`، و`cancelled` من draft أو submitted أو approved فقط. رقم تلقائي `PO-YYYY-NNNN`. لو مربوط بطلب: الطلب لازم يكون معتمدًا، ونفس العملة، والمبلغ لا يتجاوز تقدير الطلب بأكثر من 10% (وإلا يحتاج طلبًا جديدًا). المبلغ = الإجمالي شامل الضريبة. بعد الاعتماد يُسمح بتعديل حقلي التوريد فقط (`delivered_at` و`delivery_note`)، مثل نمط `deployments`. الاستلام يشترط `delivered_at`. |
| D6 | فواتير الموردين | `draft → approved → part_paid → paid`، و`void`. immutable بعد approved. رقم الفاتورة لا يتكرر لنفس المورد. لو مربوطة بأمر شراء: نفس المورد والعملة، والأمر معتمد أو مستلم أو مغلق، ومجموع فواتيره ≤ مبلغ الأمر. |
| D7 | الدفعات | `draft → active` (مؤكَّدة). immutable بعد active. مجموع الدفعات ≤ مبلغ الفاتورة (فحص + قفل، نفس نمط `receipts`). الفاتورة يجب أن تكون معتمدة. **حالة الفاتورة تتحدّث تلقائيًا**: كامل المبلغ ← `paid`، وجزء ← `part_paid`. وأرشفة دفعة مؤكَّدة تعيد حساب حالة الفاتورة. العملة تُشتق من الفاتورة. |
| D8 | الربط بتكلفة المشروع | اعتماد فاتورة مورد لها `project_id` ينشئ سجل `costs` (active) مربوطًا بها (`costs.supplier_invoice_id` حقل جديد اختياري)، بمبلغ = الإجمالي − الضريبة، وفئة `contractor` لمورد فئته freelancer/services، وإلا `tools`. فيدخل تلقائيًا في تقرير ربحية المشاريع الموجود (`reports()`). إلغاء الفاتورة (`void`) يؤرشف هذه التكلفة. سجل تكلفة واحد لكل فاتورة. |
| D9 | الإلغاء | `void` للفاتورة مسموح فقط بلا دفعات مؤكَّدة. |
| D10 | الأصول: إعادة تصميم `assets` | لا بيانات على الإنتاج، فلا ترحيل مطلوب. تُضاف: `category` (select إلزامي: أجهزة ومعدات واشتراكات ورخص ونطاقات واستضافة…)، و`asset_tag` (تلقائي `AST-NNNN` لو فارغ، ولا يتكرر)، و`serial_number` (لا يتكرر بين الأصول غير المؤرشفة)، و`brand_model`، و`supplier_id`، و`purchase_order_id`، و`purchase_date`، و`currency`، و`warranty_end`، و`condition`، و`location`. ويصبح `kind` اختياريًا بعنوان "تفاصيل النوع". تبقى الحقول الرقمية الحالية (التجديد، ومخزن الأسرار، والترخيص، والنسخ الاحتياطي) لأن الاشتراكات أصول أيضًا. الحالات: `draft, active, maintenance, retired, completed, archived` (`completed` باقٍ للتوافق). |
| D11 | العهدة (الموظف المستلم) | `assignee_id` **مفتاح محسوب** (ليس حقلًا في النموذج) لا يتغيّر إلا عبر حركة أصل مكتملة، فيبقى تاريخ العهدة كاملًا ومدقَّقًا. **الموظف يرى عهدته** (أي موظف مرتبط بحساب يرى الأصول المسندة إليه فقط، بحقول غير مالية). |
| D12 | حركات الأصول | كيان `asset_movements`: `assign` (تسليم لموظف، والأصل غير مسند)، و`transfer` (نقل لموظف آخر)، و`return` (إرجاع للمخزن)، و`maintenance` (إرسال للصيانة، والأصل يصبح `maintenance`)، و`maintenance_done` (عودة من الصيانة، مع التكلفة والمورد). الدورة `draft → completed`، والأثر يُطبَّق عند الإكمال داخل transaction مع event على الأصل. الحركة المكتملة لا تُعدَّل ولا تُؤرشف (الخطأ يُصحَّح بحركة معاكسة). يصل للمستلم إشعار عند التسليم أو النقل إليه. الأصل `retired` لا يتحرك، وإحالة الأصل للتقاعد تشترط أنه غير مسند وليس في الصيانة، ولا ينتقل الأصل يدويًا إلى/من `maintenance` (عبر الحركات فقط). |
| D13 | المخزون | `inventory_items`: `sku` (لا يتكرر)، و`category`، و`unit`، و`min_quantity` (حد إعادة الطلب)، و`location`، و`supplier_id`، و`unit_cost`، و`currency`. والكمية `quantity` **مفتاح محسوب** من `stock_movements` (`in` و`out` و`adjust` = جرد يضبط الكمية على المعدود). الدورة `draft → completed`، والصرف لا يُنزل الكمية تحت الصفر، والحركة المكتملة لا تُعدَّل. |
| D14 | تنبيه نقص المخزون | فوري عند إكمال حركة تنزل الكمية إلى الحد أو أقل، ويومي 08:15 `operating:asset-alerts` كشبكة أمان. مرة واحدة لكل نزول (marker `low_stock_alerted` يُمسح عند إعادة التعبئة فوق الحد). المستلمون: owner وfinance وhr. ويغطي الأمر نفسه انتهاء الضمان خلال 30 يومًا (مرة لكل تاريخ). |
| D15 | تنبيهات المشتريات | يومي 08:10 `operating:procurement-alerts`: توريد متأخر (أمر معتمد تجاوز موعده بلا استلام)، وفاتورة مورد مستحقة خلال 7 أيام أو متأخرة وغير مدفوعة بالكامل. مرة لكل تاريخ (markers). المستلمون: owner وfinance، ومُنشئ أمر الشراء. |
| D16 | الشاشات | `/admin/operations/procurement` (لوحة المشتريات: طلبات بانتظار الاعتماد، وطلبات معتمدة بلا أمر شراء، وأوامر بانتظار الاعتماد، وتوريد متأخر وقادم، وذمم دائنة بأعمارها لكل عملة، وأعلى الموردين إنفاقًا هذا العام). و`/admin/operations/assets/overview` (الأصول حسب الحالة والفئة، والعهد لكل موظف، والصيانة، والضمان القريب، ونقص المخزون، وآخر الحركات). ولوحات داخل صفحات السجل: المورد (الإنفاق والمستحق والأوامر والفواتير والأصول)، وأمر الشراء (المفوتر والمدفوع والمتبقي والتوريد)، وفاتورة المورد (الدفعات والمتبقي + زر "تسجيل دفعة")، والأصل (العهدة الحالية وتاريخ الحركات وتكلفة الصيانة + أزرار تسليم/نقل/إرجاع/صيانة)، والصنف (الكمية والحد والحركات + أزرار إضافة/صرف/جرد). |
| D17 | `prefill` لحقول select | يُعمَّم `?prefill[...]` ليقبل حقول select أيضًا (بشرط أن تكون القيمة ضمن الخيارات)، لأزرار الحركات. |
| D18 | الأرقام | لكل عملة على حدة، بلا تحويل. العملة الافتراضية `USD`. |
| D19 | حسابات الاختبار المحلي | في `findo_local` فقط. يُعاد استخدام `qa-*@findo.local`، ويُضاف `qa-finance` و`qa-hr` لو غير موجودين، بموظفين نشطين مرتبطين. لا شيء يُنشأ على السيرفر. |
| D20 | الدمج | `--ff-only` إلى `main`، مثل الخطط السابقة. |

## Global Constraints
- لا dependencies جديدة ولا migrations جديدة.
- PHP المحلي: `PHP=/c/dev-tools/php84/php.exe` (الـ`php` في PATH هو 8.2).
- كل مسار جديد يبدأ بـ`/admin/operations/` ويُسجَّل **قبل** `/operations/{type}/create` و`/operations/{type}`.
- لا حذف فعلي. كل تعديل يكتب `operating_events` بسبب. الأخطاء بـ`$this->fail($ar, $en)` أو `$this->workflow->fail(...)`.
- النصوص العربية مفاتيح `__()`، وترجمتها في `lang/en.json` في نفس التاسك. تسميات الـconfig `{ar, en}`.
- التواريخ `Y-m-d`. الاستعلام على الأبناء عبر `operating_links`.
- داخل أي transaction: قفل `pg_advisory_xact_lock` على pgsql ثم `lockForUpdate()`.
- بعد أي تعديل PHP: `$PHP vendor/bin/pint --dirty --format agent`.
- الاختبارات: `$PHP artisan test --compact <path>`. خط الأساس قبل البدء يُسجَّل في Task 0.
- Commit لكل تاسك على الفرع `feature/erp-procurement-assets`، والرسالة تنتهي بـ`Co-Authored-By: Claude Opus 5.5 (1M context) <noreply@anthropic.com>`.

---

## File Structure

| الملف | جديد/تعديل | المسؤولية |
|---|---|---|
| `config/operating.php` | تعديل | groups `procurement` و`assets`، والكيانات الجديدة السبعة، وحقول `assets` و`costs.supplier_invoice_id`، وتسمية الحالة `maintenance` |
| `app/Services/OperatingAccess.php` | تعديل | قيود قسم المشتريات (الطالبون)، ورؤية الموظف لعهدته، وإخفاء البيانات الحساسة |
| `app/Services/OperatingWorkflow.php` | تعديل | قواعد الحفظ والبوابات والآثار عند الانتقال |
| `app/Services/OperatingProcurement.php` | جديد | الأرقام، والتزام الأمر، وتسوية الفاتورة، والتكلفة، واللوحات، والتنبيهات |
| `app/Services/OperatingAssets.php` | جديد | رقم الأصل، وتطبيق الحركات، والمخزون، واللوحات، والتنبيهات |
| `app/Console/Commands/SendProcurementAlerts.php` | جديد | `operating:procurement-alerts` |
| `app/Console/Commands/SendAssetAlerts.php` | جديد | `operating:asset-alerts` |
| `app/Http/Controllers/ProcurementController.php` | جديد | لوحة المشتريات ولوحة الأصول |
| `app/Http/Controllers/OperatingController.php` | تعديل | `prefill` للـselect، ولوحات السجلات، وإشارات (signals) جديدة |
| `routes/admin.php`، `routes/console.php` | تعديل | المسارات والجدولة |
| `resources/views/admin/operating/procurement-dashboard.blade.php` | جديد | لوحة المشتريات |
| `resources/views/admin/operating/assets-overview.blade.php` | جديد | لوحة الأصول والمخزون |
| `resources/views/admin/operating/procurement-panel.blade.php` | جديد | partial لوحات المورد وأمر الشراء والفاتورة |
| `resources/views/admin/operating/asset-panel.blade.php` | جديد | partial لوحات الأصل والصنف |
| `resources/views/admin/operating/form.blade.php`، `admin/layout.blade.php` | تعديل | تضمين اللوحات وروابط الـsidebar |
| `lang/en.json` | تعديل | الترجمات |
| `tests/Feature/Operating/SuppliersRequestsTest.php` | جديد | T1 |
| `tests/Feature/Operating/PurchaseOrdersTest.php` | جديد | T2 |
| `tests/Feature/Operating/SupplierInvoicesTest.php` | جديد | T3 |
| `tests/Feature/Operating/ProcurementDashboardTest.php` | جديد | T4 |
| `tests/Feature/Operating/AssetsRegisterTest.php` | جديد | T5 |
| `tests/Feature/Operating/AssetMovementsTest.php` | جديد | T6 |
| `tests/Feature/Operating/InventoryTest.php` | جديد | T7 |
| `tests/Feature/Operating/AssetsOverviewTest.php` | جديد | T8 |

---

### Task 0: التجهيز
- [ ] **Step 1:** `git status` نظيف على `main` ومطابق لـ`origin/main` (تم: `0eef15e`)، ثم `git checkout -b feature/erp-procurement-assets`.
- [ ] **Step 2:** خط الأساس: `$PHP artisan test --compact` (سجّل العدد).
- [ ] **Step 3:** Postgres المحلي: `/c/dev-tools/pgsql17/bin/pg_isready -h 127.0.0.1 -p 5432` (وإلا `pg_ctl -D /c/dev-tools/pgdata17 start`).

---

### Task 1: الموردون وطلبات الشراء + صلاحيات قسم المشتريات (FIN-17، D1 وD2 وD3 وD4)

**Files:** `config/operating.php`، `OperatingAccess.php`، `OperatingWorkflow.php`، `lang/en.json`، Test `SuppliersRequestsTest.php`.

**Interfaces — Produces:**
- group `procurement`: read `[owner, finance, delivery, tech_lead, hr, marketing, sales_manager, support]`، write `[owner, finance]`.
- `OperatingAccess::PROCUREMENT_MANAGERS = ['owner', 'finance']` و`OperatingAccess::PURCHASE_REQUESTERS = ['delivery', 'tech_lead', 'hr', 'marketing', 'sales_manager', 'support']`.
- كيان `suppliers` (group procurement): `category* (select: hosting, domain, cloud, api, saas, hardware, office, freelancer, services, other)`، `contact_name`، `email (email)`، `phone`، `website (url)`، `address (textarea)`، `tax_number`، `payment_terms (select: immediate, net_15, net_30, net_45, net_60, advance, other)`، `payment_terms_note`، `currency`، `bank_details (textarea)`، `agreement (textarea)`، `agreement_url (url)`، `agreement_end (date)`. الحالات `active, inactive, archived`.
- كيان `purchase_requests` (group procurement، `approve_roles: [owner]`، `immutable: [approved, rejected, ordered, archived]`): `requester_id* (relation employees)`، `supplier_id (relation suppliers)`، `category* (select: نفس فئات المورد + equipment)`، `description* (textarea)`، `quantity (number)`، `estimated_amount* (number)`، `currency* (text)`، `needed_by (date)`، `project_id (relation projects)`، `justification (textarea)`. الحالات `requested, approved, rejected, ordered, archived`.

**قواعد:**
- `can()`: لو الكيان في group procurement والدور ليس owner/finance: read مسموح فقط لـ`suppliers` و`purchase_requests`، وwrite مسموح فقط لـ`purchase_requests` للأدوار في `PURCHASE_REQUESTERS`.
- `query()`: `purchase_requests` لغير owner/finance → `owner_id = user` أو `data->requester_id = employee`.
- `fields()`/`visibleData()`: `suppliers` لغير owner/finance يخفي `bank_details` و`tax_number`.
- `save()`: `purchase_requests` لغير owner/finance: `requester_id` = موظفه وإلا fail "تسجّل طلب الشراء باسمك فقط."؛ و`estimated_amount > 0`.
- `gate()`: `purchase_requests`: `approved`/`rejected` فقط من `requested`، و`rejected` يحتاج صلاحية approve (`abort_unless(can approve)`)، و`ordered` لا يُطلب يدويًا (fail "يتحوّل الطلب إلى تم الطلب عند اعتماد أمر الشراء.")، ومن ليس owner لا يعتمد طلبًا أنشأه (fail).
- تسمية الحالات الجديدة: `requested` و`ordered` و`inactive` و`rejected` موجودة؟ تحقق، والناقص يُضاف في `statuses`.

**Tests (`SuppliersRequestsTest`):**
1. `test_finance_creates_a_supplier_and_requesters_see_it_without_bank_details`: finance ينشئ مورد ببيانات بنكية → 302 بلا أخطاء. delivery يفتح `/admin/operations/record/{id}?format=json` (أو `getJson`) → `data` بلا `bank_details`. delivery لا يستطيع POST `/admin/operations/suppliers` → 403.
2. `test_requesters_file_requests_for_themselves_and_see_only_theirs`: delivery (بموظف) ينشئ طلبًا لنفسه → نجاح. لموظف آخر → خطأ `workflow`. طلب لموظف آخر أنشأه owner لا يظهر في `getJson('/admin/operations/purchase_requests?format=json')` للـdelivery.
3. `test_only_the_owner_approves_or_rejects_and_ordered_is_automatic`: delivery يحاول approved → 403، وrejected → 403. owner approved → نجاح. owner يحاول `ordered` يدويًا → خطأ workflow.
4. `test_developers_cannot_see_procurement`: developer → `get('/admin/operations/suppliers')` → 403.

- [ ] Steps: الاختبار الفاشل → FAIL → config → access → workflow → PASS → الحزمة كاملة → pint → commit `feat(procurement): suppliers directory and purchase requests with owner approval`.

---

### Task 2: أوامر الشراء ومواعيد التوريد (FIN-17، D5)

**Files:** `config/operating.php`، `OperatingWorkflow.php`، Create `OperatingProcurement.php`، Test `PurchaseOrdersTest.php`.

**Interfaces — Produces:**
- كيان `purchase_orders` (group procurement، `approve_roles: [owner]`، `immutable: [closed, cancelled, archived]`): `supplier_id* (relation suppliers)`، `purchase_request_id (relation purchase_requests)`، `project_id (relation projects)`، `description* (textarea)`، `amount* (number)`، `tax_amount (number)`، `currency* (text)`، `order_date* (date)`، `expected_delivery* (date)`، `delivered_at (date)`، `delivery_note (textarea)`، `terms (textarea)`. الحالات `draft, submitted, approved, received, closed, cancelled, archived`. مفتاح محسوب `po_number`.
- `OperatingProcurement::nextOrderNumber(): string` (`PO-YYYY-NNNN`).
- `OperatingProcurement::DELIVERY_FIELDS = ['delivered_at', 'delivery_note']`.
- `OperatingProcurement::orderCommitment(object $order): array{billed: float, paid: float, remaining: float, invoices: int}` (يُستخدم في T3 وT4).

**قواعد:**
- `save()`: لو `purchase_request_id`: الطلب `approved` (أو `ordered` لنفس الأمر عند التعديل)، ونفس العملة (مقارنة upper/trim)، وفي حال اختلاف المورد بين الطلب والأمر لا مشكلة (الطلب قد لا يحدد موردًا)، لكن لو الطلب حدد موردًا يجب التطابق. `expected_delivery >= order_date`. `tax_amount <= amount`. الرقم يُولَّد عند الإنشاء داخل الـtransaction.
- داخل الـtransaction: لو الأمر `approved` أو `received`، يُسمح فقط بتغيير `DELIVERY_FIELDS` (نفس نمط deployments)، وإلا fail "بعد الاعتماد، سجّل بيانات التوريد فقط."
- `gate()`: المراحل بالترتيب `draft → submitted → approved → received → closed`. `cancelled` من draft/submitted/approved فقط. `approved`: المبلغ > 0، ولو مربوط بطلب: الطلب ما زال `approved`، والمبلغ ≤ تقدير الطلب × 1.10. `received`: `delivered_at` مطلوب. `closed` بعد received فقط.
- `transition()`: عند `approved` لأمر مربوط بطلب: الطلب → `ordered` (version+1، event بسبب "Purchase order PO-… approved").

**Tests:**
1. `test_orders_get_sequential_numbers_and_follow_stages`: إنشاء أمرين → `PO-2026-0001` و`PO-2026-0002`. تخطي submitted → خطأ.
2. `test_an_order_from_a_request_needs_an_approved_request_and_marks_it_ordered`: طلب `requested` → إنشاء أمر به → خطأ. طلب approved بتقدير 1000 → أمر 1200 → approve يفشل (تجاوز 10%). أمر 1050 → approve ينجح، والطلب أصبح `ordered`.
3. `test_after_approval_only_delivery_fields_change_and_receiving_needs_a_date`: أمر approved: تعديل المبلغ → خطأ، تعديل `delivered_at` → نجاح. انتقال received بلا تاريخ (على أمر آخر) → خطأ.
4. `test_cancel_is_not_allowed_after_receipt`.

- [ ] Steps كالمعتاد، ثم commit `feat(procurement): purchase orders with numbering, approval against the request and delivery tracking`.

---

### Task 3: فواتير الموردين والدفعات والربط بالمالية (FIN-17، D6 وD7 وD8 وD9)

**Files:** `config/operating.php`، `OperatingWorkflow.php`، `OperatingProcurement.php`، Test `SupplierInvoicesTest.php`.

**Interfaces — Produces:**
- كيان `supplier_invoices` (group finance، `approve_roles: [owner, finance]`، `immutable: [approved, part_paid, paid, void, archived]`): `supplier_id*`، `purchase_order_id (relation purchase_orders)`، `project_id`، `invoice_number*`، `invoice_date* (date)`، `amount*`، `tax_amount`، `currency*`، `due_date* (date)`، `evidence (textarea)`. الحالات `draft, approved, part_paid, paid, void, archived`.
- كيان `supplier_payments` (group finance، `immutable: [active, archived]`): `supplier_invoice_id*`، `amount*`، `paid_at* (date)`، `method (select: bank_transfer, cash, card, cheque, wallet, other)`، `reference*`، `evidence (textarea)`. الحالات `draft, active, archived`. مفتاح محسوب `currency`.
- `costs`: حقل جديد `supplier_invoice_id (relation supplier_invoices, optional)`.
- `OperatingProcurement::paidAmount(int $invoiceId, ?int $exceptPaymentId = null, array $statuses = ['active']): float`.
- `OperatingProcurement::settleInvoice(int $invoiceId, User $user, string $reason): void` (تُستدعى داخل transaction الدفعة: تحدّث الحالة approved/part_paid/paid مع event).
- `OperatingProcurement::bookProjectCost(object $invoice, User $user): ?int` و`voidProjectCost(object $invoice, User $user, string $reason): void`.

**قواعد:**
- `save()` فاتورة: رقم لا يتكرر لنفس المورد (غير المؤرشفة/الملغاة)، `tax_amount <= amount`، `due_date >= invoice_date`. لو `purchase_order_id`: نفس المورد، ونفس العملة، والأمر في approved/received/closed، و(مجموع الفواتير الأخرى غير void/archived + هذه) ≤ مبلغ الأمر.
- `save()` دفعة: الفاتورة في approved/part_paid، و`amount > 0`، ومجموع (active + draft الأخرى) + هذه ≤ مبلغ الفاتورة، مع إعادة الفحص بقفل داخل الـtransaction (نمط receipts). `currency` = عملة الفاتورة.
- `gate()` فاتورة: `approved` من draft فقط ويحتاج `evidence`. `part_paid`/`paid` لا تُطلب يدويًا (fail "حالة الدفع تُحسب من الدفعات المسجلة."). `void` يحتاج صفر دفعات active. 
- `gate()` دفعة: `active` يحتاج `reference`، والفاتورة ما زالت approved/part_paid، والمجموع active + هذه ≤ المبلغ. أرشفة دفعة active مسموحة لـowner/finance فقط وتعيد التسوية.
- `transition()`: فاتورة → approved: `bookProjectCost`. فاتورة → void: `voidProjectCost`. دفعة → active أو archived: `settleInvoice`.

**Tests:**
1. `test_invoice_numbers_are_unique_per_supplier_and_orders_cap_billing`.
2. `test_payments_cannot_exceed_the_invoice_and_settle_its_status`: فاتورة 1000 approved: دفعة 400 → active → الفاتورة part_paid. دفعة 700 → خطأ. دفعة 600 → active → paid. أرشفة دفعة 600 → part_paid.
3. `test_paid_status_is_never_set_by_hand_and_void_needs_no_payments`.
4. `test_approving_a_project_invoice_books_one_project_cost_and_void_archives_it`: فاتورة 1150 وضريبة 150 ومشروع → approved → سجل costs واحد `actual_cost` = 1000 وفئة tools و`supplier_invoice_id`. void → التكلفة archived.
5. `test_only_finance_and_owner_see_supplier_invoices`: delivery → 403 على القائمة.

- [ ] commit `feat(finance): supplier invoices and payments with automatic settlement and project cost booking`.

---

### Task 4: لوحة المشتريات، ولوحات السجلات، وتنبيهات المشتريات (FIN-17، D15 وD16)

**Files:** Create `ProcurementController.php`، `procurement-dashboard.blade.php`، `procurement-panel.blade.php`، `SendProcurementAlerts.php`. Modify `OperatingProcurement.php`، `OperatingController.php` (`form()` و`signals()`)، `form.blade.php`، `layout.blade.php`، `routes/admin.php`، `routes/console.php`، `lang/en.json`. Test `ProcurementDashboardTest.php`.

**Interfaces — Produces:**
- `OperatingProcurement::dashboard(User $user): array{pending_requests: list, approved_requests: list, pending_orders: list, late_deliveries: list, upcoming_deliveries: list, payables: list<array{currency, outstanding, current, d1_30, d31_60, d61_90, d90_plus, due_7}>, due_invoices: list, top_suppliers: list<array{id, title, currency, spend, outstanding}>, counts: array}`. الأقسام المالية (payables وdue_invoices وtop_suppliers) فقط لمن يقرأ `supplier_invoices`.
- `OperatingProcurement::panel(User $user, object $record): ?array` حسب النوع: `suppliers` (الإنفاق هذا العام لكل عملة، والمستحق، وآخر الأوامر والفواتير، والأصول المرتبطة)، و`purchase_orders` (`orderCommitment` + الفواتير + حالة التوريد late/on_time)، و`supplier_invoices` (المدفوع والمتبقي والدفعات + `canPay`).
- `OperatingProcurement::sendAlerts(CarbonImmutable $now): array{late_deliveries: int, invoices_due: int, invoices_overdue: int}`، مع markers `delivery_alerted_for` و`due_alerted_for` و`overdue_alerted_for`.
- المسار `GET /admin/operations/procurement` → `ProcurementController@dashboard` (قبل `{type}`).
- جدولة `operating:procurement-alerts` يوميًا 08:10.
- signals: أمر معتمد تجاوز موعد توريده بلا استلام، وفاتورة مورد متأخرة غير مدفوعة، واتفاقية مورد منتهية.

**Tests:**
1. `test_dashboard_shows_approvals_deliveries_and_payables_aging`: travelTo 2026-10-20. طلب requested، أمر submitted، أمر approved موعده 2026-10-10 بلا استلام، فاتورة 500 مستحقة 2026-09-01 (aging 31–60)، وفاتورة 200 مستحقة 2026-10-25 (current + due_7). owner يرى كل الأقسام والأرقام. delivery يفتح اللوحة → 200 بلا أقسام مالية (لا يرى المبلغ 500).
2. `test_record_panels_show_commitment_and_remaining`: صفحة الفاتورة تعرض المتبقي، وصفحة الأمر تعرض المفوتر.
3. `test_procurement_alerts_are_sent_once_per_date`: الأمر يرسل للتوريد المتأخر والفاتورة المتأخرة، والتشغيل الثاني يرسل 0.

- [ ] commit `feat(procurement): procurement dashboard with payables aging, record panels and daily alerts`.

---

### Task 5: سجل الأصول (FIN-18، D1 وD10 وD11)

**Files:** `config/operating.php` (نقل `assets` إلى group `assets` الجديد وحقوله)، `OperatingAccess.php`، `OperatingWorkflow.php`، Create `OperatingAssets.php` (الجزء الأول)، Test `AssetsRegisterTest.php`.

**Interfaces — Produces:**
- group `assets` ("الأصول والمخزون"): read/write `[owner, finance, hr, delivery, tech_lead, support]` (يشمل كل من كان يقرأ `support` سابقًا).
- `assets` الحقول بالترتيب: `category* (select: laptop, desktop, monitor, phone, tablet, network, printer, furniture, vehicle, software_license, subscription, hosting, domain, other)`، `kind (اختياري، "تفاصيل النوع")`، `asset_tag`، `serial_number`، `brand_model`، `primary_id`، `backup_id`، `supplier_id (relation suppliers)`، `purchase_order_id (relation purchase_orders)`، `purchase_date`، `cost`، `currency`، `warranty_end`، `condition (select: new, good, fair, damaged)`، `location`، `renewal_date`، `usage`، `reference_url`، `vault_reference`، `license`، `backup_evidence`. الحالات `draft, active, maintenance, retired, completed, archived`.
- مفتاح محسوب `assignee_id`.
- `OperatingAccess::ASSET_MANAGERS` = قائمة read للـgroup. أي مستخدم له موظف يقرأ `assets` لكن `query()` يقصره على `data->assignee_id = employee`، و`visibleData()` يعرض له `category, kind, asset_tag, serial_number, brand_model, condition, location, assignee_id` فقط، و`fields()` نفس القائمة (للعرض فقط؛ لا write).
- `OperatingAssets::nextTag(): string` (`AST-NNNN`).

**قواعد:**
- `save()`: `asset_tag` فارغ عند الإنشاء ← يُولَّد. `asset_tag` و`serial_number` لا يتكرران بين الأصول غير المؤرشفة (مقارنة case-insensitive بعد trim). `warranty_end >= purchase_date`.
- `gate()`: الانتقال إلى/من `maintenance` يدويًا → fail "استخدم حركة صيانة." `retired` يشترط أن الأصل غير مسند. لا انتقال من `retired` إلا `archived`.

**Tests:**
1. `test_assets_get_a_tag_and_serials_are_unique`.
2. `test_employees_see_only_their_own_custody_without_costs`: أصل مسند لموظف مطوّر (data assignee_id) وآخر غير مسند. developer → قائمة JSON فيها أصله فقط، و`data` بلا `cost`. developer POST `/admin/operations/assets` → 403.
3. `test_assets_cannot_enter_maintenance_by_hand_or_retire_while_assigned`.
4. `test_asset_managers_keep_access`: support ينشئ أصلًا بنجاح.

- [ ] commit `feat(assets): company asset register with tags, serials, warranty and employee custody view`.

---

### Task 6: حركات الأصول: تسليم ونقل وإرجاع وصيانة (FIN-18، D12 وD17)

**Files:** `config/operating.php`، `OperatingWorkflow.php`، `OperatingAssets.php`، `OperatingController.php` (`prefill` للـselect)، Test `AssetMovementsTest.php`.

**Interfaces — Produces:**
- كيان `asset_movements` (group assets، `immutable: [completed, archived]`): `asset_id* (relation assets)`، `movement* (select: assign, transfer, return, maintenance, maintenance_done)`، `to_employee_id (relation employees)`، `movement_date* (date)`، `condition (select)`، `cost (number)`، `currency`، `supplier_id (relation suppliers)`، `notes (textarea)`. الحالات `draft, completed, archived`. مفاتيح محسوبة: `from_employee_id`.
- `OperatingAssets::applyMovement(object $movement, User $user, string $reason): array` (داخل transaction الانتقال، يرجع data الحركة بعد إضافة `from_employee_id`).
- `OperatingAssets::movements(int $assetId): Collection`.

**قواعد:**
- `save()`: `assign`/`transfer` يحتاجان `to_employee_id`، والموظف `active`. `maintenance_done` قد يحمل cost.
- `gate()` حركة: `completed` من draft فقط؛ أرشفة حركة completed → fail "صحّح بحركة معاكسة.". وفي `applyMovement` (بعد قفل الأصل): الأصل ليس archived/retired؛ `assign` يشترط أصلًا غير مسند وليس في صيانة؛ `transfer` يشترط أصلًا مسندًا ولموظف مختلف؛ `return` يشترط مسندًا؛ `maintenance` يشترط أصلًا ليس في صيانة؛ `maintenance_done` يشترط أصلًا في صيانة. الأثر: assign/transfer ← `assignee_id`، و`status` ← active لو كان draft؛ return ← `assignee_id = null`؛ maintenance ← `status = maintenance` و`status_before_maintenance`؛ maintenance_done ← يعود للحالة السابقة (active). `condition` لو مُدخل يُحدّث الأصل. event على الأصل بـversion+1.
- بعد الـtransaction: إشعار `asset_assigned` للمستلم في assign/transfer.
- `prefill`: حقل select يُقبل لو القيمة ضمن الخيارات.

**Tests:**
1. `test_assigning_then_transferring_moves_custody_and_notifies_the_receiver`.
2. `test_invalid_movements_are_rejected`: transfer لأصل غير مسند، return لأصل غير مسند، assign لأصل مسند، maintenance_done لأصل ليس في الصيانة.
3. `test_maintenance_round_trip_restores_the_asset`.
4. `test_completed_movements_are_permanent`.
5. `test_create_form_prefills_the_movement_kind`: `get('/admin/operations/asset_movements/create?prefill[asset_id]=X&prefill[movement]=return')` → يحتوي `value="return" selected`.

- [ ] commit `feat(assets): audited custody movements for assignment, transfer, return and maintenance`.

---

### Task 7: المخزون وحركاته وتنبيه الانخفاض (FIN-18، D13 وD14)

**Files:** `config/operating.php`، `OperatingWorkflow.php`، `OperatingAssets.php`، Test `InventoryTest.php`.

**Interfaces — Produces:**
- كيان `inventory_items` (group assets): `sku`، `category* (select: consumable, spare_part, office_supply, it_accessory, cleaning, merchandise, other)`، `unit* (select: piece, box, pack, kg, liter, meter, other)`، `min_quantity* (number)`، `location`، `supplier_id`، `unit_cost`، `currency`، `notes (textarea)`. الحالات `active, inactive, archived`. مفاتيح محسوبة `quantity` و`low_stock_alerted`.
- كيان `stock_movements` (group assets، `immutable: [completed, archived]`): `item_id* (relation inventory_items)`، `movement* (select: in, out, adjust)`، `quantity* (number)`، `movement_date* (date)`، `purchase_order_id`، `assignee_id (relation employees، المستلم عند الصرف)`، `notes`. الحالات `draft, completed, archived`. مفاتيح محسوبة `quantity_before` و`quantity_after`.
- `OperatingAssets::applyStock(object $movement, User $user, string $reason): array`.
- `OperatingAssets::checkStock(object $item): bool` (يرسل تنبيه الانخفاض مرة واحدة أو يمسح الـmarker عند التعبئة؛ يرجع true لو أرسل).
- `OperatingAssets::stockLevel(object $item): array{quantity: float, min: float, low: bool}`.

**قواعد:**
- `save()` صنف: `sku` لا يتكرر. عند الإنشاء `quantity = 0`.
- `save()` حركة: `quantity > 0` لـin/out، و`>= 0` لـadjust. الصنف active.
- `gate()` حركة: completed من draft فقط، وأرشفة completed ممنوعة.
- `applyStock`: قفل الصنف، in ← +q، out ← −q (fail لو النتيجة < 0 "الكمية المتاحة لا تكفي.")، adjust ← q. يحدّث `quantity` (version+1، event على الصنف).
- بعد الـtransaction: `checkStock(item)`.
- signal: صنف `quantity <= min_quantity` → "المخزون عند حد إعادة الطلب أو أقل."

**Tests:**
1. `test_stock_movements_update_quantity_and_never_go_negative`.
2. `test_low_stock_alerts_once_per_drop_and_rearms_after_restock`.
3. `test_skus_are_unique_and_quantity_is_not_editable`: محاولة إرسال `data[quantity]` في الحفظ لا تغيّر الكمية.

- [ ] commit `feat(inventory): inventory items with stock movements and low-stock alerts`.

---

### Task 8: لوحة الأصول والمخزون، ولوحات السجلات، والتنبيهات اليومية (FIN-18، D14 وD16)

**Files:** `ProcurementController.php` (`assets()`)، Create `assets-overview.blade.php`، `asset-panel.blade.php`، `SendAssetAlerts.php`. Modify `OperatingAssets.php`، `OperatingController.php`، `form.blade.php`، `layout.blade.php`، `routes/*`، `lang/en.json`. Test `AssetsOverviewTest.php`.

**Interfaces — Produces:**
- `OperatingAssets::overview(User $user): array{by_status: array, by_category: array, custody: list<array{employee, count}>, maintenance: list, warranty: list, low_stock: list, recent: list, counts: array}`.
- `OperatingAssets::panel(User $user, object $record): ?array` (`assets`: العهدة الحالية، والحركات، وتكلفة الصيانة لكل عملة، و`actions` المتاحة حسب الحالة؛ `inventory_items`: المستوى والحركات).
- `OperatingAssets::sendAlerts(CarbonImmutable $now): array{low_stock: int, warranty: int}` (marker `warranty_alerted_for`).
- `GET /admin/operations/assets/overview` (قبل `{type}`)، وجدولة `operating:asset-alerts` 08:15.

**Tests:**
1. `test_overview_summarises_custody_maintenance_warranty_and_low_stock`.
2. `test_asset_page_shows_custody_history_and_only_valid_actions`: أصل مسند → الصفحة فيها "نقل" و"إرجاع" وليس "تسليم".
3. `test_asset_alerts_cover_warranty_and_low_stock_once`.
4. `test_employees_without_asset_management_cannot_open_the_overview`: developer → 403.

- [ ] commit `feat(assets): assets and inventory overview, record panels and daily alerts`.

---

### Task 9: التحقق الكامل (اختبارات + مراجعة + تشغيل محلي حقيقي)
- [ ] **Step 1:** `$PHP artisan test --compact` → كل الاختبارات ناجحة. أي فشل: skill `systematic-debugging`.
- [ ] **Step 2:** `$PHP vendor/bin/pint --format agent`، ثم `git diff --stat`.
- [ ] **Step 3:** سلامة الـconfig والمسارات: عدد الكيانات (+6)، و`route:list --path=operations` (المساران الجديدان قبل `{type}`)، و`schedule:list` (الأمران الجديدان).
- [ ] **Step 4:** مراجعة الكود (agent `pr-review-toolkit:code-reviewer` على diff الفرع)، وإصلاح الملاحظات المؤكدة مع اختبار لكل إصلاح سلوكي.
- [ ] **Step 5:** التشغيل المحلي على PostgreSQL: `migrate:status`، ثم `config:clear` و`view:clear`، ثم `$PHP artisan serve --port=8080` في الخلفية. حسابات QA (D19).
- [ ] **Step 6: سيناريوهات المتصفح** (Playwright MCP، أو CDP headless Chrome لو فشل، حسب ذاكرة `local-qa-setup`) مع screenshots على عرضي 1366 و375:
  1. **FIN-17:** qa-finance ينشئ موردًا بشروط دفع واتفاقية → qa-delivery يطلب شراء لنفسه (ولا يرى البيانات البنكية) → owner يعتمد → qa-finance ينشئ أمر شراء من الطلب ويقدّمه → owner يعتمده (الطلب يصبح "تم الطلب") → استلام بتاريخ → فاتورة مورد بمشروع → اعتماد (تظهر تكلفة على المشروع) → دفعة جزئية ثم كاملة (part_paid ثم paid) → لوحة المشتريات (الأعمار والتوريد) → `operating:procurement-alerts`.
  2. **FIN-18:** qa-hr يسجل لابتوب برقم تسلسلي → تسليم لـqa-developer (يصله إشعار، ويرى عهدته فقط بلا تكلفة) → نقل لموظف آخر → صيانة وعودة بتكلفة → إرجاع. صنف مخزون بحد 5 → إضافة 10 → صرف 6 (تنبيه نقص) → صرف 10 يُرفض → لوحة الأصول والمخزون.
  3. صفحة واحدة على الأقل بالإنجليزية، ولا أخطاء JS، ولا تمرير أفقي على 375.
- [ ] **Step 7:** راقب `storage/logs/laravel.log`.
- [ ] **Step 8:** أوقف السيرفر المحلي، وcommit لأي إصلاحات.
- [ ] **Step 9:** skill `verification-before-completion`.

### Task 10: الدمج والرفع والنشر (بدون فقد أي تعديل على السيرفر)
- [ ] **Step 1:** `bin/server-status`. لو فيه فروقات: `bin/server-pull` ثم حل التعارض مع الحفاظ على تعديلات السيرفر، ثم الاختبارات.
- [ ] **Step 2:** `git checkout main && git pull --ff-only origin main && git merge --ff-only feature/erp-procurement-assets` (لو رُفض: rebase ثم الاختبارات).
- [ ] **Step 3:** `git push origin main` والفرع.
- [ ] **Step 4:** `git diff --stat origin/production..main -- database/migrations` (فارغ).
- [ ] **Step 5:** `bin/server-deploy --dry-run` ثم `bin/server-deploy`.
- [ ] **Step 6: تحقق بعد النشر:** HTTP 200، و`route:list --path=operations/procurement` و`--path=operations/assets`، و`schedule:list` (الأمران)، وتشغيل يدوي آمن للأمرين (قراءة/تنبيهات فقط، ولا بيانات حاليًا)، وsmoke قراءة فقط للصفحتين الجديدتين كـowner عبر HTTP kernel (حسب ذاكرة `local-qa-setup`).
- [ ] **Step 7:** `git log origin/production -1`، ولو لم يُدفع `production` والـtags: `git push origin production --tags`.

### Task 11: رسالتا الجروب
- [ ] **الرسالة 1 (ما تم):** plain text بالعربية بلا Markdown. لكل تاسك ما تم بلغة المستخدم، ثم الصلاحيات، وملاحظات التشغيل (التنبيهات اليومية، والحالة التلقائية للطلب والفاتورة، والتكلفة على المشروع، والعهدة لا تتغير إلا بحركة).
- [ ] **الرسالة 2 (أماكن التجربة):** plain text، ولكل ميزة الرابط الكامل `https://findogroup.com/admin/...`، والدور، وخطوات مرقمة.

---

## Self-Review (أُجري عند كتابة الخطة)

**1. تغطية المتطلبات:** FIN-17: الموردون (T1)، والاتصال والاتفاقيات وشروط الدفع (T1)، وطلبات وأوامر الشراء (T1 وT2)، وحالة الاعتماد (T1 وT2)، ومواعيد التوريد (T2 وT4)، وفواتير الموردين (T3)، والربط بالمالية (T3 وT4). ✅ FIN-18: الأصول والمعدات (T5)، وأصناف المخزون (T7)، والتسلسلي والشراء والتكلفة والمستلم (T5 وT6)، والنقل والإرجاع والصيانة (T6)، والكميات وتنبيه الانخفاض (T7 وT8). ✅

**2. Placeholders:** الكود يُكتب أثناء التنفيذ مقابل الاختبارات المحددة أعلاه بأسمائها وسيناريوهاتها. القواعد مكتوبة نصًا بقيمها (10%، 7 أيام، 30 يومًا، أوقات الجدولة، والرسائل الأساسية).

**3. اتساق الأسماء:** `OperatingProcurement::nextOrderNumber/orderCommitment/paidAmount/settleInvoice/bookProjectCost/voidProjectCost/dashboard/panel/sendAlerts`، و`OperatingAssets::nextTag/applyMovement/movements/applyStock/checkStock/stockLevel/overview/panel/sendAlerts`. والمفاتيح المحسوبة `po_number`، و`currency` (للدفعة)، و`assignee_id` (للأصل)، و`from_employee_id`، و`status_before_maintenance`، و`quantity`، و`low_stock_alerted`، و`quantity_before/after`، و`delivery_alerted_for`، و`due_alerted_for`، و`overdue_alerted_for`، و`warranty_alerted_for`.

**4. مخاطر معروفة:**
- `save()` يبني `data` من الحقول + الموجود، فالمفاتيح المحسوبة تبقى، لكن **يجب** ألا تكون `quantity` و`assignee_id` حقولًا في النموذج، وإلا يكتبها المستخدم.
- نقل `assets` إلى group جديد يغيّر مكانه في الـsidebar فقط، والأدوار القديمة باقية ضمن القراءة والكتابة.
- رؤية الموظف لعهدته تجعل `can(read, assets)` صحيحًا لكل موظف، فيظهر "الأصول" في الـsidebar لكل الموظفين، وهذا مقصود ("عهدتي").
- آثار التسوية والتكلفة والحركات تتم داخل transaction الانتقال، والإشعارات بعدها.
