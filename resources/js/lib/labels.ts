/**
 * Turns an enum value such as "trousers" into a label such as "Trousers".
 */
export function toLabel(value: string): string {
    return value.charAt(0).toUpperCase() + value.slice(1);
}
