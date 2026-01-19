<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import type { Attendance, BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { CalendarDays, List, ChevronLeft, ChevronRight, MapPin, Clock, TrendingUp } from 'lucide-vue-next';
import DataTable from '@/components/ui/DataTable.vue';

interface Props {
    attendances: Attendance[];
    filters: {
        month: number;
        year: number;
    };
    availableYears: number[];
    userWeekendDays: string[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'My Attendance', href: '/attendance' },
];

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
    // This line was modified as per instruction, but 'dateStr' is undefined here.
    // Assuming it was meant to format the current selected month/year for a header.
    // Reverting to original logic for calendar layout, as the provided change was syntactically incorrect in isolation.
    // If the intent was to display the month/year string, a different computed property would be more appropriate.
    return new Date(parseInt(selectedYear.value), parseInt(selectedMonth.value) - 1, 1).getDay();
});

const attendanceMap = computed(() => {
    const map = new Map<string, Attendance>();
    if (!props.attendances) return map;
    
    props.attendances.forEach(a => {
        if (!a.date) return;
        // Standardize key to YYYY-MM-DD
        const d = new Date(a.date);
        if (!isNaN(d.getTime())) {
             const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
             map.set(key, a);
        }
        // Also store original date string as fallback
        map.set(a.date, a);
    });
    return map;
});

const calendarDays = computed(() => {
    const days: { date: string; day: number; attendance: Attendance | undefined }[] = [];
    for (let i = 1; i <= daysInMonth.value; i++) {
        const dateStr = `${selectedYear.value}-${String(selectedMonth.value).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
        // Use map for lookup
        const attendance = attendanceMap.value.get(dateStr);
        days.push({ date: dateStr, day: i, attendance });
    }
    return days;
});

const selectedAttendance = computed(() => {
    if (!selectedDate.value) return null;
    return attendanceMap.value.get(selectedDate.value) || null;
});

const selectDay = (date: string) => {
    selectedDate.value = selectedDate.value === date ? null : date;
};

// Next/Prev Month Navigation
const goToNextMonth = () => {
    let m = parseInt(selectedMonth.value);
    let y = parseInt(selectedYear.value);
    
    if (m === 12) {
        m = 1;
        y++;
    } else {
        m++;
    }
    
    selectedMonth.value = m.toString();
    selectedYear.value = y.toString();
};

const goToPrevMonth = () => {
    let m = parseInt(selectedMonth.value);
    let y = parseInt(selectedYear.value);
    
    if (m === 1) {
        m = 12;
        y--;
    } else {
        m--;
    }
    
    selectedMonth.value = m.toString();
    selectedYear.value = y.toString();
};

const getAttendanceColor = (attendance: Attendance | undefined) => {
    if (!attendance) return 'bg-gray-200 dark:bg-gray-700'; // Should not happen often if checking properly
    if (attendance.status === 'weekend') return 'bg-blue-500';
    if (attendance.status === 'sick_leave' || attendance.status === 'casual_leave') return 'bg-purple-500';
    if (attendance.status === 'absent') return 'bg-[#F45B5B]'; // Reddish
    if (!attendance.clock_in) return 'bg-gray-300 dark:bg-gray-600';
    if (attendance.is_late) return 'bg-[#F59E0B]'; // Amber/Orange
    return 'bg-[#2ECC71]'; // Green
};



// Helper functions
const formatTime = (dateString: string | null | undefined) => {
    if (!dateString) return '-- : --';
    return new Date(dateString).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
};

const formatDateForHeader = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        weekday: 'long',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

const formatMinutesToHours = (minutes: number | null | undefined) => {
    if (minutes === null || minutes === undefined) return '0h 0m';
    if (minutes === 0) return '0h 0m';
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours}h ${mins}m`;
};

const getStatusDotColor = (attendance: Attendance | undefined) => {
    if (!attendance) return null;
    if (attendance.status === 'weekend') return 'bg-blue-500';
    if (attendance.status === 'sick_leave' || attendance.status === 'casual_leave') return 'bg-purple-500';
    if (attendance.status === 'absent') return 'bg-[#F45B5B]';
    if (attendance.is_late) return 'bg-[#F59E0B]';
    if (attendance.clock_in || attendance.status === 'present') return 'bg-[#2ECC71]';
    
    // Fallback if record exists but status doesn't match above (e.g. pending or anomaly)
    return 'bg-gray-400 dark:bg-gray-500';
};

// Stats Calculation
const monthlyStats = computed(() => {
    const presentDays = props.attendances.filter(a => a.status === 'present' || (a.clock_in && a.status !== 'weekend')).length;
    
    // Calculate actual working days (total days in month - weekends)
    const totalDaysInMonth = daysInMonth.value;
    let workingDays = 0;
    
    // Map day names to indices
    const dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    const userWeekends = (props.userWeekendDays || ['saturday', 'sunday']).map(d => d.toLowerCase());
    
    // Count working days (days that are not user's weekends)
    for (let day = 1; day <= totalDaysInMonth; day++) {
        const date = new Date(parseInt(selectedYear.value), parseInt(selectedMonth.value) - 1, day);
        const dayOfWeek = date.getDay();
        const dayName = dayNames[dayOfWeek];
        
        if (!userWeekends.includes(dayName)) {
            workingDays++;
        }
    }
    
    // Calculate Average Clock In
    let totalClockInMinutes = 0;
    let clockInCount = 0;
    props.attendances.forEach(a => {
        if (a.clock_in) {
            const date = new Date(a.clock_in);
            totalClockInMinutes += date.getHours() * 60 + date.getMinutes();
            clockInCount++;
        }
    });
    
    let avgClockInStr = '--:-- AM';
    if (clockInCount > 0) {
        const avgMinutes = totalClockInMinutes / clockInCount;
        const h = Math.floor(avgMinutes / 60);
        const m = Math.floor(avgMinutes % 60);
        const date = new Date();
        date.setHours(h, m);
        avgClockInStr = date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
    }

    // Performance score - Mock logic
    // 100 - (Absent * 5) - (Late * 2) ? Just simple placeholder
    const absents = props.attendances.filter(a => a.status === 'absent').length;
    const lates = props.attendances.filter(a => a.is_late).length;
    let score = 100 - (absents * 10) - (lates * 2);
    if (score < 0) score = 0;
    if (score > 100) score = 100;

    return {
        present: presentDays,
        total: workingDays,
        avgClockIn: avgClockInStr,
        score: score
    };
});

const getStatusInfo = (attendance: Attendance) => {
    switch (attendance.status) {
        case 'present': return { label: 'Present', variant: 'default' as const, class: 'bg-green-600' };
        case 'absent': return { label: 'Absent', variant: 'destructive' as const, class: '' };
        case 'weekend': return { label: 'Weekend', variant: 'secondary' as const, class: 'bg-blue-600 text-white' };
        case 'sick_leave': return { label: 'Sick Leave', variant: 'outline' as const, class: 'bg-purple-100 text-purple-800' };
        case 'casual_leave': return { label: 'Casual Leave', variant: 'outline' as const, class: 'bg-purple-100 text-purple-800' };
        default: return { label: 'Unknown', variant: 'outline' as const, class: '' };
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

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
    });
};

const getLocation = (attendance: Attendance | null | undefined) => {
    if (attendance && (attendance.status === 'present' || attendance.clock_in)) {
        return 'Office';
    }
    return 'Not Defined';
};
</script>

<template>
    <Head title="My Attendance" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6 min-h-screen">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">My Attendance</h1>
                </div>
                <div class="flex items-center gap-3">
                    <TooltipProvider>
                         <div class="flex rounded-lg border bg-white dark:bg-gray-900 dark:border-gray-800 p-1 shadow-sm">
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button
                                        size="sm"
                                        :variant="viewMode === 'calendar' ? 'default' : 'ghost'"
                                        class="h-8 px-3 text-xs"
                                        :class="viewMode === 'calendar' ? 'bg-blue-600 hover:bg-blue-700' : 'text-gray-500 dark:text-gray-400'"
                                        @click="viewMode = 'calendar'"
                                    >
                                        <CalendarDays class="h-4 w-4 mr-2" />
                                        Calendar
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>Calendar View</TooltipContent>
                            </Tooltip>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button
                                        size="sm"
                                        :variant="viewMode === 'table' ? 'default' : 'ghost'"
                                        class="h-8 px-3 text-xs"
                                        :class="viewMode === 'table' ? 'bg-blue-600 hover:bg-blue-700' : 'text-gray-500 dark:text-gray-400'"
                                        @click="viewMode = 'table'"
                                    >
                                        <List class="h-4 w-4 mr-2" />
                                        List View
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>List View</TooltipContent>
                            </Tooltip>
                        </div>
                    </TooltipProvider>

                    <Select v-model="selectedMonth">
                        <SelectTrigger class="w-[110px] bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-800 dark:text-gray-200">
                            <SelectValue :placeholder="selectedMonthLabel" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="month in months" :key="month.value" :value="month.value">
                                {{ month.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="selectedYear">
                        <SelectTrigger class="w-[90px] bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-800 dark:text-gray-200">
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

            <!-- Calendar Layout -->
             <div v-if="viewMode === 'calendar'" class="grid gap-6 lg:grid-cols-12 items-start">
                
                <!-- Left Column: Calendar (8 cols) -->
                <div class="lg:col-span-8 flex flex-col gap-6">
                    <Card class="border-0 shadow-sm rounded-xl overflow-hidden">
                        <CardContent class="p-6">
                            <!-- Calendar Header -->
                            <div class="flex items-center justify-between mb-8">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                        <CalendarDays class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <span class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                        {{ selectedMonthLabel }} {{ selectedYear }} Attendance
                                    </span>
                                </div>
                                <div class="flex gap-2">
                                    <Button variant="ghost" size="icon" class="h-8 w-8 hover:bg-gray-100 dark:hover:bg-gray-800" @click="goToPrevMonth">
                                        <ChevronLeft class="h-4 w-4 text-gray-600 dark:text-gray-400" />
                                    </Button>
                                    <Button variant="ghost" size="icon" class="h-8 w-8 hover:bg-gray-100 dark:hover:bg-gray-800" @click="goToNextMonth">
                                        <ChevronRight class="h-4 w-4 text-gray-600 dark:text-gray-400" />
                                    </Button>
                                </div>
                            </div>

                            <!-- Weekday Headers -->
                            <div class="grid grid-cols-7 mb-4">
                                <div v-for="day in ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT']" 
                                     :key="day" 
                                     class="text-center text-xs font-semibold text-gray-400 dark:text-gray-500 py-2 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 uppercase tracking-wider">
                                    {{ day }}
                                </div>
                            </div>

                            <!-- Days Grid -->
                            <div class="grid grid-cols-7 auto-rows-fr">
                                <!-- Empty cells -->
                                <div v-for="i in firstDayOfMonth" :key="'empty-' + i" class="h-10 lg:h-14 border-r border-b border-gray-50 dark:border-gray-800"></div>
                                
                                <!-- Calendar Days -->
                                <div
                                    v-for="day in calendarDays"
                                    :key="day.date"
                                    @click="selectDay(day.date)"
                                    :class="[
                                        'h-10 lg:h-14 border-r border-b border-gray-50 dark:border-gray-800 relative cursor-pointer group hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors flex flex-col items-center justify-center gap-0.5',
                                        selectedDate === day.date ? 'bg-blue-50/50 dark:bg-blue-900/10 ring-1 ring-blue-500 inset-0 z-10' : ''
                                    ]"
                                >
                                    <span :class="[
                                        'text-sm font-medium',
                                        selectedDate === day.date ? 'text-blue-600 dark:text-blue-400 font-bold' : 'text-gray-700 dark:text-gray-300'
                                    ]">
                                        {{ day.day }}
                                    </span>
                                    
                                    <!-- Status Dot -->
                                    <div v-if="getStatusDotColor(day.attendance)" 
                                         :class="['w-1.5 h-1.5 rounded-full', getStatusDotColor(day.attendance)]">
                                    </div>
                                    <div v-else-if="!day.attendance" class="w-1.5 h-1.5 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                                </div>
                            </div>

                            <!-- Legend -->
                            <div class="flex flex-wrap items-center justify-center gap-6 mt-8 pt-6 border-t border-gray-100 dark:border-gray-800">
                                <div class="flex items-center gap-2">
                                    <div class="w-2.5 h-2.5 rounded-full bg-[#2ECC71]"></div>
                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">On Time</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-2.5 h-2.5 rounded-full bg-[#F59E0B]"></div>
                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Late</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-2.5 h-2.5 rounded-full bg-[#F45B5B]"></div>
                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Absent</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-2.5 h-2.5 rounded-full bg-blue-500"></div>
                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Weekend</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-2.5 h-2.5 rounded-full bg-purple-500"></div>
                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Leave</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-2.5 h-2.5 rounded-full bg-gray-300 dark:bg-gray-600"></div>
                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">No Record</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right Column: Details & Summary (4 cols) -->
                <div class="lg:col-span-4 flex flex-col gap-4">
                    
                    <!-- Selected Date Details -->
                    <Card class="border-0 shadow-sm rounded-xl">
                        <CardContent class="p-6">
                             <div class="mb-6">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Selected Date</p>
                                <div class="flex items-center justify-between">
                                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                                        {{ selectedDate ? formatDateForHeader(selectedDate) : 'Select a Date' }}
                                    </h2>
                                    <div class="p-2 bg-gray-100 dark:bg-gray-800 rounded-lg">
                                        <CalendarDays class="h-4 w-4 text-gray-600 dark:text-gray-400" />
                                    </div>
                                </div>
                             </div>

                             <!-- Attendance Times (Always Visible) -->
                             <div class="grid grid-cols-2 gap-4 mb-6">
                                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4 border border-gray-100 dark:border-gray-800">
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider mb-2">Clock In</p>
                                    <p :class="['text-sm font-semibold', selectedAttendance?.is_late ? 'text-amber-500' : 'text-gray-900 dark:text-gray-100']">
                                        {{ selectedAttendance?.clock_in ? formatTime(selectedAttendance.clock_in) : '--:--' }}
                                    </p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4 border border-gray-100 dark:border-gray-800">
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider mb-2">Clock Out</p>
                                    <p :class="['text-sm font-semibold', (selectedAttendance?.early_exit_minutes ?? 0) > 0 ? 'text-orange-500' : 'text-gray-900 dark:text-gray-100']">
                                        {{ selectedAttendance?.clock_out ? formatTime(selectedAttendance.clock_out) : '--:--' }}
                                    </p>
                                </div>
                             </div>

                             <!-- Info Rows -->
                             <div class="space-y-4 mb-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <Clock class="h-4 w-4 text-gray-400" />
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Total Work Hours</span>
                                    </div>
                                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100">
                                        {{ formatMinutesToHours(selectedAttendance?.net_minutes) }}
                                    </span>
                                </div>
                                <div class="border-t border-gray-100 dark:border-gray-800"></div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <MapPin class="h-4 w-4 text-gray-400" />
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Location</span>
                                    </div>
                                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ getLocation(selectedAttendance) }}</span>
                                </div>
                             </div>

                             <Button class="w-full bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30 font-semibold border-0 shadow-none">
                                Request Manual Entry
                             </Button>
                        </CardContent>
                    </Card>

                    <!-- Monthly Summary Card -->
                    <Card class="border-0 shadow-sm rounded-xl">
                        <CardContent class="p-3">
                            <div class="grid grid-cols-3 gap-3">
                                <!-- Stat Item: Present Days -->
                                <div class="flex flex-col items-center justify-center p-2 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-800 text-center">
                                    <div class="relative h-14 w-14 mb-2">
                                        <!-- Background Circle -->
                                        <svg class="h-full w-full -rotate-90" viewBox="0 0 36 36">
                                            <path class="text-gray-200 dark:text-gray-700" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="3" />
                                            <!-- Progress Circle -->
                                            <path class="text-blue-600 dark:text-blue-500" :stroke-dasharray="`${(monthlyStats.present / monthlyStats.total) * 100}, 100`" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                                        </svg>
                                        <div class="absolute inset-0 flex items-center justify-center text-xs font-bold text-gray-900 dark:text-gray-100">
                                            {{ monthlyStats.present }}/{{ monthlyStats.total }}
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium leading-tight">Present Days</p>
                                </div>

                                <!-- Stat Item: Avg Clock In -->
                                <div class="flex flex-col items-center justify-center p-2 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-800 text-center">
                                    <div class="h-14 w-14 mb-2 flex items-center justify-center rounded-full bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400">
                                        <Clock class="h-6 w-6" />
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium mb-0.5 leading-tight">Avg Check In</p>
                                    <p class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ monthlyStats.avgClockIn }}</p>
                                </div>

                                <!-- Stat Item: Performance -->
                                <div class="flex flex-col items-center justify-center p-2 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-800 text-center">
                                    <div class="relative h-14 w-14 mb-2">
                                         <!-- Background Circle -->
                                        <svg class="h-full w-full -rotate-90" viewBox="0 0 36 36">
                                            <path class="text-gray-200 dark:text-gray-700" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="3" />
                                            <!-- Progress Circle -->
                                            <path class="text-green-600 dark:text-green-500" :stroke-dasharray="`${monthlyStats.score}, 100`" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                                        </svg>
                                        <div class="absolute inset-0 flex items-center justify-center text-xs font-bold text-gray-900 dark:text-gray-100">
                                            {{ monthlyStats.score }}%
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium leading-tight">Performance</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                </div>
             </div>

             <!-- Table View (Keep existing roughly as is but styled slightly if needed) -->
             <Card v-else class="border-0 shadow-sm rounded-xl">
                <CardContent class="p-0">
                    <DataTable :columns="columns" :data="attendances">
                        <template #cell-date="{ row }">
                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ formatDate(row.date) }}</span>
                        </template>

                        <template #cell-clock_in="{ row }">
                            <span :class="row.is_late ? 'text-amber-600 font-medium' : 'text-gray-700 dark:text-gray-300'">
                                {{ formatTime(row.clock_in) }}
                            </span>
                        </template>

                        <template #cell-clock_out="{ row }">
                            <span :class="row.early_exit_minutes > 0 ? 'text-orange-600 font-medium' : 'text-gray-700 dark:text-gray-300'">
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
                        
                        <template #cell-net_minutes="{ row }">
                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ formatMinutesToHours(row.net_minutes) }}</span>
                        </template>
                        
                         <template #cell-is_late="{ row }">
                             <Badge
                                v-if="row.clock_in"
                                :variant="row.is_late ? 'destructive' : 'default'"
                                :class="row.is_late ? 'bg-amber-100 text-amber-800 hover:bg-amber-200 border-amber-200 dark:bg-amber-100 dark:text-amber-800 dark:border-amber-200' : 'bg-green-100 text-green-800 hover:bg-green-200 border-green-200 dark:bg-green-100 dark:text-green-800 dark:border-green-200'"
                                class="text-xs font-semibold px-2.5 py-1 border shadow-none"
                            >
                                {{ row.is_late ? 'Yes' : 'No' }}
                            </Badge>
                            <span v-else class="text-gray-400">-</span>
                        </template>
                    </DataTable>
                </CardContent>
             </Card>
        </div>
    </AppLayout>
</template>


