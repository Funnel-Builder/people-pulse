<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { MultiSelectCalendar } from '@/components/ui/calendar';
import { Info, AlertTriangle, ChevronRight, ChevronLeft, CheckSquare, Search, Briefcase, Heart, Stethoscope, Baby } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { Checkbox } from '@/components/ui/checkbox';

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
    type: 'advance' | 'post';
    coverPersonOptions?: CoverPersonOption[];
    leaveTypes: LeaveType[];
    leaveBalances: LeaveBalance[];
    warningDays?: number;
    defaultLeaveType: string;
}

const props = withDefaults(defineProps<Props>(), {
    coverPersonOptions: () => [],
    warningDays: 2,
});

const emit = defineEmits(['cancel']);

const step = ref(1);
const showWarningModal = ref(false);
const pendingWarningDate = ref<string | null>(null);
const warningConfirmed = ref(false);

const form = useForm({
    leave_type: props.defaultLeaveType,
    reason: '',
    dates: [] as string[],
    cover_person_id: '',
    warning_confirmed: false,
});

// Filter leave types based on application type
const availableLeaveTypes = computed(() => {
    if (props.type === 'advance') {
        return props.leaveTypes.filter(t => ['casual', 'annual', 'maternity'].includes(t.code));
    }
    return props.leaveTypes;
});

// Balance Logic
const currentBalance = computed(() => {
    const balance = props.leaveBalances.find(b => b.leave_type_code === form.leave_type);
    return balance || { leave_type_code: form.leave_type, leave_type_name: '', balance: 0, used: 0, available: 0 };
});

const getCardColorClass = (code: string) => {
    const lowerCode = code.toLowerCase();
    if (lowerCode.includes('sick')) return 'bg-red-500';
    if (lowerCode.includes('casual')) return 'bg-amber-500';
    if (lowerCode.includes('annual') || lowerCode.includes('earned')) return 'bg-blue-500';
    return 'bg-slate-500';
};

const getLeaveIcon = (code: string) => {
    const lowerCode = code.toLowerCase();
    if (lowerCode.includes('sick')) return Stethoscope;
    if (lowerCode.includes('maternity')) return Baby;
    if (lowerCode.includes('casual')) return Briefcase;
    return Heart;
};

// Date Logic
const minDate = computed(() => {
    if (props.type === 'post') return undefined;
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    return tomorrow.toISOString().split('T')[0];
});

const maxDate = computed(() => {
    if (props.type === 'advance') return undefined;
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);
    return yesterday.toISOString().split('T')[0];
});

// Warning Logic
const warningDateLimit = computed(() => {
    const limit = new Date();
    limit.setDate(limit.getDate() + props.warningDays);
    return limit.toISOString().split('T')[0];
});

const isWarningDate = (dateStr: string) => {
    if (!dateStr || props.type === 'post') return false;
    return dateStr <= warningDateLimit.value && dateStr >= (minDate.value || '');
};

watch(() => form.dates, (newDates, oldDates) => {
    if (warningConfirmed.value || props.type === 'post') return;
    
    // Find newly added date
    const addedDate = newDates.find(d => !oldDates.includes(d));
    if (addedDate && isWarningDate(addedDate)) {
        pendingWarningDate.value = addedDate;
        showWarningModal.value = true;
        // Remove temporarily
        form.dates = form.dates.filter(d => d !== addedDate);
    }
}, { deep: true });

const confirmWarningDate = () => {
    warningConfirmed.value = true;
    form.warning_confirmed = true;
    showWarningModal.value = false;
    if (pendingWarningDate.value) {
        form.dates = [...form.dates, pendingWarningDate.value].sort();
        pendingWarningDate.value = null;
    }
};

const formatDate = (dateStr: string) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
};

// Handover Person Label
const selectedCoverPersonName = computed(() => {
    const person = props.coverPersonOptions.find(p => String(p.id) === form.cover_person_id);
    return person ? person.name : 'None Selected';
});

// Navigation
const canContinue = computed(() => {
    if (form.dates.length === 0) return false;
    if (form.dates.length > currentBalance.value.available) return false;
    if (!form.leave_type) return false;
    if (props.type === 'advance' && !form.cover_person_id) return false;
    if (!form.reason) return false;
    return true;
});

const submit = () => {
    console.log('Submit called, type:', props.type);
    console.log('Form data:', {
        leave_type: form.leave_type,
        reason: form.reason,
        dates: form.dates,
        cover_person_id: form.cover_person_id
    });
    const url = props.type === 'advance' ? '/leaves/advance' : '/leaves/post';
    console.log('Posting to:', url);
    form.post(url, {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="max-w-6xl mx-auto w-full">
        <!-- Wizard Steps -->
        <div class="flex items-center justify-center mb-8">
            <div class="flex items-center">
                <div :class="['w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold', step >= 1 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground']">1</div>
                <span :class="['ml-2 text-sm font-medium', step >= 1 ? 'text-foreground' : 'text-muted-foreground']">Type & Dates</span>
            </div>
            <div class="w-16 h-[2px] mx-4 bg-muted"></div>
            <div class="flex items-center">
                <div :class="['w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold', step >= 2 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground']">2</div>
                <span :class="['ml-2 text-sm font-medium', step >= 2 ? 'text-foreground' : 'text-muted-foreground']">Review & Submit</span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" v-if="step === 1">
            <!-- Left Column: Calendar -->
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold">Select Leave Duration</h2>
                    <div class="flex gap-2 text-muted-foreground">
                        <ChevronLeft class="h-5 w-5" />
                        <ChevronRight class="h-5 w-5" />
                    </div>
                </div>

                <div class="bg-card rounded-xl border p-6 min-h-[400px] flex flex-col items-center justify-center">
                    <MultiSelectCalendar
                        v-model="form.dates"
                        :min-date="minDate"
                        :max-date="maxDate"
                        class="mx-auto"
                    />
                </div>

                <!-- Info Box -->
                <div class="bg-blue-50 dark:bg-blue-950/30 rounded-xl p-4 flex items-center gap-4">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-full text-blue-600 dark:text-blue-400">
                        <Info class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground uppercase tracking-wide font-semibold">Selection Summary</p>
                        <p class="font-bold text-foreground">{{ form.dates.length }} Business Days Selected</p>
                    </div>
                </div>
            </div>

            <!-- Right Column: Leave Particulars -->
            <div class="space-y-6">
                <div>
                    <h2 class="text-xl font-bold mb-6">Leave Particulars</h2>
                    
                    <!-- Leave Category -->
                    <div class="space-y-4 mb-6">
                        <Label class="text-xs text-muted-foreground uppercase tracking-widest font-bold">Leave Category</Label>
                        <div class="grid grid-cols-2 gap-4">
                            <div 
                                v-for="type in availableLeaveTypes" 
                                :key="type.id"
                                :class="[
                                    'cursor-pointer border-2 rounded-xl p-4 flex items-center gap-3 transition-all',
                                    form.leave_type === type.code ? 'border-primary bg-primary/5' : 'border-muted hover:border-primary/50'
                                ]"
                                @click="form.leave_type = type.code"
                            >
                                <div :class="['w-2.5 h-2.5 rounded-full', getCardColorClass(type.code)]"></div>
                                <span class="font-semibold">{{ type.name }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Reason -->
                    <div class="space-y-4 mb-6">
                        <Label class="text-xs text-muted-foreground uppercase tracking-widest font-bold">Detailed Reason</Label>
                        <Textarea 
                            v-model="form.reason" 
                            placeholder="Briefly explain the purpose of your leave..." 
                            class="min-h-[120px] rounded-xl resize-none"
                        />
                    </div>

                    <!-- Covering Person (Advance Only) -->
                    <div v-if="type === 'advance'" class="space-y-4">
                        <Label class="text-xs text-muted-foreground uppercase tracking-widest font-bold">Covering Person</Label>
                        <Select v-model="form.cover_person_id">
                            <SelectTrigger class="rounded-xl h-12">
                                <SelectValue placeholder="Select Colleague">
                                    <div v-if="form.cover_person_id" class="flex items-center gap-2">
                                        <Search class="h-4 w-4 text-muted-foreground" />
                                        {{ selectedCoverPersonName }}
                                    </div>
                                    <div v-else class="flex items-center gap-2 text-muted-foreground">
                                        <Search class="h-4 w-4" />
                                        <span>Select Colleague</span>
                                    </div>
                                </SelectValue>
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
                        <p class="text-[10px] text-muted-foreground flex items-center gap-1">
                            <CheckSquare class="h-3 w-3" />
                            This person will be notified to accept your handover.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Review & Submit -->
        <div v-if="step === 2" class="max-w-2xl mx-auto space-y-8">
            <h2 class="text-2xl font-bold text-center">Review & Submit</h2>

            <Card>
                <CardContent class="p-6 space-y-6">
                   <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-xs text-muted-foreground uppercase tracking-widest font-bold mb-1">Leave Type</p>
                            <div class="flex items-center gap-2">
                                <div :class="['w-2 h-2 rounded-full', getCardColorClass(form.leave_type)]"></div>
                                <p class="font-medium">{{ availableLeaveTypes.find(t => t.code === form.leave_type)?.name }}</p>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground uppercase tracking-widest font-bold mb-1">Duration</p>
                            <p class="font-medium">{{ form.dates.length }} Days</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground uppercase tracking-widest font-bold mb-1">Dates</p>
                            <p class="font-medium text-sm">
                                <span v-for="(date, i) in form.dates" :key="date">
                                    {{ formatDate(date) }}{{ i < form.dates.length - 1 ? ', ' : '' }}
                                </span>
                            </p>
                        </div>
                        <div v-if="type === 'advance'">
                            <p class="text-xs text-muted-foreground uppercase tracking-widest font-bold mb-1">Cover Person</p>
                            <p class="font-medium">{{ selectedCoverPersonName }}</p>
                        </div>
                   </div>

                   <div>
                        <p class="text-xs text-muted-foreground uppercase tracking-widest font-bold mb-2">Reason</p>
                        <p class="text-sm text-muted-foreground bg-muted p-4 rounded-lg italic">"{{ form.reason }}"</p>
                   </div>
                   
                   <!-- Balance Preview -->
                   <div class="bg-secondary/20 p-4 rounded-lg flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium">Leave Balance Impact</p>
                            <p class="text-xs text-muted-foreground">After approval</p>
                        </div>
                        <div class="text-right">
                             <p class="text-sm font-bold">{{ currentBalance.available - form.dates.length }} / {{ currentBalance.balance }} Days</p>
                             <p class="text-xs text-muted-foreground">Remaining</p>
                        </div>
                   </div>

                </CardContent>
            </Card>
        </div>

        <!-- Footer Actions -->
        <div class="flex items-center justify-between mt-12 pt-6 border-t">
            <Button variant="ghost" @click="step === 1 ? emit('cancel') : step--" :disabled="form.processing">
                Cancel
            </Button>
            
            <Button v-if="step === 1" @click="step = 2" :disabled="!canContinue" class="bg-primary hover:bg-primary/90 text-primary-foreground px-8">
                Continue to Review
            </Button>

            <Button v-if="step === 2" @click="submit" :disabled="form.processing" class="bg-primary hover:bg-primary/90 text-primary-foreground px-8">
                {{ form.processing ? 'Submitting...' : 'Confirm and Submit' }}
            </Button>
        </div>
    </div>

    <!-- Warning Modal -->
    <Dialog v-model:open="showWarningModal">
        <DialogContent>
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-yellow-600">
                    <AlertTriangle class="h-5 w-5" />
                    Short Notice Leave
                </DialogTitle>
                <DialogDescription>
                    Casual leave requests must be submitted at least {{ warningDays }} working days earlier.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="showWarningModal = false">Change Date</Button>
                <Button @click="confirmWarningDate">Confirm</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
