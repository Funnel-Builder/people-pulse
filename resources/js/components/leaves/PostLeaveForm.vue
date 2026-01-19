<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { MultiSelectCalendar } from '@/components/ui/calendar';
import { CalendarMinus, X, AlertCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

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
    leaveTypes: LeaveType[];
    leaveBalances: LeaveBalance[];
    defaultLeaveType: string;
    showHeader?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showHeader: true,
});

const form = useForm({
    reason: '',
    dates: [] as string[],
    leave_type: props.defaultLeaveType,
});

const showInsufficientBalanceModal = ref(false);

// Get current balance for selected leave type
const currentBalance = computed(() => {
    const balance = props.leaveBalances.find(b => b.leave_type_code === form.leave_type);
    return balance || { leave_type_code: form.leave_type, leave_type_name: '', balance: 0, used: 0, available: 0 };
});

// Get selected leave type name
const selectedLeaveTypeName = computed(() => {
    const leaveType = props.leaveTypes.find(t => t.code === form.leave_type);
    return leaveType?.name || '';
});

// Calculate maximum date (yesterday - today should be disabled)
const maxDate = computed(() => {
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);
    return yesterday.toISOString().split('T')[0];
});

const removeDate = (date: string) => {
    form.dates = form.dates.filter(d => d !== date);
};

const formatDate = (dateStr: string) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
};

const submit = () => {
    // Check if user has sufficient balance
    if (form.dates.length > currentBalance.value.available) {
        showInsufficientBalanceModal.value = true;
        return;
    }

    form.post('/leaves/post', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Card>
        <CardHeader v-if="showHeader" class="text-center">
            <CardTitle class="flex items-center justify-center gap-2">
                <CalendarMinus class="h-5 w-5" />
                Post Leave Application
            </CardTitle>
            <CardDescription>
                Apply for leave for past dates
            </CardDescription>
        </CardHeader>
        <CardContent :class="{ 'pt-6': !showHeader }">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Leave Type -->
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
                                v-for="type in leaveTypes" 
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
                            :max-date="maxDate"
                            class="mx-auto"
                        />
                    </div>
                    
                    <!-- Selected Dates Display -->
                    <div v-if="form.dates.length" class="flex flex-wrap gap-2 mt-3">
                        <Badge 
                            v-for="date in form.dates" 
                            :key="date"
                            class="flex items-center gap-1 py-1"
                        >
                            {{ formatDate(date) }}
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
                    :disabled="form.processing || form.dates.length === 0"
                    class="w-full"
                >
                    {{ form.processing ? 'Submitting...' : 'Submit' }}
                </Button>
            </form>
        </CardContent>
    </Card>

    <!-- Insufficient Balance Modal -->
    <Dialog v-model:open="showInsufficientBalanceModal">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-amber-600 dark:text-amber-500">
                    <AlertCircle class="h-5 w-5" />
                    Insufficient Leave Balance
                </DialogTitle>
                <DialogDescription class="space-y-3 pt-2">
                    <p class="text-sm text-foreground">
                        You don't have enough leave balance to apply for this request. Please reduce the number of days or select a different leave type.
                    </p>
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="default" @click="showInsufficientBalanceModal = false">
                    Back to Form
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
