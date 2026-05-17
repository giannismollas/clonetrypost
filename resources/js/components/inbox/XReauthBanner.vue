<script setup lang="ts">
import { IconAlertCircle } from '@tabler/icons-vue';
import { upgradeScopes as upgradeXRoute } from '@/routes/auth/x';

defineProps<{
    accounts: Array<{ id: string; username: string }>;
}>();
</script>

<template>
    <div
        v-if="accounts.length > 0"
        class="bg-amber-50 dark:bg-amber-950 border-b border-amber-200 dark:border-amber-900 p-3 flex items-center gap-3"
        data-test="inbox-x-reauth-banner"
    >
        <IconAlertCircle class="size-5 text-amber-600 shrink-0" />
        <div class="flex-1 text-sm">
            <span class="font-medium">Activate X inbox</span>
            <span class="text-foreground">
                — your connected X account{{ accounts.length > 1 ? 's' : '' }} need a one-click re-authorization to read DMs and comments.
            </span>
        </div>
        <a
            v-for="account in accounts"
            :key="account.id"
            :href="upgradeXRoute(account.id).url"
            class="text-sm font-medium rounded-md bg-amber-600 text-white px-3 py-1.5 hover:bg-amber-700"
            :data-test="`inbox-x-reauth-${account.id}`"
        >
            Activate @{{ account.username }}
        </a>
    </div>
</template>
