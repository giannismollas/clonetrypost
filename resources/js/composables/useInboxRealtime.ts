import { router } from '@inertiajs/vue3';
import { useEcho } from '@laravel/echo-vue';

export const useInboxRealtime = (workspaceId: string) => {
    useEcho(
        `workspace.${workspaceId}.inbox`,
        '.inbox.item.received',
        () => {
            router.reload({ only: ['threads', 'accounts'], preserveScroll: true });
        },
    );
};
