<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';

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
import { Clock, LayoutGrid, Users, Shield, UserCog, FileBarChart, Settings as SettingsIcon, UsersRound, CalendarDays, CalendarPlus, ClipboardList, CalendarCog } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutGrid,
        },
        {
            title: 'My Attendance',
            href: '/attendance',
            icon: Clock,
        },
    ];

    // Leaves Group (all users)
    const leaveChildren: NavItem[] = [
        {
            title: 'My Leaves',
            href: '/leaves',
            icon: CalendarDays,
        },
    ];

    // Cover Requests - shown to all employees (they may be asked to cover)
    leaveChildren.push({
        title: 'Cover Requests',
        href: '/leaves/requests',
        icon: ClipboardList,
    });

    // Leave Approvals - only for managers and admins
    if (user.value?.role === 'manager' || user.value?.role === 'admin') {
        leaveChildren.push({
            title: 'Leave Approvals',
            href: '/leaves/approvals',
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

    // Workforce Group (Manager/Admin only)
    if (user.value?.role === 'manager' || user.value?.role === 'admin') {
        const workforceChildren: NavItem[] = [];

        if (user.value?.role === 'manager') {
            workforceChildren.push({
                title: 'Team Attendance',
                href: '/attendance/manager',
                icon: UsersRound,
            });
        }

        if (user.value?.role === 'admin') {
            workforceChildren.push({
                title: 'Attendance Records',
                href: '/attendance/admin',
                icon: Shield,
            });
        }

        if (user.value?.role === 'admin' || user.value?.role === 'manager') {
            workforceChildren.push({
                title: 'Employee Analytics',
                href: '/attendance/employee-report',
                icon: FileBarChart,
            });
        }

        items.push({
            title: 'Workforce',
            href: '#',
            icon: Users,
            isGroup: true,
            children: workforceChildren,
        });
    }

    // Reports (Admin/Manager only)
    if (user.value?.role === 'manager' || user.value?.role === 'admin') {
        items.push({
            title: 'Reports',
            href: '/attendance/reports',
            icon: FileBarChart,
        });
    }

    // Office Settings Group (Admin only)
    if (user.value?.role === 'admin') {
        const officeSettingsChildren: NavItem[] = [
            {
                title: 'Attendance Settings',
                href: '/settings/attendance',
                icon: Clock,
            },
            {
                title: 'Leave Settings',
                href: '/settings/leaves',
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
            href: '/employees',
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
                        <Link href="/dashboard">
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
