<?php

declare(strict_types=1);

namespace App\Enums\Inbox;

enum Status: string
{
    case Unread = 'unread';
    case Read = 'read';
    case Replied = 'replied';
    case Archived = 'archived';
}
