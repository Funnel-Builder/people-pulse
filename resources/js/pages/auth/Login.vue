<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/login';
import { Form, Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

// Remember me functionality
const STORAGE_KEY = 'peoplepulse_remember_email';
const REMEMBER_KEY = 'peoplepulse_remember_me';

const email = ref('');
const rememberMe = ref(false);

// Load saved credentials on mount
onMounted(() => {
    const savedRemember = localStorage.getItem(REMEMBER_KEY);
    if (savedRemember === 'true') {
        rememberMe.value = true;
        const savedEmail = localStorage.getItem(STORAGE_KEY);
        if (savedEmail) {
            email.value = savedEmail;
        }
    }
});

// Handle remember me checkbox change
const handleRememberChange = (checked: boolean | 'indeterminate') => {
    rememberMe.value = checked === true;
    if (!rememberMe.value) {
        // Clear stored credentials when unchecked
        localStorage.removeItem(STORAGE_KEY);
        localStorage.removeItem(REMEMBER_KEY);
    }
};

// Save credentials before form submit
const handleFormSubmit = () => {
    if (rememberMe.value && email.value) {
        localStorage.setItem(STORAGE_KEY, email.value);
        localStorage.setItem(REMEMBER_KEY, 'true');
    } else {
        localStorage.removeItem(STORAGE_KEY);
        localStorage.removeItem(REMEMBER_KEY);
    }
};
</script>

<template>
    <AuthBase
        title="PeoplePulse"
    >
        <Head title="Log in" />

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
            @submit="handleFormSubmit"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        v-model="email"
                        type="email"
                        name="email"
                        required
                        :autofocus="!email"
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="Password"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center justify-between">
                    <Label for="remember" class="flex items-center space-x-3 cursor-pointer">
                        <Checkbox 
                            id="remember" 
                            name="remember" 
                            :checked="rememberMe" 
                            :tabindex="3"
                            @update:checked="handleRememberChange"
                        />
                        <span>Remember me</span>
                    </Label>
                </div>

                <Button
                    type="submit"
                    class="mt-4 w-full"
                    :tabindex="4"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <Spinner v-if="processing" />
                    Log in
                </Button>
            </div>
        </Form>
    </AuthBase>
</template>
