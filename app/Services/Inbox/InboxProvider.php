<?php

declare(strict_types=1);

namespace App\Services\Inbox;

use App\Enums\SocialAccount\Platform;
use App\Models\InboxThread;
use App\Models\SocialAccount;

interface InboxProvider
{
    public function platform(): Platform;

    public function syncMentions(SocialAccount $account): int;

    public function syncDms(SocialAccount $account): int;

    public function reply(InboxThread $thread, string $body): void;

    public function sendDm(InboxThread $thread, string $body): void;

    public function hideReply(InboxThread $thread): void;
}
