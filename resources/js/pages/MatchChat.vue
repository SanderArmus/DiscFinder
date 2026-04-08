<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { ArrowLeftRight, Send, SmilePlus, X } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard } from '@/routes';

const t = useTranslations();

type Match = {
    id: number;
    name: string;
};

type Message = {
    id: number;
    senderId: number;
    content: string;
    createdAt: string;
};

type DiscSummary = {
    id: number;
    name: string;
    ownerName: string;
    date: string;
    location: string;
};

const props = defineProps<{
    match: Match;
    messages: Message[];
    otherUserName: string;
    authUserId: number;
    displayDiscDate: string;
    displayDiscLocation: string;
    displayDiscId: number;
    lostDisc: DiscSummary;
    foundDisc: DiscSummary;
    ownConfirmed: boolean;
    otherConfirmed: boolean;
    ownHandedOver: boolean;
    otherHandedOver: boolean;
    matchStatus: string;
}>();

const breadcrumbs = computed(() => [
    { title: t('My Profile'), href: dashboard().url },
]);

const content = ref('');
const sending = ref(false);
const error = ref<string | null>(null);
const messagesEl = ref<HTMLElement | null>(null);
const textareaEl = ref<HTMLTextAreaElement | null>(null);
const emojiOpen = ref(false);
const emojiButtonEl = ref<HTMLElement | null>(null);
const emojiPopoverEl = ref<HTMLElement | null>(null);

const emojiChoices = [
    '😀', '😄', '😉', '😍', '🥳', '😅', '😢', '😡',
    '👍', '🙏', '👏', '🔥', '🎉', '❤️', '🤝', '✅',
];

const bothConfirmed = computed(
    () => props.ownConfirmed && props.otherConfirmed,
);
const bothHandedOver = computed(
    () => props.ownHandedOver && props.otherHandedOver,
);

const statusHint = computed(() => {
    if (props.ownConfirmed && !bothConfirmed.value) {
        return t('Waiting for the other side to confirm', { name: props.otherUserName });
    }

    if (bothConfirmed.value && props.ownHandedOver && !bothHandedOver.value) {
        return t('Waiting for the other side to hand over', { name: props.otherUserName });
    }

    return null;
});

const matchStatusLabel = computed(() => {
    switch (props.matchStatus) {
        case 'confirmed':
            return t('Confirmed');
        case 'handed_over':
            return t('Handed over');
        case 'rejected':
            return t('Rejected');
        default:
            return t('Pending');
    }
});

const matchStatusClass = computed(() => {
    switch (props.matchStatus) {
        case 'confirmed':
            return 'bg-yellow-500/15 text-yellow-700 dark:text-yellow-400 border-yellow-500/20';
        case 'handed_over':
            return 'bg-primary/15 text-primary border-primary/20';
        case 'rejected':
            return 'bg-destructive/15 text-destructive border-destructive/20';
        default:
            return 'bg-muted/50 text-muted-foreground border-border';
    }
});

function applyEmoticonsToMessage(value: string): string {
    const replacements: Array<[RegExp, string]> = [
        [/(^|[^:])(:-\)|:\))/g, '$1🙂'],
        [/(^|[^:])(:-\(|:\()/g, '$1🙁'],
        [/(^|[^:])(;-\)|;\))/g, '$1😉'],
        [/(^|[^:])(:-D|:D)/gi, '$1😄'],
        [/(^|[^:])(:-P|:P)/gi, '$1😛'],
        [/&lt;3|<3/g, '❤️'],
        [/:smile:/g, '😄'],
        [/:heart:/g, '❤️'],
        [/:thumbsup:/g, '👍'],
    ];

    // Avoid changing URLs (basic heuristic).
    const parts = value.split(/(\s+)/);
    return parts
        .map((part) => {
            if (part.includes('://') || part.startsWith('www.') || part.startsWith('http')) {
                return part;
            }

            let out = part;
            for (const [re, repl] of replacements) {
                out = out.replace(re, repl);
            }
            return out;
        })
        .join('');
}

function insertEmoji(emoji: string): void {
    const el = textareaEl.value;
    if (!el) {
        content.value = `${content.value}${emoji}`;
        emojiOpen.value = false;
        return;
    }

    const start = el.selectionStart ?? content.value.length;
    const end = el.selectionEnd ?? content.value.length;
    const before = content.value.slice(0, start);
    const after = content.value.slice(end);
    content.value = `${before}${emoji}${after}`;

    void nextTick(() => {
        el.focus();
        const pos = start + emoji.length;
        el.setSelectionRange(pos, pos);
    });

    emojiOpen.value = false;
}

function handleGlobalPointerDown(e: MouseEvent): void {
    if (!emojiOpen.value) return;
    const target = e.target as Node | null;
    const button = emojiButtonEl.value;
    if (button && target && button.contains(target)) return;
    const popover = emojiPopoverEl.value;
    if (popover && target && popover.contains(target)) return;

    emojiOpen.value = false;
}

function handleComposerKeydown(e: KeyboardEvent): void {
    if (e.key !== 'Enter') return;
    if (e.shiftKey || e.metaKey || e.ctrlKey || e.altKey) return;
    if ((e as KeyboardEvent & { isComposing?: boolean }).isComposing) return;

    e.preventDefault();
    sendMessage();
}

function sendMessage(): void {
    const value = applyEmoticonsToMessage(content.value.trim());
    if (!value) return;

    error.value = null;
    sending.value = true;

    router.post(
        `/matches/${props.match.id}/messages`,
        { content: value },
        {
            preserveScroll: true,
            onError: (e: Record<string, unknown>) => {
                error.value = (e.content as string | undefined) ?? 'Unable to send message.';
            },
            onFinish: () => {
                sending.value = false;
                content.value = '';
            },
        },
    );
}

function confirmMatch(): void {
    if (!window.confirm(t('Are you sure you want to confirm this match?'))) {
        return;
    }

    router.post(`/matches/${props.match.id}/confirm`, {}, { preserveScroll: true });
}

function handOverMatch(): void {
    if (!window.confirm(t('Are you sure you handed over the disc?'))) {
        return;
    }

    router.post(`/matches/${props.match.id}/handover`, {}, { preserveScroll: true });
}

function rejectMatch(): void {
    if (!window.confirm(t('Are you sure you want to reject this match?'))) {
        return;
    }

    router.post(`/matches/${props.match.id}/reject`, {}, { preserveScroll: true });
}

async function scrollToLatest(behavior: ScrollBehavior = 'auto'): Promise<void> {
    await nextTick();
    const el = messagesEl.value;
    if (!el) return;
    el.scrollTo({ top: el.scrollHeight, behavior });
}

onMounted(() => {
    void scrollToLatest();
    window.addEventListener('pointerdown', handleGlobalPointerDown);
});

onBeforeUnmount(() => {
    window.removeEventListener('pointerdown', handleGlobalPointerDown);
});

watch(
    () => props.messages.length,
    () => {
        void scrollToLatest('smooth');
    },
);
</script>

<template>
    <Head :title="t('Chat')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-3xl flex-1 flex-col px-4 py-8">
            <div class="flex h-[82vh] flex-col overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                <!-- Header + actions (always visible) -->
                <div class="relative border-b border-border p-5">
                    <Link
                        href="/messages"
                        class="absolute right-5 top-5 inline-flex h-8 w-8 items-center justify-center rounded-md border border-input bg-muted/50 text-foreground transition-colors hover:bg-muted"
                        :title="t('Close')"
                        :aria-label="t('Close')"
                    >
                        <X class="h-4 w-4" />
                    </Link>

                    <div class="flex items-start">
                        <div class="min-w-0 flex-1 pr-10">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="truncate text-xl font-bold text-foreground">
                                    {{ props.otherUserName }}
                                </h1>
                                <span
                                    class="inline-flex h-7 shrink-0 items-center rounded-full border px-3 text-[11px] font-bold"
                                    :class="matchStatusClass"
                                >
                                    {{ matchStatusLabel }}
                                </span>
                                <span
                                    v-if="statusHint"
                                    class="truncate text-xs font-bold text-muted-foreground"
                                >
                                    {{ statusHint }}
                                </span>
                            </div>

                            <div class="mt-2 grid w-full grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] sm:items-stretch">
                                <Link
                                    :href="`/discs/${props.lostDisc.id}`"
                                    class="min-w-0 rounded-lg border border-border bg-background/50 p-3 transition-colors hover:bg-background"
                                >
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="inline-flex rounded-full bg-destructive/15 px-2 py-0.5 text-[11px] font-bold text-destructive">
                                            {{ t('Lost') }}
                                        </span>
                                        <span class="truncate text-xs font-bold text-muted-foreground">
                                            {{ props.lostDisc.ownerName }}
                                        </span>
                                    </div>
                                    <div class="mt-1 truncate text-sm font-bold text-foreground">
                                        {{ props.lostDisc.name }}
                                    </div>
                                    <div class="mt-0.5 truncate text-xs text-muted-foreground">
                                        {{ props.lostDisc.date }} • {{ props.lostDisc.location }}
                                    </div>
                                </Link>

                                <Link
                                    :href="`/matches/${props.match.id}/details`"
                                    class="inline-flex h-full min-h-[56px] w-10 items-center justify-center rounded-lg border border-input bg-muted/50 text-foreground transition-colors hover:bg-muted"
                                    :title="t('Compare')"
                                    :aria-label="t('Compare')"
                                >
                                    <ArrowLeftRight class="h-4 w-4" />
                                </Link>

                                <Link
                                    :href="`/discs/${props.foundDisc.id}`"
                                    class="min-w-0 rounded-lg border border-border bg-background/50 p-3 transition-colors hover:bg-background"
                                >
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="inline-flex rounded-full bg-primary/15 px-2 py-0.5 text-[11px] font-bold text-primary">
                                            {{ t('Found') }}
                                        </span>
                                        <span class="truncate text-xs font-bold text-muted-foreground">
                                            {{ props.foundDisc.ownerName }}
                                        </span>
                                    </div>
                                    <div class="mt-1 truncate text-sm font-bold text-foreground">
                                        {{ props.foundDisc.name }}
                                    </div>
                                    <div class="mt-0.5 truncate text-xs text-muted-foreground">
                                        {{ props.foundDisc.date }} • {{ props.foundDisc.location }}
                                    </div>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 space-y-2">
                        <div
                            v-if="!bothConfirmed && props.matchStatus !== 'rejected' && props.matchStatus !== 'confirmed' && props.matchStatus !== 'handed_over'"
                            class="grid grid-cols-1 gap-2 sm:grid-cols-2"
                        >
                            <button
                                type="button"
                                class="h-10 w-full rounded-lg text-sm font-bold transition-opacity"
                                :class="
                                    !props.ownConfirmed
                                        ? 'bg-primary text-primary-foreground hover:opacity-90'
                                        : 'cursor-not-allowed bg-muted text-foreground/70'
                                "
                                :disabled="props.ownConfirmed"
                                @click="confirmMatch"
                            >
                                {{
                                    !props.ownConfirmed
                                        ? t('Confirm match')
                                        : t('Waiting for the other side to confirm')
                                }}
                            </button>

                            <button
                                type="button"
                                class="h-10 w-full rounded-lg bg-destructive text-sm font-bold text-white transition-opacity hover:opacity-90"
                                @click="rejectMatch"
                            >
                                {{ t('Reject') }}
                            </button>
                        </div>

                        <div
                            v-if="bothConfirmed && !bothHandedOver && props.matchStatus !== 'rejected' && props.matchStatus !== 'handed_over'"
                            class="grid grid-cols-1 gap-2 sm:grid-cols-2"
                        >
                            <button
                                type="button"
                                class="h-10 w-full rounded-lg text-sm font-bold transition-opacity"
                                :class="
                                    !props.ownHandedOver
                                        ? 'bg-primary text-primary-foreground hover:opacity-90'
                                        : 'cursor-not-allowed bg-muted text-foreground/70'
                                "
                                :disabled="props.ownHandedOver"
                                :title="
                                    props.ownHandedOver
                                        ? t('Waiting for the other side to hand over')
                                        : t('Handed over')
                                "
                                @click="handOverMatch"
                            >
                                {{ t('Handed over') }}
                            </button>

                            <button
                                type="button"
                                class="h-10 w-full rounded-lg bg-destructive text-sm font-bold text-white transition-opacity hover:opacity-90"
                                @click="rejectMatch"
                            >
                                {{ t('Reject') }}
                            </button>
                        </div>

                        <button
                            v-else-if="bothConfirmed && bothHandedOver"
                            type="button"
                            class="h-10 w-full cursor-not-allowed rounded-lg bg-muted text-sm font-bold text-foreground/70 transition-opacity"
                            disabled
                        >
                            {{ t('Match handed over') }}
                        </button>

                    </div>
                </div>

                <!-- Messages (scrollable) + composer -->
                <div class="flex flex-1 flex-col overflow-hidden">
                    <div
                        ref="messagesEl"
                        class="flex-1 min-h-0 space-y-3 overflow-y-auto p-5"
                    >
                        <div
                            v-if="props.messages.length === 0"
                            class="py-10 text-center text-sm text-muted-foreground"
                        >
                            {{ t('No messages yet. Leave the first message.') }}
                        </div>

                        <div
                            v-for="msg in props.messages"
                            v-else
                            :key="msg.id"
                            class="flex"
                            :class="msg.senderId === props.authUserId ? 'justify-end' : 'justify-start'"
                        >
                            <div
                                class="group max-w-[80%] rounded-2xl px-3 py-2"
                                :class="
                                    msg.senderId === props.authUserId
                                        ? 'bg-primary text-primary-foreground'
                                        : 'bg-muted/50 text-foreground'
                                "
                                :title="msg.createdAt"
                            >
                                <p class="whitespace-pre-wrap text-sm font-medium">
                                    {{ msg.content }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Composer (stays visible, no overlap with scrollbar) -->
                    <form
                        class="border-t border-border bg-card/95 p-4 backdrop-blur supports-backdrop-filter:bg-card/80"
                        @submit.prevent="sendMessage"
                    >
                        <div class="relative">
                            <textarea
                                v-model="content"
                                ref="textareaEl"
                                @keydown="handleComposerKeydown"
                                class="min-h-[56px] w-full resize-none rounded-lg border border-input bg-background px-3 py-2 pr-12 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                :placeholder="t('Type your message...')"
                            />

                            <div class="absolute right-12 top-1/2 -translate-y-1/2">
                                <button
                                    ref="emojiButtonEl"
                                    type="button"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                    :title="t('Emoji')"
                                    :aria-label="t('Emoji')"
                                    @click="emojiOpen = !emojiOpen"
                                >
                                    <SmilePlus class="h-4 w-4" />
                                </button>

                                <div
                                    v-if="emojiOpen"
                                    ref="emojiPopoverEl"
                                    class="absolute right-0 bottom-11 z-20 w-56 max-h-60 overflow-y-auto rounded-xl border border-border bg-card p-3 shadow-lg"
                                >
                                    <div class="grid grid-cols-8 gap-1">
                                        <button
                                            v-for="emoji in emojiChoices"
                                            :key="emoji"
                                            type="button"
                                            class="flex h-8 w-8 items-center justify-center rounded-lg hover:bg-muted"
                                            @click="insertEmoji(emoji)"
                                        >
                                            <span class="text-lg leading-none">{{ emoji }}</span>
                                        </button>
                                    </div>
                                    <p class="mt-2 text-[10px] font-bold text-muted-foreground">
                                        {{ t('Tip: type :) or :D') }}
                                    </p>
                                </div>
                            </div>

                            <button
                                type="submit"
                                class="absolute right-2 top-1/2 inline-flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-lg bg-primary text-primary-foreground transition-opacity hover:opacity-90 disabled:opacity-60"
                                :disabled="sending || !content.trim()"
                                :aria-label="t('Send')"
                                :title="t('Send')"
                            >
                                <span v-if="sending" class="text-xs font-bold">
                                    {{ t('Submitting…') }}
                                </span>
                                <Send v-else class="h-4 w-4" />
                            </button>
                        </div>

                        <p v-if="error" class="mt-2 text-sm text-destructive">
                            {{ error }}
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

