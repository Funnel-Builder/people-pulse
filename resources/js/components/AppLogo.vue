<script setup lang="ts">
import { useSidebar } from '@/components/ui/sidebar';
import { computed } from 'vue';

const { state } = useSidebar();
const isCollapsed = computed(() => state.value === 'collapsed');
</script>

<template>
    <div class="flex items-center justify-center">
        <!-- Icon only when collapsed -->
        <template v-if="isCollapsed">
            <img
                src="/logos/logo-icon.png"
                alt="PeoplePulse"
                class="h-8 w-8"
            />
        </template>
        <!-- Full logo when expanded -->
        <template v-else>
            <img
                src="/logos/logo-icon-peoplepulse.png"
                alt="PeoplePulse Logo"
                class="h-10 w-auto block dark:hidden"
            />
            <img
                src="/logos/logo-icon-peoplepulse-dark-mode.png"
                alt="PeoplePulse Logo"
                class="h-10 w-auto hidden dark:block"
            />
        </template>
    </div>
</template>
