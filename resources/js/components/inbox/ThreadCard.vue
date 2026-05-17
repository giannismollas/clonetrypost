<script setup lang="ts">
import { IconAt, IconBrandX, IconMail, IconMessageCircle } from '@tabler/icons-vue';
import { computed } from 'vue';
import type { InboxThread } from '@/types/inbox';
import date from '@/date';

const props = defineProps<{
    thread: InboxThread;
    selected: boolean;
}>();

const emit = defineEmits<{
    select: [thread: InboxThread];
}>();

const platformIcon = computed(() => {
    return {
        x: IconBrandX,
    }[props.thread.platform] ?? IconMessageCircle;
});

const kindIcon = computed(() => {
    return {
        comment: IconMessageCircle,
        dm: IconMail,
        mention: IconAt,
    }[props.thread.kind];
});
</script>

<template>
    <button
        type="button"
        :class="[
            'w-full text-left p-3 border-b border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-900 transition-colors',
            selected && 'bg-zinc-100 dark:bg-zinc-900',
            thread.status === 'unread' && 'font-semibold',
        ]"
        :data-test="`inbox-thread-${thread.id}`"
        @click="emit('select', thread)"
    >
        <div class="flex items-start gap-3">
            <img
                v-if="thread.participant_avatar"
                :src="thread.participant_avatar"
                :alt="thread.participant_handle ?? ''"
                class="size-10 rounded-full shrink-0"
            />
            <div v-else class="size-10 rounded-full bg-zinc-200 dark:bg-zinc-800 shrink-0" />
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <span class="truncate">{{ thread.participant_handle ?? '—' }}</span>
                    <component :is="kindIcon" class="size-3.5 text-zinc-500 shrink-0" />
                </div>
                <div class="text-xs text-zinc-500 mt-1 flex items-center gap-2">
                    <component :is="platformIcon" class="size-3.5" />
                    <span>{{ thread.last_message_at ? date.diffForHumans(thread.last_message_at) : '' }}</span>
                </div>
            </div>
        </div>
    </button>
</template>
