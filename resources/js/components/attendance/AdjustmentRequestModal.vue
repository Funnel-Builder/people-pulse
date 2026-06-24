<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useForm } from '@inertiajs/vue3';
import { LogIn, LogOut, Paperclip } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface CoverPerson {
    id: number;
    name: string;
    employee_id: string;
    designation?: string;
}

interface Props {
    open: boolean;
    coverPersonOptions: CoverPerson[];
    defaultDate?: string | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{ (e: 'update:open', value: boolean): void }>();

type AdjustmentType = 'late_entry' | 'early_out';

const form = useForm<{
    type: AdjustmentType;
    date: string;
    requested_time: string;
    reason: string;
    cover_person_id: string;
    attachment: File | null;
}>({
    type: 'late_entry',
    date: props.defaultDate ?? '',
    requested_time: '',
    reason: '',
    cover_person_id: '',
    attachment: null,
});

const fileName = ref('');

// Reset the form each time the modal is opened, prefilling the selected date.
watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            form.reset();
            form.clearErrors();
            form.date = props.defaultDate ?? '';
            fileName.value = '';
        }
    },
);

const close = () => emit('update:open', false);

const onFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;
    form.attachment = file;
    fileName.value = file?.name ?? '';
};

const submit = () => {
    form.post('/attendance/adjustments', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => close(),
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="(v) => emit('update:open', v)">
        <DialogContent class="sm:max-w-[480px]">
            <DialogHeader>
                <DialogTitle>Request Attendance Adjustment</DialogTitle>
                <DialogDescription>
                    Submit a request to excuse a late arrival or early departure. It will be routed for approval.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4 py-2">
                <!-- Type toggle -->
                <div class="grid grid-cols-2 gap-2 rounded-lg bg-gray-100 dark:bg-gray-800 p-1">
                    <button
                        type="button"
                        :class="[
                            'flex items-center justify-center gap-2 rounded-md py-2 text-sm font-medium transition',
                            form.type === 'late_entry'
                                ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm'
                                : 'text-gray-500 dark:text-gray-400',
                        ]"
                        @click="form.type = 'late_entry'"
                    >
                        <LogIn class="h-4 w-4" />
                        Late Entry
                    </button>
                    <button
                        type="button"
                        :class="[
                            'flex items-center justify-center gap-2 rounded-md py-2 text-sm font-medium transition',
                            form.type === 'early_out'
                                ? 'bg-white dark:bg-gray-700 text-orange-600 dark:text-orange-400 shadow-sm'
                                : 'text-gray-500 dark:text-gray-400',
                        ]"
                        @click="form.type = 'early_out'"
                    >
                        <LogOut class="h-4 w-4" />
                        Early Out
                    </button>
                </div>

                <!-- Date + Time -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <Label for="adj-date">Date</Label>
                        <Input id="adj-date" v-model="form.date" type="date" class="mt-1.5" />
                        <p v-if="form.errors.date" class="mt-1 text-xs text-red-500">{{ form.errors.date }}</p>
                    </div>
                    <div>
                        <Label for="adj-time">
                            {{ form.type === 'late_entry' ? 'Arrival Time' : 'Departure Time' }}
                        </Label>
                        <Input id="adj-time" v-model="form.requested_time" type="time" class="mt-1.5" />
                        <p v-if="form.errors.requested_time" class="mt-1 text-xs text-red-500">{{ form.errors.requested_time }}</p>
                    </div>
                </div>

                <!-- Cover person -->
                <div>
                    <Label>Cover Person</Label>
                    <Select v-model="form.cover_person_id">
                        <SelectTrigger class="mt-1.5">
                            <SelectValue placeholder="Select a colleague" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="person in coverPersonOptions"
                                :key="person.id"
                                :value="String(person.id)"
                            >
                                {{ person.name }} <span class="text-muted-foreground">(#{{ person.employee_id }})</span>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="form.errors.cover_person_id" class="mt-1 text-xs text-red-500">{{ form.errors.cover_person_id }}</p>
                </div>

                <!-- Reason -->
                <div>
                    <Label for="adj-reason">Reason</Label>
                    <Textarea
                        id="adj-reason"
                        v-model="form.reason"
                        rows="3"
                        placeholder="Explain why you need this adjustment..."
                        class="mt-1.5"
                    />
                    <p v-if="form.errors.reason" class="mt-1 text-xs text-red-500">{{ form.errors.reason }}</p>
                </div>

                <!-- Attachment -->
                <div>
                    <Label for="adj-attachment">Supporting Document <span class="text-muted-foreground">(optional)</span></Label>
                    <label
                        for="adj-attachment"
                        class="mt-1.5 flex cursor-pointer items-center gap-2 rounded-md border border-dashed border-gray-300 dark:border-gray-700 px-3 py-2 text-sm text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800"
                    >
                        <Paperclip class="h-4 w-4" />
                        <span class="truncate">{{ fileName || 'Choose file (PDF, JPG, PNG · max 5MB)' }}</span>
                    </label>
                    <input
                        id="adj-attachment"
                        type="file"
                        accept=".pdf,.jpg,.jpeg,.png"
                        class="hidden"
                        @change="onFileChange"
                    />
                    <p v-if="form.errors.attachment" class="mt-1 text-xs text-red-500">{{ form.errors.attachment }}</p>
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="close" :disabled="form.processing">Cancel</Button>
                <Button @click="submit" :disabled="form.processing">
                    {{ form.processing ? 'Submitting...' : 'Submit Request' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
