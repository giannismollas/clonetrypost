<script setup lang="ts">
defineProps<{
    threads: {
        data: Array<Record<string, unknown>>;
    };
    filters: Record<string, string>;
}>();
</script>

<template>
    <div data-test="inbox-page">
        <pre>{{ threads }}</pre>
    </div>
</template>
