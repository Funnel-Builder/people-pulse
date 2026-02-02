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
import { FileText, Search, AlertCircle, Loader2, Eye, Download, Printer } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Services', href: '#' },
    { title: 'Certificate Approvals', href: '/services/approvals' },
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
    type: string;
    created_at: string;
}



interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedResponse {
    data: CertificateRequest[];
    links: PaginationLink[];
    from: number;
    to: number;
    total: number;
    last_page: number;
    current_page: number;
}

interface Props {
    requests: PaginatedResponse;
    userRole: string;
    isHistory: boolean;
}

const props = defineProps<Props>();

// Search and Filters
const searchQuery = ref('');
// const statusFilter = ref('all'); // Removed as per request
const typeFilter = ref('all');

const getFilterLabel = (value: string) => {
    const map: Record<string, string> = {
        'all': 'All Types',
        'employment_certificate': 'Employment Certificate (EC)',
        'release_letter': 'Release Letter (RL)',
        'visa_recommendation_letter': 'Visa Recommendation Letter (VRL)',
        'experience_certificate': 'Experience Certificate (XC)',
    };
    return map[value] || 'All Types';
};

const getTypeCode = (type: string) => {
    const map: Record<string, string> = {
        'employment_certificate': 'EC',
        'release_letter': 'RL',
        'visa_recommendation_letter': 'VRL',
        'experience_certificate': 'XC',
    };
    return map[type] || 'EC';
};

// Computed filtered data (from current page only, as it's server-side paginated main list)
// Note: Ideally filtering should be server-side too, but for type (which isn't in DB yet/mapped), we filter the current page view or assume all are EC for now.
const filteredRequestsData = computed(() => {
    let result = props.requests.data;

    // Apply search
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(r =>
            r.user.name.toLowerCase().includes(query) ||
            r.user.employee_id.toLowerCase().includes(query) ||
            r.ref_id.toLowerCase().includes(query)
        );
    }

    if (typeFilter.value !== 'all') {
        result = result.filter(r => r.type === typeFilter.value);
    }

    return result;
});

const toggleHistory = () => {
    router.get('/services/approvals', { 
        history: props.isHistory ? 0 : 1 
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

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

const goToReview = (request: any) => {
    const typeToRouteMap: Record<string, string> = {
        'employment_certificate': 'employment-certificate',
        'visa_recommendation_letter': 'visa-recommendation-letter',
        'release_letter': 'release-letter',
        'experience_certificate': 'experience-certificate',
    };
    const routeSlug = typeToRouteMap[request.type] || 'employment-certificate';
    router.get(`/services/${routeSlug}/${request.id}/review`);
};

const getRouteSlug = (type: string) => {
    const typeToRouteMap: Record<string, string> = {
        'employment_certificate': 'employment-certificate',
        'visa_recommendation_letter': 'visa-recommendation-letter',
        'release_letter': 'release-letter',
        'experience_certificate': 'experience-certificate',
    };
    return typeToRouteMap[type] || 'employment-certificate';
};

const authorizeRequest = (request: CertificateRequest) => {
    const routeSlug = getRouteSlug(request.type);
    router.post(`/services/${routeSlug}/${request.id}/authorize`);
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

    const request = filteredRequestsData.value.find(r => r.id === requestToReject.value);
    if (!request) return;

    isRejecting.value = true;
    const routeSlug = getRouteSlug(request.type);
    router.post(`/services/${routeSlug}/${requestToReject.value}/reject`, {}, {
        onFinish: () => closeRejectModal(),
    });
};

const downloadCertificate = (request: CertificateRequest) => {
    const routeSlug = getRouteSlug(request.type);
    window.open(`/services/${routeSlug}/${request.id}/download`, '_blank');
};

const printCertificate = (request: CertificateRequest) => {
    const routeSlug = getRouteSlug(request.type);
    const url = `/services/${routeSlug}/${request.id}/download?view=true`;
    const iframe = document.createElement('iframe');
    iframe.style.position = 'fixed';
    iframe.style.right = '0';
    iframe.style.bottom = '0';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = '0';
    iframe.src = url;
    
    document.body.appendChild(iframe);
    
    if (iframe.contentWindow) {
        iframe.contentWindow.onload = () => {
            iframe.contentWindow?.focus();
            iframe.contentWindow?.print();
            setTimeout(() => {
               document.body.removeChild(iframe); 
            }, 60000);
        };
    }
};
</script>

<template>
    <Head title="Certificate Approvals" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 max-w-7xl mx-auto">
            <!-- Header -->
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Certificate Approvals</h1>
                    <p class="text-muted-foreground mt-1">Review and process employee certificate requests</p>
                </div>
                <Button 
                    variant="outline" 
                    @click="toggleHistory"
                    :class="{'bg-primary/10 text-primary border-primary/30': isHistory}"
                >
                    <FileText class="w-4 h-4 mr-2" />
                    {{ isHistory ? 'Back to Pending' : 'View History' }}
                </Button>
            </div>

            <!-- Search and Filter Bar -->
            <div class="flex flex-col sm:flex-row gap-4 justify-between">
                 <div class="relative w-full sm:w-[350px]">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                        v-model="searchQuery"
                        placeholder="Search by name, ID, or reference..."
                        class="pl-10"
                    />
                </div>
                <Select v-model="typeFilter">
                    <SelectTrigger class="w-full sm:w-[200px]">
                        <SelectValue>
                            {{ getFilterLabel(typeFilter) }}
                        </SelectValue>
                    </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Types</SelectItem>
                            <SelectItem value="employment_certificate">Employment Certificate (EC)</SelectItem>
                            <SelectItem value="release_letter">Release Letter (RL)</SelectItem>
                            <SelectItem value="visa_recommendation_letter">Visa Recommendation Letter (VRL)</SelectItem>
                            <SelectItem value="experience_certificate">Experience Certificate (XC)</SelectItem>
                        </SelectContent>
                    </Select>
            </div>

            <!-- Requests Table -->
            <Card class="border-border/50 shadow-sm">
                <CardContent class="p-0 min-h-[400px]">
                    <div v-if="filteredRequestsData.length === 0" class="flex flex-col items-center justify-center min-h-[400px] text-muted-foreground">
                        <FileText class="h-12 w-12 mx-auto mb-3 opacity-50" />
                        <p class="text-lg">No requests found</p>
                        <p class="text-sm">Try adjusting your search or filter criteria</p>
                    </div>
                    <Table v-else>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent border-border/50">
                                <TableHead class="w-[120px]">Employee ID</TableHead>
                                <TableHead class="w-[250px]">Employee</TableHead>
                                <TableHead class="w-[160px]">Type</TableHead>
                                <TableHead class="w-[200px]">Purpose</TableHead>
                                <TableHead class="w-[140px]">Status</TableHead>
                                <TableHead class="w-[160px]">{{ isHistory ? 'Processed Date' : 'Request Date' }}</TableHead>
                                <TableHead class="text-center">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="request in filteredRequestsData"
                                :key="request.id"
                                class="border-border/50"
                            >

                                <TableCell>
                                    <span class="font-medium text-muted-foreground">{{ request.user.employee_id }}</span>
                                </TableCell>
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
                                        {{ getTypeCode(request.type) }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div class="max-w-[200px] truncate" :title="request.purpose">
                                        {{ request.purpose }}
                                    </div>
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
                                <TableCell class="text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Pending/Authorized View: Action Buttons -->
                                        <template v-if="!isHistory">
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
                                                @click="authorizeRequest(request)"
                                            >
                                                Authorize
                                            </Button>
                                            <!-- Admin sees Approve & Issue for pending/authorized -->
                                            <Button
                                                v-if="isAdmin"
                                                size="sm"
                                                class="text-xs bg-primary hover:bg-primary/90"
                                                @click="goToReview(request)"
                                            >
                                                Approve & Issue
                                            </Button>
                                        </template>

                                        <!-- History View: View/Download Buttons -->
                                        <template v-else>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                class="h-8 px-3 text-xs"
                                                :disabled="request.status !== 'issued'"
                                                @click="goToReview(request)"
                                            >
                                                View
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                class="h-8 w-8 p-0"
                                                :disabled="request.status !== 'issued'"
                                                @click="downloadCertificate(request)"
                                                title="Download PDF"
                                            >
                                                <Download class="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                class="h-8 w-8 p-0"
                                                :disabled="request.status !== 'issued'"
                                                @click="printCertificate(request)"
                                                title="Print"
                                            >
                                                <Printer class="h-4 w-4" />
                                            </Button>
                                        </template>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- Server-side Pagination -->
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
                                @click="link.url && router.visit(link.url, { preserveState: true, preserveScroll: true, data: { history: isHistory ? 1 : 0 } })"
                            >
                                <span v-html="link.label"></span>
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
                        No
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
