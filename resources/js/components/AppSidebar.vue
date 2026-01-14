<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import { useTenant } from '@/composables/useTenant';

import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Clock, LayoutGrid, Users, Shield, UserCog, FileBarChart, Settings as SettingsIcon, UsersRound, CalendarDays, ClipboardList, CalendarCog, FolderOpen, BarChart3, Bell } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const { url } = useTenant();

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: url('/dashboard'),
            icon: LayoutGrid,
        },
        {
            title: 'My Attendance',
            href: url('/attendance'),
            icon: Clock,
        },
    ];

    // Leaves Group (all users)
    const leaveChildren: NavItem[] = [
        {
            title: 'My Leaves',
            href: url('/leaves'),
            icon: CalendarDays,
        },
    ];

    // Cover Requests - shown to all employees (they may be asked to cover)
    leaveChildren.push({
        title: 'Cover Requests',
        href: url('/leaves/requests'),
        icon: ClipboardList,
    });

    // Leave Approvals - only for managers and admins
    if (user.value?.role === 'manager' || user.value?.role === 'admin') {
        leaveChildren.push({
            title: 'Leave Approvals',
            href: url('/leaves/approvals'),
            icon: ClipboardList,
        });
    }

    items.push({
        title: 'Leaves',
        href: '#',
        icon: CalendarDays,
        isGroup: true,
        children: leaveChildren,
    });

    // Workforce Group (Manager only - Team Attendance)
    if (user.value?.role === 'manager') {
        items.push({
            title: 'Workforce',
            href: '#',
            icon: Users,
            isGroup: true,
            children: [
                {
                    title: 'Team Attendance',
                    href: url('/attendance/manager'),
                    icon: UsersRound,
                },
            ],
        });
    }

    // Records Group (Admin/Manager only)
    if (user.value?.role === 'manager' || user.value?.role === 'admin') {
        const recordsChildren: NavItem[] = [];

        if (user.value?.role === 'admin') {
            recordsChildren.push({
                title: 'Attendance Records',
                href: url('/records/attendance'),
                icon: Clock,
            });
        }

        recordsChildren.push({
            title: 'Leave Records',
            href: url('/records/leave'),
            icon: CalendarDays,
        });

        items.push({
            title: 'Records',
            href: '#',
            icon: FolderOpen,
            isGroup: true,
            children: recordsChildren,
        });
    }

    // Reports Group (Admin/Manager only)
    if (user.value?.role === 'manager' || user.value?.role === 'admin') {
        items.push({
            title: 'Reports',
            href: '#',
            icon: BarChart3,
            isGroup: true,
            children: [
                {
                    title: 'Attendance Report',
                    href: url('/reports/attendance'),
                    icon: FileBarChart,
                },
                {
                    title: 'Leave Report',
                    href: url('/reports/leave'),
                    icon: FileBarChart,
                },
            ],
        });
    }

    // Announcements (Admin/Manager only)
    if (user.value?.role === 'manager' || user.value?.role === 'admin') {
        items.push({
            title: 'Announcements',
            href: url('/announcements'),
            icon: Bell,
        });
    }

    // Office Settings Group (Admin only)
    if (user.value?.role === 'admin') {
        const officeSettingsChildren: NavItem[] = [
            {
                title: 'Attendance Settings',
                href: url('/settings/attendance'),
                icon: Clock,
            },
            {
                title: 'Leave Settings',
                href: url('/settings/leaves'),
                icon: CalendarCog,
            },
        ];

        items.push({
            title: 'Office Settings',
            href: '#',
            icon: SettingsIcon,
            isGroup: true,
            children: officeSettingsChildren,
        });
    }

    // Employee Management as top-level item (admin only)
    if (user.value?.role === 'admin') {
        items.push({
            title: 'Employee Management',
            href: url('/employees'),
            icon: UserCog,
        });
    }

    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="hidden md:flex">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="url('/dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <!-- User menu moved to header -->
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
