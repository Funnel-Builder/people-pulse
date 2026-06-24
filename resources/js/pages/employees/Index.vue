<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import type { BreadcrumbItem, PaginatedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Plus, Edit, Trash, UserCog, Search } from 'lucide-vue-next';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { computed, ref } from 'vue';

interface Department {
    id: number;
    name: string;
}

interface SubDepartment {
    id: number;
    name: string;
}

interface Employee {
    id: number;
    employee_id: string;
    name: string;
    email: string;
    department_id: number | null;
    sub_department_id: number | null;
    department?: Department;
    sub_department?: SubDepartment;
    designation: string;
    role: 'admin' | 'manager' | 'user';
    managedSubDepartments?: SubDepartment[];
    joining_date?: string;
    created_at: string;
    is_active: boolean;
}

interface Props {
    employees: PaginatedData<Employee>;
    filters: {
        status: string;
        search: string;
    }
}

const props = defineProps<Props>();

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Employee Management', href: '/employees' },
];

const searchQuery = ref(props.filters.search);
const filterStatus = ref(props.filters.status);

const search = () => {
    router.get('/employees', {
        search: searchQuery.value,
        status: filterStatus.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const setFilterStatus = (status: string) => {
    filterStatus.value = status;
    search();
};


const formatJoiningDate = (dateStr: string | undefined) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'numeric', year: 'numeric' }).replace(/\//g, '-');
};

const getRoleBadgeVariant = (role: string) => {
    switch (role) {
        case 'admin':
            return 'destructive';
        case 'manager':
            return 'default';
        default:
            return 'secondary';
    }
};

const isCurrentUser = (employee: Employee) => {
    return currentUser.value?.id === employee.id;
};

const showDeleteAlert = ref(false);
const employeeToDelete = ref<Employee | null>(null);

const confirmDelete = (employee: Employee) => {
    employeeToDelete.value = employee;
    showDeleteAlert.value = true;
};

const deleteEmployee = () => {
    if (!employeeToDelete.value) return;
    
    router.delete(`/employees/${employeeToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteAlert.value = false;
            employeeToDelete.value = null;
        },
    });
};

import DataTable from '@/components/ui/DataTable.vue';

const columns = [
    { key: 'employee_id', label: 'ID', class: 'w-[100px] hidden md:table-cell' },
    { key: 'employee', label: 'Employee' },
    { key: 'joining_date', label: 'Joining Date', class: 'hidden md:table-cell' },
    { key: 'department', label: 'Department' },
    { key: 'designation', label: 'Designation' },
    { key: 'role', label: 'Role' },
];
</script>

<template>
    <Head title="Employee Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Employee Management</h1>
                </div>
                <Button as-child>
                    <Link href="/employees/create">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Employee
                    </Link>
                </Button>
            </div>

            <!-- Employee Table -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <UserCog class="h-5 w-5" />
                        All Employees
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <!-- Search and Filters -->
                    <div class="mb-4 flex items-center justify-between gap-4">
                        <div class="w-1/3 relative">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input 
                                v-model="searchQuery"
                                placeholder="Search by name, email, ID..."
                                class="pl-10"
                                @input="search"
                            />
                        </div>
                        <div class="flex items-center gap-2">
                            <Button 
                                variant="outline"
                                :class="{ 'bg-primary text-primary-foreground': filterStatus === 'current' }"
                                @click="setFilterStatus('current')"
                            >
                                Current
                            </Button>
                            <Button 
                                variant="outline"
                                :class="{ 'bg-primary text-primary-foreground': filterStatus === 'separated' }"
                                @click="setFilterStatus('separated')"
                            >
                                Separated
                            </Button>
                        </div>
                    </div>

                    <DataTable :columns="columns" :data="employees.data">
                        <!-- Custom Slots -->
                        <template #cell-employee="{ row }">
                            <div>
                                <div class="font-medium">{{ row.name }}</div>
                                <div class="text-sm text-muted-foreground">{{ row.email }}</div>
                            </div>
                        </template>

                        <template #cell-joining_date="{ row }">
                            <div class="text-sm">
                                {{ row.joining_date ? formatJoiningDate(row.joining_date) : '-' }}
                            </div>
                        </template>

                        <template #cell-department="{ row }">
                            <div v-if="row.department" class="text-sm">
                                {{ row.department.name }}
                            </div>
                            <div v-if="row.sub_department" class="text-xs text-muted-foreground">
                                {{ row.sub_department?.name }}
                            </div>
                            <div v-else-if="row.role === 'manager' && row.department" class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">
                                • Head of Department
                            </div>
                        </template>

                        <template #cell-role="{ row }">
                             <Badge :variant="getRoleBadgeVariant(row.role)" class="text-xs capitalize">
                                {{ row.role }}
                            </Badge>
                        </template>

                         <template #actions="{ row }">
                            <div class="flex items-center justify-end gap-2">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    as-child
                                >
                                    <Link :href="`/employees/${row.id}/edit`">
                                        <Edit class="h-4 w-4" />
                                    </Link>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="confirmDelete(row)"
                                    :disabled="isCurrentUser(row)"
                                    :class="[
                                        isCurrentUser(row)
                                            ? 'opacity-50 cursor-not-allowed'
                                            : 'text-destructive hover:text-destructive'
                                    ]"
                                    :title="isCurrentUser(row) ? 'You cannot delete yourself' : 'Delete employee'"
                                >
                                    <Trash class="h-4 w-4" />
                                </Button>
                            </div>
                        </template>
                    </DataTable>

                    <!-- Pagination -->
                    <div v-if="employees.data.length > 0" class="mt-4 flex items-center justify-between border-t border-border/50 pt-4">
                        <div class="text-sm text-muted-foreground hidden md:block">
                            Showing {{ employees.from }} to {{ employees.to }} of {{ employees.total }} results
                        </div>
                        <div class="flex gap-1 flex-1 justify-center md:justify-end">
                            <Link
                                v-for="link in employees.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    'px-3 py-1 text-sm rounded-md transition-colors',
                                    link.active ? 'bg-primary text-primary-foreground shadow-sm' : 'hover:bg-muted text-muted-foreground',
                                    !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                ]"
                                v-html="link.label"
                                :preserve-scroll="true"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>

    <!-- Delete Confirmation Dialog -->
    <AlertDialog v-model:open="showDeleteAlert">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                <AlertDialogDescription>
                    This action cannot be undone. This will permanently delete the employee
                    <strong>{{ employeeToDelete?.name }}</strong> and all their associated data.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Cancel</AlertDialogCancel>
                <AlertDialogAction class="bg-destructive text-destructive-foreground hover:bg-destructive/90" @click="deleteEmployee">
                    Delete
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
