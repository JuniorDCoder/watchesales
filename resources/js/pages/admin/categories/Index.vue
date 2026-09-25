<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ExternalLink, Pencil, Plus, Tags, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import ImageField from '@/components/admin/ImageField.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
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
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import {
    destroy,
    index as categoriesIndex,
    store,
    update,
} from '@/routes/admin/categories';
import { show as showCollection } from '@/routes/collections';
import type { Category } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Categories', href: categoriesIndex() }],
    },
});

defineProps<{
    categories: Category[];
}>();

const editing = ref<Category | null>(null);
const formOpen = ref(false);
const deleting = ref<Category | null>(null);

const form = useForm({
    name: '',
    slug: '',
    description: '',
    sort_order: '0',
    image: null as File | null,
    remove_image: false,
});

function open(category: Category | null): void {
    editing.value = category;
    form.clearErrors();
    form.name = category?.name ?? '';
    form.slug = category?.slug ?? '';
    form.description = category?.description ?? '';
    form.sort_order = String(category?.sort_order ?? 0);
    form.image = null;
    form.remove_image = false;
    formOpen.value = true;
}

function submit(): void {
    const options = {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => (formOpen.value = false),
    };

    if (editing.value) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(
            update.url(editing.value.id),
            options,
        );

        return;
    }

    form.transform((data) => data).post(store.url(), options);
}

function confirmDelete(): void {
    if (deleting.value) {
        router.delete(destroy.url(deleting.value.id), {
            preserveScroll: true,
            onFinish: () => (deleting.value = null),
        });
    }
}
</script>

<template>
    <Head title="Categories" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Categories"
            description="Collections shown in the navigation and on the home page."
        >
            <Button @click="open(null)"
                ><Plus class="size-4" /> New category</Button
            >
        </PageHeader>

        <div
            v-if="categories.length"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
        >
            <article
                v-for="category in categories"
                :key="category.id"
                class="group overflow-hidden rounded-xl border bg-card"
            >
                <div class="relative aspect-[16/9] bg-muted">
                    <img
                        v-if="category.image_url"
                        :src="category.image_url"
                        :alt="category.name"
                        class="size-full object-cover"
                    />
                    <div
                        v-else
                        class="flex size-full items-center justify-center text-xs text-muted-foreground"
                    >
                        Uses a photo from one of its watches
                    </div>
                    <span
                        class="absolute top-3 left-3 rounded-full bg-background/90 px-2.5 py-0.5 text-xs font-medium shadow"
                    >
                        {{ category.watches_count }}
                        {{ category.watches_count === 1 ? 'watch' : 'watches' }}
                    </span>
                </div>
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="truncate font-medium">
                                {{ category.name }}
                            </h2>
                            <p class="text-xs text-muted-foreground">
                                /collections/{{ category.slug }} · Order
                                {{ category.sort_order }}
                            </p>
                        </div>
                        <div class="flex shrink-0 gap-1">
                            <Button
                                as-child
                                variant="ghost"
                                size="icon-sm"
                                :aria-label="`View ${category.name}`"
                            >
                                <a
                                    :href="showCollection.url(category)"
                                    target="_blank"
                                    rel="noopener"
                                    ><ExternalLink class="size-4"
                                /></a>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                :aria-label="`Edit ${category.name}`"
                                @click="open(category)"
                            >
                                <Pencil class="size-4" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                class="text-destructive hover:text-destructive"
                                :aria-label="`Delete ${category.name}`"
                                @click="deleting = category"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                    </div>
                    <p
                        v-if="category.description"
                        class="mt-2 line-clamp-2 text-sm text-muted-foreground"
                    >
                        {{ category.description }}
                    </p>
                </div>
            </article>
        </div>
        <div
            v-else
            class="flex flex-col items-center rounded-xl border border-dashed py-16 text-center"
        >
            <Tags class="size-8 text-muted-foreground" />
            <p class="mt-3 font-medium">No categories yet</p>
            <p class="text-sm text-muted-foreground">
                Group watches into collections such as Dive or Dress.
            </p>
            <Button class="mt-5" @click="open(null)"
                ><Plus class="size-4" /> New category</Button
            >
        </div>

        <Dialog v-model:open="formOpen">
            <DialogContent class="sm:max-w-xl">
                <form class="grid gap-5" @submit.prevent="submit">
                    <DialogHeader>
                        <DialogTitle>{{
                            editing ? `Edit ${editing.name}` : 'New category'
                        }}</DialogTitle>
                        <DialogDescription
                            >Categories appear in the menu, footer and home
                            page.</DialogDescription
                        >
                    </DialogHeader>
                    <div class="grid gap-2">
                        <Label for="category-name">Name</Label>
                        <Input
                            id="category-name"
                            v-model="form.name"
                            required
                        />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-[1fr_120px]">
                        <div class="grid gap-2">
                            <Label for="category-slug">URL handle</Label>
                            <Input
                                id="category-slug"
                                v-model="form.slug"
                                placeholder="Generated from the name"
                            />
                            <InputError :message="form.errors.slug" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="category-order">Order</Label>
                            <Input
                                id="category-order"
                                v-model="form.sort_order"
                                type="number"
                                min="0"
                            />
                            <InputError :message="form.errors.sort_order" />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="category-description">Description</Label>
                        <Textarea
                            id="category-description"
                            v-model="form.description"
                            rows="3"
                        />
                        <InputError :message="form.errors.description" />
                    </div>
                    <ImageField
                        v-model:file="form.image"
                        v-model:removed="form.remove_image"
                        label="Cover image"
                        hint="Optional. Without one, a photo from a watch in this category is used."
                        :current-url="editing?.image_url"
                        :error="form.errors.image"
                    />
                    <DialogFooter>
                        <DialogClose as-child
                            ><Button type="button" variant="outline"
                                >Cancel</Button
                            ></DialogClose
                        >
                        <Button type="submit" :disabled="form.processing">
                            <Spinner v-if="form.processing" />
                            {{ editing ? 'Save changes' : 'Create category' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="deleting !== null"
            @update:open="(value) => !value && (deleting = null)"
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete {{ deleting?.name }}?</DialogTitle>
                    <DialogDescription>
                        Its watches stay in the catalogue without a category.
                        This cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose as-child
                        ><Button variant="outline">Cancel</Button></DialogClose
                    >
                    <Button variant="destructive" @click="confirmDelete"
                        >Delete category</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
