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
import { ClipboardList, Check, X, Paperclip, LogIn, LogOut, FileText, ChevronLeft, Search, BarChart3, CheckCircle2, Plus } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import type { BreadcrumbItem } from '@/types';

interface RequestUser {
    id: number;
    name: string;
    employee_id: string;
    designation?: string;
    profile_picture?: string;
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
    stats?: Stats;
    coverPersonOptions?: CoverPerson[];
}

const props = defineProps<Props>();

const viewMode = ref<'pending' | 'history'>('pending');
const searchQuery = ref('');
const showRequestModal = ref(false);

const toggleView = () => {
    viewMode.value = viewMode.value === 'pending' ? 'history' : 'pending';
};

const filteredHistory = computed(() => {
    if (!props.historyRequests) return [];
    if (!searchQuery.value) return props.historyRequests;

    const query = searchQuery.value.toLowerCase();
    return props.historyRequests.filter((r) =>
        r.user.name.toLowerCase().includes(query) ||
        r.type_label.toLowerCase().includes(query) ||
        (r.action_status?.toLowerCase().includes(query) ?? false)
    );
});

const historyBadgeVariant = (status: string | null | undefined) => {
    switch (status?.toLowerCase()) {
        case 'approved': return 'default';
        case 'rejected': return 'destructive';
        default: return 'outline';
    }
};

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
                        {{ viewMode === 'history' ? 'Past Late Entry / Early Out Requests' : 'Late Entry / Early Out Requests' }}
                    </h1>
                    <p class="text-muted-foreground">
                        {{ viewMode === 'history'
                            ? 'View all your past late entry & early out request actions'
                            : 'Review late entry & early out requests awaiting your action' }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button @click="showRequestModal = true" class="gap-2">
                        <Plus class="h-4 w-4" />
                        Apply For Late Entry or Early Out
                    </Button>
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
                        <CardTitle class="text-sm font-medium">Approved</CardTitle>
                        <CheckCircle2 class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.approved }}</div>
                        <p class="text-xs text-muted-foreground">Requests you approved</p>
                    </CardContent>
                </Card>
            </div>

            <!-- History View -->
            <Card v-if="viewMode === 'history'">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle>History</CardTitle>
                        <div class="relative w-64">
                            <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input v-model="searchQuery" placeholder="Search colleague or type..." class="pl-8" />
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <table class="w-full text-sm">
                            <thead class="bg-muted/50">
                                <tr class="text-left">
                                    <th class="p-4 font-medium text-muted-foreground">Colleague</th>
                                    <th class="p-4 font-medium text-muted-foreground">Type</th>
                                    <th class="p-4 font-medium text-muted-foreground">Date</th>
                                    <th class="p-4 font-medium text-muted-foreground">Your Action</th>
                                    <th class="p-4 font-medium text-muted-foreground">Action Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="request in filteredHistory" :key="request.id" class="border-t hover:bg-muted/50">
                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            <Avatar class="h-9 w-9">
                                                <AvatarImage :src="request.user.profile_picture ?? ''" :alt="request.user.name" />
                                                <AvatarFallback>{{ getInitials(request.user.name) }}</AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <div class="font-medium">{{ request.user.name }}</div>
                                                <div class="text-xs text-muted-foreground">#{{ request.user.employee_id }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <Badge :variant="request.type === 'late_entry' ? 'default' : 'secondary'" class="gap-1">
                                            <component :is="request.type === 'late_entry' ? LogIn : LogOut" class="h-3 w-3" />
                                            {{ request.type_label }}
                                        </Badge>
                                    </td>
                                    <td class="p-4">{{ formatDate(request.date) }} · {{ formatTime(request.requested_time) }}</td>
                                    <td class="p-4">
                                        <Badge :variant="historyBadgeVariant(request.action_status)">
                                            {{ request.action_status || 'Unknown' }}
                                        </Badge>
                                    </td>
                                    <td class="p-4 text-muted-foreground">
                                        {{ request.action_date
                                            ? new Date(request.action_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
                                            : '-' }}
                                    </td>
                                </tr>
                                <tr v-if="filteredHistory.length === 0">
                                    <td colspan="5" class="p-8 text-center text-muted-foreground">
                                        No history found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Pending View -->
            <Card v-else>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <ClipboardList class="h-5 w-5" />
                        Pending Requests ({{ pendingRequests.length }})
                    </CardTitle>
                </CardHeader>
                <CardContent>
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

        <AdjustmentRequestModal
            v-model:open="showRequestModal"
            :cover-person-options="coverPersonOptions ?? []"
        />
    </AppLayout>
</template>
