<script setup lang="ts">
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle, SheetFooter } from '@/components/ui/sheet';
import { Plus, Building2, Trash2, Edit, ChevronRight, ChevronDown, Network } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import type { BreadcrumbItem } from '@/types';

interface SubDepartment {
    id: number;
    name: string;
    description?: string;
    is_active: boolean;
    department_id: number;
}

interface Department {
    id: number;
    name: string;
    description?: string;
    is_active: boolean;
    sub_departments: SubDepartment[];
}

const props = defineProps<{
    departments: Department[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Office Settings', href: '/settings' },
    { title: 'Departments', href: '/settings/departments' },
];

const isDepartmentDrawerOpen = ref(false);
const isSubDepartmentDrawerOpen = ref(false);
const editingDepartment = ref<Department | null>(null);
const editingSubDepartment = ref<SubDepartment | null>(null);
const activeDepartmentForSub = ref<Department | null>(null);
const expandedDepartments = ref<Set<number>>(new Set());

// Toggle department expansion
const toggleExpand = (deptId: number) => {
    const newSet = new Set(expandedDepartments.value);
    if (newSet.has(deptId)) {
        newSet.delete(deptId);
    } else {
        newSet.add(deptId);
    }
    expandedDepartments.value = newSet;
};

// Department Form
const deptForm = useForm({
    name: '',
    description: '',
    is_active: true,
});

// Sub-Department Form
const subDeptForm = useForm({
    name: '',
    description: '',
    is_active: true,
    department_id: null as number | null,
});

// Department Actions
const openAddDepartment = () => {
    editingDepartment.value = null;
    deptForm.reset();
    isDepartmentDrawerOpen.value = true;
};

const openEditDepartment = (dept: Department) => {
    editingDepartment.value = dept;
    deptForm.name = dept.name;
    deptForm.description = dept.description || '';
    deptForm.is_active = dept.is_active;
    isDepartmentDrawerOpen.value = true;
};

const submitDepartmentComp = () => {
    if (editingDepartment.value) {
        deptForm.put(`/settings/departments/${editingDepartment.value.id}`, {
            onSuccess: () => isDepartmentDrawerOpen.value = false,
        });
    } else {
        deptForm.post('/settings/departments', {
            onSuccess: () => isDepartmentDrawerOpen.value = false,
        });
    }
};

const deleteDepartment = (dept: Department) => {
    if (confirm(`Are you sure you want to delete ${dept.name}?`)) {
        router.delete(`/settings/departments/${dept.id}`);
    }
};

// Sub-Department Actions
const openAddSubDepartment = (dept: Department) => {
    editingSubDepartment.value = null;
    activeDepartmentForSub.value = dept;
    subDeptForm.reset();
    subDeptForm.department_id = dept.id;
    isSubDepartmentDrawerOpen.value = true;
    
    // Auto expand the department
    const newSet = new Set(expandedDepartments.value);
    newSet.add(dept.id);
    expandedDepartments.value = newSet;
};

const openEditSubDepartment = (sub: SubDepartment) => {
    editingSubDepartment.value = sub;
    subDeptForm.name = sub.name;
    subDeptForm.description = sub.description || '';
    subDeptForm.is_active = sub.is_active;
    // We don't change department_id during edit for now
    isSubDepartmentDrawerOpen.value = true;
};

const submitSubDepartmentComp = () => {
    if (editingSubDepartment.value) {
        subDeptForm.put(`/settings/sub-departments/${editingSubDepartment.value.id}`, {
            onSuccess: () => isSubDepartmentDrawerOpen.value = false,
        });
    } else {
        if (!activeDepartmentForSub.value) return;
        subDeptForm.post(`/settings/departments/${activeDepartmentForSub.value.id}/sub-departments`, {
            onSuccess: () => isSubDepartmentDrawerOpen.value = false,
        });
    }
};

const deleteSubDepartment = (sub: SubDepartment) => {
    if (confirm(`Are you sure you want to delete ${sub.name}?`)) {
        router.delete(`/settings/sub-departments/${sub.id}`);
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col p-6 md:p-8 space-y-6 max-w-7xl mx-auto w-full">
            
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Department Settings</h1>
                    <p class="text-muted-foreground">Manage your organization's departments and structure.</p>
                </div>
                <Button @click="openAddDepartment" class="gap-2">
                    <Plus class="h-4 w-4" />
                    Add Department
                </Button>
            </div>

            <!-- List View -->
            <Card class="flex-1 overflow-hidden border-0 shadow-sm bg-transparent">
                <CardContent class="p-0 space-y-4">
                    <div v-if="departments.length === 0" class="flex flex-col items-center justify-center p-12 text-center text-muted-foreground border rounded-lg bg-background">
                        <Building2 class="h-12 w-12 mb-4 opacity-50" />
                        <h3 class="text-lg font-medium">No departments found</h3>
                        <p class="mb-4">Get started by creating your first department.</p>
                        <Button @click="openAddDepartment" variant="outline">Create Department</Button>
                    </div>

                    <div v-for="dept in departments" :key="dept.id" class="border rounded-xl bg-card overflow-hidden transition-all duration-200">
                        <!-- Department Row -->
                        <div 
                            class="flex items-center justify-between p-4 cursor-pointer hover:bg-muted/30 transition-colors"
                            @click="toggleExpand(dept.id)"
                        >
                            <div class="flex items-center gap-4">
                                <div class="p-2 rounded-lg bg-primary/10 text-primary">
                                    <Building2 class="h-5 w-5" />
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-semibold text-lg">{{ dept.name }}</h3>
                                        <Badge v-if="!dept.is_active" variant="destructive" class="text-[10px] h-5">Inactive</Badge>
                                    </div>
                                    <p class="text-sm text-muted-foreground" v-if="dept.description">{{ dept.description }}</p>
                                    <p class="text-xs text-muted-foreground mt-1 flex items-center gap-1">
                                        <Network class="h-3 w-3" />
                                        {{ dept.sub_departments.length }} Sub-departments
                                    </p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <Button variant="ghost" size="sm" class="h-8 gap-2" @click.stop="openAddSubDepartment(dept)">
                                    <Plus class="h-3.5 w-3.5" />
                                    <span class="sr-only sm:not-sr-only">Add Sub</span>
                                </Button>
                                <div class="h-4 w-[1px] bg-border mx-1"></div>
                                <Button variant="ghost" size="icon" class="h-8 w-8 text-muted-foreground" @click.stop="openEditDepartment(dept)">
                                    <Edit class="h-3.5 w-3.5" />
                                </Button>
                                <Button variant="ghost" size="icon" class="h-8 w-8 text-destructive" @click.stop="deleteDepartment(dept)">
                                    <Trash2 class="h-3.5 w-3.5" />
                                </Button>
                                <Button variant="ghost" size="icon" class="h-8 w-8">
                                    <component :is="expandedDepartments.has(dept.id) ? ChevronDown : ChevronRight" class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        <!-- Sub-departments List -->
                        <div v-show="expandedDepartments.has(dept.id)" class="bg-muted/30 border-t p-2 pl-12 pr-4 space-y-1">
                            <div v-if="dept.sub_departments.length === 0" class="py-3 text-sm text-muted-foreground italic text-center">
                                No sub-departments yet.
                            </div>
                            <div v-for="sub in dept.sub_departments" :key="sub.id" class="flex items-center justify-between p-3 rounded-lg hover:bg-background border border-transparent hover:border-border transition-all">
                                <div class="flex items-center gap-3">
                                    <div class="h-1.5 w-1.5 rounded-full bg-primary/40"></div>
                                    <div>
                                        <span class="font-medium text-sm">{{ sub.name }}</span>
                                        <p v-if="sub.description" class="text-xs text-muted-foreground">{{ sub.description }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center opacity-0 group-hover:opacity-100 transition-opacity">
                                    <Button variant="ghost" size="icon" class="h-7 w-7" @click="openEditSubDepartment(sub)">
                                        <Edit class="h-3 w-3 text-muted-foreground" />
                                    </Button>
                                    <Button variant="ghost" size="icon" class="h-7 w-7" @click="deleteSubDepartment(sub)">
                                        <Trash2 class="h-3 w-3 text-destructive" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Department Drawer -->
            <Sheet v-model:open="isDepartmentDrawerOpen">
                <SheetContent class="sm:max-w-md p-4">
                    <SheetHeader>
                        <SheetTitle>{{ editingDepartment ? 'Edit Department' : 'Add Department' }}</SheetTitle>
                        <SheetDescription>
                            {{ editingDepartment ? 'Update department details.' : 'Create a new department.' }}
                        </SheetDescription>
                    </SheetHeader>
                    <form @submit.prevent="submitDepartmentComp" class="space-y-6 mt-6">
                        <div class="space-y-2">
                            <Label for="dept-name">Name <span class="text-destructive">*</span></Label>
                            <Input id="dept-name" v-model="deptForm.name" placeholder="e.g. Engineering" required />
                        </div>
                        <div class="space-y-2">
                            <Label for="dept-desc">Description</Label>
                            <Textarea id="dept-desc" v-model="deptForm.description" placeholder="Optional description..." rows="3" />
                        </div>
                        <SheetFooter>
                            <Button type="submit" :disabled="deptForm.processing" class="w-full">
                                {{ editingDepartment ? 'Save Changes' : 'Create Department' }}
                            </Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>

            <!-- Sub-Department Drawer -->
            <Sheet v-model:open="isSubDepartmentDrawerOpen">
                <SheetContent class="sm:max-w-md p-4">
                    <SheetHeader>
                        <SheetTitle>{{ editingSubDepartment ? 'Edit Sub-department' : 'Add Sub-department' }}</SheetTitle>
                        <SheetDescription>
                            {{ editingSubDepartment 
                                ? `Update sub-department details.` 
                                : `Add a sub-department to ${activeDepartmentForSub?.name}.` 
                            }}
                        </SheetDescription>
                    </SheetHeader>
                    <form @submit.prevent="submitSubDepartmentComp" class="space-y-6 mt-6">
                        <div class="space-y-2">
                            <Label for="sub-name">Name <span class="text-destructive">*</span></Label>
                            <Input id="sub-name" v-model="subDeptForm.name" placeholder="e.g. Frontend Team" required />
                        </div>
                        <div class="space-y-2">
                            <Label for="sub-desc">Description</Label>
                            <Textarea id="sub-desc" v-model="subDeptForm.description" placeholder="Optional description..." rows="3" />
                        </div>
                        <SheetFooter>
                            <Button type="submit" :disabled="subDeptForm.processing" class="w-full">
                                {{ editingSubDepartment ? 'Save Changes' : 'Add Sub-department' }}
                            </Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>

        </div>
    </AppLayout>
</template>
