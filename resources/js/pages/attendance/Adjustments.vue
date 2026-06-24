<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Input } from '@/components/ui/input';
import AdjustmentRequestModal from '@/components/attendance/AdjustmentRequestModal.vue';
import { ClipboardList, Check, X, Paperclip, LogIn, LogOut, FileText, ChevronLeft, Search, BarChart3, CheckCircle2, Plus, Eye, User as UserIcon, Clock } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import type { BreadcrumbItem } from '@/types';

interface RequestUser {
    id: number;
    name: string;
    employee_id: string;
    designation?: string;
    profile_picture?: string;
}

interface ApprovalStep {
    id: number;
    step: number;
    approver_type: string | null;
    approver_type_label: string;
    approver_name: string | null;
    status: string;
    comment: string | null;
    acted_at: string | null;
}

interface AdjustmentRequest {
    id: number;
    type: 'late_entry' | 'early_out';
    type_label: string;
    date: string;
    requested_time: string;
    reason: string;
    status: string;
    current_approval_step: number;
    current_approver_type: string | null;
    has_attachment: boolean;
    attachment_name: string | null;
    attachment_url: string | null;
    user: RequestUser;
    cover_person: { id: number; name: string } | null;
    approvals: ApprovalStep[];
    // History-only fields
    action_status?: string | null;
    action_date?: string | null;
    action_type?: string | null;
}

interface Stats {
    total_handled: number;
    pending: number;
    approved: number;
}

interface CoverPerson {
    id: number;
    name: string;
    employee_id: string;
    designation?: string;
}

interface Props {
    pendingRequests: AdjustmentRequest[];
    historyRequests?: AdjustmentRequest[];
    ownRequests?: AdjustmentRequest[];
    stats?: Stats;
    coverPersonOptions?: CoverPerson[];
}

const props = defineProps<Props>();

// 'employee' shows the tabbed employee inbox (pending + approved colleague requests),
// 'own' shows the approver's own submitted late/early request history.
const viewMode = ref<'employee' | 'own'>('employee');
const employeeTab = ref<'pending' | 'approved'>('pending');
const searchQuery = ref('');
const showRequestModal = ref(false);

const toggleView = () => {
    viewMode.value = viewMode.value === 'employee' ? 'own' : 'employee';
};

// Colleague requests this approver has already approved.
const approvedRequests = computed(() =>
    (props.historyRequests ?? []).filter((r) => r.action_status?.toLowerCase() === 'approved')
);

const ownHistory = computed(() => props.ownRequests ?? []);

const ownStats = computed(() => {
    const all = ownHistory.value;
    return {
        total: all.length,
        pending: all.filter((r) => r.status.toLowerCase() === 'pending').length,
        approved: all.filter((r) => r.status.toLowerCase() === 'approved').length,
    };
});

const filteredOwnHistory = computed(() => {
    if (!searchQuery.value) return ownHistory.value;

    const query = searchQuery.value.toLowerCase();
    return ownHistory.value.filter((r) =>
        r.type_label.toLowerCase().includes(query) ||
        r.status.toLowerCase().includes(query) ||
        r.reason.toLowerCase().includes(query)
    );
});

const statusBadgeVariant = (status: string | null | undefined) => {
    switch (status?.toLowerCase()) {
        case 'approved': return 'default';
        case 'rejected': return 'destructive';
        default: return 'outline';
    }
};

// Approval-flow dialog (lets the user see where their request currently stands).
const showFlow = ref(false);
const flowRequest = ref<AdjustmentRequest | null>(null);

const openFlow = (request: AdjustmentRequest) => {
    flowRequest.value = request;
    showFlow.value = true;
};

const stepDotClass = (status: string) => {
    switch (status?.toLowerCase()) {
        case 'approved': return 'bg-green-500';
        case 'rejected': return 'bg-red-500';
        case 'pending': return 'bg-yellow-500';
        default: return 'bg-gray-300';
    }
};

const capitalize = (value: string | null | undefined) =>
    value ? value.charAt(0).toUpperCase() + value.slice(1) : '';

const formatDateTime = (value: string | null | undefined) =>
    value
        ? new Date(value).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' })
        : '';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Late/Early Requests', href: '/attendance/adjustments/approvals' },
];

const showModal = ref(false);
const selected = ref<AdjustmentRequest | null>(null);
const action = ref<'approve' | 'reject'>('approve');
const comment = ref('');
const isSubmitting = ref(false);

const openModal = (request: AdjustmentRequest, act: 'approve' | 'reject') => {
    selected.value = request;
    action.value = act;
    comment.value = '';
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selected.value = null;
    comment.value = '';
};

const submit = () => {
    if (!selected.value) return;
    if (action.value === 'reject' && !comment.value.trim()) return;

    isSubmitting.value = true;
    router.post(`/attendance/adjustments/${selected.value.id}/process`, {
        action: action.value,
        comment: comment.value || null,
    }, {
        preserveScroll: true,
        onFinish: () => {
            isSubmitting.value = false;
            closeModal();
        },
    });
};

const getInitials = (name: string) =>
    name.split(' ').map((p) => p[0]).slice(0, 2).join('').toUpperCase();

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });

const formatTime = (time: string) => {
    // time may be "HH:MM:SS" or a full datetime; normalize to a readable clock value.
    const t = time.length <= 8 ? `2000-01-01T${time}` : time;
    return new Date(t).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
};

const approveLabel = (request: AdjustmentRequest | null) => {
    switch (request?.current_approver_type) {
        case 'cover_person': return 'Accept';
        case 'manager': return 'Authorize';
        default: return 'Approve';
    }
};
</script>

<template>
    <Head title="Late/Early Requests" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">
                        {{ viewMode === 'own' ? 'My Late Entry / Early Out Requests' : 'Late Entry / Early Out Requests' }}
                    </h1>
                    <p class="text-muted-foreground">
                        {{ viewMode === 'own'
                            ? 'View the late entry & early out requests you have submitted'
                            : 'Review late entry & early out requests awaiting your action' }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button @click="showRequestModal = true" class="gap-2">
                        <Plus class="h-4 w-4" />
                        Apply For Late Entry or Early Out
                    </Button>
                    <Button variant="outline" @click="toggleView" class="gap-2">
                        <template v-if="viewMode === 'employee'">
                            <FileText class="h-4 w-4" />
                            View Own History
                        </template>
                        <template v-else>
                            <ChevronLeft class="h-4 w-4" />
                            Back to Requests
                        </template>
                    </Button>
                </div>
            </div>

            <!-- Stats Cards (Own History Mode) -->
            <div v-if="viewMode === 'own'" class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Submitted</CardTitle>
                        <BarChart3 class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ ownStats.total }}</div>
                        <p class="text-xs text-muted-foreground">Requests you submitted</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Pending</CardTitle>
                        <ClipboardList class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ ownStats.pending }}</div>
                        <p class="text-xs text-muted-foreground">Awaiting a decision</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Approved</CardTitle>
                        <CheckCircle2 class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ ownStats.approved }}</div>
                        <p class="text-xs text-muted-foreground">Requests approved</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Own History View -->
            <Card v-if="viewMode === 'own'">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle>My Requests</CardTitle>
                        <div class="relative w-64">
                            <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input v-model="searchQuery" placeholder="Search type, status or reason..." class="pl-8" />
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <table class="w-full text-sm">
                            <thead class="bg-muted/50">
                                <tr class="text-left">
                                    <th class="p-4 font-medium text-muted-foreground">Type</th>
                                    <th class="p-4 font-medium text-muted-foreground">Date</th>
                                    <th class="p-4 font-medium text-muted-foreground">Reason</th>
                                    <th class="p-4 font-medium text-muted-foreground">Status</th>
                                    <th class="p-4 font-medium text-muted-foreground text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="request in filteredOwnHistory" :key="request.id" class="border-t hover:bg-muted/50">
                                    <td class="p-4">
                                        <Badge :variant="request.type === 'late_entry' ? 'default' : 'secondary'" class="gap-1">
                                            <component :is="request.type === 'late_entry' ? LogIn : LogOut" class="h-3 w-3" />
                                            {{ request.type_label }}
                                        </Badge>
                                    </td>
                                    <td class="p-4">{{ formatDate(request.date) }} · {{ formatTime(request.requested_time) }}</td>
                                    <td class="p-4 max-w-xs truncate text-muted-foreground">{{ request.reason }}</td>
                                    <td class="p-4">
                                        <Badge :variant="statusBadgeVariant(request.status)" class="capitalize">
                                            {{ request.status }}
                                        </Badge>
                                    </td>
                                    <td class="p-4 text-right">
                                        <Button variant="outline" size="sm" class="gap-1" @click="openFlow(request)">
                                            <Eye class="h-4 w-4" />
                                            View
                                        </Button>
                                    </td>
                                </tr>
                                <tr v-if="filteredOwnHistory.length === 0">
                                    <td colspan="5" class="p-8 text-center text-muted-foreground">
                                        You haven't submitted any late entry / early out requests.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Employee Requests View (tabbed) -->
            <Card v-else>
                <CardHeader class="pb-0">
                    <div class="inline-flex w-fit items-center gap-1 rounded-lg bg-muted p-1">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                            :class="employeeTab === 'pending' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                            @click="employeeTab = 'pending'"
                        >
                            <ClipboardList class="h-4 w-4" />
                            Employee Pending Requests ({{ pendingRequests.length }})
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                            :class="employeeTab === 'approved' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                            @click="employeeTab = 'approved'"
                        >
                            <CheckCircle2 class="h-4 w-4" />
                            Employee Approved Requests ({{ approvedRequests.length }})
                        </button>
                    </div>
                </CardHeader>
                <CardContent class="pt-6">
                    <!-- Employee Pending Requests -->
                    <template v-if="employeeTab === 'pending'">
                        <div v-if="pendingRequests.length" class="space-y-3">
                            <div
                                v-for="request in pendingRequests"
                                :key="request.id"
                                class="flex flex-col gap-3 rounded-lg border p-4 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div class="flex items-start gap-4">
                                    <Avatar class="h-10 w-10">
                                        <AvatarImage :src="request.user.profile_picture ?? ''" :alt="request.user.name" />
                                        <AvatarFallback>{{ getInitials(request.user.name) }}</AvatarFallback>
                                    </Avatar>
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-base font-medium">{{ request.user.name }}</span>
                                            <span class="text-xs text-muted-foreground">#{{ request.user.employee_id }}</span>
                                            <Badge :variant="request.type === 'late_entry' ? 'default' : 'secondary'" class="gap-1">
                                                <component :is="request.type === 'late_entry' ? LogIn : LogOut" class="h-3 w-3" />
                                                {{ request.type_label }}
                                            </Badge>
                                        </div>
                                        <div class="text-sm text-muted-foreground">
                                            {{ formatDate(request.date) }} · {{ formatTime(request.requested_time) }}
                                        </div>
                                        <p class="text-sm">{{ request.reason }}</p>
                                        <a
                                            v-if="request.has_attachment"
                                            :href="request.attachment_url!"
                                            class="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline dark:text-blue-400"
                                        >
                                            <Paperclip class="h-3 w-3" />
                                            {{ request.attachment_name || 'Attachment' }}
                                        </a>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 self-end sm:self-center">
                                    <Button size="sm" class="bg-green-600 hover:bg-green-700" @click="openModal(request, 'approve')">
                                        <Check class="mr-1 h-4 w-4" />
                                        {{ approveLabel(request) }}
                                    </Button>
                                    <Button size="sm" variant="destructive" @click="openModal(request, 'reject')">
                                        <X class="mr-1 h-4 w-4" />
                                        Reject
                                    </Button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="py-12 text-center text-muted-foreground">
                            <ClipboardList class="mx-auto mb-4 h-12 w-12 opacity-50" />
                            <p>No pending Late Entry / Early Out requests</p>
                        </div>
                    </template>

                    <!-- Employee Approved Requests -->
                    <template v-else>
                        <div v-if="approvedRequests.length" class="space-y-3">
                            <div
                                v-for="request in approvedRequests"
                                :key="request.id"
                                class="flex flex-col gap-3 rounded-lg border p-4 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div class="flex items-start gap-4">
                                    <Avatar class="h-10 w-10">
                                        <AvatarImage :src="request.user.profile_picture ?? ''" :alt="request.user.name" />
                                        <AvatarFallback>{{ getInitials(request.user.name) }}</AvatarFallback>
                                    </Avatar>
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-base font-medium">{{ request.user.name }}</span>
                                            <span class="text-xs text-muted-foreground">#{{ request.user.employee_id }}</span>
                                            <Badge :variant="request.type === 'late_entry' ? 'default' : 'secondary'" class="gap-1">
                                                <component :is="request.type === 'late_entry' ? LogIn : LogOut" class="h-3 w-3" />
                                                {{ request.type_label }}
                                            </Badge>
                                        </div>
                                        <div class="text-sm text-muted-foreground">
                                            {{ formatDate(request.date) }} · {{ formatTime(request.requested_time) }}
                                        </div>
                                        <p class="text-sm">{{ request.reason }}</p>
                                        <a
                                            v-if="request.has_attachment"
                                            :href="request.attachment_url!"
                                            class="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline dark:text-blue-400"
                                        >
                                            <Paperclip class="h-3 w-3" />
                                            {{ request.attachment_name || 'Attachment' }}
                                        </a>
                                    </div>
                                </div>

                                <div class="flex flex-col items-end gap-1 self-end sm:self-center">
                                    <Badge variant="default" class="gap-1 bg-green-600 hover:bg-green-600">
                                        <CheckCircle2 class="h-3 w-3" />
                                        Approved
                                    </Badge>
                                    <span v-if="request.action_date" class="text-xs text-muted-foreground">
                                        {{ new Date(request.action_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="py-12 text-center text-muted-foreground">
                            <CheckCircle2 class="mx-auto mb-4 h-12 w-12 opacity-50" />
                            <p>No approved Late Entry / Early Out requests yet</p>
                        </div>
                    </template>
                </CardContent>
            </Card>
        </div>

        <Dialog v-model:open="showModal">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {{ action === 'approve' ? `Confirm ${approveLabel(selected)}` : 'Reject Request' }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ action === 'approve'
                            ? 'Approving will move this request forward. On final approval the day will be excused.'
                            : 'Please provide a reason for rejection.' }}
                    </DialogDescription>
                </DialogHeader>

                <div class="py-2">
                    <Label for="comment">Comment {{ action === 'reject' ? '(Required)' : '(Optional)' }}</Label>
                    <Textarea
                        id="comment"
                        v-model="comment"
                        :placeholder="action === 'reject' ? 'Reason for rejection...' : 'Add a comment (optional)'"
                        class="mt-2"
                        rows="3"
                    />
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="closeModal" :disabled="isSubmitting">Cancel</Button>
                    <Button
                        :class="action === 'approve' ? 'bg-green-600 hover:bg-green-700' : ''"
                        :variant="action === 'reject' ? 'destructive' : 'default'"
                        :disabled="isSubmitting || (action === 'reject' && !comment.trim())"
                        @click="submit"
                    >
                        {{ isSubmitting ? 'Processing...' : (action === 'approve' ? approveLabel(selected) : 'Reject') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Request Flow Dialog -->
        <Dialog v-model:open="showFlow">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <component :is="flowRequest?.type === 'late_entry' ? LogIn : LogOut" class="h-4 w-4" />
                        {{ flowRequest?.type_label }} Request
                    </DialogTitle>
                    <DialogDescription v-if="flowRequest">
                        {{ formatDate(flowRequest.date) }} · {{ formatTime(flowRequest.requested_time) }} —
                        <span class="capitalize">{{ flowRequest.status }}</span>
                    </DialogDescription>
                </DialogHeader>

                <div v-if="flowRequest" class="py-2">
                    <div class="mb-4 flex items-center justify-between">
                        <p class="text-sm font-medium">Approval Workflow</p>
                        <span class="text-xs text-muted-foreground">
                            Step {{ flowRequest.current_approval_step }} of {{ flowRequest.approvals.length }}
                        </span>
                    </div>

                    <div class="relative space-y-6">
                        <!-- Timeline line -->
                        <div class="absolute left-[7px] top-2 bottom-2 w-[2px] bg-border"></div>

                        <div
                            v-for="approval in flowRequest.approvals"
                            :key="approval.id"
                            class="relative pl-8"
                        >
                            <!-- Status dot -->
                            <div :class="['absolute left-[3px] top-1 z-10 h-3 w-3 rounded-full', stepDotClass(approval.status)]"></div>

                            <div class="flex items-center justify-between">
                                <p class="text-sm font-semibold">{{ approval.approver_type_label }}</p>
                                <Badge :variant="statusBadgeVariant(approval.status)" class="capitalize">
                                    {{ capitalize(approval.status) }}
                                </Badge>
                            </div>

                            <div v-if="approval.approver_name" class="mt-1 text-sm text-muted-foreground">
                                <UserIcon class="mr-1 inline h-3 w-3" />
                                {{ approval.approver_name }}
                            </div>
                            <div v-if="approval.acted_at" class="mt-1 text-xs text-muted-foreground">
                                <Clock class="mr-1 inline h-3 w-3" />
                                {{ formatDateTime(approval.acted_at) }}
                            </div>
                            <div v-if="approval.comment" class="mt-2 rounded-lg border-l-2 border-primary bg-muted p-3 text-sm">
                                <p class="mb-1 text-xs text-muted-foreground">Comment:</p>
                                <p class="italic">"{{ approval.comment }}"</p>
                            </div>
                        </div>
                    </div>

                    <p v-if="!flowRequest.approvals.length" class="py-6 text-center text-sm text-muted-foreground">
                        No approval steps recorded for this request.
                    </p>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showFlow = false">Close</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <AdjustmentRequestModal
            v-model:open="showRequestModal"
            :cover-person-options="coverPersonOptions ?? []"
        />
    </AppLayout>
</template>
