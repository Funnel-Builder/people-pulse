<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ArrowLeft, Calendar, User, FileText, CheckCircle, XCircle, Clock } from 'lucide-vue-next';
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
</script>

<template>
    <Head :title="`Leave Request #${leave.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 max-w-4xl mx-auto">
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

            <div class="grid gap-6 md:grid-cols-2">
                <!-- Leave Information -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <FileText class="h-5 w-5" />
                            Leave Information
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <p class="text-sm text-muted-foreground">Leave Type</p>
                            <p class="font-medium">{{ leave.leave_type.name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Application Type</p>
                            <Badge variant="outline">
                                {{ leave.type === 'advance' ? 'Advance Leave' : 'Post Leave' }}
                            </Badge>
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Applied On</p>
                            <p class="font-medium">{{ formatDateTime(leave.created_at) }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Dates as Calendar Cards -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Calendar class="h-5 w-5" />
                            Leave Dates
                        </CardTitle>
                        <CardDescription>{{ leave.dates.length }} day(s) requested</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-wrap gap-3">
                            <div 
                                v-for="leaveDate in leave.dates" 
                                :key="leaveDate.id"
                                class="flex flex-col items-center justify-center w-16 h-18 rounded-lg border-2 border-primary/20 bg-primary/5 hover:bg-primary/10 transition-colors"
                            >
                                <span class="text-xs font-medium text-primary uppercase">
                                    {{ new Date(leaveDate.date).toLocaleDateString('en-US', { weekday: 'short' }) }}
                                </span>
                                <span class="text-2xl font-bold text-foreground">
                                    {{ new Date(leaveDate.date).getDate() }}
                                </span>
                                <span class="text-xs text-muted-foreground">
                                    {{ new Date(leaveDate.date).toLocaleDateString('en-US', { month: 'short' }) }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Reason Section -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <FileText class="h-5 w-5" />
                        Reason for Leave
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-sm leading-relaxed">{{ leave.reason }}</p>
                </CardContent>
            </Card>

            <!-- Cover Person (if advance leave) -->
            <Card v-if="leave.cover_person">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <User class="h-5 w-5" />
                        Cover Person
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                            <User class="h-5 w-5 text-primary" />
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

            <!-- Approval Progress -->
            <Card>
                <CardHeader>
                    <CardTitle>Approval Progress</CardTitle>
                    <CardDescription>
                        Step {{ leave.current_approval_step }} of {{ leave.approvals.length }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div 
                            v-for="approval in leave.approvals" 
                            :key="approval.id"
                            class="flex items-start gap-4 p-4 rounded-lg border"
                        >
                            <component 
                                :is="getApprovalStatusIcon(approval.status)" 
                                :class="['h-6 w-6', getApprovalStatusColor(approval.status)]"
                            />
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <p class="font-medium">
                                        Step {{ approval.step }}: {{ getApproverTypeLabel(approval.approver_type) }}
                                    </p>
                                    <Badge :variant="getStatusBadgeVariant(approval.status)">
                                        {{ capitalizeStatus(approval.status) }}
                                    </Badge>
                                </div>
                                <div v-if="approval.approver" class="text-sm text-muted-foreground mt-1">
                                    {{ approval.approver.name }}
                                </div>
                                <div v-if="approval.acted_at" class="text-xs text-muted-foreground mt-1">
                                    {{ formatDateTime(approval.acted_at) }}
                                </div>
                                <div v-if="approval.comment" class="mt-2 p-2 rounded bg-muted text-sm">
                                    "{{ approval.comment }}"
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Actions -->
            <div v-if="leave.status === 'pending'" class="flex justify-end">
                <Button variant="destructive" @click="cancelLeave">
                    Cancel Application
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
