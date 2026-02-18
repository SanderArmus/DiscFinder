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
        viewBox="0 0 100 100"
        class="drop-shadow-2xl"
        :class="className"
        v-bind="$attrs"
    >
        <defs>
            <linearGradient
                id="pinGradientGreen"
                x1="0%"
                y1="0%"
                x2="0%"
                y2="100%"
            >
                <stop offset="0%" stop-color="#16a34a" />
                <stop offset="100%" stop-color="#14532d" />
            </linearGradient>

            <radialGradient id="innerShadow" cx="50%" cy="50%" r="50%">
                <stop offset="80%" stop-color="white" />
                <stop offset="100%" stop-color="#e2e8f0" />
            </radialGradient>
        </defs>

        <path
            d="M50 2C31.2 2 16 17.2 16 36c0 26 34 62 34 62s34-36 34-62c0-18.8-15.2-34-34-34Z"
            fill="url(#pinGradientGreen)"
        />

        <circle cx="50" cy="36" r="26" fill="url(#innerShadow)" />

        <g transform="translate(50, 24)">
            <ellipse cx="0" cy="0.8" rx="16" ry="5" class="fill-orange-700" />
            <ellipse cx="0" cy="0" rx="16" ry="4.5" class="fill-orange-500" />
            <path
                d="M-10 -1.5 Q 0 -4 10 -1.5"
                stroke="white"
                stroke-width="1"
                stroke-linecap="round"
                fill="none"
                opacity="0.4"
            />
            <ellipse cx="0" cy="0" rx="3" ry="1" fill="white" opacity="0.2" />
        </g>

        <g transform="translate(50, 52)">
            <rect
                x="-12"
                y="-4"
                width="24"
                height="3"
                rx="1"
                class="fill-lime-500"
                stroke="#3f6212"
                stroke-width="0.5"
            />

            <g stroke="#64748b" stroke-width="1" stroke-linecap="round">
                <path d="M-10 -1 L-3 12" />
                <path d="M10 -1 L3 12" />
                <path d="M-5 -1 L-1 12" />
                <path d="M5 -1 L1 12" />
                <path d="M0 -1 V12" />
                <path
                    d="M-6 5 Q 0 7 6 5"
                    fill="none"
                    stroke-opacity="0.3"
                    stroke-width="0.5"
                />
            </g>

            <path
                d="M-13 12 Q 0 16 13 12"
                fill="none"
                stroke="#166534"
                stroke-width="2"
            />
            <path
                d="M-14 12 C -14 12, -11 21, 0 21 C 11 21, 14 12, 14 12 Z"
                class="fill-green-600"
            />
            <g stroke="#f8fafc" stroke-width="0.6" stroke-opacity="0.5">
                <line x1="-10" y1="13" x2="-8.5" y2="20" />
                <line x1="-5" y1="14.5" x2="-4" y2="21" />
                <line x1="0" y1="15" x2="0" y2="21" />
                <line x1="5" y1="14.5" x2="4" y2="21" />
                <line x1="10" y1="13" x2="8.5" y2="20" />
            </g>

            <rect x="-1" y="21" width="2" height="6" class="fill-slate-400" />
        </g>
    </svg>
</template>
