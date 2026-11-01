import { router } from '@inertiajs/react';
import { index } from '@/routes/reports';
import type { ReportFilters } from '@/types';

/**
 * Reload the report with new filters, keeping them in the URL so a
 * filtered report can be bookmarked. Empty filters are left out.
 */
export function visitReport(filters: ReportFilters): void {
    const query = Object.fromEntries(
        Object.entries(filters).filter(
            ([, value]) => value !== null && value !== '',
        ),
    );

    router.get(index.url(), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}
