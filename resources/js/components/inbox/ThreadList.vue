<script setup lang="ts">
import type { InboxThread } from '@/types/inbox';
import ThreadCard from './ThreadCard.vue';

defineProps<{
    threads: InboxThread[];
    selectedId: string | null;
}>();

const emit = defineEmits<{
    select: [thread: InboxThread];
}>();
</script>

<template>
    <div class="h-full overflow-y-auto" data-test="inbox-thread-list">
        <ThreadCard
            v-for="thread in threads"
            :key="thread.id"
            :thread="thread"
            :selected="thread.id === selectedId"
            @select="emit('select', $event)"
        />
        <div v-if="threads.length === 0" class="p-6 text-center text-sm text-zinc-500">
            No conversations yet.
        </div>
    </div>
</template>
