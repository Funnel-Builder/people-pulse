<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { ClipboardList, Check, X, Eye, FileText, Calendar, BarChart3, Search, ChevronLeft, MoreHorizontal } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import type { BreadcrumbItem } from '@/types';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Input } from '@/components/ui/input';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Leave Requests', href: '/leaves/requests' },
];

interface LeaveDate {
    id: number;
    date: string;
}

interface LeaveType {
    id: number;
    name: string;
}

interface User {
    id: number;
    name: string;
    employee_id: string;
    designation: string;
    profile_picture?: string;
}

interface LeaveApproval {
    id: number;
    step: number;
    approver_type: string;
    status: string;
}

interface Leave {
    id: number;
    type: 'advance' | 'post';
    status: string;
    reason: string;
    current_approval_step: number;
    dates: LeaveDate[];
    leave_type: LeaveType;
    user: User;
    cover_person: User | null;
    approvals: LeaveApproval[];
    // Extended properties for history
    action_date?: string;
    action_status?: string;
}

interface Stats {
    total_handled: number;
    pending: number;
    coverage_days: number;
}

interface Props {
    pendingLeaves: Leave[];
    historyLeaves?: Leave[];
    stats?: Stats;
    pageTitle?: string;
    pageDescription?: string;
}

const props = withDefaults(defineProps<Props>(), {
    pageTitle: 'Leave Requests',
    pageDescription: 'Review and authorize leave applications',
});

const showApprovalModal = ref(false);
const selectedLeave = ref<Leave | null>(null);
const approvalAction = ref<'approve' | 'reject'>('approve');
const comment = ref('');
const isSubmitting = ref(false);

const viewMode = ref<'pending' | 'history'>('pending');
const searchQuery = ref('');

const filteredHistory = computed(() => {
    if (!props.historyLeaves) return [];
    if (!searchQuery.value) return props.historyLeaves;
    
    const query = searchQuery.value.toLowerCase();
    return props.historyLeaves.filter(leave => 
        leave.user.name.toLowerCase().includes(query) ||
        leave.leave_type.name.toLowerCase().includes(query) ||
        leave.action_status?.toLowerCase().includes(query)
    );
});

const toggleView = () => {
    viewMode.value = viewMode.value === 'pending' ? 'history' : 'pending';
};

const openApprovalModal = (leave: Leave, action: 'approve' | 'reject') => {
    selectedLeave.value = leave;
    approvalAction.value = action;
    comment.value = '';
    showApprovalModal.value = true;
};

const closeModal = () => {
    showApprovalModal.value = false;
    selectedLeave.value = null;
    comment.value = '';
};

const submitApproval = () => {
    if (!selectedLeave.value) return;
    if (approvalAction.value === 'reject' && !comment.value.trim()) return;

    isSubmitting.value = true;
    router.post(`/leaves/${selectedLeave.value.id}/process`, {
        action: approvalAction.value,
        comment: comment.value || null,
    }, {
        onFinish: () => {
            isSubmitting.value = false;
            closeModal();
        },
    });
};

const viewLeave = (leaveId: number) => {
    router.visit(`/leaves/${leaveId}`);
};

const formatDateRange = (dates: LeaveDate[]) => {
    if (dates.length === 0) return '';
    const sortedDates = [...dates].sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime());
    const startDate = new Date(sortedDates[0].date);
    const endDate = new Date(sortedDates[sortedDates.length - 1].date);
    
    const startStr = startDate.toLocaleDateString('en-US', { day: 'numeric' });
    const endStr = endDate.toLocaleDateString('en-US', { day: 'numeric', month: 'short' });
    
    if (dates.length === 1) {
        return `${endStr} (1 day)`;
    }
    
    // Check if same month
    if (startDate.getMonth() === endDate.getMonth()) {
         return `${startStr} — ${endStr} (${dates.length} days)`;
    }
     const startMonthStr = startDate.toLocaleDateString('en-US', { day: 'numeric', month: 'short' });
     return `${startMonthStr} — ${endStr} (${dates.length} days)`;
};

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map(part => part[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
};

const getCurrentApproverType = (leave: Leave) => {
    const approval = leave.approvals.find(a => a.step === leave.current_approval_step);
    return approval?.approver_type || '';
};

const getApprovalButtonLabel = (leave: Leave) => {
    const approverType = getCurrentApproverType(leave);
    switch (approverType) {
        case 'cover_person':
            return 'Accept';
        case 'manager':
            return 'Authorize';
        case 'admin':
            return 'Approve';
        default:
            return 'Approve';
    }
};

const getHistoryBadgeVariant = (status: string | undefined) => {
    switch (status?.toLowerCase()) {
        case 'approved': return 'success'; // You might need to check if 'success' variant exists, if not use 'default' or custom class
        case 'accepted': return 'success';
        case 'rejected': return 'destructive';
        case 'cancelled': return 'secondary';
        case 'expired': return 'secondary';
        default: return 'outline';
    }
};
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">{{ viewMode === 'history' ? 'Request History' : pageTitle }}</h1>
                    <p class="text-muted-foreground">{{ viewMode === 'history' ? 'View all your past cover request actions' : pageDescription }}</p>
                </div>
                <!-- Toggle View Button -->
                <Button variant="outline" @click="toggleView" class="gap-2">
                    <template v-if="viewMode === 'pending'">
                        <FileText class="h-4 w-4" />
                        View History
                    </template>
                    <template v-else>
                        <ChevronLeft class="h-4 w-4" />
                        Back to Pending
                    </template>
                </Button>
            </div>

            <!-- Stats Cards (History Mode) -->
            <div v-if="viewMode === 'history' && stats" class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Handled</CardTitle>
                        <BarChart3 class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_handled }}</div>
                        <p class="text-xs text-muted-foreground">Requests processed</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Pending</CardTitle>
                        <ClipboardList class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.pending }}</div>
                        <p class="text-xs text-muted-foreground">Requires attention</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">{{ pageTitle === 'Cover Requests' ? 'Coverage Days' : 'Approved Days' }}</CardTitle>
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.coverage_days }}</div>
                        <p class="text-xs text-muted-foreground">{{ pageTitle === 'Cover Requests' ? 'Days covered' : 'Days approved' }}</p>
                    </CardContent>
                </Card>
            </div>

            <!-- History View -->
            <Card v-if="viewMode === 'history'">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle>History</CardTitle>
                        <div class="flex items-center gap-2">
                            <div class="relative w-64">
                                <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input v-model="searchQuery" placeholder="Search colleague or type..." class="pl-8" />
                            </div>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <table class="w-full text-sm">
                            <thead class="bg-muted/50"> 
                                <tr class="text-left">
                                    <th class="p-4 font-medium text-muted-foreground">Collague</th>
                                    <th class="p-4 font-medium text-muted-foreground">Date Range</th>
                                    <th class="p-4 font-medium text-muted-foreground">Request Type</th>
                                    <th class="p-4 font-medium text-muted-foreground">Status</th>
                                    <th class="p-4 font-medium text-muted-foreground">Action Date</th>
                                    <th class="p-4 w-[50px]"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="leave in filteredHistory" :key="leave.id" class="border-t hover:bg-muted/50">
                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            <Avatar class="h-9 w-9">
                                                <AvatarImage :src="leave.user.profile_picture ?? ''" :alt="leave.user.name" />
                                                <AvatarFallback>{{ getInitials(leave.user.name) }}</AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <div class="font-medium">{{ leave.user.name }}</div>
                                                <div class="text-xs text-muted-foreground">{{ leave.user.designation }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4">{{ formatDateRange(leave.dates) }}</td>
                                    <td class="p-4">{{ leave.leave_type.name }}</td>
                                    <td class="p-4">
                                        <Badge :variant="getHistoryBadgeVariant(leave.action_status)">
                                            {{ leave.action_status || 'Unknown' }}
                                        </Badge>
                                    </td>
                                    <td class="p-4 text-muted-foreground">
                                        {{ leave.action_date ? new Date(leave.action_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '-' }}
                                    </td>
                                    <td class="p-4 text-right">
                                        <Button variant="ghost" size="icon" @click="viewLeave(leave.id)">
                                            <MoreHorizontal class="h-4 w-4" />
                                        </Button>
                                    </td>
                                </tr>
                                <tr v-if="filteredHistory.length === 0">
                                    <td colspan="6" class="p-8 text-center text-muted-foreground">
                                        No history found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Pending Requests -->
            <Card v-else>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <ClipboardList class="h-5 w-5" />
                        Pending Requests ({{ pendingLeaves.length }})
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="pendingLeaves.length" class="space-y-3">
                        <div
                            v-for="leave in pendingLeaves"
                            :key="leave.id"
                            class="flex items-center justify-between p-4 rounded-lg border"
                        >
                            <div class="flex items-center gap-4">
                                <Avatar class="h-10 w-10">
                                    <AvatarImage :src="leave.user.profile_picture ?? ''" :alt="leave.user.name" />
                                    <AvatarFallback>{{ getInitials(leave.user.name) }}</AvatarFallback>
                                </Avatar>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-base">{{ leave.user.name }}</span>
                                        <span class="text-xs text-muted-foreground">#{{ leave.user.employee_id }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                        <span class="italic">{{ leave.leave_type.name }} Request</span>
                                        <span class="text-xs">•</span>
                                        <span class="font-medium text-foreground">{{ formatDateRange(leave.dates) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <Button size="sm" variant="ghost" @click="viewLeave(leave.id)">
                                    <Eye class="h-4 w-4" />
                                </Button>
                                <Button 
                                    size="sm" 
                                    class="bg-green-600 hover:bg-green-700"
                                    @click="openApprovalModal(leave, 'approve')"
                                >
                                    <Check class="h-4 w-4 mr-1" />
                                    {{ getApprovalButtonLabel(leave) }}
                                </Button>
                                <Button 
                                    size="sm" 
                                    variant="destructive"
                                    @click="openApprovalModal(leave, 'reject')"
                                >
                                    <X class="h-4 w-4 mr-1" />
                                    Reject
                                </Button>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-muted-foreground text-center py-12">
                        <ClipboardList class="h-12 w-12 mx-auto mb-4 opacity-50" />
                        <p>No pending {{ pageTitle.toLowerCase() }}</p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Approval Modal -->
        <Dialog v-model:open="showApprovalModal">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {{ approvalAction === 'approve' 
                            ? `Confirm ${selectedLeave ? getApprovalButtonLabel(selectedLeave) : 'Approval'}`
                            : 'Reject Leave Request' }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ approvalAction === 'approve' 
                            ? `Are you sure you want to ${selectedLeave ? getApprovalButtonLabel(selectedLeave).toLowerCase() : 'approve'} this leave?` 
                            : 'Please provide a reason for rejection.' 
                        }}
                    </DialogDescription>
                </DialogHeader>

                <div class="py-4">
                    <Label :for="'comment'">
                        Comment {{ approvalAction === 'reject' ? '(Required)' : '(Optional)' }}
                    </Label>
                    <Textarea
                        id="comment"
                        v-model="comment"
                        :placeholder="approvalAction === 'reject' ? 'Reason for rejection...' : 'Add a comment (optional)'"
                        class="mt-2"
                        rows="3"
                    />
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="closeModal" :disabled="isSubmitting">
                        Cancel
                    </Button>
                    <Button 
                        :class="approvalAction === 'approve' ? 'bg-green-600 hover:bg-green-700' : ''"
                        :variant="approvalAction === 'reject' ? 'destructive' : 'default'"
                        @click="submitApproval"
                        :disabled="isSubmitting || (approvalAction === 'reject' && !comment.trim())"
                    >
                        {{ isSubmitting ? 'Processing...' : (approvalAction === 'approve' ? (selectedLeave ? getApprovalButtonLabel(selectedLeave) : 'Approve') : 'Reject') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
