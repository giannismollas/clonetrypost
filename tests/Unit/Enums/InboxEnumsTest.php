<?php

declare(strict_types=1);

use App\Enums\Inbox\Kind;
use App\Enums\Inbox\MessageDirection;
use App\Enums\Inbox\Status;

test('Kind enum has comment, dm, and mention cases', function () {
    expect(Kind::Comment->value)->toBe('comment');
    expect(Kind::Dm->value)->toBe('dm');
    expect(Kind::Mention->value)->toBe('mention');
});

test('Status enum has unread, read, replied, archived cases', function () {
    expect(Status::Unread->value)->toBe('unread');
    expect(Status::Read->value)->toBe('read');
    expect(Status::Replied->value)->toBe('replied');
    expect(Status::Archived->value)->toBe('archived');
});

test('MessageDirection enum has inbound and outbound cases', function () {
    expect(MessageDirection::Inbound->value)->toBe('inbound');
    expect(MessageDirection::Outbound->value)->toBe('outbound');
});
