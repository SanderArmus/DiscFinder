<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 64 80"
        :class="className"
        v-bind="$attrs"
    >
        <!-- Location pin: rounded head, pointed bottom -->
        <path
            d="M32 4 C50 4 58 20 58 32 C58 42 32 76 32 76 C32 76 6 42 6 32 C6 20 14 4 32 4 Z"
            fill="#4B5563"
            stroke="#6B7280"
            stroke-width="1.2"
            stroke-linejoin="round"
        />
        <!-- Green circle (disc) in pin head -->
        <circle cx="32" cy="28" r="20" fill="#2F7F6D" />
        <!-- Motion swoosh: curve from lower-left up to top-right -->
        <path
            d="M16 30 Q32 14 48 30"
            stroke="#E5E7EB"
            stroke-width="3.5"
            fill="none"
            stroke-linecap="round"
        />
        <!-- Disc golf basket: pole + rim + chains -->
        <line x1="32" y1="36" x2="32" y2="44" stroke="#FFFFFF" stroke-width="1.8" stroke-linecap="round" />
        <line x1="26" y1="40" x2="38" y2="40" stroke="#FFFFFF" stroke-width="1.2" stroke-linecap="round" />
        <line x1="27" y1="42" x2="37" y2="42" stroke="#FFFFFF" stroke-width="0.8" stroke-linecap="round" />
    </svg>
</template>
