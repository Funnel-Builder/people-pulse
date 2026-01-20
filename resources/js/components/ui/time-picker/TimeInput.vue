<script setup lang="ts">
import { ref, watch, computed, nextTick } from 'vue';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Clock } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

const props = defineProps<{
    modelValue: string; // HH:mm format (24h)
    className?: string;
    placeholder?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const isOpen = ref(false);
const selectedHour = ref<string>('09'); // 12h format
const selectedMinute = ref<string>('00');
const selectedPeriod = ref<string>('AM'); // AM or PM

const hourContainerRef = ref<HTMLElement | null>(null);
const minuteContainerRef = ref<HTMLElement | null>(null);
const periodContainerRef = ref<HTMLElement | null>(null);

// Generate hours 01-12
const hours = Array.from({ length: 12 }, (_, i) => (i + 1).toString().padStart(2, '0'));
// Generate minutes 00-59
const minutes = Array.from({ length: 60 }, (_, i) => i.toString().padStart(2, '0'));
const periods = ['AM', 'PM'];

// Parse initial value (HH:mm 24h -> 12h + AM/PM)
watch(() => props.modelValue, (val) => {
    if (val) {
        const [h, m] = val.split(':').map(Number);
        if (!isNaN(h) && !isNaN(m)) {
            const date = new Date();
            date.setHours(h);
            date.setMinutes(m);
            
            let hours12 = date.getHours();
            const period = hours12 >= 12 ? 'PM' : 'AM';
            
            hours12 = hours12 % 12;
            hours12 = hours12 ? hours12 : 12; // the hour '0' should be '12'
            
            selectedHour.value = hours12.toString().padStart(2, '0');
            selectedMinute.value = m.toString().padStart(2, '0');
            selectedPeriod.value = period;
        }
    }
}, { immediate: true });

const emitUpdate = () => {
    let hour = parseInt(selectedHour.value);
    const minute = selectedMinute.value;
    const period = selectedPeriod.value;

    if (period === 'PM' && hour !== 12) {
        hour += 12;
    } else if (period === 'AM' && hour === 12) {
        hour = 0;
    }

    const hourStr = hour.toString().padStart(2, '0');
    emit('update:modelValue', `${hourStr}:${minute}`);
};

const selectHour = (h: string) => {
    selectedHour.value = h;
    emitUpdate();
};

const selectMinute = (m: string) => {
    selectedMinute.value = m;
    emitUpdate();
};

const selectPeriod = (p: string) => {
    selectedPeriod.value = p;
    emitUpdate();
};

const scrollToSelected = (container: HTMLElement | null, selectedValue: string) => {
    if (!container) return;
    const selectedEl = container.querySelector(`[data-value="${selectedValue}"]`) as HTMLElement;
    if (selectedEl) {
        container.scrollTop = selectedEl.offsetTop - container.offsetTop - (container.offsetHeight / 2) + (selectedEl.offsetHeight / 2);
    }
};

const onOpenChange = (open: boolean) => {
    if (open) {
        nextTick(() => {
            scrollToSelected(hourContainerRef.value, selectedHour.value);
            scrollToSelected(minuteContainerRef.value, selectedMinute.value);
            scrollToSelected(periodContainerRef.value, selectedPeriod.value);
        });
    }
};

const displayTime = computed(() => {
    if (!props.modelValue) return props.placeholder || 'Pick a time';
    // Format 24h time to 12h for display
    const [h, m] = props.modelValue.split(':').map(Number);
    if (isNaN(h) || isNaN(m)) return props.modelValue;
    
    let hours12 = h % 12;
    hours12 = hours12 ? hours12 : 12;
    const period = h >= 12 ? 'PM' : 'AM';
    
    return `${hours12}:${m.toString().padStart(2, '0')} ${period}`;
});
</script>

<template>
    <Popover v-model:open="isOpen" @update:open="onOpenChange">
        <PopoverTrigger as-child>
            <Button
                type="button"
                variant="outline"
                :class="cn(
                    'w-full justify-start text-left font-normal',
                    !modelValue && 'text-muted-foreground',
                    props.className
                )"
            >
                <Clock class="mr-2 h-4 w-4" />
                {{ displayTime }}
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-auto p-0" align="start">
            <div class="flex h-52 overflow-hidden divide-x">
                <!-- Hours Column -->
                <div class="flex flex-col w-16">
                    <div class="px-2 py-2 text-xs font-semibold text-center border-b bg-muted/50">Hour</div>
                    <div ref="hourContainerRef" class="flex-1 overflow-y-auto p-1 scrollbar-hide">
                        <div
                            v-for="h in hours"
                            :key="h"
                            :data-value="h"
                            @click.stop="selectHour(h)"
                            :class="cn(
                                'flex items-center justify-center p-1.5 text-sm rounded-md cursor-pointer hover:bg-accent hover:text-accent-foreground',
                                selectedHour === h && 'bg-accent text-accent-foreground font-medium'
                            )"
                        >
                            {{ h }}
                        </div>
                    </div>
                </div>

                <!-- Minutes Column -->
                <div class="flex flex-col w-16">
                    <div class="px-2 py-2 text-xs font-semibold text-center border-b bg-muted/50">Min</div>
                    <div ref="minuteContainerRef" class="flex-1 overflow-y-auto p-1 scrollbar-hide">
                        <div
                            v-for="m in minutes"
                            :key="m"
                            :data-value="m"
                            @click.stop="selectMinute(m)"
                            :class="cn(
                                'flex items-center justify-center p-1.5 text-sm rounded-md cursor-pointer hover:bg-accent hover:text-accent-foreground',
                                selectedMinute === m && 'bg-accent text-accent-foreground font-medium'
                            )"
                        >
                            {{ m }}
                        </div>
                    </div>
                </div>

                <!-- AM/PM Column -->
                <div class="flex flex-col w-16">
                    <div class="px-2 py-2 text-xs font-semibold text-center border-b bg-muted/50">AM/PM</div>
                    <div ref="periodContainerRef" class="flex-1 overflow-y-auto p-1 scrollbar-hide">
                        <div
                            v-for="p in periods"
                            :key="p"
                            :data-value="p"
                            @click.stop="selectPeriod(p)"
                            :class="cn(
                                'flex items-center justify-center p-1.5 text-sm rounded-md cursor-pointer hover:bg-accent hover:text-accent-foreground',
                                selectedPeriod === p && 'bg-accent text-accent-foreground font-medium'
                            )"
                        >
                            {{ p }}
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Optional: Done button logic is usually not needed if updates are immediate, but can keep for explicit closing -->
            <div class="p-2 border-t bg-muted/20">
                <Button type="button" size="sm" class="w-full" @click="isOpen = false">Done</Button>
            </div>
        </PopoverContent>
    </Popover>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
