<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { FileText, Clock, CheckCircle, XCircle, AlertCircle, History, Check, X, Mail, Download, Plus, Loader2 } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Services', href: '#' },
    { title: 'Employee Certificate', href: '/services/employment-certificate' },
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
    closing_date: string | null; // Added closing_date
    nationality: string;
}

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

interface ActiveRequest {
    id: number;
    ref_id: string;
    status: string;
    created_at: string;
}

interface IssuedCertificate {
    id: number;
    ref_id: string;
    status: string;
    issued_at: string;
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
    purposes: Record<string, string>;
    employeeInfo: EmployeeInfo;
    activeRequest: ActiveRequest | null;
    latestIssuedCertificate: IssuedCertificate | null;
    issuerInfo: IssuerInfo;
    companyInfo: CompanyInfo;
    currentType: string;
}

const props = withDefaults(defineProps<Props>(), {
    currentType: 'employment_certificate',
});
const page = usePage();

const checklistItems = computed(() => [
    {
        label: 'Employee name is correct',
        valid: !!props.employeeInfo.name,
        required: true,
    },
    {
        label: 'Employee ID is valid',
        valid: !!props.employeeInfo.employee_id,
        required: true,
    },
    {
        label: "Father's name is provided",
        valid: !!props.employeeInfo.fathers_name,
        required: true,
    },
    {
        label: "Mother's name is provided",
        valid: !!props.employeeInfo.mothers_name,
        required: true,
    },
    {
        label: 'NID number is valid',
        valid: !!props.employeeInfo.nid_number,
        required: true,
    },
    {
        label: 'Department is assigned',
        valid: !!props.employeeInfo.department,
        required: true,
    },
    {
        label: 'Designation is specified',
        valid: !!props.employeeInfo.designation,
        required: true,
    },
    {
        label: 'Joining date is recorded',
        valid: !!props.employeeInfo.joining_date,
        required: true,
    },
    {
        label: 'Resignation date is recorded',
        valid: !!props.employeeInfo.closing_date,
        required: ['release_letter', 'experience_certificate'].includes(props.currentType),
    },
]);

const isProfileComplete = computed(() => {
    return checklistItems.value.every(item => !item.required || item.valid);
});

const form = useForm({
    purpose: '',
    purpose_other: '',
    urgency: 'normal',
    remarks: '',
    agreement: false,
    type: props.currentType,
    start_date: '',
    end_date: '',
    passport_number: '',
    passport_issue_date: '',
    passport_expiry_date: '',
    passport_issue_place: '',
});

// Watch for type change (no need to reset purpose on init, just keep type synced)
watch(() => props.currentType, (newType) => {
    form.type = newType;
});

// Dynamic URL helpers based on type
const getBaseUrl = computed(() => {
    switch (props.currentType) {
        case 'visa_recommendation_letter': return '/services/visa-recommendation-letter';
        case 'release_letter': return '/services/release-letter';
        case 'experience_certificate': return '/services/experience-certificate';
        default: return '/services/employment-certificate';
    }
});

const getHistoryUrl = computed(() => `${getBaseUrl.value}/history`);
const getNewRequestUrl = computed(() => `${getBaseUrl.value}?new=1`);


const submitRequest = () => {
    form.post(getBaseUrl.value, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};

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
        default:
            return 'bg-gray-500/20 text-gray-400 border-gray-500/30';
    }
};

const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatCurrentDate = () => {
    return new Date().toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: '2-digit',
    });
};

const getPurposeDisplay = (purpose: string, purposeOther: string | null) => {
    if (purpose === 'other') {
        return purposeOther || 'Other';
    }
    return props.purposes[purpose] || purpose;
};

const downloadCertificate = (id: number) => {
    window.open(`${getBaseUrl.value}/${id}/download`, '_blank');
};

// Email Button Logic
const emailStatus = ref<'idle' | 'sending' | 'success' | 'error'>('idle');

const sendEmail = (id: number) => {
    if (emailStatus.value !== 'idle') return;

    emailStatus.value = 'sending';

    router.post(`${getBaseUrl.value}/${id}/email`, { recipient: 'self' }, {
        preserveScroll: true,
        onSuccess: () => {
            emailStatus.value = 'success';
            setTimeout(() => {
                emailStatus.value = 'idle';
            }, 3000);
        },
        onError: () => {
            emailStatus.value = 'error';
            setTimeout(() => {
                emailStatus.value = 'idle';
            }, 3000);
        },
    });
};
</script>

<template>
    <Head title="Employment Certificate" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 max-w-6xl mx-auto">
            <!-- Header -->

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold">
                        <span v-if="props.currentType === 'visa_recommendation_letter'">Visa Recommendation Letter</span>
                        <span v-else-if="props.currentType === 'release_letter'">Release Letter</span>
                        <span v-else-if="props.currentType === 'experience_certificate'">Experience Certificate</span>
                        <span v-else>Employment Certificate</span>
                    </h1>
                    <p class="text-xs md:text-sm text-muted-foreground mt-1">Submit a request to receive your official document</p>
                </div>
                <!-- Show both buttons when viewing issued certificate -->
                <div v-if="props.latestIssuedCertificate && !props.activeRequest" class="flex items-center gap-2 w-full md:w-auto">
                    <Button class="gap-2 flex-1 md:flex-none" @click="$inertia.visit(getNewRequestUrl)">
                        <Plus class="h-4 w-4" />
                        New Request
                    </Button>
                    <Button variant="outline" class="gap-2 flex-1 md:flex-none" @click="$inertia.visit(getHistoryUrl)">
                        <History class="h-4 w-4" />
                        History
                    </Button>
                </div>
                <Button v-else variant="outline" class="gap-2 w-full md:w-auto" @click="$inertia.visit(getHistoryUrl)">
                    <History class="h-4 w-4" />
                    History
                </Button>
            </div>

            <div class="grid gap-6 lg:grid-cols-[1fr_400px]">
                <!-- Issued Certificate Display -->
                <template v-if="props.latestIssuedCertificate && !props.activeRequest">
                    <div class="lg:col-span-2">
                        <Card class="border-border/50 shadow-sm">
                            <CardHeader class="md:hidden">
                                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                    <div class="space-y-1">
                                        <CardTitle class="text-lg">Certificate Issued</CardTitle>
                                        <CardDescription>
                                            Issued on {{ formatDate(props.latestIssuedCertificate.issued_at) }}
                                        </CardDescription>
                                    </div>
                                    <Badge variant="outline" class="w-fit bg-green-50 text-green-700 border-green-200">
                                        <CheckCircle class="w-3 h-3 mr-1" />
                                        Active
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent class="p-0">
                                <!-- Desktop View: Certificate Preview via iframe -->
                                <div class="hidden md:block">
                                    <div class="bg-muted/50 p-6">
                                        <!-- Dynamic Certificate Preview via iframe -->
                                        <div class="bg-white rounded-lg shadow-inner border mx-auto overflow-hidden" style="max-width: 680px; height: 800px;">
                                            <iframe 
                                                :src="`${getBaseUrl}/${props.latestIssuedCertificate.id}/preview`" 
                                                class="w-full h-full border-0"
                                                title="Certificate Preview"
                                            ></iframe>
                                        </div>
                                    </div>
                                    
                                    <!-- Desktop Action Buttons -->
                                    <div v-if="page.props.auth.user.role === 'admin'" class="flex items-center justify-center gap-4 py-4 border-t border-border/50">
                                        <Button
                                            variant="outline"
                                            class="gap-2 min-w-[140px] transition-all duration-300"
                                            :class="{
                                                'bg-green-50 text-green-600 border-green-200 hover:bg-green-100': emailStatus === 'success',
                                                'bg-red-50 text-red-600 border-red-200 hover:bg-red-100': emailStatus === 'error'
                                            }"
                                            @click="sendEmail(props.latestIssuedCertificate.id)"
                                            :disabled="emailStatus !== 'idle'"
                                        >
                                            <template v-if="emailStatus === 'idle'">
                                                <Mail class="h-4 w-4" />
                                                Send to Email
                                            </template>
                                            <template v-else-if="emailStatus === 'sending'">
                                                <Loader2 class="h-4 w-4 animate-spin" />
                                                Sending...
                                            </template>
                                            <template v-else-if="emailStatus === 'success'">
                                                <Check class="h-4 w-4" />
                                                Sent!
                                            </template>
                                            <template v-else-if="emailStatus === 'error'">
                                                <XCircle class="h-4 w-4" />
                                                Failed
                                            </template>
                                        </Button>
                                        <Button
                                            class="gap-2"
                                            @click="downloadCertificate(props.latestIssuedCertificate.id)"
                                        >
                                            <Download class="h-4 w-4" />
                                            Download PDF
                                        </Button>
                                    </div>
                                </div>

                                <!-- Mobile View: Simplified Details -->
                                <div class="block md:hidden p-4">
                                    <div class="rounded-lg bg-muted/50 p-4 border border-border/50">
                                        <div class="space-y-4">
                                            <div class="space-y-1">
                                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Employee Name</p>
                                                <p class="font-medium text-lg">{{ props.employeeInfo.name }}</p>
                                            </div>
                                            <div class="space-y-1">
                                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Reference ID</p>
                                                <p class="font-mono text-lg">{{ props.latestIssuedCertificate.ref_id }}</p>
                                            </div>
                                            <div class="space-y-1">
                                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Issued Date</p>
                                                <p class="font-medium">{{ formatDate(props.latestIssuedCertificate.issued_at) }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Mobile Action Buttons -->
                                    <div v-if="page.props.auth.user.role === 'admin'" class="flex flex-col gap-3 mt-6">
                                        <Button
                                            variant="outline"
                                            class="w-full gap-2 transition-all duration-300"
                                            :class="{
                                                'bg-green-50 text-green-600 border-green-200 hover:bg-green-100': emailStatus === 'success',
                                                'bg-red-50 text-red-600 border-red-200 hover:bg-red-100': emailStatus === 'error'
                                            }"
                                            @click="sendEmail(props.latestIssuedCertificate.id)"
                                            :disabled="emailStatus !== 'idle'"
                                        >
                                            <template v-if="emailStatus === 'idle'">
                                                <Mail class="h-4 w-4" />
                                                Send to Email
                                            </template>
                                            <template v-else-if="emailStatus === 'sending'">
                                                <Loader2 class="h-4 w-4 animate-spin" />
                                                Sending...
                                            </template>
                                            <template v-else-if="emailStatus === 'success'">
                                                <Check class="h-4 w-4" />
                                                Sent!
                                            </template>
                                            <template v-else-if="emailStatus === 'error'">
                                                <XCircle class="h-4 w-4" />
                                                Failed
                                            </template>
                                        </Button>
                                        <Button
                                            class="w-full gap-2"
                                            @click="downloadCertificate(props.latestIssuedCertificate.id)"
                                        >
                                            <Download class="h-4 w-4" />
                                            Download PDF
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </template>

                <!-- Active Request Status Tracker -->
                <template v-else-if="props.activeRequest">
                    <div class="lg:col-span-2">
                        <Card class="border-border/50 shadow-sm">
                            <CardHeader class="pb-4">
                                <CardTitle class="text-lg font-semibold">Request Status</CardTitle>
                                <CardDescription>
                                    Your certificate request <span class="font-medium text-foreground">{{ props.activeRequest.ref_id }}</span> is being processed
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <!-- Status Tracker -->
                                <div class="flex items-center justify-between py-8 px-4">
                                    <!-- Step 1: Submitted -->
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="w-12 h-12 rounded-full bg-green-500/20 flex items-center justify-center">
                                            <CheckCircle class="h-6 w-6 text-green-500" />
                                        </div>
                                        <span class="text-sm font-semibold text-green-500">SUBMITTED</span>
                                        <span class="text-xs text-muted-foreground">{{ formatDate(props.activeRequest.created_at) }}</span>
                                    </div>

                                    <!-- Connector 1 -->
                                    <div class="flex-1 h-1 mx-2" :class="props.activeRequest.status === 'pending' ? 'bg-primary' : 'bg-muted'" />

                                    <!-- Step 2: Admin Review -->
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="w-12 h-12 rounded-full flex items-center justify-center"
                                            :class="props.activeRequest.status === 'pending' ? 'bg-primary' : (props.activeRequest.status === 'issued' ? 'bg-green-500/20' : 'bg-muted')">
                                            <Clock v-if="props.activeRequest.status === 'pending'" class="h-6 w-6 text-white" />
                                            <FileText v-else class="h-6 w-6 text-muted-foreground" />
                                        </div>
                                        <span class="text-sm font-semibold" :class="props.activeRequest.status === 'pending' ? 'text-primary' : 'text-muted-foreground'">ADMIN REVIEW</span>
                                        <span class="text-xs text-muted-foreground">{{ props.activeRequest.status === 'pending' ? 'In Progress' : 'Pending' }}</span>
                                    </div>

                                    <!-- Connector 2 -->
                                    <div class="flex-1 h-1 mx-2" :class="props.activeRequest.status === 'issued' ? 'bg-primary' : 'bg-muted'" />

                                    <!-- Step 4: Issued -->
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="w-12 h-12 rounded-full bg-muted flex items-center justify-center">
                                            <FileText class="h-6 w-6 text-muted-foreground" />
                                        </div>
                                        <span class="text-sm font-semibold text-muted-foreground">ISSUED</span>
                                        <span class="text-xs text-muted-foreground">Waiting</span>
                                    </div>
                                </div>

                                <div class="text-center mt-4">
                                    <p class="text-sm text-muted-foreground">You will be notified once your certificate is ready for download.</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </template>

                <!-- Normal Form View -->
                <template v-else>
                <!-- Left Column: Request Form -->
                <div class="space-y-6">
                    <!-- Request Form Card -->
                    <Card class="border-border/50 shadow-sm">
                        <CardHeader class="pb-4">
                            <CardTitle class="text-lg font-semibold flex items-center gap-2">
                                New Request
                            </CardTitle>
                            <CardDescription>Fill in the details below to submit your certificate request</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form @submit.prevent="submitRequest" class="space-y-6">
                                <!-- Purpose of Request (Only for Employment Certificate) -->
                                <div class="space-y-2" v-if="props.currentType === 'employment_certificate'">
                                    <Label for="purpose">Purpose of Request</Label>
                                    <Select v-model="form.purpose">
                                        <SelectTrigger id="purpose" class="w-full">
                                            <SelectValue placeholder="Select a purpose" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="(label, value) in purposes"
                                                :key="value"
                                                :value="value"
                                            >
                                                {{ label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="form.errors.purpose" class="text-sm text-destructive">{{ form.errors.purpose }}</p>
                                </div>

                                <!-- Visa Recommendation Fields -->
                                <template v-if="props.currentType === 'visa_recommendation_letter'">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="space-y-2">
                                            <Label>Travel Start Date</Label>
                                            <input type="date" v-model="form.start_date" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                            <p v-if="form.errors.start_date" class="text-sm text-destructive">{{ form.errors.start_date }}</p>
                                        </div>
                                        <div class="space-y-2">
                                            <Label>Travel End Date</Label>
                                            <input type="date" v-model="form.end_date" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                            <p v-if="form.errors.end_date" class="text-sm text-destructive">{{ form.errors.end_date }}</p>
                                        </div>
                                    </div>

                                    <div class="space-y-4 border rounded-md p-4 bg-muted/20">
                                        <h3 class="font-medium text-sm">Passport Details</h3>
                                        <div class="space-y-2">
                                            <Label>Passport Number</Label>
                                            <input type="text" v-model="form.passport_number" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="Passport Number">
                                            <p v-if="form.errors.passport_number" class="text-sm text-destructive">{{ form.errors.passport_number }}</p>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="space-y-2">
                                                <Label>Issue Date</Label>
                                                <input type="date" v-model="form.passport_issue_date" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                                <p v-if="form.errors.passport_issue_date" class="text-sm text-destructive">{{ form.errors.passport_issue_date }}</p>
                                            </div>
                                            <div class="space-y-2">
                                                <Label>Expiry Date</Label>
                                                <input type="date" v-model="form.passport_expiry_date" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                                <p v-if="form.errors.passport_expiry_date" class="text-sm text-destructive">{{ form.errors.passport_expiry_date }}</p>
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <Label>Place of Issue</Label>
                                            <input type="text" v-model="form.passport_issue_place" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="e.g. Dhaka">
                                            <p v-if="form.errors.passport_issue_place" class="text-sm text-destructive">{{ form.errors.passport_issue_place }}</p>
                                        </div>
                                    </div>
                                </template>


                                <!-- Urgency Level -->
                                <div class="space-y-2">
                                    <Label>Urgency Level</Label>
                                    <div class="flex gap-2">
                                        <Button
                                            type="button"
                                            :variant="form.urgency === 'normal' ? 'default' : 'outline'"
                                            @click="form.urgency = 'normal'"
                                            class="h-9 px-6"
                                        >
                                            Normal
                                        </Button>
                                        <Button
                                            type="button"
                                            :variant="form.urgency === 'urgent' ? 'destructive' : 'outline'"
                                            @click="form.urgency = 'urgent'"
                                            class="h-9 px-6"
                                        >
                                            Urgent
                                        </Button>
                                    </div>
                                    <p class="text-xs text-muted-foreground">
                                        <span v-if="form.urgency === 'urgent'" class="text-yellow-500">
                                            Urgent requests are processed within 24 hours.
                                        </span>
                                        <span v-else>
                                            Normal requests are processed within 3-5 business days.
                                        </span>
                                    </p>
                                </div>

                                <!-- Additional Remarks -->
                                <div class="space-y-2">
                                    <Label for="remarks">Additional Remarks</Label>
                                    <Textarea
                                        id="remarks"
                                        v-model="form.remarks"
                                        placeholder="Any additional information or special instructions..."
                                        class="min-h-[100px]"
                                    />
                                    <p v-if="form.errors.remarks" class="text-sm text-destructive">{{ form.errors.remarks }}</p>
                                </div>

                                <!-- Agreement Checkbox -->
                                <label class="flex items-start gap-3 p-4 rounded-lg bg-muted/50 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        v-model="form.agreement"
                                        class="mt-1 h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                    />
                                    <div class="space-y-1">
                                        <span class="text-sm font-medium">
                                            I confirm that the information displayed is correct
                                        </span>
                                        <p class="text-xs text-muted-foreground">
                                            By submitting this request, I confirm that my employee information is accurate and up-to-date.
                                        </p>
                                    </div>
                                </label>
                                <p v-if="form.errors.agreement" class="text-sm text-destructive">{{ form.errors.agreement }}</p>

                                <!-- Submit Button -->
                                <Button
                                    type="submit"
                                    class="w-full"
                                    size="lg"
                                    :disabled="form.processing || !form.agreement"
                                >
                                    <span v-if="form.processing">Submitting...</span>
                                    <span v-else>Submit Request</span>
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right Column: Verification Checklist -->
                <div class="space-y-6">
                    <Card class="border-border/50 shadow-sm">
                        <CardHeader class="pb-4">
                            <CardTitle class="text-[15px] font-semibold flex items-center gap-2">
                                Verification Checklist
                            </CardTitle>
                            <CardDescription>Auto-verified based on employee data</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-3">
                                <div
                                    v-for="(item, index) in checklistItems"
                                    :key="index"
                                    class="flex items-center justify-between group"
                                >
                                    <div class="flex items-center gap-3">
                                        <!-- Status Dot -->
                                        <div 
                                            class="h-2.5 w-2.5 rounded-full"
                                            :class="item.valid ? 'bg-green-500' : 'bg-red-500'"
                                        ></div>
                                        
                                        <span
                                            class="text-sm transition-colors"
                                            :class="[
                                                item.valid ? 'text-foreground' : 'text-muted-foreground',
                                                item.required && !item.valid ? 'text-destructive font-medium' : ''
                                            ]"
                                        >
                                            {{ item.label }}
                                            <span v-if="item.required" class="text-destructive ml-0.5">*</span>
                                        </span>
                                    </div>
                                    <Badge
                                        v-if="!item.valid && item.required"
                                        variant="outline"
                                        class="text-[10px] px-1.5 py-0 border-destructive/30 text-destructive bg-destructive/5"
                                    >
                                        Missing
                                    </Badge>
                                </div>
                            </div>

                            <Separator />

                            <div v-if="!isProfileComplete" class="space-y-3 rounded-md bg-yellow-500/10 p-4 border border-yellow-500/20">
                                <div class="flex gap-3">
                                    <AlertCircle class="h-5 w-5 text-yellow-500 shrink-0 mt-0.5" />
                                    <div class="space-y-1">
                                        <p class="text-sm font-medium text-yellow-500">Credientials Missing</p>
                                        <p class="text-xs text-muted-foreground leading-relaxed">
                                            Required credentials is needed to get the certificate. Please add this information from your profile setting.
                                        </p>
                                    </div>
                                </div>
                                <Button 
                                    variant="outline" 
                                    size="sm" 
                                    class="w-full bg-background/50 hover:bg-background border-yellow-500/30 hover:border-yellow-500/50 text-yellow-600"
                                    @click="$inertia.visit('/settings/profile')"
                                >
                                    Go to Profile Setting
                                </Button>
                            </div>
                            <div v-else class="rounded-md bg-green-500/10 p-3 border border-green-500/20">
                                <div class="flex gap-2">
                                    <CheckCircle class="h-4 w-4 text-green-500 shrink-0 mt-0.5" />
                                    <div class="space-y-1">
                                        <p class="text-xs font-medium text-green-500">Eligible</p>
                                        <p class="text-xs text-muted-foreground">
                                            You are eligible to get the certificate.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
                </template>
            </div>
        </div>
    </AppLayout>
</template>
