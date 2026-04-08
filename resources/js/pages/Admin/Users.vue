<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard } from '@/routes';

const t = useTranslations();

type UserRow = {
    id: number;
    username: string | null;
    name: string | null;
    email: string | null;
    role: string | null;
    createdAt: string | null;
};

type Filters = {
    q: string | null;
};

const props = defineProps<{
    filters: Filters;
    users: {
        data: UserRow[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        meta?: { total?: number; from?: number | null; to?: number | null };
        total?: number;
        from?: number | null;
        to?: number | null;
    };
}>();

const breadcrumbs = computed(() => [
    { title: t('My Profile'), href: dashboard().url },
    { title: t('Admin'), href: '/admin/discs' },
    { title: t('Users'), href: '/admin/users' },
]);

const form = reactive({
    q: props.filters.q ?? '',
});

const pageFrom = computed(() => props.users.meta?.from ?? props.users.from ?? 0);
const pageTo = computed(() => props.users.meta?.to ?? props.users.to ?? 0);
const pageTotal = computed(() => props.users.meta?.total ?? props.users.total ?? 0);

function submitFilters(): void {
    router.get(
        '/admin/users',
        {
            q: form.q || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
}

function clearFilters(): void {
    form.q = '';
    submitFilters();
}

type RoleDraft = {
    role: '' | 'user' | 'trusted' | 'admin';
};

const editingUserId = ref<number | null>(null);
const roleDrafts = reactive<Record<number, RoleDraft>>({});

function startEdit(user: UserRow): void {
    editingUserId.value = user.id;
    const role = user.role === 'admin' || user.role === 'trusted' ? user.role : 'user';
    roleDrafts[user.id] = { role };
}

function cancelEdit(userId: number): void {
    if (editingUserId.value !== userId) return;
    editingUserId.value = null;
    delete roleDrafts[userId];
}

function saveEdit(userId: number): void {
    const draft = roleDrafts[userId];
    if (!draft) return;
    if (!window.confirm(t('Are you sure?'))) return;

    const role = draft.role === '' ? 'user' : draft.role;

    router.patch(
        `/admin/users/${userId}`,
        { role } as any,
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                if (editingUserId.value !== userId) return;
                editingUserId.value = null;
                delete roleDrafts[userId];
            },
        },
    );
}

function userLabel(user: UserRow): string {
    return user.username || user.name || user.email || `#${user.id}`;
}

function roleLabel(role: string | null): string {
    if (role === 'admin') return t('Admin');
    if (role === 'trusted') return t('Trusted');
    return t('User');
}
</script>

<template>
    <Head :title="t('Admin') + ' • ' + t('Users')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-6xl px-4 py-8">
            <div class="rounded-xl border border-border bg-muted/20 p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="mt-4">
                            <nav class="flex border-b border-border bg-muted/20" aria-label="Admin tabs">
                                <Link
                                    href="/admin/discs?tab=discs"
                                    class="flex-1 whitespace-nowrap px-4 py-2 text-center text-sm font-bold transition-colors bg-muted/40 text-muted-foreground border border-transparent hover:bg-muted/50 hover:text-foreground rounded-t-lg"
                                >
                                    {{ t('Discs') }}
                                </Link>

                                <Link
                                    href="/admin/discs?tab=matches"
                                    class="flex-1 whitespace-nowrap px-4 py-2 text-center text-sm font-bold transition-colors bg-muted/40 text-muted-foreground border border-transparent hover:bg-muted/50 hover:text-foreground rounded-t-lg"
                                >
                                    {{ t('Matches') }}
                                </Link>

                                <Link
                                    href="/admin/users"
                                    class="flex-1 whitespace-nowrap px-4 py-2 text-center text-sm font-bold transition-colors bg-card text-foreground border border-border shadow-sm rounded-t-lg relative z-10 -mb-px"
                                >
                                    {{ t('Users') }}
                                </Link>
                            </nav>
                        </div>

                        <h1 class="mt-4 text-2xl font-bold text-foreground">
                            {{ t('Users') }}
                        </h1>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ t('Admin can manage user roles') }}
                        </p>
                    </div>
                    <div class="text-sm text-muted-foreground">
                        {{ pageFrom }}–{{ pageTo }}
                        / {{ pageTotal }}
                    </div>
                </div>

                <form class="mt-6 grid grid-cols-1 gap-3 md:grid-cols-6" @submit.prevent="submitFilters">
                    <input
                        v-model="form.q"
                        type="text"
                        class="md:col-span-3 h-10 w-full rounded-lg border border-input bg-background px-3 text-sm"
                        :placeholder="t('Search')"
                    />

                    <div class="flex gap-2 md:col-span-2">
                        <button
                            type="submit"
                            class="h-10 flex-1 rounded-lg bg-primary px-4 text-sm font-bold text-primary-foreground hover:opacity-90"
                        >
                            {{ t('Search') }}
                        </button>
                        <button
                            type="button"
                            class="h-10 rounded-lg border border-input bg-muted/50 px-4 text-sm font-bold text-foreground hover:bg-muted"
                            @click="clearFilters"
                        >
                            {{ t('Clear') }}
                        </button>
                    </div>
                </form>

                <div class="mt-6 overflow-x-auto rounded-xl border border-border bg-card">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-muted/30 text-xs font-bold uppercase tracking-wide text-muted-foreground">
                            <tr>
                                <th class="px-4 py-3">ID</th>
                                <th class="px-4 py-3">{{ t('Username') }}</th>
                                <th class="px-4 py-3">{{ t('Email Address') }}</th>
                                <th class="px-4 py-3">{{ t('Role') }}</th>
                                <th class="px-4 py-3">{{ t('Created') }}</th>
                                <th class="px-4 py-3">{{ t('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="user in props.users.data" :key="user.id" class="hover:bg-muted/20">
                                <td class="px-4 py-3 font-mono text-xs text-muted-foreground">
                                    {{ user.id }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-bold text-foreground">
                                        {{ userLabel(user) }}
                                    </div>
                                    <div class="mt-1 text-xs text-muted-foreground">
                                        {{ user.name ?? '—' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">
                                    {{ user.email ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <template v-if="editingUserId === user.id">
                                        <select
                                            class="h-9 w-full rounded-lg border border-input bg-background px-2 text-sm"
                                            v-model="roleDrafts[user.id].role"
                                        >
                                            <option value="user">{{ t('User') }}</option>
                                            <option value="trusted">{{ t('Trusted') }}</option>
                                            <option value="admin">{{ t('Admin') }}</option>
                                        </select>
                                    </template>
                                    <template v-else>
                                        <span class="text-sm font-bold text-foreground">
                                            {{ roleLabel(user.role) }}
                                        </span>
                                    </template>
                                </td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">
                                    {{ user.createdAt ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <button
                                            v-if="editingUserId !== user.id"
                                            type="button"
                                            class="h-9 rounded-lg border border-input bg-muted/50 px-3 text-sm font-bold text-foreground transition-colors hover:bg-muted"
                                            @click="startEdit(user)"
                                        >
                                            {{ t('Edit') }}
                                        </button>
                                        <template v-else>
                                            <button
                                                type="button"
                                                class="h-9 rounded-lg bg-primary px-4 text-sm font-bold text-primary-foreground hover:opacity-90"
                                                @click="saveEdit(user.id)"
                                            >
                                                {{ t('Save') }}
                                            </button>
                                            <button
                                                type="button"
                                                class="h-9 rounded-lg border border-input bg-muted/50 px-3 text-sm font-bold text-foreground transition-colors hover:bg-muted"
                                                @click="cancelEdit(user.id)"
                                            >
                                                {{ t('Cancel') }}
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="props.users.data.length === 0">
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-muted-foreground">
                                    {{ t('No results found.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-5 flex flex-wrap gap-2">
                    <a
                        v-for="link in props.users.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        class="inline-flex h-9 items-center justify-center rounded-lg border border-input bg-muted/50 px-3 text-sm font-bold text-foreground transition-colors hover:bg-muted"
                        :class="[
                            !link.url ? 'pointer-events-none opacity-50' : '',
                            link.active ? 'bg-primary/10 text-primary border-primary/20' : '',
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

