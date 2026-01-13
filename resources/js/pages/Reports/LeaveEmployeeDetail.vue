<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { BreadcrumbItem } from '@/types';
import { Head, router, Link } from '@inertiajs/vue3';
import { 
    User as UserIcon, Calendar, CalendarDays, ArrowLeft
} from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';

interface Employee {
    id: number;
    name: string;
    employee_id: string;
    department_name: string | null;
    sub_department_name: string | null;
    designation: string | null;
}

interface EmployeeSelectItem {
    id: number;
    name: string;
    employee_id: string;
}

interface LeaveRecord {
    id: number;
    type: string;
    leave_type: string;
    leave_type_code: string;
    status: string;
    reason: string;
    days: number;
    start_date: string;
    end_date: string;
    created_at: string;
}

interface Summary {
    totalLeaves: number;
    totalDays: number;
    approvedDays: number;
    pendingCount: number;
    rejectedCount: number;
}

interface Props {
    employee: Employee;
    employees: EmployeeSelectItem[];
    leaves: LeaveRecord[];
    summary: Summary;
    filters: {
        month: number;
        year: number;
    };
    availableYears: number[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Leave Report', href: '/reports/leave' },
    { title: 'Employee Report', href: '/reports/leave/employees' },
    { title: props.employee.name, href: '#' },
];

const months = [
    { value: 1, label: 'January' },
    { value: 2, label: 'February' },
    { value: 3, label: 'March' },
    { value: 4, label: 'April' },
    { value: 5, label: 'May' },
    { value: 6, label: 'June' },
    { value: 7, label: 'July' },
    { value: 8, label: 'August' },
    { value: 9, label: 'September' },
    { value: 10, label: 'October' },
    { value: 11, label: 'November' },
    { value: 12, label: 'December' },
];

const selectedMonth = ref(String(props.filters.month));
const selectedYear = ref(String(props.filters.year));

const applyFilters = () => {
    router.get(`/reports/leave/employees/${props.employee.id}`, {
        month: selectedMonth.value,
        year: selectedYear.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

watch([selectedMonth, selectedYear], () => applyFilters());

const selectedEmployeeId = ref(String(props.employee.id));

const changeEmployee = (employeeId: string | number | boolean | Record<string, any> | null) => {
    if (employeeId) {
        router.visit(`/reports/leave/employees/${employeeId}?month=${selectedMonth.value}&year=${selectedYear.value}`);
    }
};

const selectedMonthName = computed(() => {
    const month = months.find(m => m.value === Number(selectedMonth.value));
    return month?.label || '';
});

const formatDate = (dateString: string) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
    });
};

const getStatusBadgeClass = (status: string) => {
    switch (status) {
        case 'approved':
            return 'bg-green-50 text-green-700 border-green-200';
        case 'pending':
            return 'bg-yellow-50 text-yellow-700 border-yellow-200';
        case 'rejected':
            return 'bg-red-50 text-red-700 border-red-200';
        case 'cancelled':
            return 'bg-gray-50 text-gray-700 border-gray-200';
        default:
            return '';
    }
};

const getTypeBadgeClass = (type: string) => {
    return type === 'advance' 
        ? 'bg-blue-50 text-blue-700 border-blue-200'
        : 'bg-purple-50 text-purple-700 border-purple-200';
};
</script>

<template>
    <Head :title="`${employee.name} - Leave Report`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link href="/reports/leave/employees">
                        <Button variant="ghost" size="icon">
                            <ArrowLeft class="h-4 w-4" />
                        </Button>
                    </Link>
                    <h1 class="text-2xl font-bold">Leave Details</h1>
                </div>
                <div class="flex flex-wrap gap-2 items-center">
                    <Select v-model="selectedEmployeeId" @update:model-value="changeEmployee">
                        <SelectTrigger class="w-[200px]">
                            <SelectValue placeholder="Select Employee" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="emp in employees"
                                :key="emp.id"
                                :value="String(emp.id)"
                            >
                                {{ emp.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="selectedMonth" @update:model-value="applyFilters">
                        <SelectTrigger class="w-[140px]">
                            <SelectValue placeholder="Select month" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem 
                                v-for="month in months" 
                                :key="month.value" 
                                :value="String(month.value)"
                            >
                                {{ month.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="selectedYear" @update:model-value="applyFilters">
                        <SelectTrigger class="w-[100px]">
                            <SelectValue placeholder="Select year" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem 
                                v-for="year in availableYears" 
                                :key="year" 
                                :value="String(year)"
                            >
                                {{ year }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Employee Info with Stats Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 bg-card border rounded-lg">
                <div class="flex items-center gap-3">
                    <UserIcon class="h-8 w-8 text-muted-foreground" />
                    <div>
                        <h2 class="text-lg font-semibold">{{ employee.name }}</h2>
                        <p class="text-sm text-muted-foreground">
                            {{ employee.employee_id }}
                            <span v-if="employee.designation"> · {{ employee.designation }}</span>
                            <span v-if="employee.department_name"> · {{ employee.department_name }}</span>
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-4 text-sm">
                    <div class="flex items-center gap-2">
                        <span class="text-muted-foreground">Total:</span>
                        <span class="font-semibold">{{ summary.totalLeaves }} leaves ({{ summary.totalDays }} days)</span>
                    </div>
                    <div class="h-4 w-px bg-border hidden sm:block"></div>
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        <span class="text-green-600 font-medium">{{ summary.approvedDays }}</span>
                        <span class="text-muted-foreground">approved</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                        <span class="text-yellow-600 font-medium">{{ summary.pendingCount }}</span>
                        <span class="text-muted-foreground">pending</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 rounded-full bg-red-500"></div>
                        <span class="text-red-600 font-medium">{{ summary.rejectedCount }}</span>
                        <span class="text-muted-foreground">rejected</span>
                    </div>
                </div>
            </div>

            <!-- Leave Records Table -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Calendar class="h-5 w-5" />
                        Leave Records - {{ selectedMonthName }} {{ selectedYear }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="pb-3 text-left font-medium">Leave Type</th>
                                    <th class="pb-3 text-left font-medium">Type</th>
                                    <th class="pb-3 text-left font-medium">Period</th>
                                    <th class="pb-3 text-center font-medium">Days</th>
                                    <th class="pb-3 text-left font-medium">Reason</th>
                                    <th class="pb-3 text-left font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="record in leaves"
                                    :key="record.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-3">
                                        <div class="flex items-center gap-2">
                                            <CalendarDays class="h-4 w-4 text-muted-foreground" />
                                            <span class="font-medium">{{ record.leave_type }}</span>
                                            <span class="text-xs text-muted-foreground">({{ record.leave_type_code }})</span>
                                        </div>
                                    </td>
                                    <td class="py-3">
                                        <Badge variant="outline" :class="getTypeBadgeClass(record.type)">
                                            {{ record.type === 'advance' ? 'Advance' : 'Post' }}
                                        </Badge>
                                    </td>
                                    <td class="py-3">
                                        <span class="text-sm">{{ formatDate(record.start_date) }}</span>
                                        <span v-if="record.start_date !== record.end_date" class="text-muted-foreground">
                                            → {{ formatDate(record.end_date) }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-center font-medium">{{ record.days }}</td>
                                    <td class="py-3">
                                        <span class="text-sm text-muted-foreground line-clamp-1" :title="record.reason">
                                            {{ record.reason || '-' }}
                                        </span>
                                    </td>
                                    <td class="py-3">
                                        <Badge variant="outline" :class="getStatusBadgeClass(record.status)">
                                            {{ record.status.charAt(0).toUpperCase() + record.status.slice(1) }}
                                        </Badge>
                                    </td>
                                </tr>
                                <tr v-if="leaves.length === 0">
                                    <td colspan="6" class="py-8 text-center text-muted-foreground">
                                        No leave records found for this period.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
