type ChatwootConfig = {
    baseUrl: string;
    websiteToken: string;
};

type ChatwootWidget = {
    toggle: (state?: 'open' | 'close') => void;
    setCustomAttributes: (attributes: Record<string, string | number>) => void;
};

declare global {
    interface Window {
        chatwootSettings?: Record<string, unknown>;
        chatwootSDK?: { run: (config: ChatwootConfig) => void };
        $chatwoot?: ChatwootWidget;
    }
}

let loading: Promise<ChatwootWidget> | null = null;

/**
 * Load the Chatwoot widget once. The default bubble is hidden because the
 * storefront renders its own launcher that matches the site design.
 */
export function loadChatwoot(config: ChatwootConfig): Promise<ChatwootWidget> {
    if (window.$chatwoot) {
        return Promise.resolve(window.$chatwoot);
    }

    loading ??= new Promise<ChatwootWidget>((resolve, reject) => {
        window.chatwootSettings = {
            hideMessageBubble: true,
            position: 'right',
            type: 'standard',
            darkMode: 'auto',
        };

        window.addEventListener(
            'chatwoot:ready',
            () =>
                window.$chatwoot
                    ? resolve(window.$chatwoot)
                    : reject(new Error('Chatwoot is unavailable')),
            { once: true },
        );

        const script = document.createElement('script');
        script.src = `${config.baseUrl}/packs/js/sdk.js`;
        script.async = true;
        script.onload = () => window.chatwootSDK?.run(config);
        script.onerror = () => {
            loading = null;
            reject(new Error('Chatwoot failed to load'));
        };

        document.body.appendChild(script);
    });

    return loading;
}

export async function openChatwoot(
    config: ChatwootConfig,
    attributes?: Record<string, string | number>,
): Promise<void> {
    const widget = await loadChatwoot(config);

    if (attributes) {
        widget.setCustomAttributes(attributes);
    }

    widget.toggle('open');
}
