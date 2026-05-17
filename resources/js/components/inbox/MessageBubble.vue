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
                    ? 'bg-blue-600 text-white'
                    : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100',
            ]"
        >
            <div class="whitespace-pre-wrap">{{ message.body }}</div>
            <div
                :class="[
                    'text-xs mt-1',
                    message.author_is_us ? 'text-blue-100' : 'text-zinc-500',
                ]"
            >
                {{ message.posted_at ? date.formatDateTime(message.posted_at) : '' }}
            </div>
        </div>
    </div>
</template>
