<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import InputError from '@/components/InputError.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import { useTranslations } from '@/composables/useTranslations';

const t = useTranslations();

defineProps<{
    email?: string | null;
}>();

const form = useForm({
    username: '',
});

const submit = (): void => {
    form.post('/auth/facebook/username', {
        onFinish: () => {
            // no-op
        },
    });
};
</script>

<template>
    <div class="flex min-h-screen justify-center bg-gray-50 p-4 pt-24 dark:bg-[#0a0a0a]">
        <Head title="Choose a username" />

        <header
            class="fixed top-0 right-0 left-0 z-50 border-b border-gray-100 bg-white/80 backdrop-blur-md dark:border-gray-800 dark:bg-[#0a0a0a]/80"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center gap-2">
                        <AppLogoIcon class="h-9 w-9 shrink-0" />
                        <span class="text-xl font-bold text-gray-900 dark:text-white">
                            Discivo
                        </span>
                    </div>

                    <div class="flex items-center gap-6">
                        <LanguageSwitcher />
                    </div>
                </div>
            </div>
        </header>

        <div
            class="mt-8 w-full max-w-[440px] overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-800 dark:bg-[#121212]"
        >
            <div class="px-8 pb-10">
                <div class="mb-6 pt-10">
                    <h2 class="mb-1.5 text-2xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                        {{ t('Choose a username') }}
                    </h2>
                </div>

                <form class="space-y-5" @submit.prevent="submit">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ t('Username') }}
                        </label>

                        <input
                            v-model="form.username"
                            name="username"
                            type="text"
                            required
                            :placeholder="t('Choose a username')"
                            autocomplete="username"
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-gray-900 shadow-inner transition-all outline-none placeholder:text-gray-400 focus:border-transparent focus:ring-2 focus:ring-primary dark:border-gray-800 dark:bg-gray-900 dark:text-white"
                        />

                        <InputError :message="form.errors.username" />
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3.5 font-semibold text-primary-foreground shadow-md transition-all hover:bg-primary/90 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span v-if="form.processing" class="animate-spin">⏳</span>
                        <span>{{ t('Sign Up') }}</span>
                    </button>
                </form>
            </div>
        </div>

        <div
            class="pointer-events-none fixed top-0 left-0 -z-10 h-full w-full overflow-hidden opacity-20"
        >
            <div class="absolute top-[-10%] right-[-10%] h-[40%] w-[40%] rounded-full bg-primary/20 blur-[120px]" />
            <div class="absolute bottom-[-10%] left-[-10%] h-[40%] w-[40%] rounded-full bg-primary/10 blur-[120px]" />
        </div>
    </div>
</template>

