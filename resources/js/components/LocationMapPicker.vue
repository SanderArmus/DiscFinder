<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = withDefaults(
    defineProps<{
        modelValue?: { lat: number; lng: number } | null;
        defaultCenter?: [number, number];
        defaultZoom?: number;
        height?: string;
    }>(),
    {
        modelValue: null,
        defaultCenter: () => [59.437, 24.7536],
        defaultZoom: 12,
        height: '320px',
    }
);

const emit = defineEmits<{
    'update:modelValue': [value: { lat: number; lng: number } | null];
}>();

const mapContainer = ref<HTMLElement | null>(null);
let map: L.Map | null = null;
let marker: L.Marker | null = null;

const defaultIcon = L.icon({
    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
    iconRetinaUrl:
        'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
});

function updateMarker(lat: number, lng: number): void {
    if (!map) return;
    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        marker = L.marker([lat, lng], {
            icon: defaultIcon,
            draggable: true,
        }).addTo(map);
        marker.on('dragend', () => {
            const pos = marker!.getLatLng();
            emit('update:modelValue', { lat: pos.lat, lng: pos.lng });
        });
    }
}

function initMap(): void {
    if (!mapContainer.value) return;

    const [lat, lng] =
        props.modelValue != null
            ? [props.modelValue.lat, props.modelValue.lng]
            : props.defaultCenter;

    map = L.map(mapContainer.value).setView([lat, lng], props.defaultZoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution:
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

    map.on('click', (e: L.LeafletMouseEvent) => {
        const { lat, lng } = e.latlng;
        updateMarker(lat, lng);
        emit('update:modelValue', { lat, lng });
    });

    if (props.modelValue != null) {
        updateMarker(props.modelValue.lat, props.modelValue.lng);
    }
}

function destroyMap(): void {
    if (marker) {
        marker.remove();
        marker = null;
    }
    if (map) {
        map.remove();
        map = null;
    }
}

watch(
    () => props.modelValue,
    (val) => {
        if (map && val != null) {
            updateMarker(val.lat, val.lng);
            map.setView([val.lat, val.lng], map.getZoom());
        } else if (map && marker) {
            marker.remove();
            marker = null;
        }
    }
);

onMounted(() => {
    initMap();

    if (!props.modelValue && navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                if (!map) {
                    return;
                }

                const { latitude, longitude } = position.coords;
                map.setView([latitude, longitude], props.defaultZoom);
                updateMarker(latitude, longitude);
                emit('update:modelValue', { lat: latitude, lng: longitude });
            },
            () => {
                // Ignore errors and keep default center
            }
        );
    }
});

onUnmounted(() => {
    destroyMap();
});
</script>

<template>
    <div
        ref="mapContainer"
        class="w-full overflow-hidden rounded-lg border border-input bg-muted/30"
        :style="{ height }"
    />
</template>
