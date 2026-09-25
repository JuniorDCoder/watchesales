import { usePage } from '@inertiajs/vue3';
import type { ComputedRef } from 'vue';
import { computed } from 'vue';
import { currencySymbol, DEFAULT_CURRENCY, formatPrice } from '@/lib/format';
import type { Site } from '@/types';

export type UseSiteReturn = {
    site: ComputedRef<Site>;
    currency: ComputedRef<string>;
    currencySymbol: ComputedRef<string>;
    price: (value: number | null | undefined) => string;
};

export function useSite(): UseSiteReturn {
    const page = usePage();
    const site = computed(() => page.props.site);
    const currency = computed(() => site.value.currency || DEFAULT_CURRENCY);

    return {
        site,
        currency,
        currencySymbol: computed(() => currencySymbol(currency.value)),
        price: (value) => formatPrice(value, currency.value),
    };
}
