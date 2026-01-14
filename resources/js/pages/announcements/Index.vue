<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { type BreadcrumbItem, type Announcement, type PaginatedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Plus, MoreHorizontal, Pencil, Trash2, ToggleLeft, ToggleRight, Info, AlertTriangle, CheckCircle, Calendar, Bell } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    announcements: PaginatedData<Announcement & { created_by: { id: number; name: string } }>;
    filters: {
        status: string;
    };
}

const props = defineProps<Props>();
const page = usePage();

const flash = computed(() => page.props.flash as { success?: string; error?: string });

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Announcements', href: '/announcements' },
];

const statusFilter = ref(props.filters.status || 'all');

watch(statusFilter, (value) => {
    router.get('/announcements', { status: value === 'all' ? '' : value }, {
        preserveState: true,
        preserveScroll: true,
    });
});

const getTypeIcon = (type: string) => {
    switch (type) {
        case 'info': return Info;
        case 'warning': return AlertTriangle;
        case 'success': return CheckCircle;
        case 'event': return Calendar;
        default: return Bell;
    }
};

const getTypeBadgeClass = (type: string) => {
    switch (type) {
        case 'info': return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
        case 'warning': return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400';
        case 'success': return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
        case 'event': return 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400';
    }
};

const toggleAnnouncement = (id: number) => {
    router.post(`/announcements/${id}/toggle`, {}, {
        preserveScroll: true,
    });
};

const deleteAnnouncement = (id: number) => {
    if (confirm('Are you sure you want to delete this announcement?')) {
        router.delete(`/announcements/${id}`, {
            preserveScroll: true,
        });
    }
};

const formatTargetAudience = (announcement: Announcement) => {
    if (!announcement.target_audience) return 'Everyone';
    
    const parts: string[] = [];
    
    if (announcement.target_audience.roles?.length) {
        parts.push(`Roles: ${announcement.target_audience.roles.map(r => r.charAt(0).toUpperCase() + r.slice(1)).join(', ')}`);
    }
    if (announcement.target_audience.department_ids?.length) {
        parts.push(`${announcement.target_audience.department_ids.length} Dept(s)`);
    }
    if (announcement.target_audience.sub_department_ids?.length) {
        parts.push(`${announcement.target_audience.sub_department_ids.length} Sub-Dept(s)`);
    }
    
    return parts.length ? parts.join(' • ') : 'Everyone';
};
</script>

<template>
    <Head title="Announcements" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <!-- Flash Messages -->
            <Alert v-if="flash.success" class="border-green-500 bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800">
                <CheckCircle class="h-4 w-4" />
                <AlertDescription>{{ flash.success }}</AlertDescription>
            </Alert>
            <Alert v-if="flash.error" variant="destructive">
                <AlertTriangle class="h-4 w-4" />
                <AlertDescription>{{ flash.error }}</AlertDescription>
            </Alert>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold flex items-center gap-2">
                        <Bell class="h-5 w-5" />
                        Announcements
                    </h1>
                    <p class="text-sm text-muted-foreground">Manage company-wide announcements</p>
                </div>
                <Link href="/announcements/create">
                    <Button class="gap-2">
                        <Plus class="h-4 w-4" />
                        New Announcement
                    </Button>
                </Link>
            </div>

            <!-- Filters -->
            <Card>
                <CardContent class="p-4">
                    <div class="flex items-center gap-4">
                        <div class="w-48">
                            <Select v-model="statusFilter">
                                <SelectTrigger>
                                    <SelectValue placeholder="Filter by status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Status</SelectItem>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="inactive">Inactive</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Announcements Table -->
            <Card>
                <CardHeader class="pb-3">
                    <CardTitle class="text-sm font-medium text-muted-foreground">
                        {{ announcements.total }} announcement(s)
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-[300px]">Title</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Target Audience</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Created By</TableHead>
                                    <TableHead>Date</TableHead>
                                    <TableHead class="w-[80px]">Action</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="announcement in announcements.data" :key="announcement.id">
                                    <TableCell class="font-medium">
                                        <div class="flex items-center gap-2">
                                            <component :is="getTypeIcon(announcement.type)" class="h-4 w-4 text-muted-foreground flex-shrink-0" />
                                            <span class="truncate max-w-[250px]">{{ announcement.title }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="secondary" :class="getTypeBadgeClass(announcement.type)">
                                            {{ announcement.type.charAt(0).toUpperCase() + announcement.type.slice(1) }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <span class="text-sm text-muted-foreground">{{ formatTargetAudience(announcement) }}</span>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="announcement.is_active ? 'default' : 'secondary'" :class="announcement.is_active ? 'bg-green-500' : ''">
                                            {{ announcement.is_active ? 'Active' : 'Inactive' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <span class="text-sm">{{ announcement.created_by?.name || 'Unknown' }}</span>
                                    </TableCell>
                                    <TableCell>
                                        <span class="text-sm text-muted-foreground">{{ new Date(announcement.created_at).toLocaleDateString() }}</span>
                                    </TableCell>
                                    <TableCell>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="ghost" size="icon" class="h-8 w-8">
                                                    <MoreHorizontal class="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem as-child>
                                                    <Link :href="`/announcements/${announcement.id}/edit`" class="flex items-center gap-2 cursor-pointer">
                                                        <Pencil class="h-4 w-4" />
                                                        Edit
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem @click="toggleAnnouncement(announcement.id)" class="flex items-center gap-2 cursor-pointer">
                                                    <component :is="announcement.is_active ? ToggleLeft : ToggleRight" class="h-4 w-4" />
                                                    {{ announcement.is_active ? 'Deactivate' : 'Activate' }}
                                                </DropdownMenuItem>
                                                <DropdownMenuItem @click="deleteAnnouncement(announcement.id)" class="flex items-center gap-2 cursor-pointer text-destructive focus:text-destructive">
                                                    <Trash2 class="h-4 w-4" />
                                                    Delete
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="announcements.data.length === 0">
                                    <TableCell colspan="7" class="text-center py-8 text-muted-foreground">
                                        No announcements found
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="announcements.last_page > 1" class="flex items-center justify-center gap-2 mt-4">
                        <template v-for="link in announcements.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="px-3 py-1 rounded text-sm"
                                :class="link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'"
                                v-html="link.label"
                            />
                            <span v-else class="px-3 py-1 text-sm text-muted-foreground" v-html="link.label" />
                        </template>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
