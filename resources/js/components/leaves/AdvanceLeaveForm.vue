<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { MultiSelectCalendar } from '@/components/ui/calendar';
import { CalendarPlus, AlertTriangle, X } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

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

interface Props {
    coverPersonOptions: CoverPersonOption[];
    leaveTypes: LeaveType[];
    warningDays: number;
    defaultLeaveType: string;
    showHeader?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showHeader: true,
});

// Filter leave types for advance leave (Casual, Annual, Maternity)
const advanceLeaveTypes = computed(() => {
    return props.leaveTypes.filter(t => ['casual', 'annual', 'maternity'].includes(t.code));
});

const form = useForm({
    leave_type: props.defaultLeaveType || 'casual',
    reason: '',
    dates: [] as string[],
    cover_person_id: '',
    warning_confirmed: false,
});

const showWarningModal = ref(false);
const warningModalShown = ref(false);
const pendingWarningDate = ref<string | null>(null);

// Calculate minimum date (tomorrow)
const minDate = computed(() => {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    return tomorrow.toISOString().split('T')[0];
});

// Calculate warning date boundary
const warningDateLimit = computed(() => {
    const limit = new Date();
    limit.setDate(limit.getDate() + props.warningDays);
    return limit.toISOString().split('T')[0];
});

// Check if a date is in warning range
const isWarningDate = (dateStr: string) => {
    if (!dateStr) return false;
    return dateStr <= warningDateLimit.value && dateStr >= minDate.value;
};

// Watch for date changes to show warning
watch(() => form.dates, (newDates, oldDates) => {
    if (warningModalShown.value) return;
    
    // Find newly added date
    const addedDate = newDates.find(d => !oldDates.includes(d));
    if (addedDate && isWarningDate(addedDate)) {
        pendingWarningDate.value = addedDate;
        showWarningModal.value = true;
        // Remove the date until confirmed
        form.dates = form.dates.filter(d => d !== addedDate);
    }
}, { deep: true });

const confirmWarningDate = () => {
    form.warning_confirmed = true;
    warningModalShown.value = true;
    showWarningModal.value = false;
    
    // Add the pending date
    if (pendingWarningDate.value && !form.dates.includes(pendingWarningDate.value)) {
        form.dates = [...form.dates, pendingWarningDate.value].sort();
    }
    pendingWarningDate.value = null;
};

const changeDate = () => {
    pendingWarningDate.value = null;
    showWarningModal.value = false;
};

const removeDate = (date: string) => {
    form.dates = form.dates.filter(d => d !== date);
};

const formatDate = (dateStr: string) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
};

const submit = () => {
    form.post('/leaves/advance', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Card>
        <CardHeader v-if="showHeader" class="text-center">
            <CardTitle class="flex items-center justify-center gap-2">
                <CalendarPlus class="h-5 w-5" />
                Advance Leave Application
            </CardTitle>
            <CardDescription>
                Apply for leave for future dates
            </CardDescription>
        </CardHeader>
        <CardContent :class="{ 'pt-6': !showHeader }">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Leave Type Selection -->
                <div class="space-y-2">
                    <Label for="leave_type">
                        Leave Type <span class="text-destructive">*</span>
                    </Label>
                    <Select v-model="form.leave_type" required>
                        <SelectTrigger>
                            <SelectValue placeholder="Select leave type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem 
                                v-for="type in advanceLeaveTypes" 
                                :key="type.id" 
                                :value="type.code"
                            >
                                {{ type.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="form.errors.leave_type" class="text-sm text-red-500">
                        {{ form.errors.leave_type }}
                    </p>
                </div>

                <!-- Calendar Date Selection -->
                <div class="space-y-2">
                    <Label>
                        Select Dates <span class="text-destructive">*</span>
                    </Label>
                    <div class="border rounded-lg">
                        <MultiSelectCalendar
                            v-model="form.dates"
                            :min-date="minDate"
                            class="mx-auto"
                        />
                    </div>
                    
                    <!-- Selected Dates Display -->
                    <div v-if="form.dates.length" class="flex flex-wrap gap-2 mt-3">
                        <Badge 
                            v-for="date in form.dates" 
                            :key="date"
                            :class="[
                                'flex items-center gap-1 py-1',
                                isWarningDate(date) ? 'border-yellow-500 bg-yellow-50 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : ''
                            ]"
                        >
                            {{ formatDate(date) }}
                            <span v-if="isWarningDate(date)">⚠️</span>
                            <button 
                                type="button" 
                                @click="removeDate(date)"
                                class="ml-1 hover:text-destructive"
                            >
                                <X class="h-3 w-3" />
                            </button>
                        </Badge>
                    </div>
                    
                    <p v-if="form.dates.length === 0" class="text-xs text-muted-foreground">
                        Click on dates to select them
                    </p>
                    <p v-if="form.errors.dates" class="text-sm text-red-500">
                        {{ form.errors.dates }}
                    </p>
                </div>

                <!-- Cover Person -->
                <div class="space-y-2">
                    <Label for="cover_person">
                        Cover Person <span class="text-destructive">*</span>
                    </Label>
                    <Select v-model="form.cover_person_id" required>
                        <SelectTrigger>
                            <SelectValue placeholder="Select a cover person" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem 
                                v-for="person in coverPersonOptions" 
                                :key="person.id" 
                                :value="String(person.id)"
                            >
                                {{ person.name }} ({{ person.employee_id }})
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="form.errors.cover_person_id" class="text-sm text-red-500">
                        {{ form.errors.cover_person_id }}
                    </p>
                    <p v-if="coverPersonOptions.length === 0" class="text-sm text-yellow-600">
                        No cover person options available.
                    </p>
                </div>

                <!-- Reason -->
                <div class="space-y-2">
                    <Label for="reason">
                        Reason <span class="text-destructive">*</span>
                    </Label>
                    <Textarea
                        id="reason"
                        v-model="form.reason"
                        required
                        rows="3"
                        placeholder="Reason for your leave..."
                    />
                    <p v-if="form.errors.reason" class="text-sm text-red-500">{{ form.errors.reason }}</p>
                </div>

                <Button 
                    type="submit" 
                    :disabled="form.processing || coverPersonOptions.length === 0 || form.dates.length === 0"
                    class="w-full"
                >
                    {{ form.processing ? 'Submitting...' : 'Submit' }}
                </Button>
            </form>
        </CardContent>
    </Card>

    <!-- Warning Modal -->
    <Dialog v-model:open="showWarningModal">
        <DialogContent>
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-yellow-600">
                    <AlertTriangle class="h-5 w-5" />
                    Short Notice Leave
                </DialogTitle>
                <DialogDescription>
                    Casual leave requests must be submitted at least {{ warningDays }} working days earlier
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <Button variant="outline" @click="changeDate">
                    Change Date
                </Button>
                <Button @click="confirmWarningDate">
                    Confirm
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
