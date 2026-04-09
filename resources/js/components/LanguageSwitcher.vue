<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, useId } from 'vue';

const page = usePage();
const locale = computed(() => page.props.locale as string);
const ukClipId = `uk-flag-clip-${useId()}`;

const options = [
    { code: 'en' as const, short: 'EN', title: 'English' },
    { code: 'et' as const, short: 'ET', title: 'Eesti' },
];
</script>

<template>
    <div class="flex items-center gap-0.5" role="group" :aria-label="'Language'">
        <Link
            v-for="opt in options"
            :key="opt.code"
            :href="`/locale/${opt.code}`"
            :title="opt.title"
            :aria-label="opt.title"
            :class="[
                'inline-flex items-center gap-1 rounded-md px-1.5 py-1 transition-colors',
                locale === opt.code
                    ? 'font-bold text-[#61896f] dark:text-[#7ba884] bg-[#61896f]/10 dark:bg-[#61896f]/20 ring-1 ring-[#61896f]/30 dark:ring-[#61896f]/40'
                    : 'font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-muted/60',
            ]"
        >
            <!-- Estonia: blue / black / white -->
            <span
                v-if="opt.code === 'et'"
                class="relative inline-flex h-3.5 w-[22px] shrink-0 overflow-hidden rounded-[2px] border border-black/15 shadow-sm dark:border-white/10"
                aria-hidden="true"
            >
                <svg viewBox="0 0 9 6" class="h-full w-full" xmlns="http://www.w3.org/2000/svg">
                    <rect width="9" height="2" fill="#1294D8" />
                    <rect y="2" width="9" height="2" fill="#000" />
                    <rect y="4" width="9" height="2" fill="#fff" />
                </svg>
            </span>
            <!-- English (UK): compact Union Jack -->
            <span
                v-else
                class="relative inline-flex h-3.5 w-[22px] shrink-0 overflow-hidden rounded-[2px] border border-black/15 shadow-sm dark:border-white/10"
                aria-hidden="true"
            >
                <svg
                    viewBox="0 0 60 30"
                    class="h-full w-full"
                    xmlns="http://www.w3.org/2000/svg"
                    preserveAspectRatio="xMidYMid slice"
                >
                    <defs>
                        <clipPath :id="ukClipId">
                            <path d="M30 15h30v15H30V15zM0 15v15h30V15H0zM0 0v15h30V0H0zM30 0v15h30V0H30z" />
                        </clipPath>
                    </defs>
                    <path fill="#012169" d="M0 0h60v30H0z" />
                    <path stroke="#fff" stroke-width="6" d="M0 0l60 30M60 0L0 30" />
                    <path
                        stroke="#c8102e"
                        stroke-width="4"
                        d="M0 0l60 30M60 0L0 30"
                        :clip-path="`url(#${ukClipId})`"
                    />
                    <path stroke="#fff" stroke-width="10" d="M30 0v30M0 15h60" />
                    <path stroke="#c8102e" stroke-width="6" d="M30 0v30M0 15h60" />
                </svg>
            </span>
            <span class="text-[10px] font-bold uppercase leading-none tracking-tight">
                {{ opt.short }}
            </span>
        </Link>
    </div>
</template>
