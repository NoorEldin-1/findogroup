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
        DB::table('crm_lead_relations')->insert(['lead_id' => $lead->id, 'user_id' => $userId, 'relation' => self::SHARED, 'created_by' => $actor->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->event($actor, $lead, 'share', $userId);
        $this->notifier->send([User::find($userId)], new OperatingAlert(
            'lead_shared',
            ['ar' => 'تمت مشاركة عميل معك', 'en' => 'A lead was shared with you'],
            ['ar' => $actor->name.' شارك معك العميل: '.$lead->title, 'en' => $actor->name.' shared the lead: '.$lead->title],
            '/admin/operations/crm/leads/'.$lead->id,
        ));
    }

    public function unshare(User $actor, object $lead, int $userId): void
    {
        if (DB::table('crm_lead_relations')->where(['lead_id' => $lead->id, 'user_id' => $userId, 'relation' => self::SHARED])->delete()) {
            $this->event($actor, $lead, 'unshare', $userId);
        }
    }

    /**
     * Follow the lead, or stop following it when already followed; returns whether the user now follows it.
     */
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

        return User::query()->where('active', true)->whereNotIn('id', $taken)->orderBy('name')->get()
            ->filter(fn (User $user): bool => $this->access->can($user, 'opportunities'))
            ->mapWithKeys(fn (User $user): array => [$user->id => $user->name])->all();
    }

    private function event(User $actor, object $lead, string $action, int $userId): void
    {
        $name = User::find($userId)?->name;
        DB::table('operating_events')->insert(['record_id' => $lead->id, 'user_id' => $actor->id, 'action' => $action, 'reason' => ($action === 'share' ? 'مشاركة العميل مع ' : 'إلغاء مشاركة العميل مع ').$name, 'after' => json_encode(['shared_with' => $userId]), 'created_at' => now()]);
    }
}
