<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard } from '@/routes';

const t = useTranslations();

type DiscRow = {
    id: number;
    status: 'lost' | 'found';
    occurredAt: string | null;
    manufacturer: string | null;
    modelName: string | null;
    plasticType: string | null;
    backText: string | null;
    conditionEstimate: string | null;
    active: boolean;
    matchLifecycle: string | null;
    colors: string[];
    owner: {
        id: number | null;
        name: string | null;
        username: string | null;
        email: string | null;
    };
    createdAt: string | null;
};

type DiscForMatch = {
    id: number;
    manufacturer: string | null;
    modelName: string | null;
    plasticType: string | null;
    colors: string[];
    owner: DiscRow['owner'];
};

type MatchRow = {
    id: number;
    status: string | null;
    matchScore: number | null;
    createdAt: string | null;
    lostDisc: DiscForMatch;
    foundDisc: DiscForMatch;
};

type Filters = {
    q: string | null;
    status: string | null;
    active: string | null;
    lifecycle: string | null;
    sort: string;
    dir: string;
};

const props = defineProps<{
    filters: Filters;
    discs: {
        data: DiscRow[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        meta?: { total?: number; from?: number | null; to?: number | null };
        total?: number;
        from?: number | null;
        to?: number | null;
    };
    matches: {
        data: MatchRow[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        meta?: { total?: number; from?: number | null; to?: number | null };
        total?: number;
        from?: number | null;
        to?: number | null;
    };
}>();

const breadcrumbs = computed(() => [
    { title: t('My Profile'), href: dashboard().url },
    { title: t('Admin'), href: '/admin/discs' },
]);

const form = reactive({
    q: props.filters.q ?? '',
    status: props.filters.status ?? '',
    active: props.filters.active ?? '',
    lifecycle: props.filters.lifecycle ?? '',
    sort: props.filters.sort ?? 'created_at',
    dir: props.filters.dir ?? 'desc',
});

const pageFrom = computed(() => props.discs.meta?.from ?? props.discs.from ?? 0);
const pageTo = computed(() => props.discs.meta?.to ?? props.discs.to ?? 0);
const pageTotal = computed(() => props.discs.meta?.total ?? props.discs.total ?? 0);

const activeTab = ref<'discs' | 'matches'>(
    (typeof window !== 'undefined' && window.location.search)
        ? (
            new URLSearchParams(window.location.search).get('tab') === 'matches'
                ? 'matches'
                : 'discs'
        )
        : 'discs',
);

const matchesPageFrom = computed(
    () => props.matches.meta?.from ?? props.matches.from ?? 0,
);
const matchesPageTo = computed(
    () => props.matches.meta?.to ?? props.matches.to ?? 0,
);
const matchesPageTotal = computed(
    () => props.matches.meta?.total ?? props.matches.total ?? 0,
);

const pageFromOrMatches = computed(() => (
    activeTab.value === 'discs' ? pageFrom.value : matchesPageFrom.value
));
const pageToOrMatches = computed(() => (
    activeTab.value === 'discs' ? pageTo.value : matchesPageTo.value
));
const pageTotalOrMatches = computed(() => (
    activeTab.value === 'discs' ? pageTotal.value : matchesPageTotal.value
));

type DiscDraft = {
    status: 'lost' | 'found';
    active: '1' | '0';
    matchLifecycle: '' | 'confirmed' | 'handed_over';
};

const editingDiscId = ref<number | null>(null);
const discDrafts = reactive<Record<number, DiscDraft>>({});

type MatchDraft = {
    status: '' | 'confirmed' | 'handed_over' | 'rejected';
};

const editingMatchId = ref<number | null>(null);
const matchDrafts = reactive<Record<number, MatchDraft>>({});

function submitFilters(): void {
    router.get(
        '/admin/discs',
        {
            q: form.q || undefined,
            status: form.status || undefined,
            active: form.active || undefined,
            lifecycle: form.lifecycle || undefined,
            sort: form.sort || undefined,
            dir: form.dir || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
}

function clearFilters(): void {
    form.q = '';
    form.status = '';
    form.active = '';
    form.lifecycle = '';
    form.sort = 'created_at';
    form.dir = 'desc';
    submitFilters();
}

function updateDisc(discId: number, payload: Record<string, unknown>): void {
    if (!window.confirm(t('Are you sure?'))) return;

    router.patch(`/admin/discs/${discId}`, payload as unknown as any, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            if (editingDiscId.value !== discId) return;
            editingDiscId.value = null;
            delete discDrafts[discId];
        },
    });
}

function updateMatch(matchId: number, payload: Record<string, unknown>): void {
    if (!window.confirm(t('Are you sure?'))) return;

    router.patch(`/admin/matches/${matchId}`, payload as unknown as any, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            if (editingMatchId.value !== matchId) return;
            editingMatchId.value = null;
            delete matchDrafts[matchId];
        },
    });
}

function discLabel(disc: { id: number; plasticType: string | null; modelName: string | null; manufacturer: string | null }): string {
    const parts = [disc.plasticType, disc.modelName, disc.manufacturer].filter(Boolean);
    return parts.length ? String(parts.join(' ')) : `#${disc.id}`;
}

function ownerLabel(disc: { owner: DiscRow['owner'] }): string {
    return disc.owner.username || disc.owner.name || disc.owner.email || '—';
}

function startDiscEdit(disc: DiscRow): void {
    editingDiscId.value = disc.id;
    const matchLifecycle = disc.matchLifecycle;

    discDrafts[disc.id] = {
        status: disc.status,
        active: disc.active ? '1' : '0',
        matchLifecycle: matchLifecycle === 'confirmed' || matchLifecycle === 'handed_over'
            ? matchLifecycle
            : '',
    };
}

function cancelDiscEdit(discId: number): void {
    if (editingDiscId.value !== discId) return;
    editingDiscId.value = null;
    delete discDrafts[discId];
}

function saveDiscEdit(disc: DiscRow): void {
    const draft = discDrafts[disc.id];
    if (!draft) return;

    const payload: Record<string, unknown> = {
        status: draft.status,
        active: draft.active === '1',
        matchLifecycle: draft.matchLifecycle || null,
    };

    updateDisc(disc.id, payload);
}

function startMatchEdit(match: MatchRow): void {
    editingMatchId.value = match.id;

    const status = match.status;
    matchDrafts[match.id] = {
        status:
            status === 'confirmed'
                || status === 'handed_over'
                || status === 'rejected'
                ? status
                : '',
    };
}

function cancelMatchEdit(matchId: number): void {
    if (editingMatchId.value !== matchId) return;
    editingMatchId.value = null;
    delete matchDrafts[matchId];
}

function saveMatchEdit(match: MatchRow): void {
    const draft = matchDrafts[match.id];
    if (!draft) return;

    updateMatch(match.id, {
        status: draft.status === '' ? null : draft.status,
    });
}

function matchStatusLabel(status: string | null): string {
    switch (status) {
        case 'confirmed':
            return t('Confirmed');
        case 'handed_over':
            return t('Handed over');
        case 'rejected':
            return t('Rejected');
        default:
            return t('Pending');
    }
}

function matchStatusClass(status: string | null): string {
    switch (status) {
        case 'confirmed':
            return 'bg-yellow-500/15 text-yellow-700 dark:text-yellow-400 border-yellow-500/20';
        case 'handed_over':
            return 'bg-primary/15 text-primary border-primary/20';
        case 'rejected':
            return 'bg-destructive/15 text-destructive border-destructive/20';
        default:
            return 'bg-muted/50 text-muted-foreground border-border';
    }
}

function discStatusPillClass(status: 'lost' | 'found'): string {
    return status === 'lost'
        ? 'bg-destructive/15 text-destructive border-destructive/20'
        : 'bg-primary/15 text-primary border-primary/20';
}

function discActivePillLabel(active: boolean): string {
    return active ? t('Active') : t('Inactive');
}

function discActivePillClass(active: boolean): string {
    return active
        ? 'bg-primary/15 text-primary border-primary/20'
        : 'bg-muted/50 text-muted-foreground border-border';
}

function discLifecyclePillLabel(status: string | null): string {
    return status ? matchStatusLabel(status) : t('None');
}

function discLifecyclePillClass(status: string | null): string {
    return status ? matchStatusClass(status) : 'bg-muted/50 text-muted-foreground border-border';
}

function urlWithTab(
    url: string | null,
    tab: 'discs' | 'matches',
): string | null {
    if (!url) return url;
    if (typeof window === 'undefined') return url;

    const u = new URL(url, window.location.origin);
    u.searchParams.set('tab', tab);

    return `${u.pathname}${u.search}${u.hash}`;
}
</script>

<template>
    <Head
        :title="t('Admin') + ' • ' + (activeTab === 'discs' ? t('All discs') : t('Matches'))"
    />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-6xl px-4 py-8">
            <div class="rounded-xl border border-border bg-muted/20 p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="mt-4">
                            <nav class="flex border-b border-border bg-muted/20" aria-label="Admin tabs">
                                <button
                                    type="button"
                                    class="flex-1 whitespace-nowrap px-4 py-2 text-sm font-bold transition-colors"
                                    :class="
                                        activeTab === 'discs'
                                            ? 'bg-card text-foreground border border-border shadow-sm rounded-t-lg relative z-10 -mb-px'
                                            : 'bg-muted/40 text-muted-foreground border border-transparent hover:bg-muted/50 hover:text-foreground rounded-t-lg'
                                    "
                                    @click="activeTab = 'discs'"
                                >
                                    {{ t('Discs') }}
                                </button>

                                <button
                                    type="button"
                                    class="flex-1 whitespace-nowrap px-4 py-2 text-sm font-bold transition-colors"
                                    :class="
                                        activeTab === 'matches'
                                            ? 'bg-card text-foreground border border-border shadow-sm rounded-t-lg relative z-10 -mb-px'
                                            : 'bg-muted/40 text-muted-foreground border border-transparent hover:bg-muted/50 hover:text-foreground rounded-t-lg'
                                    "
                                    @click="activeTab = 'matches'"
                                >
                                    {{ t('Matches') }}
                                </button>

                                <Link
                                    href="/admin/users"
                                    class="flex-1 whitespace-nowrap px-4 py-2 text-center text-sm font-bold transition-colors bg-muted/40 text-muted-foreground border border-transparent hover:bg-muted/50 hover:text-foreground rounded-t-lg"
                                >
                                    {{ t('Users') }}
                                </Link>
                            </nav>
                        </div>
                        <h1 class="mt-4 text-2xl font-bold text-foreground">
                            {{ activeTab === 'discs' ? t('All discs') : t('Matches') }}
                        </h1>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{
                                activeTab === 'discs'
                                    ? t('Admin can search and edit disc status')
                                    : t('Admin can view match threads and statuses')
                            }}
                        </p>
                    </div>
                    <div class="text-sm text-muted-foreground">
                        {{ pageFromOrMatches }}–{{ pageToOrMatches }}
                        / {{ pageTotalOrMatches }}
                    </div>
                </div>

                <form class="mt-6 grid grid-cols-1 gap-3 md:grid-cols-6" @submit.prevent="submitFilters">
                    <input
                        v-model="form.q"
                        type="text"
                        class="md:col-span-2 h-10 w-full rounded-lg border border-input bg-background px-3 text-sm"
                        :placeholder="t('Search')"
                    />

                    <select
                        v-if="activeTab === 'discs'"
                        v-model="form.status"
                        class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm"
                    >
                        <option value="">{{ t('Status') }}</option>
                        <option value="lost">{{ t('Lost') }}</option>
                        <option value="found">{{ t('Found') }}</option>
                    </select>

                    <select
                        v-if="activeTab === 'discs'"
                        v-model="form.active"
                        class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm"
                    >
                        <option value="">{{ t('Active') }}</option>
                        <option value="1">{{ t('Active') }}</option>
                        <option value="0">{{ t('Inactive') }}</option>
                    </select>

                    <select
                        v-if="activeTab === 'discs'"
                        v-model="form.lifecycle"
                        class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm"
                    >
                        <option value="">{{ t('Match lifecycle') }}</option>
                        <option value="confirmed">{{ t('Confirmed') }}</option>
                        <option value="handed_over">{{ t('Handed over') }}</option>
                    </select>

                    <div class="flex gap-2 md:col-span-1">
                        <button type="submit" class="h-10 flex-1 rounded-lg bg-primary px-4 text-sm font-bold text-primary-foreground hover:opacity-90">
                            {{ t('Search') }}
                        </button>
                        <button type="button" class="h-10 rounded-lg border border-input bg-muted/50 px-4 text-sm font-bold text-foreground hover:bg-muted" @click="clearFilters">
                            {{ t('Clear') }}
                        </button>
                    </div>
                </form>

                <div class="mt-6 overflow-x-auto rounded-xl border border-border bg-card">
                    <table v-if="activeTab === 'discs'" class="w-full text-left text-sm">
                        <thead class="bg-muted/30 text-xs font-bold uppercase tracking-wide text-muted-foreground">
                            <tr>
                                <th class="px-4 py-3">ID</th>
                                <th class="px-4 py-3">{{ t('Disc Name') }}</th>
                                <th class="px-4 py-3">{{ t('Username') }}</th>
                                <th class="px-4 py-3">{{ t('Status') }}</th>
                                <th class="px-4 py-3">{{ t('Active') }}</th>
                                <th class="px-4 py-3">{{ t('Match lifecycle') }}</th>
                                <th class="px-4 py-3">{{ t('Reported') }}</th>
                                <th class="px-4 py-3">{{ t('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr
                                v-for="disc in props.discs.data"
                                :key="disc.id"
                                class="hover:bg-muted/20"
                            >
                                <td class="px-4 py-3 font-mono text-xs text-muted-foreground">
                                    {{ disc.id }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-bold text-foreground">
                                        {{ discLabel(disc) }}
                                    </div>
                                    <div class="mt-1 text-xs text-muted-foreground">
                                        {{ disc.colors.join(', ') || '—' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-bold text-foreground">
                                        {{ ownerLabel(disc) }}
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <template v-if="editingDiscId === disc.id">
                                        <select
                                            class="h-9 w-full rounded-lg border border-input bg-background px-2 text-sm"
                                            v-model="discDrafts[disc.id].status"
                                        >
                                            <option value="lost">{{ t('Lost') }}</option>
                                            <option value="found">{{ t('Found') }}</option>
                                        </select>
                                    </template>
                                    <template v-else>
                                        <span
                                            class="inline-flex h-7 shrink-0 items-center rounded-full border px-3 text-[11px] font-bold"
                                            :class="discStatusPillClass(disc.status)"
                                        >
                                            {{ disc.status === 'lost' ? t('Lost') : t('Found') }}
                                        </span>
                                    </template>
                                </td>

                                <td class="px-4 py-3">
                                    <template v-if="editingDiscId === disc.id">
                                        <select
                                            class="h-9 w-full rounded-lg border border-input bg-background px-2 text-sm"
                                            v-model="discDrafts[disc.id].active"
                                        >
                                            <option value="1">{{ t('Active') }}</option>
                                            <option value="0">{{ t('Inactive') }}</option>
                                        </select>
                                    </template>
                                    <template v-else>
                                        <span
                                            class="inline-flex h-7 shrink-0 items-center rounded-full border px-3 text-[11px] font-bold"
                                            :class="discActivePillClass(disc.active)"
                                        >
                                            {{ discActivePillLabel(disc.active) }}
                                        </span>
                                    </template>
                                </td>

                                <td class="px-4 py-3">
                                    <template v-if="editingDiscId === disc.id">
                                        <select
                                            class="h-9 w-full rounded-lg border border-input bg-background px-2 text-sm"
                                            v-model="discDrafts[disc.id].matchLifecycle"
                                        >
                                            <option value="">{{ t('None') }}</option>
                                            <option value="confirmed">{{ t('Confirmed') }}</option>
                                            <option value="handed_over">{{ t('Handed over') }}</option>
                                        </select>
                                    </template>
                                    <template v-else>
                                        <span
                                            class="inline-flex h-7 shrink-0 items-center rounded-full border px-3 text-[11px] font-bold"
                                            :class="discLifecyclePillClass(disc.matchLifecycle)"
                                        >
                                            {{ discLifecyclePillLabel(disc.matchLifecycle) }}
                                        </span>
                                    </template>
                                </td>

                                <td class="px-4 py-3 text-xs text-muted-foreground">
                                    {{ disc.createdAt ?? '—' }}
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <button
                                            v-if="editingDiscId !== disc.id"
                                            type="button"
                                            class="h-9 rounded-lg border border-input bg-muted/50 px-3 text-sm font-bold text-foreground transition-colors hover:bg-muted"
                                            @click="startDiscEdit(disc)"
                                        >
                                            {{ t('Edit') }}
                                        </button>

                                        <template v-else>
                                            <button
                                                type="button"
                                                class="h-9 rounded-lg bg-primary px-4 text-sm font-bold text-primary-foreground hover:opacity-90"
                                                @click="saveDiscEdit(disc)"
                                            >
                                                {{ t('Save') }}
                                            </button>
                                            <button
                                                type="button"
                                                class="h-9 rounded-lg border border-input bg-muted/50 px-3 text-sm font-bold text-foreground transition-colors hover:bg-muted"
                                                @click="cancelDiscEdit(disc.id)"
                                            >
                                                {{ t('Cancel') }}
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="props.discs.data.length === 0">
                                <td
                                    colspan="8"
                                    class="px-4 py-10 text-center text-sm text-muted-foreground"
                                >
                                    {{ t('No results found.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <table v-else class="w-full text-left text-sm">
                        <thead class="bg-muted/30 text-xs font-bold uppercase tracking-wide text-muted-foreground">
                            <tr>
                                <th class="px-4 py-3">ID</th>
                                <th class="px-4 py-3">{{ t('Lost disc') }}</th>
                                <th class="px-4 py-3">{{ t('Found disc') }}</th>
                                <th class="px-4 py-3">{{ t('Status') }}</th>
                                <th class="px-4 py-3">{{ t('Score') }}</th>
                                <th class="px-4 py-3">{{ t('Created') }}</th>
                                <th class="px-4 py-3">{{ t('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr
                                v-for="match in props.matches.data"
                                :key="match.id"
                                class="hover:bg-muted/20"
                            >
                                <td class="px-4 py-3 font-mono text-xs text-muted-foreground">
                                    {{ match.id }}
                                </td>

                                <td class="px-4 py-3">
                                    <div class="font-bold text-foreground">
                                        {{ discLabel(match.lostDisc) }}
                                    </div>
                                    <div class="mt-1 text-xs text-muted-foreground">
                                        {{ match.lostDisc.colors.join(', ') || '—' }}
                                    </div>
                                    <div class="mt-1 text-xs font-bold text-foreground">
                                        {{ ownerLabel(match.lostDisc) }}
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="font-bold text-foreground">
                                        {{ discLabel(match.foundDisc) }}
                                    </div>
                                    <div class="mt-1 text-xs text-muted-foreground">
                                        {{ match.foundDisc.colors.join(', ') || '—' }}
                                    </div>
                                    <div class="mt-1 text-xs font-bold text-foreground">
                                        {{ ownerLabel(match.foundDisc) }}
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <template v-if="editingMatchId === match.id">
                                        <select
                                            class="h-9 w-full rounded-lg border border-input bg-background px-2 text-sm"
                                            v-model="matchDrafts[match.id].status"
                                        >
                                            <option value="">{{ t('Pending') }}</option>
                                            <option value="confirmed">{{ t('Confirmed') }}</option>
                                            <option value="handed_over">{{ t('Handed over') }}</option>
                                            <option value="rejected">{{ t('Rejected') }}</option>
                                        </select>
                                    </template>

                                    <template v-else>
                                        <span
                                            class="inline-flex h-7 shrink-0 items-center rounded-full border px-3 text-[11px] font-bold"
                                            :class="matchStatusClass(match.status)"
                                        >
                                            {{ matchStatusLabel(match.status) }}
                                        </span>
                                    </template>
                                </td>

                                <td class="px-4 py-3 text-xs text-muted-foreground">
                                    {{ match.matchScore ?? '—' }}
                                </td>

                                <td class="px-4 py-3 text-xs text-muted-foreground">
                                    {{ match.createdAt ?? '—' }}
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <button
                                            v-if="editingMatchId !== match.id"
                                            type="button"
                                            class="h-9 rounded-lg border border-input bg-muted/50 px-3 text-sm font-bold text-foreground transition-colors hover:bg-muted"
                                            @click="startMatchEdit(match)"
                                        >
                                            {{ t('Edit') }}
                                        </button>

                                        <template v-else>
                                            <button
                                                type="button"
                                                class="h-9 rounded-lg bg-primary px-4 text-sm font-bold text-primary-foreground hover:opacity-90"
                                                @click="saveMatchEdit(match)"
                                            >
                                                {{ t('Save') }}
                                            </button>
                                            <button
                                                type="button"
                                                class="h-9 rounded-lg border border-input bg-muted/50 px-3 text-sm font-bold text-foreground transition-colors hover:bg-muted"
                                                @click="cancelMatchEdit(match.id)"
                                            >
                                                {{ t('Cancel') }}
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="props.matches.data.length === 0">
                                <td
                                    colspan="7"
                                    class="px-4 py-10 text-center text-sm text-muted-foreground"
                                >
                                    {{ t('No results found.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-5 flex flex-wrap gap-2">
                    <template v-if="activeTab === 'discs'">
                        <a
                            v-for="link in props.discs.links"
                            :key="link.label"
                            :href="urlWithTab(link.url, 'discs') ?? '#'"
                            class="inline-flex h-9 items-center justify-center rounded-lg border border-input bg-muted/50 px-3 text-sm font-bold text-foreground transition-colors hover:bg-muted"
                            :class="[
                                !link.url ? 'pointer-events-none opacity-50' : '',
                                link.active ? 'bg-primary/10 text-primary border-primary/20' : '',
                            ]"
                            v-html="link.label"
                        />
                    </template>

                    <template v-else>
                        <a
                            v-for="link in props.matches.links"
                            :key="link.label"
                            :href="urlWithTab(link.url, 'matches') ?? '#'"
                            class="inline-flex h-9 items-center justify-center rounded-lg border border-input bg-muted/50 px-3 text-sm font-bold text-foreground transition-colors hover:bg-muted"
                            :class="[
                                !link.url ? 'pointer-events-none opacity-50' : '',
                                link.active ? 'bg-primary/10 text-primary border-primary/20' : '',
                            ]"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

