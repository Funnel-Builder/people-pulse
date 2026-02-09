import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
    pendingCoverRequests?: number;
    pendingLeaveApprovals?: number;
    pendingCertificateApprovals?: number;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    subtitle?: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
    isGroup?: boolean;
    children?: NavItem[];
    badge?: number;
}

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    employee_id: string;
    name: string;
    email: string;
    profile_picture?: string | null;
    department?: { id: number; name: string };
    sub_department?: { id: number; name: string };
    designation: string;
    role: 'admin' | 'manager' | 'user';
    weekend_days: string[];
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface Attendance {
    id: number;
    user_id: number;
    user?: User;
    date: string;
    clock_in: string | null;
    clock_out: string | null;
    gross_minutes: number | null;
    break_minutes: number;
    net_minutes: number | null;
    is_late: boolean;
    late_minutes: number;
    early_exit_minutes: number;
    status: 'present' | 'absent' | 'weekend' | 'sick_leave' | 'casual_leave';
    clock_in_ip: string | null;
    clock_out_ip: string | null;
    formatted_gross_hours?: string;
    formatted_net_hours?: string;
    created_at: string;
    updated_at: string;
}

export interface AttendanceAuditLog {
    id: number;
    attendance_id: number;
    changed_by: number;
    changed_by_user?: User;
    field_changed: string;
    old_value: string | null;
    new_value: string | null;
    reason: string;
    ip_address: string | null;
    created_at: string;
}

export interface AttendanceStats {
    total_days: number;
    late_days: number;
    total_net_hours: number;
    average_net_hours: number;
    present: number;
    late: number;
}

export interface DepartmentSummary {
    total_employees: number;
    present: number;
    absent: number;
    late: number;
}

export interface AttendanceFilters {
    start_date?: string;
    end_date?: string;
    department?: string;
    sub_department?: string;
    employee?: string;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginatedData<T> {
    current_page: number;
    data: T[];
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string;
    links: PaginationLink[];
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
    total: number;
}

export interface Announcement {
    id: number;
    title: string;
    content: string;
    type: 'info' | 'warning' | 'success' | 'event';
    target_audience: {
        roles?: string[];
        department_ids?: number[];
        sub_department_ids?: number[];
    } | null;
    starts_at: string | null;
    expires_at: string | null;
    is_active: boolean;
    created_by: number;
    created_by_user?: User;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;
