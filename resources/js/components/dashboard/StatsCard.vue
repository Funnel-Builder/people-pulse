<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { LucideIcon } from 'lucide-vue-next';

interface Props {
    title: string;
    value: string | number;
    subtext?: string;
    icon?: any;
    trend?: 'up' | 'down' | 'neutral';
    trendValue?: string;
    class?: string;
    iconClass?: string;
}

defineProps<Props>();
</script>

<template>
    <Card :class="cn('overflow-hidden', $props.class)">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">
                {{ title }}
            </CardTitle>
            <component 
                :is="icon" 
                v-if="icon" 
                :class="cn('h-4 w-4 text-muted-foreground', iconClass)" 
            />
        </CardHeader>
        <CardContent>
            <div class="text-2xl font-bold">{{ value }}</div>
            <p v-if="subtext" class="text-xs text-muted-foreground mt-1">
                {{ subtext }}
            </p>
        </CardContent>
    </Card>
</template>
