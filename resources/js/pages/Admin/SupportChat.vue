<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Flag, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import ChatThreadBox from '@/components/ChatThreadBox.vue';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';

const t = useTranslations();

type Message = {
    id: number;
    senderId: number;
    content: string;
    createdAt: string;
};

const props = defineProps<{
    receiverId: number;
    messages: Message[];
    otherUserName: string;
    authUserId: number;
    chatBlocked: boolean;
}>();

const breadcrumbs = computed(() => [
    { title: t('My Profile'), href: dashboard().url },
    { title: t('Admin'), href: '/admin/discs' },
    { title: t('Messages'), href: '/messages' },
]);

const sending = ref(false);
const error = ref<string | null>(null);
const reportOpen = ref(false);
const reportReason = ref<'harassment' | 'spam' | 'scam' | 'other'>('harassment');
const reportDetails = ref('');
const reportAlsoBlock = ref(true);
function sendMessage(value: string): void {
    if (props.chatBlocked) return;

    error.value = null;
    sending.value = true;

    router.post(
        '/support/messages',
        { content: value, receiver_id: props.receiverId } as any,
        {
            preserveScroll: true,
            onError: (e: Record<string, unknown>) => {
                error.value = (e.content as string | undefined) ?? 'Unable to send message.';
            },
            onFinish: () => {
                sending.value = false;
            },
        },
    );
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

</script>

<template>
    <Head :title="t('Admin') + ' • ' + t('Support') + ' • ' + otherUserName" />

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
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
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

                <ChatThreadBox
                    :messages="props.messages"
                    :authUserId="props.authUserId"
                    :sending="sending"
                    :chatBlocked="props.chatBlocked"
                    :placeholder="props.chatBlocked ? t('Chat ended') : t('Type your message...')"
                    :error="error"
                    @send="sendMessage"
                />
            </div>
        </div>

        <Dialog :open="reportOpen" @update:open="reportOpen = $event">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ t('Report') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('Report message hint') }}
                    </DialogDescription>
                </DialogHeader>

                <div class="mt-4 space-y-3 text-sm">
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            class="rounded-lg border border-input bg-muted/50 px-3 py-2 text-left font-bold text-foreground"
                            :class="reportReason === 'harassment' ? 'ring-2 ring-primary/30' : ''"
                            @click="reportReason = 'harassment'"
                        >
                            {{ t('Harassment') }}
                        </button>
                        <button
                            type="button"
                            class="rounded-lg border border-input bg-muted/50 px-3 py-2 text-left font-bold text-foreground"
                            :class="reportReason === 'spam' ? 'ring-2 ring-primary/30' : ''"
                            @click="reportReason = 'spam'"
                        >
                            {{ t('Spam') }}
                        </button>
                        <button
                            type="button"
                            class="rounded-lg border border-input bg-muted/50 px-3 py-2 text-left font-bold text-foreground"
                            :class="reportReason === 'scam' ? 'ring-2 ring-primary/30' : ''"
                            @click="reportReason = 'scam'"
                        >
                            {{ t('Scam') }}
                        </button>
                        <button
                            type="button"
                            class="rounded-lg border border-input bg-muted/50 px-3 py-2 text-left font-bold text-foreground"
                            :class="reportReason === 'other' ? 'ring-2 ring-primary/30' : ''"
                            @click="reportReason = 'other'"
                        >
                            {{ t('Other') }}
                        </button>
                    </div>

                    <div>
                        <div class="mb-1 text-xs font-bold uppercase tracking-wider text-muted-foreground">
                            {{ t('Details (optional)') }}
                        </div>
                        <textarea
                            v-model="reportDetails"
                            rows="4"
                            class="w-full rounded-lg border border-input bg-muted/50 px-3 py-2 text-sm text-foreground shadow-xs outline-none transition-colors placeholder:text-muted-foreground focus:border-ring focus-visible:ring-2 focus-visible:ring-ring/20 dark:bg-muted/30"
                            :placeholder="t('Reporter notes')"
                        />
                    </div>

                    <label class="flex items-start gap-2">
                        <input v-model="reportAlsoBlock" type="checkbox" class="mt-1 h-4 w-4 rounded border-input" />
                        <span class="text-sm text-foreground">
                            {{ t('Also block user') }}
                        </span>
                    </label>
                </div>

                <div class="mt-4 flex items-center justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-lg border border-input bg-muted/50 px-4 py-2 text-sm font-bold text-foreground hover:bg-muted"
                        @click="reportOpen = false"
                    >
                        {{ t('Cancel') }}
                    </button>
                    <button
                        type="button"
                        class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-primary-foreground hover:opacity-90"
                        @click="submitReport"
                    >
                        {{ t('Report') }}
                    </button>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

