/** The sizes an item is made in. Every size costs the same per piece. */
export const SIZES = [
    { key: 'small', label: 'Small', short: 'S' },
    { key: 'medium', label: 'Medium', short: 'M' },
    { key: 'large', label: 'Large', short: 'L' },
    { key: 'xl', label: 'XL', short: 'XL' },
] as const;

export type SizeKey = (typeof SIZES)[number]['key'];

/** Form field names for each size's quantity, e.g. "quantity_small". */
export type SizeQuantityField = `quantity_${SizeKey}`;

export type SizeQuantities = Record<SizeQuantityField, number | ''>;

/** Total pieces across all sizes. */
export function totalPieces(quantities: SizeQuantities): number {
    return SIZES.reduce(
        (sum, size) => sum + Number(quantities[`quantity_${size.key}`] || 0),
        0,
    );
}
