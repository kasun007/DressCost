import type { ChartConfig } from '@/components/ui/chart';

const SLOT_COUNT = 8;
const OTHER_KEY = 'other';

export type Series = {
    key: string;
    label: string;
    color: string;
};

export type SeriesSet = {
    series: Series[];
    /** The chart key a name is drawn under (several names may share "Other"). */
    keyOf: (name: string) => string;
    config: ChartConfig;
};

/**
 * Assigns the validated chart colour slots to names in their fixed order, so
 * a colour always follows the same project or costing type, whatever is
 * filtered. Up to 8 names each get a slot; past that, the first 7 keep
 * theirs and the rest fold into a grey "Other" series.
 */
export function buildSeries(names: string[]): SeriesSet {
    const fold = names.length > SLOT_COUNT;
    const ownSlots = fold ? SLOT_COUNT - 1 : SLOT_COUNT;
    const keys = new Map<string, string>();

    const series: Series[] = names.slice(0, ownSlots).map((name, index) => {
        const key = `s${index}`;
        keys.set(name, key);

        return { key, label: name, color: `var(--chart-${index + 1})` };
    });

    if (fold) {
        series.push({
            key: OTHER_KEY,
            label: 'Other',
            color: 'var(--muted-foreground)',
        });
    }

    const config: ChartConfig = Object.fromEntries(
        series.map((s) => [s.key, { label: s.label, color: s.color }]),
    );

    return {
        series,
        keyOf: (name) => keys.get(name) ?? OTHER_KEY,
        config,
    };
}
