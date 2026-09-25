<script setup lang="ts">
import { Watch as WatchIcon } from '@lucide/vue';

defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex flex-col items-center justify-center rounded-lg border border-dashed px-6 py-20 text-center"
    >
        <div
            class="mb-5 flex size-14 items-center justify-center rounded-full bg-muted text-muted-foreground"
        >
            <WatchIcon class="size-6" stroke-width="1.5" />
        </div>
        <h3 class="font-display text-2xl font-medium">{{ title }}</h3>
        <p
            v-if="description"
            class="mt-2 max-w-sm text-sm text-muted-foreground"
        >
            {{ description }}
        </p>
        <div v-if="$slots.default" class="mt-6">
            <slot />
        </div>
    </div>
</template>
