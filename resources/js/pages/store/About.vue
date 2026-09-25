<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import { computed } from 'vue';
import InquireButton from '@/components/store/InquireButton.vue';
import { Button } from '@/components/ui/button';
import { useSite } from '@/composables/useSite';
import { index as watchesIndex } from '@/routes/watches';

const props = defineProps<{
    about: {
        title: string | null;
        body: string | null;
        imageUrl: string | null;
    };
    stats: { available: number; sold: number; brands: number };
}>();

const { site } = useSite();

const paragraphs = computed(() =>
    (props.about.body ?? '')
        .split(/\n{2,}/)
        .map((text) => text.trim())
        .filter(Boolean),
);

const values = [
    {
        title: 'Condition, told straight',
        text: 'We grade every watch honestly and photograph the flaws as carefully as the highlights. What you see is what arrives.',
    },
    {
        title: 'Provenance matters',
        text: 'Serial numbers are checked, service histories are recorded and papers are verified wherever they exist.',
    },
    {
        title: 'Fair, transparent pricing',
        text: 'Our prices follow the real market, not the hype. If a watch is priced on request, it is only because the market is moving.',
    },
];
</script>

<template>
    <section
        class="mx-auto grid max-w-7xl gap-12 px-4 pt-14 pb-20 sm:px-6 md:pt-20 lg:grid-cols-12 lg:gap-16 lg:px-8"
    >
        <div class="lg:col-span-6 lg:py-10">
            <p
                class="animate-rise text-[11px] font-medium tracking-[0.28em] text-gold uppercase"
            >
                About {{ site.name }}
            </p>
            <h1
                class="mt-4 animate-rise font-display text-5xl leading-[1.02] font-medium text-balance [animation-delay:80ms] md:text-7xl"
            >
                {{ about.title }}
            </h1>
            <div
                class="mt-8 animate-rise space-y-5 text-lg leading-relaxed text-pretty text-muted-foreground [animation-delay:160ms]"
            >
                <p v-for="(paragraph, index) in paragraphs" :key="index">
                    {{ paragraph }}
                </p>
            </div>
            <div
                class="mt-10 flex animate-rise flex-wrap gap-3 [animation-delay:240ms]"
            >
                <Button as-child class="h-12 gap-2 rounded-full px-7">
                    <Link :href="watchesIndex()">
                        Browse the collection <ArrowRight class="size-4" />
                    </Link>
                </Button>
                <InquireButton
                    variant="outline"
                    label="Get in touch"
                    class="h-12 rounded-full px-7"
                />
            </div>
        </div>
        <div class="relative lg:col-span-6">
            <div
                class="aspect-[4/5] overflow-hidden rounded-sm bg-muted lg:absolute lg:inset-0 lg:aspect-auto"
            >
                <img
                    v-if="about.imageUrl"
                    :src="about.imageUrl"
                    alt=""
                    class="size-full animate-ken-burns object-cover"
                />
            </div>
        </div>
    </section>

    <section class="border-y bg-muted/40">
        <dl
            class="mx-auto grid max-w-7xl grid-cols-1 divide-y px-4 sm:grid-cols-3 sm:divide-x sm:divide-y-0 sm:px-6 lg:px-8"
        >
            <div v-reveal class="px-2 py-10 text-center">
                <dt class="text-sm text-muted-foreground">
                    Watches available now
                </dt>
                <dd class="mt-2 font-display text-6xl tabular-nums">
                    {{ stats.available }}
                </dd>
            </div>
            <div v-reveal="100" class="px-2 py-10 text-center">
                <dt class="text-sm text-muted-foreground">
                    Watches found new homes
                </dt>
                <dd class="mt-2 font-display text-6xl tabular-nums">
                    {{ stats.sold }}
                </dd>
            </div>
            <div v-reveal="200" class="px-2 py-10 text-center">
                <dt class="text-sm text-muted-foreground">Brands we source</dt>
                <dd class="mt-2 font-display text-6xl tabular-nums">
                    {{ stats.brands }}
                </dd>
            </div>
        </dl>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-24 sm:px-6 md:py-32 lg:px-8">
        <h2
            v-reveal
            class="max-w-2xl font-display text-4xl leading-tight font-medium text-balance md:text-5xl"
        >
            What we believe in
        </h2>
        <div class="mt-14 grid gap-12 md:grid-cols-3">
            <div
                v-for="(value, index) in values"
                :key="value.title"
                v-reveal="index * 100"
                class="border-t pt-8"
            >
                <span class="font-display text-2xl text-gold"
                    >0{{ index + 1 }}</span
                >
                <h3 class="mt-3 text-xl font-medium">{{ value.title }}</h3>
                <p class="mt-3 text-sm leading-relaxed text-muted-foreground">
                    {{ value.text }}
                </p>
            </div>
        </div>
    </section>
</template>
