<script setup lang="ts">
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    { value: 'light', Icon: Sun, label: 'Light' },
    { value: 'dark', Icon: Moon, label: 'Dark' },
    { value: 'system', Icon: Monitor, label: 'System' },
] as const;
</script>

<template>
    <div
        class="inline-flex w-full gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800 sm:w-auto"
    >
        <button
            v-for="{ value, Icon, label } in tabs"
            :key="value"
            @click="updateAppearance(value)"
            :class="[
                'flex flex-1 min-w-0 flex-col items-center justify-center gap-0.5 rounded-md px-2 py-1.5 transition-colors sm:flex-none sm:flex-row sm:gap-1.5 sm:px-3.5',
                appearance === value
                    ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                    : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
            ]"
        >
            <component :is="Icon" class="h-4 w-4 sm:-ml-1" />
            <span class="text-xs leading-none sm:ml-1.5 sm:text-sm">{{ label }}</span>
        </button>
    </div>
</template>
