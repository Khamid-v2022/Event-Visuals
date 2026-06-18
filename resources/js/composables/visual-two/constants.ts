export const VISUAL_TWO_SORT_OPTIONS = [
    { value: 'recent', label: 'Date (earliest)' },
    { value: 'price_asc', label: 'Price ASC' },
    { value: 'price_desc', label: 'Price DESC' },
] as const;

export type VisualEventSort = (typeof VISUAL_TWO_SORT_OPTIONS)[number]['value'];

export const MAX_VISIBLE_CHIPS = 3;
