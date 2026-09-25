import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import StoreLayout from '@/layouts/StoreLayout.vue';
import { initializeFlashToast } from '@/lib/flashToast';
import { vReveal } from '@/lib/reveal';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    title: (title, page) => {
        const siteName =
            (page?.props as { site?: { name?: string } } | undefined)?.site
                ?.name ?? appName;

        if (!title || title === siteName) {
            return siteName;
        }

        return `${title} | ${siteName}`;
    },
    layout: (name) => {
        switch (true) {
            case name.startsWith('store/'):
                return StoreLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    withApp: (app) => {
        app.directive('focus', {
            mounted: (el: HTMLElement, shouldFocus) => {
                if (shouldFocus.value !== false) {
                    el.focus();
                }
            },
        });

        app.directive('reveal', vReveal);
    },
    progress: {
        color: '#b08d57',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
