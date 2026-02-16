<script setup lang="ts">
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';

interface Disc {
    id: number;
    name: string;
    brand: string;
    color: string;
    status: 'Lost' | 'Found';
    reportedAt: string;
}

interface Match {
    id: number;
    name: string;
    confidence: number;
    location: string;
    date: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'My Profile',
        href: dashboard().url,
    },
];

const discs = ref<Disc[]>([
    {
        id: 1,
        name: 'Innova Destroyer',
        brand: 'Star',
        color: 'Electric Blue',
        status: 'Lost',
        reportedAt: 'Oct 15, 2023',
    },
    {
        id: 2,
        name: 'Discraft Buzzz',
        brand: 'Z Line',
        color: 'Neon Pink',
        status: 'Found',
        reportedAt: 'Sep 22, 2023',
    },
    {
        id: 3,
        name: 'MVP Glitch',
        brand: 'Neutron Soft',
        color: 'White / Black Rim',
        status: 'Lost',
        reportedAt: 'Sep 05, 2023',
    },
]);

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
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
            <!-- Page Header -->
            <div
                class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between"
            >
                <div>
                    <h2
                        class="mb-2 text-3xl font-black tracking-tight text-foreground"
                    >
                        My Profile
                    </h2>
                    <p
                        class="max-w-md text-muted-foreground"
                    >
                        Manage your reported equipment and review potential
                        matches found by the community.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button
                        type="button"
                        class="rounded-lg border-2 border-border px-6 py-3 font-bold text-foreground transition-colors hover:bg-muted"
                    >
                        Report Lost Disc
                    </button>
                    <button
                        type="button"
                        class="rounded-lg bg-primary px-6 py-3 font-bold text-primary-foreground shadow-sm transition-opacity hover:opacity-90"
                    >
                        Report Found Disc
                    </button>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                <!-- Left: My Reported Discs -->
                <div class="space-y-4 lg:col-span-7">
                    <div class="flex items-center justify-between px-2">
                        <h3 class="text-xl font-bold text-foreground">
                            My Reported Discs
                        </h3>
                        <span
                            class="text-xs font-bold uppercase tracking-wider text-muted-foreground"
                        >
                            {{ discs.length }} Items
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
                                            Disc Name
                                        </th>
                                        <th
                                            class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-muted-foreground"
                                        >
                                            Plastic / Brand
                                        </th>
                                        <th
                                            class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-muted-foreground"
                                        >
                                            Color
                                        </th>
                                        <th
                                            class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-muted-foreground"
                                        >
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-sidebar-border dark:divide-sidebar-border"
                                >
                                    <tr
                                        v-for="disc in discs"
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
                                                Reported {{ disc.reportedAt }}
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
                                                    disc.status === 'Lost'
                                                        ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                                        : 'bg-primary/20 text-foreground dark:text-primary'
                                                "
                                            >
                                                {{ disc.status }}
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
                                View All Activity
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right: Potential Matches -->
                <div class="space-y-4 lg:col-span-5">
                    <div class="flex items-center justify-between px-2">
                        <h3 class="text-xl font-bold text-foreground">
                            Potential Matches
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
                                        Matches your lost disc report
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
                                    {{ match.confidence }}% Match
                                </span>
                            </div>
                            <div
                                class="mb-4 grid grid-cols-2 gap-4 text-sm"
                            >
                                <div>
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-tighter text-muted-foreground"
                                    >
                                        Location Found
                                    </p>
                                    <p class="text-foreground">
                                        {{ match.location }}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-tighter text-muted-foreground"
                                    >
                                        Date Found
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
                                    Not Mine
                                </button>
                                <button
                                    type="button"
                                    class="flex-1 rounded bg-primary py-2 text-xs font-bold text-primary-foreground transition-opacity hover:opacity-90"
                                >
                                    Claim Disc
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
