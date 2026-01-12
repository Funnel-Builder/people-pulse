<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import ClockInButton from '@/components/ClockInButton.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { type BreadcrumbItem, type Attendance, type AttendanceStats, type User } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Clock, LogIn, LogOut, AlertTriangle, CheckCircle, CalendarDays, Timer, TrendingUp, Calendar } from 'lucide-vue-next';
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
    todayAttendance: Attendance | null;
    stats: AttendanceStats;
    companyStats?: CompanyStats | null;
    isWeekend: boolean;
    officeStartTime: string;
    currentTime: string;
    leaveBalances: Array<{
        leave_type_id: number;
        leave_type_name: string;
        leave_type_code: string;
        balance: number;
        used: number;
        available: number;
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

const handleClockAction = () => {
    if (buttonStatus.value === 'idle') {
        clockIn();
    } else if (buttonStatus.value === 'working') {
        clockOut();
    }
};

// Mock upcoming event - only show 1
const upcomingEvent = { id: 1, title: 'Team Meeting', date: 'Today, 3:00 PM', type: 'meeting' };

// Generate heatmap data (365 days)
const heatmapData = computed(() => {
    const today = new Date();
    const data: { date: Date; status: 'present' | 'late' | 'absent' | 'weekend' | 'future'; dateStr: string }[] = [];

    // Generate last 365 days
    for (let i = 364; i >= 0; i--) {
        const date = new Date(today);
        date.setDate(date.getDate() - i);

        const dayOfWeek = date.getDay();
        const isFuture = date > today;
        const isWeekendDay = dayOfWeek === 5 || dayOfWeek === 6; // Fri-Sat weekend for BD

        let status: 'present' | 'late' | 'absent' | 'weekend' | 'future';

        if (isFuture) {
            status = 'future';
        } else if (isWeekendDay) {
            status = 'weekend';
        } else {
            // Random status for demo
            const rand = Math.random();
            if (rand > 0.9) status = 'absent';
            else if (rand > 0.8) status = 'late';
            else status = 'present';
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
        case 'weekend': return 'Weekend';
        case 'future': return '';
        default: return 'No Data';
    }
};
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

            <!-- Welcome -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold">Welcome, {{ user.name.split(' ')[0] }}</h1>
                    <p class="text-sm text-muted-foreground">{{ formattedDate }} • {{ formattedTime }}</p>
                </div>
            </div>

            <!-- Bento Grid Layout -->
            <div class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-8">

                <!-- Clock In/Out Card -->
                <Card class="lg:col-span-2 bg-gradient-to-br from-primary/5 to-primary/10 border-primary/20">
                    <CardContent class="p-6 h-full flex flex-col justify-center items-center">
                        <div class="text-center space-y-4">


                            <div>
                                <div v-if="isWeekend" class="text-sm text-muted-foreground">
                                    Weekend
                                </div>
                                <div v-else-if="currentStatus === 'working'" class="text-sm text-green-600 dark:text-green-400 flex items-center gap-2 justify-center">
                                    <span class="h-2 w-2 rounded-full bg-green-500 animate-pulse"></span>
                                    Working
                                </div>
                                <div v-else-if="currentStatus === 'clocked_out'" class="text-sm text-muted-foreground">
                                    Day Complete
                                </div>
                                <div v-else class="text-sm text-muted-foreground">
                                    Ready to Clock In
                                </div>
                            </div>

                            <ClockInButton
                                :status="buttonStatus"
                                :loading="isProcessing"
                                @click="handleClockAction"
                            />
                        </div>
                    </CardContent>
                </Card>

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
                                }"
                                class="text-xs"
                            >
                                {{ todayAttendance?.is_late ? 'Late' : (todayAttendance?.clock_in ? 'On Time' : 'Not Marked') }}
                            </Badge>
                        </div>
                    </CardContent>
                </Card>

                <!-- Upcoming Event Card - Single event -->
                <Card class="lg:col-span-4">
                    <CardContent class="p-4 h-full flex items-center">
                        <div class="flex items-center gap-4 w-full">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary/10 flex-shrink-0">
                                <Calendar class="h-5 w-5 text-primary" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-muted-foreground">Next Event</p>
                                <p class="font-medium truncate">{{ upcomingEvent.title }}</p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="text-sm text-muted-foreground">{{ upcomingEvent.date }}</p>
                            </div>
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
                    <Card class="bg-gradient-to-br from-background to-muted/20">
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
                    <Card class="bg-gradient-to-br from-background to-muted/20">
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
    </AppLayout>
</template>
