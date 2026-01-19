<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ArrowLeft, Calendar, User, Clock, CheckCircle, XCircle } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

interface LeaveDate {
    id: number;
    date: string;
}

interface LeaveType {
    id: number;
    name: string;
    code: string;
}

interface UserInfo {
    id: number;
    name: string;
    employee_id: string;
    designation: string;
}

interface LeaveApproval {
    id: number;
    step: number;
    approver_type: string;
    status: string;
    comment: string | null;
    acted_at: string | null;
    approver: UserInfo | null;
}

interface Leave {
    id: number;
    type: 'advance' | 'post';
    status: string;
    reason: string;
    current_approval_step: number;
    dates: LeaveDate[];
    leave_type: LeaveType;
    user: UserInfo;
    cover_person: UserInfo | null;
    approvals: LeaveApproval[];
    created_at: string;
}

interface Props {
    leave: Leave;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'My Leaves', href: '/leaves' },
    { title: `Request #${props.leave.id}`, href: `/leaves/${props.leave.id}` },
];

const formatDate = (dateStr: string) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { 
        weekday: 'long', 
        month: 'long', 
        day: 'numeric',
        year: 'numeric'
    });
};

const formatDateTime = (dateStr: string) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
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

const getApprovalStatusColor = (status: string) => {
    switch (status) {
        case 'approved': return 'text-green-600';
        case 'rejected': return 'text-red-600';
        case 'pending': return 'text-yellow-600';
        default: return 'text-gray-400';
    }
};

const getApprovalStatusIcon = (status: string) => {
    switch (status) {
        case 'approved': return CheckCircle;
        case 'rejected': return XCircle;
        default: return Clock;
    }
};

const getApproverTypeLabel = (type: string) => {
    switch (type) {
        case 'cover_person': return 'Cover Person';
        case 'manager': return 'Manager';
        case 'admin': return 'Admin';
        default: return type;
    }
};

const capitalizeStatus = (status: string) => {
    return status.charAt(0).toUpperCase() + status.slice(1);
};

const goBack = () => {
    router.visit('/leaves');
};

const cancelLeave = () => {
    if (confirm('Are you sure you want to cancel this leave application?')) {
        router.post(`/leaves/${props.leave.id}/cancel`);
    }
};

const getCardColorClass = (code: string) => {
    const lowerCode = code.toLowerCase();
    if (lowerCode.includes('sick')) return 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border-red-100 dark:border-red-800';
    if (lowerCode.includes('casual')) return 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 border-amber-100 dark:border-amber-800';
    if (lowerCode.includes('annual') || lowerCode.includes('earned')) return 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 border-blue-100 dark:border-blue-800';
    return 'text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/50 border-slate-100 dark:border-slate-800';
};

const formatLeaveDateRange = (dates: LeaveDate[]) => {
    if (!dates.length) return '';
    
    // Sort dates to find start and end
    const sortedDates = [...dates].sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime());
    const startDate = new Date(sortedDates[0].date);
    const endDate = new Date(sortedDates[sortedDates.length - 1].date);

    const options: Intl.DateTimeFormatOptions = { weekday: 'short', month: 'short', day: '2-digit' };
    const startStr = startDate.toLocaleDateString('en-US', options);
    
    if (dates.length === 1) {
        return `${startStr}, ${startDate.getFullYear()}`;
    }

    const endStr = endDate.toLocaleDateString('en-US', options);
    return `${startStr} — ${endStr}, ${endDate.getFullYear()}`;
};
</script>

<template>
    <Head :title="`Leave Request #${leave.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="icon" @click="goBack">
                    <ArrowLeft class="h-5 w-5" />
                </Button>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold">Leave Request Details</h1>
                    <p class="text-muted-foreground">Application #{{ leave.id }}</p>
                </div>
                <Badge :variant="getStatusBadgeVariant(leave.status)" class="text-sm px-3 py-1">
                    {{ capitalizeStatus(leave.status) }}
                </Badge>
            </div>

            <!-- Two Column Layout -->
            <div class="grid gap-6 lg:grid-cols-[1fr_400px]">
                <!-- Left Column: Leave Information -->
                <div class="space-y-6">
                    <!-- Leave Information -->
                    <Card class="border-border/50 shadow-sm">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-[15px] font-semibold flex items-center gap-2">
                                Leave Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="pt-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-y-8 gap-x-4">
                                <div class="space-y-3">
                                    <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Leave Type</p>
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 rounded-lg bg-secondary/50">
                                            <div :class="['w-2.5 h-2.5 rounded-full', getCardColorClass(leave.leave_type.code).split(' ')[0].replace('text-', 'bg-')]"></div>
                                        </div>
                                        <span class="font-semibold text-base">{{ leave.leave_type.name }}</span>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Application Type</p>
                                    <Badge variant="secondary" class="bg-secondary/50 text-secondary-foreground font-medium px-3 py-1 rounded-md text-sm border-none">
                                        {{ leave.type === 'advance' ? 'Advance Leave' : 'Post Leave' }}
                                    </Badge>
                                </div>

                                <div class="space-y-3">
                                    <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Applied On</p>
                                    <div class="flex items-center gap-2 text-sm font-medium">
                                        <Calendar class="h-4 w-4 text-muted-foreground" />
                                        <span>{{ formatDateTime(leave.created_at) }}</span>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Duration ({{ leave.dates.length }} Days)</p>
                                    <div class="flex items-center gap-2 text-sm font-medium">
                                        <Calendar class="h-4 w-4 text-muted-foreground" />
                                        <span>{{ formatLeaveDateRange(leave.dates) }}</span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>



                    <!-- Reason -->
                    <Card class="border-border/50 shadow-sm">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-[15px] font-semibold">
                                Reason for Leave
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="bg-blue-50/50 dark:bg-blue-900/10 border-l-4 border-blue-500 rounded-r-lg p-4 mt-2">
                                <p class="text-sm leading-relaxed text-muted-foreground italic">
                                    "{{ leave.reason }}"
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Cover Person -->
                    <Card v-if="leave.cover_person">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <User class="h-5 w-5" />
                                Cover Person
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-center gap-3 p-3 rounded-lg bg-muted/50">
                                <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center">
                                    <User class="h-6 w-6 text-primary" />
                                </div>
                                <div>
                                    <p class="font-medium">{{ leave.cover_person.name }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ leave.cover_person.employee_id }} • {{ leave.cover_person.designation }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                </div>

                <!-- Right Column: Approval Workflow -->
                <div>
                    <Card class="sticky top-4">
                        <CardHeader>
                            <CardTitle>Approval Workflow</CardTitle>
                            <CardDescription>
                                Step {{ leave.current_approval_step }} of {{ leave.approvals.length }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="relative space-y-6">
                                <!-- Timeline line -->
                                <div class="absolute left-[15px] top-3 bottom-3 w-[2px] bg-border"></div>
                                
                                <div 
                                    v-for="(approval, index) in leave.approvals" 
                                    :key="approval.id"
                                    class="relative pl-10"
                                >
                                    <!-- Status Dot -->
                                    <div 
                                        :class="[
                                            'absolute left-[10px] top-1 w-3 h-3 rounded-full z-10',
                                            approval.status === 'approved' ? 'bg-green-500' :
                                            approval.status === 'rejected' ? 'bg-red-500' :
                                            approval.status === 'pending' ? 'bg-yellow-500' :
                                            'bg-gray-300'
                                        ]"
                                    >
                                    </div>

                                    <!-- Content -->
                                    <div class="pb-6">
                                        <div class="flex items-center justify-between mb-2">
                                            <p class="font-semibold text-sm">
                                                {{ getApproverTypeLabel(approval.approver_type) }}
                                            </p>
                                            <Badge 
                                                :variant="getStatusBadgeVariant(approval.status)"
                                                class="text-xs"
                                            >
                                                {{ capitalizeStatus(approval.status) }}
                                            </Badge>
                                        </div>
                                        
                                        <div v-if="approval.approver" class="text-sm text-muted-foreground mb-1">
                                            <User class="h-3 w-3 inline mr-1" />
                                            {{ approval.approver.name }}
                                        </div>
                                        
                                        <div v-if="approval.acted_at" class="text-xs text-muted-foreground mb-2">
                                            <Clock class="h-3 w-3 inline mr-1" />
                                            {{ formatDateTime(approval.acted_at) }}
                                        </div>
                                        
                                        <div v-if="approval.comment" class="mt-2 p-3 rounded-lg bg-muted text-sm border-l-2 border-primary">
                                            <p class="text-xs text-muted-foreground mb-1">Comment:</p>
                                            <p class="italic">"{{ approval.comment }}"</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
