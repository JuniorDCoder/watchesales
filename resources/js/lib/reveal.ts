import type { Directive } from 'vue';

let observer: IntersectionObserver | null = null;

function revealObserver(): IntersectionObserver {
    observer ??= new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    (entry.target as HTMLElement).dataset.reveal = 'shown';
                    observer?.unobserve(entry.target);
                }
            }
        },
        { rootMargin: '0px 0px -8% 0px', threshold: 0.08 },
    );

    return observer;
}

/**
 * Fade and lift an element into view as it scrolls into the viewport.
 * Elements already on screen are left untouched so nothing flickers on load.
 * The optional value staggers the animation in milliseconds.
 */
export const vReveal: Directive<HTMLElement, number | undefined> = {
    mounted(el, binding) {
        if (!('IntersectionObserver' in window)) {
            return;
        }

        if (el.getBoundingClientRect().top < window.innerHeight * 0.92) {
            return;
        }

        if (binding.value) {
            el.style.setProperty('--reveal-delay', `${binding.value}ms`);
        }

        el.dataset.reveal = 'pending';
        revealObserver().observe(el);
    },
    unmounted(el) {
        observer?.unobserve(el);
    },
};
