<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    ChevronDown,
    LayoutDashboard,
    Menu,
    Search,
} from '@lucide/vue';
import { useEventListener, useWindowScroll } from '@vueuse/core';
import { computed, ref, watch } from 'vue';
import SearchDialog from '@/components/store/SearchDialog.vue';
import SiteLogo from '@/components/store/SiteLogo.vue';
import ThemeToggle from '@/components/store/ThemeToggle.vue';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { about, contact, dashboard, home } from '@/routes';
import { show as showCollection } from '@/routes/collections';
import { index as watchesIndex } from '@/routes/watches';

const props = withDefaults(defineProps<{ transparent?: boolean }>(), {
    transparent: false,
});

const page = usePage();
const { y } = useWindowScroll();
const { isCurrentOrParentUrl } = useCurrentUrl();

const categories = computed(() => page.props.navigation.categories);
const isAdmin = computed(() => Boolean(page.props.auth.user?.is_admin));
const isScrolled = computed(() => y.value > 24);
const isOverHero = computed(() => props.transparent && !isScrolled.value);

const menuOpen = ref(false);
const mobileOpen = ref(false);
const searchOpen = ref(false);

const links = [
    { title: 'About', href: about() },
    { title: 'Contact', href: contact() },
];

watch(
    () => page.url,
    () => {
        menuOpen.value = false;
        mobileOpen.value = false;
    },
);

useEventListener('keydown', (event: KeyboardEvent) => {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        searchOpen.value = true;
    }

    if (event.key === 'Escape') {
        menuOpen.value = false;
    }
});
</script>

<template>
    <header
        class="sticky top-0 z-40 transition-[background-color,color,border-color,backdrop-filter] duration-500"
        :class="[
            isOverHero
                ? 'border-b border-transparent text-white'
                : 'border-b border-border/70 bg-background/80 text-foreground backdrop-blur-xl backdrop-saturate-150',
            { '-mb-[72px]': transparent },
        ]"
        @mouseleave="menuOpen = false"
    >
        <div
            class="mx-auto flex h-[72px] max-w-7xl items-center gap-6 px-4 sm:px-6 lg:px-8"
        >
            <Sheet v-model:open="mobileOpen">
                <SheetTrigger as-child>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="-ml-2 rounded-full text-current hover:bg-current/10 hover:text-current lg:hidden"
                        aria-label="Open menu"
                    >
                        <Menu class="size-5" />
                    </Button>
                </SheetTrigger>
                <SheetContent side="left" class="w-[320px] gap-0 p-0">
                    <SheetTitle class="sr-only">Menu</SheetTitle>
                    <SheetDescription class="sr-only">
                        Site navigation
                    </SheetDescription>
                    <div class="border-b px-6 py-5">
                        <SiteLogo />
                    </div>
                    <nav class="flex flex-1 flex-col overflow-y-auto px-6 py-6">
                        <Link
                            :href="watchesIndex()"
                            class="font-display text-3xl font-medium"
                        >
                            All watches
                        </Link>
                        <div class="mt-5 space-y-1 border-l pl-4">
                            <Link
                                v-for="category in categories"
                                :key="category.id"
                                :href="showCollection(category)"
                                class="block py-1.5 text-muted-foreground transition-colors hover:text-foreground"
                            >
                                {{ category.name }}
                            </Link>
                        </div>
                        <div class="mt-8 space-y-3">
                            <Link
                                v-for="link in links"
                                :key="link.title"
                                :href="link.href"
                                class="block font-display text-3xl font-medium"
                            >
                                {{ link.title }}
                            </Link>
                        </div>
                        <Link
                            v-if="isAdmin"
                            :href="dashboard()"
                            class="mt-auto flex items-center gap-2 pt-8 text-sm text-muted-foreground"
                        >
                            <LayoutDashboard class="size-4" /> Dashboard
                        </Link>
                    </nav>
                </SheetContent>
            </Sheet>

            <Link
                :href="home()"
                class="shrink-0 transition-opacity hover:opacity-80"
                aria-label="Home"
            >
                <SiteLogo :inverted="isOverHero" />
            </Link>

            <nav
                class="hidden flex-1 items-center justify-center gap-1 lg:flex"
                aria-label="Main"
            >
                <button
                    type="button"
                    class="group inline-flex h-10 items-center gap-1.5 rounded-full px-4 text-sm font-medium transition-colors hover:bg-current/10"
                    :aria-expanded="menuOpen"
                    aria-controls="collections-menu"
                    @mouseenter="menuOpen = true"
                    @click="menuOpen = !menuOpen"
                >
                    Watches
                    <ChevronDown
                        class="size-3.5 transition-transform duration-300"
                        :class="{ 'rotate-180': menuOpen }"
                    />
                </button>
                <Link
                    v-for="link in links"
                    :key="link.title"
                    :href="link.href"
                    class="relative inline-flex h-10 items-center rounded-full px-4 text-sm font-medium transition-colors hover:bg-current/10"
                    @mouseenter="menuOpen = false"
                >
                    {{ link.title }}
                    <span
                        v-if="isCurrentOrParentUrl(link.href)"
                        class="absolute inset-x-4 bottom-1.5 h-px bg-gold"
                    />
                </Link>
            </nav>

            <div class="ml-auto flex items-center gap-1 lg:ml-0">
                <Button
                    variant="ghost"
                    class="h-10 gap-2 rounded-full px-3 text-current hover:bg-current/10 hover:text-current"
                    aria-label="Search watches"
                    @click="searchOpen = true"
                >
                    <Search class="size-[18px]" />
                    <kbd
                        class="hidden rounded border border-current/25 px-1.5 text-[10px] font-normal opacity-70 xl:inline"
                        >Ctrl K</kbd
                    >
                </Button>
                <ThemeToggle />
                <Button
                    v-if="isAdmin"
                    as-child
                    variant="ghost"
                    size="icon"
                    class="hidden rounded-full text-current hover:bg-current/10 hover:text-current sm:inline-flex"
                >
                    <Link :href="dashboard()" aria-label="Dashboard">
                        <LayoutDashboard class="size-[18px]" />
                    </Link>
                </Button>
                <Button
                    as-child
                    class="ml-2 hidden h-10 rounded-full px-5 md:inline-flex"
                    :class="
                        isOverHero
                            ? 'bg-white text-neutral-900 hover:bg-white/90'
                            : ''
                    "
                >
                    <Link :href="watchesIndex()">Shop the collection</Link>
                </Button>
            </div>
        </div>

        <Transition
            enter-active-class="transition duration-300 ease-(--ease-out-expo)"
            enter-from-class="opacity-0 -translate-y-2"
            leave-active-class="transition duration-200"
            leave-to-class="opacity-0 -translate-y-1"
        >
            <div
                v-show="menuOpen"
                id="collections-menu"
                class="absolute inset-x-0 top-full hidden border-b bg-background/95 text-foreground shadow-2xl shadow-black/5 backdrop-blur-xl lg:block"
            >
                <div
                    class="mx-auto grid max-w-7xl grid-cols-12 gap-10 px-8 py-10"
                >
                    <div class="col-span-4">
                        <p
                            class="text-[11px] font-medium tracking-[0.28em] text-gold uppercase"
                        >
                            The collection
                        </p>
                        <p
                            class="mt-3 font-display text-3xl leading-tight font-medium text-balance"
                        >
                            Every watch inspected, authenticated and ready to
                            wear.
                        </p>
                        <Link
                            :href="watchesIndex()"
                            class="group mt-6 inline-flex items-center gap-2 text-sm font-medium"
                        >
                            Browse all watches
                            <ArrowRight
                                class="size-4 transition-transform group-hover:translate-x-1"
                            />
                        </Link>
                    </div>
                    <div class="col-span-8 grid grid-cols-2 gap-x-10 gap-y-1">
                        <Link
                            v-for="category in categories"
                            :key="category.id"
                            :href="showCollection(category)"
                            class="group flex items-center justify-between border-b border-border/60 py-3.5 text-[15px] transition-colors hover:text-gold"
                        >
                            {{ category.name }}
                            <ArrowRight
                                class="size-4 -translate-x-2 opacity-0 transition-all duration-300 group-hover:translate-x-0 group-hover:opacity-100"
                            />
                        </Link>
                    </div>
                </div>
            </div>
        </Transition>

        <SearchDialog v-model:open="searchOpen" />
    </header>
</template>
