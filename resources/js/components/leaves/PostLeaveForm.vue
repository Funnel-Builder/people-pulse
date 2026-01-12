<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { MultiSelectCalendar } from '@/components/ui/calendar';
import { CalendarMinus, X } from 'lucide-vue-next';
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

interface LeaveType {
    id: number;
    name: string;
    code: string;
}

interface Props {
    leaveTypes: LeaveType[];
    defaultLeaveType: string;
    showHeader?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showHeader: true,
});

const form = useForm({
    reason: '',
    dates: [] as string[],
    leave_type: props.defaultLeaveType,
});

// Calculate maximum date (yesterday - today should be disabled)
const maxDate = computed(() => {
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);
    return yesterday.toISOString().split('T')[0];
});

const removeDate = (date: string) => {
    form.dates = form.dates.filter(d => d !== date);
};

const formatDate = (dateStr: string) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
};

const submit = () => {
    form.post('/leaves/post', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Card>
        <CardHeader v-if="showHeader" class="text-center">
            <CardTitle class="flex items-center justify-center gap-2">
                <CalendarMinus class="h-5 w-5" />
                Post Leave Application
            </CardTitle>
            <CardDescription>
                Apply for leave for past dates
            </CardDescription>
        </CardHeader>
        <CardContent :class="{ 'pt-6': !showHeader }">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Leave Type -->
                <div class="space-y-2">
                    <Label for="leave_type">
                        Leave Type <span class="text-destructive">*</span>
                    </Label>
                    <Select v-model="form.leave_type" required>
                        <SelectTrigger>
                            <SelectValue placeholder="Select leave type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem 
                                v-for="type in leaveTypes" 
                                :key="type.id" 
                                :value="type.code"
                            >
                                {{ type.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="form.errors.leave_type" class="text-sm text-red-500">
                        {{ form.errors.leave_type }}
                    </p>
                </div>

                <!-- Calendar Date Selection -->
                <div class="space-y-2">
                    <Label>
                        Select Dates <span class="text-destructive">*</span>
                    </Label>
                    <div class="border rounded-lg">
                        <MultiSelectCalendar
                            v-model="form.dates"
                            :max-date="maxDate"
                            class="mx-auto"
                        />
                    </div>
                    
                    <!-- Selected Dates Display -->
                    <div v-if="form.dates.length" class="flex flex-wrap gap-2 mt-3">
                        <Badge 
                            v-for="date in form.dates" 
                            :key="date"
                            class="flex items-center gap-1 py-1"
                        >
                            {{ formatDate(date) }}
                            <button 
                                type="button" 
                                @click="removeDate(date)"
                                class="ml-1 hover:text-destructive"
                            >
                                <X class="h-3 w-3" />
                            </button>
                        </Badge>
                    </div>
                    
                    <p v-if="form.dates.length === 0" class="text-xs text-muted-foreground">
                        Click on dates to select them
                    </p>
                    <p v-if="form.errors.dates" class="text-sm text-red-500">
                        {{ form.errors.dates }}
                    </p>
                </div>

                <!-- Reason -->
                <div class="space-y-2">
                    <Label for="reason">
                        Reason <span class="text-destructive">*</span>
                    </Label>
                    <Textarea
                        id="reason"
                        v-model="form.reason"
                        required
                        rows="3"
                        placeholder="Reason for your leave..."
                    />
                    <p v-if="form.errors.reason" class="text-sm text-red-500">{{ form.errors.reason }}</p>
                </div>

                <Button 
                    type="submit" 
                    :disabled="form.processing || form.dates.length === 0"
                    class="w-full"
                >
                    {{ form.processing ? 'Submitting...' : 'Submit' }}
                </Button>
            </form>
        </CardContent>
    </Card>
</template>
