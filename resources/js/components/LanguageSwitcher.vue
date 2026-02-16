<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const locale = computed(() => page.props.locale as string);

const options = [
    { code: 'en', label: 'ENG' },
    { code: 'et', label: 'EST' },
] as const;
</script>

<template>
    <div class="flex items-center gap-1">
        <Link
            v-for="opt in options"
            :key="opt.code"
            :href="`/locale/${opt.code}`"
            :class="[
                'text-sm px-2 py-1 rounded transition-colors',
                locale === opt.code
                    ? 'font-bold text-[#61896f] dark:text-[#7ba884] bg-[#61896f]/10 dark:bg-[#61896f]/20 ring-1 ring-[#61896f]/30 dark:ring-[#61896f]/40'
                    : 'font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200',
            ]"
        >
            {{ opt.label }}
        </Link>
    </div>
</template>
