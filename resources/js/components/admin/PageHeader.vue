<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
    >
        <div>
            <h1
                class="font-display text-3xl font-semibold tracking-tight md:text-4xl"
            >
                {{ title }}
            </h1>
            <p v-if="description" class="mt-1 text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>
        <div v-if="$slots.default" class="flex flex-wrap items-center gap-2">
            <slot />
        </div>
    </div>
</template>
