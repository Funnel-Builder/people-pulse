<script setup lang="ts">
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Camera, X, User as UserIcon, Calendar, Lock, Edit3 } from 'lucide-vue-next';

interface UserData {
    id: number;
    employee_id: string;
    name: string;
    email: string;
    profile_picture: string | null;
    department: string | null;
    sub_department: string | null;
    designation: string | null;
    role: string;
    joining_date: string | null;
    nid_number: string | null;
    nationality: string | null;
    fathers_name: string | null;
    mothers_name: string | null;
    graduated_institution: string | null;
    permanent_address: string | null;
    present_address: string | null;
}

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
    userData: UserData;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: edit().url,
    },
];

const page = usePage();
const user = computed(() => page.props.auth.user);

const form = useForm({
    name: props.userData.name,
    profile_picture: null as File | null,
    // Additional info fields (editable)
    nid_number: props.userData.nid_number || '',
    nationality: props.userData.nationality || '',
    fathers_name: props.userData.fathers_name || '',
    mothers_name: props.userData.mothers_name || '',
    graduated_institution: props.userData.graduated_institution || '',
    permanent_address: props.userData.permanent_address || '',
    present_address: props.userData.present_address || '',
});

const previewUrl = ref<string | null>(null);

const currentProfilePicture = computed(() => {
    if (previewUrl.value) return previewUrl.value;
    if (props.userData.profile_picture) return `/storage/${props.userData.profile_picture}`;
    return null;
});

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    
    if (file) {
        form.profile_picture = file;
        previewUrl.value = URL.createObjectURL(file);
    }
};

const removePreview = () => {
    form.profile_picture = null;
    previewUrl.value = null;
    const input = document.getElementById('profile_picture') as HTMLInputElement;
    if (input) input.value = '';
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: 'PATCH',
    })).post('/settings/profile', {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            previewUrl.value = null;
            form.profile_picture = null;
        },
    });
};

const removeProfilePicture = () => {
    if (confirm('Are you sure you want to remove your profile picture?')) {
        form.delete('/settings/profile/picture', {
            preserveScroll: true,
        });
    }
};

const formatDate = (dateStr: string | null) => {
    if (!dateStr) return 'Not set';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatRole = (role: string) => {
    return role.charAt(0).toUpperCase() + role.slice(1);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6 max-w-3xl">
                <HeadingSmall
                    title="Profile information"
                    description="View your profile details and update your additional information"
                />

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Profile Picture & Basic Info Card -->
                    <Card class="border-border/50 shadow-sm">
                        <CardHeader class="pb-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle class="text-lg">Profile Overview</CardTitle>
                                    <CardDescription>Your basic profile information</CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Profile Picture Section -->
                            <div class="flex items-start gap-6">
                                <div class="relative">
                                    <div 
                                        class="h-24 w-24 rounded-full overflow-hidden bg-muted flex items-center justify-center border-2 border-border"
                                    >
                                        <img 
                                            v-if="currentProfilePicture" 
                                            :src="currentProfilePicture" 
                                            alt="Profile picture"
                                            class="h-full w-full object-cover"
                                        />
                                        <UserIcon v-else class="h-12 w-12 text-muted-foreground" />
                                    </div>
                                    <label 
                                        for="profile_picture"
                                        class="absolute bottom-0 right-0 p-1.5 bg-primary text-primary-foreground rounded-full cursor-pointer hover:bg-primary/90 transition-colors"
                                    >
                                        <Camera class="h-4 w-4" />
                                    </label>
                                </div>
                                <div class="flex-1 space-y-3">
                                    <input
                                        id="profile_picture"
                                        type="file"
                                        accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                                        class="hidden"
                                        @change="handleFileChange"
                                    />
                                    <div class="flex gap-2">
                                        <Button 
                                            type="button" 
                                            variant="outline" 
                                            size="sm"
                                            as="label"
                                            for="profile_picture"
                                            class="cursor-pointer"
                                        >
                                            Choose Photo
                                        </Button>
                                        <Button 
                                            v-if="previewUrl" 
                                            type="button" 
                                            variant="ghost" 
                                            size="sm"
                                            @click="removePreview"
                                        >
                                            <X class="h-4 w-4 mr-1" />
                                            Cancel
                                        </Button>
                                        <Button 
                                            v-else-if="userData.profile_picture" 
                                            type="button" 
                                            variant="ghost" 
                                            size="sm"
                                            class="text-destructive hover:text-destructive"
                                            @click="removeProfilePicture"
                                        >
                                            <X class="h-4 w-4 mr-1" />
                                            Remove
                                        </Button>
                                    </div>
                                    <p class="text-xs text-muted-foreground">
                                        JPG, PNG, GIF or WebP. Max 2MB.
                                    </p>
                                </div>
                            </div>
                            <InputError class="mt-2" :message="form.errors.profile_picture" />

                            <Separator />

                            <!-- Basic Info Grid -->
                            <div class="grid gap-4 md:grid-cols-2">
                                <!-- Name (Editable) -->
                                <div class="space-y-2">
                                    <Label for="name" class="flex items-center gap-2">
                                        Name
                                        <Edit3 class="h-3 w-3 text-primary" />
                                    </Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        required
                                        autocomplete="name"
                                        placeholder="Full name"
                                    />
                                    <InputError :message="form.errors.name" />
                                </div>

                                <!-- Employee ID (Read-only) -->
                                <div class="space-y-2">
                                    <Label class="flex items-center gap-2 text-muted-foreground">
                                        Employee ID
                                        <Lock class="h-3 w-3" />
                                    </Label>
                                    <Input
                                        :value="userData.employee_id"
                                        disabled
                                        class="bg-muted/50 cursor-not-allowed"
                                    />
                                </div>

                                <!-- Email (Read-only) -->
                                <div class="space-y-2">
                                    <Label class="flex items-center gap-2 text-muted-foreground">
                                        Email
                                        <Lock class="h-3 w-3" />
                                    </Label>
                                    <Input
                                        :value="userData.email"
                                        type="email"
                                        disabled
                                        class="bg-muted/50 cursor-not-allowed"
                                    />
                                </div>

                                <!-- Joining Date (Read-only) -->
                                <div class="space-y-2">
                                    <Label class="flex items-center gap-2 text-muted-foreground">
                                        <Calendar class="h-3 w-3" />
                                        Joining Date
                                        <Lock class="h-3 w-3" />
                                    </Label>
                                    <Input
                                        :value="formatDate(userData.joining_date)"
                                        disabled
                                        class="bg-muted/50 cursor-not-allowed"
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Additional Information Card (Editable) -->
                    <Card class="border-border/50 shadow-sm border-primary/20">
                        <CardHeader class="pb-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle class="text-lg flex items-center gap-2">
                                        Additional Information
                                        <Edit3 class="h-4 w-4 text-primary" />
                                    </CardTitle>
                                    <CardDescription>Update your personal details as needed</CardDescription>
                                </div>
                                <Badge variant="default" class="text-xs bg-primary/10 text-primary border-primary/20">
                                    <Edit3 class="h-3 w-3 mr-1" />
                                    Editable
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <!-- NID Number -->
                                <div class="space-y-2">
                                    <Label for="nid_number" class="flex items-center gap-1">
                                        NID Number
                                        <span class="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="nid_number"
                                        v-model="form.nid_number"
                                        placeholder="National ID Number"
                                    />
                                    <InputError :message="form.errors.nid_number" />
                                </div>

                                <!-- Nationality -->
                                <div class="space-y-2">
                                    <Label for="nationality">Nationality</Label>
                                    <Input
                                        id="nationality"
                                        v-model="form.nationality"
                                        placeholder="e.g., Bangladeshi"
                                    />
                                    <InputError :message="form.errors.nationality" />
                                </div>

                                <!-- Father's Name -->
                                <div class="space-y-2">
                                    <Label for="fathers_name" class="flex items-center gap-1">
                                        Father's Name
                                        <span class="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="fathers_name"
                                        v-model="form.fathers_name"
                                        placeholder="Father's full name"
                                    />
                                    <InputError :message="form.errors.fathers_name" />
                                </div>

                                <!-- Mother's Name -->
                                <div class="space-y-2">
                                    <Label for="mothers_name" class="flex items-center gap-1">
                                        Mother's Name
                                        <span class="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="mothers_name"
                                        v-model="form.mothers_name"
                                        placeholder="Mother's full name"
                                    />
                                    <InputError :message="form.errors.mothers_name" />
                                </div>

                                <!-- Graduated Institution -->
                                <div class="space-y-2 md:col-span-2">
                                    <Label for="graduated_institution">Graduated Institution</Label>
                                    <Input
                                        id="graduated_institution"
                                        v-model="form.graduated_institution"
                                        placeholder="University/College name"
                                    />
                                    <InputError :message="form.errors.graduated_institution" />
                                </div>

                                <!-- Permanent Address -->
                                <div class="space-y-2 md:col-span-2">
                                    <Label for="permanent_address">Permanent Address</Label>
                                    <textarea
                                        id="permanent_address"
                                        v-model="form.permanent_address"
                                        rows="2"
                                        class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                        placeholder="Permanent address"
                                    ></textarea>
                                    <InputError :message="form.errors.permanent_address" />
                                </div>

                                <!-- Present Address -->
                                <div class="space-y-2 md:col-span-2">
                                    <Label for="present_address">Present Address</Label>
                                    <textarea
                                        id="present_address"
                                        v-model="form.present_address"
                                        rows="2"
                                        class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                        placeholder="Present address"
                                    ></textarea>
                                    <InputError :message="form.errors.present_address" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Email Verification -->
                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="text-sm text-muted-foreground">
                            Your email address is unverified.
                            <Link
                                :href="send()"
                                as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                Click here to resend the verification email.
                            </Link>
                        </p>

                        <div
                            v-if="status === 'verification-link-sent'"
                            class="mt-2 text-sm font-medium text-green-600"
                        >
                            A new verification link has been sent to your email
                            address.
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="form.processing"
                            data-test="update-profile-button"
                            class="px-8"
                        >
                            {{ form.processing ? 'Saving...' : 'Save Changes' }}
                        </Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="form.recentlySuccessful"
                                class="text-sm text-green-600"
                            >
                                Saved successfully.
                            </p>
                        </Transition>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
