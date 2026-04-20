<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import LocationMapPicker from '@/components/LocationMapPicker.vue';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

const t = useTranslations();

function colorListLabel(colors: string[]): string {
    if (!colors.length) {
        return '—';
    }

    return colors.map((c) => t(c)).join(', ');
}

type PossibleMatch = {
    id: number;
    name: string;
    confidence: number;
    location: string;
    date: string;
};

const props = defineProps<{
    disc: {
        id: number;
        status: 'lost' | 'found';
        name: string;
        brand: string;
        manufacturer: string | null;
        modelName: string | null;
        plasticType: string | null;
        condition: string | null;
        inscription: string | null;
        occurredAt: string;
        active: boolean;
        expiresAt?: string | null;
        matchLifecycle: string | null;
        colorNames: string[];
        locationText: string;
        locationPin: { lat: number; lng: number } | null;
    };
    possibleMatches: PossibleMatch[];
    canEdit: boolean;
    canDelete: boolean;
    canRenew?: boolean;
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('My Profile'), href: dashboard().url },
    { title: props.disc.name, href: `/discs/${props.disc.id}` },
]);

function statusLabel(disc: typeof props.disc): string {
    if (disc.matchLifecycle === 'confirmed') {
        return t('Confirmed');
    }

    if (disc.matchLifecycle === 'handed_over') {
        return t('Handed over');
    }

    return disc.status === 'lost' ? t('Lost') : t('Found');
}

const NOMINATIM_HEADERS = {
    Accept: 'application/json',
    'User-Agent': 'Discivo/1.0 (contact@discivo.local)',
};

const COLOR_PRESETS = [
    { id: 'red', hex: '#dc2626', labelKey: 'Red' },
    { id: 'blue', hex: '#2563eb', labelKey: 'Blue' },
    { id: 'green', hex: '#16a34a', labelKey: 'Green' },
    { id: 'yellow', hex: '#eab308', labelKey: 'Yellow' },
    { id: 'orange', hex: '#ea580c', labelKey: 'Orange' },
    { id: 'pink', hex: '#db2777', labelKey: 'Pink' },
    { id: 'purple', hex: '#7c3aed', labelKey: 'Purple' },
    { id: 'white', hex: '#ffffff', labelKey: 'White' },
    { id: 'black', hex: '#1f2937', labelKey: 'Black' },
    { id: 'clear', hex: 'transparent', labelKey: 'Clear / Transparent' },
] as const;

const NAME_TO_HEX: Record<string, string> = {
    Red: '#dc2626',
    Blue: '#2563eb',
    Green: '#16a34a',
    Yellow: '#eab308',
    Orange: '#ea580c',
    Pink: '#db2777',
    Purple: '#7c3aed',
    White: '#ffffff',
    Black: '#1f2937',
    'Clear / Transparent': 'transparent',
};

function colorNameToHex(name: string): string | null {
    return NAME_TO_HEX[name] ?? null;
}

const colorSectionOpen = ref(false);

const locationPin = ref<{ lat: number; lng: number } | null>(props.disc.locationPin);
const locationSearching = ref(false);
const locationSearchError = ref('');

const inputClass =
    'w-full rounded-lg border border-input bg-muted/50 px-3 py-2 text-foreground shadow-xs outline-none transition-colors placeholder:text-muted-foreground focus:border-ring focus-visible:ring-2 focus-visible:ring-ring/20 dark:bg-muted/30';
const selectClass = inputClass;

const form = ref({
    location: props.disc.locationText ?? '',
    datetime: props.disc.occurredAt ?? '',
    manufacturer: props.disc.manufacturer ?? '',
    name: props.disc.modelName ?? '',
    plastic: props.disc.plasticType ?? '',
    selectedColors: props.disc.colorNames
        .map(colorNameToHex)
        .filter((v): v is string => v !== null),
    condition: props.disc.condition ?? '',
    inscription: props.disc.inscription ?? '',
});

const editing = ref(false);

function resetFormToDisc(): void {
    locationPin.value = props.disc.locationPin;

    form.value = {
        location: props.disc.locationText ?? '',
        datetime: props.disc.occurredAt ?? '',
        manufacturer: props.disc.manufacturer ?? '',
        name: props.disc.modelName ?? '',
        plastic: props.disc.plasticType ?? '',
        selectedColors: props.disc.colorNames
            .map(colorNameToHex)
            .filter((v): v is string => v !== null),
        condition: props.disc.condition ?? '',
        inscription: props.disc.inscription ?? '',
    };

    colorSectionOpen.value = false;

    locationSearching.value = false;
    locationSearchError.value = '';
    reverseGeocodeInitialized.value = false;
}

function togglePresetColor(hex: string): void {
    const arr = form.value.selectedColors;
    const i = arr.indexOf(hex);
    if (i === -1) arr.push(hex);
    else arr.splice(i, 1);
}

function isPresetSelected(hex: string): boolean {
    return form.value.selectedColors.includes(hex);
}

function selectedColorLabels(): string {
    if (form.value.selectedColors.length === 0) return '';

    return form.value.selectedColors
        .map((hex) => {
            const preset = COLOR_PRESETS.find((p) => p.hex === hex);
            return preset ? t(preset.labelKey) : hex;
        })
        .join(', ');
}

let reverseGeocodeTimeout: ReturnType<typeof setTimeout> | null = null;
const reverseGeocodeInitialized = ref(false);

watch(
    () => locationPin.value,
    (value) => {
        if (!props.canEdit || !editing.value) return;
        if (!value) return;

        if (!reverseGeocodeInitialized.value) {
            reverseGeocodeInitialized.value = true;
            return;
        }

        if (reverseGeocodeTimeout) clearTimeout(reverseGeocodeTimeout);
        reverseGeocodeTimeout = setTimeout(async () => {
            reverseGeocodeTimeout = null;
            try {
                const response = await fetch(
                    `https://nominatim.openstreetmap.org/reverse?lat=${value.lat}&lon=${value.lng}&format=jsonv2`,
                    { headers: NOMINATIM_HEADERS },
                );
                if (!response.ok) return;

                const data = (await response.json()) as { display_name?: string };
                if (data.display_name) form.value.location = data.display_name;
            } catch (err) {
                // Ignore errors and keep whatever user typed.
                console.error('Reverse geocode failed', err);
            }
        }, 500);
    },
    { deep: true },
);

async function searchLocationOnMap(): Promise<void> {
    if (!props.canEdit || !editing.value) return;

    const query = form.value.location?.trim();
    if (!query) return;

    locationSearchError.value = '';
    locationSearching.value = true;

    try {
        const response = await fetch(
            `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(query)}&format=json&limit=1`,
            { headers: NOMINATIM_HEADERS },
        );

        if (!response.ok) {
            locationSearchError.value = t('Search failed. Try another address or place.');
            return;
        }

        const data = (await response.json()) as Array<{
            lat: string;
            lon: string;
            display_name?: string;
        }>;

        if (data.length === 0) {
            locationSearchError.value = t('No results found. Try another address or place.');
            return;
        }

        const first = data[0];
        locationPin.value = { lat: Number(first.lat), lng: Number(first.lon) };
        if (first.display_name) form.value.location = first.display_name;
    } catch (err) {
        console.error('Search failed', err);
        locationSearchError.value = t('Search failed. Try another address or place.');
    } finally {
        locationSearching.value = false;
    }
}

function submit(): void {
    if (!props.canEdit || !editing.value) return;

    router.post(
        `/discs/${props.disc.id}`,
        {
            ...form.value,
            latitude: locationPin.value?.lat ?? null,
            longitude: locationPin.value?.lng ?? null,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                editing.value = false;
            },
            onError: () => undefined,
        },
    );
}

function deleteDisc(): void {
    if (!props.canDelete) return;

    // eslint-disable-next-line no-alert
    const ok = window.confirm(t('Are you sure?'));
    if (!ok) return;

    router.delete(`/discs/${props.disc.id}`, {
        preserveScroll: true,
    });
}

function renewDisc(): void {
    if (!props.canRenew) return;

    router.post(`/discs/${props.disc.id}/renew`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="t('Disc Details')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-3xl px-4 py-8">
            <div
                v-if="props.canRenew && !props.disc.active"
                class="mb-6 rounded-xl border border-border bg-muted/20 p-4 shadow-sm"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold text-foreground">
                            {{ t('This disc is inactive.') }}
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ t('You can renew it for 90 days with one click.') }}
                        </p>
                        <p v-if="props.disc.expiresAt" class="mt-1 text-sm text-muted-foreground">
                            <span class="font-bold text-foreground">{{ t('Expired at') }}:</span>
                            {{ props.disc.expiresAt }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="h-10 rounded-lg bg-primary px-4 text-sm font-bold text-primary-foreground hover:opacity-90"
                        @click="renewDisc"
                    >
                        {{ t('Renew for 90 days') }}
                    </button>
                </div>
            </div>
            <div class="mb-6 rounded-xl border border-border bg-card p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-black text-foreground">
                            {{ props.disc.name }}
                        </h1>
                        <p class="mt-1 text-sm text-muted-foreground">
                            <span
                                class="mr-2 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold"
                                :class="
                                    props.disc.status === 'lost'
                                        ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                        : 'bg-primary/20 text-foreground dark:text-primary'
                                "
                            >
                                {{ props.disc.status === 'lost' ? t('Lost') : t('Found') }}
                            </span>
                            <span class="font-bold">{{ props.disc.brand }}</span>
                        </p>
                        <p class="mt-2 text-sm text-muted-foreground">
                            {{ statusLabel(props.disc) }}
                        </p>
                        <p v-if="props.disc.expiresAt" class="mt-2 text-sm text-muted-foreground">
                            <span class="font-bold text-foreground">{{ t('Expires at') }}:</span>
                            {{ props.disc.expiresAt }}
                        </p>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <span
                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold"
                            :class="
                                props.disc.matchLifecycle === 'handed_over'
                                    ? 'bg-muted text-foreground/70 dark:bg-muted/50'
                                    : props.disc.matchLifecycle === 'confirmed'
                                      ? 'bg-primary/20 text-primary dark:text-primary'
                                      : props.disc.status === 'lost'
                                        ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                        : 'bg-primary/20 text-foreground dark:text-primary'
                            "
                        >
                            {{ statusLabel(props.disc) }}
                        </span>
                        <button
                            v-if="props.canEdit && !editing"
                            type="button"
                            class="w-36 rounded-lg bg-primary px-4 py-2 text-center text-sm font-bold text-primary-foreground transition-opacity hover:opacity-90"
                            @click="editing = true"
                        >
                            {{ t('Edit Disc') }}
                        </button>
                        <button
                            v-if="props.canEdit && editing"
                            type="button"
                            class="w-36 rounded-lg bg-muted px-4 py-2 text-center text-sm font-bold text-foreground/70 transition-opacity hover:opacity-90"
                            @click="
                                editing = false;
                                resetFormToDisc();
                            "
                        >
                            {{ t('Cancel') }}
                        </button>
                        <button
                            v-if="props.canDelete"
                            type="button"
                            class="w-36 rounded-lg bg-destructive px-4 py-2 text-center text-sm font-bold text-white transition-opacity hover:opacity-90"
                            @click="deleteDisc"
                        >
                            {{ t('Delete Disc') }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-xl border border-border bg-card p-5 shadow-sm lg:col-span-1">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-lg font-bold text-foreground">
                            {{ t('Location') }}
                        </h2>
                    </div>

                    <div class="space-y-3">
                        <Label for="location">{{ t('Location') }}</Label>
                        <div class="flex gap-2">
                            <input
                                id="location"
                                v-model="form.location"
                                type="text"
                                class="flex-1 rounded-lg border border-input bg-muted/50 px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                :disabled="!props.canEdit"
                                :placeholder="t('Address, course name, or landmark — type and search to move the pin')"
                                @keydown.enter.prevent="searchLocationOnMap()"
                            />
                            <button
                                type="button"
                                class="shrink-0 rounded-lg border border-input bg-muted/50 px-4 py-2 text-sm font-medium text-foreground transition-colors hover:bg-muted dark:bg-muted/30 disabled:opacity-60"
                                :disabled="!props.canEdit || locationSearching || !form.location.trim()"
                                @click="searchLocationOnMap()"
                            >
                                {{ locationSearching ? t('Searching…') : t('Search on map') }}
                            </button>
                        </div>

                        <p v-if="locationSearchError" class="text-sm text-destructive">
                            {{ locationSearchError }}
                        </p>

                        <LocationMapPicker
                            v-model="locationPin"
                            :height="'280px'"
                            :default-center="[59.437, 24.7536]"
                            :default-zoom="11"
                            :draggable="props.canEdit && editing"
                            :allowClickToSet="props.canEdit && editing"
                            :allowGeolocation="props.canEdit && editing"
                        />
                    </div>
                </div>

                <div class="rounded-xl border border-border bg-card p-5 shadow-sm lg:col-span-1">
                    <h2 class="mb-4 text-lg font-bold text-foreground">
                        {{ t('Disc Information') }}
                    </h2>

                    <form
                        v-if="props.canEdit && editing"
                        @submit.prevent="submit"
                        class="grid gap-4"
                    >
                        <div class="space-y-2">
                            <Label for="datetime">{{ t('Date & Time') }}</Label>
                            <input
                                id="datetime"
                                v-model="form.datetime"
                                type="datetime-local"
                                :class="inputClass"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="manufacturer">{{ t('Manufacturer') }}</Label>
                            <input
                                id="manufacturer"
                                v-model="form.manufacturer"
                                type="text"
                                :class="inputClass"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="name">{{ t('Disc Name') }}</Label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                :class="inputClass"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="plastic">{{ t('Plastic Type') }}</Label>
                            <input
                                id="plastic"
                                v-model="form.plastic"
                                type="text"
                                :class="inputClass"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label>{{ t('Color') }}</Label>
                            <details
                                class="rounded-lg border border-border bg-muted/20"
                                :open="colorSectionOpen"
                                @toggle="colorSectionOpen = ($event.target as HTMLDetailsElement).open"
                            >
                                <summary class="cursor-pointer list-none px-4 py-3 text-sm font-medium text-foreground">
                                    <span class="truncate">
                                        {{ selectedColorLabels() || t('Select color(s)') }}
                                    </span>
                                </summary>
                                <div class="border-t border-border px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            v-for="preset in COLOR_PRESETS"
                                            :key="preset.id"
                                            type="button"
                                            class="flex items-center gap-1.5 rounded-lg border-2 px-3 py-2 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                            :class="
                                                isPresetSelected(preset.hex)
                                                    ? 'border-primary bg-primary/10 text-primary'
                                                    : 'border-input bg-muted/50 text-foreground hover:border-muted-foreground/40'
                                            "
                                            :title="t(preset.labelKey)"
                                            @click="togglePresetColor(preset.hex)"
                                        >
                                            <span
                                                v-if="preset.hex !== 'transparent'"
                                                class="h-5 w-5 shrink-0 rounded-full border border-border shadow-inner"
                                                :style="{ backgroundColor: preset.hex }"
                                            />
                                            <span
                                                v-else
                                                class="h-5 w-5 shrink-0 rounded-full border-2 border-dashed border-muted-foreground/50 bg-muted/30"
                                                :title="t('Clear / Transparent')"
                                            />
                                            {{ t(preset.labelKey) }}
                                        </button>
                                    </div>
                                </div>
                            </details>
                        </div>

                        <div class="space-y-2">
                            <Label for="condition">{{ t('Condition') }}</Label>
                            <select
                                id="condition"
                                v-model="form.condition"
                                :class="selectClass"
                            >
                                <option disabled value="">
                                    {{ t('Select condition') }}
                                </option>
                                <option value="new">{{ t('New / Like New') }}</option>
                                <option value="good">{{ t('Used - Good') }}</option>
                                <option value="worn">{{ t('Worn / Beat-in') }}</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <Label for="inscription">{{ t('Name/Number written on disc') }}</Label>
                            <input
                                id="inscription"
                                v-model="form.inscription"
                                type="text"
                                :class="inputClass"
                            />
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <Link
                                :href="dashboard()"
                                class="rounded-lg px-4 py-2 text-sm font-bold text-muted-foreground transition-colors hover:text-primary"
                            >
                                {{ t('Cancel') }}
                            </Link>
                            <button
                                type="submit"
                                class="rounded-lg bg-primary px-6 py-2 text-sm font-bold text-primary-foreground transition-opacity hover:opacity-90"
                            >
                                {{ t('Save Changes') }}
                            </button>
                        </div>
                    </form>

                    <div v-else class="space-y-3 text-sm text-muted-foreground">
                        <p><span class="font-bold text-foreground">{{ t('Date & Time') }}:</span> {{ props.disc.occurredAt || '—' }}</p>
                        <p><span class="font-bold text-foreground">{{ t('Manufacturer') }}:</span> {{ props.disc.manufacturer || '—' }}</p>
                        <p><span class="font-bold text-foreground">{{ t('Disc Name') }}:</span> {{ props.disc.modelName || '—' }}</p>
                        <p><span class="font-bold text-foreground">{{ t('Plastic Type') }}:</span> {{ props.disc.plasticType || '—' }}</p>
                        <p><span class="font-bold text-foreground">{{ t('Condition') }}:</span> {{ props.disc.condition || '—' }}</p>
                        <p><span class="font-bold text-foreground">{{ t('Name/Number written on disc') }}:</span> {{ props.disc.inscription || '—' }}</p>
                        <p><span class="font-bold text-foreground">{{ t('Color') }}:</span> {{ colorListLabel(props.disc.colorNames) }}</p>
                    </div>
                </div>
            </div>

            <div v-if="props.canEdit" class="mt-6 space-y-4">
                <div class="flex items-center justify-between px-1">
                    <h2 class="text-xl font-bold text-foreground">
                        {{ t('Potential Matches') }}
                    </h2>
                    <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                        {{ props.possibleMatches.length }} {{ t('Items') }}
                    </span>
                </div>

                <div class="space-y-3">
                    <div
                        v-if="props.possibleMatches.length === 0"
                        class="rounded-xl border border-border bg-card p-6 text-center text-sm text-muted-foreground shadow-sm"
                    >
                        {{ t('No potential matches right now. Try reporting another disc.') }}
                    </div>

                    <div
                        v-for="m in props.possibleMatches"
                        :key="m.id"
                        class="rounded-xl border border-sidebar-border bg-card p-5 shadow-sm"
                    >
                        <div class="mb-2 flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate text-sm font-bold text-foreground">
                                    {{ m.name }}
                                </h3>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ m.date }} • {{ m.location }}
                                </p>
                            </div>
                            <span
                                class="rounded px-2 py-1 text-xs font-bold"
                                :class="m.confidence > 90 ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'"
                            >
                                {{ m.confidence }}% {{ t('Match') }}
                            </span>
                        </div>

                        <div>
                            <Link
                                :href="`/matches/${m.id}`"
                                class="mt-2 inline-flex w-full items-center justify-center rounded bg-primary py-2 text-xs font-bold text-primary-foreground transition-opacity hover:opacity-90"
                            >
                                {{ t('Leave a message') }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

