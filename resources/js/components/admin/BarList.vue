<script setup lang="ts">
defineProps<{
    items: { name: string; value: number; share: number }[];
    valueLabel?: string;
}>();
</script>

<template>
    <ul class="space-y-4">
        <li v-for="item in items" :key="item.name">
            <div class="flex items-baseline justify-between gap-4 text-sm">
                <span class="truncate">{{ item.name }}</span>
                <span class="shrink-0 tabular-nums">
                    <span class="font-medium">{{ item.value }}</span>
                    <span class="ml-1.5 text-xs text-muted-foreground"
                        >{{ item.share }}%</span
                    >
                </span>
            </div>
            <div
                class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-muted"
                role="img"
                :aria-label="`${item.name}: ${item.value} ${valueLabel ?? ''}, ${item.share}%`"
            >
                <div
                    class="h-full rounded-full bg-chart-1 transition-[width] duration-700"
                    :style="{
                        width: `${Math.max(item.share, item.value > 0 ? 2 : 0)}%`,
                    }"
                />
            </div>
        </li>
    </ul>
</template>
