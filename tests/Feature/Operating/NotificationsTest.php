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
