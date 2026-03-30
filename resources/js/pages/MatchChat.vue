<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
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

const props = defineProps<{
    match: Match;
    messages: Message[];
    otherUserName: string;
    authUserId: number;
    displayDiscDate: string;
    displayDiscLocation: string;
    displayDiscId: number;
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

const bothConfirmed = computed(
    () => props.ownConfirmed && props.otherConfirmed,
);
const bothHandedOver = computed(
    () => props.ownHandedOver && props.otherHandedOver,
);

function sendMessage(): void {
    const value = content.value.trim();
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
    router.post(`/matches/${props.match.id}/confirm`, {}, { preserveScroll: true });
}

function handOverMatch(): void {
    router.post(`/matches/${props.match.id}/handover`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="t('Chat')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 px-4 py-8">
            <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                <div class="mb-2 flex items-center justify-between gap-4">
                    <div>
                        <Link
                            :href="`/discs/${props.displayDiscId}`"
                            class="text-xl font-bold text-foreground hover:text-primary"
                        >
                            {{ props.match.name }}
                        </Link>
                        <p class="text-sm text-muted-foreground">
                            {{ t('Leave a message') }} • {{ props.otherUserName }}
                        </p>
                            <Link
                                :href="`/discs/${props.displayDiscId}`"
                                class="mt-1 block text-xs font-bold text-muted-foreground hover:text-primary"
                            >
                                {{ props.displayDiscDate }} • {{ props.displayDiscLocation }}
                            </Link>
                        <div
                            v-if="bothConfirmed"
                            class="mt-3 rounded-md bg-primary/10 p-2 text-sm font-bold text-primary"
                        >
                            {{ t('Match confirmed') }}
                        </div>
                        <div
                            v-else-if="props.ownConfirmed"
                            class="mt-3 rounded-md bg-muted/50 p-2 text-sm font-bold text-foreground"
                        >
                            {{ t('Waiting for the other side to confirm') }}
                        </div>
                    </div>
                    <Link :href="dashboard().url" class="text-sm font-bold text-muted-foreground hover:text-primary">
                        {{ t('Cancel') }}
                    </Link>
                </div>

                <div class="mt-4">
                    <button
                        v-if="!bothConfirmed"
                        type="button"
                        class="w-full rounded-lg py-2 text-sm font-bold transition-opacity"
                        :class="
                            !props.ownConfirmed
                                ? 'bg-primary text-primary-foreground hover:opacity-90'
                                : 'bg-muted text-foreground/70 cursor-not-allowed'
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
                        v-else
                        type="button"
                        class="w-full cursor-not-allowed rounded-lg bg-muted py-2 text-sm font-bold text-foreground/70 transition-opacity"
                        disabled
                    >
                        {{ t('Match confirmed') }}
                    </button>
                </div>

                <div
                    class="mt-3"
                    v-if="bothConfirmed"
                >
                    <button
                        v-if="!bothHandedOver && !props.ownHandedOver"
                        type="button"
                        class="w-full rounded-lg bg-primary py-2 text-sm font-bold text-primary-foreground transition-opacity hover:opacity-90"
                        @click="handOverMatch"
                    >
                        {{ t('Handed over') }}
                    </button>

                    <button
                        v-else-if="!bothHandedOver && props.ownHandedOver"
                        type="button"
                        class="w-full cursor-not-allowed rounded-lg bg-muted py-2 text-sm font-bold text-foreground/70 transition-opacity"
                        disabled
                    >
                        {{ t('Waiting for the other side to hand over') }}
                    </button>

                    <button
                        v-else
                        type="button"
                        class="w-full cursor-not-allowed rounded-lg bg-muted py-2 text-sm font-bold text-foreground/70 transition-opacity"
                        disabled
                    >
                        {{ t('Match handed over') }}
                    </button>
                </div>
            </div>

            <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                <div
                    v-if="props.messages.length === 0"
                    class="py-10 text-center text-sm text-muted-foreground"
                >
                    {{ t('No messages yet. Leave the first message.') }}
                </div>

                <div v-else class="flex flex-col gap-3">
                    <div
                        v-for="msg in props.messages"
                        :key="msg.id"
                        class="flex"
                        :class="msg.senderId === props.authUserId ? 'justify-end' : 'justify-start'"
                    >
                        <div
                            class="max-w-[80%] rounded-xl px-4 py-3"
                            :class="
                                msg.senderId === props.authUserId
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted/50 text-foreground'
                            "
                        >
                            <p class="whitespace-pre-wrap text-sm font-medium">
                                {{ msg.content }}
                            </p>
                            <p class="mt-2 text-[10px] font-bold uppercase tracking-tighter opacity-70">
                                {{ msg.createdAt }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <form
                class="rounded-xl border border-border bg-card p-5 shadow-sm"
                @submit.prevent="sendMessage"
            >
                <label class="block text-sm font-bold text-foreground">
                    {{ t('Message (optional)') }}
                </label>
                <textarea
                    v-model="content"
                    class="mt-2 min-h-[96px] w-full rounded-lg border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    :placeholder="t('Type your message...')"
                />
                <div class="mt-3 flex items-center justify-between gap-3">
                    <p v-if="error" class="text-sm text-destructive">
                        {{ error }}
                    </p>
                    <button
                        type="submit"
                        class="shrink-0 rounded-lg bg-primary px-5 py-2 text-sm font-bold text-primary-foreground transition-opacity hover:opacity-90 disabled:opacity-60"
                        :disabled="sending || !content.trim()"
                    >
                        {{ sending ? t('Submitting…') : t('Send message') }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

