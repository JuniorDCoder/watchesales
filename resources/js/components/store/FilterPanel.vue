<script setup lang="ts">
import { Check } from '@lucide/vue';
import { ref, watch } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { useSite } from '@/composables/useSite';
import type { CatalogueFilters, CatalogueOptions, Option } from '@/types';

const props = defineProps<{
    filters: CatalogueFilters;
    options: CatalogueOptions;
    lockedBrand: boolean;
}>();

const emit = defineEmits<{
    change: [patch: Partial<CatalogueFilters>];
    category: [slug: string | null];
}>();

const { currencySymbol } = useSite();

const minPrice = ref<string>(props.filters.min_price?.toString() ?? '');
const maxPrice = ref<string>(props.filters.max_price?.toString() ?? '');

watch(
    () => [props.filters.min_price, props.filters.max_price],
    ([min, max]) => {
        minPrice.value = min?.toString() ?? '';
        maxPrice.value = max?.toString() ?? '';
    },
);

function applyPrice(): void {
    const min = Number.parseInt(minPrice.value, 10);
    const max = Number.parseInt(maxPrice.value, 10);

    emit('change', {
        min_price: Number.isFinite(min) && min > 0 ? min : null,
        max_price: Number.isFinite(max) && max > 0 ? max : null,
    });
}

function toggle(
    key: 'condition' | 'movement' | 'gender' | 'brand',
    value: string,
): void {
    emit('change', { [key]: props.filters[key] === value ? null : value });
}

const optionGroups = [
    { key: 'condition', title: 'Condition' },
    { key: 'movement', title: 'Movement' },
    { key: 'gender', title: 'Suited to' },
] as const;

const groupOptions = (key: 'condition' | 'movement' | 'gender'): Option[] =>
    ({
        condition: props.options.conditions,
        movement: props.options.movements,
        gender: props.options.genders,
    })[key];
</script>

<template>
    <div class="space-y-9">
        <div class="flex items-center justify-between gap-4">
            <Label for="available-only" class="text-sm font-normal">
                Available to buy only
            </Label>
            <Switch
                id="available-only"
                :model-value="filters.available"
                @update:model-value="
                    emit('change', { available: Boolean($event) })
                "
            />
        </div>

        <fieldset>
            <legend
                class="mb-3 text-[11px] font-medium tracking-[0.22em] text-muted-foreground uppercase"
            >
                Collection
            </legend>
            <ul class="space-y-0.5">
                <li>
                    <button
                        type="button"
                        class="flex w-full items-center justify-between rounded-md px-2 py-1.5 text-left text-sm transition-colors hover:bg-accent"
                        :class="{ 'font-medium': !filters.category }"
                        @click="emit('category', null)"
                    >
                        All collections
                        <Check
                            v-if="!filters.category"
                            class="size-3.5 text-gold"
                        />
                    </button>
                </li>
                <li v-for="category in options.categories" :key="category.id">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between rounded-md px-2 py-1.5 text-left text-sm transition-colors hover:bg-accent"
                        :class="{
                            'font-medium': filters.category === category.slug,
                        }"
                        @click="emit('category', category.slug)"
                    >
                        {{ category.name }}
                        <Check
                            v-if="filters.category === category.slug"
                            class="size-3.5 text-gold"
                        />
                        <span
                            v-else
                            class="text-xs text-muted-foreground tabular-nums"
                        >
                            {{ category.watches_count }}
                        </span>
                    </button>
                </li>
            </ul>
        </fieldset>

        <fieldset v-if="!lockedBrand && options.brands.length">
            <legend
                class="mb-3 text-[11px] font-medium tracking-[0.22em] text-muted-foreground uppercase"
            >
                Brand
            </legend>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="brand in options.brands"
                    :key="brand.id"
                    type="button"
                    class="rounded-full border px-3.5 py-1.5 text-sm transition-colors"
                    :class="
                        filters.brand === brand.slug
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'hover:border-foreground/40'
                    "
                    :aria-pressed="filters.brand === brand.slug"
                    @click="toggle('brand', brand.slug)"
                >
                    {{ brand.name }}
                </button>
            </div>
        </fieldset>

        <fieldset v-for="group in optionGroups" :key="group.key">
            <legend
                class="mb-3 text-[11px] font-medium tracking-[0.22em] text-muted-foreground uppercase"
            >
                {{ group.title }}
            </legend>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="option in groupOptions(group.key)"
                    :key="option.value"
                    type="button"
                    class="rounded-full border px-3.5 py-1.5 text-sm transition-colors"
                    :class="
                        filters[group.key] === option.value
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'hover:border-foreground/40'
                    "
                    :aria-pressed="filters[group.key] === option.value"
                    @click="toggle(group.key, option.value)"
                >
                    {{ option.label }}
                </button>
            </div>
        </fieldset>

        <fieldset>
            <legend
                class="mb-3 text-[11px] font-medium tracking-[0.22em] text-muted-foreground uppercase"
            >
                Price
            </legend>
            <form class="flex items-center gap-2" @submit.prevent="applyPrice">
                <label class="relative flex-1">
                    <span
                        class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-muted-foreground"
                        aria-hidden="true"
                        >{{ currencySymbol }}</span
                    >
                    <Input
                        v-model="minPrice"
                        type="number"
                        min="0"
                        inputmode="numeric"
                        placeholder="Min"
                        aria-label="Minimum price"
                        class="pl-8"
                        @blur="applyPrice"
                    />
                </label>
                <span class="text-muted-foreground" aria-hidden="true">to</span>
                <label class="relative flex-1">
                    <span
                        class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-muted-foreground"
                        aria-hidden="true"
                        >{{ currencySymbol }}</span
                    >
                    <Input
                        v-model="maxPrice"
                        type="number"
                        min="0"
                        inputmode="numeric"
                        placeholder="Max"
                        aria-label="Maximum price"
                        class="pl-8"
                        @blur="applyPrice"
                    />
                </label>
            </form>
        </fieldset>
    </div>
</template>
