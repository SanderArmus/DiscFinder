<script setup lang="ts">
import { Send, SmilePlus } from 'lucide-vue-next';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useTranslations } from '@/composables/useTranslations';

type Message = {
    id: number;
    senderId: number;
    content: string;
    createdAt: string;
};

const props = defineProps<{
    messages: Message[];
    authUserId: number;
    sending: boolean;
    chatBlocked: boolean;
    placeholder?: string;
    emptyHint?: string;
    error?: string | null;
}>();

const emit = defineEmits<{
    (e: 'send', content: string): void;
}>();

const t = useTranslations();

const content = ref('');
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

function scrollToBottom(): void {
    if (!messagesEl.value) return;
    messagesEl.value.scrollTop = messagesEl.value.scrollHeight;
}

function send(): void {
    if (props.chatBlocked) return;
    const value = applyEmoticonsToMessage(content.value.trim());
    if (!value) return;

    emit('send', value);
    content.value = '';
}

function handleComposerKeydown(e: KeyboardEvent): void {
    if (e.key !== 'Enter') return;
    if (e.shiftKey || e.metaKey || e.ctrlKey || e.altKey) return;
    if ((e as KeyboardEvent & { isComposing?: boolean }).isComposing) return;

    e.preventDefault();
    send();
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
    <div class="flex flex-1 flex-col overflow-hidden">
        <div
            ref="messagesEl"
            class="flex-1 min-h-0 space-y-3 overflow-y-auto p-5"
        >
            <div
                v-if="props.messages.length === 0"
                class="py-10 text-center text-sm text-muted-foreground"
            >
                {{ props.emptyHint ?? t('No messages yet. Leave the first message.') }}
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

        <form
            class="border-t border-border bg-card/95 p-4 backdrop-blur supports-backdrop-filter:bg-card/80"
            @submit.prevent="send"
        >
            <div class="relative">
                <textarea
                    v-model="content"
                    ref="textareaEl"
                    class="min-h-[56px] w-full resize-none rounded-lg border border-input bg-background px-3 py-2 pr-12 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    :placeholder="props.placeholder ?? t('Type your message...')"
                    :disabled="props.chatBlocked"
                    @keydown="handleComposerKeydown"
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
                    :disabled="props.chatBlocked || props.sending || !content.trim()"
                    :aria-label="t('Send')"
                    :title="t('Send')"
                >
                    <span v-if="props.sending" class="text-xs font-bold">
                        {{ t('Submitting…') }}
                    </span>
                    <Send v-else class="h-4 w-4" />
                </button>
            </div>

            <p v-if="props.error" class="mt-2 text-sm text-destructive">
                {{ props.error }}
            </p>
        </form>
    </div>
</template>

