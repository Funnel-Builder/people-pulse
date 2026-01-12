<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Clock, LayoutGrid, Menu, Users } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);

const items = computed(() => {
    const navItems = [
        {
            title: 'Home',
            href: '/dashboard',
            icon: LayoutGrid,
            active: page.url.startsWith('/dashboard'),
        },
        {
            title: 'Attendance',
            href: '/attendance',
            icon: Clock,
            active: page.url.startsWith('/attendance') && !page.url.includes('/manager'),
        },
    ];

    if (user.value?.role === 'manager' || user.value?.role === 'admin') {
        navItems.push({
            title: 'Team',
            href: '/attendance/manager',
            icon: Users,
            active: page.url.startsWith('/attendance/manager'),
        });
    }

    // "More" or "Menu" item to trigger sidebar or go to settings
    // For now, let's just link to settings or profile if sidebar is hidden
    return navItems;
});
</script>

<template>
    <div class="fixed bottom-0 left-0 right-0 z-50 flex h-16 items-center justify-around border-t border-border bg-background pb-safe md:hidden">
        <Link
            v-for="item in items"
            :key="item.href"
            :href="item.href"
            class="flex flex-col items-center justify-center gap-1 text-xs font-medium text-muted-foreground transition-colors hover:text-primary"
            :class="{ 'text-primary': item.active }"
        >
            <component :is="item.icon" class="h-6 w-6" />
            <span>{{ item.title }}</span>
        </Link>
        
        <!-- Sidebar Trigger (Mobile Menu) -->
        <!-- We might want to use a Sheet trigger here in the future -->
    </div>
</template>

<style scoped>
.pb-safe {
    padding-bottom: env(safe-area-inset-bottom, 20px);
}
</style>
