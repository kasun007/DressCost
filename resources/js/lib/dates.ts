import { LOCALE } from '@/lib/locale';

const dayFormatter = new Intl.DateTimeFormat(LOCALE, {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

const monthFormatter = new Intl.DateTimeFormat(LOCALE, {
    month: 'short',
    year: 'numeric',
});

/** "2026-03-04" -> "4 Mar 2026". */
export function formatDate(isoDate: string): string {
    return dayFormatter.format(new Date(`${isoDate}T00:00:00`));
}

/** "2026-03" -> "Mar 2026". */
export function formatMonth(month: string): string {
    return monthFormatter.format(new Date(`${month}-01T00:00:00`));
}

/** Today's date as "YYYY-MM-DD" in the reader's time zone. */
export function today(): string {
    const now = new Date();
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}
