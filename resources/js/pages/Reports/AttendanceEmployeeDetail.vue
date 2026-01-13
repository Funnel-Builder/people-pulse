<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { BreadcrumbItem } from '@/types';
import { Head, router, Link } from '@inertiajs/vue3';
import { 
    User as UserIcon, Calendar, Clock, 
    Download, FileText, ArrowLeft
} from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

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

interface AttendanceRecord {
    id: number;
    date: string;
    clock_in: string | null;
    clock_out: string | null;
    is_late: boolean;
    net_minutes: number | null;
    gross_minutes: number | null;
    break_minutes: number;
    status: string;
    late_minutes: number | null;
}

interface Summary {
    totalAttendance: number;
    lateCount: number;
    onTimeCount: number;
    totalHours: number;
    avgHoursPerDay: number;
}

interface Props {
    employee: Employee;
    employees: EmployeeSelectItem[];
    attendances: AttendanceRecord[];
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
    { title: 'Attendance Report', href: '/reports/attendance' },
    { title: 'Employee Report', href: '/reports/attendance/employees' },
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
    router.get(`/reports/attendance/employees/${props.employee.id}`, {
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
        router.visit(`/reports/attendance/employees/${employeeId}?month=${selectedMonth.value}&year=${selectedYear.value}`);
    }
};

const exportReport = (type: 'csv' | 'xlsx') => {
    const params = new URLSearchParams();
    params.append('month', selectedMonth.value);
    params.append('year', selectedYear.value);
    params.append('type', type);
    
    window.location.href = `/reports/attendance/employees/${props.employee.id}/export?${params.toString()}`;
};

const selectedMonthName = computed(() => {
    const month = months.find(m => m.value === Number(selectedMonth.value));
    return month?.label || '';
});

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
    });
};

const formatTime = (dateString: string | null) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatMinutesToHours = (minutes: number | null) => {
    if (minutes === null) return '-';
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours}h ${mins}m`;
};
</script>

<template>
    <Head :title="`${employee.name} - Attendance Report`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link href="/reports/attendance/employees">
                        <Button variant="ghost" size="icon">
                            <ArrowLeft class="h-4 w-4" />
                        </Button>
                    </Link>
                    <h1 class="text-2xl font-bold">Attendance Details</h1>
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
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button>
                                <Download class="mr-2 h-4 w-4" />
                                Export
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent>
                            <DropdownMenuItem @click="exportReport('csv')">
                                <FileText class="mr-2 h-4 w-4" />
                                Export as CSV
                            </DropdownMenuItem>
                            <DropdownMenuItem @click="exportReport('xlsx')">
                                <FileText class="mr-2 h-4 w-4" />
                                Export as XLSX
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
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
                        <span class="text-muted-foreground">Attendance:</span>
                        <span class="font-semibold">{{ summary.totalAttendance }} days</span>
                    </div>
                    <div class="h-4 w-px bg-border hidden sm:block"></div>
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        <span class="text-green-600 font-medium">{{ summary.onTimeCount }}</span>
                        <span class="text-muted-foreground">on time</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                        <span class="text-yellow-600 font-medium">{{ summary.lateCount }}</span>
                        <span class="text-muted-foreground">late</span>
                    </div>
                    <div class="h-4 w-px bg-border hidden sm:block"></div>
                    <div class="flex items-center gap-1">
                        <Clock class="h-4 w-4 text-blue-500" />
                        <span class="text-blue-600 font-medium">{{ summary.totalHours.toFixed(1) }}h</span>
                        <span class="text-muted-foreground">({{ summary.avgHoursPerDay.toFixed(1) }}h/day)</span>
                    </div>
                </div>
            </div>

            <!-- Attendance Records Table -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Calendar class="h-5 w-5" />
                        Attendance Records - {{ selectedMonthName }} {{ selectedYear }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="pb-3 text-left font-medium">Date</th>
                                    <th class="pb-3 text-left font-medium">Clock In</th>
                                    <th class="pb-3 text-left font-medium">Clock Out</th>
                                    <th class="pb-3 text-left font-medium">Net Hours</th>
                                    <th class="pb-3 text-left font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="record in attendances"
                                    :key="record.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-3 font-medium">{{ formatDate(record.date) }}</td>
                                    <td class="py-3">{{ formatTime(record.clock_in) }}</td>
                                    <td class="py-3">{{ formatTime(record.clock_out) }}</td>
                                    <td class="py-3">{{ formatMinutesToHours(record.net_minutes) }}</td>
                                    <td class="py-3">
                                        <Badge v-if="record.is_late" variant="destructive">Late</Badge>
                                        <Badge v-else variant="outline" class="bg-green-50 text-green-700 border-green-200">On Time</Badge>
                                    </td>
                                </tr>
                                <tr v-if="attendances.length === 0">
                                    <td colspan="5" class="py-8 text-center text-muted-foreground">
                                        No attendance records found for this period.
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
