<script setup lang="ts">
import {
    Activity,
    BadgeCheck,
    BarChart3,
    Bell,
    Building,
    Calendar,
    CheckCircle,
    Clock,
    Coffee,
    Eye,
    History,
    IdCard,
    Mail,
    MapPin,
    MousePointerClick,
    Palmtree,
    Send,
    Settings,
    Shield,
    TrendingUp,
    Users
} from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';

// Scene definitions
const scenes = [
    {
        id: 1,
        title: 'Attendance',
        description: 'One Tap Check-In/Check-Out',
        centralIcon: Clock,
        orbitItems: [
            { icon: MousePointerClick, color: 'text-sky-400', bg: 'bg-sky-500/10', label: 'Clock In/Out', angle: 0, delay: '0s' },
            { icon: TrendingUp, color: 'text-emerald-400', bg: 'bg-emerald-500/10', label: 'Net Hours', angle: 120, delay: '1s' },
            { icon: History, color: 'text-purple-400', bg: 'bg-purple-500/10', label: 'View History', angle: 240, delay: '2s' },
        ],
    },
    {
        id: 2,
        title: 'Leave',
        description: 'Apply, track & manage leaves.',
        centralIcon: Palmtree,
        orbitItems: [
            { icon: Send, color: 'text-sky-400', bg: 'bg-sky-500/10', label: 'Apply Leave', angle: 45, delay: '0.5s' },
            { icon: Calendar, color: 'text-green-400', bg: 'bg-green-500/10', label: 'Calendar', angle: 165, delay: '1.5s' },
            { icon: CheckCircle, color: 'text-orange-400', bg: 'bg-orange-500/10', label: 'Balance', angle: 285, delay: '2.5s' },
        ],
    },
    {
        id: 3,
        title: 'Team Analytics',
        description: 'Real-time insights. Clear reports.',
        centralIcon: BarChart3,
        orbitItems: [
            { icon: Users, color: 'text-indigo-400', bg: 'bg-indigo-500/10', label: 'Team View', angle: 90, delay: '0.8s' },
            { icon: Activity, color: 'text-cyan-400', bg: 'bg-cyan-500/10', label: 'Performance', angle: 210, delay: '1.8s' },
            { icon: Bell, color: 'text-yellow-400', bg: 'bg-yellow-500/10', label: 'Alerts', angle: 330, delay: '2.8s' },
        ],
    },
    {
        id: 4,
        title: 'Employees',
        description: 'Manage your team efficiently.',
        centralIcon: IdCard,
        orbitItems: [
            { icon: Building, color: 'text-slate-400', bg: 'bg-slate-500/10', label: 'Department', angle: 0, delay: '0s' },
            { icon: BadgeCheck, color: 'text-amber-400', bg: 'bg-amber-500/10', label: 'Designation', angle: 120, delay: '1s' },
            { icon: Shield, color: 'text-pink-400', bg: 'bg-pink-500/10', label: 'Roles', angle: 240, delay: '2s' },
        ],
    },
    {
        id: 5,
        title: 'Settings',
        description: 'Configure your workspace.',
        centralIcon: Settings,
        orbitItems: [
            { icon: Clock, color: 'text-sky-400', bg: 'bg-sky-500/10', label: 'Work Hours', angle: 45, delay: '0s' },
            { icon: Palmtree, color: 'text-green-400', bg: 'bg-green-500/10', label: 'Leave Rules', angle: 165, delay: '1s' },
            { icon: Coffee, color: 'text-amber-400', bg: 'bg-amber-500/10', label: 'Break Time', angle: 285, delay: '2s' },
        ],
    },
];

const currentSceneIndex = ref(0);
const timer = ref<number | null>(null);

const startTimer = () => {
    timer.value = window.setInterval(() => {
        currentSceneIndex.value = (currentSceneIndex.value + 1) % scenes.length;
    }, 6000); // Change scene every 6 seconds
};

onMounted(() => {
    startTimer();
});

onUnmounted(() => {
    if (timer.value) clearInterval(timer.value);
});
</script>

<template>
    <div class="relative h-full w-full overflow-hidden bg-zinc-950 text-white">
        <!-- Abstract Background -->
        <div class="absolute inset-0 z-0">
             <div class="absolute -left-1/4 -top-1/4 h-1/2 w-1/2 rounded-full bg-sky-600/20 blur-[100px]" />
             <div class="absolute -bottom-1/4 -right-1/4 h-1/2 w-1/2 rounded-full bg-cyan-600/20 blur-[100px]" />
        </div>

        <div class="relative z-10 flex h-full flex-col items-center justify-center p-8">
            
            <!-- Central Animation Area -->
            <div class="relative flex h-80 w-80 items-center justify-center">
                
                <!-- Rings -->
                <div class="absolute inset-0 rounded-full border border-white/5 opacity-20" />
                <div class="absolute inset-12 rounded-full border border-white/10 opacity-30" />
                <div class="absolute inset-24 rounded-full border border-white/10 opacity-40" />

                <!-- Scene Transition Container -->
                <transition 
                    name="scene"
                    mode="out-in"
                >
                    <!-- Render current scene only -->
                    <div 
                        :key="scenes[currentSceneIndex].id"
                        class="absolute inset-0 flex items-center justify-center"
                    >
                        <!-- Central Icon with Wavy Animation -->
                        <div 
                            class="central-icon-container relative z-10 flex h-32 w-32 items-center justify-center rounded-full bg-gradient-to-br from-sky-500 to-cyan-600 shadow-2xl shadow-sky-500/30"
                        >
                            <component 
                                :is="scenes[currentSceneIndex].centralIcon" 
                                class="h-12 w-12 text-white" 
                            />
                        </div>
                        
                        <!-- Wavy Ripple Effect (Separate layer) -->
                        <div class="ripple-wave absolute h-32 w-32 rounded-full bg-white/20" />

                        <!-- Orbiting Items -->
                        <div 
                            v-for="(item, i) in scenes[currentSceneIndex].orbitItems" 
                            :key="scenes[currentSceneIndex].id + '-' + i"
                            class="absolute z-20"
                            :style="{
                                '--enter-angle': `${item.angle}deg`,
                                '--enter-delay': `${i * 0.1}s`,
                            }"
                        >
                            <!-- The container that gets positioned -->
                            <div 
                                class="orbit-item-wrapper"
                                :style="{ transform: `rotate(${item.angle}deg) translate(140px) rotate(-${item.angle}deg)` }"
                            >
                                <div 
                                    class="flex items-center gap-2 rounded-full border border-white/10 bg-zinc-900/80 px-4 py-2 shadow-xl backdrop-blur-md"
                                    :class="item.color"
                                >
                                    <div :class="['flex items-center justify-center rounded-full p-1', item.bg]">
                                        <component :is="item.icon" class="h-4 w-4" />
                                    </div>
                                    <span class="text-xs font-medium text-white">{{ item.label }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>

            <!-- Text Content -->
             <transition name="fade-up" mode="out-in">
                <div :key="currentSceneIndex" class="mt-12 text-center">
                    <h2 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">
                        {{ scenes[currentSceneIndex].title }}
                    </h2>
                    <p class="mt-4 text-lg text-zinc-400 max-w-xs mx-auto">
                        {{ scenes[currentSceneIndex].description }}
                    </p>
                </div>
            </transition>
        </div>
    </div>
</template>

<style scoped>
/* 
   Central Wavy / Ripple Animation 
   Simulates a fluid expansion like the iPhone charging ripple.
   Runs once when the element enters.
*/
.ripple-wave {
    animation: ripple 1.5s ease-out forwards;
}

@keyframes ripple {
    0% {
        transform: scale(0.8);
        opacity: 0.8;
        box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.3);
    }
    100% {
        transform: scale(2);
        opacity: 0;
        box-shadow: 0 0 0 40px rgba(255, 255, 255, 0);
    }
}

/* GPU acceleration for central icon to prevent jitter */
.central-icon-container {
    will-change: transform, opacity;
    transform: translateZ(0);
    backface-visibility: hidden;
}


/* 
   Scene Transitions (The container for the whole group)
   We keep the container static-ish, but animate children differently.
*/
.scene-enter-active,
.scene-leave-active {
    transition: opacity 1s ease-in-out;
}

.scene-enter-from,
.scene-leave-to {
    opacity: 0;
}

/* 
   Orbit Items Circular Animation 
   On Enter: Rotate along the orbit INDO to position (Spin In)
   On Leave: Rotate along the orbit OUT of position (Spin Out)
   
   "Top comes to bottom" effect implies significant rotation.
   We will rotate them by 120deg during the transition to simulate the ring spinning.
*/
.scene-enter-active .orbit-item-wrapper {
    animation: orbit-in 1s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
}

.scene-leave-active .orbit-item-wrapper {
    animation: orbit-out 0.8s ease-in forwards;
}

@keyframes orbit-in {
    0% {
        opacity: 0;
        /* Start 120deg behind */
        transform: rotate(calc(var(--enter-angle) - 120deg)) translate(140px) rotate(calc(-1 * (var(--enter-angle) - 120deg)));
    }
    100% {
        opacity: 1;
        transform: rotate(var(--enter-angle)) translate(140px) rotate(calc(-1 * var(--enter-angle)));
    }
}

@keyframes orbit-out {
    0% {
        opacity: 1;
        transform: rotate(var(--enter-angle)) translate(140px) rotate(calc(-1 * var(--enter-angle)));
    }
    100% {
        /* No fade: opacity stays 1 */
        opacity: 1;
        /* End 120deg ahead */
        transform: rotate(calc(var(--enter-angle) + 120deg)) translate(140px) rotate(calc(-1 * (var(--enter-angle) + 120deg)));
    }
}

/* Text Transitions */
.fade-up-enter-active,
.fade-up-leave-active {
    transition: all 0.5s ease;
}

.fade-up-enter-from {
    opacity: 0;
    transform: translateY(20px);
}
.fade-up-leave-to {
    opacity: 0;
    transform: translateY(-20px);
}
</style>
