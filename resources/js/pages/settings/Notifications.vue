<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

type Preferences = {
    emailNotifyDiscExpiring: boolean;
    emailNotifyDiscExpired: boolean;
    emailNotifyNewMessage: boolean;
};

const props = defineProps<{
    preferences: Preferences;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Notification settings',
        href: '/settings/notifications',
    },
];

const page = usePage();
const errors = computed(() => (page.props as { errors?: Record<string, string> }).errors ?? {});

const form = reactive({
    email_notify_disc_expiring: props.preferences.emailNotifyDiscExpiring,
    email_notify_disc_expired: props.preferences.emailNotifyDiscExpired,
    email_notify_new_message: props.preferences.emailNotifyNewMessage,
});

const saving = ref(false);
const saved = ref(false);

function save(): void {
    saving.value = true;
    saved.value = false;

    router.patch('/settings/notifications', form, {
        preserveScroll: true,
        onSuccess: () => {
            saved.value = true;
            window.setTimeout(() => {
                saved.value = false;
            }, 2000);
        },
        onFinish: () => {
            saving.value = false;
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Notification settings" />

        <h1 class="sr-only">Notification Settings</h1>

        <SettingsLayout>
            <div class="space-y-6">
                <Heading
                    variant="small"
                    title="Notifications"
                    description="Choose which emails you want to receive"
                />

                <div class="space-y-4 rounded-lg border border-border bg-card p-4">
                    <label class="flex items-start gap-3">
                        <input
                            v-model="form.email_notify_disc_expiring"
                            type="checkbox"
                            class="mt-1 h-4 w-4"
                        />
                        <div class="min-w-0">
                            <div class="font-bold text-foreground">Disc expiring soon</div>
                            <div class="mt-1 text-sm text-muted-foreground">
                                Get an email before your disc report expires.
                            </div>
                        </div>
                    </label>

                    <label class="flex items-start gap-3">
                        <input
                            v-model="form.email_notify_disc_expired"
                            type="checkbox"
                            class="mt-1 h-4 w-4"
                        />
                        <div class="min-w-0">
                            <div class="font-bold text-foreground">Disc expired</div>
                            <div class="mt-1 text-sm text-muted-foreground">
                                Get an email when your disc report becomes inactive.
                            </div>
                        </div>
                    </label>

                    <label class="flex items-start gap-3">
                        <input
                            v-model="form.email_notify_new_message"
                            type="checkbox"
                            class="mt-1 h-4 w-4"
                        />
                        <div class="min-w-0">
                            <div class="font-bold text-foreground">New messages</div>
                            <div class="mt-1 text-sm text-muted-foreground">
                                Get an email when someone sends you a new message.
                            </div>
                        </div>
                    </label>

                    <InputError
                        v-if="errors.email_notify_disc_expiring || errors.email_notify_disc_expired || errors.email_notify_new_message"
                        class="mt-2"
                        :message="
                            errors.email_notify_disc_expiring ||
                            errors.email_notify_disc_expired ||
                            errors.email_notify_new_message
                        "
                    />

                    <div class="flex justify-end pt-2">
                        <div class="flex items-center gap-3">
                            <span v-if="saved" class="text-sm font-medium text-green-600">
                                Saved.
                            </span>
                            <Button :disabled="saving" @click="save">
                                {{ saving ? 'Saving…' : 'Save' }}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>

