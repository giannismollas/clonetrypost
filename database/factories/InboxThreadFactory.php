<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Inbox\Kind;
use App\Enums\Inbox\Status;
use App\Enums\SocialAccount\Platform;
use App\Models\InboxThread;
use App\Models\SocialAccount;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InboxThread>
 */
class InboxThreadFactory extends Factory
{
    protected $model = InboxThread::class;

    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'social_account_id' => SocialAccount::factory(),
            'post_platform_id' => null,
            'platform' => Platform::X->value,
            'kind' => Kind::Mention->value,
            'external_thread_id' => (string) $this->faker->randomNumber(8),
            'participant_handle' => '@'.$this->faker->userName(),
            'participant_avatar' => $this->faker->imageUrl(),
            'last_message_at' => now(),
            'last_user_message_at' => null,
            'status' => Status::Unread->value,
            'metadata' => [],
        ];
    }
}
