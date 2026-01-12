<script setup lang="ts">
import { dashboard } from '@/routes';
import { Link, usePage } from '@inertiajs/vue3';
import AuthAnimation from '@/components/auth/AuthAnimation.vue';

const page = usePage();
const name = page.props.name;
const quote = page.props.quote;

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0"
    >
        <div
            class="relative hidden h-full flex-col text-white lg:flex dark:border-r"
        >
            <AuthAnimation />
            <Link
                :href="dashboard()"
                class="absolute left-8 top-8 z-20 flex items-center"
            >
                <img
                    src="/logos/logo-icon.png"
                    alt="PeoplePulse Logo"
                    class="h-10 w-auto"
                />
            </Link>

        </div>
        <div class="lg:p-8">
            <div
                class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]"
            >
                <div class="flex flex-col items-center space-y-2 text-center">
                    <!-- Logo Icon -->
                    <img
                        src="/logos/logo-icon.png"
                        alt="PeoplePulse"
                        class="h-12 w-auto sm:h-14 md:h-16"
                    />
                    <!-- Brand Name -->
                    <h1 class="text-xl sm:text-2xl font-semibold tracking-tight text-foreground">
                        PeoplePulse
                    </h1>
                    <!-- Subtitle -->
                    <p class="text-xs sm:text-sm text-muted-foreground">
                        Sign in with your organizational Email ID
                    </p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
