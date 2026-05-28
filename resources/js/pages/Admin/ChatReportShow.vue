<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';

const t = useTranslations();

type Person = {
    id: number | null;
    username: string | null;
    name: string | null;
    email: string | null;
};

type Report = {
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
    messages: Array<{
        id: number;
        senderId: number;
        receiverId: number;
        createdAt: string | null;
        content: string;
    }>;
    reporter: Person;
    reported: Person;
};

const props = defineProps<{
    report: Report;
}>();

const breadcrumbs = computed(() => [
    { title: t('My Profile'), href: dashboard().url },
    { title: t('Admin'), href: '/admin/discs' },
    { title: t('Reports'), href: '/admin/chat-reports' },
    { title: t('Report details'), href: `/admin/chat-reports/${props.report.id}` },
]);

function userLabel(p: Person): string {
    return p.username || p.name || p.email || (p.id ? `#${p.id}` : '—');
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

function senderLabel(senderId: number): string {
    if (props.report.reporter.id !== null && senderId === props.report.reporter.id) {
        return userLabel(props.report.reporter);
    }
    if (props.report.reported.id !== null && senderId === props.report.reported.id) {
        return userLabel(props.report.reported);
    }

    return `${t('Unknown')} (#${senderId})`;
}

function isReportedSender(senderId: number): boolean {
    return props.report.reported.id !== null && senderId === props.report.reported.id;
}
</script>

<template>
    <Head :title="t('Admin') + ' • ' + t('Report details')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-4xl px-4 py-8">
            <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 class="text-2xl font-bold text-foreground">
                            {{ t('Report details') }}
                            <span class="font-mono text-base font-normal text-muted-foreground">#{{ props.report.id }}</span>
                        </h1>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ props.report.createdAt || '—' }}
                        </p>
                    </div>

                    <div class="flex gap-2">
                        <Button as-child variant="outline">
                            <Link href="/admin/chat-reports">{{ t('Back') }}</Link>
                        </Button>
                        <Button
                            v-if="props.report.context === 'match' && props.report.matchId"
                            as-child
                            variant="outline"
                        >
                            <Link :href="`/matches/${props.report.matchId}`">{{ t('Open match chat') }}</Link>
                        </Button>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 rounded-lg border border-border bg-muted/20 p-4 text-sm">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex rounded-full bg-muted/60 px-2.5 py-0.5 text-xs font-bold text-muted-foreground">
                            {{ props.report.context === 'match' ? t('Match') : t('Support') }}
                        </span>
                        <span v-if="props.report.matchId" class="font-mono text-xs text-muted-foreground">
                            match #{{ props.report.matchId }}
                        </span>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">
                                {{ t('From') }}
                            </p>
                            <p class="mt-1 font-medium text-foreground">{{ userLabel(props.report.reporter) }}</p>
                            <p class="mt-0.5 text-xs text-muted-foreground">{{ props.report.reporter.email || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">
                                {{ t('Reported user') }}
                            </p>
                            <p class="mt-1 font-medium text-foreground">{{ userLabel(props.report.reported) }}</p>
                            <p class="mt-0.5 text-xs text-muted-foreground">{{ props.report.reported.email || '—' }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">
                            {{ t('Reason') }}
                        </p>
                        <p class="mt-1 font-bold text-foreground">{{ reasonLabel(props.report.reason) }}</p>
                    </div>

                    <div v-if="props.report.details">
                        <p class="text-xs font-bold uppercase tracking-wide text-muted-foreground">
                            {{ t('Reporter notes') }}
                        </p>
                        <p class="mt-1 whitespace-pre-wrap text-foreground/90">{{ props.report.details }}</p>
                    </div>
                </div>

                <div class="mt-6">
                    <h2 class="text-lg font-bold text-foreground">{{ t('Message thread') }}</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ t('View') }}:
                        {{ props.report.messages.length ? t('Live') : t('Snapshot') }}
                    </p>

                    <div class="mt-4 space-y-2">
                        <div
                            v-for="m in (props.report.messages.length ? props.report.messages : (props.report.messagesSnapshot ?? []))"
                            :key="m.id"
                            class="rounded-lg border border-border bg-muted/30 p-3"
                        >
                            <div class="flex items-center justify-between gap-2 text-xs text-muted-foreground">
                                <span>{{ ('createdAt' in m ? m.createdAt : m.created_at) || '—' }}</span>
                                <span
                                    class="max-w-[55%] truncate text-right font-semibold"
                                    :class="isReportedSender('senderId' in m ? m.senderId : m.sender_id) ? 'text-destructive' : 'text-foreground'"
                                >
                                    {{ senderLabel('senderId' in m ? m.senderId : m.sender_id) }}
                                </span>
                            </div>
                            <p class="mt-2 whitespace-pre-wrap text-sm text-foreground/90">
                                {{ m.content }}
                            </p>
                        </div>

                        <div
                            v-if="!props.report.messages.length && !(props.report.messagesSnapshot && props.report.messagesSnapshot.length)"
                            class="rounded-lg border border-dashed border-border bg-muted/20 px-4 py-8 text-center text-sm text-muted-foreground"
                        >
                            {{ t('No results found.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

