<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Pagination,
    PaginationEllipsis,
    PaginationFirst,
    PaginationLast,
    PaginationList,
    PaginationListItem,
    PaginationNext,
    PaginationPrev,
} from '@/components/ui/pagination';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { FileText, Clock, AlertTriangle, CheckCircle, Download, XCircle } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Services', href: '#' },
    { title: 'Employee Certificate', href: '/services/certificate' },
    { title: 'History', href: '/services/certificate/history' },
];

interface CertificateRequest {
    id: number;
    ref_id: string;
    purpose: string;
    purpose_other: string | null;
    urgency: string;
    status: string;
    created_at: string;
    issued_at: string | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedResponse<T> {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    from: number;
    to: number;
}

interface Props {
    requests: PaginatedResponse<CertificateRequest>;
    purposes: Record<string, string>;
}

const props = defineProps<Props>();

// Pagination is now handled server-side via props.requests.data and props.requests.links

const getStatusBadgeClass = (status: string) => {
    switch (status) {
        case 'pending':
            return 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30';
        case 'approved':
            return 'bg-blue-500/20 text-blue-400 border-blue-500/30';
        case 'issued':
            return 'bg-green-500/20 text-green-400 border-green-500/30';
        case 'rejected':
            return 'bg-red-500/20 text-red-400 border-red-500/30';
        case 'cancelled':
            return 'bg-gray-500/20 text-gray-400 border-gray-500/30';
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

const getPurposeDisplay = (purpose: string, purposeOther: string | null) => {
    if (purpose === 'other') {
        return purposeOther || 'Other';
    }
    return props.purposes[purpose] || purpose;
};

const downloadCertificate = (requestId: number) => {
    window.open(`/services/certificate/${requestId}/download`, '_blank');
};

const requestToCancel = ref<number | null>(null);

const confirmCancel = (requestId: number) => {
    requestToCancel.value = requestId;
};

const handleCancel = () => {
    if (requestToCancel.value) {
        router.post(`/services/certificate/${requestToCancel.value}/cancel`, {}, {
            preserveScroll: true,
            onFinish: () => {
                requestToCancel.value = null;
            },
        });
    }
};
</script>

<template>
    <Head title="Certificate History" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Request History</h1>
                    <p class="text-muted-foreground mt-1">View the status of your certificate requests</p>
                </div>
                <Button variant="outline" @click="router.visit('/services/certificate')">
                    Back to Request
                </Button>
            </div>

            <!-- Requests Table -->
            <Card class="border-border/50 shadow-sm min-h-[500px] flex flex-col">
                <CardContent class="p-0 flex-1 flex flex-col">
                    <div v-if="requests.data.length === 0" class="flex-1 flex flex-col items-center justify-center py-12 text-muted-foreground">
                        <FileText class="h-12 w-12 mb-3 opacity-50" />
                        <p class="text-lg">No requests found</p>
                        <p class="text-sm">You haven't submitted any certificate requests yet</p>
                    </div>
                    <Table v-else>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent border-border/50">
                                <TableHead class="w-[120px]">Ref ID</TableHead>
                                <TableHead class="w-[200px]">Purpose</TableHead>
                                <TableHead class="w-[200px]">Request Date</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="request in requests.data"
                                :key="request.id"
                                class="border-border/50"
                            >
                                <TableCell class="font-medium text-xs font-mono">
                                    {{ request.ref_id }}
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <span>{{ getPurposeDisplay(request.purpose, request.purpose_other) }}</span>
                                        <Badge v-if="request.urgency === 'urgent'" variant="destructive" class="text-[10px] px-1.5 py-0">
                                            Urgent
                                        </Badge>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div>
                                        <p class="font-medium">{{ formatDate(request.created_at).date }}</p>
                                        <p class="text-sm text-muted-foreground">{{ formatDate(request.created_at).time }}</p>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge :class="['text-xs border', getStatusBadgeClass(request.status)]">
                                        {{ request.status.charAt(0).toUpperCase() + request.status.slice(1) }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            class="h-8 gap-2"
                                            :class="{
                                                'text-red-500 hover:text-red-600 hover:bg-red-50 border-red-200': request.status === 'pending',
                                                'opacity-50 cursor-not-allowed': request.status !== 'pending'
                                            }"
                                            :disabled="request.status !== 'pending'"
                                            @click="confirmCancel(request.id)"
                                        >
                                            <XCircle class="h-3.5 w-3.5" />
                                            Cancel
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            class="h-8 gap-2"
                                            :disabled="request.status !== 'issued'"
                                            @click="downloadCertificate(request.id)"
                                        >
                                            <Download class="h-3.5 w-3.5" />
                                            Download
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- Pagination -->
                    <div v-if="requests.last_page > 1" class="flex items-center justify-between px-6 py-4 border-t border-border/50">
                        <p class="text-sm text-muted-foreground">
                            Showing {{ requests.from }} to {{ requests.to }} of {{ requests.total }} requests
                        </p>
                        <div class="flex items-center gap-2">
                            <Button
                                v-for="(link, index) in requests.links"
                                :key="index"
                                :variant="link.active ? 'default' : 'outline'"
                                size="sm"
                                :class="['h-8 min-w-[32px] px-2', { 'pointer-events-none opacity-50': !link.url && link.label !== '...' }]"
                                :disabled="!link.url"
                                @click="link.url && router.visit(link.url)"
                            >
                                <span v-html="link.label"></span>
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <AlertDialog :open="!!requestToCancel" @update:open="val => !val && (requestToCancel = null)">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Cancel Request</AlertDialogTitle>
                    <AlertDialogDescription>
                        Are you sure you want to cancel this certificate request? This action cannot be undone.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="requestToCancel = null">Cancel</AlertDialogCancel>
                    <AlertDialogAction class="bg-red-500 hover:bg-red-600" @click="handleCancel">
                        Yes, Cancel Request
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
