<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';

import ClockInButton from '@/components/ClockInButton.vue';
import ClockInModal from '@/components/ClockInModal.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { type BreadcrumbItem, type Attendance, type AttendanceStats, type User } from '@/types';
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import { Clock, LogIn, LogOut, AlertTriangle, CheckCircle, CalendarDays, Timer, TrendingUp, Calendar, Info, Bell, Check } from 'lucide-vue-next';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

interface CompanyStats {
    total_employees: number;
    present: number;
    absent: number;
    late: number;
    present_list: User[];
    absent_list: User[];
    late_list: User[];
}

interface Props {
    attendanceSummary?: {
        total_employees: number;
        present: number;
        absent: number;
        late: number;
        all_list: User[];
        present_list: User[];
        absent_list: User[];
        late_list: User[];
    } | null;
    punctuality?: {
        percentage: number;
        total_present: number;
        total_late: number;
    };
    punctualityTrends?: Array<{
        month: string;
        full_date: string;
        percentage: number;
        total_present: number;
        total_late: number;
    }>;
    todayAttendance: Attendance | null;
    stats: AttendanceStats;
    companyStats?: CompanyStats | null;
    isWeekend: boolean;
    officeStartTime: string;
    currentTime: string;
    attendanceHistory: Record<string, { date: string; is_late: boolean; clock_in: string | null; clock_out: string | null; status: string }>;
    userWeekendDays: string[];
    leaveBalances: Array<{
        leave_type_id: number;
        leave_type_name: string;
        leave_type_code: string;
        balance: number;
        used: number;
        available: number;
    }>;
    announcements: Array<{
        id: number;
        title: string;
        content: string;
        type: 'info' | 'warning' | 'success' | 'event';
        created_at: string;
        created_by: string | null;
    }>;
}

const props = defineProps<Props>();
const page = usePage();
const user = computed(() => page.props.auth.user);

const flash = computed(() => page.props.flash as { success?: string; error?: string });

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const isProcessing = ref(false);

// Live clock
const currentTime = ref(new Date());
let clockInterval: number | null = null;

onMounted(() => {
    clockInterval = window.setInterval(() => {
        currentTime.value = new Date();
    }, 1000);
});

onUnmounted(() => {
    if (clockInterval) {
        clearInterval(clockInterval);
    }
});

const formattedTime = computed(() => {
    return currentTime.value.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    });
});

const formattedDate = computed(() => {
    return currentTime.value.toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric'
    });
});

const currentStatus = computed(() => {
    if (props.todayAttendance?.status === 'absent') return 'absent';
    if (props.todayAttendance?.clock_in && !props.todayAttendance?.clock_out) return 'working';
    if (props.todayAttendance?.clock_out) return 'clocked_out';
    if (props.isWeekend) return 'weekend';
    return 'not_clocked_in';
});

const formatTime = (dateString: string | null) => {
    if (!dateString) return '--:--';
    return new Date(dateString).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    });
};

const clockIn = () => {
    isProcessing.value = true;
    router.post('/attendance/clock-in', {}, {
        preserveScroll: true,
        onFinish: () => {
            isProcessing.value = false;
        },
    });
};

const clockOut = () => {
    isProcessing.value = true;
    router.post('/attendance/clock-out', {}, {
        preserveScroll: true,
        onFinish: () => {
            isProcessing.value = false;
        },
    });
};

const buttonStatus = computed(() => {
    if (currentStatus.value === 'working') return 'working';
    if (currentStatus.value === 'clocked_out') return 'clocked_out';
    return 'idle';
});

// Calculate dynamic worked seconds today if clocked in
const workedSecondsToday = computed(() => {
    if (props.todayAttendance?.clock_in && !props.todayAttendance?.clock_out) {
        const start = new Date(props.todayAttendance.clock_in).getTime();
        const now = currentTime.value.getTime();
        return Math.floor((now - start) / 1000);
    }
    return 0;
});

const formattedWorkedTimeToday = computed(() => {
    const seconds = workedSecondsToday.value;
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;
    return `${h}h ${m}m`;
});

const showClockModal = ref(false);

const handleClockAction = () => {
    if (buttonStatus.value === 'clocked_out') return;
    console.log('Clock action triggered, showing modal');
    showClockModal.value = true;
};

const confirmClockAction = () => {
    if (buttonStatus.value === 'idle') {
        clockIn();
    } else if (buttonStatus.value === 'working') {
        clockOut();
    }
};

// Map day index to day name for weekend checking
const dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

// Announcement type helpers
const getAnnouncementIcon = (type: string) => {
    switch (type) {
        case 'info': return Info;
        case 'warning': return AlertTriangle;
        case 'success': return CheckCircle;
        case 'event': return Calendar;
        default: return Bell;
    }
};

const getAnnouncementClass = (type: string) => {
    switch (type) {
        case 'info': return 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800';
        case 'warning': return 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800';
        case 'success': return 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800';
        case 'event': return 'bg-purple-50 dark:bg-purple-900/20 border-purple-200 dark:border-purple-800';
        default: return 'bg-gray-50 dark:bg-gray-900/20 border-gray-200 dark:border-gray-800';
    }
};

const getAnnouncementIconClass = (type: string) => {
    switch (type) {
        case 'info': return 'text-blue-600 dark:text-blue-400';
        case 'warning': return 'text-amber-600 dark:text-amber-400';
        case 'success': return 'text-green-600 dark:text-green-400';
        case 'event': return 'text-purple-600 dark:text-purple-400';
        default: return 'text-gray-600 dark:text-gray-400';
    }
};

// Generate heatmap data (365 days) using real attendance records
const heatmapData = computed(() => {
    const today = new Date();
    const data: { date: Date; status: 'present' | 'late' | 'absent' | 'no_record' | 'weekend' | 'future'; dateStr: string }[] = [];
    
    // Get user's weekend days (lowercase for comparison)
    const weekendDays = (props.userWeekendDays || ['saturday', 'sunday']).map(d => d.toLowerCase());

    // Generate last 365 days
    for (let i = 364; i >= 0; i--) {
        const date = new Date(today);
        date.setDate(date.getDate() - i);

        const dayOfWeek = date.getDay();
        const dayName = dayNames[dayOfWeek];
        const isFuture = date > today;
        const isWeekendDay = weekendDays.includes(dayName);

        // Get date key for attendance lookup
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const dateKey = `${year}-${month}-${day}`;
        const attendanceRecord = props.attendanceHistory[dateKey];

        let status: 'present' | 'late' | 'absent' | 'no_record' | 'weekend' | 'future';

        if (isFuture) {
            status = 'future';
        } else if (attendanceRecord && attendanceRecord.status === 'absent') {
            // Show absent status when marked by scheduler
            status = 'absent';
        } else if (attendanceRecord && attendanceRecord.clock_in) {
            // If user clocked in (even on weekend), show as present or late
            status = attendanceRecord.is_late ? 'late' : 'present';
        } else if (isWeekendDay) {
            // Only show as weekend if no attendance record
            status = 'weekend';
        } else {
            status = 'no_record';
        }

        const dateStr = date.toLocaleDateString('en-US', {
            weekday: 'short',
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });

        data.push({ date, status, dateStr });
    }

    return data;
});

// Group heatmap by weeks (7 days per column)
const heatmapWeeks = computed(() => {
    const weeks: typeof heatmapData.value[] = [];
    let currentWeek: typeof heatmapData.value = [];

    // Pad the first week if it doesn't start on Sunday
    const firstDay = heatmapData.value[0];
    if (firstDay) {
        const startDayOfWeek = firstDay.date.getDay();
        for (let i = 0; i < startDayOfWeek; i++) {
            currentWeek.push({ date: new Date(0), status: 'future', dateStr: '' });
        }
    }

    heatmapData.value.forEach((day) => {
        currentWeek.push(day);
        if (currentWeek.length === 7) {
            weeks.push(currentWeek);
            currentWeek = [];
        }
    });

    // Push remaining days
    if (currentWeek.length > 0) {
        weeks.push(currentWeek);
    }

    return weeks;
});

// Get month labels
const monthLabels = computed(() => {
    const labels: { month: string; weekIndex: number }[] = [];
    let lastMonth = -1;

    heatmapWeeks.value.forEach((week, weekIndex) => {
        const validDay = week.find(d => d.dateStr !== '');
        if (validDay) {
            const month = validDay.date.getMonth();
            if (month !== lastMonth) {
                labels.push({
                    month: validDay.date.toLocaleDateString('en-US', { month: 'short' }),
                    weekIndex
                });
                lastMonth = month;
            }
        }
    });

    return labels;
});

const getHeatmapColor = (status: string) => {
    switch (status) {
        case 'present': return 'bg-sky-500 dark:bg-sky-600';
        case 'late': return 'bg-amber-500 dark:bg-amber-600';
        case 'absent': return 'bg-red-500 dark:bg-red-600';
        case 'no_record': return 'bg-transparent border border-gray-300 dark:border-gray-600';
        case 'weekend': return 'bg-gray-300 dark:bg-gray-700';
        case 'future': return 'bg-transparent';
        default: return 'bg-gray-100 dark:bg-gray-800';
    }
};

const getStatusLabel = (status: string) => {
    switch (status) {
        case 'present': return 'Present';
        case 'late': return 'Late';
        case 'absent': return 'Absent';
        case 'no_record': return 'No Record';
        case 'weekend': return 'Weekend';
        case 'future': return '';
        default: return 'No Data';
    }
};

// --- New Cards Logic ---

// --- New Cards Logic ---

const monthlyTrends = computed(() => {
    // Group attendanceHistory by Month (YYYY-MM)
    const monthlyData: Record<string, { 
        totalMinutes: number; 
        present: number; 
        late: number; 
        absent: number;
        totalEntries: number; 
        monthLabel: string;
        year: number;
        month: number;
    }> = {};

    // Get last 6 months including current
    const today = new Date();
    for (let i = 5; i >= 0; i--) {
        const d = new Date(today.getFullYear(), today.getMonth() - i, 1);
        const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
        monthlyData[key] = {
            totalMinutes: 0,
            present: 0,
            late: 0,
            absent: 0,
            totalEntries: 0,
            monthLabel: d.toLocaleString('default', { month: 'short' }),
            year: d.getFullYear(),
            month: d.getMonth()
        };
    }

    // Process all history
    Object.values(props.attendanceHistory).forEach(record => {
        const d = new Date(record.date);
        const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
        
        if (monthlyData[key]) {
             if (record.clock_in && record.clock_out) {
                const start = new Date(record.clock_in);
                const end = new Date(record.clock_out);
                const duration = (end.getTime() - start.getTime()) / 1000 / 60;
                monthlyData[key].totalMinutes += duration;
             }
             
             if (record.status !== 'weekend' && record.status !== 'holiday') { // Only count working days for punctuality
                 monthlyData[key].totalEntries++;
                 if (record.status === 'present' || record.status === 'late') {
                     monthlyData[key].present++;
                 }
                 if (record.is_late) {
                     monthlyData[key].late++;
                 }
                 if (record.status === 'absent') {
                     monthlyData[key].absent++;
                 }
             }
        }
    });

    // Convert to Array
    return Object.values(monthlyData).map(m => {
        const hours = Math.floor(m.totalMinutes / 60);
        
        // Punctuality Calculation (Legacy/Reference)
        let punctuality = 0;
        if (m.totalEntries > 0) {
             const onTime = m.present - m.late;
             punctuality = (onTime / m.totalEntries) * 100;
        }

        // Performance Score Calculation (Sync with UserDashboard)
        // Formula: 100 - (Absent * 10) - (Late * 2)
        // Fix: If no entries, score should be 0 (not 100)
        let score = 0;
        if (m.totalEntries > 0) {
            score = 100 - (m.absent * 10) - (m.late * 2);
            if (score < 0) score = 0;
            if (score > 100) score = 100;
        }
        
        // Work Hours Height Scale (Max 200h/mo?)
        // Ensure min height of 15% for visibility of "empty" or low bars
        let workHeight = (hours / 200) * 100;
        if (workHeight > 100) workHeight = 100;
        if (workHeight < 15) workHeight = 15;

        // Punctuality/Performance Height
        // Ensure min height of 15%
        let punctualityHeight = score; // Use Score for height
        if (punctualityHeight < 15) punctualityHeight = 15;

        // Calculate Daily Average (Hours)
        let dailyAvg = 0;
        if (m.totalEntries > 0) {
            dailyAvg = (m.totalMinutes / m.totalEntries) / 60;
        }

        return {
            month: m.monthLabel,
            year: m.year,   // Ensure year is passed for sorting if needed, logic uses index though.
            monthIndex: m.month, // Ensure month index is passed
            hours,
            dailyAvg,
            punctuality: Math.round(punctuality),
            score: Math.round(score), // Export score
            workHeight,
            punctualityHeight
        };
    }).sort((a, b) => {
        if (a.year !== b.year) return a.year - b.year;
        return a.monthIndex - b.monthIndex;
    });
});

const workHoursTrend = computed(() => {
    const trends = monthlyTrends.value;
    if (trends.length < 2) return 0;
    
    const current = trends[trends.length - 1];
    const previous = trends[trends.length - 2];
    
    // Logic: Calculate trend based on VISIBLE hours (floored), not internal exact minutes.
    // This prevents "494%" scenarios when both show "0h".
    
    // Case 1: Both are 0h -> No change visibly.
    if (previous.hours === 0 && current.hours === 0) return 0;

    // Case 2: Previous was 0h, now we have hours -> 100% increase (New)
    if (previous.hours === 0) return 100;
    
    // Case 3: Standard calculation
    const diff = ((current.hours - previous.hours) / previous.hours) * 100;
    return Math.round(diff);
});

const punctualityTrend = computed(() => { // Actually Performance Trend now
    const trends = monthlyTrends.value;
    if (trends.length < 2) return 0;
    
    const current = trends[trends.length - 1];
    const previous = trends[trends.length - 2];
    
    // Guard: If current score is 0 (no data), trend is 0
    if (current.score === 0) return 0;

    // Calculate percentage point difference
    return current.score - previous.score;
});

const currentPerformanceScore = computed(() => {
    const current = monthlyTrends.value[monthlyTrends.value.length - 1];
    return current ? current.score : 100; // Default 100 if no data? Or 0? Logic says 100 start.
});

const currentMonthWorkHours = computed(() => {
    // Get the last month from trends (which is current month)
    const current = monthlyTrends.value[monthlyTrends.value.length - 1];
    return current ? current.hours : 0;
});

</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <!-- Flash Messages -->
            <Alert v-if="flash.success" class="border-green-500 bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800">
                <CheckCircle class="h-4 w-4" />
                <AlertDescription>{{ flash.success }}</AlertDescription>
            </Alert>
            <Alert v-if="flash.error" variant="destructive">
                <AlertTriangle class="h-4 w-4" />
                <AlertDescription>{{ flash.error }}</AlertDescription>
            </Alert>

            <!-- Welcome & Clock Action -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold">Welcome, {{ user.name.split(' ')[0] }}</h1>
                    <p class="text-sm text-muted-foreground">{{ formattedDate }} • {{ formattedTime }}</p>
                </div>

                <!-- Header Clock Action -->
<<<<<<< HEAD
                <div class="flex items-center bg-card border rounded-xl shadow-sm overflow-hidden">
=======
                <div class="hidden md:flex items-center bg-card border rounded-xl shadow-sm overflow-hidden">
>>>>>>> dev
                    <div class="px-4 py-1.5 flex flex-col items-start border-r bg-muted/20 min-w-[100px] justify-center h-[42px]">
                        <span class="text-[9px] uppercase font-bold text-muted-foreground tracking-wider leading-none mb-0.5">Status</span>
                        <div class="flex items-center gap-2">
                            <span class="relative flex h-2.5 w-2.5">
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5" :class="{
                                    'bg-green-500': buttonStatus === 'working',
                                    'bg-amber-500': buttonStatus === 'idle',
                                    'bg-gray-400': buttonStatus === 'clocked_out'
                                }"></span>
                            </span>
                            <span class="text-xs font-semibold leading-none" :class="{
                                'text-green-600 dark:text-green-400': buttonStatus === 'working',
                                'text-amber-600 dark:text-amber-400': buttonStatus === 'idle',
                                'text-muted-foreground': buttonStatus === 'clocked_out'
                            }">
                                {{ buttonStatus === 'working' ? 'Working' : (buttonStatus === 'idle' ? 'Ready' : 'Completed') }}
                            </span>
                        </div>
                    </div>
                    <div class="p-1">
                        <Button 
                            v-if="buttonStatus !== 'clocked_out'"
                            :variant="buttonStatus === 'working' ? 'destructive' : 'default'"
                            class="h-[42px] px-5 font-semibold shadow-md transition-all hover:scale-[1.02] active:scale-95 rounded-lg flex items-center gap-2 text-sm"
                            :class="{
                                'bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white shadow-blue-500/20': buttonStatus === 'idle',
                                'bg-gradient-to-r from-red-500 to-rose-600 hover:from-red-600 hover:to-rose-700 text-white shadow-red-500/20': buttonStatus === 'working'
                            }"
                            :disabled="isProcessing"
                            @click="handleClockAction"
                        >
                            <div class="flex flex-col items-center leading-none gap-0.5">
                                <LogIn v-if="buttonStatus === 'idle'" class="h-3.5 w-3.5" />
                                <LogOut v-else class="h-3.5 w-3.5" />
                            </div>
                            <span>{{ buttonStatus === 'working' ? 'Clock Out' : 'Clock In' }}</span>
                        </Button>
                         <div v-else class="h-[42px] px-5 bg-muted/30 rounded-lg text-xs font-medium text-muted-foreground flex items-center gap-2">
                            <CheckCircle class="h-4 w-4 text-green-500" />
                            <span>Day Complete</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bento Grid Layout -->
            <div class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-8">



                <!-- Today's Stats Card -->
                <Card class="lg:col-span-2">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Today's Status</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <LogIn class="h-4 w-4 text-muted-foreground" />
                                <span class="text-sm text-muted-foreground">Clock In</span>
                            </div>
                            <span class="text-sm font-medium">{{ formatTime(todayAttendance?.clock_in ?? null) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <LogOut class="h-4 w-4 text-muted-foreground" />
                                <span class="text-sm text-muted-foreground">Clock Out</span>
                            </div>
                            <span class="text-sm font-medium">{{ formatTime(todayAttendance?.clock_out ?? null) }}</span>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t">
                            <span class="text-sm text-muted-foreground">Status</span>
                            <Badge
                                variant="outline"
                                :class="{
                                    'border-green-500 text-green-600 bg-green-50 dark:bg-green-900/20': !todayAttendance?.is_late && todayAttendance?.clock_in,
                                    'border-amber-500 text-amber-600 bg-amber-50 dark:bg-amber-900/20': todayAttendance?.is_late,
                                    'border-red-500 text-red-600 bg-red-50 dark:bg-red-900/20': todayAttendance?.status === 'absent',
                                }"
                                class="text-xs"
                            >
                                {{ todayAttendance?.status === 'absent' ? 'Absent' : (todayAttendance?.is_late ? 'Late' : (todayAttendance?.clock_in ? 'On Time' : 'Not Marked')) }}
                            </Badge>
                        </div>
                    </CardContent>
                </Card>

<<<<<<< HEAD
                <!-- Punctuality Card -->
                <Card class="bg-card/50 backdrop-blur-sm border-muted/50 shadow-sm hover:shadow-md transition-all duration-300 group overflow-hidden relative">
                     <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                        <Clock class="h-16 w-16 text-primary" />
                    </div>
                    <CardHeader class="pb-2">
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Punctuality</CardTitle>
                            <span v-if="punctualityTrends && punctualityTrends.length > 1" 
                                  class="text-xs font-medium px-2 py-0.5 rounded-full" 
                                  :class="punctualityTrends[punctualityTrends.length - 1].percentage >= punctualityTrends[punctualityTrends.length - 2].percentage ? 'bg-green-500/10 text-green-500' : 'bg-red-500/10 text-red-500'">
                                {{ punctualityTrends[punctualityTrends.length - 1].percentage >= punctualityTrends[punctualityTrends.length - 2].percentage ? '+' : '' }}{{ (punctualityTrends[punctualityTrends.length - 1].percentage - punctualityTrends[punctualityTrends.length - 2].percentage).toFixed(1) }}%
                            </span>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div>
                                <div class="text-3xl font-bold tracking-tight text-foreground flex items-baseline gap-1">
                                    {{ punctuality ? punctuality.percentage : 0 }}%
                                </div>
                                <p class="text-xs text-muted-foreground mt-1">Lifetime on-time arrival rate</p>
                            </div>
                            
                            <!-- Simple Bar Chart -->
                            <div class="h-[60px] flex items-end justify-between gap-1 pt-2">
                                <div v-for="(month, index) in punctualityTrends" :key="index" class="relative flex-1 flex flex-col justify-end group/bar">
                                    <div 
                                        class="w-full rounded-sm transition-all duration-300 hover:opacity-80 relative"
                                        :class="month.percentage >= 90 ? 'bg-amber-500' : (month.percentage >= 75 ? 'bg-amber-400' : 'bg-red-400')"
                                        :style="{ height: `${Math.max(month.percentage * 0.6, 5)}%` }"
                                    ></div>
                                    <div class="opacity-0 group-hover/bar:opacity-100 absolute bottom-full left-1/2 -translate-x-1/2 mb-1 bg-popover text-popover-foreground text-[10px] px-1.5 py-0.5 rounded shadow-sm whitespace-nowrap z-10 border transition-opacity pointer-events-none">
                                        {{ month.month }}: {{ month.percentage }}%
                                    </div>
                                </div>
                                <!-- Empty states placeholders if needed -->
                                <div v-if="!punctualityTrends || punctualityTrends.length === 0" class="w-full h-full flex items-center justify-center text-[10px] text-muted-foreground bg-muted/20 rounded">
                                    No data
                                </div>
                            </div>
=======
                <!-- New Stats Grid -->
                <!-- Work Hours Card -->
                <Card class="lg:col-span-2">
                    <CardHeader class="pb-3">
                         <div class="flex items-center justify-between">
                            <CardTitle class="text-sm font-medium text-muted-foreground flex items-center gap-2">
                                <Timer class="h-4 w-4" />
                                Work Hours
                            </CardTitle>
                                         <div 
                                             class="px-2 py-0.5 rounded-full text-[10px] font-medium border cursor-default"
                                             :class="[
                                                 workHoursTrend > 0 ? 'bg-green-100 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-600 dark:text-green-400' : 
                                                 workHoursTrend < 0 ? 'bg-red-100 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-600 dark:text-red-400' :
                                                 'bg-gray-100 dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400'
                                             ]"
                                         >
                                             {{ workHoursTrend > 0 ? '+' : ''}}{{ workHoursTrend }}%
                                         </div>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-baseline gap-1">
                            <p class="text-2xl font-bold tracking-tight">{{ currentMonthWorkHours }}h</p>
                            <span class="text-sm text-muted-foreground">/mo</span>
                        </div>

                         <!-- Bar Chart -->
                         <div class="flex items-end justify-between gap-2 mt-4">
                            <template v-for="(month, index) in monthlyTrends" :key="index">
                                <div class="flex flex-col items-center gap-2 flex-1">
                                    <TooltipProvider>
                                        <Tooltip :delay-duration="0">
                                            <TooltipTrigger as-child>
                                                <div class="h-12 w-full flex items-end rounded-sm bg-muted/20 overflow-hidden">
                                                    <div 
                                                        class="w-2/3 mx-auto rounded-t-sm transition-all duration-300 hover:opacity-80 cursor-help"
                                                        :class="[
                                                            month.workHeight > 20 ? 'bg-blue-500 dark:bg-blue-600' : 'bg-muted'
                                                        ]" 
                                                        :style="`height: ${month.workHeight}%`"
                                                    ></div>
                                                </div>
                                            </TooltipTrigger>
                                            <TooltipContent side="top" class="text-xs">
                                                <p>{{ month.month }}</p>
                                                <p>{{ month.hours }}h Worked</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                    <span class="text-[10px] text-muted-foreground font-medium uppercase">{{ month.month }}</span>
                                </div>
                            </template>
                         </div>
                    </CardContent>
                </Card>

                <!-- Performance Card (Previously Punctuality) -->
                <Card class="lg:col-span-2">
                     <CardHeader class="pb-3">
                         <div class="flex items-center justify-between">
                            <CardTitle class="text-sm font-medium text-muted-foreground flex items-center gap-2">
                                <TrendingUp class="h-4 w-4" />
                                Performance
                            </CardTitle>
                                         <div 
                                             class="px-2 py-0.5 rounded-full text-[10px] font-medium border cursor-default"
                                             :class="[
                                                 punctualityTrend > 0 ? 'bg-green-100 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-600 dark:text-green-400' : 
                                                 punctualityTrend < 0 ? 'bg-red-100 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-600 dark:text-red-400' :
                                                 'bg-gray-100 dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400'
                                             ]"
                                         >
                                             {{ punctualityTrend > 0 ? '+' : ''}}{{ punctualityTrend }}%
                                         </div>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-baseline gap-1">
                             <p class="text-2xl font-bold tracking-tight">{{ currentPerformanceScore }}%</p>
                        </div>

                        <!-- Bar Chart -->
                        <div class="flex items-end justify-between gap-2 mt-4">
                            <template v-for="(month, index) in monthlyTrends" :key="index">
                                <div class="flex flex-col items-center gap-2 flex-1">
                                    <TooltipProvider>
                                        <Tooltip :delay-duration="0">
                                            <TooltipTrigger as-child>
                                                <div class="h-12 w-full flex items-end rounded-sm bg-muted/20 overflow-hidden">
                                                    <div 
                                                        class="w-2/3 mx-auto rounded-t-sm transition-all duration-300 hover:opacity-80 cursor-help"
                                                        :class="[
                                                            month.score > 0 ? 'bg-amber-500' : 'bg-muted'
                                                        ]" 
                                                        :style="`height: ${month.punctualityHeight}%`"
                                                    ></div>
                                                </div>
                                            </TooltipTrigger>
                                            <TooltipContent side="top" class="text-xs">
                                                <p>{{ month.month }}</p>
                                                <p>{{ month.score }}% Score</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                    <span class="text-[10px] text-muted-foreground font-medium uppercase">{{ month.month }}</span>
                                </div>
                            </template>
>>>>>>> dev
                        </div>
                    </CardContent>
                </Card>

                <!-- Announcements Section -->
                <Card class="lg:col-span-2 flex flex-col">
                    <CardHeader class="pb-3">
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-sm font-medium text-muted-foreground flex items-center gap-2">
                                <Bell class="h-4 w-4" />
                                Announcements
                            </CardTitle>
                            <Link v-if="user.role === 'admin' || user.role === 'manager'" href="/announcements" class="text-xs text-primary hover:underline">
                                Manage
                            </Link>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-3 flex-1 overflow-hidden">
                        <template v-if="announcements && announcements.length > 0">
                            <div
                                v-for="announcement in announcements.slice(0, 3)"
                                :key="announcement.id"
                                class="p-3 rounded-lg border transition-colors"
                                :class="getAnnouncementClass(announcement.type)"
                            >
                                <div class="flex items-start gap-3">
                                    <component
                                        :is="getAnnouncementIcon(announcement.type)"
                                        class="h-4 w-4 mt-0.5 flex-shrink-0"
                                        :class="getAnnouncementIconClass(announcement.type)"
                                    />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium truncate">{{ announcement.title }}</p>
                                        <p class="text-xs text-muted-foreground line-clamp-1 mt-1">{{ announcement.content }}</p>
                                        <p class="text-xs text-muted-foreground mt-2">{{ announcement.created_at }}</p>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div v-else class="text-center py-6 text-sm text-muted-foreground">
                            <Bell class="h-8 w-8 mx-auto mb-2 opacity-50" />
                            <p>No announcements</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Heatmap Card - Full width to prevent overflow -->
                <Card class="lg:col-span-5">
                    <CardHeader class="pb-3">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground">
                                Attendance Overview
                            </CardTitle>
                            <div class="flex items-center gap-3 text-xs flex-wrap">
                                <div class="flex items-center gap-1">
                                    <div class="h-3 w-3 rounded-[2px] bg-sky-500"></div>
                                    <span class="text-muted-foreground">Present</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <div class="h-3 w-3 rounded-[2px] bg-amber-500"></div>
                                    <span class="text-muted-foreground">Late</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <div class="h-3 w-3 rounded-[2px] bg-red-500"></div>
                                    <span class="text-muted-foreground">Absent</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <div class="h-3 w-3 rounded-[2px] bg-gray-300 dark:bg-gray-700"></div>
                                    <span class="text-muted-foreground">Weekend</span>
                                </div>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="pt-0 overflow-hidden">
                        <TooltipProvider :delay-duration="0">
                            <div class="overflow-x-auto pb-2">
                                <!-- Heatmap grid -->
                                <div class="inline-flex gap-1 pr-4">
                                    <!-- Day labels -->
                                    <div class="flex flex-col gap-1 text-[10px] text-muted-foreground pr-2 flex-shrink-0">
                                        <div class="h-3 w-4 flex items-center justify-center font-medium">S</div>
                                        <div class="h-3 w-4 flex items-center justify-center font-medium">M</div>
                                        <div class="h-3 w-4 flex items-center justify-center font-medium">T</div>
                                        <div class="h-3 w-4 flex items-center justify-center font-medium">W</div>
                                        <div class="h-3 w-4 flex items-center justify-center font-medium">T</div>
                                        <div class="h-3 w-4 flex items-center justify-center font-medium">F</div>
                                        <div class="h-3 w-4 flex items-center justify-center font-medium">S</div>
                                    </div>

                                    <!-- Weeks -->
                                    <div
                                        v-for="(week, weekIndex) in heatmapWeeks"
                                        :key="weekIndex"
                                        class="flex flex-col gap-1 flex-shrink-0"
                                    >
                                        <template v-for="(day, dayIndex) in week" :key="dayIndex">
                                            <Tooltip v-if="day.dateStr">
                                                <TooltipTrigger as-child>
                                                    <div
                                                        class="h-3 w-3 rounded-[2px] cursor-pointer transition-all hover:ring-2 hover:ring-primary/50"
                                                        :class="getHeatmapColor(day.status)"
                                                    ></div>
                                                </TooltipTrigger>
                                                <TooltipContent side="top" class="text-xs">
                                                    <p class="font-medium">{{ day.dateStr }}</p>
                                                    <p class="text-muted-foreground">{{ getStatusLabel(day.status) }}</p>
                                                </TooltipContent>
                                            </Tooltip>
                                            <div
                                                v-else
                                                class="h-3 w-3"
                                            ></div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </TooltipProvider>
                    </CardContent>
                </Card>



                <!-- Statistics & Leave Balances Split -->
                <div class="lg:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Stats Card - Combined -->
                    <Card>
                        <CardHeader class="pb-3">
                            <CardTitle class="text-sm font-medium text-muted-foreground">Attendance</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-2 rounded-full bg-primary"></div>
                                    <span class="text-sm text-muted-foreground">Total Days</span>
                                </div>
                                <span class="text-sm font-semibold">{{ stats.total_days }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-2 rounded-full bg-amber-500"></div>
                                    <span class="text-sm text-muted-foreground">Late Days</span>
                                </div>
                                <span class="text-sm font-medium">{{ stats.late_days }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-2 rounded-full bg-green-500"></div>
                                    <span class="text-sm text-muted-foreground">Total Hours</span>
                                </div>
                                <span class="text-sm font-medium">{{ stats.total_net_hours }}h</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-2 rounded-full bg-blue-500"></div>
                                    <span class="text-sm text-muted-foreground">Avg Hours/Day</span>
                                </div>
                                <span class="text-sm font-semibold">{{ stats.average_net_hours }}h</span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Leave Balance Card -->
                    <Card>
                        <CardHeader class="pb-3">
                            <CardTitle class="text-sm font-medium text-muted-foreground">Leave Balances</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div v-for="leave in props.leaveBalances.slice(0, 4)" :key="leave.leave_type_id" class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-2 w-2 rounded-full"
                                        :class="{
                                            'bg-purple-500': leave.leave_type_code === 'SL', // Sick
                                            'bg-indigo-500': leave.leave_type_code === 'CL', // Casual
                                            'bg-pink-500': leave.leave_type_code === 'EL', // Earned
                                            'bg-gray-500': !['SL', 'CL', 'EL'].includes(leave.leave_type_code)
                                        }"
                                    ></div>
                                    <span class="text-sm text-muted-foreground">{{ leave.leave_type_name }}</span>
                                </div>
                                <div class="flex items-center gap-2">

                                    <span class="text-sm font-medium">{{ leave.available }}/{{ leave.balance }}</span>
                                </div>
                            </div>
                            <div v-if="props.leaveBalances.length === 0" class="text-center py-2 text-sm text-muted-foreground">
                                No leave data
                            </div>
                        </CardContent>
                    </Card>
                </div>

            </div>
        </div>


        <!-- Mobile Floating Clock Button -->
        <div class="fixed bottom-[4.5rem] left-1/2 -translate-x-1/2 z-40 md:hidden print:hidden">
            <button
                @click="handleClockAction"
                class="relative group flex items-center justify-center px-6 py-3 rounded-full shadow-2xl transition-all duration-300 active:scale-95"
                :class="{
                    'bg-blue-600 text-white shadow-blue-500/30': buttonStatus === 'idle',
                    'bg-amber-500 text-white shadow-amber-500/30': buttonStatus === 'working',
                    'bg-emerald-600 text-white shadow-emerald-500/30 opacity-90 cursor-default': buttonStatus === 'clocked_out'
                }"
            > 
                <!-- Ripple/Pulse Effect for Idle -->
                <span v-if="buttonStatus === 'idle'" class="absolute inset-0 rounded-full border-2 border-blue-400 opacity-0 animate-ping pointer-events-none"></span>
                <span v-if="buttonStatus === 'working'" class="absolute inset-0 rounded-full border-2 border-amber-400 opacity-0 animate-ping pointer-events-none"></span>

                <!-- Content -->
                <div class="flex items-center gap-2 whitespace-nowrap">
                    <LogIn v-if="buttonStatus === 'idle'" class="h-5 w-5" />
                    <LogOut v-else-if="buttonStatus === 'working'" class="h-5 w-5" />
                    <CheckCircle v-else class="h-5 w-5" />
                    
                    <span class="font-semibold text-sm">
                        {{ buttonStatus === 'idle' ? 'Clock In' : (buttonStatus === 'working' ? 'Clock Out' : 'Day Complete') }}
                    </span>
                </div>
            </button>
        </div>

        <!-- Clock In Modal -->
        <ClockInModal 
            v-model="showClockModal"
            :status="buttonStatus"
            :loading="isProcessing"
            :stats="stats"
            :worked-today="formattedWorkedTimeToday"
            @confirm="confirmClockAction"
        />

    </AppLayout>
</template>
