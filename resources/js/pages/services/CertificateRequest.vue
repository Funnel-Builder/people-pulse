<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
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
import { FileText, Clock, CheckCircle, XCircle, AlertCircle, History, Check, X } from 'lucide-vue-next';
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
}

const props = defineProps<Props>();
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
});


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
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Request for Employment Certificate</h1>
                    <p class="text-muted-foreground mt-1">Submit a request to receive your official employment certificate</p>
                </div>
                <Button variant="outline" class="gap-2" @click="$inertia.visit('/services/certificate/history')">
                    <History class="h-4 w-4" />
                    History
                </Button>
            </div>

            <div class="grid gap-6 lg:grid-cols-[1fr_400px]">
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
            </div>
        </div>
    </AppLayout>
</template>
