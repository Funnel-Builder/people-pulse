<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Head, usePage } from '@inertiajs/vue3';
import { CalendarCog, CheckCircle, AlertTriangle, Search, Settings2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AdjustLeaveModal from '@/components/leaves/AdjustLeaveModal.vue';

interface Department {
    id: number;
    name: string;
}

interface Employee {
    id: number;
    name: string;
    email: string;
    employee_id: string;
    designation: string | null;
    department: Department | null;
}

interface LeaveType {
    id: number;
    name: string;
    code: string;
}

interface Props {
    employees: Employee[];
    leaveTypes: LeaveType[];
}

const props = defineProps<Props>();
const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string });

const searchQuery = ref('');
const selectedEmployee = ref<Employee | null>(null);
const isModalOpen = ref(false);

const filteredEmployees = computed(() => {
    if (!searchQuery.value) return props.employees;
    const query = searchQuery.value.toLowerCase();
    return props.employees.filter(emp => 
        emp.name.toLowerCase().includes(query) ||
        emp.email.toLowerCase().includes(query) ||
        emp.employee_id?.toLowerCase().includes(query)
    );
});

const openAdjustModal = (employee: Employee) => {
    selectedEmployee.value = employee;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    selectedEmployee.value = null;
};
</script>

<template>
    <Head title="Leave Settings" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold">Leave Settings</h1>
            </div>

            <!-- Flash Messages -->
            <Alert v-if="flash.success" class="border-green-500 bg-green-50 text-green-700">
                <CheckCircle class="h-4 w-4" />
                <AlertDescription>{{ flash.success }}</AlertDescription>
            </Alert>
            <Alert v-if="flash.error" variant="destructive">
                <AlertTriangle class="h-4 w-4" />
                <AlertDescription>{{ flash.error }}</AlertDescription>
            </Alert>

            <!-- Employee List Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <CalendarCog class="h-5 w-5" />
                        Employee Leave Balances
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <!-- Search -->
                    <div class="mb-4">
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input 
                                v-model="searchQuery"
                                placeholder="Search employees by name, email, or ID..."
                                class="pl-10"
                            />
                        </div>
                    </div>

                    <!-- Employee Table -->
                    <div class="rounded-md border">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b bg-muted/50">
                                    <th class="px-4 py-3 text-left text-sm font-medium">Employee</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium">Employee ID</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium">Department</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium">Designation</th>
                                    <th class="px-4 py-3 text-right text-sm font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr 
                                    v-for="employee in filteredEmployees" 
                                    :key="employee.id"
                                    class="border-b last:border-0 hover:bg-muted/30"
                                >
                                    <td class="px-4 py-3">
                                        <div>
                                            <div class="font-medium">{{ employee.name }}</div>
                                            <div class="text-sm text-muted-foreground">{{ employee.email }}</div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        {{ employee.employee_id || '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        {{ employee.department?.name || '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        {{ employee.designation || '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <Button 
                                            size="sm" 
                                            variant="outline"
                                            @click="openAdjustModal(employee)"
                                        >
                                            <Settings2 class="mr-1.5 h-4 w-4" />
                                            Adjust Leave
                                        </Button>
                                    </td>
                                </tr>
                                <tr v-if="filteredEmployees.length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">
                                        No employees found matching your search.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Adjust Leave Modal -->
        <AdjustLeaveModal 
            v-if="selectedEmployee"
            :is-open="isModalOpen"
            :employee="selectedEmployee"
            :leave-types="leaveTypes"
            @close="closeModal"
        />
    </AppLayout>
</template>
