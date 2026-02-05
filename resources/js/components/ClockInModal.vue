<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent } from '@/components/ui/dialog';
import { cn } from '@/lib/utils';
import { Loader2, LogIn, LogOut, CheckCircle2 } from 'lucide-vue-next';
import { ref, computed } from 'vue';

const props = defineProps<{
    modelValue: boolean;
    status: 'idle' | 'working' | 'clocked_out';
    loading?: boolean;
    stats?: {
        total_days: number;
        present: number;
        late: number;
        total_net_hours: number;
    };
    workedToday?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
    (e: 'confirm'): void;
}>();

const showSuccess = ref(false);
const isClosing = ref(false);

const title = computed(() => {
    switch (props.status) {
        case 'working':
            return 'Ready to Clock Out?';
        case 'clocked_out':
            return 'Day Complete!';
        default:
            return 'Ready to Clock In?';
    }
});

const buttonLabel = computed(() => {
    switch (props.status) {
        case 'working':
            return 'Confirm Clock Out';
        default:
            return 'Confirm Clock In';
    }
});

const handleConfirm = async () => {
    // Determine animation duration based on connection speed (simulated by loading prop duration)
    // The parent handles the API call and sets loading.
    // We want the wave to start immediately.
    
    // Trigger parent action
    emit('confirm');
};

// Watch for loading completion to trigger success
import { watch } from 'vue';
watch(() => props.loading, (newVal, oldVal) => {
    if (oldVal === true && newVal === false) {
        // Loading finished, show success
        showSuccess.value = true;
        setTimeout(() => {
            closeModal();
        }, 1500); // Show success for 1.5s
    }
});

const closeModal = () => {
    isClosing.value = true;
    setTimeout(() => {
        showSuccess.value = false;
        isClosing.value = false;
        emit('update:modelValue', false);
    }, 300);
};

</script>

<template>
    <Dialog :open="modelValue" @update:open="(val) => !loading && emit('update:modelValue', val)">
        <DialogContent class="sm:max-w-[425px] overflow-hidden border-0 bg-transparent shadow-none p-0 flex items-center justify-center min-h-[300px]">
            
            <!-- Backdrop/Card with blurring is handled by DialogOverlay usually, but we want a custom card look -->
            <div class="relative w-full max-w-sm overflow-hidden rounded-3xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-2xl transition-all duration-300 transform">
                
                <!-- Success Overlay (Fades in) -->
                <transition name="fade-scale">
                    <div 
                        v-if="showSuccess" 
                        class="absolute inset-0 z-50 flex flex-col items-center justify-center bg-blue-600 text-white"
                    >
                        <CheckCircle2 class="h-12 w-12 animate-in zoom-in duration-500" />
                        <h3 class="mt-4 text-lg font-semibold">Success!</h3>
                        <p class="text-xs opacity-90">Action completed successfully.</p>
                    </div>
                </transition>

                <!-- Loading Wave Animation Overlay -->
                <transition name="fade">
                    <div v-if="loading" class="absolute inset-0 z-40 flex items-center justify-center bg-black/5 dark:bg-white/5 backdrop-blur-[2px]">
                        <div class="ripple-container">
                            <div class="ripple-wave bg-primary/30"></div>
                            <div class="ripple-wave bg-primary/30" style="animation-delay: 0.5s"></div>
                            <div class="ripple-wave bg-primary/30" style="animation-delay: 1.0s"></div>
                        </div>
                    </div>
                </transition>

                <!-- Content -->
                <div class="relative z-10 p-6 flex flex-col items-center text-center space-y-6" :class="{ 'opacity-0': showSuccess }">
                    
                    <!-- Icon Bubble -->
                    <div 
                        class="relative flex h-24 w-24 items-center justify-center rounded-full bg-primary/10 shadow-inner"
                        :class="{ 'bg-amber-500/10 text-amber-500': status === 'working', 'bg-primary/10 text-primary': status === 'idle' }"
                    >
                        <LogOut v-if="status === 'working'" class="h-10 w-10" />
                        <LogIn v-else class="h-10 w-10" />
                        
                        <!-- Pulse Ring -->
                        <div class="absolute inset-0 rounded-full border-2 border-inherit opacity-20 animate-ping"></div>
                    </div>

                    
                    <div class="space-y-2">
                        <h2 class="text-xl font-semibold text-foreground">
                            {{ title }}
                        </h2>
                        
                        <!-- Working (Clocking Out) -> Show Today's Hours -->
                        <div v-if="status === 'working'" class="text-sm text-muted-foreground">
                            <p>You have worked <span class="font-bold text-foreground">{{ workedToday || '0h 0m' }}</span> today.</p>
                            <p class="text-xs mt-1">Clock out to finalize your shift.</p>
                        </div>

                        <!-- Idle (Clocking In) -> Show Month's Hours -->
                        <div v-else-if="status === 'idle' && stats" class="text-sm text-muted-foreground">
                            <p>You have worked <span class="font-bold text-foreground">{{ stats.total_net_hours }}h</span> this month.</p>
                            <p class="text-xs mt-1">Confirm to update your attendance record.</p>
                        </div>

                        <!-- Fallback / Day Complete -->
                        <div v-else-if="status === 'clocked_out'" class="text-sm text-muted-foreground">
                            <p>Attendance for today is complete.</p>
                        </div>
                        
                        <p v-else class="text-sm text-muted-foreground">
                            Please confirm your action below.
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="w-full flex flex-col gap-3">
                        <Button 
                            class="w-full h-11 text-base font-medium rounded-xl shadow-lg transition-all duration-200 active:scale-95"
                            :class="{ 
                                'bg-gradient-to-r from-red-500 to-rose-600 hover:from-red-600 hover:to-rose-700 text-white': status === 'working',
                                'bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white': status === 'idle'
                            }"
                            :disabled="loading"
                            @click="handleConfirm"
                        >
                            <span v-if="loading">Processing...</span>
                            <span v-else>{{ buttonLabel }}</span>
                        </Button>
                        
                        <Button 
                            variant="ghost" 
                            class="w-full rounded-xl"
                            @click="emit('update:modelValue', false)"
                            :disabled="loading"
                        >
                            Cancel
                        </Button>
                    </div>

                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>

<style scoped>
/* Wave Animation similar to AuthSplitLayout */
.ripple-container {
    position: absolute;
    display: flex;
    justify-content: center;
    align-items: center;
    inset: 0;
    pointer-events: none;
}

.ripple-wave {
    position: absolute;
    width: 200px;
    height: 200px;
    border-radius: 50%;
    animation: ripple 2s cubic-bezier(0, 0.2, 0.8, 1) infinite;
    mix-blend-mode: multiply; /* Creates nice mixing effect */
    opacity: 0;
}

.dark .ripple-wave {
    mix-blend-mode: screen;
}

@keyframes ripple {
    0% {
        transform: scale(0);
        opacity: 0.8;
    }
    100% {
        transform: scale(3);
        opacity: 0;
    }
}

/* Transitions */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.fade-scale-enter-active {
    transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275); /* Bouncy enter */
}
.fade-scale-leave-active {
    transition: all 0.3s ease-in;
}

.fade-scale-enter-from {
    opacity: 0;
    transform: scale(0.8);
}
.fade-scale-leave-to {
    opacity: 0;
    transform: scale(1.1);
}
</style>
