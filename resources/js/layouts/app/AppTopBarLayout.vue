<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import {
    LayoutGrid,
    LogOut,
    Menu,
    Settings,
    Target,
    MapPin,
    Info,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { getInitials } from '@/composables/useInitials';
import { useTranslations } from '@/composables/useTranslations';
import type { Auth } from '@/types/auth';
import type { BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';
import { edit } from '@/routes/profile';
import { logout } from '@/routes';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const auth = computed(() => (page.props as { auth?: Auth }).auth);
const t = useTranslations();

const mainNavItems = [
    { label: 'Profile', href: '/dashboard' },
    { label: 'About DiscFinder', href: '/about' },
];

function isCurrentNav(href: string): boolean {
    const path = String(page.url).replace(/\?.*$/, '');
    return path === href || (href !== '/dashboard' && path.startsWith(href));
}

const mobileNavItems = [
    { title: 'My Profile', href: '/dashboard', icon: LayoutGrid },
    { title: 'About DiscFinder', href: '/about', icon: Info },
];
</script>

<template>
    <div class="flex min-h-screen w-full flex-col">
        <header
            class="flex h-14 shrink-0 items-center gap-4 border-b border-border bg-card px-4 md:px-6"
        >
            <!-- Mobile: Burger menu -->
            <div class="flex lg:hidden">
                <Sheet>
                    <SheetTrigger as-child>
                        <Button variant="ghost" size="icon" class="h-9 w-9">
                            <Menu class="h-5 w-5" />
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="left" class="w-[280px] p-0">
                        <SheetHeader class="border-b border-border p-4 text-left">
                            <SheetTitle class="sr-only">Menu</SheetTitle>
                            <Link
                                :href="dashboard()"
                                class="flex items-center gap-2"
                            >
                                <AppLogoIcon class="h-8 w-8" />
                                <span class="font-semibold">DiscFinder</span>
                            </Link>
                        </SheetHeader>
                        <nav class="flex flex-col p-2">
                            <Link
                                v-for="item in mobileNavItems"
                                :key="item.href"
                                :href="item.href"
                                class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-foreground hover:bg-muted"
                            >
                                <component
                                    :is="item.icon"
                                    class="h-5 w-5 shrink-0"
                                />
                                {{ t(item.title) }}
                            </Link>
                            <div class="my-2 border-t border-border" />
                            <Link
                                :href="edit()"
                                class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-foreground hover:bg-muted"
                            >
                                <Settings class="h-5 w-5 shrink-0" />
                                {{ t('Settings') }}
                            </Link>
                            <Link
                                :href="logout()"
                                method="post"
                                class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium text-foreground hover:bg-muted"
                            >
                                <LogOut class="h-5 w-5 shrink-0" />
                                {{ t('Log out') }}
                            </Link>
                        </nav>
                    </SheetContent>
                </Sheet>
            </div>

            <!-- Logo + DiscFinder -->
            <Link
                :href="dashboard()"
                class="flex shrink-0 items-center gap-2"
            >
                <AppLogo />
            </Link>

            <!-- Main nav: Profile, Lost Discs, Found Discs, About (desktop only) -->
            <nav
                class="hidden flex-1 items-center gap-1 lg:flex"
                aria-label="Main"
            >
                <Link
                    v-for="item in mainNavItems"
                    :key="item.href"
                    :href="item.href"
                    class="rounded-md px-3 py-2 text-sm font-medium transition-colors"
                    :class="
                        isCurrentNav(item.href)
                            ? 'bg-primary/10 text-primary'
                            : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                    "
                >
                    {{ t(item.label) }}
                </Link>
            </nav>

            <!-- Breadcrumbs (when no nav or on small screens - optional) -->
            <div
                v-if="breadcrumbs && breadcrumbs.length > 0"
                class="hidden min-w-0 flex-1 md:block lg:hidden"
            >
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>

            <div class="ml-auto flex shrink-0 items-center gap-2">
                <LanguageSwitcher />
                <!-- Profile / Settings (desktop & mobile) -->
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="relative h-9 w-9 rounded-full"
                        >
                            <Avatar class="h-8 w-8">
                                <AvatarImage
                                    v-if="auth?.user?.avatar"
                                    :src="auth.user.avatar"
                                    :alt="auth?.user?.name"
                                />
                                <AvatarFallback
                                    class="rounded-full bg-muted text-sm font-medium"
                                >
                                    {{ getInitials(auth?.user?.name) }}
                                </AvatarFallback>
                            </Avatar>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56">
                        <UserMenuContent
                            v-if="auth?.user"
                            :user="auth.user"
                        />
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </header>

        <main class="flex flex-1 flex-col overflow-x-hidden bg-muted/20">
            <slot />
        </main>
    </div>
</template>
