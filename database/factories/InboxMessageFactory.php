<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Inbox\MessageDirection;
use App\Models\InboxMessage;
use App\Models\InboxThread;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InboxMessage>
 */
class InboxMessageFactory extends Factory
{
    protected $model = InboxMessage::class;

    public function definition(): array
    {
        return [
            'thread_id' => InboxThread::factory(),
            'external_message_id' => (string) $this->faker->randomNumber(8),
            'direction' => MessageDirection::Inbound->value,
            'author_handle' => '@'.$this->faker->userName(),
            'author_is_us' => false,
            'body' => $this->faker->sentence(),
            'media' => [],
            'posted_at' => now(),
            'fetched_at' => now(),
            'was_sent_via_trypost' => false,
        ];
    }
}
