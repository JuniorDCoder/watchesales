<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ChevronRight, Search, SlidersHorizontal, X } from '@lucide/vue';
import { useDebounceFn } from '@vueuse/core';
import { computed, ref, watch } from 'vue';
import EmptyState from '@/components/store/EmptyState.vue';
import FilterPanel from '@/components/store/FilterPanel.vue';
import Pagination from '@/components/store/Pagination.vue';
import WatchCard from '@/components/store/WatchCard.vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useSite } from '@/composables/useSite';
import { home } from '@/routes';
import { show as showBrand } from '@/routes/brands';
import { show as showCollection } from '@/routes/collections';
import { index as watchesIndex } from '@/routes/watches';
import type {
    CatalogueFilters,
    CatalogueOptions,
    Paginated,
    Watch,
} from '@/types';

const props = defineProps<{
    watches: Paginated<Watch>;
    filters: CatalogueFilters;
    heading: { title: string; description: string; eyebrow: string | null };
    scope: { category: string | null; brand: string | null };
    options: CatalogueOptions;
}>();

const { price } = useSite();
const search = ref(props.filters.search ?? '');
const loading = ref(false);
const filtersOpen = ref(false);

watch(
    () => props.filters.search,
    (value) => {
        if ((value ?? '') !== search.value.trim()) {
            search.value = value ?? '';
        }
    },
);

const optionLabel = (
    key: 'condition' | 'movement' | 'gender',
    value: string | null,
): string | undefined =>
    ({
        condition: props.options.conditions,
        movement: props.options.movements,
        gender: props.options.genders,
    })[key].find((option) => option.value === value)?.label;

const activeChips = computed(() => {
    const chips: { key: keyof CatalogueFilters; label: string }[] = [];
    const { filters } = props;

    if (filters.search) {
        chips.push({ key: 'search', label: `"${filters.search}"` });
    }

    if (filters.brand && !props.scope.brand) {
        const brand = props.options.brands.find(
            (item) => item.slug === filters.brand,
        );
        chips.push({ key: 'brand', label: brand?.name ?? filters.brand });
    }

    (['condition', 'movement', 'gender'] as const).forEach((key) => {
        if (filters[key]) {
            chips.push({
                key,
                label: optionLabel(key, filters[key]) ?? filters[key]!,
            });
        }
    });

    if (filters.min_price || filters.max_price) {
        chips.push({
            key: 'min_price',
            label: filters.max_price
                ? `${price(filters.min_price ?? 0)} to ${price(filters.max_price)}`
                : `From ${price(filters.min_price)}`,
        });
    }

    if (filters.available) {
        chips.push({ key: 'available', label: 'Available only' });
    }

    return chips;
});

function queryFor(filters: CatalogueFilters): Record<string, string | number> {
    const query: Record<string, string | number> = {};

    (
        [
            'search',
            'brand',
            'condition',
            'movement',
            'gender',
            'min_price',
            'max_price',
        ] as const
    ).forEach((key) => {
        const value = filters[key];

        if (
            value !== null &&
            value !== '' &&
            !(key === 'brand' && props.scope.brand)
        ) {
            query[key] = value;
        }
    });

    if (filters.available) {
        query.available = 1;
    }

    if (filters.sort !== 'newest') {
        query.sort = filters.sort;
    }

    return query;
}

function baseUrl(category: string | null): string {
    if (category) {
        return showCollection.url({ slug: category });
    }

    if (props.scope.brand) {
        return showBrand.url({ slug: props.scope.brand });
    }

    return watchesIndex.url();
}

function visit(
    url: string,
    filters: CatalogueFilters,
    preserveScroll = true,
): void {
    router.get(url, queryFor(filters), {
        preserveState: true,
        preserveScroll,
        replace: true,
        onStart: () => (loading.value = true),
        onFinish: () => (loading.value = false),
    });
}

function applyFilters(patch: Partial<CatalogueFilters>): void {
    visit(baseUrl(props.filters.category), { ...props.filters, ...patch });
}

function changeCategory(slug: string | null): void {
    filtersOpen.value = false;
    visit(baseUrl(slug), { ...props.filters, category: slug }, false);
}

function removeChip(key: keyof CatalogueFilters): void {
    if (key === 'min_price') {
        applyFilters({ min_price: null, max_price: null });

        return;
    }

    if (key === 'search') {
        search.value = '';
    }

    applyFilters({ [key]: key === 'available' ? false : null });
}

function clearAll(): void {
    search.value = '';
    visit(baseUrl(props.scope.category), {
        ...props.filters,
        search: null,
        brand: null,
        condition: null,
        movement: null,
        gender: null,
        min_price: null,
        max_price: null,
        available: false,
    });
}

const debouncedSearch = useDebounceFn(() => {
    applyFilters({ search: search.value.trim() || null });
}, 350);
</script>

<template>
    <section class="border-b bg-muted/30">
        <div
            class="mx-auto max-w-7xl px-4 pt-10 pb-12 sm:px-6 md:pt-14 lg:px-8"
        >
            <nav aria-label="Breadcrumb">
                <ol
                    class="flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground"
                >
                    <li>
                        <Link :href="home()" class="hover:text-foreground"
                            >Home</Link
                        >
                    </li>
                    <li aria-hidden="true"><ChevronRight class="size-3" /></li>
                    <li>
                        <Link
                            v-if="scope.category || scope.brand"
                            :href="watchesIndex()"
                            class="hover:text-foreground"
                            >Watches</Link
                        >
                        <span v-else class="text-foreground">Watches</span>
                    </li>
                    <template v-if="scope.category || scope.brand">
                        <li aria-hidden="true">
                            <ChevronRight class="size-3" />
                        </li>
                        <li class="text-foreground">{{ heading.title }}</li>
                    </template>
                </ol>
            </nav>

            <div
                class="mt-8 flex flex-col gap-6 md:flex-row md:items-end md:justify-between"
            >
                <div class="max-w-2xl">
                    <p
                        v-if="heading.eyebrow"
                        class="animate-rise text-[11px] font-medium tracking-[0.28em] text-gold uppercase"
                    >
                        {{ heading.eyebrow }}
                    </p>
                    <h1
                        class="mt-3 animate-rise font-display text-5xl leading-none font-medium [animation-delay:80ms] md:text-6xl"
                    >
                        {{ heading.title }}
                    </h1>
                    <p
                        class="mt-4 animate-rise text-pretty text-muted-foreground [animation-delay:160ms]"
                    >
                        {{ heading.description }}
                    </p>
                </div>
                <p class="shrink-0 text-sm text-muted-foreground tabular-nums">
                    {{ watches.meta.total }}
                    {{ watches.meta.total === 1 ? 'watch' : 'watches' }}
                </p>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <label class="relative flex-1">
                <span class="sr-only">Search watches</span>
                <Search
                    class="pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <input
                    v-model="search"
                    type="search"
                    placeholder="Search brand, model or reference"
                    class="h-12 w-full rounded-full border bg-card pr-4 pl-11 text-sm transition-shadow outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/40"
                    @input="debouncedSearch"
                />
            </label>

            <div class="flex items-center gap-3">
                <Sheet v-model:open="filtersOpen">
                    <SheetTrigger as-child>
                        <Button
                            variant="outline"
                            class="h-12 flex-1 gap-2 rounded-full px-5 lg:hidden"
                        >
                            <SlidersHorizontal class="size-4" />
                            Filters
                            <span
                                v-if="activeChips.length"
                                class="flex size-5 items-center justify-center rounded-full bg-primary text-[10px] text-primary-foreground"
                                >{{ activeChips.length }}</span
                            >
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="left" class="w-full gap-0 sm:max-w-sm">
                        <SheetHeader class="border-b">
                            <SheetTitle
                                class="font-display text-2xl font-medium"
                                >Filters</SheetTitle
                            >
                            <SheetDescription
                                >Refine the collection</SheetDescription
                            >
                        </SheetHeader>
                        <div class="flex-1 overflow-y-auto p-6">
                            <FilterPanel
                                :filters="filters"
                                :options="options"
                                :locked-brand="Boolean(scope.brand)"
                                @change="applyFilters"
                                @category="changeCategory"
                            />
                        </div>
                        <SheetFooter class="border-t">
                            <Button
                                class="h-11 rounded-full"
                                @click="filtersOpen = false"
                            >
                                Show {{ watches.meta.total }} results
                            </Button>
                        </SheetFooter>
                    </SheetContent>
                </Sheet>

                <Select
                    :model-value="filters.sort"
                    @update:model-value="applyFilters({ sort: String($event) })"
                >
                    <SelectTrigger
                        class="h-12! flex-1 rounded-full bg-card px-5 sm:w-56"
                        aria-label="Sort watches"
                    >
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent align="end">
                        <SelectItem
                            v-for="sort in options.sorts"
                            :key="sort.value"
                            :value="sort.value"
                        >
                            {{ sort.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <TransitionGroup
            v-if="activeChips.length"
            tag="div"
            class="mt-5 flex flex-wrap items-center gap-2"
            enter-active-class="transition duration-300"
            enter-from-class="opacity-0 scale-95"
        >
            <button
                v-for="chip in activeChips"
                :key="chip.key"
                type="button"
                class="group inline-flex items-center gap-1.5 rounded-full bg-secondary py-1.5 pr-2.5 pl-3.5 text-sm transition-colors hover:bg-accent"
                @click="removeChip(chip.key)"
            >
                {{ chip.label }}
                <X class="size-3.5 opacity-60 group-hover:opacity-100" />
                <span class="sr-only">Remove filter</span>
            </button>
            <button
                key="clear"
                type="button"
                class="px-2 text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
                @click="clearAll"
            >
                Clear all
            </button>
        </TransitionGroup>

        <div class="mt-10 grid gap-12 lg:grid-cols-[240px_1fr]">
            <aside class="hidden lg:block">
                <div class="sticky top-28">
                    <FilterPanel
                        :filters="filters"
                        :options="options"
                        :locked-brand="Boolean(scope.brand)"
                        @change="applyFilters"
                        @category="changeCategory"
                    />
                </div>
            </aside>

            <div>
                <div
                    v-if="watches.data.length"
                    class="grid grid-cols-2 gap-x-4 gap-y-12 transition-opacity duration-300 sm:gap-x-6 xl:grid-cols-3"
                    :class="{ 'pointer-events-none opacity-50': loading }"
                    :aria-busy="loading"
                >
                    <WatchCard
                        v-for="(watch, index) in watches.data"
                        :key="watch.id"
                        v-reveal="(index % 3) * 70"
                        :watch="watch"
                        :eager="index < 3"
                    />
                </div>
                <EmptyState
                    v-else
                    title="Nothing matches just yet"
                    description="Try removing a filter or two. New pieces arrive every week, and we are always happy to source something for you."
                >
                    <Button
                        variant="outline"
                        class="rounded-full"
                        @click="clearAll"
                    >
                        Clear filters
                    </Button>
                </EmptyState>

                <div class="mt-16">
                    <Pagination :meta="watches.meta" :links="watches.links" />
                </div>
            </div>
        </div>
    </div>
</template>
