<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard } from '@/routes';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { computed, ref } from 'vue';

const t = useTranslations();
const page = usePage();

const breadcrumbs = [
    { title: t('My Profile'), href: dashboard().url },
    { title: t('Help'), href: '/help' },
];

const isOpen = ref(false);
const content = ref('');
const isSending = ref(false);

const errors = computed(() => (page.props as { errors?: Record<string, string> }).errors ?? {});
const successMessage = computed(
    () => (page.props as { flash?: { success?: string } }).flash?.success,
);

function sendToAdmin(): void {
    isSending.value = true;

    router.post(
        '/help/message-to-admin',
        { content: content.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                content.value = '';
                isOpen.value = false;
            },
            onFinish: () => {
                isSending.value = false;
            },
        },
    );
}
</script>

<template>
    <Head :title="t('Help')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-3xl px-4 py-8">
            <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
                <h1 class="text-2xl font-bold text-foreground">
                    {{ t('Help') }}
                </h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ t('Help intro') }}
                </p>

                <div class="mt-6 space-y-6">
                    <section>
                        <h2 class="text-lg font-bold text-foreground">
                            {{ t('How it works') }}
                        </h2>
                        <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-foreground">
                            <li>{{ t('Help step 1') }}</li>
                            <li>{{ t('Help step 2') }}</li>
                            <li>{{ t('Help step 3') }}</li>
                            <li>{{ t('Help step 4') }}</li>
                        </ol>
                    </section>

                    <section>
                        <h2 class="text-lg font-bold text-foreground">
                            {{ t('Messaging') }}
                        </h2>
                        <ul class="mt-3 list-disc space-y-2 pl-5 text-sm text-foreground">
                            <li>{{ t('Help messaging 1') }}</li>
                            <li>{{ t('Help messaging 2') }}</li>
                            <li>{{ t('Help messaging 3') }}</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-lg font-bold text-foreground">
                            {{ t('Confirm & handover') }}
                        </h2>
                        <ul class="mt-3 list-disc space-y-2 pl-5 text-sm text-foreground">
                            <li>
                                <span class="font-bold">{{ t('Confirm match') }}:</span>
                                {{ t('Help confirm 1') }}
                            </li>
                            <li>
                                <span class="font-bold">{{ t('Handed over') }}:</span>
                                {{ t('Help handover 1') }}
                            </li>
                            <li>
                                <span class="font-bold">{{ t('Reject') }}:</span>
                                {{ t('Help reject 1') }}
                            </li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-lg font-bold text-foreground">
                            {{ t('Disc activity') }}
                        </h2>
                        <ul class="mt-3 list-disc space-y-2 pl-5 text-sm text-foreground">
                            <li>{{ t('Help activity 1') }}</li>
                            <li>{{ t('Help activity 2') }}</li>
                            <li>{{ t('Help activity 3') }}</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-lg font-bold text-foreground">
                            {{ t('Tips') }}
                        </h2>
                        <ul class="mt-3 list-disc space-y-2 pl-5 text-sm text-foreground">
                            <li>{{ t('Help tip 1') }}</li>
                            <li>{{ t('Help tip 2') }}</li>
                            <li>{{ t('Help tip 3') }}</li>
                        </ul>
                    </section>

                    <section class="rounded-lg border border-border bg-background/50 p-4">
                        <p class="text-sm text-foreground">
                            {{ t('Need help?') }}
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ t('Help contact') }}
                        </p>
                        <div v-if="successMessage" class="mt-3 text-sm font-medium text-primary">
                            {{ successMessage }}
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <Button class="h-9 rounded-lg" @click="isOpen = true">
                                {{ t('Message to admin') }}
                            </Button>
                        </div>

                        <Dialog :open="isOpen" @update:open="isOpen = $event">
                            <DialogContent class="sm:max-w-lg">
                                <DialogHeader>
                                    <DialogTitle>{{ t('Message to admin') }}</DialogTitle>
                                    <DialogDescription>
                                        {{ t('Help admin message hint') }}
                                    </DialogDescription>
                                </DialogHeader>

                                <div class="mt-3 space-y-2">
                                    <textarea
                                        v-model="content"
                                        rows="5"
                                        class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground shadow-xs outline-none transition-colors placeholder:text-muted-foreground focus:border-ring focus-visible:ring-2 focus-visible:ring-ring/20 dark:bg-muted/30"
                                        :placeholder="t('Write your message')"
                                    />
                                    <p v-if="errors.content" class="text-sm text-destructive">
                                        {{ errors.content }}
                                    </p>
                                </div>

                                <div class="mt-4 flex items-center justify-end gap-2">
                                    <Button variant="outline" @click="isOpen = false">
                                        {{ t('Cancel') }}
                                    </Button>
                                    <Button :disabled="isSending || content.trim() === ''" @click="sendToAdmin">
                                        {{ isSending ? t('Sending...') : t('Send') }}
                                    </Button>
                                </div>
                            </DialogContent>
                        </Dialog>
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

