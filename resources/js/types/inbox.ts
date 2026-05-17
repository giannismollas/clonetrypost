export type InboxKind = 'comment' | 'dm' | 'mention';
export type InboxStatus = 'unread' | 'read' | 'replied' | 'archived';
export type InboxPlatform =
    | 'x'
    | 'facebook'
    | 'instagram'
    | 'instagram-facebook'
    | 'youtube'
    | 'threads'
    | 'linkedin'
    | 'linkedin-page'
    | 'bluesky'
    | 'mastodon';

export type InboxThread = {
    id: string;
    platform: InboxPlatform;
    kind: InboxKind;
    status: InboxStatus;
    participant_handle: string | null;
    participant_avatar: string | null;
    last_message_at: string | null;
    last_user_message_at: string | null;
    social_account_id: string;
};

export type InboxMessage = {
    id: string;
    direction: 'inbound' | 'outbound';
    author_handle: string | null;
    author_is_us: boolean;
    body: string | null;
    media: Array<{ url: string; type: string }> | null;
    posted_at: string | null;
    was_sent_via_trypost: boolean;
};
