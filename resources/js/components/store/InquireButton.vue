<script setup lang="ts">
import { Mail, MessagesSquare } from '@lucide/vue';
import BrandIcon from '@/components/store/BrandIcon.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useInquiry } from '@/composables/useInquiry';
import type { Watch } from '@/types';

const props = withDefaults(
    defineProps<{
        watch?: Pick<Watch, 'name' | 'slug' | 'reference' | 'brand'>;
        label?: string;
        variant?: 'default' | 'outline' | 'secondary' | 'ghost';
        size?: 'default' | 'lg' | 'sm';
    }>(),
    { variant: 'default', size: 'lg', label: undefined, watch: undefined },
);

const { channel, label: channelLabel, opening, inquire } = useInquiry();
</script>

<template>
    <Button
        :variant="variant"
        :size="size"
        :disabled="opening"
        class="gap-2.5"
        @click="inquire(props.watch)"
    >
        <Spinner v-if="opening" />
        <BrandIcon
            v-else-if="channel === 'whatsapp'"
            name="whatsapp"
            class="size-[18px]"
        />
        <MessagesSquare
            v-else-if="channel === 'chatwoot'"
            class="size-[18px]"
        />
        <Mail v-else class="size-[18px]" />
        {{ label ?? channelLabel }}
    </Button>
</template>
