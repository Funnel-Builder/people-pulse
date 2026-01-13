<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { CalendarDays } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';

interface Employee {
    id: number;
    name: string;
    employee_id: string;
}

interface EmployeeSummary {
    id: number;
    employee_id: string;
    name: string;
    department_name: string | null;
    sub_department_name: string | null;
    designation: string | null;
    total_leaves: number;
    total_days: number;
    approved_days: number;
    pending_count: number;
    rejected_count: number;
}

interface Props {
    employees: Employee[];
    employeeSummaries: EmployeeSummary[];
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
    const params: Record<string, string> = {
        month: selectedMonth.value,
        year: selectedYear.value,
    };
    router.get('/reports/leave/employees', params, {
        preserveState: true,
        preserveScroll: true,
    });
};

watch([selectedMonth, selectedYear], () => applyFilters());

const selectEmployee = (employeeId: number) => {
    router.visit(`/reports/leave/employees/${employeeId}?month=${selectedMonth.value}&year=${selectedYear.value}`);
};

const selectedMonthName = computed(() => {
    const month = months.find(m => m.value === Number(selectedMonth.value));
    return month?.label || '';
});
</script>

<template>
    <Head title="Leave Employee Report" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <h1 class="text-2xl font-bold">Employee Leave Report</h1>
                <div class="flex flex-wrap gap-2 items-center">
                    <Select @update:model-value="(val) => selectEmployee(Number(val))">
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

            <!-- Employee List View -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <CalendarDays class="h-5 w-5" />
                        Employee Leaves - {{ selectedMonthName }} {{ selectedYear }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="pb-3 text-left font-medium">Employee</th>
                                    <th class="pb-3 text-left font-medium">Department</th>
                                    <th class="pb-3 text-center font-medium">Total Leaves</th>
                                    <th class="pb-3 text-center font-medium">Total Days</th>
                                    <th class="pb-3 text-center font-medium">Approved Days</th>
                                    <th class="pb-3 text-center font-medium">Pending</th>
                                    <th class="pb-3 text-center font-medium">Rejected</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="employee in employeeSummaries"
                                    :key="employee.id"
                                    class="border-b last:border-0 cursor-pointer hover:bg-muted/50 transition-colors"
                                    @click="selectEmployee(employee.id)"
                                >
                                    <td class="py-3">
                                        <div>
                                            <div class="font-medium">{{ employee.name }}</div>
                                            <div class="text-sm text-muted-foreground">{{ employee.employee_id }}</div>
                                        </div>
                                    </td>
                                    <td class="py-3">
                                        <div v-if="employee.department_name" class="text-sm">{{ employee.department_name }}</div>
                                        <div v-if="employee.sub_department_name" class="text-xs text-muted-foreground">
                                            {{ employee.sub_department_name }}
                                        </div>
                                    </td>
                                    <td class="py-3 text-center">
                                        <Badge variant="outline">{{ employee.total_leaves }}</Badge>
                                    </td>
                                    <td class="py-3 text-center">
                                        <span class="font-medium">{{ employee.total_days }}</span>
                                    </td>
                                    <td class="py-3 text-center">
                                        <Badge variant="outline" class="bg-green-50 text-green-700 border-green-200">
                                            {{ employee.approved_days }}
                                        </Badge>
                                    </td>
                                    <td class="py-3 text-center">
                                        <Badge v-if="employee.pending_count > 0" variant="outline" class="bg-yellow-50 text-yellow-700 border-yellow-200">
                                            {{ employee.pending_count }}
                                        </Badge>
                                        <Badge v-else variant="outline" class="text-muted-foreground">0</Badge>
                                    </td>
                                    <td class="py-3 text-center">
                                        <Badge v-if="employee.rejected_count > 0" variant="destructive">
                                            {{ employee.rejected_count }}
                                        </Badge>
                                        <Badge v-else variant="outline" class="text-muted-foreground">0</Badge>
                                    </td>
                                </tr>
                                <tr v-if="employeeSummaries.length === 0">
                                    <td colspan="7" class="py-8 text-center text-muted-foreground">
                                        No employees found.
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
