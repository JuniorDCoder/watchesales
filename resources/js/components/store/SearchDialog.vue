<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { ArrowRight, Search } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { show as showCollection } from '@/routes/collections';
import { index as watchesIndex } from '@/routes/watches';

const open = defineModel<boolean>('open', { default: false });

const page = usePage();
const query = ref('');
const categories = computed(() => page.props.navigation.categories);

watch(open, (isOpen) => {
    if (isOpen) {
        query.value = '';
    }
});

function submit(): void {
    const search = query.value.trim();

    open.value = false;
    router.get(watchesIndex.url({ query: search ? { search } : {} }));
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent
            class="top-[18%] translate-y-0 gap-0 overflow-hidden p-0 sm:max-w-xl"
        >
            <DialogTitle class="sr-only">Search watches</DialogTitle>
            <DialogDescription class="sr-only">
                Search by brand, model or reference number.
            </DialogDescription>

            <form
                class="flex items-center gap-3 border-b px-5"
                @submit.prevent="submit"
            >
                <Search class="size-5 shrink-0 text-muted-foreground" />
                <input
                    v-model="query"
                    v-focus
                    type="search"
                    name="search"
                    placeholder="Search brand, model or reference"
                    class="h-16 flex-1 bg-transparent text-base outline-none placeholder:text-muted-foreground"
                    autocomplete="off"
                />
                <kbd
                    class="hidden rounded border px-1.5 py-0.5 text-[10px] text-muted-foreground sm:inline"
                    >Enter</kbd
                >
            </form>

            <div class="p-5">
                <p
                    class="mb-3 text-[11px] font-medium tracking-[0.22em] text-muted-foreground uppercase"
                >
                    Browse collections
                </p>
                <div class="grid grid-cols-2 gap-1">
                    <Link
                        v-for="category in categories"
                        :key="category.id"
                        :href="showCollection(category)"
                        class="group flex items-center justify-between rounded-md px-3 py-2.5 text-sm transition-colors hover:bg-accent"
                        @click="open = false"
                    >
                        {{ category.name }}
                        <ArrowRight
                            class="size-3.5 -translate-x-1 opacity-0 transition-all group-hover:translate-x-0 group-hover:opacity-100"
                        />
                    </Link>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
