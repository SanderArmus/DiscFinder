<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
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
const page = usePage();

type Person = {
    id: number | null;
    username: string | null;
    name: string | null;
    email: string | null;
    role: string | null;
};

type SupportMessageRow = {
    id: number;
    content: string | null;
    createdAt: string | null;
    sender: Person;
    receiver: Person;
};

type Filters = {
    q: string | null;
};

const props = defineProps<{
    filters: Filters;
    messages: {
        data: SupportMessageRow[];
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
    { title: t('Support messages'), href: '/admin/support-messages' },
]);

const form = reactive({
    q: props.filters.q ?? '',
});

const pageFrom = computed(() => props.messages.meta?.from ?? props.messages.from ?? 0);
const pageTo = computed(() => props.messages.meta?.to ?? props.messages.to ?? 0);
const pageTotal = computed(() => props.messages.meta?.total ?? props.messages.total ?? 0);

const successMessage = computed(
    () => (page.props as { flash?: { success?: string } }).flash?.success,
);

function submitFilters(): void {
    router.get(
        '/admin/support-messages',
        {
            q: form.q || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
}

function clearFilters(): void {
    form.q = '';
    submitFilters();
}

function userLabel(p: Person): string {
    return p.username || p.name || p.email || (p.id ? `#${p.id}` : t('Unknown'));
}

const isOpen = ref(false);
const replyingTo = ref<SupportMessageRow | null>(null);
const replyContent = ref('');
const isSending = ref(false);

function openReply(message: SupportMessageRow): void {
    replyingTo.value = message;
    replyContent.value = '';
    isOpen.value = true;
}

function sendReply(): void {
    if (!replyingTo.value) return;

    isSending.value = true;
    router.post(
        `/admin/support-messages/${replyingTo.value.id}/reply`,
        { content: replyContent.value } as any,
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                isOpen.value = false;
                replyingTo.value = null;
                replyContent.value = '';
            },
            onFinish: () => {
                isSending.value = false;
            },
        },
    );
}
</script>

<template>
    <Head :title="t('Admin') + ' • ' + t('Support messages')" />

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
                                    class="flex-1 whitespace-nowrap px-4 py-2 text-center text-sm font-bold transition-colors bg-card text-foreground border border-border shadow-sm rounded-t-lg relative z-10 -mb-px"
                                >
                                    {{ t('Support messages') }}
                                </Link>
                                <Link
                                    href="/admin/chat-reports"
                                    class="flex-1 whitespace-nowrap px-4 py-2 text-center text-sm font-bold transition-colors bg-muted/40 text-muted-foreground border border-transparent hover:bg-muted/50 hover:text-foreground rounded-t-lg"
                                >
                                    {{ t('Reports') }}
                                </Link>
                            </nav>
                        </div>

                        <h1 class="mt-4 text-2xl font-bold text-foreground">
                            {{ t('Support messages') }}
                        </h1>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ t('Messages sent to admin from Help page') }}
                        </p>
                    </div>

                    <div class="text-sm text-muted-foreground">
                        {{ pageFrom }}–{{ pageTo }}
                        / {{ pageTotal }}
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

                <div v-if="successMessage" class="mt-4 text-sm font-medium text-primary">
                    {{ successMessage }}
                </div>

                <div class="mt-6 overflow-x-auto rounded-lg border border-border bg-card">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-muted/40 text-xs uppercase tracking-wide text-muted-foreground">
                            <tr>
                                <th class="px-4 py-3">{{ t('Sent') }}</th>
                                <th class="px-4 py-3">{{ t('From') }}</th>
                                <th class="px-4 py-3">{{ t('To') }}</th>
                                <th class="px-4 py-3">{{ t('Message') }}</th>
                                <th class="px-4 py-3 text-right">{{ t('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="m in props.messages.data"
                                :key="m.id"
                                class="border-t border-border"
                            >
                                <td class="px-4 py-3 text-muted-foreground">
                                    {{ m.createdAt || '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-foreground">
                                        {{ userLabel(m.sender) }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ m.sender.email || '' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-foreground">
                                        {{ userLabel(m.receiver) }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ m.receiver.email || '' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-foreground">
                                    <div class="max-w-[520px] whitespace-pre-wrap">
                                        {{ m.content || '—' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Button variant="outline" class="h-9" @click="openReply(m)">
                                        {{ t('Reply') }}
                                    </Button>
                                </td>
                            </tr>

                            <tr v-if="props.messages.data.length === 0">
                                <td colspan="5" class="px-4 py-10 text-center text-sm text-muted-foreground">
                                    {{ t('No results found.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-2">
                    <Link
                        v-for="link in props.messages.links"
                        :key="link.label"
                        :href="link.url || ''"
                        class="rounded-md px-3 py-1.5 text-sm"
                        :class="link.active ? 'bg-primary text-primary-foreground' : 'bg-muted/40 text-foreground'"
                        v-html="link.label"
                    />
                </div>

                <Dialog :open="isOpen" @update:open="isOpen = $event">
                    <DialogContent class="sm:max-w-lg">
                        <DialogHeader>
                            <DialogTitle>{{ t('Reply') }}</DialogTitle>
                            <DialogDescription>
                                {{ t('Replying to') }}
                                <span class="font-medium text-foreground">
                                    {{ replyingTo ? userLabel(replyingTo.sender) : '' }}
                                </span>
                            </DialogDescription>
                        </DialogHeader>

                        <div class="mt-3 space-y-2">
                            <textarea
                                v-model="replyContent"
                                rows="5"
                                class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground shadow-xs outline-none transition-colors placeholder:text-muted-foreground focus:border-ring focus-visible:ring-2 focus-visible:ring-ring/20 dark:bg-muted/30"
                                :placeholder="t('Write your message')"
                            />
                        </div>

                        <div class="mt-4 flex items-center justify-end gap-2">
                            <Button variant="outline" @click="isOpen = false">
                                {{ t('Cancel') }}
                            </Button>
                            <Button
                                :disabled="isSending || replyContent.trim() === ''"
                                @click="sendReply"
                            >
                                {{ isSending ? t('Sending...') : t('Send') }}
                            </Button>
                        </div>
                    </DialogContent>
                </Dialog>
            </div>
        </div>
    </AppLayout>
</template>

