<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const navItems = [
    { label: 'Profile', href: '/dashboard' },
    { label: 'Lost Discs', href: '/lost-discs' },
    { label: 'Found Discs', href: '/found-discs' },
    { label: 'About DiscFinder', href: '/about' },
];

function isCurrent(href: string): boolean {
    const path = String(page.url).replace(/\?.*$/, '');
    return path === href || (href !== '/dashboard' && path.startsWith(href));
}
</script>

<template>
    <nav
        class="flex items-center gap-1 border-b border-border bg-card px-4 py-3"
        aria-label="Main"
    >
        <Link
            v-for="item in navItems"
            :key="item.href"
            :href="item.href"
            class="rounded-md px-3 py-2 text-sm font-medium transition-colors"
            :class="
                isCurrent(item.href)
                    ? 'bg-primary/10 text-primary'
                    : 'text-muted-foreground hover:bg-muted hover:text-foreground'
            "
        >
            {{ item.label }}
        </Link>
    </nav>
</template>
