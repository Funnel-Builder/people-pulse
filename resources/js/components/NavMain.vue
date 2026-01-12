<script setup lang="ts">
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { urlIsActive } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';
import { ref, computed } from 'vue';

const props = defineProps<{
    items: NavItem[];
}>();

const page = usePage();
const { state } = useSidebar();
const isCollapsed = computed(() => state.value === 'collapsed');

// Track which groups are open
// true = open, false = closed (user explicitly closed it)
const openGroups = ref<Record<string, boolean>>({});

// Check if any child in a group is currently active
const hasActiveChild = (item: NavItem): boolean => {
    if (!item.children) return false;
    return item.children.some(child => urlIsActive(child.href, page.url));
};

// Determine if a group should be open
// Open if: explicitly opened OR has active child (and not explicitly closed)
const isGroupOpen = (item: NavItem): boolean => {
    const groupState = openGroups.value[item.title];
    
    // If explicitly set, use that value
    if (groupState !== undefined) {
        return groupState;
    }
    
    // Auto-open if has active child (first time)
    if (hasActiveChild(item)) {
        // Mark it as explicitly open so it stays open when navigating away
        openGroups.value[item.title] = true;
        return true;
    }
    
    // Default to closed
    return false;
};

// Handle user toggle - only update the clicked group, leave others unchanged
const handleToggle = (title: string, newValue: boolean) => {
    openGroups.value[title] = newValue;
};
</script>

<template>
    <SidebarGroup class="py-0">
        <SidebarGroupLabel>Platform</SidebarGroupLabel>
        <SidebarMenu>
            <template v-for="item in items" :key="item.title">
                <!-- When Collapsed: Flatten groups and show all children as individual icons -->
                <template v-if="isCollapsed && item.isGroup && item.children">
                    <SidebarMenuItem v-for="subItem in item.children" :key="subItem.title">
                        <SidebarMenuButton
                            as-child
                            :is-active="urlIsActive(subItem.href, page.url)"
                            :tooltip="subItem.title"
                        >
                            <Link :href="subItem.href" class="flex items-center justify-center">
                                <component :is="subItem.icon" v-if="subItem.icon" />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </template>

                <!-- When Expanded: Show grouped items with Collapsible -->
                <template v-else-if="!isCollapsed && item.isGroup && item.children">
                    <Collapsible
                        as-child
                        :default-open="false"
                        :open="isGroupOpen(item)"
                        @update:open="(value: boolean) => handleToggle(item.title, value)"
                        class="group/collapsible"
                    >
                        <SidebarMenuItem>
                            <CollapsibleTrigger as-child>
                                <SidebarMenuButton 
                                    :tooltip="item.title"
                                >
                                    <component :is="item.icon" v-if="item.icon" />
                                    <span>{{ item.title }}</span>
                                    <ChevronRight 
                                        class="ml-auto h-4 w-4 text-muted-foreground transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" 
                                    />
                                </SidebarMenuButton>
                            </CollapsibleTrigger>
                            <CollapsibleContent>
                                <SidebarMenuSub class="ml-0 border-l-0 px-1.5">
                                    <SidebarMenuSubItem v-for="subItem in item.children" :key="subItem.title">
                                        <SidebarMenuSubButton
                                            as-child
                                            :is-active="urlIsActive(subItem.href, page.url)"
                                            class="pl-8"
                                        >
                                            <Link :href="subItem.href">
                                                <component :is="subItem.icon" v-if="subItem.icon" class="h-3.5 w-3.5 !text-gray-500" />
                                                <span class="text-sm">{{ subItem.title }}</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                </SidebarMenuSub>
                            </CollapsibleContent>
                        </SidebarMenuItem>
                    </Collapsible>
                </template>

                <!-- Regular Menu Item (Non-grouped) - works same in both states -->
                <SidebarMenuItem v-else-if="!item.isGroup">
                    <SidebarMenuButton
                        as-child
                        :is-active="urlIsActive(item.href, page.url)"
                        :tooltip="item.title"
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" v-if="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>

