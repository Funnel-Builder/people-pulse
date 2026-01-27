<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { FileText, Search, AlertCircle, Loader2 } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Services', href: '#' },
    { title: 'Certificate Approvals', href: '/services/certificate/approvals' },
];

interface User {
    id: number;
    name: string;
    employee_id: string;
    designation: string;
    profile_picture: string | null;
    department: { name: string } | null;
    sub_department: { name: string } | null;
}

interface CertificateRequest {
    id: number;
    ref_id: string;
    user: User;
    purpose: string;
    purpose_other: string | null;
    urgency: string;
    status: string;
    created_at: string;
}

interface Props {
    requests: CertificateRequest[];
    userRole: string;
}

const props = defineProps<Props>();

// Search and filter
const searchQuery = ref('');
const statusFilter = ref('all');

// Pagination
const currentPage = ref(1);
const itemsPerPage = 7;

const filteredRequests = computed(() => {
    let result = props.requests;

    // Apply search
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(r =>
            r.user.name.toLowerCase().includes(query) ||
            r.user.employee_id.toLowerCase().includes(query) ||
            r.ref_id.toLowerCase().includes(query)
        );
    }

    // Apply status filter
    if (statusFilter.value !== 'all') {
        result = result.filter(r => r.status === statusFilter.value);
    }

    return result;
});

const totalPages = computed(() => Math.ceil(filteredRequests.value.length / itemsPerPage));

const paginatedRequests = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return filteredRequests.value.slice(start, end);
});

const isAdmin = computed(() => props.userRole === 'admin');

const getStatusBadgeClass = (status: string) => {
    switch (status) {
        case 'pending':
            return 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30';
        case 'authorized':
            return 'bg-blue-500/20 text-blue-400 border-blue-500/30';
        default:
            return 'bg-gray-500/20 text-gray-400 border-gray-500/30';
    }
};

const formatDate = (dateStr: string) => {
    const date = new Date(dateStr);
    return {
        date: date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        }),
        time: date.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
        }),
    };
};

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const goToReview = (requestId: number) => {
    router.get(`/services/certificate/${requestId}/review`);
};

const authorizeRequest = (requestId: number) => {
    router.post(`/services/certificate/${requestId}/authorize`);
};

// Rejection Modal Logic
const rejectModalOpen = ref(false);
const requestToReject = ref<number | null>(null);
const isRejecting = ref(false);

const openRejectModal = (requestId: number) => {
    requestToReject.value = requestId;
    rejectModalOpen.value = true;
};

const closeRejectModal = () => {
    rejectModalOpen.value = false;
    requestToReject.value = null;
    isRejecting.value = false;
};

const confirmReject = () => {
    if (!requestToReject.value) return;

    isRejecting.value = true;
    router.post(`/services/certificate/${requestToReject.value}/reject`, {}, {
        onFinish: () => closeRejectModal(),
    });
};
</script>

<template>
    <Head title="Certificate Approvals" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 max-w-7xl mx-auto">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold">Certificate Approvals</h1>
                <p class="text-muted-foreground mt-1">Review and process employee certificate requests</p>
            </div>

            <!-- Search and Filter Bar -->
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                        v-model="searchQuery"
                        placeholder="Search by name, employee ID, or reference..."
                        class="pl-10"
                    />
                </div>
                <Select v-model="statusFilter">
                    <SelectTrigger class="w-full sm:w-[180px]">
                        <SelectValue placeholder="Filter by status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Status</SelectItem>
                        <SelectItem value="pending">Pending</SelectItem>
                        <SelectItem value="authorized">Authorized</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- Requests Table -->
            <Card class="border-border/50 shadow-sm">
                <CardContent class="p-0 min-h-[400px]">
                    <div v-if="filteredRequests.length === 0" class="text-center py-12 text-muted-foreground">
                        <FileText class="h-12 w-12 mx-auto mb-3 opacity-50" />
                        <p class="text-lg">No requests found</p>
                        <p class="text-sm">Try adjusting your search or filter criteria</p>
                    </div>
                    <Table v-else>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent border-border/50">
                                <TableHead class="w-[280px]">Employee</TableHead>
                                <TableHead class="w-[160px]">Type</TableHead>
                                <TableHead class="w-[140px]">Status</TableHead>
                                <TableHead class="w-[160px]">Request Date</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="request in paginatedRequests"
                                :key="request.id"
                                class="border-border/50"
                            >
                                <TableCell>
                                    <div class="flex items-center gap-3">
                                        <Avatar class="h-10 w-10">
                                            <AvatarImage v-if="request.user.profile_picture" :src="request.user.profile_picture" />
                                            <AvatarFallback class="bg-primary/10 text-primary text-sm">
                                                {{ getInitials(request.user.name) }}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div>
                                            <p class="font-medium">{{ request.user.name }}</p>
                                            <p class="text-sm text-muted-foreground">{{ request.user.designation }}</p>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge class="text-xs border bg-primary/10 text-primary border-primary/30">
                                        EC
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge :class="['text-xs border', getStatusBadgeClass(request.status)]">
                                        {{ request.status.charAt(0).toUpperCase() + request.status.slice(1) }}
                                    </Badge>
                                    <Badge v-if="request.urgency === 'urgent'" variant="destructive" class="text-[10px] px-1.5 py-0 ml-1">
                                        Urgent
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div>
                                        <p class="font-medium">{{ formatDate(request.created_at).date }}</p>
                                        <p class="text-sm text-muted-foreground">{{ formatDate(request.created_at).time }}</p>
                                    </div>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Cancel/Reject button -->
                                        <Button
                                            v-if="request.status === 'pending' || request.status === 'authorized'"
                                            variant="outline"
                                            size="sm"
                                            class="text-xs text-red-500 border-red-500/30 hover:bg-red-500/10"
                                            @click="openRejectModal(request.id)"
                                        >
                                            Cancel
                                        </Button>
                                        <!-- Manager sees Authorize for pending requests -->
                                        <Button
                                            v-if="!isAdmin && request.status === 'pending'"
                                            size="sm"
                                            class="text-xs bg-blue-600 hover:bg-blue-700"
                                            @click="authorizeRequest(request.id)"
                                        >
                                            Authorize
                                        </Button>
                                        <!-- Admin sees Approve & Issue for pending/authorized -->
                                        <Button
                                            v-if="isAdmin"
                                            size="sm"
                                            class="text-xs bg-primary hover:bg-primary/90"
                                            @click="goToReview(request.id)"
                                        >
                                            Approve & Issue
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- Pagination -->
                    <div v-if="totalPages > 1" class="flex items-center justify-between px-6 py-4 border-t border-border/50">
                        <p class="text-sm text-muted-foreground">
                            Showing {{ (currentPage - 1) * itemsPerPage + 1 }} to {{ Math.min(currentPage * itemsPerPage, filteredRequests.length) }} of {{ filteredRequests.length }} requests
                        </p>
                        <div class="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="currentPage === 1"
                                @click="currentPage--"
                            >
                                Previous
                            </Button>
                            <div class="flex items-center gap-1">
                                <Button
                                    v-for="page in totalPages"
                                    :key="page"
                                    :variant="currentPage === page ? 'default' : 'outline'"
                                    size="sm"
                                    class="w-8 h-8 p-0"
                                    @click="currentPage = page"
                                >
                                    {{ page }}
                                </Button>
                            </div>
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="currentPage === totalPages"
                                @click="currentPage++"
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Rejection Confirmation Modal -->
        <Dialog v-model:open="rejectModalOpen">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <div class="mx-auto bg-red-100 h-12 w-12 rounded-full flex items-center justify-center mb-4">
                        <AlertCircle class="h-6 w-6 text-red-600" />
                    </div>
                    <DialogTitle class="text-center">Reject Request</DialogTitle>
                    <DialogDescription class="text-center">
                        Are you sure you want to reject this certificate request? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter class="flex flex-col sm:flex-row gap-2 mt-4">
                    <Button variant="outline" @click="closeRejectModal" :disabled="isRejecting" class="w-full sm:w-auto mt-2 sm:mt-0">
                        Nevermind
                    </Button>
                    <Button variant="destructive" @click="confirmReject" :disabled="isRejecting" class="w-full sm:w-auto">
                        <Loader2 v-if="isRejecting" class="mr-2 h-4 w-4 animate-spin" />
                        Yes, Reject
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
