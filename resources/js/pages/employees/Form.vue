<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { ChevronDown, ChevronUp, UserMinus, AlertTriangle, Loader2, Calendar as CalendarIcon, CheckCircle, Ban } from 'lucide-vue-next';
import { format } from 'date-fns';
import { cn } from '@/lib/utils';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';

interface SubDepartment {
    id: number;
    name: string;
}

interface Department {
    id: number;
    name: string;
    sub_departments: SubDepartment[];
}

interface Employee {
    id: number;
    employee_id: string;
    name: string;
    email: string;
    department_id: number | null;
    sub_department_id: number | null;
    designation: string;
    role: 'user' | 'manager' | 'admin';
    weekend_days: string[];
    managedSubDepartments?: SubDepartment[];
    // Personal Information
    nid_number?: string | null;
    joining_date?: string | null;
    closing_date?: string | null;
    permanent_address?: string | null;
    present_address?: string | null;
    nationality?: string | null;
    fathers_name?: string | null;
    mothers_name?: string | null;
    graduated_institution?: string | null;
    is_active?: boolean;
}

interface Props {
    employee?: Employee | null;
    departments: Department[];
}

const props = defineProps<Props>();

const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string });

const isEditMode = computed(() => !!props.employee);
const isPersonalInfoOpen = ref(false);
const showSeparationModal = ref(false);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Employee Management', href: '/employees' },
    { title: isEditMode.value ? 'Edit Employee' : 'Create Employee', href: isEditMode.value ? `/employees/${props.employee?.id}/edit` : '/employees/create' },
]);

// Use separate ref for weekend days to work with native checkboxes
const selectedWeekendDays = ref<string[]>(props.employee?.weekend_days ? [...props.employee.weekend_days] : []);

const form = useForm({
    employee_id: props.employee?.employee_id || '',
    name: props.employee?.name || '',
    email: props.employee?.email || '',
    password: '',
    department_id: props.employee?.department_id || null as number | null,
    sub_department_id: props.employee?.sub_department_id || null as number | null,
    designation: props.employee?.designation || '',
    role: props.employee?.role || 'user' as 'user' | 'manager' | 'admin',
    weekend_days: [] as string[],
    // Personal Information
    nid_number: props.employee?.nid_number || '',
    joining_date: props.employee?.joining_date || '',
    permanent_address: props.employee?.permanent_address || '',
    present_address: props.employee?.present_address || '',
    nationality: props.employee?.nationality || '',
    fathers_name: props.employee?.fathers_name || '',
    mothers_name: props.employee?.mothers_name || '',
    graduated_institution: props.employee?.graduated_institution || '',
});

// Separation form
const separationForm = useForm({
    closing_date: '',
});

const weekendOptions = [
    { value: 'friday', label: 'Friday' },
    { value: 'saturday', label: 'Saturday' },
    { value: 'sunday', label: 'Sunday' },
];

const selectedDepartment = computed(() => {
    return props.departments.find(d => d.id === form.department_id);
});

const availableSubDepartments = computed(() => {
    return selectedDepartment.value?.sub_departments || [];
});

// Format joining date for display
const formattedJoiningDate = computed(() => {
    if (!props.employee?.joining_date) return null;
    const date = new Date(props.employee.joining_date);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
});

// Check if employee is already scheduled for separation
const hasClosingDate = computed(() => !!props.employee?.closing_date);

// Format closing date for display
const formattedClosingDate = computed(() => {
    if (!props.employee?.closing_date) return null;
    const date = new Date(props.employee.closing_date);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
});

const submit = () => {
    form.weekend_days = selectedWeekendDays.value;

    if (isEditMode.value) {
        form.put(`/employees/${props.employee!.id}`, {
            preserveScroll: true,
        });
    } else {
        form.post('/employees', {
            preserveScroll: true,
        });
    }
};

const openSeparationModal = () => {
    separationForm.reset();
    separationForm.closing_date = '';
    showSeparationModal.value = true;
};

const closeSeparationModal = () => {
    showSeparationModal.value = false;
    separationForm.reset();
};

const submitSeparation = () => {
    separationForm.post(`/employees/${props.employee!.id}/separate`, {
        preserveScroll: true,
        onSuccess: () => {
            closeSeparationModal();
        },
    });
};

// Watch department changes and reset sub_department if not available
watch(() => form.department_id, (newDeptId, oldDeptId) => {
    if (newDeptId !== oldDeptId) {
        const isValid = availableSubDepartments.value.some(sd => sd.id === form.sub_department_id);
        if (!isValid) {
            form.sub_department_id = null;
        }
    }
});

const employeeStatusBanner = computed(() => {
    if (!props.employee) return null;

    if (props.employee.is_active === false) {
        return {
            message: 'This employee is separated.',
            variant: 'destructive',
            icon: Ban,
        };
    }

    if (props.employee.closing_date && props.employee.is_active === true) {
        const closingDate = new Date(props.employee.closing_date);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (closingDate > today) {
            return {
                message: `This employee is on notice period until ${format(closingDate, 'PPP')}.`,
                variant: 'warning',
                icon: AlertTriangle,
            };
        }
    }

    return null;
});
</script>

<template>
    <Head :title="isEditMode ? 'Edit Employee' : 'Create Employee'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <!-- Flash Message -->
            <Alert v-if="flash.success" class="border-green-500 bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800">
                <CheckCircle class="h-4 w-4" />
                <AlertDescription>{{ flash.success }}</AlertDescription>
            </Alert>
            <Alert v-if="flash.error" variant="destructive">
                <AlertTriangle class="h-4 w-4" />
                <AlertDescription>{{ flash.error }}</AlertDescription>
            </Alert>

            <!-- Employee Status Banner -->
            <Alert
                v-if="employeeStatusBanner"
                :variant="employeeStatusBanner.variant"
                class="flex items-center justify-between"
            >
                <div class="flex items-center gap-2">
                    <component :is="employeeStatusBanner.icon" class="h-4 w-4" />
                    <AlertDescription class="font-medium">
                        {{ employeeStatusBanner.message }}
                    </AlertDescription>
                </div>
            </Alert>

            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold">{{ isEditMode ? 'Edit Employee' : 'Create Employee' }}</h1>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit">
                <div class="grid gap-6">
                    <!-- Personal Information -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Personal Information</CardTitle>
                        </CardHeader>
                        <CardContent class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="employee_id">Employee ID <span class="text-destructive">*</span></Label>
                                <Input
                                    id="employee_id"
                                    v-model="form.employee_id"
                                    required
                                    placeholder="EMP001"
                                />
                                <p v-if="form.errors.employee_id" class="text-sm text-destructive">{{ form.errors.employee_id }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="name">Full Name <span class="text-destructive">*</span></Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    required
                                    placeholder="John Doe"
                                />
                                <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="email">Email <span v-if="!isEditMode" class="text-destructive">*</span><span v-else>(Read-only)</span></Label>
                                <Input
                                    id="email"
                                    type="email"
                                    v-model="form.email"
                                    :required="!isEditMode"
                                    :disabled="isEditMode"
                                    :class="{ 'opacity-60': isEditMode }"
                                    placeholder="john@example.com"
                                />
                                <p v-if="isEditMode" class="text-xs text-muted-foreground">Email cannot be changed</p>
                                <p v-if="form.errors.email" class="text-sm text-destructive">{{ form.errors.email }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="password">Password <span v-if="!isEditMode" class="text-destructive">*</span><span v-else>(Read-only)</span></Label>
                                <Input
                                    v-if="!isEditMode"
                                    id="password"
                                    type="password"
                                    v-model="form.password"
                                    required
                                    placeholder="********"
                                />
                                <Input
                                    v-else
                                    id="password"
                                    type="password"
                                    value="********"
                                    disabled
                                    class="opacity-60"
                                />
                                <p v-if="isEditMode" class="text-xs text-muted-foreground">Password cannot be changed here</p>
                                <p v-if="form.errors.password" class="text-sm text-destructive">{{ form.errors.password }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Additional Personal Details (Collapsible) -->
                    <Card>
                        <CardHeader 
                            class="cursor-pointer select-none"
                            @click="isPersonalInfoOpen = !isPersonalInfoOpen"
                        >
                            <div class="flex items-center justify-between">
                                <CardTitle>Additional Personal Details</CardTitle>
                                <ChevronDown v-if="!isPersonalInfoOpen" class="h-5 w-5 text-muted-foreground" />
                                <ChevronUp v-else class="h-5 w-5 text-muted-foreground" />
                            </div>
                            <p class="text-sm text-muted-foreground">Optional fields for detailed employee records</p>
                        </CardHeader>
                        <CardContent v-show="isPersonalInfoOpen" class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="nid_number">NID Number</Label>
                                <Input
                                    id="nid_number"
                                    v-model="form.nid_number"
                                    placeholder="National ID Number"
                                />
                                <p v-if="form.errors.nid_number" class="text-sm text-destructive">{{ form.errors.nid_number }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="nationality">Nationality</Label>
                                <Input
                                    id="nationality"
                                    v-model="form.nationality"
                                    placeholder="e.g., Bangladeshi"
                                />
                                <p v-if="form.errors.nationality" class="text-sm text-destructive">{{ form.errors.nationality }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="joining_date">Joining Date</Label>
                                <Input
                                    id="joining_date"
                                    type="date"
                                    v-model="form.joining_date"
                                />
                                <p v-if="form.errors.joining_date" class="text-sm text-destructive">{{ form.errors.joining_date }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="fathers_name">Father's Name</Label>
                                <Input
                                    id="fathers_name"
                                    v-model="form.fathers_name"
                                    placeholder="Father's full name"
                                />
                                <p v-if="form.errors.fathers_name" class="text-sm text-destructive">{{ form.errors.fathers_name }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="mothers_name">Mother's Name</Label>
                                <Input
                                    id="mothers_name"
                                    v-model="form.mothers_name"
                                    placeholder="Mother's full name"
                                />
                                <p v-if="form.errors.mothers_name" class="text-sm text-destructive">{{ form.errors.mothers_name }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="graduated_institution">Graduated Institution</Label>
                                <Input
                                    id="graduated_institution"
                                    v-model="form.graduated_institution"
                                    placeholder="University/College name"
                                />
                                <p v-if="form.errors.graduated_institution" class="text-sm text-destructive">{{ form.errors.graduated_institution }}</p>
                            </div>
                            <div class="space-y-2 md:col-span-2">
                                <Label for="permanent_address">Permanent Address</Label>
                                <textarea
                                    id="permanent_address"
                                    v-model="form.permanent_address"
                                    rows="2"
                                    class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                    placeholder="Permanent address"
                                ></textarea>
                                <p v-if="form.errors.permanent_address" class="text-sm text-destructive">{{ form.errors.permanent_address }}</p>
                            </div>
                            <div class="space-y-2 md:col-span-2">
                                <Label for="present_address">Present Address</Label>
                                <textarea
                                    id="present_address"
                                    v-model="form.present_address"
                                    rows="2"
                                    class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                    placeholder="Present address"
                                ></textarea>
                                <p v-if="form.errors.present_address" class="text-sm text-destructive">{{ form.errors.present_address }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Job Details -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Job Details</CardTitle>
                        </CardHeader>
                        <CardContent class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="department_id">Department</Label>
                                <select
                                    id="department_id"
                                    v-model.number="form.department_id"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                >
                                    <option :value="null">No Department</option>
                                    <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                                        {{ dept.name }}
                                    </option>
                                </select>
                                <p v-if="form.errors.department_id" class="text-sm text-destructive">{{ form.errors.department_id }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="sub_department_id">Sub-Department</Label>
                                <select
                                    id="sub_department_id"
                                    v-model.number="form.sub_department_id"
                                    :disabled="!form.department_id"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:opacity-50"
                                >
                                    <option :value="null">No Sub-Department</option>
                                    <option v-for="subDept in availableSubDepartments" :key="subDept.id" :value="subDept.id">
                                        {{ subDept.name }}
                                    </option>
                                </select>
                                <p v-if="form.errors.sub_department_id" class="text-sm text-destructive">{{ form.errors.sub_department_id }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="designation">Designation <span class="text-destructive">*</span></Label>
                                <Input
                                    id="designation"
                                    v-model="form.designation"
                                    required
                                    placeholder="Software Engineer"
                                />
                                <p v-if="form.errors.designation" class="text-sm text-destructive">{{ form.errors.designation }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="role">Role <span class="text-destructive">*</span></Label>
                                <select
                                    id="role"
                                    v-model="form.role"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                >
                                    <option value="user">User</option>
                                    <option value="manager">Manager</option>
                                    <option value="admin">Admin</option>
                                </select>
                                <p v-if="form.errors.role" class="text-sm text-destructive">{{ form.errors.role }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Weekend Days -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Weekend Days <span class="text-destructive">*</span></CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex flex-wrap gap-4">
                                <div v-for="option in weekendOptions" :key="option.value" class="flex items-center space-x-2">
                                    <input
                                        type="checkbox"
                                        :id="option.value"
                                        :value="option.value"
                                        v-model="selectedWeekendDays"
                                        class="h-4 w-4 rounded border-gray-300"
                                    />
                                    <Label :for="option.value" class="cursor-pointer">{{ option.label }}</Label>
                                </div>
                            </div>
                            <p class="mt-2 text-sm text-muted-foreground">Select at least one weekend day</p>
                            <p v-if="form.errors.weekend_days" class="mt-2 text-sm text-destructive">{{ form.errors.weekend_days }}</p>
                        </CardContent>
                    </Card>

                    <!-- Manager Info Card -->
                    <Card v-if="form.role === 'manager'">
                        <CardHeader>
                            <CardTitle>Manager Responsibilities</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2">
                                <ul class="text-sm text-muted-foreground list-disc list-inside space-y-1">
                                    <li v-if="!form.department_id">Please assign a department to this manager.</li>
                                    <li v-else-if="form.sub_department_id">
                                        This manager will manage only the <strong class="text-foreground">{{ availableSubDepartments.find(sd => sd.id === form.sub_department_id)?.name || 'selected sub-department' }}</strong>.
                                    </li>
                                    <li v-else-if="availableSubDepartments.length > 0">
                                        This manager will manage <strong class="text-foreground">all sub-departments</strong> in the <strong class="text-foreground">{{ departments.find(d => d.id === form.department_id)?.name }}</strong> department:
                                        <ul class="ml-6 mt-1">
                                            <li v-for="subDept in availableSubDepartments" :key="subDept.id">• {{ subDept.name }}</li>
                                        </ul>
                                    </li>
                                    <li v-else>
                                        This manager will manage the <strong class="text-foreground">{{ departments.find(d => d.id === form.department_id)?.name }}</strong> department (no sub-departments available).
                                    </li>
                                </ul>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Separation Status Card (only in edit mode when already scheduled) -->
                    <Card v-if="isEditMode && hasClosingDate" class="border-amber-500/50 bg-amber-50/50 dark:bg-amber-950/20">
                        <CardContent class="pt-6">
                            <p class="text-sm text-amber-700 dark:text-amber-400">
                                <strong>{{ employee?.name }}</strong> is separated on <strong>{{ formattedClosingDate }}</strong>.
                            </p>
                            <p class="text-sm text-amber-700 dark:text-amber-400 mt-1">
                                They will be deactivated and unable to log in after this date.
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Actions -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <Button type="submit" :disabled="form.processing">
                                {{ isEditMode ? 'Update Employee' : 'Create Employee' }}
                            </Button>
                            <Button type="button" variant="outline" as-child>
                                <a href="/employees">Cancel</a>
                            </Button>
                        </div>
                        
                        <!-- Separate Employee Button (only in edit mode) -->
                        <Button 
                            v-if="isEditMode && !hasClosingDate"
                            type="button" 
                            variant="destructive"
                            @click="openSeparationModal"
                        >
                            <UserMinus class="mr-2 h-4 w-4" />
                            Separate Employee
                        </Button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Separation Modal -->
        <Dialog :open="showSeparationModal" @update:open="closeSeparationModal">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <UserMinus class="h-5 w-5 text-destructive" />
                        Separate Employee
                    </DialogTitle>
                    <DialogDescription>
                        The employee will be deactivated and unable to log in after the closing date.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitSeparation">
                    <div class="py-4">
                        <div class="space-y-2">
                            <Label for="separation_closing_date">Closing Date <span class="text-destructive">*</span></Label>
                            <Popover>
                                <PopoverTrigger as-child>
                                    <Button
                                        variant="outline"
                                        :class="cn(
                                            'w-full justify-start text-left font-normal',
                                            !separationForm.closing_date && 'text-muted-foreground'
                                        )"
                                    >
                                        <CalendarIcon class="mr-2 h-4 w-4" />
                                        <span>{{ separationForm.closing_date ? format(new Date(separationForm.closing_date), 'PPP') : 'Pick a date' }}</span>
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent class="w-auto p-0">
                                    <Calendar v-model="separationForm.closing_date" />
                                </PopoverContent>
                            </Popover>
                            <p class="text-xs text-muted-foreground">
                                If a past date is selected, the employee will be separated immediately.
                            </p>
                            <p v-if="separationForm.errors.closing_date" class="text-sm text-destructive">
                                {{ separationForm.errors.closing_date }}
                            </p>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="closeSeparationModal">
                            Cancel
                        </Button>
                        <Button 
                            type="submit" 
                            variant="destructive"
                            :disabled="separationForm.processing || !separationForm.closing_date"
                        >
                            <Loader2 v-if="separationForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                            {{ separationForm.processing ? 'Scheduling...' : 'Confirm Separation' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
