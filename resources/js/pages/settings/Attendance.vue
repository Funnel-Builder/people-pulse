<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Clock, CheckCircle, AlertTriangle } from 'lucide-vue-next';
import { computed } from 'vue';
import TimePicker from '@/components/ui/time-picker/TimePicker.vue';

interface Props {
    settings: {
        office_start_time: string;
        late_grace_minutes: number;
        office_end_time: string;
        default_break_minutes: number;
    };
}

const props = defineProps<Props>();
const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string });

const form = useForm({
    office_start_time: props.settings.office_start_time,
    late_grace_minutes: props.settings.late_grace_minutes,
    office_end_time: props.settings.office_end_time,
    default_break_minutes: props.settings.default_break_minutes,
});

const submit = () => {
    form.post('/settings/attendance', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Attendance Settings" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold">Attendance Settings</h1>
                <p class="text-muted-foreground">Configure office hours and attendance rules</p>
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

            <form @submit.prevent="submit">
                <div class="grid gap-6">
                    <!-- Office Hours Card -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Clock class="h-5 w-5" />
                                Office Hours
                            </CardTitle>
                            <CardDescription>
                                Set the official start and end times for the workday
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="office_start_time">Office Start Time</Label>
                                <TimePicker
                                    v-model="form.office_start_time"
                                    :class="{ 'border-red-500': form.errors.office_start_time }"
                                />
                                <p v-if="form.errors.office_start_time" class="text-sm text-red-500">
                                    {{ form.errors.office_start_time }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="office_end_time">Office End Time</Label>
                                <TimePicker
                                    v-model="form.office_end_time"
                                    :class="{ 'border-red-500': form.errors.office_end_time }"
                                />
                                <p v-if="form.errors.office_end_time" class="text-sm text-red-500">
                                    {{ form.errors.office_end_time }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Attendance Rules Card -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Attendance Rules</CardTitle>
                            <CardDescription>
                                Configure grace periods and break durations
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="late_grace_minutes">Late Grace Period (minutes)</Label>
                                <Input
                                    id="late_grace_minutes"
                                    v-model.number="form.late_grace_minutes"
                                    type="number"
                                    min="0"
                                    max="120"
                                    required
                                    :class="{ 'border-red-500': form.errors.late_grace_minutes }"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Employees won't be marked late if they clock in within this grace period
                                </p>
                                <p v-if="form.errors.late_grace_minutes" class="text-sm text-red-500">
                                    {{ form.errors.late_grace_minutes }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="default_break_minutes">Default Break Duration (minutes)</Label>
                                <Input
                                    id="default_break_minutes"
                                    v-model.number="form.default_break_minutes"
                                    type="number"
                                    min="0"
                                    max="240"
                                    required
                                    :class="{ 'border-red-500': form.errors.default_break_minutes }"
                                />
                                <p class="text-xs text-muted-foreground">
                                    This break time will be deducted from gross hours to calculate net hours
                                </p>
                                <p v-if="form.errors.default_break_minutes" class="text-sm text-red-500">
                                    {{ form.errors.default_break_minutes }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Save Button -->
                    <div class="flex justify-end">
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Saving...' : 'Save Settings' }}
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
