<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Label } from '@/components/ui/label';
import LocationMapPicker from '@/components/LocationMapPicker.vue';
import { useTranslations } from '@/composables/useTranslations';
import { type BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';

const t = useTranslations();

const NOMINATIM_HEADERS = {
    Accept: 'application/json',
    'User-Agent': 'DiscFinder/1.0 (contact@discfinder.local)',
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('Found Discs'), href: '/found-discs' },
];

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

const form = ref({
    location: '',
    datetime: '',
    manufacturer: '',
    name: '',
    plastic: '',
    customDisc: false,
    selectedColors: [] as string[],
    condition: '',
    inscription: '',
});

const colorSectionOpen = ref(false);

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

const locationPin = ref<{ lat: number; lng: number } | null>(null);
const locationSearching = ref(false);
const locationSearchError = ref('');

const inputClass =
    'w-full rounded-lg border border-input bg-muted/50 px-3 py-2 text-foreground shadow-xs outline-none transition-colors placeholder:text-muted-foreground focus:border-ring focus-visible:ring-2 focus-visible:ring-ring/20 dark:bg-muted/30';
const selectClass =
    'w-full rounded-lg border border-input bg-muted/50 px-3 py-2 text-foreground shadow-xs outline-none transition-colors focus:border-ring focus-visible:ring-2 focus-visible:ring-ring/20 dark:bg-muted/30';

const submitting = ref(false);

function submit(): void {
    submitting.value = true;
    router.post('/found-discs', {
        ...form.value,
        latitude: locationPin.value?.lat ?? null,
        longitude: locationPin.value?.lng ?? null,
    }, {
        preserveScroll: true,
        onFinish: () => { submitting.value = false; },
    });
}

let reverseGeocodeTimeout: ReturnType<typeof setTimeout> | null = null;

watch(
    () => locationPin.value,
    (value) => {
        if (!value) return;

        if (reverseGeocodeTimeout) clearTimeout(reverseGeocodeTimeout);
        reverseGeocodeTimeout = setTimeout(async () => {
            reverseGeocodeTimeout = null;
            try {
                const response = await fetch(
                    `https://nominatim.openstreetmap.org/reverse?lat=${value.lat}&lon=${value.lng}&format=jsonv2`,
                    { headers: NOMINATIM_HEADERS }
                );
                if (!response.ok) return;
                const data = (await response.json()) as {
                    display_name?: string;
                };
                if (data.display_name) {
                    form.value.location = data.display_name;
                }
            } catch (err) {
                console.error('Reverse geocode failed', err);
            }
        }, 500);
    },
    { deep: true }
);

async function searchLocationOnMap(): Promise<void> {
    const query = form.value.location?.trim();
    if (!query) return;

    locationSearchError.value = '';
    locationSearching.value = true;
    try {
        const response = await fetch(
            `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(query)}&format=json&limit=1`,
            { headers: NOMINATIM_HEADERS }
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
        locationPin.value = {
            lat: Number(first.lat),
            lng: Number(first.lon),
        };
        if (first.display_name) {
            form.value.location = first.display_name;
        }
    } catch (err) {
        console.error('Search failed', err);
        locationSearchError.value = t('Search failed. Try another address or place.');
    } finally {
        locationSearching.value = false;
    }
}
</script>

<template>
    <Head :title="t('Report a Found Disc')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col">
            <main class="flex-1 px-6 py-12">
                <div class="mx-auto max-w-3xl">
                    <div class="mb-10 text-center">
                        <h2
                            class="mb-2 text-3xl font-extrabold tracking-tight text-foreground"
                        >
                            {{ t('Report a Found Disc') }}
                        </h2>
                        <p
                            class="mx-auto max-w-md text-muted-foreground"
                        >
                            {{ t('Help a fellow player reunite with their plastic.') }}
                        </p>
                    </div>

                    <div
                        class="overflow-hidden rounded-xl border border-border bg-card shadow-sm"
                    >
                        <div class="p-8 md:p-10">
                            <form
                                @submit.prevent="submit"
                                class="grid grid-cols-1 gap-6 md:grid-cols-2"
                            >
                                <div class="space-y-2 md:col-span-2">
                                    <Label for="location">{{ t('Location where found') }}</Label>
                                    <p class="text-sm text-muted-foreground">
                                        {{ t('Click the map to place a pin where you found the disc. You can drag the pin to adjust.') }}
                                    </p>
                                    <LocationMapPicker
                                        v-model="locationPin"
                                        class="mt-2"
                                        :default-center="[59.437, 24.7536]"
                                        :default-zoom="11"
                                        height="280px"
                                    />
                                    <div class="mt-2 flex gap-2">
                                        <input
                                            id="location"
                                            v-model="form.location"
                                            type="text"
                                            :class="inputClass"
                                            class="flex-1"
                                            :placeholder="t('Address, course name, or landmark — type and search to move the pin')"
                                            @keydown.enter.prevent="searchLocationOnMap()"
                                        />
                                        <button
                                            type="button"
                                            class="shrink-0 rounded-lg border border-input bg-muted/50 px-4 py-2 text-sm font-medium text-foreground transition-colors hover:bg-muted dark:bg-muted/30"
                                            :disabled="locationSearching || !form.location.trim()"
                                            @click="searchLocationOnMap()"
                                        >
                                            {{ locationSearching ? t('Searching…') : t('Search on map') }}
                                        </button>
                                    </div>
                                    <p
                                        v-if="locationSearchError"
                                        class="mt-1 text-sm text-destructive"
                                    >
                                        {{ locationSearchError }}
                                    </p>
                                </div>

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
                                    <select
                                        id="manufacturer"
                                        v-model="form.manufacturer"
                                        :class="selectClass"
                                    >
                                        <option disabled value="">
                                            {{ t('Select manufacturer') }}
                                        </option>
                                        <option value="discraft">Discraft</option>
                                        <option value="innova">Innova</option>
                                        <option value="mvp">MVP</option>
                                        <option value="dynamic">Dynamic Discs</option>
                                        <option value="other">Other</option>
                                    </select>
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
                                        <summary
                                            class="flex cursor-pointer list-none items-center justify-between gap-2 px-4 py-3 text-sm font-medium text-foreground focus-visible:outline focus-visible:ring-2 focus-visible:ring-ring"
                                        >
                                            <span class="truncate text-left">
                                                {{ selectedColorLabels() || t('Select color(s)') }}
                                            </span>
                                            <span
                                                class="shrink-0 transition-transform"
                                                :class="colorSectionOpen ? 'rotate-180' : ''"
                                            >
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </span>
                                        </summary>
                                        <div class="border-t border-border px-4 py-3">
                                            <p class="mb-2 text-xs text-muted-foreground">
                                                {{ t('Select one or more colors. Click again to deselect.') }}
                                            </p>
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
                                    <label class="flex cursor-pointer items-start gap-2 pt-1">
                                        <input
                                            v-model="form.customDisc"
                                            type="checkbox"
                                            class="mt-0.5 h-4 w-4 rounded border-input"
                                        />
                                        <span class="text-sm text-foreground">
                                            <span class="font-medium">{{ t('Custom disc') }}</span>
                                            <span class="text-muted-foreground"> — {{ t('Custom disc hint') }}</span>
                                        </span>
                                    </label>
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

                                <div class="space-y-2 md:col-span-2">
                                    <Label for="inscription">{{ t('Name/Number written on disc') }}</Label>
                                    <input
                                        id="inscription"
                                        v-model="form.inscription"
                                        type="text"
                                        :class="inputClass"
                                    />
                                </div>

                                <div
                                    class="mt-4 flex flex-col items-center gap-4 md:col-span-2 sm:flex-row"
                                >
                                    <button
                                        type="submit"
                                        class="w-full rounded-lg bg-primary py-4 font-bold text-primary-foreground shadow-md shadow-primary/20 transition-all hover:opacity-90 sm:flex-1 disabled:opacity-70"
                                        :disabled="submitting"
                                    >
                                        {{ submitting ? t('Submitting…') : t('Submit Found Report') }}
                                    </button>

                                    <Link
                                        :href="dashboard()"
                                        class="w-full px-10 py-4 text-center font-medium text-muted-foreground transition-colors hover:text-foreground sm:w-auto"
                                    >
                                        {{ t('Cancel') }}
                                    </Link>
                                </div>
                            </form>
                        </div>
                    </div>

                    <p
                        class="mt-8 text-center text-xs font-medium uppercase tracking-widest text-muted-foreground"
                    >
                        © 2023 DiscFinder • {{ t('Helping discs find their way home.') }}
                    </p>
                </div>
            </main>
        </div>
    </AppLayout>
</template>
