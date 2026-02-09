<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Separator } from '@/components/ui/separator';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import {
    Printer,
    Download,
    Mail,
    Phone,
    MapPin,
    Calendar,
    Briefcase,
    Shield,
    Clock,
    UserCheck,
    UserX,
    AlertTriangle,
    TrendingUp,
    FileText,
    History,
    Timer
} from 'lucide-vue-next';
import { computed } from 'vue';

interface EmployeeDetails {
    id: number;
    name: string;
    employee_id: string;
    designation: string;
    department: string;
    sub_department: string;
    joining_date: string;
    email: string;
    phone: string;
    profile_picture: string | null;
    status: string;
}

interface AttendanceStats {
    total_present: number;
    total_late: number;
    total_absent: number;
    avg_working_hours: number;
}

interface AttendanceRecord {
    date: string;
    status: string;
    clock_in: string | null;
    clock_out: string | null;
    is_late: boolean;
    net_minutes: number;
}

interface LeaveStatItem {
    type: string;
    count: number;
}

interface CoverHistoryItem {
    date: string;
    covered_for: string;
    type: string;
}

interface Props {
    employee: EmployeeDetails;
    attendanceStats: AttendanceStats;
    attendanceHistory: Record<string, AttendanceRecord>;
    leaveStats: {
        total_leaves_taken: number;
        rejected_requests: number;
        pending_requests: number;
    };
    leaveBreakdown: LeaveStatItem[];
    coverHistory: {
        count: number;
        recent: CoverHistoryItem[];
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Employee Reports', href: '/reports/employees' },
    { title: props.employee.name, href: '#' },
];

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
};

const handlePrint = () => {
    // Open the PDF export route in a new window
    window.open(`/reports/employees/${props.employee.id}/export`, '_blank');
};

// --- Chart Logic Ported from Dashboard.vue ---

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

    // Get last 12 months including current
    const today = new Date();
    for (let i = 11; i >= 0; i--) {
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
    if (props.attendanceHistory) {
        Object.values(props.attendanceHistory).forEach(record => {
            const d = new Date(record.date);
            const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
            
            if (monthlyData[key]) {
                 if (record.net_minutes) {
                    monthlyData[key].totalMinutes += record.net_minutes;
                 } else if (record.clock_in && record.clock_out) {
                    const start = new Date(record.clock_in);
                    const end = new Date(record.clock_out);
                    const duration = (end.getTime() - start.getTime()) / 1000 / 60;
                    monthlyData[key].totalMinutes += duration;
                 }
                 
                 if (record.status !== 'weekend' && record.status !== 'holiday') {
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
    }

    // Convert to Array
    return Object.values(monthlyData).map(m => {
        const hours = Math.floor(m.totalMinutes / 60);
        
        // Performance Score Calculation
        // Formula: 100 - (Absent * 10) - (Late * 2)
        let score = 0;
        if (m.totalEntries > 0) {
            score = 100 - (m.absent * 10) - (m.late * 2);
            if (score < 0) score = 0;
            if (score > 100) score = 100;
        }
        
        // Work Hours Height Scale (Max 200h/mo?)
        let workHeight = (hours / 200) * 100;
        if (workHeight > 100) workHeight = 100;
        if (workHeight < 15) workHeight = 15;

        // Performance Height
        let punctualityHeight = score;
        if (punctualityHeight < 15) punctualityHeight = 15;

        return {
            month: m.monthLabel,
            year: m.year,
            monthIndex: m.month,
            hours,
            score: Math.round(score),
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
    
    if (previous.hours === 0 && current.hours === 0) return 0;
    if (previous.hours === 0) return 100;
    
    const diff = ((current.hours - previous.hours) / previous.hours) * 100;
    return Math.round(diff);
});

const punctualityTrend = computed(() => {
    const trends = monthlyTrends.value;
    if (trends.length < 2) return 0;
    
    const current = trends[trends.length - 1];
    const previous = trends[trends.length - 2];
    
    if (current.score === 0) return 0;

    return current.score - previous.score;
});

const averageMonthlyWorkHours = computed(() => {
    const trends = monthlyTrends.value;
    if (trends.length <= 1) return 0; // Need at least 1 previous month
    
    // Create copy and remove last (current) month
    const previousMonths = trends.slice(0, -1);
    
    // Filter months with 0 hours
    const activeMonths = previousMonths.filter(m => m.hours > 0);
    if (activeMonths.length === 0) return 0;
    
    const totalHours = activeMonths.reduce((sum, item) => sum + item.hours, 0);
    return Math.round(totalHours / activeMonths.length);
});

const averagePerformanceScore = computed(() => {
    const trends = monthlyTrends.value;
    if (trends.length <= 1) return 0;
    
    // Create copy and remove last (current) month
    const previousMonths = trends.slice(0, -1);
    
    // Filter months with 0 score
    const activeMonths = previousMonths.filter(m => m.score > 0);
    if (activeMonths.length === 0) return 0;

    const totalScore = activeMonths.reduce((sum, item) => sum + item.score, 0);
    return Math.round(totalScore / activeMonths.length);
});
</script>

<template>
    <Head :title="`${employee.name} - Lifetime Report`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-8 p-4 md:p-8 min-h-screen bg-gray-50/30 dark:bg-gray-900/30 print:bg-white print:p-0">
            
            <!-- Header Actions -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 print:hidden">
                <div class="space-y-1">
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        Lifetime Report
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400">
                        {{ employee.name }}'s history and performance.
                    </p>
                </div>
                <div class="flex gap-3 print:hidden">
                     <Button variant="outline" @click="handlePrint">
                        <Printer class="h-4 w-4 mr-2" />
                        Print Report
                    </Button>
                    <!--
                    <Link :href="`/reports/employees/${employee.employee_id}/export`">
                        <Button>
                            <Download class="h-4 w-4 mr-2" />
                            Download PDF
                        </Button>
                    </Link>
                    -->
                </div>
            </div>

            <!-- Profile Hero Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 print:block print:space-y-8">
                <!-- Left Column: Profile Card -->
                <Card class="lg:col-span-1 border-0 shadow-lg bg-gradient-to-b from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 overflow-hidden print:border print:shadow-none print:break-inside-avoid">
                     <div class="h-32 bg-blue-600/10 dark:bg-blue-900/20 relative">
                        <div class="absolute top-4 right-4">
                            <Badge variant="outline" :class="employee.status === 'Active' ? 'bg-green-100 text-green-700 border-green-200' : 'bg-gray-100 text-gray-700'">
                                {{ employee.status === 'Active' ? 'Current' : employee.status }}
                            </Badge>
                        </div>
                     </div>
                     <CardContent class="relative pt-0 px-6 pb-8">
                        <div class="flex flex-col items-center -mt-16 text-center">
                            <Avatar class="h-32 w-32 border-4 border-white dark:border-gray-800 shadow-xl mb-4">
                                <AvatarImage 
                                    :src="employee.profile_picture ? `/storage/${employee.profile_picture}` : ''" 
                                    :alt="employee.name" 
                                    class="object-cover"
                                />
                                <AvatarFallback class="text-3xl bg-blue-50 text-blue-600 font-bold">
                                    {{ getInitials(employee.name) }}
                                </AvatarFallback>
                            </Avatar>
                            
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">
                                {{ employee.name }}
                            </h2>
                            <p class="text-blue-600 dark:text-blue-400 font-medium mb-4 flex items-center gap-1.5">
                                <Briefcase class="h-4 w-4" />
                                {{ employee.designation }}
                            </p>

                            <div class="w-full grid grid-cols-1 gap-4 mt-4 text-left">
                                <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                                    <div class="flex items-center gap-3 text-gray-500">
                                        <div class="p-2 bg-gray-50 dark:bg-gray-700 rounded-md">
                                            <Shield class="h-4 w-4" />
                                        </div>
                                        <span class="text-sm font-medium">ID</span>
                                    </div>
                                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100">
                                        {{ employee.employee_id }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                                    <div class="flex items-center gap-3 text-gray-500">
                                        <div class="p-2 bg-gray-50 dark:bg-gray-700 rounded-md">
                                            <Calendar class="h-4 w-4" />
                                        </div>
                                        <span class="text-sm font-medium">Joined</span>
                                    </div>
                                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100">
                                        {{ employee.joining_date }}
                                    </span>
                                </div>
                                
                                <div class="border-t border-gray-100 dark:border-gray-700 my-2"></div>

                                <div class="space-y-3">
                                    <div class="flex items-center gap-3 text-sm">
                                        <Mail class="h-4 w-4 text-gray-400" />
                                        <span class="text-gray-600 dark:text-gray-300">{{ employee.email }}</span>
                                    </div>
                                    <div class="flex items-center gap-3 text-sm">
                                        <Phone class="h-4 w-4 text-gray-400" />
                                        <span class="text-gray-600 dark:text-gray-300">{{ employee.phone }}</span>
                                    </div>
                                    <div class="flex items-center gap-3 text-sm">
                                        <MapPin class="h-4 w-4 text-gray-400" />
                                        <span class="text-gray-600 dark:text-gray-300">
                                            {{ employee.department }}
                                            <span v-if="employee.sub_department" class="text-gray-400">
                                                • {{ employee.sub_department }}
                                            </span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                     </CardContent>
                </Card>

                <!-- Right Column: Stats & Charts -->
                <div class="lg:col-span-2 space-y-8">
                    
                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <Card class="border shadow-sm bg-white dark:bg-gray-900 overflow-hidden hover:shadow-md transition-shadow relative">
                            <div class="absolute bottom-0 left-0 right-0 h-1 bg-emerald-500"></div>
                            <CardContent class="p-6 flex flex-col items-center justify-center text-center gap-1">
                                <span class="text-3xl font-bold text-gray-900 dark:text-gray-100 tracking-tight">
                                    {{ attendanceStats.total_present }}
                                </span>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Present
                                </p>
                            </CardContent>
                        </Card>

                        <Card class="border shadow-sm bg-white dark:bg-gray-900 overflow-hidden hover:shadow-md transition-shadow relative">
                            <div class="absolute bottom-0 left-0 right-0 h-1 bg-amber-500"></div>
                            <CardContent class="p-6 flex flex-col items-center justify-center text-center gap-1">
                                <span class="text-3xl font-bold text-gray-900 dark:text-gray-100 tracking-tight">
                                    {{ attendanceStats.total_late }}
                                </span>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Late
                                </p>
                            </CardContent>
                        </Card>

                        <Card class="border shadow-sm bg-white dark:bg-gray-900 overflow-hidden hover:shadow-md transition-shadow relative">
                            <div class="absolute bottom-0 left-0 right-0 h-1 bg-rose-500"></div>
                            <CardContent class="p-6 flex flex-col items-center justify-center text-center gap-1">
                                <span class="text-3xl font-bold text-gray-900 dark:text-gray-100 tracking-tight">
                                    {{ attendanceStats.total_absent }}
                                </span>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Absent
                                </p>
                            </CardContent>
                        </Card>

                        <Card class="border shadow-sm bg-white dark:bg-gray-900 overflow-hidden hover:shadow-md transition-shadow relative">
                            <div class="absolute bottom-0 left-0 right-0 h-1 bg-slate-500"></div>
                            <CardContent class="p-6 flex flex-col items-center justify-center text-center gap-1">
                                <span class="text-3xl font-bold text-gray-900 dark:text-gray-100 tracking-tight">
                                    {{ attendanceStats.avg_working_hours }}h
                                </span>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Avg Hours
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- New Stats Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 print:grid-cols-2">
                        <!-- Work Hours Card -->
                        <Card class="print:shadow-none print:border print:break-inside-avoid">
                            <CardHeader class="pb-3">
                                 <div class="flex items-center justify-between">
                                    <CardTitle class="text-sm font-medium text-muted-foreground flex items-center gap-2">
                                        <Timer class="h-4 w-4" />
                                        Work Hours
                                    </CardTitle>
                                </div>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="flex items-baseline gap-1">
                                    <p class="text-2xl font-bold tracking-tight">{{ averageMonthlyWorkHours }}h</p>
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
                                                                    month.workHeight > 20 ? 'bg-blue-500 dark:bg-blue-600' : 'bg-gray-200 dark:bg-gray-700'
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
                                            <div class="flex flex-col items-center">
                                                <span class="text-[10px] text-muted-foreground font-medium uppercase">{{ month.month }}</span>
                                                <span class="text-[9px] text-muted-foreground">{{ month.hours }}h</span>
                                            </div>
                                        </div>
                                    </template>
                                 </div>
                            </CardContent>
                        </Card>

                        <!-- Performance Card -->
                        <Card class="print:shadow-none print:border print:break-inside-avoid">
                             <CardHeader class="pb-3">
                                 <div class="flex items-center justify-between">
                                    <CardTitle class="text-sm font-medium text-muted-foreground flex items-center gap-2">
                                        <TrendingUp class="h-4 w-4" />
                                        Performance
                                    </CardTitle>
                                </div>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="flex items-baseline gap-1">
                                     <p class="text-2xl font-bold tracking-tight">{{ averagePerformanceScore }}%</p>
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
                                                                    month.score > 0 ? 'bg-amber-500' : 'bg-gray-200 dark:bg-gray-700'
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
                                            <div class="flex flex-col items-center">
                                                <span class="text-[10px] text-muted-foreground font-medium uppercase">{{ month.month }}</span>
                                                <span class="text-[9px] text-muted-foreground">{{ month.score }}%</span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Leave History -->
                        <Card class="h-full border shadow-sm">
                            <CardHeader>
                                <CardTitle class="text-lg flex items-center gap-2">
                                    <FileText class="h-5 w-5 text-purple-500" />
                                    Leave Overview
                                </CardTitle>
                                <CardDescription>
                                    Lifetime leave history breakdown
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-6">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Leaves Taken</span>
                                        <span class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ leaveStats.total_leaves_taken }}</span>
                                    </div>
                                    <Separator />
                                    <div class="space-y-3">
                                        <div v-for="leave in leaveBreakdown" :key="leave.type" class="flex items-center justify-between text-sm">
                                             <div class="flex items-center gap-2">
                                                <div class="w-2 h-2 rounded-full bg-purple-500"></div>
                                                <span>{{ leave.type }}</span>
                                             </div>
                                             <span class="font-medium">{{ leave.count }}</span>
                                        </div>
                                        <div v-if="leaveBreakdown.length === 0" class="text-sm text-gray-500 italic">
                                            No leaves taken yet.
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-lg flex justify-between items-center text-xs">
                                        <span class="text-gray-500">Pending Requests</span>
                                        <Badge variant="outline" class="bg-yellow-50 text-yellow-700 border-yellow-200">
                                            {{ leaveStats.pending_requests }}
                                        </Badge>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Cover History -->
                        <Card class="h-full border shadow-sm">
                            <CardHeader>
                                <CardTitle class="text-lg flex items-center gap-2">
                                    <History class="h-5 w-5 text-indigo-500" />
                                    Cover History
                                </CardTitle>
                                <CardDescription>
                                    Covered for other employees ({{ coverHistory.count }} times)
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <div v-for="(cover, index) in coverHistory.recent" :key="index" class="flex items-start gap-4 pb-4 border-b border-gray-100 dark:border-gray-800 last:border-0 last:pb-0">
                                        <div class="mt-1 p-1.5 bg-indigo-50 dark:bg-indigo-900/20 rounded-full text-indigo-600 dark:text-indigo-400">
                                            <UserCheck class="h-3 w-3" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                Covered for {{ cover.covered_for }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                {{ cover.type }} • {{ cover.date }}
                                            </p>
                                        </div>
                                    </div>
                                    <div v-if="coverHistory.recent.length === 0" class="flex flex-col items-center justify-center py-6 text-center text-gray-500">
                                        <p class="text-sm italic">No cover history found.</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                </div>
            </div>
            

        </div>
    </AppLayout>
</template>

<style scoped>
@media print {
    /* Hide all layout elements */
    :deep(nav), :deep(aside), :deep(header), :deep(footer) {
        display: none !important;
    }
    /* Specifically target MobileNav and AppSidebarHeader */
    :deep(.mobile-nav), :deep(.app-header), :deep(.sidebar), :deep(.sidebar-rail) {
        display: none !important;
    }
    :deep(.print\:hidden) {
        display: none !important;
    }
    /* Reset main content margins/padding */
    :deep(main), :deep(.app-content) {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }
    /* Remove browser headers/footers */
    @page {
        margin: 1cm;
        size: auto;
    }
    :deep(.print\:block) {
        display: block !important;
    }
    :deep(.print\:grid-cols-2) {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }
    :deep(.print\:space-y-8 > :not([hidden]) ~ :not([hidden])) {
        --tw-space-y-reverse: 0;
        margin-top: calc(2rem * calc(1 - var(--tw-space-y-reverse)));
        margin-bottom: calc(2rem * var(--tw-space-y-reverse));
    }
    :deep(.print\:shadow-none) {
        box-shadow: none !important;
    }
    :deep(.print\:border) {
        border-width: 1px !important;
        border-style: solid !important;
        border-color: #e5e7eb !important; /* gray-200 */
    }
    :deep(.print\:break-inside-avoid) {
        break-inside: avoid !important;
    }
    body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        background-color: white !important;
    }
    /* Ensure charts use light mode colors if dark mode is active */
    .dark .bg-gray-900 {
        background-color: white !important;
        color: black !important;
    }
    .dark .text-gray-100 {
        color: #111827 !important; /* gray-900 */
    }
    .dark .text-gray-400 {
        color: #6b7280 !important; /* gray-500 */
    }

    /* Force background colors to print */
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    /* Specific overrides for chart bars to ensure visibility */
    :deep(.bg-blue-500), :deep(.bg-blue-600) {
        background-color: #3b82f6 !important;
        border: 1px solid #2563eb !important;
    }
    :deep(.bg-amber-500) {
        background-color: #f59e0b !important;
        border: 1px solid #d97706 !important;
    }
    :deep(.bg-gray-200) {
        background-color: #e5e7eb !important;
        border: 1px solid #d1d5db !important;
    }
}
</style>
