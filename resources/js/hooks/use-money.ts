import { usePage } from '@inertiajs/react';
import { LOCALE } from '@/lib/locale';

/**
 * Returns a formatter for amounts in the app's configured currency.
 * `compact` gives short forms such as "LKR 12K" for chart axes.
 */
export function useMoney({ compact = false } = {}): (amount: number) => string {
    const { currency } = usePage().props;
    const formatter = new Intl.NumberFormat(LOCALE, {
        style: 'currency',
        currency,
        ...(compact ? { notation: 'compact', maximumFractionDigits: 1 } : {}),
    });

    return (amount) => formatter.format(amount);
}
