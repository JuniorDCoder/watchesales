<script setup lang="ts">
import { Watch } from '@lucide/vue';
import { useSite } from '@/composables/useSite';

const { site } = useSite();
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
    >
        <img
            v-if="site.faviconUrl"
            :src="site.faviconUrl"
            :alt="site.name"
            class="size-full object-cover"
        />
        <Watch v-else class="size-[18px]" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span
            class="mb-0.5 truncate font-display text-lg leading-tight font-semibold"
            >{{ site.name }}</span
        >
    </div>
</template>
