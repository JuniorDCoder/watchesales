<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Check,
    ChevronRight,
    Link2,
    PackageCheck,
    Share2,
    ShieldCheck,
    Wrench,
} from '@lucide/vue';
import { useIntersectionObserver } from '@vueuse/core';
import { computed, ref, useTemplateRef } from 'vue';
import { toast } from 'vue-sonner';
import InquireButton from '@/components/store/InquireButton.vue';
import SectionHeading from '@/components/store/SectionHeading.vue';
import StatusBadge from '@/components/store/StatusBadge.vue';
import WatchCard from '@/components/store/WatchCard.vue';
import WatchGallery from '@/components/store/WatchGallery.vue';
import { Button } from '@/components/ui/button';
import { useSite } from '@/composables/useSite';
import { home } from '@/routes';
import { show as showBrand } from '@/routes/brands';
import { show as showCollection } from '@/routes/collections';
import { index as watchesIndex } from '@/routes/watches';
import type { Watch, WatchDetail } from '@/types';

defineOptions({
    layout: { floatingContactOnMobile: false },
});

const props = defineProps<{
    watch: WatchDetail;
    related: Watch[];
}>();

const { price } = useSite();

const fullName = computed(() =>
    `${props.watch.brand?.name ?? ''} ${props.watch.name}`.trim(),
);
const paragraphs = computed(() =>
    (props.watch.description ?? '')
        .split(/\n{2,}/)
        .map((text) => text.trim())
        .filter(Boolean),
);

const ctaLabel = computed(() => {
    if (props.watch.status === 'sold') {
        return 'Find me a similar watch';
    }

    return props.watch.status === 'reserved'
        ? 'Join the waiting list'
        : undefined;
});

const specifications = computed(() => {
    const watch = props.watch;
    const yesNo = (value: boolean) => (value ? 'Included' : 'Not included');

    return [
        { label: 'Brand', value: watch.brand?.name },
        { label: 'Model', value: watch.name },
        { label: 'Reference', value: watch.reference },
        { label: 'Year', value: watch.year?.toString() },
        { label: 'Condition', value: watch.condition_label },
        { label: 'Movement', value: watch.movement_label },
        { label: 'Case material', value: watch.case_material },
        {
            label: 'Case diameter',
            value: watch.case_diameter ? `${watch.case_diameter} mm` : null,
        },
        {
            label: 'Water resistance',
            value: watch.water_resistance
                ? `${watch.water_resistance} m`
                : null,
        },
        { label: 'Dial', value: watch.dial_color },
        { label: 'Strap or bracelet', value: watch.strap_material },
        { label: 'Original box', value: yesNo(watch.has_box) },
        { label: 'Papers', value: yesNo(watch.has_papers) },
    ].filter((spec): spec is { label: string; value: string } =>
        Boolean(spec.value),
    );
});

const highlights = computed(() =>
    [
        props.watch.case_diameter
            ? { label: 'Case', value: `${props.watch.case_diameter} mm` }
            : null,
        props.watch.movement_label
            ? { label: 'Movement', value: props.watch.movement_label }
            : null,
        props.watch.year
            ? { label: 'Year', value: String(props.watch.year) }
            : null,
        {
            label: 'Box and papers',
            value:
                props.watch.has_box && props.watch.has_papers
                    ? 'Full set'
                    : props.watch.has_box || props.watch.has_papers
                      ? 'Partial'
                      : 'Watch only',
        },
    ].filter((item): item is { label: string; value: string } => item !== null),
);

const primaryCta = useTemplateRef<HTMLElement>('primaryCta');
const showStickyBar = ref(false);

useIntersectionObserver(primaryCta, ([entry]) => {
    showStickyBar.value = entry
        ? !entry.isIntersecting && entry.boundingClientRect.top < 0
        : false;
});

async function share(): Promise<void> {
    const url = window.location.href;

    if (navigator.share) {
        await navigator.share({ title: fullName.value, url }).catch(() => {});

        return;
    }

    await navigator.clipboard.writeText(url);
    toast.success('Link copied to clipboard');
}
</script>

<template>
    <div class="mx-auto max-w-7xl px-4 pt-8 pb-24 sm:px-6 lg:px-8">
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
                    <Link :href="watchesIndex()" class="hover:text-foreground"
                        >Watches</Link
                    >
                </li>
                <template v-if="watch.category">
                    <li aria-hidden="true"><ChevronRight class="size-3" /></li>
                    <li>
                        <Link
                            :href="showCollection(watch.category)"
                            class="hover:text-foreground"
                        >
                            {{ watch.category.name }}
                        </Link>
                    </li>
                </template>
                <li aria-hidden="true"><ChevronRight class="size-3" /></li>
                <li class="truncate text-foreground" aria-current="page">
                    {{ watch.name }}
                </li>
            </ol>
        </nav>

        <div
            v-if="!watch.is_published"
            class="mt-6 rounded-md border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-800 dark:text-amber-200"
        >
            This watch is hidden from the storefront. Only administrators can
            see this preview.
        </div>

        <div class="mt-8 grid gap-10 lg:grid-cols-12 lg:gap-16">
            <div class="lg:col-span-7">
                <WatchGallery :images="watch.images ?? []" :title="fullName" />
            </div>

            <div class="lg:col-span-5">
                <div class="lg:sticky lg:top-28">
                    <div class="flex items-center justify-between gap-4">
                        <Link
                            v-if="watch.brand"
                            :href="showBrand(watch.brand)"
                            class="text-[11px] font-medium tracking-[0.28em] text-gold uppercase hover:underline"
                        >
                            {{ watch.brand.name }}
                        </Link>
                        <StatusBadge
                            :status="watch.status"
                            :label="watch.status_label"
                        />
                    </div>

                    <h1
                        class="mt-4 font-display text-4xl leading-[1.05] font-medium text-balance md:text-5xl"
                    >
                        {{ watch.name }}
                    </h1>
                    <p
                        v-if="watch.reference"
                        class="mt-3 text-sm text-muted-foreground"
                    >
                        Reference {{ watch.reference }}
                    </p>

                    <p
                        class="mt-8 font-display text-4xl tabular-nums"
                        :class="{
                            'text-muted-foreground line-through decoration-1':
                                watch.status === 'sold',
                        }"
                    >
                        {{ price(watch.price) }}
                    </p>
                    <p
                        v-if="watch.price === null"
                        class="mt-1 text-sm text-muted-foreground"
                    >
                        Contact us for current pricing and availability.
                    </p>

                    <p
                        v-if="watch.summary"
                        class="mt-6 leading-relaxed text-pretty text-muted-foreground"
                    >
                        {{ watch.summary }}
                    </p>

                    <dl
                        class="mt-8 grid grid-cols-2 gap-px overflow-hidden rounded-md border bg-border"
                    >
                        <div
                            v-for="item in highlights"
                            :key="item.label"
                            class="bg-card px-4 py-3.5"
                        >
                            <dt
                                class="text-[11px] tracking-wide text-muted-foreground uppercase"
                            >
                                {{ item.label }}
                            </dt>
                            <dd class="mt-1 text-sm font-medium">
                                {{ item.value }}
                            </dd>
                        </div>
                    </dl>

                    <div ref="primaryCta" class="mt-8 flex gap-3">
                        <InquireButton
                            :watch="watch"
                            :label="ctaLabel"
                            class="h-14 flex-1 rounded-full text-base"
                        />
                        <Button
                            variant="outline"
                            size="icon"
                            class="size-14 shrink-0 rounded-full"
                            aria-label="Share this watch"
                            @click="share"
                        >
                            <Share2 class="size-5" />
                        </Button>
                    </div>

                    <ul class="mt-8 space-y-3 border-t pt-8 text-sm">
                        <li class="flex items-center gap-3">
                            <ShieldCheck class="size-4 text-gold" />
                            Authenticity guaranteed
                        </li>
                        <li class="flex items-center gap-3">
                            <Wrench class="size-4 text-gold" /> Inspected and
                            timed by our watchmaker
                        </li>
                        <li class="flex items-center gap-3">
                            <PackageCheck class="size-4 text-gold" /> Fully
                            insured, discreet delivery
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="mt-24 grid gap-16 border-t pt-16 lg:grid-cols-12">
            <section v-reveal class="lg:col-span-7">
                <h2 class="font-display text-3xl font-medium">
                    About this watch
                </h2>
                <div
                    class="mt-6 space-y-5 text-[17px] leading-[1.8] text-pretty text-muted-foreground"
                >
                    <p v-for="(paragraph, index) in paragraphs" :key="index">
                        {{ paragraph }}
                    </p>
                    <p v-if="!paragraphs.length">
                        Get in touch for a full condition report and additional
                        photographs.
                    </p>
                </div>
            </section>
            <section v-reveal="120" class="lg:col-span-5">
                <h2 class="font-display text-3xl font-medium">
                    Specifications
                </h2>
                <dl class="mt-6 divide-y border-y">
                    <div
                        v-for="spec in specifications"
                        :key="spec.label"
                        class="flex justify-between gap-6 py-3.5 text-sm"
                    >
                        <dt class="text-muted-foreground">{{ spec.label }}</dt>
                        <dd
                            class="flex items-center gap-1.5 text-right font-medium"
                        >
                            <Check
                                v-if="spec.value === 'Included'"
                                class="size-3.5 text-gold"
                            />
                            {{ spec.value }}
                        </dd>
                    </div>
                </dl>
                <p
                    class="mt-6 flex items-center gap-2 text-xs text-muted-foreground"
                >
                    <Link2 class="size-3.5" /> Photographs show the actual watch
                    for sale.
                </p>
            </section>
        </div>

        <section v-if="related.length" class="mt-28">
            <SectionHeading
                v-reveal
                eyebrow="Keep exploring"
                title="You may also like"
            />
            <div
                class="mt-12 grid grid-cols-2 gap-x-4 gap-y-12 sm:gap-x-6 lg:grid-cols-4"
            >
                <WatchCard
                    v-for="(item, index) in related"
                    :key="item.id"
                    v-reveal="index * 80"
                    :watch="item"
                />
            </div>
        </section>
    </div>

    <Transition
        enter-active-class="transition duration-500 ease-(--ease-out-expo)"
        enter-from-class="translate-y-full"
        leave-active-class="transition duration-300"
        leave-to-class="translate-y-full"
    >
        <div
            v-if="showStickyBar"
            class="fixed inset-x-0 bottom-0 z-30 border-t bg-background/90 backdrop-blur-xl lg:hidden"
        >
            <div class="flex items-center gap-4 px-4 py-3">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium">{{ watch.name }}</p>
                    <p class="text-sm text-muted-foreground tabular-nums">
                        {{ price(watch.price) }}
                    </p>
                </div>
                <InquireButton
                    :watch="watch"
                    label="Enquire"
                    class="h-11 rounded-full px-6"
                />
            </div>
        </div>
    </Transition>
</template>
