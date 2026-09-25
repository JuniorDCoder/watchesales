<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, Watch as WatchIcon } from '@lucide/vue';
import { useTemplateRef } from 'vue';
import StatusBadge from '@/components/store/StatusBadge.vue';
import { useSite } from '@/composables/useSite';
import { show } from '@/routes/watches';
import type { Watch } from '@/types';

const props = withDefaults(
    defineProps<{
        watch: Watch;
        eager?: boolean;
    }>(),
    { eager: false },
);

const { price } = useSite();
const image = useTemplateRef<HTMLImageElement>('image');

/**
 * Give only the clicked card's photo the shared transition name, so it can
 * morph into the gallery on the detail page without clashing with other cards.
 */
function prepareTransition(): void {
    document
        .querySelectorAll<HTMLElement>('[data-vt-hero]')
        .forEach((element) => (element.style.viewTransitionName = 'none'));

    if (image.value) {
        image.value.dataset.vtHero = '';
        image.value.style.viewTransitionName = 'watch-hero';
    }
}
</script>

<template>
    <article class="group relative">
        <Link
            :href="show(props.watch)"
            prefetch
            view-transition
            class="block rounded-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-4 focus-visible:ring-offset-background focus-visible:outline-none"
            @click="prepareTransition"
        >
            <div
                class="relative aspect-[4/5] overflow-hidden rounded-sm bg-muted"
            >
                <img
                    v-if="watch.image"
                    ref="image"
                    :src="watch.image.url"
                    :alt="
                        watch.image.alt ?? `${watch.brand?.name} ${watch.name}`
                    "
                    width="1600"
                    height="2000"
                    :loading="eager ? 'eager' : 'lazy'"
                    decoding="async"
                    class="size-full object-cover transition-transform duration-[1400ms] ease-(--ease-out-expo) group-hover:scale-[1.06]"
                    :class="{ 'grayscale-[35%]': watch.status === 'sold' }"
                />
                <div
                    v-else
                    class="flex size-full items-center justify-center text-muted-foreground/50"
                >
                    <WatchIcon class="size-12" stroke-width="1" />
                </div>

                <div
                    class="pointer-events-none absolute inset-x-0 bottom-0 h-1/2 bg-linear-to-t from-black/55 to-transparent opacity-0 transition-opacity duration-500 group-hover:opacity-100"
                />

                <StatusBadge
                    v-if="watch.status !== 'available'"
                    :status="watch.status"
                    :label="watch.status_label"
                    class="absolute top-3 left-3"
                />

                <span
                    class="absolute inset-x-4 bottom-4 flex translate-y-3 items-center justify-between text-[11px] font-medium tracking-[0.24em] text-white uppercase opacity-0 transition-all duration-500 ease-(--ease-out-expo) group-hover:translate-y-0 group-hover:opacity-100"
                >
                    View details
                    <ArrowUpRight class="size-4" />
                </span>
            </div>

            <div class="mt-4 flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p
                        class="text-[11px] font-medium tracking-[0.22em] text-muted-foreground uppercase"
                    >
                        {{ watch.brand?.name }}
                    </p>
                    <h3
                        class="mt-1 font-display text-[1.35rem] leading-tight font-medium text-balance"
                    >
                        {{ watch.name }}
                    </h3>
                    <p class="mt-1.5 text-xs text-muted-foreground">
                        {{ watch.condition_label
                        }}<template v-if="watch.year">
                            <span class="mx-1.5" aria-hidden="true">·</span
                            >{{ watch.year }}</template
                        >
                    </p>
                </div>
                <p
                    class="shrink-0 pt-5 text-sm font-medium tabular-nums"
                    :class="{
                        'text-muted-foreground line-through decoration-1':
                            watch.status === 'sold',
                    }"
                >
                    {{ price(watch.price) }}
                </p>
            </div>
        </Link>
    </article>
</template>
