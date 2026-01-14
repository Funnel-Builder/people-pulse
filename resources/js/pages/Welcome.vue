<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { buttonVariants } from '@/components/ui/button'
import { cn } from '@/lib/utils'
import { Users, Clock, Calendar, BarChart3, Shield, Building2 } from 'lucide-vue-next'
</script>

<template>
    <Head title="PeoplePulse - HR & Attendance Management" />

    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
        <!-- Navigation -->
        <nav class="container mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-sky-500 to-indigo-600 flex items-center justify-center">
                    <Building2 class="w-5 h-5 text-white" />
                </div>
                <span class="text-xl font-bold text-white">PeoplePulse</span>
            </div>
            <div class="flex items-center gap-4">
                <Link href="/login" class="text-slate-300 hover:text-white transition-colors">
                    Login
                </Link>
                <Link href="/register" :class="cn(buttonVariants(), 'bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-600 hover:to-indigo-700')">
                    Get Started
                </Link>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="container mx-auto px-6 py-20 text-center">
            <h1 class="text-4xl md:text-6xl font-bold text-white mb-6">
                Modern HR & Attendance
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-sky-400 to-indigo-400">
                    Management
                </span>
            </h1>
            <p class="text-xl text-slate-400 max-w-2xl mx-auto mb-12">
                Streamline your workforce management with PeoplePulse. Track attendance, 
                manage leaves, and gain insights - all in one powerful platform.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <Link href="/register" :class="cn(buttonVariants({ size: 'lg' }), 'bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-600 hover:to-indigo-700 px-8')">
                    Register Your Office
                </Link>
                <Link href="/login" :class="cn(buttonVariants({ size: 'lg', variant: 'outline' }), 'border-slate-600 text-slate-300 hover:bg-slate-800 px-8')">
                    Sign In
                </Link>
            </div>
        </section>

        <!-- Features Section -->
        <section class="container mx-auto px-6 py-20">
            <h2 class="text-3xl font-bold text-white text-center mb-12">Everything You Need</h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="p-6 rounded-2xl bg-slate-800/50 border border-slate-700">
                    <div class="w-12 h-12 rounded-xl bg-sky-500/20 flex items-center justify-center mb-4">
                        <Clock class="w-6 h-6 text-sky-400" />
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">Attendance Tracking</h3>
                    <p class="text-slate-400">
                        Real-time clock in/out with automatic late detection and working hours calculation.
                    </p>
                </div>

                <div class="p-6 rounded-2xl bg-slate-800/50 border border-slate-700">
                    <div class="w-12 h-12 rounded-xl bg-indigo-500/20 flex items-center justify-center mb-4">
                        <Calendar class="w-6 h-6 text-indigo-400" />
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">Leave Management</h3>
                    <p class="text-slate-400">
                        Apply for leaves, track balances, and manage approvals with a multi-step workflow.
                    </p>
                </div>

                <div class="p-6 rounded-2xl bg-slate-800/50 border border-slate-700">
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/20 flex items-center justify-center mb-4">
                        <Users class="w-6 h-6 text-emerald-400" />
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">Employee Management</h3>
                    <p class="text-slate-400">
                        Manage your workforce with departments, roles, and comprehensive profiles.
                    </p>
                </div>

                <div class="p-6 rounded-2xl bg-slate-800/50 border border-slate-700">
                    <div class="w-12 h-12 rounded-xl bg-amber-500/20 flex items-center justify-center mb-4">
                        <BarChart3 class="w-6 h-6 text-amber-400" />
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">Insights & Reports</h3>
                    <p class="text-slate-400">
                        Generate detailed reports on attendance patterns, leave usage, and more.
                    </p>
                </div>

                <div class="p-6 rounded-2xl bg-slate-800/50 border border-slate-700">
                    <div class="w-12 h-12 rounded-xl bg-rose-500/20 flex items-center justify-center mb-4">
                        <Shield class="w-6 h-6 text-rose-400" />
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">Data Isolation</h3>
                    <p class="text-slate-400">
                        Each office gets its own isolated database. Your data stays yours, always.
                    </p>
                </div>

                <div class="p-6 rounded-2xl bg-slate-800/50 border border-slate-700">
                    <div class="w-12 h-12 rounded-xl bg-purple-500/20 flex items-center justify-center mb-4">
                        <Building2 class="w-6 h-6 text-purple-400" />
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">Multi-Tenant</h3>
                    <p class="text-slate-400">
                        Perfect for multiple offices or branches. Each operates independently.
                    </p>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="container mx-auto px-6 py-20 text-center">
            <div class="p-12 rounded-3xl bg-gradient-to-r from-sky-500/20 to-indigo-500/20 border border-slate-700">
                <h2 class="text-3xl font-bold text-white mb-4">Ready to Get Started?</h2>
                <p class="text-slate-400 mb-8 max-w-xl mx-auto">
                    Create your office account in minutes and start managing your workforce effectively.
                </p>
                <Link href="/register" :class="cn(buttonVariants({ size: 'lg' }), 'bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-600 hover:to-indigo-700 px-12')">
                    Register Your Office - Free
                </Link>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-slate-800 py-8">
            <div class="container mx-auto px-6 text-center text-slate-500">
                <p>&copy; {{ new Date().getFullYear() }} PeoplePulse. All rights reserved.</p>
            </div>
        </footer>
    </div>
</template>
