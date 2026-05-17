<script setup lang="ts">
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { Auth } from '@/types';
import type { InboxThread } from '@/types/inbox';
import InboxFilters from '@/components/inbox/InboxFilters.vue';
import ThreadDetail from '@/components/inbox/ThreadDetail.vue';
import ThreadList from '@/components/inbox/ThreadList.vue';
import { useInboxRealtime } from '@/composables/useInboxRealtime';

defineProps<{
    threads: { data: InboxThread[]; meta?: unknown };
    filters: { platform?: string; kind?: string; status?: string };
}>();

const page = usePage();
const workspaceId = computed(() => (page.props.auth as Auth | undefined)?.currentWorkspace?.id ?? '');

if (workspaceId.value) {
    useInboxRealtime(workspaceId.value);
}

const selected = ref<InboxThread | null>(null);
</script>

<template>
    <div class="h-screen grid grid-cols-[360px_1fr]" data-test="inbox-page">
        <div class="border-r border-zinc-200 dark:border-zinc-800 flex flex-col">
            <InboxFilters :filters="filters" />
            <ThreadList
                :threads="threads.data"
                :selected-id="selected?.id ?? null"
                @select="selected = $event"
            />
        </div>
        <ThreadDetail :thread="selected" />
    </div>
</template>
