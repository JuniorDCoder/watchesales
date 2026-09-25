export const DEFAULT_CURRENCY = 'USD';

const moneyFormatters = new Map<string, Intl.NumberFormat>();

/**
 * A cached formatter for the currency, falling back to US dollars for an unknown code.
 */
function moneyFormatter(currency: string): Intl.NumberFormat {
    if (!moneyFormatters.has(currency)) {
        let formatter: Intl.NumberFormat;

        try {
            formatter = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency,
                maximumFractionDigits: 0,
            });
        } catch {
            formatter = moneyFormatter(DEFAULT_CURRENCY);
        }

        moneyFormatters.set(currency, formatter);
    }

    return moneyFormatters.get(currency)!;
}

/**
 * The symbol shown for the currency, such as $ or €.
 */
export function currencySymbol(currency: string): string {
    return (
        moneyFormatter(currency)
            .formatToParts(0)
            .find((part) => part.type === 'currency')?.value ?? currency
    );
}

/**
 * A readable name for the currency, such as "Euro".
 */
export function currencyName(currency: string): string {
    try {
        return (
            new Intl.DisplayNames(['en'], { type: 'currency' }).of(currency) ??
            currency
        );
    } catch {
        return currency;
    }
}

/**
 * Format a price in the store currency. A missing price means the watch is priced on request.
 */
export function formatPrice(
    price: number | null | undefined,
    currency: string,
): string {
    if (price === null || price === undefined) {
        return 'Price on request';
    }

    return moneyFormatter(currency).format(price);
}

export function formatNumber(value: number): string {
    return new Intl.NumberFormat('en-US').format(value);
}

export function formatCompactNumber(value: number): string {
    return new Intl.NumberFormat('en-US', {
        notation: 'compact',
        maximumFractionDigits: 1,
    }).format(value);
}

const relativeTimeUnits: [Intl.RelativeTimeFormatUnit, number][] = [
    ['year', 31536000],
    ['month', 2592000],
    ['week', 604800],
    ['day', 86400],
    ['hour', 3600],
    ['minute', 60],
];

export function formatRelativeTime(
    isoDate: string,
    now: Date = new Date(),
): string {
    const seconds = Math.round(
        (new Date(isoDate).getTime() - now.getTime()) / 1000,
    );
    const formatter = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });

    for (const [unit, unitSeconds] of relativeTimeUnits) {
        if (Math.abs(seconds) >= unitSeconds) {
            return formatter.format(Math.round(seconds / unitSeconds), unit);
        }
    }

    return 'just now';
}
