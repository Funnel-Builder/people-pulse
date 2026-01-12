<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { CalendarDays, CalendarPlus, Eye, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';

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

interface CalendarLeave {
    id: number;
    date: string;
    status: string;
    type: string;
    leave_type: string;
    reason: string;
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
    calendarLeaves: CalendarLeave[];
    leaveBalances: LeaveBalance[];
}

const props = defineProps<Props>();

const selectedDate = ref<string | null>(null);
const selectedLeaves = computed(() => {
    if (!selectedDate.value) return [];
    return props.calendarLeaves.filter(l => l.date === selectedDate.value);
});

// Pagination for leave applications
const currentPage = ref(1);
const itemsPerPage = 10;

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

// Generate calendar data
const today = new Date();
const currentMonth = ref(today.getMonth());
const currentYear = ref(today.getFullYear());

const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

const daysInMonth = computed(() => {
    return new Date(currentYear.value, currentMonth.value + 1, 0).getDate();
});

const firstDayOfMonth = computed(() => {
    return new Date(currentYear.value, currentMonth.value, 1).getDay();
});

const calendarDays = computed(() => {
    const days: { date: string; day: number; leaves: CalendarLeave[] }[] = [];
    for (let i = 1; i <= daysInMonth.value; i++) {
        const dateStr = `${currentYear.value}-${String(currentMonth.value + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
        const dayLeaves = props.calendarLeaves.filter(l => l.date === dateStr);
        days.push({ date: dateStr, day: i, leaves: dayLeaves });
    }
    return days;
});

const prevMonth = () => {
    if (currentMonth.value === 0) {
        currentMonth.value = 11;
        currentYear.value--;
    } else {
        currentMonth.value--;
    }
};

const nextMonth = () => {
    if (currentMonth.value === 11) {
        currentMonth.value = 0;
        currentYear.value++;
    } else {
        currentMonth.value++;
    }
};

const selectDay = (date: string) => {
    selectedDate.value = selectedDate.value === date ? null : date;
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'approved': return 'bg-green-500';
        case 'pending': return 'bg-yellow-500';
        case 'rejected': return 'bg-red-500';
        case 'cancelled': return 'bg-gray-400';
        default: return 'bg-gray-400';
    }
};

const getStatusBadgeVariant = (status: string) => {
    switch (status) {
        case 'approved': return 'default';
        case 'pending': return 'secondary';
        case 'rejected': return 'destructive';
        case 'cancelled': return 'outline';
        default: return 'outline';
    }
};

const capitalizeStatus = (status: string) => {
    return status.charAt(0).toUpperCase() + status.slice(1);
};
</script>

<template>
    <Head title="My Leaves" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">My Leaves</h1>
                    <p class="text-muted-foreground">View your leave history and calendar</p>
                </div>
                <Button @click="router.visit('/leaves/apply')">
                    <CalendarPlus class="mr-2 h-4 w-4" />
                    Apply for Leave
                </Button>
            </div>

            <!-- Leave Balance Cards -->
            <div v-if="leaveBalances.length" class="grid gap-4 grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
                <Card 
                    v-for="balance in leaveBalances" 
                    :key="balance.leave_type_id"
                    class="hover:shadow-md transition-shadow"
                >
                    <CardContent class="p-4">
                        <div class="text-xs text-muted-foreground uppercase tracking-wide">{{ balance.leave_type_name }}</div>
                        <div class="mt-1 flex items-baseline gap-1">
                            <span class="text-2xl font-bold">{{ balance.available }}</span>
                            <span class="text-sm text-muted-foreground">/ {{ balance.balance }}</span>
                        </div>
                        <div class="text-xs text-muted-foreground mt-1">
                            {{ balance.used }} used
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Calendar -->
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle class="flex items-center gap-2">
                                <CalendarDays class="h-5 w-5" />
                                {{ monthNames[currentMonth] }} {{ currentYear }}
                            </CardTitle>
                            <div class="flex gap-2">
                                <Button variant="outline" size="sm" @click="prevMonth">Previous</Button>
                                <Button variant="outline" size="sm" @click="nextMonth">Next</Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-7 gap-1 text-center text-xs mb-2">
                            <div class="font-semibold text-muted-foreground py-2">Sun</div>
                            <div class="font-semibold text-muted-foreground py-2">Mon</div>
                            <div class="font-semibold text-muted-foreground py-2">Tue</div>
                            <div class="font-semibold text-muted-foreground py-2">Wed</div>
                            <div class="font-semibold text-muted-foreground py-2">Thu</div>
                            <div class="font-semibold text-muted-foreground py-2">Fri</div>
                            <div class="font-semibold text-muted-foreground py-2">Sat</div>
                        </div>
                        <div class="grid grid-cols-7 gap-1">
                            <!-- Empty cells for first week -->
                            <div v-for="i in firstDayOfMonth" :key="'empty-' + i" class="h-12"></div>
                            <!-- Calendar days -->
                            <div
                                v-for="day in calendarDays"
                                :key="day.date"
                                @click="selectDay(day.date)"
                                :class="[
                                    'h-12 p-1 rounded-md border cursor-pointer transition-colors relative',
                                    selectedDate === day.date ? 'border-primary bg-accent' : 'hover:bg-accent/50',
                                ]"
                            >
                                <span class="text-sm">{{ day.day }}</span>
                                <div v-if="day.leaves.length" class="absolute bottom-1 left-1/2 -translate-x-1/2 flex gap-0.5">
                                    <div
                                        v-for="leave in day.leaves.slice(0, 3)"
                                        :key="leave.id"
                                        :class="['w-1.5 h-1.5 rounded-full', getStatusColor(leave.status)]"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <!-- Legend -->
                        <div class="flex gap-4 mt-4 text-xs">
                            <div class="flex items-center gap-1">
                                <div class="w-3 h-3 rounded-full bg-green-500"></div>
                                <span>Approved</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                                <span>Pending</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                <span>Rejected</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Selected Date Details -->
                <Card>
                    <CardHeader>
                        <CardTitle>
                            {{ selectedDate ? `Details for ${selectedDate}` : 'Select a Date' }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="selectedLeaves.length" class="space-y-4">
                            <div
                                v-for="leave in selectedLeaves"
                                :key="leave.id"
                                class="p-3 rounded-lg border"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-medium">{{ leave.leave_type }}</span>
                                    <Badge :variant="getStatusBadgeVariant(leave.status)">
                                        {{ capitalizeStatus(leave.status) }}
                                    </Badge>
                                </div>
                                <p class="text-sm text-muted-foreground">{{ leave.reason }}</p>
                                <p class="text-xs text-muted-foreground mt-1">
                                    Type: {{ leave.type === 'advance' ? 'Advance' : 'Post' }} Leave
                                </p>
                            </div>
                        </div>
                        <div v-else-if="selectedDate" class="text-muted-foreground text-sm">
                            No leaves on this date.
                        </div>
                        <div v-else class="text-muted-foreground text-sm">
                            Click on a date to view leave details.
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Leave Applications Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Recent Leave Applications</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="leaves.length" class="space-y-4">
                        <div class="rounded-md border">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b bg-muted/50">
                                        <th class="px-4 py-3 text-left text-sm font-medium">Leave Type</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium">Type</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium">Dates</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium">Status</th>
                                        <th class="px-4 py-3 text-right text-sm font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr 
                                        v-for="leave in paginatedLeaves" 
                                        :key="leave.id"
                                        class="border-b last:border-0 hover:bg-muted/30"
                                    >
                                        <td class="px-4 py-3">
                                            <span class="font-medium">{{ leave.leave_type.name }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <Badge variant="outline" class="text-xs">
                                                {{ leave.type === 'advance' ? 'Advance' : 'Post' }}
                                            </Badge>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-muted-foreground">
                                            {{ formatLeaveDates(leave.dates) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <Badge :variant="getStatusBadgeVariant(leave.status)">
                                                {{ capitalizeStatus(leave.status) }}
                                            </Badge>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <Button size="sm" variant="outline" @click="viewLeave(leave.id)">
                                                <Eye class="h-4 w-4 mr-1" />
                                                View
                                            </Button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="totalPages > 1" class="flex items-center justify-between">
                            <div class="text-sm text-muted-foreground">
                                Showing {{ (currentPage - 1) * itemsPerPage + 1 }} to {{ Math.min(currentPage * itemsPerPage, leaves.length) }} of {{ leaves.length }} entries
                            </div>
                            <div class="flex items-center gap-2">
                                <Button 
                                    variant="outline" 
                                    size="sm" 
                                    :disabled="currentPage === 1"
                                    @click="goToPage(currentPage - 1)"
                                >
                                    <ChevronLeft class="h-4 w-4" />
                                </Button>
                                <div class="flex gap-1">
                                    <Button
                                        v-for="page in totalPages"
                                        :key="page"
                                        :variant="currentPage === page ? 'default' : 'outline'"
                                        size="sm"
                                        @click="goToPage(page)"
                                    >
                                        {{ page }}
                                    </Button>
                                </div>
                                <Button 
                                    variant="outline" 
                                    size="sm" 
                                    :disabled="currentPage === totalPages"
                                    @click="goToPage(currentPage + 1)"
                                >
                                    <ChevronRight class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-muted-foreground text-center py-8">
                        No leave applications yet.
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
