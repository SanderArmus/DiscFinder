<script setup lang="ts">
import { ref } from 'vue';
import { Form, Head, Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import InputError from '@/components/InputError.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import { useTranslations } from '@/composables/useTranslations';
import { login } from '@/routes';
import { store } from '@/routes/register';

const t = useTranslations();
const showPassword = ref(false);
const showConfirmPassword = ref(false);
</script>

<template>
    <div class="bg-gray-50 dark:bg-[#0a0a0a] min-h-screen flex justify-center p-4 pt-24">
        <Head title="Register">
            <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
        </Head>

        <header
            class="fixed top-0 left-0 right-0 bg-white/80 dark:bg-[#0a0a0a]/80 backdrop-blur-md z-50 border-b border-gray-100 dark:border-gray-800"
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center gap-2">
                        <AppLogoIcon class="w-6 h-6" />
                        <span class="text-xl font-bold text-gray-900 dark:text-white">
                            DiscFinder
                        </span>
                    </div>

                    <div class="flex items-center gap-6">
                        <LanguageSwitcher />
                    </div>
                </div>
            </div>
        </header>

        <!-- Card (same as Login) -->
        <div class="w-full max-w-[440px] bg-white dark:bg-[#121212] shadow-sm rounded-2xl overflow-hidden border border-gray-100 dark:border-gray-800 mt-8">
            <div class="px-8 pb-10">
                <div class="mb-6 pt-10">
                    <h2 class="text-gray-900 dark:text-white text-2xl font-extrabold tracking-tight mb-1.5">
                        {{ t('Create an Account') }}
                    </h2>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">
                        {{ t('Enter your details to join the DiscFinder community.') }}
                    </p>
                </div>

                <Form
                    v-bind="store.form()"
                    :reset-on-success="['password', 'password_confirmation']"
                    v-slot="{ errors, processing }"
                    class="space-y-5"
                >
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ t('Full Name') }}</label>
                        <input
                            name="name"
                            type="text"
                            required
                            autofocus
                            :placeholder="t('Enter your full name')"
                            autocomplete="name"
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all text-gray-900 dark:text-white placeholder:text-gray-400"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ t('Email Address') }}</label>
                        <input
                            name="email"
                            type="email"
                            required
                            :placeholder="t('email@example.com')"
                            autocomplete="email"
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all text-gray-900 dark:text-white placeholder:text-gray-400"
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ t('Password') }}</label>
                        <input
                            name="password"
                            type="password"
                            required
                            :placeholder="t('Create a password')"
                            autocomplete="new-password"
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all text-gray-900 dark:text-white placeholder:text-gray-400"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ t('Confirm Password') }}</label>
                        <input
                            name="password_confirmation"
                            type="password"
                            required
                            :placeholder="t('Re-enter password')"
                            autocomplete="new-password"
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all text-gray-900 dark:text-white placeholder:text-gray-400"
                        />
                        <InputError :message="errors.password_confirmation" />
                    </div>

                    <button
                        type="submit"
                        :disabled="processing"
                        class="w-full bg-[#5c7564] hover:bg-[#6d9472] text-white font-semibold py-3.5 rounded-xl transition-all shadow-md active:scale-[0.99] disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    >
                        <span v-if="processing" class="animate-spin">⏳</span>
                        {{ t('Sign Up') }}
                    </button>
                </Form>

                <!-- Divider (match Login) -->
                <div class="relative my-8">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-[#dbe6df] dark:border-gray-700"></div>
                    </div>
                    <div class="relative flex justify-center text-xs uppercase">
                        <span class="bg-white dark:bg-[#121212] px-3 text-[#61896f] font-medium">
                            {{ t('Or continue with') }}
                        </span>
                    </div>
                </div>

                <!-- Social: Google + Facebook -->
                <div class="grid grid-cols-2 gap-4">
                    <button type="button" class="flex items-center justify-center gap-2 rounded-xl border border-[#5c7564]/40 bg-[#5c7564]/10 px-4 py-2.5 text-[#5c7564] transition-colors hover:bg-[#6d9472]/20 hover:border-[#6d9472]/50 dark:bg-[#5c7564]/20 dark:text-[#8faf94] dark:hover:bg-[#6d9472]/30">
                        <span class="text-sm font-medium">{{ t('Google') }}</span>
                    </button>
                    <button type="button" class="flex items-center justify-center gap-2 rounded-xl border border-[#5c7564]/40 bg-[#5c7564]/10 px-4 py-2.5 text-[#5c7564] transition-colors hover:bg-[#6d9472]/20 hover:border-[#6d9472]/50 dark:bg-[#5c7564]/20 dark:text-[#8faf94] dark:hover:bg-[#6d9472]/30">
                        <span class="text-sm font-medium">{{ t('Facebook') }}</span>
                    </button>
                </div>

                <div class="mt-8 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ t('Already have an account?') }}
                        <Link
                            :href="login()"
                            class="text-primary font-bold hover:underline ml-1"
                        >
                            {{ t('Log In') }}
                        </Link>
                    </p>
                </div>
            </div>
        </div>

        <!-- Background blobs (match Login) -->
        <div class="fixed top-0 left-0 w-full h-full -z-10 pointer-events-none opacity-20 overflow-hidden">
            <div class="absolute top-[-10%] right-[-10%] w-[40%] h-[40%] bg-primary/20 rounded-full blur-[120px]" />
            <div class="absolute bottom-[-10%] left-[-10%] w-[40%] h-[40%] bg-primary/10 rounded-full blur-[120px]" />
        </div>
    </div>
</template>
