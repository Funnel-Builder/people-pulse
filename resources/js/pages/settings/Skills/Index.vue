<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { useForm, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Plus, Pencil, Trash2, CheckCircle, AlertTriangle, MoreHorizontal, FolderPlus } from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Badge } from '@/components/ui/badge';

interface Skill {
    id: number;
    name: string;
    skill_group_id: number;
}

interface SkillGroup {
    id: number;
    name: string;
    skills: Skill[];
}

interface Props {
    skillGroups: SkillGroup[];
}

const props = defineProps<Props>();

// --- Group Management ---
const showGroupDialog = ref(false);
const editingGroup = ref<SkillGroup | null>(null);
const groupForm = useForm({
    name: '',
});

const openCreateGroupDialog = () => {
    editingGroup.value = null;
    groupForm.reset();
    showGroupDialog.value = true;
};

const openEditGroupDialog = (group: SkillGroup) => {
    editingGroup.value = group;
    groupForm.name = group.name;
    showGroupDialog.value = true;
};

const submitGroup = () => {
    if (editingGroup.value) {
        groupForm.put(`/settings/skill-groups/${editingGroup.value.id}`, {
            onSuccess: () => showGroupDialog.value = false,
        });
    } else {
        groupForm.post('/settings/skill-groups', {
            onSuccess: () => showGroupDialog.value = false,
        });
    }
};

// --- Skill Management ---
const showSkillDialog = ref(false);
const editingSkill = ref<Skill | null>(null);
const targetGroupId = ref<number | null>(null);
const skillForm = useForm({
    name: '',
    skill_group_id: null as number | null,
});

const openCreateSkillDialog = (groupId: number) => {
    editingSkill.value = null;
    targetGroupId.value = groupId;
    skillForm.reset();
    skillForm.skill_group_id = groupId;
    showSkillDialog.value = true;
};

const openEditSkillDialog = (skill: Skill, groupId: number) => {
    editingSkill.value = skill;
    targetGroupId.value = groupId;
    skillForm.name = skill.name;
    skillForm.skill_group_id = groupId;
    showSkillDialog.value = true;
};

const submitSkill = () => {
    if (editingSkill.value) {
        skillForm.put(`/settings/skills/${editingSkill.value.id}`, {
            onSuccess: () => showSkillDialog.value = false,
        });
    } else {
        skillForm.post('/settings/skills', {
            onSuccess: () => showSkillDialog.value = false,
        });
    }
};

// --- Deletion (Shared) ---
const showDeleteDialog = ref(false);
const itemToDelete = ref<{ type: 'group' | 'skill'; id: number; name: string } | null>(null);
const deleteForm = useForm({});

const confirmDeleteGroup = (group: SkillGroup) => {
    itemToDelete.value = { type: 'group', id: group.id, name: group.name };
    showDeleteDialog.value = true;
};

const confirmDeleteSkill = (skill: Skill) => {
    itemToDelete.value = { type: 'skill', id: skill.id, name: skill.name };
    showDeleteDialog.value = true;
};

const executeDelete = () => {
    if (!itemToDelete.value) return;

    const url = itemToDelete.value.type === 'group'
        ? `/settings/skill-groups/${itemToDelete.value.id}`
        : `/settings/skills/${itemToDelete.value.id}`;

    deleteForm.delete(url, {
        onSuccess: () => showDeleteDialog.value = false,
    });
};

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Settings', href: '#' },
    { title: 'Skills & Expertise', href: '/settings/skills' },
];
</script>

<template>
    <Head title="Skills & Expertise" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6 max-w-7xl mx-auto w-full">
            <!-- Flash Messages -->
            <div v-if="$page.props.flash.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ $page.props.flash.success }}</span>
            </div>
            <div v-if="$page.props.flash.error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ $page.props.flash.error }}</span>
            </div>

            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Skills & Expertise</h1>
                    <p class="text-muted-foreground">Manage skill categories and individual skills for employees.</p>
                </div>
                <Button @click="openCreateGroupDialog">
                    <FolderPlus class="mr-2 h-4 w-4" />
                    New Skill Group
                </Button>
            </div>

            <!-- Skill Groups Grid -->
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <Card v-for="group in skillGroups" :key="group.id" class="flex flex-col h-full">
                    <CardHeader class="pb-3">
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-lg font-semibold">{{ group.name }}</CardTitle>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="ghost" size="icon" class="h-8 w-8">
                                        <MoreHorizontal class="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem @click="openEditGroupDialog(group)">
                                        <Pencil class="mr-2 h-4 w-4" />
                                        Edit Group
                                    </DropdownMenuItem>
                                    <DropdownMenuItem @click="confirmDeleteGroup(group)" class="text-destructive focus:text-destructive">
                                        <Trash2 class="mr-2 h-4 w-4" />
                                        Delete Group
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                        <CardDescription>
                            {{ group.skills.length }} skills defined
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="flex-1 flex flex-col gap-4">
                        <div v-if="group.skills.length > 0" class="flex flex-wrap gap-2">
                            <Badge
                                v-for="skill in group.skills"
                                :key="skill.id"
                                variant="secondary"
                                class="flex items-center gap-1.5 pr-1.5 py-1"
                            >
                                {{ skill.name }}
                                <div class="flex gap-1 items-center ml-1 border-l pl-1 border-border/50">
                                    <button
                                        @click="openEditSkillDialog(skill, group.id)"
                                        class="text-muted-foreground hover:text-primary transition-colors"
                                    >
                                        <Pencil class="h-3 w-3" />
                                    </button>
                                    <button
                                        @click="confirmDeleteSkill(skill)"
                                        class="text-muted-foreground hover:text-destructive transition-colors"
                                    >
                                        <Trash2 class="h-3 w-3" />
                                    </button>
                                </div>
                            </Badge>
                        </div>
                        <div v-else class="text-sm text-muted-foreground italic py-4 text-center border-2 border-dashed rounded-md">
                            No skills in this group yet.
                        </div>

                        <div class="mt-auto pt-2">
                             <Button
                                variant="outline"
                                size="sm"
                                class="w-full border-dashed"
                                @click="openCreateSkillDialog(group.id)"
                            >
                                <Plus class="mr-2 h-3.5 w-3.5" />
                                Add Skill
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Empty State -->
                <div v-if="skillGroups.length === 0" class="col-span-full flex flex-col items-center justify-center p-12 text-center border-2 border-dashed rounded-lg bg-muted/20">
                    <FolderPlus class="h-12 w-12 text-muted-foreground mb-4" />
                    <h3 class="text-lg font-semibold">No Skill Groups</h3>
                    <p class="text-muted-foreground mb-4 max-w-sm">
                        Create a skill group (e.g., "Frontend", "Backend", "Design") to start organizing employee skills.
                    </p>
                    <Button @click="openCreateGroupDialog">
                        Create First Group
                    </Button>
                </div>
            </div>
        </div>

        <!-- Group Dialog -->
        <Dialog :open="showGroupDialog" @update:open="showGroupDialog = false">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>{{ editingGroup ? 'Edit Skill Group' : 'New Skill Group' }}</DialogTitle>
                    <DialogDescription>
                        Create a category to group related skills (e.g. "Programming Languages", "Certifications").
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitGroup" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="group-name">Group Name</Label>
                        <Input id="group-name" v-model="groupForm.name" placeholder="e.g. Frontend Development" />
                        <p v-if="groupForm.errors.name" class="text-sm text-destructive">{{ groupForm.errors.name }}</p>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="showGroupDialog = false">
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="groupForm.processing">
                            {{ editingGroup ? 'Update Group' : 'Create Group' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Skill Dialog -->
        <Dialog :open="showSkillDialog" @update:open="showSkillDialog = false">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>{{ editingSkill ? 'Edit Skill' : 'Add Skill' }}</DialogTitle>
                    <DialogDescription>
                        Add a specific skill to this group.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitSkill" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="skill-name">Skill Name</Label>
                        <Input id="skill-name" v-model="skillForm.name" placeholder="e.g. React.js" />
                        <p v-if="skillForm.errors.name" class="text-sm text-destructive">{{ skillForm.errors.name }}</p>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="showSkillDialog = false">
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="skillForm.processing">
                            {{ editingSkill ? 'Update Skill' : 'Add Skill' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <AlertDialog :open="showDeleteDialog" @update:open="showDeleteDialog = false">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This will permanently delete the {{ itemToDelete?.type }} <strong>"{{ itemToDelete?.name }}"</strong>.
                        <template v-if="itemToDelete?.type === 'skill'">
                            This skill will be removed from all employees who currently have it.
                        </template>
                        <template v-else>
                            You cannot delete a group that still contains skills.
                        </template>
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        @click="executeDelete"
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                    >
                        Delete
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

    </AppLayout>
</template>
