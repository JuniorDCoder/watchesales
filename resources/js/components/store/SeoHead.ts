import { Head, usePage } from '@inertiajs/vue3';
import { defineComponent, h } from 'vue';
import type { VNode } from 'vue';
import type { Seo } from '@/types';

/**
 * Renders the page's search and social metadata. The same tags are rendered
 * by the Blade template on the first request, keyed so Inertia can adopt them.
 */
export default defineComponent({
    name: 'SeoHead',
    setup() {
        const page = usePage();

        return () => {
            const seo = page.props.seo as Seo | undefined;

            if (!seo) {
                return null;
            }

            const siteName = page.props.site.name;
            const fullTitle = seo.title
                ? `${seo.title} | ${siteName}`
                : siteName;
            const meta = (
                key: string,
                attributes: Record<string, string>,
            ): VNode => h('meta', { 'head-key': key, ...attributes });

            const tags: VNode[] = [
                meta('description', {
                    name: 'description',
                    content: seo.description,
                }),
                meta('robots', { name: 'robots', content: seo.robots }),
                h('link', {
                    'head-key': 'canonical',
                    rel: 'canonical',
                    href: seo.url,
                }),
                meta('og:site_name', {
                    property: 'og:site_name',
                    content: siteName,
                }),
                meta('og:type', { property: 'og:type', content: seo.type }),
                meta('og:title', { property: 'og:title', content: fullTitle }),
                meta('og:description', {
                    property: 'og:description',
                    content: seo.description,
                }),
                meta('og:url', { property: 'og:url', content: seo.url }),
                meta('twitter:card', {
                    name: 'twitter:card',
                    content: seo.image ? 'summary_large_image' : 'summary',
                }),
                meta('twitter:title', {
                    name: 'twitter:title',
                    content: fullTitle,
                }),
                meta('twitter:description', {
                    name: 'twitter:description',
                    content: seo.description,
                }),
            ];

            if (seo.image) {
                tags.push(
                    meta('og:image', {
                        property: 'og:image',
                        content: seo.image,
                    }),
                    meta('twitter:image', {
                        name: 'twitter:image',
                        content: seo.image,
                    }),
                );
            }

            seo.jsonLd.forEach((schema, index) => {
                tags.push(
                    h(
                        'script',
                        {
                            'head-key': `jsonld-${index}`,
                            type: 'application/ld+json',
                        },
                        JSON.stringify(schema).replace(/</g, '\\u003c'),
                    ),
                );
            });

            return h(Head, { title: seo.title ?? siteName }, () => tags);
        };
    },
});
