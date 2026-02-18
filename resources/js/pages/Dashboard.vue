<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { useTranslations } from '@/composables/useTranslations';
import { type BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';

const t = useTranslations();

interface Disc {
    id: number;
    name: string;
    brand: string;
    color: string;
    status: 'lost' | 'found';
    reportedAt: string;
}

interface Match {
    id: number;
    name: string;
    confidence: number;
    location: string;
    date: string;
}

const props = defineProps<{
    discs: Disc[];
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('My Profile'), href: dashboard().url },
]);

function statusLabel(status: 'lost' | 'found'): string {
    return status === 'lost' ? t('Lost') : t('Found');
}

const matches = ref<Match[]>([
    {
        id: 1,
        name: 'Innova Destroyer',
        confidence: 95,
        location: 'Tallinn - Nõmme Course',
        date: 'Oct 18, 2023',
    },
    {
        id: 2,
        name: 'MVP Glitch',
        confidence: 82,
        location: 'Tartu - Tähtvere',
        date: 'Sep 12, 2023',
    },
]);
</script>

<template>
    <Head title="My Profile" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col overflow-x-auto">
            <div class="flex flex-1 flex-col gap-6 p-4">
                <!-- Page Header -->
                <div
                    class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between"
                >
                    <div>
                        <h2
                            class="mb-2 text-3xl font-black tracking-tight text-foreground"
                        >
                            {{ t('My Profile') }}
                        </h2>
                        <p
                            class="max-w-md text-muted-foreground"
                        >
                            {{ t('Manage your reported equipment and review potential matches found by the community.') }}
                        </p>
                    </div>
                    <div class="flex w-full flex-wrap gap-3 sm:w-auto">
                        <Link
                            href="/lost-discs"
                            class="inline-flex min-w-0 flex-1 items-center justify-center rounded-xl bg-[#5c7564] px-6 py-3 font-bold text-white shadow-md transition-colors hover:bg-[#6d9472] sm:flex-initial"
                        >
                            {{ t('Report Lost Disc') }}
                        </Link>
                        <Link
                            href="/found-discs"
                            class="inline-flex min-w-0 flex-1 items-center justify-center rounded-xl bg-[#5c7564] px-6 py-3 font-bold text-white shadow-md transition-colors hover:bg-[#6d9472] sm:flex-initial"
                        >
                            {{ t('Report Found Disc') }}
                        </Link>
                    </div>
                </div>

            <!-- Dashboard Grid -->
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                <!-- Left: My Reported Discs -->
                <div class="space-y-4 lg:col-span-7">
                    <div class="flex items-center justify-between px-2">
                        <h3 class="text-xl font-bold text-foreground">
                            {{ t('My Reported Discs') }}
                        </h3>
                        <span
                            class="text-xs font-bold uppercase tracking-wider text-muted-foreground"
                        >
                            {{ props.discs.length }} {{ t('Items') }}
                        </span>
                    </div>
                    <div
                        class="overflow-hidden rounded-xl border border-sidebar-border bg-card shadow-sm dark:border-sidebar-border"
                    >
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse text-left">
                                <thead>
                                    <tr
                                        class="border-b border-sidebar-border bg-muted/50 dark:border-sidebar-border"
                                    >
                                        <th
                                            class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-muted-foreground"
                                        >
                                            {{ t('Disc Name') }}
                                        </th>
                                        <th
                                            class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-muted-foreground"
                                        >
                                            {{ t('Plastic / Brand') }}
                                        </th>
                                        <th
                                            class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-muted-foreground"
                                        >
                                            {{ t('Color') }}
                                        </th>
                                        <th
                                            class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-muted-foreground"
                                        >
                                            {{ t('Status') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-sidebar-border dark:divide-sidebar-border"
                                >
                                    <tr
                                        v-if="props.discs.length === 0"
                                        class="text-center text-muted-foreground"
                                    >
                                        <td
                                            colspan="4"
                                            class="px-6 py-12 text-sm"
                                        >
                                            {{ t('No reported discs yet. Report a lost or found disc to get started.') }}
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="disc in props.discs"
                                        :key="disc.id"
                                        class="transition-colors hover:bg-muted/30"
                                    >
                                        <td class="px-6 py-5">
                                            <div
                                                class="font-bold text-foreground"
                                            >
                                                {{ disc.name }}
                                            </div>
                                            <div
                                                class="text-xs text-muted-foreground"
                                            >
                                                {{ t('Reported') }} {{ disc.reportedAt }}
                                            </div>
                                        </td>
                                        <td
                                            class="px-6 py-5 text-sm text-muted-foreground"
                                        >
                                            {{ disc.brand }}
                                        </td>
                                        <td
                                            class="px-6 py-5 text-sm text-muted-foreground"
                                        >
                                            {{ disc.color }}
                                        </td>
                                        <td class="px-6 py-5">
                                            <span
                                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold"
                                                :class="
                                                    disc.status === 'lost'
                                                        ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                                        : 'bg-primary/20 text-foreground dark:text-primary'
                                                "
                                            >
                                                {{ statusLabel(disc.status) }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div
                            class="border-t border-sidebar-border bg-muted/50 p-4 text-center dark:border-sidebar-border"
                        >
                            <button
                                type="button"
                                class="text-sm font-bold text-muted-foreground transition-colors hover:text-primary"
                            >
                                {{ t('View All Activity') }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right: Potential Matches -->
                <div class="space-y-4 lg:col-span-5">
                    <div class="flex items-center justify-between px-2">
                        <h3 class="text-xl font-bold text-foreground">
                            {{ t('Potential Matches') }}
                        </h3>
                        <span
                            class="flex h-2 w-2 animate-pulse rounded-full bg-primary"
                        />
                    </div>
                    <div class="space-y-3">
                        <div
                            v-for="match in matches"
                            :key="match.id"
                            class="rounded-xl border border-sidebar-border bg-card p-5 shadow-sm transition-colors hover:border-primary/50 dark:border-sidebar-border"
                        >
                            <div class="mb-3 flex items-start justify-between">
                                <div>
                                    <h4 class="font-bold text-foreground">
                                        {{ match.name }}
                                    </h4>
                                    <p class="text-xs text-muted-foreground">
                                        {{ t('Matches your lost disc report') }}
                                    </p>
                                </div>
                                <span
                                    class="rounded px-2 py-1 text-xs font-bold"
                                    :class="
                                        match.confidence > 90
                                            ? 'bg-primary/10 text-primary'
                                            : 'bg-muted text-muted-foreground'
                                    "
                                >
                                    {{ match.confidence }}% {{ t('Match') }}
                                </span>
                            </div>
                            <div
                                class="mb-4 grid grid-cols-2 gap-4 text-sm"
                            >
                                <div>
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-tighter text-muted-foreground"
                                    >
                                        {{ t('Location Found') }}
                                    </p>
                                    <p class="text-foreground">
                                        {{ match.location }}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-tighter text-muted-foreground"
                                    >
                                        {{ t('Date Found') }}
                                    </p>
                                    <p class="text-foreground">
                                        {{ match.date }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    class="flex-1 rounded py-2 text-xs font-bold text-muted-foreground transition-colors hover:bg-muted"
                                >
                                    {{ t('Not Mine') }}
                                </button>
                                <button
                                    type="button"
                                    class="flex-1 rounded bg-primary py-2 text-xs font-bold text-primary-foreground transition-opacity hover:opacity-90"
                                >
                                    {{ t('Claim Disc') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </AppLayout>
</template>
