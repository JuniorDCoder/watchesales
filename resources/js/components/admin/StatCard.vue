<script setup lang="ts">
import type { Component } from 'vue';
import { computed } from 'vue';
import Sparkline from '@/components/admin/Sparkline.vue';

const props = defineProps<{
    label: string;
    value: string;
    icon: Component;
    hint?: string;
    current?: number;
    previous?: number;
    comparison?: string;
    trend?: number[];
}>();

/** Percentage change against the previous period, or null when there is nothing to compare with. */
const change = computed(() => {
    if (
        props.current === undefined ||
        props.previous === undefined ||
        props.previous === 0
    ) {
        return null;
    }

    return Math.round(
        ((props.current - props.previous) / props.previous) * 100,
    );
});
</script>

<template>
    <div class="flex flex-col rounded-xl border bg-card p-5">
        <div class="flex items-center justify-between">
            <p class="text-sm text-muted-foreground">{{ label }}</p>
            <component :is="icon" class="size-4 text-muted-foreground" />
        </div>
        <p class="mt-3 text-3xl font-semibold tracking-tight tabular-nums">
            {{ value }}
        </p>
        <p
            class="mt-1 flex flex-wrap items-center gap-x-2 text-xs text-muted-foreground"
        >
            <span
                v-if="change !== null"
                class="font-medium"
                :class="
                    change >= 0
                        ? 'text-emerald-600 dark:text-emerald-400'
                        : 'text-rose-600 dark:text-rose-400'
                "
            >
                {{ change > 0 ? '+' : '' }}{{ change }}%
            </span>
            <span
                v-else-if="
                    current !== undefined && previous === 0 && current > 0
                "
                class="font-medium text-emerald-600 dark:text-emerald-400"
            >
                New
            </span>
            {{
                change !== null || (previous === 0 && (current ?? 0) > 0)
                    ? comparison
                    : hint
            }}
        </p>
        <div
            v-if="trend && trend.some((value) => value > 0)"
            class="mt-auto pt-4"
        >
            <Sparkline :values="trend" />
        </div>
    </div>
</template>
