<script setup lang="ts">
import { useElementSize } from '@vueuse/core';
import { computed, ref, useTemplateRef } from 'vue';

const props = withDefaults(
    defineProps<{
        data: { date: string; total: number }[];
        title: string;
        unit: { one: string; many: string };
        height?: number;
    }>(),
    { height: 180 },
);

const container = useTemplateRef<HTMLDivElement>('container');
const { width } = useElementSize(container);
const hovered = ref<number | null>(null);

const padding = { top: 12, right: 8, bottom: 28, left: 32 };

const dateFormatter = new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
});
const formatDate = (date: string): string =>
    dateFormatter.format(new Date(`${date}T00:00:00`));

/** Round the axis up to a clean number so ticks read naturally. */
const axisMax = computed(() => {
    const max = Math.max(...props.data.map((point) => point.total), 0);

    if (max <= 4) {
        return 4;
    }

    const magnitude = 10 ** Math.floor(Math.log10(max));
    const step = [1, 2, 2.5, 5, 10]
        .map((factor) => factor * magnitude)
        .find((candidate) => candidate * 4 >= max)!;

    return step * 4;
});

const ticks = computed(() =>
    [0, 1, 2, 3, 4].map((index) => (axisMax.value / 4) * index),
);

const plotWidth = computed(() =>
    Math.max(width.value - padding.left - padding.right, 0),
);
const plotHeight = computed(() => props.height - padding.top - padding.bottom);
const band = computed(() => plotWidth.value / Math.max(props.data.length, 1));
const barWidth = computed(() => Math.min(24, Math.max(band.value * 0.62, 2)));

const y = (value: number): number =>
    padding.top + plotHeight.value - (value / axisMax.value) * plotHeight.value;

const bars = computed(() =>
    props.data.map((point, index) => {
        const x =
            padding.left +
            band.value * index +
            (band.value - barWidth.value) / 2;
        const top = y(point.total);
        const barHeight = padding.top + plotHeight.value - top;
        const radius = Math.min(4, barHeight, barWidth.value / 2);
        const base = padding.top + plotHeight.value;
        const right = x + barWidth.value;
        const path =
            barHeight <= 0
                ? ''
                : `M${x},${base}V${top + radius}Q${x},${top} ${x + radius},${top}H${right - radius}Q${right},${top} ${right},${top + radius}V${base}Z`;

        return { ...point, index, x, path, center: x + barWidth.value / 2 };
    }),
);

const labelIndexes = computed(() => {
    const last = props.data.length - 1;

    return new Set([0, Math.round(last / 2), last]);
});

const tooltip = computed(() =>
    hovered.value === null ? null : bars.value[hovered.value],
);
</script>

<template>
    <div
        ref="container"
        class="relative w-full"
        :style="{ height: `${height}px` }"
        @mouseleave="hovered = null"
    >
        <svg
            v-if="width > 0"
            :width="width"
            :height="height"
            role="img"
            :aria-label="title"
            class="block"
        >
            <g>
                <template v-for="tick in ticks" :key="tick">
                    <line
                        :x1="padding.left"
                        :x2="width - padding.right"
                        :y1="y(tick)"
                        :y2="y(tick)"
                        class="stroke-border"
                        stroke-width="1"
                    />
                    <text
                        :x="padding.left - 8"
                        :y="y(tick)"
                        text-anchor="end"
                        dominant-baseline="middle"
                        class="fill-muted-foreground text-[11px] tabular-nums"
                    >
                        {{ tick }}
                    </text>
                </template>
            </g>

            <g>
                <path
                    v-for="bar in bars"
                    :key="bar.date"
                    :d="bar.path"
                    class="fill-chart-1 transition-opacity duration-150"
                    :class="{
                        'opacity-40': hovered !== null && hovered !== bar.index,
                    }"
                />
            </g>

            <g>
                <template v-for="bar in bars" :key="`label-${bar.date}`">
                    <text
                        v-if="labelIndexes.has(bar.index)"
                        :x="bar.center"
                        :y="height - 8"
                        :text-anchor="
                            bar.index === 0
                                ? 'start'
                                : bar.index === data.length - 1
                                  ? 'end'
                                  : 'middle'
                        "
                        class="fill-muted-foreground text-[11px]"
                    >
                        {{ formatDate(bar.date) }}
                    </text>
                </template>
            </g>

            <g>
                <rect
                    v-for="bar in bars"
                    :key="`hit-${bar.date}`"
                    :x="padding.left + band * bar.index"
                    :y="padding.top"
                    :width="band"
                    :height="plotHeight"
                    fill="transparent"
                    @mouseenter="hovered = bar.index"
                />
            </g>
        </svg>

        <div
            v-if="tooltip"
            class="pointer-events-none absolute top-0 z-10 -translate-x-1/2 rounded-md border bg-popover px-3 py-2 text-xs shadow-md"
            :style="{
                left: `${Math.min(Math.max(tooltip.center, 60), width - 60)}px`,
            }"
        >
            <p class="text-muted-foreground">{{ formatDate(tooltip.date) }}</p>
            <p class="mt-0.5 font-medium tabular-nums">
                {{ tooltip.total }}
                {{ tooltip.total === 1 ? unit.one : unit.many }}
            </p>
        </div>

        <table class="sr-only">
            <caption>
                {{
                    title
                }}
            </caption>
            <thead>
                <tr>
                    <th scope="col">Date</th>
                    <th scope="col">{{ unit.many }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="point in data" :key="point.date">
                    <td>{{ formatDate(point.date) }}</td>
                    <td>{{ point.total }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
