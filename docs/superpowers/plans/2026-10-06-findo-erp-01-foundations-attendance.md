# Findo ERP — المرحلة 0 (أساسيات) + المرحلة 1 (الحضور والنقاط والبونص): خطة التنفيذ

> **For agentic workers:** REQUIRED SUB-SKILL: Use `subagent-driven-development` (recommended) or `executing-plans` to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.
>
> **قبل أي شيء في السيشن الجديدة:**
> 1. اقرأ `docs/superpowers/plans/2026-10-06-findo-erp-00-roadmap.md` بالكامل، **خصوصًا القسم 2 (كيف بُني النظام والقواعد الثابتة)** والقسم 4 (القرارات).
> 2. اقرأ `CLAUDE.md` في جذر المشروع. وإن وُجد `.ai/rules/index.md` فاقرأ القواعد المطابقة (لم يكن موجودًا وقت كتابة الخطة).
> 3. تأكد أن شجرة العمل نظيفة (`git status`)، ثم أنشئ الفرع: `git checkout -b feature/erp-phase-1-attendance`.
> 4. نفّذ التاسكات **بالترتيب**: كل تاسك يعتمد على ما قبله (الأسماء والتواقيع في بلوك Interfaces).
> 5. إن اختلف الكود الفعلي عن المقتطفات المقتبسة هنا (أرقام أسطر أو نص)، فابحث بالنص المميز (grep) وطبّق نفس المعنى، ولا تفرض المقتطف حرفيًا.

**Goal:** نظام حضور وانصراف بالنقاط (🟢/⚫/🔴) مع قاعدة الغياب المتتالي، ولوحة HR يومية، وتقرير شهري يحسب الالتزام والبونص (25–40$)، والإشعارات الأربعة المطلوبة، مبني بالكامل داخل محرّك التشغيل الحالي.

**Architecture:** كيانا `attendance` و`holidays` يُضافان إلى `config/operating.php` (التخزين في `operating_records`، فيعمل التدقيق والصلاحيات وشاشات القائمة والسجل العامة تلقائيًا). المنطق كله في service جديد `App\Services\OperatingAttendance`. التسجيل من الموظف عبر `AttendanceController` (وقت السيرفر، وليس إدخال المستخدم)، والتصحيح اليدوي لـHR عبر `OperatingWorkflow::save()` العام مع اشتقاق القيم. أمر مجدول يغلق اليوم ويسجّل النقاط الحمراء، وآخر يرسل تقرير البونص الشهري. الإشعارات عبر Laravel Notifications (database + mail).

**Tech Stack:** Laravel 13.34، PHP 8.4، PHPUnit 12.5 (SQLite in-memory)، PostgreSQL، Blade، Carbon 3.14 (`diffInMinutes` يرجع **float بإشارة**: `$a->diffInMinutes($b)` موجبة إذا كانت `$b` بعد `$a`).

**Spec:** القسم 1 من ملف الـroadmap (مواصفة الحضور بالنقاط) + القرارات D1، D2، D6، D7، D8 في القسم 4.2.

## Global Constraints
- لا dependencies جديدة (composer/npm).
- كل مسار جديد يبدأ بـ`/admin/operations/` ويُسجَّل **قبل** `Route::get('/operations/{type}/create', …)` في `routes/admin.php`.
- لا حذف فعلي، وكل تعديل يكتب `operating_events` بـreason.
- النصوص العربية مفاتيح `__()`، والإنجليزية في `lang/en.json`. رسائل الأخطاء `$this->workflow->fail($ar, $en)`.
- قيم البونص: الحد الأدنى **25**، الحد الأقصى **40**، العملة **USD**، وحد الاستحقاق الافتراضي **80%**.
- قاعدة الغياب: **2** يومي عمل متتاليين بلا تسجيل.
- أيام العمل الافتراضية: الأحد(0) → الخميس(4)، 09:00 → 17:00، سماحية 0 دقيقة. Carbon: `dayOfWeek` 0 = الأحد.
- المنطقة الزمنية `Africa/Cairo`.
- صيغة تخزين الأوقات: `Y-m-d\TH:i` (تطابق `<input type="datetime-local">`).
- بعد أي تعديل PHP: `vendor/bin/pint --dirty --format agent`.
- تقويم مرجعي للاختبارات: 2026-10-02 جمعة، 10-03 سبت، **10-04 أحد**، 10-05 اثنين، 10-06 ثلاثاء، 10-07 أربعاء، 10-08 خميس.

---

## File Structure

| الملف | جديد/تعديل | المسؤولية |
|---|---|---|
| `tests/TestCase.php` | جديد | Base test case |
| `tests/Concerns/BuildsOperatingRecords.php` | جديد | Helpers لإنشاء users/employees/records في الاختبارات |
| `tests/Feature/Operating/MigrationsSmokeTest.php` | جديد | يثبت أن الـschema يعمل على SQLite |
| `database/migrations/xxxx_create_notifications_table.php` | جديد (artisan) | جدول الإشعارات |
| `app/Notifications/OperatingAlert.php` | جديد | إشعار عام ثنائي اللغة |
| `app/Services/OperatingNotifier.php` | جديد | تحديد المستلمين (أدوار، موظف، مدير مباشر) والإرسال |
| `app/Http/Controllers/NotificationController.php` | جديد | قائمة الإشعارات وتعليمها كمقروءة |
| `resources/views/admin/operating/notifications.blade.php` | جديد | صفحة الإشعارات |
| `config/operating.php` | تعديل | كيانا `attendance` و`holidays`، وحالتا absent/excused، وحقلا الوردية للموظف، ومفتاح `attendance` للقواعد |
| `app/Services/OperatingAccess.php` | تعديل | قراءة الحضور لأي موظف مرتبط، والكتابة لـHR فقط |
| `app/Services/OperatingWorkflow.php` | تعديل | نوع `time`، واشتقاق قيم الحضور ومنع التكرار، وبوابات انتقال الحضور |
| `app/Services/OperatingAttendance.php` | جديد | القواعد، الوردية، يوم العمل، الاشتقاق، punch، إغلاق اليوم، الغياب المتتالي، الملخص الشهري، البونص |
| `app/Http/Controllers/AttendanceController.php` | جديد | check-in، check-out، اللوحة اليومية، التقرير الشهري + CSV |
| `app/Http/Controllers/OperatingController.php` | تعديل | widget الحضور في overview، وتخفيف signals، وإعدادات الحضور |
| `app/Console/Commands/CloseAttendanceDay.php` | جديد | `operating:attendance-close-day` |
| `app/Console/Commands/SendAttendanceMonthlyReport.php` | جديد | `operating:attendance-monthly-report` |
| `routes/admin.php` | تعديل | المسارات الجديدة |
| `routes/console.php` | تعديل | الجدولة |
| `resources/views/admin/operating/attendance-board.blade.php` | جديد | لوحة HR اليومية |
| `resources/views/admin/operating/attendance-monthly.blade.php` | جديد | التقرير الشهري + البونص |
| `resources/views/admin/operating/attendance-widget.blade.php` | جديد | widget الموظف (partial) |
| `resources/views/admin/operating/overview.blade.php` | تعديل | تضمين الـwidget |
| `resources/views/admin/operating/settings.blade.php` | تعديل | نموذج قواعد الحضور |
| `resources/views/admin/layout.blade.php` | تعديل | روابط اللوحة والتقرير والإشعارات |
| `public/admin-assets/operating.css` | تعديل | نقاط الألوان وwidget |
| `lang/en.json` | تعديل | الترجمات |
| `tests/Feature/Operating/*.php` | جديد | اختبارات كل تاسك |

---

# المرحلة 0: الأساسيات

### Task 0.1: بنية الاختبارات + smoke test

**Files:**
- Create: `tests/TestCase.php`
- Create: `tests/Concerns/BuildsOperatingRecords.php`
- Create: `tests/Feature/Operating/MigrationsSmokeTest.php`
- Create: `tests/Unit/.gitkeep`

**Interfaces:**
- Produces: `Tests\TestCase`، والـtrait `Tests\Concerns\BuildsOperatingRecords` بالدوال:
  - `operatingUser(string $role): User`
  - `employeeFor(User $user, array $data = [], string $status = 'active'): int`
  - `operatingRecord(string $type, string $status, array $data, string $title = 'Record', ?int $ownerId = null): int`
  - `recordData(int $id): array`

- [ ] **Step 1: أنشئ `tests/TestCase.php`**

```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    //
}
```

- [ ] **Step 2: أنشئ `tests/Concerns/BuildsOperatingRecords.php`**

```php
<?php

namespace Tests\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait BuildsOperatingRecords
{
    protected function operatingUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'active' => true]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function employeeFor(User $user, array $data = [], string $status = 'active'): int
    {
        return $this->operatingRecord('employees', $status, array_merge([
            'job_title' => 'Developer',
            'department' => 'delivery',
            'manager_id' => null,
            'user_id' => $user->id,
        ], $data), $user->name);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function operatingRecord(string $type, string $status, array $data, string $title = 'Record', ?int $ownerId = null): int
    {
        $id = DB::table('operating_records')->insertGetId([
            'uuid' => Str::uuid()->toString(),
            'type' => $type,
            'title' => $title,
            'status' => $status,
            'owner_id' => $ownerId,
            'created_by' => $ownerId,
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        foreach ($data as $field => $value) {
            if (str_ends_with($field, '_id') && $field !== 'user_id' && is_int($value)) {
                DB::table('operating_links')->insert(['record_id' => $id, 'target_id' => $value, 'field' => $field]);
            }
        }

        return $id;
    }

    /**
     * @return array<string, mixed>
     */
    protected function recordData(int $id): array
    {
        return json_decode(DB::table('operating_records')->where('id', $id)->value('data'), true);
    }
}
```

- [ ] **Step 3: أنشئ الـsmoke test**

Run: `php artisan make:test --phpunit Operating/MigrationsSmokeTest --no-interaction`، ثم استبدل محتواه بـ:

```php
<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class MigrationsSmokeTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_owner_can_open_the_operating_overview(): void
    {
        $owner = $this->operatingUser('owner');

        $this->actingAs($owner)->get('/admin')->assertOk();
    }
}
```

وأنشئ `tests/Unit/.gitkeep` (ملف فارغ) لأن `phpunit.xml` يعرّف suite باسم Unit.

- [ ] **Step 4: شغّل الاختبار**

Run: `php artisan test --compact tests/Feature/Operating/MigrationsSmokeTest.php`
Expected: PASS.
إن فشل بسبب migration تستخدم SQL خاصًا بـPostgreSQL، فاحمِ ذلك الجزء بـ`if (DB::getDriverName() === 'pgsql')` داخل الـmigration نفسها، وأعد التشغيل. وإن فشل بسبب Vite manifest في الـlayout، فشغّل `npm run build` مرة واحدة.

- [ ] **Step 5: Commit**

```bash
git add tests
git commit -m "test: add PHPUnit base, operating record helpers and smoke test

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 0.2: بنية الإشعارات

**Files:**
- Create (artisan): `database/migrations/*_create_notifications_table.php`
- Create: `app/Notifications/OperatingAlert.php`
- Create: `app/Services/OperatingNotifier.php`
- Create: `app/Http/Controllers/NotificationController.php`
- Create: `resources/views/admin/operating/notifications.blade.php`
- Modify: `routes/admin.php`
- Modify: `resources/views/admin/layout.blade.php`
- Modify: `lang/en.json`
- Test: `tests/Feature/Operating/NotificationsTest.php`

**Interfaces:**
- Produces:
  - `new OperatingAlert(string $kind, array $title, array $body, ?string $url = null, bool $mail = false)`، حيث `$title` و`$body` بالشكل `['ar' => string, 'en' => string]`. `toArray()` يرجع `['kind', 'title', 'body', 'url']`.
  - `OperatingNotifier::roles(array $roles): Collection<User>`
  - `OperatingNotifier::employeeUser(int $employeeId): ?User`
  - `OperatingNotifier::managerUser(int $employeeId): ?User`
  - `OperatingNotifier::send(iterable $users, OperatingAlert $alert): void` (يتجاهل null والمكرر)
  - Routes: `GET /admin/operations/notifications`، و`POST /admin/operations/notifications/read`

- [ ] **Step 1: اكتب الاختبار الفاشل**

Run: `php artisan make:test --phpunit Operating/NotificationsTest --no-interaction`، ثم:

```php
<?php

namespace Tests\Feature\Operating;

use App\Notifications\OperatingAlert;
use App\Services\OperatingNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_alert_reaches_employee_and_direct_manager_once(): void
    {
        $managerUser = $this->operatingUser('delivery');
        $managerId = $this->employeeFor($managerUser);
        $employeeUser = $this->operatingUser('developer');
        $employeeId = $this->employeeFor($employeeUser, ['manager_id' => $managerId]);
        $notifier = app(OperatingNotifier::class);

        $notifier->send([$notifier->employeeUser($employeeId), $notifier->managerUser($employeeId), $notifier->managerUser($employeeId), null], new OperatingAlert('test', ['ar' => 'عنوان', 'en' => 'Title'], ['ar' => 'نص', 'en' => 'Body'], '/admin'));

        $this->assertSame(1, $employeeUser->notifications()->count());
        $this->assertSame(1, $managerUser->notifications()->count());
        $this->assertSame('test', $employeeUser->notifications()->first()->data['kind']);
    }

    public function test_user_sees_and_clears_own_notifications(): void
    {
        $user = $this->operatingUser('developer');
        $user->notify(new OperatingAlert('test', ['ar' => 'تنبيه تجريبي', 'en' => 'Test alert'], ['ar' => 'نص', 'en' => 'Body']));

        $this->actingAs($user)->get('/admin/operations/notifications')->assertOk()->assertSee('تنبيه تجريبي');
        $this->actingAs($user)->post('/admin/operations/notifications/read')->assertRedirect();

        $this->assertSame(0, $user->unreadNotifications()->count());
    }
}
```

- [ ] **Step 2: شغّله وتأكد من الفشل**

Run: `php artisan test --compact tests/Feature/Operating/NotificationsTest.php`
Expected: FAIL (`Class "App\Notifications\OperatingAlert" not found`).

- [ ] **Step 3: أنشئ جدول الإشعارات**

Run: `php artisan make:notifications-table --no-interaction`، ثم `php artisan migrate --no-interaction` (محليًا على pgsql).

- [ ] **Step 4: أنشئ `app/Notifications/OperatingAlert.php`**

Run: `php artisan make:notification OperatingAlert --no-interaction`، ثم استبدل المحتوى:

```php
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OperatingAlert extends Notification
{
    use Queueable;

    /**
     * @param  array{ar: string, en: string}  $title
     * @param  array{ar: string, en: string}  $body
     */
    public function __construct(public string $kind, public array $title, public array $body, public ?string $url = null, public bool $mail = false) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $this->mail ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title['ar'].' / '.$this->title['en'])
            ->line($this->body['ar'])
            ->line($this->body['en'])
            ->when($this->url, fn (MailMessage $message): MailMessage => $message->action('Findo Group', url($this->url)));
    }

    /**
     * @return array{kind: string, title: array{ar: string, en: string}, body: array{ar: string, en: string}, url: ?string}
     */
    public function toArray(object $notifiable): array
    {
        return ['kind' => $this->kind, 'title' => $this->title, 'body' => $this->body, 'url' => $this->url];
    }
}
```

- [ ] **Step 5: أنشئ `app/Services/OperatingNotifier.php`**

```php
<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\OperatingAlert;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class OperatingNotifier
{
    public function __construct(private OperatingCatalog $catalog) {}

    /**
     * @param  list<string>  $roles
     * @return Collection<int, User>
     */
    public function roles(array $roles): Collection
    {
        return User::query()->where('active', true)->whereIn('role', $roles)->get();
    }

    public function employeeUser(int $employeeId): ?User
    {
        $employee = DB::table('operating_records')->where('type', 'employees')->find($employeeId);
        $userId = $employee ? ($this->catalog->data($employee)['user_id'] ?? null) : null;

        return $userId ? User::query()->where('active', true)->find($userId) : null;
    }

    public function managerUser(int $employeeId): ?User
    {
        $employee = DB::table('operating_records')->where('type', 'employees')->find($employeeId);
        $managerId = $employee ? ($this->catalog->data($employee)['manager_id'] ?? null) : null;

        return $managerId ? $this->employeeUser((int) $managerId) : null;
    }

    /**
     * @param  iterable<User|null>  $users
     */
    public function send(iterable $users, OperatingAlert $alert): void
    {
        $recipients = collect($users)->filter()->unique('id')->values();
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, $alert);
        }
    }
}
```

- [ ] **Step 6: أنشئ `app/Http/Controllers/NotificationController.php`**

```php
<?php

namespace App\Http\Controllers;

use App\Services\Cms;
use App\Services\OperatingAccess;
use App\Services\OperatingCatalog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends Controller
{
    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access) {}

    public function index(Request $request): Response
    {
        return response()->view('admin.operating.notifications', [
            'notifications' => $request->user()->notifications()->latest()->paginate(30),
            'collections' => Cms::COLLECTIONS,
            'catalog' => $this->catalog,
            'access' => $this->access,
        ]);
    }

    public function markRead(Request $request): Response
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', __('تم تعليم الإشعارات كمقروءة.'));
    }
}
```

- [ ] **Step 7: أنشئ `resources/views/admin/operating/notifications.blade.php`**

```blade
@extends('admin.layout')
@section('title',__('الإشعارات'))
@section('content')
<div class="page-heading"><div><h1>{{ __('الإشعارات') }}</h1><p class="muted">{{ __('تنبيهات التشغيل الموجهة لك.') }}</p></div><form method="post" action="/admin/operations/notifications/read">@csrf<button class="button secondary">{{ __('تعليم الكل كمقروء') }}</button></form></div>
<section class="card">@forelse($notifications as $notification)<a class="operation-row" href="{{ $notification->data['url'] ?? '#' }}"><b>{{ $catalog->label($notification->data['title']) }}@if(! $notification->read_at) •@endif</b><span>{{ $catalog->label($notification->data['body']) }} · {{ $notification->created_at->format('Y-m-d H:i') }}</span></a>@empty<p class="muted">{{ __('لا توجد إشعارات.') }}</p>@endforelse</section>
{{ $notifications->links() }}
@endsection
```

- [ ] **Step 8: المسارات**: في `routes/admin.php` أضف `use App\Http\Controllers\NotificationController as N;` بجانب الـuse الموجودة، وأضف السطرين **قبل** `Route::get('/operations/{type}/create', …)` مباشرة:

```php
        Route::get('/operations/notifications', [N::class, 'index']);
        Route::post('/operations/notifications/read', [N::class, 'markRead']);
```

- [ ] **Step 9: الـsidebar**: في `resources/views/admin/layout.blade.php`، بعد سطر رابط `/admin/operations/reports` أضف:

```blade
@php $unreadAlerts = auth()->user()->unreadNotifications()->count(); @endphp<a href="/admin/operations/notifications">{{ __('الإشعارات') }}@if($unreadAlerts) <b class="badge">{{ $unreadAlerts }}</b>@endif</a>
```

وفي `public/admin-assets/operating.css` أضف في آخر الملف:

```css
.badge{display:inline-grid;place-items:center;min-width:1.4em;padding:0 .4em;border-radius:999px;background:#f47b20;color:#fff;font-size:.75em;margin-inline-start:.35em}
```

- [ ] **Step 10: الترجمات**: أضف إلى `lang/en.json` (داخل الكائن، مع الحفاظ على صحة الـJSON):

```json
"الإشعارات": "Notifications",
"تنبيهات التشغيل الموجهة لك.": "Operating alerts addressed to you.",
"تعليم الكل كمقروء": "Mark all as read",
"تم تعليم الإشعارات كمقروءة.": "Notifications marked as read.",
"لا توجد إشعارات.": "No notifications."
```

- [ ] **Step 11: شغّل الاختبار**

Run: `php artisan test --compact tests/Feature/Operating/NotificationsTest.php`
Expected: PASS.

- [ ] **Step 12: Pint + Commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat(operating): add database/mail notifications with recipient resolver and inbox

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

# المرحلة 1: الحضور والانصراف

### Task 1.1: تعريف الكيانات + الصلاحيات + نوع الحقل `time`

**Files:**
- Modify: `config/operating.php`
- Modify: `app/Services/OperatingAccess.php` (`can()`)
- Modify: `app/Services/OperatingWorkflow.php` (`save()`: الـmatch الخاص بالقواعد)
- Test: `tests/Feature/Operating/AttendanceAccessTest.php`

**Interfaces:**
- Produces:
  - entity `attendance`: الحقول `assignee_id, work_date, check_in, check_out, mode (office|remote), source (web|device|manual|system), note`. الحالات `open, closed, absent, excused, archived`، و`private: true`. القيم المحسوبة في data: `point (green|black|red|null), late_minutes, early_leave_minutes, overtime_minutes, worked_minutes`.
  - entity `holidays`: الحقل `holiday_date`. الحالات `active, archived`.
  - حقلا `employees`: `shift_start`، `shift_end` (type `time`).
  - `config('operating.attendance')`: `work_start, work_end, work_days, grace_minutes, bonus_min, bonus_max, bonus_eligibility_percent, absence_alert_days, currency`.

- [ ] **Step 1: اكتب الاختبار الفاشل**

Run: `php artisan make:test --phpunit Operating/AttendanceAccessTest --no-interaction`، ثم:

```php
<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class AttendanceAccessTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_employee_sees_only_own_attendance_and_manager_sees_team(): void
    {
        $managerUser = $this->operatingUser('delivery');
        $managerId = $this->employeeFor($managerUser);
        $aliceUser = $this->operatingUser('developer');
        $aliceId = $this->employeeFor($aliceUser, ['manager_id' => $managerId]);
        $bobUser = $this->operatingUser('support');
        $bobId = $this->employeeFor($bobUser);
        $this->operatingRecord('attendance', 'closed', ['assignee_id' => $aliceId, 'work_date' => '2026-10-04'], 'Alice day');
        $this->operatingRecord('attendance', 'closed', ['assignee_id' => $bobId, 'work_date' => '2026-10-04'], 'Bob day');

        $this->actingAs($aliceUser)->getJson('/admin/operations/attendance?format=json')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Alice day');
        $this->actingAs($bobUser)->getJson('/admin/operations/attendance?format=json')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Bob day');
        $this->actingAs($managerUser)->getJson('/admin/operations/attendance?format=json')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Alice day');
    }

    public function test_only_hr_and_owner_can_write_attendance_or_holidays(): void
    {
        $managerUser = $this->operatingUser('delivery');
        $this->employeeFor($managerUser);

        $this->actingAs($managerUser)->get('/admin/operations/attendance/create')->assertForbidden();
        $this->actingAs($managerUser)->get('/admin/operations/holidays/create')->assertForbidden();
        $this->actingAs($this->operatingUser('hr'))->get('/admin/operations/attendance/create')->assertOk();
    }

    public function test_shift_start_must_be_a_valid_time(): void
    {
        $hr = $this->operatingUser('hr');

        $this->actingAs($hr)->post('/admin/operations/employees', ['title' => 'New hire', 'reason' => 'Onboarding', 'data' => ['job_title' => 'QA', 'department' => 'delivery', 'shift_start' => '25:99']])->assertSessionHasErrors('data.shift_start');
        $this->actingAs($hr)->post('/admin/operations/employees', ['title' => 'New hire', 'reason' => 'Onboarding', 'data' => ['job_title' => 'QA', 'department' => 'delivery', 'shift_start' => '10:00']])->assertSessionHasNoErrors();
    }
}
```

- [ ] **Step 2: شغّله وتأكد من الفشل**

Run: `php artisan test --compact tests/Feature/Operating/AttendanceAccessTest.php`
Expected: FAIL (404 لأن entity `attendance` غير موجودة).

- [ ] **Step 3: عدّل `config/operating.php`** (هو JSON داخل heredoc، فحافظ على صحة الـJSON):

(أ) داخل `"entities"`، بعد كيان `"onboarding"`، أضف:

```json
"attendance": {
  "label": {"ar": "الحضور والانصراف", "en": "Attendance"},
  "group": "people",
  "pages": [],
  "fields": [
    {"key": "assignee_id", "label": {"ar": "الموظف", "en": "Employee"}, "type": "relation", "required": true, "entity": "employees"},
    {"key": "work_date", "label": {"ar": "يوم العمل", "en": "Work date"}, "type": "date", "required": true},
    {"key": "check_in", "label": {"ar": "وقت الحضور", "en": "Check-in"}, "type": "datetime-local", "required": false},
    {"key": "check_out", "label": {"ar": "وقت الانصراف", "en": "Check-out"}, "type": "datetime-local", "required": false},
    {"key": "mode", "label": {"ar": "مكان العمل", "en": "Work mode"}, "type": "select", "required": true, "options": {"office": {"ar": "من المقر", "en": "Office"}, "remote": {"ar": "من المنزل", "en": "Work from home"}}},
    {"key": "source", "label": {"ar": "مصدر التسجيل", "en": "Source"}, "type": "select", "required": true, "options": {"web": {"ar": "النظام", "en": "Web"}, "device": {"ar": "جهاز البصمة", "en": "Device"}, "manual": {"ar": "تصحيح يدوي", "en": "Manual correction"}, "system": {"ar": "تلقائي", "en": "System"}}},
    {"key": "note", "label": {"ar": "ملاحظة / سبب التصحيح", "en": "Note / correction reason"}, "type": "textarea", "required": false}
  ],
  "statuses": ["open", "closed", "absent", "excused", "archived"],
  "private": true
},
"holidays": {
  "label": {"ar": "العطلات الرسمية", "en": "Public holidays"},
  "group": "people",
  "pages": [],
  "fields": [
    {"key": "holiday_date", "label": {"ar": "التاريخ", "en": "Date"}, "type": "date", "required": true}
  ],
  "statuses": ["active", "archived"]
},
```

(ب) داخل `"statuses"` أضف (`open` و`closed` موجودتان بالفعل):

```json
"absent": {"ar": "غياب / عدم تسجيل", "en": "Absent / no check-in"},
"excused": {"ar": "غياب بعذر", "en": "Excused"},
```

(ج) داخل `entities.employees.fields`، بعد حقل `weekly_hours`، أضف:

```json
{"key": "shift_start", "label": {"ar": "بداية الوردية", "en": "Shift start"}, "type": "time", "required": false},
{"key": "shift_end", "label": {"ar": "نهاية الوردية", "en": "Shift end"}, "type": "time", "required": false},
```

(د) في المستوى الأعلى، بعد `"field_ownership": {…}`، أضف مفتاحًا جديدًا (ضع الفاصلة بعد قوس field_ownership):

```json
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
```

تحقق من صحة الملف:
Run: `php -r '$c = require "config/operating.php"; var_dump(isset($c["entities"]["attendance"], $c["entities"]["holidays"], $c["attendance"]["bonus_max"]));'`
Expected: `bool(true)`.

- [ ] **Step 4: عدّل `OperatingAccess::can()`**: مباشرة بعد سطر `if ($user->role === 'owner') { return true; }` أضف:

```php
        if (in_array($type, ['attendance', 'holidays'], true) && $action !== 'read') {
            return $user->role === 'hr';
        }
        if ($type === 'attendance' && $this->employeeId($user)) {
            return true;
        }
```

(السطر الثاني يسمح لأي موظف مرتبط بحساب، بما فيه `support` و`data_entry` غير الموجودين في قراءة `people`، بقراءة الحضور. والنطاق الفعلي يحدده `query()` عبر `private`: نفسه + فريقه إن كان `delivery`، وowner وhr يرون الكل.)

- [ ] **Step 5: عدّل `OperatingWorkflow::save()`**: داخل `match ($field['type'])` أضف السطر قبل `'checkbox' => 'boolean',`:

```php
                'time' => 'date_format:H:i',
```

- [ ] **Step 6: شغّل الاختبار**

Run: `php artisan test --compact tests/Feature/Operating/AttendanceAccessTest.php`
Expected: PASS (الاختبارات الثلاثة).

- [ ] **Step 7: Pint + Commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat(attendance): define attendance and holidays entities, shift fields and access rules

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 1.2: `OperatingAttendance`: القواعد، يوم العمل، الاشتقاق، البونص

**Files:**
- Create: `app/Services/OperatingAttendance.php`
- Test: `tests/Feature/Operating/AttendanceRulesTest.php`

**Interfaces:**
- Consumes: `config('operating.attendance')`، وآخر event بالـaction `configure_attendance` بتاريخ سريان ≤ اليوم (نفس أسلوب `AdminLocale` مع قواعد `configure_rules`، لتُحترم تواريخ السريان المستقبلية)، و`OperatingNotifier` (Task 0.2).
- Produces (public):
  - `rules(): array`
  - `isWorkday(CarbonImmutable $date): bool`
  - `schedule(int $employeeId): array{start: string, end: string}`
  - `derive(array $data, string $status): array` (يضيف point و late_minutes و early_leave_minutes و overtime_minutes و worked_minutes)
  - `initialStatus(array $data): string`
  - `bonus(float $commitment): float`

- [ ] **Step 1: اكتب الاختبار الفاشل**

Run: `php artisan make:test --phpunit Operating/AttendanceRulesTest --no-interaction`، ثم:

```php
<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingAttendance;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class AttendanceRulesTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    private function derived(array $data, string $status = 'closed', array $employee = []): array
    {
        $employeeId = $this->employeeFor($this->operatingUser('developer'), $employee);

        return app(OperatingAttendance::class)->derive(array_merge(['assignee_id' => $employeeId, 'work_date' => '2026-10-04', 'check_in' => null, 'check_out' => null], $data), $status);
    }

    public function test_check_in_at_start_time_is_green(): void
    {
        $data = $this->derived(['check_in' => '2026-10-04T09:00', 'check_out' => '2026-10-04T17:00']);

        $this->assertSame('green', $data['point']);
        $this->assertSame(0, $data['late_minutes']);
        $this->assertSame(480, $data['worked_minutes']);
    }

    public function test_late_check_in_is_black_with_minutes_and_early_leave(): void
    {
        $data = $this->derived(['check_in' => '2026-10-04T09:17', 'check_out' => '2026-10-04T16:30']);

        $this->assertSame('black', $data['point']);
        $this->assertSame(17, $data['late_minutes']);
        $this->assertSame(30, $data['early_leave_minutes']);
        $this->assertSame(0, $data['overtime_minutes']);
    }

    public function test_missing_check_in_is_red_and_excused_has_no_point(): void
    {
        $this->assertSame('red', $this->derived([], 'absent')['point']);
        $this->assertNull($this->derived([], 'excused')['point']);
    }

    public function test_employee_shift_overrides_company_start_and_counts_overtime(): void
    {
        $data = $this->derived(['check_in' => '2026-10-04T09:55', 'check_out' => '2026-10-04T19:00'], 'closed', ['shift_start' => '10:00', 'shift_end' => '18:00']);

        $this->assertSame('green', $data['point']);
        $this->assertSame(60, $data['overtime_minutes']);
    }

    public function test_friday_and_holidays_are_not_workdays_and_carry_no_point(): void
    {
        $attendance = app(OperatingAttendance::class);
        $this->operatingRecord('holidays', 'active', ['holiday_date' => '2026-10-06'], 'National day');

        $this->assertFalse($attendance->isWorkday(CarbonImmutable::parse('2026-10-02')));
        $this->assertFalse($attendance->isWorkday(CarbonImmutable::parse('2026-10-06')));
        $this->assertTrue($attendance->isWorkday(CarbonImmutable::parse('2026-10-04')));
        $this->assertNull($this->derived(['work_date' => '2026-10-02', 'check_in' => '2026-10-02T11:00', 'check_out' => '2026-10-02T13:00'])['point']);
    }

    public function test_bonus_scales_from_25_to_40_above_eligibility(): void
    {
        $attendance = app(OperatingAttendance::class);

        $this->assertSame(0.0, $attendance->bonus(79.99));
        $this->assertSame(25.0, $attendance->bonus(80));
        $this->assertSame(32.5, $attendance->bonus(90));
        $this->assertSame(40.0, $attendance->bonus(100));
    }
}
```

- [ ] **Step 2: شغّله وتأكد من الفشل**

Run: `php artisan test --compact tests/Feature/Operating/AttendanceRulesTest.php`
Expected: FAIL (`Class "App\Services\OperatingAttendance" not found`).

- [ ] **Step 3: أنشئ `app/Services/OperatingAttendance.php`** (الجزء الأول، وتوسّعه التاسكات التالية):

```php
<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class OperatingAttendance
{
    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access, private OperatingWorkflow $workflow, private OperatingNotifier $notifier) {}

    /**
     * @return array{work_start: string, work_end: string, work_days: list<int>, grace_minutes: int, bonus_min: float, bonus_max: float, bonus_eligibility_percent: float, absence_alert_days: int, currency: string}
     */
    public function rules(): array
    {
        $saved = DB::table('operating_events')->where('action', 'configure_attendance')->where('after->effective_date', '<=', today()->toDateString())->latest('id')->value('after');
        $rules = array_replace(config('operating.attendance'), $saved ? json_decode($saved, true) : []);
        $rules['work_days'] = array_map('intval', $rules['work_days']);
        $rules['grace_minutes'] = (int) $rules['grace_minutes'];
        $rules['absence_alert_days'] = (int) $rules['absence_alert_days'];

        return $rules;
    }

    public function isWorkday(CarbonImmutable $date): bool
    {
        return in_array($date->dayOfWeek, $this->rules()['work_days'], true)
            && ! DB::table('operating_records')->where('type', 'holidays')->where('status', 'active')->where('data->holiday_date', $date->toDateString())->exists();
    }

    /**
     * @return array{start: string, end: string}
     */
    public function schedule(int $employeeId): array
    {
        $data = $this->catalog->data(DB::table('operating_records')->find($employeeId));
        $rules = $this->rules();

        return ['start' => ($data['shift_start'] ?? null) ?: $rules['work_start'], 'end' => ($data['shift_end'] ?? null) ?: $rules['work_end']];
    }

    /**
     * Derive the point and minute counters stored beside the editable fields.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function derive(array $data, string $status): array
    {
        $date = CarbonImmutable::parse($data['work_date']);
        $schedule = $this->schedule((int) $data['assignee_id']);
        $start = $date->setTimeFromTimeString($schedule['start']);
        $end = $date->setTimeFromTimeString($schedule['end']);
        $checkIn = empty($data['check_in']) ? null : CarbonImmutable::parse($data['check_in']);
        $checkOut = empty($data['check_out']) ? null : CarbonImmutable::parse($data['check_out']);
        $workday = $this->isWorkday($date);
        $late = $workday && $checkIn && $checkIn->greaterThan($start->addMinutes($this->rules()['grace_minutes']));
        $data['point'] = match (true) {
            $status === 'excused', ! $workday => null,
            $checkIn === null => 'red',
            $late => 'black',
            default => 'green',
        };
        $data['late_minutes'] = $late ? (int) $start->diffInMinutes($checkIn) : 0;
        $data['worked_minutes'] = $checkIn && $checkOut ? max(0, (int) $checkIn->diffInMinutes($checkOut)) : 0;
        $data['early_leave_minutes'] = $workday && $checkOut && $checkOut->lessThan($end) ? (int) $checkOut->diffInMinutes($end) : 0;
        $data['overtime_minutes'] = ! $checkOut ? 0 : ($workday ? max(0, (int) $end->diffInMinutes($checkOut)) : $data['worked_minutes']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function initialStatus(array $data): string
    {
        return match (true) {
            empty($data['check_in']) => 'absent',
            empty($data['check_out']) => 'open',
            default => 'closed',
        };
    }

    public function bonus(float $commitment): float
    {
        $rules = $this->rules();
        if ($commitment < $rules['bonus_eligibility_percent']) {
            return 0.0;
        }
        if ($rules['bonus_eligibility_percent'] >= 100) {
            return (float) $rules['bonus_max'];
        }

        return round($rules['bonus_min'] + ($rules['bonus_max'] - $rules['bonus_min']) * ($commitment - $rules['bonus_eligibility_percent']) / (100 - $rules['bonus_eligibility_percent']), 2);
    }
}
```

- [ ] **Step 4: شغّل الاختبار**

Run: `php artisan test --compact tests/Feature/Operating/AttendanceRulesTest.php`
Expected: PASS (6 اختبارات).

- [ ] **Step 5: Pint + Commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat(attendance): add attendance rules, workday calendar, point derivation and bonus formula

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 1.3: Check-in / Check-out (punch) + إشعار التأخير

**Files:**
- Modify: `app/Services/OperatingAttendance.php` (إضافة `punch()` و`insertRecord()`)
- Create: `app/Http/Controllers/AttendanceController.php`
- Modify: `routes/admin.php`
- Test: `tests/Feature/Operating/AttendanceCheckInTest.php`

**Interfaces:**
- Consumes: `derive()` (Task 1.2)، و`OperatingNotifier::send/employeeUser`، و`OperatingWorkflow::event/fail`.
- Produces:
  - `punch(int $employeeId, CarbonImmutable $at, string $source, string $mode, string $direction, ?int $userId = null): object` (`$direction` = `'in'|'out'`). هذا هو المدخل الوحيد الذي سيستخدمه جهاز البصمة لاحقًا (A6).
  - Routes: `POST /admin/operations/attendance/check-in` (body: `mode`)، و`POST /admin/operations/attendance/check-out`.
  - Notification kind: `late_check_in`.

- [ ] **Step 1: اكتب الاختبار الفاشل**

Run: `php artisan make:test --phpunit Operating/AttendanceCheckInTest --no-interaction`، ثم:

```php
<?php

namespace Tests\Feature\Operating;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class AttendanceCheckInTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_on_time_check_in_earns_green_point_with_server_time(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-04 08:58:40'));
        $user = $this->operatingUser('developer');
        $employeeId = $this->employeeFor($user);

        $this->actingAs($user)->post('/admin/operations/attendance/check-in', ['mode' => 'remote', 'check_in' => '2026-10-04T07:00'])->assertSessionHasNoErrors();

        $record = DB::table('operating_records')->where('type', 'attendance')->sole();
        $data = json_decode($record->data, true);
        $this->assertSame('open', $record->status);
        $this->assertSame('2026-10-04T08:58', $data['check_in']);
        $this->assertSame('green', $data['point']);
        $this->assertSame('remote', $data['mode']);
        $this->assertSame($employeeId, $data['assignee_id']);
        $this->assertSame(0, $user->notifications()->count());
    }

    public function test_late_check_in_is_black_and_notifies_employee(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-04 09:12'));
        $user = $this->operatingUser('developer');
        $this->employeeFor($user);

        $this->actingAs($user)->post('/admin/operations/attendance/check-in', ['mode' => 'office']);

        $data = json_decode(DB::table('operating_records')->where('type', 'attendance')->value('data'), true);
        $this->assertSame('black', $data['point']);
        $this->assertSame(12, $data['late_minutes']);
        $this->assertSame('late_check_in', $user->notifications()->sole()->data['kind']);
    }

    public function test_second_check_in_is_rejected_and_check_out_closes_the_day(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-04 09:00'));
        $user = $this->operatingUser('qa');
        $this->employeeFor($user);
        $this->actingAs($user)->post('/admin/operations/attendance/check-in', ['mode' => 'office']);

        $this->actingAs($user)->post('/admin/operations/attendance/check-in', ['mode' => 'office'])->assertSessionHasErrors('workflow');

        $this->travelTo(CarbonImmutable::parse('2026-10-04 16:30'));
        $this->actingAs($user)->post('/admin/operations/attendance/check-out')->assertSessionHasNoErrors();
        $record = DB::table('operating_records')->where('type', 'attendance')->sole();
        $data = json_decode($record->data, true);
        $this->assertSame('closed', $record->status);
        $this->assertSame(450, $data['worked_minutes']);
        $this->assertSame(30, $data['early_leave_minutes']);
        $this->assertSame(['check_in', 'check_out'], DB::table('operating_events')->where('record_id', $record->id)->orderBy('id')->pluck('action')->all());
    }

    public function test_check_out_without_check_in_fails_and_unlinked_user_is_forbidden(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-04 17:00'));
        $user = $this->operatingUser('developer');
        $this->employeeFor($user);

        $this->actingAs($user)->post('/admin/operations/attendance/check-out')->assertSessionHasErrors('workflow');
        $this->actingAs($this->operatingUser('developer'))->post('/admin/operations/attendance/check-in', ['mode' => 'office'])->assertForbidden();
    }
}
```

- [ ] **Step 2: شغّله وتأكد من الفشل**

Run: `php artisan test --compact tests/Feature/Operating/AttendanceCheckInTest.php`
Expected: FAIL (404 للمسار).

- [ ] **Step 3: أضف إلى `OperatingAttendance`** في أعلى الملف: `use App\Notifications\OperatingAlert;` و`use Illuminate\Support\Str;`. ثم أضف الدوال:

```php
    /**
     * Single entry point for web buttons today and attendance devices later.
     */
    public function punch(int $employeeId, CarbonImmutable $at, string $source, string $mode, string $direction, ?int $userId = null): object
    {
        $record = DB::transaction(function () use ($employeeId, $at, $source, $mode, $direction, $userId): object {
            if (DB::getDriverName() === 'pgsql') {
                DB::select("SELECT pg_advisory_xact_lock(hashtext('findo-operating-gates'))");
            }
            $date = $at->toDateString();
            $stamp = $at->format('Y-m-d\TH:i');
            $existing = DB::table('operating_records')->where('type', 'attendance')->where('data->assignee_id', $employeeId)->where('data->work_date', $date)->where('status', '!=', 'archived')->lockForUpdate()->first();
            if ($direction === 'in') {
                if ($existing) {
                    $this->workflow->fail('تم تسجيل حضور هذا اليوم بالفعل.', 'Check-in for today is already recorded.');
                }
                $data = $this->derive(['assignee_id' => $employeeId, 'work_date' => $date, 'check_in' => $stamp, 'check_out' => null, 'mode' => $mode, 'source' => $source, 'note' => null], 'open');

                return $this->insertRecord($employeeId, $data, 'open', $userId, 'check_in', 'Attendance check-in ('.$source.')');
            }
            if (! $existing || $existing->status !== 'open') {
                $this->workflow->fail('لا يوجد حضور مفتوح اليوم لتسجيل الانصراف.', 'There is no open check-in today to check out.');
            }
            $data = $this->derive(array_replace($this->catalog->data($existing), ['check_out' => $stamp]), 'closed');
            DB::table('operating_records')->where('id', $existing->id)->update(['status' => 'closed', 'data' => json_encode($data, JSON_UNESCAPED_UNICODE), 'version' => $existing->version + 1, 'updated_at' => now()]);
            $record = DB::table('operating_records')->find($existing->id);
            $this->workflow->event($record, 'check_out', 'Attendance check-out ('.$source.')', (array) $existing, (array) $record, $userId);

            return $record;
        });
        $data = $this->catalog->data($record);
        if ($direction === 'in' && $data['point'] === 'black') {
            $this->notifier->send([$this->notifier->employeeUser($employeeId)], new OperatingAlert(
                'late_check_in',
                ['ar' => 'تم تسجيل حضور متأخر', 'en' => 'Late check-in recorded'],
                ['ar' => 'تأخير '.$data['late_minutes'].' دقيقة يوم '.$data['work_date'].' (نقطة سوداء).', 'en' => $data['late_minutes'].' minutes late on '.$data['work_date'].' (black point).'],
                '/admin/operations/record/'.$record->id,
            ));
        }

        return $record;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function insertRecord(int $employeeId, array $data, string $status, ?int $userId, string $action, string $reason): object
    {
        $employee = DB::table('operating_records')->find($employeeId);
        $id = DB::table('operating_records')->insertGetId(['uuid' => Str::uuid()->toString(), 'type' => 'attendance', 'title' => $employee->title.' · '.$data['work_date'], 'status' => $status, 'owner_id' => $userId, 'created_by' => $userId, 'data' => json_encode($data, JSON_UNESCAPED_UNICODE), 'version' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('operating_links')->insert(['record_id' => $id, 'target_id' => $employeeId, 'field' => 'assignee_id']);
        $record = DB::table('operating_records')->find($id);
        $this->workflow->event($record, $action, $reason, null, (array) $record, $userId);

        return $record;
    }
```

- [ ] **Step 4: أنشئ `app/Http/Controllers/AttendanceController.php`** (ستُضاف board وmonthly في Task 1.6):

```php
<?php

namespace App\Http\Controllers;

use App\Services\Cms;
use App\Services\OperatingAccess;
use App\Services\OperatingAttendance;
use App\Services\OperatingCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AttendanceController extends Controller
{
    public function __construct(private OperatingAttendance $attendance, private OperatingAccess $access, private OperatingCatalog $catalog) {}

    public function checkIn(Request $request): Response
    {
        $input = $request->validate(['mode' => 'required|in:office,remote']);
        $employeeId = $this->access->employeeId($request->user());
        abort_unless($employeeId, 403);
        $this->attendance->punch($employeeId, CarbonImmutable::now(), 'web', $input['mode'], 'in', $request->user()->id);

        return back()->with('success', __('تم تسجيل الحضور.'));
    }

    public function checkOut(Request $request): Response
    {
        $employeeId = $this->access->employeeId($request->user());
        abort_unless($employeeId, 403);
        $this->attendance->punch($employeeId, CarbonImmutable::now(), 'web', 'office', 'out', $request->user()->id);

        return back()->with('success', __('تم تسجيل الانصراف.'));
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

(ملاحظة: `punch` عند `out` يحتفظ بقيمة `mode` المسجلة عند الحضور، لأن `array_replace` يغيّر `check_out` فقط.)

- [ ] **Step 5: المسارات**: في `routes/admin.php` أضف `use App\Http\Controllers\AttendanceController as T;`، وقبل `Route::get('/operations/{type}/create', …)`:

```php
        Route::post('/operations/attendance/check-in', [T::class, 'checkIn'])->middleware('throttle:10,1');
        Route::post('/operations/attendance/check-out', [T::class, 'checkOut'])->middleware('throttle:10,1');
```

- [ ] **Step 6: الترجمات** في `lang/en.json`:

```json
"تم تسجيل الحضور.": "Check-in recorded.",
"تم تسجيل الانصراف.": "Check-out recorded."
```

- [ ] **Step 7: شغّل الاختبار**

Run: `php artisan test --compact tests/Feature/Operating/AttendanceCheckInTest.php`
Expected: PASS.

- [ ] **Step 8: Pint + Commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat(attendance): web check-in/check-out via a single punch entry point with late alerts

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 1.4: التصحيح اليدوي لـHR + بوابات الانتقال

**Files:**
- Modify: `app/Services/OperatingWorkflow.php` (`save()` و`gate()`)
- Test: `tests/Feature/Operating/AttendanceCorrectionTest.php`

**Interfaces:**
- Consumes: `OperatingAttendance::derive()` و`initialStatus()` (تُستدعى عبر `app()` لتجنّب الاعتماد الدائري في الـconstructor، لأن `OperatingAttendance` يعتمد على `OperatingWorkflow`).

- [ ] **Step 1: اكتب الاختبار الفاشل**

Run: `php artisan make:test --phpunit Operating/AttendanceCorrectionTest --no-interaction`، ثم:

```php
<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class AttendanceCorrectionTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    private function payload(int $employeeId, array $data = []): array
    {
        return ['title' => 'Correction', 'reason' => 'Forgot to check in, confirmed by manager', 'data' => array_merge(['assignee_id' => $employeeId, 'work_date' => '2026-10-04', 'check_in' => '2026-10-04T09:20', 'check_out' => '2026-10-04T17:00', 'mode' => 'office', 'source' => 'manual', 'note' => 'Badge issue'], $data)];
    }

    public function test_hr_manual_record_gets_derived_point_and_closed_status(): void
    {
        $hr = $this->operatingUser('hr');
        $employeeId = $this->employeeFor($this->operatingUser('developer'));

        $this->actingAs($hr)->post('/admin/operations/attendance', $this->payload($employeeId))->assertSessionHasNoErrors();

        $record = DB::table('operating_records')->where('type', 'attendance')->sole();
        $this->assertSame('closed', $record->status);
        $this->assertSame('black', json_decode($record->data, true)['point']);
        $this->assertSame(20, json_decode($record->data, true)['late_minutes']);
    }

    public function test_duplicate_day_is_rejected(): void
    {
        $hr = $this->operatingUser('hr');
        $employeeId = $this->employeeFor($this->operatingUser('developer'));
        $this->actingAs($hr)->post('/admin/operations/attendance', $this->payload($employeeId));

        $this->actingAs($hr)->post('/admin/operations/attendance', $this->payload($employeeId))->assertSessionHasErrors('workflow');
    }

    public function test_excusing_an_absence_requires_hr_and_a_note_and_clears_the_point(): void
    {
        $hr = $this->operatingUser('hr');
        $employeeUser = $this->operatingUser('developer');
        $employeeId = $this->employeeFor($employeeUser);
        $id = $this->operatingRecord('attendance', 'absent', ['assignee_id' => $employeeId, 'work_date' => '2026-10-04', 'check_in' => null, 'check_out' => null, 'mode' => 'office', 'source' => 'system', 'note' => null, 'point' => 'red'], 'Absent day');

        $this->actingAs($employeeUser)->post("/admin/operations/record/{$id}/transition", ['target' => 'excused', 'version' => 1, 'reason' => 'Sick'])->assertForbidden();
        $this->actingAs($hr)->post("/admin/operations/record/{$id}/transition", ['target' => 'excused', 'version' => 1, 'reason' => 'Sick'])->assertSessionHasErrors('workflow');

        DB::table('operating_records')->where('id', $id)->update(['data' => json_encode(array_merge($this->recordData($id), ['note' => 'Medical report attached']))]);
        $this->actingAs($hr)->post("/admin/operations/record/{$id}/transition", ['target' => 'excused', 'version' => 1, 'reason' => 'Sick'])->assertSessionHasNoErrors();

        $this->assertSame('excused', DB::table('operating_records')->where('id', $id)->value('status'));
        $this->assertNull($this->recordData($id)['point']);
    }
}
```

- [ ] **Step 2: شغّله وتأكد من الفشل**

Run: `php artisan test --compact tests/Feature/Operating/AttendanceCorrectionTest.php`
Expected: FAIL (لا point، ولا منع تكرار).

- [ ] **Step 3: عدّل `OperatingWorkflow::save()`**: أضف `use App\Services\OperatingAttendance;` غير مطلوب لأنهما في نفس الـnamespace. ابحث عن السطر:

```php
        if ($type === 'evaluations' && ! empty($data['ratings'])) {
```

وأضف **قبله**:

```php
        if ($type === 'attendance') {
            if (DB::table('operating_records')->where('type', 'attendance')->where('data->assignee_id', $data['assignee_id'])->where('data->work_date', $data['work_date'])->where('status', '!=', 'archived')->when($id, fn ($query) => $query->where('id', '!=', $id))->exists()) {
                $this->fail('يوجد سجل حضور لهذا الموظف في هذا اليوم.', 'This employee already has an attendance record for that day.');
            }
            $data = app(OperatingAttendance::class)->derive($data, $existing?->status ?? app(OperatingAttendance::class)->initialStatus($data));
        }
```

ثم في جزء الإنشاء داخل الـtransaction، ابحث عن `'status' => $entity['statuses'][0],` داخل `insertGetId` واستبدله بـ:

```php
'status' => $type === 'attendance' ? app(OperatingAttendance::class)->initialStatus($data) : $entity['statuses'][0],
```

- [ ] **Step 4: عدّل `OperatingWorkflow::gate()`**: قبل السطر `if ($record->type === 'seo_issues' && $target === 'verified') {` أضف:

```php
        if ($record->type === 'attendance') {
            abort_unless(in_array($user->role, ['owner', 'hr'], true), 403);
            if ($target === 'excused') {
                $this->requireFields($data, ['note']);
            }
            if ($target === 'closed') {
                $this->requireFields($data, ['check_in', 'check_out']);
            }
        }
```

وفي `transition()` لا يحتفظ الانتقال بقيم محسوبة جديدة، لذلك لتصفير النقطة عند excused (أو إعادتها عند الرجوع)، ابحث في `transition()` عن:

```php
            DB::table('operating_records')->where('id', $record->id)->update(['status' => $target, 'version' => $record->version + 1, 'updated_at' => now()]);
```

واستبدله بـ:

```php
            $changes = ['status' => $target, 'version' => $record->version + 1, 'updated_at' => now()];
            if ($record->type === 'attendance' && $target !== 'archived') {
                $changes['data'] = json_encode(app(OperatingAttendance::class)->derive($data, $target), JSON_UNESCAPED_UNICODE);
            }
            DB::table('operating_records')->where('id', $record->id)->update($changes);
```

> ملاحظة: الموظف (developer) يحصل على 403 من `transition()` لأن `can($user, 'attendance', 'write')` = false لغير hr (Task 1.1). وbوابة `gate()` تحمي إضافيًا من delivery.

- [ ] **Step 5: شغّل الاختبار + اختبارات الحضور السابقة**

Run: `php artisan test --compact tests/Feature/Operating`
Expected: PASS للجميع.

- [ ] **Step 6: Pint + Commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat(attendance): HR manual corrections with derived points, duplicate guard and excuse gate

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 1.5: إغلاق اليوم (النقطة الحمراء) + الغياب المتتالي + الإشعارات + الجدولة

**Files:**
- Modify: `app/Services/OperatingAttendance.php` (`closeDay()` و`missingStreak()`)
- Create: `app/Console/Commands/CloseAttendanceDay.php`
- Modify: `routes/console.php`
- Test: `tests/Feature/Operating/AttendanceCloseDayTest.php`

**Interfaces:**
- Produces:
  - `closeDay(CarbonImmutable $date): array{absent: list<int>, excused: list<int>}` (idempotent)
  - `missingStreak(int $employeeId, CarbonImmutable $date): int` (عدد أيام العمل المتتالية بحالة `absent` حتى `$date`، شاملًا إياه)
  - Artisan: `operating:attendance-close-day {--date=}`
  - Notification kinds: `missing_check_in` (للمدير المباشر)، و`missing_check_in_digest` (لـowner وhr)، و`absence_streak` (لـhr وowner والمدير والموظف، مع mail).

**القواعد:**
- يُعالج فقط موظف `status = active` و`data.user_id` غير فارغ، وتاريخ `effective_date` (إن وُجد) ≤ اليوم.
- يُتجاهل اليوم بالكامل إن لم يكن يوم عمل (`isWorkday`).
- إجازة `leave` بحالة `approved` تغطي اليوم ← سجل `excused` بلا نقطة.
- غير ذلك وبلا سجل ← سجل `absent` بنقطة `red` و`source = system`.
- تنبيه الغياب المتتالي يُرسل عندما يساوي `missingStreak` **بالضبط** قيمة `absence_alert_days` (حتى لا يتكرر يوميًا).

- [ ] **Step 1: اكتب الاختبار الفاشل**

Run: `php artisan make:test --phpunit Operating/AttendanceCloseDayTest --no-interaction`، ثم:

```php
<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class AttendanceCloseDayTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    private function statusOf(int $employeeId, string $day): ?string
    {
        return DB::table('operating_records')->where('type', 'attendance')->where('data->assignee_id', $employeeId)->where('data->work_date', $day)->value('status');
    }

    public function test_close_day_marks_red_for_missing_and_excused_for_approved_leave(): void
    {
        $present = $this->employeeFor($this->operatingUser('developer'));
        $missing = $this->employeeFor($this->operatingUser('developer'));
        $onLeave = $this->employeeFor($this->operatingUser('qa'));
        $proposed = $this->employeeFor($this->operatingUser('qa'), [], 'proposed');
        $this->operatingRecord('attendance', 'closed', ['assignee_id' => $present, 'work_date' => '2026-10-04', 'point' => 'green'], 'Present');
        $this->operatingRecord('leave', 'approved', ['assignee_id' => $onLeave, 'start_date' => '2026-10-04', 'end_date' => '2026-10-05'], 'Leave');

        $this->artisan('operating:attendance-close-day', ['--date' => '2026-10-04'])->assertSuccessful();
        $this->artisan('operating:attendance-close-day', ['--date' => '2026-10-04'])->assertSuccessful();

        $this->assertSame('absent', $this->statusOf($missing, '2026-10-04'));
        $this->assertSame('red', json_decode(DB::table('operating_records')->where('type', 'attendance')->where('data->assignee_id', $missing)->value('data'), true)['point']);
        $this->assertSame('excused', $this->statusOf($onLeave, '2026-10-04'));
        $this->assertNull($this->statusOf($proposed, '2026-10-04'));
        $this->assertSame(1, DB::table('operating_records')->where('type', 'attendance')->where('data->assignee_id', $missing)->count());
    }

    public function test_weekend_is_skipped(): void
    {
        $this->employeeFor($this->operatingUser('developer'));

        $this->artisan('operating:attendance-close-day', ['--date' => '2026-10-02'])->assertSuccessful();

        $this->assertSame(0, DB::table('operating_records')->where('type', 'attendance')->count());
    }

    public function test_second_consecutive_missing_workday_alerts_hr_manager_and_employee(): void
    {
        $hr = $this->operatingUser('hr');
        $managerUser = $this->operatingUser('delivery');
        $managerId = $this->employeeFor($managerUser);
        $employeeUser = $this->operatingUser('developer');
        $employeeId = $this->employeeFor($employeeUser, ['manager_id' => $managerId]);
        $this->operatingRecord('attendance', 'closed', ['assignee_id' => $managerId, 'work_date' => '2026-10-04', 'point' => 'green'], 'Manager present');
        $this->operatingRecord('attendance', 'closed', ['assignee_id' => $managerId, 'work_date' => '2026-10-05', 'point' => 'green'], 'Manager present');

        $this->artisan('operating:attendance-close-day', ['--date' => '2026-10-04']);
        $this->assertSame(0, $employeeUser->notifications()->where('data->kind', 'absence_streak')->count());
        $this->assertSame(1, $managerUser->notifications()->where('data->kind', 'missing_check_in')->count());
        $this->assertSame(1, $hr->notifications()->where('data->kind', 'missing_check_in_digest')->count());

        $this->artisan('operating:attendance-close-day', ['--date' => '2026-10-05']);

        foreach ([$hr, $managerUser, $employeeUser] as $user) {
            $this->assertSame(1, $user->notifications()->where('data->kind', 'absence_streak')->count());
        }
    }
}
```

- [ ] **Step 2: شغّله وتأكد من الفشل**

Run: `php artisan test --compact tests/Feature/Operating/AttendanceCloseDayTest.php`
Expected: FAIL (`Command "operating:attendance-close-day" is not defined`).

- [ ] **Step 3: أضف إلى `OperatingAttendance`**:

```php
    /**
     * @return array{absent: list<int>, excused: list<int>}
     */
    public function closeDay(CarbonImmutable $date): array
    {
        $result = ['absent' => [], 'excused' => []];
        if (! $this->isWorkday($date)) {
            return $result;
        }
        $day = $date->toDateString();
        foreach (DB::table('operating_records')->where('type', 'employees')->where('status', 'active')->orderBy('id')->get() as $employee) {
            $data = $this->catalog->data($employee);
            if (empty($data['user_id']) || (! empty($data['effective_date']) && $data['effective_date'] > $day)) {
                continue;
            }
            if (DB::table('operating_records')->where('type', 'attendance')->where('data->assignee_id', $employee->id)->where('data->work_date', $day)->where('status', '!=', 'archived')->exists()) {
                continue;
            }
            $onLeave = DB::table('operating_records')->where('type', 'leave')->where('status', 'approved')->where('data->assignee_id', $employee->id)->where('data->start_date', '<=', $day)->where('data->end_date', '>=', $day)->exists();
            $status = $onLeave ? 'excused' : 'absent';
            DB::transaction(fn (): object => $this->insertRecord($employee->id, $this->derive(['assignee_id' => $employee->id, 'work_date' => $day, 'check_in' => null, 'check_out' => null, 'mode' => 'office', 'source' => 'system', 'note' => null], $status), $status, null, 'close_day', $onLeave ? 'Approved leave covers this workday' : 'No check-in recorded by the end of the workday'));
            $result[$status][] = $employee->id;
        }
        $this->alertMissing($result['absent'], $date);

        return $result;
    }

    public function missingStreak(int $employeeId, CarbonImmutable $date): int
    {
        $streak = 0;
        $day = $date;
        for ($checked = 0; $checked < 62; $checked++, $day = $day->subDay()) {
            if (! $this->isWorkday($day)) {
                continue;
            }
            if (DB::table('operating_records')->where('type', 'attendance')->where('data->assignee_id', $employeeId)->where('data->work_date', $day->toDateString())->where('status', '!=', 'archived')->value('status') !== 'absent') {
                break;
            }
            $streak++;
        }

        return $streak;
    }

    /**
     * @param  list<int>  $employeeIds
     */
    private function alertMissing(array $employeeIds, CarbonImmutable $date): void
    {
        if (! $employeeIds) {
            return;
        }
        $day = $date->toDateString();
        $names = DB::table('operating_records')->whereIn('id', $employeeIds)->orderBy('title')->pluck('title')->implode('، ');
        $boardUrl = '/admin/operations/attendance/board?date='.$day;
        $this->notifier->send($this->notifier->roles(['owner', 'hr']), new OperatingAlert(
            'missing_check_in_digest',
            ['ar' => 'موظفون بلا تسجيل حضور', 'en' => 'Employees without check-in'],
            ['ar' => $day.': '.$names, 'en' => $day.': '.$names],
            $boardUrl,
        ));
        foreach ($employeeIds as $employeeId) {
            $name = DB::table('operating_records')->where('id', $employeeId)->value('title');
            $this->notifier->send([$this->notifier->managerUser($employeeId)], new OperatingAlert(
                'missing_check_in',
                ['ar' => 'عدم تسجيل حضور', 'en' => 'No check-in recorded'],
                ['ar' => $name.' لم يسجّل حضورًا يوم '.$day.' (نقطة حمراء).', 'en' => $name.' did not check in on '.$day.' (red point).'],
                $boardUrl,
            ));
            $alertDays = $this->rules()['absence_alert_days'];
            if ($this->missingStreak($employeeId, $date) === $alertDays) {
                $this->notifier->send([...$this->notifier->roles(['owner', 'hr']), $this->notifier->managerUser($employeeId), $this->notifier->employeeUser($employeeId)], new OperatingAlert(
                    'absence_streak',
                    ['ar' => $alertDays.' أيام عمل متتالية بدون تسجيل حضور', 'en' => $alertDays.' consecutive workdays without check-in'],
                    ['ar' => $name.' لديه '.$alertDays.' أيام عمل متتالية بدون تسجيل حضور حتى '.$day.'. يلزم إجراء من HR أو المدير.', 'en' => $name.' has '.$alertDays.' consecutive workdays without check-in up to '.$day.'. HR or manager action is needed.'],
                    $boardUrl,
                    true,
                ));
            }
        }
    }
```

- [ ] **Step 4: أنشئ الأمر**

Run: `php artisan make:command CloseAttendanceDay --no-interaction`، ثم استبدل المحتوى:

```php
<?php

namespace App\Console\Commands;

use App\Services\OperatingAttendance;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class CloseAttendanceDay extends Command
{
    protected $signature = 'operating:attendance-close-day {--date= : Workday to close (Y-m-d), defaults to today}';

    protected $description = 'Record red points for active employees without check-in, mark approved leave as excused and alert on consecutive absences';

    public function handle(OperatingAttendance $attendance): int
    {
        $date = CarbonImmutable::parse($this->option('date') ?: today()->toDateString());
        $result = $attendance->closeDay($date);
        $this->info(sprintf('%s: %d absent, %d excused.', $date->toDateString(), count($result['absent']), count($result['excused'])));

        return self::SUCCESS;
    }
}
```

- [ ] **Step 5: الجدولة**: في `routes/console.php` بعد سطر `website:publish-due`:

```php
Schedule::command('operating:attendance-close-day')->dailyAt('23:55')->withoutOverlapping();
```

- [ ] **Step 6: شغّل الاختبار**

Run: `php artisan test --compact tests/Feature/Operating/AttendanceCloseDayTest.php`
Expected: PASS.
إن فشل `where('data->kind', …)` على notifications في SQLite، فاستبدل الفلترة بـ`->get()->where('data.kind', 'absence_streak')->count()`.

- [ ] **Step 7: Pint + Commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat(attendance): scheduled day close with red points, excused leave and consecutive-absence alerts

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 1.6: الملخص الشهري + لوحة HR اليومية + التقرير الشهري (CSV)

**Files:**
- Modify: `app/Services/OperatingAttendance.php` (`monthSummary()` و`board()`)
- Modify: `app/Http/Controllers/AttendanceController.php` (`board()` و`monthly()`)
- Modify: `routes/admin.php`
- Create: `resources/views/admin/operating/attendance-board.blade.php`
- Create: `resources/views/admin/operating/attendance-monthly.blade.php`
- Modify: `public/admin-assets/operating.css`
- Test: `tests/Feature/Operating/AttendanceReportsTest.php`

**Interfaces:**
- Produces:
  - `monthSummary(?User $viewer, int $year, int $month): list<array{employee_id:int, name:string, green:int, black:int, red:int, excused:int, late_minutes:int, early_leave_minutes:int, overtime_minutes:int, worked_hours:float, remote_days:int, commitment:float, bonus:float, eligible:bool}>` (`$viewer = null` ← بلا تقييد، للأوامر المجدولة)
  - `board(User $viewer, CarbonImmutable $date): array{rows: list<array{employee: object, record: ?object, data: array, streak: int}>, counts: array{present:int, late:int, absent:int, excused:int, pending:int, remote:int}}`
  - Routes: `GET /admin/operations/attendance/board?date=Y-m-d` (owner, hr, delivery)، و`GET /admin/operations/attendance/monthly?month=Y-m[&format=csv]` (owner, hr، وdelivery لفريقه)

**نسبة الالتزام (D2):** `commitment = green ÷ (green + black + red) × 100` مقربة لمنزلتين، و`excused` مستبعد. `eligible = bonus > 0`.

- [ ] **Step 1: اكتب الاختبار الفاشل**

Run: `php artisan make:test --phpunit Operating/AttendanceReportsTest --no-interaction`، ثم:

```php
<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingAttendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class AttendanceReportsTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    private function day(int $employeeId, string $date, string $status, ?string $point, int $late = 0): void
    {
        $this->operatingRecord('attendance', $status, ['assignee_id' => $employeeId, 'work_date' => $date, 'point' => $point, 'late_minutes' => $late, 'early_leave_minutes' => 0, 'overtime_minutes' => 0, 'worked_minutes' => $point === 'red' || $point === null ? 0 : 480, 'mode' => 'office'], 'Day '.$date);
    }

    public function test_month_summary_computes_commitment_and_bonus(): void
    {
        $hr = $this->operatingUser('hr');
        $punctual = $this->employeeFor($this->operatingUser('developer'));
        $mixed = $this->employeeFor($this->operatingUser('qa'));
        foreach (range(1, 10) as $index) {
            $date = sprintf('2026-10-%02d', $index + 10);
            $this->day($punctual, $date, 'closed', 'green');
            $this->day($mixed, $date, 'closed', $index <= 8 ? 'green' : 'black', $index <= 8 ? 0 : 15);
        }
        $this->day($mixed, '2026-10-25', 'excused', null);
        $this->day($mixed, '2026-11-01', 'absent', 'red');

        $rows = collect(app(OperatingAttendance::class)->monthSummary($hr, 2026, 10))->keyBy('employee_id');

        $this->assertSame(100.0, $rows[$punctual]['commitment']);
        $this->assertSame(40.0, $rows[$punctual]['bonus']);
        $this->assertSame(80.0, $rows[$mixed]['commitment']);
        $this->assertSame(25.0, $rows[$mixed]['bonus']);
        $this->assertSame(30, $rows[$mixed]['late_minutes']);
        $this->assertSame(1, $rows[$mixed]['excused']);
        $this->assertSame(0, $rows[$mixed]['red']);
    }

    public function test_board_is_for_hr_and_managers_only(): void
    {
        $hr = $this->operatingUser('hr');
        $developer = $this->operatingUser('developer');
        $employeeId = $this->employeeFor($developer);
        $this->day($employeeId, '2026-10-04', 'closed', 'black', 12);

        $this->actingAs($hr)->get('/admin/operations/attendance/board?date=2026-10-04')->assertOk()->assertSee($developer->name);
        $this->actingAs($developer)->get('/admin/operations/attendance/board?date=2026-10-04')->assertForbidden();
    }

    public function test_monthly_report_exports_csv(): void
    {
        $hr = $this->operatingUser('hr');
        $developer = $this->operatingUser('developer');
        $this->day($this->employeeFor($developer), '2026-10-04', 'closed', 'green');

        $response = $this->actingAs($hr)->get('/admin/operations/attendance/monthly?month=2026-10&format=csv');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString($developer->name, $response->streamedContent());
    }
}
```

- [ ] **Step 2: شغّله وتأكد من الفشل**

Run: `php artisan test --compact tests/Feature/Operating/AttendanceReportsTest.php`
Expected: FAIL.

- [ ] **Step 3: أضف إلى `OperatingAttendance`** (`use App\Models\User;` في الأعلى):

```php
    /**
     * @return list<array{employee_id: int, name: string, green: int, black: int, red: int, excused: int, late_minutes: int, early_leave_minutes: int, overtime_minutes: int, worked_hours: float, remote_days: int, commitment: float, bonus: float, eligible: bool}>
     */
    public function monthSummary(?User $viewer, int $year, int $month): array
    {
        $first = CarbonImmutable::create($year, $month, 1);
        $query = $viewer ? $this->access->query($viewer, 'attendance') : DB::table('operating_records')->where('type', 'attendance');
        $records = $query->where('status', '!=', 'archived')->where('data->work_date', '>=', $first->toDateString())->where('data->work_date', '<=', $first->endOfMonth()->toDateString())->get();
        $rows = [];
        foreach ($records->groupBy(fn (object $record): int => (int) $this->catalog->data($record)['assignee_id']) as $employeeId => $items) {
            $days = $items->map(fn (object $record): array => $this->catalog->data($record) + ['status' => $record->status]);
            $green = $days->where('point', 'green')->count();
            $black = $days->where('point', 'black')->count();
            $red = $days->where('point', 'red')->count();
            $counted = $green + $black + $red;
            $commitment = $counted ? round($green / $counted * 100, 2) : 0.0;
            $bonus = $this->bonus($commitment);
            $rows[] = [
                'employee_id' => (int) $employeeId,
                'name' => (string) DB::table('operating_records')->where('id', $employeeId)->value('title'),
                'green' => $green,
                'black' => $black,
                'red' => $red,
                'excused' => $days->where('status', 'excused')->count(),
                'late_minutes' => (int) $days->sum('late_minutes'),
                'early_leave_minutes' => (int) $days->sum('early_leave_minutes'),
                'overtime_minutes' => (int) $days->sum('overtime_minutes'),
                'worked_hours' => round($days->sum('worked_minutes') / 60, 2),
                'remote_days' => $days->where('mode', 'remote')->count(),
                'commitment' => (float) $commitment,
                'bonus' => $bonus,
                'eligible' => $bonus > 0,
            ];
        }
        usort($rows, fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

        return $rows;
    }

    /**
     * @return array{rows: list<array{employee: object, record: ?object, data: array<string, mixed>, streak: int}>, counts: array{present: int, late: int, absent: int, excused: int, pending: int, remote: int}}
     */
    public function board(User $viewer, CarbonImmutable $date): array
    {
        $records = $this->access->query($viewer, 'attendance')->where('data->work_date', $date->toDateString())->where('status', '!=', 'archived')->get()->keyBy(fn (object $record): int => (int) $this->catalog->data($record)['assignee_id']);
        $managerId = $viewer->role === 'delivery' ? $this->access->employeeId($viewer) : null;
        $employees = DB::table('operating_records')->where('type', 'employees')->where('status', 'active')->orderBy('title')->get()->filter(function (object $employee) use ($viewer, $managerId): bool {
            $data = $this->catalog->data($employee);

            return ! empty($data['user_id']) && ($viewer->role !== 'delivery' || ($data['manager_id'] ?? null) == $managerId);
        });
        $rows = [];
        $counts = ['present' => 0, 'late' => 0, 'absent' => 0, 'excused' => 0, 'pending' => 0, 'remote' => 0];
        foreach ($employees as $employee) {
            $record = $records->get($employee->id);
            $data = $record ? $this->catalog->data($record) : [];
            match (true) {
                ! $record => $counts['pending']++,
                $record->status === 'excused' => $counts['excused']++,
                $record->status === 'absent' => $counts['absent']++,
                default => $counts['present']++,
            };
            if (($data['point'] ?? null) === 'black') {
                $counts['late']++;
            }
            if ($record && ($data['mode'] ?? null) === 'remote') {
                $counts['remote']++;
            }
            $rows[] = ['employee' => $employee, 'record' => $record, 'data' => $data, 'streak' => $this->missingStreak($employee->id, $date)];
        }

        return ['rows' => $rows, 'counts' => $counts];
    }
```

- [ ] **Step 4: أضف إلى `AttendanceController`** (`use Symfony\Component\HttpFoundation\StreamedResponse;` غير لازم لأن نوع الإرجاع `Response` يشمله):

```php
    public function board(Request $request): Response
    {
        abort_unless(in_array($request->user()->role, ['owner', 'hr', 'delivery'], true), 403);
        $input = $request->validate(['date' => 'nullable|date_format:Y-m-d']);
        $date = CarbonImmutable::parse($input['date'] ?? today()->toDateString());

        return $this->view('attendance-board', $this->attendance->board($request->user(), $date) + ['date' => $date, 'workday' => $this->attendance->isWorkday($date), 'alertDays' => $this->attendance->rules()['absence_alert_days']]);
    }

    public function monthly(Request $request): Response
    {
        abort_unless(in_array($request->user()->role, ['owner', 'hr', 'delivery'], true), 403);
        $input = $request->validate(['month' => 'nullable|date_format:Y-m', 'format' => 'nullable|in:csv']);
        $month = CarbonImmutable::createFromFormat('Y-m-d', ($input['month'] ?? today()->format('Y-m')).'-01');
        $rows = $this->attendance->monthSummary($request->user(), $month->year, $month->month);
        if (($input['format'] ?? null) === 'csv') {
            return response()->streamDownload(function () use ($rows): void {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, ['Employee', 'Green', 'Black', 'Red', 'Excused', 'Late minutes', 'Early leave minutes', 'Overtime minutes', 'Worked hours', 'Remote days', 'Commitment %', 'Bonus', 'Eligible']);
                foreach ($rows as $row) {
                    fputcsv($out, [$row['name'], $row['green'], $row['black'], $row['red'], $row['excused'], $row['late_minutes'], $row['early_leave_minutes'], $row['overtime_minutes'], $row['worked_hours'], $row['remote_days'], $row['commitment'], $row['bonus'], $row['eligible'] ? 'yes' : 'no']);
                }
                fclose($out);
            }, 'attendance-'.$month->format('Y-m').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return $this->view('attendance-monthly', ['rows' => $rows, 'month' => $month, 'rules' => $this->attendance->rules()]);
    }
```

- [ ] **Step 5: المسارات** (قبل `/operations/{type}/create`):

```php
        Route::get('/operations/attendance/board', [T::class, 'board']);
        Route::get('/operations/attendance/monthly', [T::class, 'monthly']);
```

- [ ] **Step 6: أنشئ `resources/views/admin/operating/attendance-board.blade.php`**

```blade
@extends('admin.layout')
@section('title',__('لوحة الحضور اليومية'))
@section('content')
<div class="page-heading"><div><span class="eyebrow">{{ $date->toDateString() }}</span><h1>{{ __('لوحة الحضور اليومية') }}</h1><p class="muted">{{ $workday ? __('النقاط: خضراء في الموعد، سوداء متأخر، حمراء بلا تسجيل.') : __('هذا اليوم ليس يوم عمل.') }}</p></div><form method="get" class="inline-form"><input type="date" name="date" value="{{ $date->toDateString() }}"><button class="button secondary">{{ __('عرض') }}</button><a class="button secondary" href="/admin/operations/attendance/monthly?month={{ $date->format('Y-m') }}">{{ __('التقرير الشهري') }} ↗</a></form></div>
<div class="stats-grid"><div class="stat"><span>{{ __('حاضرون') }}</span><strong>{{ $counts['present'] }}</strong></div><div class="stat"><span>{{ __('متأخرون') }}</span><strong>{{ $counts['late'] }}</strong></div><div class="stat"><span>{{ __('غياب / عدم تسجيل') }}</span><strong>{{ $counts['absent'] }}</strong></div><div class="stat"><span>{{ __('لم يسجّلوا بعد') }}</span><strong>{{ $counts['pending'] }}</strong></div><div class="stat"><span>{{ __('غياب بعذر') }}</span><strong>{{ $counts['excused'] }}</strong></div><div class="stat"><span>{{ __('من المنزل') }}</span><strong>{{ $counts['remote'] }}</strong></div></div>
<div class="card table-card"><table><thead><tr><th>{{ __('الموظف') }}</th><th>{{ __('النقطة') }}</th><th>{{ __('الحضور') }}</th><th>{{ __('الانصراف') }}</th><th>{{ __('التأخير (د)') }}</th><th>{{ __('ساعات العمل') }}</th><th>{{ __('مكان العمل') }}</th><th>{{ __('غياب متتالٍ') }}</th></tr></thead><tbody>
@foreach($rows as $row)
<tr><td>@if($row['record'])<a href="/admin/operations/record/{{ $row['record']->id }}">{{ $row['employee']->title }}</a>@else{{ $row['employee']->title }}@endif</td>
<td><span class="point-dot point-{{ $row['data']['point'] ?? 'none' }}" title="{{ $row['data']['point'] ?? '—' }}"></span> {{ $row['record'] ? $catalog->status($row['record']->status) : __('لم يسجّل بعد') }}</td>
<td>{{ isset($row['data']['check_in']) ? substr($row['data']['check_in'], 11) : '—' }}</td>
<td>{{ isset($row['data']['check_out']) ? substr($row['data']['check_out'], 11) : '—' }}</td>
<td>{{ $row['data']['late_minutes'] ?? 0 }}</td>
<td>{{ round(($row['data']['worked_minutes'] ?? 0) / 60, 2) }}</td>
<td>{{ ($row['data']['mode'] ?? null) === 'remote' ? __('من المنزل') : (($row['data']['mode'] ?? null) ? __('من المقر') : '—') }}</td>
<td>@if($row['streak'] >= $alertDays)<b class="badge">{{ $row['streak'] }}</b>@else{{ $row['streak'] }}@endif</td></tr>
@endforeach
</tbody></table></div>
<div class="notice">{{ __('النقاط الحمراء تُسجَّل تلقائيًا في نهاية يوم العمل. التصحيح من سجل الحضور مع سبب مكتوب.') }}</div>
@endsection
```

- [ ] **Step 7: أنشئ `resources/views/admin/operating/attendance-monthly.blade.php`**

```blade
@extends('admin.layout')
@section('title',__('تقرير الحضور الشهري'))
@section('content')
<div class="page-heading"><div><span class="eyebrow">{{ $month->format('Y-m') }}</span><h1>{{ __('تقرير الحضور الشهري') }}</h1><p class="muted">{{ __('نسبة الالتزام = الخضراء ÷ (الخضراء + السوداء + الحمراء). البونص من') }} {{ $rules['bonus_min'] }} {{ __('إلى') }} {{ $rules['bonus_max'] }} {{ $rules['currency'] }} {{ __('عند التزام') }} {{ $rules['bonus_eligibility_percent'] }}% {{ __('فأكثر.') }}</p></div><form method="get" class="inline-form"><input type="month" name="month" value="{{ $month->format('Y-m') }}"><button class="button secondary">{{ __('عرض') }}</button><a class="button" href="?month={{ $month->format('Y-m') }}&format=csv">{{ __('تصدير CSV') }}</a></form></div>
<div class="stats-grid"><div class="stat"><span>{{ __('مستحقو البونص') }}</span><strong>{{ collect($rows)->where('eligible', true)->count() }}</strong></div><div class="stat"><span>{{ __('إجمالي البونص') }}</span><strong>{{ collect($rows)->sum('bonus') }} {{ $rules['currency'] }}</strong></div><div class="stat"><span>{{ __('إجمالي دقائق التأخير') }}</span><strong>{{ collect($rows)->sum('late_minutes') }}</strong></div><div class="stat"><span>{{ __('ساعات إضافية') }}</span><strong>{{ round(collect($rows)->sum('overtime_minutes') / 60, 1) }}</strong></div></div>
<div class="card table-card"><table><thead><tr><th>{{ __('الموظف') }}</th><th><span class="point-dot point-green"></span></th><th><span class="point-dot point-black"></span></th><th><span class="point-dot point-red"></span></th><th>{{ __('بعذر') }}</th><th>{{ __('التأخير (د)') }}</th><th>{{ __('انصراف مبكر (د)') }}</th><th>{{ __('إضافي (س)') }}</th><th>{{ __('ساعات العمل') }}</th><th>{{ __('من المنزل') }}</th><th>{{ __('الالتزام') }}</th><th>{{ __('البونص') }}</th></tr></thead><tbody>
@forelse($rows as $row)
<tr><td>{{ $row['name'] }}</td><td>{{ $row['green'] }}</td><td>{{ $row['black'] }}</td><td>{{ $row['red'] }}</td><td>{{ $row['excused'] }}</td><td>{{ $row['late_minutes'] }}</td><td>{{ $row['early_leave_minutes'] }}</td><td>{{ round($row['overtime_minutes'] / 60, 1) }}</td><td>{{ $row['worked_hours'] }}</td><td>{{ $row['remote_days'] }}</td><td>{{ $row['commitment'] }}%</td><td>@if($row['eligible'])<b>{{ $row['bonus'] }} {{ $rules['currency'] }}</b>@else—@endif</td></tr>
@empty<tr><td colspan="12" class="muted">{{ __('لا توجد سجلات حضور لهذا الشهر.') }}</td></tr>@endforelse
</tbody></table></div>
<div class="notice">{{ __('البونص مقترح محسوب ولا يُصرف تلقائيًا. يُعتمد من الإدارة ضمن الرواتب.') }}</div>
@endsection
```

- [ ] **Step 8: CSS**: أضف في آخر `public/admin-assets/operating.css`:

```css
.point-dot{display:inline-block;width:.8em;height:.8em;border-radius:50%;vertical-align:middle;border:1px solid rgba(0,0,0,.15)}
.point-green{background:#16a34a}.point-black{background:#111827}.point-red{background:#dc2626}.point-none{background:transparent}
.inline-form{display:flex;gap:.5rem;align-items:center;flex-wrap:wrap}
```

- [ ] **Step 9: شغّل الاختبار**

Run: `php artisan test --compact tests/Feature/Operating/AttendanceReportsTest.php`
Expected: PASS.

- [ ] **Step 10: Pint + Commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat(attendance): HR daily board, monthly commitment and bonus report with CSV export

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 1.7: تقرير البونص الشهري المجدول

**Files:**
- Create: `app/Console/Commands/SendAttendanceMonthlyReport.php`
- Modify: `routes/console.php`
- Test: `tests/Feature/Operating/AttendanceMonthlyReportTest.php`

**Interfaces:**
- Consumes: `monthSummary(null, …)`، و`OperatingNotifier::roles()`.
- Produces: `operating:attendance-monthly-report {--month=}` (الافتراضي: الشهر السابق)، وnotification kind `bonus_report` (mail = true) لـowner وhr.

- [ ] **Step 1: اكتب الاختبار الفاشل**

```php
<?php

namespace Tests\Feature\Operating;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class AttendanceMonthlyReportTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_monthly_report_lists_eligible_employees_for_hr(): void
    {
        $hr = $this->operatingUser('hr');
        $employeeId = $this->employeeFor($this->operatingUser('developer'));
        $this->operatingRecord('attendance', 'closed', ['assignee_id' => $employeeId, 'work_date' => '2026-09-14', 'point' => 'green', 'late_minutes' => 0, 'worked_minutes' => 480], 'Day');

        $this->artisan('operating:attendance-monthly-report', ['--month' => '2026-09'])->assertSuccessful();

        $data = $hr->notifications()->sole()->data;
        $this->assertSame('bonus_report', $data['kind']);
        $this->assertStringContainsString('2026-09', $data['url']);
        $this->assertStringContainsString('40', $data['body']['en']);
    }
}
```

(أنشئه بـ`php artisan make:test --phpunit Operating/AttendanceMonthlyReportTest --no-interaction`.)

- [ ] **Step 2: شغّله وتأكد من الفشل**: `php artisan test --compact tests/Feature/Operating/AttendanceMonthlyReportTest.php`، والمتوقع FAIL.

- [ ] **Step 3: أنشئ الأمر**: `php artisan make:command SendAttendanceMonthlyReport --no-interaction`، ثم:

```php
<?php

namespace App\Console\Commands;

use App\Notifications\OperatingAlert;
use App\Services\OperatingAttendance;
use App\Services\OperatingNotifier;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class SendAttendanceMonthlyReport extends Command
{
    protected $signature = 'operating:attendance-monthly-report {--month= : Month (Y-m), defaults to the previous month}';

    protected $description = 'Send management and HR the monthly list of employees eligible for the attendance bonus';

    public function handle(OperatingAttendance $attendance, OperatingNotifier $notifier): int
    {
        $month = CarbonImmutable::createFromFormat('Y-m-d', ($this->option('month') ?: today()->subMonthNoOverflow()->format('Y-m')).'-01');
        $rows = collect($attendance->monthSummary(null, $month->year, $month->month))->where('eligible', true);
        $currency = $attendance->rules()['currency'];
        $list = $rows->map(fn (array $row): string => $row['name'].' ('.$row['commitment'].'% → '.$row['bonus'].' '.$currency.')')->implode('، ');
        $notifier->send($notifier->roles(['owner', 'hr']), new OperatingAlert(
            'bonus_report',
            ['ar' => 'تقرير البونص الشهري '.$month->format('Y-m'), 'en' => 'Monthly bonus report '.$month->format('Y-m')],
            ['ar' => $rows->count().' مستحق، الإجمالي '.$rows->sum('bonus').' '.$currency.'. '.$list, 'en' => $rows->count().' eligible, total '.$rows->sum('bonus').' '.$currency.'. '.$list],
            '/admin/operations/attendance/monthly?month='.$month->format('Y-m'),
            true,
        ));
        $this->info($rows->count().' eligible employees reported.');

        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: الجدولة** في `routes/console.php`:

```php
Schedule::command('operating:attendance-monthly-report')->monthlyOn(1, '08:00');
```

- [ ] **Step 5: شغّل الاختبار**: والمتوقع PASS.

- [ ] **Step 6: Pint + Commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat(attendance): scheduled monthly bonus eligibility report to management and HR

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 1.8: widget الموظف في الـoverview + الـsidebar + تخفيف signals + إعدادات الحضور

**Files:**
- Modify: `app/Services/OperatingAttendance.php` (`today()`)
- Modify: `app/Http/Controllers/OperatingController.php` (`overview()` و`signals()` و`settings()` + `saveAttendanceSettings()` جديدة)
- Create: `resources/views/admin/operating/attendance-widget.blade.php`
- Modify: `resources/views/admin/operating/overview.blade.php`
- Modify: `resources/views/admin/operating/settings.blade.php`
- Modify: `resources/views/admin/layout.blade.php`
- Modify: `routes/admin.php`
- Modify: `public/admin-assets/operating.css`
- Modify: `lang/en.json`
- Test: `tests/Feature/Operating/AttendanceWidgetAndSettingsTest.php`

**Interfaces:**
- Produces:
  - `today(User $user): ?array{record: ?object, data: array, workday: bool, missing_streak: int, alert_days: int}` (null إن لم يكن الحساب مرتبطًا بموظف)
  - Route: `POST /admin/operations/settings/attendance` (owner فقط)

- [ ] **Step 1: اكتب الاختبار الفاشل**

```php
<?php

namespace Tests\Feature\Operating;

use App\Services\OperatingAttendance;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOperatingRecords;
use Tests\TestCase;

class AttendanceWidgetAndSettingsTest extends TestCase
{
    use BuildsOperatingRecords, RefreshDatabase;

    public function test_employee_sees_check_in_button_and_two_day_warning(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-06 08:30'));
        $user = $this->operatingUser('developer');
        $employeeId = $this->employeeFor($user);
        foreach (['2026-10-04', '2026-10-05'] as $day) {
            $this->operatingRecord('attendance', 'absent', ['assignee_id' => $employeeId, 'work_date' => $day, 'point' => 'red'], 'Absent');
        }

        $this->actingAs($user)->get('/admin')->assertOk()->assertSee('/admin/operations/attendance/check-in', false)->assertSee('لديك 2 أيام عمل متتالية بدون تسجيل حضور');
    }

    public function test_owner_updates_attendance_rules_with_version_and_audit(): void
    {
        $owner = $this->operatingUser('owner');
        $payload = ['version' => 0, 'work_start' => '10:00', 'work_end' => '18:00', 'work_days' => ['0', '1', '2', '3', '4'], 'grace_minutes' => 5, 'bonus_min' => 25, 'bonus_max' => 40, 'bonus_eligibility_percent' => 90, 'absence_alert_days' => 2, 'effective_date' => '2026-10-01', 'reason' => 'Management decision'];
        $this->travelTo(CarbonImmutable::parse('2026-10-06 10:00'));

        $this->actingAs($this->operatingUser('hr'))->post('/admin/operations/settings/attendance', $payload)->assertForbidden();
        $this->actingAs($owner)->post('/admin/operations/settings/attendance', $payload)->assertSessionHasNoErrors();

        $rules = app(OperatingAttendance::class)->rules();
        $this->assertSame('10:00', $rules['work_start']);
        $this->assertSame([0, 1, 2, 3, 4], $rules['work_days']);
        $this->assertSame(5, $rules['grace_minutes']);
        $this->assertDatabaseHas('operating_events', ['action' => 'configure_attendance']);
        $this->actingAs($owner)->post('/admin/operations/settings/attendance', $payload)->assertSessionHasErrors('workflow');

        $this->actingAs($owner)->post('/admin/operations/settings/attendance', array_merge($payload, ['version' => 1, 'work_start' => '11:00', 'effective_date' => '2026-12-01']))->assertSessionHasNoErrors();
        $this->assertSame('10:00', app(OperatingAttendance::class)->rules()['work_start']);
    }
}
```

(أنشئه بـ`php artisan make:test --phpunit Operating/AttendanceWidgetAndSettingsTest --no-interaction`.)

- [ ] **Step 2: شغّله وتأكد من الفشل**: والمتوقع FAIL.

- [ ] **Step 3: أضف إلى `OperatingAttendance`**:

```php
    /**
     * @return array{record: ?object, data: array<string, mixed>, workday: bool, missing_streak: int, alert_days: int}|null
     */
    public function today(User $user): ?array
    {
        $employeeId = $this->access->employeeId($user);
        if (! $employeeId) {
            return null;
        }
        $today = CarbonImmutable::today();
        $record = DB::table('operating_records')->where('type', 'attendance')->where('data->assignee_id', $employeeId)->where('data->work_date', $today->toDateString())->where('status', '!=', 'archived')->first();
        $previous = $today->subDay();
        for ($guard = 0; $guard < 14 && ! $this->isWorkday($previous); $guard++) {
            $previous = $previous->subDay();
        }

        return ['record' => $record, 'data' => $record ? $this->catalog->data($record) : [], 'workday' => $this->isWorkday($today), 'missing_streak' => $this->missingStreak($employeeId, $previous), 'alert_days' => $this->rules()['absence_alert_days']];
    }
```

- [ ] **Step 4: عدّل `OperatingController`**:

(أ) في `overview()`: داخل `foreach ($this->catalog->entities() as $type => $entity)`، استبدل الحلقة الداخلية:

```php
            foreach ((clone $query)->where('status', '!=', 'archived')->get() as $record) {
```

بـ:

```php
            foreach ((clone $query)->when($type === 'attendance', fn ($scoped) => $scoped->where('status', 'open'), fn ($scoped) => $scoped->where('status', '!=', 'archived'))->get() as $record) {
```

وفي `return $this->view('overview', [...])` أضف المفتاح:

```php
'attendanceToday' => app(OperatingAttendance::class)->today($request->user()),
```

مع `use App\Services\OperatingAttendance;` في أعلى الملف.

(ب) في `signals()`، قبل `return $signals;`:

```php
        if ($record->type === 'attendance' && $record->status === 'open' && ($data['work_date'] ?? '') < today()->toDateString()) {
            $signals[] = __('حضور بدون انصراف مسجل. صحّح السجل.');
        }
```

(ج) في `settings()`: أضف إلى مصفوفة الـview:

```php
'attendance' => app(OperatingAttendance::class)->rules(), 'attendanceVersion' => (int) (DB::table('operating_settings')->where('key', 'attendance')->value('version') ?? 0),
```

(د) أضف دالة جديدة بعد `saveSettings()`:

```php
    public function saveAttendanceSettings(Request $request): Response
    {
        abort_unless($request->user()->role === 'owner', 403);
        $input = $request->validate(['version' => 'required|integer', 'work_start' => 'required|date_format:H:i', 'work_end' => 'required|date_format:H:i|after:work_start', 'work_days' => 'required|array|min:1', 'work_days.*' => 'integer|between:0,6', 'grace_minutes' => 'required|integer|min:0|max:120', 'bonus_min' => 'required|numeric|min:0|max:100000', 'bonus_max' => 'required|numeric|gte:bonus_min|max:100000', 'bonus_eligibility_percent' => 'required|numeric|min:1|max:100', 'absence_alert_days' => 'required|integer|min:1|max:10', 'effective_date' => 'required|date', 'reason' => 'required|string|max:2000']);
        DB::transaction(function () use ($input): void {
            $old = DB::table('operating_settings')->where('key', 'attendance')->lockForUpdate()->first();
            if (($old?->version ?? 0) != $input['version']) {
                $this->workflow->fail('الإعدادات تغيرت. حدّث الصفحة.', 'Settings changed. Refresh first.');
            }
            $data = ['work_start' => $input['work_start'], 'work_end' => $input['work_end'], 'work_days' => array_values(array_map('intval', $input['work_days'])), 'grace_minutes' => (int) $input['grace_minutes'], 'bonus_min' => (float) $input['bonus_min'], 'bonus_max' => (float) $input['bonus_max'], 'bonus_eligibility_percent' => (float) $input['bonus_eligibility_percent'], 'absence_alert_days' => (int) $input['absence_alert_days'], 'effective_date' => $input['effective_date']];
            DB::table('operating_settings')->updateOrInsert(['key' => 'attendance'], ['data' => json_encode($data), 'version' => ($old?->version ?? 0) + 1, 'created_at' => $old?->created_at ?? now(), 'updated_at' => now()]);
            $this->workflow->event(null, 'configure_attendance', $input['reason'], $old ? json_decode($old->data, true) : null, $data);
        });

        return back()->with('success', __('تم اعتماد قواعد الحضور والبونص.'));
    }
```

- [ ] **Step 5: المسار** في `routes/admin.php`، بعد `Route::post('/operations/settings', …)`:

```php
        Route::post('/operations/settings/attendance', [O::class, 'saveAttendanceSettings']);
```

- [ ] **Step 6: أنشئ `resources/views/admin/operating/attendance-widget.blade.php`**

```blade
@if($attendanceToday)
@php $today = $attendanceToday; $status = $today['record']?->status; @endphp
<section class="card attendance-widget">
<div class="section-heading"><h2>{{ __('حضوري اليوم') }}</h2>@if($today['record'])<span><span class="point-dot point-{{ $today['data']['point'] ?? 'none' }}"></span> {{ $catalog->status($status) }}</span>@endif</div>
@if($today['missing_streak'] >= $today['alert_days'])<div class="notice">{{ __('لديك') }} {{ $today['missing_streak'] }} {{ __('أيام عمل متتالية بدون تسجيل حضور') }}. {{ __('تواصل مع مديرك أو HR.') }}</div>@endif
@if(! $today['record'])
<form method="post" action="/admin/operations/attendance/check-in" class="inline-form">@csrf<select name="mode"><option value="office">{{ __('من المقر') }}</option><option value="remote">{{ __('من المنزل') }}</option></select><button class="button">{{ __('تسجيل الحضور') }}</button></form>
@if(! $today['workday'])<p class="muted">{{ __('اليوم ليس يوم عمل؛ الوقت يُحسب إضافيًا بلا نقطة.') }}</p>@endif
@elseif($status === 'open')
<p class="muted">{{ __('الحضور') }}: {{ substr($today['data']['check_in'], 11) }} @if(($today['data']['late_minutes'] ?? 0) > 0)· {{ __('تأخير') }} {{ $today['data']['late_minutes'] }} {{ __('دقيقة') }}@endif</p>
<form method="post" action="/admin/operations/attendance/check-out">@csrf<button class="button secondary">{{ __('تسجيل الانصراف') }}</button></form>
@else
<p class="muted">{{ __('الحضور') }}: {{ isset($today['data']['check_in']) ? substr($today['data']['check_in'], 11) : '—' }} · {{ __('الانصراف') }}: {{ isset($today['data']['check_out']) ? substr($today['data']['check_out'], 11) : '—' }}</p>
@endif
</section>
@endif
```

- [ ] **Step 7: في `overview.blade.php`**: أضف مباشرة بعد سطر `<div class="page-heading">…</div>` (السطر الأول بعد `@section('content')`):

```blade
@include('admin.operating.attendance-widget')
```

- [ ] **Step 8: في `settings.blade.php`**: أضف قبل `<div class="notice">` الأخيرة:

```blade
<form method="post" action="/admin/operations/settings/attendance" class="card">@csrf<h2>{{ __('قواعد الحضور والبونص') }}</h2><input type="hidden" name="version" value="{{ $attendanceVersion }}">
<div class="field-grid"><label>{{ __('بداية العمل') }}<input type="time" name="work_start" value="{{ $attendance['work_start'] }}" required></label><label>{{ __('نهاية العمل') }}<input type="time" name="work_end" value="{{ $attendance['work_end'] }}" required></label><label>{{ __('سماحية التأخير (دقائق)') }}<input type="number" name="grace_minutes" min="0" max="120" value="{{ $attendance['grace_minutes'] }}" required></label><label>{{ __('أيام الغياب المتتالي للتنبيه') }}<input type="number" name="absence_alert_days" min="1" max="10" value="{{ $attendance['absence_alert_days'] }}" required></label><label>{{ __('أقل بونص') }} ({{ $attendance['currency'] }})<input type="number" step="0.01" name="bonus_min" value="{{ $attendance['bonus_min'] }}" required></label><label>{{ __('أعلى بونص') }} ({{ $attendance['currency'] }})<input type="number" step="0.01" name="bonus_max" value="{{ $attendance['bonus_max'] }}" required></label><label>{{ __('حد الاستحقاق %') }}<input type="number" step="0.01" name="bonus_eligibility_percent" min="1" max="100" value="{{ $attendance['bonus_eligibility_percent'] }}" required></label></div>
<fieldset><legend>{{ __('أيام العمل') }}</legend>@foreach(['الأحد','الاثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'] as $index => $dayName)<label class="inline-check"><input type="checkbox" name="work_days[]" value="{{ $index }}" @checked(in_array($index, $attendance['work_days'], true))> {{ __($dayName) }}</label>@endforeach</fieldset>
<label>{{ __('تاريخ السريان') }}<input type="date" name="effective_date" value="{{ $attendance['effective_date'] ?? '' }}" required></label><label>{{ __('سبب الاعتماد / المعايرة') }}<textarea name="reason" required rows="2"></textarea></label><button class="button">{{ __('اعتماد قواعد الحضور') }}</button></form>
```

- [ ] **Step 9: في `layout.blade.php`**: بعد رابط الإشعارات (Task 0.2) أضف:

```blade
@if(in_array(auth()->user()->role,['owner','hr','delivery'],true))<a href="/admin/operations/attendance/board">{{ __('لوحة الحضور اليومية') }}</a><a href="/admin/operations/attendance/monthly">{{ __('تقرير الحضور الشهري') }}</a>@endif
```

- [ ] **Step 10: CSS** (آخر `operating.css`):

```css
.attendance-widget{margin-block-end:1.5rem}.attendance-widget .notice{margin-block:.75rem}.inline-check{display:inline-flex;gap:.35rem;align-items:center;margin-inline-end:1rem}
```

- [ ] **Step 11: الترجمات**: أضف إلى `lang/en.json` كل مفاتيح المرحلة 1 التالية (مع الحفاظ على صحة الـJSON وعدم تكرار مفتاح موجود؛ تحقق بـ`grep` قبل الإضافة):

```json
"لوحة الحضور اليومية": "Daily attendance board",
"تقرير الحضور الشهري": "Monthly attendance report",
"النقاط: خضراء في الموعد، سوداء متأخر، حمراء بلا تسجيل.": "Points: green on time, black late, red no check-in.",
"هذا اليوم ليس يوم عمل.": "This day is not a workday.",
"عرض": "Show",
"التقرير الشهري": "Monthly report",
"حاضرون": "Present",
"متأخرون": "Late",
"غياب / عدم تسجيل": "Absent / no check-in",
"لم يسجّلوا بعد": "Not checked in yet",
"لم يسجّل بعد": "Not checked in yet",
"غياب بعذر": "Excused",
"من المنزل": "Work from home",
"من المقر": "Office",
"الموظف": "Employee",
"النقطة": "Point",
"الحضور": "Check-in",
"الانصراف": "Check-out",
"التأخير (د)": "Late (min)",
"ساعات العمل": "Worked hours",
"مكان العمل": "Work mode",
"غياب متتالٍ": "Consecutive absences",
"النقاط الحمراء تُسجَّل تلقائيًا في نهاية يوم العمل. التصحيح من سجل الحضور مع سبب مكتوب.": "Red points are recorded automatically at the end of the workday. Corrections go through the attendance record with a written reason.",
"نسبة الالتزام = الخضراء ÷ (الخضراء + السوداء + الحمراء). البونص من": "Commitment = green ÷ (green + black + red). Bonus from",
"إلى": "to",
"عند التزام": "at a commitment of",
"فأكثر.": "or more.",
"تصدير CSV": "Export CSV",
"مستحقو البونص": "Bonus-eligible",
"إجمالي البونص": "Total bonus",
"إجمالي دقائق التأخير": "Total late minutes",
"ساعات إضافية": "Overtime hours",
"بعذر": "Excused",
"انصراف مبكر (د)": "Early leave (min)",
"إضافي (س)": "Overtime (h)",
"الالتزام": "Commitment",
"البونص": "Bonus",
"لا توجد سجلات حضور لهذا الشهر.": "No attendance records for this month.",
"البونص مقترح محسوب ولا يُصرف تلقائيًا. يُعتمد من الإدارة ضمن الرواتب.": "The bonus is a calculated proposal and is not paid automatically. Management approves it within payroll.",
"حضوري اليوم": "My attendance today",
"لديك": "You have",
"أيام عمل متتالية بدون تسجيل حضور": "consecutive workdays without check-in",
"تواصل مع مديرك أو HR.": "Please contact your manager or HR.",
"تسجيل الحضور": "Check in",
"تسجيل الانصراف": "Check out",
"اليوم ليس يوم عمل؛ الوقت يُحسب إضافيًا بلا نقطة.": "Today is not a workday; time counts as overtime with no point.",
"تأخير": "Late",
"دقيقة": "minutes",
"حضور بدون انصراف مسجل. صحّح السجل.": "Check-in without a recorded check-out. Correct the record.",
"تم اعتماد قواعد الحضور والبونص.": "Attendance and bonus rules approved.",
"قواعد الحضور والبونص": "Attendance and bonus rules",
"بداية العمل": "Work start",
"نهاية العمل": "Work end",
"سماحية التأخير (دقائق)": "Late grace (minutes)",
"أيام الغياب المتتالي للتنبيه": "Consecutive absent days before alert",
"أقل بونص": "Minimum bonus",
"أعلى بونص": "Maximum bonus",
"حد الاستحقاق %": "Eligibility threshold %",
"أيام العمل": "Workdays",
"الأحد": "Sunday", "الاثنين": "Monday", "الثلاثاء": "Tuesday", "الأربعاء": "Wednesday", "الخميس": "Thursday", "الجمعة": "Friday", "السبت": "Saturday",
"اعتماد قواعد الحضور": "Approve attendance rules"
```

تحقق من صحة الـJSON:
Run: `php -r 'json_decode(file_get_contents("lang/en.json"), true, 512, JSON_THROW_ON_ERROR); echo "ok";'`
Expected: `ok`.

- [ ] **Step 12: شغّل الاختبار ثم كل اختبارات المشروع**

Run: `php artisan test --compact tests/Feature/Operating/AttendanceWidgetAndSettingsTest.php` (المتوقع PASS)
Run: `php artisan test --compact` (المتوقع PASS لكل الاختبارات)

- [ ] **Step 13: Pint + Commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat(attendance): employee check-in widget, absence warning, sidebar links and owner-managed rules

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

---

### Task 1.9: تحقق نهائي في التطبيق الحقيقي

- [ ] **Step 1:** `php artisan migrate --no-interaction` على قاعدة pgsql المحلية (جدول notifications)، ثم `npm run build` إن لزم.
- [ ] **Step 2:** استخدم skill `run` أو `chrome-devtools-mcp:chrome-devtools` لفتح `http://localhost:8080/admin` (سجّل الدخول بحساب owner):
  - اربط حسابًا تجريبيًا بموظف active (من شاشة الموظف: الحقل "معرف حساب المستخدم المرتبط")، وتأكد من ظهور widget "حضوري اليوم" وزر تسجيل الحضور.
  - سجّل الحضور ثم الانصراف، وافتح `/admin/operations/attendance/board` و`/admin/operations/attendance/monthly`.
  - جرّب الواجهتين بالعربية والإنجليزية (`?ui_lang=en`) وفي الوضع الداكن.
  - شغّل `php artisan operating:attendance-close-day` وتأكد من ظهور الإشعارات في `/admin/operations/notifications`.
- [ ] **Step 3:** تأكد أن السيرفر يشغّل `schedule:run` كل دقيقة (اقرأ `DEPLOYMENT.md`). إن لم يكن مذكورًا، فاذكره للمستخدم ولا تعدّل السيرفر.
- [ ] **Step 4:** استخدم skill `verification-before-completion`، ثم اعرض النتيجة على المستخدم واسأله قبل أي push أو PR (`commit-commands:commit-push-pr`).

---

## Self-Review (أُجري عند كتابة الخطة)

**تغطية مواصفة الحضور:**
| المتطلب | التاسك |
|---|---|
| 🟢 في الموعد أو قبله | 1.2 `derive` + 1.3 |
| ⚫ متأخر + وقت الحضور الفعلي + مدة التأخير | 1.2 + 1.3 (`check_in`، `late_minutes`) |
| 🔴 لم يسجّل خلال اليوم + "عدم حضور / عدم تسجيل" | 1.5 `closeDay` (status `absent` بتسمية "غياب / عدم تسجيل") |
| يومان متتاليان: تنبيه HR/المدير + يظهر للموظف + يُسجَّل | 1.5 (`absence_streak`) + 1.8 (widget) + السجلات نفسها والأحداث |
| لوحة يومية: الحالة والنقاط والأوقات والمتأخرون ومن لم يسجّل والغياب المتتالي | 1.6 board |
| نسبة الالتزام + المستحقون + البونص 25–40 | 1.2 `bonus` + 1.6 `monthSummary` |
| تقارير يومية وشهرية | 1.6 (+ CSV) |
| إشعار الموظف عند التأخير | 1.3 `late_check_in` |
| إشعار HR/المدير عند عدم التسجيل | 1.5 `missing_check_in` و`missing_check_in_digest` |
| إشعار يومين متتاليين | 1.5 `absence_streak` (+ mail) |
| تقرير المستحقين الشهري | 1.7 `bonus_report` (+ mail) |
| ساعات العمل، Early check-out، Overtime، WFH | 1.1 `mode` + 1.2 (worked/early/overtime) |
| تحديد ساعات العمل | 1.1 (`shift_start`/`shift_end`) + 1.8 (الإعدادات) |
| جهاز بصمة مستقبلًا | 1.3 `punch(..., source: 'device')` |
| ربط بالمرتبات والبونص | مؤجَّل للمرحلة 3 (مسودات `attendance_bonus`) بحسب الـroadmap |

**الأسماء والتواقيع:** `punch` و`derive` و`initialStatus` و`closeDay` و`missingStreak` و`monthSummary` و`board` و`today` و`bonus` و`rules` و`isWorkday` و`schedule` تُستخدم بنفس التواقيع في كل التاسكات. وأنواع الإشعارات: `late_check_in` و`missing_check_in` و`missing_check_in_digest` و`absence_streak` و`bonus_report`.

**قيود معروفة (موثقة في الـroadmap D7–D9):** لا ورديات ليلية، ولا قيد IP/موقع، والحضور في يوم إجازة بلا نقطة.
