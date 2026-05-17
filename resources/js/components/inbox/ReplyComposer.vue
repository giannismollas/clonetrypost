<script setup lang="ts">
import { IconSend } from '@tabler/icons-vue';
import { reply as replyRoute } from '@/routes/app/inbox/threads';
import { useForm } from '@inertiajs/vue3';
import type { InboxThread } from '@/types/inbox';

const props = defineProps<{
    thread: InboxThread;
}>();

const emit = defineEmits<{
    sent: [];
}>();

const form = useForm({ body: '' });

const submit = () => {
    if (!form.body.trim()) return;
    form.post(replyRoute(props.thread.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('body');
            emit('sent');
        },
    });
};
</script>

<template>
    <form
        class="p-3 border-t border-zinc-200 dark:border-zinc-800 flex items-end gap-2"
        data-test="inbox-reply-composer"
        @submit.prevent="submit"
    >
        <textarea
            v-model="form.body"
            rows="2"
            placeholder="Write a reply..."
            class="flex-1 resize-none rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            data-test="inbox-reply-body"
        />
        <button
            type="submit"
            :disabled="form.processing || !form.body.trim()"
            class="rounded-md bg-blue-600 px-3 py-2 text-white disabled:opacity-50"
            data-test="inbox-reply-submit"
        >
            <IconSend class="size-4" />
        </button>
    </form>
</template>
