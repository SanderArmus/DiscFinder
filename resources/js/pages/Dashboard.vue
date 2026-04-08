<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { MessageSquare } from 'lucide-vue-next';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

const t = useTranslations();

interface Disc {
    id: number;
    name: string;
    brand: string;
    color: string;
    status: 'lost' | 'found';
    matchLifecycle?: string | null;
    active: boolean;
    reportedAt: string;
}

interface Match {
    id: number;
    name: string;
    confidence: number;
    location: string;
    date: string;
    lostDiscId: number;
    foundDiscId: number;
}

const props = defineProps<{
    discs: Disc[];
    matches: Match[];
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('My Profile'), href: dashboard().url },
]);

const activeDiscs = computed(() => props.discs.filter((disc) => disc.active));

const matchHistoryDiscs = computed(() => props.discs.filter((disc) => !disc.active));

function statusLabel(disc: Disc): string {
    if (disc.matchLifecycle === 'confirmed') {
        return t('Confirmed');
    }

    if (disc.matchLifecycle === 'handed_over') {
        return t('Handed over');
    }

    return disc.status === 'lost' ? t('Lost') : t('Found');
}

</script>

<template>
    <Head title="My Profile" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col overflow-x-hidden sm:overflow-x-auto">
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
                            {{ activeDiscs.length }} {{ t('Items') }}
                        </span>
                    </div>
                    <div
                        class="overflow-hidden rounded-xl border border-sidebar-border bg-card shadow-sm dark:border-sidebar-border"
                    >
                        <div class="overflow-x-hidden">
                            <table class="w-full table-fixed border-collapse text-left">
                                <thead>
                                    <tr
                                        class="border-b border-sidebar-border bg-muted/50 dark:border-sidebar-border"
                                    >
                                        <th class="px-3 sm:px-6 py-4 text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                            {{ t('Disc Name') }}
                                        </th>
                                        <th class="px-3 sm:px-6 py-4 text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                            {{ t('Plastic') }}
                                        </th>
                                        <th class="px-3 sm:px-6 py-4 text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                            {{ t('Color') }}
                                        </th>
                                        <th class="px-3 sm:px-6 py-4 text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                            {{ t('Status') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-sidebar-border dark:divide-sidebar-border"
                                >
                                    <tr v-if="activeDiscs.length === 0" class="text-center text-muted-foreground">
                                        <td
                                            colspan="4"
                                            class="px-3 sm:px-6 py-12 text-sm"
                                        >
                                            {{ t('No active reported discs yet.') }}
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="disc in activeDiscs"
                                        :key="disc.id"
                                        class="transition-colors hover:bg-muted/30"
                                    >
                                        <td class="px-3 sm:px-6 py-5 wrap-break-word">
                                            <Link
                                                :href="`/discs/${disc.id}`"
                                                class="wrap-break-word font-bold text-foreground hover:text-primary"
                                            >
                                                {{ disc.name }}
                                            </Link>
                                            <div
                                                class="text-xs text-muted-foreground"
                                            >
                                                {{ t('Reported') }} {{ disc.reportedAt }}
                                            </div>
                                        </td>
                                        <td
                                            class="px-3 sm:px-6 py-5 text-sm text-muted-foreground wrap-break-word"
                                        >
                                            {{ disc.brand }}
                                        </td>
                                        <td
                                            class="px-3 sm:px-6 py-5 text-sm text-muted-foreground wrap-break-word"
                                        >
                                            {{ disc.color }}
                                        </td>
                                        <td class="px-3 sm:px-6 py-5">
                                            <span
                                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold"
                                                :class="
                                                    disc.matchLifecycle === 'handed_over'
                                                        ? 'bg-muted text-foreground/70 dark:bg-muted/50'
                                                        : disc.matchLifecycle === 'confirmed'
                                                          ? 'bg-primary/20 text-primary dark:text-primary'
                                                          : disc.status === 'lost'
                                                            ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                                            : 'bg-primary/20 text-foreground dark:text-primary'
                                                "
                                            >
                                                {{ statusLabel(disc) }}
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

                    <!-- Match History -->
                    <div v-if="false" class="space-y-4">
                        <div class="flex items-center justify-between px-2">
                            <h3 class="text-xl font-bold text-foreground">
                                {{ t('Match History') }}
                            </h3>
                            <span
                                class="text-xs font-bold uppercase tracking-wider text-muted-foreground"
                            >
                                {{ matchHistoryDiscs.length }} {{ t('Items') }}
                            </span>
                        </div>
                        <div
                            class="overflow-hidden rounded-xl border border-sidebar-border bg-card shadow-sm dark:border-sidebar-border"
                        >
                            <div class="overflow-x-hidden">
                                <table class="w-full table-fixed border-collapse text-left">
                                    <thead>
                                        <tr
                                            class="border-b border-sidebar-border bg-muted/50 dark:border-sidebar-border"
                                        >
                                            <th class="px-3 sm:px-6 py-4 text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                                {{ t('Disc Name') }}
                                            </th>
                                            <th class="px-3 sm:px-6 py-4 text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                                {{ t('Plastic') }}
                                            </th>
                                            <th class="px-3 sm:px-6 py-4 text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                                {{ t('Color') }}
                                            </th>
                                            <th class="px-3 sm:px-6 py-4 text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                                {{ t('Status') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="divide-y divide-sidebar-border dark:divide-sidebar-border"
                                    >
                                        <tr
                                            v-if="matchHistoryDiscs.length === 0"
                                            class="text-center text-muted-foreground"
                                        >
                                            <td colspan="4" class="px-3 sm:px-6 py-12 text-sm">
                                                {{ t('No match history yet.') }}
                                            </td>
                                        </tr>
                                        <tr
                                            v-for="disc in matchHistoryDiscs"
                                            :key="disc.id"
                                            class="transition-colors hover:bg-muted/30"
                                        >
                                            <td class="px-3 sm:px-6 py-5 wrap-break-word">
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
                                            <td class="px-3 sm:px-6 py-5 text-sm text-muted-foreground wrap-break-word">
                                                {{ disc.brand }}
                                            </td>
                                            <td class="px-3 sm:px-6 py-5 text-sm text-muted-foreground wrap-break-word">
                                                {{ disc.color }}
                                            </td>
                                            <td class="px-3 sm:px-6 py-5">
                                                <span
                                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold"
                                                    :class="
                                                        disc.matchLifecycle === 'handed_over'
                                                            ? 'bg-muted text-foreground/70 dark:bg-muted/50'
                                                            : disc.matchLifecycle === 'confirmed'
                                                              ? 'bg-primary/20 text-primary dark:text-primary'
                                                              : disc.status === 'lost'
                                                                ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                                                : 'bg-primary/20 text-foreground dark:text-primary'
                                                    "
                                                >
                                                    {{ statusLabel(disc) }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                <!-- Right: Potential Matches -->
                <div class="space-y-4 lg:col-span-5">
                    <div class="space-y-4">
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
                                v-if="props.matches.length === 0"
                                class="text-center text-sm text-muted-foreground py-8"
                            >
                                {{ t('No potential matches right now. Try reporting another disc.') }}
                            </div>
                            <div
                                v-for="match in props.matches"
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
                                <div class="mb-4 grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-tighter text-muted-foreground">
                                            {{ t('Location Found') }}
                                        </p>
                                        <p class="text-foreground">
                                            {{ match.location }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-tighter text-muted-foreground">
                                            {{ t('Date Found') }}
                                        </p>
                                        <p class="text-foreground">
                                            {{ match.date }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <Link
                                        :href="`/matches/${match.id}`"
                                        class="flex flex-1 items-center justify-center gap-2 rounded bg-primary px-3 py-2 text-xs font-bold text-primary-foreground transition-opacity hover:opacity-90"
                                    >
                                        <MessageSquare class="h-4 w-4" />
                                        {{ t('Leave a message') }}
                                    </Link>
                                    <Link
                                        :href="`/discs/${match.foundDiscId}`"
                                        class="rounded border border-input bg-muted/50 px-3 py-2 text-xs font-bold text-foreground transition-colors hover:bg-muted"
                                    >
                                        {{ t('View disc') }}
                                    </Link>
                                    <Link
                                        :href="`/matches/${match.id}/details`"
                                        class="rounded border border-input bg-muted/50 px-3 py-2 text-xs font-bold text-foreground transition-colors hover:bg-muted"
                                    >
                                        {{ t('Compare') }}
                                    </Link>
                                    <button
                                        type="button"
                                        class="rounded bg-destructive px-3 py-2 text-xs font-bold text-white transition-opacity hover:opacity-90"
                                        @click="
                                            $inertia.post(`/matches/${match.id}/reject`, {}, { preserveScroll: true })
                                        "
                                    >
                                        {{ t('Reject') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
 
                    <!-- Match History -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between px-2">
                            <h3 class="text-xl font-bold text-foreground">
                                {{ t('Match History') }}
                            </h3>
                            <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                {{ matchHistoryDiscs.length }} {{ t('Items') }}
                            </span>
                        </div>
                        <div class="overflow-hidden rounded-xl border border-sidebar-border bg-card shadow-sm dark:border-sidebar-border">
                            <div class="overflow-x-hidden">
                                <table class="w-full table-fixed border-collapse text-left">
                                    <thead>
                                        <tr class="border-b border-sidebar-border bg-muted/50 dark:border-sidebar-border">
                                            <th class="px-3 sm:px-6 py-4 text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                                {{ t('Disc Name') }}
                                            </th>
                                            <th class="px-3 sm:px-6 py-4 text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                                {{ t('Plastic') }}
                                            </th>
                                            <th class="px-3 sm:px-6 py-4 text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                                {{ t('Color') }}
                                            </th>
                                            <th class="px-3 sm:px-6 py-4 text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                                {{ t('Status') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-sidebar-border dark:divide-sidebar-border">
                                        <tr
                                            v-if="matchHistoryDiscs.length === 0"
                                            class="text-center text-muted-foreground"
                                        >
                                            <td colspan="4" class="px-3 sm:px-6 py-12 text-sm">
                                                {{ t('No match history yet.') }}
                                            </td>
                                        </tr>
                                        <tr
                                            v-for="disc in matchHistoryDiscs"
                                            :key="disc.id"
                                            class="transition-colors hover:bg-muted/30"
                                        >
                                            <td class="px-3 sm:px-6 py-5 wrap-break-word">
                                                <div class="font-bold text-foreground">
                                                    {{ disc.name }}
                                                </div>
                                                <div class="text-xs text-muted-foreground">
                                                    {{ t('Reported') }} {{ disc.reportedAt }}
                                                </div>
                                            </td>
                                            <td class="px-3 sm:px-6 py-5 text-sm text-muted-foreground wrap-break-word">
                                                {{ disc.brand }}
                                            </td>
                                            <td class="px-3 sm:px-6 py-5 text-sm text-muted-foreground wrap-break-word">
                                                {{ disc.color }}
                                            </td>
                                            <td class="px-3 sm:px-6 py-5">
                                                <span
                                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold"
                                                    :class="
                                                        disc.matchLifecycle === 'handed_over'
                                                            ? 'bg-muted text-foreground/70 dark:bg-muted/50'
                                                            : disc.matchLifecycle === 'confirmed'
                                                              ? 'bg-primary/20 text-primary dark:text-primary'
                                                              : disc.status === 'lost'
                                                                ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                                                : 'bg-primary/20 text-foreground dark:text-primary'
                                                    "
                                                >
                                                    {{ statusLabel(disc) }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </AppLayout>
</template>
