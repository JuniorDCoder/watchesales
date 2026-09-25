<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    ArrowUpRight,
    MessagesSquare,
    PackageCheck,
    ShieldCheck,
    Wrench,
} from '@lucide/vue';
import { computed } from 'vue';
import InquireButton from '@/components/store/InquireButton.vue';
import SectionHeading from '@/components/store/SectionHeading.vue';
import WatchCard from '@/components/store/WatchCard.vue';
import { Button } from '@/components/ui/button';
import { useSite } from '@/composables/useSite';
import { show as showBrand } from '@/routes/brands';
import { show as showCollection } from '@/routes/collections';
import { index as watchesIndex, show as showWatch } from '@/routes/watches';
import type { Watch } from '@/types';

defineOptions({
    layout: { transparentHeader: true },
});

type CategoryTile = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    count: number;
    imageUrl: string | null;
};

const props = defineProps<{
    hero: {
        eyebrow: string | null;
        title: string | null;
        subtitle: string | null;
        imageUrl: string | null;
    };
    featured: Watch[];
    latest: Watch[];
    categories: CategoryTile[];
    brands: { id: number; name: string; slug: string }[];
    stats: { available: number; brands: number };
}>();

const { site, price } = useSite();

const spotlight = computed(() => props.featured[0] ?? props.latest[0] ?? null);
const heroImage = computed(
    () => props.hero.imageUrl ?? spotlight.value?.image?.url ?? null,
);
const marqueeBrands = computed(() => {
    const names = props.brands.length
        ? props.brands
        : [{ id: 0, name: site.value.name, slug: '' }];

    return [...names, ...names, ...names, ...names];
});

const promises = [
    {
        icon: ShieldCheck,
        title: 'Authenticated',
        text: 'Every watch is checked against manufacturer records and inspected under magnification before it is listed.',
    },
    {
        icon: Wrench,
        title: 'Serviced and tested',
        text: 'Our watchmaker times each movement and services it where needed, so it arrives running as it should.',
    },
    {
        icon: PackageCheck,
        title: 'Insured delivery',
        text: 'Fully insured, discreet shipping with signature on delivery. Collection by appointment is also available.',
    },
    {
        icon: MessagesSquare,
        title: 'Real people',
        text: 'Speak directly with the specialist who inspected your watch. Honest advice, no pressure and no scripts.',
    },
];

const steps = [
    {
        title: 'Find your watch',
        text: 'Browse the collection by style, brand or budget. Every listing shows real photographs and full specifications.',
    },
    {
        title: 'Talk to a specialist',
        text: 'Ask anything about condition, history or price. We can send extra photos or a video of the watch on the wrist.',
    },
    {
        title: 'Wear it with confidence',
        text: 'Secure payment, insured delivery and our own warranty. Your watch arrives ready to wear from day one.',
    },
];
</script>

<template>
    <!-- Hero -->
    <section
        class="relative isolate flex min-h-[92svh] items-end overflow-hidden bg-neutral-950 text-white"
    >
        <div class="absolute inset-0 -z-10">
            <img
                v-if="heroImage"
                :src="heroImage"
                alt=""
                class="size-full animate-ken-burns object-cover opacity-80"
                fetchpriority="high"
                decoding="async"
            />
            <div
                class="absolute inset-0 bg-linear-to-t from-neutral-950 via-neutral-950/45 to-neutral-950/30"
            />
            <div
                class="absolute inset-0 bg-linear-to-r from-neutral-950/70 via-transparent to-transparent"
            />
        </div>

        <div
            class="mx-auto grid w-full max-w-7xl gap-12 px-4 pt-40 pb-16 sm:px-6 md:pb-24 lg:grid-cols-12 lg:items-end lg:px-8"
        >
            <div class="lg:col-span-8">
                <p
                    v-if="hero.eyebrow"
                    class="flex animate-rise items-center gap-3 text-[11px] font-medium tracking-[0.32em] text-gold uppercase"
                >
                    <span class="h-px w-10 bg-gold/70" aria-hidden="true" />
                    {{ hero.eyebrow }}
                </p>
                <h1
                    class="mt-6 animate-rise font-display text-[clamp(3rem,8vw,7rem)] leading-[0.95] font-medium tracking-[-0.01em] text-balance [animation-delay:120ms]"
                >
                    {{ hero.title ?? site.name }}
                </h1>
                <p
                    v-if="hero.subtitle"
                    class="mt-7 max-w-xl animate-rise text-base leading-relaxed text-pretty text-white/75 [animation-delay:240ms] md:text-lg"
                >
                    {{ hero.subtitle }}
                </p>
                <div
                    class="mt-10 flex animate-rise flex-wrap items-center gap-3 [animation-delay:360ms]"
                >
                    <Button
                        as-child
                        size="lg"
                        class="h-12 gap-2 rounded-full bg-white px-7 text-neutral-900 hover:bg-white/90"
                    >
                        <Link :href="watchesIndex()">
                            Explore the collection
                            <ArrowRight class="size-4" />
                        </Link>
                    </Button>
                    <InquireButton
                        variant="ghost"
                        label="Speak to a specialist"
                        class="h-12 rounded-full border border-white/30 px-7 text-white hover:bg-white/10 hover:text-white"
                    />
                </div>
            </div>

            <Link
                v-if="spotlight"
                :href="showWatch(spotlight)"
                class="group hidden animate-rise items-center gap-4 rounded-2xl border border-white/15 bg-white/10 p-3 pr-5 backdrop-blur-xl transition-colors [animation-delay:520ms] hover:bg-white/15 lg:col-span-4 lg:flex lg:justify-self-end"
            >
                <img
                    v-if="spotlight.image"
                    :src="spotlight.image.url"
                    :alt="spotlight.image.alt ?? spotlight.name"
                    class="size-20 rounded-xl object-cover"
                />
                <div class="min-w-0">
                    <p
                        class="text-[10px] tracking-[0.24em] text-white/60 uppercase"
                    >
                        In the spotlight
                    </p>
                    <p class="mt-1 truncate font-display text-xl">
                        {{ spotlight.brand?.name }} {{ spotlight.name }}
                    </p>
                    <p class="mt-0.5 text-sm text-white/70 tabular-nums">
                        {{ price(spotlight.price) }}
                    </p>
                </div>
                <ArrowUpRight
                    class="ml-2 size-5 shrink-0 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                />
            </Link>
        </div>
    </section>

    <!-- Brands -->
    <section
        v-if="brands.length"
        class="border-b py-7"
        aria-label="Brands in the collection"
    >
        <div class="mask-fade-x flex overflow-hidden">
            <div
                class="flex shrink-0 animate-marquee items-center gap-14 pr-14 hover:[animation-play-state:paused]"
            >
                <component
                    :is="brand.slug ? Link : 'span'"
                    v-for="(brand, index) in marqueeBrands"
                    :key="`${brand.id}-${index}`"
                    :href="brand.slug ? showBrand(brand) : undefined"
                    class="flex items-center gap-14 font-display text-3xl whitespace-nowrap text-muted-foreground transition-colors hover:text-foreground"
                    :tabindex="index >= brands.length ? -1 : undefined"
                    :aria-hidden="index >= brands.length ? 'true' : undefined"
                >
                    {{ brand.name }}
                    <svg
                        viewBox="0 0 10 10"
                        class="size-2 text-gold"
                        aria-hidden="true"
                    >
                        <path d="M5 0 10 5 5 10 0 5Z" fill="currentColor" />
                    </svg>
                </component>
            </div>
        </div>
    </section>

    <!-- Featured -->
    <section
        v-if="featured.length"
        class="mx-auto max-w-7xl px-4 py-24 sm:px-6 md:py-32 lg:px-8"
    >
        <SectionHeading
            v-reveal
            eyebrow="Featured"
            title="Pieces we are excited about"
            description="A handpicked selection from the current collection, chosen for condition, rarity and sheer wearability."
        >
            <template #action>
                <Link
                    :href="watchesIndex()"
                    class="group inline-flex items-center gap-2 text-sm font-medium"
                >
                    View all watches
                    <ArrowRight
                        class="size-4 transition-transform group-hover:translate-x-1"
                    />
                </Link>
            </template>
        </SectionHeading>

        <div
            class="mt-14 grid gap-x-6 gap-y-14 sm:grid-cols-2"
            :class="
                featured.length % 4 === 0 ? 'lg:grid-cols-4' : 'lg:grid-cols-3'
            "
        >
            <WatchCard
                v-for="(watch, index) in featured"
                :key="watch.id"
                v-reveal="(index % 3) * 90"
                :watch="watch"
            />
        </div>
    </section>

    <!-- Collections -->
    <section
        v-if="categories.length"
        class="border-y bg-muted/40 py-24 md:py-32"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <SectionHeading
                v-reveal
                eyebrow="Collections"
                title="Shop by style"
                description="From serious tool watches to quiet dress pieces, find the character that suits you."
            />
        </div>

        <div
            class="mx-auto mt-14 flex max-w-7xl snap-x snap-mandatory scrollbar-none gap-4 overflow-x-auto px-4 sm:px-6 md:grid md:grid-cols-3 md:gap-5 md:overflow-visible lg:px-8"
        >
            <Link
                v-for="(category, index) in categories"
                :key="category.id"
                v-reveal="(index % 3) * 90"
                :href="showCollection(category)"
                class="group relative isolate flex aspect-[3/4] w-[75vw] shrink-0 snap-start flex-col justify-end overflow-hidden rounded-sm bg-neutral-900 p-6 text-white sm:w-[45vw] md:aspect-[4/5] md:w-auto"
            >
                <img
                    v-if="category.imageUrl"
                    :src="category.imageUrl"
                    :alt="category.name"
                    loading="lazy"
                    decoding="async"
                    class="absolute inset-0 -z-10 size-full object-cover opacity-85 transition-transform duration-[1400ms] ease-(--ease-out-expo) group-hover:scale-105"
                />
                <div
                    class="absolute inset-0 -z-10 bg-linear-to-t from-black/80 via-black/20 to-transparent"
                />
                <p
                    class="text-[11px] tracking-[0.24em] text-white/70 uppercase"
                >
                    {{ category.count }}
                    {{ category.count === 1 ? 'watch' : 'watches' }}
                </p>
                <h3 class="mt-2 font-display text-3xl font-medium">
                    {{ category.name }}
                </h3>
                <p
                    v-if="category.description"
                    class="mt-2 line-clamp-2 max-h-0 text-sm text-white/75 opacity-0 transition-all duration-500 group-hover:max-h-12 group-hover:opacity-100"
                >
                    {{ category.description }}
                </p>
                <span
                    class="absolute top-5 right-5 flex size-10 items-center justify-center rounded-full border border-white/30 backdrop-blur transition-colors group-hover:bg-white group-hover:text-neutral-900"
                >
                    <ArrowUpRight class="size-4" />
                </span>
            </Link>
        </div>
    </section>

    <!-- Promise -->
    <section class="bg-primary text-primary-foreground">
        <div
            class="mx-auto grid max-w-7xl gap-16 px-4 py-24 sm:px-6 md:py-32 lg:grid-cols-12 lg:px-8"
        >
            <div v-reveal class="lg:col-span-5">
                <p
                    class="flex items-center gap-3 text-[11px] font-medium tracking-[0.28em] text-gold uppercase"
                >
                    <span class="h-px w-8 bg-gold/60" aria-hidden="true" />
                    Our promise
                </p>
                <h2
                    class="mt-4 font-display text-4xl leading-[1.05] font-medium text-balance md:text-5xl"
                >
                    Buying a fine watch should feel as good as wearing one.
                </h2>
                <dl
                    class="mt-12 grid grid-cols-2 gap-8 border-t border-current/15 pt-8"
                >
                    <div>
                        <dt class="text-xs tracking-wide opacity-60">
                            Available now
                        </dt>
                        <dd class="mt-1 font-display text-5xl tabular-nums">
                            {{ stats.available }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs tracking-wide opacity-60">
                            Brands sourced
                        </dt>
                        <dd class="mt-1 font-display text-5xl tabular-nums">
                            {{ stats.brands }}
                        </dd>
                    </div>
                </dl>
            </div>
            <div class="grid gap-x-10 gap-y-12 sm:grid-cols-2 lg:col-span-7">
                <div
                    v-for="(promise, index) in promises"
                    :key="promise.title"
                    v-reveal="index * 80"
                >
                    <div
                        class="flex size-12 items-center justify-center rounded-full border border-current/20"
                    >
                        <component
                            :is="promise.icon"
                            class="size-5 text-gold"
                            stroke-width="1.5"
                        />
                    </div>
                    <h3 class="mt-5 text-lg font-medium">
                        {{ promise.title }}
                    </h3>
                    <p class="mt-2 text-sm leading-relaxed opacity-70">
                        {{ promise.text }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- New arrivals -->
    <section
        v-if="latest.length"
        class="mx-auto max-w-7xl px-4 py-24 sm:px-6 md:py-32 lg:px-8"
    >
        <SectionHeading
            v-reveal
            eyebrow="Just in"
            title="New arrivals"
            description="The latest additions to the collection. Good pieces rarely stay long."
        >
            <template #action>
                <Button
                    as-child
                    variant="outline"
                    class="h-11 rounded-full px-6"
                >
                    <Link :href="watchesIndex({ query: { sort: 'newest' } })">
                        See everything new
                    </Link>
                </Button>
            </template>
        </SectionHeading>
        <div
            class="mt-14 grid grid-cols-2 gap-x-4 gap-y-12 sm:gap-x-6 lg:grid-cols-4"
        >
            <WatchCard
                v-for="(watch, index) in latest"
                :key="watch.id"
                v-reveal="(index % 4) * 80"
                :watch="watch"
            />
        </div>
    </section>

    <!-- How it works -->
    <section class="border-t">
        <div class="mx-auto max-w-7xl px-4 py-24 sm:px-6 md:py-32 lg:px-8">
            <SectionHeading
                v-reveal
                eyebrow="How it works"
                title="Three simple steps"
                align="center"
            />
            <ol class="mt-16 grid gap-12 md:grid-cols-3 md:gap-8">
                <li
                    v-for="(step, index) in steps"
                    :key="step.title"
                    v-reveal="index * 120"
                    class="relative border-t pt-8"
                >
                    <span
                        class="font-display text-6xl text-gold/80 tabular-nums"
                        aria-hidden="true"
                        >0{{ index + 1 }}</span
                    >
                    <h3 class="mt-4 text-xl font-medium">{{ step.title }}</h3>
                    <p
                        class="mt-3 text-sm leading-relaxed text-muted-foreground"
                    >
                        {{ step.text }}
                    </p>
                </li>
            </ol>
        </div>
    </section>
</template>
