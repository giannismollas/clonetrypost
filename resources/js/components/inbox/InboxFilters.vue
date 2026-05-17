<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { index as inboxRoute } from '@/routes/app/inbox';

const props = defineProps<{
    filters: { platform?: string; kind?: string; status?: string };
}>();

const platforms = [
    { value: '', label: 'All platforms' },
    { value: 'x', label: 'X' },
];

const kinds = [
    { value: '', label: 'All types' },
    { value: 'comment', label: 'Comments' },
    { value: 'dm', label: 'DMs' },
    { value: 'mention', label: 'Mentions' },
];

const statuses = [
    { value: '', label: 'All' },
    { value: 'unread', label: 'Unread' },
    { value: 'read', label: 'Read' },
    { value: 'replied', label: 'Replied' },
    { value: 'archived', label: 'Archived' },
];

const update = (key: 'platform' | 'kind' | 'status', value: string) => {
    router.get(
        inboxRoute().url,
        { ...props.filters, [key]: value || undefined },
        { preserveState: true, replace: true, preserveScroll: true },
    );
};
</script>

<template>
    <div class="flex items-center gap-2 p-3 border-b border-border" data-test="inbox-filters">
        <select
            :value="filters.platform ?? ''"
            class="text-sm rounded-md border border-border bg-background px-2 py-1"
            data-test="inbox-filter-platform"
            @change="update('platform', ($event.target as HTMLSelectElement).value)"
        >
            <option v-for="p in platforms" :key="p.value" :value="p.value">{{ p.label }}</option>
        </select>
        <select
            :value="filters.kind ?? ''"
            class="text-sm rounded-md border border-border bg-background px-2 py-1"
            data-test="inbox-filter-kind"
            @change="update('kind', ($event.target as HTMLSelectElement).value)"
        >
            <option v-for="k in kinds" :key="k.value" :value="k.value">{{ k.label }}</option>
        </select>
        <select
            :value="filters.status ?? ''"
            class="text-sm rounded-md border border-border bg-background px-2 py-1"
            data-test="inbox-filter-status"
            @change="update('status', ($event.target as HTMLSelectElement).value)"
        >
            <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
        </select>
    </div>
</template>
