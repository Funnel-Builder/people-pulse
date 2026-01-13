<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { ClipboardList, Check, X, Eye } from 'lucide-vue-next';
import { ref } from 'vue';
import type { BreadcrumbItem } from '@/types';

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
}

interface Props {
    pendingLeaves: Leave[];
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

const formatDates = (dates: LeaveDate[]) => {
    if (dates.length === 1) {
        return new Date(dates[0].date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }
    return `${dates.length} days`;
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
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold">{{ pageTitle }}</h1>
            </div>

            <!-- Pending Requests -->
            <Card>
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
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium">{{ leave.user.name }}</span>
                                        <Badge variant="outline" class="text-xs">{{ leave.user.employee_id }}</Badge>
                                    </div>
                                    <div class="flex items-center gap-2 mt-1 text-sm text-muted-foreground">
                                        <span>{{ leave.leave_type.name }}</span>
                                        <span>•</span>
                                        <span>{{ formatDates(leave.dates) }}</span>
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
