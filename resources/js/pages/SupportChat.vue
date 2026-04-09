<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Flag, Send, SmilePlus, X } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard } from '@/routes';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

const t = useTranslations();

type Message = {
    id: number;
    senderId: number;
    content: string;
    createdAt: string;
};

const props = defineProps<{
    messages: Message[];
    otherUserName: string;
    authUserId: number;
    chatBlocked: boolean;
}>();

const breadcrumbs = computed(() => [
    { title: t('My Profile'), href: dashboard().url },
    { title: t('Messages'), href: '/messages' },
]);

const content = ref('');
const sending = ref(false);
const error = ref<string | null>(null);
const reportOpen = ref(false);
const reportReason = ref<'harassment' | 'spam' | 'scam' | 'other'>('harassment');
const reportDetails = ref('');
const reportAlsoBlock = ref(true);
const messagesEl = ref<HTMLElement | null>(null);
const textareaEl = ref<HTMLTextAreaElement | null>(null);
const emojiOpen = ref(false);
const emojiButtonEl = ref<HTMLElement | null>(null);
const emojiPopoverEl = ref<HTMLElement | null>(null);

const emojiChoices = [
    '😀', '😄', '😉', '😍', '🥳', '😅', '😢', '😡',
    '👍', '🙏', '👏', '🔥', '🎉', '❤️', '🤝', '✅',
];

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
    if (props.chatBlocked) return;
    const value = applyEmoticonsToMessage(content.value.trim());
    if (!value) return;

    error.value = null;
    sending.value = true;

    router.post(
        '/support/messages',
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

function endChat(): void {
    if (!window.confirm(t('Are you sure you want to block this user?'))) {
        return;
    }

    router.post('/support/chat/end', {}, { preserveScroll: true });
}

function submitReport(): void {
    router.post(
        '/chat-reports',
        {
            context: 'support',
            reason: reportReason.value,
            details: reportDetails.value || null,
            also_block: reportAlsoBlock.value,
        } as any,
        {
            preserveScroll: true,
            onFinish: () => {
                reportOpen.value = false;
                reportDetails.value = '';
            },
        },
    );
}

function scrollToBottom(): void {
    if (!messagesEl.value) return;
    messagesEl.value.scrollTop = messagesEl.value.scrollHeight;
}

onMounted(() => {
    document.addEventListener('pointerdown', handleGlobalPointerDown);
    void nextTick(scrollToBottom);
});

onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', handleGlobalPointerDown);
});

watch(
    () => props.messages.length,
    () => void nextTick(scrollToBottom),
);
</script>

<template>
    <Head :title="t('Messages') + ' • ' + t('Support')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-3xl px-4 py-6">
            <div class="rounded-xl border border-border bg-card shadow-sm">
                <div class="flex items-center justify-between gap-3 border-b border-border p-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-bold text-muted-foreground">
                                {{ t('Support') }}
                            </span>
                            <h1 class="truncate text-base font-bold text-foreground">
                                {{ otherUserName }}
                            </h1>
                            <button
                                v-if="!props.chatBlocked"
                                type="button"
                                class="inline-flex h-7 items-center justify-center rounded-full border border-destructive/40 bg-destructive/10 px-2.5 text-[11px] font-bold text-destructive transition-colors hover:bg-destructive/20"
                                @click="endChat"
                            >
                                {{ t('Block user') }}
                            </button>
                            <button
                                v-else
                                type="button"
                                class="inline-flex h-7 items-center justify-center gap-1 rounded-full border border-destructive/40 bg-destructive/10 px-2.5 text-[11px] font-bold text-destructive transition-colors hover:bg-destructive/20"
                                :aria-label="t('Report')"
                                :title="t('Report')"
                                @click="reportOpen = true"
                            >
                                <Flag class="h-3 w-3 shrink-0" />
                                {{ t('Report') }}
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                    <button
                        v-if="!props.chatBlocked"
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-input bg-muted/50 text-foreground transition-colors hover:bg-muted"
                        :aria-label="t('Report')"
                        :title="t('Report')"
                        @click="reportOpen = true"
                    >
                        <Flag class="h-5 w-5" />
                    </button>
                    <Link
                        href="/messages"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-md hover:bg-muted"
                        :aria-label="t('Close')"
                        :title="t('Close')"
                    >
                        <X class="h-5 w-5" />
                    </Link>
                    </div>
                </div>

                <div
                    ref="messagesEl"
                    class="h-[55vh] overflow-y-auto p-4"
                >
                    <div class="space-y-2">
                        <div
                            v-for="m in props.messages"
                            :key="m.id"
                            class="flex"
                            :class="m.senderId === authUserId ? 'justify-end' : 'justify-start'"
                        >
                            <div
                                class="max-w-[85%] rounded-2xl px-3 py-2 text-sm shadow-sm"
                                :class="
                                    m.senderId === authUserId
                                        ? 'bg-primary text-primary-foreground'
                                        : 'bg-muted text-foreground'
                                "
                            >
                                <p class="whitespace-pre-wrap wrap-break-word">
                                    {{ m.content }}
                                </p>
                                <p class="mt-1 text-right text-[10px] opacity-70">
                                    {{ m.createdAt }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-border p-3">
                    <div
                        v-if="props.chatBlocked"
                        class="mb-3 rounded-lg border border-border bg-muted/30 p-3 text-sm text-muted-foreground"
                    >
                        {{ t('Chat ended') }}
                    </div>

                    <div v-if="error" class="mb-2 text-sm text-destructive">
                        {{ error }}
                    </div>

                    <div class="relative flex items-end gap-2">
                        <button
                            ref="emojiButtonEl"
                            type="button"
                            class="mb-1 inline-flex h-9 w-9 items-center justify-center rounded-md hover:bg-muted"
                            @click="emojiOpen = !emojiOpen"
                            :aria-label="t('Emoji')"
                            title="Emoji"
                        >
                            <SmilePlus class="h-5 w-5" />
                        </button>

                        <div
                            v-if="emojiOpen"
                            ref="emojiPopoverEl"
                            class="absolute bottom-12 left-0 z-50 w-56 rounded-xl border border-border bg-card p-2 shadow-lg max-h-60 overflow-y-auto"
                        >
                            <div class="grid grid-cols-8 gap-1">
                                <button
                                    v-for="e in emojiChoices"
                                    :key="e"
                                    type="button"
                                    class="rounded-md p-1 text-lg hover:bg-muted"
                                    @click="insertEmoji(e)"
                                >
                                    {{ e }}
                                </button>
                            </div>
                        </div>

                        <textarea
                            ref="textareaEl"
                            v-model="content"
                            rows="2"
                            class="min-h-10 flex-1 resize-none rounded-xl border border-input bg-background px-3 py-2 text-sm text-foreground shadow-xs outline-none transition-colors placeholder:text-muted-foreground focus:border-ring focus-visible:ring-2 focus-visible:ring-ring/20 dark:bg-muted/30"
                            :placeholder="t('Write your message')"
                            @keydown="handleComposerKeydown"
                            :disabled="props.chatBlocked"
                        />

                        <button
                            type="button"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-sm transition-opacity hover:opacity-90 disabled:opacity-60"
                            :disabled="props.chatBlocked || sending || content.trim() === ''"
                            @click="sendMessage"
                            :title="t('Send')"
                            :aria-label="t('Send')"
                        >
                            <Send class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

    <Dialog :open="reportOpen" @update:open="reportOpen = $event">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{ t('Report') }}</DialogTitle>
                <DialogDescription>
                    {{ t('Report message hint') }}
                </DialogDescription>
            </DialogHeader>

            <div class="mt-3 space-y-3">
                <label class="block text-sm font-bold text-foreground">
                    {{ t('Reason') }}
                </label>
                <select
                    v-model="reportReason"
                    class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm"
                >
                    <option value="harassment">{{ t('Harassment') }}</option>
                    <option value="spam">{{ t('Spam') }}</option>
                    <option value="scam">{{ t('Scam') }}</option>
                    <option value="other">{{ t('Other') }}</option>
                </select>

                <label class="block text-sm font-bold text-foreground">
                    {{ t('Details (optional)') }}
                </label>
                <textarea
                    v-model="reportDetails"
                    rows="4"
                    class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground shadow-xs outline-none transition-colors placeholder:text-muted-foreground focus:border-ring focus-visible:ring-2 focus-visible:ring-ring/20 dark:bg-muted/30"
                    :placeholder="t('Write your message')"
                />

                <label class="flex items-center gap-2 text-sm text-foreground">
                    <input v-model="reportAlsoBlock" type="checkbox" class="h-4 w-4" />
                    <span class="font-bold">{{ t('Also block user') }}</span>
                </label>
            </div>

            <div class="mt-4 flex items-center justify-end gap-2">
                <button
                    type="button"
                    class="h-10 rounded-lg border border-input bg-muted/50 px-4 text-sm font-bold text-foreground hover:bg-muted"
                    @click="reportOpen = false"
                >
                    {{ t('Cancel') }}
                </button>
                <button
                    type="button"
                    class="h-10 rounded-lg bg-destructive px-4 text-sm font-bold text-white hover:opacity-90"
                    @click="submitReport"
                >
                    {{ t('Report') }}
                </button>
            </div>
        </DialogContent>
    </Dialog>
</template>

