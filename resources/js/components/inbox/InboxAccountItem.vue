<script setup lang="ts">
import { computed } from 'vue';
import {
    IconAlertCircle,
    IconBrandBluesky,
    IconBrandFacebook,
    IconBrandInstagram,
    IconBrandLinkedin,
    IconBrandMastodon,
    IconBrandThreads,
    IconBrandX,
    IconBrandYoutube,
    IconShareplay,
} from '@tabler/icons-vue';
import type { InboxAccount } from '@/types/inbox';

const props = defineProps<{
    account: InboxAccount;
    selected: boolean;
}>();

defineEmits<{
    click: [];
}>();

const platformIcon = computed(() => {
    return (
        {
            x: IconBrandX,
            facebook: IconBrandFacebook,
            instagram: IconBrandInstagram,
            'instagram-facebook': IconBrandInstagram,
            youtube: IconBrandYoutube,
            threads: IconBrandThreads,
            linkedin: IconBrandLinkedin,
            'linkedin-page': IconBrandLinkedin,
            bluesky: IconBrandBluesky,
            mastodon: IconBrandMastodon,
        }[props.account.platform] ?? IconShareplay
    );
});
</script>

<template>
    <button
        type="button"
        :class="[
            'flex w-full items-center gap-3 border-b border-border p-3 text-left transition-colors',
            selected ? 'bg-muted' : 'hover:bg-accent',
        ]"
        :data-test="`inbox-account-${account.id}`"
        @click="$emit('click')"
    >
        <div class="relative shrink-0">
            <img
                v-if="account.avatar"
                :src="account.avatar"
                :alt="account.username ?? ''"
                class="size-9 rounded-full"
            />
            <div v-else class="size-9 rounded-full bg-muted" />
            <component
                :is="platformIcon"
                class="absolute -right-1 -bottom-1 size-4 rounded-full bg-background p-0.5 text-foreground"
            />
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-1.5">
                <span class="truncate text-sm font-medium text-foreground">@{{ account.username ?? '—' }}</span>
                <IconAlertCircle
                    v-if="account.requires_inbox_scope_upgrade"
                    class="size-3.5 shrink-0 text-amber-600"
                    title="Needs re-authorization"
                />
            </div>
        </div>
        <span
            v-if="account.unread_count > 0"
            class="ml-auto inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-primary px-1.5 text-xs font-medium text-primary-foreground"
            :data-test="`inbox-account-unread-${account.id}`"
        >
            {{ account.unread_count }}
        </span>
    </button>
</template>
