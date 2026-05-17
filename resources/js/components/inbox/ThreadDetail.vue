<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { show as showRoute } from '@/routes/app/inbox/threads';
import type { InboxMessage, InboxThread } from '@/types/inbox';
import MessageBubble from './MessageBubble.vue';
import ReplyComposer from './ReplyComposer.vue';

const props = defineProps<{
    thread: InboxThread | null;
}>();

const messages = ref<InboxMessage[]>([]);
const loading = ref(false);

const load = async () => {
    if (!props.thread) return;
    loading.value = true;
    try {
        const res = await fetch(showRoute(props.thread.id).url, {
            headers: { Accept: 'application/json' },
        });
        const data = await res.json();
        messages.value = data.messages ?? [];
    } finally {
        loading.value = false;
    }
};

watch(() => props.thread?.id, () => {
    messages.value = [];
    load();
});
onMounted(load);
</script>

<template>
    <div v-if="thread" class="flex flex-col h-full" data-test="inbox-thread-detail">
        <div class="p-3 border-b border-border">
            <div class="font-semibold">{{ thread.participant_handle ?? '—' }}</div>
        </div>
        <div class="flex-1 overflow-y-auto p-4 space-y-3" data-test="inbox-thread-messages">
            <MessageBubble v-for="msg in messages" :key="msg.id" :message="msg" />
            <div v-if="loading" class="text-center text-sm text-muted-foreground">Loading…</div>
        </div>
        <ReplyComposer :thread="thread" @sent="load" />
    </div>
    <div v-else class="h-full flex items-center justify-center text-muted-foreground" data-test="inbox-empty-state">
        Select a conversation to start replying.
    </div>
</template>
