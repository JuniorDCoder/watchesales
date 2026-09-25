import type { InquiryChannel } from './catalog';

export type SocialNetwork =
    | 'instagram'
    | 'facebook'
    | 'x'
    | 'tiktok'
    | 'youtube';

export type Site = {
    name: string;
    tagline: string | null;
    announcement: string | null;
    logoUrl: string | null;
    logoDarkUrl: string | null;
    faviconUrl: string | null;
    currency: string;
    contact: {
        email: string | null;
        phone: string | null;
        whatsapp: string | null;
        address: string | null;
        hours: string | null;
    };
    inquiry: {
        channel: InquiryChannel;
        floatingButton: boolean;
        chatwoot: { baseUrl: string; websiteToken: string } | null;
    };
    social: Partial<Record<SocialNetwork, string>>;
};

export type Navigation = {
    categories: { id: number; name: string; slug: string }[];
};
