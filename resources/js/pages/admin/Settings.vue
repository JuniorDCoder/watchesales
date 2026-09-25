<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Check,
    Globe,
    Home,
    Mail,
    MessagesSquare,
    Palette,
    Search,
} from '@lucide/vue';
import { computed, reactive } from 'vue';
import type { Component } from 'vue';
import ImageField from '@/components/admin/ImageField.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import BrandIcon from '@/components/store/BrandIcon.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { currencyName, currencySymbol, DEFAULT_CURRENCY } from '@/lib/format';
import { edit as editSettings, update } from '@/routes/admin/settings';
import type { InquiryChannel, Option } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Site settings', href: editSettings() }],
    },
});

type ImageKey = 'logo' | 'logo_dark' | 'favicon' | 'og_image' | 'hero_image';

const props = defineProps<{
    settings: Record<string, string | boolean | null>;
    images: Record<ImageKey, string | null>;
    channels: Option[];
    currencies: string[];
}>();

const text = (key: string): string =>
    (props.settings[key] as string | null) ?? '';

const form = useForm({
    site_name: text('site_name'),
    tagline: text('tagline'),
    announcement: text('announcement'),
    hero_eyebrow: text('hero_eyebrow'),
    hero_title: text('hero_title'),
    hero_subtitle: text('hero_subtitle'),
    about_title: text('about_title'),
    about_body: text('about_body'),
    inquiry_channel:
        (props.settings.inquiry_channel as InquiryChannel) ?? 'email',
    floating_button_enabled: Boolean(props.settings.floating_button_enabled),
    contact_email: text('contact_email'),
    contact_phone: text('contact_phone'),
    whatsapp_number: text('whatsapp_number'),
    address: text('address'),
    business_hours: text('business_hours'),
    chatwoot_base_url: text('chatwoot_base_url'),
    chatwoot_website_token: text('chatwoot_website_token'),
    currency: text('currency') || DEFAULT_CURRENCY,
    meta_title: text('meta_title'),
    meta_description: text('meta_description'),
    instagram_url: text('instagram_url'),
    facebook_url: text('facebook_url'),
    x_url: text('x_url'),
    tiktok_url: text('tiktok_url'),
    youtube_url: text('youtube_url'),
    logo: null as File | null,
    logo_dark: null as File | null,
    favicon: null as File | null,
    og_image: null as File | null,
    hero_image: null as File | null,
});

const removals = reactive<Record<ImageKey, boolean>>({
    logo: false,
    logo_dark: false,
    favicon: false,
    og_image: false,
    hero_image: false,
});

const whatsappDigits = computed(() => form.whatsapp_number.replace(/\D/g, ''));

const channelDetails: Record<
    InquiryChannel,
    { icon: Component | 'whatsapp'; description: string }
> = {
    chatwoot: {
        icon: MessagesSquare,
        description: 'Live chat through your Chatwoot inbox',
    },
    whatsapp: {
        icon: 'whatsapp',
        description: 'Opens WhatsApp with a message ready to send',
    },
    email: {
        icon: Mail,
        description: "Opens the visitor's email app with a draft",
    },
};

const currencyLabel = (code: string): string =>
    `${code}, ${currencyName(code)} (${currencySymbol(code)})`;

const tabErrors = computed(() => {
    const keys = Object.keys(form.errors);
    const has = (fields: string[]) => keys.some((key) => fields.includes(key));

    return {
        brand: has([
            'site_name',
            'tagline',
            'announcement',
            'logo',
            'logo_dark',
            'favicon',
            'currency',
        ]),
        home: has([
            'hero_eyebrow',
            'hero_title',
            'hero_subtitle',
            'hero_image',
            'about_title',
            'about_body',
        ]),
        contact: has([
            'inquiry_channel',
            'contact_email',
            'contact_phone',
            'whatsapp_number',
            'address',
            'business_hours',
            'chatwoot_base_url',
            'chatwoot_website_token',
        ]),
        seo: has([
            'meta_title',
            'meta_description',
            'og_image',
            'instagram_url',
            'facebook_url',
            'x_url',
            'tiktok_url',
            'youtube_url',
        ]),
    };
});

function submit(): void {
    form.transform((data) => ({
        ...data,
        _method: 'put',
        remove: (Object.keys(removals) as ImageKey[]).filter(
            (key) => removals[key],
        ),
    })).post(update.url(), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            (Object.keys(removals) as ImageKey[]).forEach((key) => {
                removals[key] = false;
                form[key] = null;
            });
            form.defaults();
        },
    });
}
</script>

<template>
    <Head title="Site settings" />

    <form
        class="flex flex-1 flex-col gap-6 p-4 pb-0 md:p-6 md:pb-0"
        @submit.prevent="submit"
    >
        <PageHeader
            title="Site settings"
            description="Branding, homepage content, how customers contact you and search appearance."
        />

        <Tabs default-value="brand" class="gap-6">
            <TabsList class="h-auto w-full flex-wrap justify-start sm:w-fit">
                <TabsTrigger value="brand" class="gap-2 px-3 py-1.5">
                    <Palette class="size-4" /> Brand
                    <span
                        v-if="tabErrors.brand"
                        class="size-1.5 rounded-full bg-destructive"
                    />
                </TabsTrigger>
                <TabsTrigger value="home" class="gap-2 px-3 py-1.5">
                    <Home class="size-4" /> Homepage
                    <span
                        v-if="tabErrors.home"
                        class="size-1.5 rounded-full bg-destructive"
                    />
                </TabsTrigger>
                <TabsTrigger value="contact" class="gap-2 px-3 py-1.5">
                    <MessagesSquare class="size-4" /> Contact and inquiries
                    <span
                        v-if="tabErrors.contact"
                        class="size-1.5 rounded-full bg-destructive"
                    />
                </TabsTrigger>
                <TabsTrigger value="seo" class="gap-2 px-3 py-1.5">
                    <Search class="size-4" /> SEO and social
                    <span
                        v-if="tabErrors.seo"
                        class="size-1.5 rounded-full bg-destructive"
                    />
                </TabsTrigger>
            </TabsList>

            <TabsContent value="brand" class="grid gap-6 lg:grid-cols-2">
                <section
                    class="grid content-start gap-5 rounded-xl border bg-card p-5 md:p-6"
                >
                    <h2 class="font-medium">Identity</h2>
                    <div class="grid gap-2">
                        <Label for="site_name">Store name</Label>
                        <Input
                            id="site_name"
                            v-model="form.site_name"
                            required
                        />
                        <InputError :message="form.errors.site_name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="tagline">Tagline</Label>
                        <Input id="tagline" v-model="form.tagline" />
                        <InputError :message="form.errors.tagline" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="announcement">Announcement bar</Label>
                        <Input
                            id="announcement"
                            v-model="form.announcement"
                            placeholder="Leave empty to hide the bar"
                        />
                        <InputError :message="form.errors.announcement" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="currency">Store currency</Label>
                        <Select v-model="form.currency">
                            <SelectTrigger id="currency" class="w-full"
                                ><SelectValue
                            /></SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="currency in props.currencies"
                                    :key="currency"
                                    :value="currency"
                                    >{{ currencyLabel(currency) }}</SelectItem
                                >
                            </SelectContent>
                        </Select>
                        <p class="text-xs text-muted-foreground">
                            Used for every price on the storefront, the
                            dashboard and search results. Prices are not
                            converted, so update them if you switch currency.
                        </p>
                        <InputError :message="form.errors.currency" />
                    </div>
                </section>

                <section
                    class="grid content-start gap-5 rounded-xl border bg-card p-5 md:p-6"
                >
                    <h2 class="font-medium">Logos</h2>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <ImageField
                            v-model:file="form.logo"
                            v-model:removed="removals.logo"
                            label="Logo"
                            hint="Shown on light backgrounds. PNG or WebP with transparency works best."
                            aspect="aspect-[3/1]"
                            contain
                            :current-url="images.logo"
                            :error="form.errors.logo"
                        />
                        <ImageField
                            v-model:file="form.logo_dark"
                            v-model:removed="removals.logo_dark"
                            label="Logo for dark backgrounds"
                            hint="Used in dark mode and over the homepage photo."
                            aspect="aspect-[3/1]"
                            contain
                            class="[&_.border-dashed]:bg-neutral-900"
                            :current-url="images.logo_dark"
                            :error="form.errors.logo_dark"
                        />
                    </div>
                    <div class="max-w-40">
                        <ImageField
                            v-model:file="form.favicon"
                            v-model:removed="removals.favicon"
                            label="Browser icon"
                            hint="Square PNG, at least 180px."
                            aspect="aspect-square"
                            accept="image/png,image/webp,image/x-icon"
                            contain
                            :current-url="images.favicon"
                            :error="form.errors.favicon"
                        />
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Without a logo, the store name is shown in the display
                        typeface.
                    </p>
                </section>
            </TabsContent>

            <TabsContent value="home" class="grid gap-6 lg:grid-cols-2">
                <section
                    class="grid content-start gap-5 rounded-xl border bg-card p-5 md:p-6"
                >
                    <h2 class="font-medium">Hero</h2>
                    <div class="grid gap-2">
                        <Label for="hero_eyebrow">Small heading</Label>
                        <Input id="hero_eyebrow" v-model="form.hero_eyebrow" />
                        <InputError :message="form.errors.hero_eyebrow" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="hero_title">Headline</Label>
                        <Input id="hero_title" v-model="form.hero_title" />
                        <InputError :message="form.errors.hero_title" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="hero_subtitle">Introduction</Label>
                        <Textarea
                            id="hero_subtitle"
                            v-model="form.hero_subtitle"
                            rows="3"
                        />
                        <InputError :message="form.errors.hero_subtitle" />
                    </div>
                    <ImageField
                        v-model:file="form.hero_image"
                        v-model:removed="removals.hero_image"
                        label="Hero photograph"
                        hint="A wide, moody photo works best. Without one, the first featured watch is used."
                        :current-url="images.hero_image"
                        :error="form.errors.hero_image"
                    />
                </section>

                <section
                    class="grid content-start gap-5 rounded-xl border bg-card p-5 md:p-6"
                >
                    <h2 class="font-medium">About page</h2>
                    <div class="grid gap-2">
                        <Label for="about_title">Headline</Label>
                        <Input id="about_title" v-model="form.about_title" />
                        <InputError :message="form.errors.about_title" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="about_body">Story</Label>
                        <Textarea
                            id="about_body"
                            v-model="form.about_body"
                            class="min-h-56"
                        />
                        <p class="text-xs text-muted-foreground">
                            Leave a blank line between paragraphs.
                        </p>
                        <InputError :message="form.errors.about_body" />
                    </div>
                </section>
            </TabsContent>

            <TabsContent value="contact" class="grid gap-6 lg:grid-cols-5">
                <section
                    class="grid content-start gap-5 rounded-xl border bg-card p-5 md:p-6 lg:col-span-3"
                >
                    <div>
                        <h2 class="font-medium">Inquiry channel</h2>
                        <p class="text-sm text-muted-foreground">
                            Used by the enquire button on every watch. When
                            Chatwoot is connected, its live chat bubble is
                            always shown in the corner of every page.
                        </p>
                    </div>
                    <div
                        class="grid gap-3 sm:grid-cols-3"
                        role="radiogroup"
                        aria-label="Inquiry channel"
                    >
                        <button
                            v-for="channel in channels"
                            :key="channel.value"
                            type="button"
                            role="radio"
                            :aria-checked="
                                form.inquiry_channel === channel.value
                            "
                            class="relative flex flex-col items-start gap-3 rounded-lg border p-4 text-left transition-all"
                            :class="
                                form.inquiry_channel === channel.value
                                    ? 'border-primary bg-primary/[0.03] ring-1 ring-primary'
                                    : 'hover:border-foreground/30'
                            "
                            @click="
                                form.inquiry_channel =
                                    channel.value as InquiryChannel
                            "
                        >
                            <span
                                class="flex size-9 items-center justify-center rounded-full bg-muted"
                            >
                                <BrandIcon
                                    v-if="
                                        channelDetails[
                                            channel.value as InquiryChannel
                                        ].icon === 'whatsapp'
                                    "
                                    name="whatsapp"
                                    class="size-[18px]"
                                />
                                <component
                                    :is="
                                        channelDetails[
                                            channel.value as InquiryChannel
                                        ].icon
                                    "
                                    v-else
                                    class="size-[18px]"
                                />
                            </span>
                            <span>
                                <span class="block text-sm font-medium">{{
                                    channel.label
                                }}</span>
                                <span
                                    class="block text-xs text-muted-foreground"
                                >
                                    {{
                                        channelDetails[
                                            channel.value as InquiryChannel
                                        ].description
                                    }}
                                </span>
                            </span>
                            <Check
                                v-if="form.inquiry_channel === channel.value"
                                class="absolute top-3 right-3 size-4"
                            />
                        </button>
                    </div>
                    <InputError :message="form.errors.inquiry_channel" />

                    <div class="grid gap-4 rounded-lg border bg-muted/30 p-4">
                        <div class="grid gap-1">
                            <h3 class="text-sm font-medium">
                                Chatwoot live chat
                            </h3>
                            <p class="text-xs text-muted-foreground">
                                Add a website token to show the Chatwoot chat
                                bubble on every storefront page, whichever
                                inquiry channel is selected.
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="chatwoot_base_url">Chatwoot URL</Label>
                            <Input
                                id="chatwoot_base_url"
                                v-model="form.chatwoot_base_url"
                                placeholder="https://app.chatwoot.com"
                            />
                            <InputError
                                :message="form.errors.chatwoot_base_url"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="chatwoot_website_token"
                                >Website token</Label
                            >
                            <Input
                                id="chatwoot_website_token"
                                v-model="form.chatwoot_website_token"
                                placeholder="From Settings, Inboxes, your website inbox"
                            />
                            <InputError
                                :message="form.errors.chatwoot_website_token"
                            />
                        </div>
                        <p class="flex gap-2 text-xs text-muted-foreground">
                            <AlertTriangle class="size-4 shrink-0" />
                            If chat cannot load, live chat enquiries are sent to
                            WhatsApp or email instead, so keep one of them
                            filled in.
                        </p>
                    </div>

                    <div
                        class="flex items-start justify-between gap-4 border-t pt-5"
                    >
                        <Label
                            for="floating_button_enabled"
                            class="grid gap-1 font-normal"
                        >
                            <span class="font-medium"
                                >Floating contact button</span
                            >
                            <span class="text-xs text-muted-foreground"
                                >Keeps a contact button in the corner of every
                                storefront page. Replaced by the Chatwoot bubble
                                when Chatwoot is connected.</span
                            >
                        </Label>
                        <Switch
                            id="floating_button_enabled"
                            v-model="form.floating_button_enabled"
                        />
                    </div>
                </section>

                <section
                    class="grid content-start gap-5 rounded-xl border bg-card p-5 md:p-6 lg:col-span-2"
                >
                    <h2 class="font-medium">Contact details</h2>
                    <div class="grid gap-2">
                        <div class="flex items-center justify-between gap-2">
                            <Label for="whatsapp_number">WhatsApp number</Label>
                            <span
                                v-if="form.inquiry_channel === 'whatsapp'"
                                class="rounded-full bg-primary px-2 py-0.5 text-[10px] font-medium text-primary-foreground"
                                >Used for inquiries</span
                            >
                        </div>
                        <Input
                            id="whatsapp_number"
                            v-model="form.whatsapp_number"
                            placeholder="+1 (929) 796-3621"
                        />
                        <p class="text-xs text-muted-foreground">
                            Include the country code.
                            <a
                                v-if="whatsappDigits.length >= 7"
                                :href="`https://wa.me/${whatsappDigits}`"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="font-medium text-foreground underline underline-offset-2"
                                >Test this number</a
                            >
                        </p>
                        <InputError :message="form.errors.whatsapp_number" />
                    </div>
                    <div class="grid gap-2">
                        <div class="flex items-center justify-between gap-2">
                            <Label for="contact_email">Email</Label>
                            <span
                                v-if="form.inquiry_channel === 'email'"
                                class="rounded-full bg-primary px-2 py-0.5 text-[10px] font-medium text-primary-foreground"
                                >Used for inquiries</span
                            >
                        </div>
                        <Input
                            id="contact_email"
                            v-model="form.contact_email"
                            type="email"
                        />
                        <InputError :message="form.errors.contact_email" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="contact_phone">Telephone</Label>
                        <Input
                            id="contact_phone"
                            v-model="form.contact_phone"
                        />
                        <InputError :message="form.errors.contact_phone" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="address">Address or visiting note</Label>
                        <Input id="address" v-model="form.address" />
                        <InputError :message="form.errors.address" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="business_hours">Opening hours</Label>
                        <Input
                            id="business_hours"
                            v-model="form.business_hours"
                        />
                        <InputError :message="form.errors.business_hours" />
                    </div>
                </section>
            </TabsContent>

            <TabsContent value="seo" class="grid gap-6 lg:grid-cols-2">
                <section
                    class="grid content-start gap-5 rounded-xl border bg-card p-5 md:p-6"
                >
                    <h2 class="font-medium">Search engines</h2>
                    <div class="grid gap-2">
                        <div class="flex justify-between">
                            <Label for="meta_title">Homepage title</Label>
                            <span
                                class="text-xs text-muted-foreground tabular-nums"
                                >{{ form.meta_title.length }} / 70</span
                            >
                        </div>
                        <Input
                            id="meta_title"
                            v-model="form.meta_title"
                            maxlength="70"
                            placeholder="Defaults to the tagline"
                        />
                        <InputError :message="form.errors.meta_title" />
                    </div>
                    <div class="grid gap-2">
                        <div class="flex justify-between">
                            <Label for="meta_description"
                                >Default description</Label
                            >
                            <span
                                class="text-xs text-muted-foreground tabular-nums"
                                >{{ form.meta_description.length }} / 160</span
                            >
                        </div>
                        <Textarea
                            id="meta_description"
                            v-model="form.meta_description"
                            rows="3"
                            maxlength="160"
                        />
                        <InputError :message="form.errors.meta_description" />
                    </div>
                    <ImageField
                        v-model:file="form.og_image"
                        v-model:removed="removals.og_image"
                        label="Social sharing image"
                        hint="Shown when pages are shared on social media. 1200 by 630 pixels is ideal."
                        aspect="aspect-[1200/630]"
                        :current-url="images.og_image"
                        :error="form.errors.og_image"
                    />
                </section>

                <section
                    class="grid content-start gap-5 rounded-xl border bg-card p-5 md:p-6"
                >
                    <h2 class="flex items-center gap-2 font-medium">
                        <Globe class="size-4" /> Social profiles
                    </h2>
                    <div
                        v-for="network in [
                            'instagram',
                            'facebook',
                            'x',
                            'tiktok',
                            'youtube',
                        ] as const"
                        :key="network"
                        class="grid gap-2"
                    >
                        <Label
                            :for="`${network}_url`"
                            class="flex items-center gap-2 capitalize"
                        >
                            <BrandIcon :name="network" class="size-4" />
                            {{ network === 'x' ? 'X' : network }}
                        </Label>
                        <Input
                            :id="`${network}_url`"
                            v-model="form[`${network}_url`]"
                            type="url"
                            placeholder="https://"
                        />
                        <InputError :message="form.errors[`${network}_url`]" />
                    </div>
                </section>
            </TabsContent>
        </Tabs>

        <div
            class="sticky bottom-0 z-20 -mx-4 mt-auto border-t bg-background/85 backdrop-blur-xl md:-mx-6"
        >
            <div class="flex items-center justify-end gap-3 px-4 py-3 md:px-6">
                <p
                    v-if="form.hasErrors"
                    class="mr-auto text-sm text-destructive"
                >
                    Please fix the highlighted fields.
                </p>
                <p
                    v-else-if="form.isDirty"
                    class="mr-auto text-sm text-muted-foreground"
                >
                    Unsaved changes
                </p>
                <Button type="submit" :disabled="form.processing">
                    <Spinner v-if="form.processing" /> Save settings
                </Button>
            </div>
        </div>
    </form>
</template>
