<script setup lang="ts">
import { onMounted } from 'vue';
import FloatingContact from '@/components/store/FloatingContact.vue';
import SeoHead from '@/components/store/SeoHead';
import StoreFooter from '@/components/store/StoreFooter.vue';
import StoreHeader from '@/components/store/StoreHeader.vue';
import { Toaster } from '@/components/ui/sonner';
import { useSite } from '@/composables/useSite';
import { loadChatwoot } from '@/lib/chatwoot';

withDefaults(
    defineProps<{
        transparentHeader?: boolean;
        floatingContactOnMobile?: boolean;
    }>(),
    {
        transparentHeader: false,
        floatingContactOnMobile: true,
    },
);

const { site } = useSite();

onMounted(() => {
    const chatwoot = site.value.inquiry.chatwoot;

    if (site.value.inquiry.channel !== 'chatwoot' || !chatwoot) {
        return;
    }

    const preload = () => loadChatwoot(chatwoot).catch(() => {});

    if ('requestIdleCallback' in window) {
        window.requestIdleCallback(preload, { timeout: 4000 });
    } else {
        setTimeout(preload, 2500);
    }
});
</script>

<template>
    <div class="flex min-h-svh flex-col bg-background">
        <SeoHead />
        <a
            href="#main"
            class="sr-only z-50 rounded-md bg-primary px-4 py-2 text-primary-foreground focus:not-sr-only focus:fixed focus:top-4 focus:left-4"
            >Skip to content</a
        >
        <div
            v-if="site.announcement"
            class="relative z-50 bg-primary px-4 py-2.5 text-center text-[10px] font-medium tracking-[0.12em] text-primary-foreground uppercase sm:text-[11px] sm:tracking-[0.2em]"
        >
            {{ site.announcement }}
        </div>
        <StoreHeader :transparent="transparentHeader" />
        <main id="main" class="flex-1">
            <slot />
        </main>
        <StoreFooter />
        <FloatingContact
            :class="{ 'max-sm:hidden': !floatingContactOnMobile }"
        />
        <Toaster />
    </div>
</template>
