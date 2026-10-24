# لوحة CRM — إكمال تاسكات FIN-19 (FIN-20 → FIN-26) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** نكمّل لوحة CRM (`/admin/operations/crm`) عشان تطابق تاسكات Jira من FIN-20 لـFIN-26: تبويبات "الليدز / مشترك معي / متابعة" ببيانات حقيقية، وهيدر وكروت بنفس شكل TCRM لكن بألوان Findo، وبعدين نعمل commit وpush وdeploy.

**Architecture:** نعمل جدول جديد `crm_lead_relations` بيسجل مشاركة العميل (`shared`) ومتابعته (`following`)، ويديره service جديد اسمه `OperatingLeadRelations`. المشاركة بتدي مندوب المبيعات صلاحية **قراءة بس** على العميل، وده من خلال `OperatingAccess::query`. الداشبورد بيعرض التبويبات كروابط `?tab=`، بنفس نمط تبويبات صندوق الوارد. الشكل بيتغير في Blade و`operating.css` بس، باستخدام متغيرات ألوان Findo الموجودة.

**Tech Stack:** Laravel 12 / PHP 8.4, Blade, PostgreSQL محليا (`findo_local`)، PHPUnit، Pint، `bin/server-deploy`.

**Spec:** تاسكات Jira من FIN-19 لـFIN-26 (اتلخّصت في الجدول تحت)، وصورة المرجع `scratchpad/tcrm/00-dashboard.jpg` من session يوم 2026-10-20. الخطة دي مكملة لـ `docs/superpowers/plans/2026-10-20-findo-crm-tcrm-parity.md`.

## Global Constraints

- **الألوان تفضل ألوان Findo:** بنستخدم المتغيرات `--primary` و`--coral` (24 98% 59%)، و`--navy`/`--secondary` (202 39% 22%)، و`--success` و`--info` و`--warning` و`--destructive`، و`--gradient-hero-overlay` و`--gradient-coral`. **ممنوع ناخد البنفسجي أو أي لون تاني من TCRM.** اللي بناخده من TCRM هو الشكل بس: هيدر بخلفية متدرجة، وكروت بأيقونة ملونة وسهم ↗، وتبويبات على شكل أقراص.
- لازم الشكل يشتغل في الوضع الفاتح والداكن (`.dark`) من غير ما نكتب أي لون ثابت جديد غير اللي موجود في الـtokens.
- RTL: اتجاه الصفحة من `<html dir>`، ونستخدم `inline-start` و`inline-end` بدل left وright.
- النصوص عربي جوه `__()`، ولكل نص جديد نضيف ترجمة في `lang/en.json`.
- مافيش dependencies جديدة. الأيقونات SVG جوه `resources/views/admin/icon.blade.php`.
- الأمان: المشاركة بتدي صلاحية قراءة بس. التعديل وتسليم العميل وإخفاء الـSLA تفضل لصاحب العميل أو المسؤول عنه أو المدير.
- كل تعديل PHP لازم بعده `vendor/bin/pint --dirty --format agent`.
- ملفي المحادثات `firstconversation-*.txt` و`second*conversation*.txt` في جذر المشروع **فيهم بيانات دخول TCRM، وممنوع يتعملهم commit.** ننقلهم للـscratchpad قبل الـdeploy، لأن `bin/server-deploy` بيرفض يشتغل لو في ملفات untracked.

## حالة التاسكات قبل الخطة

| التاسك | الموجود | الناقص (الخطة دي بتنفذه) |
|---|---|---|
| FIN-20 الهيدر | العنوان والترحيب والفترة، و"عملائي" كـselect | هيدر متدرج بألوان Findo فيه أيقونة، وقرص للفترة، و"عملائي" زرار لوحده (Task 4) |
| FIN-21 الكروت | 8 KPIs حقيقية | أيقونات SVG ملونة وسهم ↗ وتلميح ? (Task 5) |
| FIN-22 مهام اليوم | قايمة ورسالة لما تكون فاضية | شارة حالة ونوع لكل مهمة، ورسالة فاضية بأيقونة (Task 6) |
| FIN-23 تتبع العملاء | أحدث العملاء + SLA | بيانات المشاركة والمتابعة والتبويبات الـ3 (Tasks 1–3) |
| FIN-24 الأقسام التفصيلية | أنشطتي والتقويم والـSLA | توحيد شكل الأقسام وإخفاؤها حسب الصلاحية (Task 6) |
| FIN-25 الفترة | تحقق السيرفر | زرار "هذا الشهر"، وتأكيد إن رسالة الخطأ بتظهر (Task 4) |
| FIN-26 توحيد الكروت | grid موحد | الأرقام الطويلة والعملات المتعددة ماتخرجش برا الكارت (Task 5) |

## القرارات (اتاخدت بشكل مستقل حسب تفضيل المستخدم)

| # | القرار | السبب |
|---|---|---|
| D1 | جدول واحد `crm_lead_relations` فيه `relation` بقيمة `shared` أو `following` | المشاركة والمتابعة بنفس الشكل (عميل + مستخدم)، فجدول واحد أبسط ومافيهوش تكرار |
| D2 | المشاركة بتدي صلاحية قراءة بس: الزميل يشوف العميل ويعلّق عليه ويتابعه. أما تغيير المرحلة أو تسجيل نشاط أو التعديل فمحتاجين صلاحية write على العميل نفسه | تسجيل النشاط بيعمل متابعات وبيأثر على الـSLA بتاع صاحب العميل (اتعدّل بعد مراجعة الأمان) |
| D3 | يقدر يشارك اللي عنده صلاحية `write` على العميل. وينفع نشارك مع أي مستخدم نشط عنده صلاحية `opportunities` ما عدا نفسه | نفس صلاحيات التعديل الموجودة |
| D4 | أي حد يقدر يشوف العميل يقدر يتابعه، وزرار المتابعة بيشغّل ويلغي | المتابعة مش بتغير أي صلاحية |
| D5 | لما عميل يتشارك مع حد، بيوصله إشعار `lead_shared` في صندوق الوارد من غير إيميل | حد اتشارك معاه عميل لازم يعرف |
| D6 | المشاركة وإلغاؤها بيتسجلوا في `operating_events` (`share` و`unshare`) | عشان يظهروا في سجل العمليات زي باقي الحركات |
| D7 | التبويبات روابط `?tab=leads|shared|following` بتحافظ على from وto وscope | نفس نمط صندوق الوارد، وبتشتغل من غير JS وسهل نعملها تستات |
| D8 | "عملائي": المدير يقلب بين `scope=mine` و`team`، والمندوب الزرار بيفتح قايمة عملائه | المندوب أصلا شايف عملاءه بس |
| D9 | ألوان الأيقونات: إجمالي العملاء coral، والأنشطة info، والمكتسبة success، والإيرادات navy، والمحتمل warning، والـSLA destructive، وتحويل التواصل info، وتحويل الاجتماع success | كلها من tokens بتاعة Findo، وكل كارت لون مختلف زي TCRM |

## File Structure

- Create: `database/migrations/2026_10_24_100000_create_crm_lead_relations_table.php`
- Create: `app/Services/OperatingLeadRelations.php` — المشاركة والمتابعة: كتابة، وقراءة، وإشعار، وتسجيل في السجل
- Modify: `app/Services/OperatingAccess.php` — `query()` بقى ياخد `$action`، والعميل المشترك بيظهر في القراءة بس
- Modify: `app/Services/OperatingLeads.php` — فلتر `relation` في `query()`
- Modify: `app/Services/OperatingCrmReports.php` — `dashboard()` بيرجع `shared` و`following` و`tabCounts`
- Modify: `app/Http/Controllers/CrmController.php` — `share` و`unshare` و`follow`، والـ`tab` في الداشبورد، و`relation` في الفلاتر، وبيانات المشاركة في ملف العميل
- Modify: `routes/admin.php` — 3 routes جديدة
- Modify: `resources/views/admin/icon.blade.php` — أيقونات جديدة وparam اسمه `class`
- Modify: `resources/views/admin/operating/crm-dashboard.blade.php` — الهيدر والكروت والتبويبات والأقسام
- Modify: `resources/views/admin/operating/crm-lead.blade.php` — جزء "المشاركة والمتابعة"
- Modify: `public/admin-assets/operating.css` — `.crm-hero` و`.kpi` بالألوان و`.seg-tabs` و`.empty-state`
- Modify: `lang/en.json`
- Test: `tests/Feature/Operating/CrmLeadRelationsTest.php` (جديد)، و`CrmDashboardTest.php` (تعديل)

---

### Task 1: جدول العلاقات والـservice وصلاحية القراءة

**Files:** migration وservice جديدين، وتعديل `OperatingAccess.php:144-215`، وتست `CrmLeadRelationsTest.php`.

**Interfaces:**
- Produces: `OperatingLeadRelations::SHARED = 'shared'` و`FOLLOWING = 'following'`
  - `share(User $actor, object $lead, int $userId): void`
  - `unshare(User $actor, object $lead, int $userId): void`
  - `toggleFollow(User $user, object $lead): bool` (بترجع الحالة الجديدة)
  - `leadIds(User $user, string $relation): Collection<int,int>`
  - `sharedWith(object $lead): Collection<int, object{id,name}>`
  - `isFollowing(User $user, object $lead): bool`
  - `shareableUsers(User $actor, object $lead): array<int,string>`
- Produces: `OperatingAccess::query(User $user, string $type, string $action = 'read'): Builder`

- [ ] **Step 1: التستات اللي هتفشل** — `php artisan make:test --phpunit CrmLeadRelationsTest --no-interaction`، ونحرّك الملف لـ`tests/Feature/Operating/`:

```php
<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingAccess;
use App\Services\OperatingLeadRelations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class CrmLeadRelationsTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_sharing_a_lead_gives_the_colleague_read_but_not_write_access(): void
    {
        $owner = $this->operatingUser('sales');
        $this->employeeFor($owner);
        $colleague = $this->operatingUser('sales');
        $this->employeeFor($colleague);
        $lead = $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Co'], 'Shared lead', $owner->id);
        $access = app(OperatingAccess::class);

        $this->assertFalse($access->query($colleague, 'opportunities')->where('id', $lead)->exists());

        app(OperatingLeadRelations::class)->share($owner, DB::table('operating_records')->find($lead), $colleague->id);

        $this->assertTrue($access->query($colleague, 'opportunities')->where('id', $lead)->exists());
        $this->assertFalse($access->query($colleague, 'opportunities', 'write')->where('id', $lead)->exists());
        $this->assertDatabaseHas('operating_events', ['record_id' => $lead, 'action' => 'share']);
        $this->assertSame(1, $colleague->notifications()->count());
    }

    public function test_unsharing_removes_access_and_following_toggles(): void
    {
        $owner = $this->operatingUser('sales');
        $this->employeeFor($owner);
        $colleague = $this->operatingUser('sales');
        $lead = DB::table('operating_records')->find($this->operatingRecord('opportunities', 'new', [], 'Lead', $owner->id));
        $relations = app(OperatingLeadRelations::class);

        $relations->share($owner, $lead, $colleague->id);
        $relations->unshare($owner, $lead, $colleague->id);

        $this->assertFalse(app(OperatingAccess::class)->query($colleague, 'opportunities')->where('id', $lead->id)->exists());
        $this->assertTrue($relations->toggleFollow($owner, $lead));
        $this->assertSame([$lead->id], $relations->leadIds($owner, OperatingLeadRelations::FOLLOWING)->all());
        $this->assertFalse($relations->toggleFollow($owner, $lead));
    }

    public function test_a_lead_cannot_be_shared_with_yourself_or_a_user_without_crm_access(): void
    {
        $owner = $this->operatingUser('sales');
        $lead = DB::table('operating_records')->find($this->operatingRecord('opportunities', 'new', [], 'Lead', $owner->id));
        $relations = app(OperatingLeadRelations::class);

        $this->assertArrayNotHasKey($owner->id, $relations->shareableUsers($owner, $lead));
        $this->assertArrayNotHasKey($this->operatingUser('designer')->id, $relations->shareableUsers($owner, $lead));
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $relations->share($owner, $lead, $owner->id);
    }
}
```

- [ ] **Step 2:** `php artisan test --compact tests/Feature/Operating/CrmLeadRelationsTest.php`، والمتوقع يفشل (الكلاس `OperatingLeadRelations` مش موجود).
- [ ] **Step 3: الـmigration** — `php artisan make:migration create_crm_lead_relations_table --no-interaction`:

```php
public function up(): void
{
    Schema::create('crm_lead_relations', function (Blueprint $table) {
        $table->id();
        $table->foreignId('lead_id')->constrained('operating_records')->cascadeOnDelete();
        $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
        $table->string('relation', 20);
        $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamps();
        $table->unique(['lead_id', 'user_id', 'relation']);
        $table->index(['user_id', 'relation']);
    });
}

public function down(): void
{
    Schema::dropIfExists('crm_lead_relations');
}
```

- [ ] **Step 4: الـservice** — `php artisan make:class Services/OperatingLeadRelations --no-interaction`:

```php
<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\OperatingAlert;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OperatingLeadRelations
{
    public const SHARED = 'shared';

    public const FOLLOWING = 'following';

    public function __construct(private OperatingAccess $access, private OperatingNotifier $notifier) {}

    /**
     * Share read access to a lead with a colleague who can use the CRM, record it in the audit log and tell them.
     */
    public function share(User $actor, object $lead, int $userId): void
    {
        abort_unless(array_key_exists($userId, $this->shareableUsers($actor, $lead)), 422);
        $inserted = DB::table('crm_lead_relations')->insertOrIgnore(['lead_id' => $lead->id, 'user_id' => $userId, 'relation' => self::SHARED, 'created_by' => $actor->id, 'created_at' => now(), 'updated_at' => now()]);
        if (! $inserted) {
            return;
        }
        $this->event($actor, $lead, 'share', $userId);
        $this->notifier->send([User::find($userId)], new OperatingAlert('lead_shared', ['ar' => 'تمت مشاركة عميل معك', 'en' => 'A lead was shared with you'], ['ar' => $actor->name.' شارك معك العميل: '.$lead->title, 'en' => $actor->name.' shared the lead: '.$lead->title], '/admin/operations/crm/leads/'.$lead->id));
    }

    public function unshare(User $actor, object $lead, int $userId): void
    {
        if (DB::table('crm_lead_relations')->where(['lead_id' => $lead->id, 'user_id' => $userId, 'relation' => self::SHARED])->delete()) {
            $this->event($actor, $lead, 'unshare', $userId);
        }
    }

    public function toggleFollow(User $user, object $lead): bool
    {
        $match = ['lead_id' => $lead->id, 'user_id' => $user->id, 'relation' => self::FOLLOWING];
        if (DB::table('crm_lead_relations')->where($match)->delete()) {
            return false;
        }
        DB::table('crm_lead_relations')->insert($match + ['created_by' => $user->id, 'created_at' => now(), 'updated_at' => now()]);

        return true;
    }

    /**
     * @return Collection<int, int>
     */
    public function leadIds(User $user, string $relation): Collection
    {
        return DB::table('crm_lead_relations')->where('user_id', $user->id)->where('relation', $relation)->orderBy('lead_id')->pluck('lead_id')->map(fn (mixed $id): int => (int) $id);
    }

    /**
     * @return Collection<int, object>
     */
    public function sharedWith(object $lead): Collection
    {
        return DB::table('crm_lead_relations')->join('users', 'users.id', '=', 'crm_lead_relations.user_id')->where('lead_id', $lead->id)->where('relation', self::SHARED)->orderBy('users.name')->get(['users.id', 'users.name']);
    }

    public function isFollowing(User $user, object $lead): bool
    {
        return DB::table('crm_lead_relations')->where(['lead_id' => $lead->id, 'user_id' => $user->id, 'relation' => self::FOLLOWING])->exists();
    }

    /**
     * Active colleagues who can open the CRM, excluding the actor and those the lead is already shared with.
     *
     * @return array<int, string>
     */
    public function shareableUsers(User $actor, object $lead): array
    {
        $taken = $this->sharedWith($lead)->pluck('id')->push($actor->id)->all();

        return User::query()->where('active', true)->whereNotIn('id', $taken)->orderBy('name')->get()->filter(fn (User $user): bool => $this->access->can($user, 'opportunities'))->mapWithKeys(fn (User $user): array => [$user->id => $user->name])->all();
    }

    private function event(User $actor, object $lead, string $action, int $userId): void
    {
        DB::table('operating_events')->insert(['record_id' => $lead->id, 'user_id' => $actor->id, 'action' => $action, 'reason' => ($action === 'share' ? 'مشاركة العميل مع ' : 'إلغاء المشاركة مع ').User::find($userId)?->name, 'after' => json_encode(['user_id' => $userId]), 'created_at' => now()]);
    }
}
```

- [ ] **Step 5: الصلاحيات** — في `OperatingAccess::query` نخلي التوقيع `query(User $user, string $type, string $action = 'read')`. وجوه فرع `$salesScoped`، بعد الـforeach بتاع الحقول، نضيف:

```php
                if ($type === 'opportunities' && $action === 'read') {
                    $scope->orWhereIn('id', DB::table('crm_lead_relations')->where('user_id', $user->id)->where('relation', 'shared')->select('lead_id'));
                }
```

ولازم `$action` يتضاف لـ`use (...)`. وفي `record()` نبدل الاستدعاء بـ`$this->query($user, $record->type, $action)`. عندنا استدعاءات متداخلة لـ`query()` من غير `$action`، زي `$this->query($user, 'opportunities')` في فرع الـquotes. دي مقصود تفضل قراءة، فمش هنغيرها.
- [ ] **Step 6: نشغّل الـmigration محليا ونعيد التست:** `php artisan migrate --no-interaction`، وبعده `php artisan test --compact tests/Feature/Operating/CrmLeadRelationsTest.php`. المتوقع PASS. لو الإشعار فشل، نشوف الـkind بيتعامل إزاي في `notifications.blade.php`، وغالبا مش محتاج أي تعديل.
- [ ] **Step 7: تست الانحدار:** `php artisan test --compact tests/Feature/Operating`، والمتوقع كله يعدي (عندنا 201 تست قبل كده).
- [ ] **Step 8:** `vendor/bin/pint --dirty --format agent`، وبعدين commit:

```bash
git add database/migrations/*crm_lead_relations* app/Services/OperatingLeadRelations.php app/Services/OperatingAccess.php tests/Feature/Operating/CrmLeadRelationsTest.php
git commit -m "feat(crm): share leads read-only with colleagues and follow leads"
```

### Task 2: routes المشاركة والمتابعة وجزء في ملف العميل

**Files:** `CrmController.php`، و`routes/admin.php:66`، و`crm-lead.blade.php`، و`lang/en.json`، والتست في `CrmLeadRelationsTest.php`.

**Interfaces:**
- Consumes: Task 1
- Produces: `POST /admin/operations/crm/leads/{id}/share` (`user_id`)، و`POST .../unshare` (`user_id`)، و`POST .../follow`. والـview بيستقبل `sharedWith` و`shareable` و`following` و`canShare`

- [ ] **Step 1: التست:**

```php
    public function test_the_lead_page_shares_and_follows_through_http(): void
    {
        $owner = $this->operatingUser('sales');
        $this->employeeFor($owner);
        $colleague = $this->operatingUser('sales');
        $this->employeeFor($colleague);
        $lead = $this->operatingRecord('opportunities', 'new', ['lead_company' => 'Co'], 'Lead', $owner->id);

        $this->actingAs($owner)->get('/admin/operations/crm/leads/'.$lead)->assertOk()->assertSee(__('المشاركة والمتابعة'));
        $this->actingAs($colleague)->post('/admin/operations/crm/leads/'.$lead.'/share', ['user_id' => $owner->id])->assertForbidden();
        $this->actingAs($owner)->post('/admin/operations/crm/leads/'.$lead.'/share', ['user_id' => $colleague->id])->assertRedirect();
        $this->actingAs($colleague)->get('/admin/operations/crm/leads/'.$lead)->assertOk()->assertDontSee('/share"', false);
        $this->actingAs($colleague)->post('/admin/operations/crm/leads/'.$lead.'/follow')->assertRedirect();
        $this->assertDatabaseHas('crm_lead_relations', ['lead_id' => $lead, 'user_id' => $colleague->id, 'relation' => 'following']);
        $this->actingAs($owner)->post('/admin/operations/crm/leads/'.$lead.'/unshare', ['user_id' => $colleague->id])->assertRedirect();
        $this->actingAs($colleague)->get('/admin/operations/crm/leads/'.$lead)->assertForbidden();
    }
```

- [ ] **Step 2:** نشغّله، والمتوقع يفشل بـ404 أو لأن النص مش موجود.
- [ ] **Step 3: الـroutes**، بعد سطر `sla-dismiss`:

```php
        Route::post('/operations/crm/leads/{id}/share', [C::class, 'shareLead'])->whereNumber('id');
        Route::post('/operations/crm/leads/{id}/unshare', [C::class, 'unshareLead'])->whereNumber('id');
        Route::post('/operations/crm/leads/{id}/follow', [C::class, 'followLead'])->whereNumber('id');
```

- [ ] **Step 4: الـcontroller** — نضيف `private OperatingLeadRelations $relations` في الـconstructor:

```php
    public function shareLead(int $id, Request $request): Response
    {
        $this->authorizeCrm($request);
        $lead = $this->access->record($request->user(), $id, 'write');
        abort_unless($lead->type === 'opportunities', 404);
        $input = $request->validate(['user_id' => 'required|integer']);
        $this->relations->share($request->user(), $lead, (int) $input['user_id']);

        return back()->with('success', __('تمت مشاركة العميل.'));
    }

    public function unshareLead(int $id, Request $request): Response
    {
        $this->authorizeCrm($request);
        $lead = $this->access->record($request->user(), $id, 'write');
        abort_unless($lead->type === 'opportunities', 404);
        $this->relations->unshare($request->user(), $lead, (int) $request->validate(['user_id' => 'required|integer'])['user_id']);

        return back()->with('success', __('تم إلغاء المشاركة.'));
    }

    public function followLead(int $id, Request $request): Response
    {
        $this->authorizeCrm($request);
        $lead = $this->access->record($request->user(), $id);
        abort_unless($lead->type === 'opportunities', 404);

        return back()->with('success', $this->relations->toggleFollow($request->user(), $lead) ? __('أنت تتابع هذا العميل الآن.') : __('تم إلغاء المتابعة.'));
    }
```

وفي `lead()` نضيف للـarray:

```php
            'canShare' => $canShare = $this->access->can($request->user(), 'opportunities', 'write') && $this->access->query($request->user(), 'opportunities', 'write')->where('id', $id)->exists(),
            'sharedWith' => $this->relations->sharedWith($lead),
            'shareable' => $canShare ? $this->relations->shareableUsers($request->user(), $lead) : [],
            'following' => $this->relations->isFollowing($request->user(), $lead),
```

(هنا assignment جوه الـarray عشان نستخدم `$canShare` في السطر اللي بعده. لو Pint أو القراءة طلعوا إن ده مش واضح، نحسب `$canShare` قبل الـreturn.)
- [ ] **Step 5: الـview** — في `crm-lead.blade.php` نضيف section جديد، أول واحد في العمود الجانبي (نشوف ترتيب الأعمدة في الملف الأول):

```blade
<section class="card"><div class="section-heading"><h2>{{ __('المشاركة والمتابعة') }}</h2><form method="post" action="/admin/operations/crm/leads/{{ $lead->id }}/follow">@csrf<button class="button {{ $following?'':'secondary' }}">{{ $following ? __('إلغاء المتابعة') : __('متابعة') }}</button></form></div>
@forelse($sharedWith as $person)<div class="operation-row"><b>{{ $person->name }}</b>@if($canShare)<form method="post" action="/admin/operations/crm/leads/{{ $lead->id }}/unshare">@csrf<input type="hidden" name="user_id" value="{{ $person->id }}"><button class="button secondary small">{{ __('إلغاء') }}</button></form>@endif</div>@empty<p class="muted">{{ __('لم تتم مشاركة هذا العميل مع أحد.') }}</p>@endforelse
@if($canShare && $shareable)<form class="inline-form" method="post" action="/admin/operations/crm/leads/{{ $lead->id }}/share">@csrf<select name="user_id" aria-label="{{ __('شارك مع') }}" required>@foreach($shareable as $userId=>$name)<option value="{{ $userId }}">{{ $name }}</option>@endforeach</select><button class="button">{{ __('مشاركة') }}</button></form>@endif</section>
```

- [ ] **Step 6:** نضيف للـ`lang/en.json`: "المشاركة والمتابعة": "Sharing & following"، "متابعة": "Follow"، "إلغاء المتابعة": "Unfollow"، "مشاركة": "Share"، "شارك مع": "Share with"، "لم تتم مشاركة هذا العميل مع أحد.": "This lead isn't shared with anyone."، "تمت مشاركة العميل.": "Lead shared."، "تم إلغاء المشاركة.": "Sharing removed."، "أنت تتابع هذا العميل الآن.": "You're following this lead."، "تم إلغاء المتابعة.": "Unfollowed."
- [ ] **Step 7:** نشغّل التست وتستات `CrmLeadProfileTest`، والمتوقع PASS.
- [ ] **Step 8:** Pint، وبعدين commit `feat(crm): share and follow controls on the lead profile`.

### Task 3: تبويبات الداشبورد "الليدز / مشترك معي / متابعة" (FIN-23)

**Files:** `OperatingCrmReports.php:43-81`، و`OperatingLeads.php:32`، و`CrmController.php` (`dashboard` و`leadFilters`)، و`crm-dashboard.blade.php`، و`operating.css`، و`CrmDashboardTest.php`.

**Interfaces:**
- Consumes: `OperatingLeadRelations::leadIds()`
- Produces: `dashboard()` بيرجع كمان `'shared' => array rows` و`'following' => array rows` و`'tabCounts' => ['leads'=>int,'shared'=>int,'following'=>int]`. وفلتر الليدز `relation` قيمته `shared` أو `following`

- [ ] **Step 1: التست** في `CrmDashboardTest.php`:

```php
    public function test_the_shared_and_following_tabs_list_their_leads_with_empty_states(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $owner = $this->operatingUser('sales');
        $this->employeeFor($owner);
        $rep = $this->operatingUser('sales');
        $this->employeeFor($rep);
        $lead = DB::table('operating_records')->find($this->lead('new', [], '2026-10-02 10:00:00', $owner->id));
        $relations = app(\App\Services\OperatingLeadRelations::class);

        $this->actingAs($rep)->get('/admin/operations/crm?tab=shared')->assertOk()->assertSee(__('لا يوجد عملاء مشتركون معك.'));
        $relations->share($owner, $lead, $rep->id);
        $relations->toggleFollow($rep, $lead);

        $dashboard = app(OperatingCrmReports::class)->dashboard($rep, '2026-10-01', '2026-10-31', 'mine');
        $this->assertSame([$lead->id], array_column($dashboard['shared'], 'id'));
        $this->assertSame([$lead->id], array_column($dashboard['following'], 'id'));
        $this->assertSame(['leads' => 0, 'shared' => 1, 'following' => 1], $dashboard['tabCounts']);
        $this->actingAs($rep)->get('/admin/operations/crm?tab=following')->assertOk()->assertSee($lead->title);
        $this->actingAs($rep)->get('/admin/operations/crm/leads?relation=shared')->assertOk()->assertSee($lead->title);
        $this->actingAs($rep)->get('/admin/operations/crm?tab=bogus')->assertSessionHasErrors('tab');
    }
```

(لو `rows()` مش بيرجع `id`، نتأكد من `OperatingLeads::rows`، لأنه مستخدم بالفعل في `/crm/leads/{{ $row['id'] }}`.)
- [ ] **Step 2:** نشغّله، والمتوقع يفشل.
- [ ] **Step 3: الـreports** — نضيف `private OperatingLeadRelations $relations` في الـconstructor. وفي `dashboard()`:

```php
        $related = fn (string $relation): Collection => $this->access->query($user, 'opportunities')->whereIn('id', $this->relations->leadIds($user, $relation))->where('status', '!=', 'archived')->latest('updated_at')->get();
        $shared = $related(OperatingLeadRelations::SHARED);
        $following = $related(OperatingLeadRelations::FOLLOWING);
```

ونضيف للـarray اللي راجع:

```php
            'shared' => $this->leadRows->rows($shared->take(6)),
            'following' => $this->leadRows->rows($following->take(6)),
            'tabCounts' => ['leads' => $leads->count(), 'shared' => $shared->count(), 'following' => $following->count()],
```

ملاحظة: `tabCounts.leads` هو عدد عملاء الـscope كله، مش الفترة بس، عشان يطابق قايمة "أحدث العملاء". في التست الليد بتاع الـowner مش جوه `mine` بتاع الـrep، عشان كده قيمته 0.
- [ ] **Step 4: فلتر الليدز** — في `OperatingLeads::query`، قبل الـreturn:

```php
        if (in_array($filters['relation'] ?? null, [OperatingLeadRelations::SHARED, OperatingLeadRelations::FOLLOWING], true)) {
            $query->whereIn('id', app(OperatingLeadRelations::class)->leadIds($user, $filters['relation']));
        }
```

(نحقنه في الـconstructor لو مافيش circular dependency: `OperatingLeadRelations` بيعتمد على Access وNotifier بس، فالحقن آمن.) وفي `leadFilters` نضيف `'relation' => 'nullable|in:shared,following'`.
- [ ] **Step 5: الـcontroller** — في `dashboard()`: `$tab = $request->validate(['tab' => 'nullable|in:leads,shared,following'])['tab'] ?? 'leads';` ونبعته للـview.
- [ ] **Step 6: الـview** — نبدل section "أحدث العملاء" بـ:

```blade
@php $tabRows=['leads'=>$recent,'shared'=>$shared,'following'=>$following][$tab]; $tabMore=['leads'=>$leadsUrl(),'shared'=>$leadsUrl(['relation'=>'shared']),'following'=>$leadsUrl(['relation'=>'following'])][$tab]; @endphp
<section class="card"><div class="section-heading"><nav class="seg-tabs" aria-label="{{ __('العملاء') }}">@foreach(['leads'=>['users','الليدز'],'shared'=>['share','مشترك معي'],'following'=>['eye','متابعة']] as $key=>[$icon,$label])<a href="{{ request()->fullUrlWithQuery(['tab'=>$key]) }}" class="{{ $tab===$key?'active':'' }}" @if($tab===$key) aria-current="page" @endif>@include('admin.icon',['name'=>$icon]){{ __($label) }} <b class="count-badge">{{ $tabCounts[$key] }}</b></a>@endforeach</nav><a href="{{ $tabMore }}">{{ __('عرض') }} ↗</a></div>
@forelse($tabRows as $row)<a class="operation-row" href="/admin/operations/crm/leads/{{ $row['id'] }}"><b>{{ $row['title'] }}</b><span><span class="badge">{{ $row['status'] }}</span> {{ $row['assignee'] ?? __('غير مسند') }} · {{ $row['created_at'] }}</span></a>@empty<div class="empty-state">@include('admin.icon',['name'=>['leads'=>'users','shared'=>'share','following'=>'eye'][$tab]])<p>{{ ['leads'=>__('لا توجد بيانات'),'shared'=>__('لا يوجد عملاء مشتركون معك.'),'following'=>__('أنت لا تتابع أي عميل بعد.')][$tab] }}</p></div>@endforelse</section>
```

- [ ] **Step 7: الـCSS** (في آخر `operating.css`):

```css
.seg-tabs{display:flex;gap:.25rem;padding:.25rem;border-radius:999px;background:var(--admin-surface);border:1px solid var(--admin-border);overflow-x:auto;max-width:100%}
.seg-tabs a{display:inline-flex;align-items:center;gap:.4rem;padding:.4rem .85rem;border-radius:999px;color:var(--admin-muted);text-decoration:none;font-weight:600;white-space:nowrap}
.seg-tabs a.active{background:var(--admin-card);color:var(--admin-accent);box-shadow:0 1px 3px rgba(16,24,40,.12)}
.seg-tabs .nav-icon{width:1rem;height:1rem}
.empty-state{display:grid;justify-items:center;gap:.5rem;padding:2rem 1rem;color:var(--admin-muted);text-align:center}
.empty-state .nav-icon{width:2.6rem;height:2.6rem;padding:.6rem;border-radius:14px;background:var(--admin-accent-soft);color:var(--admin-accent)}
```

- [ ] **Step 8: الأيقونات** — في `icon.blade.php` نضيف للـpaths (كلها stroke 24×24، على نمط lucide):

```php
    'share' => 'M18 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6 M6 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6 M18 22a3 3 0 1 0 0-6 3 3 0 0 0 0 6 M8.6 13.5l6.8 4 M15.4 6.5l-6.8 4',
    'eye' => 'M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z M12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6',
    'activity' => 'M22 12h-4l-3 9L9 3l-3 9H2',
    'check' => 'M22 11.1V12a10 10 0 1 1-5.9-9.1 M22 4L12 14l-3-3',
    'money' => 'M12 2v20 M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6',
    'trend' => 'M22 7l-8.5 8.5-5-5L2 17 M16 7h6v6',
    'alert' => 'M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z M12 9v4 M12 17h.01',
    'phone' => 'M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z',
    'handshake' => 'M11 17l2 2a1 1 0 0 0 3-3 M14 14l2.5 2.5a1 1 0 0 0 3-3l-3.9-3.9a3 3 0 0 0-4.2 0l-.9.9a1 1 0 0 1-3-3l2.8-2.8a5.8 5.8 0 0 1 7.1-.9l.5.3a2 2 0 0 0 1.5.2L21 4 M21 3l1 11h-2 M3 3L2 14l6.5 6.5a1 1 0 0 0 3-3 M3 4h8',
    'calendar' => 'M3 5h18v16H3z M3 10h18 M8 3v4 M16 3v4',
    'tasks' => 'M3 6h11 M3 12h11 M3 18h11 M18 5l1.5 1.5L22 4 M18 11l1.5 1.5L22 10 M18 17l1.5 1.5L22 16',
    'arrow-out' => 'M7 17L17 7 M8 7h9v9',
    'help' => 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20 M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3 M12 17h.01',
```

ونبدل سطر الـsvg بـ`<svg class="{{ $class ?? 'nav-icon' }}" ...>`.
- [ ] **Step 8.5:** نضيف للـ`lang/en.json`: "الليدز": "Leads"، "مشترك معي": "Shared with me"، "متابعة": "Following" (موجودة من Task 2)، "عرض": "View"، "لا يوجد عملاء مشتركون معك.": "No leads are shared with you."، "أنت لا تتابع أي عميل بعد.": "You aren't following any leads yet."
- [ ] **Step 9:** `php artisan test --compact tests/Feature/Operating/CrmDashboardTest.php tests/Feature/Operating/CrmLeadsListTest.php`، والمتوقع PASS.
- [ ] **Step 10:** Pint، وبعدين commit `feat(crm): leads, shared-with-me and following tabs on the dashboard`.

### Task 4: هيدر بألوان Findo، وقرص الفترة، وزرار "عملائي" (FIN-20 و FIN-25)

**Files:** `crm-dashboard.blade.php` (سطر 5–6)، و`operating.css`، و`CrmDashboardTest.php`.

- [ ] **Step 1: التست** (سلوك "عملائي" وزرار "هذا الشهر"):

```php
    public function test_the_my_clients_button_toggles_the_manager_scope_and_links_reps_to_their_leads(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $manager = $this->operatingUser('sales_manager');
        $rep = $this->operatingUser('sales');
        $this->employeeFor($rep);

        $this->actingAs($manager)->get('/admin/operations/crm')->assertOk()->assertSee('scope=mine', false);
        $this->actingAs($manager)->get('/admin/operations/crm?scope=mine')->assertOk()->assertSee('scope=team', false);
        $this->actingAs($rep)->get('/admin/operations/crm')->assertOk()->assertSee('href="/admin/operations/crm/leads"', false)->assertDontSee('scope=mine', false);
    }
```

- [ ] **Step 2:** نشغّله، والمتوقع يفشل.
- [ ] **Step 3: الـview** — نبدل `page-heading` بتاع الداشبورد بـ:

```blade
@php $isManager=$crm->isManager(auth()->user()); $keep=fn(array $extra)=>request()->fullUrlWithQuery($extra); @endphp
<header class="crm-hero"><div class="crm-hero-title"><span class="crm-hero-icon">@include('admin.icon',['name'=>'overview'])</span><div><h1>{{ __('لوحة التحكم') }}</h1><p>{{ __('مرحبًا') }} {{ auth()->user()->name }}، {{ __('هذا ملخص أنشطتك') }}</p></div></div>
<div class="crm-hero-actions"><form class="date-pill" method="get">@foreach(['scope'=>$isManager?$scope:null,'tab'=>request('tab')] as $name=>$value)@if($value)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif @endforeach @include('admin.icon',['name'=>'calendar'])<input type="date" name="from" value="{{ $from }}" aria-label="{{ __('من') }}"><span aria-hidden="true">←</span><input type="date" name="to" value="{{ $to }}" aria-label="{{ __('إلى') }}"><button>{{ __('تطبيق') }}</button><a href="{{ $keep(['from'=>today()->startOfMonth()->format('Y-m-d'),'to'=>today()->endOfMonth()->format('Y-m-d')]) }}">{{ __('هذا الشهر') }}</a></form>
@if($isManager)<a class="hero-button {{ $scope==='mine'?'active':'' }}" href="{{ $keep(['scope'=>$scope==='mine'?'team':'mine']) }}" @if($scope==='mine') aria-pressed="true" @endif>@include('admin.icon',['name'=>'users']){{ $scope==='mine' ? __('كل الفريق') : __('عملائي') }}</a>@else<a class="hero-button" href="/admin/operations/crm/leads">@include('admin.icon',['name'=>'users']){{ __('عملائي') }}</a>@endif</div></header>
```

الـselect بتاع scope اتشال. وتست `assertSee('2026-10-01')` القديم لسه هيعدي، لأن الـinputs موجودة.
- [ ] **Step 4: الـCSS** — الخلفية من tokens بتاعة Findo (navy hero + توهج coral):

```css
.crm-hero{position:relative;overflow:hidden;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;padding:1.25rem 1.5rem;margin-bottom:1rem;border-radius:22px;color:#fff;background:radial-gradient(120% 140% at 0% 0%,hsl(var(--coral) / .35) 0%,transparent 55%),var(--gradient-hero-overlay),hsl(var(--navy));box-shadow:var(--shadow-3d)}
.crm-hero-title{display:flex;align-items:center;gap:.85rem;min-width:0}
.crm-hero-title h1{margin:0;font-size:1.5rem;color:#fff}
.crm-hero-title p{margin:.15rem 0 0;color:rgba(255,255,255,.78)}
.crm-hero-icon{display:grid;place-items:center;width:2.9rem;height:2.9rem;border-radius:14px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.18);flex:none}
.crm-hero-icon .nav-icon{width:1.4rem;height:1.4rem;color:hsl(var(--coral-light))}
.crm-hero-actions{display:flex;flex-wrap:wrap;gap:.6rem;align-items:center}
.date-pill{display:flex;flex-wrap:wrap;align-items:center;gap:.4rem;padding:.35rem .5rem .35rem .75rem;border-radius:14px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.28)}
.date-pill input[type=date]{background:transparent;border:0;color:#fff;font:inherit;font-weight:600;color-scheme:dark;min-width:0}
.date-pill button,.date-pill a{border:0;border-radius:10px;padding:.35rem .7rem;font:inherit;font-weight:600;cursor:pointer;text-decoration:none}
.date-pill button{background:var(--gradient-coral);color:hsl(var(--primary-foreground))}
.date-pill a{background:transparent;color:rgba(255,255,255,.85)}
.date-pill .nav-icon{width:1rem;height:1rem;color:rgba(255,255,255,.8)}
.hero-button{display:inline-flex;align-items:center;gap:.45rem;padding:.55rem .95rem;border-radius:14px;border:1px solid rgba(255,255,255,.35);color:#fff;text-decoration:none;font-weight:700}
.hero-button.active,.hero-button:hover{background:#fff;color:hsl(var(--navy))}
.hero-button .nav-icon{width:1.05rem;height:1.05rem}
@media (max-width:640px){.crm-hero{padding:1rem}.crm-hero-actions,.date-pill{width:100%}.date-pill input[type=date]{flex:1}}
```

- [ ] **Step 5:** نتأكد إن رسالة خطأ الفترة بتظهر. الـlayout بيعرض `$errors` (layout.blade.php:34)، والـvalidate بيعمل redirect back. نعمل كده يدوي في QA (Task 7).
- [ ] **Step 6:** نضيف للـ`lang/en.json`: "لوحة التحكم": "Dashboard"، "هذا الشهر": "This month"، "كل الفريق": "Whole team"، "عملائي" (لو مش موجودة): "My clients".
- [ ] **Step 7:** نشغّل التستات، وبعدين Pint، وبعدين commit `feat(crm): Findo-branded dashboard header with date pill and my-clients button`.

### Task 5: كروت الإحصائيات الملونة والموحدة (FIN-21 و FIN-26)

**Files:** `crm-dashboard.blade.php` (سطر 8–17)، و`operating.css` (سطر 30–36، `.kpi*`).

ده تعديل شكل بس، فمش محتاج تستات جديدة. لكن التستات الموجودة اللي بتدور على النصوص لازم تفضل تعدي.

- [ ] **Step 1: الـview** — نبني الكروت من array واحدة، عشان نضمن إن كلهم نفس الشكل:

```blade
@php $cards=[
 ['icon'=>'users','tone'=>'coral','value'=>number_format($kpis['total_leads']),'label'=>'إجمالي العملاء','hint'=>'أُنشئوا في الفترة','url'=>$leadsUrl(['from'=>$from,'to'=>$to])],
 ['icon'=>'activity','tone'=>'info','value'=>number_format($kpis['activities']),'label'=>'إجمالي الأنشطة','hint'=>'أنشطة مكتملة في الفترة','url'=>null],
 ['icon'=>'check','tone'=>'success','value'=>number_format($kpis['won']),'label'=>'الصفقات المكتسبة','hint'=>'حسب تاريخ الإغلاق','url'=>$leadsUrl(['stage'=>'won','date_field'=>'closed','from'=>$from,'to'=>$to])],
 ['icon'=>'money','tone'=>'navy','value'=>$money($kpis['revenue']),'label'=>'إجمالي الإيرادات','hint'=>'صفقات مقفولة فقط حسب تاريخ الإغلاق','url'=>null,'ltr'=>true],
 ['icon'=>'trend','tone'=>'warning','value'=>$money($kpis['potential']),'label'=>'الإيراد المحتمل','hint'=>'صفقات مفتوحة لم تُقفل بعد','url'=>null,'ltr'=>true],
 ['icon'=>'alert','tone'=>'danger','value'=>number_format($kpis['sla_alerts']),'label'=>'تنبيهات SLA','hint'=>'عملاء تجاوزوا مهلة التواصل','url'=>$leadsUrl(['sla'=>'breached'])],
 ['icon'=>'phone','tone'=>'info','value'=>$pct($kpis['contact_to_meeting']),'label'=>'تحويل التواصل للاجتماع','hint'=>'من تم التواصل معهم ووصلوا لاجتماع','url'=>null,'help'=>true],
 ['icon'=>'handshake','tone'=>'success','value'=>$pct($kpis['meeting_to_close']),'label'=>'تحويل الاجتماع للإغلاق','hint'=>'من وصلوا لاجتماع وتم كسبهم','url'=>null,'help'=>true],
]; @endphp
<div class="kpi-grid">@foreach($cards as $card)<{{ $card['url']?'a':'div' }} class="kpi tone-{{ $card['tone'] }} {{ $card['tone']==='danger'&&$kpis['sla_alerts']?'warn':'' }}" @if($card['url']) href="{{ $card['url'] }}" @endif>
<span class="kpi-top"><span class="kpi-icon">@include('admin.icon',['name'=>$card['icon']])</span><span class="kpi-meta">@if(!empty($card['help']))<span class="kpi-help" title="{{ __($card['hint']) }}" aria-label="{{ __($card['hint']) }}">@include('admin.icon',['name'=>'help'])</span>@endif @if($card['url'])@include('admin.icon',['name'=>'arrow-out'])@endif</span></span>
<strong @if(!empty($card['ltr'])) dir="ltr" @endif title="{{ $card['value'] }}">{{ $card['value'] }}</strong><span class="kpi-label">{{ __($card['label']) }}</span><small>{{ __($card['hint']) }}</small></{{ $card['tone']?($card['url']?'a':'div'):'div' }}>@endforeach</div>
```

(الـtag اللي بيقفل لازم يطابق اللي فتح. الأبسط: `</{{ $card['url']?'a':'div' }}>`.)
- [ ] **Step 2: الـCSS** — نبدل `.kpi*` القديمة بـ:

```css
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(165px,1fr));gap:.85rem;margin-bottom:1.25rem}
.kpi{--tone:var(--admin-accent);position:relative;background:var(--admin-card);border:1px solid var(--admin-border);border-top:3px solid color-mix(in srgb,var(--tone) 70%,transparent);border-radius:18px;padding:1rem;display:flex;flex-direction:column;gap:.3rem;color:inherit;text-decoration:none;box-shadow:var(--shadow-card);min-width:0;transition:transform .18s var(--ease-ds),box-shadow .18s var(--ease-ds)}
a.kpi:hover{transform:translateY(-2px);box-shadow:var(--shadow-3d)}
a.kpi:focus-visible{outline:2px solid var(--tone);outline-offset:2px}
.kpi.tone-coral{--tone:hsl(var(--coral))}.kpi.tone-info{--tone:hsl(var(--info))}.kpi.tone-success{--tone:hsl(var(--success))}.kpi.tone-navy{--tone:hsl(var(--navy-light))}.kpi.tone-warning{--tone:hsl(var(--warning))}.kpi.tone-danger{--tone:var(--admin-danger)}
.dark .kpi.tone-navy{--tone:hsl(205 45% 62%)}
.kpi-top{display:flex;align-items:flex-start;justify-content:space-between}
.kpi .kpi-icon{width:2.6rem;height:2.6rem;border-radius:14px;display:grid;place-items:center;background:color-mix(in srgb,var(--tone) 14%,transparent);color:var(--tone);box-shadow:0 6px 16px -8px var(--tone)}
.kpi .kpi-icon .nav-icon{width:1.3rem;height:1.3rem;stroke-width:1.9}
.kpi-meta{display:flex;gap:.3rem;color:var(--admin-muted)}
.kpi-meta .nav-icon{width:.95rem;height:.95rem}
.kpi-help{cursor:help}
.kpi strong{font-size:clamp(1.2rem,1rem + .8vw,1.6rem);line-height:1.2;font-variant-numeric:tabular-nums;overflow-wrap:anywhere;margin-top:.35rem}
.kpi .kpi-label{font-weight:700;background:linear-gradient(transparent 62%,color-mix(in srgb,var(--tone) 22%,transparent) 62%);width:fit-content;max-width:100%}
.kpi small{color:var(--admin-muted);overflow-wrap:anywhere}
.kpi.warn{border-color:color-mix(in srgb,var(--admin-danger) 45%,transparent)}
```

ومهم: تعديل `.kpi-grid` الخاص بالموبايل (كارتين في الصف، من commit `0b1f997`) لازم يفضل، فنشوف الـmedia query الموجودة ونسيبها زي ما هي. وكلاسات `.kpi` و`.kpi-icon` مستخدمة كمان في `crm-funnel` و`crm-sla` و`crm-lead`، والتعديل متوافق معاهم، لأن الكروت اللي مالهاش tone بتاخد لون `--admin-accent`.
- [ ] **Step 3:** `php artisan test --compact tests/Feature/Operating/CrmDashboardTest.php tests/Feature/Operating/CrmFunnelTest.php tests/Feature/Operating/CrmSlaBoardTest.php`، والمتوقع PASS.
- [ ] **Step 4:** commit `feat(crm): tone-coded KPI cards with icons, help hints and overflow-safe values`.

### Task 6: مهام اليوم والأقسام التفصيلية (FIN-22 و FIN-24)

**Files:** `crm-dashboard.blade.php` (سطر 18–25)، و`OperatingCrmReports.php:78`، و`operating.css`، و`CrmDashboardTest.php`.

- [ ] **Step 1: التست:**

```php
    public function test_todays_tasks_show_kind_and_overdue_status(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $rep = $this->operatingUser('sales');
        $employee = $this->employeeFor($rep);
        $this->operatingRecord('activities', 'planned', ['kind' => 'call', 'assignee_id' => $employee, 'due_at' => '2026-10-20T09:00'], 'Morning call');
        $this->operatingRecord('activities', 'planned', ['kind' => 'meeting', 'assignee_id' => $employee, 'due_at' => '2026-10-20T15:00'], 'Afternoon meeting');

        $this->actingAs($rep)->get('/admin/operations/crm')->assertOk()->assertSeeInOrder(['Morning call', __('متأخرة'), 'Afternoon meeting', __('اليوم')]);
    }
```

- [ ] **Step 2:** نشغّله، والمتوقع يفشل.
- [ ] **Step 3: الـview** — مهام اليوم:

```blade
<section class="card"><div class="section-heading"><h2>@include('admin.icon',['name'=>'tasks','class'=>'nav-icon heading-icon']){{ __('مهام اليوم') }}</h2><a href="/admin/operations/activities/follow-ups">{{ __('عرض') }} ↗</a></div>
@forelse($today as $task)@php $taskData=$catalog->data($task); $late=($taskData['due_at']??'')<now()->format('Y-m-d\TH:i'); $kind=$taskData['kind']??null; @endphp<a class="operation-row" href="{{ !empty($taskData['opportunity_id']) ? '/admin/operations/crm/leads/'.$taskData['opportunity_id'] : '/admin/operations/record/'.$task->id }}"><b>{{ $task->title }}</b><span>@if($kind && isset($kinds[$kind]))<span class="badge">{{ $kinds[$kind] }}</span>@endif <span class="sla-badge {{ $late?'breached':'met' }}">{{ $late ? __('متأخرة') : __('اليوم') }}</span> <span dir="ltr">{{ substr($taskData['due_at'] ?? '', 11, 5) ?: str_replace('T',' ',$taskData['due_at'] ?? '') }}</span></span></a>
@empty<div class="empty-state">@include('admin.icon',['name'=>'calendar'])<p><b>{{ __('لا توجد مهام لليوم') }}</b><br>{{ __('كل شيء تحت السيطرة ✨') }}</p></div>@endforelse</section>
```

ملاحظة: المهام المتأخرة من أيام فاتت بتظهر هي كمان (الـquery بيقول `due_at <= اليوم`). في الحالة دي `substr(..., 11, 5)` هيظهر الساعة بس، فلازم نظهر التاريخ كمان لما اليوم مش النهارده: `{{ str_starts_with($taskData['due_at'] ?? '', today()->format('Y-m-d')) ? substr($taskData['due_at'], 11, 5) : str_replace('T',' ',$taskData['due_at'] ?? '') }}`.

`$kinds`: الـcontroller بيبعت للداشبورد `'kinds' => array_map(fn (array $label): string => $this->catalog->label($label), (array) collect($this->catalog->entity('activities')['fields'])->firstWhere('key', 'kind')['options'])`، بنفس طريقة `lead()`.

- [ ] **Step 4: توحيد الأقسام (FIN-24)** — كل `<h2>` في الداشبورد ياخد أيقونة `heading-icon`: SLA (`alert`)، والتذكيرات (`calendar`)، وأنشطتي (`activity`). وجزء أنشطتي والتقويم ومهام اليوم يتلفوا بـ`@if($access->can(auth()->user(),'activities'))` بدل ما يظهروا فاضيين. نتأكد إن `$access` موجود في الـview (الـcontroller بيبعت `crm` و`catalog`، ولو `access` مش موجود نبعته). والقسم الفاضي يستخدم `.empty-state` بنفس طريقة Task 3.
- [ ] **Step 5: الـCSS:**

```css
.section-heading h2{display:flex;align-items:center;gap:.5rem}
.heading-icon{width:1.15rem;height:1.15rem;color:var(--admin-accent)}
```

- [ ] **Step 6:** نضيف للـ`lang/en.json`: "متأخرة": "Overdue"، "اليوم": "Today"، "لا توجد مهام لليوم": "No tasks for today"، "كل شيء تحت السيطرة ✨": "You're all caught up ✨".
- [ ] **Step 7:** تستات `CrmDashboardTest` و`CrmCalendarTest`، وبعدين Pint، وبعدين commit `feat(crm): today's tasks with kind and status badges, unified dashboard sections`.

### Task 7: QA بالشكل (1366 و375، فاتح وداكن) وتحقق من FIN-25 وFIN-26

- [ ] **Step 1:** `npm run build` لو في أي asset بيعدي على Vite. ملف `operating.css` بيتقدم مباشرة من `public/`، فغالبا مش محتاجين.
- [ ] **Step 2:** `php artisan serve --port=8080` (`/c/dev-tools/php84/php.exe`). ندخل كـ`qa-qasalesmanager@findo.local` و`qa-qasalesrep@findo.local` بباسورد QA المحلي (اتفصيله في الميموري).
- [ ] **Step 3:** نصوّر بـPlaywright MCP، ولو مش شغال نستخدم سكريبت CDP، على `/admin/operations/crm` وبعدين `?tab=shared` و`?tab=following` و`?from=2026-10-10&to=2026-10-01` (لازم رسالة الخطأ تظهر) و`?scope=mine`، بعرض 1366 و375، في الوضع الفاتح والداكن.
- [ ] **Step 4:** نتأكد إن مافيش scroll أفقي (`scrollWidth <= clientWidth`)، ومافيش أخطاء في الـconsole، والإيرادات بعملتين ماتخرجش برا الكارت. محليا نحط ليد `won` بـUSD وليد تاني بـEGP، ودي داتا محلية بس.
- [ ] **Step 5:** نقارن بصورة المرجع `00-dashboard.jpg`: الترتيب والمسافات والحواف والشكل. ونتأكد إن **مافيش أي لون بنفسجي**، والألوان كلها coral وnavy.
- [ ] **Step 6:** لو في مشاكل نصلحها، وبعدين commit `fix(crm): dashboard visual QA fixes`.

### Task 8: التحقق النهائي وcommit وpush وdeploy

- [ ] **Step 1:** `php artisan test --compact` (التستات كلها)، والمتوقع كلها تعدي.
- [ ] **Step 2:** `vendor/bin/pint --dirty --format agent`.
- [ ] **Step 3:** ننقل ملفي المحادثات `firstconversation-2026-10-24-145322.txt` و`second2026-10-24-145338-1111conversation-2026-10-20-171206txt.txt` لـ`C:\Users\hp\AppData\Local\Temp\claude\c--work-projects-findogroup\ed96012e-52b6-457e-8d97-f1eee3dd94df\scratchpad\conversations\`، لأن فيهم بيانات دخول ومينفعش يتعملهم commit. وبعدين `git status`، والمتوقع يكون نضيف من أي ملف untracked.
- [ ] **Step 4:** commit للخطة نفسها، `docs(crm): FIN-19 dashboard completion plan`، وبعدين `git push origin main`، بنفس طريقة الشغل اللي فات اللي اتعمل merge على main واتعمله deploy.
- [ ] **Step 5:** `bin/server-deploy --dry-run`، والمتوقع كل الفحوصات تعدي.
- [ ] **Step 6:** `bin/server-deploy`. الـmigration `crm_lead_relations` بيتنفذ ضمن الـdeploy، ونتأكد من ده من الـoutput.
- [ ] **Step 7:** smoke للقراءة بس على الإنتاج: نطلب `https://findogroup.com/admin/operations/crm` ونتأكد إنه بيرد بـ302 للـlogin، يعني الصفحة شغالة ومافيش 500. ونتأكد إن الـmigration اتعمل.
- [ ] **Step 8:** نحدّث الميموري (`crm-tcrm-parity-plan.md`) ونكتب فيها اللي اتعمل.

## Self-Review

- **تغطية التاسكات:** FIN-20 في Task 4، وFIN-21 في Task 5، وFIN-22 في Task 6، وFIN-23 في Tasks 1–3، وFIN-24 في Task 6، وFIN-25 في Task 4 و7، وFIN-26 في Task 5 و7، والـdeploy في Task 8.
- **ثبات الأسماء:** `OperatingLeadRelations::SHARED` و`FOLLOWING` و`leadIds()` و`share()` و`unshare()` و`toggleFollow()` و`sharedWith()` و`isFollowing()` و`shareableUsers()` نفس الأسماء في كل المهام. و`query(..., $action)` في Task 1 و2.
- **المخاطر:** (1) تغيير توقيع `OperatingAccess::query` آمن، لأن البارامتر الجديد اختياري. (2) الـ`.kpi` مشتركة مع صفحات تانية، والـtone الافتراضي `--admin-accent`. (3) لو `insertOrIgnore` فيه مشكلة مع PostgreSQL والـunique، نستخدم `exists()` قبل الـinsert.

## إضافة أثناء التنفيذ: نفس الشكل على مستوى الـERP كله (بطلب المستخدم)

المستخدم وضّح إن شكل TCRM مطلوب على كل صفحات الـERP مش لوحة CRM بس، وبألوان Findo. ده اللي اتعمل:

- **الشريط الجانبي** (`layout.blade.php`): أيقونة لكل مجموعة ولكل صفحة، والصفحة الحالية بتظهر كقرص coral، والمجموعات بتتقفل وتتفتح بسهم، وتحت بطاقة المستخدم بحرف اسمه، وزرار خروج واضح.
- **الشريط العلوي**: أزرار دائرية للإشعارات (بعدد الجديد) واللغة والوضع الداكن.
- **رؤوس الصفحات** (`.page-heading`): بطاقة فاتحة فيها لمسة coral في كل الصفحات. البانر الداكن متدرج الكحلي في لوحة CRM بس، زي TCRM.
- **الجداول والأزرار والنماذج**: حقول بحدود واضحة وإطار focus باللون الأساسي، وأزرار موحدة (أساسي، ثانوي، خطر، نصي)، وجداول بصف عناوين ثابت وتظليل الصف عند المرور، وشارات حالة ملوّنة حسب نوع الحالة، وصفحات (pagination) بأزرار، وزرار "الأعمدة" و"التصدير" على شكل أزرار، وإشعارات بشريط ملوّن.
- **كروت الأرقام** (`.stat`) في كل الصفحات بنفس شكل كروت لوحة CRM، وعدد الأعمدة بيتحدد حسب عدد الكروت.
- **النصوص الطويلة**: كلمة طويلة من غير مسافات بقت تنكسر جوه الكارت بدل ما توسّع الصفحة (المشكلة اللي ظهرت في سجل العمليات).
- **التحقق**: فحص تلقائي لـ127 صفحة من الشريط الجانبي بعرض 1366 و375 (254 عرض): مافيش scroll أفقي، ولا زرار اتكسر، ولا صفحة فشلت.
