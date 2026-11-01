import { Head } from '@inertiajs/react';
import { Eye } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import CostSummaryCards from '@/components/cost-summary-cards';
import ItemCostPanel from '@/components/item-cost-panel';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Badge } from '@/components/ui/badge';
import { useMoney } from '@/hooks/use-money';
import { toLabel } from '@/lib/labels';
import type { ItemDetail } from '@/types';

type Props = {
    project: { name: string; description: string | null };
    workspace: string;
    items: ItemDetail[];
    totalCost: number;
    totalPieces: number;
};

/**
 * The public, read-only costing of a shared project. Opened from a share
 * link without logging in; nothing on it can be changed.
 */
export default function SharedProject({
    project,
    workspace,
    items,
    totalCost,
    totalPieces,
}: Props) {
    const money = useMoney();

    return (
        <>
            <Head title={`${project.name} (shared costing)`}>
                <meta name="robots" content="noindex, nofollow" />
            </Head>

            <div className="min-h-svh bg-background">
                <header className="bg-sidebar text-sidebar-foreground">
                    <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 md:px-6">
                        <div className="flex items-center gap-2">
                            <span className="flex size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                                <AppLogoIcon className="size-5 fill-current" />
                            </span>
                            <span className="font-semibold">DressCost</span>
                        </div>
                        <span className="flex items-center gap-1.5 text-sm">
                            <Eye className="size-4" aria-hidden />
                            Shared costing, view only
                        </span>
                    </div>
                </header>

                <main className="mx-auto max-w-6xl space-y-6 px-4 py-6 md:px-6">
                    <div>
                        <p className="text-sm text-muted-foreground">
                            {workspace}
                        </p>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {project.name}
                        </h1>
                        {project.description && (
                            <p className="mt-1 text-muted-foreground">
                                {project.description}
                            </p>
                        )}
                    </div>

                    <CostSummaryCards
                        cost={{
                            total_cost: totalCost,
                            selling_price: null,
                            profit: null,
                        }}
                    />

                    <section className="space-y-3">
                        <div>
                            <h2 className="text-lg font-semibold">
                                Items ({totalPieces} pieces)
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Select an item to see its full cost breakdown.
                            </p>
                        </div>

                        {items.length === 0 ? (
                            <p className="rounded-xl border border-dashed bg-card p-8 text-center text-sm text-muted-foreground">
                                This project has no items yet.
                            </p>
                        ) : (
                            <Accordion
                                type="multiple"
                                className="overflow-hidden rounded-xl border bg-card"
                            >
                                {items.map((item) => (
                                    <AccordionItem
                                        key={item.id}
                                        value={String(item.id)}
                                        className="data-[state=open]:bg-muted/40"
                                    >
                                        <AccordionTrigger className="items-center px-4 hover:no-underline md:px-5">
                                            <div className="flex min-w-0 flex-1 flex-wrap items-center gap-x-3 gap-y-1">
                                                <span className="font-semibold">
                                                    {item.name}
                                                </span>
                                                <Badge variant="secondary">
                                                    {toLabel(item.type)}
                                                </Badge>
                                            </div>
                                            <div className="text-right">
                                                <p className="text-xs font-normal text-muted-foreground">
                                                    Pieces
                                                </p>
                                                <p className="tabular-nums">
                                                    {item.quantity}
                                                </p>
                                            </div>
                                            <div className="hidden text-right sm:block">
                                                <p className="text-xs font-normal text-muted-foreground">
                                                    Total cost
                                                </p>
                                                <p className="tabular-nums">
                                                    {money(
                                                        item.all_pieces
                                                            .total_cost,
                                                    )}
                                                </p>
                                            </div>
                                            <div className="w-32 text-right">
                                                <p className="text-xs font-normal text-muted-foreground">
                                                    Selling value
                                                </p>
                                                <p className="font-semibold tabular-nums">
                                                    {money(
                                                        item.all_pieces
                                                            .selling_price,
                                                    )}
                                                </p>
                                            </div>
                                        </AccordionTrigger>
                                        <AccordionContent className="px-4 md:px-5">
                                            <ItemCostPanel item={item} />
                                        </AccordionContent>
                                    </AccordionItem>
                                ))}
                            </Accordion>
                        )}
                    </section>
                </main>
            </div>
        </>
    );
}
