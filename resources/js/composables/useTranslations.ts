import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Returns a translation function that looks up the current locale's strings.
 * Falls back to the key if the translation is missing.
 */
export function useTranslations(): (key: string) => string {
    const page = usePage();
    const translations = computed(() => (page.props.translations as Record<string, string>) ?? {});

    return (key: string): string => {
        return translations.value[key] ?? key;
    };
}
