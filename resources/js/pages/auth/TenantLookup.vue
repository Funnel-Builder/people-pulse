<script setup lang="ts">
import { useForm, Head, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Building2 } from 'lucide-vue-next'

const form = useForm({
    office_id: '',
})

const submit = () => {
    form.post('/login')
}
</script>

<template>
    <Head title="Login - Enter Office ID" />
    
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 p-4">
        <div class="w-full max-w-md">
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
                    <CardTitle class="text-xl text-white">Welcome Back</CardTitle>
                    <CardDescription class="text-slate-400">
                        Enter your Office ID to continue
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="space-y-2">
                            <Label for="office_id" class="text-slate-300">Office ID</Label>
                            <Input
                                id="office_id"
                                v-model="form.office_id"
                                type="text"
                                placeholder="e.g., abc-corp"
                                autocomplete="organization"
                                autofocus
                                class="bg-slate-700/50 border-slate-600 text-white placeholder:text-slate-500 focus:border-sky-500 focus:ring-sky-500"
                                :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': form.errors.office_id }"
                            />
                            <p v-if="form.errors.office_id" class="text-sm text-red-400">
                                {{ form.errors.office_id }}
                            </p>
                        </div>
                        
                        <Button 
                            type="submit" 
                            class="w-full bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-600 hover:to-indigo-700 text-white font-medium"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing">Loading...</span>
                            <span v-else>Continue to Login</span>
                        </Button>
                        
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <span class="w-full border-t border-slate-600" />
                            </div>
                            <div class="relative flex justify-center text-xs uppercase">
                                <span class="bg-slate-800 px-2 text-slate-500">New to PeoplePulse?</span>
                            </div>
                        </div>

                        <Link 
                            href="/register" 
                            class="flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-slate-300 bg-slate-700/50 border border-slate-600 rounded-md hover:bg-slate-700 hover:text-white transition-colors"
                        >
                            Register your office
                        </Link>
                    </form>
                </CardContent>
            </Card>

            <p class="text-center text-sm text-slate-500 mt-6">
                &copy; {{ new Date().getFullYear() }} PeoplePulse. All rights reserved.
            </p>
        </div>
    </div>
</template>
