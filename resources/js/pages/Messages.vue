<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { MessageSquare } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

const t = useTranslations();

type MatchThreadCard = {
    id: number;
    discName: string;
    otherDiscDate: string;
    otherDiscLocation: string;
    otherUserName: string;
    matchStatus: string;
    otherConfirmed: boolean;
    otherHandedOver: boolean;
    lastMessagePreview: string;
    lastMessageAt: string;
};

const props = defineProps<{
    threads: MatchThreadCard[];
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('My Profile'), href: dashboard().url },
]);
</script>

<template>
    <Head :title="t('App Messaging')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-3xl px-4 py-8">
            <div class="mb-6 rounded-xl border border-border bg-card p-5 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <MessageSquare class="h-5 w-5 text-primary" />
                        <h1 class="text-xl font-bold text-foreground">
                            {{ t('App Messaging') }}
                        </h1>
                    </div>
                    <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                        {{ props.threads.length }} {{ t('Items') }}
                    </span>
                </div>
            </div>

            <div class="space-y-3">
                <div
                    v-if="props.threads.length === 0"
                    class="rounded-xl border border-border bg-card p-6 text-center text-sm text-muted-foreground shadow-sm"
                >
                    {{ t('No messages yet. Leave the first message.') }}
                </div>

                <Link
                    v-for="thread in props.threads"
                    :key="thread.id"
                    :href="`/matches/${thread.id}`"
                    class="block rounded-xl border border-sidebar-border bg-card p-5 shadow-sm transition-colors hover:border-primary/50 dark:border-sidebar-border"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="font-bold text-foreground">
                                {{ thread.discName }}
                            </h2>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ thread.otherUserName }} • {{ t('Chat') }}
                            </p>
                            <div class="mt-2 flex items-center gap-2">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold"
                                    :class="
                                        thread.matchStatus === 'handed_over'
                                            ? 'bg-muted text-foreground/70 dark:bg-muted/50'
                                            : thread.matchStatus === 'confirmed'
                                              ? 'bg-primary/20 text-foreground dark:text-primary'
                                              : 'bg-muted/50 text-muted-foreground'
                                    "
                                >
                                    {{
                                        thread.matchStatus === 'handed_over'
                                            ? t('Handed over')
                                            : thread.matchStatus === 'confirmed'
                                              ? t('Confirmed')
                                              : t('Pending')
                                    }}
                                </span>
                                <span
                                    v-if="thread.otherHandedOver"
                                    class="text-xs font-bold text-muted-foreground"
                                >
                                    {{ t('Other handed over') }}
                                </span>
                                <span
                                    v-else-if="thread.otherConfirmed"
                                    class="text-xs font-bold text-muted-foreground"
                                >
                                    {{ t('Other confirmed') }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ thread.otherDiscDate }} • {{ thread.otherDiscLocation }}
                            </p>
                            <p class="mt-2 truncate text-sm text-foreground/80">
                                {{ thread.lastMessagePreview }}
                            </p>
                        </div>
                        <p class="text-[10px] font-bold uppercase tracking-tighter text-muted-foreground">
                            {{ thread.lastMessageAt }}
                        </p>
                    </div>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

