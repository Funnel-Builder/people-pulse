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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { ArrowLeft, CheckCircle, XCircle, Download, Mail, FileText, Printer, AlertCircle, Building2, User as UserIcon, AtSign, Check, X, Loader2 } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Services', href: '#' },
    { title: 'Certificate Approvals', href: '/services/approvals' },
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
    profile_picture: string | null;
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
const rejectModalOpen = ref(false);
const isRejecting = ref(false);
const emailStates = ref<{
    employee: 'idle' | 'sending' | 'success' | 'error';
    self: 'idle' | 'sending' | 'success' | 'error';
}>({
    employee: 'idle',
    self: 'idle',
});

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

const hasMissingInfo = computed(() => {
    return !props.employeeInfo.fathers_name || 
           !props.employeeInfo.mothers_name || 
           !props.employeeInfo.nid_number;
});

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
    router.get('/services/approvals');
};

const issueCertificate = () => {
    isIssuing.value = true;
    router.post(`/services/employment-certificate/${props.request.id}/issue`, {}, {
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
    rejectModalOpen.value = true;
};

const closeRejectModal = () => {
    rejectModalOpen.value = false;
    isRejecting.value = false;
};

const confirmReject = () => {
    isRejecting.value = true;
    router.post(`/services/employment-certificate/${props.request.id}/reject`, {}, {
        onFinish: () => closeRejectModal(),
    });
};

const downloadCertificate = () => {
    window.open(`/services/employment-certificate/${props.request.id}/download`, '_blank');
};

const printCertificate = () => {
    const url = `/services/employment-certificate/${props.request.id}/download?view=true`;
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
            // Clean up after print dialog closes (or close enough estimate)
            // Note: There is no reliable event for "print dialog closed", so we leave it or remove after long timeout
            setTimeout(() => {
               document.body.removeChild(iframe); 
            }, 60000); // 1 minute timeout to ensure it doesn't clutter DOM indefinitely
        };
    }
};

const emailCertificate = (recipient: 'employee' | 'self') => {
    emailStates.value[recipient] = 'sending';
    
    router.post(`/services/employment-certificate/${props.request.id}/email`, {
        recipient,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            emailStates.value[recipient] = 'success';
            setTimeout(() => {
                emailStates.value[recipient] = 'idle';
            }, 2000);
        },
        onError: () => {
            emailStates.value[recipient] = 'error';
            setTimeout(() => {
                emailStates.value[recipient] = 'idle';
            }, 2000);
        },
    });
};

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const openGmail = () => {
    const subject = encodeURIComponent(`Regarding Employment Certificate - ${props.request.ref_id}`);
    const body = encodeURIComponent(`Hello ${props.employeeInfo.name},\n\nRegarding your certificate request...`);
    window.open(`https://mail.google.com/mail/?view=cm&fs=1&to=${props.employeeInfo.email}&su=${subject}&body=${body}`, '_blank');
};

const sendMissingInfoEmail = () => {
    router.post(`/services/employment-certificate/${props.request.id}/request-missing-info`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Optional: You could show a toast here if not handled globally by your flash messages
        },
    });
};

const closeModal = () => {
    showIssuedModal.value = false;
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
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle class="text-lg font-semibold flex items-center gap-2">
                                        Certificate Preview
                                    </CardTitle>
                                    <CardDescription>This is how the issued certificate will appear</CardDescription>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Badge variant="outline" class="bg-blue-50 text-blue-700 border-blue-200">
                                        {{ getPurposeDisplay(request.purpose, request.purpose_other) }}
                                    </Badge>
                                    <Badge 
                                        variant="outline" 
                                        :class="[
                                            request.urgency === 'urgent' 
                                                ? 'bg-red-50 text-red-700 border-red-200' 
                                                : 'bg-gray-50 text-gray-700 border-gray-200'
                                        ]"
                                    >
                                        {{ request.urgency.charAt(0).toUpperCase() + request.urgency.slice(1) }}
                                    </Badge>
                                </div>
                            </div>
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


                    <!-- Request Details (Moved to right column) -->
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
                    <!-- Employee Summary -->
                    <!-- Employee Summary -->
                    <Card class="border-border/50 shadow-sm overflow-hidden">
                        <CardContent class="p-6">
                            <div class="flex items-center gap-5">
                                <Avatar class="h-14 w-14 border-2 border-background shadow-sm">
                                    <AvatarImage v-if="employeeInfo.profile_picture" :src="employeeInfo.profile_picture" />
                                    <AvatarFallback class="bg-primary/10 text-primary text-lg font-semibold">
                                        {{ getInitials(employeeInfo.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                
                                <div class="space-y-1">
                                    <h3 class="font-bold text-lg text-foreground leading-none">{{ employeeInfo.name }}</h3>
                                    <p class="text-sm text-muted-foreground font-medium">{{ employeeInfo.designation }}</p>
                                    <div class="flex items-center gap-3 text-xs text-muted-foreground pt-1">
                                        <div class="flex items-center gap-1.5">
                                            <UserIcon class="h-3.5 w-3.5" />
                                            <span class="font-medium text-foreground/80">{{ employeeInfo.employee_id }}</span>
                                        </div>
                                        <span class="text-border">•</span>
                                        <div class="flex items-center gap-1.5">
                                            <Building2 class="h-3.5 w-3.5" />
                                            <span>{{ employeeInfo.department || 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="ml-auto flex items-center gap-6">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="h-9 w-9 text-muted-foreground hover:text-primary hover:bg-primary/5 rounded-full"
                                                title="Send Email"
                                            >
                                                <Mail class="h-5 w-5" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem @click="sendMissingInfoEmail" :disabled="!hasMissingInfo">
                                                <AlertCircle class="mr-2 h-4 w-4 text-orange-500" />
                                                <span>Request Missing Info</span>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem @click="openGmail">
                                                <Mail class="mr-2 h-4 w-4" />
                                                <span>Custom Message</span>
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Action Buttons -->
                    <!-- Action Buttons -->
                    <div class="space-y-3">
                        <template v-if="request.status === 'issued'">
                            <Button
                                class="w-full"
                                size="lg"
                                @click="downloadCertificate"
                            >
                                <Download class="h-4 w-4 mr-2" />
                                Download Certificate
                            </Button>
                        </template>
                        <template v-else>
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
                                variant="destructive"
                                class="w-full bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 hover:text-red-700 hover:border-red-300 shadow-sm"
                                @click="rejectRequest"
                            >
                                <XCircle class="h-4 w-4 mr-2" />
                                Reject Request
                            </Button>
                        </template>
                    </div>
                </div>
            </div>
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
                    <div class="bg-muted/30 rounded-lg p-6 text-center border border-border/50">
                        <FileText class="h-12 w-12 mx-auto mb-3 text-muted-foreground/50" />
                        <p class="font-medium text-lg text-foreground">Employment Certificate</p>
                        <p class="text-sm text-muted-foreground">{{ request.ref_id }}</p>
                        <div class="mt-4 pt-4 border-t border-border/50">
                            <p class="text-xs uppercase tracking-wide text-muted-foreground mb-1">Issued To</p>
                            <p class="text-base font-bold text-foreground">{{ employeeInfo.name }}</p>
                        </div>
                    </div>
                </div>

                <DialogFooter class="flex-col sm:flex-row gap-3">


                    <Button
                        :variant="emailStates.self === 'idle' ? 'outline' : (emailStates.self === 'success' ? 'default' : 'destructive')"
                        @click="emailCertificate('self')"
                        :disabled="emailStates.self === 'sending'"
                        class="flex-1 transition-all duration-300"
                        :class="emailStates.self === 'success' ? 'bg-green-600 hover:bg-green-700 text-white border-transparent' : ''"
                    >
                        <template v-if="emailStates.self === 'sending'">
                           <span class="animate-spin mr-2">⏳</span> Sending...
                        </template>
                        <template v-else-if="emailStates.self === 'success'">
                            <Check class="h-4 w-4 mr-2" /> Sent
                        </template>
                         <template v-else-if="emailStates.self === 'error'">
                             <X class="h-4 w-4 mr-2" /> Failed
                        </template>
                        <template v-else>
                            <AtSign class="h-4 w-4 mr-2" />
                            Email to Me
                        </template>
                    </Button>
                    
                    <Button
                        @click="printCertificate"
                        class="flex-1"
                        variant="secondary"
                    >
                        <Printer class="h-4 w-4 mr-2" />
                        Print
                    </Button>

                    <Button
                        @click="downloadCertificate"
                        class="flex-1"
                    >
                        <Download class="h-4 w-4 mr-2" />
                        Download
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
