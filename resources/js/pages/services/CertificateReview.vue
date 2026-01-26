<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { ArrowLeft, CheckCircle, XCircle, Download, Mail, FileText, Printer, AlertCircle } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Services', href: '#' },
    { title: 'Certificate Approvals', href: '/services/certificate/approvals' },
    { title: 'Review', href: '#' },
];

interface EmployeeInfo {
    id: number;
    employee_id: string;
    name: string;
    email: string;
    designation: string;
    department: string | null;
    sub_department: string | null;
    fathers_name: string | null;
    mothers_name: string | null;
    nid_number: string | null;
    joining_date: string | null;
    nationality: string;
}

interface CertificateRequest {
    id: number;
    ref_id: string;
    user_id: number;
    purpose: string;
    purpose_other: string | null;
    urgency: string;
    status: string;
    remarks: string | null;
    created_at: string;
}

interface IssuerInfo {
    name: string;
    title: string;
    phone: string;
}

interface CompanyInfo {
    name: string;
    short_name: string;
}

interface Props {
    request: CertificateRequest;
    employeeInfo: EmployeeInfo;
    issuerInfo: IssuerInfo;
    companyInfo: CompanyInfo;
}

const props = defineProps<Props>();

const showIssuedModal = ref(false);
const isIssuing = ref(false);
const emailingTo = ref<string | null>(null);

// Verification checklist - auto-check based on data availability
const verificationChecks = computed(() => [
    {
        id: 'name',
        label: 'Employee name is correct',
        checked: !!props.employeeInfo.name,
        required: true,
    },
    {
        id: 'employee_id',
        label: 'Employee ID is valid',
        checked: !!props.employeeInfo.employee_id,
        required: true,
    },
    {
        id: 'fathers_name',
        label: "Father's name is provided",
        checked: !!props.employeeInfo.fathers_name,
        required: false,
    },
    {
        id: 'mothers_name',
        label: "Mother's name is provided",
        checked: !!props.employeeInfo.mothers_name,
        required: false,
    },
    {
        id: 'nid',
        label: 'NID number is valid',
        checked: !!props.employeeInfo.nid_number,
        required: false,
    },
    {
        id: 'department',
        label: 'Department is assigned',
        checked: !!props.employeeInfo.department,
        required: true,
    },
    {
        id: 'designation',
        label: 'Designation is specified',
        checked: !!props.employeeInfo.designation,
        required: true,
    },
    {
        id: 'joining_date',
        label: 'Joining date is recorded',
        checked: !!props.employeeInfo.joining_date,
        required: true,
    },
]);

const allRequiredChecksPassed = computed(() => {
    return verificationChecks.value
        .filter(c => c.required)
        .every(c => c.checked);
});

const purposes: Record<string, string> = {
    visa_application: 'Visa Application',
    bank_loan: 'Bank Loan / Mortgage',
    apartment_leasing: 'Apartment Leasing',
    higher_education: 'Higher Education',
    other: 'Other',
};

const getPurposeDisplay = (purpose: string, purposeOther: string | null) => {
    if (purpose === 'other') {
        return purposeOther || 'Other';
    }
    return purposes[purpose] || purpose;
};

const formatCurrentDate = () => {
    return new Date().toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: '2-digit',
    });
};

const goBack = () => {
    router.get('/services/certificate/approvals');
};

const issueCertificate = () => {
    isIssuing.value = true;
    router.post(`/services/certificate/${props.request.id}/issue`, {}, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            isIssuing.value = false;
            showIssuedModal.value = true;
        },
        onError: () => {
            isIssuing.value = false;
        },
    });
};

const rejectRequest = () => {
    if (confirm('Are you sure you want to reject this request?')) {
        router.post(`/services/certificate/${props.request.id}/reject`);
    }
};

const downloadCertificate = () => {
    window.open(`/services/certificate/${props.request.id}/download`, '_blank');
};

const emailCertificate = (recipient: 'employee' | 'self') => {
    emailingTo.value = recipient;
    router.post(`/services/certificate/${props.request.id}/email`, {
        recipient,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            emailingTo.value = null;
        },
        onError: () => {
            emailingTo.value = null;
        },
    });
};

const closeModal = () => {
    showIssuedModal.value = false;
    router.get('/services/certificate/approvals');
};
</script>

<template>
    <Head title="Review Certificate Request" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="icon" @click="goBack">
                    <ArrowLeft class="h-5 w-5" />
                </Button>
                <div>
                    <h1 class="text-2xl font-bold">Review Certificate Request</h1>
                    <p class="text-muted-foreground">{{ request.ref_id }}</p>
                </div>
                <Badge v-if="request.urgency === 'urgent'" variant="destructive" class="ml-2">
                    Urgent
                </Badge>
            </div>

            <!-- Two Column Layout -->
            <div class="grid gap-6 lg:grid-cols-[1fr_400px]">
                <!-- Left Column: Certificate Preview -->
                <div class="space-y-6">
                    <Card class="border-border/50 shadow-sm">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-lg font-semibold flex items-center gap-2">
                                <FileText class="h-5 w-5 text-primary" />
                                Certificate Preview
                            </CardTitle>
                            <CardDescription>This is how the issued certificate will appear</CardDescription>
                        </CardHeader>
                        <CardContent class="pt-4">
                            <!-- Certificate Preview (mimicking the PDF look) -->
                            <div class="bg-white text-black rounded-lg p-8 shadow-inner border">
                                <!-- Header -->
                                <div class="flex justify-between items-start mb-8">
                                    <p class="text-sm">Ref: {{ request.ref_id }}</p>
                                    <p class="text-sm">Date: {{ formatCurrentDate() }}</p>
                                </div>

                                <!-- Title -->
                                <h2 class="text-center text-xl font-bold underline mb-8">
                                    EMPLOYMENT CERTIFICATE
                                </h2>

                                <!-- Salutation -->
                                <p class="font-semibold mb-4">To Whom It May Concern,</p>

                                <!-- Body -->
                                <!-- Body -->
                                <!-- Body -->
                                <p class="text-justify leading-relaxed mb-4">
                                    To Whom It May Concern, This is to certify that Mr. 
                                    <span>{{ employeeInfo.name || '[Name]' }}</span>
                                    (ID: <span>{{ employeeInfo.employee_id || '[ID]' }}</span>), 
                                    son of <span>{{ employeeInfo.fathers_name || "[Father's Name]" }}</span>
                                    and <span>{{ employeeInfo.mothers_name || "[Mother's Name]" }}</span>, 
                                    National ID Card Number. <span>{{ employeeInfo.nid_number || '[NID Number]' }}</span>, 
                                    has been employed at {{ companyInfo.name }} as a permanent employee since 
                                    <span>{{ employeeInfo.joining_date || '[joining date]' }}</span>. 
                                    Currently he is working in the 
                                    <span>{{ employeeInfo.department || '[Department Name]' }}</span>
                                    <span> ({{ employeeInfo.sub_department || 'Sub-Department Name if applicable' }})</span>
                                    department as a <span>{{ employeeInfo.designation || '[Current Designation]' }}</span>.
                                </p>

                                <p class="text-justify leading-relaxed mb-4">
                                    This certification is being issued on the date of {{ formatCurrentDate() }}
                                    upon {{ employeeInfo.name?.split(' ')[0] || 'his' }} request and can be used for reference purposes.
                                </p>

                                <p class="text-justify leading-relaxed mb-8">
                                    I hereby certify that the above-mentioned information is correct and accurate to the best of my knowledge.
                                </p>

                                <!-- Closing -->
                                <p class="mb-16">Sincerely,</p>

                                <!-- Signature -->
                                <div class="border-t border-black inline-block pt-2 min-w-[200px]">
                                    <p class="font-bold">{{ issuerInfo.name }}</p>
                                    <p class="text-sm">{{ issuerInfo.title }}</p>
                                    <p class="text-sm">Cell: {{ issuerInfo.phone }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Request Details -->
                    <Card class="border-border/50 shadow-sm">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-[15px] font-semibold">Request Details</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <p class="text-sm text-muted-foreground">Purpose</p>
                                    <p class="font-medium">{{ getPurposeDisplay(request.purpose, request.purpose_other) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-muted-foreground">Urgency</p>
                                    <p class="font-medium capitalize">{{ request.urgency }}</p>
                                </div>
                                <div v-if="request.remarks" class="md:col-span-2">
                                    <p class="text-sm text-muted-foreground">Remarks</p>
                                    <p class="font-medium">{{ request.remarks }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right Column: Verification & Actions -->
                <div class="space-y-6">
                    <!-- Verification Checklist -->
                    <Card class="border-border/50 shadow-sm">
                        <CardHeader class="pb-4">
                            <CardTitle class="text-[15px] font-semibold">Verification Checklist</CardTitle>
                            <CardDescription>Auto-verified based on employee data</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div
                                v-for="check in verificationChecks"
                                :key="check.id"
                                class="flex items-center gap-3"
                            >
                                <div
                                    :class="[
                                        'w-5 h-5 rounded flex items-center justify-center',
                                        check.checked ? 'bg-green-500/20' : 'bg-red-500/20'
                                    ]"
                                >
                                    <CheckCircle v-if="check.checked" class="h-3.5 w-3.5 text-green-500" />
                                    <XCircle v-else class="h-3.5 w-3.5 text-red-500" />
                                </div>
                                <span :class="['text-sm', check.checked ? '' : 'text-muted-foreground']">
                                    {{ check.label }}
                                    <span v-if="check.required" class="text-red-500">*</span>
                                </span>
                            </div>

                            <div v-if="!allRequiredChecksPassed" class="mt-4 p-3 rounded-lg bg-yellow-500/10 border border-yellow-500/20">
                                <div class="flex items-start gap-2">
                                    <AlertCircle class="h-4 w-4 text-yellow-500 mt-0.5" />
                                    <p class="text-sm text-yellow-500">
                                        Some required information is missing. The certificate may have incomplete data.
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Employee Summary -->
                    <Card class="border-border/50 shadow-sm">
                        <CardHeader class="pb-4">
                            <CardTitle class="text-[15px] font-semibold">Employee Summary</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-muted-foreground">Name</span>
                                <span class="text-sm font-medium">{{ employeeInfo.name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-muted-foreground">ID</span>
                                <span class="text-sm font-medium">{{ employeeInfo.employee_id }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-muted-foreground">Department</span>
                                <span class="text-sm font-medium">{{ employeeInfo.department || 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-muted-foreground">Designation</span>
                                <span class="text-sm font-medium">{{ employeeInfo.designation }}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Action Buttons -->
                    <div class="space-y-3">
                        <Button
                            class="w-full"
                            size="lg"
                            @click="issueCertificate"
                            :disabled="isIssuing"
                        >
                            <CheckCircle class="h-4 w-4 mr-2" />
                            <span v-if="isIssuing">Issuing...</span>
                            <span v-else>Issue Certificate</span>
                        </Button>
                        <Button
                            variant="outline"
                            class="w-full"
                            @click="rejectRequest"
                        >
                            <XCircle class="h-4 w-4 mr-2" />
                            Reject Request
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Issued Modal -->
        <Dialog :open="showIssuedModal" @update:open="(v) => !v && closeModal()">
            <DialogContent class="sm:max-w-[600px]">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <CheckCircle class="h-5 w-5 text-green-500" />
                        Certificate Issued Successfully
                    </DialogTitle>
                    <DialogDescription>
                        The certificate has been issued. You can now download or email it.
                    </DialogDescription>
                </DialogHeader>

                <div class="py-4">
                    <div class="bg-muted/50 rounded-lg p-4 text-center">
                        <FileText class="h-16 w-16 mx-auto mb-3 text-primary" />
                        <p class="font-medium">Employment Certificate</p>
                        <p class="text-sm text-muted-foreground">{{ request.ref_id }}</p>
                        <p class="text-sm text-muted-foreground">{{ employeeInfo.name }}</p>
                    </div>
                </div>

                <DialogFooter class="flex-col sm:flex-row gap-2">
                    <Button
                        variant="outline"
                        @click="emailCertificate('employee')"
                        :disabled="emailingTo !== null"
                        class="flex-1"
                    >
                        <Mail class="h-4 w-4 mr-2" />
                        <span v-if="emailingTo === 'employee'">Sending...</span>
                        <span v-else>Email to Employee</span>
                    </Button>
                    <Button
                        variant="outline"
                        @click="emailCertificate('self')"
                        :disabled="emailingTo !== null"
                        class="flex-1"
                    >
                        <Mail class="h-4 w-4 mr-2" />
                        <span v-if="emailingTo === 'self'">Sending...</span>
                        <span v-else>Email to Me</span>
                    </Button>
                    <Button
                        @click="downloadCertificate"
                        class="flex-1"
                    >
                        <Download class="h-4 w-4 mr-2" />
                        Download PDF
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
