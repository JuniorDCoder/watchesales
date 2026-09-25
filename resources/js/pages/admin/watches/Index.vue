<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ExternalLink,
    Eye,
    EyeOff,
    MoreHorizontal,
    Pencil,
    Plus,
    Search,
    Star,
    Trash2,
    Watch as WatchIcon,
} from '@lucide/vue';
import { useDebounceFn } from '@vueuse/core';
import { ref } from 'vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import Pagination from '@/components/store/Pagination.vue';
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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import StatusBadge from '@/components/store/StatusBadge.vue';
import { useSite } from '@/composables/useSite';
import { update as updateAttributes } from '@/routes/admin/watches/attributes';
import {
    create,
    destroy,
    edit,
    index as watchesIndex,
} from '@/routes/admin/watches';
import { show as showWatch } from '@/routes/watches';
import type { Option, Paginated, Watch } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Watches', href: watchesIndex() }],
    },
});

type Filters = {
    search: string | null;
    status: string | null;
    category: string | null;
    brand: string | null;
};

const props = defineProps<{
    watches: Paginated<Watch>;
    filters: Filters;
    options: {
        brands: { id: number; name: string; slug: string }[];
        categories: { id: number; name: string; slug: string }[];
        statuses: Option[];
    };
}>();

const { price } = useSite();
const search = ref(props.filters.search ?? '');
const deleting = ref<Watch | null>(null);

const ALL = 'all';

function applyFilters(patch: Partial<Filters>): void {
    const next = { ...props.filters, ...patch };
    const query = Object.fromEntries(
        Object.entries(next).filter(
            ([, value]) => value !== null && value !== '' && value !== ALL,
        ),
    );

    router.get(watchesIndex.url(), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

const debouncedSearch = useDebounceFn(
    () => applyFilters({ search: search.value || null }),
    300,
);

function updateWatch(
    watch: Watch,
    attributes: Partial<Pick<Watch, 'status' | 'is_featured' | 'is_published'>>,
): void {
    router
        .optimistic((pageProps) => {
            const current = (pageProps as { watches: Paginated<Watch> })
                .watches;

            return {
                watches: {
                    ...current,
                    data: current.data.map((item) =>
                        item.id === watch.id
                            ? { ...item, ...attributes }
                            : item,
                    ),
                },
            };
        })
        .patch(updateAttributes.url(watch.id), attributes, {
            preserveScroll: true,
        });
}

function confirmDelete(): void {
    if (!deleting.value) {
        return;
    }

    router.delete(destroy.url(deleting.value.id), {
        preserveScroll: true,
        onFinish: () => (deleting.value = null),
    });
}
</script>

<template>
    <Head title="Watches" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Watches"
            :description="`${watches.meta.total} watches in the catalogue`"
        >
            <Button as-child>
                <Link :href="create()"><Plus class="size-4" /> Add watch</Link>
            </Button>
        </PageHeader>

        <div class="flex flex-col gap-3 md:flex-row">
            <div class="relative flex-1">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Search name, brand or reference"
                    class="pl-9"
                    @input="debouncedSearch"
                />
            </div>
            <Select
                :model-value="filters.status ?? ALL"
                @update:model-value="applyFilters({ status: String($event) })"
            >
                <SelectTrigger class="md:w-40"
                    ><SelectValue placeholder="Status"
                /></SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ALL">All statuses</SelectItem>
                    <SelectItem
                        v-for="status in options.statuses"
                        :key="status.value"
                        :value="status.value"
                    >
                        {{ status.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <Select
                :model-value="filters.category ?? ALL"
                @update:model-value="applyFilters({ category: String($event) })"
            >
                <SelectTrigger class="md:w-48"
                    ><SelectValue placeholder="Category"
                /></SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ALL">All categories</SelectItem>
                    <SelectItem
                        v-for="category in options.categories"
                        :key="category.id"
                        :value="category.slug"
                    >
                        {{ category.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <Select
                :model-value="filters.brand ?? ALL"
                @update:model-value="applyFilters({ brand: String($event) })"
            >
                <SelectTrigger class="md:w-44"
                    ><SelectValue placeholder="Brand"
                /></SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ALL">All brands</SelectItem>
                    <SelectItem
                        v-for="brand in options.brands"
                        :key="brand.id"
                        :value="brand.slug"
                    >
                        {{ brand.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="overflow-hidden rounded-xl border bg-card">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="border-b bg-muted/40 text-left text-xs text-muted-foreground"
                    >
                        <tr>
                            <th scope="col" class="px-4 py-3 font-medium">
                                Watch
                            </th>
                            <th
                                scope="col"
                                class="hidden px-4 py-3 font-medium lg:table-cell"
                            >
                                Category
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-right font-medium"
                            >
                                Price
                            </th>
                            <th scope="col" class="px-4 py-3 font-medium">
                                Status
                            </th>
                            <th
                                scope="col"
                                class="hidden px-4 py-3 text-right font-medium md:table-cell"
                            >
                                Inquiries
                            </th>
                            <th scope="col" class="px-4 py-3">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="watch in watches.data"
                            :key="watch.id"
                            class="group transition-colors hover:bg-muted/30"
                        >
                            <td class="px-4 py-3">
                                <Link
                                    :href="edit(watch.id)"
                                    class="flex items-center gap-3"
                                >
                                    <img
                                        v-if="watch.image"
                                        :src="watch.image.url"
                                        :alt="watch.name"
                                        class="size-12 shrink-0 rounded-md object-cover"
                                        loading="lazy"
                                    />
                                    <span
                                        v-else
                                        class="flex size-12 shrink-0 items-center justify-center rounded-md bg-muted"
                                    >
                                        <WatchIcon
                                            class="size-5 text-muted-foreground"
                                        />
                                    </span>
                                    <span class="min-w-0">
                                        <span
                                            class="flex items-center gap-1.5 font-medium"
                                        >
                                            <span class="truncate">{{
                                                watch.name
                                            }}</span>
                                            <Star
                                                v-if="watch.is_featured"
                                                class="size-3.5 shrink-0 fill-gold text-gold"
                                            />
                                            <EyeOff
                                                v-if="!watch.is_published"
                                                class="size-3.5 shrink-0 text-muted-foreground"
                                            />
                                        </span>
                                        <span
                                            class="block truncate text-xs text-muted-foreground"
                                        >
                                            {{ watch.brand?.name
                                            }}<template v-if="watch.reference">
                                                ·
                                                {{ watch.reference }}</template
                                            >
                                        </span>
                                    </span>
                                </Link>
                            </td>
                            <td
                                class="hidden px-4 py-3 text-muted-foreground lg:table-cell"
                            >
                                {{ watch.category?.name ?? 'None' }}
                            </td>
                            <td
                                class="px-4 py-3 text-right whitespace-nowrap tabular-nums"
                            >
                                {{ price(watch.price) }}
                            </td>
                            <td class="px-4 py-3">
                                <StatusBadge
                                    :status="watch.status"
                                    :label="watch.status_label"
                                />
                            </td>
                            <td
                                class="hidden px-4 py-3 text-right tabular-nums md:table-cell"
                            >
                                {{ watch.inquiries_count ?? 0 }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            :aria-label="`Actions for ${watch.name}`"
                                        >
                                            <MoreHorizontal class="size-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent
                                        align="end"
                                        class="w-52"
                                    >
                                        <DropdownMenuItem as-child>
                                            <Link :href="edit(watch.id)"
                                                ><Pencil class="size-4" />
                                                Edit</Link
                                            >
                                        </DropdownMenuItem>
                                        <DropdownMenuItem as-child>
                                            <a
                                                :href="showWatch.url(watch)"
                                                target="_blank"
                                                rel="noopener"
                                            >
                                                <ExternalLink class="size-4" />
                                                View on site
                                            </a>
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            @select="
                                                updateWatch(watch, {
                                                    is_featured:
                                                        !watch.is_featured,
                                                })
                                            "
                                        >
                                            <Star class="size-4" />
                                            {{
                                                watch.is_featured
                                                    ? 'Remove from featured'
                                                    : 'Feature on home page'
                                            }}
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            @select="
                                                updateWatch(watch, {
                                                    is_published:
                                                        !watch.is_published,
                                                })
                                            "
                                        >
                                            <component
                                                :is="
                                                    watch.is_published
                                                        ? EyeOff
                                                        : Eye
                                                "
                                                class="size-4"
                                            />
                                            {{
                                                watch.is_published
                                                    ? 'Hide from store'
                                                    : 'Publish to store'
                                            }}
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuLabel
                                            class="text-xs text-muted-foreground"
                                            >Status</DropdownMenuLabel
                                        >
                                        <DropdownMenuRadioGroup
                                            :model-value="watch.status"
                                            @update:model-value="
                                                updateWatch(watch, {
                                                    status: $event as Watch['status'],
                                                })
                                            "
                                        >
                                            <DropdownMenuRadioItem
                                                v-for="status in options.statuses"
                                                :key="status.value"
                                                :value="status.value"
                                            >
                                                {{ status.label }}
                                            </DropdownMenuRadioItem>
                                        </DropdownMenuRadioGroup>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            variant="destructive"
                                            @select="deleting = watch"
                                        >
                                            <Trash2 class="size-4" /> Delete
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </td>
                        </tr>
                        <tr v-if="!watches.data.length">
                            <td
                                colspan="6"
                                class="px-4 py-16 text-center text-muted-foreground"
                            >
                                No watches match these filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Pagination :meta="watches.meta" :links="watches.links" />

        <Dialog
            :open="deleting !== null"
            @update:open="(open) => !open && (deleting = null)"
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete {{ deleting?.name }}?</DialogTitle>
                    <DialogDescription>
                        The watch and all of its photos will be permanently
                        removed. This cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose as-child
                        ><Button variant="outline">Cancel</Button></DialogClose
                    >
                    <Button variant="destructive" @click="confirmDelete"
                        >Delete watch</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
