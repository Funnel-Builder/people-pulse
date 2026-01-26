<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { FileText, Clock, CheckCircle, XCircle, AlertCircle } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Services', href: '#' },
    { title: 'Employee Certificate', href: '/services/certificate' },
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
    purpose: string;
    purpose_other: string | null;
    urgency: string;
    status: string;
    created_at: string;
    issued_at: string | null;
}

interface Props {
    purposes: Record<string, string>;
    employeeInfo: EmployeeInfo;
    myRequests: CertificateRequest[];
}

const props = defineProps<Props>();
const page = usePage();

const form = useForm({
    purpose: '',
    purpose_other: '',
    urgency: 'normal',
    remarks: '',
    agreement: false,
});

const showOtherInput = computed(() => form.purpose === 'other');

const submitRequest = () => {
    form.post('/services/certificate', {
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

const getPurposeDisplay = (purpose: string, purposeOther: string | null) => {
    if (purpose === 'other') {
        return purposeOther || 'Other';
    }
    return props.purposes[purpose] || purpose;
};
</script>

<template>
    <Head title="Request Employment Certificate" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 max-w-6xl mx-auto">
            <!-- Header -->
            <div class="text-center">
                <h1 class="text-2xl font-bold">Request for Employment Certificate</h1>
                <p class="text-muted-foreground mt-1">Submit a request to receive your official employment certificate</p>
            </div>

            <div class="grid gap-6 lg:grid-cols-[1fr_400px]">
                <!-- Left Column: Request Form -->
                <div class="space-y-6">
                    <!-- Request Form Card -->
                    <Card class="border-border/50 shadow-sm">
                        <CardHeader class="pb-4">
                            <CardTitle class="text-lg font-semibold flex items-center gap-2">
                                <FileText class="h-5 w-5 text-primary" />
                                New Request
                            </CardTitle>
                            <CardDescription>Fill in the details below to submit your certificate request</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form @submit.prevent="submitRequest" class="space-y-6">
                                <!-- Purpose of Request -->
                                <div class="space-y-2">
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

                                <!-- Other Purpose Input -->
                                <div v-if="showOtherInput" class="space-y-2">
                                    <Label for="purpose_other">Specify Purpose</Label>
                                    <Textarea
                                        id="purpose_other"
                                        v-model="form.purpose_other"
                                        placeholder="E.g. Address to 'Emirates NBD Bank'"
                                        class="min-h-[80px]"
                                    />
                                    <p v-if="form.errors.purpose_other" class="text-sm text-destructive">{{ form.errors.purpose_other }}</p>
                                </div>

                                <!-- Urgency Level -->
                                <div class="space-y-2">
                                    <Label>Urgency Level</Label>
                                    <div class="flex gap-2">
                                        <Button
                                            type="button"
                                            :variant="form.urgency === 'normal' ? 'default' : 'outline'"
                                            @click="form.urgency = 'normal'"
                                            class="flex-1"
                                        >
                                            Normal
                                        </Button>
                                        <Button
                                            type="button"
                                            :variant="form.urgency === 'urgent' ? 'destructive' : 'outline'"
                                            @click="form.urgency = 'urgent'"
                                            class="flex-1"
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

                <!-- Right Column: Employee Information & Request History -->
                <div class="space-y-6">
                    <!-- Employee Information Card -->
                    <Card class="border-border/50 shadow-sm">
                        <CardHeader class="pb-4">
                            <CardTitle class="text-[15px] font-semibold">Your Information</CardTitle>
                            <CardDescription>This information will appear on your certificate</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <p class="text-sm leading-relaxed">
                                This is to certify that <strong class="text-foreground">{{ employeeInfo.name }}</strong> 
                                (ID: <strong class="text-foreground">{{ employeeInfo.employee_id }}</strong>), 
                                <span v-if="employeeInfo.fathers_name">
                                    son/daughter of <strong class="text-foreground">{{ employeeInfo.fathers_name }}</strong>
                                </span>
                                <span v-if="employeeInfo.mothers_name">
                                    and <strong class="text-foreground">{{ employeeInfo.mothers_name }}</strong>,
                                </span>
                                <span v-if="employeeInfo.nid_number">
                                    National ID Card Number: <strong class="text-foreground">{{ employeeInfo.nid_number }}</strong>,
                                </span>
                                has been employed at <strong class="text-foreground">BD Funnel Builder Limited</strong> as a permanent employee
                                <span v-if="employeeInfo.joining_date">
                                    since <strong class="text-foreground">{{ employeeInfo.joining_date }}</strong>
                                </span>.
                                Currently working in the 
                                <strong class="text-foreground">{{ employeeInfo.department || 'N/A' }}</strong>
                                <span v-if="employeeInfo.sub_department">
                                    (<strong class="text-foreground">{{ employeeInfo.sub_department }}</strong>)
                                </span>
                                department as a <strong class="text-foreground">{{ employeeInfo.designation }}</strong>.
                            </p>
                            
                            <!-- Missing Information Warning -->
                            <div v-if="!employeeInfo.fathers_name || !employeeInfo.mothers_name || !employeeInfo.nid_number" 
                                 class="p-3 rounded-lg bg-yellow-500/10 border border-yellow-500/20">
                                <p class="text-xs text-yellow-500">
                                    <strong>Note:</strong> Some information is missing from your profile. Please contact HR to update your records for a complete certificate.
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Request History Card -->
                    <Card class="border-border/50 shadow-sm">
                        <CardHeader class="pb-4">
                            <CardTitle class="text-[15px] font-semibold">Your Requests</CardTitle>
                            <CardDescription>History of your certificate requests</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="myRequests.length === 0" class="text-center py-6 text-muted-foreground">
                                <FileText class="h-10 w-10 mx-auto mb-2 opacity-50" />
                                <p>No requests yet</p>
                            </div>
                            <div v-else class="space-y-3">
                                <div
                                    v-for="request in myRequests"
                                    :key="request.id"
                                    class="flex items-center justify-between p-3 rounded-lg bg-muted/30 border border-border/50"
                                >
                                    <div class="space-y-1">
                                        <p class="text-sm font-medium">{{ request.ref_id }}</p>
                                        <p class="text-xs text-muted-foreground">{{ getPurposeDisplay(request.purpose, request.purpose_other) }}</p>
                                        <p class="text-xs text-muted-foreground">{{ formatDate(request.created_at) }}</p>
                                    </div>
                                    <Badge :class="['text-xs border', getStatusBadgeClass(request.status)]">
                                        {{ request.status.charAt(0).toUpperCase() + request.status.slice(1) }}
                                    </Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
