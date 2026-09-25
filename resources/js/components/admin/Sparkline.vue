<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    values: number[];
}>();

const width = 120;
const height = 32;

const path = computed(() => {
    const max = Math.max(...props.values, 1);
    const step = width / Math.max(props.values.length - 1, 1);

    return props.values
        .map(
            (value, index) =>
                `${index === 0 ? 'M' : 'L'}${(index * step).toFixed(1)},${(height - 2 - (value / max) * (height - 4)).toFixed(1)}`,
        )
        .join('');
});
</script>

<template>
    <svg
        :viewBox="`0 0 ${width} ${height}`"
        preserveAspectRatio="none"
        class="h-8 w-full overflow-visible"
        aria-hidden="true"
    >
        <path
            :d="path"
            fill="none"
            class="stroke-chart-1"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            vector-effect="non-scaling-stroke"
        />
    </svg>
</template>
