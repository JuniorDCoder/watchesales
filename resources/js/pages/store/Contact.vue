<script setup lang="ts">
import {
    ArrowUpRight,
    Clock,
    Mail,
    MapPin,
    MessagesSquare,
    Phone,
} from '@lucide/vue';
import { computed } from 'vue';
import type { Component } from 'vue';
import BrandIcon from '@/components/store/BrandIcon.vue';
import InquireButton from '@/components/store/InquireButton.vue';
import { useInquiry } from '@/composables/useInquiry';
import { useSite } from '@/composables/useSite';

const { site } = useSite();
const { channel, inquire } = useInquiry();

type ContactMethod = {
    key: string;
    title: string;
    detail: string;
    icon: Component | 'whatsapp';
    href?: string;
    action?: () => void;
    preferred: boolean;
};

const methods = computed<ContactMethod[]>(() => {
    const { contact, inquiry } = site.value;
    const list: ContactMethod[] = [];

    if (inquiry.chatwoot) {
        list.push({
            key: 'chatwoot',
            title: 'Live chat',
            detail: 'Usually replies within minutes',
            icon: MessagesSquare,
            action: () => inquire(),
            preferred: channel.value === 'chatwoot',
        });
    }

    if (contact.whatsapp) {
        list.push({
            key: 'whatsapp',
            title: 'WhatsApp',
            detail: contact.whatsapp,
            icon: 'whatsapp',
            href: `https://wa.me/${contact.whatsapp.replace(/\D/g, '')}`,
            preferred: channel.value === 'whatsapp',
        });
    }

    if (contact.email) {
        list.push({
            key: 'email',
            title: 'Email',
            detail: contact.email,
            icon: Mail,
            href: `mailto:${contact.email}`,
            preferred: channel.value === 'email',
        });
    }

    if (contact.phone) {
        list.push({
            key: 'phone',
            title: 'Telephone',
            detail: contact.phone,
            icon: Phone,
            href: `tel:${contact.phone}`,
            preferred: false,
        });
    }

    return list.sort((a, b) => Number(b.preferred) - Number(a.preferred));
});
</script>

<template>
    <section
        class="mx-auto max-w-7xl px-4 pt-14 pb-24 sm:px-6 md:pt-20 lg:px-8"
    >
        <div class="grid gap-16 lg:grid-cols-12">
            <div class="lg:col-span-5">
                <p
                    class="animate-rise text-[11px] font-medium tracking-[0.28em] text-gold uppercase"
                >
                    Contact
                </p>
                <h1
                    class="mt-4 animate-rise font-display text-5xl leading-[1.02] font-medium text-balance [animation-delay:80ms] md:text-7xl"
                >
                    Speak with a specialist
                </h1>
                <p
                    class="mt-6 max-w-md animate-rise text-lg leading-relaxed text-muted-foreground [animation-delay:160ms]"
                >
                    Questions about a watch, a request to source something
                    special, or simply advice on where to start. We are here to
                    help.
                </p>
                <div class="mt-10 animate-rise [animation-delay:240ms]">
                    <InquireButton class="h-12 rounded-full px-7" />
                </div>

                <ul
                    class="mt-14 space-y-4 border-t pt-8 text-sm text-muted-foreground"
                >
                    <li v-if="site.contact.address" class="flex gap-3">
                        <MapPin class="mt-0.5 size-4 shrink-0 text-gold" />
                        {{ site.contact.address }}
                    </li>
                    <li v-if="site.contact.hours" class="flex gap-3">
                        <Clock class="mt-0.5 size-4 shrink-0 text-gold" />
                        {{ site.contact.hours }}
                    </li>
                </ul>
            </div>

            <div class="grid content-start gap-4 sm:grid-cols-2 lg:col-span-7">
                <component
                    :is="method.href ? 'a' : 'button'"
                    v-for="(method, index) in methods"
                    :key="method.key"
                    v-reveal="index * 80"
                    :href="method.href"
                    :type="method.href ? undefined : 'button'"
                    :target="method.key === 'whatsapp' ? '_blank' : undefined"
                    :rel="
                        method.key === 'whatsapp'
                            ? 'noopener noreferrer'
                            : undefined
                    "
                    class="group relative flex min-h-52 flex-col justify-between rounded-lg border p-7 text-left transition-all duration-500 hover:-translate-y-1 hover:shadow-xl hover:shadow-black/5"
                    :class="
                        method.preferred
                            ? 'border-transparent bg-primary text-primary-foreground sm:col-span-2'
                            : 'bg-card'
                    "
                    @click="method.action?.()"
                >
                    <div class="flex items-start justify-between">
                        <span
                            class="flex size-12 items-center justify-center rounded-full"
                            :class="
                                method.preferred
                                    ? 'bg-primary-foreground/10'
                                    : 'bg-muted'
                            "
                        >
                            <BrandIcon
                                v-if="method.icon === 'whatsapp'"
                                name="whatsapp"
                                class="size-5"
                            />
                            <component
                                :is="method.icon"
                                v-else
                                class="size-5"
                            />
                        </span>
                        <ArrowUpRight
                            class="size-5 opacity-50 transition-all group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:opacity-100"
                        />
                    </div>
                    <div>
                        <p
                            v-if="method.preferred"
                            class="text-[11px] tracking-[0.24em] text-gold uppercase"
                        >
                            Fastest response
                        </p>
                        <p class="mt-1 font-display text-3xl font-medium">
                            {{ method.title }}
                        </p>
                        <p class="mt-1 text-sm break-all opacity-70">
                            {{ method.detail }}
                        </p>
                    </div>
                </component>
            </div>
        </div>
    </section>
</template>
