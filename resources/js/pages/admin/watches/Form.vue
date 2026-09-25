<script setup lang="ts">
import { Head, Link, router, setLayoutProps, useForm } from '@inertiajs/vue3';
import { ExternalLink, ImagePlus, Trash2, X } from '@lucide/vue';
import { computed, onBeforeUnmount, ref } from 'vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import WatchImageManager from '@/components/admin/WatchImageManager.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { useSite } from '@/composables/useSite';
import {
    destroy,
    index as watchesIndex,
    store,
    update,
} from '@/routes/admin/watches';
import { show as showWatch } from '@/routes/watches';
import type { Option, WatchDetail } from '@/types';

const props = defineProps<{
    watch: WatchDetail | null;
    options: {
        brands: { id: number; name: string }[];
        categories: { id: number; name: string }[];
        statuses: Option[];
        conditions: Option[];
        movements: Option[];
        genders: Option[];
    };
}>();

const isEditing = computed(() => props.watch !== null);
const pageTitle = computed(() =>
    props.watch ? props.watch.name : 'Add a watch',
);

setLayoutProps({
    breadcrumbs: [
        { title: 'Watches', href: watchesIndex() },
        { title: props.watch ? props.watch.name : 'New watch', href: '#' },
    ],
});

const { site } = useSite();
const NONE = 'none';

const form = useForm({
    brand_id: props.watch?.brand_id?.toString() ?? '',
    category_id: props.watch?.category_id?.toString() ?? NONE,
    name: props.watch?.name ?? '',
    slug: props.watch?.slug ?? '',
    reference: props.watch?.reference ?? '',
    summary: props.watch?.summary ?? '',
    description: props.watch?.description ?? '',
    price: props.watch?.price?.toString() ?? '',
    status: props.watch?.status ?? 'available',
    condition: props.watch?.condition ?? 'pre_owned',
    movement: props.watch?.movement ?? 'automatic',
    gender: props.watch?.gender ?? 'unisex',
    year: props.watch?.year?.toString() ?? '',
    case_material: props.watch?.case_material ?? '',
    case_diameter: props.watch?.case_diameter?.toString() ?? '',
    water_resistance: props.watch?.water_resistance?.toString() ?? '',
    dial_color: props.watch?.dial_color ?? '',
    strap_material: props.watch?.strap_material ?? '',
    has_box: props.watch?.has_box ?? false,
    has_papers: props.watch?.has_papers ?? false,
    is_featured: props.watch?.is_featured ?? false,
    is_published: props.watch?.is_published ?? true,
    meta_title: props.watch?.meta_title ?? '',
    meta_description: props.watch?.meta_description ?? '',
    images: [] as File[],
});

const previews = ref<string[]>([]);
const confirmingDelete = ref(false);

function addImages(files: FileList | null | undefined): void {
    Array.from(files ?? []).forEach((file) => {
        form.images.push(file);
        previews.value.push(URL.createObjectURL(file));
    });
}

function removeImage(index: number): void {
    URL.revokeObjectURL(previews.value[index]);
    form.images.splice(index, 1);
    previews.value.splice(index, 1);
}

onBeforeUnmount(() =>
    previews.value.forEach((url) => URL.revokeObjectURL(url)),
);

function submit(): void {
    const transformed = form.transform((data) => ({
        ...data,
        category_id: data.category_id === NONE ? null : data.category_id,
        price: data.price === '' ? null : data.price,
        year: data.year === '' ? null : data.year,
        case_diameter: data.case_diameter === '' ? null : data.case_diameter,
        water_resistance:
            data.water_resistance === '' ? null : data.water_resistance,
        slug: data.slug === '' ? null : data.slug,
    }));

    if (props.watch) {
        transformed.put(update.url(props.watch.id), {
            preserveScroll: true,
            onSuccess: () => form.defaults(),
        });

        return;
    }

    transformed.post(store.url(), { forceFormData: true });
}

function deleteWatch(): void {
    if (props.watch) {
        router.delete(destroy.url(props.watch.id));
    }
}

const seoTitle = computed(
    () =>
        form.meta_title ||
        `${props.options.brands.find((brand) => brand.id.toString() === form.brand_id)?.name ?? ''} ${form.name}`.trim() ||
        'Watch title',
);
const seoDescription = computed(
    () =>
        form.meta_description ||
        form.summary ||
        'A short description shown in search results.',
);
const firstError = computed(() => Object.values(form.errors)[0]);
</script>

<template>
    <Head :title="pageTitle" />

    <form
        class="flex flex-1 flex-col gap-6 p-4 pb-0 md:p-6 md:pb-0"
        @submit.prevent="submit"
    >
        <PageHeader
            :title="pageTitle"
            :description="
                isEditing
                    ? 'Update the listing, photos and visibility.'
                    : 'Create a new listing for the storefront.'
            "
        >
            <Button v-if="watch" as-child variant="outline">
                <a :href="showWatch.url(watch)" target="_blank" rel="noopener">
                    <ExternalLink class="size-4" /> View on site
                </a>
            </Button>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="grid content-start gap-6 lg:col-span-2">
                <section class="rounded-xl border bg-card p-5 md:p-6">
                    <h2 class="font-medium">Details</h2>
                    <div class="mt-5 grid gap-5 sm:grid-cols-2">
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="name">Model name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="Submariner Date 41"
                                required
                            />
                            <InputError :message="form.errors.name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="brand">Brand</Label>
                            <Select v-model="form.brand_id">
                                <SelectTrigger id="brand" class="w-full"
                                    ><SelectValue placeholder="Choose a brand"
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="brand in options.brands"
                                        :key="brand.id"
                                        :value="brand.id.toString()"
                                    >
                                        {{ brand.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.brand_id" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="category">Category</Label>
                            <Select v-model="form.category_id">
                                <SelectTrigger id="category" class="w-full"
                                    ><SelectValue
                                        placeholder="Choose a category"
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="NONE"
                                        >No category</SelectItem
                                    >
                                    <SelectItem
                                        v-for="category in options.categories"
                                        :key="category.id"
                                        :value="category.id.toString()"
                                    >
                                        {{ category.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.category_id" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="reference">Reference number</Label>
                            <Input
                                id="reference"
                                v-model="form.reference"
                                placeholder="126610LN"
                            />
                            <InputError :message="form.errors.reference" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="year">Year</Label>
                            <Input
                                id="year"
                                v-model="form.year"
                                type="number"
                                inputmode="numeric"
                                placeholder="2021"
                            />
                            <InputError :message="form.errors.year" />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <div class="flex items-center justify-between">
                                <Label for="summary">Summary</Label>
                                <span
                                    class="text-xs text-muted-foreground tabular-nums"
                                    >{{ form.summary.length }} / 300</span
                                >
                            </div>
                            <Textarea
                                id="summary"
                                v-model="form.summary"
                                rows="2"
                                maxlength="300"
                                placeholder="One or two sentences shown near the price."
                            />
                            <InputError :message="form.errors.summary" />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="description">Description</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                class="min-h-48"
                                placeholder="History, condition, service record and what is included. Leave a blank line between paragraphs."
                            />
                            <InputError :message="form.errors.description" />
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border bg-card p-5 md:p-6">
                    <h2 class="font-medium">Specifications</h2>
                    <div class="mt-5 grid gap-5 sm:grid-cols-3">
                        <div class="grid gap-2">
                            <Label for="condition">Condition</Label>
                            <Select v-model="form.condition">
                                <SelectTrigger id="condition" class="w-full"
                                    ><SelectValue
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in options.conditions"
                                        :key="option.value"
                                        :value="option.value"
                                        >{{ option.label }}</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="movement">Movement</Label>
                            <Select v-model="form.movement">
                                <SelectTrigger id="movement" class="w-full"
                                    ><SelectValue
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in options.movements"
                                        :key="option.value"
                                        :value="option.value"
                                        >{{ option.label }}</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="gender">Suited to</Label>
                            <Select v-model="form.gender">
                                <SelectTrigger id="gender" class="w-full"
                                    ><SelectValue
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in options.genders"
                                        :key="option.value"
                                        :value="option.value"
                                        >{{ option.label }}</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="case_material">Case material</Label>
                            <Input
                                id="case_material"
                                v-model="form.case_material"
                                placeholder="Stainless steel"
                            />
                            <InputError :message="form.errors.case_material" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="case_diameter"
                                >Case diameter (mm)</Label
                            >
                            <Input
                                id="case_diameter"
                                v-model="form.case_diameter"
                                type="number"
                                step="0.1"
                                placeholder="41"
                            />
                            <InputError :message="form.errors.case_diameter" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="water_resistance"
                                >Water resistance (m)</Label
                            >
                            <Input
                                id="water_resistance"
                                v-model="form.water_resistance"
                                type="number"
                                placeholder="300"
                            />
                            <InputError
                                :message="form.errors.water_resistance"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="dial_color">Dial</Label>
                            <Input
                                id="dial_color"
                                v-model="form.dial_color"
                                placeholder="Black"
                            />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="strap_material"
                                >Strap or bracelet</Label
                            >
                            <Input
                                id="strap_material"
                                v-model="form.strap_material"
                                placeholder="Oyster bracelet"
                            />
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border bg-card p-5 md:p-6">
                    <h2 class="font-medium">Photos</h2>
                    <p class="text-sm text-muted-foreground">
                        Use a portrait crop (4:5) for the best results.
                    </p>
                    <div class="mt-5">
                        <WatchImageManager
                            v-if="watch"
                            :watch-id="watch.id"
                            :images="watch.images ?? []"
                        />
                        <div v-else class="grid gap-4">
                            <ul
                                v-if="previews.length"
                                class="grid grid-cols-2 gap-4 sm:grid-cols-3"
                            >
                                <li
                                    v-for="(preview, index) in previews"
                                    :key="preview"
                                    class="group relative aspect-[4/5] overflow-hidden rounded-lg border"
                                >
                                    <img
                                        :src="preview"
                                        alt=""
                                        class="size-full object-cover"
                                    />
                                    <span
                                        v-if="index === 0"
                                        class="absolute top-2 left-2 rounded-full bg-background/90 px-2 py-0.5 text-[11px] font-medium shadow"
                                        >Cover</span
                                    >
                                    <button
                                        type="button"
                                        class="absolute top-2 right-2 flex size-7 items-center justify-center rounded-full bg-background/90 shadow"
                                        aria-label="Remove photo"
                                        @click="removeImage(index)"
                                    >
                                        <X class="size-4" />
                                    </button>
                                </li>
                            </ul>
                            <label
                                class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border border-dashed px-6 py-10 text-sm text-muted-foreground transition-colors hover:border-ring hover:text-foreground"
                                @dragover.prevent
                                @drop.prevent="
                                    addImages($event.dataTransfer?.files)
                                "
                            >
                                <ImagePlus class="size-6" />
                                Drop photos here or click to choose
                                <span class="text-xs"
                                    >JPG, PNG, WebP or AVIF up to 8 MB
                                    each</span
                                >
                                <input
                                    type="file"
                                    multiple
                                    accept="image/jpeg,image/png,image/webp,image/avif"
                                    class="sr-only"
                                    @change="
                                        addImages(
                                            ($event.target as HTMLInputElement)
                                                .files,
                                        )
                                    "
                                />
                            </label>
                            <InputError
                                :message="
                                    form.errors.images ??
                                    Object.entries(form.errors).find(([key]) =>
                                        key.startsWith('images.'),
                                    )?.[1]
                                "
                            />
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border bg-card p-5 md:p-6">
                    <h2 class="font-medium">Search engine listing</h2>
                    <p class="text-sm text-muted-foreground">
                        How this watch may appear in Google. Leave blank to use
                        the defaults.
                    </p>
                    <div class="mt-5 rounded-lg border bg-background p-4">
                        <p class="truncate text-xs text-muted-foreground">
                            {{ site.name }} › watches ›
                            {{ form.slug || 'new-watch' }}
                        </p>
                        <p
                            class="mt-1 truncate text-lg text-[#1a0dab] dark:text-[#8ab4f8]"
                        >
                            {{ seoTitle }} | {{ site.name }}
                        </p>
                        <p
                            class="mt-1 line-clamp-2 text-sm text-muted-foreground"
                        >
                            {{ seoDescription }}
                        </p>
                    </div>
                    <div class="mt-5 grid gap-5">
                        <div class="grid gap-2">
                            <Label for="meta_title">Page title</Label>
                            <Input
                                id="meta_title"
                                v-model="form.meta_title"
                                maxlength="70"
                            />
                            <InputError :message="form.errors.meta_title" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="meta_description"
                                >Meta description</Label
                            >
                            <Textarea
                                id="meta_description"
                                v-model="form.meta_description"
                                rows="2"
                                maxlength="320"
                            />
                            <InputError
                                :message="form.errors.meta_description"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="slug">URL handle</Label>
                            <Input
                                id="slug"
                                v-model="form.slug"
                                placeholder="Generated from the brand and name"
                            />
                            <InputError :message="form.errors.slug" />
                        </div>
                    </div>
                </section>
            </div>

            <aside
                class="grid content-start gap-6 lg:sticky lg:top-6 lg:self-start"
            >
                <section class="rounded-xl border bg-card p-5">
                    <h2 class="font-medium">Price and availability</h2>
                    <div class="mt-5 grid gap-5">
                        <div class="grid gap-2">
                            <Label for="price"
                                >Price ({{ site.currency }})</Label
                            >
                            <Input
                                id="price"
                                v-model="form.price"
                                type="number"
                                min="0"
                                step="1"
                                placeholder="Leave empty for price on request"
                            />
                            <InputError :message="form.errors.price" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="status">Status</Label>
                            <Select v-model="form.status">
                                <SelectTrigger id="status" class="w-full"
                                    ><SelectValue
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in options.statuses"
                                        :key="option.value"
                                        :value="option.value"
                                        >{{ option.label }}</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border bg-card p-5">
                    <h2 class="font-medium">Visibility</h2>
                    <div class="mt-5 grid gap-4">
                        <div class="flex items-start justify-between gap-4">
                            <Label
                                for="is_published"
                                class="grid gap-1 font-normal"
                            >
                                <span class="font-medium">Published</span>
                                <span class="text-xs text-muted-foreground"
                                    >Visible on the storefront</span
                                >
                            </Label>
                            <Switch
                                id="is_published"
                                v-model="form.is_published"
                            />
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <Label
                                for="is_featured"
                                class="grid gap-1 font-normal"
                            >
                                <span class="font-medium">Featured</span>
                                <span class="text-xs text-muted-foreground"
                                    >Highlighted on the home page</span
                                >
                            </Label>
                            <Switch
                                id="is_featured"
                                v-model="form.is_featured"
                            />
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border bg-card p-5">
                    <h2 class="font-medium">Included with the watch</h2>
                    <div class="mt-5 grid gap-4">
                        <div class="flex items-center justify-between gap-4">
                            <Label for="has_box" class="font-normal"
                                >Original box</Label
                            >
                            <Switch id="has_box" v-model="form.has_box" />
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <Label for="has_papers" class="font-normal"
                                >Papers or warranty card</Label
                            >
                            <Switch id="has_papers" v-model="form.has_papers" />
                        </div>
                    </div>
                </section>

                <section v-if="watch" class="rounded-xl border bg-card p-5">
                    <h2 class="font-medium">Performance</h2>
                    <dl class="mt-4 grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-xs text-muted-foreground">
                                Page views
                            </dt>
                            <dd class="text-2xl font-semibold tabular-nums">
                                {{ watch.views_count ?? 0 }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted-foreground">
                                Inquiries
                            </dt>
                            <dd class="text-2xl font-semibold tabular-nums">
                                {{ watch.inquiries_count ?? 0 }}
                            </dd>
                        </div>
                    </dl>
                    <Button
                        type="button"
                        variant="ghost"
                        class="mt-5 w-full text-destructive hover:text-destructive"
                        @click="confirmingDelete = true"
                    >
                        <Trash2 class="size-4" /> Delete watch
                    </Button>
                </section>
            </aside>
        </div>

        <div
            class="sticky bottom-0 z-20 -mx-4 mt-auto border-t bg-background/85 backdrop-blur-xl md:-mx-6"
        >
            <div class="flex items-center justify-end gap-3 px-4 py-3 md:px-6">
                <p
                    v-if="form.hasErrors"
                    class="mr-auto truncate text-sm text-destructive"
                >
                    {{ firstError }}
                </p>
                <p
                    v-else-if="form.isDirty"
                    class="mr-auto text-sm text-muted-foreground"
                >
                    Unsaved changes
                </p>
                <p
                    v-else-if="form.recentlySuccessful"
                    class="mr-auto text-sm text-muted-foreground"
                >
                    Saved
                </p>
                <Button as-child variant="outline">
                    <Link :href="watchesIndex()">Cancel</Link>
                </Button>
                <Button type="submit" :disabled="form.processing">
                    <Spinner v-if="form.processing" />
                    {{ isEditing ? 'Save changes' : 'Create watch' }}
                </Button>
            </div>
            <div
                v-if="form.progress"
                class="h-0.5 bg-chart-1 transition-[width]"
                :style="{ width: `${form.progress.percentage}%` }"
            />
        </div>

        <Dialog v-model:open="confirmingDelete">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete {{ watch?.name }}?</DialogTitle>
                    <DialogDescription
                        >The watch and all of its photos will be permanently
                        removed.</DialogDescription
                    >
                </DialogHeader>
                <DialogFooter>
                    <DialogClose as-child
                        ><Button variant="outline">Cancel</Button></DialogClose
                    >
                    <Button variant="destructive" @click="deleteWatch"
                        >Delete watch</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </form>
</template>
