<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Calendar as CalendarIcon } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Calendar } from '@/components/ui/calendar';
import { cn } from '@/lib/utils';
import TimePicker from '@/components/ui/time-picker/TimePicker.vue';

const props = defineProps<{
    modelValue: string; // ISO string YYYY-MM-DDTHH:mm
    className?: string;
    placeholder?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

// Use string for selectedDate to match Calendar component's expected type
const selectedDate = ref<string | null>(null);
const selectedTime = ref<string>('09:00');
const isOpen = ref(false);

// Initialize from modelValue
watch(() => props.modelValue, (val) => {
    if (val) {
        const date = new Date(val);
        if (!isNaN(date.getTime())) {
            // Format as YYYY-MM-DD for Calendar component
            selectedDate.value = date.toLocaleDateString('en-CA');
            const hours = date.getHours().toString().padStart(2, '0');
            const minutes = date.getMinutes().toString().padStart(2, '0');
            selectedTime.value = `${hours}:${minutes}`;
        }
    }
}, { immediate: true });

// Combine Date and Time into modelValue string
const updateModelValue = () => {
    if (selectedDate.value && selectedTime.value) {
        emit('update:modelValue', `${selectedDate.value}T${selectedTime.value}`);
    }
};

watch(selectedDate, () => updateModelValue());
watch(selectedTime, () => updateModelValue());

const formattedDisplay = computed(() => {
    if (!selectedDate.value) return props.placeholder || 'Pick a date & time';
    const date = new Date(selectedDate.value);
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return `${monthNames[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()} at ${selectedTime.value}`;
});
</script>

<template>
    <Popover v-model:open="isOpen">
        <PopoverTrigger as-child>
            <Button
                variant="outline"
                :class="cn(
                    'w-full justify-start text-left font-normal',
                    !modelValue && 'text-muted-foreground',
                    props.className
                )"
            >
                <CalendarIcon class="mr-2 h-4 w-4" />
                {{ formattedDisplay }}
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-auto p-0" align="start">
            <div class="flex flex-col sm:flex-row divide-y sm:divide-y-0 sm:divide-x">
                <div class="p-3">
                    <Calendar v-model="selectedDate" class="rounded-md border shadow-none" />
                </div>
                <div class="p-3">
                    <div class="mb-2 text-sm font-medium">Time</div>
                    <TimePicker v-model="selectedTime" />
                </div>
            </div>
        </PopoverContent>
    </Popover>
</template>

