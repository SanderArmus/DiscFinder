<script setup lang="ts">
import { ref } from 'vue';
import { Form, Head, Link } from '@inertiajs/vue3';
import { ChevronDown, Eye, EyeOff } from 'lucide-vue-next';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import InputError from '@/components/InputError.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import { useTranslations } from '@/composables/useTranslations';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();

const t = useTranslations();
const showPassword = ref(false);
const showAbout = ref(false);
</script>

<template>
    <div
        class="flex min-h-screen justify-center bg-gray-50 p-4 pt-24 dark:bg-[#0a0a0a]"
    >
        <Head title="Log in">
            <link
                rel="stylesheet"
                href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200"
            />
        </Head>

        <header
            class="fixed top-0 right-0 left-0 z-50 border-b border-gray-100 bg-white/80 backdrop-blur-md dark:border-gray-800 dark:bg-[#0a0a0a]/80"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <!-- Logo -->
                    <div class="flex items-center gap-2">
                        <AppLogoIcon class="h-9 w-9 shrink-0" />
                        <span
                            class="text-xl font-bold text-gray-900 dark:text-white"
                        >
                            DiscFinder
                        </span>
                    </div>

                    <div class="flex items-center gap-6">
                        <LanguageSwitcher />
                    </div>
                </div>
            </div>
        </header>

        <!-- Card -->
        <div
            class="mt-8 w-full max-w-[440px] overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-800 dark:bg-[#121212]"
        >
            <div class="px-8 pb-10">
                <div class="mb-6 pt-10">
                    <h2
                        class="mb-1.5 text-2xl font-extrabold tracking-tight text-gray-900 dark:text-white"
                    >
                        {{ t('Log In') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ t('Log in to your account') }}
                    </p>
                </div>

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
                    class="space-y-5"
                >
                    <!-- Email -->
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            {{ t('Email Address') }}
                        </label>
                        <input
                            name="email"
                            type="email"
                            required
                            :placeholder="t('hello@example.com')"
                            autocomplete="email"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-gray-900 shadow-inner transition-all outline-none placeholder:text-gray-400 focus:border-transparent focus:ring-2 focus:ring-primary dark:border-gray-800 dark:bg-gray-900 dark:text-white"
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <!-- Password -->
                    <div>
                        <div class="mb-1.5 flex items-center justify-between">
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >
                                {{ t('Password') }}
                            </label>
                            <Link
                                v-if="canResetPassword"
                                :href="request()"
                                class="text-xs font-medium text-primary hover:underline"
                            >
                                {{ t('Forgot password?') }}
                            </Link>
                        </div>

                        <div class="relative">
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                name="password"
                                required
                                :placeholder="t('Enter your password')"
                                autocomplete="current-password"
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 pr-12 text-gray-900 shadow-inner transition-all outline-none placeholder:text-gray-400 focus:border-transparent focus:ring-2 focus:ring-primary dark:border-gray-800 dark:bg-gray-900 dark:text-white"
                            />

                            <button
                                type="button"
                                class="absolute top-1/2 right-3 -translate-y-1/2 text-[#61896f] transition-colors hover:text-primary"
                                @click="showPassword = !showPassword"
                            >
                                <component
                                    :is="showPassword ? EyeOff : Eye"
                                    class="h-5 w-5"
                                />
                            </button>
                        </div>
                        <InputError :message="errors.password" />
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        :disabled="processing"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3.5 font-semibold text-primary-foreground shadow-md transition-all hover:bg-primary/90 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span v-if="processing" class="animate-spin">⏳</span>
                        {{ t('Log In') }}
                    </button>
                </Form>

                <!-- Divider -->
                <div class="relative my-8">
                    <div class="absolute inset-0 flex items-center">
                        <div
                            class="w-full border-t border-[#dbe6df] dark:border-gray-700"
                        ></div>
                    </div>
                    <div class="relative flex justify-center text-xs uppercase">
                        <span
                            class="bg-white px-3 font-medium text-[#61896f] dark:bg-[#121212]"
                        >
                            {{ t('Or continue with') }}
                        </span>
                    </div>
                </div>

                <!-- Social -->
                <div class="grid grid-cols-2 gap-4">
                    <button
                        type="button"
                        class="flex items-center justify-center gap-2 rounded-xl border border-[#5c7564]/40 bg-[#5c7564]/10 px-4 py-2.5 text-[#5c7564] transition-colors hover:bg-[#6d9472]/20 hover:border-[#6d9472]/50 dark:bg-[#5c7564]/20 dark:text-[#8faf94] dark:hover:bg-[#6d9472]/30"
                    >
                        <span class="text-sm font-medium">{{ t('Google') }}</span>
                    </button>
                    <button
                        type="button"
                        class="flex items-center justify-center gap-2 rounded-xl border border-[#5c7564]/40 bg-[#5c7564]/10 px-4 py-2.5 text-[#5c7564] transition-colors hover:bg-[#6d9472]/20 hover:border-[#6d9472]/50 dark:bg-[#5c7564]/20 dark:text-[#8faf94] dark:hover:bg-[#6d9472]/30"
                    >
                        <span class="text-sm font-medium">{{ t('Facebook') }}</span>
                    </button>
                </div>

                <div class="mt-8 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ t("Don't have an account?") }}
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="font-bold text-primary hover:underline"
                        >
                            {{ t('Sign Up') }}
                        </Link>
                    </p>
                </div>

                <!-- Collapsible: About DiscFinder (inside card) -->
                <div class="mt-8 rounded-xl border border-gray-100 bg-gray-50/80 shadow-inner dark:border-gray-800 dark:bg-gray-800/40">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between px-4 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white"
                        :aria-expanded="showAbout"
                        aria-controls="about-content"
                        @click="showAbout = !showAbout"
                    >
                        <span>{{ t('About DiscFinder') }}</span>
                        <ChevronDown
                            class="h-4 w-4 shrink-0 text-gray-500 transition-transform dark:text-gray-400"
                            :class="showAbout ? 'rotate-180' : ''"
                        />
                    </button>
                    <div
                        id="about-content"
                        :class="showAbout ? 'block' : 'hidden'"
                    >
                        <div class="border-t border-gray-200/80 px-4 pb-4 pt-3 text-sm leading-relaxed text-gray-600 shadow-inner dark:border-gray-700/80 dark:text-gray-400">
                            <p class="mb-3">
                                {{ t("DiscFinder helps lost disc golf discs find their way back home. If you've ever lost your favorite driver in the woods or fished someone else's disc out of a pond, you already know the pain on both sides.") }}
                            </p>
                            <p class="mb-3">
                                {{ t("DiscFinder connects people who find discs with those who lost them — in one simple, searchable place. Report lost or found discs with location, brand, color, and more; we suggest matches so you can connect and arrange a handoff.") }}
                            </p>
                            <p>
                                <Link
                                    href="/about"
                                    class="font-medium text-[#5c7564] hover:underline dark:text-[#6d9472]"
                                >
                                    {{ t('Read full story →') }}
                                </Link>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Background blobs -->
        <div
            class="pointer-events-none fixed top-0 left-0 -z-10 h-full w-full overflow-hidden opacity-20"
        >
            <div
                class="absolute top-[-10%] right-[-10%] h-[40%] w-[40%] rounded-full bg-primary/20 blur-[120px]"
            />
            <div
                class="absolute bottom-[-10%] left-[-10%] h-[40%] w-[40%] rounded-full bg-primary/10 blur-[120px]"
            />
        </div>
    </div>
</template>
