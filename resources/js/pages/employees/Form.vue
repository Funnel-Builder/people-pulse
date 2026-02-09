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
import { ChevronDown, ChevronUp, UserMinus, AlertTriangle, Loader2, Calendar as CalendarIcon, CheckCircle, Ban, ChevronsUpDown, Check, X, Search } from 'lucide-vue-next';
import { format } from 'date-fns';
import { cn } from '@/lib/utils';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Badge } from '@/components/ui/badge';

interface SubDepartment {
    id: number;
    name: string;
}

interface Department {
    id: number;
    name: string;
    sub_departments: SubDepartment[];
}

interface Skill {
    id: number;
    name: string;
}

interface SkillGroup {
    id: number;
    name: string;
    skills: Skill[];
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
    skills?: Skill[];
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
    managerResponsibilities?: number[]; // Array of sub-department IDs the manager is responsible for
    skillGroups: SkillGroup[];
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

// Use separate ref for selected skills
const selectedSkills = ref<number[]>(props.employee?.skills ? props.employee.skills.map(s => s.id) : []);

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
    // Manager Responsibilities
    manager_responsibilities: [] as number[],
    // Skills
    skills: [] as number[],
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

// Manager responsibilities selection - track selected sub-department IDs
const selectedManagerResponsibilities = ref<number[]>(props.managerResponsibilities || []);

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

// Helper functions for manager responsibilities
const isSubDepartmentSelected = (subDeptId: number) => {
    return selectedManagerResponsibilities.value.includes(subDeptId);
};

const toggleSubDepartment = (subDeptId: number) => {
    const index = selectedManagerResponsibilities.value.indexOf(subDeptId);
    if (index === -1) {
        selectedManagerResponsibilities.value.push(subDeptId);
    } else {
        selectedManagerResponsibilities.value.splice(index, 1);
    }
};

const isDepartmentFullySelected = (dept: Department) => {
    return dept.sub_departments.length > 0 && 
           dept.sub_departments.every(sd => selectedManagerResponsibilities.value.includes(sd.id));
};

const isDepartmentPartiallySelected = (dept: Department) => {
    const selectedCount = dept.sub_departments.filter(sd => 
        selectedManagerResponsibilities.value.includes(sd.id)
    ).length;
    return selectedCount > 0 && selectedCount < dept.sub_departments.length;
};

const toggleDepartment = (dept: Department) => {
    const allSubDeptIds = dept.sub_departments.map(sd => sd.id);
    const allSelected = isDepartmentFullySelected(dept);
    
    if (allSelected) {
        // Remove all sub-departments of this department
        selectedManagerResponsibilities.value = selectedManagerResponsibilities.value.filter(
            id => !allSubDeptIds.includes(id)
        );
    } else {
        // Add all sub-departments of this department
        allSubDeptIds.forEach(id => {
            if (!selectedManagerResponsibilities.value.includes(id)) {
                selectedManagerResponsibilities.value.push(id);
            }
        });
    }
};

const hasAnyResponsibilities = computed(() => {
    return selectedManagerResponsibilities.value.length > 0;
});

// Get summary of managed sub-departments grouped by department
const managedSummary = computed(() => {
    const summary: { department: string; subDepartments: string[] }[] = [];
    
    props.departments.forEach(dept => {
        const selectedSubDepts = dept.sub_departments.filter(sd => 
            selectedManagerResponsibilities.value.includes(sd.id)
        );
        
        if (selectedSubDepts.length > 0) {
            summary.push({
                department: dept.name,
                subDepartments: selectedSubDepts.map(sd => sd.name),
            });
        }
    });
    
    return summary;
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
    form.manager_responsibilities = selectedManagerResponsibilities.value;
    form.skills = selectedSkills.value;

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

// --- Skill Handling Logic ---
const skillSearchQuery = ref('');
const isSkillPopoverOpen = ref(false);

const allSkills = computed(() => {
    return props.skillGroups.flatMap(group => 
        group.skills.map(skill => ({
            ...skill,
            groupName: group.name
        }))
    );
});

const filteredSkills = computed(() => {
    if (!skillSearchQuery.value) return allSkills.value;
    const query = skillSearchQuery.value.toLowerCase();
    return allSkills.value.filter(skill => 
        skill.name.toLowerCase().includes(query)
    );
});

const getSkillName = (id: number) => {
    return allSkills.value.find(s => s.id === id)?.name || 'Unknown Skill';
};

const getSkillGroupName = (id: number) => {
    return allSkills.value.find(s => s.id === id)?.groupName || '';
};

const toggleSkill = (id: number) => {
    const index = selectedSkills.value.indexOf(id);
    if (index === -1) {
        selectedSkills.value.push(id);
    } else {
        selectedSkills.value.splice(index, 1);
    }
    // Optional: Keep popover open for multiple selections
    // isSkillPopoverOpen.value = false; 
};

const removeSkill = (id: number) => {
    const index = selectedSkills.value.indexOf(id);
    if (index !== -1) {
        selectedSkills.value.splice(index, 1);
    }
};
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
                :variant="employeeStatusBanner.variant as 'default' | 'destructive' | null | undefined"
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



                    <!-- Manager Responsibilities Card -->
                    <Card v-if="form.role === 'manager'">
                        <CardHeader>
                            <CardTitle>Manager Responsibilities</CardTitle>
                            <p class="text-sm text-muted-foreground">
                                Select the departments and sub-departments this manager will oversee.
                            </p>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <!-- Department list with sub-departments -->
                                <div class="flex flex-wrap gap-4">
                                    <div v-for="dept in departments" :key="dept.id" class="border rounded-lg p-4 w-full">
                                    <div class="flex items-center space-x-3">
                                        <input
                                            type="checkbox"
                                            :id="`dept-${dept.id}`"
                                            :checked="isDepartmentFullySelected(dept)"
                                            :indeterminate="isDepartmentPartiallySelected(dept)"
                                            @change="toggleDepartment(dept)"
                                            class="h-4 w-4 rounded border-gray-300"
                                            :disabled="dept.sub_departments.length === 0"
                                        />
                                        <Label :for="`dept-${dept.id}`" class="font-semibold cursor-pointer">
                                            {{ dept.name }}
                                            <span v-if="dept.sub_departments.length === 0" class="text-xs text-muted-foreground ml-2">
                                                (No sub-departments)
                                            </span>
                                        </Label>
                                    </div>
                                    <!-- Sub-departments -->
                                    <div v-if="dept.sub_departments.length > 0" class="ml-7 mt-3 space-y-2">
                                        <div v-for="subDept in dept.sub_departments" :key="subDept.id" class="flex items-center space-x-2">
                                            <input
                                                type="checkbox"
                                                :id="`subdept-${subDept.id}`"
                                                :checked="isSubDepartmentSelected(subDept.id)"
                                                @change="toggleSubDepartment(subDept.id)"
                                                class="h-4 w-4 rounded border-gray-300"
                                            />
                                            <Label :for="`subdept-${subDept.id}`" class="cursor-pointer text-sm">
                                                {{ subDept.name }}
                                            </Label>
                                        </div>
                                    </div>
                                    </div>
                                </div>
                                
                                <!-- Summary -->
                                <div v-if="hasAnyResponsibilities" class="mt-4 p-4 bg-primary/5 rounded-lg border border-primary/20">
                                    <p class="text-sm font-medium text-primary mb-2">This manager will have access to:</p>
                                    <ul class="text-sm text-muted-foreground space-y-1">
                                        <li v-for="item in managedSummary" :key="item.department">
                                            <strong class="text-foreground">{{ item.department }}:</strong>
                                            {{ item.subDepartments.join(', ') }}
                                        </li>
                                    </ul>
                                </div>
                                
                                <!-- Fallback info when no explicit responsibilities -->
                                <div v-else class="mt-4 p-4 bg-muted/50 rounded-lg">
                                    <p class="text-sm text-muted-foreground">
                                        <template v-if="!form.department_id">
                                            Please assign a department to this manager, or select specific responsibilities above.
                                        </template>
                                        <template v-else-if="form.sub_department_id">
                                            Without explicit responsibilities selected, this manager will manage only the 
                                            <strong class="text-foreground">{{ availableSubDepartments.find(sd => sd.id === form.sub_department_id)?.name || 'selected sub-department' }}</strong>.
                                        </template>
                                        <template v-else-if="availableSubDepartments.length > 0">
                                            Without explicit responsibilities selected, this manager will manage 
                                            <strong class="text-foreground">all sub-departments</strong> in the 
                                            <strong class="text-foreground">{{ departments.find(d => d.id === form.department_id)?.name }}</strong> department.
                                        </template>
                                        <template v-else>
                                            Without explicit responsibilities selected, this manager will manage the 
                                            <strong class="text-foreground">{{ departments.find(d => d.id === form.department_id)?.name }}</strong> department.
                                        </template>
                                    </p>
                                </div>
                            </div>
                            <p v-if="form.errors.manager_responsibilities" class="mt-2 text-sm text-destructive">{{ form.errors.manager_responsibilities }}</p>
                        </CardContent>
                    </Card>

                    <!-- Skills & Expertise -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Skills & Expertise</CardTitle>
                            <p class="text-sm text-muted-foreground">Select the skills and technologies relevant to this employee.</p>
                        </CardHeader>
                        <CardContent>
                             <div class="space-y-4">
                                <div class="flex flex-wrap gap-2 mb-2">
                                    <Badge
                                        v-for="skillId in selectedSkills"
                                        :key="skillId"
                                        variant="secondary"
                                        class="pl-2 pr-1 py-1 flex items-center gap-1"
                                    >
                                        {{ getSkillName(skillId) }}
                                        <button
                                            type="button"
                                            @click="removeSkill(skillId)"
                                            class="ml-1 hover:bg-muted p-0.5 rounded-full transition-colors"
                                        >
                                            <X class="h-3 w-3 text-muted-foreground hover:text-foreground" />
                                        </button>
                                    </Badge>
                                </div>

                                <Popover v-model:open="isSkillPopoverOpen">
                                    <PopoverTrigger as-child>
                                        <Button
                                            variant="outline"
                                            role="combobox"
                                            :aria-expanded="isSkillPopoverOpen"
                                            class="w-full justify-between"
                                        >
                                            <span v-if="selectedSkills.length === 0" class="text-muted-foreground">Select skills...</span>
                                            <span v-else>{{ selectedSkills.length }} skills selected</span>
                                            <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent class="w-[400px] p-0" align="start">
                                        <div class="p-2 border-b">
                                            <div class="flex items-center px-2">
                                                <Search class="mr-2 h-4 w-4 shrink-0 opacity-50" />
                                                <input
                                                    v-model="skillSearchQuery"
                                                    class="flex h-10 w-full rounded-md bg-transparent py-3 text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50"
                                                    placeholder="Search skills..."
                                                />
                                            </div>
                                        </div>
                                        <div class="max-h-[300px] overflow-y-auto p-1">
                                            <div v-if="filteredSkills.length === 0" class="py-6 text-center text-sm text-muted-foreground">
                                                No skill found.
                                            </div>
                                            <div v-else>
                                                <div
                                                    v-for="skill in filteredSkills"
                                                    :key="skill.id"
                                                    @click="toggleSkill(skill.id)"
                                                    class="relative flex cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50 cursor-pointer"
                                                >
                                                    <div class="mr-2 flex h-4 w-4 items-center justify-center">
                                                        <Check
                                                            v-if="selectedSkills.includes(skill.id)"
                                                            class="h-4 w-4"
                                                        />
                                                    </div>
                                                    <span>{{ skill.name }}</span>
                                                    <span class="ml-auto text-xs text-muted-foreground">
                                                        {{ getSkillGroupName(skill.id) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </PopoverContent>
                                </Popover>
                                <p class="text-xs text-muted-foreground">
                                    Search and select skills. Selected skills appear as tags above.
                                </p>
                             </div>
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
