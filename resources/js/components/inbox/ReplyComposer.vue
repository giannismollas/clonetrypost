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
        class="p-3 border-t border-border flex items-end gap-2"
        data-test="inbox-reply-composer"
        @submit.prevent="submit"
    >
        <textarea
            v-model="form.body"
            rows="2"
            placeholder="Write a reply..."
            class="flex-1 resize-none rounded-md border border-border bg-background px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            data-test="inbox-reply-body"
        />
        <button
            type="submit"
            :disabled="form.processing || !form.body.trim()"
            class="rounded-md bg-primary px-3 py-2 text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
            data-test="inbox-reply-submit"
        >
            <IconSend class="size-4" />
        </button>
    </form>
</template>
