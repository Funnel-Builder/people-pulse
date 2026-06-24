<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Switch } from '@/components/ui/switch';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Bell } from 'lucide-vue-next';
import { computed, watch } from 'vue';

interface Department {
    id: number;
    name: string;
}

interface SubDepartment {
    id: number;
    name: string;
    department_id: number;
    department?: { id: number; name: string };
}

interface Props {
    departments: Department[];
    subDepartments: SubDepartment[];
    roles: string[];
    types: string[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Announcements', href: '/announcements' },
    { title: 'Create', href: '/announcements/create' },
];

const form = useForm({
    title: '',
    content: '',
    type: 'info',
    target_roles: [] as string[],
    target_departments: [] as number[],
    target_sub_departments: [] as number[],
    starts_at: '',
    expires_at: '',
    is_active: true,
});

// Filter sub-departments based on selected departments
const filteredSubDepartments = computed(() => {
    if (form.target_departments.length === 0) {
        return props.subDepartments;
    }
    return props.subDepartments.filter(sd => 
        form.target_departments.includes(sd.department_id)
    );
});

// Clear sub-departments when departments change
watch(() => form.target_departments, () => {
    form.target_sub_departments = form.target_sub_departments.filter(id => 
        filteredSubDepartments.value.some(sd => sd.id === id)
    );
});

const toggleRole = (role: string) => {
    const index = form.target_roles.indexOf(role);
    if (index === -1) {
        form.target_roles.push(role);
    } else {
        form.target_roles.splice(index, 1);
    }
};

const toggleDepartment = (id: number) => {
    const index = form.target_departments.indexOf(id);
    if (index === -1) {
        form.target_departments.push(id);
    } else {
        form.target_departments.splice(index, 1);
    }
};

const toggleSubDepartment = (id: number) => {
    const index = form.target_sub_departments.indexOf(id);
    if (index === -1) {
        form.target_sub_departments.push(id);
    } else {
        form.target_sub_departments.splice(index, 1);
    }
};

const submit = () => {
    form.post('/announcements');
};

const goBack = () => {
    router.visit('/announcements');
};
</script>

<template>
    <Head title="Create Announcement" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="icon" @click="goBack">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
                <div>
                    <h1 class="text-xl font-semibold flex items-center gap-2">
                        <Bell class="h-5 w-5" />
                        Create Announcement
                    </h1>
                    <p class="text-sm text-muted-foreground">Create a new announcement for your team</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="grid gap-6 lg:grid-cols-3">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Announcement Details</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <Label for="title">Title *</Label>
                                <Input
                                    id="title"
                                    v-model="form.title"
                                    placeholder="Enter announcement title"
                                    :class="{ 'border-destructive': form.errors.title }"
                                />
                                <p v-if="form.errors.title" class="text-sm text-destructive">{{ form.errors.title }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="content">Content *</Label>
                                <Textarea
                                    id="content"
                                    v-model="form.content"
                                    placeholder="Enter announcement content"
                                    rows="6"
                                    :class="{ 'border-destructive': form.errors.content }"
                                />
                                <p v-if="form.errors.content" class="text-sm text-destructive">{{ form.errors.content }}</p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label for="type">Type *</Label>
                                    <Select v-model="form.type">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="info">
                                                <span class="flex items-center gap-2">
                                                    <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                                                    Info
                                                </span>
                                            </SelectItem>
                                            <SelectItem value="warning">
                                                <span class="flex items-center gap-2">
                                                    <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                                    Warning
                                                </span>
                                            </SelectItem>
                                            <SelectItem value="success">
                                                <span class="flex items-center gap-2">
                                                    <span class="h-2 w-2 rounded-full bg-green-500"></span>
                                                    Success
                                                </span>
                                            </SelectItem>
                                            <SelectItem value="event">
                                                <span class="flex items-center gap-2">
                                                    <span class="h-2 w-2 rounded-full bg-purple-500"></span>
                                                    Event
                                                </span>
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="space-y-2 flex items-end">
                                    <div class="flex items-center gap-3">
                                        <Switch id="is_active" :checked="form.is_active" @update:checked="form.is_active = $event" />
                                        <Label for="is_active">Active</Label>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Schedule -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Schedule (Optional)</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label for="starts_at">Starts At</Label>
                                    <Input
                                        id="starts_at"
                                        type="datetime-local"
                                        v-model="form.starts_at"
                                    />
                                    <p class="text-xs text-muted-foreground">Leave empty to start immediately</p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="expires_at">Expires At</Label>
                                    <Input
                                        id="expires_at"
                                        type="datetime-local"
                                        v-model="form.expires_at"
                                    />
                                    <p class="text-xs text-muted-foreground">Leave empty for no expiration</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar - Target Audience -->
                <div class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Target Audience</CardTitle>
                            <p class="text-sm text-muted-foreground">Leave all unchecked to target everyone</p>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Roles -->
                            <div class="space-y-3">
                                <Label class="text-sm font-medium">Roles</Label>
                                <div class="space-y-2">
                                    <div v-for="role in roles" :key="role" class="flex items-center gap-2">
                                        <Checkbox
                                            :id="`role-${role}`"
                                            :checked="form.target_roles.includes(role)"
                                            @update:checked="toggleRole(role)"
                                        />
                                        <Label :for="`role-${role}`" class="text-sm font-normal cursor-pointer">
                                            {{ role.charAt(0).toUpperCase() + role.slice(1) }}
                                        </Label>
                                    </div>
                                </div>
                            </div>

                            <!-- Departments -->
                            <div class="space-y-3">
                                <Label class="text-sm font-medium">Departments</Label>
                                <div class="space-y-2 max-h-48 overflow-y-auto">
                                    <div v-for="dept in departments" :key="dept.id" class="flex items-center gap-2">
                                        <Checkbox
                                            :id="`dept-${dept.id}`"
                                            :checked="form.target_departments.includes(dept.id)"
                                            @update:checked="toggleDepartment(dept.id)"
                                        />
                                        <Label :for="`dept-${dept.id}`" class="text-sm font-normal cursor-pointer">
                                            {{ dept.name }}
                                        </Label>
                                    </div>
                                    <p v-if="departments.length === 0" class="text-sm text-muted-foreground">No departments available</p>
                                </div>
                            </div>

                            <!-- Sub-Departments -->
                            <div class="space-y-3">
                                <Label class="text-sm font-medium">Sub-Departments</Label>
                                <div class="space-y-2 max-h-48 overflow-y-auto">
                                    <div v-for="subDept in filteredSubDepartments" :key="subDept.id" class="flex items-center gap-2">
                                        <Checkbox
                                            :id="`subdept-${subDept.id}`"
                                            :checked="form.target_sub_departments.includes(subDept.id)"
                                            @update:checked="toggleSubDepartment(subDept.id)"
                                        />
                                        <Label :for="`subdept-${subDept.id}`" class="text-sm font-normal cursor-pointer">
                                            {{ subDept.name }}
                                        </Label>
                                    </div>
                                    <p v-if="filteredSubDepartments.length === 0" class="text-sm text-muted-foreground">No sub-departments available</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Submit Button -->
                    <Button type="submit" class="w-full gap-2" :disabled="form.processing">
                        <Save class="h-4 w-4" />
                        {{ form.processing ? 'Creating...' : 'Create Announcement' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
