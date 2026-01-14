<script setup lang="ts">
import { useForm, Head, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Building2 } from 'lucide-vue-next'
import { computed } from 'vue'

const form = useForm({
    company_name: '',
    office_id: '',
    admin_name: '',
    admin_email: '',
    admin_password: '',
    admin_password_confirmation: '',
})

const previewUrl = computed(() => {
    const id = form.office_id.toLowerCase().replace(/[^a-z0-9-_]/g, '') || 'your-id'
    return `/app/${id}/login`
})

const submit = () => {
    form.post('/register')
}
</script>

<template>
    <Head title="Register Your Office" />
    
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 p-4 py-12">
        <div class="w-full max-w-lg">
            <!-- Logo/Brand -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-sky-500 to-indigo-600 mb-4">
                    <Building2 class="w-8 h-8 text-white" />
                </div>
                <h1 class="text-3xl font-bold text-white">PeoplePulse</h1>
                <p class="text-slate-400 mt-2">HR & Attendance Management</p>
            </div>

            <Card class="border-slate-700 bg-slate-800/50 backdrop-blur-sm">
                <CardHeader class="text-center pb-4">
                    <CardTitle class="text-xl text-white">Register Your Office</CardTitle>
                    <CardDescription class="text-slate-400">
                        Create your PeoplePulse account to get started
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Company Info Section -->
                        <div class="space-y-4">
                            <p class="text-sm font-medium text-sky-400">Office Information</p>
                            
                            <div class="space-y-2">
                                <Label for="company_name" class="text-slate-300">Company/Office Name</Label>
                                <Input
                                    id="company_name"
                                    v-model="form.company_name"
                                    type="text"
                                    placeholder="ABC Corporation"
                                    class="bg-slate-700/50 border-slate-600 text-white placeholder:text-slate-500"
                                    :class="{ 'border-red-500': form.errors.company_name }"
                                />
                                <p v-if="form.errors.company_name" class="text-sm text-red-400">{{ form.errors.company_name }}</p>
                            </div>
                            
                            <div class="space-y-2">
                                <Label for="office_id" class="text-slate-300">Office ID</Label>
                                <Input
                                    id="office_id"
                                    v-model="form.office_id"
                                    type="text"
                                    placeholder="abc-corp"
                                    class="bg-slate-700/50 border-slate-600 text-white placeholder:text-slate-500"
                                    :class="{ 'border-red-500': form.errors.office_id }"
                                />
                                <p class="text-xs text-slate-500">
                                    Your login URL: <span class="text-sky-400">{{ previewUrl }}</span>
                                </p>
                                <p v-if="form.errors.office_id" class="text-sm text-red-400">{{ form.errors.office_id }}</p>
                            </div>
                        </div>

                        <!-- Admin Info Section -->
                        <div class="space-y-4 pt-4 border-t border-slate-700">
                            <p class="text-sm font-medium text-sky-400">Admin Account</p>
                            
                            <div class="space-y-2">
                                <Label for="admin_name" class="text-slate-300">Admin Name</Label>
                                <Input
                                    id="admin_name"
                                    v-model="form.admin_name"
                                    type="text"
                                    placeholder="John Doe"
                                    class="bg-slate-700/50 border-slate-600 text-white placeholder:text-slate-500"
                                    :class="{ 'border-red-500': form.errors.admin_name }"
                                />
                                <p v-if="form.errors.admin_name" class="text-sm text-red-400">{{ form.errors.admin_name }}</p>
                            </div>
                            
                            <div class="space-y-2">
                                <Label for="admin_email" class="text-slate-300">Admin Email</Label>
                                <Input
                                    id="admin_email"
                                    v-model="form.admin_email"
                                    type="email"
                                    placeholder="admin@example.com"
                                    class="bg-slate-700/50 border-slate-600 text-white placeholder:text-slate-500"
                                    :class="{ 'border-red-500': form.errors.admin_email }"
                                />
                                <p v-if="form.errors.admin_email" class="text-sm text-red-400">{{ form.errors.admin_email }}</p>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label for="admin_password" class="text-slate-300">Password</Label>
                                    <Input
                                        id="admin_password"
                                        v-model="form.admin_password"
                                        type="password"
                                        placeholder="••••••••"
                                        class="bg-slate-700/50 border-slate-600 text-white placeholder:text-slate-500"
                                        :class="{ 'border-red-500': form.errors.admin_password }"
                                    />
                                    <p v-if="form.errors.admin_password" class="text-sm text-red-400">{{ form.errors.admin_password }}</p>
                                </div>
                                
                                <div class="space-y-2">
                                    <Label for="admin_password_confirmation" class="text-slate-300">Confirm</Label>
                                    <Input
                                        id="admin_password_confirmation"
                                        v-model="form.admin_password_confirmation"
                                        type="password"
                                        placeholder="••••••••"
                                        class="bg-slate-700/50 border-slate-600 text-white placeholder:text-slate-500"
                                    />
                                </div>
                            </div>
                        </div>
                        
                        <Button 
                            type="submit" 
                            class="w-full bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-600 hover:to-indigo-700 text-white font-medium"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing">Creating your office...</span>
                            <span v-else>Register Office</span>
                        </Button>
                        
                        <p class="text-center text-sm text-slate-400">
                            Already have an account?
                            <Link href="/login" class="text-sky-400 hover:text-sky-300 hover:underline">
                                Login
                            </Link>
                        </p>
                    </form>
                </CardContent>
            </Card>

            <p class="text-center text-sm text-slate-500 mt-6">
                &copy; {{ new Date().getFullYear() }} PeoplePulse. All rights reserved.
            </p>
        </div>
    </div>
</template>
