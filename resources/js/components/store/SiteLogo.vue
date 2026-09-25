<script setup lang="ts">
import { computed } from 'vue';
import { useSite } from '@/composables/useSite';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        inverted?: boolean;
        class?: string;
    }>(),
    { inverted: false, class: '' },
);

const { site } = useSite();

const lightLogo = computed(() => site.value.logoUrl);
const darkLogo = computed(() => site.value.logoDarkUrl ?? site.value.logoUrl);
</script>

<template>
    <span :class="cn('inline-flex items-center', props.class)">
        <template v-if="lightLogo">
            <img
                v-if="inverted"
                :src="darkLogo!"
                :alt="site.name"
                class="h-8 w-auto max-w-[180px] object-contain"
            />
            <template v-else>
                <img
                    :src="lightLogo"
                    :alt="site.name"
                    class="h-8 w-auto max-w-[180px] object-contain dark:hidden"
                />
                <img
                    :src="darkLogo!"
                    :alt="site.name"
                    class="hidden h-8 w-auto max-w-[180px] object-contain dark:block"
                />
            </template>
        </template>
        <span
            v-else
            class="font-display text-[1.7rem] leading-none font-semibold tracking-[0.02em]"
        >
            {{ site.name }}
        </span>
    </span>
</template>
