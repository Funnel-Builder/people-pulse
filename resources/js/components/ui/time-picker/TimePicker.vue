<script setup lang="ts">
import { ref, watch, computed, nextTick } from 'vue';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Clock } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

const props = defineProps<{
    modelValue: string;
    className?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const isOpen = ref(false);
const selectedHour = ref<string>('09');
const selectedMinute = ref<string>('00');
const hourContainerRef = ref<HTMLElement | null>(null);
const minuteContainerRef = ref<HTMLElement | null>(null);

// Generate hours 00-23
const hours = Array.from({ length: 24 }, (_, i) => i.toString().padStart(2, '0'));
// Generate minutes 00-59
const minutes = Array.from({ length: 60 }, (_, i) => i.toString().padStart(2, '0'));

// Parse initial value
watch(() => props.modelValue, (val) => {
    if (val) {
        const [h, m] = val.split(':');
        if (h && m) {
            selectedHour.value = h;
            selectedMinute.value = m;
        }
    }
}, { immediate: true });

const displayTime = computed(() => {
    return `${selectedHour.value}:${selectedMinute.value}`;
});

const handleTimeChange = () => {
    emit('update:modelValue', displayTime.value);
};

const selectHour = (h: string) => {
    selectedHour.value = h;
    handleTimeChange();
};

const selectMinute = (m: string) => {
    selectedMinute.value = m;
    handleTimeChange();
};

const scrollToSelected = (container: HTMLElement | null, selectedValue: string) => {
    if (!container) return;
    const selectedEl = container.querySelector(`[data-value="${selectedValue}"]`) as HTMLElement;
    if (selectedEl) {
        container.scrollTop = selectedEl.offsetTop - container.offsetTop - (container.offsetHeight / 2) + (selectedEl.offsetHeight / 2);
    }
};

// Scroll to selected time when popover opens
const onOpenChange = (open: boolean) => {
    if (open) {
        nextTick(() => {
            scrollToSelected(hourContainerRef.value, selectedHour.value);
            scrollToSelected(minuteContainerRef.value, selectedMinute.value);
        });
    }
};
</script>

<template>
    <Popover v-model:open="isOpen" @update:open="onOpenChange">
        <PopoverTrigger as-child>
            <Button
                variant="outline"
                :class="cn(
                    'w-full justify-start text-left font-normal',
                    !modelValue && 'text-muted-foreground',
                    props.className
                )"
            >
                <Clock class="mr-2 h-4 w-4" />
                {{ modelValue || 'Pick a time' }}
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-auto p-0" align="start">
            <div class="flex h-64 overflow-hidden divide-x">
                <!-- Hours Column -->
                <div class="flex flex-col w-20">
                    <div class="px-3 py-2 text-xs font-semibold text-center border-b bg-muted/50">Hour</div>
                    <div ref="hourContainerRef" class="flex-1 overflow-y-auto p-1 scrollbar-hide">
                        <div
                            v-for="h in hours"
                            :key="h"
                            :data-value="h"
                            @click="selectHour(h)"
                            :class="cn(
                                'flex items-center justify-center p-2 text-sm rounded-md cursor-pointer hover:bg-accent hover:text-accent-foreground',
                                selectedHour === h && 'bg-accent text-accent-foreground font-medium'
                            )"
                        >
                            {{ h }}
                        </div>
                    </div>
                </div>

                <!-- Minutes Column -->
                <div class="flex flex-col w-20">
                    <div class="px-3 py-2 text-xs font-semibold text-center border-b bg-muted/50">Minute</div>
                    <div ref="minuteContainerRef" class="flex-1 overflow-y-auto p-1 scrollbar-hide">
                        <div
                            v-for="m in minutes"
                            :key="m"
                            :data-value="m"
                            @click="selectMinute(m)"
                            :class="cn(
                                'flex items-center justify-center p-2 text-sm rounded-md cursor-pointer hover:bg-accent hover:text-accent-foreground',
                                selectedMinute === m && 'bg-accent text-accent-foreground font-medium'
                            )"
                        >
                            {{ m }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-2 border-t bg-muted/20">
                <Button size="sm" class="w-full" @click="isOpen = false">Done</Button>
            </div>
        </PopoverContent>
    </Popover>
</template>

<style scoped>
/* Hide scrollbar for Chrome, Safari and Opera */
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
/* Hide scrollbar for IE, Edge and Firefox */
.scrollbar-hide {
    -ms-overflow-style: none;  /* IE and Edge */
    scrollbar-width: none;  /* Firefox */
}
</style>
