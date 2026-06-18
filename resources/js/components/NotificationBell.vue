<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { usePushNotifications } from '@/composables/usePushNotifications';
import { router, usePage } from '@inertiajs/vue3';
import { Bell, CheckCheck } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';

interface NotificationItem {
    id: string;
    data: {
        title?: string;
        message?: string;
        url?: string;
        [key: string]: unknown;
    };
    read_at: string | null;
    created_at: string;
}

const page = usePage();

const items = computed<NotificationItem[]>(
    () => (page.props.notifications as { items?: NotificationItem[] })?.items ?? [],
);
const unreadCount = computed<number>(
    () => (page.props.notifications as { unreadCount?: number })?.unreadCount ?? 0,
);
const vapidPublicKey = computed<string | null>(
    () => (page.props.vapidPublicKey as string) ?? null,
);

const { subscribe } = usePushNotifications();

onMounted(() => {
    // Register the service worker and subscribe this browser for native push.
    subscribe(vapidPublicKey.value);
});

const openNotification = (item: NotificationItem) => {
    if (!item.read_at) {
        router.post(`/notifications/${item.id}/read`, {}, { preserveScroll: true, preserveState: true });
    }
    if (item.data.url) {
        router.visit(item.data.url);
    }
};

const markAllRead = () => {
    router.post('/notifications/read-all', {}, { preserveScroll: true, preserveState: true });
};

const formatTime = (iso: string) => {
    const date = new Date(iso);
    const diffMs = Date.now() - date.getTime();
    const mins = Math.floor(diffMs / 60000);
    if (mins < 1) return 'Just now';
    if (mins < 60) return `${mins}m ago`;
    const hours = Math.floor(mins / 60);
    if (hours < 24) return `${hours}h ago`;
    const days = Math.floor(hours / 24);
    if (days < 7) return `${days}d ago`;
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger class="relative flex items-center outline-none">
            <Button variant="ghost" size="icon" class="relative h-9 w-9 text-muted-foreground hover:text-foreground">
                <Bell class="h-5 w-5" />
                <span
                    v-if="unreadCount > 0"
                    class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white"
                >
                    {{ unreadCount > 9 ? '9+' : unreadCount }}
                </span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-80 p-0">
            <div class="flex items-center justify-between border-b px-4 py-3">
                <span class="text-sm font-semibold">Notifications</span>
                <button
                    v-if="unreadCount > 0"
                    class="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
                    @click="markAllRead"
                >
                    <CheckCheck class="h-3.5 w-3.5" />
                    Mark all read
                </button>
            </div>

            <div class="max-h-96 overflow-y-auto">
                <div v-if="items.length === 0" class="px-4 py-10 text-center text-sm text-muted-foreground">
                    You're all caught up.
                </div>
                <button
                    v-for="item in items"
                    :key="item.id"
                    class="flex w-full items-start gap-3 border-b px-4 py-3 text-left transition-colors last:border-0 hover:bg-muted/50"
                    :class="{ 'bg-blue-50/60 dark:bg-blue-950/20': !item.read_at }"
                    @click="openNotification(item)"
                >
                    <span
                        class="mt-1.5 h-2 w-2 shrink-0 rounded-full"
                        :class="item.read_at ? 'bg-transparent' : 'bg-blue-500'"
                    ></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-foreground">{{ item.data.title || 'Notification' }}</p>
                        <p class="truncate text-xs text-muted-foreground">{{ item.data.message }}</p>
                        <p class="mt-0.5 text-[11px] text-muted-foreground/70">{{ formatTime(item.created_at) }}</p>
                    </div>
                </button>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
