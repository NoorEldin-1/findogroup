# Findo ERP — تاسكات 10 أكتوبر (FIN-7 → FIN-11): Leads، العملاء، العروض، المتابعات، المشاريع — خطة التنفيذ

> **For agentic workers:** REQUIRED SUB-SKILL: Use `subagent-driven-development` (recommended) or `executing-plans` to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.
>
> **قبل أي شيء:**
> 1. اقرأ `docs/superpowers/plans/2026-10-06-findo-erp-00-roadmap.md`، **القسم 2 كاملًا** (القواعد الثابتة)، والقسم 3.4 و3.5 و3.6.
> 2. اقرأ `CLAUDE.md`. مجلد `.ai/rules` غير موجود (تم التحقق في 2026-10-10).
> 3. نفّذ التاسكات **بالترتيب**. أسماء الدوال وتواقيعها في بلوك Interfaces لكل تاسك.
> 4. إن اختلف الكود عن المقتطفات (أرقام أسطر)، ابحث بالنص المميز وطبّق نفس المعنى.

**Goal:** تنفيذ تاسكات Jira الخمسة المستحقة 2026-10-10 (FIN-7 إلى FIN-11) داخل محرّك التشغيل الحالي، مع اختبارات كاملة وتشغيل محلي ونشر آمن.

**Architecture:** كل كيان جديد أو حقل جديد يُضاف إلى `config/operating.php` (JSON)، فيعمل التدقيق والصلاحيات والقوائم والنماذج العامة تلقائيًا. القواعد في `OperatingWorkflow::save()` و`gate()`. الحسابات في services جديدة: `OperatingSales` (pipeline، تحويل lead لعميل، ملف العميل)، و`OperatingFollowUps` (لوحة المتابعات والتذكيرات)، و`OperatingQuotes` (بنود العرض والإجماليات والترقيم والمستند)، و`OperatingProjects` (نسبة الإنجاز ولوحة المشاريع). الشاشات الخاصة views إضافية فوق نفس البيانات، وكنترولرين جديدين: `SalesController` و`ProjectController`. **لا migrations جديدة** في هذه الخطة.

**Tech Stack:** Laravel 13.34، PHP 8.4، PHPUnit 12.5 (SQLite in-memory)، PostgreSQL 17، Blade، JS عادي في `public/admin-assets/operating.js` (ليس Vite).

**Spec:** صور Jira (FIN-7..FIN-11) كما في جدول التغطية أدناه + الـroadmap الأقسام 3.4، 3.5، 3.6، والمرحلة 4 والمرحلة 6 و10a.

## تحليل: الموجود والناقص لكل تاسك

| التاسك | البند | الحالة قبل الخطة | التاسك المنفّذ |
|---|---|---|---|
| **FIN-7** Leads & Pipeline | إنشاء وإدارة الـleads | 🟡 `opportunities` موجود لكن `client_id` **إلزامي** (لا يمكن تسجيل lead قبل أن يصبح عميلًا) | T2 |
| | مصدر الـlead | 🟡 `source` نص حر | T2 (`lead_source` select + `source` يصبح تفاصيل) |
| | بيانات الاتصال | ❌ | T2 (`lead_company/lead_contact/lead_phone/lead_email`) |
| | مندوب المبيعات المسؤول | ❌ | T1 (دور `sales_manager` + نطاق الرؤية) + T2 (`assignee_id`) |
| | المراحل New → Qualified → Proposal → Won/Lost | ✅ الحالات موجودة، و"لوحة مراحل" بسيطة (25 سجل بلا قيم) | T2 (لوحة Pipeline بالقيم والتوقع) |
| | قيمة الصفقة والاحتمالية | ❌ | T2 (`expected_value/currency/probability/expected_close`) |
| | تسجيل المتابعات والاجتماعات والأنشطة | ❌ (فقط `next_action` و`follow_up`) | T3 (`activities`) |
| **FIN-8** Customers | ملف عميل مركزي | 🟡 `clients` (company, decision_maker, contact, email, notes) | T4 (هاتف، عنوان، مجال، دولة، رقم ضريبي، موقع) |
| | بيانات الاتصال وسجل التواصل | ❌ | T4 (`contacts`) + T3 (`activities` = سجل التواصل) |
| | ربط العميل بالمشاريع والعقود والفواتير والفرص | 🟡 الروابط موجودة كقائمة "سجلات مرتبطة" فقط | T4 (صفحة ملف العميل 360°) |
| | حالة العميل وتاريخ العلاقة | 🟡 الحالات موجودة (draft/active/completed/archived) | T4 (الخط الزمني + المتبقي عليه) |
| **FIN-9** Quotations | إنشاء عروض للمشاريع والخدمات | ✅ `quotes` | — |
| | بنود (منتجات/خدمات، كمية، سعر، خصم، ضريبة) | ❌ (سعر واحد + خصم) | T5 (نوع حقل `lines` + إجماليات) |
| | PDF قابل للتحميل | ❌ (لا مكتبة PDF) | T6 (صفحة مستند للطباعة/حفظ PDF، انظر القرار Q1) |
| | تتبع الحالة | ✅ draft/submitted/approved/rejected/archived | — |
| | تحويل العرض المعتمد إلى مشروع أو أمر بيع | ❌ | T6 (تحويل لمشروع، أو لعقد = أمر البيع في النظام) |
| **FIN-10** Follow-ups | جدولة متابعات للـleads والعملاء | ❌ | T3 |
| | إسنادها لمندوبي المبيعات | ❌ | T3 |
| | تذكيرات ومواعيد استحقاق | ❌ | T3 (أمر مجدول كل 15 دقيقة + إشعارات) |
| | تتبع المكتملة والمعلقة والمتأخرة | ❌ | T3 (لوحة المتابعات) |
| **FIN-11** Projects | إنشاء مشروع وتعيين PM | ✅ `projects.manager_id` | — |
| | أعضاء الفريق | 🟡 ضمنيًا عبر المهام | T7 (`project_members`) |
| | النطاق والمحطات والمواعيد | ✅ `scopes`، `milestones`، `original_due/approved_due` | — |
| | الأولويات | ❌ | T7 (`priority`) |
| | الحالة والتقدم ونسبة الإنجاز | 🟡 الحالة فقط | T7 (`OperatingProjects::progress` + لوحة المشاريع) |
| | الربط بالعملاء والعقود والميزانيات والموظفين | ✅ `client_id`، `contract_id`، `budgets.project_id` + T7 (الأعضاء) | T7 |

## قرارات (محسومة: فوّض المستخدم كل القرارات للتنفيذ بأفضل نتيجة منطقية، 2026-10-10)

| # | القرار | المحسوم |
|---|---|---|
| Q1 | PDF العرض | صفحة مستند A4 مصممة للطباعة، وزر "تحميل PDF" يفتح حفظ PDF في المتصفح. **السبب:** مكتبات PDF في PHP (dompdf) تكسر تشكيل الحروف العربية، و`mpdf` ثقيلة وتضيف dependency تُثبَّت على السيرفر الإنتاجي. المتصفح يعطي عربية سليمة 100% وبلا مخاطرة نشر. |
| Q2 | اعتماد عرض بلا نطاق (scope) | اليوم الاعتماد يشترط نسخة نطاق معتمدة، وهذا يستلزم وجود مشروع قبل العرض، فيتعارض مع "تحويل العرض المعتمد إلى مشروع". **الجديد:** لو العرض مربوط بنطاق فالنطاق يجب أن يكون معتمدًا (القاعدة القديمة كما هي). لو غير مربوط فيلزمه بنود (line items). |
| Q3 | رؤية المبيعات | دور جديد `sales_manager` يرى كل الفرص والمتابعات ويوزّعها. دور `sales` يرى فقط ما أنشأه أو المُسند إليه. **أثر على البيانات الحالية:** الفرص القديمة بلا `assignee_id` يراها منشئها وowner وsales_manager فقط. |
| Q4 | `source` القديم | لا يتغير نوعه (القاعدة 6.6 في الـroadmap). يُضاف `lead_source` (select إلزامي)، و`source` يصبح اختياريًا بعنوان "تفاصيل المصدر". السجلات القديمة تطلب اختيار المصدر عند أول تعديل. |
| Q5 | أمر البيع (Sales order) | لا كيان منفصل. العقد (`contracts`) هو أمر البيع في النظام، والتحويل ينشئ مسودة عقد مملوءة من العرض. |
| Q6 | حسابات الاختبار المحلي | تُنشأ مستخدمين QA محليين في `findo_local` **فقط** (owner/sales/sales_manager/delivery/developer، بريد `qa-<role>@findo.local`) مع سجلات موظفين مرتبطة، عبر `tinker`. لا شيء يُنشأ على السيرفر. |
| Q7 | طريقة الدمج | `--ff-only` إلى `main` (نفس تاريخ المرحلة 1 الخطي). |

## Global Constraints
- لا dependencies جديدة (composer/npm) ولا migrations جديدة.
- PHP المحلي: **`/c/dev-tools/php84/php.exe`** (الـ`php` في PATH هو 8.2 من XAMPP ويفشل). اكتب في الأوامر `PHP=/c/dev-tools/php84/php.exe` ثم `$PHP artisan …`.
- كل مسار جديد يبدأ بـ`/admin/operations/` ويُسجَّل **قبل** `Route::get('/operations/{type}/create', …)` في `routes/admin.php`.
- لا حذف فعلي، وكل تعديل يكتب `operating_events` بـreason. رسائل الأخطاء `$this->workflow->fail($ar, $en)` أو `$this->fail($ar, $en)` داخل `OperatingWorkflow`.
- النصوص العربية مفاتيح `__()`، والترجمة الإنجليزية تُضاف إلى `lang/en.json` في نفس التاسك.
- التواريخ والأوقات: `Y-m-d` و`Y-m-d\TH:i` (المقارنة النصية صحيحة).
- العملة الافتراضية لو فارغة: `USD`. الإجماليات تُجمَّع لكل عملة على حدة، بلا تحويل.
- الاستعلامات على مفاتيح JSON بقائمة IDs: استخدم `operating_links` (`field` + `target_id`) بدل `whereIn('data->x', …)`.
- بعد أي تعديل PHP: `vendor/bin/pint --dirty --format agent` (عبر `$PHP vendor/bin/pint …`).
- الاختبارات: `$PHP artisan test --compact <path>`. خط الأساس قبل البدء: **34 اختبارًا ناجحًا**.
- Commit لكل تاسك على الفرع `feature/erp-sales-crm-projects`، والرسالة تنتهي بـ:
  `Co-Authored-By: Claude Opus 5.5 (1M context) <noreply@anthropic.com>`

---

## File Structure

| الملف | جديد/تعديل | المسؤولية |
|---|---|---|
| `config/operating.php` | تعديل | دور `sales_manager`، حقول `opportunities` و`clients` و`quotes` و`projects`، كيانات `activities` و`contacts` و`project_members` |
| `app/Services/OperatingAccess.php` | تعديل | صلاحيات `sales_manager`، نطاق رؤية `sales`، رؤية أعضاء المشروع |
| `app/Services/OperatingWorkflow.php` | تعديل | نوع حقل `lines`، قواعد الحفظ والانتقال للكيانات الجديدة والمعدلة |
| `app/Services/OperatingSales.php` | جديد | pipeline، تحويل lead لعميل، ملف العميل 360° |
| `app/Services/OperatingFollowUps.php` | جديد | لوحة المتابعات، التذكيرات |
| `app/Services/OperatingQuotes.php` | جديد | تطبيع البنود، الإجماليات، الترقيم، مستند العرض، مسودة مشروع/عقد من العرض |
| `app/Services/OperatingProjects.php` | جديد | نسبة الإنجاز، لوحة المشاريع |
| `app/Services/OperatingReports.php` | تعديل | إضافة التقدم والفريق لتقرير المشروع |
| `app/Http/Controllers/SalesController.php` | جديد | pipeline، التحويل، ملف العميل، المتابعات، مستند العرض |
| `app/Http/Controllers/ProjectController.php` | جديد | لوحة المشاريع |
| `app/Http/Controllers/OperatingController.php` | تعديل | create من عرض، عنوان أولي، signals المتابعات |
| `app/Console/Commands/SendFollowUpReminders.php` | جديد | `operating:follow-up-reminders` |
| `routes/admin.php`، `routes/console.php` | تعديل | المسارات والجدولة |
| `resources/views/admin/operating/opportunities-pipeline.blade.php` | جديد | لوحة Pipeline |
| `resources/views/admin/operating/activities-followups.blade.php` | جديد | لوحة المتابعات |
| `resources/views/admin/operating/client-profile.blade.php` | جديد | ملف العميل 360° |
| `resources/views/admin/operating/line-items.blade.php` | جديد | محرر البنود (partial) |
| `resources/views/admin/operating/quote-print.blade.php` | جديد | مستند العرض A4 |
| `resources/views/admin/operating/projects-portfolio.blade.php` | جديد | لوحة المشاريع |
| `resources/views/admin/operating/form.blade.php` | تعديل | أزرار السياق، حقل `lines`، التقدم والفريق |
| `resources/views/admin/layout.blade.php` | تعديل | روابط الـsidebar |
| `public/admin-assets/operating.js`، `operating.css` | تعديل | محرر البنود، شريط التقدم |
| `lang/en.json` | تعديل | الترجمات |
| `tests/Feature/Operating/SalesAccessTest.php` | جديد | T1 |
| `tests/Feature/Operating/LeadsPipelineTest.php` | جديد | T2 |
| `tests/Feature/Operating/FollowUpsTest.php` | جديد | T3 |
| `tests/Feature/Operating/ClientProfileTest.php` | جديد | T4 |
| `tests/Feature/Operating/QuoteLinesTest.php` | جديد | T5 |
| `tests/Feature/Operating/QuoteDocumentTest.php` | جديد | T6 |
| `tests/Feature/Operating/ProjectLifecycleTest.php` | جديد | T7 |

---

### Task 0: التجهيز

- [ ] **Step 1:** `git status` نظيف وعلى `main` ومطابق لـ`origin/main`، ثم `git checkout -b feature/erp-sales-crm-projects`.
- [ ] **Step 2:** خط الأساس: `$PHP artisan test --compact` → 34 passed.
- [ ] **Step 3:** شغّل PostgreSQL المحلي (لن نحتاجه حتى Task 8، لكن تأكد مبكرًا): `/c/dev-tools/pgsql17/bin/pg_isready -h 127.0.0.1 -p 5432`. إن لم يستجب: `/c/dev-tools/pgsql17/bin/pg_ctl -D /c/dev-tools/pgdata17 -l /c/dev-tools/pgdata17/server.log start` (تحقق أولًا أن `/c/dev-tools/pgdata17/PG_VERSION` موجود).
- [ ] **Step 4:** ابحث عن كل المواضع الثابتة لدور `sales`: `grep -rn "'sales'" app resources/views` وسجّلها (متوقع: `OperatingAccess` الأسطر ~36، 105، 140، 149، 158، و`OperatingWorkflow::gate()` ~331).

---

### Task 1: دور `sales_manager` ونطاق رؤية المبيعات (FIN-7)

**Files:**
- Modify: `config/operating.php` (roles، groups: sales، advertising، people)
- Modify: `app/Services/OperatingAccess.php`
- Modify: `app/Services/OperatingWorkflow.php` (قائمة الأرشفة في `gate()`)
- Modify: `lang/en.json`
- Test: `tests/Feature/Operating/SalesAccessTest.php`

**Interfaces:**
- Produces: الدور `sales_manager`. الثابت `OperatingAccess::SALES_SCOPED = ['opportunities', 'activities']` (الأنواع التي يراها `sales` بالإسناد فقط). `activities` سيُعرَّف في T3، والثابت لا يضر قبلها.

- [ ] **Step 1: الاختبار الفاشل**

```php
<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class SalesAccessTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_sales_manager_is_a_valid_role_that_reads_and_writes_the_sales_group(): void
    {
        $manager = $this->operatingUser('sales_manager');

        $this->actingAs($manager)->get('/admin/operations/opportunities')->assertOk();
        $this->actingAs($manager)->post('/admin/operations/clients', ['title' => 'Acme', 'reason' => 'New client', 'data' => ['company' => 'Acme', 'decision_maker' => 'Mona', 'contact' => '0100']])->assertSessionHasNoErrors();
    }

    public function test_sales_sees_only_own_or_assigned_leads_while_the_manager_sees_all(): void
    {
        $sales = $this->operatingUser('sales');
        $salesEmployee = $this->employeeFor($sales, ['department' => 'sales']);
        $other = $this->operatingUser('sales');
        $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Mine', 'assignee_id' => $salesEmployee], 'Assigned to me');
        $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Created'], 'Created by me', $sales->id);
        $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Other'], 'Someone else', $other->id);

        $titles = collect($this->actingAs($sales)->getJson('/admin/operations/opportunities?format=json')->json('data'))->pluck('title')->sort()->values()->all();
        $this->assertSame(['Assigned to me', 'Created by me'], $titles);

        $this->assertCount(3, $this->actingAs($this->operatingUser('sales_manager'))->getJson('/admin/operations/opportunities?format=json')->json('data'));
    }

    public function test_sales_manager_can_archive_and_reads_projects_like_sales(): void
    {
        $manager = $this->operatingUser('sales_manager');
        $clientId = $this->operatingRecord('clients', 'active', ['company' => 'Acme', 'decision_maker' => 'Mona', 'contact' => '0100'], 'Acme');
        $projectId = $this->operatingRecord('projects', 'discovery', ['client_id' => $clientId, 'outcome' => 'Portal', 'original_due' => '2026-12-01', 'client_contact' => 'Mona'], 'Portal');

        $this->actingAs($manager)->get("/admin/operations/record/{$projectId}")->assertOk();
        $this->actingAs($manager)->post("/admin/operations/record/{$clientId}/transition", ['target' => 'archived', 'version' => 1, 'reason' => 'Duplicate'])->assertSessionHasNoErrors();
    }
}
```

- [ ] **Step 2:** `$PHP artisan test --compact tests/Feature/Operating/SalesAccessTest.php` → FAIL (الدور غير موجود → 403).

- [ ] **Step 3: التنفيذ**

`config/operating.php`:
- في `roles` بعد `"sales"` أضف: `"sales_manager": { "ar": "مدير المبيعات", "en": "Sales manager" },` (طابق شكل باقي الأدوار في الملف).
- `groups.sales.read` و`groups.sales.write`: أضف `"sales_manager"`.
- `groups.advertising.read` و`groups.people.read`: أضف `"sales_manager"`.

`OperatingAccess`:
```php
    /** Entity types a plain sales rep sees only when they created or are assigned to the record. */
    public const SALES_SCOPED = ['opportunities', 'activities'];
```
- في `can()` السطر الخاص بقراءة sales للمشاريع: استبدل `$user->role === 'sales'` بـ`in_array($user->role, ['sales', 'sales_manager'], true)`.
- في `query()` استبدل شرط `assigned`:
```php
        $salesScoped = $user->role === 'sales' && in_array($type, self::SALES_SCOPED, true);
        if ($salesScoped || (($entity['assigned'] ?? false) && ! in_array($user->role, ['delivery', 'tech_lead', 'marketing', 'sales', 'sales_manager', 'finance'], true))) {
```
(باقي الـclosure كما هو: owner_id أو assignee/reviewer/tester).
- في `visibleData()` و`restrictedContext()` و`fields()`: كل `$user->role === 'sales'` الخاصة بالمشاريع تصبح `in_array($user->role, ['sales', 'sales_manager'], true)`.

`OperatingWorkflow::gate()` قائمة الأرشفة: أضف `'sales_manager'`.

`lang/en.json`: لا نصوص جديدة في هذا التاسك (التسمية من الـconfig).

- [ ] **Step 4:** الاختبار → PASS. ثم `$PHP artisan test --compact` كاملًا (لا تراجع).
- [ ] **Step 5:** pint ثم commit: `feat(sales): add sales manager role and scope reps to their own leads`.

---

### Task 2: الـLeads وخط البيع (FIN-7)

**Files:**
- Modify: `config/operating.php` (`opportunities`)
- Modify: `app/Services/OperatingWorkflow.php`
- Create: `app/Services/OperatingSales.php`
- Create: `app/Http/Controllers/SalesController.php`
- Create: `resources/views/admin/operating/opportunities-pipeline.blade.php`
- Modify: `routes/admin.php`، `resources/views/admin/operating/form.blade.php`، `resources/views/admin/layout.blade.php`، `lang/en.json`
- Test: `tests/Feature/Operating/LeadsPipelineTest.php`

**Interfaces:**
- Produces:
  - `OperatingSales::OPEN_STAGES` = `['new','contacted','qualified','discovery','proposal','negotiation']`
  - `OperatingSales::pipeline(User $user, array $filters = []): array` → `['columns' => [stage => ['cards' => list<card>, 'count' => int, 'value' => array<currency, float>]], 'summary' => ['open' => int, 'weighted' => array<currency, float>, 'won_month' => int, 'won_month_value' => array<currency, float>, 'lost_month' => int, 'win_rate' => ?float]]`. الـcard: `['id','title','company','assignee','value','currency','probability','follow_up','overdue']`.
  - `OperatingSales::convertToClient(User $user, int $opportunityId, string $reason): object` (سجل العميل الجديد).
  - `OperatingSales::saveAs(User $user, string $type, array $payload, ?int $id = null): object` (يحفظ عبر `OperatingWorkflow::save` بطلب مُركّب، يُستخدم في T2 وT6).
  - `OperatingWorkflow::limitAssignment(User $user, array $data, ?object $existing): void` (private helper يُعاد استخدامه في T3).

- [ ] **Step 1: الاختبار الفاشل**

```php
<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingSales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class LeadsPipelineTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function lead(array $data = []): array
    {
        return ['title' => 'ERP lead', 'reason' => 'Inbound call', 'data' => array_merge(['lead_company' => 'Nile Foods', 'lead_contact' => 'Omar', 'lead_phone' => '01000000000', 'lead_email' => 'omar@nile.test', 'need' => 'ERP system', 'solution' => 'web', 'lead_source' => 'facebook', 'next_action' => 'Discovery call', 'follow_up' => '2026-10-12', 'expected_value' => 5000, 'currency' => 'USD', 'probability' => 40], $data)];
    }

    public function test_a_lead_needs_a_client_or_a_company_but_not_an_existing_client(): void
    {
        $sales = $this->operatingUser('sales');

        $this->actingAs($sales)->post('/admin/operations/opportunities', $this->lead())->assertSessionHasNoErrors();
        $this->actingAs($sales)->post('/admin/operations/opportunities', $this->lead(['lead_company' => null]))->assertSessionHasErrors('workflow');
        $this->actingAs($sales)->post('/admin/operations/opportunities', $this->lead(['probability' => 120]))->assertSessionHasErrors('workflow');
    }

    public function test_reps_cannot_assign_leads_to_others_but_the_manager_can(): void
    {
        $sales = $this->operatingUser('sales');
        $own = $this->employeeFor($sales);
        $colleague = $this->employeeFor($this->operatingUser('sales'));

        $this->actingAs($sales)->post('/admin/operations/opportunities', $this->lead(['assignee_id' => $own]))->assertSessionHasNoErrors();
        $this->actingAs($sales)->post('/admin/operations/opportunities', $this->lead(['assignee_id' => $colleague]))->assertSessionHasErrors('workflow');
        $this->actingAs($this->operatingUser('sales_manager'))->post('/admin/operations/opportunities', $this->lead(['assignee_id' => $colleague]))->assertSessionHasNoErrors();
    }

    public function test_proposal_requires_a_client_and_won_requires_a_value(): void
    {
        $owner = $this->operatingUser('owner');
        $id = $this->operatingRecord('opportunities', 'qualified', ['lead_company' => 'Nile Foods', 'expected_value' => null], 'Lead');

        $this->actingAs($owner)->post("/admin/operations/record/{$id}/transition", ['target' => 'proposal', 'version' => 1, 'reason' => 'Send offer'])->assertSessionHasErrors('workflow');

        $clientId = $this->operatingRecord('clients', 'active', ['company' => 'Nile Foods', 'decision_maker' => 'Omar', 'contact' => '0100'], 'Nile Foods');
        DB::table('operating_records')->where('id', $id)->update(['data' => json_encode(['lead_company' => 'Nile Foods', 'client_id' => $clientId, 'expected_value' => null])]);
        $this->actingAs($owner)->post("/admin/operations/record/{$id}/transition", ['target' => 'proposal', 'version' => 1, 'reason' => 'Send offer'])->assertSessionHasNoErrors();
        $this->actingAs($owner)->post("/admin/operations/record/{$id}/transition", ['target' => 'won', 'version' => 2, 'reason' => 'Signed'])->assertSessionHasErrors('workflow');
    }

    public function test_converting_a_lead_creates_and_links_a_client_once(): void
    {
        $sales = $this->operatingUser('sales');
        $this->actingAs($sales)->post('/admin/operations/opportunities', $this->lead());
        $leadId = DB::table('operating_records')->where('type', 'opportunities')->value('id');

        $this->actingAs($sales)->post("/admin/operations/opportunities/{$leadId}/convert-client", ['reason' => 'Qualified'])->assertRedirect();

        $client = DB::table('operating_records')->where('type', 'clients')->sole();
        $this->assertSame('Nile Foods', $client->title);
        $this->assertSame('omar@nile.test', json_decode($client->data, true)['email']);
        $this->assertSame($client->id, $this->recordData($leadId)['client_id']);
        $this->assertTrue(DB::table('operating_links')->where('record_id', $leadId)->where('target_id', $client->id)->exists());

        $this->actingAs($sales)->post("/admin/operations/opportunities/{$leadId}/convert-client", ['reason' => 'Again'])->assertSessionHasErrors('workflow');
    }

    public function test_pipeline_groups_open_stages_and_computes_the_weighted_forecast(): void
    {
        $owner = $this->operatingUser('owner');
        $this->operatingRecord('opportunities', 'new', ['lead_company' => 'A', 'expected_value' => 1000, 'currency' => 'USD', 'probability' => 50, 'follow_up' => '2020-01-01'], 'A');
        $this->operatingRecord('opportunities', 'proposal', ['lead_company' => 'B', 'expected_value' => 2000, 'currency' => 'USD', 'probability' => 25], 'B');
        $this->operatingRecord('opportunities', 'lost', ['lead_company' => 'C', 'expected_value' => 9000, 'currency' => 'USD', 'probability' => 90], 'C');

        $pipeline = app(OperatingSales::class)->pipeline($owner);

        $this->assertSame(1000.0, $pipeline['summary']['weighted']['USD']);
        $this->assertSame(2, $pipeline['summary']['open']);
        $this->assertSame(1, $pipeline['columns']['new']['count']);
        $this->assertTrue($pipeline['columns']['new']['cards'][0]['overdue']);
        $this->assertSame(2000.0, $pipeline['columns']['proposal']['value']['USD']);
        $this->actingAs($owner)->get('/admin/operations/opportunities/pipeline')->assertOk()->assertSee('B');
    }
}
```

- [ ] **Step 2:** شغّل الملف → FAIL.

- [ ] **Step 3: الـconfig** — في `entities.opportunities.fields`:
  - `client_id`: `"required": false`.
  - `source`: `"required": false`، و`label` يصبح `{"ar": "تفاصيل المصدر", "en": "Source details"}`.
  - أضف بعد `client_id` الحقول التالية (بنفس شكل JSON الموجود):

```json
        { "key": "lead_company", "label": { "ar": "شركة العميل المحتمل", "en": "Lead company" }, "type": "text", "required": false },
        { "key": "lead_contact", "label": { "ar": "اسم جهة الاتصال", "en": "Contact name" }, "type": "text", "required": false },
        { "key": "lead_phone", "label": { "ar": "الهاتف", "en": "Phone" }, "type": "text", "required": false },
        { "key": "lead_email", "label": { "ar": "البريد الإلكتروني", "en": "Email" }, "type": "email", "required": false },
        { "key": "lead_source", "label": { "ar": "مصدر الفرصة", "en": "Lead source" }, "type": "select", "required": true, "options": {
          "website": { "ar": "الموقع", "en": "Website" }, "facebook": { "ar": "فيسبوك", "en": "Facebook" }, "instagram": { "ar": "إنستجرام", "en": "Instagram" },
          "google": { "ar": "جوجل", "en": "Google" }, "linkedin": { "ar": "لينكدإن", "en": "LinkedIn" }, "referral": { "ar": "ترشيح", "en": "Referral" },
          "cold_call": { "ar": "اتصال مباشر", "en": "Cold call" }, "event": { "ar": "فعالية", "en": "Event" }, "other": { "ar": "أخرى", "en": "Other" } } },
        { "key": "assignee_id", "label": { "ar": "مندوب المبيعات المسؤول", "en": "Sales owner" }, "type": "relation", "required": false, "entity": "employees" },
        { "key": "expected_value", "label": { "ar": "القيمة المتوقعة", "en": "Expected value" }, "type": "number", "required": false },
        { "key": "currency", "label": { "ar": "العملة", "en": "Currency" }, "type": "text", "required": false },
        { "key": "probability", "label": { "ar": "احتمالية الفوز %", "en": "Win probability %" }, "type": "number", "required": false },
        { "key": "expected_close", "label": { "ar": "تاريخ الإغلاق المتوقع", "en": "Expected close" }, "type": "date", "required": false },
```

- [ ] **Step 4: قواعد `OperatingWorkflow`**
  - في حلقة النسب: `foreach (['target_margin', 'weight', 'probability', 'allocation_percent'] as $percentage)` (`allocation_percent` لـT7).
  - بعد كتلة `data_batches` أضف:

```php
        if ($type === 'opportunities') {
            if (empty($data['client_id']) && empty($data['lead_company'])) {
                $this->fail('اربط الفرصة بعميل أو سجّل اسم شركة العميل المحتمل.', 'Link a client or enter the lead company.');
            }
            $this->limitAssignment($user, $data, $existing);
        }
```

  - helper جديد في نفس الكلاس:

```php
    /**
     * Sales reps may keep or take ownership of their own records; assigning work to someone else is a manager decision.
     *
     * @param  array<string, mixed>  $data
     */
    private function limitAssignment(User $user, array $data, ?object $existing): void
    {
        if (in_array($user->role, ['owner', 'sales_manager'], true) || empty($data['assignee_id'])) {
            return;
        }
        $previous = $existing ? ($this->catalog->data($existing)['assignee_id'] ?? null) : null;
        if ((int) $data['assignee_id'] !== (int) $this->access->employeeId($user) && (int) $data['assignee_id'] !== (int) $previous) {
            $this->fail('الإسناد لموظف آخر من صلاحية مدير المبيعات.', 'Only a sales manager can assign this to someone else.');
        }
    }
```

  - في `gate()` استبدل كتلة `opportunities` الحالية بـ:

```php
        if ($record->type === 'opportunities') {
            if (in_array($target, ['lost', 'deferred', 'unsuitable'], true)) {
                $this->requireFields($data, ['reason']);
            }
            if (in_array($target, ['proposal', 'negotiation', 'won'], true) && empty($data['client_id'])) {
                $this->fail('حوّل العميل المحتمل إلى عميل قبل العرض أو التفاوض أو الفوز.', 'Convert the lead to a client before proposal, negotiation or win.');
            }
            if ($target === 'won') {
                $this->requireFields($data, ['expected_value']);
            }
        }
```

- [ ] **Step 5: `OperatingSales`**

```php
<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperatingSales
{
    public const OPEN_STAGES = ['new', 'contacted', 'qualified', 'discovery', 'proposal', 'negotiation'];

    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access, private OperatingWorkflow $workflow) {}

    /**
     * Save through the generic workflow so validation, gates, links and the audit event stay identical to the UI path.
     *
     * @param  array<string, mixed>  $payload
     */
    public function saveAs(User $user, string $type, array $payload, ?int $id = null): object
    {
        $request = Request::create('/', 'POST', $payload);
        $request->setUserResolver(fn (): User => $user);

        return $this->workflow->save($type, $request, $id);
    }

    /**
     * @param  array{assignee?: int|string|null, source?: string|null}  $filters
     * @return array{columns: array<string, array{cards: list<array<string, mixed>>, count: int, value: array<string, float>}>, summary: array{open: int, weighted: array<string, float>, won_month: int, won_month_value: array<string, float>, lost_month: int, win_rate: float|null}}
     */
    public function pipeline(User $user, array $filters = []): array
    {
        $records = $this->access->query($user, 'opportunities')->where('status', '!=', 'archived')
            ->when($filters['assignee'] ?? null, fn ($query, $assignee) => $query->where('data->assignee_id', (int) $assignee))
            ->when($filters['source'] ?? null, fn ($query, $source) => $query->where('data->lead_source', $source))
            ->orderBy('updated_at', 'desc')->get();
        $ids = [];
        foreach ($records as $record) {
            $data = $this->catalog->data($record);
            $ids[] = $data['client_id'] ?? null;
            $ids[] = $data['assignee_id'] ?? null;
        }
        $titles = $this->titles(array_values(array_unique(array_filter($ids))));
        $closedAt = $this->closedAt($records->whereIn('status', ['won', 'lost'])->pluck('id')->all());
        $monthStart = today()->startOfMonth()->toDateTimeString();
        $columns = array_fill_keys(self::OPEN_STAGES, ['cards' => [], 'count' => 0, 'value' => []]);
        $summary = ['open' => 0, 'weighted' => [], 'won_month' => 0, 'won_month_value' => [], 'lost_month' => 0, 'win_rate' => null];
        $won = $lost = 0;
        foreach ($records as $record) {
            $data = $this->catalog->data($record);
            $currency = strtoupper($data['currency'] ?? '') ?: 'USD';
            $value = (float) ($data['expected_value'] ?? 0);
            if (in_array($record->status, ['won', 'lost'], true)) {
                $record->status === 'won' ? $won++ : $lost++;
                if (($closedAt[$record->id] ?? '') >= $monthStart) {
                    if ($record->status === 'won') {
                        $summary['won_month']++;
                        $summary['won_month_value'][$currency] = ($summary['won_month_value'][$currency] ?? 0) + $value;
                    } else {
                        $summary['lost_month']++;
                    }
                }

                continue;
            }
            if (! isset($columns[$record->status])) {
                continue;
            }
            $summary['open']++;
            $summary['weighted'][$currency] = round(($summary['weighted'][$currency] ?? 0) + $value * (float) ($data['probability'] ?? 0) / 100, 2);
            $columns[$record->status]['count']++;
            $columns[$record->status]['value'][$currency] = ($columns[$record->status]['value'][$currency] ?? 0) + $value;
            $columns[$record->status]['cards'][] = [
                'id' => $record->id,
                'title' => $record->title,
                'company' => $titles[$data['client_id'] ?? 0] ?? ($data['lead_company'] ?? null),
                'assignee' => $titles[$data['assignee_id'] ?? 0] ?? null,
                'value' => $value,
                'currency' => $currency,
                'probability' => $data['probability'] ?? null,
                'follow_up' => $data['follow_up'] ?? null,
                'overdue' => ! empty($data['follow_up']) && $data['follow_up'] < today()->toDateString(),
            ];
        }
        $summary['win_rate'] = $won + $lost ? round($won / ($won + $lost) * 100, 1) : null;

        return ['columns' => $columns, 'summary' => $summary];
    }

    public function convertToClient(User $user, int $opportunityId, string $reason): object
    {
        $opportunity = $this->access->record($user, $opportunityId, 'write');
        abort_unless($opportunity->type === 'opportunities', 404);
        abort_unless($this->access->can($user, 'clients', 'write'), 403);
        $data = $this->catalog->data($opportunity);
        if (! empty($data['client_id'])) {
            $this->workflow->fail('الفرصة مرتبطة بعميل بالفعل.', 'This lead is already linked to a client.');
        }
        if (empty($data['lead_company']) || empty($data['lead_contact'])) {
            $this->workflow->fail('أكمل اسم الشركة وجهة الاتصال قبل التحويل.', 'Complete the lead company and contact before converting.');
        }

        return DB::transaction(function () use ($user, $opportunity, $data, $reason): object {
            $client = $this->saveAs($user, 'clients', ['title' => $data['lead_company'], 'reason' => $reason, 'data' => ['company' => $data['lead_company'], 'decision_maker' => ($data['decision_maker'] ?? null) ?: $data['lead_contact'], 'contact' => $data['lead_contact'], 'email' => $data['lead_email'] ?? null, 'phone' => $data['lead_phone'] ?? null]]);
            $this->saveAs($user, 'opportunities', ['title' => $opportunity->title, 'version' => $opportunity->version, 'reason' => $reason, 'data' => array_merge($data, ['client_id' => $client->id])], $opportunity->id);

            return $client;
        });
    }

    /**
     * @param  list<int|string>  $ids
     * @return array<int, string>
     */
    public function titles(array $ids): array
    {
        return $ids ? DB::table('operating_records')->whereIn('id', $ids)->pluck('title', 'id')->all() : [];
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, string>
     */
    private function closedAt(array $ids): array
    {
        if (! $ids) {
            return [];
        }

        return DB::table('operating_events')->whereIn('record_id', $ids)->where('action', 'transition')->groupBy('record_id')->selectRaw('record_id, max(created_at) as closed_at')->pluck('closed_at', 'record_id')->all();
    }
}
```

  > ملاحظة: حقل `phone` للعميل يُضاف في T4. قبل T4 سيتجاهله `save()` (لأنه ليس ضمن الحقول) والاختبار لا يفحصه. لا مشكلة في الترتيب.

- [ ] **Step 6: الكنترولر والمسارات**

```php
<?php

namespace App\Http\Controllers;

use App\Services\Cms;
use App\Services\OperatingAccess;
use App\Services\OperatingCatalog;
use App\Services\OperatingSales;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SalesController extends Controller
{
    public function __construct(private OperatingSales $sales, private OperatingAccess $access, private OperatingCatalog $catalog) {}

    public function pipeline(Request $request): Response
    {
        $filters = $request->validate(['assignee' => 'nullable|integer', 'source' => 'nullable|string|max:40']);

        return $this->view('opportunities-pipeline', $this->sales->pipeline($request->user(), $filters) + [
            'filters' => $filters,
            'assignees' => $this->access->can($request->user(), 'employees') ? $this->access->query($request->user(), 'employees')->where('status', 'active')->orderBy('title')->pluck('title', 'id') : collect(),
            'sources' => collect($this->catalog->entity('opportunities')['fields'])->firstWhere('key', 'lead_source')['options'],
        ]);
    }

    public function convertLead(int $id, Request $request): Response
    {
        $input = $request->validate(['reason' => 'required|string|max:2000']);
        $client = $this->sales->convertToClient($request->user(), $id, $input['reason']);

        return redirect('/admin/operations/record/'.$client->id)->with('success', __('تم تحويل الفرصة إلى عميل وربطها به.'));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function view(string $page, array $data): Response
    {
        return response()->view('admin.operating.'.$page, $data + ['collections' => Cms::COLLECTIONS, 'catalog' => $this->catalog, 'access' => $this->access]);
    }
}
```

`routes/admin.php` (بعد سطر `attendance/monthly` وقبل `{type}/create`)، مع `use App\Http\Controllers\SalesController as S;`:
```php
        Route::get('/operations/opportunities/pipeline', [S::class, 'pipeline']);
        Route::post('/operations/opportunities/{id}/convert-client', [S::class, 'convertLead'])->whereNumber('id');
```

- [ ] **Step 7: الـview** `opportunities-pipeline.blade.php`:

```blade
@extends('admin.layout')
@section('title',__('خط البيع'))
@section('content')
<div class="page-heading"><div><span class="eyebrow">{{ __('المبيعات') }}</span><h1>{{ __('خط البيع') }}</h1><p class="muted">{{ __('الفرص المفتوحة حسب المرحلة، مع القيمة المتوقعة والمتابعة القادمة.') }}</p></div>@if($access->can(auth()->user(),'opportunities','write'))<a class="button" href="/admin/operations/opportunities/create">+ {{ __('فرصة جديدة') }}</a>@endif</div>
<div class="inline-stats card">
<span>{{ __('فرص مفتوحة') }}: <b>{{ $summary['open'] }}</b></span>
<span>{{ __('الإيراد المتوقع المرجّح') }}: @forelse($summary['weighted'] as $currency=>$amount)<b dir="ltr">{{ number_format($amount,2) }} {{ $currency }}</b> @empty — @endforelse</span>
<span>{{ __('فوز هذا الشهر') }}: <b>{{ $summary['won_month'] }}</b> @foreach($summary['won_month_value'] as $currency=>$amount)<small dir="ltr">({{ number_format($amount,2) }} {{ $currency }})</small>@endforeach</span>
<span>{{ __('خسارة هذا الشهر') }}: <b>{{ $summary['lost_month'] }}</b></span>
<span>{{ __('نسبة الفوز') }}: <b>{{ $summary['win_rate']!==null ? $summary['win_rate'].'%' : '—' }}</b></span>
</div>
<form class="filters"><select name="assignee"><option value="">{{ __('كل المندوبين') }}</option>@foreach($assignees as $id=>$name)<option value="{{ $id }}" @selected((string)($filters['assignee']??'')===(string)$id)>{{ $name }}</option>@endforeach</select><select name="source"><option value="">{{ __('كل المصادر') }}</option>@foreach($sources as $key=>$label)<option value="{{ $key }}" @selected(($filters['source']??'')===$key)>{{ $catalog->label($label) }}</option>@endforeach</select><button class="button secondary">{{ __('تصفية') }}</button></form>
<div class="workflow-board">@foreach($columns as $stage=>$column)<section><h2>{{ $catalog->status($stage) }} <small class="muted">{{ $column['count'] }}</small></h2>@foreach($column['value'] as $currency=>$amount)<p class="muted" dir="ltr">{{ number_format($amount,2) }} {{ $currency }}</p>@endforeach
@foreach($column['cards'] as $card)<a class="card operation-row" href="/admin/operations/record/{{ $card['id'] }}"><b>{{ $card['title'] }}</b><small>{{ $card['company'] ?? '—' }} · {{ $card['assignee'] ?? __('غير مسند') }}</small>@if($card['value'])<small dir="ltr">{{ number_format($card['value'],2) }} {{ $card['currency'] }} · {{ $card['probability'] ?? 0 }}%</small>@endif @if($card['follow_up'])<small class="{{ $card['overdue']?'danger':'' }}">{{ __('المتابعة') }}: {{ $card['follow_up'] }}</small>@endif</a>@endforeach
</section>@endforeach</div>
<p class="help">{{ __('لنقل فرصة بين المراحل افتح السجل واستخدم الانتقال مع السبب. الخادم يتحقق من البوابات.') }}</p>
@endsection
```

- [ ] **Step 8: أزرار السياق** في `form.blade.php` بعد سطر `@foreach($signals …)`:

```blade
@if($record && $type==='opportunities' && empty($data['client_id']) && $canWrite && $access->can(auth()->user(),'clients','write'))<form class="card row-actions" method="post" action="/admin/operations/opportunities/{{ $record->id }}/convert-client">@csrf<span>{{ __('هذه الفرصة غير مرتبطة بعميل بعد.') }}</span><input name="reason" required maxlength="2000" placeholder="{{ __('سبب التحويل') }}"><button class="button secondary">{{ __('تحويل إلى عميل') }}</button></form>@endif
```

وفي `listing.blade.php` داخل `page-heading` بجانب زر الإضافة: `@if($type==='opportunities')<a class="button secondary" href="/admin/operations/opportunities/pipeline">{{ __('خط البيع') }}</a>@endif`.

`layout.blade.php` بعد سطر روابط الحضور:
```blade
@if($access->can(auth()->user(),'opportunities'))<a href="/admin/operations/opportunities/pipeline" class="{{ request()->is('admin/operations/opportunities/pipeline')?'active':'' }}">{{ __('خط البيع') }}</a>@endif
```
(تحقق أن `$access` متاح في الـlayout: الـviews تمرره، وهو مستخدم بالفعل في السطر 11 عبر `$visibleTypes`. إن لم يكن متاحًا كمتغير استخدم `app(\App\Services\OperatingAccess::class)`.)

- [ ] **Step 9: الترجمات** في `lang/en.json`:
`"خط البيع": "Sales pipeline"`, `"المبيعات": "Sales"`, `"الفرص المفتوحة حسب المرحلة، مع القيمة المتوقعة والمتابعة القادمة.": "Open opportunities by stage with expected value and next follow-up."`, `"فرصة جديدة": "New lead"`, `"فرص مفتوحة": "Open leads"`, `"الإيراد المتوقع المرجّح": "Weighted expected revenue"`, `"فوز هذا الشهر": "Won this month"`, `"خسارة هذا الشهر": "Lost this month"`, `"نسبة الفوز": "Win rate"`, `"كل المندوبين": "All reps"`, `"كل المصادر": "All sources"`, `"تصفية": "Filter"`, `"غير مسند": "Unassigned"`, `"المتابعة": "Follow-up"`, `"لنقل فرصة بين المراحل افتح السجل واستخدم الانتقال مع السبب. الخادم يتحقق من البوابات.": "To move a lead, open it and apply a transition with a reason. The server checks the gates."`, `"هذه الفرصة غير مرتبطة بعميل بعد.": "This lead is not linked to a client yet."`, `"سبب التحويل": "Conversion reason"`, `"تحويل إلى عميل": "Convert to client"`, `"تم تحويل الفرصة إلى عميل وربطها به.": "The lead was converted and linked to a new client."`.

- [ ] **Step 10:** اختبارات T2 → PASS، ثم الحزمة كاملة، ثم pint، ثم commit: `feat(sales): leads without clients, assignment, pipeline board and lead conversion`.

---

### Task 3: المتابعات والتذكيرات (FIN-10، وجزء "تسجيل المتابعات والاجتماعات" من FIN-7)

**Files:**
- Modify: `config/operating.php` (كيان `activities`)
- Modify: `app/Services/OperatingWorkflow.php`
- Create: `app/Services/OperatingFollowUps.php`
- Create: `app/Console/Commands/SendFollowUpReminders.php`
- Modify: `app/Http/Controllers/SalesController.php` (`followUps`)، `app/Http/Controllers/OperatingController.php` (signals + overview)
- Create: `resources/views/admin/operating/activities-followups.blade.php`
- Modify: `routes/admin.php`، `routes/console.php`، `layout.blade.php`، `listing.blade.php`، `lang/en.json`
- Test: `tests/Feature/Operating/FollowUpsTest.php`

**Interfaces:**
- Consumes: `OperatingWorkflow::limitAssignment()` (T2)، `OperatingNotifier`، `OperatingAlert`.
- Produces:
  - كيان `activities` بالحالات `planned, done, cancelled, archived`، وحقول `kind, assignee_id, due_at, client_id, opportunity_id, priority, agenda, outcome, next_step`، ومفاتيح محسوبة `completed_at`، `reminded_at`، `overdue_alerted_at`.
  - `OperatingFollowUps::board(User $user, ?int $assigneeId = null): array` → `['overdue' => list<row>, 'today' => list<row>, 'week' => list<row>, 'later' => list<row>, 'done' => list<row>, 'leads' => list<row>, 'counts' => array<string,int>]`. الـrow: `['id','title','kind','due_at','assignee','client','status']`.
  - `OperatingFollowUps::sendReminders(CarbonImmutable $now): array{due: int, overdue: int}`.

- [ ] **Step 1: الاختبار الفاشل**

```php
<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingFollowUps;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class FollowUpsTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 10:00'));
    }

    private function client(): int
    {
        return $this->operatingRecord('clients', 'active', ['company' => 'Acme', 'decision_maker' => 'Mona', 'contact' => '0100'], 'Acme');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function activity(int $assigneeId, string $dueAt, array $data = [], string $status = 'planned'): int
    {
        return $this->operatingRecord('activities', $status, array_merge(['kind' => 'call', 'assignee_id' => $assigneeId, 'due_at' => $dueAt, 'client_id' => $this->client()], $data), 'Call '.$dueAt);
    }

    public function test_a_follow_up_needs_a_client_or_lead_and_inherits_the_lead_client(): void
    {
        $sales = $this->operatingUser('sales');
        $employee = $this->employeeFor($sales);
        $clientId = $this->client();
        $leadId = $this->operatingRecord('opportunities', 'new', ['client_id' => $clientId, 'lead_company' => 'Acme'], 'Lead');
        $payload = ['title' => 'Call Mona', 'reason' => 'Plan call', 'data' => ['kind' => 'call', 'assignee_id' => $employee, 'due_at' => '2026-10-11T11:00']];

        $this->actingAs($sales)->post('/admin/operations/activities', $payload)->assertSessionHasErrors('workflow');
        $payload['data']['opportunity_id'] = $leadId;
        $this->actingAs($sales)->post('/admin/operations/activities', $payload)->assertSessionHasNoErrors();

        $activity = DB::table('operating_records')->where('type', 'activities')->sole();
        $this->assertSame($clientId, json_decode($activity->data, true)['client_id']);
        $this->assertSame('planned', $activity->status);
    }

    public function test_completing_requires_an_outcome_and_stamps_the_time(): void
    {
        $sales = $this->operatingUser('sales');
        $id = $this->activity($this->employeeFor($sales), '2026-10-10T09:00');

        $this->actingAs($sales)->post("/admin/operations/record/{$id}/transition", ['target' => 'done', 'version' => 1, 'reason' => 'Called'])->assertSessionHasErrors('workflow');
        DB::table('operating_records')->where('id', $id)->update(['data' => json_encode(array_merge($this->recordData($id), ['outcome' => 'Interested, wants a demo']))]);
        $this->actingAs($sales)->post("/admin/operations/record/{$id}/transition", ['target' => 'done', 'version' => 1, 'reason' => 'Called'])->assertSessionHasNoErrors();

        $this->assertSame('2026-10-10T10:00', $this->recordData($id)['completed_at']);
    }

    public function test_the_board_splits_overdue_today_upcoming_and_overdue_lead_follow_ups(): void
    {
        $manager = $this->operatingUser('sales_manager');
        $employee = $this->employeeFor($this->operatingUser('sales'));
        $this->activity($employee, '2026-10-09T15:00');
        $this->activity($employee, '2026-10-10T16:00');
        $this->activity($employee, '2026-10-13T10:00');
        $this->activity($employee, '2026-11-20T10:00');
        $this->activity($employee, '2026-10-08T10:00', ['outcome' => 'Done'], 'done');
        $this->operatingRecord('opportunities', 'contacted', ['lead_company' => 'Late', 'follow_up' => '2026-10-01'], 'Late lead');

        $board = app(OperatingFollowUps::class)->board($manager);

        $this->assertSame(['overdue' => 1, 'today' => 1, 'week' => 1, 'later' => 1, 'done' => 1, 'leads' => 1], $board['counts']);
        $this->actingAs($manager)->get('/admin/operations/activities/follow-ups')->assertOk()->assertSee('Late lead');
    }

    public function test_reminders_notify_once_and_rescheduling_rearms_them(): void
    {
        $salesUser = $this->operatingUser('sales');
        $managerUser = $this->operatingUser('delivery');
        $managerEmployee = $this->employeeFor($managerUser);
        $employee = $this->employeeFor($salesUser, ['manager_id' => $managerEmployee]);
        $salesManager = $this->operatingUser('sales_manager');
        $soon = $this->activity($employee, '2026-10-10T10:30');
        $late = $this->activity($employee, '2026-10-10T08:00');

        $this->artisan('operating:follow-up-reminders')->assertSuccessful();
        $this->artisan('operating:follow-up-reminders')->assertSuccessful();

        $this->assertSame(2, $salesUser->notifications()->count());
        $this->assertSame(1, $managerUser->notifications()->count());
        $this->assertSame(1, $salesManager->notifications()->count());
        $this->assertNotNull($this->recordData($soon)['reminded_at']);
        $this->assertNotNull($this->recordData($late)['overdue_alerted_at']);

        $this->actingAs($salesUser)->post("/admin/operations/record/{$soon}", ['title' => 'Call', 'version' => 1, 'reason' => 'Client asked to move', 'data' => array_merge($this->recordData($soon), ['due_at' => '2026-10-10T10:45'])])->assertSessionHasNoErrors();
        $this->assertArrayNotHasKey('reminded_at', $this->recordData($soon));
    }

    public function test_a_rep_only_sees_their_own_follow_ups(): void
    {
        $sales = $this->operatingUser('sales');
        $this->activity($this->employeeFor($sales), '2026-10-11T10:00');
        $this->activity($this->employeeFor($this->operatingUser('sales')), '2026-10-11T10:00');

        $this->assertCount(1, $this->actingAs($sales)->getJson('/admin/operations/activities?format=json')->json('data'));
    }
}
```

- [ ] **Step 2:** → FAIL (الكيان غير موجود).

- [ ] **Step 3: الـconfig** — أضف إلى `entities` بعد `opportunities`:

```json
    "activities": {
      "label": { "ar": "المتابعات والأنشطة", "en": "Follow-ups and activities" },
      "group": "sales",
      "pages": [],
      "assigned": true,
      "immutable": ["done", "cancelled", "archived"],
      "fields": [
        { "key": "kind", "label": { "ar": "النوع", "en": "Type" }, "type": "select", "required": true, "options": {
          "call": { "ar": "مكالمة", "en": "Call" }, "meeting": { "ar": "اجتماع", "en": "Meeting" }, "follow_up": { "ar": "متابعة", "en": "Follow-up" },
          "email": { "ar": "بريد", "en": "Email" }, "whatsapp": { "ar": "واتساب", "en": "WhatsApp" }, "task": { "ar": "مهمة", "en": "Task" }, "note": { "ar": "ملاحظة", "en": "Note" } } },
        { "key": "assignee_id", "label": { "ar": "المسؤول", "en": "Owner" }, "type": "relation", "required": true, "entity": "employees" },
        { "key": "due_at", "label": { "ar": "الموعد", "en": "Due at" }, "type": "datetime-local", "required": true },
        { "key": "client_id", "label": { "ar": "العميل", "en": "Client" }, "type": "relation", "required": false, "entity": "clients" },
        { "key": "opportunity_id", "label": { "ar": "الفرصة", "en": "Lead" }, "type": "relation", "required": false, "entity": "opportunities" },
        { "key": "priority", "label": { "ar": "الأولوية", "en": "Priority" }, "type": "select", "required": false, "options": {
          "low": { "ar": "منخفضة", "en": "Low" }, "normal": { "ar": "عادية", "en": "Normal" }, "high": { "ar": "عالية", "en": "High" } } },
        { "key": "agenda", "label": { "ar": "الهدف / جدول الأعمال", "en": "Agenda" }, "type": "textarea", "required": false },
        { "key": "outcome", "label": { "ar": "النتيجة", "en": "Outcome" }, "type": "textarea", "required": false },
        { "key": "next_step", "label": { "ar": "الخطوة التالية", "en": "Next step" }, "type": "text", "required": false }
      ],
      "statuses": ["planned", "done", "cancelled", "archived"]
    },
```

  وأضف تسميات الحالات الجديدة في `statuses` (إن لم تكن موجودة): `"planned"` (مخطط / Planned)، `"done"` غالبًا موجودة، `"cancelled"` (ملغي / Cancelled). تحقق بـ`grep -n '"planned"\|"cancelled"' config/operating.php`.

- [ ] **Step 4: `OperatingWorkflow`**
  - في `save()` بعد كتلة `opportunities`:

```php
        if ($type === 'activities') {
            if (empty($data['client_id']) && empty($data['opportunity_id'])) {
                $this->fail('اربط المتابعة بعميل أو فرصة.', 'Link the follow-up to a client or a lead.');
            }
            if (! empty($data['opportunity_id']) && empty($data['client_id'])) {
                $data['client_id'] = $this->catalog->data(DB::table('operating_records')->find($data['opportunity_id']))['client_id'] ?? null;
            }
            $this->limitAssignment($user, $data, $existing);
            if ($existing && ($this->catalog->data($existing)['due_at'] ?? null) !== $data['due_at']) {
                unset($data['reminded_at'], $data['overdue_alerted_at']);
            }
        }
```

  - روابط `operating_links` تُبنى من `$entity['fields']` و`$data`، فالـ`client_id` الموروث يُربط تلقائيًا.
  - في `gate()`:

```php
        if ($record->type === 'activities' && $target === 'done') {
            $this->requireFields($data, ['outcome']);
        }
```

  - في `transition()` بعد كتلة attendance:

```php
            if ($record->type === 'activities' && $target === 'done') {
                $changes['data'] = json_encode(array_merge($data, ['completed_at' => now()->format('Y-m-d\TH:i')]), JSON_UNESCAPED_UNICODE);
            }
```

- [ ] **Step 5: `OperatingFollowUps`**

```php
<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\OperatingAlert;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class OperatingFollowUps
{
    private const BUCKETS = ['overdue', 'today', 'week', 'later', 'done', 'leads'];

    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access, private OperatingNotifier $notifier, private OperatingWorkflow $workflow, private OperatingSales $sales) {}

    /**
     * @return array<string, mixed>
     */
    public function board(User $user, ?int $assigneeId = null): array
    {
        $now = now()->format('Y-m-d\TH:i');
        $todayEnd = today()->format('Y-m-d').'T23:59';
        $weekEnd = today()->addDays(7)->format('Y-m-d').'T23:59';
        $board = array_fill_keys(self::BUCKETS, []);
        $planned = $this->access->query($user, 'activities')->where('status', 'planned')->when($assigneeId, fn ($query) => $query->where('data->assignee_id', $assigneeId))->get();
        $done = $this->access->query($user, 'activities')->where('status', 'done')->where('updated_at', '>=', now()->subDays(30))->when($assigneeId, fn ($query) => $query->where('data->assignee_id', $assigneeId))->latest('updated_at')->limit(50)->get();
        $titles = $this->sales->titles($planned->merge($done)->flatMap(fn (object $row): array => array_filter([($data = $this->catalog->data($row))['assignee_id'] ?? null, $data['client_id'] ?? null]))->unique()->values()->all());
        foreach ($planned->sortBy(fn (object $row): string => $this->catalog->data($row)['due_at'] ?? '') as $row) {
            $due = $this->catalog->data($row)['due_at'] ?? '';
            $bucket = $due < $now ? 'overdue' : ($due <= $todayEnd ? 'today' : ($due <= $weekEnd ? 'week' : 'later'));
            $board[$bucket][] = $this->row($row, $titles);
        }
        foreach ($done as $row) {
            $board['done'][] = $this->row($row, $titles);
        }
        if ($this->access->can($user, 'opportunities') && ! $assigneeId) {
            foreach ($this->access->query($user, 'opportunities')->whereIn('status', OperatingSales::OPEN_STAGES)->where('data->follow_up', '<', today()->toDateString())->get() as $lead) {
                $board['leads'][] = ['id' => $lead->id, 'title' => $lead->title, 'kind' => 'lead', 'due_at' => $this->catalog->data($lead)['follow_up'] ?? null, 'assignee' => null, 'client' => $this->catalog->data($lead)['lead_company'] ?? null, 'status' => $lead->status];
            }
        }
        $board['counts'] = array_map('count', array_intersect_key($board, array_flip(self::BUCKETS)));

        return $board;
    }

    /**
     * Notify the owner shortly before a follow-up is due, and escalate once when it slips past its time.
     *
     * @return array{due: int, overdue: int}
     */
    public function sendReminders(CarbonImmutable $now): array
    {
        $sent = ['due' => 0, 'overdue' => 0];
        $current = $now->format('Y-m-d\TH:i');
        $soon = $now->addHour()->format('Y-m-d\TH:i');
        $planned = DB::table('operating_records')->where('type', 'activities')->where('status', 'planned')->where('data->due_at', '<=', $soon)->get();
        foreach ($planned as $activity) {
            $data = $this->catalog->data($activity);
            $assignee = (int) ($data['assignee_id'] ?? 0);
            $url = '/admin/operations/record/'.$activity->id;
            if (($data['due_at'] ?? '') < $current) {
                if (! empty($data['overdue_alerted_at'])) {
                    continue;
                }
                $this->notifier->send([$this->notifier->employeeUser($assignee), $this->notifier->managerUser($assignee), ...$this->notifier->roles(['sales_manager'])], new OperatingAlert('follow_up_overdue', ['ar' => 'متابعة متأخرة', 'en' => 'Overdue follow-up'], ['ar' => 'المتابعة "'.$activity->title.'" تجاوزت موعدها '.$data['due_at'].'.', 'en' => 'The follow-up "'.$activity->title.'" passed its due time '.$data['due_at'].'.'], $url));
                $this->mark($activity, $data, 'overdue_alerted_at', $current, 'Overdue follow-up alert sent');
                $sent['overdue']++;

                continue;
            }
            if (! empty($data['reminded_at'])) {
                continue;
            }
            $this->notifier->send([$this->notifier->employeeUser($assignee)], new OperatingAlert('follow_up_due', ['ar' => 'تذكير بمتابعة', 'en' => 'Follow-up reminder'], ['ar' => 'موعد "'.$activity->title.'" الساعة '.substr($data['due_at'], 11).'.', 'en' => '"'.$activity->title.'" is due at '.substr($data['due_at'], 11).'.'], $url));
            $this->mark($activity, $data, 'reminded_at', $current, 'Follow-up reminder sent');
            $sent['due']++;
        }

        return $sent;
    }

    /**
     * Reminder markers are system bookkeeping: they are recorded in the audit log but do not bump the record version, so they never conflict with a user editing the same follow-up.
     *
     * @param  array<string, mixed>  $data
     */
    private function mark(object $activity, array $data, string $key, string $value, string $reason): void
    {
        $data[$key] = $value;
        DB::table('operating_records')->where('id', $activity->id)->update(['data' => json_encode($data, JSON_UNESCAPED_UNICODE)]);
        $this->workflow->event($activity, 'reminder', $reason, null, [$key => $value], null);
    }

    /**
     * @param  array<int, string>  $titles
     * @return array<string, mixed>
     */
    private function row(object $row, array $titles): array
    {
        $data = $this->catalog->data($row);

        return ['id' => $row->id, 'title' => $row->title, 'kind' => $data['kind'] ?? null, 'due_at' => $data['due_at'] ?? null, 'assignee' => $titles[$data['assignee_id'] ?? 0] ?? null, 'client' => $titles[$data['client_id'] ?? 0] ?? null, 'status' => $row->status];
    }
}
```

  > تحقق: `OperatingWorkflow::event()` يستخدم `auth()->id()` كقيمة افتراضية، ونمرّر `null` صراحة (أمر مجدول). وإن فشل الـevent لأن `user_id` غير nullable في `operating_events`، راجع كيف يكتب `OperatingAttendance::closeDay` أحداثه واتبعه.
  > تحقق أيضًا من وجود مشكلة dependency cycle: `OperatingFollowUps` يعتمد على `OperatingSales` الذي يعتمد على `OperatingWorkflow`، ولا يعتمد أيٌّ منهما على `OperatingFollowUps`. لا دائرة.

- [ ] **Step 6: الأمر والجدولة**

```php
<?php

namespace App\Console\Commands;

use App\Services\OperatingFollowUps;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class SendFollowUpReminders extends Command
{
    protected $signature = 'operating:follow-up-reminders';

    protected $description = 'Remind owners of follow-ups due within the hour and escalate overdue ones to their manager and sales managers';

    public function handle(OperatingFollowUps $followUps): int
    {
        $sent = $followUps->sendReminders(CarbonImmutable::now());
        $this->info(sprintf('%d reminders, %d overdue alerts.', $sent['due'], $sent['overdue']));

        return self::SUCCESS;
    }
}
```
  أنشئه بـ`$PHP artisan make:command SendFollowUpReminders --no-interaction` ثم استبدل المحتوى. وفي `routes/console.php`:
  `Schedule::command('operating:follow-up-reminders')->everyFifteenMinutes()->withoutOverlapping();`

- [ ] **Step 7: الشاشة والمسار**
  - `SalesController::followUps(Request $request): Response`:

```php
    public function followUps(Request $request): Response
    {
        $input = $request->validate(['assignee' => 'nullable|integer']);

        return $this->view('activities-followups', ['board' => app(\App\Services\OperatingFollowUps::class)->board($request->user(), $input['assignee'] ?? null), 'assignee' => $input['assignee'] ?? null, 'assignees' => $this->access->can($request->user(), 'employees') ? $this->access->query($request->user(), 'employees')->where('status', 'active')->orderBy('title')->pluck('title', 'id') : collect()]);
    }
```
  (حقنه في الـconstructor بدل `app()` وأضف `use`.)
  - المسار قبل `{type}/create`: `Route::get('/operations/activities/follow-ups', [S::class, 'followUps']);`
  - `activities-followups.blade.php`:

```blade
@extends('admin.layout')
@section('title',__('المتابعات والتذكيرات'))
@section('content')
@php $labels=['overdue'=>__('متأخرة'),'today'=>__('اليوم'),'week'=>__('خلال 7 أيام'),'later'=>__('لاحقًا'),'leads'=>__('فرص تجاوزت موعد متابعتها'),'done'=>__('مكتملة (30 يومًا)')]; @endphp
<div class="page-heading"><div><span class="eyebrow">{{ __('المبيعات') }}</span><h1>{{ __('المتابعات والتذكيرات') }}</h1><p class="muted">{{ __('المكالمات والاجتماعات والمتابعات المجدولة، مع التذكير قبل الموعد بساعة والتصعيد عند التأخير.') }}</p></div>@if($access->can(auth()->user(),'activities','write'))<a class="button" href="/admin/operations/activities/create">+ {{ __('متابعة جديدة') }}</a>@endif</div>
<div class="inline-stats card">@foreach($labels as $key=>$label)<span class="{{ $key==='overdue' && $board['counts']['overdue'] ? 'danger' : '' }}">{{ $label }}: <b>{{ $board['counts'][$key] }}</b></span>@endforeach</div>
@if($assignees->isNotEmpty())<form class="filters"><select name="assignee"><option value="">{{ __('كل المندوبين') }}</option>@foreach($assignees as $id=>$name)<option value="{{ $id }}" @selected((string)$assignee===(string)$id)>{{ $name }}</option>@endforeach</select><button class="button secondary">{{ __('تصفية') }}</button></form>@endif
@foreach($labels as $key=>$label)<section class="card table-card"><h2>{{ $label }}</h2><table><thead><tr><th>{{ __('السجل') }}</th><th>{{ __('الموعد') }}</th><th>{{ __('المسؤول') }}</th><th>{{ __('العميل') }}</th></tr></thead><tbody>@forelse($board[$key] as $row)<tr><td><a class="item-title" href="/admin/operations/record/{{ $row['id'] }}">{{ $row['title'] }}</a></td><td dir="ltr">{{ str_replace('T',' ',$row['due_at'] ?? '—') }}</td><td>{{ $row['assignee'] ?? '—' }}</td><td>{{ $row['client'] ?? '—' }}</td></tr>@empty<tr><td colspan="4" class="muted">{{ __('لا شيء هنا.') }}</td></tr>@endforelse</tbody></table></section>@endforeach
@endsection
```

  - `layout.blade.php`: بجانب رابط خط البيع: `@if($access->can(auth()->user(),'activities'))<a href="/admin/operations/activities/follow-ups" …>{{ __('المتابعات') }}</a>@endif`. و`listing.blade.php`: زر "لوحة المتابعات" عندما `$type==='activities'`.

- [ ] **Step 8: signals وoverview** في `OperatingController`:
  - في `signals()` قبل `return`:
```php
        if ($record->type === 'activities' && $record->status === 'planned' && ($data['due_at'] ?? '9999') < now()->format('Y-m-d\TH:i')) {
            $signals[] = __('متابعة تجاوزت موعدها. أنجزها أو أعد جدولتها.');
        }
```
  - في `overview()` حمّل للمتابعات `planned` فقط (مثل attendance): `->when($type === 'attendance', … )` تصبح `->when(in_array($type, ['attendance', 'activities'], true), fn ($scoped) => $scoped->where('status', $type === 'attendance' ? 'open' : 'planned'), fn ($scoped) => $scoped->where('status', '!=', 'archived'))`.

- [ ] **Step 9: الترجمات** لكل النصوص الجديدة في Steps 5–8 (المتابعات والتذكيرات، متأخرة، اليوم، خلال 7 أيام، لاحقًا، فرص تجاوزت موعد متابعتها، مكتملة (30 يومًا)، الوصف، متابعة جديدة، الموعد، المسؤول، العميل، لا شيء هنا.، المتابعات، لوحة المتابعات، متابعة تجاوزت موعدها. أنجزها أو أعد جدولتها.).
- [ ] **Step 10:** اختبارات T3 → PASS، الحزمة كاملة، pint، commit: `feat(sales): follow-ups with reminders, overdue escalation and board`.

---

### Task 4: ملف العميل وجهات الاتصال (FIN-8)

**Files:**
- Modify: `config/operating.php` (`clients`، كيان `contacts`)
- Modify: `app/Services/OperatingWorkflow.php` (جهة اتصال أساسية واحدة)
- Modify: `app/Services/OperatingSales.php` (`clientProfile`)
- Modify: `app/Http/Controllers/SalesController.php` (`clientProfile`)
- Create: `resources/views/admin/operating/client-profile.blade.php`
- Modify: `routes/admin.php`، `form.blade.php`، `lang/en.json`
- Test: `tests/Feature/Operating/ClientProfileTest.php`

**Interfaces:**
- Produces: `OperatingSales::clientProfile(User $user, object $client): array` →
  `['client' => object, 'data' => array, 'sections' => array<type, Collection|null>, 'outstanding' => ?array<currency, float>, 'collected' => ?array<currency, float>, 'stats' => ['open_leads' => int, 'won_value' => array<currency,float>, 'active_projects' => int], 'timeline' => list<['at' => string, 'title' => string, 'kind' => string, 'id' => int]>]`.
  الـ`sections`: `contacts, opportunities, activities, quotes, contracts, projects, invoices, receipts, support` (القيمة `null` = لا صلاحية، فتُخفى).

- [ ] **Step 1: الاختبار الفاشل**

```php
<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class ClientProfileTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    /**
     * @return array{client: int, invoice: int}
     */
    private function seedClient(): array
    {
        $client = $this->operatingRecord('clients', 'active', ['company' => 'Acme', 'decision_maker' => 'Mona', 'contact' => '0100', 'phone' => '0222'], 'Acme');
        $this->operatingRecord('contacts', 'active', ['client_id' => $client, 'position' => 'CTO', 'is_primary' => true], 'Mona CTO');
        $lead = $this->operatingRecord('opportunities', 'won', ['client_id' => $client, 'expected_value' => 5000, 'currency' => 'USD'], 'ERP deal');
        $this->operatingRecord('quotes', 'approved', ['opportunity_id' => $lead, 'price' => 5000, 'currency' => 'USD'], 'ERP quote');
        $this->operatingRecord('projects', 'execution', ['client_id' => $client, 'outcome' => 'ERP', 'original_due' => '2026-12-01', 'client_contact' => 'Mona'], 'ERP project');
        $this->operatingRecord('activities', 'done', ['kind' => 'meeting', 'client_id' => $client, 'due_at' => '2026-10-01T10:00', 'outcome' => 'Kickoff'], 'Kickoff meeting');
        $invoice = $this->operatingRecord('invoices', 'part_paid', ['client_id' => $client, 'invoice_number' => 'INV-1', 'amount' => 1000, 'currency' => 'USD', 'due_date' => '2026-10-20'], 'INV-1');
        $this->operatingRecord('receipts', 'active', ['invoice_id' => $invoice, 'amount' => 400, 'received_at' => '2026-10-05', 'reference' => 'TRX'], 'Receipt');

        return ['client' => $client, 'invoice' => $invoice];
    }

    public function test_owner_sees_the_full_profile_with_outstanding_balance(): void
    {
        $ids = $this->seedClient();

        $this->actingAs($this->operatingUser('owner'))->get("/admin/operations/clients/{$ids['client']}/profile")
            ->assertOk()->assertSee('Mona CTO')->assertSee('ERP deal')->assertSee('ERP quote')->assertSee('ERP project')->assertSee('Kickoff meeting')->assertSee('INV-1')->assertSee('600.00');
    }

    public function test_sales_does_not_see_finance_sections(): void
    {
        $ids = $this->seedClient();

        $this->actingAs($this->operatingUser('sales_manager'))->get("/admin/operations/clients/{$ids['client']}/profile")
            ->assertOk()->assertSee('ERP deal')->assertDontSee('INV-1')->assertDontSee('600.00');
    }

    public function test_profile_rejects_other_types_and_unauthorised_roles(): void
    {
        $ids = $this->seedClient();

        $this->actingAs($this->operatingUser('owner'))->get("/admin/operations/clients/{$ids['invoice']}/profile")->assertNotFound();
        $this->actingAs($this->operatingUser('developer'))->get("/admin/operations/clients/{$ids['client']}/profile")->assertForbidden();
    }

    public function test_a_client_has_one_primary_contact(): void
    {
        $ids = $this->seedClient();
        $payload = ['title' => 'Second', 'reason' => 'New contact', 'data' => ['client_id' => $ids['client'], 'is_primary' => true]];

        $this->actingAs($this->operatingUser('sales'))->post('/admin/operations/contacts', $payload)->assertSessionHasErrors('workflow');
        $payload['data']['is_primary'] = false;
        $this->actingAs($this->operatingUser('sales'))->post('/admin/operations/contacts', $payload)->assertSessionHasNoErrors();
    }
}
```

- [ ] **Step 2:** → FAIL.

- [ ] **Step 3: الـconfig**
  - `clients.fields` أضف: `phone` (text، "الهاتف"/"Phone")، `website` (url، "الموقع الإلكتروني"/"Website")، `industry` (text، "المجال"/"Industry")، `country` (text، "الدولة"/"Country")، `address` (textarea، "العنوان"/"Address")، `tax_number` (text، "الرقم الضريبي"/"Tax number")، كلها `required: false`.
  - كيان جديد بعد `clients`:

```json
    "contacts": {
      "label": { "ar": "جهات اتصال العملاء", "en": "Client contacts" },
      "group": "sales",
      "pages": [],
      "fields": [
        { "key": "client_id", "label": { "ar": "العميل", "en": "Client" }, "type": "relation", "required": true, "entity": "clients" },
        { "key": "position", "label": { "ar": "المنصب", "en": "Position" }, "type": "text", "required": false },
        { "key": "phone", "label": { "ar": "الهاتف", "en": "Phone" }, "type": "text", "required": false },
        { "key": "email", "label": { "ar": "البريد الإلكتروني", "en": "Email" }, "type": "email", "required": false },
        { "key": "is_primary", "label": { "ar": "جهة الاتصال الأساسية", "en": "Primary contact" }, "type": "checkbox", "required": false },
        { "key": "notes", "label": { "ar": "ملاحظات", "en": "Notes" }, "type": "textarea", "required": false }
      ],
      "statuses": ["active", "archived"]
    },
```

- [ ] **Step 4: قاعدة الأساسية** في `save()`:

```php
        if ($type === 'contacts' && ! empty($data['is_primary']) && DB::table('operating_records')->where('type', 'contacts')->where('status', '!=', 'archived')->where('data->client_id', (int) $data['client_id'])->where('data->is_primary', true)->when($id, fn ($query) => $query->where('id', '!=', $id))->exists()) {
            $this->fail('للعميل جهة اتصال أساسية بالفعل. ألغِ تحديدها أولًا.', 'This client already has a primary contact. Unset it first.');
        }
```
  > تحقق أن `where('data->is_primary', true)` يعمل على SQLite (القيمة مخزنة JSON `true`) وعلى pgsql (Laravel يحوّل المقارنة البوليانية في JSON). الاختبار يغطي SQLite، وTask 8 يغطي pgsql يدويًا.

- [ ] **Step 5: `OperatingSales::clientProfile`**

```php
    /**
     * @return array<string, mixed>
     */
    public function clientProfile(User $user, object $client): array
    {
        $linked = function (string $type, string $field, array $targets) use ($user): ?\Illuminate\Support\Collection {
            if (! $this->access->can($user, $type) || ! $targets) {
                return $this->access->can($user, $type) ? collect() : null;
            }
            $ids = DB::table('operating_links')->where('field', $field)->whereIn('target_id', $targets)->pluck('record_id');

            return $this->access->query($user, $type)->whereIn('id', $ids)->where('status', '!=', 'archived')->latest('updated_at')->get();
        };
        $sections = [];
        foreach (['contacts', 'opportunities', 'activities', 'contracts', 'projects', 'invoices'] as $type) {
            $sections[$type] = $linked($type, 'client_id', [$client->id]);
        }
        $sections['quotes'] = $linked('quotes', 'opportunity_id', $sections['opportunities']?->pluck('id')->all() ?? []);
        $sections['receipts'] = $linked('receipts', 'invoice_id', $sections['invoices']?->pluck('id')->all() ?? []);
        $sections['support'] = $linked('support', 'project_id', $sections['projects']?->pluck('id')->all() ?? []);
        $outstanding = $collected = null;
        if ($sections['invoices'] !== null && $sections['receipts'] !== null) {
            $outstanding = $collected = [];
            $paid = [];
            foreach ($sections['receipts']->whereIn('status', ['active', 'completed']) as $receipt) {
                $receiptData = $this->catalog->data($receipt);
                $paid[$receiptData['invoice_id']] = ($paid[$receiptData['invoice_id']] ?? 0) + (float) ($receiptData['amount'] ?? 0);
            }
            foreach ($sections['invoices'] as $invoice) {
                $invoiceData = $this->catalog->data($invoice);
                $currency = strtoupper($invoiceData['currency'] ?? '') ?: 'USD';
                $collected[$currency] = ($collected[$currency] ?? 0) + ($paid[$invoice->id] ?? 0);
                if (in_array($invoice->status, ['issued', 'part_paid', 'overdue'], true)) {
                    $outstanding[$currency] = ($outstanding[$currency] ?? 0) + max(0, (float) ($invoiceData['amount'] ?? 0) - ($paid[$invoice->id] ?? 0));
                }
            }
        }
        $wonValue = [];
        foreach ($sections['opportunities']?->where('status', 'won') ?? [] as $won) {
            $wonData = $this->catalog->data($won);
            $currency = strtoupper($wonData['currency'] ?? '') ?: 'USD';
            $wonValue[$currency] = ($wonValue[$currency] ?? 0) + (float) ($wonData['expected_value'] ?? 0);
        }
        $timeline = [];
        foreach ($sections['activities'] ?? [] as $activity) {
            $activityData = $this->catalog->data($activity);
            $timeline[] = ['at' => str_replace('T', ' ', $activityData['completed_at'] ?? $activityData['due_at'] ?? (string) $activity->updated_at), 'title' => $activity->title, 'kind' => $activity->status === 'done' ? 'activity_done' : 'activity_'.$activity->status, 'id' => $activity->id];
        }
        $milestoneIds = collect([$client->id])->merge($sections['opportunities']?->pluck('id') ?? [])->merge($sections['contracts']?->pluck('id') ?? [])->merge($sections['projects']?->pluck('id') ?? [])->all();
        foreach (DB::table('operating_events')->join('operating_records as r', 'r.id', '=', 'operating_events.record_id')->whereIn('record_id', $milestoneIds)->whereIn('action', ['create', 'transition'])->select('operating_events.created_at', 'operating_events.action', 'r.title', 'r.id', 'r.type')->latest('operating_events.id')->limit(40)->get() as $event) {
            $timeline[] = ['at' => (string) $event->created_at, 'title' => $event->title, 'kind' => $event->type.'_'.$event->action, 'id' => $event->id];
        }
        usort($timeline, fn (array $a, array $b): int => strcmp($b['at'], $a['at']));

        return [
            'client' => $client,
            'data' => $this->access->visibleData($user, $client),
            'sections' => $sections,
            'outstanding' => $outstanding,
            'collected' => $collected,
            'stats' => ['open_leads' => $sections['opportunities']?->whereIn('status', self::OPEN_STAGES)->count() ?? 0, 'won_value' => $wonValue, 'active_projects' => $sections['projects']?->whereNotIn('status', ['closed', 'archived'])->count() ?? 0],
            'timeline' => array_slice($timeline, 0, 60),
        ];
    }
```
  > الـtimeline يعرض أحداث create/transition فقط للسجلات التي يقرأها المستخدم بالفعل (لأن الـIDs جاءت من `access->query`)، وبلا before/after، فلا تسريب بيانات.

- [ ] **Step 6: الكنترولر والمسار**

```php
    public function clientProfile(int $id, Request $request): Response
    {
        $client = $this->access->record($request->user(), $id);
        abort_unless($client->type === 'clients', 404);

        return $this->view('client-profile', $this->sales->clientProfile($request->user(), $client));
    }
```
  المسار: `Route::get('/operations/clients/{id}/profile', [S::class, 'clientProfile'])->whereNumber('id');`
  > ترتيب الفحص: `record()` يرجع 403 لمن لا يقرأ النوع (developer)، و404 لو الـid نوعه ليس clients. الاختبار يتوقع 404 للفاتورة مع owner (owner يقرأ الكل، فيصل لفحص النوع).

- [ ] **Step 7: الـview** `client-profile.blade.php`:

```blade
@extends('admin.layout')
@section('title',$client->title)
@section('content')
@php $sectionLabels=['contacts'=>__('جهات الاتصال'),'opportunities'=>__('الفرص'),'activities'=>__('المتابعات والأنشطة'),'quotes'=>__('العروض'),'contracts'=>__('العقود'),'projects'=>__('المشروعات'),'invoices'=>__('الفواتير'),'receipts'=>__('التحصيل'),'support'=>__('تذاكر الدعم')]; @endphp
<div class="page-heading"><div><span class="eyebrow">{{ __('ملف العميل') }}</span><h1>{{ $client->title }}</h1><p class="muted"><span class="badge">{{ $catalog->status($client->status) }}</span> {{ $data['industry'] ?? '' }} {{ $data['country'] ?? '' }}</p></div><a class="button secondary" href="/admin/operations/record/{{ $client->id }}">{{ __('تعديل بيانات العميل') }}</a></div>
<section class="card"><dl class="payload">@foreach(['company'=>__('الشركة'),'decision_maker'=>__('صاحب القرار'),'contact'=>__('جهة الاتصال'),'phone'=>__('الهاتف'),'email'=>__('البريد الإلكتروني'),'website'=>__('الموقع الإلكتروني'),'address'=>__('العنوان'),'tax_number'=>__('الرقم الضريبي')] as $key=>$label)@if(!empty($data[$key]))<dt>{{ $label }}</dt><dd class="wrap">{{ $data[$key] }}</dd>@endif @endforeach</dl></section>
<div class="inline-stats card"><span>{{ __('فرص مفتوحة') }}: <b>{{ $stats['open_leads'] }}</b></span><span>{{ __('قيمة الصفقات المكسوبة') }}: @forelse($stats['won_value'] as $currency=>$amount)<b dir="ltr">{{ number_format($amount,2) }} {{ $currency }}</b> @empty — @endforelse</span><span>{{ __('مشروعات جارية') }}: <b>{{ $stats['active_projects'] }}</b></span>
@if($outstanding!==null)<span>{{ __('المتبقي على العميل') }}: @forelse($outstanding as $currency=>$amount)<b dir="ltr" class="{{ $amount>0?'danger':'' }}">{{ number_format($amount,2) }} {{ $currency }}</b> @empty <b>0</b> @endforelse</span><span>{{ __('المحصّل') }}: @forelse($collected as $currency=>$amount)<b dir="ltr">{{ number_format($amount,2) }} {{ $currency }}</b> @empty <b>0</b> @endforelse</span>@endif</div>
<div class="operating-editor"><div>
@foreach($sectionLabels as $type=>$label)@if($sections[$type]!==null)<section class="card"><h2>{{ $label }} <small class="muted">{{ $sections[$type]->count() }}</small></h2>@forelse($sections[$type] as $row)<a class="operation-row" href="/admin/operations/record/{{ $row->id }}"><b>{{ $row->title }}</b><span>{{ $catalog->status($row->status) }}</span></a>@empty<p class="muted">{{ __('لا شيء هنا.') }}</p>@endforelse
@if(in_array($type,['contacts','activities','opportunities'],true) && $access->can(auth()->user(),$type,'write'))<a class="button secondary" href="/admin/operations/{{ $type }}/create">+ {{ __('إضافة') }}</a>@endif</section>@endif @endforeach
</div><aside><section class="card"><h2>{{ __('تاريخ العلاقة') }}</h2>@forelse($timeline as $item)<div class="revision-row"><div><b>{{ $item['title'] }}</b><small class="path">{{ $item['at'] }} · {{ __($item['kind']) }}</small></div></div>@empty<p class="muted">{{ __('لا شيء هنا.') }}</p>@endforelse</section></aside></div>
@endsection
```
  > `__($item['kind'])` يعرض المفتاح كما هو إن لم توجد ترجمة. أضف ترجمات عربية/إنجليزية مختصرة لـ`activity_done`، `activity_planned`، `activity_cancelled`، و`<type>_create`/`<type>_transition` للأنواع الأربعة، أو بسّطها: اعرض `$catalog->label($catalog->entity(type)['label'])` + "إنشاء/تغيير حالة". **القرار:** غيّر `kind` في الـservice إلى نص جاهز: `['type' => …, 'action' => …]` واعرضه بـ`$catalog->label(...)` و`__('إنشاء')`/`__('تغيير حالة')`/`__('نشاط مكتمل')`. عدّل الـinterface والـview معًا قبل التنفيذ.

- [ ] **Step 8:** في `form.blade.php` (أزرار السياق): `@if($record && $type==='clients')<div class="row-actions"><a class="button secondary" href="/admin/operations/clients/{{ $record->id }}/profile">{{ __('ملف العميل الكامل') }}</a></div>@endif`.
- [ ] **Step 9:** الترجمات. ثم الاختبارات → PASS، الحزمة، pint، commit: `feat(crm): client profile with contacts, history and outstanding balance`.

---

### Task 5: بنود العرض والإجماليات والترقيم (FIN-9، الجزء الأول)

**Files:**
- Modify: `config/operating.php` (`quotes`)
- Modify: `app/Services/OperatingWorkflow.php` (نوع `lines`، الإجماليات، الترقيم، بوابة الاعتماد Q2)
- Create: `app/Services/OperatingQuotes.php`
- Create: `resources/views/admin/operating/line-items.blade.php`
- Modify: `form.blade.php`، `public/admin-assets/operating.js`، `public/admin-assets/operating.css`، `lang/en.json`
- Test: `tests/Feature/Operating/QuoteLinesTest.php`

**Interfaces:**
- Produces:
  - نوع حقل `lines`. القيمة المخزنة: `list<array{description: string, kind: 'product'|'service', quantity: float, unit_price: float, discount: float, tax: float}>` (`discount` و`tax` نسب مئوية للبند).
  - `OperatingQuotes::normalizeLines(array $rows): array` (يحذف الصفوف بلا وصف ويحوّل الأرقام).
  - `OperatingQuotes::totals(array $lines, float $discountAmount = 0): array{subtotal: float, line_discount: float, net: float, tax: float, discount: float, total: float}`.
  - `OperatingQuotes::nextNumber(): string` → `Q-YYYY-NNNN`.
  - بيانات العرض المحسوبة: `data.totals` (مصفوفة totals)، `data.quote_number`، و`data.price` = `totals.total` عند وجود بنود.

**المعادلة (موثقة في الكود):** لكل بند: `gross = quantity × unit_price`، `net = gross × (1 − discount%)`، `tax = net × tax%`. ثم `subtotal = Σ gross`، `line_discount = Σ (gross − net)`، `net = Σ net`، `tax = Σ tax`، `total = net + tax − discount` (خصم العرض العام مبلغ ثابت بعد الضريبة، ولا يتجاوز `net + tax`).

- [ ] **Step 1: الاختبار الفاشل**

```php
<?php

namespace Tests\Feature\Operating;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class QuoteLinesTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    private int $leadId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 10:00'));
        $client = $this->operatingRecord('clients', 'active', ['company' => 'Acme', 'decision_maker' => 'Mona', 'contact' => '0100'], 'Acme');
        $this->leadId = $this->operatingRecord('opportunities', 'proposal', ['client_id' => $client, 'lead_company' => 'Acme'], 'ERP lead');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function quote(array $data = []): array
    {
        return ['title' => 'ERP quote', 'reason' => 'Draft offer', 'data' => array_merge(['opportunity_id' => $this->leadId, 'currency' => 'USD', 'payment_terms' => '50% upfront', 'discount' => 100, 'line_items' => [
            ['description' => 'ERP implementation', 'kind' => 'service', 'quantity' => 2, 'unit_price' => 500, 'discount' => 10, 'tax' => 14],
            ['description' => 'License', 'kind' => 'product', 'quantity' => 1, 'unit_price' => 1000, 'discount' => 0, 'tax' => 0],
            ['description' => '', 'kind' => 'service', 'quantity' => null, 'unit_price' => null, 'discount' => null, 'tax' => null],
        ]], $data)];
    }

    public function test_lines_are_normalised_and_totals_are_computed_into_price(): void
    {
        $this->actingAs($this->operatingUser('sales'))->post('/admin/operations/quotes', $this->quote())->assertSessionHasNoErrors();

        $data = json_decode(DB::table('operating_records')->where('type', 'quotes')->value('data'), true);
        $this->assertCount(2, $data['line_items']);
        $this->assertSame(['subtotal' => 2000.0, 'line_discount' => 100.0, 'net' => 1900.0, 'tax' => 126.0, 'discount' => 100.0, 'total' => 1926.0], $data['totals']);
        $this->assertSame(1926.0, $data['price']);
        $this->assertSame('Q-2026-0001', $data['quote_number']);
    }

    public function test_quote_numbers_are_sequential_per_year(): void
    {
        $sales = $this->operatingUser('sales');
        $this->actingAs($sales)->post('/admin/operations/quotes', $this->quote());
        $this->actingAs($sales)->post('/admin/operations/quotes', $this->quote());

        $numbers = DB::table('operating_records')->where('type', 'quotes')->orderBy('id')->pluck('data')->map(fn (string $data): string => json_decode($data, true)['quote_number'])->all();
        $this->assertSame(['Q-2026-0001', 'Q-2026-0002'], $numbers);
    }

    public function test_invalid_lines_and_discounts_are_rejected(): void
    {
        $sales = $this->operatingUser('sales');

        $this->actingAs($sales)->post('/admin/operations/quotes', $this->quote(['line_items' => [['description' => 'Zero', 'kind' => 'service', 'quantity' => 0, 'unit_price' => 10, 'discount' => 0, 'tax' => 0]]]))->assertSessionHasErrors('workflow');
        $this->actingAs($sales)->post('/admin/operations/quotes', $this->quote(['discount' => 5000]))->assertSessionHasErrors('workflow');
        $this->actingAs($sales)->post('/admin/operations/quotes', $this->quote(['line_items' => [], 'price' => null]))->assertSessionHasErrors('workflow');
        $this->actingAs($sales)->post('/admin/operations/quotes', $this->quote(['line_items' => [], 'price' => 3000, 'discount' => null]))->assertSessionHasNoErrors();
    }

    public function test_approval_needs_an_approved_scope_when_linked_otherwise_line_items(): void
    {
        $owner = $this->operatingUser('owner');
        $withLines = $this->operatingRecord('quotes', 'submitted', ['opportunity_id' => $this->leadId, 'price' => 100, 'currency' => 'USD', 'payment_terms' => 'Net 30', 'approval_evidence' => 'Signed by client', 'line_items' => [['description' => 'Audit', 'kind' => 'service', 'quantity' => 1, 'unit_price' => 100, 'discount' => 0, 'tax' => 0]]], 'With lines');
        $withoutLines = $this->operatingRecord('quotes', 'submitted', ['opportunity_id' => $this->leadId, 'price' => 100, 'currency' => 'USD', 'payment_terms' => 'Net 30', 'approval_evidence' => 'Signed by client'], 'No lines');
        $projectId = $this->operatingRecord('projects', 'discovery', ['client_id' => null, 'outcome' => 'X', 'original_due' => '2026-12-01', 'client_contact' => 'Mona'], 'Project');
        $draftScope = $this->operatingRecord('scopes', 'draft', ['project_id' => $projectId, 'scope_version' => 1], 'Scope v1');
        $scoped = $this->operatingRecord('quotes', 'submitted', ['opportunity_id' => $this->leadId, 'scope_id' => $draftScope, 'price' => 100, 'currency' => 'USD', 'payment_terms' => 'Net 30', 'approval_evidence' => 'Signed', 'line_items' => [['description' => 'Audit', 'kind' => 'service', 'quantity' => 1, 'unit_price' => 100, 'discount' => 0, 'tax' => 0]]], 'Scoped');

        $this->actingAs($owner)->post("/admin/operations/record/{$withLines}/transition", ['target' => 'approved', 'version' => 1, 'reason' => 'Client signed'])->assertSessionHasNoErrors();
        $this->actingAs($owner)->post("/admin/operations/record/{$withoutLines}/transition", ['target' => 'approved', 'version' => 1, 'reason' => 'Client signed'])->assertSessionHasErrors('workflow');
        $this->actingAs($owner)->post("/admin/operations/record/{$scoped}/transition", ['target' => 'approved', 'version' => 1, 'reason' => 'Client signed'])->assertSessionHasErrors('workflow');
    }

    public function test_approved_quote_lines_are_immutable(): void
    {
        $sales = $this->operatingUser('sales');
        $this->actingAs($sales)->post('/admin/operations/quotes', $this->quote());
        $id = DB::table('operating_records')->where('type', 'quotes')->value('id');
        DB::table('operating_records')->where('id', $id)->update(['status' => 'approved']);

        $this->actingAs($sales)->post("/admin/operations/record/{$id}", $this->quote(['discount' => 0]) + ['version' => 1])->assertSessionHasErrors('workflow');
    }
}
```

- [ ] **Step 2:** → FAIL.

- [ ] **Step 3: الـconfig** — في `quotes.fields`:
  - `price`: `"required": false`، والتسمية `{"ar": "السعر الإجمالي (يُحسب من البنود إن وُجدت)", "en": "Total price (computed from line items when present)"}`.
  - `discount`: التسمية `{"ar": "خصم إضافي على العرض (مبلغ)", "en": "Extra quote discount (amount)"}`.
  - أضف بعد `scope_id`:
```json
        { "key": "line_items", "label": { "ar": "بنود العرض", "en": "Line items" }, "type": "lines", "required": false },
        { "key": "valid_until", "label": { "ar": "صالح حتى", "en": "Valid until" }, "type": "date", "required": false },
        { "key": "terms", "label": { "ar": "الشروط والملاحظات", "en": "Terms and notes" }, "type": "textarea", "required": false },
```
  > تحقق أن أي مكان يرسم الحقول بشكل عام لا ينهار مع النوع الجديد: `grep -rn "\['type'\]" app resources/views`. المتوقع: `OperatingWorkflow::save()` (يُعالَج هنا)، `form.blade.php` (يُعالَج هنا)، و`OperatingController::form()` (يتجاهل الأنواع غير relation/user). و`JiraSync`/`SetupOperatingModel` لا تمس `quotes`.

- [ ] **Step 4: `OperatingQuotes`**

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class OperatingQuotes
{
    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access, private OperatingWorkflow $workflow) {}

    /**
     * Drop rows without a description (blank template rows from the form) and cast the numeric columns.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return list<array{description: string, kind: string, quantity: float, unit_price: float, discount: float, tax: float}>
     */
    public function normalizeLines(array $rows): array
    {
        $lines = [];
        foreach ($rows as $row) {
            $description = trim((string) ($row['description'] ?? ''));
            if ($description === '') {
                continue;
            }
            $quantity = (float) ($row['quantity'] ?? 0);
            if ($quantity <= 0) {
                $this->workflow->fail('كمية كل بند يجب أن تكون أكبر من صفر.', 'Each line needs a quantity above zero.');
            }
            $lines[] = ['description' => $description, 'kind' => ($row['kind'] ?? 'service') === 'product' ? 'product' : 'service', 'quantity' => $quantity, 'unit_price' => (float) ($row['unit_price'] ?? 0), 'discount' => (float) ($row['discount'] ?? 0), 'tax' => (float) ($row['tax'] ?? 0)];
        }

        return $lines;
    }

    /**
     * Per line: net = qty × price × (1 − discount%), tax = net × tax%. The quote-level discount is a fixed amount taken off the taxed total.
     *
     * @param  list<array{quantity: float, unit_price: float, discount: float, tax: float}>  $lines
     * @return array{subtotal: float, line_discount: float, net: float, tax: float, discount: float, total: float}
     */
    public function totals(array $lines, float $discountAmount = 0): array
    {
        $subtotal = $net = $tax = 0.0;
        foreach ($lines as $line) {
            $gross = $line['quantity'] * $line['unit_price'];
            $lineNet = $gross * (1 - $line['discount'] / 100);
            $subtotal += $gross;
            $net += $lineNet;
            $tax += $lineNet * $line['tax'] / 100;
        }
        if ($discountAmount > $net + $tax) {
            $this->workflow->fail('الخصم الإضافي أكبر من إجمالي العرض.', 'The extra discount exceeds the quote total.');
        }

        return ['subtotal' => round($subtotal, 2), 'line_discount' => round($subtotal - $net, 2), 'net' => round($net, 2), 'tax' => round($tax, 2), 'discount' => round($discountAmount, 2), 'total' => round($net + $tax - $discountAmount, 2)];
    }

    /**
     * Must run inside the save transaction, after the advisory lock, so two concurrent quotes cannot take the same number.
     */
    public function nextNumber(): string
    {
        $prefix = 'Q-'.now()->year.'-';
        $last = DB::table('operating_records')->where('type', 'quotes')->where('data->quote_number', 'like', $prefix.'%')->get()->map(fn (object $quote): int => (int) substr($this->catalog->data($quote)['quote_number'], strlen($prefix)))->max() ?? 0;

        return $prefix.str_pad((string) ($last + 1), 4, '0', STR_PAD_LEFT);
    }
}
```
  > **dependency cycle:** `OperatingQuotes` يعتمد على `OperatingWorkflow`، و`OperatingWorkflow` سيستدعي `OperatingQuotes` عبر `app(OperatingQuotes::class)` (وليس constructor injection)، تمامًا كما يستدعي `OperatingAttendance` اليوم. لا دائرة في الـcontainer.
  > `where('data->quote_number', 'like', …)`: على pgsql يصبح `data->>'quote_number' like` ✅، وعلى SQLite `json_extract` ✅.

- [ ] **Step 5: `OperatingWorkflow::save()`**
  - في بناء القواعد، أول سطر داخل `foreach ($fields as $field)`:
```php
            if ($field['type'] === 'lines') {
                $rules['data.'.$field['key']] = 'nullable|array|max:100';
                foreach (['description' => 'nullable|string|max:500', 'kind' => 'nullable|in:product,service', 'quantity' => 'nullable|numeric|min:0|max:1000000', 'unit_price' => 'nullable|numeric|min:0|max:1000000000', 'discount' => 'nullable|numeric|min:0|max:100', 'tax' => 'nullable|numeric|min:0|max:100'] as $column => $columnRule) {
                    $rules['data.'.$field['key'].'.*.'.$column] = $columnRule;
                }

                continue;
            }
```
  - في حلقة القيم، أول سطر:
```php
            if ($field['type'] === 'lines') {
                $data[$field['key']] = app(OperatingQuotes::class)->normalizeLines($validated['data'][$field['key']] ?? []);

                continue;
            }
```
  - بعد كتلة `contacts` (T4):
```php
        if ($type === 'quotes') {
            if ($data['line_items'] ?? []) {
                $data['totals'] = app(OperatingQuotes::class)->totals($data['line_items'], (float) ($data['discount'] ?? 0));
                $data['price'] = $data['totals']['total'];
            } else {
                unset($data['totals']);
                if (empty($data['price'])) {
                    $this->fail('أضف بنودًا للعرض أو سجّل سعرًا إجماليًا.', 'Add line items or enter a total price.');
                }
            }
        }
```
  - داخل `DB::transaction` في فرع الإنشاء (`} else {` قبل `insertGetId`):
```php
                if ($type === 'quotes') {
                    $data['quote_number'] = app(OperatingQuotes::class)->nextNumber();
                }
```
  (أضف `&$data` لا يلزم لأن التعديل داخل الـclosure يستخدم نسخته المحلية قبل `json_encode` في نفس الفرع. تأكد أن `insertGetId` يستخدم `$data` بعد هذا السطر.)
  - **نسخة جديدة من عرض** (`create?from=`): `quote_number` و`totals` ليستا ضمن الحقول، فـ`save()` لن يأخذهما من الطلب، ويُولَّد رقم جديد. ✅
  - في `gate()` استبدل `if ($record->type === 'quotes' && $target === 'approved') { $this->requireFields($data, ['scope_id']); … }` بـ:
```php
        if ($record->type === 'quotes' && $target === 'approved') {
            if (! empty($data['scope_id'])) {
                if (DB::table('operating_records')->where('id', $data['scope_id'])->value('status') !== 'approved') {
                    $this->fail('العرض المربوط بنطاق يحتاج نسخة نطاق معتمدة.', 'A quote linked to a scope requires an approved scope version.');
                }
            } elseif (empty($data['line_items'])) {
                $this->fail('العرض بدون نطاق يحتاج بنودًا مفصلة قبل الاعتماد.', 'A quote without a scope needs line items before approval.');
            }
        }
```

- [ ] **Step 6: الواجهة**
  - `form.blade.php`: داخل `@foreach($fields as $field)` اجعل أول فرع:
```blade
@if($field['type']==='lines')@include('admin.operating.line-items',['key'=>$key,'lines'=>is_array($value)?$value:[],'label'=>$catalog->label($field['label']),'totals'=>$data['totals']??null,'readonly'=>!$canEdit])@continue @endif
```
  (ضعه بعد سطر `@php $key=…` وقبل `<label>`، ولاحظ أن `$value` قد يكون مصفوفة من `old()`.)
  - `line-items.blade.php`:
```blade
<div class="long line-items" data-line-items data-next="{{ count($lines) + 1 }}"><b>{{ $label }}</b>
<table><thead><tr><th>{{ __('الوصف') }}</th><th>{{ __('النوع') }}</th><th>{{ __('الكمية') }}</th><th>{{ __('سعر الوحدة') }}</th><th>{{ __('خصم %') }}</th><th>{{ __('ضريبة %') }}</th><th>{{ __('الإجمالي') }}</th><th></th></tr></thead>
<tbody>@foreach(array_merge($lines, [[]]) as $i=>$line)<tr data-line>
<td><input name="data[{{ $key }}][{{ $i }}][description]" value="{{ $line['description'] ?? '' }}" maxlength="500"></td>
<td><select name="data[{{ $key }}][{{ $i }}][kind]"><option value="service" @selected(($line['kind']??'service')==='service')>{{ __('خدمة') }}</option><option value="product" @selected(($line['kind']??'')==='product')>{{ __('منتج') }}</option></select></td>
@foreach(['quantity'=>1,'unit_price'=>0,'discount'=>0,'tax'=>0] as $column=>$default)<td><input type="number" step="any" min="0" name="data[{{ $key }}][{{ $i }}][{{ $column }}]" value="{{ $line[$column] ?? ($line ? $default : '') }}" data-{{ $column }}></td>@endforeach
<td dir="ltr" data-line-total>—</td><td>@unless($readonly)<button type="button" class="button secondary" data-remove-line aria-label="{{ __('حذف البند') }}">×</button>@endunless</td></tr>@endforeach</tbody></table>
@unless($readonly)<button type="button" class="button secondary" data-add-line>+ {{ __('إضافة بند') }}</button>@endunless
@if($totals)<div class="inline-stats"><span>{{ __('الإجمالي قبل الخصم') }}: <b dir="ltr">{{ number_format($totals['subtotal'],2) }}</b></span><span>{{ __('خصم البنود') }}: <b dir="ltr">{{ number_format($totals['line_discount'],2) }}</b></span><span>{{ __('الضريبة') }}: <b dir="ltr">{{ number_format($totals['tax'],2) }}</b></span><span>{{ __('الخصم الإضافي') }}: <b dir="ltr">{{ number_format($totals['discount'],2) }}</b></span><span>{{ __('الإجمالي النهائي') }}: <b dir="ltr">{{ number_format($totals['total'],2) }}</b></span></div>@endif
<p class="help">{{ __('الإجمالي يُحسب ويُحفظ على الخادم. الصف الفارغ يُتجاهل.') }}</p></div>
```
  - `operating.js` (أضف في نهاية الملف، بنفس أسلوب الملف):
```js
document.querySelectorAll('[data-line-items]').forEach((editor) => {
  const body = editor.querySelector('tbody');
  const recalc = () => body.querySelectorAll('[data-line]').forEach((row) => {
    const value = (name) => parseFloat(row.querySelector(`[data-${name}]`)?.value) || 0;
    const net = value('quantity') * value('unit_price') * (1 - value('discount') / 100);
    row.querySelector('[data-line-total]').textContent = row.querySelector('input')?.value ? (net * (1 + value('tax') / 100)).toFixed(2) : '—';
  });
  editor.addEventListener('input', recalc);
  editor.querySelector('[data-add-line]')?.addEventListener('click', () => {
    const template = body.querySelector('[data-line]:last-child');
    const row = template.cloneNode(true);
    const index = editor.dataset.next++;
    row.querySelectorAll('input,select').forEach((input) => {
      input.name = input.name.replace(/\[\d+\]/, `[${index}]`);
      if (input.tagName === 'INPUT') input.value = input.dataset.quantity !== undefined ? '1' : '';
    });
    body.appendChild(row);
    recalc();
  });
  editor.addEventListener('click', (event) => {
    if (!event.target.matches('[data-remove-line]')) return;
    if (body.querySelectorAll('[data-line]').length > 1) event.target.closest('[data-line]').remove();
    else event.target.closest('[data-line]').querySelectorAll('input').forEach((input) => { input.value = ''; });
    recalc();
  });
  recalc();
});
```
  > `name.replace(/\[\d+\]/, …)` يستبدل أول رقم بين أقواس، وهو فهرس الصف (`data[line_items][3][description]`: أول `[رقم]` هو 3 لأن `[line_items]` ليس رقمًا). ✅
  - `operating.css`: `.line-items table{width:100%}.line-items td input,.line-items td select{min-width:0;width:100%}.line-items td:first-child{min-width:14rem}` + تمرير أفقي داخل الكارد على الشاشات الضيقة: `.line-items{overflow-x:auto}`.

- [ ] **Step 7:** الترجمات (الوصف، النوع، الكمية، سعر الوحدة، خصم %، ضريبة %، الإجمالي، خدمة، منتج، حذف البند، إضافة بند، الإجمالي قبل الخصم، خصم البنود، الضريبة، الخصم الإضافي، الإجمالي النهائي، الإجمالي يُحسب…).
- [ ] **Step 8:** الاختبارات → PASS (وتأكد أن اختبارات الحضور القديمة ما زالت ناجحة لأن `save()` تغيّر)، pint، commit: `feat(quotes): itemised quotes with discounts, taxes, totals and numbering`.

---

### Task 6: مستند العرض (PDF) والتحويل إلى مشروع أو عقد (FIN-9، الجزء الثاني)

**Files:**
- Modify: `app/Services/OperatingQuotes.php` (`document`, `draftFrom`)
- Modify: `config/operating.php` (`projects.quote_id`)
- Modify: `app/Services/OperatingWorkflow.php` (قاعدة `projects.quote_id`)
- Modify: `app/Http/Controllers/OperatingController.php` (`create` + `form` لعنوان أولي)
- Modify: `app/Http/Controllers/SalesController.php` (`printQuote`)
- Create: `resources/views/admin/operating/quote-print.blade.php`
- Modify: `routes/admin.php`، `form.blade.php`، `lang/en.json`
- Test: `tests/Feature/Operating/QuoteDocumentTest.php`

**Interfaces:**
- Consumes: `OperatingQuotes::totals()`, حقول T5.
- Produces:
  - `OperatingQuotes::document(User $user, object $quote): array` → `['quote' => object, 'number' => string, 'issued' => string, 'valid_until' => ?string, 'status' => string, 'draft' => bool, 'client' => array{name: ?string, contact: ?string, email: ?string, phone: ?string, address: ?string, tax_number: ?string}, 'lines' => list, 'totals' => ?array, 'price' => float, 'currency' => string, 'payment_terms' => ?string, 'terms' => ?string]`.
  - `OperatingQuotes::draftFrom(User $user, int $quoteId, string $type): array{title: string, data: array<string, mixed>}` لـ`projects` أو `contracts`.
  - `OperatingQuotes::convertedProject(int $quoteId): ?object`.

- [ ] **Step 1: الاختبار الفاشل**

```php
<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class QuoteDocumentTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    private int $clientId;

    private function quote(string $status): int
    {
        $this->clientId = $this->operatingRecord('clients', 'active', ['company' => 'Acme Corp', 'decision_maker' => 'Mona', 'contact' => 'Mona Ali', 'email' => 'mona@acme.test', 'tax_number' => 'TX-9'], 'Acme Corp');
        $lead = $this->operatingRecord('opportunities', 'proposal', ['client_id' => $this->clientId, 'need' => 'Customer portal', 'requested_date' => '2026-12-15'], 'Portal lead');

        return $this->operatingRecord('quotes', $status, ['opportunity_id' => $lead, 'currency' => 'USD', 'payment_terms' => '50% upfront', 'quote_number' => 'Q-2026-0007', 'valid_until' => '2026-11-01', 'price' => 1140, 'line_items' => [['description' => 'Portal build', 'kind' => 'service', 'quantity' => 1, 'unit_price' => 1000, 'discount' => 0, 'tax' => 14]], 'totals' => ['subtotal' => 1000, 'line_discount' => 0, 'net' => 1000, 'tax' => 140, 'discount' => 0, 'total' => 1140]], 'Portal quote');
    }

    public function test_the_printable_document_shows_client_lines_and_totals(): void
    {
        $id = $this->quote('approved');

        $this->actingAs($this->operatingUser('sales'))->get("/admin/operations/quotes/{$id}/print")
            ->assertOk()->assertSee('Q-2026-0007')->assertSee('Acme Corp')->assertSee('TX-9')->assertSee('Portal build')->assertSee('1,140.00')->assertDontSee(__('مسودة — غير معتمد'));
    }

    public function test_unapproved_quotes_are_watermarked_and_outsiders_are_blocked(): void
    {
        $id = $this->quote('draft');

        $this->actingAs($this->operatingUser('sales'))->get("/admin/operations/quotes/{$id}/print")->assertOk()->assertSee(__('مسودة — غير معتمد'));
        $this->actingAs($this->operatingUser('developer'))->get("/admin/operations/quotes/{$id}/print")->assertForbidden();
        $this->actingAs($this->operatingUser('owner'))->get("/admin/operations/quotes/{$this->clientId}/print")->assertNotFound();
    }

    public function test_an_approved_quote_prefills_a_project_and_a_contract(): void
    {
        $id = $this->quote('approved');
        $owner = $this->operatingUser('owner');

        $this->actingAs($owner)->get("/admin/operations/projects/create?quote={$id}")->assertOk()->assertSee('Customer portal')->assertSee('2026-12-15')->assertSee('value="'.$id.'" selected', false);
        $this->actingAs($owner)->get("/admin/operations/contracts/create?quote={$id}")->assertOk()->assertSee('Portal build');
    }

    public function test_conversion_requires_an_approved_quote_and_happens_once(): void
    {
        $owner = $this->operatingUser('owner');
        $draft = $this->quote('draft');
        $this->actingAs($owner)->get("/admin/operations/projects/create?quote={$draft}")->assertSessionHasErrors('workflow');

        $approved = $this->quote('approved');
        $project = ['title' => 'Portal', 'reason' => 'Converted from quote', 'data' => ['client_id' => $this->clientId, 'quote_id' => $approved, 'outcome' => 'Customer portal', 'original_due' => '2026-12-15', 'client_contact' => 'Mona Ali']];
        $this->actingAs($owner)->post('/admin/operations/projects', $project)->assertSessionHasNoErrors();
        $this->actingAs($owner)->post('/admin/operations/projects', $project)->assertSessionHasErrors('workflow');

        $project['data']['quote_id'] = $draft;
        $this->actingAs($owner)->post('/admin/operations/projects', $project)->assertSessionHasErrors('workflow');
    }
}
```

- [ ] **Step 2:** → FAIL.

- [ ] **Step 3:** `projects.fields` أضف بعد `contract_id`: `{ "key": "quote_id", "label": { "ar": "العرض المعتمد المصدر", "en": "Source quote" }, "type": "relation", "required": false, "entity": "quotes" }`.

- [ ] **Step 4: قاعدة المشروع** في `save()`:
```php
        if ($type === 'projects' && ! empty($data['quote_id'])) {
            if (DB::table('operating_records')->where('id', $data['quote_id'])->value('status') !== 'approved') {
                $this->fail('التحويل إلى مشروع يحتاج عرضًا معتمدًا.', 'Only an approved quote can become a project.');
            }
            if (DB::table('operating_records')->where('type', 'projects')->where('status', '!=', 'archived')->where('data->quote_id', (int) $data['quote_id'])->when($id, fn ($query) => $query->where('id', '!=', $id))->exists()) {
                $this->fail('هذا العرض تحوّل إلى مشروع بالفعل.', 'This quote has already been converted to a project.');
            }
        }
```
  > فحص "سجل مرتبط من مشروع مختلف" العام لا يتأثر: العروض ليس لها `project_id`.

- [ ] **Step 5: `OperatingQuotes`** (أضف):

```php
    /**
     * @return array<string, mixed>
     */
    public function document(User $user, object $quote): array
    {
        $data = $this->catalog->data($quote);
        $opportunity = ! empty($data['opportunity_id']) ? DB::table('operating_records')->find($data['opportunity_id']) : null;
        $lead = $opportunity ? $this->catalog->data($opportunity) : [];
        $client = ! empty($lead['client_id']) && $this->access->can($user, 'clients') ? DB::table('operating_records')->find($lead['client_id']) : null;
        $clientData = $client ? $this->catalog->data($client) : [];

        return [
            'quote' => $quote,
            'number' => $data['quote_number'] ?? 'Q-'.$quote->id,
            'issued' => substr((string) $quote->created_at, 0, 10),
            'valid_until' => $data['valid_until'] ?? null,
            'status' => $quote->status,
            'draft' => $quote->status !== 'approved',
            'client' => ['name' => $clientData['company'] ?? $client?->title ?? ($lead['lead_company'] ?? null), 'contact' => $clientData['contact'] ?? ($lead['lead_contact'] ?? null), 'email' => $clientData['email'] ?? ($lead['lead_email'] ?? null), 'phone' => $clientData['phone'] ?? ($lead['lead_phone'] ?? null), 'address' => $clientData['address'] ?? null, 'tax_number' => $clientData['tax_number'] ?? null],
            'lines' => $data['line_items'] ?? [],
            'totals' => $data['totals'] ?? null,
            'price' => (float) ($data['price'] ?? 0),
            'currency' => strtoupper($data['currency'] ?? '') ?: 'USD',
            'payment_terms' => $data['payment_terms'] ?? null,
            'terms' => $data['terms'] ?? null,
        ];
    }

    /**
     * Prefill a project or contract form from an approved quote; the user still reviews and saves it through the normal gates.
     *
     * @return array{title: string, data: array<string, mixed>}
     */
    public function draftFrom(User $user, int $quoteId, string $type): array
    {
        $quote = $this->access->record($user, $quoteId);
        abort_unless($quote->type === 'quotes' && in_array($type, ['projects', 'contracts'], true), 404);
        if ($quote->status !== 'approved') {
            $this->workflow->fail('التحويل متاح للعروض المعتمدة فقط.', 'Only approved quotes can be converted.');
        }
        $data = $this->catalog->data($quote);
        $lead = ! empty($data['opportunity_id']) ? $this->catalog->data(DB::table('operating_records')->find($data['opportunity_id'])) : [];
        $clientId = $lead['client_id'] ?? null;
        $client = $clientId ? $this->catalog->data(DB::table('operating_records')->find($clientId)) : [];
        $scope = implode("\n", array_map(fn (array $line): string => '- '.$line['description'].' × '.$line['quantity'], $data['line_items'] ?? []));
        if ($type === 'contracts') {
            return ['title' => $quote->title, 'data' => ['client_id' => $clientId, 'quote_id' => $quote->id, 'scope_reference' => ($data['quote_number'] ?? '')."\n".$scope]];
        }
        $contract = DB::table('operating_records')->where('type', 'contracts')->where('status', 'approved')->where('data->quote_id', $quote->id)->value('id');

        return ['title' => $quote->title, 'data' => ['client_id' => $clientId, 'quote_id' => $quote->id, 'contract_id' => $contract, 'outcome' => trim(($lead['need'] ?? '')."\n".$scope), 'original_due' => $lead['requested_date'] ?? null, 'client_contact' => $client['contact'] ?? ($lead['lead_contact'] ?? null)]];
    }

    public function convertedProject(int $quoteId): ?object
    {
        return DB::table('operating_records')->where('type', 'projects')->where('status', '!=', 'archived')->where('data->quote_id', $quoteId)->first();
    }
```
  (أضف `use App\Models\User;`.)

- [ ] **Step 6: `OperatingController`**
  - في `create()` بعد كتلة `from`:
```php
        $initialTitle = null;
        if ($request->filled('quote')) {
            $draft = app(OperatingQuotes::class)->draftFrom($request->user(), $request->integer('quote'), $type);
            $initial = $draft['data'];
            $initialTitle = $draft['title'];
        }

        return $this->form($type, $request, null, $initial, $initialTitle);
```
  - `form()` يقبل `?string $initialTitle = null` ويمرره للـview كـ`'initialTitle' => $initialTitle`.
  - `form.blade.php`: `value="{{ old('title',$record?->title ?? ($initialTitle ?? null)) }}"`.
  > `draftFrom` يرمي `ValidationException` على GET، فيحوّل Laravel للخلف مع الأخطاء (يطابق الاختبار `assertSessionHasErrors`).

- [ ] **Step 7: الطباعة**
  - `SalesController`:
```php
    public function printQuote(int $id, Request $request): Response
    {
        $quote = $this->access->record($request->user(), $id);
        abort_unless($quote->type === 'quotes', 404);

        return response()->view('admin.operating.quote-print', app(OperatingQuotes::class)->document($request->user(), $quote));
    }
```
  (حقنه في الـconstructor.)
  - المسار: `Route::get('/operations/quotes/{id}/print', [S::class, 'printQuote'])->whereNumber('id');`
  - `quote-print.blade.php` (صفحة مستقلة، **لا** تمد `admin.layout`):
```blade
@php $locale=app()->getLocale(); @endphp
<!doctype html><html lang="{{ $locale }}" dir="{{ $locale==='ar'?'rtl':'ltr' }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>{{ $number }} — Findo Group</title>
<style>
@page{size:A4;margin:16mm}
*{box-sizing:border-box}body{margin:0;background:#eef0f3;color:#14161a;font:14px/1.6 system-ui,"Segoe UI",Tahoma,sans-serif}
.sheet{max-width:210mm;margin:24px auto;background:#fff;padding:18mm 16mm;position:relative;box-shadow:0 1px 4px rgba(0,0,0,.12)}
.toolbar{max-width:210mm;margin:16px auto 0;display:flex;gap:8px;justify-content:flex-end}.toolbar button,.toolbar a{font:inherit;padding:8px 14px;border-radius:6px;border:1px solid #c9ced6;background:#fff;color:#14161a;text-decoration:none;cursor:pointer}.toolbar button{background:#14161a;color:#fff;border-color:#14161a}
header{display:flex;justify-content:space-between;align-items:flex-start;gap:24px;border-bottom:2px solid #14161a;padding-bottom:12px;margin-bottom:18px}header h1{margin:0;font-size:22px}.meta{text-align:end;font-size:13px}
.parties{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:18px}.parties h2{font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#5b6270;margin:0 0 4px}
table{width:100%;border-collapse:collapse;font-size:13px}th,td{padding:8px 6px;border-bottom:1px solid #e3e6ea;text-align:start}th{background:#f5f6f8;font-weight:600}td.num,th.num{text-align:end;font-variant-numeric:tabular-nums;white-space:nowrap}
.totals{margin-inline-start:auto;width:60%;margin-top:12px}.totals td{border:0;padding:4px 6px}.totals tr.grand td{border-top:2px solid #14161a;font-weight:700;font-size:15px}
.terms{margin-top:20px;font-size:13px;white-space:pre-line}.terms h2{font-size:13px;margin:12px 0 4px}
.watermark{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;font-size:64px;font-weight:800;color:rgba(200,30,30,.10);transform:rotate(-24deg)}
@media print{body{background:#fff}.toolbar{display:none}.sheet{margin:0;box-shadow:none;padding:0;max-width:none}}
@media (max-width:640px){.sheet{padding:16px;margin:12px}.parties{grid-template-columns:1fr}.totals{width:100%}}
</style></head><body>
<div class="toolbar"><a href="/admin/operations/record/{{ $quote->id }}">{{ __('العودة للعرض') }}</a><button type="button" onclick="window.print()">{{ __('تحميل PDF / طباعة') }}</button></div>
<main class="sheet">@if($draft)<div class="watermark">{{ __('مسودة — غير معتمد') }}</div>@endif
<header><div>@include('admin.brand-logo')<h1>{{ __('عرض سعر') }}</h1></div><div class="meta"><div><b>{{ __('رقم العرض') }}:</b> <span dir="ltr">{{ $number }}</span></div><div><b>{{ __('التاريخ') }}:</b> {{ $issued }}</div>@if($valid_until)<div><b>{{ __('صالح حتى') }}:</b> {{ $valid_until }}</div>@endif<div><b>{{ __('الحالة') }}:</b> {{ app(\App\Services\OperatingCatalog::class)->status($status) }}</div></div></header>
<section class="parties"><div><h2>{{ __('مقدم إلى') }}</h2><b>{{ $client['name'] ?? '—' }}</b>@foreach(['contact','email','phone','address'] as $key)@if($client[$key])<div>{{ $client[$key] }}</div>@endif @endforeach @if($client['tax_number'])<div>{{ __('الرقم الضريبي') }}: {{ $client['tax_number'] }}</div>@endif</div><div><h2>{{ __('من') }}</h2><b>Findo Group</b><div>findogroup.com</div></div></section>
@if($lines)<table><thead><tr><th>#</th><th>{{ __('الوصف') }}</th><th>{{ __('النوع') }}</th><th class="num">{{ __('الكمية') }}</th><th class="num">{{ __('سعر الوحدة') }}</th><th class="num">{{ __('خصم %') }}</th><th class="num">{{ __('ضريبة %') }}</th><th class="num">{{ __('الإجمالي') }}</th></tr></thead><tbody>
@foreach($lines as $i=>$line)@php $net=$line['quantity']*$line['unit_price']*(1-$line['discount']/100); @endphp<tr><td>{{ $i+1 }}</td><td>{{ $line['description'] }}</td><td>{{ $line['kind']==='product'?__('منتج'):__('خدمة') }}</td><td class="num">{{ rtrim(rtrim(number_format($line['quantity'],2),'0'),'.') }}</td><td class="num">{{ number_format($line['unit_price'],2) }}</td><td class="num">{{ $line['discount'] }}</td><td class="num">{{ $line['tax'] }}</td><td class="num">{{ number_format($net*(1+$line['tax']/100),2) }}</td></tr>@endforeach</tbody></table>@endif
<table class="totals">@if($totals)<tr><td>{{ __('الإجمالي قبل الخصم') }}</td><td class="num">{{ number_format($totals['subtotal'],2) }}</td></tr><tr><td>{{ __('خصم البنود') }}</td><td class="num">− {{ number_format($totals['line_discount'],2) }}</td></tr><tr><td>{{ __('الضريبة') }}</td><td class="num">{{ number_format($totals['tax'],2) }}</td></tr>@if($totals['discount'])<tr><td>{{ __('الخصم الإضافي') }}</td><td class="num">− {{ number_format($totals['discount'],2) }}</td></tr>@endif @endif<tr class="grand"><td>{{ __('الإجمالي النهائي') }}</td><td class="num" dir="ltr">{{ number_format($totals['total'] ?? $price,2) }} {{ $currency }}</td></tr></table>
<section class="terms">@if($payment_terms)<h2>{{ __('شروط الدفع') }}</h2>{{ $payment_terms }}@endif @if($terms)<h2>{{ __('الشروط والملاحظات') }}</h2>{{ $terms }}@endif</section>
</main>@if(request('autoprint'))<script>window.addEventListener('load',()=>window.print());</script>@endif</body></html>
```
  > تحقق أن `admin.brand-logo` partial يعمل خارج الـlayout (لا يعتمد على متغيرات الـlayout). إن اعتمد، استبدله بنص "Findo Group".
  > تحقق في المتصفح (Task 8) أن الأرقام بالعربية لا تنعكس وأن الصفحة A4 واحدة لعرض بـ3 بنود.

- [ ] **Step 8: أزرار العرض** في `form.blade.php` (أزرار السياق):
```blade
@if($record && $type==='quotes')<div class="card row-actions"><a class="button secondary" href="/admin/operations/quotes/{{ $record->id }}/print" target="_blank" rel="noopener">{{ __('مستند العرض / PDF') }}</a>
@if($record->status==='approved')@php $converted=app(\App\Services\OperatingQuotes::class)->convertedProject($record->id); @endphp
@if($converted)<a class="button secondary" href="/admin/operations/record/{{ $converted->id }}">{{ __('فتح المشروع المحوَّل') }}</a>@elseif($access->can(auth()->user(),'projects','write'))<a class="button" href="/admin/operations/projects/create?quote={{ $record->id }}">{{ __('تحويل إلى مشروع') }}</a>@endif
@if($access->can(auth()->user(),'contracts','write'))<a class="button secondary" href="/admin/operations/contracts/create?quote={{ $record->id }}">{{ __('إنشاء عقد (أمر بيع)') }}</a>@endif @endif</div>@endif
```
  > `convertedProject` لا يمر عبر `access->query` لكنه يعرض فقط رابطًا؛ صفحة المشروع نفسها محمية. مقبول.

- [ ] **Step 9:** الترجمات، الاختبارات → PASS، الحزمة، pint، commit: `feat(quotes): printable quote document and conversion to project or contract`.

---

### Task 7: المشاريع: الفريق والأولوية ونسبة الإنجاز ولوحة المشاريع (FIN-11)

**Files:**
- Modify: `config/operating.php` (`projects`: `project_type`, `priority`؛ كيان `project_members`)
- Modify: `app/Services/OperatingWorkflow.php` (تكرار العضو، تواريخ العضوية)
- Modify: `app/Services/OperatingAccess.php` (رؤية المشروع للأعضاء)
- Create: `app/Services/OperatingProjects.php`
- Modify: `app/Services/OperatingReports.php` (`progress`, `members`)
- Create: `app/Http/Controllers/ProjectController.php`
- Create: `resources/views/admin/operating/projects-portfolio.blade.php`
- Modify: `form.blade.php`، `layout.blade.php`، `listing.blade.php`، `routes/admin.php`، `operating.css`، `lang/en.json`
- Test: `tests/Feature/Operating/ProjectLifecycleTest.php`

**Interfaces:**
- Produces:
  - `OperatingProjects::DONE_JIRA_STATUSES = ['done', 'closed', 'resolved']`.
  - `OperatingProjects::progress(object $project): array{percent: ?int, basis: ?string, tasks: array{done: int, total: int}, milestones: array{accepted: int, total: int}, due: ?string, overdue: bool, due_soon: bool}`.
  - `OperatingProjects::portfolio(User $user, array $filters = []): list<array{id: int, title: string, status: string, client: ?string, manager: ?string, type: ?string, priority: ?string, members: int, progress: array}>`.

**المعادلة:** `percent` = المهام المنجزة ÷ كل المهام غير المؤرشفة × 100 (مهمة Findo منجزة إذا `status = done`، ومهمة Jira إذا `source_status` (بلا حساسية للحالة) ضمن `DONE_JIRA_STATUSES`). إن لم توجد مهام: المحطات المقبولة ÷ كل المحطات. إن لم توجد محطات: `null` (يُعرض "غير معلوم"). مشروع `closed` = 100. `overdue` = الموعد (المعتمد وإلا الأصلي) قبل اليوم والمشروع غير مغلق. `due_soon` = خلال 14 يومًا.

- [ ] **Step 1: الاختبار الفاشل**

```php
<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingProjects;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class ProjectLifecycleTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 10:00'));
    }

    private function project(string $due = '2026-12-01', string $status = 'execution'): int
    {
        $client = $this->operatingRecord('clients', 'active', ['company' => 'Acme', 'decision_maker' => 'Mona', 'contact' => '0100'], 'Acme');

        return $this->operatingRecord('projects', $status, ['client_id' => $client, 'outcome' => 'Portal', 'original_due' => $due, 'client_contact' => 'Mona', 'priority' => 'high', 'project_type' => 'web'], 'Portal');
    }

    public function test_progress_uses_tasks_then_milestones_and_counts_jira_done(): void
    {
        $projects = app(OperatingProjects::class);
        $withTasks = $this->project();
        $this->operatingRecord('tasks', 'done', ['project_id' => $withTasks], 'T1');
        $this->operatingRecord('tasks', 'in_progress', ['project_id' => $withTasks], 'T2');
        $this->operatingRecord('tasks', 'backlog', ['project_id' => $withTasks], 'T3');
        $jira = $this->operatingRecord('tasks', 'backlog', ['project_id' => $withTasks, 'source_status' => 'Done'], 'J1');
        DB::table('operating_records')->where('id', $jira)->update(['source' => 'jira']);
        $this->operatingRecord('tasks', 'archived', ['project_id' => $withTasks], 'Old');

        $this->assertSame(50, $projects->progress(DB::table('operating_records')->find($withTasks))['percent']);

        $withMilestones = $this->project();
        $this->operatingRecord('milestones', 'accepted', ['project_id' => $withMilestones], 'M1');
        $this->operatingRecord('milestones', 'planned', ['project_id' => $withMilestones], 'M2');
        $progress = $projects->progress(DB::table('operating_records')->find($withMilestones));
        $this->assertSame(50, $progress['percent']);
        $this->assertSame('milestones', $progress['basis']);

        $this->assertNull($projects->progress(DB::table('operating_records')->find($this->project()))['percent']);
        $this->assertSame(100, $projects->progress(DB::table('operating_records')->find($this->project('2026-01-01', 'closed')))['percent']);
    }

    public function test_overdue_and_due_soon_flags(): void
    {
        $projects = app(OperatingProjects::class);

        $this->assertTrue($projects->progress(DB::table('operating_records')->find($this->project('2026-10-01')))['overdue']);
        $this->assertTrue($projects->progress(DB::table('operating_records')->find($this->project('2026-10-20')))['due_soon']);
        $this->assertFalse($projects->progress(DB::table('operating_records')->find($this->project('2026-10-01', 'closed')))['overdue']);
    }

    public function test_members_are_unique_per_role_and_allocation_is_capped(): void
    {
        $delivery = $this->operatingUser('delivery');
        $projectId = $this->project();
        $employee = $this->employeeFor($this->operatingUser('developer'));
        $member = ['title' => 'Dev on portal', 'reason' => 'Staffing', 'data' => ['project_id' => $projectId, 'assignee_id' => $employee, 'member_role' => 'developer', 'allocation_percent' => 50, 'start_date' => '2026-10-10']];

        $this->actingAs($delivery)->post('/admin/operations/project_members', $member)->assertSessionHasNoErrors();
        $this->actingAs($delivery)->post('/admin/operations/project_members', $member)->assertSessionHasErrors('workflow');
        $member['data']['member_role'] = 'qa';
        $member['data']['allocation_percent'] = 150;
        $this->actingAs($delivery)->post('/admin/operations/project_members', $member)->assertSessionHasErrors('workflow');
        $member['data']['allocation_percent'] = 20;
        $member['data']['end_date'] = '2026-10-01';
        $this->actingAs($delivery)->post('/admin/operations/project_members', $member)->assertSessionHasErrors('workflow');
    }

    public function test_a_developer_sees_projects_they_are_members_of(): void
    {
        $developer = $this->operatingUser('developer');
        $employee = $this->employeeFor($developer);
        $mine = $this->project();
        $this->project();
        $this->operatingRecord('project_members', 'active', ['project_id' => $mine, 'assignee_id' => $employee, 'member_role' => 'developer'], 'Member');

        $this->assertSame([$mine], collect($this->actingAs($developer)->getJson('/admin/operations/projects?format=json')->json('data'))->pluck('id')->all());
    }

    public function test_the_portfolio_lists_projects_with_progress(): void
    {
        $id = $this->project('2026-10-01');
        $this->operatingRecord('tasks', 'done', ['project_id' => $id], 'T1');

        $this->actingAs($this->operatingUser('delivery'))->get('/admin/operations/projects/portfolio')->assertOk()->assertSee('Portal')->assertSee('100%')->assertSee(__('متأخر'));
        $this->actingAs($this->operatingUser('marketing'))->get('/admin/operations/projects/portfolio')->assertForbidden();
    }
}
```

- [ ] **Step 2:** → FAIL.

- [ ] **Step 3: الـconfig**
  - `projects.fields` بعد `client_id`:
```json
        { "key": "project_type", "label": { "ar": "نوع المشروع", "en": "Project type" }, "type": "select", "required": false, "options": {
          "web": { "ar": "موقع / نظام ويب", "en": "Web" }, "mobile": { "ar": "تطبيق جوال", "en": "Mobile app" }, "erp": { "ar": "ERP / نظام داخلي", "en": "ERP / internal system" },
          "ecommerce": { "ar": "متجر إلكتروني", "en": "E-commerce" }, "wordpress": { "ar": "ووردبريس", "en": "WordPress" }, "maintenance": { "ar": "صيانة", "en": "Maintenance" }, "other": { "ar": "أخرى", "en": "Other" } } },
        { "key": "priority", "label": { "ar": "الأولوية", "en": "Priority" }, "type": "select", "required": false, "options": {
          "low": { "ar": "منخفضة", "en": "Low" }, "medium": { "ar": "متوسطة", "en": "Medium" }, "high": { "ar": "عالية", "en": "High" }, "critical": { "ar": "حرجة", "en": "Critical" } } },
```
  - كيان جديد بعد `projects`:
```json
    "project_members": {
      "label": { "ar": "فريق المشروع", "en": "Project team" },
      "group": "delivery",
      "pages": [],
      "fields": [
        { "key": "project_id", "label": { "ar": "المشروع", "en": "Project" }, "type": "relation", "required": true, "entity": "projects" },
        { "key": "assignee_id", "label": { "ar": "الموظف", "en": "Employee" }, "type": "relation", "required": true, "entity": "employees" },
        { "key": "member_role", "label": { "ar": "الدور في المشروع", "en": "Project role" }, "type": "select", "required": true, "options": {
          "pm": { "ar": "مدير المشروع", "en": "Project manager" }, "tech_lead": { "ar": "قائد تقني", "en": "Tech lead" }, "developer": { "ar": "مطور", "en": "Developer" },
          "designer": { "ar": "مصمم", "en": "Designer" }, "ui_ux": { "ar": "مصمم UI/UX", "en": "UI/UX designer" }, "qa": { "ar": "اختبار جودة", "en": "QA" }, "devops": { "ar": "DevOps", "en": "DevOps" }, "other": { "ar": "أخرى", "en": "Other" } } },
        { "key": "allocation_percent", "label": { "ar": "نسبة التخصيص %", "en": "Allocation %" }, "type": "number", "required": false },
        { "key": "start_date", "label": { "ar": "من", "en": "From" }, "type": "date", "required": false },
        { "key": "end_date", "label": { "ar": "إلى", "en": "To" }, "type": "date", "required": false }
      ],
      "statuses": ["active", "ended", "archived"]
    },
```
  (تحقق من وجود تسمية لحالة `ended` في `statuses`، وأضفها إن لم توجد: "انتهت" / "Ended".)

- [ ] **Step 4: قواعد `save()`**
```php
        if ($type === 'project_members') {
            if (! empty($data['end_date']) && ! empty($data['start_date']) && $data['end_date'] < $data['start_date']) {
                $this->fail('تاريخ النهاية قبل تاريخ البداية.', 'The end date is before the start date.');
            }
            if (DB::table('operating_records')->where('type', 'project_members')->where('status', '!=', 'archived')->where('data->project_id', (int) $data['project_id'])->where('data->assignee_id', (int) $data['assignee_id'])->where('data->member_role', $data['member_role'])->when($id, fn ($query) => $query->where('id', '!=', $id))->exists()) {
                $this->fail('الموظف مسجل بنفس الدور في هذا المشروع.', 'This employee already has that role on the project.');
            }
        }
```
  (`allocation_percent` أُضيف لحلقة النسب في T2.)

- [ ] **Step 5: رؤية المشروع للأعضاء** في `OperatingAccess::query()` استبدل كتلة developer/qa للمشاريع:
```php
        if (in_array($user->role, ['developer', 'qa'], true) && $type === 'projects') {
            $taskIds = $this->query($user, 'tasks')->pluck('id');
            $memberIds = $employeeId ? DB::table('operating_records')->where('type', 'project_members')->where('status', 'active')->where('data->assignee_id', $employeeId)->pluck('id') : collect();
            $projects = DB::table('operating_links')->whereIn('record_id', $taskIds->merge($memberIds))->where('field', 'project_id')->pluck('target_id');

            return $query->whereIn('id', $projects);
        }
```

- [ ] **Step 6: `OperatingProjects`**
```php
<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class OperatingProjects
{
    public const DONE_JIRA_STATUSES = ['done', 'closed', 'resolved'];

    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access, private OperatingSales $sales) {}

    /**
     * Completion is an aggregate, so it counts every linked task even when the viewer cannot open each one.
     *
     * @return array{percent: ?int, basis: ?string, tasks: array{done: int, total: int}, milestones: array{accepted: int, total: int}, due: ?string, overdue: bool, due_soon: bool}
     */
    public function progress(object $project): array
    {
        $data = $this->catalog->data($project);
        $children = fn (string $type) => DB::table('operating_records')->where('type', $type)->where('status', '!=', 'archived')->whereIn('id', DB::table('operating_links')->where('field', 'project_id')->where('target_id', $project->id)->select('record_id'))->get();
        $tasks = $children('tasks');
        $done = $tasks->filter(fn (object $task): bool => $task->source === 'jira' ? in_array(strtolower($this->catalog->data($task)['source_status'] ?? ''), self::DONE_JIRA_STATUSES, true) : $task->status === 'done')->count();
        $milestones = $children('milestones');
        $accepted = $milestones->where('status', 'accepted')->count();
        [$percent, $basis] = match (true) {
            $project->status === 'closed' => [100, 'closed'],
            $tasks->isNotEmpty() => [(int) round($done / $tasks->count() * 100), 'tasks'],
            $milestones->isNotEmpty() => [(int) round($accepted / $milestones->count() * 100), 'milestones'],
            default => [null, null],
        };
        $due = $data['approved_due'] ?? $data['original_due'] ?? null;
        $open = ! in_array($project->status, ['closed', 'archived'], true);

        return ['percent' => $percent, 'basis' => $basis, 'tasks' => ['done' => $done, 'total' => $tasks->count()], 'milestones' => ['accepted' => $accepted, 'total' => $milestones->count()], 'due' => $due, 'overdue' => $open && $due && $due < today()->toDateString(), 'due_soon' => $open && $due && $due >= today()->toDateString() && $due <= today()->addDays(14)->toDateString()];
    }

    /**
     * @param  array{status?: string|null, priority?: string|null, type?: string|null}  $filters
     * @return list<array<string, mixed>>
     */
    public function portfolio(User $user, array $filters = []): array
    {
        $projects = $this->access->query($user, 'projects')->where('status', '!=', 'archived')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['priority'] ?? null, fn ($query, $priority) => $query->where('data->priority', $priority))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('data->project_type', $type))
            ->get();
        $ids = $projects->flatMap(fn (object $project): array => array_filter([($data = $this->catalog->data($project))['client_id'] ?? null, $data['manager_id'] ?? null]))->unique()->values()->all();
        $titles = $this->sales->titles($ids);
        $members = DB::table('operating_links')->where('field', 'project_id')->whereIn('target_id', $projects->pluck('id'))->whereIn('record_id', DB::table('operating_records')->where('type', 'project_members')->where('status', 'active')->select('id'))->selectRaw('target_id, count(*) as total')->groupBy('target_id')->pluck('total', 'target_id');
        $rank = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
        $rows = $projects->map(function (object $project) use ($titles, $members): array {
            $data = $this->catalog->data($project);

            return ['id' => $project->id, 'title' => $project->title, 'status' => $project->status, 'client' => $titles[$data['client_id'] ?? 0] ?? null, 'manager' => $titles[$data['manager_id'] ?? 0] ?? null, 'type' => $data['project_type'] ?? null, 'priority' => $data['priority'] ?? null, 'members' => (int) ($members[$project->id] ?? 0), 'progress' => $this->progress($project)];
        })->all();
        usort($rows, fn (array $a, array $b): int => [$rank[$a['priority']] ?? 4, $a['progress']['due'] ?? '9999'] <=> [$rank[$b['priority']] ?? 4, $b['progress']['due'] ?? '9999']);

        return $rows;
    }
}
```
  > `progress()` يُستدعى لكل مشروع (2 استعلام لكل مشروع). مقبول لعدد المشاريع الحالي (عشرات). لا تحسين مبكر.

- [ ] **Step 7: `OperatingReports::project`** أضف للمصفوفة المرجعة:
```php
            'progress' => app(OperatingProjects::class)->progress($project),
            'members' => $this->access->can($user, 'project_members') ? $this->access->query($user, 'project_members')->where('status', 'active')->whereIn('id', DB::table('operating_links')->where('field', 'project_id')->where('target_id', $project->id)->select('record_id'))->get() : collect(),
```
  (أضف `use Illuminate\Support\Facades\DB;`.) وفي `form.blade.php` داخل قسم `projectReport` بعد `inline-stats`:
```blade
@php $progress=$projectReport['progress']; @endphp<div class="progress-row"><span>{{ __('نسبة الإنجاز') }}: <b>{{ $progress['percent']!==null ? $progress['percent'].'%' : __('غير معلوم') }}</b> <small class="muted">({{ __('المهام') }} {{ $progress['tasks']['done'] }}/{{ $progress['tasks']['total'] }} · {{ __('المحطات') }} {{ $progress['milestones']['accepted'] }}/{{ $progress['milestones']['total'] }})</small></span><div class="progress" role="progressbar" aria-valuenow="{{ $progress['percent'] ?? 0 }}" aria-valuemin="0" aria-valuemax="100"><i style="width:{{ $progress['percent'] ?? 0 }}%"></i></div></div>
<h3>{{ __('فريق المشروع') }}</h3>@forelse($projectReport['members'] as $member)<a class="operation-row" href="/admin/operations/record/{{ $member->id }}"><b>{{ $member->title }}</b><span>{{ $catalog->data($member)['member_role'] ?? '' }}</span></a>@empty<p class="muted">{{ __('لم يُسجَّل فريق بعد.') }}</p>@endforelse @if($access->can(auth()->user(),'project_members','write'))<a class="button secondary" href="/admin/operations/project_members/create">+ {{ __('إضافة عضو') }}</a>@endif
```
  > عرض الدور: استخدم تسمية الـoption بدل المفتاح: `$catalog->label(collect($catalog->entity('project_members')['fields'])->firstWhere('key','member_role')['options'][$role] ?? $role)`. جهّزها في `@php` لتبقى السطر مقروءًا.

- [ ] **Step 8: اللوحة**
  - `ProjectController`:
```php
<?php

namespace App\Http\Controllers;

use App\Services\Cms;
use App\Services\OperatingAccess;
use App\Services\OperatingCatalog;
use App\Services\OperatingProjects;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProjectController extends Controller
{
    public function __construct(private OperatingProjects $projects, private OperatingAccess $access, private OperatingCatalog $catalog) {}

    public function portfolio(Request $request): Response
    {
        abort_unless($this->access->can($request->user(), 'projects'), 403);
        $filters = $request->validate(['status' => 'nullable|string|max:40', 'priority' => 'nullable|string|max:40', 'type' => 'nullable|string|max:40']);
        $fields = collect($this->catalog->entity('projects')['fields']);

        return response()->view('admin.operating.projects-portfolio', ['rows' => $this->projects->portfolio($request->user(), $filters), 'filters' => $filters, 'priorities' => $fields->firstWhere('key', 'priority')['options'], 'types' => $fields->firstWhere('key', 'project_type')['options'], 'collections' => Cms::COLLECTIONS, 'catalog' => $this->catalog, 'access' => $this->access]);
    }
}
```
  > `marketing` لا يقرأ `delivery` → 403 ✅. `sales`/`sales_manager`/`finance` يقرؤون المشاريع عبر الاستثناء في `can()`: يرون اللوحة بأعمدة محدودة؟ `portfolio` يقرأ `client_id` و`manager_id` من `catalog->data` مباشرة. **لـsales:** `visibleData` يخفي `manager_id`. استخدم `$this->access->visibleData($user, $project)` بدل `$this->catalog->data($project)` داخل `portfolio()` (لا داخل `progress()` لأنه aggregate). عدّل الكود أعلاه قبل التنفيذ.
  - المسار: `Route::get('/operations/projects/portfolio', [P::class, 'portfolio']);` مع `use App\Http\Controllers\ProjectController as P;`.
  - `projects-portfolio.blade.php`:
```blade
@extends('admin.layout')
@section('title',__('لوحة المشاريع'))
@section('content')
<div class="page-heading"><div><span class="eyebrow">{{ __('المشروعات والتطوير') }}</span><h1>{{ __('لوحة المشاريع') }}</h1><p class="muted">{{ __('الحالة والأولوية ونسبة الإنجاز والموعد لكل مشروع جارٍ.') }}</p></div>@if($access->can(auth()->user(),'projects','write'))<a class="button" href="/admin/operations/projects/create">+ {{ __('مشروع جديد') }}</a>@endif</div>
<div class="inline-stats card"><span>{{ __('مشروعات') }}: <b>{{ count($rows) }}</b></span><span class="danger">{{ __('متأخر') }}: <b>{{ collect($rows)->where('progress.overdue',true)->count() }}</b></span><span>{{ __('تسليم خلال 14 يومًا') }}: <b>{{ collect($rows)->where('progress.due_soon',true)->count() }}</b></span></div>
<form class="filters"><select name="status"><option value="">{{ __('كل الحالات') }}</option>@foreach($catalog->entity('projects')['statuses'] as $state)@if($state!=='archived')<option value="{{ $state }}" @selected(($filters['status']??'')===$state)>{{ $catalog->status($state) }}</option>@endif @endforeach</select><select name="priority"><option value="">{{ __('كل الأولويات') }}</option>@foreach($priorities as $key=>$label)<option value="{{ $key }}" @selected(($filters['priority']??'')===$key)>{{ $catalog->label($label) }}</option>@endforeach</select><select name="type"><option value="">{{ __('كل الأنواع') }}</option>@foreach($types as $key=>$label)<option value="{{ $key }}" @selected(($filters['type']??'')===$key)>{{ $catalog->label($label) }}</option>@endforeach</select><button class="button secondary">{{ __('تصفية') }}</button></form>
<div class="card table-card"><table><thead><tr><th>{{ __('المشروع') }}</th><th>{{ __('العميل') }}</th><th>{{ __('الحالة') }}</th><th>{{ __('الأولوية') }}</th><th>{{ __('مدير المشروع') }}</th><th>{{ __('الفريق') }}</th><th>{{ __('الموعد') }}</th><th>{{ __('الإنجاز') }}</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td><a class="item-title" href="/admin/operations/record/{{ $row['id'] }}">{{ $row['title'] }}</a>@if($row['type'])<small class="path">{{ $catalog->label($types[$row['type']] ?? $row['type']) }}</small>@endif</td><td>{{ $row['client'] ?? '—' }}</td><td><span class="badge">{{ $catalog->status($row['status']) }}</span></td><td>{{ $row['priority'] ? $catalog->label($priorities[$row['priority']] ?? $row['priority']) : '—' }}</td><td>{{ $row['manager'] ?? '—' }}</td><td>{{ $row['members'] }}</td><td>{{ $row['progress']['due'] ?? '—' }} @if($row['progress']['overdue'])<b class="danger">{{ __('متأخر') }}</b>@elseif($row['progress']['due_soon'])<small>{{ __('قريب') }}</small>@endif</td><td><div class="progress" aria-label="{{ $row['progress']['percent'] ?? 0 }}%"><i style="width:{{ $row['progress']['percent'] ?? 0 }}%"></i></div><small>{{ $row['progress']['percent']!==null ? $row['progress']['percent'].'%' : __('غير معلوم') }}</small></td></tr>
@empty<tr><td colspan="8"><div class="empty-state">{{ __('لا شيء هنا.') }}</div></td></tr>@endforelse</tbody></table></div>
@endsection
```
  - `operating.css`: `.progress{height:8px;border-radius:999px;background:var(--line,#e3e6ea);overflow:hidden;min-width:80px}.progress i{display:block;height:100%;background:var(--accent,#2d6cdf)}.progress-row{display:grid;gap:6px;margin-top:12px}` (استخدم متغيرات الألوان الموجودة في `operating.css` لو أسماؤها مختلفة: `grep -n "^\s*--" public/admin-assets/operating.css | head`).
  - `layout.blade.php`: `@if($access->can(auth()->user(),'projects'))<a href="/admin/operations/projects/portfolio" …>{{ __('لوحة المشاريع') }}</a>@endif`. و`listing.blade.php`: زر للوحة عند `$type==='projects'`.

- [ ] **Step 9:** الترجمات، الاختبارات → PASS، الحزمة، pint، commit: `feat(projects): team members, priority, type, completion progress and portfolio board`.

---

### Task 8: التحقق الكامل (اختبارات + مراجعة + تشغيل محلي حقيقي)

- [ ] **Step 1:** `$PHP artisan test --compact` → كل الاختبارات ناجحة (34 قديمة + الجديدة). أي فشل: استخدم skill `systematic-debugging`.
- [ ] **Step 2:** `$PHP vendor/bin/pint --format agent` ثم `git diff --stat` للتأكد أن pint لم يلمس ملفات خارج النطاق.
- [ ] **Step 3: الـconfig سليم:** `$PHP artisan tinker --execute 'echo count(config("operating.entities"));'` (يجب ألا ينهار JSON)، و`$PHP artisan route:list --path=operations` (المسارات الجديدة قبل `{type}`).
- [ ] **Step 4: مراجعة الكود:** شغّل skill `code-review` على الفرع (أو agent `pr-review-toolkit:code-reviewer`) وأصلح الملاحظات المؤكدة مع اختبار لكل إصلاح سلوكي.
- [ ] **Step 5: التشغيل المحلي على PostgreSQL:**
  1. Postgres يعمل (Task 0 Step 3). `$PHP artisan migrate:status` (لا migrations معلقة متوقعة).
  2. `$PHP artisan config:clear && $PHP artisan view:clear`.
  3. `$PHP artisan serve --port=8080` في الخلفية.
  4. حسابات الاختبار (القرار Q6، موافَق عليه بالتفويض): أنشئ في `findo_local` فقط المستخدمين `qa-owner@findo.local`، `qa-sales@findo.local`، `qa-sales2@findo.local`، `qa-salesmanager@findo.local`، `qa-delivery@findo.local`، `qa-developer@findo.local` بكلمة سر محلية واحدة (12+ حرفًا) وسجلات `employees` نشطة مرتبطة بهم، عبر `$PHP artisan tinker --execute '…'` (تحقق أولًا `config('app.env') === 'local'` و`config('database.connections.pgsql.database') === 'findo_local'`).
- [ ] **Step 6: سيناريوهات المتصفح (Playwright MCP)** مع screenshot لكل خطوة مهمة، بالعربية ثم الإنجليزية لصفحة واحدة على الأقل:
  1. sales: إنشاء lead بلا عميل → يظهر في خط البيع تحت "جديد" بالقيمة → محاولة الانتقال لـproposal ترفض برسالة واضحة → "تحويل إلى عميل" → الانتقال لـproposal ينجح.
  2. sales: إنشاء متابعة (مكالمة) لنفس الـlead بموعد بعد 30 دقيقة → `$PHP artisan operating:follow-up-reminders` → الإشعار يظهر في `/admin/operations/notifications` وعدّاد الـsidebar → إنهاء المتابعة يتطلب نتيجة.
  3. sales_manager: لوحة المتابعات بالفلتر، وخط البيع بفلتر المندوب.
  4. ملف العميل 360°: كل الأقسام، والمتبقي يظهر لـowner فقط.
  5. عرض سعر ببندين وخصم وضريبة: الإجمالي الحي في المحرر يطابق المحفوظ → اعتماده بلا نطاق (owner) → مستند PDF: A4، العربية سليمة، "حفظ كـPDF" من نافذة الطباعة → "تحويل إلى مشروع" يملأ النموذج → الحفظ → زر "فتح المشروع المحوَّل".
  6. المشروع: إضافة عضوين → نسبة الإنجاز في صفحة المشروع → لوحة المشاريع بالفلاتر → developer عضو يرى المشروع.
  7. عرض الموبايل (375px) لخط البيع ولوحة المشاريع ومحرر البنود: لا تمرير أفقي للصفحة.
  8. `list_console_messages`: لا أخطاء JS.
- [ ] **Step 7:** تحقق من استعلامات JSON على pgsql تحديدًا (المقارنات `data->x` بأرقام وبـ`true` و`like`) عبر السيناريوهات أعلاه، وراقب `storage/logs/laravel.log`.
- [ ] **Step 8:** أوقف السيرفر المحلي. commit لأي إصلاحات: `fix(sales): …`.
- [ ] **Step 9:** skill `verification-before-completion` قبل إعلان الانتهاء.

---

### Task 9: الدمج والرفع والنشر (بدون فقد أي تعديل على السيرفر)

- [ ] **Step 1:** `git checkout main && git pull --ff-only origin main` ثم `git merge --ff-only feature/erp-sales-crm-projects` (القرار Q7).
- [ ] **Step 2: اسحب أي شغل من السيرفر أولًا:** `bin/server-status` (قراءة فقط). إن ظهرت فروقات: `bin/server-pull` (يسجلها على `production` ويدمجها في `main`). حل أي تعارض يدويًا مع الحفاظ على تعديلات السيرفر، ثم أعد `$PHP artisan test --compact`.
- [ ] **Step 3:** `git push origin main` و`git push origin feature/erp-sales-crm-projects`.
- [ ] **Step 4:** تأكد أنه لا migrations في هذا الإصدار: `git diff --stat origin/production..main -- database/migrations` (فارغ متوقع، فالـrollback آمن للكود).
- [ ] **Step 5:** `bin/server-deploy --dry-run` → كل الفحوصات تمر.
- [ ] **Step 6:** `bin/server-deploy` (يرفض تلقائيًا لو السيرفر عليه تعديلات غير مسحوبة).
- [ ] **Step 7: تحقق بعد النشر:**
  - الموقع يرجّع 200 (السكربت يتحقق، وأكد بـ`curl -sI https://findogroup.com | head -1`).
  - `ssh findo "cd /home/findogroup.com/app && /usr/local/lsws/lsphp84/bin/php artisan route:list --path=operations/opportunities"` (المسارات الجديدة موجودة).
  - `ssh findo "cd /home/findogroup.com/app && /usr/local/lsws/lsphp84/bin/php artisan schedule:list"` (يظهر `operating:follow-up-reminders` كل 15 دقيقة).
  - `ssh findo "cd /home/findogroup.com/app && /usr/local/lsws/lsphp84/bin/php artisan operating:follow-up-reminders"` (تشغيل يدوي آمن: يطبع عدد التذكيرات).
- [ ] **Step 8:** `git push origin production --tags` إن لم يدفعها السكربت (تحقق بـ`git status -sb` و`git log origin/production -1`).

---

### Task 10: رسالة الجروب

- [ ] نص عادي (plain text) جاهز للنسخ، بالعربية، يذكر لكل تاسك (FIN-7..FIN-11) ما تم بلغة المستخدم النهائي (ليس أسماء ملفات)، ثم: الصلاحيات الجديدة (دور مدير المبيعات وتقييد رؤية المندوب)، وملاحظات التشغيل (التذكير كل 15 دقيقة، PDF عبر حفظ الطباعة، السجلات القديمة تطلب مصدر الـlead عند أول تعديل، الفرص القديمة بلا مندوب يراها منشئها والمدير فقط)، ومسارات الصفحات الجديدة. بلا Markdown (لا `**` ولا جداول).

---

## Self-Review (أُجري عند كتابة الخطة)

**1. تغطية المتطلبات:** كل بند في صور FIN-7..FIN-11 له صف في جدول التحليل وتاسك منفّذ (T1–T7). البنود الموجودة بالفعل (الحالات، المحطات، النطاق، الربط بالعقود) مذكورة كـ✅ بلا عمل.

**2. Placeholders:** لا TBD. ثلاثة مواضع فيها "تحقق" صريح مع الإجراء البديل (event بلا user_id، brand-logo خارج الـlayout، متغيرات CSS) لأنها تعتمد على تفاصيل لم تُقرأ سطرًا بسطر. وموضعان فيهما تعديل مُعلن قبل التنفيذ: (T4 Step 7) شكل `kind` في الـtimeline، و(T7 Step 8) استخدام `visibleData` في `portfolio`. **نفّذهما كما هو موصوف، وحدّث الـInterfaces.**

**3. اتساق الأسماء:** `OperatingSales::titles()` معرّفة في T2 ومستخدمة في T3 وT7. `limitAssignment()` في T2 ومستخدمة في T3. `OperatingQuotes::totals()/normalizeLines()/nextNumber()` في T5 و`document()/draftFrom()/convertedProject()` في T6. `allocation_percent` أُضيف لحلقة النسب في T2 ويُستخدم في T7. `SALES_SCOPED` يشمل `activities` قبل تعريفه (لا ضرر).

**4. مخاطر معروفة:**
- `where('data->x', int)` على pgsql: النمط مستخدم في الإنتاج اليوم (`assignee_id` في الحضور)، والتحقق اليدوي في Task 8 Step 7.
- تغيّر رؤية دور `sales` (Q3) قد يفاجئ المستخدمين الحاليين؛ مذكور في رسالة الجروب.
- `OperatingWorkflow::save()` يكبر؛ لا إعادة هيكلة في هذه الخطة (خارج النطاق، والملف يتبع هذا النمط).
