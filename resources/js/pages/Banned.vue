<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import TextLink from '@/components/TextLink.vue';
import { useTranslations } from '@/composables/useTranslations';
import { logout } from '@/routes';

const t = useTranslations();

const props = defineProps<{
    reason: string | null;
    bannedAt: string | null;
}>();

const breadcrumbs = computed(() => [
    { title: t('My Profile'), href: '/dashboard' },
    { title: t('Banned'), href: '/banned' },
]);
</script>

<template>
    <Head :title="t('Banned')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-2xl px-4 py-10">
            <div class="rounded-xl border border-border bg-card p-6 shadow-sm">
                <h1 class="text-2xl font-bold text-foreground">
                    {{ t('Account banned') }}
                </h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ t('Your account has been restricted. If you think this is a mistake, contact support.') }}
                </p>

                <div class="mt-5 rounded-lg border border-border bg-muted/20 p-4 text-sm">
                    <div v-if="props.bannedAt" class="text-muted-foreground">
                        <span class="font-bold text-foreground">{{ t('Banned at') }}:</span>
                        {{ props.bannedAt }}
                    </div>
                    <div class="mt-2 text-muted-foreground">
                        <span class="font-bold text-foreground">{{ t('Reason') }}:</span>
                        {{ props.reason || '—' }}
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap gap-2">
                    <Button as-child variant="outline">
                        <TextLink :href="logout()" as="button">
                            {{ t('Log out') }}
                        </TextLink>
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

