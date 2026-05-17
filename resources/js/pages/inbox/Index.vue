<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import type { Auth } from '@/types';
import type { InboxThread } from '@/types/inbox';
import AppLayout from '@/layouts/AppLayout.vue';
import InboxFilters from '@/components/inbox/InboxFilters.vue';
import ThreadDetail from '@/components/inbox/ThreadDetail.vue';
import ThreadList from '@/components/inbox/ThreadList.vue';
import XReauthBanner from '@/components/inbox/XReauthBanner.vue';
import { useInboxRealtime } from '@/composables/useInboxRealtime';

const props = withDefaults(defineProps<{
    threads: { data: InboxThread[]; meta?: unknown };
    filters: { platform?: string; kind?: string; status?: string };
    x_accounts_needing_upgrade?: Array<{ id: string; username: string }>;
}>(), {
    x_accounts_needing_upgrade: () => [],
});

const page = usePage();
const workspaceId = computed(() => (page.props.auth as Auth | undefined)?.currentWorkspace?.id ?? '');

if (workspaceId.value) {
    useInboxRealtime(workspaceId.value);
}

const selected = ref<InboxThread | null>(null);
</script>

<template>
    <Head title="Inbox" />

    <AppLayout full-width>
        <div class="flex h-full flex-1 flex-col" data-test="inbox-page">
            <XReauthBanner :accounts="props.x_accounts_needing_upgrade" />

            <div class="grid flex-1 grid-cols-[360px_1fr] overflow-hidden">
                <div class="flex flex-col border-r border-border">
                    <InboxFilters :filters="filters" />
                    <ThreadList
                        :threads="threads.data"
                        :selected-id="selected?.id ?? null"
                        @select="selected = $event"
                    />
                </div>
                <ThreadDetail :thread="selected" />
            </div>
        </div>
    </AppLayout>
</template>
