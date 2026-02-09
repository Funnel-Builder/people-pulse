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
import { Clock, LayoutGrid, Users, Shield, UserCog, FileBarChart, Settings as SettingsIcon, CalendarDays, ClipboardList, CalendarCog, FolderOpen, BarChart3, Bell, Network, Award } from 'lucide-vue-next';
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
        badge: page.props.auth?.pendingCoverRequests || 0,
    });

    // Leave Approvals - only for managers and admins
    if (user.value?.role === 'manager' || user.value?.role === 'admin') {
        leaveChildren.push({
            title: 'Leave Approvals',
            href: '/leaves/approvals',
            icon: ClipboardList,
            badge: page.props.auth?.pendingLeaveApprovals || 0,
        });
    }

    items.push({
        title: 'Leaves',
        href: '#',
        icon: CalendarDays,
        isGroup: true,
        children: leaveChildren,
    });

    // Services Group (all users can request, managers/admins can approve)
    const servicesChildren: NavItem[] = [
        {
            title: 'Certificate',
            subtitle: 'Request & Manage',
            href: '/services/certificate',
            icon: ClipboardList,
            // permission: 'view_services'
        },
    ];

    // Certificate Approvals - only for managers and admins
    if (user.value?.role === 'admin') {
        servicesChildren.push({
            title: 'Approvals',
            href: '/services/approvals',
            icon: ClipboardList,
            badge: page.props.auth?.pendingCertificateApprovals || 0,
        });
    }

    items.push({
        title: 'Services',
        href: '#',
        icon: Shield,
        isGroup: true,
        children: servicesChildren,
    });

    // Records Group (Admin/Manager only)
    if (user.value?.role === 'manager' || user.value?.role === 'admin') {
        const recordsChildren: NavItem[] = [];

        // Both admin and manager go to the same route
        recordsChildren.push({
            title: 'Attendance Records',
            href: '/records/attendance',
            icon: Clock,
        });

        recordsChildren.push({
            title: 'Leave Records',
            href: '/records/leave',
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
                    href: '/reports/attendance',
                    icon: FileBarChart,
                },
                {
                    title: 'Leave Report',
                    href: '/reports/leave',
                    icon: FileBarChart,
                },
                {
                    title: 'Employee Report',
                    href: '/reports/employees',
                    icon: UserCog, // Using UserCog as a placeholder for Employee Report icon
                },
            ],
        });
    }

    // Announcements (Admin/Manager only)
    if (user.value?.role === 'manager' || user.value?.role === 'admin') {
        items.push({
            title: 'Announcements',
            href: '/announcements',
            icon: Bell,
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
            {
                title: 'Departments',
                href: '/settings/departments',
                icon: Network, // Reuse Network icon or import appropriate one
            },
            {
                title: 'Holidays',
                href: '/settings/holidays',
                icon: CalendarDays,
            },
            {
                title: 'Skills & Expertise',
                href: '/settings/skills',
                icon: Award, 
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
