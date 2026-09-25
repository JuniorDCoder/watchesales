<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { GripVertical, ImagePlus, Trash2 } from '@lucide/vue';
import { ref, useTemplateRef, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    destroy as destroyImage,
    reorder as reorderImages,
    store as storeImages,
    update as updateImage,
} from '@/routes/admin/watches/images';
import type { WatchImage } from '@/types';

const props = defineProps<{
    watchId: number;
    images: WatchImage[];
}>();

const items = ref<WatchImage[]>([...props.images]);
const draggingIndex = ref<number | null>(null);
const uploadProgress = ref<number | null>(null);
const uploadError = ref<string | undefined>();
const fileInput = useTemplateRef<HTMLInputElement>('fileInput');

watch(
    () => props.images,
    (images) => (items.value = [...images]),
);

function upload(files: FileList | null | undefined): void {
    if (!files?.length) {
        return;
    }

    uploadError.value = undefined;

    router.post(
        storeImages.url(props.watchId),
        { images: Array.from(files) },
        {
            forceFormData: true,
            preserveScroll: true,
            onProgress: (progress) =>
                (uploadProgress.value = progress?.percentage ?? null),
            onError: (errors) => (uploadError.value = Object.values(errors)[0]),
            onFinish: () => {
                uploadProgress.value = null;

                if (fileInput.value) {
                    fileInput.value.value = '';
                }
            },
        },
    );
}

function onDragStart(index: number): void {
    draggingIndex.value = index;
}

function onDragEnter(index: number): void {
    if (draggingIndex.value === null || draggingIndex.value === index) {
        return;
    }

    const reordered = [...items.value];
    const [moved] = reordered.splice(draggingIndex.value, 1);

    reordered.splice(index, 0, moved);
    items.value = reordered;
    draggingIndex.value = index;
}

function onDragEnd(): void {
    draggingIndex.value = null;

    const order = items.value.map((image) => image.id);

    if (order.join() === props.images.map((image) => image.id).join()) {
        return;
    }

    router.put(
        reorderImages.url(props.watchId),
        { order },
        { preserveScroll: true },
    );
}

function makeCover(index: number): void {
    draggingIndex.value = index;
    onDragEnter(0);
    onDragEnd();
}

function saveAlt(image: WatchImage, alt: string): void {
    if (alt === (image.alt ?? '')) {
        return;
    }

    router.patch(
        updateImage.url({ watch: props.watchId, image: image.id }),
        { alt },
        { preserveScroll: true, preserveState: true },
    );
}

function remove(image: WatchImage): void {
    router.delete(destroyImage.url({ watch: props.watchId, image: image.id }), {
        preserveScroll: true,
    });
}
</script>

<template>
    <div class="grid gap-4">
        <ul v-if="items.length" class="grid grid-cols-2 gap-4 sm:grid-cols-3">
            <li
                v-for="(image, index) in items"
                :key="image.id"
                draggable="true"
                class="group relative overflow-hidden rounded-lg border bg-muted/40 transition-all"
                :class="{
                    'scale-[0.97] opacity-50 ring-2 ring-ring':
                        draggingIndex === index,
                }"
                @dragstart="onDragStart(index)"
                @dragenter.prevent="onDragEnter(index)"
                @dragover.prevent
                @dragend="onDragEnd"
            >
                <div class="relative aspect-[4/5]">
                    <img
                        :src="image.url"
                        :alt="image.alt ?? ''"
                        class="size-full object-cover"
                        loading="lazy"
                    />
                    <span
                        v-if="index === 0"
                        class="absolute top-2 left-2 rounded-full bg-background/90 px-2 py-0.5 text-[11px] font-medium shadow"
                    >
                        Cover
                    </span>
                    <span
                        class="absolute top-2 right-2 flex size-7 cursor-grab items-center justify-center rounded-md bg-background/90 shadow active:cursor-grabbing"
                        aria-hidden="true"
                    >
                        <GripVertical class="size-4" />
                    </span>
                    <div
                        class="absolute inset-x-2 bottom-2 flex gap-1.5 opacity-0 transition-opacity group-hover:opacity-100 focus-within:opacity-100"
                    >
                        <Button
                            v-if="index !== 0"
                            type="button"
                            size="sm"
                            variant="secondary"
                            class="h-7 flex-1 text-xs"
                            @click="makeCover(index)"
                        >
                            Make cover
                        </Button>
                        <Button
                            type="button"
                            size="icon-sm"
                            variant="destructive"
                            class="ml-auto size-7"
                            aria-label="Delete photo"
                            @click="remove(image)"
                        >
                            <Trash2 class="size-3.5" />
                        </Button>
                    </div>
                </div>
                <Input
                    :model-value="image.alt ?? ''"
                    placeholder="Describe this photo"
                    aria-label="Alternative text"
                    class="h-8 rounded-none border-0 border-t text-xs shadow-none focus-visible:ring-0"
                    @change="
                        saveAlt(
                            image,
                            ($event.target as HTMLInputElement).value,
                        )
                    "
                />
            </li>
        </ul>

        <button
            type="button"
            class="flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed px-6 py-8 text-sm text-muted-foreground transition-colors hover:border-ring hover:text-foreground"
            :disabled="uploadProgress !== null"
            @click="fileInput?.click()"
            @dragover.prevent
            @drop.prevent="upload($event.dataTransfer?.files)"
        >
            <ImagePlus class="size-6" />
            <span v-if="uploadProgress !== null"
                >Uploading {{ Math.round(uploadProgress) }}%</span
            >
            <span v-else>Drop photos here or click to upload</span>
            <span class="text-xs">JPG, PNG, WebP or AVIF up to 8 MB each</span>
        </button>
        <input
            ref="fileInput"
            type="file"
            multiple
            accept="image/jpeg,image/png,image/webp,image/avif"
            class="sr-only"
            aria-label="Upload photos"
            @change="upload(($event.target as HTMLInputElement).files)"
        />
        <InputError :message="uploadError" />
        <p class="text-xs text-muted-foreground">
            Drag photos to reorder. The first photo is used as the cover.
        </p>
    </div>
</template>
