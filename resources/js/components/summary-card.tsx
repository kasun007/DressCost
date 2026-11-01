import { Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type Props = {
    href: NonNullable<InertiaLinkProps['href']>;
    name: string;
    description?: string | null;
    stats: { label: string; value: string }[];
};

/**
 * A clickable summary card, used for both workspaces and projects.
 */
export default function SummaryCard({ href, name, description, stats }: Props) {
    return (
        <Link
            href={href}
            className="rounded-xl transition-shadow hover:shadow-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
        >
            <Card className="h-full">
                <CardHeader>
                    <CardTitle>{name}</CardTitle>
                    {description && (
                        <CardDescription className="line-clamp-2">
                            {description}
                        </CardDescription>
                    )}
                </CardHeader>
                <CardContent className="mt-auto">
                    <dl
                        className="grid gap-2 text-sm"
                        style={{
                            gridTemplateColumns: `repeat(${stats.length}, minmax(0, 1fr))`,
                        }}
                    >
                        {stats.map((stat) => (
                            <div key={stat.label}>
                                <dt className="text-muted-foreground">
                                    {stat.label}
                                </dt>
                                <dd className="font-medium tabular-nums">
                                    {stat.value}
                                </dd>
                            </div>
                        ))}
                    </dl>
                </CardContent>
            </Card>
        </Link>
    );
}
