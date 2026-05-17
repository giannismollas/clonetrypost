<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Inbox\Kind;
use App\Enums\SocialAccount\Platform;
use App\Models\InboxSyncState;
use App\Models\SocialAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InboxSyncState>
 */
class InboxSyncStateFactory extends Factory
{
    protected $model = InboxSyncState::class;

    public function definition(): array
    {
        return [
            'social_account_id' => SocialAccount::factory(),
            'platform' => Platform::X->value,
            'kind' => Kind::Mention->value,
            'last_synced_at' => null,
            'last_cursor' => null,
            'last_error' => null,
        ];
    }
}
