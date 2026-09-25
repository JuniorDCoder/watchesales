import { useHttp } from '@inertiajs/vue3';
import type { ComputedRef, Ref } from 'vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import { useSite } from '@/composables/useSite';
import { openChatwoot } from '@/lib/chatwoot';
import { store as storeInquiry } from '@/routes/inquiries';
import { show as showWatch } from '@/routes/watches';
import type { InquiryChannel, Site, Watch } from '@/types';

type InquirableWatch = Pick<Watch, 'name' | 'slug' | 'reference' | 'brand'>;

export type UseInquiryReturn = {
    channel: ComputedRef<InquiryChannel>;
    label: ComputedRef<string>;
    opening: Ref<boolean>;
    inquire: (watch?: InquirableWatch) => Promise<void>;
};

const channelLabels: Record<InquiryChannel, string> = {
    chatwoot: 'Chat with a specialist',
    whatsapp: 'Enquire on WhatsApp',
    email: 'Enquire by email',
};

function watchTitle(watch: InquirableWatch): string {
    return [watch.brand?.name, watch.name].filter(Boolean).join(' ');
}

function inquiryMessage(site: Site, watch?: InquirableWatch): string {
    if (!watch) {
        return `Hello ${site.name}, I would like some help finding a watch.`;
    }

    const reference = watch.reference ? ` (ref. ${watch.reference})` : '';
    const url = `${window.location.origin}${showWatch.url(watch)}`;

    return `Hello ${site.name}, I am interested in the ${watchTitle(watch)}${reference}. Is it still available?\n\n${url}`;
}

function openWhatsapp(number: string, message: string): void {
    const digits = number.replace(/\D/g, '');

    window.open(
        `https://wa.me/${digits}?text=${encodeURIComponent(message)}`,
        '_blank',
        'noopener',
    );
}

function openEmail(email: string, subject: string, message: string): void {
    window.location.href = `mailto:${email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(message)}`;
}

/**
 * Contact the store through whichever channel the admin selected in the site settings.
 */
export function useInquiry(): UseInquiryReturn {
    const { site } = useSite();
    const opening = ref(false);
    const tracker = useHttp<{ channel: string; watch: string | null }>({
        channel: '',
        watch: null,
    });

    const channel = computed(() => site.value.inquiry.channel);
    const label = computed(() => channelLabels[channel.value]);

    function track(usedChannel: InquiryChannel, watch?: InquirableWatch): void {
        tracker.channel = usedChannel;
        tracker.watch = watch?.slug ?? null;
        tracker.post(storeInquiry.url()).catch(() => {});
    }

    function fallback(
        message: string,
        subject: string,
        watch?: InquirableWatch,
    ): void {
        const { whatsapp, email } = site.value.contact;

        if (whatsapp) {
            track('whatsapp', watch);
            openWhatsapp(whatsapp, message);
        } else if (email) {
            track('email', watch);
            openEmail(email, subject, message);
        } else {
            toast.error(
                'Live chat is unavailable right now. Please try again shortly.',
            );
        }
    }

    async function inquire(watch?: InquirableWatch): Promise<void> {
        const message = inquiryMessage(site.value, watch);
        const subject = watch
            ? `Enquiry: ${watchTitle(watch)}`
            : `Enquiry for ${site.value.name}`;
        const { chatwoot } = site.value.inquiry;
        const { whatsapp, email } = site.value.contact;

        if (channel.value === 'chatwoot' && chatwoot) {
            opening.value = true;

            try {
                await openChatwoot(
                    chatwoot,
                    watch
                        ? {
                              watch: watchTitle(watch),
                              reference: watch.reference ?? '',
                              url: `${window.location.origin}${showWatch.url(watch)}`,
                          }
                        : undefined,
                );
                track('chatwoot', watch);
            } catch {
                fallback(message, subject, watch);
            } finally {
                opening.value = false;
            }

            return;
        }

        if (channel.value === 'whatsapp' && whatsapp) {
            track('whatsapp', watch);
            openWhatsapp(whatsapp, message);

            return;
        }

        if (email) {
            track('email', watch);
            openEmail(email, subject, message);

            return;
        }

        fallback(message, subject, watch);
    }

    return { channel, label, opening, inquire };
}
