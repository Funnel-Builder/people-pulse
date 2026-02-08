<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { BreadcrumbItem, User } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, User as UserIcon, Building2, Calendar, IdCard, Mail } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import debounce from 'lodash/debounce';

interface Employee {
    id: number;
    name: string;
    email: string;
    employee_id: string;
    profile_picture: string | null;
    designation: string;
    department: string;
    joining_date: string;
    is_active: boolean;
}

interface Props {
    employees: {
        data: Employee[];
        links: any[];
    };
    departments: { id: string; name: string }[];
    filters: {
        search: string;
        department_id: string;
    };
}


const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reports', href: '#' },
    { title: 'Employee Reports', href: '/reports/employees' },
];

const search = ref(props.filters.search || '');
const departmentId = ref(props.filters.department_id || 'all');

const updateFilters = debounce(() => {
    router.get(
        '/reports/employees',
        {
            search: search.value,
            department_id: departmentId.value === 'all' ? null : departmentId.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
}, 300);

watch(search, updateFilters);
watch(departmentId, updateFilters);

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
};
</script>

<template>
    <Head title="Employee Reports" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6 min-h-screen bg-gray-50/50 dark:bg-gray-900/50">
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        Employee Reports
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Select an employee to view their detailed lifetime report.
                    </p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row gap-4 bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="relative flex-1">
                    <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-gray-500" />
                    <Input
                        v-model="search"
                        placeholder="Search by name, ID or email..."
                        class="pl-9 bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-700"
                    />
                </div>
                <div class="w-full sm:w-[200px]">
                    <Select v-model="departmentId">
                        <SelectTrigger class="bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-700">
                            <SelectValue placeholder="All Departments" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Departments</SelectItem>
                            <SelectItem
                                v-for="dept in departments"
                                :key="dept.id"
                                :value="String(dept.id)"
                            >
                                {{ dept.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Employee Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                <Link
                    v-for="employee in employees.data"
                    :key="employee.id"
                    :href="`/reports/employees/${employee.id}`"
                    class="block group"
                >
                    <Card class="relative overflow-hidden rounded-[32px] border border-gray-100 bg-white shadow-sm transition-all hover:shadow-md dark:border-gray-800 dark:bg-gray-900 h-full">
                         <!-- Dot Pattern Background -->
                        <div class="absolute inset-0 z-0 opacity-40 bg-[radial-gradient(#e5e7eb_1px,transparent_1px)] [background-size:20px_20px] dark:bg-[radial-gradient(#1f2937_1px,transparent_1px)]"></div>

                        <div class="relative z-10 flex flex-col h-full">
                            <div class="flex flex-col sm:flex-row gap-6 p-7 pb-4 items-start">
                                <!-- Avatar Section -->
                                <div class="relative shrink-0 mx-auto sm:mx-0">
                                    <Avatar class="h-24 w-24 border-4 border-white shadow-lg dark:border-gray-800">
                                        <AvatarImage :src="employee.profile_picture || undefined" :alt="employee.name" />
                                        <AvatarFallback class="bg-gray-100 text-gray-500 text-xl font-bold">
                                            {{ getInitials(employee.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <!-- Online Status Dot -->
                                    <span 
                                        class="absolute bottom-1 right-1 h-5 w-5 rounded-full border-4 border-white dark:border-gray-800 transition-colors duration-300"
                                        :class="employee.is_active ? 'bg-blue-600' : 'bg-red-500'"
                                    ></span>
                                </div>

                                <!-- Info Section -->
                                <div class="flex-1 text-center sm:text-left space-y-4 w-full">
                                    <div class="space-y-1">
                                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 leading-tight">
                                            {{ employee.name }}
                                        </h3>
                                        <p class="text-gray-500 dark:text-gray-400 font-medium">
                                            {{ employee.designation || 'No Designation' }}
                                        </p>
                                    </div>

                                    <!-- Blue Separator -->
                                    <div class="h-1 w-12 bg-blue-100 dark:bg-blue-900/40 rounded-full mx-auto sm:mx-0"></div>

                                    <!-- Detailed Info Grid -->
                                    <div class="space-y-3">
                                        <!-- Employee ID -->
                                        <div class="flex items-center gap-3 justify-center sm:justify-start">
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gray-50 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                                <IdCard class="h-5 w-5" />
                                            </div>
                                            <div class="text-left">
                                                <p class="text-[10px] uppercase font-bold tracking-wider text-gray-400">Employee ID</p>
                                                <p class="font-bold text-gray-900 dark:text-gray-100 text-sm">{{ employee.employee_id }}</p>
                                            </div>
                                        </div>

                                        <!-- Department -->
                                        <div class="flex items-center gap-3 justify-center sm:justify-start">
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gray-50 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                                <Building2 class="h-5 w-5" />
                                            </div>
                                            <div class="text-left">
                                                <p class="text-[10px] uppercase font-bold tracking-wider text-gray-400">Department</p>
                                                <p class="font-bold text-gray-900 dark:text-gray-100 text-sm">{{ employee.department }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="mt-auto p-6 pt-4 flex items-center justify-between border-t border-dashed border-gray-100 dark:border-gray-800">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-2 rounded-full bg-gray-300"></div>
                                    <span class="text-xs font-medium text-gray-400">Full-time Employee</span>
                                </div>
                                
                                <div class="flex items-center gap-3 text-gray-400">
                                    <a :href="`mailto:${employee.email}`" @click.stop class="hover:text-blue-600 transition-colors cursor-pointer">
                                        <Mail class="h-4 w-4" />
                                    </a>
                                </div>
                            </div>
                        </div>
                    </Card>
                </Link>
            </div>

            <!-- Empty State -->
            <div v-if="employees.data.length === 0" class="flex flex-col items-center justify-center py-12 text-center text-gray-500">
                <UserIcon class="h-12 w-12 text-gray-300 mb-4" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">No employees found</h3>
                <p class="max-w-xs mx-auto mt-2">
                    Try adjusting your search or filters to find what you're looking for.
                </p>
            </div>
            
             <!-- Pagination Placeholder -->
             <div v-if="employees.data.length > 0 && employees.links" class="flex justify-center mt-6">
                <!-- Simple pagination links rendering could go here -->
             </div>

        </div>
    </AppLayout>
</template>
