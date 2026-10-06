<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\OperatingAlert;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

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
     * Deliver per recipient so a failing mail transport is logged without blocking the attendance flow or other recipients.
     *
     * @param  iterable<User|null>  $users
     */
    public function send(iterable $users, OperatingAlert $alert): void
    {
        foreach (collect($users)->filter()->unique('id')->values() as $recipient) {
            try {
                Notification::send($recipient, $alert);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }
}
