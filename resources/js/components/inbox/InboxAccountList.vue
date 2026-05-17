<script setup lang="ts">
import type { InboxAccount } from '@/types/inbox';
import InboxAccountItem from './InboxAccountItem.vue';

defineProps<{
    accounts: InboxAccount[];
    selectedId: string | null;
}>();

const emit = defineEmits<{
    select: [account: InboxAccount];
}>();
</script>

<template>
    <div class="flex h-full w-[240px] shrink-0 flex-col border-r border-border bg-card" data-test="inbox-account-list">
        <div class="border-b border-border p-3">
            <h3 class="text-sm font-semibold text-foreground">Accounts</h3>
        </div>
        <div class="flex-1 overflow-y-auto">
            <InboxAccountItem
                v-for="account in accounts"
                :key="account.id"
                :account="account"
                :selected="account.id === selectedId"
                @click="emit('select', account)"
            />
            <div v-if="accounts.length === 0" class="p-4 text-center text-sm text-muted-foreground">
                Connect a social account to start receiving messages.
            </div>
        </div>
    </div>
</template>
