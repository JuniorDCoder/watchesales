<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed } from 'vue';
import type { Paginated } from '@/types';

const props = defineProps<{
    meta: Paginated<unknown>['meta'];
    links: Paginated<unknown>['links'];
}>();

const pages = computed(() => props.meta.links.slice(1, -1));
</script>

<template>
    <nav
        v-if="meta.last_page > 1"
        class="flex items-center justify-center gap-1"
        aria-label="Pagination"
    >
        <component
            :is="links.prev ? Link : 'span'"
            :href="links.prev ?? undefined"
            preserve-scroll
            rel="prev"
            class="flex size-10 items-center justify-center rounded-full transition-colors hover:bg-accent"
            :class="{ 'pointer-events-none opacity-40': !links.prev }"
            aria-label="Previous page"
        >
            <ChevronLeft class="size-4" />
        </component>

        <template v-for="(link, index) in pages" :key="index">
            <span
                v-if="!link.url"
                class="flex size-10 items-center justify-center text-sm text-muted-foreground"
                >&hellip;</span
            >
            <Link
                v-else
                :href="link.url"
                class="flex size-10 items-center justify-center rounded-full text-sm tabular-nums transition-colors"
                :class="
                    link.active
                        ? 'bg-primary text-primary-foreground'
                        : 'hover:bg-accent'
                "
                :aria-current="link.active ? 'page' : undefined"
            >
                {{ link.label }}
            </Link>
        </template>

        <component
            :is="links.next ? Link : 'span'"
            :href="links.next ?? undefined"
            rel="next"
            class="flex size-10 items-center justify-center rounded-full transition-colors hover:bg-accent"
            :class="{ 'pointer-events-none opacity-40': !links.next }"
            aria-label="Next page"
        >
            <ChevronRight class="size-4" />
        </component>
    </nav>
</template>
