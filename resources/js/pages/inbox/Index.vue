<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import type { Auth } from '@/types';
import type { InboxAccount, InboxThread } from '@/types/inbox';
import { index as inboxRoute } from '@/routes/app/inbox';
import AppLayout from '@/layouts/AppLayout.vue';
import InboxAccountList from '@/components/inbox/InboxAccountList.vue';
import InboxFilters from '@/components/inbox/InboxFilters.vue';
import ThreadDetail from '@/components/inbox/ThreadDetail.vue';
import ThreadList from '@/components/inbox/ThreadList.vue';
import XReauthBanner from '@/components/inbox/XReauthBanner.vue';
import { useInboxRealtime } from '@/composables/useInboxRealtime';

const props = defineProps<{
    accounts: InboxAccount[];
    selected_account_id: string | null;
    threads: { data: InboxThread[]; meta?: unknown };
    filters: { account?: string; kind?: string; status?: string };
}>();

const page = usePage();
const workspaceId = computed(() => (page.props.auth as Auth | undefined)?.currentWorkspace?.id ?? '');

if (workspaceId.value) {
    useInboxRealtime(workspaceId.value);
}

const selected = ref<InboxThread | null>(null);

const xAccountsNeedingUpgrade = computed(() =>
    props.accounts
        .filter((a) => a.platform === 'x' && a.requires_inbox_scope_upgrade)
        .map((a) => ({ id: a.id, username: a.username ?? '' })),
);

const selectAccount = (account: InboxAccount) => {
    selected.value = null;
    router.get(
        inboxRoute().url,
        { account: account.id },
        { preserveState: true, preserveScroll: true },
    );
};
</script>

<template>
    <Head title="Inbox" />

    <AppLayout full-width>
        <div class="flex h-full flex-1 flex-col" data-test="inbox-page">
            <XReauthBanner :accounts="xAccountsNeedingUpgrade" />

            <div class="flex flex-1 overflow-hidden">
                <InboxAccountList
                    :accounts="accounts"
                    :selected-id="selected_account_id"
                    @select="selectAccount"
                />

                <div class="flex w-[360px] shrink-0 flex-col border-r border-border">
                    <InboxFilters :filters="filters" />
                    <div v-if="accounts.length === 0" class="flex flex-1 items-center justify-center p-4 text-center text-sm text-muted-foreground">
                        Connect an account to see conversations.
                    </div>
                    <ThreadList
                        v-else
                        :threads="threads.data"
                        :selected-id="selected?.id ?? null"
                        @select="selected = $event"
                    />
                </div>

                <div class="flex-1">
                    <ThreadDetail :thread="selected" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
