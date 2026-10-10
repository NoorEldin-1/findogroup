# Findo ERP — تاسكات 11 أكتوبر (FIN-12، FIN-14، FIN-15، FIN-16): السبرنتات، المحطات والمخرجات، الحملات، أداء التسويق — خطة التنفيذ

> **For agentic workers:** REQUIRED SUB-SKILL: Use `subagent-driven-development` (recommended) or `executing-plans` to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.
>
> **قبل أي شيء:**
> 1. اقرأ `docs/superpowers/plans/2026-10-06-findo-erp-00-roadmap.md`، **القسم 2 كاملًا** (القواعد الثابتة)، والقسمين 3.3 و3.5، والمرحلتين 5 و6.
> 2. اقرأ `CLAUDE.md`. مجلد `.ai/rules` غير موجود (تم التحقق في 2026-10-11).
> 3. نفّذ التاسكات **بالترتيب**. أسماء الدوال وتواقيعها موجودة في بلوك Interfaces لكل تاسك.
> 4. لو اختلف الكود عن المقتطفات (أرقام الأسطر)، ابحث بالنص المميز وطبّق نفس المعنى.
> 5. الخطة السابقة `2026-10-10-findo-erp-02-sales-crm-projects.md` نُفّذت بالكامل (commits من `907c6a0` إلى `d23ff17`)، وهذه الخطة تبني عليها.

**Goal:** تنفيذ تاسكات Jira المستحقة 2026-10-11 والمسندة لـNE (FIN-12، FIN-14، FIN-15، FIN-16) داخل محرّك التشغيل، مع اختبارات كاملة، وتشغيل محلي حقيقي، ونشر آمن.

**Architecture:** كل كيان أو حقل جديد يُضاف كـJSON في `config/operating.php`، فتعمل تلقائيًا الصلاحيات والتدقيق والقوائم والنماذج العامة. القواعد تُكتب في `OperatingWorkflow::save()` و`gate()`، والحسابات في services جديدة: `OperatingSprints` (الباكلوج والسبرنت والتقدم)، و`OperatingMilestones` (تقدم المحطات، ومتابعتها، وتنبيهاتها)، و`OperatingMarketing` (نتائج الحملة، ومقارنة القنوات، والعائد على الاستثمار). الشاشات الخاصة views إضافية فوق نفس البيانات، وفيها كنترولر جديد واحد هو `MarketingController`. **لا migrations جديدة** (جدول `notifications` موجود من المرحلة 1).

**Tech Stack:** Laravel 13.34، PHP 8.4، PHPUnit 12.5 (SQLite in-memory)، PostgreSQL 17، Blade، وJS عادي في `public/admin-assets/operating.js` (بدون Vite).

**Spec:** صور Jira لـFIN-12 وFIN-14 وFIN-15 وFIN-16 (منقولة حرفيًا في جدول التحليل أدناه)، والـroadmap في الأقسام 3.3 و3.5 والمرحلتين 5 و6.

## نص التاسكات (من صور Jira)

- **FIN-12 Manage Project Backlog & Sprints:** إنشاء وإدارة باكلوج المشروع. إنشاء سبرنتات بتاريخ بداية ونهاية. إسناد المهام للمطورين ومختبري الجودة. تتبع حالة المهام وأولوياتها وتقديراتها ومواعيدها. عرض تقدم السبرنت والمهام المتأخرة.
- **FIN-14 Manage Project Milestones & Deliverables:** تعريف محطات المشروع والمخرجات المتوقعة. تعيين مسؤول وموعد لكل محطة. تتبع حالة الإنجاز والمخرجات المتأخرة. تنبيه مديري المشاريع بالمحطات القادمة والمتأخرة.
- **FIN-15 Manage Marketing Campaigns:** إنشاء وإدارة الحملات التسويقية. تحديد هدف الحملة وقنواتها وميزانيتها وجدولها. إسناد مهام الحملة لأعضاء فريق التسويق. تتبع حالة الحملة والإنفاق والـLeads والنتائج.
- **FIN-16 Track Marketing Performance:** تتبع إنفاق الحملات والـLeads والتحويلات والإيراد. حساب العائد على الاستثمار (ROI). مقارنة أداء الحملات عبر القنوات التسويقية. تقارير أداء التسويق.
- **FIN-13 Manage Bugs & QA Testing:** **خارج هذه الخطة** (القرار D1).

## تحليل: الموجود والناقص لكل تاسك

الرموز: ✅ موجود ويكفي · 🟡 موجود جزئيًا · ❌ غير موجود.

| التاسك | البند | الحالة قبل الخطة | التاسك المنفّذ |
|---|---|---|---|
| **FIN-12** | الباكلوج | 🟡 `tasks` بحالة `backlog`، لكن لا شاشة باكلوج للمشروع ولا ترتيب حسب الأولوية | T3 |
| | السبرنتات بتاريخ بداية ونهاية | ❌ | T2 (كيان `sprints` + `tasks.sprint_id`) |
| | الإسناد للمطورين ومختبري الجودة | ✅ `assignee_id` و`reviewer_id` و`tester_id`، و❌ لا يُبلَّغ المُسنَد إليه | T3 (إشعار عند الإسناد) |
| | الحالة | ✅ backlog → ready → in_progress → code_review → qa → ready_to_release → done مع بوابات جودة | — |
| | الأولوية | 🟡 `priority` نص حر (لا يُرتَّب ولا يُفلتَر) | T1 (قائمة ثابتة + أمر ترحيل) |
| | التقديرات والمواعيد | ✅ `estimate_hours` و`remaining_hours` و`original_due` و`approved_due` | — |
| | تقدم السبرنت والمهام المتأخرة | ❌ | T3 (`OperatingSprints::progress` + لوحة السبرنت) |
| **FIN-14** | المحطات والمخرجات | 🟡 `milestones` بحقل `deliverable` نصي واحد | T4 (كيان `deliverables` لكل محطة) |
| | مسؤول وموعد لكل محطة | 🟡 الموعد موجود (`original_due`/`approved_due` مع حماية تغيير الموعد)، والمسؤول ❌ | T4 (`milestones.assignee_id` + بوابة) |
| | حالة الإنجاز والمخرجات المتأخرة | 🟡 الحالات موجودة، ولا نسبة إنجاز ولا مفهوم "مخرج متأخر" | T4 (`OperatingMilestones::progress`) + T5 (لوحة المتابعة) |
| | تنبيه مديري المشاريع (قادمة/متأخرة) | ❌ (إشارة عامة في الـoverview فقط) | T5 (أمر يومي + إشعارات) |
| **FIN-15** | إنشاء وإدارة الحملات | ✅ `campaigns` (draft → submitted → approved → running → paused → completed) مع بوابات اعتماد وسقف تفويض | — |
| | الهدف | 🟡 select بـ3 خيارات (وعي، زيارة، استفسارات) | T6 (+ تفاعل، مبيعات) |
| | القنوات | 🟡 `platform` نص حر | T6 (`channel` select) |
| | الميزانية والجدول | ✅ `daily_budget` و`budget_cap` و`start_date` و`end_date`، و❌ بلا تحقق من ترتيب التواريخ ولا عملة | T6 |
| | إسناد مهام الحملة لفريق التسويق | ❌ (`tasks` مربوطة بالمشاريع ومقصورة على فريق التطوير) | T6 (كيان `team_tasks`) |
| | تتبع الحالة والإنفاق والـLeads والنتائج | 🟡 `campaign_metrics` أرقام يدوية، و**لا علاقة** بين الحملة والـLeads في المبيعات | T6 (`opportunities.campaign_id`) + T7 (لوحة نتائج داخل صفحة الحملة) |
| **FIN-16** | الإنفاق والـLeads والتحويلات والإيراد | ❌ لا تجميع | T7 (`OperatingMarketing::stats`) |
| | ROI | ❌ | T7 |
| | المقارنة عبر القنوات | ❌ | T8 |
| | تقارير الأداء | ❌ | T8 (صفحة تقرير + CSV) |

## قرارات (محسومة: فوّض المستخدم كل القرارات لأفضل نتيجة منطقية، 2026-10-11)

| # | القرار | المحسوم والسبب |
|---|---|---|
| D1 | FIN-13 (Bugs & QA) | **خارج الخطة.** صورة الـbacklog تُظهر أن FIN-13 مسند لعضو آخر (ليس NE)، وكل الصور التفصيلية المرسلة تخص 12 و14 و15 و16. الأساس موجود أصلًا: `defects` و`test_cases` و`reviews` مع بوابات. تُذكر في رسالة الجروب كتاسك لصاحبها. |
| D2 | `tasks.priority` نص حر | يتحول إلى `select` (low/medium/high/critical) **مع أمر ترحيل** `operating:normalize-task-priorities` (يدعم `--dry-run`، ويحوّل المرادفات العربية والإنجليزية وP0..P4، وما لا يُعرف يصبح `medium` ويُحفظ الأصل في `priority_note`، مع event لكل سجل). **السبب:** لوحة السبرنت تحتاج ترتيبًا وفلترة بالأولوية، والقاعدة 6.6 في الـroadmap تمنع تغيير نوع حقل بلا ترحيل. `requirements.priority` يبقى كما هو (خارج النطاق). |
| D3 | إكمال سبرنت فيه مهام غير منجزة | المهام غير المنجزة **ترجع للباكلوج تلقائيًا** (`sprint_id = null` و`carried_from_sprint`) مع event مسبَّب لكل مهمة، ويُحفظ ملخص `completed_summary` على السبرنت. نفس سلوك Jira، ويمنع "سبرنت مغلق فيه شغل مفتوح". |
| D4 | قيود السبرنت | سبرنت **active واحد** لكل مشروع. الحالات بالترتيب planned → active → completed. التفعيل يشترط أن يكون المشروع في execution أو acceptance أو launch (لأن المهام نفسها لا تبدأ قبل ذلك). مهام Jira لا تُخطَّط في سبرنتات Findo (للقراءة فقط، ولها سبرنتاتها في Jira). |
| D5 | المخرجات (Deliverables) | كيان مستقل `deliverables` تحت المحطة (مسؤول، موعد، رابط/دليل تسليم). `project_id` يُملأ تلقائيًا من المحطة. موعد المخرج لا يتجاوز موعد المحطة. تقديم المحطة (submitted) يشترط تسليم كل مخرجاتها. القبول (accepted) يأتي بعد التقديم فقط. |
| D6 | تنبيهات المحطات | أمر يومي 08:00 `operating:milestone-reminders`. "قادمة" = خلال 3 أيام، و"متأخرة" = بعد الموعد وهي ما زالت planned أو in_progress (المحطة المقدَّمة تنتظر العميل، فلا تُعتبر متأخرة على الفريق). المستلمون: مدير المشروع (`projects.manager_id`) ومسؤول المحطة، ولو المشروع بلا مدير فكل مستخدمي دور `delivery`. التنبيه مرة واحدة لكل موعد (marker = تاريخ الموعد، فلو تغيّر الموعد بطلب تغيير معتمد يُنبَّه من جديد). والمتأخر يُرسل بالبريد أيضًا. |
| D7 | مهام فريق التسويق | كيان `team_tasks` (group `marketing`، `assigned`). الإنشاء لـowner وmarketing وmedia_buyer. الإسناد لأي شخص لـowner وmarketing فقط، وغيرهما لنفسه. المصمم والمونتير يريان مهامهما فقط، ويعدّلان فيها رابط التسليم والملاحظات فقط، وينقلانها حتى "مراجعة". "تم" لـowner أو marketing أو منشئ المهمة. |
| D8 | إشعار الإسناد | عند إسناد مهمة تطوير (منفّذ أو مراجع أو مختبر) أو مهمة تسويق لشخص جديد، يصله إشعار داخل النظام، ولا يُبلَّغ الشخص بإسناد قام به لنفسه. |
| D9 | مصدر الـLeads والإيراد للحملة | الـLeads = فرص المبيعات المربوطة بالحملة (`opportunities.campaign_id`)، وهي المصدر الحقيقي، وتُعرض الأرقام اليدوية من `campaign_metrics` بجانبها للمقارنة. التحويل = فرص won. الإيراد = Σ `expected_value` للفرص won **بنفس عملة الحملة** (وما بعملة أخرى يُعدّ ويُذكر ولا يُجمع). ROI = (الإيراد − الإنفاق) ÷ الإنفاق × 100، و`null` عند إنفاق صفر. CPL = الإنفاق ÷ الـLeads. |
| D10 | الإنفاق | لو للحملة سجلات `campaign_metrics` يكون الإنفاق = مجموعها. ولو لا يوجد، يُستخدم `campaigns.spent`. **السبب:** تعديل حملة جارية يعيدها لمسودة (قاعدة اعتماد قائمة)، فالإنفاق اليومي يُسجَّل كـmetrics لا كتعديل للحملة. |
| D11 | رؤية الأرقام المجمّعة | نتائج الحملة وتقرير الأداء يجمعان **كل** الفرص المربوطة، حتى ما لا يستطيع المشاهد فتحه، كأرقام مجمّعة فقط بلا تفاصيل ولا روابط (نفس سابقة `OperatingProjects::progress`). **السبب:** دور marketing لا يقرأ قسم المبيعات، ومن غير ذلك يكون الـROI بلا معنى. |
| D12 | الفترة في التقرير | الافتراضي من أول الشهر الحالي حتى اليوم. الـmetrics تُفلتر بتاريخها، والـLeads بتاريخ إنشائها (cohort)، والتحويلات = ما فاز من leads نفس الفترة. |
| D13 | القناة | `channel` select جديد (اختياري للحفظ، وإلزامي للاعتماد والتشغيل)، و`platform` يصبح اختياريًا بعنوان "تفاصيل المنصة / الحساب". لا ترحيل مطلوب (اختياري ← اختياري). |
| D14 | ملء النماذج من السياق | `?prefill[field]=id` عام لحقول العلاقة في أي نموذج إنشاء (مع تحقق القراءة والنوع)، ويُستخدم في: مهمة/سبرنت من صفحة المشروع، ومخرج من المحطة، ومهمة/نتائج من الحملة. |
| D15 | حسابات الاختبار المحلي | في `findo_local` **فقط**، عبر tinker مع تحقق `app.env=local` واسم القاعدة. يُعاد استخدام `qa-*@findo.local` من الخطة السابقة، وتُضاف `qa-techlead` و`qa-qa` و`qa-marketing` و`qa-mediabuyer` و`qa-designer`. لا شيء يُنشأ على السيرفر. |
| D16 | الدمج | `--ff-only` إلى `main`، مثل الخطتين السابقتين. |

## Global Constraints
- لا dependencies جديدة (composer/npm) ولا migrations جديدة.
- PHP المحلي: **`/c/dev-tools/php84/php.exe`** (الـ`php` الموجود في PATH هو 8.2 ويفشل). في الأوامر: `PHP=/c/dev-tools/php84/php.exe` ثم `$PHP artisan …`.
- كل مسار جديد يبدأ بـ`/admin/operations/` ويُسجَّل **قبل** `Route::get('/operations/{type}/create', …)` في `routes/admin.php`.
- لا حذف فعلي. كل تعديل يكتب `operating_events` بسبب. رسائل الأخطاء `$this->fail($ar, $en)` داخل `OperatingWorkflow`، و`$this->workflow->fail(...)` خارجه.
- النصوص العربية مفاتيح `__()`، وترجمتها الإنجليزية تُضاف إلى `lang/en.json` في نفس التاسك.
- التواريخ بصيغة `Y-m-d` (المقارنة النصية صحيحة).
- العملة الافتراضية عند الغياب `USD`. التجميع لكل عملة على حدة، بلا تحويل.
- الاستعلام على أبناء سجل يكون عبر `operating_links` (`field` + `target_id`).
- بعد أي تعديل PHP: `$PHP vendor/bin/pint --dirty --format agent`.
- الاختبارات: `$PHP artisan test --compact <path>`. خط الأساس قبل البدء: **74 اختبارًا ناجحًا** (تم التحقق في 2026-10-11 على `d23ff17`).
- Commit لكل تاسك على الفرع `feature/erp-delivery-marketing`، والرسالة تنتهي بـ:
  `Co-Authored-By: Claude Opus 5.5 (1M context) <noreply@anthropic.com>`

---

## File Structure

| الملف | جديد/تعديل | المسؤولية |
|---|---|---|
| `config/operating.php` | تعديل | `tasks.priority` select، و`tasks.sprint_id`، وكيانات `sprints` و`deliverables` و`team_tasks`، و`milestones.assignee_id`، وحقول `campaigns`، و`opportunities.campaign_id` |
| `app/Services/OperatingAccess.php` | تعديل | `canCreate()`، ونطاق رؤية المطور/QA للسبرنتات والمخرجات، وصلاحيات المصمم/المونتير على `team_tasks` |
| `app/Services/OperatingWorkflow.php` | تعديل | قواعد الحفظ والانتقال للكيانات الجديدة، و`mark()` (منقول من FollowUps)، و`notifyAssignees()`، وتعميم `limitAssignment()` |
| `app/Services/OperatingSprints.php` | جديد | تقدم السبرنت، ولوحة الباكلوج/السبرنت، والتخطيط، وإرجاع غير المنجز |
| `app/Services/OperatingMilestones.php` | جديد | تقدم المحطة، ولوحة المتابعة، والتنبيهات |
| `app/Services/OperatingMarketing.php` | جديد | إحصاءات الحملة، وتقرير الأداء والقنوات والمصادر |
| `app/Services/OperatingFollowUps.php` | تعديل | يستخدم `OperatingWorkflow::mark()` |
| `app/Console/Commands/NormalizeTaskPriorities.php` | جديد | `operating:normalize-task-priorities` |
| `app/Console/Commands/SendMilestoneReminders.php` | جديد | `operating:milestone-reminders` |
| `app/Http/Controllers/ProjectController.php` | تعديل | لوحة السبرنت، والتخطيط، ولوحة المحطات |
| `app/Http/Controllers/MarketingController.php` | جديد | تقرير الأداء + CSV |
| `app/Http/Controllers/OperatingController.php` | تعديل | `prefill`، و`canCreate`، ولوحات السجل (سبرنت/محطة/حملة) |
| `routes/admin.php`، `routes/console.php` | تعديل | المسارات والجدولة |
| `resources/views/admin/operating/project-sprints.blade.php` | جديد | الباكلوج والسبرنتات |
| `resources/views/admin/operating/sprint-progress.blade.php` | جديد | partial تقدم السبرنت |
| `resources/views/admin/operating/task-card.blade.php` | جديد | partial بطاقة مهمة |
| `resources/views/admin/operating/milestones-tracker.blade.php` | جديد | متابعة المحطات والمخرجات |
| `resources/views/admin/operating/campaign-report.blade.php` | جديد | partial نتائج الحملة ومهامها |
| `resources/views/admin/operating/marketing-performance.blade.php` | جديد | تقرير أداء التسويق |
| `resources/views/admin/operating/form.blade.php`، `listing.blade.php`، `projects-portfolio.blade.php`، `admin/layout.blade.php` | تعديل | أزرار ولوحات وروابط |
| `lang/en.json` | تعديل | الترجمات |
| `tests/Feature/Operating/TaskPriorityTest.php` | جديد | T1 |
| `tests/Feature/Operating/SprintsTest.php` | جديد | T2 |
| `tests/Feature/Operating/SprintBoardTest.php` | جديد | T3 |
| `tests/Feature/Operating/MilestonesTest.php` | جديد | T4 |
| `tests/Feature/Operating/MilestoneTrackerTest.php` | جديد | T5 |
| `tests/Feature/Operating/CampaignsTest.php` | جديد | T6 |
| `tests/Feature/Operating/CampaignStatsTest.php` | جديد | T7 |
| `tests/Feature/Operating/MarketingPerformanceTest.php` | جديد | T8 |

---

### Task 0: التجهيز

- [ ] **Step 1:** `git status` نظيف، وعلى `main`، و`git fetch && git status -sb` يطابق `origin/main`. ثم `git checkout -b feature/erp-delivery-marketing`.
- [ ] **Step 2:** خط الأساس: `$PHP artisan test --compact` → 74 passed.
- [ ] **Step 3:** PostgreSQL المحلي: `/c/dev-tools/pgsql17/bin/pg_isready -h 127.0.0.1 -p 5432`. لو لم يستجب: تأكد من وجود `/c/dev-tools/pgdata17/PG_VERSION`، ثم `/c/dev-tools/pgsql17/bin/pg_ctl -D /c/dev-tools/pgdata17 -l /c/dev-tools/pgdata17/server.log start`.
- [ ] **Step 4:** راجع مواضع الأدوار الثابتة التي ستُلمس: `grep -n "'designer'\|'video_editor'\|'tasks'" app/Services/OperatingAccess.php app/Services/OperatingWorkflow.php`.

---

### Task 1: أولوية المهام قائمة ثابتة + أمر ترحيل (FIN-12، القرار D2)

**Files:**
- Modify: `config/operating.php` (`entities.tasks.fields` → `priority`)
- Create: `app/Console/Commands/NormalizeTaskPriorities.php`
- Modify: `lang/en.json`
- Test: `tests/Feature/Operating/TaskPriorityTest.php`

**Interfaces:**
- Produces: `tasks.priority` ∈ `low|medium|high|critical|null`. أمر `operating:normalize-task-priorities {--dry-run}` يطبع سطرًا لكل مهمة ثم `"<n> tasks normalized."` (أو `"<n> tasks normalized (dry run)."`). مفتاح محسوب `priority_note` يحفظ القيمة الأصلية غير المعروفة.

- [ ] **Step 1: الاختبار الفاشل** (`php artisan make:test --phpunit Operating/TaskPriorityTest` ثم استبدل المحتوى)

```php
<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class TaskPriorityTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    private function project(): int
    {
        return $this->operatingRecord('projects', 'execution', ['outcome' => 'Portal', 'original_due' => '2026-12-01', 'client_contact' => 'Mona'], 'Portal');
    }

    public function test_task_priority_accepts_only_the_fixed_list(): void
    {
        $lead = $this->operatingUser('tech_lead');
        $task = ['title' => 'Login', 'reason' => 'Plan', 'data' => ['project_id' => $this->project(), 'priority' => 'urgent']];

        $this->actingAs($lead)->post('/admin/operations/tasks', $task)->assertSessionHasErrors('data.priority');
        $task['data']['priority'] = 'high';
        $this->actingAs($lead)->post('/admin/operations/tasks', $task)->assertSessionHasNoErrors();
    }

    public function test_legacy_priorities_are_normalized_once_with_an_audit_trail(): void
    {
        $project = $this->project();
        $high = $this->operatingRecord('tasks', 'backlog', ['project_id' => $project, 'priority' => ' High '], 'A');
        $urgent = $this->operatingRecord('tasks', 'done', ['project_id' => $project, 'priority' => 'عاجل'], 'B');
        $unknown = $this->operatingRecord('tasks', 'ready', ['project_id' => $project, 'priority' => 'ASAP-ish'], 'C');
        $empty = $this->operatingRecord('tasks', 'backlog', ['project_id' => $project, 'priority' => null], 'D');
        $valid = $this->operatingRecord('tasks', 'backlog', ['project_id' => $project, 'priority' => 'low'], 'E');

        $this->artisan('operating:normalize-task-priorities --dry-run')->expectsOutputToContain('3 tasks normalized (dry run).')->assertSuccessful();
        $this->assertSame(' High ', $this->recordData($high)['priority']);

        $this->artisan('operating:normalize-task-priorities')->expectsOutputToContain('3 tasks normalized.')->assertSuccessful();
        $this->assertSame('high', $this->recordData($high)['priority']);
        $this->assertSame('critical', $this->recordData($urgent)['priority']);
        $this->assertSame('medium', $this->recordData($unknown)['priority']);
        $this->assertSame('ASAP-ish', $this->recordData($unknown)['priority_note']);
        $this->assertNull($this->recordData($empty)['priority']);
        $this->assertSame(1, DB::table('operating_records')->where('id', $valid)->value('version'));
        $this->assertSame(2, DB::table('operating_records')->where('id', $high)->value('version'));
        $this->assertTrue(DB::table('operating_events')->where('record_id', $high)->where('action', 'update')->exists());

        $this->artisan('operating:normalize-task-priorities')->expectsOutputToContain('0 tasks normalized.')->assertSuccessful();
    }
}
```

- [ ] **Step 2:** `$PHP artisan test --compact tests/Feature/Operating/TaskPriorityTest.php` → FAIL (الأمر غير موجود، والأولوية نص).

- [ ] **Step 3: الـconfig.** في `entities.tasks.fields` استبدل حقل `priority` (`"type": "text"`) بـ:

```json
        {
          "key": "priority",
          "label": { "ar": "الأولوية", "en": "Priority" },
          "type": "select",
          "required": false,
          "options": {
            "low": { "ar": "منخفضة", "en": "Low" },
            "medium": { "ar": "متوسطة", "en": "Medium" },
            "high": { "ar": "عالية", "en": "High" },
            "critical": { "ar": "حرجة", "en": "Critical" }
          }
        },
```

- [ ] **Step 4: الأمر** (`$PHP artisan make:command NormalizeTaskPriorities --no-interaction` ثم استبدل المحتوى)

```php
<?php

namespace App\Console\Commands;

use App\Services\OperatingCatalog;
use App\Services\OperatingWorkflow;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizeTaskPriorities extends Command
{
    protected $signature = 'operating:normalize-task-priorities {--dry-run : List the changes without writing them}';

    protected $description = 'Map legacy free-text task priorities to the fixed low/medium/high/critical list';

    /**
     * Known spellings, lower-cased and trimmed. Anything else becomes medium and keeps its original text in priority_note.
     */
    private const MAP = [
        'critical' => 'critical', 'urgent' => 'critical', 'blocker' => 'critical', 'highest' => 'critical', 'p0' => 'critical', 'حرجة' => 'critical', 'حرج' => 'critical', 'عاجل' => 'critical', 'عاجلة' => 'critical',
        'high' => 'high', 'p1' => 'high', 'عالية' => 'high', 'عالي' => 'high', 'مرتفعة' => 'high',
        'medium' => 'medium', 'normal' => 'medium', 'p2' => 'medium', 'متوسطة' => 'medium', 'متوسط' => 'medium', 'عادية' => 'medium',
        'low' => 'low', 'lowest' => 'low', 'minor' => 'low', 'p3' => 'low', 'p4' => 'low', 'منخفضة' => 'low', 'منخفض' => 'low',
    ];

    public function handle(OperatingCatalog $catalog, OperatingWorkflow $workflow): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $count = DB::transaction(function () use ($catalog, $workflow, $dryRun): int {
            if (DB::getDriverName() === 'pgsql') {
                DB::select("SELECT pg_advisory_xact_lock(hashtext('findo-operating-gates'))");
            }
            $count = 0;
            foreach (DB::table('operating_records')->where('type', 'tasks')->where('source', 'findo')->lockForUpdate()->orderBy('id')->get() as $task) {
                $data = $catalog->data($task);
                $value = $data['priority'] ?? null;
                if ($value === null || $value === '' || in_array($value, ['low', 'medium', 'high', 'critical'], true)) {
                    continue;
                }
                $known = self::MAP[mb_strtolower(trim((string) $value))] ?? null;
                $after = array_merge($data, ['priority' => $known ?? 'medium'], $known ? [] : ['priority_note' => (string) $value]);
                $count++;
                $this->line(sprintf('#%d %s: "%s" → %s', $task->id, $task->title, $value, $after['priority']));
                if ($dryRun) {
                    continue;
                }
                DB::table('operating_records')->where('id', $task->id)->update(['data' => json_encode($after, JSON_UNESCAPED_UNICODE), 'version' => $task->version + 1, 'updated_at' => now()]);
                $workflow->event(DB::table('operating_records')->find($task->id), 'update', 'Normalize legacy priority to the fixed list', ['priority' => $value], ['priority' => $after['priority']]);
            }

            return $count;
        });
        $this->info(sprintf('%d tasks normalized%s.', $count, $dryRun ? ' (dry run)' : ''));

        return self::SUCCESS;
    }
}
```

> المهام `done` (immutable) تُرحَّل أيضًا لأن هذا ترحيل نظامي موثّق بـevent، وليس تعديل مستخدم.

- [ ] **Step 5:** الاختبار → PASS، ثم الحزمة كاملة (لا تراجع)، ثم pint، ثم commit: `feat(projects): fixed task priority list with a legacy normalization command`.

---

### Task 2: السبرنتات وقواعدها (FIN-12، القراران D3 وD4)

**Files:**
- Modify: `config/operating.php` (كيان `sprints` بعد `project_members`، و`tasks.sprint_id` بعد `requirement_id`)
- Modify: `app/Services/OperatingWorkflow.php`
- Modify: `app/Services/OperatingAccess.php`
- Create: `app/Services/OperatingSprints.php` (الجزء الأول: `tasks()` و`releaseUnfinished()`)
- Modify: `lang/en.json`
- Test: `tests/Feature/Operating/SprintsTest.php`

**Interfaces:**
- Produces:
  - كيان `sprints`: الحقول `project_id*, goal*, start_date*, end_date*, capacity_hours`، والحالات `planned, active, completed, archived`، و`immutable: ["completed","archived"]`. ومفتاح محسوب `completed_summary: array{done: int, carried: int}`.
  - `tasks.sprint_id` (relation sprints). ومفتاح محسوب على المهمة: `carried_from_sprint: int`.
  - `OperatingSprints::tasks(object $sprint): Collection` (كل مهام السبرنت غير المؤرشفة).
  - `OperatingSprints::releaseUnfinished(object $sprint, User $user, string $reason): array{done: int, carried: int}`.
  - `OperatingAccess::PROJECT_SCOPED = ['project_members', 'sprints', 'deliverables']` (المطور وQA يرونها لمشاريعهم فقط).

- [ ] **Step 1: الاختبار الفاشل**

```php
<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class SprintsTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    private function project(string $status = 'execution'): int
    {
        return $this->operatingRecord('projects', $status, ['outcome' => 'Portal', 'original_due' => '2026-12-01', 'client_contact' => 'Mona'], 'Portal');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function sprint(int $project, string $status = 'planned', string $title = 'Sprint 1', array $data = []): int
    {
        return $this->operatingRecord('sprints', $status, array_merge(['project_id' => $project, 'goal' => 'Login flow', 'start_date' => '2026-10-11', 'end_date' => '2026-10-24'], $data), $title);
    }

    public function test_sprint_dates_must_be_in_order(): void
    {
        $delivery = $this->operatingUser('delivery');
        $sprint = ['title' => 'Sprint 1', 'reason' => 'Plan', 'data' => ['project_id' => $this->project(), 'goal' => 'Login', 'start_date' => '2026-10-24', 'end_date' => '2026-10-11']];

        $this->actingAs($delivery)->post('/admin/operations/sprints', $sprint)->assertSessionHasErrors('workflow');
        $sprint['data']['end_date'] = '2026-10-30';
        $this->actingAs($delivery)->post('/admin/operations/sprints', $sprint)->assertSessionHasNoErrors();
    }

    public function test_one_active_sprint_per_executing_project_and_stages_in_order(): void
    {
        $owner = $this->operatingUser('owner');
        $project = $this->project();
        $this->sprint($project, 'active');
        $second = $this->sprint($project, 'planned', 'Sprint 2');

        $this->actingAs($owner)->post("/admin/operations/record/{$second}/transition", ['target' => 'active', 'version' => 1, 'reason' => 'Start'])->assertSessionHasErrors('workflow');
        $this->actingAs($owner)->post("/admin/operations/record/{$second}/transition", ['target' => 'completed', 'version' => 1, 'reason' => 'Skip'])->assertSessionHasErrors('workflow');

        $planning = $this->sprint($this->project('planning'));
        $this->actingAs($owner)->post("/admin/operations/record/{$planning}/transition", ['target' => 'active', 'version' => 1, 'reason' => 'Start'])->assertSessionHasErrors('workflow');

        $free = $this->sprint($this->project());
        $this->actingAs($owner)->post("/admin/operations/record/{$free}/transition", ['target' => 'active', 'version' => 1, 'reason' => 'Start'])->assertSessionHasNoErrors();
    }

    public function test_completing_a_sprint_returns_unfinished_tasks_to_the_backlog(): void
    {
        $owner = $this->operatingUser('owner');
        $project = $this->project();
        $sprint = $this->sprint($project, 'active');
        $done = $this->operatingRecord('tasks', 'done', ['project_id' => $project, 'sprint_id' => $sprint], 'Done task');
        $open = $this->operatingRecord('tasks', 'in_progress', ['project_id' => $project, 'sprint_id' => $sprint], 'Open task');

        $this->actingAs($owner)->post("/admin/operations/record/{$sprint}/transition", ['target' => 'completed', 'version' => 1, 'reason' => 'Sprint review'])->assertSessionHasNoErrors();

        $this->assertNull($this->recordData($open)['sprint_id']);
        $this->assertSame($sprint, $this->recordData($open)['carried_from_sprint']);
        $this->assertSame('in_progress', DB::table('operating_records')->where('id', $open)->value('status'));
        $this->assertFalse(DB::table('operating_links')->where('record_id', $open)->where('field', 'sprint_id')->exists());
        $this->assertTrue(DB::table('operating_events')->where('record_id', $open)->where('action', 'update')->exists());
        $this->assertSame($sprint, $this->recordData($done)['sprint_id']);
        $this->assertSame(['done' => 1, 'carried' => 1], $this->recordData($sprint)['completed_summary']);
    }

    public function test_tasks_join_only_open_sprints_of_their_own_project(): void
    {
        $lead = $this->operatingUser('tech_lead');
        $project = $this->project();
        $task = fn (int $sprint): array => ['title' => 'Login', 'reason' => 'Plan', 'data' => ['project_id' => $project, 'sprint_id' => $sprint]];

        $this->actingAs($lead)->post('/admin/operations/tasks', $task($this->sprint($this->project())))->assertSessionHasErrors('workflow');
        $this->actingAs($lead)->post('/admin/operations/tasks', $task($this->sprint($project, 'completed')))->assertSessionHasErrors('workflow');
        $this->actingAs($lead)->post('/admin/operations/tasks', $task($this->sprint($project)))->assertSessionHasNoErrors();
    }

    public function test_developers_see_only_sprints_of_their_projects(): void
    {
        $developer = $this->operatingUser('developer');
        $employee = $this->employeeFor($developer);
        $mine = $this->project();
        $this->operatingRecord('project_members', 'active', ['project_id' => $mine, 'assignee_id' => $employee, 'member_role' => 'developer'], 'Member');
        $this->sprint($mine, 'planned', 'Mine');
        $this->sprint($this->project(), 'planned', 'Other');

        $titles = collect($this->actingAs($developer)->getJson('/admin/operations/sprints?format=json')->json('data'))->pluck('title')->all();
        $this->assertSame(['Mine'], $titles);
    }
}
```

- [ ] **Step 2:** → FAIL.

- [ ] **Step 3: الـconfig**
  - كيان جديد بعد `project_members`:

```json
    "sprints": {
      "label": { "ar": "السبرنتات", "en": "Sprints" },
      "group": "delivery",
      "pages": [],
      "fields": [
        { "key": "project_id", "label": { "ar": "المشروع", "en": "Project" }, "type": "relation", "required": true, "entity": "projects" },
        { "key": "goal", "label": { "ar": "هدف السبرنت", "en": "Sprint goal" }, "type": "textarea", "required": true },
        { "key": "start_date", "label": { "ar": "البداية", "en": "Start date" }, "type": "date", "required": true },
        { "key": "end_date", "label": { "ar": "النهاية", "en": "End date" }, "type": "date", "required": true },
        { "key": "capacity_hours", "label": { "ar": "السعة المتاحة بالساعات", "en": "Capacity (hours)" }, "type": "number", "required": false }
      ],
      "statuses": ["planned", "active", "completed", "archived"],
      "immutable": ["completed", "archived"]
    },
```

  - في `tasks.fields` بعد `requirement_id`:

```json
        { "key": "sprint_id", "label": { "ar": "السبرنت", "en": "Sprint" }, "type": "relation", "required": false, "entity": "sprints" },
```

  (تحقق: الحالات `planned/active/completed` لها تسميات في `statuses`. نعم، وتم التحقق.)

- [ ] **Step 4: قواعد `save()`** (بعد كتلة `project_members`):

```php
        if ($type === 'sprints' && $data['end_date'] < $data['start_date']) {
            $this->fail('نهاية السبرنت قبل بدايته.', 'The sprint ends before it starts.');
        }
        if ($type === 'tasks' && ! empty($data['sprint_id']) && (int) $data['sprint_id'] !== (int) (($existing ? $this->catalog->data($existing) : [])['sprint_id'] ?? 0) && DB::table('operating_records')->where('id', $data['sprint_id'])->value('status') === 'completed') {
            $this->fail('لا تُضاف مهام إلى سبرنت مكتمل.', 'Tasks cannot join a completed sprint.');
        }
```

  > فحص "سجل من مشروع مختلف" العام الموجود يغطي `sprint_id` تلقائيًا، لأن السبرنت له `project_id`.

- [ ] **Step 5: `gate()`** (قبل كتلة `quotes`):

```php
        if ($record->type === 'sprints') {
            $stages = ['planned', 'active', 'completed'];
            if (array_search($target, $stages, true) !== array_search($record->status, $stages, true) + 1) {
                $this->fail('انتقل عبر مراحل السبرنت بالترتيب.', 'Follow sprint stages in order.');
            }
            if ($target === 'active') {
                if (! in_array(DB::table('operating_records')->where('id', $data['project_id'])->value('status'), ['execution', 'acceptance', 'launch'], true)) {
                    $this->fail('تفعيل السبرنت يحتاج مشروعًا في مرحلة التنفيذ.', 'Start a sprint once the project is in execution.');
                }
                if (DB::table('operating_records')->where('type', 'sprints')->where('status', 'active')->where('data->project_id', (int) $data['project_id'])->where('id', '!=', $record->id)->exists()) {
                    $this->fail('للمشروع سبرنت جارٍ بالفعل. أكمله أولًا.', 'This project already has an active sprint. Complete it first.');
                }
            }
        }
```

- [ ] **Step 6: `transition()`** بعد كتلة `activities`:

```php
            if ($record->type === 'sprints' && $target === 'completed') {
                $changes['data'] = json_encode(array_merge($data, ['completed_summary' => app(OperatingSprints::class)->releaseUnfinished($record, $user, $input['reason'])]), JSON_UNESCAPED_UNICODE);
            }
```

- [ ] **Step 7: `OperatingSprints`** (الجزء الأول)

```php
<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OperatingSprints
{
    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access, private OperatingWorkflow $workflow, private OperatingSales $sales) {}

    /**
     * Every non-archived task planned in the sprint, regardless of who is viewing (progress is an aggregate).
     *
     * @return Collection<int, object>
     */
    public function tasks(object $sprint): Collection
    {
        return DB::table('operating_records')->where('type', 'tasks')->where('status', '!=', 'archived')
            ->whereIn('id', DB::table('operating_links')->where('field', 'sprint_id')->where('target_id', $sprint->id)->select('record_id'))
            ->orderBy('id')->get();
    }

    /**
     * Runs inside the sprint transition transaction: unfinished work goes back to the backlog with an audited reason, done work stays in the sprint history.
     *
     * @return array{done: int, carried: int}
     */
    public function releaseUnfinished(object $sprint, User $user, string $reason): array
    {
        $summary = ['done' => 0, 'carried' => 0];
        foreach ($this->tasks($sprint) as $task) {
            if ($task->status === 'done') {
                $summary['done']++;

                continue;
            }
            $summary['carried']++;
            $data = array_merge($this->catalog->data($task), ['sprint_id' => null, 'carried_from_sprint' => $sprint->id]);
            DB::table('operating_records')->where('id', $task->id)->update(['data' => json_encode($data, JSON_UNESCAPED_UNICODE), 'version' => $task->version + 1, 'updated_at' => now()]);
            DB::table('operating_links')->where('record_id', $task->id)->where('field', 'sprint_id')->delete();
            $this->workflow->event(DB::table('operating_records')->find($task->id), 'update', 'Returned to backlog when sprint "'.$sprint->title.'" completed: '.$reason, ['sprint_id' => $sprint->id], ['sprint_id' => null], $user->id);
        }

        return $summary;
    }
}
```

  > `OperatingSprints` يعتمد على `OperatingWorkflow`، والـWorkflow يستدعيه بـ`app()` عند الحاجة فقط (نفس نمط `OperatingQuotes`)، فلا حلقة في الـcontainer.

- [ ] **Step 8: `OperatingAccess`**
  - أضف الثابت:

```php
    /**
     * Delivery child records a developer or QA sees only for projects they can already see.
     */
    public const PROJECT_SCOPED = ['project_members', 'sprints', 'deliverables'];
```

  - في `query()` استبدل الشرط `in_array($user->role, ['developer', 'qa'], true) && $type === 'project_members'` بـ`in_array($user->role, ['developer', 'qa'], true) && in_array($type, self::PROJECT_SCOPED, true)` (باقي الكتلة كما هي: روابط `project_id` إلى المشاريع المرئية). `deliverables` سيُعرَّف في T4، ووجوده في الثابت قبل ذلك لا يضر.

- [ ] **Step 9:** الترجمات: `"نهاية السبرنت قبل بدايته."`، `"لا تُضاف مهام إلى سبرنت مكتمل."`، `"انتقل عبر مراحل السبرنت بالترتيب."`، `"تفعيل السبرنت يحتاج مشروعًا في مرحلة التنفيذ."`، `"للمشروع سبرنت جارٍ بالفعل. أكمله أولًا."` (رسائل `fail` تأخذ النصين مباشرة، فهذه **لا** تحتاج en.json. أضف فقط نصوص الـviews في T3). لا ترجمات في هذا التاسك.
- [ ] **Step 10:** الاختبارات → PASS، ثم الحزمة، ثم pint، ثم commit: `feat(projects): sprints with ordered stages, one active per project and backlog carry-over`.

---

### Task 3: لوحة الباكلوج والسبرنت + التخطيط + إشعار الإسناد (FIN-12)

**Files:**
- Modify: `app/Services/OperatingSprints.php` (`progress`، `board`، `plan`)
- Modify: `app/Services/OperatingWorkflow.php` (`notifyAssignees` + حقن `OperatingNotifier`)
- Modify: `app/Services/OperatingAccess.php` (`canCreate`)
- Modify: `app/Http/Controllers/ProjectController.php` (`sprints`، `planSprint`)
- Modify: `app/Http/Controllers/OperatingController.php` (`prefill`، و`canCreate`، و`sprintProgress`)
- Create: `resources/views/admin/operating/project-sprints.blade.php`، `sprint-progress.blade.php`، `task-card.blade.php`
- Modify: `routes/admin.php`، `form.blade.php`، `listing.blade.php`، `projects-portfolio.blade.php`، `lang/en.json`
- Test: `tests/Feature/Operating/SprintBoardTest.php`

**Interfaces:**
- Consumes: `OperatingSprints::tasks()` (T2)، و`OperatingSales::titles()` و`saveAs()`، و`OperatingProjects::DONE_JIRA_STATUSES`.
- Produces:
  - `OperatingSprints::PRIORITY_RANK = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3]`.
  - `OperatingSprints::BOARD_COLUMNS = ['backlog', 'ready', 'in_progress', 'code_review', 'qa', 'ready_to_release', 'done']`.
  - `OperatingSprints::progress(object $sprint): array{percent: ?int, tasks: array{done: int, total: int}, hours: array{estimate: float, remaining: float}, days: array{total: int, elapsed: int, left: int}, time_percent: int, overdue_tasks: int, late: bool, at_risk: bool}`.
  - `OperatingSprints::board(User $user, object $project): array{active: ?array{sprint: object, progress: array, columns: array<string, list<array>>}, planned: list<array{sprint: object, progress: array, cards: list<array>}>, completed: list<array{sprint: object, summary: ?array}>, backlog: list<array>, overdue: list<array>, in_sprints: list<array>, targets: list<object>}`.
  - الـcard: `array{id: int, title: string, status: string, source: string, done: bool, sprint_id: ?int, carried: bool, priority: ?string, assignee: ?string, tester: ?string, estimate: ?float, remaining: ?float, due: ?string, overdue: bool}`.
  - `OperatingSprints::plan(User $user, object $project, ?int $sprintId, list<int> $taskIds, string $reason): int` (عدد المهام المنقولة، و`null` = إرجاع للباكلوج).
  - `OperatingAccess::canCreate(User $user, string $type): bool`.

- [ ] **Step 1: الاختبار الفاشل**

```php
<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingSprints;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class SprintBoardTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    private int $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-15 10:00'));
        $this->project = $this->operatingRecord('projects', 'execution', ['outcome' => 'Portal', 'original_due' => '2026-12-01', 'client_contact' => 'Mona'], 'Portal');
    }

    private function sprint(string $status = 'active'): int
    {
        return $this->operatingRecord('sprints', $status, ['project_id' => $this->project, 'goal' => 'Login', 'start_date' => '2026-10-11', 'end_date' => '2026-10-20'], 'Sprint 1');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function task(string $status, string $title, array $data = []): int
    {
        return $this->operatingRecord('tasks', $status, array_merge(['project_id' => $this->project], $data), $title);
    }

    public function test_sprint_progress_counts_tasks_hours_time_and_overdue_work(): void
    {
        $sprint = $this->sprint();
        $this->task('done', 'Done', ['sprint_id' => $sprint, 'estimate_hours' => 4]);
        $this->task('in_progress', 'Doing', ['sprint_id' => $sprint, 'estimate_hours' => 6, 'remaining_hours' => 2]);
        $this->task('ready', 'Late', ['sprint_id' => $sprint, 'estimate_hours' => 5, 'original_due' => '2026-10-12']);

        $progress = app(OperatingSprints::class)->progress(DB::table('operating_records')->find($sprint));

        $this->assertSame(33, $progress['percent']);
        $this->assertSame(['done' => 1, 'total' => 3], $progress['tasks']);
        $this->assertSame(['estimate' => 15.0, 'remaining' => 7.0], $progress['hours']);
        $this->assertSame(['total' => 10, 'elapsed' => 5, 'left' => 5], $progress['days']);
        $this->assertSame(50, $progress['time_percent']);
        $this->assertSame(1, $progress['overdue_tasks']);
        $this->assertFalse($progress['late']);
    }

    public function test_board_separates_backlog_sprint_columns_and_overdue_tasks(): void
    {
        $sprint = $this->sprint();
        $this->task('in_progress', 'In sprint', ['sprint_id' => $sprint]);
        $this->task('backlog', 'Low item', ['priority' => 'low']);
        $this->task('backlog', 'Critical item', ['priority' => 'critical', 'original_due' => '2026-10-01']);
        $this->task('done', 'Finished');

        $board = app(OperatingSprints::class)->board($this->operatingUser('owner'), DB::table('operating_records')->find($this->project));

        $this->assertSame(['Critical item', 'Low item'], array_column($board['backlog'], 'title'));
        $this->assertSame(['In sprint'], array_column($board['active']['columns']['in_progress'], 'title'));
        $this->assertSame(['Critical item'], array_column($board['overdue'], 'title'));
        $this->actingAs($this->operatingUser('delivery'))->get("/admin/operations/projects/{$this->project}/sprints")->assertOk()->assertSee('Critical item')->assertSee('In sprint');
    }

    public function test_planning_moves_tasks_into_a_sprint_and_back(): void
    {
        $lead = $this->operatingUser('tech_lead');
        $sprint = $this->sprint('planned');
        $task = $this->task('backlog', 'Login API', ['priority' => 'high']);

        $this->actingAs($lead)->post("/admin/operations/projects/{$this->project}/sprints/plan", ['sprint_id' => $sprint, 'task_ids' => [$task], 'reason' => 'Sprint planning'])->assertSessionHasNoErrors();
        $this->assertSame($sprint, $this->recordData($task)['sprint_id']);

        $this->actingAs($lead)->post("/admin/operations/projects/{$this->project}/sprints/plan", ['sprint_id' => '', 'task_ids' => [$task], 'reason' => 'Descoped'])->assertSessionHasNoErrors();
        $this->assertNull($this->recordData($task)['sprint_id']);

        $this->actingAs($this->operatingUser('developer'))->post("/admin/operations/projects/{$this->project}/sprints/plan", ['sprint_id' => $sprint, 'task_ids' => [$task], 'reason' => 'Mine'])->assertForbidden();

        $jira = $this->task('backlog', 'From Jira');
        DB::table('operating_records')->where('id', $jira)->update(['source' => 'jira']);
        $this->actingAs($lead)->post("/admin/operations/projects/{$this->project}/sprints/plan", ['sprint_id' => $sprint, 'task_ids' => [$jira], 'reason' => 'Plan'])->assertSessionHasErrors('workflow');
    }

    public function test_new_assignees_are_notified_once_and_never_for_self_assignment(): void
    {
        $lead = $this->operatingUser('tech_lead');
        $leadEmployee = $this->employeeFor($lead);
        $developer = $this->operatingUser('developer');
        $qa = $this->operatingUser('qa');
        $task = ['title' => 'Login', 'reason' => 'Plan', 'data' => ['project_id' => $this->project, 'assignee_id' => $this->employeeFor($developer), 'tester_id' => $this->employeeFor($qa), 'reviewer_id' => $leadEmployee]];

        $this->actingAs($lead)->post('/admin/operations/tasks', $task)->assertSessionHasNoErrors();
        $id = DB::table('operating_records')->where('type', 'tasks')->where('title', 'Login')->value('id');
        $this->actingAs($lead)->post("/admin/operations/record/{$id}", $task + ['version' => 1])->assertSessionHasNoErrors();

        $this->assertSame(1, $developer->notifications()->count());
        $this->assertSame(1, $qa->notifications()->count());
        $this->assertSame(0, $lead->notifications()->count());
    }

    public function test_only_delivery_roles_open_the_board(): void
    {
        $this->actingAs($this->operatingUser('marketing'))->get("/admin/operations/projects/{$this->project}/sprints")->assertForbidden();
        $client = $this->operatingRecord('clients', 'active', ['company' => 'Acme'], 'Acme');
        $this->actingAs($this->operatingUser('owner'))->get("/admin/operations/projects/{$client}/sprints")->assertNotFound();
    }
}
```

- [ ] **Step 2:** → FAIL.

- [ ] **Step 3: `OperatingSprints`** (أضف `use Carbon\CarbonImmutable;`):

```php
    public const PRIORITY_RANK = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];

    public const BOARD_COLUMNS = ['backlog', 'ready', 'in_progress', 'code_review', 'qa', 'ready_to_release', 'done'];

    /**
     * Completion by task count; remaining effort uses remaining_hours, or the estimate when nobody updated it yet.
     * "At risk" means completion trails elapsed time by more than 20 points.
     *
     * @return array{percent: ?int, tasks: array{done: int, total: int}, hours: array{estimate: float, remaining: float}, days: array{total: int, elapsed: int, left: int}, time_percent: int, overdue_tasks: int, late: bool, at_risk: bool}
     */
    public function progress(object $sprint): array
    {
        $data = $this->catalog->data($sprint);
        $tasks = $this->tasks($sprint);
        $hours = fn (object $task, string $key): float => (float) ($this->catalog->data($task)[$key] ?? 0);
        $open = $tasks->reject(fn (object $task): bool => $task->status === 'done');
        $today = today()->toDateString();
        $start = CarbonImmutable::parse($data['start_date']);
        $total = (int) $start->diffInDays(CarbonImmutable::parse($data['end_date'])) + 1;
        $elapsed = $today < $data['start_date'] ? 0 : ($today > $data['end_date'] ? $total : (int) $start->diffInDays(today()) + 1);
        $percent = $tasks->count() ? (int) round(($tasks->count() - $open->count()) / $tasks->count() * 100) : null;
        $timePercent = (int) round($elapsed / max(1, $total) * 100);

        return [
            'percent' => $percent,
            'tasks' => ['done' => $tasks->count() - $open->count(), 'total' => $tasks->count()],
            'hours' => ['estimate' => round($tasks->sum(fn (object $task): float => $hours($task, 'estimate_hours')), 1), 'remaining' => round($open->sum(fn (object $task): float => ($this->catalog->data($task)['remaining_hours'] ?? null) !== null ? $hours($task, 'remaining_hours') : $hours($task, 'estimate_hours')), 1)],
            'days' => ['total' => $total, 'elapsed' => $elapsed, 'left' => max(0, $total - $elapsed)],
            'time_percent' => $timePercent,
            'overdue_tasks' => $open->filter(fn (object $task): bool => ($due = $this->due($task)) !== null && $due < $today)->count(),
            'late' => $sprint->status === 'active' && $today > $data['end_date'],
            'at_risk' => $sprint->status === 'active' && $percent !== null && $percent + 20 < $timePercent,
        ];
    }

    /**
     * Cards are limited to tasks the viewer can open; ordering is priority, then due date.
     *
     * @return array<string, mixed>
     */
    public function board(User $user, object $project): array
    {
        $tasks = $this->access->query($user, 'tasks')->where('status', '!=', 'archived')
            ->whereIn('id', DB::table('operating_links')->where('field', 'project_id')->where('target_id', $project->id)->select('record_id'))->get();
        $sprints = $this->access->query($user, 'sprints')->where('status', '!=', 'archived')->where('data->project_id', $project->id)->orderBy('id')->get();
        $titles = $this->sales->titles($tasks->flatMap(fn (object $task): array => array_filter([($data = $this->catalog->data($task))['assignee_id'] ?? null, $data['tester_id'] ?? null]))->unique()->values()->all());
        $cards = $tasks->map(fn (object $task): array => $this->card($task, $titles))->sort(fn (array $a, array $b): int => [self::PRIORITY_RANK[$a['priority']] ?? 4, $a['due'] ?? '9999', $a['id']] <=> [self::PRIORITY_RANK[$b['priority']] ?? 4, $b['due'] ?? '9999', $b['id']])->values();
        $inSprint = fn (int $sprintId) => $cards->where('sprint_id', $sprintId)->values();
        $active = $sprints->firstWhere('status', 'active');

        return [
            'active' => $active ? ['sprint' => $active, 'progress' => $this->progress($active), 'columns' => collect(self::BOARD_COLUMNS)->mapWithKeys(fn (string $state): array => [$state => $inSprint($active->id)->where('status', $state)->values()->all()])->all()] : null,
            'planned' => $sprints->where('status', 'planned')->map(fn (object $sprint): array => ['sprint' => $sprint, 'progress' => $this->progress($sprint), 'cards' => $inSprint($sprint->id)->all()])->values()->all(),
            'completed' => $sprints->where('status', 'completed')->sortByDesc('id')->take(5)->map(fn (object $sprint): array => ['sprint' => $sprint, 'summary' => $this->catalog->data($sprint)['completed_summary'] ?? null])->values()->all(),
            'backlog' => $cards->filter(fn (array $card): bool => ! $card['sprint_id'] && ! $card['done'])->values()->all(),
            'overdue' => $cards->where('overdue', true)->sortBy('due')->values()->all(),
            'in_sprints' => $cards->filter(fn (array $card): bool => $card['sprint_id'] && ! $card['done'] && $card['source'] === 'findo')->values()->all(),
            'targets' => $sprints->whereIn('status', ['planned', 'active'])->values()->all(),
        ];
    }

    /**
     * Move tasks between the backlog (null sprint) and a planned or active sprint through the normal task save, so every gate and audit event still applies.
     *
     * @param  list<int|string>  $taskIds
     */
    public function plan(User $user, object $project, ?int $sprintId, array $taskIds, string $reason): int
    {
        abort_unless(in_array($user->role, ['owner', 'delivery', 'tech_lead'], true), 403);
        if ($sprintId) {
            $sprint = $this->access->record($user, $sprintId);
            abort_unless($sprint->type === 'sprints' && (int) ($this->catalog->data($sprint)['project_id'] ?? 0) === $project->id, 404);
            if (! in_array($sprint->status, ['planned', 'active'], true)) {
                $this->workflow->fail('أضف المهام إلى سبرنت مخطط أو جارٍ فقط.', 'Add tasks to a planned or active sprint only.');
            }
        }

        return DB::transaction(function () use ($user, $project, $sprintId, $taskIds, $reason): int {
            $moved = 0;
            foreach (array_unique(array_map('intval', $taskIds)) as $taskId) {
                $task = $this->access->record($user, $taskId, 'write');
                $data = $this->catalog->data($task);
                abort_unless($task->type === 'tasks' && (int) ($data['project_id'] ?? 0) === $project->id, 404);
                if ($task->source !== 'findo' || in_array($task->status, ['done', 'archived'], true)) {
                    $this->workflow->fail('مهام Jira والمهام المنجزة لا تُنقل بين السبرنتات.', 'Jira and finished tasks cannot move between sprints.');
                }
                if ((int) ($data['sprint_id'] ?? 0) === (int) $sprintId) {
                    continue;
                }
                $this->sales->saveAs($user, 'tasks', ['title' => $task->title, 'version' => $task->version, 'reason' => $reason, 'data' => array_merge($data, ['sprint_id' => $sprintId])], $task->id);
                $moved++;
            }

            return $moved;
        });
    }

    /**
     * @param  array<int, string>  $titles
     * @return array<string, mixed>
     */
    private function card(object $task, array $titles): array
    {
        $data = $this->catalog->data($task);
        $done = $task->source === 'jira' ? in_array(strtolower((string) ($data['source_status'] ?? '')), OperatingProjects::DONE_JIRA_STATUSES, true) : $task->status === 'done';
        $due = $this->due($task);

        return [
            'id' => $task->id, 'title' => $task->title, 'status' => $task->status, 'source' => $task->source, 'done' => $done,
            'sprint_id' => isset($data['sprint_id']) ? (int) $data['sprint_id'] : null, 'carried' => empty($data['sprint_id']) && ! empty($data['carried_from_sprint']),
            'priority' => $data['priority'] ?? null, 'assignee' => $titles[$data['assignee_id'] ?? 0] ?? ($data['assignee_name'] ?? null), 'tester' => $titles[$data['tester_id'] ?? 0] ?? null,
            'estimate' => isset($data['estimate_hours']) ? (float) $data['estimate_hours'] : null, 'remaining' => isset($data['remaining_hours']) ? (float) $data['remaining_hours'] : null,
            'due' => $due, 'overdue' => ! $done && $due !== null && $due < today()->toDateString(),
        ];
    }

    private function due(object $task): ?string
    {
        $data = $this->catalog->data($task);

        return ($data['approved_due'] ?? null) ?: ($data['original_due'] ?? null);
    }
```

  > `remaining` لمهمة مفتوحة = `remaining_hours` لو كان مسجلًا (حتى لو 0)، وإلا `estimate_hours`. الشرط `!== null` يضمن أن الصفر المسجّل يُحسب صفرًا.

- [ ] **Step 4: إشعار الإسناد في `OperatingWorkflow`**
  - الـconstructor: `public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access, private OperatingNotifier $notifier) {}`، وأضف `use App\Notifications\OperatingAlert;`.
  - في `save()` حوّل `return DB::transaction(function () … });` إلى `$record = DB::transaction(function () … });` ثم بعده:

```php
        $this->notifyAssignees($type, $record, $existing ? $this->catalog->data($existing) : [], $user);

        return $record;
```

  - الـhelper:

```php
    /**
     * Tell people when work newly lands on them; nobody is notified about an assignment they made to themselves.
     *
     * @param  array<string, mixed>  $before
     */
    private function notifyAssignees(string $type, object $record, array $before, User $user): void
    {
        $fields = ['tasks' => ['assignee_id', 'reviewer_id', 'tester_id'], 'team_tasks' => ['assignee_id']][$type] ?? [];
        $after = $this->catalog->data($record);
        $self = (int) $this->access->employeeId($user);
        foreach ($fields as $field) {
            $employee = (int) ($after[$field] ?? 0);
            if (! $employee || $employee === (int) ($before[$field] ?? 0) || $employee === $self) {
                continue;
            }
            $this->notifier->send([$this->notifier->employeeUser($employee)], new OperatingAlert(
                'work_assigned',
                ['ar' => 'عمل جديد مسند إليك', 'en' => 'New work assigned to you'],
                ['ar' => 'أُسند إليك "'.$record->title.'".', 'en' => 'You were assigned "'.$record->title.'".'],
                '/admin/operations/record/'.$record->id,
            ));
        }
    }
```

- [ ] **Step 5: `canCreate`** في `OperatingAccess`:

```php
    /**
     * Creating is narrower than editing for work queues: only leads open new development or marketing tasks.
     */
    public function canCreate(User $user, string $type): bool
    {
        if (! $this->can($user, $type, 'write')) {
            return false;
        }

        return match ($type) {
            'tasks' => in_array($user->role, ['owner', 'delivery', 'tech_lead'], true),
            'team_tasks' => in_array($user->role, ['owner', 'marketing', 'media_buyer'], true),
            default => true,
        };
    }
```

  - في `OperatingWorkflow::save()` استبدل `if (! $existing && $type === 'tasks' && ! in_array($user->role, ['owner', 'delivery', 'tech_lead'], true)) { abort(403); }` بـ`abort_if(! $existing && ! $this->access->canCreate($user, $type), 403);`.
  - في `OperatingController::create()` استبدل `abort_unless($this->access->can($request->user(), $type, 'write'), 403);` بـ`abort_unless($this->access->canCreate($request->user(), $type), 403);`.
  - في `listing.blade.php` زر "إضافة سجل": `@if($access->canCreate(auth()->user(),$type))`.

- [ ] **Step 6: `prefill` العام** في `OperatingController::create()` بعد كتلة `from` وقبل `quote` (القرار D14):

```php
        $relations = collect($this->catalog->entity($type)['fields'])->where('type', 'relation')->keyBy('key');
        foreach ((array) $request->input('prefill', []) as $key => $value) {
            if (isset($relations[$key]) && is_numeric($value)) {
                $target = $this->access->record($request->user(), (int) $value);
                abort_unless($target->type === $relations[$key]['entity'], 404);
                $initial[$key] = (int) $value;
            }
        }
```

- [ ] **Step 7: الكنترولر والمسارات**
  - `ProjectController`: احقن `private OperatingSprints $sprints` في الـconstructor، وأضف:

```php
    public function sprints(int $id, Request $request): Response
    {
        $user = $request->user();
        $project = $this->access->record($user, $id);
        abort_unless($project->type === 'projects', 404);
        abort_unless($this->access->can($user, 'tasks') && $this->access->can($user, 'sprints'), 403);

        return response()->view('admin.operating.project-sprints', $this->sprints->board($user, $project) + [
            'project' => $project,
            'canPlan' => in_array($user->role, ['owner', 'delivery', 'tech_lead'], true),
            'priorities' => collect($this->catalog->entity('tasks')['fields'])->firstWhere('key', 'priority')['options'],
            'collections' => Cms::COLLECTIONS, 'catalog' => $this->catalog, 'access' => $this->access,
        ]);
    }

    public function planSprint(int $id, Request $request): Response
    {
        $project = $this->access->record($request->user(), $id);
        abort_unless($project->type === 'projects', 404);
        $input = $request->validate(['sprint_id' => 'nullable|integer', 'task_ids' => 'required|array|min:1|max:100', 'task_ids.*' => 'integer', 'reason' => 'required|string|max:2000']);
        $moved = $this->sprints->plan($request->user(), $project, $input['sprint_id'] ?? null, $input['task_ids'], $input['reason']);

        return back()->with('success', __('تم نقل :count مهمة.', ['count' => $moved]));
    }
```

  - `routes/admin.php` بعد سطر `projects/portfolio`:

```php
        Route::get('/operations/projects/{id}/sprints', [P::class, 'sprints'])->whereNumber('id');
        Route::post('/operations/projects/{id}/sprints/plan', [P::class, 'planSprint'])->whereNumber('id');
```

- [ ] **Step 8: الـviews**
  - `sprint-progress.blade.php`:

```blade
<div class="inline-stats"><span>{{ __('الإنجاز') }}: <b>{{ $progress['percent']!==null ? $progress['percent'].'%' : __('غير معلوم') }}</b> <small class="muted">({{ $progress['tasks']['done'] }}/{{ $progress['tasks']['total'] }})</small></span><span>{{ __('الوقت المنقضي') }}: <b>{{ $progress['time_percent'] }}%</b> <small class="muted">({{ __('متبقٍ') }} {{ $progress['days']['left'] }} {{ __('يوم') }})</small></span><span>{{ __('الساعات المتبقية / المقدّرة') }}: <b dir="ltr">{{ $progress['hours']['remaining'] }} / {{ $progress['hours']['estimate'] }}</b></span><span class="{{ $progress['overdue_tasks']?'danger':'' }}">{{ __('مهام متأخرة') }}: <b>{{ $progress['overdue_tasks'] }}</b></span>@if($progress['late'])<b class="danger">{{ __('تجاوز السبرنت موعد نهايته') }}</b>@elseif($progress['at_risk'])<b class="danger">{{ __('الإنجاز أبطأ من الوقت المنقضي') }}</b>@endif</div>
<div class="progress" role="progressbar" aria-label="{{ __('الإنجاز') }}" aria-valuenow="{{ $progress['percent'] ?? 0 }}" aria-valuemin="0" aria-valuemax="100"><i style="width:{{ $progress['percent'] ?? 0 }}%"></i></div>
```

  - `task-card.blade.php`:

```blade
<a class="card operation-row" href="/admin/operations/record/{{ $card['id'] }}"><b>{{ $card['title'] }}</b><small>{{ $card['assignee'] ?? __('غير مسند') }}@if($card['tester']) · {{ __('الاختبار') }}: {{ $card['tester'] }}@endif</small><small>{{ $card['priority'] && isset($priorities[$card['priority']]) ? $catalog->label($priorities[$card['priority']]) : __('بلا أولوية') }}@if($card['estimate']) · <span dir="ltr">{{ $card['remaining'] ?? $card['estimate'] }}h / {{ $card['estimate'] }}h</span>@endif @if($card['source']!=='findo') · Jira @endif</small>@if($card['due'])<small class="{{ $card['overdue']?'danger':'' }}">{{ __('الموعد') }}: {{ $card['due'] }}</small>@endif</a>
```

  - `project-sprints.blade.php`:

```blade
@extends('admin.layout')
@section('title',__('الباكلوج والسبرنتات'))
@section('content')
<div class="page-heading"><div><span class="eyebrow"><a href="/admin/operations/record/{{ $project->id }}">{{ $project->title }}</a></span><h1>{{ __('الباكلوج والسبرنتات') }}</h1><p class="muted">{{ __('خطط السبرنت من الباكلوج، وتابع تقدمه والمهام المتأخرة.') }}</p></div>@if($canPlan)<div class="row-actions"><a class="button secondary" href="/admin/operations/tasks/create?prefill[project_id]={{ $project->id }}">+ {{ __('مهمة جديدة') }}</a><a class="button" href="/admin/operations/sprints/create?prefill[project_id]={{ $project->id }}">+ {{ __('سبرنت جديد') }}</a></div>@endif</div>

@if($active)@php $sprintData=$catalog->data($active['sprint']); @endphp
<section class="card"><h2><a href="/admin/operations/record/{{ $active['sprint']->id }}">{{ $active['sprint']->title }}</a> <span class="badge">{{ $catalog->status('active') }}</span></h2><p class="muted">{{ $sprintData['goal'] ?? '' }} · <span dir="ltr">{{ $sprintData['start_date'] }} → {{ $sprintData['end_date'] }}</span></p>
@include('admin.operating.sprint-progress',['progress'=>$active['progress']])
<div class="workflow-board">@foreach($active['columns'] as $state=>$cards)<section><h2>{{ $catalog->status($state) }} <small class="muted">{{ count($cards) }}</small></h2>@foreach($cards as $card)@include('admin.operating.task-card')@endforeach</section>@endforeach</div></section>
@else<section class="card"><p class="muted">{{ __('لا يوجد سبرنت جارٍ. افتح سبرنتًا مخططًا وفعّله من صفحته.') }}</p></section>@endif

@foreach($planned as $item)<section class="card"><h2><a href="/admin/operations/record/{{ $item['sprint']->id }}">{{ $item['sprint']->title }}</a> <span class="badge">{{ $catalog->status('planned') }}</span></h2>@include('admin.operating.sprint-progress',['progress'=>$item['progress']])@forelse($item['cards'] as $card)@include('admin.operating.task-card')@empty<p class="muted">{{ __('لا مهام في هذا السبرنت بعد.') }}</p>@endforelse</section>@endforeach

<section class="card"><h2>{{ __('الباكلوج') }} <small class="muted">{{ count($backlog) }}</small></h2>
@if($backlog)<form method="post" action="/admin/operations/projects/{{ $project->id }}/sprints/plan">@csrf
<div class="table-card"><table><thead><tr>@if($canPlan)<th><span class="sr-only">{{ __('تحديد') }}</span></th>@endif<th>{{ __('المهمة') }}</th><th>{{ __('الحالة') }}</th><th>{{ __('الأولوية') }}</th><th>{{ __('المسؤول') }}</th><th>{{ __('التقدير') }}</th><th>{{ __('الموعد') }}</th></tr></thead><tbody>
@foreach($backlog as $card)<tr>@if($canPlan)<td>@if($card['source']==='findo')<input type="checkbox" name="task_ids[]" value="{{ $card['id'] }}" aria-label="{{ $card['title'] }}">@endif</td>@endif<td><a class="item-title" href="/admin/operations/record/{{ $card['id'] }}">{{ $card['title'] }}</a>@if($card['carried'])<small class="path">{{ __('مرحّلة من سبرنت سابق') }}</small>@endif</td><td><span class="badge">{{ $catalog->status($card['status']) }}</span></td><td>{{ $card['priority'] && isset($priorities[$card['priority']]) ? $catalog->label($priorities[$card['priority']]) : '—' }}</td><td>{{ $card['assignee'] ?? '—' }}</td><td dir="ltr">{{ $card['estimate'] ? $card['estimate'].'h' : '—' }}</td><td class="{{ $card['overdue']?'danger':'' }}">{{ $card['due'] ?? '—' }}</td></tr>@endforeach</tbody></table></div>
@if($canPlan && $targets)<div class="filters"><select name="sprint_id" required aria-label="{{ __('السبرنت') }}">@foreach($targets as $target)<option value="{{ $target->id }}">{{ $target->title }} ({{ $catalog->status($target->status) }})</option>@endforeach</select><input name="reason" required maxlength="2000" placeholder="{{ __('سبب التخطيط') }}" aria-label="{{ __('سبب التخطيط') }}"><button class="button">{{ __('نقل المحدد إلى السبرنت') }}</button></div>@elseif($canPlan)<p class="help">{{ __('أنشئ سبرنتًا أولًا لتنقل إليه المهام.') }}</p>@endif
</form>@else<p class="muted">{{ __('الباكلوج فارغ.') }}</p>@endif</section>

@if($canPlan && $in_sprints)<details class="card"><summary>{{ __('إرجاع مهام إلى الباكلوج') }}</summary><form method="post" action="/admin/operations/projects/{{ $project->id }}/sprints/plan">@csrf<input type="hidden" name="sprint_id" value="">@foreach($in_sprints as $card)<label class="checkbox"><input type="checkbox" name="task_ids[]" value="{{ $card['id'] }}"> {{ $card['title'] }}</label>@endforeach<div class="filters"><input name="reason" required maxlength="2000" placeholder="{{ __('سبب الإرجاع') }}" aria-label="{{ __('سبب الإرجاع') }}"><button class="button secondary">{{ __('إرجاع المحدد') }}</button></div></form></details>@endif

<section class="card"><h2 class="{{ $overdue?'danger':'' }}">{{ __('مهام متأخرة') }} <small class="muted">{{ count($overdue) }}</small></h2>@forelse($overdue as $card)@include('admin.operating.task-card')@empty<p class="muted">{{ __('لا مهام متأخرة.') }}</p>@endforelse</section>

@if($completed)<section class="card"><h2>{{ __('سبرنتات مكتملة') }}</h2>@foreach($completed as $item)<a class="operation-row" href="/admin/operations/record/{{ $item['sprint']->id }}"><b>{{ $item['sprint']->title }}</b><span>@if($item['summary']){{ __('منجز') }}: {{ $item['summary']['done'] }} · {{ __('مرحّل') }}: {{ $item['summary']['carried'] }}@endif</span></a>@endforeach</section>@endif
@endsection
```

  > تحقق أن الكلاسين `sr-only` و`checkbox` موجودان في `operating.css` (`grep -n "sr-only\|\.checkbox" public/admin-assets/operating.css`). لو غير موجودين: أضف `.sr-only{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0)}` و`label.checkbox{display:flex;gap:8px;align-items:center;padding:6px 0}`.

  - `form.blade.php`:
    - في قسم `projectReport` بعد شريط التقدم: `@if($access->can(auth()->user(),'sprints') && $access->can(auth()->user(),'tasks'))<a class="button secondary" href="/admin/operations/projects/{{ $record->id }}/sprints">{{ __('الباكلوج والسبرنتات') }}</a>@endif`.
    - بعد سطر الـsignals: `@if($sprintProgress)<section class="card"><h2>{{ __('تقدم السبرنت') }}</h2>@include('admin.operating.sprint-progress',['progress'=>$sprintProgress])<a class="button secondary" href="/admin/operations/projects/{{ $data['project_id'] }}/sprints">{{ __('لوحة السبرنت') }}</a></section>@endif`.
  - `OperatingController::form()`: أضف للمصفوفة `'sprintProgress' => $record && $type === 'sprints' ? app(OperatingSprints::class)->progress($record) : null` (مع `use App\Services\OperatingSprints;`).
  - `projects-portfolio.blade.php`: في خلية العنوان بعد الرابط: `@if($access->can(auth()->user(),'sprints') && $access->can(auth()->user(),'tasks'))<a class="path" href="/admin/operations/projects/{{ $row['id'] }}/sprints">{{ __('الباكلوج والسبرنتات') }}</a>@endif`.

- [ ] **Step 9: الترجمات** (`lang/en.json`): `"الباكلوج والسبرنتات": "Backlog & sprints"`, `"خطط السبرنت من الباكلوج، وتابع تقدمه والمهام المتأخرة.": "Plan sprints from the backlog and follow progress and overdue work."`, `"مهمة جديدة": "New task"`, `"سبرنت جديد": "New sprint"`, `"لا يوجد سبرنت جارٍ. افتح سبرنتًا مخططًا وفعّله من صفحته.": "No active sprint. Open a planned sprint and start it from its page."`, `"لا مهام في هذا السبرنت بعد.": "No tasks in this sprint yet."`, `"الباكلوج": "Backlog"`, `"تحديد": "Select"`, `"المهمة": "Task"`, `"المسؤول": "Owner"`, `"التقدير": "Estimate"`, `"السبرنت": "Sprint"`, `"سبب التخطيط": "Planning reason"`, `"نقل المحدد إلى السبرنت": "Move selected to sprint"`, `"أنشئ سبرنتًا أولًا لتنقل إليه المهام.": "Create a sprint first to move tasks into it."`, `"الباكلوج فارغ.": "The backlog is empty."`, `"مرحّلة من سبرنت سابق": "Carried over from a previous sprint"`, `"إرجاع مهام إلى الباكلوج": "Return tasks to the backlog"`, `"سبب الإرجاع": "Reason"`, `"إرجاع المحدد": "Return selected"`, `"مهام متأخرة": "Overdue tasks"`, `"لا مهام متأخرة.": "No overdue tasks."`, `"سبرنتات مكتملة": "Completed sprints"`, `"منجز": "Done"`, `"مرحّل": "Carried over"`, `"الوقت المنقضي": "Time elapsed"`, `"متبقٍ": "left"`, `"يوم": "days"`, `"الساعات المتبقية / المقدّرة": "Remaining / estimated hours"`, `"تجاوز السبرنت موعد نهايته": "The sprint passed its end date"`, `"الإنجاز أبطأ من الوقت المنقضي": "Completion trails elapsed time"`, `"الاختبار": "QA"`, `"بلا أولوية": "No priority"`, `"تقدم السبرنت": "Sprint progress"`, `"لوحة السبرنت": "Sprint board"`, `"تم نقل :count مهمة.": "Moved :count task(s)."`. وتحقق من أن `"الإنجاز"` و`"غير معلوم"` و`"الموعد"` و`"غير مسند"` موجودة مسبقًا (`grep`)، وأضف الناقص فقط.
- [ ] **Step 10:** الاختبارات → PASS، ثم الحزمة كاملة (`save()` تغيّر: الحضور والمبيعات يجب أن تبقى ناجحة)، ثم pint، ثم commit: `feat(projects): backlog and sprint board with planning, progress, overdue work and assignment alerts`.

---

### Task 4: المحطات بمسؤول، والمخرجات وقواعدها (FIN-14، القرار D5)

**Files:**
- Modify: `config/operating.php` (`milestones.assignee_id`، وكيان `deliverables` بعد `milestones`)
- Modify: `app/Services/OperatingWorkflow.php`
- Create: `app/Services/OperatingMilestones.php` (`progress`)
- Modify: `app/Http/Controllers/OperatingController.php` (`milestonePanel`)، `form.blade.php`، `lang/en.json`
- Test: `tests/Feature/Operating/MilestonesTest.php`

**Interfaces:**
- Produces:
  - `milestones.assignee_id` (relation employees، اختياري للحفظ، وإلزامي لـin_progress وsubmitted).
  - كيان `deliverables`: `milestone_id*, project_id (يُملأ تلقائيًا), assignee_id, due_date*, description, delivery_url, evidence`. الحالات `planned, in_progress, delivered, archived`.
  - `OperatingMilestones::OPEN = ['planned', 'in_progress']`.
  - `OperatingMilestones::progress(object $milestone): array{percent: ?int, delivered: int, total: int, delayed: int, due: ?string, overdue: bool, due_soon: bool}`.
  - `OperatingMilestones::deliverables(int $milestoneId): Collection`.

- [ ] **Step 1: الاختبار الفاشل**

```php
<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingMilestones;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class MilestonesTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    private int $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-15 10:00'));
        $this->project = $this->operatingRecord('projects', 'execution', ['outcome' => 'Portal', 'original_due' => '2026-12-01', 'client_contact' => 'Mona'], 'Portal');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function milestone(string $status = 'planned', array $data = []): int
    {
        return $this->operatingRecord('milestones', $status, array_merge(['project_id' => $this->project, 'deliverable' => 'Phase 1', 'acceptance_criteria' => 'Signed off', 'original_due' => '2026-11-01'], $data), 'Phase 1');
    }

    private function deliverable(int $milestone, string $status, string $due = '2026-10-20', array $data = []): int
    {
        return $this->operatingRecord('deliverables', $status, array_merge(['milestone_id' => $milestone, 'project_id' => $this->project, 'due_date' => $due], $data), 'Login screens');
    }

    public function test_deliverables_inherit_the_milestone_project_and_respect_its_due_date(): void
    {
        $delivery = $this->operatingUser('delivery');
        $milestone = $this->milestone();
        $payload = ['title' => 'Login screens', 'reason' => 'Plan', 'data' => ['milestone_id' => $milestone, 'due_date' => '2026-10-20']];

        $this->actingAs($delivery)->post('/admin/operations/deliverables', $payload)->assertSessionHasNoErrors();
        $id = DB::table('operating_records')->where('type', 'deliverables')->value('id');
        $this->assertSame($this->project, $this->recordData($id)['project_id']);
        $this->assertTrue(DB::table('operating_links')->where('record_id', $id)->where('field', 'project_id')->where('target_id', $this->project)->exists());

        $payload['data']['due_date'] = '2026-12-30';
        $this->actingAs($delivery)->post('/admin/operations/deliverables', $payload)->assertSessionHasErrors('workflow');
    }

    public function test_a_deliverable_is_delivered_only_with_a_link_or_evidence(): void
    {
        $owner = $this->operatingUser('owner');
        $id = $this->deliverable($this->milestone(), 'in_progress');

        $this->actingAs($owner)->post("/admin/operations/record/{$id}/transition", ['target' => 'delivered', 'version' => 1, 'reason' => 'Done'])->assertSessionHasErrors('workflow');
        DB::table('operating_records')->where('id', $id)->update(['data' => json_encode(['milestone_id' => $this->milestone(), 'project_id' => $this->project, 'due_date' => '2026-10-20', 'delivery_url' => 'https://figma.test/login'])]);
        $this->actingAs($owner)->post("/admin/operations/record/{$id}/transition", ['target' => 'delivered', 'version' => 1, 'reason' => 'Done'])->assertSessionHasNoErrors();
    }

    public function test_milestones_need_an_owner_delivered_outputs_and_submission_before_acceptance(): void
    {
        $owner = $this->operatingUser('owner');
        $employee = $this->employeeFor($this->operatingUser('delivery'));
        $unowned = $this->milestone();
        $this->actingAs($owner)->post("/admin/operations/record/{$unowned}/transition", ['target' => 'in_progress', 'version' => 1, 'reason' => 'Start'])->assertSessionHasErrors('workflow');

        $milestone = $this->milestone('in_progress', ['assignee_id' => $employee, 'client_acceptance' => 'Email', 'accepted_at' => '2026-10-15']);
        $deliverable = $this->deliverable($milestone, 'in_progress');
        $this->actingAs($owner)->post("/admin/operations/record/{$milestone}/transition", ['target' => 'accepted', 'version' => 1, 'reason' => 'Skip'])->assertSessionHasErrors('workflow');
        $this->actingAs($owner)->post("/admin/operations/record/{$milestone}/transition", ['target' => 'submitted', 'version' => 1, 'reason' => 'Send'])->assertSessionHasErrors('workflow');

        DB::table('operating_records')->where('id', $deliverable)->update(['status' => 'delivered']);
        $this->actingAs($owner)->post("/admin/operations/record/{$milestone}/transition", ['target' => 'submitted', 'version' => 1, 'reason' => 'Send'])->assertSessionHasNoErrors();
        $this->actingAs($owner)->post("/admin/operations/record/{$milestone}/transition", ['target' => 'accepted', 'version' => 2, 'reason' => 'Client signed'])->assertSessionHasNoErrors();
    }

    public function test_progress_counts_delivered_outputs_and_flags_delays(): void
    {
        $milestones = app(OperatingMilestones::class);
        $late = $this->milestone('in_progress', ['original_due' => '2026-10-10']);
        $this->deliverable($late, 'delivered', '2026-10-05');
        $this->deliverable($late, 'in_progress', '2026-10-08');

        $progress = $milestones->progress(DB::table('operating_records')->find($late));
        $this->assertSame(50, $progress['percent']);
        $this->assertSame(1, $progress['delayed']);
        $this->assertTrue($progress['overdue']);

        $this->assertFalse($milestones->progress(DB::table('operating_records')->find($this->milestone('submitted', ['original_due' => '2026-10-01'])))['overdue']);
        $this->assertTrue($milestones->progress(DB::table('operating_records')->find($this->milestone('planned', ['original_due' => '2026-10-20'])))['due_soon']);
        $this->assertSame(100, $milestones->progress(DB::table('operating_records')->find($this->milestone('accepted')))['percent']);
    }
}
```

- [ ] **Step 2:** → FAIL.

- [ ] **Step 3: الـconfig**
  - `milestones.fields` بعد `project_id`: `{ "key": "assignee_id", "label": { "ar": "مسؤول المحطة", "en": "Milestone owner" }, "type": "relation", "required": false, "entity": "employees" },`
  - كيان جديد بعد `milestones`:

```json
    "deliverables": {
      "label": { "ar": "المخرجات", "en": "Deliverables" },
      "group": "delivery",
      "pages": [],
      "fields": [
        { "key": "milestone_id", "label": { "ar": "المحطة", "en": "Milestone" }, "type": "relation", "required": true, "entity": "milestones" },
        { "key": "project_id", "label": { "ar": "المشروع (يُملأ من المحطة)", "en": "Project (taken from the milestone)" }, "type": "relation", "required": false, "entity": "projects" },
        { "key": "assignee_id", "label": { "ar": "المسؤول", "en": "Owner" }, "type": "relation", "required": false, "entity": "employees" },
        { "key": "due_date", "label": { "ar": "الموعد", "en": "Due date" }, "type": "date", "required": true },
        { "key": "description", "label": { "ar": "الوصف ومعيار التسليم", "en": "Description and done criteria" }, "type": "textarea", "required": false },
        { "key": "delivery_url", "label": { "ar": "رابط التسليم", "en": "Delivery link" }, "type": "url", "required": false },
        { "key": "evidence", "label": { "ar": "دليل التسليم", "en": "Delivery evidence" }, "type": "textarea", "required": false }
      ],
      "statuses": ["planned", "in_progress", "delivered", "archived"]
    },
```

- [ ] **Step 4: قواعد `save()`** (بعد كتلة sprints):

```php
        if ($type === 'deliverables') {
            $milestone = DB::table('operating_records')->find($data['milestone_id']);
            $milestoneData = $this->catalog->data($milestone);
            $data['project_id'] = $milestoneData['project_id'] ?? null;
            $milestoneDue = ($milestoneData['approved_due'] ?? null) ?: ($milestoneData['original_due'] ?? null);
            if ($milestoneDue && $data['due_date'] > $milestoneDue) {
                $this->fail('موعد المخرج بعد موعد المحطة.', 'The deliverable is due after its milestone.');
            }
            if (! $existing && in_array($milestone->status, ['submitted', 'accepted'], true)) {
                $this->fail('المحطة قُدِّمت للعميل؛ لا تُضاف لها مخرجات جديدة.', 'The milestone was already submitted; add no new deliverables.');
            }
        }
```

  > ترتيب مهم: فحص "مشروع مختلف" العام يعمل قبل هذه الكتلة. لو أدخل المستخدم `project_id` لمشروع آخر يُرفض هناك، وهنا يُضبط من المحطة دائمًا. والروابط تُبنى بعد ذلك من `$data`، فيُنشأ رابط `project_id` صحيح.

- [ ] **Step 5: `gate()`** استبدل كتلة `milestones` الحالية (`if ($record->type === 'milestones' && $target === 'accepted') { … }`) بـ:

```php
        if ($record->type === 'milestones') {
            if (in_array($target, ['in_progress', 'submitted'], true)) {
                $this->requireFields($data, ['assignee_id']);
            }
            if ($target === 'submitted' && app(OperatingMilestones::class)->deliverables($record->id)->where('status', '!=', 'delivered')->isNotEmpty()) {
                $this->fail('سلّم كل مخرجات المحطة قبل تقديمها للعميل.', 'Deliver every deliverable before submitting the milestone.');
            }
            if ($target === 'accepted') {
                if ($record->status !== 'submitted') {
                    $this->fail('اقبل المحطة بعد تقديمها.', 'Accept a milestone after it is submitted.');
                }
                $this->requireFields($data, ['client_acceptance', 'accepted_at']);
            }
        }
        if ($record->type === 'deliverables' && $target === 'delivered' && empty($data['delivery_url']) && empty($data['evidence'])) {
            $this->fail('سجّل رابط التسليم أو دليله.', 'Add the delivery link or evidence.');
        }
```

- [ ] **Step 6: `OperatingMilestones`** (الجزء الأول)

```php
<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OperatingMilestones
{
    /**
     * States in which a passed due date is the team's delay; a submitted milestone is waiting on the client.
     */
    public const OPEN = ['planned', 'in_progress'];

    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access, private OperatingNotifier $notifier, private OperatingWorkflow $workflow, private OperatingSales $sales) {}

    /**
     * @return Collection<int, object>
     */
    public function deliverables(int $milestoneId): Collection
    {
        return DB::table('operating_records')->where('type', 'deliverables')->where('status', '!=', 'archived')
            ->whereIn('id', DB::table('operating_links')->where('field', 'milestone_id')->where('target_id', $milestoneId)->select('record_id'))
            ->orderBy('id')->get();
    }

    /**
     * @return array{percent: ?int, delivered: int, total: int, delayed: int, due: ?string, overdue: bool, due_soon: bool}
     */
    public function progress(object $milestone): array
    {
        $data = $this->catalog->data($milestone);
        $deliverables = $this->deliverables($milestone->id);
        $delivered = $deliverables->where('status', 'delivered')->count();
        $today = today()->toDateString();
        $due = ($data['approved_due'] ?? null) ?: ($data['original_due'] ?? null);
        $open = in_array($milestone->status, self::OPEN, true);

        return [
            'percent' => $milestone->status === 'accepted' ? 100 : ($deliverables->count() ? (int) round($delivered / $deliverables->count() * 100) : null),
            'delivered' => $delivered,
            'total' => $deliverables->count(),
            'delayed' => $deliverables->filter(fn (object $item): bool => $item->status !== 'delivered' && ($this->catalog->data($item)['due_date'] ?? '9999') < $today)->count(),
            'due' => $due,
            'overdue' => $open && $due !== null && $due < $today,
            'due_soon' => $open && $due !== null && $due >= $today && $due <= today()->addDays(7)->toDateString(),
        ];
    }
}
```

- [ ] **Step 7: لوحة المحطة في صفحة السجل**
  - `OperatingController::form()`: `'milestonePanel' => $record && $type === 'milestones' ? ['progress' => app(OperatingMilestones::class)->progress($record), 'deliverables' => $this->access->can($request->user(), 'deliverables') ? $this->access->query($request->user(), 'deliverables')->where('status', '!=', 'archived')->where('data->milestone_id', $record->id)->orderBy('id')->get() : collect()] : null`.
  - `form.blade.php` بعد لوحة السبرنت:

```blade
@if($milestonePanel)@php $mp=$milestonePanel['progress']; @endphp<section class="card"><h2>{{ __('مخرجات المحطة') }}</h2><div class="inline-stats"><span>{{ __('الإنجاز') }}: <b>{{ $mp['percent']!==null ? $mp['percent'].'%' : __('غير معلوم') }}</b> <small class="muted">({{ $mp['delivered'] }}/{{ $mp['total'] }})</small></span><span class="{{ $mp['delayed']?'danger':'' }}">{{ __('مخرجات متأخرة') }}: <b>{{ $mp['delayed'] }}</b></span>@if($mp['overdue'])<b class="danger">{{ __('المحطة متأخرة') }}</b>@elseif($mp['due_soon'])<b>{{ __('تقترب من موعدها') }}</b>@endif</div><div class="progress" role="progressbar" aria-label="{{ __('الإنجاز') }}" aria-valuenow="{{ $mp['percent'] ?? 0 }}" aria-valuemin="0" aria-valuemax="100"><i style="width:{{ $mp['percent'] ?? 0 }}%"></i></div>
@forelse($milestonePanel['deliverables'] as $item)@php $itemData=$catalog->data($item); @endphp<a class="operation-row" href="/admin/operations/record/{{ $item->id }}"><b>{{ $item->title }}</b><span>{{ $catalog->status($item->status) }} · <span class="{{ $item->status!=='delivered' && ($itemData['due_date'] ?? '9999') < today()->toDateString() ? 'danger' : '' }}">{{ $itemData['due_date'] ?? '—' }}</span></span></a>@empty<p class="muted">{{ __('لا مخرجات مسجلة بعد.') }}</p>@endforelse
@if($access->canCreate(auth()->user(),'deliverables'))<a class="button secondary" href="/admin/operations/deliverables/create?prefill[milestone_id]={{ $record->id }}">+ {{ __('إضافة مخرج') }}</a>@endif</section>@endif
```

- [ ] **Step 8:** الترجمات: `"مخرجات المحطة": "Milestone deliverables"`, `"مخرجات متأخرة": "Delayed deliverables"`, `"المحطة متأخرة": "Milestone overdue"`, `"تقترب من موعدها": "Due soon"`, `"لا مخرجات مسجلة بعد.": "No deliverables yet."`, `"إضافة مخرج": "Add deliverable"`.
- [ ] **Step 9:** الاختبارات → PASS. **انتبه:** `ProjectLifecycleTest` يستخدم محطات `accepted` مُدرجة مباشرة (لا انتقال)، فلن يتأثر. شغّل الحزمة، ثم pint، ثم commit: `feat(projects): milestone owners, deliverables with delivery gates and milestone progress`.

---

### Task 5: متابعة المحطات وتنبيهات مديري المشاريع (FIN-14، القرار D6)

**Files:**
- Modify: `app/Services/OperatingWorkflow.php` (`mark()` عام)
- Modify: `app/Services/OperatingFollowUps.php` (يستخدم `workflow->mark`)
- Modify: `app/Services/OperatingMilestones.php` (`tracker`، `sendReminders`)
- Create: `app/Console/Commands/SendMilestoneReminders.php`
- Modify: `app/Http/Controllers/ProjectController.php` (`milestones`)
- Create: `resources/views/admin/operating/milestones-tracker.blade.php`
- Modify: `routes/admin.php`، `routes/console.php`، `layout.blade.php`، `listing.blade.php`، `form.blade.php`، `lang/en.json`
- Test: `tests/Feature/Operating/MilestoneTrackerTest.php`

**Interfaces:**
- Produces:
  - `OperatingWorkflow::mark(object $record, string $key, string $value, string $reason): void` (public، منقول من `OperatingFollowUps::mark` بنفس السلوك وتعليقه).
  - `OperatingMilestones::NOTICE_DAYS = 3`.
  - `OperatingMilestones::tracker(User $user, array{project?: int|null, owner?: int|null} $filters = []): array{overdue: list<row>, due_soon: list<row>, upcoming: list<row>, submitted: list<row>, accepted: list<row>, delayed_deliverables: list<array{id: int, title: string, milestone: ?string, project: ?string, owner: ?string, due: string}>, counts: array<string, int>}`. الـrow: `array{id: int, title: string, project: ?string, project_id: ?int, owner: ?string, status: string, progress: array}`.
  - `OperatingMilestones::sendReminders(CarbonImmutable $now): array{upcoming: int, overdue: int, deliverables: int}`.
  - مفاتيح محسوبة: `upcoming_alerted_for` و`overdue_alerted_for` (قيمتها تاريخ الموعد الذي نُبِّه له).

- [ ] **Step 1: الاختبار الفاشل**

```php
<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingMilestones;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class MilestoneTrackerTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-15 08:00'));
    }

    private function project(?int $managerId = null): int
    {
        return $this->operatingRecord('projects', 'execution', ['outcome' => 'Portal', 'original_due' => '2026-12-01', 'client_contact' => 'Mona', 'manager_id' => $managerId], 'Portal');
    }

    private function milestone(int $project, string $status, string $due, ?int $owner = null, string $title = 'Phase'): int
    {
        return $this->operatingRecord('milestones', $status, ['project_id' => $project, 'assignee_id' => $owner, 'deliverable' => 'X', 'acceptance_criteria' => 'Y', 'original_due' => $due], $title);
    }

    public function test_project_manager_and_owner_get_one_upcoming_and_one_overdue_alert_per_due_date(): void
    {
        $manager = $this->operatingUser('delivery');
        $owner = $this->operatingUser('developer');
        $ownerEmployee = $this->employeeFor($owner);
        $project = $this->project($this->employeeFor($manager));
        $this->milestone($project, 'in_progress', '2026-10-17', $ownerEmployee);
        $late = $this->milestone($project, 'planned', '2026-10-10', $ownerEmployee);
        $this->milestone($project, 'planned', '2026-11-30', $ownerEmployee);
        $this->milestone($project, 'submitted', '2026-10-01', $ownerEmployee);
        $service = app(OperatingMilestones::class);

        $this->assertSame(['upcoming' => 1, 'overdue' => 1, 'deliverables' => 0], $service->sendReminders(CarbonImmutable::now()));
        $this->assertSame(2, $manager->notifications()->count());
        $this->assertSame(2, $owner->notifications()->count());
        $this->assertSame(['upcoming' => 0, 'overdue' => 0, 'deliverables' => 0], $service->sendReminders(CarbonImmutable::now()));

        DB::table('operating_records')->where('id', $late)->update(['data->approved_due' => '2026-10-12']);
        $this->assertSame(1, $service->sendReminders(CarbonImmutable::now())['overdue']);
    }

    public function test_without_a_project_manager_every_delivery_manager_is_alerted(): void
    {
        $first = $this->operatingUser('delivery');
        $second = $this->operatingUser('delivery');
        $this->milestone($this->project(), 'planned', '2026-10-01');

        app(OperatingMilestones::class)->sendReminders(CarbonImmutable::now());

        $this->assertSame(1, $first->notifications()->count());
        $this->assertSame(1, $second->notifications()->count());
    }

    public function test_overdue_deliverables_alert_their_owner_once(): void
    {
        $owner = $this->operatingUser('developer');
        $project = $this->project();
        $milestone = $this->milestone($project, 'in_progress', '2026-11-01');
        $this->operatingRecord('deliverables', 'in_progress', ['milestone_id' => $milestone, 'project_id' => $project, 'assignee_id' => $this->employeeFor($owner), 'due_date' => '2026-10-14'], 'API docs');
        $this->operatingRecord('deliverables', 'delivered', ['milestone_id' => $milestone, 'project_id' => $project, 'due_date' => '2026-10-01'], 'Done docs');

        $this->assertSame(1, app(OperatingMilestones::class)->sendReminders(CarbonImmutable::now())['deliverables']);
        $this->assertSame(1, $owner->notifications()->count());
        $this->assertSame(0, app(OperatingMilestones::class)->sendReminders(CarbonImmutable::now())['deliverables']);
    }

    public function test_tracker_buckets_milestones_and_lists_delayed_deliverables(): void
    {
        $project = $this->project();
        $this->milestone($project, 'planned', '2026-10-10', null, 'Late phase');
        $this->milestone($project, 'in_progress', '2026-10-18', null, 'Soon phase');
        $this->milestone($project, 'planned', '2026-12-01', null, 'Later phase');
        $submitted = $this->milestone($project, 'submitted', '2026-10-01', null, 'Sent phase');
        $this->operatingRecord('deliverables', 'planned', ['milestone_id' => $submitted, 'project_id' => $project, 'due_date' => '2026-10-02'], 'Late doc');

        $tracker = app(OperatingMilestones::class)->tracker($this->operatingUser('owner'));

        $this->assertSame(['Late phase'], array_column($tracker['overdue'], 'title'));
        $this->assertSame(['Soon phase'], array_column($tracker['due_soon'], 'title'));
        $this->assertSame(['Later phase'], array_column($tracker['upcoming'], 'title'));
        $this->assertSame(['Sent phase'], array_column($tracker['submitted'], 'title'));
        $this->assertSame(['Late doc'], array_column($tracker['delayed_deliverables'], 'title'));
        $this->actingAs($this->operatingUser('delivery'))->get('/admin/operations/milestones/tracker')->assertOk()->assertSee('Late phase')->assertSee('Late doc');
        $this->actingAs($this->operatingUser('marketing'))->get('/admin/operations/milestones/tracker')->assertForbidden();
    }

    public function test_the_command_prints_what_it_sent(): void
    {
        $this->operatingUser('delivery');
        $this->milestone($this->project(), 'planned', '2026-10-16');

        $this->artisan('operating:milestone-reminders')->expectsOutputToContain('1 upcoming, 0 overdue, 0 deliverables')->assertSuccessful();
    }
}
```

  > السطر `update(['data->approved_due' => …])` يكتب مفتاح JSON مباشرة. نفس نمط `mark()`، ويعمل على SQLite وpgsql.

- [ ] **Step 2:** → FAIL.

- [ ] **Step 3: نقل `mark()`.** انقل الدالة `mark` من `OperatingFollowUps` (مع الـdocblock كاملًا) إلى `OperatingWorkflow` كـ`public function mark(object $record, string $key, string $value, string $reason): void`، بجسم: `DB::table('operating_records')->where('id', $record->id)->update(['data->'.$key => $value]); $this->event($record, 'reminder', $reason, null, [$key => $value]);`. وفي `OperatingFollowUps` استبدل `$this->mark(` بـ`$this->workflow->mark(` واحذف الدالة الخاصة. شغّل `tests/Feature/Operating/FollowUpsTest.php` → PASS.

- [ ] **Step 4: `OperatingMilestones`** (أضف `use App\Models\User; use App\Notifications\OperatingAlert; use Carbon\CarbonImmutable;`):

```php
    public const NOTICE_DAYS = 3;

    /**
     * @param  array{project?: int|null, owner?: int|null}  $filters
     * @return array<string, mixed>
     */
    public function tracker(User $user, array $filters = []): array
    {
        $milestones = $this->access->query($user, 'milestones')->whereIn('status', ['planned', 'in_progress', 'submitted', 'accepted'])
            ->when($filters['project'] ?? null, fn ($query, $project) => $query->where('data->project_id', (int) $project))
            ->when($filters['owner'] ?? null, fn ($query, $owner) => $query->where('data->assignee_id', (int) $owner))
            ->get();
        $deliverables = $this->access->can($user, 'deliverables') ? $this->access->query($user, 'deliverables')->whereIn('status', ['planned', 'in_progress'])->where('data->due_date', '<', today()->toDateString())
            ->when($filters['project'] ?? null, fn ($query, $project) => $query->where('data->project_id', (int) $project))
            ->when($filters['owner'] ?? null, fn ($query, $owner) => $query->where('data->assignee_id', (int) $owner))
            ->get() : collect();
        $ids = $milestones->merge($deliverables)->flatMap(fn (object $row): array => array_values(array_filter([($data = $this->catalog->data($row))['project_id'] ?? null, $data['assignee_id'] ?? null, $data['milestone_id'] ?? null])))->unique()->values()->all();
        $titles = $this->sales->titles($ids);
        $board = ['overdue' => [], 'due_soon' => [], 'upcoming' => [], 'submitted' => [], 'accepted' => []];
        $recent = today()->subDays(30)->toDateString();
        foreach ($milestones as $milestone) {
            $data = $this->catalog->data($milestone);
            $progress = $this->progress($milestone);
            $bucket = match (true) {
                $milestone->status === 'accepted' => ($data['accepted_at'] ?? substr((string) $milestone->updated_at, 0, 10)) >= $recent ? 'accepted' : null,
                $milestone->status === 'submitted' => 'submitted',
                $progress['overdue'] => 'overdue',
                $progress['due_soon'] => 'due_soon',
                default => 'upcoming',
            };
            if ($bucket) {
                $board[$bucket][] = ['id' => $milestone->id, 'title' => $milestone->title, 'project' => $titles[$data['project_id'] ?? 0] ?? null, 'project_id' => $data['project_id'] ?? null, 'owner' => $titles[$data['assignee_id'] ?? 0] ?? null, 'status' => $milestone->status, 'progress' => $progress];
            }
        }
        foreach ($board as $bucket => $rows) {
            usort($rows, fn (array $a, array $b): int => ($a['progress']['due'] ?? '9999') <=> ($b['progress']['due'] ?? '9999'));
            $board[$bucket] = $rows;
        }
        $board['delayed_deliverables'] = $deliverables->map(fn (object $item): array => ['id' => $item->id, 'title' => $item->title, 'milestone' => $titles[($data = $this->catalog->data($item))['milestone_id'] ?? 0] ?? null, 'project' => $titles[$data['project_id'] ?? 0] ?? null, 'owner' => $titles[$data['assignee_id'] ?? 0] ?? null, 'due' => $data['due_date']])->sortBy('due')->values()->all();
        $board['counts'] = array_map('count', $board);

        return $board;
    }

    /**
     * Daily: one alert when an open milestone enters the notice window, one when it passes its date, and one per overdue deliverable.
     * Markers store the due date that was announced, so an approved date change re-arms the alert.
     *
     * @return array{upcoming: int, overdue: int, deliverables: int}
     */
    public function sendReminders(CarbonImmutable $now): array
    {
        $sent = ['upcoming' => 0, 'overdue' => 0, 'deliverables' => 0];
        $today = $now->toDateString();
        $horizon = $now->addDays(self::NOTICE_DAYS)->toDateString();
        foreach (DB::table('operating_records')->where('type', 'milestones')->whereIn('status', self::OPEN)->get() as $milestone) {
            $data = $this->catalog->data($milestone);
            $due = ($data['approved_due'] ?? null) ?: ($data['original_due'] ?? null);
            if (! $due || $due > $horizon) {
                continue;
            }
            $overdue = $due < $today;
            $marker = $overdue ? 'overdue_alerted_for' : 'upcoming_alerted_for';
            if (($data[$marker] ?? null) === $due) {
                continue;
            }
            $this->notifier->send($this->recipients($data['project_id'] ?? null, $data['assignee_id'] ?? null), new OperatingAlert(
                $overdue ? 'milestone_overdue' : 'milestone_due_soon',
                $overdue ? ['ar' => 'محطة متأخرة', 'en' => 'Overdue milestone'] : ['ar' => 'محطة تقترب من موعدها', 'en' => 'Milestone due soon'],
                $overdue ? ['ar' => 'المحطة "'.$milestone->title.'" تجاوزت موعدها '.$due.'.', 'en' => 'The milestone "'.$milestone->title.'" passed its due date '.$due.'.'] : ['ar' => 'موعد المحطة "'.$milestone->title.'" هو '.$due.'.', 'en' => 'The milestone "'.$milestone->title.'" is due on '.$due.'.'],
                '/admin/operations/record/'.$milestone->id,
                $overdue,
            ));
            $this->workflow->mark($milestone, $marker, $due, $overdue ? 'Overdue milestone alert sent' : 'Upcoming milestone alert sent');
            $sent[$overdue ? 'overdue' : 'upcoming']++;
        }
        foreach (DB::table('operating_records')->where('type', 'deliverables')->whereIn('status', ['planned', 'in_progress'])->where('data->due_date', '<', $today)->get() as $deliverable) {
            $data = $this->catalog->data($deliverable);
            if (($data['overdue_alerted_for'] ?? null) === $data['due_date']) {
                continue;
            }
            $this->notifier->send($this->recipients($data['project_id'] ?? null, $data['assignee_id'] ?? null), new OperatingAlert(
                'deliverable_overdue',
                ['ar' => 'مخرج متأخر', 'en' => 'Overdue deliverable'],
                ['ar' => 'المخرج "'.$deliverable->title.'" تجاوز موعده '.$data['due_date'].'.', 'en' => 'The deliverable "'.$deliverable->title.'" passed its due date '.$data['due_date'].'.'],
                '/admin/operations/record/'.$deliverable->id,
            ));
            $this->workflow->mark($deliverable, 'overdue_alerted_for', $data['due_date'], 'Overdue deliverable alert sent');
            $sent['deliverables']++;
        }

        return $sent;
    }

    /**
     * The project's manager and the item's owner; a project without a manager alerts every active delivery manager.
     *
     * @return list<User|null>
     */
    private function recipients(int|string|null $projectId, int|string|null $ownerId): array
    {
        $project = $projectId ? DB::table('operating_records')->find($projectId) : null;
        $managerId = $project ? (int) ($this->catalog->data($project)['manager_id'] ?? 0) : 0;
        $managers = $managerId ? [$this->notifier->employeeUser($managerId)] : $this->notifier->roles(['delivery'])->all();

        return [...$managers, $ownerId ? $this->notifier->employeeUser((int) $ownerId) : null];
    }
```

  > `OperatingNotifier::send()` يزيل التكرار والقيم الفارغة، فلو كان المسؤول هو مدير المشروع نفسه يصله تنبيه واحد.

- [ ] **Step 5: الأمر والجدولة**

```php
<?php

namespace App\Console\Commands;

use App\Services\OperatingMilestones;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class SendMilestoneReminders extends Command
{
    protected $signature = 'operating:milestone-reminders';

    protected $description = 'Alert project managers and owners about milestones due soon or overdue, and about overdue deliverables';

    public function handle(OperatingMilestones $milestones): int
    {
        $sent = $milestones->sendReminders(CarbonImmutable::now());
        $this->info(sprintf('%d upcoming, %d overdue, %d deliverables.', $sent['upcoming'], $sent['overdue'], $sent['deliverables']));

        return self::SUCCESS;
    }
}
```

  `routes/console.php`: `Schedule::command('operating:milestone-reminders')->dailyAt('08:00')->withoutOverlapping();`

- [ ] **Step 6: اللوحة**
  - `ProjectController` (احقن `OperatingMilestones $milestones`):

```php
    public function milestones(Request $request): Response
    {
        $user = $request->user();
        abort_unless($this->access->can($user, 'milestones'), 403);
        $filters = $request->validate(['project' => 'nullable|integer', 'owner' => 'nullable|integer']);

        return response()->view('admin.operating.milestones-tracker', $this->milestones->tracker($user, $filters) + [
            'filters' => $filters,
            'projects' => $this->access->query($user, 'projects')->where('status', '!=', 'archived')->orderBy('title')->pluck('title', 'id'),
            'owners' => $this->access->can($user, 'employees') ? $this->access->query($user, 'employees')->where('status', 'active')->orderBy('title')->pluck('title', 'id') : collect(),
            'collections' => Cms::COLLECTIONS, 'catalog' => $this->catalog, 'access' => $this->access,
        ]);
    }
```

  - المسار (قبل `{type}`): `Route::get('/operations/milestones/tracker', [P::class, 'milestones']);`
  - `milestones-tracker.blade.php`:

```blade
@extends('admin.layout')
@section('title',__('متابعة المحطات والمخرجات'))
@section('content')
@php $labels=['overdue'=>__('متأخرة'),'due_soon'=>__('خلال 7 أيام'),'upcoming'=>__('قادمة'),'submitted'=>__('مقدَّمة بانتظار العميل'),'accepted'=>__('مقبولة آخر 30 يومًا')]; @endphp
<div class="page-heading"><div><span class="eyebrow">{{ $catalog->label(config('operating.groups.delivery.label')) }}</span><h1>{{ __('متابعة المحطات والمخرجات') }}</h1><p class="muted">{{ __('المحطات حسب الموعد، ونسبة تسليم مخرجاتها، والمخرجات المتأخرة. التنبيهات تصل لمدير المشروع والمسؤول يوميًا.') }}</p></div>@if($access->canCreate(auth()->user(),'milestones'))<a class="button" href="/admin/operations/milestones/create{{ ($filters['project'] ?? null) ? '?prefill[project_id]='.$filters['project'] : '' }}">+ {{ __('محطة جديدة') }}</a>@endif</div>
<div class="inline-stats card">@foreach($labels as $key=>$label)<span class="{{ $key==='overdue' && $counts['overdue'] ? 'danger' : '' }}">{{ $label }}: <b>{{ $counts[$key] }}</b></span>@endforeach<span class="{{ $counts['delayed_deliverables']?'danger':'' }}">{{ __('مخرجات متأخرة') }}: <b>{{ $counts['delayed_deliverables'] }}</b></span></div>
<form class="filters"><select name="project" aria-label="{{ __('المشروع') }}"><option value="">{{ __('كل المشاريع') }}</option>@foreach($projects as $id=>$name)<option value="{{ $id }}" @selected((string)($filters['project']??'')===(string)$id)>{{ $name }}</option>@endforeach</select>@if($owners->isNotEmpty())<select name="owner" aria-label="{{ __('المسؤول') }}"><option value="">{{ __('كل المسؤولين') }}</option>@foreach($owners as $id=>$name)<option value="{{ $id }}" @selected((string)($filters['owner']??'')===(string)$id)>{{ $name }}</option>@endforeach</select>@endif<button class="button secondary">{{ __('تصفية') }}</button></form>
@foreach($labels as $bucket=>$label)@if(${$bucket})<section class="card"><h2 class="{{ $bucket==='overdue'?'danger':'' }}">{{ $label }} <small class="muted">{{ count(${$bucket}) }}</small></h2><div class="table-card"><table><thead><tr><th>{{ __('المحطة') }}</th><th>{{ __('المشروع') }}</th><th>{{ __('المسؤول') }}</th><th>{{ __('الموعد') }}</th><th>{{ __('المخرجات') }}</th></tr></thead><tbody>
@foreach(${$bucket} as $row)<tr><td><a class="item-title" href="/admin/operations/record/{{ $row['id'] }}">{{ $row['title'] }}</a><small class="path">{{ $catalog->status($row['status']) }}</small></td><td>{{ $row['project'] ?? '—' }}</td><td>{{ $row['owner'] ?? __('بلا مسؤول') }}</td><td class="{{ $row['progress']['overdue']?'danger':'' }}">{{ $row['progress']['due'] ?? '—' }}</td><td><div class="progress" aria-hidden="true"><i style="width:{{ $row['progress']['percent'] ?? 0 }}%"></i></div><small>{{ $row['progress']['delivered'] }}/{{ $row['progress']['total'] }}@if($row['progress']['delayed']) · <span class="danger">{{ __('متأخر') }} {{ $row['progress']['delayed'] }}</span>@endif</small></td></tr>@endforeach
</tbody></table></div></section>@endif @endforeach
<section class="card"><h2 class="{{ $delayed_deliverables?'danger':'' }}">{{ __('مخرجات متأخرة') }}</h2>@forelse($delayed_deliverables as $item)<a class="operation-row" href="/admin/operations/record/{{ $item['id'] }}"><b>{{ $item['title'] }}</b><span>{{ $item['milestone'] ?? '—' }} · {{ $item['project'] ?? '—' }} · {{ $item['owner'] ?? __('بلا مسؤول') }} · <span class="danger">{{ $item['due'] }}</span></span></a>@empty<p class="muted">{{ __('لا مخرجات متأخرة.') }}</p>@endforelse</section>
@endsection
```

  - `layout.blade.php` بعد رابط لوحة المشاريع: `@if($access->can(auth()->user(),'milestones'))<a href="/admin/operations/milestones/tracker" class="{{ request()->is('admin/operations/milestones/tracker')?'active':'' }}">{{ __('متابعة المحطات') }}</a>@endif`.
  - `listing.blade.php`: `@if($type==='milestones')<a class="button secondary" href="/admin/operations/milestones/tracker">{{ __('متابعة المحطات') }}</a>@endif`.
  - `form.blade.php` في قسم `projectReport` بجانب زر السبرنتات: `@if($access->can(auth()->user(),'milestones'))<a class="button secondary" href="/admin/operations/milestones/tracker?project={{ $record->id }}">{{ __('المحطات والمخرجات') }}</a>@endif`.

- [ ] **Step 7:** الترجمات: `"متابعة المحطات والمخرجات": "Milestones & deliverables tracker"`, `"متأخرة": "Overdue"`, `"خلال 7 أيام": "Due within 7 days"`, `"قادمة": "Upcoming"`, `"مقدَّمة بانتظار العميل": "Submitted, awaiting client"`, `"مقبولة آخر 30 يومًا": "Accepted in the last 30 days"`, `"المحطات حسب الموعد، ونسبة تسليم مخرجاتها، والمخرجات المتأخرة. التنبيهات تصل لمدير المشروع والمسؤول يوميًا.": "Milestones by due date with deliverable completion and delayed deliverables. Project managers and owners are alerted daily."`, `"محطة جديدة": "New milestone"`, `"كل المشاريع": "All projects"`, `"كل المسؤولين": "All owners"`, `"المحطة": "Milestone"`, `"المخرجات": "Deliverables"`, `"بلا مسؤول": "No owner"`, `"متابعة المحطات": "Milestone tracker"`, `"المحطات والمخرجات": "Milestones & deliverables"` (وتحقق من وجود `"المشروع"` و`"متأخر"` و`"تصفية"`).
- [ ] **Step 8:** الاختبارات (`MilestoneTrackerTest` + `FollowUpsTest`) → PASS، ثم الحزمة، ثم pint، ثم commit: `feat(projects): milestone tracker with daily due-soon and overdue alerts to project managers`.

---

### Task 6: الحملات: القناة والعملة والمسؤول، وربط الـLeads، ومهام فريق التسويق (FIN-15، القرارات D7 وD8 وD13)

**Files:**
- Modify: `config/operating.php` (`campaigns`، و`opportunities.campaign_id`، وكيان `team_tasks` بعد `campaign_metrics`)
- Modify: `app/Services/OperatingWorkflow.php` (قواعد الحملات و`team_tasks`، وتعميم `limitAssignment`)
- Modify: `app/Services/OperatingAccess.php` (صلاحيات المصمم والمونتير، و`fields()`)
- Modify: `lang/en.json`
- Test: `tests/Feature/Operating/CampaignsTest.php`

**Interfaces:**
- Consumes: `notifyAssignees()` (T3) يغطي `team_tasks`، و`canCreate()` (T3).
- Produces:
  - `campaigns`: `channel` (select)، و`currency` (text)، و`assignee_id` (relation employees)، و`objective` بخيارات إضافية `engagement` و`sales`، و`platform` اختياري.
  - `opportunities.campaign_id` (relation campaigns).
  - كيان `team_tasks`: `campaign_id, assignee_id*, priority, due_date*, description*, deliverable_url, notes`، والحالات `backlog, in_progress, review, done, archived`، و`assigned: true`.
  - `OperatingWorkflow::limitAssignment(User $user, array $data, ?object $existing, string $managerRole = 'sales_manager'): void`.

- [ ] **Step 1: الاختبار الفاشل**

```php
<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CampaignsTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function campaign(array $data = []): array
    {
        return ['title' => 'Ramadan ERP', 'reason' => 'Plan', 'data' => array_merge(['objective' => 'leads', 'budget_cap' => 1000, 'start_date' => '2026-10-11', 'end_date' => '2026-10-31'], $data)];
    }

    private function campaignId(): int
    {
        return $this->operatingRecord('campaigns', 'running', ['channel' => 'facebook', 'objective' => 'leads', 'budget_cap' => 1000], 'Ramadan ERP');
    }

    public function test_campaign_dates_are_ordered_and_a_channel_is_needed_for_approval(): void
    {
        $buyer = $this->operatingUser('media_buyer');
        $this->actingAs($buyer)->post('/admin/operations/campaigns', $this->campaign(['end_date' => '2026-10-01']))->assertSessionHasErrors('workflow');
        $this->actingAs($buyer)->post('/admin/operations/campaigns', $this->campaign())->assertSessionHasNoErrors();

        $id = DB::table('operating_records')->where('type', 'campaigns')->value('id');
        $owner = $this->operatingUser('owner');
        $this->actingAs($owner)->post("/admin/operations/record/{$id}/transition", ['target' => 'approved', 'version' => 1, 'reason' => 'Go'])->assertSessionHasErrors('workflow');
        DB::table('operating_records')->where('id', $id)->update(['data->channel' => 'google']);
        $this->actingAs($owner)->post("/admin/operations/record/{$id}/transition", ['target' => 'approved', 'version' => 1, 'reason' => 'Go'])->assertSessionHasNoErrors();
    }

    public function test_a_lead_can_point_to_its_source_campaign(): void
    {
        $campaign = $this->campaignId();
        $this->actingAs($this->operatingUser('sales'))->post('/admin/operations/opportunities', ['title' => 'ERP lead', 'reason' => 'Inbound', 'data' => ['lead_company' => 'Nile Foods', 'lead_source' => 'facebook', 'campaign_id' => $campaign, 'need' => 'ERP', 'solution' => 'web', 'next_action' => 'Call', 'follow_up' => '2026-10-20']])->assertSessionHasNoErrors();

        $lead = DB::table('operating_records')->where('type', 'opportunities')->value('id');
        $this->assertTrue(DB::table('operating_links')->where('record_id', $lead)->where('field', 'campaign_id')->where('target_id', $campaign)->exists());
    }

    public function test_only_marketing_leads_create_and_assign_team_tasks(): void
    {
        $designer = $this->operatingUser('designer');
        $designerEmployee = $this->employeeFor($designer, ['department' => 'marketing']);
        $task = ['title' => 'Carousel design', 'reason' => 'Campaign brief', 'data' => ['campaign_id' => $this->campaignId(), 'assignee_id' => $designerEmployee, 'due_date' => '2026-10-20', 'description' => '5 slides', 'priority' => 'high']];

        $this->actingAs($designer)->post('/admin/operations/team_tasks', $task)->assertForbidden();
        $this->actingAs($this->operatingUser('media_buyer'))->post('/admin/operations/team_tasks', $task)->assertSessionHasErrors('workflow');
        $this->actingAs($this->operatingUser('marketing'))->post('/admin/operations/team_tasks', $task)->assertSessionHasNoErrors();
        $this->assertSame(1, $designer->notifications()->count());
    }

    public function test_assignees_see_and_move_only_their_own_tasks_until_review(): void
    {
        $designer = $this->operatingUser('designer');
        $mine = $this->operatingRecord('team_tasks', 'backlog', ['assignee_id' => $this->employeeFor($designer), 'due_date' => '2026-10-20', 'description' => '5 slides'], 'Mine');
        $this->operatingRecord('team_tasks', 'backlog', ['assignee_id' => $this->employeeFor($this->operatingUser('video_editor')), 'due_date' => '2026-10-20', 'description' => 'Reel'], 'Theirs');

        $this->assertSame(['Mine'], collect($this->actingAs($designer)->getJson('/admin/operations/team_tasks?format=json')->json('data'))->pluck('title')->all());

        $this->actingAs($designer)->post("/admin/operations/record/{$mine}", ['title' => 'Mine', 'version' => 1, 'reason' => 'Progress', 'data' => ['description' => 'Changed', 'notes' => 'Draft ready']])->assertSessionHasNoErrors();
        $this->assertSame('5 slides', $this->recordData($mine)['description']);
        $this->assertSame('Draft ready', $this->recordData($mine)['notes']);

        $this->actingAs($designer)->post("/admin/operations/record/{$mine}/transition", ['target' => 'review', 'version' => 2, 'reason' => 'Ready'])->assertSessionHasNoErrors();
        $this->actingAs($designer)->post("/admin/operations/record/{$mine}/transition", ['target' => 'done', 'version' => 3, 'reason' => 'Close'])->assertForbidden();
        $this->actingAs($this->operatingUser('marketing'))->post("/admin/operations/record/{$mine}/transition", ['target' => 'done', 'version' => 3, 'reason' => 'Approved'])->assertSessionHasNoErrors();
    }
}
```

- [ ] **Step 2:** → FAIL.

- [ ] **Step 3: الـconfig**
  - `campaigns.fields`:
    - `platform`: `"required": false`، و`label` يصبح `{ "ar": "تفاصيل المنصة / الحساب", "en": "Platform / account details" }`.
    - أضف بعد `platform`:

```json
        { "key": "channel", "label": { "ar": "القناة التسويقية", "en": "Marketing channel" }, "type": "select", "required": false, "options": {
          "facebook": { "ar": "فيسبوك", "en": "Facebook" }, "instagram": { "ar": "إنستجرام", "en": "Instagram" }, "google": { "ar": "جوجل", "en": "Google" },
          "tiktok": { "ar": "تيك توك", "en": "TikTok" }, "linkedin": { "ar": "لينكدإن", "en": "LinkedIn" }, "snapchat": { "ar": "سناب شات", "en": "Snapchat" },
          "youtube": { "ar": "يوتيوب", "en": "YouTube" }, "email": { "ar": "البريد الإلكتروني", "en": "Email" }, "website": { "ar": "الموقع / SEO", "en": "Website / SEO" }, "other": { "ar": "أخرى", "en": "Other" } } },
        { "key": "assignee_id", "label": { "ar": "مسؤول الحملة", "en": "Campaign owner" }, "type": "relation", "required": false, "entity": "employees" },
```

    - `objective.options` أضف: `"engagement": { "ar": "تفاعل", "en": "Engagement" }, "sales": { "ar": "مبيعات / تحويلات", "en": "Sales / conversions" }`.
    - بعد `spent`: `{ "key": "currency", "label": { "ar": "العملة", "en": "Currency" }, "type": "text", "required": false },`
  - `opportunities.fields` بعد `lead_source`: `{ "key": "campaign_id", "label": { "ar": "الحملة المصدر", "en": "Source campaign" }, "type": "relation", "required": false, "entity": "campaigns" },`
  - كيان جديد بعد `campaign_metrics`:

```json
    "team_tasks": {
      "label": { "ar": "مهام فريق التسويق", "en": "Marketing team tasks" },
      "group": "marketing",
      "pages": [],
      "fields": [
        { "key": "campaign_id", "label": { "ar": "الحملة", "en": "Campaign" }, "type": "relation", "required": false, "entity": "campaigns" },
        { "key": "assignee_id", "label": { "ar": "المسؤول", "en": "Assignee" }, "type": "relation", "required": true, "entity": "employees" },
        { "key": "priority", "label": { "ar": "الأولوية", "en": "Priority" }, "type": "select", "required": false, "options": {
          "low": { "ar": "منخفضة", "en": "Low" }, "medium": { "ar": "متوسطة", "en": "Medium" }, "high": { "ar": "عالية", "en": "High" }, "critical": { "ar": "حرجة", "en": "Critical" } } },
        { "key": "due_date", "label": { "ar": "الموعد", "en": "Due date" }, "type": "date", "required": true },
        { "key": "description", "label": { "ar": "المطلوب", "en": "What is needed" }, "type": "textarea", "required": true },
        { "key": "deliverable_url", "label": { "ar": "رابط التسليم", "en": "Delivery link" }, "type": "url", "required": false },
        { "key": "notes", "label": { "ar": "ملاحظات التنفيذ", "en": "Execution notes" }, "type": "textarea", "required": false }
      ],
      "statuses": ["backlog", "in_progress", "review", "done", "archived"],
      "assigned": true
    },
```

- [ ] **Step 4: `OperatingAccess`**
  - في `can()` استبدل سطر المصمم والمونتير:

```php
            if (in_array($user->role, ['designer', 'video_editor'], true) && ! in_array($type, ['team_tasks', $user->role === 'designer' ? 'creative_assets' : 'videos'], true)) {
                return false;
            }
```

  - في `fields()`:

```php
        if ($type === 'team_tasks' && ! in_array($user->role, ['owner', 'marketing', 'media_buyer'], true)) {
            $fields = array_filter($fields, fn (array $field): bool => in_array($field['key'], ['deliverable_url', 'notes'], true));
        }
```

  > لا يحتاج `query()` تعديلًا: `assigned: true` يحصر media_buyer والمصمم والمونتير في مهامهم، بينما marketing ضمن الأدوار المستثناة فيرى الكل.

- [ ] **Step 5: `OperatingWorkflow`**
  - عمّم `limitAssignment` (التوقيع الجديد والرسالة حسب المدير):

```php
    private function limitAssignment(User $user, array $data, ?object $existing, string $managerRole = 'sales_manager'): void
    {
        if (in_array($user->role, ['owner', $managerRole], true) || empty($data['assignee_id'])) {
            return;
        }
        $previous = $existing ? ($this->catalog->data($existing)['assignee_id'] ?? null) : null;
        if ((int) $data['assignee_id'] !== (int) $this->access->employeeId($user) && (int) $data['assignee_id'] !== (int) $previous) {
            $managerRole === 'marketing'
                ? $this->fail('الإسناد لموظف آخر من صلاحية مدير التسويق.', 'Only the marketing manager can assign this to someone else.')
                : $this->fail('الإسناد لموظف آخر من صلاحية مدير المبيعات.', 'Only a sales manager can assign this to someone else.');
        }
    }
```

  (حدّث الـdocblock: "Non-managers may keep or take ownership of their own records; assigning work to someone else is a manager decision.")
  - قواعد `save()`:

```php
        if ($type === 'campaigns' && ! empty($data['start_date']) && ! empty($data['end_date']) && $data['end_date'] < $data['start_date']) {
            $this->fail('نهاية الحملة قبل بدايتها.', 'The campaign ends before it starts.');
        }
        if ($type === 'team_tasks') {
            $this->limitAssignment($user, $data, $existing, 'marketing');
        }
```

  - `gate()` كتلة `campaigns`: أضف `channel` إلى `requireFields` في `approved` وفي `running`:
    - `$this->requireFields($data, ['budget_cap', 'channel']);`
    - `$this->requireFields($data, ['landing_url', 'asset_approval', 'tracking_test', 'sales_ready', 'channel']);`
  - `gate()` كتلة جديدة:

```php
        if ($record->type === 'team_tasks') {
            if ($target === 'done' && ! in_array($user->role, ['owner', 'marketing'], true) && (int) $record->owner_id !== $user->id) {
                abort(403);
            }
            if (in_array($target, ['review', 'done'], true) && empty($data['deliverable_url']) && empty($data['notes'])) {
                $this->fail('سجّل رابط التسليم أو ملاحظة التنفيذ قبل المراجعة.', 'Add the delivery link or an execution note before review.');
            }
        }
```

  > أثر معروف (يُذكر في رسالة الجروب): أي حفظ لحملة معتمدة أو جارية يعيدها لمسودة (قاعدة قائمة). الحقول الجديدة لا تغيّر هذا السلوك، لكن تعبئتها لحملة جارية تعني إعادة اعتماد. لذلك يُسجَّل الإنفاق اليومي كـ"نتائج إعلانات" (القرار D10).

- [ ] **Step 6:** الترجمات: لا نصوص views جديدة في هذا التاسك (التسميات من الـconfig، والرسائل ثنائية في `fail`).
- [ ] **Step 7:** الاختبارات → PASS، ثم الحزمة (`LeadsPipelineTest` و`FollowUpsTest` يستخدمان `limitAssignment`)، ثم pint، ثم commit: `feat(marketing): campaign channel, owner and currency, lead attribution and team tasks`.

---

### Task 7: نتائج الحملة داخل صفحتها (FIN-15 + أساس FIN-16، القرارات D9 وD10 وD11)

**Files:**
- Create: `app/Services/OperatingMarketing.php` (`stats`، `campaign`)
- Modify: `app/Http/Controllers/OperatingController.php` (`campaignReport`)
- Create: `resources/views/admin/operating/campaign-report.blade.php`
- Modify: `form.blade.php`، `lang/en.json`
- Test: `tests/Feature/Operating/CampaignStatsTest.php`

**Interfaces:**
- Produces:
  - `OperatingMarketing::QUALIFIED_STAGES = ['qualified', 'discovery', 'proposal', 'negotiation', 'won']`.
  - `OperatingMarketing::stats(object $campaign, ?string $from = null, ?string $to = null): array{currency: string, channel: ?string, budget_cap: float, spend: float, budget_used: ?float, impressions: int, clicks: int, ctr: ?float, leads: int, reported_leads: int, qualified: int, won: int, lost: int, revenue: float, reported_revenue: float, other_currency_won: int, cpl: ?float, conversion: ?float, roi: ?float}`.
  - `OperatingMarketing::campaign(User $user, object $campaign): array{stats: array, tasks: list<array{id: int, title: string, status: string, assignee: ?string, due: ?string, overdue: bool}>, task_counts: array{open: int, done: int, overdue: int}}`.

- [ ] **Step 1: الاختبار الفاشل**

```php
<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingMarketing;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CampaignStatsTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-15 10:00'));
    }

    private function campaign(array $data = []): int
    {
        return $this->operatingRecord('campaigns', 'running', array_merge(['channel' => 'facebook', 'objective' => 'leads', 'budget_cap' => 1000, 'currency' => 'USD', 'spent' => 999], $data), 'Ramadan ERP');
    }

    private function metric(int $campaign, string $date, array $data): int
    {
        return $this->operatingRecord('campaign_metrics', 'active', array_merge(['campaign_id' => $campaign, 'metric_date' => $date], $data), 'Day');
    }

    private function lead(int $campaign, string $status, array $data = []): int
    {
        return $this->operatingRecord('opportunities', $status, array_merge(['campaign_id' => $campaign, 'lead_company' => 'Co', 'lead_source' => 'facebook'], $data), 'Lead '.$status);
    }

    private function stats(int $campaign, ?string $from = null, ?string $to = null): array
    {
        return app(OperatingMarketing::class)->stats(DB::table('operating_records')->find($campaign), $from, $to);
    }

    public function test_stats_combine_reported_metrics_with_crm_leads_and_deals(): void
    {
        $campaign = $this->campaign();
        $this->metric($campaign, '2026-10-11', ['spent' => 200, 'impressions' => 10000, 'clicks' => 100, 'leads' => 6, 'revenue' => 900]);
        $this->metric($campaign, '2026-10-12', ['spent' => 300, 'impressions' => 10000, 'clicks' => 300, 'leads' => 4]);
        $this->lead($campaign, 'new');
        $this->lead($campaign, 'qualified');
        $this->lead($campaign, 'won', ['expected_value' => 2000, 'currency' => 'USD']);
        $this->lead($campaign, 'won', ['expected_value' => 500, 'currency' => 'EGP']);
        $this->lead($campaign, 'lost');

        $stats = $this->stats($campaign);

        $this->assertSame(500.0, $stats['spend']);
        $this->assertSame(50.0, $stats['budget_used']);
        $this->assertSame(2.0, $stats['ctr']);
        $this->assertSame(5, $stats['leads']);
        $this->assertSame(10, $stats['reported_leads']);
        $this->assertSame(3, $stats['qualified']);
        $this->assertSame(2, $stats['won']);
        $this->assertSame(2000.0, $stats['revenue']);
        $this->assertSame(1, $stats['other_currency_won']);
        $this->assertSame(900.0, $stats['reported_revenue']);
        $this->assertSame(100.0, $stats['cpl']);
        $this->assertSame(40.0, $stats['conversion']);
        $this->assertSame(300.0, $stats['roi']);
    }

    public function test_without_metrics_the_campaign_spend_is_used_and_empty_ratios_stay_null(): void
    {
        $stats = $this->stats($this->campaign(['spent' => 0]));

        $this->assertSame(0.0, $stats['spend']);
        $this->assertNull($stats['cpl']);
        $this->assertNull($stats['conversion']);
        $this->assertNull($stats['roi']);
        $this->assertNull($stats['ctr']);
        $this->assertSame(250.0, $this->stats($this->campaign(['spent' => 250]))['spend']);
    }

    public function test_a_period_filters_metrics_by_date_and_leads_by_creation(): void
    {
        $campaign = $this->campaign();
        $this->metric($campaign, '2026-09-30', ['spent' => 100]);
        $this->metric($campaign, '2026-10-05', ['spent' => 40]);
        $old = $this->lead($campaign, 'new');
        DB::table('operating_records')->where('id', $old)->update(['created_at' => '2026-09-20 10:00:00']);
        $this->lead($campaign, 'new');

        $stats = $this->stats($campaign, '2026-10-01', '2026-10-31');
        $this->assertSame(40.0, $stats['spend']);
        $this->assertSame(1, $stats['leads']);
    }

    public function test_the_campaign_page_shows_results_and_team_tasks(): void
    {
        $campaign = $this->campaign();
        $this->operatingRecord('team_tasks', 'in_progress', ['campaign_id' => $campaign, 'assignee_id' => $this->employeeFor($this->operatingUser('designer')), 'due_date' => '2026-10-10', 'description' => 'Slides'], 'Carousel design');

        $this->actingAs($this->operatingUser('marketing'))->get("/admin/operations/record/{$campaign}")->assertOk()->assertSee(__('نتائج الحملة'))->assertSee('Carousel design');
        $this->actingAs($this->operatingUser('developer'))->get("/admin/operations/record/{$campaign}")->assertForbidden();
    }
}
```

- [ ] **Step 2:** → FAIL.

- [ ] **Step 3: `OperatingMarketing`**

```php
<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OperatingMarketing
{
    public const QUALIFIED_STAGES = ['qualified', 'discovery', 'proposal', 'negotiation', 'won'];

    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access, private OperatingSales $sales) {}

    /**
     * Results for one campaign. Leads and deals are aggregates over every linked lead, including leads the viewer cannot open,
     * so marketing sees its outcomes without reading the sales pipeline. Revenue is booked won value in the campaign currency.
     * A period limits metrics by metric date and leads by creation date (a cohort view).
     *
     * @return array<string, mixed>
     */
    public function stats(object $campaign, ?string $from = null, ?string $to = null): array
    {
        $data = $this->catalog->data($campaign);
        $currency = $this->currency($data);
        $metrics = $this->linked('campaign_metrics', $campaign->id)->filter(fn (object $row): bool => $this->inPeriod($this->catalog->data($row)['metric_date'] ?? null, $from, $to));
        $sum = fn (string $key): float => (float) $metrics->sum(fn (object $row): float => (float) ($this->catalog->data($row)[$key] ?? 0));
        $spend = $metrics->isNotEmpty() || $from || $to ? $sum('spent') : (float) ($data['spent'] ?? 0);
        $leads = $this->linked('opportunities', $campaign->id)->filter(fn (object $row): bool => $this->inPeriod(substr((string) $row->created_at, 0, 10), $from, $to));
        $won = $leads->where('status', 'won');
        $sameCurrency = $won->filter(fn (object $row): bool => $this->currency($this->catalog->data($row)) === $currency);
        $revenue = (float) $sameCurrency->sum(fn (object $row): float => (float) ($this->catalog->data($row)['expected_value'] ?? 0));
        $impressions = (int) $sum('impressions');
        $clicks = (int) $sum('clicks');
        $count = $leads->count();
        $cap = (float) ($data['budget_cap'] ?? 0);

        return [
            'currency' => $currency,
            'channel' => $data['channel'] ?? null,
            'budget_cap' => $cap,
            'spend' => round($spend, 2),
            'budget_used' => $cap > 0 ? round($spend / $cap * 100, 1) : null,
            'impressions' => $impressions,
            'clicks' => $clicks,
            'ctr' => $impressions > 0 ? round($clicks / $impressions * 100, 2) : null,
            'leads' => $count,
            'reported_leads' => (int) $sum('leads'),
            'qualified' => $leads->whereIn('status', self::QUALIFIED_STAGES)->count(),
            'won' => $won->count(),
            'lost' => $leads->where('status', 'lost')->count(),
            'revenue' => round($revenue, 2),
            'reported_revenue' => round($sum('revenue'), 2),
            'other_currency_won' => $won->count() - $sameCurrency->count(),
            'cpl' => $count ? round($spend / $count, 2) : null,
            'conversion' => $count ? round($won->count() / $count * 100, 1) : null,
            'roi' => $spend > 0 ? round(($revenue - $spend) / $spend * 100, 1) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function campaign(User $user, object $campaign): array
    {
        $tasks = $this->access->can($user, 'team_tasks') ? $this->access->query($user, 'team_tasks')->where('status', '!=', 'archived')
            ->whereIn('id', DB::table('operating_links')->where('field', 'campaign_id')->where('target_id', $campaign->id)->select('record_id'))->get() : collect();
        $titles = $this->sales->titles($tasks->map(fn (object $task): mixed => $this->catalog->data($task)['assignee_id'] ?? null)->filter()->unique()->values()->all());
        $today = today()->toDateString();
        $rows = $tasks->map(fn (object $task): array => ['id' => $task->id, 'title' => $task->title, 'status' => $task->status, 'assignee' => $titles[($data = $this->catalog->data($task))['assignee_id'] ?? 0] ?? null, 'due' => $data['due_date'] ?? null, 'overdue' => $task->status !== 'done' && ($data['due_date'] ?? '9999') < $today])->sortBy('due')->values();

        return ['stats' => $this->stats($campaign), 'tasks' => $rows->all(), 'task_counts' => ['open' => $rows->where('status', '!=', 'done')->count(), 'done' => $rows->where('status', 'done')->count(), 'overdue' => $rows->where('overdue', true)->count()]];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function currency(array $data): string
    {
        return strtoupper(trim((string) ($data['currency'] ?? ''))) ?: 'USD';
    }

    /**
     * @return Collection<int, object>
     */
    private function linked(string $type, int $campaignId): Collection
    {
        return DB::table('operating_records')->where('type', $type)->where('status', '!=', 'archived')
            ->whereIn('id', DB::table('operating_links')->where('field', 'campaign_id')->where('target_id', $campaignId)->select('record_id'))->get();
    }

    private function inPeriod(?string $date, ?string $from, ?string $to): bool
    {
        if (! $from && ! $to) {
            return true;
        }

        return $date !== null && $date !== '' && (! $from || $date >= $from) && (! $to || $date <= $to);
    }
}
```

- [ ] **Step 4: الربط بالصفحة**
  - `OperatingController::form()`: `'campaignReport' => $record && $type === 'campaigns' ? app(OperatingMarketing::class)->campaign($request->user(), $record) : null`.
  - `form.blade.php` بعد لوحة المحطة: `@if($campaignReport)@include('admin.operating.campaign-report',['report'=>$campaignReport])@endif`.
  - `campaign-report.blade.php`:

```blade
@php $s=$report['stats']; $money=fn($v)=>$v===null?'—':number_format($v,2).' '.$s['currency']; $pct=fn($v)=>$v===null?'—':$v.'%'; @endphp
<section class="card"><h2>{{ __('نتائج الحملة') }}</h2><p class="muted">{{ __('الـLeads والصفقات من المبيعات المربوطة بالحملة. الأرقام المُبلَّغة يدويًا من نتائج الإعلانات بجانبها للمقارنة.') }}</p>
<div class="inline-stats"><span>{{ __('الإنفاق') }}: <b dir="ltr">{{ $money($s['spend']) }}</b> <small class="muted">/ <span dir="ltr">{{ $money($s['budget_cap']) }}</span> ({{ $pct($s['budget_used']) }})</small></span><span>{{ __('الظهور / النقرات') }}: <b dir="ltr">{{ number_format($s['impressions']) }} / {{ number_format($s['clicks']) }}</b> <small class="muted">CTR {{ $pct($s['ctr']) }}</small></span><span>{{ __('الـLeads') }}: <b>{{ $s['leads'] }}</b> <small class="muted">({{ __('مُبلَّغ') }} {{ $s['reported_leads'] }})</small></span><span>{{ __('مؤهلة') }}: <b>{{ $s['qualified'] }}</b></span><span>{{ __('صفقات مكسوبة') }}: <b>{{ $s['won'] }}</b> <small class="muted">({{ $pct($s['conversion']) }})</small></span><span>{{ __('الإيراد') }}: <b dir="ltr">{{ $money($s['revenue']) }}</b>@if($s['other_currency_won']) <small class="muted">+{{ $s['other_currency_won'] }} {{ __('بعملة أخرى') }}</small>@endif</span><span>CPL: <b dir="ltr">{{ $money($s['cpl']) }}</b></span><span class="{{ $s['roi']!==null && $s['roi']<0 ? 'danger' : '' }}">ROI: <b dir="ltr">{{ $pct($s['roi']) }}</b></span></div>
@if($s['budget_used']!==null)<div class="progress" role="progressbar" aria-label="{{ __('استهلاك الميزانية') }}" aria-valuenow="{{ min(100,$s['budget_used']) }}" aria-valuemin="0" aria-valuemax="100"><i style="width:{{ min(100,$s['budget_used']) }}%"></i></div>@endif
<div class="row-actions">@if($access->canCreate(auth()->user(),'campaign_metrics'))<a class="button secondary" href="/admin/operations/campaign_metrics/create?prefill[campaign_id]={{ $record->id }}">+ {{ __('تسجيل نتائج يوم') }}</a>@endif<a class="button secondary" href="/admin/operations/campaigns/performance">{{ __('أداء التسويق') }}</a></div>
<h3>{{ __('مهام الحملة') }} <small class="muted">{{ __('مفتوحة') }} {{ $report['task_counts']['open'] }} · {{ __('منجزة') }} {{ $report['task_counts']['done'] }}@if($report['task_counts']['overdue']) · <span class="danger">{{ __('متأخرة') }} {{ $report['task_counts']['overdue'] }}</span>@endif</small></h3>
@forelse($report['tasks'] as $task)<a class="operation-row" href="/admin/operations/record/{{ $task['id'] }}"><b>{{ $task['title'] }}</b><span>{{ $catalog->status($task['status']) }} · {{ $task['assignee'] ?? __('غير مسند') }} · <span class="{{ $task['overdue']?'danger':'' }}">{{ $task['due'] ?? '—' }}</span></span></a>@empty<p class="muted">{{ __('لا مهام لهذه الحملة بعد.') }}</p>@endforelse
@if($access->canCreate(auth()->user(),'team_tasks'))<a class="button secondary" href="/admin/operations/team_tasks/create?prefill[campaign_id]={{ $record->id }}">+ {{ __('إسناد مهمة للفريق') }}</a>@endif</section>
```

- [ ] **Step 5:** الترجمات: `"نتائج الحملة": "Campaign results"`, `"الـLeads والصفقات من المبيعات المربوطة بالحملة. الأرقام المُبلَّغة يدويًا من نتائج الإعلانات بجانبها للمقارنة.": "Leads and deals come from sales records linked to the campaign; manually reported ad metrics are shown beside them for comparison."`, `"الإنفاق": "Spend"`, `"الظهور / النقرات": "Impressions / clicks"`, `"الـLeads": "Leads"`, `"مُبلَّغ": "reported"`, `"مؤهلة": "Qualified"`, `"صفقات مكسوبة": "Won deals"`, `"الإيراد": "Revenue"`, `"بعملة أخرى": "in another currency"`, `"استهلاك الميزانية": "Budget used"`, `"تسجيل نتائج يوم": "Log daily results"`, `"أداء التسويق": "Marketing performance"`, `"مهام الحملة": "Campaign tasks"`, `"مفتوحة": "Open"`, `"منجزة": "Done"`, `"لا مهام لهذه الحملة بعد.": "No tasks for this campaign yet."`, `"إسناد مهمة للفريق": "Assign a team task"`.
- [ ] **Step 6:** الاختبارات → PASS، ثم الحزمة، ثم pint، ثم commit: `feat(marketing): campaign results with spend, leads, conversions, revenue, CPL and ROI`.

---

### Task 8: تقرير أداء التسويق ومقارنة القنوات + CSV (FIN-16، القراران D11 وD12)

**Files:**
- Modify: `app/Services/OperatingMarketing.php` (`performance`، `aggregate`)
- Create: `app/Http/Controllers/MarketingController.php`
- Create: `resources/views/admin/operating/marketing-performance.blade.php`
- Modify: `routes/admin.php`، `layout.blade.php`، `listing.blade.php`، `lang/en.json`
- Test: `tests/Feature/Operating/MarketingPerformanceTest.php`

**Interfaces:**
- Consumes: `OperatingMarketing::stats()` (T7).
- Produces:
  - `OperatingMarketing::performance(User $user, array{from?: ?string, to?: ?string, channel?: ?string} $filters = []): array{from: string, to: string, rows: list<array>, totals: list<array>, channels: list<array>, sources: list<array>, best: ?array, worst: ?array}`.
  - aggregate row: `array{campaigns: int, spend: float, impressions: int, clicks: int, leads: int, won: int, revenue: float, cpl: ?float, conversion: ?float, roi: ?float}` (+ `currency`، و`channel` في القنوات).
  - source row: `array{source: string, leads: int, won: int, conversion: ?float, won_value: array<string, float>}`.

- [ ] **Step 1: الاختبار الفاشل**

```php
<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingMarketing;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class MarketingPerformanceTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-15 10:00'));
    }

    private function campaign(string $title, string $channel, float $spend, int $leads, int $won, float $value, string $currency = 'USD'): int
    {
        $id = $this->operatingRecord('campaigns', 'running', ['channel' => $channel, 'objective' => 'leads', 'budget_cap' => 5000, 'currency' => $currency], $title);
        $this->operatingRecord('campaign_metrics', 'active', ['campaign_id' => $id, 'metric_date' => '2026-10-05', 'spent' => $spend], 'Day');
        for ($i = 0; $i < $leads; $i++) {
            $this->operatingRecord('opportunities', $i < $won ? 'won' : 'new', ['campaign_id' => $id, 'lead_company' => 'Co', 'lead_source' => $channel, 'expected_value' => $i < $won ? $value : null, 'currency' => $currency], 'Lead');
        }

        return $id;
    }

    public function test_channels_are_compared_and_ranked_by_roi(): void
    {
        $this->campaign('FB A', 'facebook', 100, 4, 1, 300);
        $this->campaign('FB B', 'facebook', 100, 1, 0, 0);
        $this->campaign('Search', 'google', 200, 2, 1, 1000);

        $report = app(OperatingMarketing::class)->performance($this->operatingUser('marketing'));

        $facebook = collect($report['channels'])->firstWhere('channel', 'facebook');
        $this->assertSame(200.0, $facebook['spend']);
        $this->assertSame(5, $facebook['leads']);
        $this->assertSame(40.0, $facebook['cpl']);
        $this->assertSame(50.0, $facebook['roi']);
        $this->assertSame('google', $report['best']['channel']);
        $this->assertSame(400.0, $report['best']['roi']);
        $this->assertSame('facebook', $report['worst']['channel']);
        $this->assertSame(['2026-10-01', '2026-10-15'], [$report['from'], $report['to']]);
    }

    public function test_totals_never_mix_currencies(): void
    {
        $this->campaign('USD', 'facebook', 100, 1, 1, 500);
        $this->campaign('EGP', 'facebook', 5000, 1, 1, 20000, 'EGP');

        $totals = collect(app(OperatingMarketing::class)->performance($this->operatingUser('owner'))['totals'])->keyBy('currency');

        $this->assertSame(100.0, $totals['USD']['spend']);
        $this->assertSame(5000.0, $totals['EGP']['spend']);
        $this->assertSame(300.0, $totals['EGP']['roi']);
    }

    public function test_sources_count_every_lead_created_in_the_period(): void
    {
        $this->operatingRecord('opportunities', 'won', ['lead_company' => 'A', 'lead_source' => 'referral', 'expected_value' => 800, 'currency' => 'USD'], 'Referral lead');
        $this->operatingRecord('opportunities', 'new', ['lead_company' => 'B', 'lead_source' => 'referral'], 'Referral lead 2');

        $referral = collect(app(OperatingMarketing::class)->performance($this->operatingUser('marketing'))['sources'])->firstWhere('source', 'referral');

        $this->assertSame(2, $referral['leads']);
        $this->assertSame(50.0, $referral['conversion']);
        $this->assertSame(['USD' => 800.0], $referral['won_value']);
    }

    public function test_the_report_page_csv_and_access(): void
    {
        $this->campaign('Ramadan ERP', 'facebook', 100, 2, 1, 300);
        $marketing = $this->operatingUser('marketing');

        $this->actingAs($marketing)->get('/admin/operations/campaigns/performance')->assertOk()->assertSee('Ramadan ERP')->assertSee(__('مقارنة القنوات'));
        $csv = $this->actingAs($marketing)->get('/admin/operations/campaigns/performance?format=csv');
        $csv->assertOk();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv->streamedContent());
        $this->assertStringContainsString('Ramadan ERP', $csv->streamedContent());

        $this->actingAs($marketing)->get('/admin/operations/campaigns/performance?from=2026-10-10&to=2026-10-01')->assertSessionHasErrors('to');
        $this->actingAs($this->operatingUser('developer'))->get('/admin/operations/campaigns/performance')->assertForbidden();
    }
}
```

- [ ] **Step 2:** → FAIL.

- [ ] **Step 3: `OperatingMarketing`** (أضف):

```php
    /**
     * Campaigns with spend, leads or a running state in the period; totals and channel groups are per currency, never converted.
     *
     * @param  array{from?: ?string, to?: ?string, channel?: ?string}  $filters
     * @return array<string, mixed>
     */
    public function performance(User $user, array $filters = []): array
    {
        $from = ($filters['from'] ?? null) ?: today()->startOfMonth()->toDateString();
        $to = ($filters['to'] ?? null) ?: today()->toDateString();
        $rows = collect();
        foreach ($this->access->query($user, 'campaigns')->where('status', '!=', 'archived')->when($filters['channel'] ?? null, fn ($query, $channel) => $query->where('data->channel', $channel))->orderBy('title')->get() as $campaign) {
            $stats = $this->stats($campaign, $from, $to);
            if ($stats['spend'] > 0 || $stats['leads'] || $stats['reported_leads'] || $campaign->status === 'running') {
                $rows->push(['id' => $campaign->id, 'title' => $campaign->title, 'status' => $campaign->status] + $stats);
            }
        }
        $channels = $rows->groupBy(fn (array $row): string => ($row['channel'] ?? 'unspecified').'|'.$row['currency'])
            ->map(fn (Collection $group, string $key): array => array_combine(['channel', 'currency'], explode('|', $key)) + $this->aggregate($group))
            ->sortByDesc('spend')->values();
        $ranked = $channels->filter(fn (array $channel): bool => $channel['roi'] !== null)->sortBy('roi')->values();
        $leads = DB::table('operating_records')->where('type', 'opportunities')->where('status', '!=', 'archived')->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])->get();
        $sources = $leads->groupBy(fn (object $lead): string => $this->catalog->data($lead)['lead_source'] ?? 'unspecified')
            ->map(function (Collection $group, string $source): array {
                $won = $group->where('status', 'won');
                $value = [];
                foreach ($won as $lead) {
                    $data = $this->catalog->data($lead);
                    $value[$this->currency($data)] = round(($value[$this->currency($data)] ?? 0) + (float) ($data['expected_value'] ?? 0), 2);
                }

                return ['source' => $source, 'leads' => $group->count(), 'won' => $won->count(), 'conversion' => round($won->count() / $group->count() * 100, 1), 'won_value' => $value];
            })->sortByDesc('leads')->values();

        return [
            'from' => $from,
            'to' => $to,
            'rows' => $rows->all(),
            'totals' => $rows->groupBy('currency')->map(fn (Collection $group, string $currency): array => ['currency' => $currency] + $this->aggregate($group))->values()->all(),
            'channels' => $channels->all(),
            'sources' => $sources->all(),
            'best' => $ranked->count() > 1 ? $ranked->last() : null,
            'worst' => $ranked->count() > 1 ? $ranked->first() : null,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{campaigns: int, spend: float, impressions: int, clicks: int, leads: int, won: int, revenue: float, cpl: ?float, conversion: ?float, roi: ?float}
     */
    private function aggregate(Collection $rows): array
    {
        $spend = round((float) $rows->sum('spend'), 2);
        $leads = (int) $rows->sum('leads');
        $won = (int) $rows->sum('won');
        $revenue = round((float) $rows->sum('revenue'), 2);

        return ['campaigns' => $rows->count(), 'spend' => $spend, 'impressions' => (int) $rows->sum('impressions'), 'clicks' => (int) $rows->sum('clicks'), 'leads' => $leads, 'won' => $won, 'revenue' => $revenue, 'cpl' => $leads ? round($spend / $leads, 2) : null, 'conversion' => $leads ? round($won / $leads * 100, 1) : null, 'roi' => $spend > 0 ? round(($revenue - $spend) / $spend * 100, 1) : null];
    }
```

  > `best`/`worst` يُعرضان فقط عند وجود قناتين أو أكثر لهما ROI (مقارنة قناة بنفسها بلا معنى). وفي الاختبار الأول ROI جوجل (1000−200)/200 = 400، وفيسبوك (300−200)/200 = 50.

- [ ] **Step 4: الكنترولر**

```php
<?php

namespace App\Http\Controllers;

use App\Services\Cms;
use App\Services\OperatingAccess;
use App\Services\OperatingCatalog;
use App\Services\OperatingMarketing;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MarketingController extends Controller
{
    public function __construct(private OperatingMarketing $marketing, private OperatingAccess $access, private OperatingCatalog $catalog) {}

    public function performance(Request $request): Response
    {
        abort_unless($this->access->can($request->user(), 'campaigns'), 403);
        $channels = collect($this->catalog->entity('campaigns')['fields'])->firstWhere('key', 'channel')['options'];
        $input = $request->validate(['from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from', 'channel' => 'nullable|in:'.implode(',', array_keys($channels)), 'format' => 'nullable|in:csv']);
        $report = $this->marketing->performance($request->user(), $input);
        if (($input['format'] ?? null) === 'csv') {
            return response()->streamDownload(function () use ($report): void {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, ['Campaign', 'Status', 'Channel', 'Currency', 'Spend', 'Impressions', 'Clicks', 'CTR %', 'Leads (CRM)', 'Leads (reported)', 'Qualified', 'Won', 'Revenue', 'CPL', 'Conversion %', 'ROI %'], ',', '"', '');
                foreach ($report['rows'] as $row) {
                    fputcsv($out, [$row['title'], $row['status'], $row['channel'] ?? '', $row['currency'], $row['spend'], $row['impressions'], $row['clicks'], $row['ctr'] ?? '', $row['leads'], $row['reported_leads'], $row['qualified'], $row['won'], $row['revenue'], $row['cpl'] ?? '', $row['conversion'] ?? '', $row['roi'] ?? ''], ',', '"', '');
                }
                fclose($out);
            }, 'marketing-performance-'.$report['from'].'-to-'.$report['to'].'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return response()->view('admin.operating.marketing-performance', $report + ['filters' => $input, 'channelOptions' => $channels, 'sourceOptions' => collect($this->catalog->entity('opportunities')['fields'])->firstWhere('key', 'lead_source')['options'], 'collections' => Cms::COLLECTIONS, 'catalog' => $this->catalog, 'access' => $this->access]);
    }
}
```

  > قارن مع تصدير CSV الموجود في `AttendanceController::monthly` (BOM ونمط `fputcsv`)، ووحّد الأسلوب لو كان مختلفًا. `fputcsv` بمعامل escape صريح `''` يتجنب تحذير الإهمال في PHP 8.4.

  - المسار (قبل `{type}`)، مع `use App\Http\Controllers\MarketingController as M;`: `Route::get('/operations/campaigns/performance', [M::class, 'performance']);`

- [ ] **Step 5: الـview** `marketing-performance.blade.php`:

```blade
@extends('admin.layout')
@section('title',__('أداء التسويق'))
@section('content')
@php
$label=fn($options,$key)=>$key && isset($options[$key]) ? $catalog->label($options[$key]) : __('غير محدد');
$money=fn($v,$c)=>$v===null?'—':number_format($v,2).' '.$c;
$pct=fn($v)=>$v===null?'—':$v.'%';
@endphp
<div class="page-heading"><div><span class="eyebrow">{{ $catalog->label(config('operating.groups.advertising.label')) }}</span><h1>{{ __('أداء التسويق') }}</h1><p class="muted">{{ __('الإنفاق والـLeads والتحويلات والإيراد والعائد لكل حملة وقناة. الأرقام لكل عملة على حدة.') }}</p></div><div class="row-actions"><a class="button secondary" href="{{ request()->fullUrlWithQuery(['format'=>'csv']) }}">{{ __('تصدير CSV') }}</a></div></div>
<form class="filters"><label>{{ __('من') }} <input type="date" name="from" value="{{ $from }}"></label><label>{{ __('إلى') }} <input type="date" name="to" value="{{ $to }}"></label><select name="channel" aria-label="{{ __('القناة') }}"><option value="">{{ __('كل القنوات') }}</option>@foreach($channelOptions as $key=>$option)<option value="{{ $key }}" @selected(($filters['channel']??'')===$key)>{{ $catalog->label($option) }}</option>@endforeach</select><button class="button secondary">{{ __('تصفية') }}</button></form>
@forelse($totals as $total)<div class="inline-stats card"><b dir="ltr">{{ $total['currency'] }}</b><span>{{ __('الإنفاق') }}: <b dir="ltr">{{ $money($total['spend'],$total['currency']) }}</b></span><span>{{ __('الـLeads') }}: <b>{{ $total['leads'] }}</b></span><span>CPL: <b dir="ltr">{{ $money($total['cpl'],$total['currency']) }}</b></span><span>{{ __('التحويلات') }}: <b>{{ $total['won'] }}</b> <small class="muted">({{ $pct($total['conversion']) }})</small></span><span>{{ __('الإيراد') }}: <b dir="ltr">{{ $money($total['revenue'],$total['currency']) }}</b></span><span class="{{ $total['roi']!==null && $total['roi']<0 ? 'danger' : '' }}">ROI: <b dir="ltr">{{ $pct($total['roi']) }}</b></span></div>@empty<div class="card"><p class="muted">{{ __('لا نشاط تسويقي في هذه الفترة.') }}</p></div>@endforelse
@if($best && $worst)<div class="inline-stats card"><span>{{ __('أفضل قناة') }}: <b>{{ $label($channelOptions,$best['channel']) }}</b> <small dir="ltr">ROI {{ $pct($best['roi']) }}</small></span><span>{{ __('أضعف قناة') }}: <b>{{ $label($channelOptions,$worst['channel']) }}</b> <small dir="ltr">ROI {{ $pct($worst['roi']) }}</small></span></div>@endif
<section class="card"><h2>{{ __('مقارنة القنوات') }}</h2><div class="table-card"><table><thead><tr><th>{{ __('القناة') }}</th><th>{{ __('حملات') }}</th><th>{{ __('الإنفاق') }}</th><th>{{ __('الـLeads') }}</th><th>CPL</th><th>{{ __('التحويلات') }}</th><th>{{ __('الإيراد') }}</th><th>ROI</th></tr></thead><tbody>
@forelse($channels as $row)<tr><td>{{ $label($channelOptions,$row['channel']) }}</td><td>{{ $row['campaigns'] }}</td><td dir="ltr">{{ $money($row['spend'],$row['currency']) }}</td><td>{{ $row['leads'] }}</td><td dir="ltr">{{ $money($row['cpl'],$row['currency']) }}</td><td>{{ $row['won'] }} <small class="muted">{{ $pct($row['conversion']) }}</small></td><td dir="ltr">{{ $money($row['revenue'],$row['currency']) }}</td><td dir="ltr" class="{{ $row['roi']!==null && $row['roi']<0 ? 'danger' : '' }}">{{ $pct($row['roi']) }}</td></tr>@empty<tr><td colspan="8"><div class="empty-state">{{ __('لا شيء هنا.') }}</div></td></tr>@endforelse</tbody></table></div></section>
<section class="card"><h2>{{ __('الحملات') }}</h2><div class="table-card"><table><thead><tr><th>{{ __('الحملة') }}</th><th>{{ __('القناة') }}</th><th>{{ __('الإنفاق') }}</th><th>CTR</th><th>{{ __('الـLeads') }}</th><th>{{ __('مؤهلة') }}</th><th>{{ __('التحويلات') }}</th><th>{{ __('الإيراد') }}</th><th>CPL</th><th>ROI</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td><a class="item-title" href="/admin/operations/record/{{ $row['id'] }}">{{ $row['title'] }}</a><small class="path">{{ $catalog->status($row['status']) }}</small></td><td>{{ $label($channelOptions,$row['channel']) }}</td><td dir="ltr">{{ $money($row['spend'],$row['currency']) }}</td><td dir="ltr">{{ $pct($row['ctr']) }}</td><td>{{ $row['leads'] }} <small class="muted">({{ $row['reported_leads'] }})</small></td><td>{{ $row['qualified'] }}</td><td>{{ $row['won'] }} <small class="muted">{{ $pct($row['conversion']) }}</small></td><td dir="ltr">{{ $money($row['revenue'],$row['currency']) }}</td><td dir="ltr">{{ $money($row['cpl'],$row['currency']) }}</td><td dir="ltr" class="{{ $row['roi']!==null && $row['roi']<0 ? 'danger' : '' }}">{{ $pct($row['roi']) }}</td></tr>@empty<tr><td colspan="10"><div class="empty-state">{{ __('لا شيء هنا.') }}</div></td></tr>@endforelse</tbody></table></div><p class="help">{{ __('الـLeads من المبيعات (بين قوسين: المُبلَّغ يدويًا). الإيراد = قيمة الصفقات المكسوبة بعملة الحملة.') }}</p></section>
<section class="card"><h2>{{ __('مصادر الـLeads') }}</h2><div class="table-card"><table><thead><tr><th>{{ __('المصدر') }}</th><th>{{ __('الـLeads') }}</th><th>{{ __('التحويلات') }}</th><th>{{ __('قيمة المكسوب') }}</th></tr></thead><tbody>
@forelse($sources as $row)<tr><td>{{ $label($sourceOptions,$row['source']) }}</td><td>{{ $row['leads'] }}</td><td>{{ $row['won'] }} <small class="muted">{{ $pct($row['conversion']) }}</small></td><td dir="ltr">@forelse($row['won_value'] as $currency=>$amount){{ $money($amount,$currency) }} @empty — @endforelse</td></tr>@empty<tr><td colspan="4"><div class="empty-state">{{ __('لا شيء هنا.') }}</div></td></tr>@endforelse</tbody></table></div></section>
@endsection
```

  - `layout.blade.php`: `@if($access->can(auth()->user(),'campaigns'))<a href="/admin/operations/campaigns/performance" class="{{ request()->is('admin/operations/campaigns/performance')?'active':'' }}">{{ __('أداء التسويق') }}</a>@endif`.
  - `listing.blade.php`: `@if(in_array($type,['campaigns','campaign_metrics'],true))<a class="button secondary" href="/admin/operations/campaigns/performance">{{ __('أداء التسويق') }}</a>@endif`.
  - **قبل كتابة جداول أو رسوم إضافية:** لو قررت إضافة رسم (مثل أعمدة إنفاق القنوات)، استدعِ skill `dataviz` أولًا. الخطة الحالية جداول فقط (YAGNI).

- [ ] **Step 6:** الترجمات: `"الإنفاق والـLeads والتحويلات والإيراد والعائد لكل حملة وقناة. الأرقام لكل عملة على حدة.": "Spend, leads, conversions, revenue and return per campaign and channel. Figures are per currency."`, `"تصدير CSV": "Export CSV"`, `"من": "From"`, `"إلى": "To"`, `"القناة": "Channel"`, `"كل القنوات": "All channels"`, `"التحويلات": "Conversions"`, `"لا نشاط تسويقي في هذه الفترة.": "No marketing activity in this period."`, `"أفضل قناة": "Best channel"`, `"أضعف قناة": "Weakest channel"`, `"مقارنة القنوات": "Channel comparison"`, `"حملات": "Campaigns"`, `"الحملات": "Campaigns"`, `"الحملة": "Campaign"`, `"غير محدد": "Unspecified"`, `"الـLeads من المبيعات (بين قوسين: المُبلَّغ يدويًا). الإيراد = قيمة الصفقات المكسوبة بعملة الحملة.": "Leads come from sales (manually reported in brackets). Revenue is the value of won deals in the campaign currency."`, `"مصادر الـLeads": "Lead sources"`, `"المصدر": "Source"`, `"قيمة المكسوب": "Won value"`. (تحقق من تعارض `"من"` و`"إلى"`: موجودان كتسميات في الـconfig، وليسا مفاتيح `__()`؛ لو وُجدا في `en.json` بترجمة مختلفة فاستخدم الموجود.)
- [ ] **Step 7:** الاختبارات → PASS، ثم الحزمة، ثم pint، ثم commit: `feat(marketing): marketing performance report with channel comparison, lead sources and CSV`.

---

### Task 9: التحقق الكامل (اختبارات + مراجعة + تشغيل محلي حقيقي)

- [ ] **Step 1:** `$PHP artisan test --compact` → كل الاختبارات ناجحة (74 قديمة + الجديدة، والمتوقع نحو 105). أي فشل: استخدم skill `systematic-debugging`.
- [ ] **Step 2:** `$PHP vendor/bin/pint --format agent` ثم `git diff --stat` للتأكد أن pint لم يلمس ملفات خارج النطاق.
- [ ] **Step 3: سلامة الـconfig والمسارات:** `$PHP artisan tinker --execute 'echo count(config("operating.entities"));'` (يجب ألا ينهار الـJSON، والمتوقع زيادة 3 كيانات)، و`$PHP artisan route:list --path=operations` (المسارات الجديدة قبل `{type}`)، و`$PHP artisan schedule:list` (يظهر `operating:milestone-reminders` يوميًا 08:00).
- [ ] **Step 4: مراجعة الكود:** skill `code-review` على الفرع (أو agent `pr-review-toolkit:code-reviewer`)، ثم أصلح الملاحظات المؤكدة مع اختبار لكل إصلاح سلوكي.
- [ ] **Step 5: التشغيل المحلي على PostgreSQL:**
  1. Postgres يعمل. `$PHP artisan migrate:status` (لا شيء معلق).
  2. `$PHP artisan operating:normalize-task-priorities --dry-run` ثم بدون `--dry-run` على `findo_local` (تجربة الأمر على pgsql قبل السيرفر).
  3. `$PHP artisan config:clear && $PHP artisan view:clear`، ثم `$PHP artisan serve --port=8080` في الخلفية.
  4. حسابات QA (القرار D15): أنشئ الناقص فقط بعد التحقق من `config('app.env') === 'local'` و`config('database.connections.pgsql.database') === 'findo_local'`، مع سجلات `employees` نشطة مرتبطة.
- [ ] **Step 6: سيناريوهات المتصفح (Playwright MCP؛ لو فشل الاتصال فـchrome-devtools MCP)** مع screenshot لكل خطوة مهمة، بالعربية ثم الإنجليزية لصفحة واحدة على الأقل:
  1. **FIN-12** (qa-techlead): مشروع في التنفيذ → "الباكلوج والسبرنتات" → سبرنت جديد (تاريخ نهاية قبل البداية يُرفض) → مهمتان بأولويات مختلفة ومسؤول ومختبر (يظهر إشعار عند qa-developer وqa-qa) → نقلهما للسبرنت → تفعيل السبرنت → التقدم والأيام والمهام المتأخرة → إكمال السبرنت مع مهمة غير منجزة ترجع للباكلوج بعلامة "مرحّلة".
  2. **FIN-14** (qa-delivery): محطة بلا مسؤول → in_progress يُرفض → مسؤول → مخرجان → تقديم المحطة يُرفض → تسليم المخرجين برابط → تقديم → قبول. ثم محطة بموعد بعد يومين وأخرى متأخرة → `$PHP artisan operating:milestone-reminders` → الإشعارات تظهر لمدير المشروع → لوحة "متابعة المحطات" بالفلتر.
  3. **FIN-15** (qa-marketing): حملة بقناة ومسؤول وعملة → إسناد مهمة لـqa-designer (يصله إشعار) → qa-designer يرى مهمته فقط ويضيف رابطًا وينقلها لمراجعة ولا يستطيع "تم" → marketing يغلقها. qa-mediabuyer يسجل نتائج يومين. qa-sales ينشئ lead مربوطًا بالحملة ويكسبه بقيمة. صفحة الحملة تعرض CPL وROI ومهامها.
  4. **FIN-16** (qa-marketing ثم owner): "أداء التسويق" بالفترة والقناة → مقارنة القنوات وأفضل/أضعف قناة → مصادر الـLeads → تصدير CSV يُفتح في Excel بالعربية سليمة.
  5. عرض الموبايل (375px) للوحة السبرنت ولوحة المحطات وتقرير الأداء: لا تمرير أفقي للصفحة (الجداول تمرر داخل كاردها).
  6. `list_console_messages`: لا أخطاء JS.
- [ ] **Step 7:** راقب `storage/logs/laravel.log` أثناء السيناريوهات (خصوصًا استعلامات `data->x` بأرقام وتواريخ على pgsql).
- [ ] **Step 8:** أوقف السيرفر المحلي. commit لأي إصلاحات: `fix(…): …`.
- [ ] **Step 9:** skill `verification-before-completion` قبل إعلان الانتهاء.

---

### Task 10: الدمج والرفع والنشر (بدون فقد أي تعديل على السيرفر)

- [ ] **Step 1: اسحب أي شغل من السيرفر أولًا:** `bin/server-status` (قراءة فقط). لو ظهرت فروقات: `bin/server-pull` (يسجلها على `production` ويدمجها في فرعك)، وحل أي تعارض يدويًا مع **الحفاظ على تعديلات السيرفر**، ثم أعد `$PHP artisan test --compact`.
- [ ] **Step 2:** `git checkout main && git pull --ff-only origin main` ثم `git merge --ff-only feature/erp-delivery-marketing` (القرار D16). لو رفض ff-only بسبب سحب السيرفر: `git rebase main` على الفرع، ثم الاختبارات، ثم الدمج.
- [ ] **Step 3:** `git push origin main` و`git push origin feature/erp-delivery-marketing`.
- [ ] **Step 4:** تأكد أنه لا migrations: `git diff --stat origin/production..main -- database/migrations` (المتوقع فارغ).
- [ ] **Step 5:** `bin/server-deploy --dry-run` → كل الفحوصات تمر.
- [ ] **Step 6:** `bin/server-deploy` (يرفض تلقائيًا لو على السيرفر تعديلات غير مسحوبة).
- [ ] **Step 7: ترحيل الأولويات على الإنتاج** (مرة واحدة، مباشرة بعد النشر):
  - `ssh findo "cd /home/findogroup.com/app && /usr/local/lsws/lsphp84/bin/php artisan operating:normalize-task-priorities --dry-run"`: راجع القائمة. لو كانت فيها قيمة شائعة غير معروفة تحوّلت لـmedium خطأً، أضفها للـMAP ثم commit وانشر من جديد قبل التنفيذ.
  - ثم بدون `--dry-run`.
- [ ] **Step 8: تحقق بعد النشر:**
  - `curl -sI https://findogroup.com | head -1` → 200.
  - `ssh findo "cd /home/findogroup.com/app && /usr/local/lsws/lsphp84/bin/php artisan route:list --path=operations/campaigns"` (يظهر `performance`)، و`--path=operations/projects` (يظهر `sprints`).
  - `ssh findo "cd /home/findogroup.com/app && /usr/local/lsws/lsphp84/bin/php artisan schedule:list"` (يظهر `operating:milestone-reminders`).
  - `ssh findo "cd /home/findogroup.com/app && /usr/local/lsws/lsphp84/bin/php artisan operating:milestone-reminders"` (تشغيل يدوي آمن يطبع الأعداد).
- [ ] **Step 9:** `git status -sb` و`git log origin/production -1`: لو لم يدفع السكربت `production` والـtags، فشغّل `git push origin production --tags`.

---

### Task 11: رسالتا الجروب

- [ ] **الرسالة 1 (ما تم):** plain text جاهزة للنسخ، بالعربية، بلا Markdown (لا `**` ولا جداول ولا `#`). لكل تاسك (FIN-12 وFIN-14 وFIN-15 وFIN-16) ما تم بلغة المستخدم النهائي، ثم: الصلاحيات (من يخطط السبرنت، من ينشئ مهام التسويق ويسندها، ما يراه المصمم والمونتير)، وملاحظات التشغيل (تنبيهات المحطات يوميًا 08:00، وأولويات المهام القديمة تم توحيدها مع حفظ الأصل، وإكمال السبرنت يرجّع غير المنجز للباكلوج، والإنفاق يُسجَّل كنتائج يومية لأن تعديل حملة جارية يعيدها للاعتماد، والإيراد = الصفقات المكسوبة بعملة الحملة)، وأن FIN-13 خارج هذا التسليم لأنه مسند لعضو آخر.
- [ ] **الرسالة 2 (أماكن التجربة):** plain text جاهزة للنسخ. لكل ميزة: الرابط الكامل `https://findogroup.com/admin/...`، والدور المطلوب، وخطوات تجربة قصيرة مرقمة.

---

## Self-Review (أُجري عند كتابة الخطة)

**1. تغطية المتطلبات:**
- FIN-12: الباكلوج (T3)، السبرنتات بتواريخ (T2)، الإسناد للمطورين وQA (موجود + إشعار T3)، الحالة/الأولوية/التقدير/الموعد (موجود + الأولوية T1)، تقدم السبرنت والمتأخر (T3). ✅
- FIN-14: المحطات والمخرجات (T4)، المسؤول والموعد (T4 + الموجود)، الإنجاز والمتأخر (T4 وT5)، تنبيه مديري المشاريع (T5). ✅
- FIN-15: الإنشاء والإدارة (موجود)، الهدف والقنوات والميزانية والجدول (T6)، مهام الفريق (T6)، الحالة والإنفاق والـLeads والنتائج (T6 وT7). ✅
- FIN-16: الإنفاق/الـLeads/التحويلات/الإيراد (T7)، ROI (T7)، مقارنة القنوات (T8)، التقارير + CSV (T8). ✅
- FIN-13: مستبعد صراحة بقرار D1 مع السبب.

**2. Placeholders:** لا TBD. المواضع التي فيها "تحقق" لها إجراء بديل صريح: كلاسات CSS (`sr-only`، `checkbox`)، ومفاتيح ترجمة قد تكون موجودة، ونمط CSV في `AttendanceController`، وسطر `remaining` في `progress()` (التصحيح موصوف تحت الكود مباشرة: **طبّقه**).

**3. اتساق الأسماء:** `OperatingSprints::tasks()/releaseUnfinished()` (T2) ثم `progress()/board()/plan()` (T3). `OperatingAccess::PROJECT_SCOPED` (T2) يشمل `deliverables` (T4). `canCreate()` (T3) مستخدم في T4 وT5 وT7. `notifyAssignees()` (T3) يغطي `team_tasks` (T6). `OperatingWorkflow::mark()` (T5) مستخدم في FollowUps وMilestones. `limitAssignment(..., 'marketing')` (T6). `OperatingMarketing::stats()` (T7) مستخدم في `performance()` (T8). مفاتيح المحفوظات المحسوبة: `carried_from_sprint`، `completed_summary`، `priority_note`، `upcoming_alerted_for`، `overdue_alerted_for`.

**4. مخاطر معروفة:**
- تغيير `tasks.priority` إلى select: أي مهمة بقيمة قديمة تفشل validation عند التعديل **حتى** يُشغَّل أمر الترحيل. الأمر جزء إلزامي من النشر (T10 Step 7) مباشرة بعده.
- `OperatingWorkflow::save()` يكبر أكثر. لا إعادة هيكلة في هذه الخطة (نفس نمط الملف).
- الإشعارات تُرسل بعد الـtransaction. لو فشل البريد فـ`OperatingNotifier` يسجّل الخطأ ولا يكسر الحفظ.
- `best/worst` قناة: يعتمد على ROI فقط. قناة بلا إنفاق لا تدخل الترتيب.
- `progress()` و`stats()` لكل صف (استعلامان أو ثلاثة لكل سجل): مقبول لأعداد الحملات والسبرنتات الحالية (عشرات). لا تحسين مبكر.
