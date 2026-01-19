<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle, SheetTrigger, SheetFooter } from '@/components/ui/sheet';
import { Plus, Search, Calendar as CalendarIcon, MoreVertical, Trash2, Edit, RefreshCw, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Switch } from '@/components/ui/switch';
import { Separator } from '@/components/ui/separator';
import type { BreadcrumbItem } from '@/types';

interface Holiday {
    id: number;
    name: string;
    date: string;
    type: 'national' | 'company' | 'optional' | 'religious';
    is_recurring: boolean;
    description?: string;
}

const props = defineProps<{
    holidays: Holiday[];
    year: number;
}>();

const searchQuery = ref('');
const isDrawerOpen = ref(false);
const editingHoliday = ref<Holiday | null>(null);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Office Settings', href: '/settings' },
    { title: 'Holidays', href: '/settings/holidays' },
];

const form = useForm({
    name: '',
    date: '',
    type: 'national',
    is_recurring: false,
    description: '',
});

const filteredHolidays = computed(() => {
    if (!searchQuery.value) return props.holidays;
    const query = searchQuery.value.toLowerCase();
    return props.holidays.filter(holiday => 
        holiday.name.toLowerCase().includes(query) ||
        holiday.type.toLowerCase().includes(query)
    );
});

const groupedHolidays = computed(() => {
    // Group holidays by month
    const groups: Record<string, Holiday[]> = {};
    const sorted = [...filteredHolidays.value].sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime());
    
    sorted.forEach(holiday => {
        const date = new Date(holiday.date);
        const month = date.toLocaleString('default', { month: 'long' });
        if (!groups[month]) {
            groups[month] = [];
        }
        groups[month].push(holiday);
    });
    
    return groups;
});

const getBadgeVariant = (type: string) => {
    switch (type) {
        case 'national': return 'default';
        case 'company': return 'success';
        case 'religious': return 'secondary'; // Using secondary (purple/gray) for religious
        case 'optional': return 'outline';
        default: return 'outline';
    }
};

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { weekday: 'long', day: 'numeric', month: 'long' });
};

const openAddDrawer = () => {
    editingHoliday.value = null;
    form.reset();
    // Set default date to today or current year
    form.date = new Date().toISOString().split('T')[0];
    isDrawerOpen.value = true;
};

const openEditDrawer = (holiday: Holiday) => {
    editingHoliday.value = holiday;
    form.name = holiday.name;
    // Format date to YYYY-MM-DD for input field
    form.date = new Date(holiday.date).toISOString().split('T')[0];
    form.type = holiday.type;
    form.is_recurring = holiday.is_recurring;
    form.description = holiday.description || '';
    isDrawerOpen.value = true;
};

const submitForm = () => {
    if (editingHoliday.value) {
        form.put(`/settings/holidays/${editingHoliday.value.id}`, {
            onSuccess: () => {
                isDrawerOpen.value = false;
            },
        });
    } else {
        form.post('/settings/holidays', {
            onSuccess: () => {
                isDrawerOpen.value = false;
            },
        });
    }
};

const deleteHoliday = (id: number) => {
    if (confirm('Are you sure you want to delete this holiday?')) {
        router.delete(`/settings/holidays/${id}`, {
            onSuccess: () => {},
        });
    }
};

const nextYear = () => {
    router.visit(`/settings/holidays?year=${props.year + 1}`);
};

const prevYear = () => {
    router.visit(`/settings/holidays?year=${props.year - 1}`);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col p-6 md:p-8 space-y-6 max-w-7xl mx-auto w-full">
            
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Holiday Management</h1>
                    <p class="text-muted-foreground">Configure and manage company holidays for {{ year }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="icon" @click="prevYear">
                        <span class="sr-only">Previous Year</span>
                        <ChevronLeft class="h-4 w-4" /> <!-- Need to import ChevronLeft/Right -->
                    </Button>
                    <span class="font-semibold text-lg min-w-[4rem] text-center">{{ year }}</span>
                    <Button variant="outline" size="icon" @click="nextYear">
                        <span class="sr-only">Next Year</span>
                        <ChevronRight class="h-4 w-4" />
                    </Button>
                    <Button @click="openAddDrawer" class="ml-2 gap-2">
                        <Plus class="h-4 w-4" />
                        Add Holiday
                    </Button>
                </div>
            </div>

            <!-- Stats/Filters Chips (Optional enhancement, sticking to minimalist list for now) -->

            <!-- List View -->
            <Card class="flex-1 overflow-hidden border-0 shadow-sm bg-transparent">
                <CardContent class="p-0 space-y-8">
                    <div v-if="filteredHolidays.length === 0" class="flex flex-col items-center justify-center p-12 text-center text-muted-foreground border rounded-lg bg-background">
                        <CalendarIcon class="h-12 w-12 mb-4 opacity-50" />
                        <h3 class="text-lg font-medium">No holidays found for {{ year }}</h3>
                        <p class="mb-4">Get started by adding a new holiday to the calendar.</p>
                        <Button @click="openAddDrawer" variant="outline">Create First Holiday</Button>
                    </div>

                    <div v-for="(holidays, month) in groupedHolidays" :key="month" class="space-y-3">
                        <h3 class="text-sm font-semibold text-muted-foreground uppercase tracking-wider sticky top-0 bg-background/95 backdrop-blur py-2 z-10">
                            {{ month }}
                        </h3>
                        <div class="grid gap-3">
                            <div v-for="holiday in holidays" :key="holiday.id" 
                                class="group flex items-center justify-between p-4 rounded-xl border bg-card hover:shadow-sm transition-all duration-200">
                                <div class="flex items-center gap-4">
                                    <div class="flex flex-col items-center justify-center w-12 h-12 rounded-lg bg-muted/50 text-muted-foreground shrink-0 border">
                                        <span class="text-xs font-medium uppercase">{{ new Date(holiday.date).toLocaleDateString('en-US', { month: 'short' }) }}</span>
                                        <span class="text-lg font-bold text-foreground">{{ new Date(holiday.date).getDate() }}</span>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-semibold text-base">{{ holiday.name }}</h4>
                                            <RefreshCw v-if="holiday.is_recurring" class="h-3 w-3 text-muted-foreground" title="Recurs Annually" />
                                        </div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <Badge :variant="getBadgeVariant(holiday.type)" class="capitalize text-[10px] px-2 py-0 h-5">
                                                {{ holiday.type }}
                                            </Badge>
                                            <span class="text-xs text-muted-foreground" v-if="holiday.description">
                                                {{ holiday.description }}
                                            </span>
                                            <span class="text-xs text-muted-foreground ml-1">
                                                {{ new Date(holiday.date).toLocaleDateString('en-US', { weekday: 'long' }) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <Button variant="ghost" size="icon" @click="openEditDrawer(holiday)">
                                        <Edit class="h-4 w-4 text-muted-foreground" />
                                    </Button>
                                    <Button variant="ghost" size="icon" @click="deleteHoliday(holiday.id)">
                                        <Trash2 class="h-4 w-4 text-destructive" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Side Drawer -->
            <Sheet v-model:open="isDrawerOpen">
                <SheetContent class="sm:max-w-md overflow-y-auto p-6">
                    <SheetHeader>
                        <SheetTitle>{{ editingHoliday ? 'Edit Holiday' : 'Add New Holiday' }}</SheetTitle>
                        <SheetDescription>
                            {{ editingHoliday ? 'Update the details of the holiday.' : 'Fill in the details to add a new holiday to the calendar.' }}
                        </SheetDescription>
                    </SheetHeader>
                    
                    <form @submit.prevent="submitForm" class="space-y-6 mt-6">
                        <div class="space-y-2">
                            <Label for="name">Holiday Name <span class="text-destructive">*</span></Label>
                            <Input id="name" v-model="form.name" placeholder="e.g. Independence Day" required />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="date">Date <span class="text-destructive">*</span></Label>
                                <Input id="date" type="date" v-model="form.date" required />
                            </div>
                            <div class="space-y-2">
                                <Label for="type">Type <span class="text-destructive">*</span></Label>
                                <Select v-model="form.type">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="national">National</SelectItem>
                                        <SelectItem value="company">Company</SelectItem>
                                        <SelectItem value="religious">Religious</SelectItem>
                                        <SelectItem value="optional">Optional</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div class="flex items-center justify-between rounded-lg border p-4">
                            <div class="space-y-0.5">
                                <Label class="text-base">Recur Annually</Label>
                                <p class="text-sm text-muted-foreground">Automatically repeat this holiday every year</p>
                            </div>
                            <Switch v-model:checked="form.is_recurring" />
                        </div>

                        <div class="space-y-2">
                            <Label for="description">Description (Optional)</Label>
                            <Textarea id="description" v-model="form.description" placeholder="Additional details..." rows="3" />
                        </div>

                        <SheetFooter class="pt-4">
                            <!-- <Button type="button" variant="ghost" @click="isDrawerOpen = false">Cancel</Button> -->
                            <Button type="submit" :disabled="form.processing" class="w-full">
                                {{ editingHoliday ? 'Save Changes' : 'Add Holiday' }}
                            </Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>
        </div>
    </AppLayout>
</template>
