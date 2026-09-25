<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Clock, Mail, MapPin, Phone } from '@lucide/vue';
import { computed } from 'vue';
import BrandIcon from '@/components/store/BrandIcon.vue';
import InquireButton from '@/components/store/InquireButton.vue';
import SiteLogo from '@/components/store/SiteLogo.vue';
import { useSite } from '@/composables/useSite';
import { about, contact, sitemap } from '@/routes';
import { show as showCollection } from '@/routes/collections';
import { index as watchesIndex } from '@/routes/watches';
import type { SocialNetwork } from '@/types';

const page = usePage();
const { site } = useSite();

const categories = computed(() => page.props.navigation.categories);
const socials = computed(
    () => Object.entries(site.value.social) as [SocialNetwork, string][],
);
const year = new Date().getFullYear();

const socialLabels: Record<SocialNetwork, string> = {
    instagram: 'Instagram',
    facebook: 'Facebook',
    x: 'X',
    tiktok: 'TikTok',
    youtube: 'YouTube',
};
</script>

<template>
    <footer class="relative overflow-hidden border-t bg-card">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div
                class="flex flex-col items-start justify-between gap-8 border-b py-16 md:flex-row md:items-end"
            >
                <div class="max-w-xl">
                    <p
                        class="text-[11px] font-medium tracking-[0.28em] text-gold uppercase"
                    >
                        Personal service
                    </p>
                    <h2
                        class="mt-3 font-display text-4xl leading-tight font-medium text-balance md:text-5xl"
                    >
                        Looking for something specific? We will find it for you.
                    </h2>
                </div>
                <InquireButton
                    label="Talk to a specialist"
                    class="h-12 rounded-full px-7"
                />
            </div>

            <div class="grid gap-12 py-14 md:grid-cols-12">
                <div class="md:col-span-4">
                    <SiteLogo />
                    <p
                        v-if="site.tagline"
                        class="mt-4 max-w-xs text-sm leading-relaxed text-muted-foreground"
                    >
                        {{ site.tagline }}
                    </p>
                    <div v-if="socials.length" class="mt-6 flex gap-2">
                        <a
                            v-for="[network, url] in socials"
                            :key="network"
                            :href="url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex size-10 items-center justify-center rounded-full border transition-colors hover:border-foreground hover:bg-foreground hover:text-background"
                            :aria-label="socialLabels[network]"
                        >
                            <BrandIcon :name="network" class="size-[18px]" />
                        </a>
                    </div>
                </div>

                <div class="md:col-span-3">
                    <h3
                        class="text-[11px] font-medium tracking-[0.22em] text-muted-foreground uppercase"
                    >
                        Collections
                    </h3>
                    <ul class="mt-5 space-y-3 text-sm">
                        <li>
                            <Link
                                :href="watchesIndex()"
                                class="transition-colors hover:text-gold"
                                >All watches</Link
                            >
                        </li>
                        <li v-for="category in categories" :key="category.id">
                            <Link
                                :href="showCollection(category)"
                                class="transition-colors hover:text-gold"
                            >
                                {{ category.name }}
                            </Link>
                        </li>
                    </ul>
                </div>

                <div class="md:col-span-2">
                    <h3
                        class="text-[11px] font-medium tracking-[0.22em] text-muted-foreground uppercase"
                    >
                        Company
                    </h3>
                    <ul class="mt-5 space-y-3 text-sm">
                        <li>
                            <Link
                                :href="about()"
                                class="transition-colors hover:text-gold"
                                >About us</Link
                            >
                        </li>
                        <li>
                            <Link
                                :href="contact()"
                                class="transition-colors hover:text-gold"
                                >Contact</Link
                            >
                        </li>
                        <li>
                            <a
                                :href="sitemap.url()"
                                class="transition-colors hover:text-gold"
                                >Sitemap</a
                            >
                        </li>
                    </ul>
                </div>

                <div class="md:col-span-3">
                    <h3
                        class="text-[11px] font-medium tracking-[0.22em] text-muted-foreground uppercase"
                    >
                        Visit and contact
                    </h3>
                    <ul class="mt-5 space-y-3 text-sm text-muted-foreground">
                        <li v-if="site.contact.address" class="flex gap-3">
                            <MapPin class="mt-0.5 size-4 shrink-0" />
                            {{ site.contact.address }}
                        </li>
                        <li v-if="site.contact.hours" class="flex gap-3">
                            <Clock class="mt-0.5 size-4 shrink-0" />
                            {{ site.contact.hours }}
                        </li>
                        <li v-if="site.contact.whatsapp" class="flex gap-3">
                            <BrandIcon
                                name="whatsapp"
                                class="mt-0.5 size-4 shrink-0"
                            />
                            <a
                                :href="`https://wa.me/${site.contact.whatsapp.replace(/\D/g, '')}`"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="hover:text-foreground"
                                >{{ site.contact.whatsapp }}</a
                            >
                        </li>
                        <li v-if="site.contact.phone" class="flex gap-3">
                            <Phone class="mt-0.5 size-4 shrink-0" />
                            <a
                                :href="`tel:${site.contact.phone}`"
                                class="hover:text-foreground"
                                >{{ site.contact.phone }}</a
                            >
                        </li>
                        <li v-if="site.contact.email" class="flex gap-3">
                            <Mail class="mt-0.5 size-4 shrink-0" />
                            <a
                                :href="`mailto:${site.contact.email}`"
                                class="break-all hover:text-foreground"
                                >{{ site.contact.email }}</a
                            >
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div
            class="pointer-events-none mx-auto max-w-7xl overflow-hidden px-4 select-none sm:px-6 lg:px-8"
            aria-hidden="true"
        >
            <p
                class="translate-y-[18%] text-center font-display text-[clamp(4rem,17vw,15rem)] leading-none font-medium tracking-tight whitespace-nowrap text-foreground/[0.045]"
            >
                {{ site.name }}
            </p>
        </div>

        <div class="border-t">
            <div
                class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-6 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8"
            >
                <p>&copy; {{ year }} {{ site.name }}. All rights reserved.</p>
                <p>
                    All trademarks belong to their respective owners. We are an
                    independent dealer.
                </p>
            </div>
        </div>
    </footer>
</template>
