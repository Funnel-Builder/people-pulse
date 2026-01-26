<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
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
import { FileText, Clock, AlertTriangle, CheckCircle } from 'lucide-vue-next';
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
}

const props = defineProps<Props>();

// Pagination
const currentPage = ref(1);
const itemsPerPage = 5;

const totalPages = computed(() => Math.ceil(props.requests.length / itemsPerPage));

const paginatedRequests = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return props.requests.slice(start, end);
});

const purposes: Record<string, string> = {
    visa_application: 'Visa Application',
    bank_loan: 'Bank Loan / Mortgage',
    apartment_leasing: 'Apartment Leasing',
    higher_education: 'Higher Education',
    other: 'Other',
};

const getTypeBadgeClass = (purpose: string) => {
    switch (purpose) {
        case 'visa_application':
            return 'bg-blue-500/20 text-blue-400 border-blue-500/30';
        case 'bank_loan':
            return 'bg-green-500/20 text-green-400 border-green-500/30';
        case 'apartment_leasing':
            return 'bg-purple-500/20 text-purple-400 border-purple-500/30';
        case 'higher_education':
            return 'bg-orange-500/20 text-orange-400 border-orange-500/30';
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

const isOverdue = (createdAt: string, urgency: string) => {
    if (urgency !== 'urgent') return false;
    const created = new Date(createdAt);
    const now = new Date();
    const hoursDiff = (now.getTime() - created.getTime()) / (1000 * 60 * 60);
    return hoursDiff > 24;
};

const getPurposeDisplay = (purpose: string, purposeOther: string | null) => {
    if (purpose === 'other') {
        return purposeOther || 'Other';
    }
    return purposes[purpose] || purpose;
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
</script>

<template>
    <Head title="Certificate Approvals" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 max-w-7xl mx-auto">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold">Certificate Approvals</h1>
                <p class="text-muted-foreground mt-1">Review and issue employee certificate requests</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-3">
                <Card class="border-border/50">
                    <CardContent class="pt-6">
                        <div class="flex items-center gap-4">
                            <div class="p-3 rounded-full bg-yellow-500/10">
                                <Clock class="h-6 w-6 text-yellow-500" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold">{{ requests.length }}</p>
                                <p class="text-sm text-muted-foreground">Pending Requests</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card class="border-border/50">
                    <CardContent class="pt-6">
                        <div class="flex items-center gap-4">
                            <div class="p-3 rounded-full bg-red-500/10">
                                <AlertTriangle class="h-6 w-6 text-red-500" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold">{{ requests.filter(r => r.urgency === 'urgent').length }}</p>
                                <p class="text-sm text-muted-foreground">Urgent Requests</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card class="border-border/50">
                    <CardContent class="pt-6">
                        <div class="flex items-center gap-4">
                            <div class="p-3 rounded-full bg-green-500/10">
                                <CheckCircle class="h-6 w-6 text-green-500" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold">{{ requests.filter(r => isOverdue(r.created_at, r.urgency)).length }}</p>
                                <p class="text-sm text-muted-foreground">Overdue</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Requests Table -->
            <Card class="border-border/50 shadow-sm">
                <CardContent class="p-0">
                    <div v-if="requests.length === 0" class="text-center py-12 text-muted-foreground">
                        <FileText class="h-12 w-12 mx-auto mb-3 opacity-50" />
                        <p class="text-lg">No pending requests</p>
                        <p class="text-sm">All certificate requests have been processed</p>
                    </div>
                    <Table v-else>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent border-border/50">
                                <TableHead class="w-[280px]">Employee</TableHead>
                                <TableHead class="w-[160px]">Type</TableHead>
                                <TableHead class="w-[160px]">Request Date</TableHead>
                                <TableHead>Purpose</TableHead>
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
                                    <Badge :class="['text-xs border', getTypeBadgeClass(request.purpose)]">
                                        {{ request.purpose === 'visa_application' ? 'Visa' : 
                                           request.purpose === 'bank_loan' ? 'Bank/Mortgage' :
                                           request.purpose === 'apartment_leasing' ? 'Apartment' :
                                           request.purpose === 'higher_education' ? 'Education' : 'Other' }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div>
                                        <p class="font-medium">{{ formatDate(request.created_at).date }}</p>
                                        <p class="text-sm text-muted-foreground">{{ formatDate(request.created_at).time }}</p>
                                        <p v-if="isOverdue(request.created_at, request.urgency)" class="text-xs text-red-500 font-medium mt-1">
                                            Overdue
                                        </p>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <span>{{ getPurposeDisplay(request.purpose, request.purpose_other) }}</span>
                                        <Badge v-if="request.urgency === 'urgent'" variant="destructive" class="text-[10px] px-1.5 py-0">
                                            !
                                        </Badge>
                                    </div>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            class="text-xs"
                                        >
                                            Clarify
                                        </Button>
                                        <Button
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
                            Showing {{ (currentPage - 1) * itemsPerPage + 1 }} to {{ Math.min(currentPage * itemsPerPage, requests.length) }} of {{ requests.length }} requests
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
    </AppLayout>
</template>
