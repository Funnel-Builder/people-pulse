<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import type { BreadcrumbItem, PaginatedData } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarDays, X, Clock, CheckCircle, XCircle, AlertCircle, Download, ArrowRight } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import DateRangePicker from '@/components/ui/date-range-picker/DateRangePicker.vue';

interface LeaveType {
    id: number;
    name: string;
    code: string;
}

interface SubDepartment {
    id: number;
    name: string;
    department_id: number;
    department?: {
        id: number;
        name: string;
    };
}

interface Employee {
    id: number;
    name: string;
    employee_id: string;
}

interface UserInfo {
    id: number;
    name: string;
    employee_id: string;
    designation: string | null;
    department?: { id: number; name: string };
    sub_department?: { id: number; name: string };
}

interface LeaveRecord {
    id: number;
    user: UserInfo;
    leave_type: LeaveType;
    type: 'advance' | 'post';
    status: 'pending' | 'approved' | 'rejected' | 'cancelled';
    reason: string;
    cover_person: { id: number; name: string } | null;
    days: number;
    start_date: string;
    end_date: string;
    created_at: string;
}

interface Summary {
    total: number;
    pending: number;
    approved: number;
    rejected: number;
}

interface Filters {
    start_date: string;
    end_date: string;
    status: string;
    leave_type: string;
    sub_department: string;
    employee: string;
}

interface Props {
    leaves: PaginatedData<LeaveRecord>;
    leaveTypes: LeaveType[];
    subDepartments: SubDepartment[];
    employees: Employee[];
    summary: Summary;
    filters: Filters;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Leave Records', href: '/records/leave' },
];

const localFilters = ref({
    start_date: props.filters.start_date ?? '',
    end_date: props.filters.end_date ?? '',
    status: props.filters.status ?? '',
    leave_type: props.filters.leave_type ?? '',
    sub_department: props.filters.sub_department ?? '',
    employee: props.filters.employee ?? '',
});

const applyFilters = () => {
    const filters = { ...localFilters.value };
    
    // Sanitize filters
    if (filters.status === 'all') filters.status = '';
    if (filters.leave_type === 'all') filters.leave_type = '';
    if (filters.sub_department === 'all_sub_departments') filters.sub_department = '';
    if (filters.employee === 'all_employees') filters.employee = '';

    router.get('/records/leave', filters, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    localFilters.value = {
        start_date: '',
        end_date: '',
        status: '',
        leave_type: '',
        sub_department: '',
        employee: '',
    };
    applyFilters();
};

// Immediate filter application for selects
watch(() => localFilters.value.status, () => applyFilters());
watch(() => localFilters.value.leave_type, () => applyFilters());
watch(() => localFilters.value.sub_department, () => applyFilters());
watch(() => localFilters.value.employee, () => applyFilters());

const hasActiveFilters = computed(() => {
    return localFilters.value.start_date || 
           localFilters.value.end_date || 
           (localFilters.value.status && localFilters.value.status !== 'all') ||
           (localFilters.value.leave_type && localFilters.value.leave_type !== 'all') ||
           (localFilters.value.sub_department && localFilters.value.sub_department !== 'all_sub_departments') ||
           (localFilters.value.employee && localFilters.value.employee !== 'all_employees');
});

const formatDatePeriod = (start: string, end: string) => {
    const startDate = new Date(start);
    const endDate = new Date(end);
    
    const options: Intl.DateTimeFormatOptions = { month: 'short', day: 'numeric' };
    const startStr = startDate.toLocaleDateString('en-US', options);
    
    if (start === end) return startStr;
    
    // Check if same year and month to condense (e.g., Jan 12 - 15)
    if (startDate.getMonth() === endDate.getMonth() && startDate.getFullYear() === endDate.getFullYear()) {
        return `${startStr} - ${endDate.getDate()}`;
    }
    
    return `${startStr} - ${endDate.toLocaleDateString('en-US', options)}`;
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'approved':
            return { class: 'bg-green-50 text-green-700 border-green-200', label: 'Approved' };
        case 'pending':
            return { class: 'bg-yellow-50 text-yellow-700 border-yellow-200', label: 'Pending' };
        case 'rejected':
            return { class: 'bg-red-50 text-red-700 border-red-200', label: 'Rejected' };
        case 'cancelled':
            return { class: 'bg-gray-50 text-gray-700 border-gray-200', label: 'Cancelled' };
        default:
            return { class: '', label: status };
    }
};

const exportRecords = (type: 'csv' | 'xlsx') => {
    const params = new URLSearchParams();
    params.append('type', type);
    if (localFilters.value.start_date) params.append('start_date', localFilters.value.start_date);
    if (localFilters.value.end_date) params.append('end_date', localFilters.value.end_date);
    if (localFilters.value.status && localFilters.value.status !== 'all') params.append('status', localFilters.value.status);
    if (localFilters.value.leave_type && localFilters.value.leave_type !== 'all') params.append('leave_type', localFilters.value.leave_type);
    if (localFilters.value.sub_department && localFilters.value.sub_department !== 'all_sub_departments') params.append('sub_department', localFilters.value.sub_department);
    if (localFilters.value.employee && localFilters.value.employee !== 'all_employees') params.append('employee', localFilters.value.employee);
    
    window.location.href = `/records/leave/export?${params.toString()}`;
};
</script>

<template>
    <Head title="Leave Records" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <!-- Header with Filters -->
            <div class="flex flex-col gap-4">
                <h1 class="text-2xl font-bold">Leave Records</h1>
                <div class="flex flex-wrap gap-2 items-center">
                    <DateRangePicker 
                        :start-date="localFilters.start_date"
                        :end-date="localFilters.end_date"
                        @update:start-date="(v) => localFilters.start_date = v"
                        @update:end-date="(v) => localFilters.end_date = v"
                        @apply="applyFilters"
                    />
                    <Select v-model="localFilters.status">
                        <SelectTrigger class="w-[140px]">
                            <SelectValue placeholder="All Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Status</SelectItem>
                            <SelectItem value="pending">Pending</SelectItem>
                            <SelectItem value="approved">Approved</SelectItem>
                            <SelectItem value="rejected">Rejected</SelectItem>
                            <SelectItem value="cancelled">Cancelled</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="localFilters.leave_type">
                        <SelectTrigger class="w-[160px]">
                            <SelectValue placeholder="All Leave Types" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Leave Types</SelectItem>
                            <SelectItem v-for="lt in leaveTypes" :key="lt.id" :value="String(lt.id)">
                                {{ lt.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="localFilters.sub_department">
                        <SelectTrigger class="w-[180px]">
                            <SelectValue placeholder="All Sub-Departments" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all_sub_departments">All Sub-Departments</SelectItem>
                            <SelectItem v-for="sub in subDepartments" :key="sub.id" :value="String(sub.id)">
                                {{ sub.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="localFilters.employee">
                        <SelectTrigger class="w-[180px]">
                            <SelectValue placeholder="All Employees" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all_employees">All Employees</SelectItem>
                            <SelectItem v-for="emp in employees" :key="emp.id" :value="String(emp.id)">
                                {{ emp.name }} ({{ emp.employee_id }})
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Button variant="outline" size="icon" @click="resetFilters" v-if="hasActiveFilters" title="Reset Filters">
                        <X class="h-4 w-4" />
                    </Button>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="default">
                                <Download class="mr-2 h-4 w-4" />
                                Export
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem @click="exportRecords('csv')">
                                Export as CSV
                            </DropdownMenuItem>
                            <DropdownMenuItem @click="exportRecords('xlsx')">
                                Export as XLSX
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card class="py-2">
                    <CardHeader class="flex flex-row items-center justify-between py-2 px-4">
                        <CardDescription class="text-xs">Total Applications</CardDescription>
                        <CalendarDays class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent class="py-1 px-4">
                        <div class="text-2xl font-bold">{{ summary.total }}</div>
                    </CardContent>
                </Card>
                <Card class="py-2">
                    <CardHeader class="flex flex-row items-center justify-between py-2 px-4">
                        <CardDescription class="text-xs">Pending</CardDescription>
                        <Clock class="h-4 w-4 text-yellow-500" />
                    </CardHeader>
                    <CardContent class="py-1 px-4">
                        <div class="text-2xl font-bold text-yellow-600">{{ summary.pending }}</div>
                    </CardContent>
                </Card>
                <Card class="py-2">
                    <CardHeader class="flex flex-row items-center justify-between py-2 px-4">
                        <CardDescription class="text-xs">Approved</CardDescription>
                        <CheckCircle class="h-4 w-4 text-green-500" />
                    </CardHeader>
                    <CardContent class="py-1 px-4">
                        <div class="text-2xl font-bold text-green-600">{{ summary.approved }}</div>
                    </CardContent>
                </Card>
                <Card class="py-2">
                    <CardHeader class="flex flex-row items-center justify-between py-2 px-4">
                        <CardDescription class="text-xs">Rejected</CardDescription>
                        <XCircle class="h-4 w-4 text-red-500" />
                    </CardHeader>
                    <CardContent class="py-1 px-4">
                        <div class="text-2xl font-bold text-red-600">{{ summary.rejected }}</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Leave Records Table -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <CalendarDays class="h-5 w-5" />
                        All Leave Records
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="px-4 pb-3 text-left font-medium">Employee</th>
                                    <th class="px-4 pb-3 text-left font-medium">Department</th>
                                    <th class="px-4 pb-3 text-left font-medium">Leave Type</th>
                                    <th class="px-4 pb-3 text-left font-medium">Period</th>
                                    <th class="px-4 pb-3 text-left font-medium">Status</th>
                                    <th class="px-4 pb-3 text-center font-medium">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="leave in leaves.data"
                                    :key="leave.id"
                                    class="border-b last:border-0 hover:bg-muted/30 transition-colors"
                                >
                                    <td class="px-4 py-3">
                                        <div class="font-medium">{{ leave.user?.name }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div v-if="leave.user?.department" class="text-sm">{{ leave.user.department.name }}</div>
                                        <div v-if="leave.user?.sub_department" class="text-xs text-muted-foreground">
                                            {{ leave.user.sub_department.name }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="font-medium">{{ leave.leave_type?.name }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-muted/50 border text-sm">
                                            <CalendarDays class="h-3.5 w-3.5 text-muted-foreground" />
                                            <span class="font-medium">{{ formatDatePeriod(leave.start_date, leave.end_date) }}</span>
                                            <span class="h-3 w-[1px] bg-border mx-1"></span>
                                            <span class="text-xs text-muted-foreground">{{ leave.days }} Day{{ leave.days > 1 ? 's' : '' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <Badge variant="outline" :class="getStatusBadge(leave.status).class">
                                            {{ getStatusBadge(leave.status).label }}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <Link :href="`/records/leave/${leave.id}/manage`">
                                            <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                                <ArrowRight class="h-4 w-4 text-muted-foreground" />
                                            </Button>
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="leaves.data.length === 0">
                                    <td colspan="6" class="py-8 text-center text-muted-foreground">
                                        No leave records found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="leaves.data.length > 0" class="mt-4 flex items-center justify-between border-t pt-4">
                        <div class="text-sm text-muted-foreground">
                            Showing {{ leaves.from }} to {{ leaves.to }} of {{ leaves.total }} results
                        </div>
                        <div class="flex gap-1">
                            <Link
                                v-for="link in leaves.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    'px-3 py-1 text-sm rounded',
                                    link.active ? 'bg-primary text-primary-foreground' : 'bg-muted hover:bg-muted/80',
                                    !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                ]"
                                v-html="link.label"
                                :preserve-scroll="true"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
