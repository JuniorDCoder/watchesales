<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { WatchStatus } from '@/types';

const props = defineProps<{
    status: WatchStatus;
    label: string;
    class?: string;
}>();

const tones: Record<WatchStatus, string> = {
    available:
        'bg-emerald-50 text-emerald-800 ring-emerald-600/20 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/25',
    reserved:
        'bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-400/10 dark:text-amber-200 dark:ring-amber-400/25',
    sold: 'bg-neutral-900/85 text-white ring-white/10 backdrop-blur dark:bg-white/90 dark:text-neutral-900',
};
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-medium tracking-wide uppercase ring-1 ring-inset',
                tones[props.status],
                props.class,
            )
        "
    >
        <span
            v-if="status !== 'sold'"
            class="size-1.5 rounded-full bg-current"
            aria-hidden="true"
        />
        {{ label }}
    </span>
</template>
