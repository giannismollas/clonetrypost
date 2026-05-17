<script setup lang="ts">
import type { InboxMessage } from '@/types/inbox';
import date from '@/date';

defineProps<{
    message: InboxMessage;
}>();
</script>

<template>
    <div
        :class="[
            'flex',
            message.author_is_us ? 'justify-end' : 'justify-start',
        ]"
        :data-test="`inbox-message-${message.id}`"
    >
        <div
            :class="[
                'max-w-[70%] rounded-2xl px-4 py-2',
                message.author_is_us
                    ? 'bg-primary text-primary-foreground'
                    : 'bg-muted text-foreground',
            ]"
        >
            <div class="whitespace-pre-wrap">{{ message.body }}</div>
            <div
                :class="[
                    'text-xs mt-1',
                    message.author_is_us ? 'text-primary-foreground/70' : 'text-muted-foreground',
                ]"
            >
                {{ message.posted_at ? date.formatDateTime(message.posted_at) : '' }}
            </div>
        </div>
    </div>
</template>
