<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogScrollContent,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Loader2 } from 'lucide-vue-next';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

interface Employee {
    id: number;
    name: string;
    email: string;
    employee_id: string;
}

interface LeaveType {
    id: number;
    name: string;
    code: string;
}

interface LeaveBalance {
    leave_type_id: number;
    leave_type_name: string;
    leave_type_code: string;
    balance: number;
    used: number;
    available: number;
    accrual_type: 'manual' | 'attendance';
    attendance_days_threshold: number;
}

interface Props {
    isOpen: boolean;
    employee: Employee;
    leaveTypes: LeaveType[];
}

interface Emits {
    (e: 'close'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const loading = ref(true);
const balances = ref<LeaveBalance[]>([]);

const form = useForm({
    balances: [] as {
        leave_type_id: number;
        balance: number;
        accrual_type: 'manual' | 'attendance';
        attendance_days_threshold: number | null;
    }[],
});

const fetchBalances = async () => {
    loading.value = true;
    try {
        const response = await fetch(`/settings/leaves/${props.employee.id}/balances`);
        const data = await response.json();
        balances.value = data.balances;
        
        // Initialize form with fetched balances
        form.balances = data.balances.map((b: LeaveBalance) => ({
            leave_type_id: b.leave_type_id,
            balance: b.balance,
            accrual_type: b.accrual_type,
            attendance_days_threshold: b.attendance_days_threshold,
        }));
    } catch (error) {
        console.error('Failed to fetch balances:', error);
    } finally {
        loading.value = false;
    }
};

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        fetchBalances();
    }
});

onMounted(() => {
    if (props.isOpen) {
        fetchBalances();
    }
});

const getBalance = (leaveTypeId: number) => {
    return form.balances.find(b => b.leave_type_id === leaveTypeId);
};

const isAttendanceBased = (leaveTypeId: number) => {
    const balance = getBalance(leaveTypeId);
    return balance?.accrual_type === 'attendance';
};

const toggleAccrualType = (leaveTypeId: number) => {
    const balance = getBalance(leaveTypeId);
    if (balance) {
        balance.accrual_type = balance.accrual_type === 'manual' ? 'attendance' : 'manual';
        
        if (balance.accrual_type === 'attendance') {
            balance.attendance_days_threshold = balance.balance || 0;
        } else {
            // When switching back to manual, preserve the visible value
            balance.balance = balance.attendance_days_threshold || 0;
        }
    }
};

const handleBlur = (leaveTypeId: number) => {
    const b = getBalance(leaveTypeId);
    if (b && isAttendanceBased(leaveTypeId)) {
        if (!b.attendance_days_threshold || b.attendance_days_threshold === 0) {
            b.attendance_days_threshold = 1;
            b.balance = 1;
        }
    }
};

const isAnnualLeave = (leaveType: LeaveType) => {
    return leaveType.name.toLowerCase().includes('annual');
};

const getOriginalBalance = (leaveTypeId: number) => {
    const originalBalance = balances.value.find(b => b.leave_type_id === leaveTypeId);
    return originalBalance?.balance ?? 0;
};

const getOriginalUsed = (leaveTypeId: number) => {
    const originalBalance = balances.value.find(b => b.leave_type_id === leaveTypeId);
    return originalBalance?.used ?? 0;
};

const submit = () => {
    // Sync attendance_days_threshold with balance for attendance-based leaves
    form.balances.forEach(balance => {
        if (balance.accrual_type === 'attendance') {
            balance.attendance_days_threshold = balance.balance || 0;
        }
    });
    
    form.post(`/settings/leaves/${props.employee.id}/balances`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
        },
        onError: (errors) => {
            console.error('Form errors:', errors);
        },
        onFinish: () => {
            // Always close modal after request completes (success or validation errors shown in toast)
            setTimeout(() => emit('close'), 100);
        },
    });
};

const close = () => {
    emit('close');
};
</script>

<template>
    <Dialog :open="isOpen" @update:open="close">
        <DialogScrollContent class="max-w-5xl max-h-[90vh]">
            <DialogHeader>
                <DialogTitle>Adjust Leave Balance</DialogTitle>
                <DialogDescription>
                    Configure leave allocations for <span class="font-medium text-foreground">{{ employee.name }}</span> ({{ employee.employee_id }})
                </DialogDescription>
            </DialogHeader>

            <div v-if="loading" class="flex items-center justify-center py-12">
                <Loader2 class="h-8 w-8 animate-spin text-muted-foreground" />
            </div>

            <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-3 py-4">
                <div 
                    v-for="leaveType in leaveTypes" 
                    :key="leaveType.id"
                    class="rounded-lg border p-4 transition-colors"
                >
                    <div class="flex items-center justify-between mb-4">
                        <Label :for="`balance-${leaveType.id}`" class="font-semibold text-base truncate pr-2">
                            {{ leaveType.name }}
                        </Label>
                        
                        <!-- Custom Toggle for Annual Leave -->
                        <div v-if="isAnnualLeave(leaveType) && getBalance(leaveType.id)">
                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <button
                                            type="button"
                                            :id="`accrual-${leaveType.id}`"
                                            @click.stop.prevent="toggleAccrualType(leaveType.id)"
                                            class="relative inline-flex h-[1.15rem] w-8 shrink-0 items-center rounded-full border-2 border-transparent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                            :style="{ backgroundColor: isAttendanceBased(leaveType.id) ? '#0ea5e9' : '#94a3b8' }"
                                        >
                                            <span
                                                class="pointer-events-none block h-4 w-4 rounded-full bg-white shadow-lg ring-0 transition-transform"
                                                :style="{ transform: isAttendanceBased(leaveType.id) ? 'translateX(12px)' : 'translateX(0px)' }"
                                            ></span>
                                        </button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>{{ isAttendanceBased(leaveType.id) ? 'Disable' : 'Enable' }} Attendance Based</p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </div>
                    </div>

                    <div class="space-y-4" v-if="getBalance(leaveType.id)">
                        <div class="relative">
                            <Input 
                                :id="`balance-${leaveType.id}`"
                                type="number"
                                step="1"
                                min="0"
                                max="365"
                                class="pr-12"
                                placeholder="0"
                                :model-value="isAttendanceBased(leaveType.id) 
                                    ? (getBalance(leaveType.id)?.attendance_days_threshold ?? 0) 
                                    : Math.floor(getBalance(leaveType.id)?.balance ?? 0)"
                                @update:model-value="(v) => { 
                                    const b = getBalance(leaveType.id); 
                                    if (b) { 
                                        const val = parseInt(String(v)) || 0;
                                        if (isAttendanceBased(leaveType.id)) {
                                            b.attendance_days_threshold = val;
                                            b.balance = val;
                                        } else {
                                            b.balance = val;
                                        }
                                    } 
                                }"
                                @blur="handleBlur(leaveType.id)"
                            />
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">days</span>
                        </div>
                    </div>
                    <div v-else class="text-sm text-muted-foreground">Loading...</div>
                </div>
            </div>

            <DialogFooter>
                <div class="flex gap-2 w-full justify-end">
                    <Button variant="outline" @click="close">Cancel</Button>
                    <Button 
                        @click="submit" 
                        :disabled="form.processing || loading"
                    >
                        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        {{ form.processing ? 'Saving...' : 'Save Configuration' }}
                    </Button>
                </div>
            </DialogFooter>
        </DialogScrollContent>
    </Dialog>
</template>
