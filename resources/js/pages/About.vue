<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { useTranslations } from '@/composables/useTranslations';
import { type BreadcrumbItem } from '@/types';

const t = useTranslations();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('About DiscFinder'), href: '/about' },
];
</script>

<template>
    <Head :title="t('About DiscFinder')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col">
            <main class="flex-1 px-4 py-10 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-3xl">
                    <!-- Hero -->
                    <header class="mb-12 text-center">
                        <h1
                            class="text-4xl font-extrabold tracking-tight text-foreground sm:text-5xl"
                        >
                            {{ t('About DiscFinder') }}
                        </h1>
                        <p
                            class="mt-4 text-lg leading-relaxed text-muted-foreground"
                        >
                            {{ t('DiscFinder helps lost disc golf discs find their way back home.') }}
                        </p>
                    </header>

                    <!-- Content -->
                    <div class="space-y-8">
                        <section class="space-y-4">
                            <p
                                class="text-base leading-7 text-foreground sm:text-lg"
                            >
                                {{ t("If you've ever lost your favorite driver in the woods or fished someone else's disc out of a pond, you already know the pain on both sides. DiscFinder connects the people who find discs with the people who lost them — in one simple, searchable place.") }}
                            </p>
                        </section>

                        <section
                            class="rounded-xl border border-border bg-muted/30 p-6 sm:p-8"
                        >
                            <h2
                                class="mb-3 text-lg font-semibold text-foreground"
                            >
                                {{ t('The problem we solve') }}
                            </h2>
                            <p
                                class="text-base leading-7 text-muted-foreground"
                            >
                                {{ t('Today, most found discs end up in Facebook groups. Posts get buried fast, large "found disc" dumps are hard to search, and weeks can pass before the owner even sees them. DiscFinder fixes that.') }}
                            </p>
                        </section>

                        <section class="space-y-4">
                            <h2
                                class="text-lg font-semibold text-foreground"
                            >
                                {{ t('How it works') }}
                            </h2>
                            <p
                                class="text-base leading-7 text-foreground sm:text-lg"
                            >
                                {{ t('With DiscFinder, players can report lost and found discs with detailed information like location, time, brand, color, and any writing on the disc. Our system automatically compares reports and suggests potential matches. When both sides confirm it\'s the right disc, you can connect, arrange a handoff, and close the case.') }}
                            </p>
                        </section>

                        <!-- Closing statement -->
                        <blockquote
                            class="border-l-4 border-primary pl-6 font-medium italic text-foreground"
                        >
                            {{ t("It's all about helping discs — and players — find their way back to each other.") }}
                        </blockquote>
                    </div>
                </div>
            </main>
        </div>
    </AppLayout>
</template>
