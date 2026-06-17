<script setup lang="ts">
import { IconCheck } from '@tabler/icons-vue';

interface StyleOption {
    key: string;
    preview: string;
    name: string;
    description?: string;
}

const props = defineProps<{
    modelValue: string;
    styles: StyleOption[];
}>();

const emit = defineEmits<{
    'update:modelValue': [string];
}>();

const select = (key: string) => {
    emit('update:modelValue', key);
};
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-3">
        <button
            v-for="style in props.styles"
            :key="style.key"
            type="button"
            class="relative flex cursor-pointer flex-col overflow-hidden rounded-xl border-2 border-foreground bg-card text-left shadow-2xs transition-all hover:bg-foreground/5"
            :class="modelValue === style.key ? '!bg-violet-100 shadow-md' : ''"
            @click="select(style.key)"
        >
            <div class="aspect-video w-full overflow-hidden bg-muted">
                <img :src="style.preview" :alt="style.name" class="size-full object-cover" />
            </div>
            <div class="flex items-start gap-2 p-3">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold text-foreground">{{ style.name }}</p>
                    <p v-if="style.description" class="mt-0.5 text-xs leading-snug text-foreground/60">{{ style.description }}</p>
                </div>
                <IconCheck v-if="modelValue === style.key" class="mt-0.5 size-4 shrink-0 text-foreground" stroke-width="3" />
            </div>
        </button>
    </div>
</template>
