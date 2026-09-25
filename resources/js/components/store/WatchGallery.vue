<script setup lang="ts">
import {
    ChevronLeft,
    ChevronRight,
    Expand,
    Watch as WatchIcon,
    X,
} from '@lucide/vue';
import { useEventListener } from '@vueuse/core';
import { computed, ref, useTemplateRef } from 'vue';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import type { WatchImage } from '@/types';

const props = defineProps<{
    images: WatchImage[];
    title: string;
}>();

const active = ref(0);
const lightboxOpen = ref(false);
const zoomed = ref(false);
const zoomOrigin = ref('50% 50%');
const track = useTemplateRef<HTMLDivElement>('track');

const current = computed(() => props.images[active.value]);
const hasMany = computed(() => props.images.length > 1);

function select(index: number): void {
    const count = props.images.length;

    active.value = (index + count) % count;
    zoomed.value = false;
    track.value?.scrollTo({
        left: track.value.clientWidth * active.value,
        behavior: 'smooth',
    });
}

function onTrackScroll(): void {
    if (!track.value) {
        return;
    }

    active.value = Math.round(track.value.scrollLeft / track.value.clientWidth);
}

function toggleZoom(event: MouseEvent): void {
    const target = event.currentTarget as HTMLElement;
    const rect = target.getBoundingClientRect();

    zoomOrigin.value = `${((event.clientX - rect.left) / rect.width) * 100}% ${((event.clientY - rect.top) / rect.height) * 100}%`;
    zoomed.value = !zoomed.value;
}

function panZoom(event: MouseEvent): void {
    if (!zoomed.value) {
        return;
    }

    const rect = (event.currentTarget as HTMLElement).getBoundingClientRect();

    zoomOrigin.value = `${((event.clientX - rect.left) / rect.width) * 100}% ${((event.clientY - rect.top) / rect.height) * 100}%`;
}

useEventListener('keydown', (event: KeyboardEvent) => {
    if (!lightboxOpen.value || !hasMany.value) {
        return;
    }

    if (event.key === 'ArrowRight') {
        select(active.value + 1);
    }

    if (event.key === 'ArrowLeft') {
        select(active.value - 1);
    }
});
</script>

<template>
    <div v-if="images.length" class="flex flex-col-reverse gap-4 lg:flex-row">
        <div
            v-if="hasMany"
            class="hidden scrollbar-none gap-3 overflow-auto lg:flex lg:max-h-[680px] lg:w-20 lg:flex-col"
            role="tablist"
            aria-label="Photos"
        >
            <button
                v-for="(image, index) in images"
                :key="image.id"
                type="button"
                role="tab"
                :aria-selected="index === active"
                :aria-label="`Show photo ${index + 1}`"
                class="relative aspect-[4/5] w-20 shrink-0 overflow-hidden rounded-sm transition-all duration-300"
                :class="
                    index === active
                        ? 'opacity-100 ring-1 ring-foreground ring-offset-2 ring-offset-background'
                        : 'opacity-55 hover:opacity-100'
                "
                @click="select(index)"
            >
                <img
                    :src="image.url"
                    :alt="image.alt ?? ''"
                    class="size-full object-cover"
                    loading="lazy"
                />
            </button>
        </div>

        <div class="group relative flex-1">
            <div
                ref="track"
                class="flex snap-x snap-mandatory scrollbar-none overflow-x-auto rounded-sm bg-muted lg:overflow-hidden"
                @scroll.passive="onTrackScroll"
            >
                <button
                    v-for="(image, index) in images"
                    :key="image.id"
                    type="button"
                    class="relative aspect-[4/5] w-full shrink-0 cursor-zoom-in snap-center overflow-hidden"
                    :aria-label="`Open photo ${index + 1} full screen`"
                    @click="lightboxOpen = true"
                >
                    <img
                        :src="image.url"
                        :alt="image.alt ?? title"
                        width="1600"
                        height="2000"
                        :loading="index === 0 ? 'eager' : 'lazy'"
                        :fetchpriority="index === 0 ? 'high' : undefined"
                        decoding="async"
                        class="size-full object-cover"
                        :data-vt-hero="index === 0 ? '' : undefined"
                        :style="
                            index === 0
                                ? { viewTransitionName: 'watch-hero' }
                                : undefined
                        "
                    />
                </button>
            </div>

            <template v-if="hasMany">
                <button
                    type="button"
                    class="absolute top-1/2 left-4 hidden size-11 -translate-y-1/2 items-center justify-center rounded-full bg-background/85 opacity-0 shadow-lg backdrop-blur transition-opacity group-hover:opacity-100 focus-visible:opacity-100 lg:flex"
                    aria-label="Previous photo"
                    @click="select(active - 1)"
                >
                    <ChevronLeft class="size-5" />
                </button>
                <button
                    type="button"
                    class="absolute top-1/2 right-4 hidden size-11 -translate-y-1/2 items-center justify-center rounded-full bg-background/85 opacity-0 shadow-lg backdrop-blur transition-opacity group-hover:opacity-100 focus-visible:opacity-100 lg:flex"
                    aria-label="Next photo"
                    @click="select(active + 1)"
                >
                    <ChevronRight class="size-5" />
                </button>
                <div
                    class="absolute inset-x-0 bottom-4 flex justify-center gap-1.5 lg:hidden"
                >
                    <span
                        v-for="(image, index) in images"
                        :key="image.id"
                        class="h-1.5 rounded-full bg-white shadow transition-all duration-300"
                        :class="index === active ? 'w-5' : 'w-1.5 opacity-60'"
                    />
                </div>
            </template>

            <span
                class="pointer-events-none absolute top-4 right-4 flex items-center gap-1.5 rounded-full bg-background/85 px-3 py-1.5 text-xs font-medium opacity-0 shadow backdrop-blur transition-opacity group-hover:opacity-100"
            >
                <Expand class="size-3.5" /> {{ active + 1 }} /
                {{ images.length }}
            </span>
        </div>

        <Dialog v-model:open="lightboxOpen" @update:open="zoomed = false">
            <DialogContent
                :show-close-button="false"
                class="h-svh max-h-svh w-screen max-w-none! rounded-none border-0 bg-neutral-950/97 p-0 text-white sm:max-w-none"
            >
                <DialogTitle class="sr-only">{{ title }}</DialogTitle>
                <DialogDescription class="sr-only">
                    Photo {{ active + 1 }} of {{ images.length }}. Click the
                    photo to zoom.
                </DialogDescription>

                <div
                    class="relative flex size-full items-center justify-center overflow-hidden"
                    :class="zoomed ? 'cursor-zoom-out' : 'cursor-zoom-in'"
                    @click="toggleZoom"
                    @mousemove="panZoom"
                >
                    <Transition
                        mode="out-in"
                        enter-active-class="transition duration-300"
                        enter-from-class="opacity-0 scale-[0.98]"
                        leave-active-class="transition duration-150"
                        leave-to-class="opacity-0"
                    >
                        <img
                            v-if="current"
                            :key="current.id"
                            :src="current.url"
                            :alt="current.alt ?? title"
                            class="max-h-full max-w-full object-contain transition-transform duration-300 ease-out select-none"
                            :style="{
                                transform: zoomed ? 'scale(2.2)' : 'scale(1)',
                                transformOrigin: zoomOrigin,
                            }"
                            draggable="false"
                        />
                    </Transition>
                </div>

                <div
                    class="absolute inset-x-0 top-0 flex items-center justify-between p-5"
                >
                    <p class="text-sm text-white/70 tabular-nums">
                        {{ active + 1 }} / {{ images.length }}
                    </p>
                    <DialogClose
                        class="flex size-11 items-center justify-center rounded-full bg-white/10 transition-colors hover:bg-white/20"
                        aria-label="Close"
                    >
                        <X class="size-5" />
                    </DialogClose>
                </div>

                <template v-if="hasMany">
                    <button
                        type="button"
                        class="absolute top-1/2 left-4 flex size-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 transition-colors hover:bg-white/20"
                        aria-label="Previous photo"
                        @click.stop="select(active - 1)"
                    >
                        <ChevronLeft class="size-6" />
                    </button>
                    <button
                        type="button"
                        class="absolute top-1/2 right-4 flex size-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 transition-colors hover:bg-white/20"
                        aria-label="Next photo"
                        @click.stop="select(active + 1)"
                    >
                        <ChevronRight class="size-6" />
                    </button>
                </template>
            </DialogContent>
        </Dialog>
    </div>

    <div
        v-else
        class="flex aspect-[4/5] items-center justify-center rounded-sm bg-muted text-muted-foreground/50"
    >
        <WatchIcon class="size-16" stroke-width="1" />
    </div>
</template>
