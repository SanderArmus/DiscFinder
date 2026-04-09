<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard } from '@/routes';

const t = useTranslations();

type Person = {
    id: number | null;
    username: string | null;
    name: string | null;
    email: string | null;
};

type ReportRow = {
    id: number;
    context: 'match' | 'support';
    matchId: number | null;
    reason: string;
    details: string | null;
    lastMessagePreview: string | null;
    lastMessageAt: string | null;
    createdAt: string | null;
    messagesSnapshot?: Array<{
        id: number;
        sender_id: number;
        receiver_id: number;
        created_at: string | null;
        content: string;
    }> | null;
    reporter: Person;
    reported: Person;
};

type Filters = {
    q: string | null;
};

const props = defineProps<{
    filters: Filters;
    reports: {
        data: ReportRow[];
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
    { title: t('Reports'), href: '/admin/chat-reports' },
]);

const form = reactive({
    q: props.filters.q ?? '',
});

const pageFrom = computed(() => props.reports.meta?.from ?? props.reports.from ?? 0);
const pageTo = computed(() => props.reports.meta?.to ?? props.reports.to ?? 0);
const pageTotal = computed(() => props.reports.meta?.total ?? props.reports.total ?? 0);

const selectedReport = ref<ReportRow | null>(null);

function submitFilters(): void {
    router.get(
        '/admin/chat-reports',
        { q: form.q || undefined },
        { preserveState: true, preserveScroll: true },
    );
}

function clearFilters(): void {
    form.q = '';
    submitFilters();
}

function userLabel(p: Person): string {
    return p.username || p.name || p.email || (p.id ? `#${p.id}` : '—');
}

function isReportedMessage(r: ReportRow, senderId: number): boolean {
    return r.reported.id !== null && senderId === r.reported.id;
}

/** Resolve snapshot sender_id to a display name using reporter/reported on the report */
function senderLabel(r: ReportRow, senderId: number): string {
    if (r.reporter.id !== null && senderId === r.reporter.id) {
        return userLabel(r.reporter);
    }
    if (r.reported.id !== null && senderId === r.reported.id) {
        return userLabel(r.reported);
    }

    return `${t('Unknown')} (#${senderId})`;
}

function reasonLabel(reason: string): string {
    const map: Record<string, string> = {
        harassment: t('Harassment'),
        spam: t('Spam'),
        scam: t('Scam'),
        other: t('Other'),
    };

    return map[reason] ?? reason;
}

function openReport(r: ReportRow): void {
    selectedReport.value = r;
}

function onDialogOpenChange(open: boolean): void {
    if (!open) {
        selectedReport.value = null;
    }
}

function previewSnippet(text: string | null, maxLen = 140): string {
    if (!text) {
        return '—';
    }
    const s = text.trim();
    if (s.length <= maxLen) {
        return s;
    }

    return `${s.slice(0, maxLen)}…`;
}

/** Short preview for compact one-line list rows */
function listPreview(text: string | null): string {
    return previewSnippet(text, 72);
}
</script>

<template>
    <Head :title="t('Admin') + ' • ' + t('Reports')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-6xl px-4 py-8">
            <div class="rounded-xl border border-border bg-muted/20 p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="mt-4">
                            <nav class="flex border-b border-border bg-muted/20" aria-label="Admin tabs">
                                <Link
                                    href="/admin/discs?tab=discs"
                                    class="flex-1 whitespace-nowrap px-4 py-2 text-center text-sm font-bold transition-colors bg-muted/40 text-muted-foreground border border-transparent hover:bg-muted/50 hover:text-foreground rounded-t-lg"
                                >
                                    {{ t('Discs') }}
                                </Link>
                                <Link
                                    href="/admin/discs?tab=matches"
                                    class="flex-1 whitespace-nowrap px-4 py-2 text-center text-sm font-bold transition-colors bg-muted/40 text-muted-foreground border border-transparent hover:bg-muted/50 hover:text-foreground rounded-t-lg"
                                >
                                    {{ t('Matches') }}
                                </Link>
                                <Link
                                    href="/admin/users"
                                    class="flex-1 whitespace-nowrap px-4 py-2 text-center text-sm font-bold transition-colors bg-muted/40 text-muted-foreground border border-transparent hover:bg-muted/50 hover:text-foreground rounded-t-lg"
                                >
                                    {{ t('Users') }}
                                </Link>
                                <Link
                                    href="/admin/support-messages"
                                    class="flex-1 whitespace-nowrap px-4 py-2 text-center text-sm font-bold transition-colors bg-muted/40 text-muted-foreground border border-transparent hover:bg-muted/50 hover:text-foreground rounded-t-lg"
                                >
                                    {{ t('Support messages') }}
                                </Link>
                                <Link
                                    href="/admin/chat-reports"
                                    class="flex-1 whitespace-nowrap px-4 py-2 text-center text-sm font-bold transition-colors bg-card text-foreground border border-border shadow-sm rounded-t-lg relative z-10 -mb-px"
                                >
                                    {{ t('Reports') }}
                                </Link>
                            </nav>
                        </div>

                        <h1 class="mt-4 text-2xl font-bold text-foreground">
                            {{ t('Reports') }}
                        </h1>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ t('Reported messages summary') }}
                        </p>
                    </div>

                    <div class="text-sm text-muted-foreground">
                        {{ pageFrom }}–{{ pageTo }} / {{ pageTotal }}
                    </div>
                </div>

                <form class="mt-6 grid grid-cols-1 gap-3 md:grid-cols-6" @submit.prevent="submitFilters">
                    <input
                        v-model="form.q"
                        type="text"
                        class="md:col-span-3 h-10 w-full rounded-lg border border-input bg-background px-3 text-sm"
                        :placeholder="t('Search')"
                    />

                    <div class="flex gap-2 md:col-span-2">
                        <button
                            type="submit"
                            class="h-10 flex-1 rounded-lg bg-primary px-4 text-sm font-bold text-primary-foreground hover:opacity-90"
                        >
                            {{ t('Search') }}
                        </button>
                        <button
                            type="button"
                            class="h-10 rounded-lg border border-input bg-muted/50 px-4 text-sm font-bold text-foreground hover:bg-muted"
                            @click="clearFilters"
                        >
                            {{ t('Clear') }}
                        </button>
                    </div>
                </form>

                <div
                    v-if="props.reports.data.length > 0"
                    class="mt-6 overflow-x-auto rounded-lg border border-border bg-card"
                >
                    <div class="w-full min-w-[560px]">
                    <button
                        v-for="r in props.reports.data"
                        :key="r.id"
                        type="button"
                        class="group flex w-full min-w-0 flex-nowrap items-center gap-2 border-b border-border px-2 py-1.5 text-left text-[11px] leading-snug transition-colors last:border-b-0 hover:bg-muted/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring sm:gap-3 sm:px-3 sm:py-2 sm:text-xs"
                        @click="openReport(r)"
                    >
                        <span class="shrink-0 font-mono text-[10px] text-muted-foreground sm:text-[11px]">
                            #{{ r.id }}
                        </span>
                        <span
                            class="hidden shrink-0 whitespace-nowrap text-muted-foreground sm:inline"
                            :title="r.createdAt || undefined"
                        >
                            {{ r.createdAt || '—' }}
                        </span>
                        <span
                            class="shrink-0 whitespace-nowrap text-muted-foreground sm:hidden"
                            :title="r.createdAt || undefined"
                        >
                            {{ r.createdAt ? r.createdAt.slice(0, 10) : '—' }}
                        </span>
                        <span
                            class="inline-flex max-w-18 shrink-0 truncate rounded bg-muted/70 px-1.5 py-px text-[10px] font-bold uppercase text-muted-foreground sm:max-w-none sm:px-2 sm:text-[11px]"
                        >
                            {{ r.context === 'match' ? t('Match') : t('Support') }}
                        </span>
                        <span v-if="r.matchId" class="shrink-0 font-mono text-[10px] text-muted-foreground">
                            #{{ r.matchId }}
                        </span>
                        <span class="shrink-0 font-semibold text-foreground">
                            {{ reasonLabel(r.reason) }}
                        </span>
                        <span class="shrink-0 text-muted-foreground" aria-hidden="true">·</span>
                        <span class="max-w-[140px] shrink-0 truncate text-muted-foreground sm:max-w-[200px]">
                            {{ userLabel(r.reporter) }}
                            <span class="text-muted-foreground/80">→</span>
                            {{ userLabel(r.reported) }}
                        </span>
                        <span class="shrink-0 text-muted-foreground" aria-hidden="true">·</span>
                        <span
                            class="min-w-0 flex-1 truncate text-muted-foreground"
                            :title="r.lastMessagePreview || undefined"
                        >
                            {{ listPreview(r.lastMessagePreview) }}
                            <span
                                v-if="r.messagesSnapshot?.length"
                                class="ml-1 font-mono text-[10px] text-muted-foreground/80"
                            >
                                ({{ r.messagesSnapshot.length }})
                            </span>
                        </span>
                        <ChevronRight
                            class="h-4 w-4 shrink-0 text-muted-foreground opacity-60 transition group-hover:translate-x-0.5 group-hover:opacity-100"
                            aria-hidden="true"
                        />
                    </button>
                    </div>
                </div>

                <div
                    v-else
                    class="mt-6 rounded-lg border border-dashed border-border bg-muted/20 px-4 py-12 text-center text-sm text-muted-foreground"
                >
                    {{ t('No results found.') }}
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-2">
                    <Link
                        v-for="link in props.reports.links"
                        :key="link.label"
                        :href="link.url || ''"
                        class="rounded-md px-3 py-1.5 text-sm"
                        :class="link.active ? 'bg-primary text-primary-foreground' : 'bg-muted/40 text-foreground'"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <Dialog :open="selectedReport !== null" @update:open="onDialogOpenChange">
            <DialogContent
                v-if="selectedReport"
                class="max-h-[min(90vh,800px)] max-w-2xl gap-0 overflow-hidden p-0 sm:max-w-2xl"
            >
                <DialogHeader class="border-b border-border px-6 py-4 text-left">
                    <DialogTitle class="text-xl">
                        {{ t('Report details') }}
                        <span class="font-mono text-base font-normal text-muted-foreground">
                            #{{ selectedReport.id }}
                        </span>
                    </DialogTitle>
                    <DialogDescription class="text-sm text-muted-foreground">
                        {{ selectedReport.createdAt || '—' }}
                    </DialogDescription>
                </DialogHeader>

                <div class="max-h-[min(70vh,560px)] space-y-5 overflow-y-auto px-6 py-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="inline-flex rounded-full bg-muted/60 px-2.5 py-0.5 text-xs font-bold text-muted-foreground"
                        >
                            {{
                                selectedReport.context === 'match' ? t('Match') : t('Support')
                            }}
                        </span>
                        <span
                            v-if="selectedReport.matchId"
                            class="font-mono text-xs text-muted-foreground"
                        >
                            match #{{ selectedReport.matchId }}
                        </span>
                    </div>

                    <div class="grid gap-3 rounded-lg border border-border bg-muted/20 p-4 text-sm">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">
                                {{ t('Reason') }}
                            </p>
                            <p class="mt-1 font-bold text-foreground">
                                {{ reasonLabel(selectedReport.reason) }}
                            </p>
                        </div>
                        <div v-if="selectedReport.details">
                            <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">
                                {{ t('Reporter notes') }}
                            </p>
                            <p class="mt-1 whitespace-pre-wrap text-foreground/90">
                                {{ selectedReport.details }}
                            </p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">
                                    {{ t('From') }}
                                </p>
                                <p class="mt-1 font-medium text-foreground">
                                    {{ userLabel(selectedReport.reporter) }}
                                </p>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    {{ selectedReport.reporter.email || '—' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">
                                    {{ t('Reported user') }}
                                </p>
                                <p class="mt-1 font-medium text-foreground">
                                    {{ userLabel(selectedReport.reported) }}
                                </p>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    {{ selectedReport.reported.email || '—' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="selectedReport.lastMessagePreview || selectedReport.lastMessageAt"
                        class="rounded-lg border border-border bg-card p-4 text-sm"
                    >
                        <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">
                            {{ t('Message') }}
                        </p>
                        <p class="mt-2 whitespace-pre-wrap text-foreground/90">
                            {{ selectedReport.lastMessagePreview || '—' }}
                        </p>
                        <p v-if="selectedReport.lastMessageAt" class="mt-2 text-xs text-muted-foreground">
                            {{ selectedReport.lastMessageAt }}
                        </p>
                    </div>

                    <div v-if="selectedReport.messagesSnapshot?.length">
                        <p class="mb-3 text-xs font-bold uppercase tracking-wide text-muted-foreground">
                            {{ t('Message thread') }}
                        </p>
                        <div class="space-y-2">
                            <div
                                v-for="m in selectedReport.messagesSnapshot"
                                :key="m.id"
                                class="rounded-lg border border-border bg-muted/30 p-3"
                            >
                                <div class="flex items-center justify-between gap-2 text-xs text-muted-foreground">
                                    <span>{{ m.created_at || '—' }}</span>
                                    <span
                                        class="max-w-[55%] truncate text-right font-semibold"
                                        :class="
                                            isReportedMessage(selectedReport, m.sender_id)
                                                ? 'text-destructive'
                                                : 'text-foreground'
                                        "
                                        :title="senderLabel(selectedReport, m.sender_id)"
                                    >
                                        {{ senderLabel(selectedReport, m.sender_id) }}
                                    </span>
                                </div>
                                <p class="mt-2 whitespace-pre-wrap text-sm text-foreground/90">
                                    {{ m.content }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 pt-1">
                        <Button v-if="selectedReport.context === 'match' && selectedReport.matchId" as-child variant="outline">
                            <Link :href="`/matches/${selectedReport.matchId}`">
                                {{ t('Open match chat') }}
                            </Link>
                        </Button>
                        <Button v-if="selectedReport.context === 'support'" as-child variant="outline">
                            <Link href="/support/chat">
                                {{ t('Open support chat') }}
                            </Link>
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
