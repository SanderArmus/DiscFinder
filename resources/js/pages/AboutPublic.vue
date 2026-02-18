<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import { useTranslations } from '@/composables/useTranslations';
import { home } from '@/routes';

const t = useTranslations();
</script>

<template>
    <Head :title="t('About DiscFinder')" />
    <div
        class="flex min-h-screen flex-col bg-gray-50 dark:bg-[#0a0a0a]"
    >
        <header
            class="border-b border-gray-100 bg-white/80 backdrop-blur-md dark:border-gray-800 dark:bg-[#0a0a0a]/80"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center gap-2">
                        <AppLogoIcon class="h-9 w-9 shrink-0" />
                        <span
                            class="text-xl font-bold text-gray-900 dark:text-white"
                        >
                            DiscFinder
                        </span>
                    </div>
                    <nav class="flex items-center gap-6">
                        <Link
                            :href="home()"
                            class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                        >
                            {{ t('Log in') }}
                        </Link>
                        <LanguageSwitcher />
                    </nav>
                </div>
            </div>
        </header>

        <main class="flex-1 px-4 py-10 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-3xl">
                <header class="mb-12 text-center">
                    <h1
                        class="text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white sm:text-5xl"
                    >
                        {{ t('About DiscFinder') }}
                    </h1>
                    <p
                        class="mt-4 text-lg leading-relaxed text-gray-600 dark:text-gray-400"
                    >
                        {{ t('DiscFinder helps lost disc golf discs find their way back home.') }}
                    </p>
                </header>

                <div class="space-y-8">
                    <section class="space-y-4">
                        <p
                            class="text-base leading-7 text-gray-700 dark:text-gray-300 sm:text-lg"
                        >
                            {{ t("If you've ever lost your favorite driver in the woods or fished someone else's disc out of a pond, you already know the pain on both sides. DiscFinder connects the people who find discs with the people who lost them — in one simple, searchable place.") }}
                        </p>
                    </section>

                    <section
                        class="rounded-xl border border-gray-200 bg-gray-100/50 p-6 dark:border-gray-800 dark:bg-gray-900/50 sm:p-8"
                    >
                        <h2
                            class="mb-3 text-lg font-semibold text-gray-900 dark:text-white"
                        >
                            {{ t('The problem we solve') }}
                        </h2>
                        <p
                            class="text-base leading-7 text-gray-600 dark:text-gray-400"
                        >
                            {{ t('Today, most found discs end up in Facebook groups. Posts get buried fast, large "found disc" dumps are hard to search, and weeks can pass before the owner even sees them. DiscFinder fixes that.') }}
                        </p>
                    </section>

                    <section class="space-y-4">
                        <h2
                            class="text-lg font-semibold text-gray-900 dark:text-white"
                        >
                            {{ t('How it works') }}
                        </h2>
                        <p
                            class="text-base leading-7 text-gray-700 dark:text-gray-300 sm:text-lg"
                        >
                            {{ t('With DiscFinder, players can report lost and found discs with detailed information like location, time, brand, color, and any writing on the disc. Our system automatically compares reports and suggests potential matches. When both sides confirm it\'s the right disc, you can connect, arrange a handoff, and close the case.') }}
                        </p>
                    </section>

                    <blockquote
                        class="border-l-4 border-[#5c7564] pl-6 font-medium italic text-gray-800 dark:text-gray-200"
                    >
                        {{ t("It's all about helping discs — and players — find their way back to each other.") }}
                    </blockquote>
                </div>

                <p class="mt-12 text-center">
                    <Link
                        :href="home()"
                        class="inline-flex items-center rounded-xl bg-primary px-6 py-3 font-semibold text-primary-foreground transition-colors hover:bg-primary/90"
                    >
                        {{ t('Log in to DiscFinder') }}
                    </Link>
                </p>
            </div>
        </main>
    </div>
</template>
