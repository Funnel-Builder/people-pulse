<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import MobileNav from '@/components/MobileNav.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <MobileNav />
        <AppContent variant="sidebar" class="overflow-x-hidden mb-16 md:mb-0">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <div class="px-2 py-2 md:px-3 md:py-3 lg:px-12 lg:py-4">
                <slot />
            </div>
        </AppContent>
    </AppShell>
</template>
