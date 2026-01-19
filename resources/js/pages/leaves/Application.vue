<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { CalendarPlus, CalendarMinus } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import LeaveApplicationWizard from '@/components/leaves/LeaveApplicationWizard.vue';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'My Leaves', href: '/leaves' },
    { title: 'Apply', href: '/leaves/apply' },
];

interface CoverPersonOption {
    id: number;
    name: string;
    employee_id: string;
    designation: string;
}

interface LeaveType {
    id: number;
    name: string;
    code: string;
}

interface LeaveBalance {
    leave_type_code: string;
    leave_type_name: string;
    balance: number;
    used: number;
    available: number;
}

interface Props {
    coverPersonOptions: CoverPersonOption[];
    leaveTypes: LeaveType[];
    leaveBalances: LeaveBalance[];
    warningDays: number;
    defaultAdvanceLeaveType: string;
    defaultPostLeaveType: string;
}

const props = defineProps<Props>();

const selectedType = ref<'advance' | 'post' | null>(null);

const pageTitle = computed(() => {
    if (selectedType.value === 'advance') return 'Advance Leave Application';
    if (selectedType.value === 'post') return 'Post Leave Application';
    return 'Leave Application';
});

const pageDescription = computed(() => {
    if (selectedType.value === 'advance') return 'Applying for advance leave';
    if (selectedType.value === 'post') return 'Applying for post leave';
    return 'Apply for advance or post leave';
});

const goBack = () => {
    selectedType.value = null;
};
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col items-center gap-6 p-4 md:p-6">
            <!-- Header - Dynamic based on selection -->
            <div class="text-center">
                <h1 class="text-2xl font-bold">{{ pageTitle }}</h1>
            </div>

            <!-- Type Selection - Centered -->
            <div v-if="!selectedType" class="grid gap-6 md:grid-cols-2 max-w-3xl w-full">
                <Card
                    class="cursor-pointer transition-all hover:border-primary hover:shadow-md"
                    @click="selectedType = 'advance'"
                >
                    <CardHeader class="text-center">
                        <div class="flex flex-col items-center gap-3">
                            <div class="p-4 rounded-full bg-green-100 dark:bg-green-900">
                                <CalendarPlus class="h-8 w-8 text-green-600 dark:text-green-400" />
                            </div>
                            <CardTitle>Advance Leave</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent class="text-center text-sm text-muted-foreground">
                        For future dates
                    </CardContent>
                </Card>

                <Card
                    class="cursor-pointer transition-all hover:border-primary hover:shadow-md"
                    @click="selectedType = 'post'"
                >
                    <CardHeader class="text-center">
                        <div class="flex flex-col items-center gap-3">
                            <div class="p-4 rounded-full bg-blue-100 dark:bg-blue-900">
                                <CalendarMinus class="h-8 w-8 text-blue-600 dark:text-blue-400" />
                            </div>
                            <CardTitle>Post Leave</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent class="text-center text-sm text-muted-foreground">
                        For past dates
                    </CardContent>
                </Card>
            </div>

            <!-- Wizard Form -->
            <div v-else class="w-full">
                <LeaveApplicationWizard
                    :type="selectedType"
                    :cover-person-options="coverPersonOptions"
                    :leave-types="leaveTypes"
                    :leave-balances="leaveBalances"
                    :warning-days="warningDays"
                    :default-leave-type="selectedType === 'advance' ? defaultAdvanceLeaveType : defaultPostLeaveType"
                    @cancel="goBack"
                />
            </div>
        </div>
    </AppLayout>
</template>
