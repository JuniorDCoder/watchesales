<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';
import { index as watchesIndex } from '@/routes/watches';

const props = defineProps<{ status: number }>();

const messages: Record<number, { title: string; description: string }> = {
    403: {
        title: 'This area is private',
        description: 'You do not have permission to view this page.',
    },
    404: {
        title: 'This page has moved on',
        description:
            'Like the best watches, it may have already found a new home. Let us help you find another.',
    },
    419: {
        title: 'Your session expired',
        description: 'Please refresh the page and try again.',
    },
    429: {
        title: 'Please slow down',
        description:
            'You have made a lot of requests in a short time. Wait a moment and try again.',
    },
    500: {
        title: 'Something went wrong',
        description:
            'We are looking into it. Please try again in a few minutes.',
    },
    503: {
        title: 'Back shortly',
        description:
            'We are carrying out some maintenance and will be back very soon.',
    },
};

const message = computed(() => messages[props.status] ?? messages[500]);
</script>

<template>
    <Head :title="message.title">
        <meta head-key="robots" name="robots" content="noindex" />
    </Head>

    <section
        class="mx-auto flex min-h-[70svh] max-w-3xl flex-col items-center justify-center px-4 py-24 text-center"
    >
        <p
            class="animate-rise font-display text-[clamp(6rem,20vw,12rem)] leading-none text-gold/70 tabular-nums"
        >
            {{ status }}
        </p>
        <h1
            class="mt-4 animate-rise font-display text-4xl font-medium text-balance [animation-delay:100ms] md:text-5xl"
        >
            {{ message.title }}
        </h1>
        <p
            class="mt-4 max-w-md animate-rise text-muted-foreground [animation-delay:200ms]"
        >
            {{ message.description }}
        </p>
        <div
            class="mt-10 flex animate-rise flex-wrap justify-center gap-3 [animation-delay:300ms]"
        >
            <Button as-child class="h-12 gap-2 rounded-full px-7">
                <Link :href="watchesIndex()"
                    >Browse watches <ArrowRight class="size-4"
                /></Link>
            </Button>
            <Button as-child variant="outline" class="h-12 rounded-full px-7">
                <Link :href="home()">Back to home</Link>
            </Button>
        </div>
    </section>
</template>
