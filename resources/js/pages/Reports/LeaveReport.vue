<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    CalendarDays, FileText, PieChart, Activity, Clock,
    CheckCircle, XCircle, Users
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface LeaveByType {
    name: string;
    code: string;
    count: number;
}

interface MonthlyTrend {
    month: string;
    year: string;
    approved: number;
    pending: number;
}

interface DepartmentDistribution {
    name: string;
    total_employees: number;
    total_leaves: number;
    approved: number;
}

interface StatusBreakdown {
    approved: number;
    pending: number;
    rejected: number;
    total: number;
}

interface TopLeaveTaker {
    user_id: number;
    leave_count: number;
    user: {
        id: number;
        name: string;
        employee_id: string;
    };
}

interface PendingApproval {
    id: number;
    user: {
        id: number;
        name: string;
        employee_id: string;
    };
    leave_type: string;
    days: number;
    start_date: string;
    created_at: string;
}

interface Stats {
    total_leaves: number;
    approved_leaves: number;
    pending_leaves: number;
    rejected_leaves: number;
    total_leave_days: number;
}

interface Charts {
    leave_by_type: LeaveByType[];
    monthly_trend: MonthlyTrend[];
    department_distribution: DepartmentDistribution[];
    status_breakdown: StatusBreakdown;
}

interface Lists {
    top_leave_takers: TopLeaveTaker[];
    pending_approvals: PendingApproval[];
}

interface Filters {
    month: number;
    year: number;
}

interface Props {
    stats: Stats;
    charts: Charts;
    lists: Lists;
    filters: Filters;
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Leave Report', href: '/reports/leave' },
];

// Month/Year filter state
const selectedMonth = ref(String(props.filters.month));
const selectedYear = ref(String(props.filters.year));

const months = [
    { value: '1', label: 'January' },
    { value: '2', label: 'February' },
    { value: '3', label: 'March' },
    { value: '4', label: 'April' },
    { value: '5', label: 'May' },
    { value: '6', label: 'June' },
    { value: '7', label: 'July' },
    { value: '8', label: 'August' },
    { value: '9', label: 'September' },
    { value: '10', label: 'October' },
    { value: '11', label: 'November' },
    { value: '12', label: 'December' },
];

const currentYear = new Date().getFullYear();
const years = Array.from({ length: 5 }, (_, i) => ({
    value: String(currentYear - i),
    label: String(currentYear - i),
}));

const applyFilters = () => {
    router.get('/reports/leave', {
        month: selectedMonth.value,
        year: selectedYear.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

watch([selectedMonth, selectedYear], () => {
    applyFilters();
});

const selectedMonthLabel = computed(() => {
    const month = months.find(m => m.value === selectedMonth.value);
    return month ? `${month.label} ${selectedYear.value}` : '';
});

// Chart colors
const typeColors = ['#22c55e', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];

// Status breakdown percentages
const approvedPercentage = computed(() => {
    const total = props.charts.status_breakdown.total;
    if (total === 0) return 0;
    return Math.round((props.charts.status_breakdown.approved / total) * 100);
});

const pendingPercentage = computed(() => {
    const total = props.charts.status_breakdown.total;
    if (total === 0) return 0;
    return Math.round((props.charts.status_breakdown.pending / total) * 100);
});

const rejectedPercentage = computed(() => {
    const total = props.charts.status_breakdown.total;
    if (total === 0) return 0;
    return Math.round((props.charts.status_breakdown.rejected / total) * 100);
});

// Max values for bar charts
const maxMonthlyValue = computed(() => {
    return Math.max(...props.charts.monthly_trend.map(m => m.approved + m.pending), 1);
});
</script>

<template>
    <Head title="Leave Report" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold">Leave Report</h1>
                </div>
                <div class="flex flex-wrap gap-2 items-center">
                    <Select v-model="selectedMonth">
                        <SelectTrigger class="w-[140px]">
                            <SelectValue placeholder="Select month" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="m in months" :key="m.value" :value="m.value">
                                {{ m.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="selectedYear">
                        <SelectTrigger class="w-[100px]">
                            <SelectValue placeholder="Select year" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="y in years" :key="y.value" :value="y.value">
                                {{ y.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Link href="/reports/leave/employees">
                        <Button variant="outline">
                            <FileText class="mr-2 h-4 w-4" />
                            Employee Reports
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardDescription>Total Leaves</CardDescription>
                        <CalendarDays class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">{{ stats.total_leaves }}</div>
                        <p class="text-xs text-muted-foreground mt-1">{{ stats.total_leave_days }} total days</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardDescription>Approved</CardDescription>
                        <CheckCircle class="h-4 w-4 text-green-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-green-600">{{ stats.approved_leaves }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardDescription>Pending</CardDescription>
                        <Clock class="h-4 w-4 text-yellow-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-yellow-600">{{ stats.pending_leaves }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardDescription>Rejected</CardDescription>
                        <XCircle class="h-4 w-4 text-red-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-red-600">{{ stats.rejected_leaves }}</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Charts Row -->
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Monthly Trend -->
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Activity class="h-5 w-5" />
                            Monthly Leave Trend
                        </CardTitle>
                        <CardDescription>Last 6 months comparison</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="item in charts.monthly_trend"
                                :key="`${item.month}-${item.year}`"
                                class="flex items-center gap-3"
                            >
                                <div class="w-16 text-xs text-muted-foreground">
                                    {{ item.month }} '{{ item.year.slice(-2) }}
                                </div>
                                <div class="flex-1 flex gap-0.5 h-6 items-center">
                                    <div
                                        class="bg-green-500 rounded-l transition-all duration-300 h-full"
                                        :style="{ width: `${maxMonthlyValue > 0 ? (item.approved / maxMonthlyValue) * 100 : 0}%` }"
                                        :title="`Approved: ${item.approved}`"
                                    ></div>
                                    <div
                                        class="bg-yellow-500 transition-all duration-300 h-full"
                                        :class="item.pending > 0 ? 'rounded-r' : ''"
                                        :style="{ width: `${maxMonthlyValue > 0 ? (item.pending / maxMonthlyValue) * 100 : 0}%` }"
                                        :title="`Pending: ${item.pending}`"
                                    ></div>
                                </div>
                                <div class="w-20 text-xs text-right">
                                    <span class="text-green-600 font-medium">{{ item.approved }}</span>
                                    <span class="text-muted-foreground"> / </span>
                                    <span class="text-yellow-600">{{ item.pending }}</span>
                                </div>
                            </div>
                            <div v-if="charts.monthly_trend.length === 0" class="text-center text-muted-foreground py-8">
                                No leave data for this period.
                            </div>
                        </div>
                        <div class="flex gap-6 text-xs pt-4 border-t mt-4 justify-center">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-green-500 rounded"></div>
                                <span>Approved</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-yellow-500 rounded"></div>
                                <span>Pending</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Status Breakdown Donut -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <PieChart class="h-5 w-5" />
                            Status Breakdown
                        </CardTitle>
                        <CardDescription>{{ selectedMonthLabel }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-col items-center">
                            <!-- SVG Donut Chart -->
                            <div class="relative w-40 h-40">
                                <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90">
                                    <!-- Background circle -->
                                    <circle
                                        cx="50" cy="50" r="40"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="12"
                                        class="text-secondary"
                                    />
                                    <!-- Approved segment -->
                                    <circle
                                        cx="50" cy="50" r="40"
                                        fill="none"
                                        stroke="#22c55e"
                                        stroke-width="12"
                                        :stroke-dasharray="`${approvedPercentage * 2.51} 251`"
                                        stroke-linecap="round"
                                        class="transition-all duration-700"
                                    />
                                    <!-- Pending segment -->
                                    <circle
                                        cx="50" cy="50" r="40"
                                        fill="none"
                                        stroke="#eab308"
                                        stroke-width="12"
                                        :stroke-dasharray="`${pendingPercentage * 2.51} 251`"
                                        :stroke-dashoffset="`${-approvedPercentage * 2.51}`"
                                        stroke-linecap="round"
                                        class="transition-all duration-700"
                                    />
                                    <!-- Rejected segment -->
                                    <circle
                                        cx="50" cy="50" r="40"
                                        fill="none"
                                        stroke="#ef4444"
                                        stroke-width="12"
                                        :stroke-dasharray="`${rejectedPercentage * 2.51} 251`"
                                        :stroke-dashoffset="`${-(approvedPercentage + pendingPercentage) * 2.51}`"
                                        stroke-linecap="round"
                                        class="transition-all duration-700"
                                    />
                                </svg>
                                <!-- Center text -->
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-2xl font-bold">{{ charts.status_breakdown.total }}</span>
                                    <span class="text-xs text-muted-foreground">Total</span>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-4 mt-4 text-sm justify-center">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                    <span>{{ charts.status_breakdown.approved }} Approved</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                    <span>{{ charts.status_breakdown.pending }} Pending</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                    <span>{{ charts.status_breakdown.rejected }} Rejected</span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Leave by Type -->
            <Card v-if="charts.leave_by_type.length > 0">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <CalendarDays class="h-5 w-5" />
                        Leave by Type - {{ selectedMonthLabel }}
                    </CardTitle>
                    <CardDescription>Approved leaves breakdown by type</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-3 lg:grid-cols-4">
                        <div
                            v-for="(type, index) in charts.leave_by_type"
                            :key="type.code"
                            class="p-4 rounded-lg border bg-card"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="font-medium">{{ type.name }}</h4>
                                <span class="text-xs px-2 py-1 rounded-full bg-muted">{{ type.code }}</span>
                            </div>
                            <div class="text-3xl font-bold" :style="{ color: typeColors[index % typeColors.length] }">
                                {{ type.count }}
                            </div>
                            <p class="text-xs text-muted-foreground mt-1">leaves approved</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Department Distribution -->
            <Card v-if="charts.department_distribution.length > 0">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Users class="h-5 w-5" />
                        Department-wise Leaves - {{ selectedMonthLabel }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="dept in charts.department_distribution"
                            :key="dept.name"
                            class="p-4 rounded-lg border bg-card"
                        >
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-medium">{{ dept.name }}</h4>
                                <span class="text-sm text-muted-foreground">{{ dept.total_employees }} employees</span>
                            </div>
                            <div class="flex gap-4 text-sm">
                                <div>
                                    <span class="text-2xl font-bold">{{ dept.total_leaves }}</span>
                                    <p class="text-xs text-muted-foreground">Total</p>
                                </div>
                                <div>
                                    <span class="text-2xl font-bold text-green-600">{{ dept.approved }}</span>
                                    <p class="text-xs text-muted-foreground">Approved</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Top Leave Takers and Pending Approvals -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Top Leave Takers -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <CalendarDays class="h-5 w-5 text-blue-500" />
                            Top Leave Takers - {{ selectedMonthLabel }}
                        </CardTitle>
                        <CardDescription>Employees with most approved leaves</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="(employee, index) in lists.top_leave_takers"
                                :key="employee.user_id"
                                class="flex items-center justify-between p-3 rounded-lg bg-blue-50 dark:bg-blue-950/30"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 font-semibold text-sm">
                                        {{ index + 1 }}
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ employee.user?.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ employee.user?.employee_id }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-lg font-bold text-blue-600">{{ employee.leave_count }}</span>
                                    <p class="text-xs text-muted-foreground">leaves</p>
                                </div>
                            </div>
                            <div v-if="lists.top_leave_takers.length === 0" class="text-center text-muted-foreground py-8">
                                No leave data available.
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Pending Approvals -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Clock class="h-5 w-5 text-yellow-500" />
                            Pending Approvals
                        </CardTitle>
                        <CardDescription>Recent leave applications awaiting approval</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="leave in lists.pending_approvals"
                                :key="leave.id"
                                class="flex items-center justify-between p-3 rounded-lg bg-yellow-50 dark:bg-yellow-950/30"
                            >
                                <div>
                                    <p class="font-medium">{{ leave.user?.name }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ leave.leave_type }} · {{ leave.days }} day(s) · {{ leave.start_date }}
                                    </p>
                                </div>
                                <Link :href="`/leaves/${leave.id}`">
                                    <Button variant="outline" size="sm">View</Button>
                                </Link>
                            </div>
                            <div v-if="lists.pending_approvals.length === 0" class="text-center text-muted-foreground py-8">
                                No pending approvals! 🎉
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
