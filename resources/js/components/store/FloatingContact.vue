<script setup lang="ts">
import { Mail, MessagesSquare } from '@lucide/vue';
import { useWindowScroll } from '@vueuse/core';
import { computed } from 'vue';
import BrandIcon from '@/components/store/BrandIcon.vue';
import { Spinner } from '@/components/ui/spinner';
import { useInquiry } from '@/composables/useInquiry';
import { useSite } from '@/composables/useSite';

const { site } = useSite();
const { channel, label, opening, inquire } = useInquiry();
const { y } = useWindowScroll();

const isExpanded = computed(() => y.value < 400);
</script>

<template>
    <div
        v-if="site.inquiry.floatingButton"
        class="fixed right-4 bottom-4 z-40 sm:right-6 sm:bottom-6"
    >
        <button
            type="button"
            class="group relative flex h-14 items-center gap-3 overflow-hidden rounded-full bg-primary pr-5 pl-4 text-primary-foreground shadow-2xl ring-1 shadow-black/25 ring-white/10 transition-all duration-500 ease-(--ease-out-expo) hover:-translate-y-0.5 hover:shadow-black/35 focus-visible:ring-4 focus-visible:ring-ring/50 focus-visible:outline-none"
            :class="isExpanded ? 'max-w-80' : 'max-w-14 pr-4'"
            :aria-label="label"
            @click="inquire()"
        >
            <span
                class="pointer-events-none absolute top-1/2 left-7 size-10 -translate-x-1/2 -translate-y-1/2 animate-pulse-ring rounded-full bg-gold/60"
                aria-hidden="true"
            />
            <span
                class="relative flex size-6 shrink-0 items-center justify-center"
            >
                <Spinner v-if="opening" />
                <BrandIcon
                    v-else-if="channel === 'whatsapp'"
                    name="whatsapp"
                    class="size-6"
                />
                <MessagesSquare
                    v-else-if="channel === 'chatwoot'"
                    class="size-6"
                />
                <Mail v-else class="size-6" />
            </span>
            <span
                class="relative text-sm font-medium whitespace-nowrap transition-opacity duration-300"
                :class="isExpanded ? 'opacity-100' : 'opacity-0'"
            >
                {{ label }}
            </span>
        </button>
    </div>
</template>
