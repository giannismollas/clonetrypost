<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { index as inboxRoute } from '@/routes/app/inbox';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const props = defineProps<{
    filters: { account?: string; kind?: string; status?: string };
}>();

const ALL = 'all';

const kinds = [
    { value: ALL, label: 'All types' },
    { value: 'comment', label: 'Comments' },
    { value: 'dm', label: 'DMs' },
    { value: 'mention', label: 'Mentions' },
];

const statuses = [
    { value: ALL, label: 'All' },
    { value: 'unread', label: 'Unread' },
    { value: 'read', label: 'Read' },
    { value: 'replied', label: 'Replied' },
    { value: 'archived', label: 'Archived' },
];

const update = (key: 'kind' | 'status', value: string) => {
    router.get(
        inboxRoute().url,
        { ...props.filters, [key]: value === ALL ? undefined : value },
        { preserveState: true, replace: true, preserveScroll: true },
    );
};

const valueFor = (current: string | undefined): string => current || ALL;
</script>

<template>
    <div class="flex items-center gap-2 border-b border-border p-3" data-test="inbox-filters">
        <Select
            :model-value="valueFor(filters.kind)"
            @update:model-value="(v) => update('kind', String(v))"
        >
            <SelectTrigger class="w-[140px]" data-test="inbox-filter-kind">
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                <SelectItem v-for="k in kinds" :key="k.value" :value="k.value">
                    {{ k.label }}
                </SelectItem>
            </SelectContent>
        </Select>

        <Select
            :model-value="valueFor(filters.status)"
            @update:model-value="(v) => update('status', String(v))"
        >
            <SelectTrigger class="w-[120px]" data-test="inbox-filter-status">
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                <SelectItem v-for="s in statuses" :key="s.value" :value="s.value">
                    {{ s.label }}
                </SelectItem>
            </SelectContent>
        </Select>
    </div>
</template>
