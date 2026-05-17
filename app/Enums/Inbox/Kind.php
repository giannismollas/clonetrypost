<?php

declare(strict_types=1);

namespace App\Enums\Inbox;

enum Kind: string
{
    case Comment = 'comment';
    case Dm = 'dm';
    case Mention = 'mention';
}
