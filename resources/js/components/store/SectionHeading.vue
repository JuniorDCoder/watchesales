<script setup lang="ts">
withDefaults(
    defineProps<{
        eyebrow?: string | null;
        title: string;
        description?: string | null;
        align?: 'left' | 'center';
        as?: 'h1' | 'h2';
    }>(),
    { align: 'left', as: 'h2', eyebrow: null, description: null },
);
</script>

<template>
    <div
        class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between"
        :class="{
            'items-center text-center md:flex-col md:items-center':
                align === 'center',
        }"
    >
        <div class="max-w-2xl" :class="{ 'mx-auto': align === 'center' }">
            <p
                v-if="eyebrow"
                class="mb-3 flex items-center gap-3 text-[11px] font-medium tracking-[0.28em] text-gold uppercase"
                :class="{ 'justify-center': align === 'center' }"
            >
                <span class="h-px w-8 bg-gold/60" aria-hidden="true" />
                {{ eyebrow }}
            </p>
            <component
                :is="as"
                class="font-display text-4xl leading-[1.05] font-medium text-balance md:text-5xl"
            >
                {{ title }}
            </component>
            <p
                v-if="description"
                class="mt-4 text-base leading-relaxed text-pretty text-muted-foreground"
            >
                {{ description }}
            </p>
        </div>
        <div v-if="$slots.action" class="shrink-0">
            <slot name="action" />
        </div>
    </div>
</template>
