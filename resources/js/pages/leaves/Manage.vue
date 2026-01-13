<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { CalendarDays, Clock, CheckCircle, XCircle, ArrowLeft, User, FileText, Shield, UserCheck } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

// Interfaces
interface LeaveType {
    id: number;
    name: string;
    code: string;
}

interface UserInfo {
    id: number;
    name: string;
    employee_id: string;
    designation: string | null;
    department?: { id: number; name: string };
    sub_department?: { id: number; name: string };
}

interface LeaveApproval {
    id: number;
    step: number;
    approver_type: 'cover_person' | 'manager' | 'admin';
    status: 'pending' | 'approved' | 'rejected';
    approver?: { id: number; name: string };
    comment: string | null;
    updated_at: string;
}

interface LeaveRecord {
    id: number;
    user: UserInfo;
    leave_type: LeaveType;
    type: 'advance' | 'post';
    status: 'pending' | 'approved' | 'rejected' | 'cancelled';
    reason: string;
    cover_person: { id: number; name: string } | null;
    days: number;
    start_date: string;
    end_date: string;
    created_at: string;
    current_approval_step?: number;
    approvals?: LeaveApproval[];
}

const props = defineProps<{
    leave: LeaveRecord;
}>();

const page = usePage();
const currentUser = computed(() => page.props.auth.user);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Leave Records', href: '/records/leave' },
    { title: 'Manage Application', href: '#' },
];

const approvalForm = useForm({
    action: '',
    comment: '',
});

const submitApproval = (action: 'approve' | 'reject') => {
    approvalForm.action = action;
    approvalForm.post(`/leaves/${props.leave.id}/process`, {
        preserveScroll: true,
    });
};

const formatDate = (dateString: string) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

const getTypeBadge = (type: string) => {
    return type === 'advance' 
        ? { class: 'bg-blue-50 text-blue-700 border-blue-200', label: 'Advance Application' }
        : { class: 'bg-purple-50 text-purple-700 border-purple-200', label: 'Post Application' };
};

const canApproveCurrentStep = computed(() => {
    if (props.leave.status !== 'pending') return false;
    
    // Admins can always override pending steps
    if (currentUser.value.role === 'admin') return true;

    const currentStep = props.leave.approvals?.find(a => a.status === 'pending');
    if (!currentStep) return false;

    if (currentStep.approver_type === 'manager' && currentUser.value.role === 'manager') return true;
    
    return false;
});

const getApprovalStepStatus = (stepType: string): 'pending' | 'completed' | 'current' | 'rejected' => {
    if (!props.leave.approvals) return 'pending';
    
    const approval = props.leave.approvals.find(a => a.approver_type === stepType);
    if (!approval) return 'pending';

    if (approval.status === 'approved') return 'completed';
    if (approval.status === 'rejected') return 'rejected';
    
    const firstPending = props.leave.approvals.find(a => a.status === 'pending');
    if (firstPending && firstPending.approver_type === stepType) return 'current';
    
    return 'pending';
};

const getStepIcon = (stepType: string) => {
    switch (stepType) {
        case 'cover_person': return User;
        case 'manager': return UserCheck;
        case 'admin': return Shield;
        default: return User;
    }
};

const getStepTitle = (stepType: string) => {
    switch (stepType) {
        case 'cover_person': return 'Cover Person';
        case 'manager': return 'Line Manager';
        case 'admin': return 'HR / Admin';
        default: return 'Approver';
    }
};

const getStepDescription = (stepType: string) => {
    switch (stepType) {
        case 'cover_person': return 'Agreement to cover duties';
        case 'manager': return 'Authorization of leave';
        case 'admin': return 'Final verification & approval';
        default: return '';
    }
};

</script>

<template>
    <Head title="Manage Leave" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto p-4 md:p-6 space-y-6">
            
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link href="/records/leave">
                    <Button variant="outline" size="icon" class="h-9 w-9">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Manage Leave Application</h1>
                    <p class="text-muted-foreground text-sm">Review details and process approval workflow</p>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                
                <!-- Main Content -->
                <div class="md:col-span-2 space-y-6">
                    
                    <!-- Applicant Details -->
                    <Card>
                        <CardHeader class="pb-3">
                            <CardTitle class="text-base flex items-center gap-2">
                                <User class="h-4 w-4 text-muted-foreground" />
                                Applicant Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-center gap-4">
                                <div class="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-lg">
                                    {{ props.leave.user.name.charAt(0) }}
                                </div>
                                <div>
                                    <h3 class="font-semibold">{{ props.leave.user.name }}</h3>
                                    <div class="flex items-center gap-2 text-sm text-muted-foreground mt-0.5">
                                        <span>{{ props.leave.user.designation || 'Employee' }}</span>
                                        <span class="w-1 h-1 rounded-full bg-muted-foreground/50"></span>
                                        <span>{{ props.leave.user.employee_id }}</span>
                                    </div>
                                    <div class="text-xs text-muted-foreground mt-1">
                                        {{ props.leave.user.department?.name }} 
                                        <span v-if="props.leave.user.sub_department">• {{ props.leave.user.sub_department?.name }}</span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Leave Details -->
                    <Card>
                        <CardHeader class="pb-3">
                            <CardTitle class="text-base flex items-center gap-2">
                                <FileText class="h-4 w-4 text-muted-foreground" />
                                Application Details
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="grid gap-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <Label class="text-xs text-muted-foreground">Leave Type</Label>
                                    <p class="font-medium">{{ props.leave.leave_type.name }}</p>
                                </div>
                                <div class="space-y-1">
                                    <Label class="text-xs text-muted-foreground">Category</Label>
                                    <Badge variant="outline" :class="getTypeBadge(props.leave.type).class">
                                        {{ getTypeBadge(props.leave.type).label }}
                                    </Badge>
                                </div>
                                <div class="space-y-1">
                                    <Label class="text-xs text-muted-foreground">Duration</Label>
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-lg">{{ props.leave.days }}</span>
                                        <span class="text-sm text-muted-foreground">Days</span>
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <Label class="text-xs text-muted-foreground">Date Range</Label>
                                    <div class="flex items-center gap-2">
                                        <CalendarDays class="h-4 w-4 text-muted-foreground" />
                                        <span class="font-medium">{{ formatDate(props.leave.start_date) }} - {{ formatDate(props.leave.end_date) }}</span>
                                    </div>
                                </div>
                            </div>

                            <Separator />

                            <div class="space-y-2">
                                <Label class="text-xs text-muted-foreground">Reason for Leave</Label>
                                <div class="bg-muted/30 p-4 rounded-lg border text-sm leading-relaxed">
                                    {{ props.leave.reason }}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                </div>

                <!-- Sidebar: Approval Workflow -->
                <div class="space-y-6">
                    <Card class="h-full flex flex-col">
                        <CardHeader>
                            <CardTitle class="text-base">Approval Workflow</CardTitle>
                            <CardDescription>Current status of the application</CardDescription>
                        </CardHeader>
                        <CardContent class="flex-1">
                            <div class="relative pl-6 space-y-8 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-muted">
                                
                                <div v-for="step in ['cover_person', 'manager', 'admin']" :key="step" class="relative">
                                    <div class="absolute -left-6 z-10 bg-background p-1">
                                        <div class="h-6 w-6 rounded-full border-2 flex items-center justify-center"
                                            :class="{
                                                'border-green-500 text-green-500 bg-green-50': getApprovalStepStatus(step) === 'completed',
                                                'border-red-500 text-red-500 bg-red-50': getApprovalStepStatus(step) === 'rejected',
                                                'border-primary text-primary bg-primary/10': getApprovalStepStatus(step) === 'current',
                                                'border-muted text-muted-foreground bg-muted': getApprovalStepStatus(step) === 'pending'
                                            }"
                                        >
                                            <component :is="getApprovalStepStatus(step) === 'completed' ? CheckCircle : (getApprovalStepStatus(step) === 'rejected' ? XCircle : getStepIcon(step))" class="h-3.5 w-3.5" />
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-1">
                                        <div class="flex items-center justify-between">
                                            <span class="font-medium text-sm">{{ getStepTitle(step) }}</span>
                                            <Badge variant="secondary" class="text-[10px] uppercase font-bold tracking-wider px-1.5 py-0.5"
                                                :class="{
                                                    'bg-green-100 text-green-700': getApprovalStepStatus(step) === 'completed',
                                                    'bg-red-100 text-red-700': getApprovalStepStatus(step) === 'rejected',
                                                    'bg-blue-100 text-blue-700': getApprovalStepStatus(step) === 'current'
                                                }"
                                            >
                                                {{ getApprovalStepStatus(step) === 'current' ? 'InProgress' : getApprovalStepStatus(step) }}
                                            </Badge>
                                        </div>
                                        <p class="text-xs text-muted-foreground">{{ getStepDescription(step) }}</p>
                                        
                                        <!-- Step Details (if applicable) -->
                                        <div v-if="step === 'cover_person' && props.leave.cover_person" class="mt-2 text-xs bg-muted/50 p-2 rounded">
                                            <span class="font-medium">Selected:</span> {{ props.leave.cover_person.name }}
                                        </div>
                                        <div v-if="step === 'cover_person' && !props.leave.cover_person" class="mt-2 text-xs text-muted-foreground italic">
                                            Not required
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </CardContent>

                        <!-- Actions Footer -->
                        <div v-if="canApproveCurrentStep" class="p-4 border-t bg-muted/20">
                            <div class="space-y-4">
                                <Label>Action Required</Label>
                                <Textarea 
                                    placeholder="Add a comment (optional)..." 
                                    v-model="approvalForm.comment"
                                    rows="3"
                                    class="resize-none"
                                />
                                <div class="grid grid-cols-2 gap-3">
                                    <Button 
                                        class="bg-green-600 hover:bg-green-700 text-white" 
                                        @click="submitApproval('approve')" 
                                        :disabled="approvalForm.processing"
                                    >
                                        <CheckCircle class="mr-2 h-4 w-4" />
                                        Approve
                                    </Button>
                                    <Button 
                                        variant="destructive" 
                                        @click="submitApproval('reject')"
                                        :disabled="approvalForm.processing"
                                    >
                                        <XCircle class="mr-2 h-4 w-4" />
                                        Reject
                                    </Button>
                                </div>
                            </div>
                        </div>

                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
