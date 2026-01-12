<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import type { Attendance, BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { CalendarDays, List } from 'lucide-vue-next';
import DataTable from '@/components/ui/DataTable.vue';

interface Props {
    attendances: Attendance[];
    filters: {
        month: number;
        year: number;
    };
    availableYears: number[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'My Attendance', href: '/attendance' },
];

// View mode toggle - default to calendar
const viewMode = ref<'calendar' | 'table'>('calendar');

const selectedMonth = ref(props.filters.month.toString());
const selectedYear = ref(props.filters.year.toString());

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

const selectedMonthLabel = computed(() => {
    const month = months.find(m => m.value === selectedMonth.value);
    return month?.label || '';
});

const applyFilters = () => {
    router.get('/attendance', {
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

// Calendar logic
const today = new Date();
const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
const selectedDate = ref<string | null>(todayStr);

const daysInMonth = computed(() => {
    return new Date(parseInt(selectedYear.value), parseInt(selectedMonth.value), 0).getDate();
});

const firstDayOfMonth = computed(() => {
    return new Date(parseInt(selectedYear.value), parseInt(selectedMonth.value) - 1, 1).getDay();
});

const calendarDays = computed(() => {
    const days: { date: string; day: number; attendance: Attendance | undefined }[] = [];
    for (let i = 1; i <= daysInMonth.value; i++) {
        const dateStr = `${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
        const attendance = props.attendances.find(a => a.date === dateStr);
        days.push({ date: dateStr, day: i, attendance });
    }
    return days;
});

const selectedAttendance = computed(() => {
    if (!selectedDate.value) return null;
    return props.attendances.find(a => a.date === selectedDate.value) || null;
});

const selectDay = (date: string) => {
    selectedDate.value = selectedDate.value === date ? null : date;
};

const getAttendanceColor = (attendance: Attendance | undefined) => {
    if (!attendance) return 'bg-gray-200 dark:bg-gray-700';
    if (attendance.status === 'weekend') return 'bg-blue-500';
    if (attendance.status === 'sick_leave' || attendance.status === 'casual_leave') return 'bg-purple-500';
    if (attendance.status === 'absent') return 'bg-red-500';
    if (!attendance.clock_in) return 'bg-gray-300 dark:bg-gray-600';
    if (attendance.is_late) return 'bg-yellow-500';
    return 'bg-green-500';
};

const getAttendanceBgClass = (attendance: Attendance | undefined) => {
    if (!attendance) return '';
    if (attendance.status === 'weekend') return 'bg-blue-50 dark:bg-blue-950/30';
    if (attendance.status === 'sick_leave' || attendance.status === 'casual_leave') return 'bg-purple-50 dark:bg-purple-950/30';
    if (attendance.status === 'absent') return 'bg-red-50 dark:bg-red-950/30';
    if (!attendance.clock_in) return '';
    if (attendance.is_late) return 'bg-yellow-50 dark:bg-yellow-950/30';
    return 'bg-green-50 dark:bg-green-950/30';
};

// Helper functions
const formatTime = (dateString: string | null) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
    });
};

const formatFullDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
};

const formatMinutesToHours = (minutes: number | null) => {
    if (minutes === null) return '-';
    if (minutes === 0) return '0h';
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    if (hours === 0) return `${mins}m`;
    return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`;
};

const getStatusInfo = (attendance: Attendance) => {
    switch (attendance.status) {
        case 'present':
            return {
                label: 'Present',
                variant: 'default' as const,
                class: 'bg-green-600 hover:bg-green-700'
            };
        case 'absent':
            return {
                label: 'Absent',
                variant: 'destructive' as const,
                class: ''
            };
        case 'weekend':
            return {
                label: 'Weekend',
                variant: 'secondary' as const,
                class: 'bg-blue-600 hover:bg-blue-700 text-white'
            };
        case 'sick_leave':
            return {
                label: 'Sick Leave',
                variant: 'outline' as const,
                class: 'bg-purple-100 text-purple-800 border-purple-300 hover:bg-purple-200'
            };
        case 'casual_leave':
            return {
                label: 'Casual Leave',
                variant: 'outline' as const,
                class: 'bg-purple-100 text-purple-800 border-purple-300 hover:bg-purple-200'
            };
        default:
            return {
                label: 'Unknown',
                variant: 'outline' as const,
                class: ''
            };
    }
};

const columns = [
    { key: 'date', label: 'Date', class: 'w-[120px]' },
    { key: 'clock_in', label: 'Clock In' },
    { key: 'clock_out', label: 'Clock Out' },
    { key: 'status', label: 'Status' },
    { key: 'late_minutes', label: 'Late Time', class: 'hidden md:table-cell' },
    { key: 'early_exit_minutes', label: 'Early Exit', class: 'hidden md:table-cell' },
    { key: 'net_minutes', label: 'Net Hours' },
    { key: 'is_late', label: 'Late Status', class: 'hidden md:table-cell' },
];
</script>

<template>
    <Head title="My Attendance" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <!-- Header with Filters and Toggle -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold">My Attendance</h1>
                </div>
                <div class="flex items-center gap-2">
                    <!-- View Toggle -->
                    <TooltipProvider>
                        <div class="flex rounded-lg border bg-muted p-1">
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button
                                        size="sm"
                                        :variant="viewMode === 'calendar' ? 'default' : 'ghost'"
                                        class="h-8 w-8 p-0"
                                        @click="viewMode = 'calendar'"
                                    >
                                        <CalendarDays class="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>Calendar</p>
                                </TooltipContent>
                            </Tooltip>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button
                                        size="sm"
                                        :variant="viewMode === 'table' ? 'default' : 'ghost'"
                                        class="h-8 w-8 p-0"
                                        @click="viewMode = 'table'"
                                    >
                                        <List class="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>Table</p>
                                </TooltipContent>
                            </Tooltip>
                        </div>
                    </TooltipProvider>
                    <Select v-model="selectedMonth">
                        <SelectTrigger class="w-[140px]">
                            <SelectValue :placeholder="selectedMonthLabel" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="month in months" :key="month.value" :value="month.value">
                                {{ month.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="selectedYear">
                        <SelectTrigger class="w-[100px]">
                            <SelectValue :placeholder="selectedYear" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="year in availableYears" :key="year" :value="year.toString()">
                                {{ year }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Calendar View -->
            <template v-if="viewMode === 'calendar'">
                <div class="grid gap-6 lg:grid-cols-3">
                    <!-- Calendar Grid -->
                    <Card class="lg:col-span-2">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <CalendarDays class="h-5 w-5" />
                                {{ selectedMonthLabel }} {{ selectedYear }} Attendance
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <!-- Day Headers -->
                            <div class="grid grid-cols-7 gap-1 text-center text-xs mb-2">
                                <div class="font-semibold text-muted-foreground py-2">Sun</div>
                                <div class="font-semibold text-muted-foreground py-2">Mon</div>
                                <div class="font-semibold text-muted-foreground py-2">Tue</div>
                                <div class="font-semibold text-muted-foreground py-2">Wed</div>
                                <div class="font-semibold text-muted-foreground py-2">Thu</div>
                                <div class="font-semibold text-muted-foreground py-2">Fri</div>
                                <div class="font-semibold text-muted-foreground py-2">Sat</div>
                            </div>
                            <!-- Calendar Grid -->
                            <div class="grid grid-cols-7 gap-1">
                                <!-- Empty cells for first week offset -->
                                <div v-for="i in firstDayOfMonth" :key="'empty-' + i" class="h-14"></div>
                                <!-- Calendar days -->
                                <div
                                    v-for="day in calendarDays"
                                    :key="day.date"
                                    @click="selectDay(day.date)"
                                    :class="[
                                        'h-14 p-1 rounded-lg border cursor-pointer transition-all relative',
                                        selectedDate === day.date ? 'border-primary ring-2 ring-primary/20' : 'hover:border-primary/50',
                                        getAttendanceBgClass(day.attendance),
                                    ]"
                                >
                                    <span class="text-sm font-medium">{{ day.day }}</span>
                                    <div class="absolute bottom-1.5 left-1/2 -translate-x-1/2">
                                        <div
                                            :class="['w-2.5 h-2.5 rounded-full', getAttendanceColor(day.attendance)]"
                                        ></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Legend -->
                            <div class="flex flex-wrap gap-4 mt-6 pt-4 border-t text-xs">
                                <div class="flex items-center gap-1.5">
                                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                                    <span>On Time</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                                    <span>Late</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                    <span>Absent</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                                    <span>Weekend</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <div class="w-3 h-3 rounded-full bg-purple-500"></div>
                                    <span>Leave</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <div class="w-3 h-3 rounded-full bg-gray-300 dark:bg-gray-600"></div>
                                    <span>No Record</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Selected Date Details -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">
                                {{ selectedDate ? formatFullDate(selectedDate) : 'Select a Date' }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="selectedAttendance" class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <p class="text-xs text-muted-foreground uppercase tracking-wide">Clock In</p>
                                        <p class="text-lg font-semibold" :class="selectedAttendance.is_late ? 'text-destructive' : ''">
                                            {{ formatTime(selectedAttendance.clock_in) }}
                                        </p>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-xs text-muted-foreground uppercase tracking-wide">Clock Out</p>
                                        <p class="text-lg font-semibold">
                                            {{ formatTime(selectedAttendance.clock_out) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <p class="text-xs text-muted-foreground uppercase tracking-wide">Net Hours</p>
                                        <p class="text-lg font-semibold">
                                            {{ formatMinutesToHours(selectedAttendance.net_minutes) }}
                                        </p>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-xs text-muted-foreground uppercase tracking-wide">Status</p>
                                        <Badge
                                            :variant="getStatusInfo(selectedAttendance).variant"
                                            :class="getStatusInfo(selectedAttendance).class"
                                        >
                                            {{ getStatusInfo(selectedAttendance).label }}
                                        </Badge>
                                    </div>
                                </div>
                                <div v-if="selectedAttendance.late_minutes && selectedAttendance.late_minutes > 0" class="pt-2 border-t">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-muted-foreground">Late by</span>
                                        <span class="text-sm font-medium text-destructive">
                                            {{ formatMinutesToHours(selectedAttendance.late_minutes) }}
                                        </span>
                                    </div>
                                </div>
                                <div v-if="selectedAttendance.early_exit_minutes && selectedAttendance.early_exit_minutes > 0" class="pt-2 border-t">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-muted-foreground">Left early by</span>
                                        <span class="text-sm font-medium text-orange-600">
                                            {{ formatMinutesToHours(selectedAttendance.early_exit_minutes) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div v-else-if="selectedDate" class="text-center py-8 text-muted-foreground">
                                <p>No attendance record for this date.</p>
                            </div>
                            <div v-else class="text-center py-8 text-muted-foreground">
                                <CalendarDays class="h-12 w-12 mx-auto mb-3 opacity-30" />
                                <p>Click on a date to view attendance details.</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </template>

            <!-- Table View -->
            <Card v-else>
                <CardHeader>
                    <CardTitle>{{ selectedMonthLabel }} {{ selectedYear }} Attendance</CardTitle>
                </CardHeader>
                <CardContent>
                    <DataTable :columns="columns" :data="attendances">
                        <template #cell-date="{ row }">
                            <span class="font-medium">{{ formatDate(row.date) }}</span>
                        </template>

                        <template #cell-clock_in="{ row }">
                            <span :class="row.is_late ? 'text-destructive font-medium' : ''">
                                {{ formatTime(row.clock_in) }}
                            </span>
                        </template>

                        <template #cell-clock_out="{ row }">
                            <span :class="row.early_exit_minutes > 0 ? 'text-orange-600 font-medium' : ''">
                                {{ formatTime(row.clock_out) }}
                            </span>
                        </template>

                        <template #cell-status="{ row }">
                            <Badge
                                :variant="getStatusInfo(row).variant"
                                :class="getStatusInfo(row).class"
                                class="text-xs font-semibold px-2.5 py-1"
                            >
                                {{ getStatusInfo(row).label }}
                            </Badge>
                        </template>

                        <template #cell-late_minutes="{ row }">
                            <span v-if="row.late_minutes > 0" class="text-destructive font-medium">
                                {{ formatMinutesToHours(row.late_minutes) }}
                            </span>
                            <span v-else class="text-muted-foreground">-</span>
                        </template>

                        <template #cell-early_exit_minutes="{ row }">
                            <span v-if="row.early_exit_minutes > 0" class="text-orange-600 font-medium">
                                {{ formatMinutesToHours(row.early_exit_minutes) }}
                            </span>
                            <span v-else class="text-muted-foreground">-</span>
                        </template>

                        <template #cell-net_minutes="{ row }">
                            <span class="font-medium">{{ formatMinutesToHours(row.net_minutes) }}</span>
                        </template>
                        
                        <template #cell-is_late="{ row }">
                             <Badge
                                v-if="row.clock_in"
                                :variant="row.is_late ? 'destructive' : 'default'"
                                :class="row.is_late ? 'bg-destructive hover:bg-destructive/90' : 'bg-green-600 hover:bg-green-700'"
                                class="text-xs font-semibold px-2.5 py-1"
                            >
                                {{ row.is_late ? 'Yes' : 'No' }}
                            </Badge>
                            <span v-else class="text-muted-foreground">-</span>
                        </template>
                    </DataTable>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

