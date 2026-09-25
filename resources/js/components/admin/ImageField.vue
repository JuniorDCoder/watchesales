<script setup lang="ts">
import { ImagePlus, Trash2 } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, useTemplateRef } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        label: string;
        currentUrl?: string | null;
        error?: string;
        hint?: string;
        accept?: string;
        aspect?: string;
        contain?: boolean;
    }>(),
    {
        currentUrl: null,
        error: undefined,
        hint: undefined,
        accept: 'image/jpeg,image/png,image/webp,image/avif',
        aspect: 'aspect-video',
        contain: false,
    },
);

const file = defineModel<File | null>('file', { default: null });
const removed = defineModel<boolean>('removed', { default: false });

const input = useTemplateRef<HTMLInputElement>('input');
const dragging = ref(false);
const previewUrl = ref<string | null>(null);

const shownUrl = computed(
    () => previewUrl.value ?? (removed.value ? null : props.currentUrl),
);

function select(selected: File | undefined): void {
    if (!selected) {
        return;
    }

    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }

    file.value = selected;
    removed.value = false;
    previewUrl.value = URL.createObjectURL(selected);
}

function clear(): void {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }

    previewUrl.value = null;
    file.value = null;
    removed.value = Boolean(props.currentUrl);

    if (input.value) {
        input.value.value = '';
    }
}

function onDrop(event: DragEvent): void {
    dragging.value = false;
    select(event.dataTransfer?.files[0]);
}

onBeforeUnmount(() => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
});
</script>

<template>
    <div class="grid gap-2">
        <span class="text-sm font-medium">{{ label }}</span>
        <div
            :class="
                cn(
                    'group relative overflow-hidden rounded-lg border border-dashed bg-muted/40 transition-colors',
                    aspect,
                    dragging && 'border-ring bg-accent',
                )
            "
            @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="onDrop"
        >
            <img
                v-if="shownUrl"
                :src="shownUrl"
                :alt="label"
                class="size-full"
                :class="contain ? 'object-contain p-4' : 'object-cover'"
            />
            <button
                v-else
                type="button"
                class="flex size-full flex-col items-center justify-center gap-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
                @click="input?.click()"
            >
                <ImagePlus class="size-6" />
                Drop an image or click to upload
            </button>

            <div
                v-if="shownUrl"
                class="absolute inset-0 flex items-center justify-center gap-2 bg-black/50 opacity-0 transition-opacity group-hover:opacity-100 focus-within:opacity-100"
            >
                <Button
                    type="button"
                    size="sm"
                    variant="secondary"
                    @click="input?.click()"
                    >Replace</Button
                >
                <Button
                    type="button"
                    size="sm"
                    variant="destructive"
                    @click="clear"
                >
                    <Trash2 class="size-4" /> Remove
                </Button>
            </div>
        </div>
        <input
            ref="input"
            type="file"
            class="sr-only"
            :accept="accept"
            :aria-label="label"
            @change="select(($event.target as HTMLInputElement).files?.[0])"
        />
        <p v-if="hint" class="text-xs text-muted-foreground">{{ hint }}</p>
        <InputError :message="error" />
    </div>
</template>
