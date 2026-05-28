<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import LocationMapPicker from '@/components/LocationMapPicker.vue';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

const t = useTranslations();

function colorListLabel(colors: string[]): string {
    if (!colors.length) {
        return '—';
    }

    return colors.map((c) => t(c)).join(', ');
}

type DiscCard = {
    id: number;
    ownerName: string;
    status: 'lost' | 'found';
    occurredAt: string;
    manufacturer: string | null;
    modelName: string | null;
    plasticType: string | null;
    condition: string | null;
    inscription: string | null;
    inscriptionName?: string | null;
    inscriptionNumber?: string | null;
    customDescription?: string | null;
    colors: string[];
    locationText: string;
    locationPin: { lat: number; lng: number } | null;
};

const props = defineProps<{
    match: {
        id: number;
        status: string;
        score: number | null;
    };
    lostDisc: DiscCard;
    foundDisc: DiscCard;
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('My Profile'), href: dashboard().url },
    { title: t('Match Details'), href: `/matches/${props.match.id}/details` },
]);

const lostPin = ref<{ lat: number; lng: number } | null>(props.lostDisc.locationPin);
const foundPin = ref<{ lat: number; lng: number } | null>(props.foundDisc.locationPin);

function discTitle(disc: DiscCard): string {
    return disc.modelName || disc.manufacturer || '—';
}

function statusBadgeClass(status: 'lost' | 'found'): string {
    return status === 'lost'
        ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
        : 'bg-primary/20 text-foreground dark:text-primary';
}
</script>

<template>
    <Head :title="t('Match Details')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-5xl px-4 py-8">
            <div class="mb-6 rounded-xl border border-border bg-card p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-black text-foreground">
                            {{ t('Match Details') }}
                        </h1>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ t('Pending') }}
                            <span v-if="props.match.status === 'confirmed'">• {{ t('Confirmed') }}</span>
                            <span v-else-if="props.match.status === 'handed_over'">• {{ t('Handed over') }}</span>
                            <span v-else-if="props.match.status && props.match.status !== 'pending'">• {{ props.match.status }}</span>
                            <span v-if="props.match.score != null">• {{ Math.round(props.match.score) }}% {{ t('Match') }}</span>
                        </p>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <Link
                            :href="`/matches/${props.match.id}`"
                            class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-primary-foreground transition-opacity hover:opacity-90"
                        >
                            {{ t('Chat') }}
                        </Link>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="truncate text-lg font-bold text-foreground">
                                <span
                                    class="mr-2 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold"
                                    :class="statusBadgeClass('lost')"
                                >
                                    {{ t('Lost') }}
                                </span>
                                {{ discTitle(props.lostDisc) }}
                            </h2>
                            <p class="mt-1 text-sm font-bold text-foreground">
                                {{ props.lostDisc.ownerName }}
                                <span class="text-xs font-medium text-muted-foreground"> • {{ props.lostDisc.occurredAt }}</span>
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ props.lostDisc.locationText }}
                            </p>
                        </div>
                        <Link
                            :href="`/discs/${props.lostDisc.id}`"
                            class="shrink-0 rounded-lg border border-input bg-muted/50 px-3 py-2 text-xs font-bold text-foreground transition-colors hover:bg-muted"
                        >
                            {{ t('View disc') }}
                        </Link>
                    </div>

                    <div class="mt-4">
                        <LocationMapPicker
                            v-model="lostPin"
                            height="240px"
                            :draggable="false"
                            :allowClickToSet="false"
                            :allowGeolocation="false"
                        />
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-2 text-sm text-muted-foreground">
                        <p><span class="font-bold text-foreground">{{ t('Manufacturer') }}:</span> {{ props.lostDisc.manufacturer || '—' }}</p>
                        <p><span class="font-bold text-foreground">{{ t('Disc Name') }}:</span> {{ props.lostDisc.modelName || '—' }}</p>
                        <p><span class="font-bold text-foreground">{{ t('Plastic Type') }}:</span> {{ props.lostDisc.plasticType || '—' }}</p>
                        <p><span class="font-bold text-foreground">{{ t('Condition') }}:</span> {{ props.lostDisc.condition || '—' }}</p>
                        <p><span class="font-bold text-foreground">{{ t('Color') }}:</span> {{ colorListLabel(props.lostDisc.colors) }}</p>
                        <p><span class="font-bold text-foreground">{{ t('Name on disc') }}:</span> {{ props.lostDisc.inscriptionName || '—' }}</p>
                        <p><span class="font-bold text-foreground">{{ t('Number on disc') }}:</span> {{ props.lostDisc.inscriptionNumber || '—' }}</p>
                        <p v-if="props.lostDisc.customDescription"><span class="font-bold text-foreground">{{ t('Custom description') }}:</span> {{ props.lostDisc.customDescription }}</p>
                    </div>
                </div>

                <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="truncate text-lg font-bold text-foreground">
                                <span
                                    class="mr-2 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold"
                                    :class="statusBadgeClass('found')"
                                >
                                    {{ t('Found') }}
                                </span>
                                {{ discTitle(props.foundDisc) }}
                            </h2>
                            <p class="mt-1 text-sm font-bold text-foreground">
                                {{ props.foundDisc.ownerName }}
                                <span class="text-xs font-medium text-muted-foreground"> • {{ props.foundDisc.occurredAt }}</span>
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ props.foundDisc.locationText }}
                            </p>
                        </div>
                        <Link
                            :href="`/discs/${props.foundDisc.id}`"
                            class="shrink-0 rounded-lg border border-input bg-muted/50 px-3 py-2 text-xs font-bold text-foreground transition-colors hover:bg-muted"
                        >
                            {{ t('View disc') }}
                        </Link>
                    </div>

                    <div class="mt-4">
                        <LocationMapPicker
                            v-model="foundPin"
                            height="240px"
                            :draggable="false"
                            :allowClickToSet="false"
                            :allowGeolocation="false"
                        />
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-2 text-sm text-muted-foreground">
                        <p><span class="font-bold text-foreground">{{ t('Manufacturer') }}:</span> {{ props.foundDisc.manufacturer || '—' }}</p>
                        <p><span class="font-bold text-foreground">{{ t('Disc Name') }}:</span> {{ props.foundDisc.modelName || '—' }}</p>
                        <p><span class="font-bold text-foreground">{{ t('Plastic Type') }}:</span> {{ props.foundDisc.plasticType || '—' }}</p>
                        <p><span class="font-bold text-foreground">{{ t('Condition') }}:</span> {{ props.foundDisc.condition || '—' }}</p>
                        <p><span class="font-bold text-foreground">{{ t('Color') }}:</span> {{ colorListLabel(props.foundDisc.colors) }}</p>
                        <p><span class="font-bold text-foreground">{{ t('Name on disc') }}:</span> {{ props.foundDisc.inscriptionName || '—' }}</p>
                        <p><span class="font-bold text-foreground">{{ t('Number on disc') }}:</span> {{ props.foundDisc.inscriptionNumber || '—' }}</p>
                        <p v-if="props.foundDisc.customDescription"><span class="font-bold text-foreground">{{ t('Custom description') }}:</span> {{ props.foundDisc.customDescription }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

