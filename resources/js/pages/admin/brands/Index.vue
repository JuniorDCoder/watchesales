<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ExternalLink, Pencil, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
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
    index as brandsIndex,
    store,
    update,
} from '@/routes/admin/brands';
import { show as showBrand } from '@/routes/brands';
import type { Brand } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Brands', href: brandsIndex() }],
    },
});

defineProps<{
    brands: Brand[];
}>();

const editing = ref<Brand | null>(null);
const formOpen = ref(false);
const deleting = ref<Brand | null>(null);

const form = useForm({
    name: '',
    slug: '',
    country: '',
    description: '',
});

function open(brand: Brand | null): void {
    editing.value = brand;
    form.clearErrors();
    form.name = brand?.name ?? '';
    form.slug = brand?.slug ?? '';
    form.country = brand?.country ?? '';
    form.description = brand?.description ?? '';
    formOpen.value = true;
}

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => (formOpen.value = false),
    };

    if (editing.value) {
        form.put(update.url(editing.value.id), options);

        return;
    }

    form.post(store.url(), options);
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
    <Head title="Brands" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Brands"
            description="Manufacturers of the watches you sell. Each brand gets its own landing page."
        >
            <Button @click="open(null)"
                ><Plus class="size-4" /> New brand</Button
            >
        </PageHeader>

        <div class="overflow-hidden rounded-xl border bg-card">
            <table class="w-full text-sm">
                <thead
                    class="border-b bg-muted/40 text-left text-xs text-muted-foreground"
                >
                    <tr>
                        <th scope="col" class="px-4 py-3 font-medium">Brand</th>
                        <th
                            scope="col"
                            class="hidden px-4 py-3 font-medium md:table-cell"
                        >
                            Country
                        </th>
                        <th
                            scope="col"
                            class="px-4 py-3 text-right font-medium"
                        >
                            Watches
                        </th>
                        <th scope="col" class="px-4 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="brand in brands"
                        :key="brand.id"
                        class="hover:bg-muted/30"
                    >
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ brand.name }}</p>
                            <p
                                class="line-clamp-1 text-xs text-muted-foreground"
                            >
                                {{ brand.description }}
                            </p>
                        </td>
                        <td
                            class="hidden px-4 py-3 text-muted-foreground md:table-cell"
                        >
                            {{ brand.country }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ brand.watches_count }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1">
                                <Button
                                    as-child
                                    variant="ghost"
                                    size="icon-sm"
                                    :aria-label="`View ${brand.name}`"
                                >
                                    <a
                                        :href="showBrand.url(brand)"
                                        target="_blank"
                                        rel="noopener"
                                        ><ExternalLink class="size-4"
                                    /></a>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    :aria-label="`Edit ${brand.name}`"
                                    @click="open(brand)"
                                >
                                    <Pencil class="size-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    class="text-destructive hover:text-destructive"
                                    :disabled="(brand.watches_count ?? 0) > 0"
                                    :title="
                                        (brand.watches_count ?? 0) > 0
                                            ? 'Move or delete its watches first'
                                            : undefined
                                    "
                                    :aria-label="`Delete ${brand.name}`"
                                    @click="deleting = brand"
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!brands.length">
                        <td
                            colspan="4"
                            class="px-4 py-16 text-center text-muted-foreground"
                        >
                            Add your first brand to start listing watches.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Dialog v-model:open="formOpen">
            <DialogContent>
                <form class="grid gap-5" @submit.prevent="submit">
                    <DialogHeader>
                        <DialogTitle>{{
                            editing ? `Edit ${editing.name}` : 'New brand'
                        }}</DialogTitle>
                        <DialogDescription
                            >Shown on watch pages and its own brand landing
                            page.</DialogDescription
                        >
                    </DialogHeader>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="brand-name">Name</Label>
                            <Input
                                id="brand-name"
                                v-model="form.name"
                                required
                            />
                            <InputError :message="form.errors.name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="brand-country">Country</Label>
                            <Input
                                id="brand-country"
                                v-model="form.country"
                                placeholder="Switzerland"
                            />
                            <InputError :message="form.errors.country" />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="brand-slug">URL handle</Label>
                        <Input
                            id="brand-slug"
                            v-model="form.slug"
                            placeholder="Generated from the name"
                        />
                        <InputError :message="form.errors.slug" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="brand-description">Description</Label>
                        <Textarea
                            id="brand-description"
                            v-model="form.description"
                            rows="3"
                        />
                        <InputError :message="form.errors.description" />
                    </div>
                    <DialogFooter>
                        <DialogClose as-child
                            ><Button type="button" variant="outline"
                                >Cancel</Button
                            ></DialogClose
                        >
                        <Button type="submit" :disabled="form.processing">
                            <Spinner v-if="form.processing" />
                            {{ editing ? 'Save changes' : 'Create brand' }}
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
                    <DialogDescription
                        >This cannot be undone.</DialogDescription
                    >
                </DialogHeader>
                <DialogFooter>
                    <DialogClose as-child
                        ><Button variant="outline">Cancel</Button></DialogClose
                    >
                    <Button variant="destructive" @click="confirmDelete"
                        >Delete brand</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
