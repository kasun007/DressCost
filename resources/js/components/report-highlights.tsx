import { FolderTree, Layers, Package } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { CategoryTotal, ReportNode, ReportSummary } from '@/types';

type Props = {
    summary: ReportSummary;
    categories: CategoryTotal[];
    nodes: ReportNode[];
};

type Fact = {
    icon: LucideIcon;
    label: string;
    value: string;
    detail: string;
};

/**
 * The few facts that answer "what stands out?" for the current view.
 */
export default function ReportHighlights({
    summary,
    categories,
    nodes,
}: Props) {
    const total = categories.reduce((sum, c) => sum + c.amount, 0);
    const percent = (amount: number) =>
        total > 0 ? `${Math.round((amount / total) * 100)}%` : '0%';

    const facts: Fact[] = [
        {
            icon: Package,
            label: 'Items costed',
            value: String(summary.items_count),
            detail: `in ${summary.projects_count} project${summary.projects_count === 1 ? '' : 's'}`,
        },
    ];

    const biggestType = categories[0];

    if (biggestType) {
        facts.push({
            icon: Layers,
            label: 'Biggest costing type',
            value: biggestType.name,
            detail: `${percent(biggestType.amount)} of costs`,
        });
    }

    const costliest = [...nodes].sort((a, b) => b.total_cost - a.total_cost)[0];

    if (nodes.length > 1 && costliest) {
        facts.push({
            icon: FolderTree,
            label: 'Most costly project',
            value: costliest.name,
            detail: `${percent(costliest.total_cost)} of costs`,
        });
    }

    return (
        <dl className="grid gap-px overflow-hidden rounded-xl border bg-border sm:grid-cols-3">
            {facts.map((fact) => (
                <div key={fact.label} className="flex gap-3 bg-card p-4">
                    <fact.icon
                        className="mt-0.5 size-5 shrink-0 text-primary"
                        aria-hidden
                    />
                    <div className="min-w-0">
                        <dt className="text-sm text-muted-foreground">
                            {fact.label}
                        </dt>
                        <dd className="truncate text-lg font-semibold">
                            {fact.value}
                        </dd>
                        <dd className="text-sm text-muted-foreground">
                            {fact.detail}
                        </dd>
                    </div>
                </div>
            ))}
        </dl>
    );
}
