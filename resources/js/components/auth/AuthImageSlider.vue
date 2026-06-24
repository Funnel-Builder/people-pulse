<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';

const images = [
    '/auth-images/uploaded_image_0_1767946730200.png',
    '/auth-images/uploaded_image_1_1767946730200.png',
    '/auth-images/uploaded_image_2_1767946730200.png',
];

const currentIndex = ref(0);
const timer = ref<number | null>(null);

const startTimer = () => {
    timer.value = window.setInterval(() => {
        next();
    }, 5000);
};

const stopTimer = () => {
    if (timer.value) clearInterval(timer.value);
};

const next = () => {
    currentIndex.value = (currentIndex.value + 1) % images.length;
};

onMounted(() => {
    startTimer();
});

onUnmounted(() => {
    stopTimer();
});
</script>

<template>
    <div class="absolute inset-0 overflow-hidden bg-zinc-900">
        <TransitionGroup name="fade-slide">
            <div
                v-for="(image, index) in images"
                :key="image"
                v-show="currentIndex === index"
                class="absolute inset-0 h-full w-full"
            >
                <img
                    :src="image"
                    alt="Authentication Background"
                    class="h-full w-full object-cover transition-transform duration-[10000ms] ease-linear hover:scale-110"
                    style="animation: kenburns 15s infinite alternate;"
                />
                <!-- Subtle overlay to ensure text readability -->
                <div class="absolute inset-0 bg-black/20" />
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
/* Transistions */
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 1s ease-in-out;
}

.fade-slide-enter-from {
    opacity: 0;
    transform: translateX(20px);
}

.fade-slide-leave-to {
    opacity: 0;
    transform: translateX(-20px);
}

.fade-slide-enter-to,
.fade-slide-leave-from {
    opacity: 1;
    transform: translateX(0);
}

/* Ken Burns Effect */
@keyframes kenburns {
    0% {
        transform: scale(1) translate(0, 0);
    }
    100% {
        transform: scale(1.1) translate(-2%, -1%);
    }
}
</style>
