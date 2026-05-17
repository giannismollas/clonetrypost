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
            'w-full text-left p-3 border-b border-border hover:bg-accent transition-colors',
            selected && 'bg-muted',
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
            <div v-else class="size-10 rounded-full bg-muted shrink-0" />
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <span class="truncate">{{ thread.participant_handle ?? '—' }}</span>
                    <component :is="kindIcon" class="size-3.5 text-muted-foreground shrink-0" />
                </div>
                <div class="text-xs text-muted-foreground mt-1 flex items-center gap-2">
                    <component :is="platformIcon" class="size-3.5" />
                    <span>{{ thread.last_message_at ? date.diffForHumans(thread.last_message_at) : '' }}</span>
                </div>
            </div>
        </div>
    </button>
</template>
