<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { 
    CalendarPlus, 
    Eye, 
    ChevronLeft, 
    ChevronRight,
    Briefcase,
    AlertCircle,
    Coffee,
    Clock,
    Calendar as CalendarIcon 
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'My Leaves', href: '/leaves' },
];

interface LeaveDate {
    id: number;
    date: string;
}

interface LeaveType {
    id: number;
    name: string;
    code: string;
}

interface Leave {
    id: number;
    type: 'advance' | 'post';
    status: 'pending' | 'approved' | 'rejected' | 'cancelled';
    reason: string;
    dates: LeaveDate[];
    leave_type: LeaveType;
    created_at: string;
}

interface LeaveBalance {
    leave_type_id: number;
    leave_type_name: string;
    leave_type_code: string;
    balance: number;
    used: number;
    available: number;
}

interface Props {
    leaves: Leave[];
    leaveBalances: LeaveBalance[];
}

const props = defineProps<Props>();

// Pagination for leave applications
const currentPage = ref(1);
const itemsPerPage = 5; // Fixed to 5 as requested

// Sort leaves by created_at descending (latest first)
const sortedLeaves = computed(() => {
    return [...props.leaves].sort((a, b) => {
        return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
    });
});

const totalPages = computed(() => Math.ceil(sortedLeaves.value.length / itemsPerPage));
const paginatedLeaves = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    return sortedLeaves.value.slice(start, start + itemsPerPage);
});

const goToPage = (page: number) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

// Format date for display
const formatLeaveDate = (dateStr: string) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric',
        year: 'numeric'
    });
};

// Format multiple dates nicely
const formatLeaveDates = (dates: LeaveDate[]) => {
    if (dates.length === 1) {
        return formatLeaveDate(dates[0].date);
    }
    if (dates.length === 2) {
        return `${formatLeaveDate(dates[0].date)} & ${formatLeaveDate(dates[1].date)}`;
    }
    // For more than 2 dates, show first and count
    return `${formatLeaveDate(dates[0].date)} + ${dates.length - 1} more`;
};

// View leave details
const viewLeave = (leaveId: number) => {
    router.visit(`/leaves/${leaveId}`);
};

const getStatusBadgeVariant = (status: string) => {
    switch (status) {
        case 'approved': return 'default'; // Greenish usually
        case 'pending': return 'secondary'; // Yellowish/Gray
        case 'rejected': return 'destructive'; // Red
        case 'cancelled': return 'outline';
        default: return 'outline';
    }
};

const capitalizeStatus = (status: string) => {
    return status.charAt(0).toUpperCase() + status.slice(1);
};

// Colors for stats cards based on type (simple mapping based on name or code)
const getCardIcon = (code: string) => {
    const lowerCode = code.toLowerCase();
    if (lowerCode.includes('sick')) return AlertCircle;
    if (lowerCode.includes('casual')) return Coffee;
    if (lowerCode.includes('annual') || lowerCode.includes('earned')) return Briefcase;
    return Clock;
};

const getCardColorClass = (code: string) => {
    const lowerCode = code.toLowerCase();
    if (lowerCode.includes('sick')) return 'text-red-600 bg-red-50 border-red-100';
    if (lowerCode.includes('casual')) return 'text-amber-600 bg-amber-50 border-amber-100';
    if (lowerCode.includes('annual') || lowerCode.includes('earned')) return 'text-blue-600 bg-blue-50 border-blue-100';
    return 'text-slate-600 bg-slate-50 border-slate-100';
};

// Demo data for Planning Calendar
const planningEvents = [
    { date: '25', month: 'DEC', title: 'Christmas Day', type: 'Public Holiday', isHoliday: true },
    { date: '01', month: 'JAN', title: "New Year's Day", type: 'Public Holiday', isHoliday: true },
    { date: '21', month: 'FEB', title: 'Intl. Mother Language Day', type: 'Public Holiday', isHoliday: true },
    { date: '26', month: 'MAR', title: 'Independence Day', type: 'Public Holiday', isHoliday: true },
    { date: '14', month: 'APR', title: 'Bengali New Year', type: 'Public Holiday', isHoliday: true },
];

</script>

<template>
    <Head title="My Leaves" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 bg-slate-50/50">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">Leave Management</h1>
                    <p class="text-muted-foreground">Plan your time off and monitor availability.</p>
                </div>
                <Button @click="router.visit('/leaves/apply')" class="shadow-sm">
                    <CalendarPlus class="mr-2 h-4 w-4" />
                    Apply for Leave
                </Button>
            </div>

            <!-- Leave Balance Cards -->
            <div v-if="leaveBalances.length" class="grid gap-4 grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
                <Card 
                    v-for="balance in leaveBalances" 
                    :key="balance.leave_type_id"
                    class="hover:shadow-md transition-all duration-200 border-none shadow-sm relative group bg-white"
                >
                    <CardContent class="p-4 flex flex-col h-full justify-between">
                        <div class="flex items-start justify-between mb-2">
                            <div class="space-y-1">
                                <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">
                                    {{ balance.leave_type_name }}
                                </p>
                                <div class="flex items-baseline gap-1">
                                    <span class="text-2xl font-bold tracking-tight text-foreground">{{ balance.available }}</span>
                                    <span class="text-xs font-medium text-muted-foreground">/ {{ balance.balance }}</span>
                                </div>
                            </div>
                            <div :class="['p-2 rounded-lg transition-colors', getCardColorClass(balance.leave_type_code)]">
                                <component :is="getCardIcon(balance.leave_type_code)" class="h-4 w-4" />
                            </div>
                        </div>
                        
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-[10px] font-medium">
                                <span class="text-muted-foreground">Used: {{ balance.used }}</span>
                                <span :class="balance.available < 3 ? 'text-red-500' : 'text-green-500'">
                                    {{ Math.round((balance.available / balance.balance) * 100) }}% Left
                                </span>
                            </div>
                            <div class="h-1.5 w-full bg-secondary/50 rounded-full overflow-hidden">
                                <div 
                                    class="h-full rounded-full transition-all duration-500" 
                                    :class="getCardColorClass(balance.leave_type_code).split(' ')[0].replace('text-', 'bg-')"
                                    :style="{ width: `${(balance.used / balance.balance) * 100}%` }"
                                ></div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Bottom Section: Recent Applications & Planning Calendar -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Recent Leave Applications (2/3 width) -->
                <Card class="lg:col-span-2 shadow-sm flex flex-col h-[520px]">
                    <CardHeader class="border-b pb-4">
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-lg font-semibold flex items-center gap-2">
                                Recent Leave Requests
                            </CardTitle>
                            <Badge variant="outline" class="font-normal">
                                Total: {{ leaves.length }}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent class="p-0 flex flex-col flex-1 overflow-hidden">
                        <div class="flex-1 overflow-auto">
                            <!-- Fixed height table container -->
                            <table class="w-full">
                                <thead class="bg-muted/50 sticky top-0 z-10">
                                    <tr class="text-left text-xs uppercase tracking-wider text-muted-foreground font-medium border-b">
                                        <th class="px-6 py-4 w-[25%]">Leave Type</th>
                                        <th class="px-6 py-4 w-[30%]">Dates</th>
                                        <th class="px-6 py-4 w-[15%]">Duration</th>
                                        <th class="px-6 py-4 w-[15%]">Status</th>
                                        <th class="px-6 py-4 w-[15%] text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr 
                                        v-for="leave in paginatedLeaves" 
                                        :key="leave.id"
                                        class="hover:bg-muted/30 transition-colors group"
                                    >
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div :class="['w-2 h-2 rounded-full', getCardColorClass(leave.leave_type.code).split(' ')[0].replace('text-', 'bg-')]"></div>
                                                <span class="font-medium text-sm text-foreground">{{ leave.leave_type.name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm font-medium text-foreground">{{ formatLeaveDates(leave.dates) }}</span>
                                            <div class="text-xs text-muted-foreground mt-0.5" v-if="leave.type === 'advance'">Advance Appl.</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm text-foreground">{{ leave.dates.length }} Days</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <Badge :variant="getStatusBadgeVariant(leave.status)" class="capitalize font-medium shadow-none">
                                                {{ capitalizeStatus(leave.status) }}
                                            </Badge>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <Button size="icon" variant="ghost" class="h-8 w-8 opacity-0 group-hover:opacity-100 transition-opacity" @click="viewLeave(leave.id)">
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                        </td>
                                    </tr>
                                    <!-- Empty state filler to maintain height if needed, or just message -->
                                    <tr v-if="paginatedLeaves.length === 0">
                                        <td colspan="5" class="px-6 py-12 text-center text-muted-foreground">
                                            No recent leave applications found.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination fixed at bottom -->
                        <div class="p-4 border-t mt-auto bg-white" v-if="totalPages > 0">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-muted-foreground">
                                    Showing {{ (currentPage - 1) * itemsPerPage + 1 }} - {{ Math.min(currentPage * itemsPerPage, leaves.length) }} of {{ leaves.length }}
                                </span>
                                <div class="flex gap-2">
                                    <Button 
                                        variant="outline" 
                                        size="icon"
                                        class="h-8 w-8" 
                                        :disabled="currentPage === 1"
                                        @click="goToPage(currentPage - 1)"
                                    >
                                        <ChevronLeft class="h-4 w-4" />
                                    </Button>
                                    <Button 
                                        variant="outline" 
                                        size="icon"
                                        class="h-8 w-8" 
                                        :disabled="currentPage === totalPages"
                                        @click="goToPage(currentPage + 1)"
                                    >
                                        <ChevronRight class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Planning Calendar (1/3 width) -->
                <Card class="shadow-sm flex flex-col h-[520px]">
                    <CardHeader class="pb-4 border-b">
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-lg font-semibold">Planning Calendar</CardTitle>
                            <Button variant="ghost" size="sm" class="text-xs h-7 text-primary hover:text-primary/80">
                                View All
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent class="p-0 flex-1 overflow-auto bg-slate-50/30">
                        <div class="divide-y">
                            <div v-for="(event, index) in planningEvents" :key="index" class="p-4 hover:bg-white transition-colors group">
                                <div class="flex items-start gap-4">
                                    <div class="flex flex-col items-center justify-center bg-white border rounded-xl w-14 h-14 shadow-sm group-hover:border-primary/20 group-hover:shadow-md transition-all">
                                        <span class="text-[0.65rem] font-bold uppercase text-muted-foreground">{{ event.month }}</span>
                                        <span class="text-xl font-bold text-foreground">{{ event.date }}</span>
                                    </div>
                                    <div class="flex-1 py-1">
                                        <h4 class="font-medium text-sm text-foreground mb-1">{{ event.title }}</h4>
                                        <Badge variant="secondary" class="font-normal text-xs bg-slate-100 text-slate-600">{{ event.type }}</Badge>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Ensure consistent height even with empty content if needed */
</style>
