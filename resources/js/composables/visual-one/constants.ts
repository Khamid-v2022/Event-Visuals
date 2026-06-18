/** First request loads a full grid; scroll requests load smaller aligned chunks. */
export const VISUAL_ONE_INITIAL_PER_PAGE = 48;
export const VISUAL_ONE_LOAD_MORE_PER_PAGE = 24;

export function perPageForVisualPage(page: number): number {
    return page === 1 ? VISUAL_ONE_INITIAL_PER_PAGE : VISUAL_ONE_LOAD_MORE_PER_PAGE;
}

/**
 * Offset-based cursor for variable page sizes.
 * Page 2 must start at index 48, not Laravel's default (page - 1) * per_page.
 */
export function pageOffset(targetPage: number): number {
    if (targetPage <= 1) {
        return 0;
    }

    return VISUAL_ONE_INITIAL_PER_PAGE + (targetPage - 2) * VISUAL_ONE_LOAD_MORE_PER_PAGE;
}

export const VISUAL_ONE_SORT_OPTIONS = [
    { value: 'recent', label: 'Most Recent' },
    { value: 'price_asc', label: 'Price ASC' },
    { value: 'price_desc', label: 'Price DESC' },
] as const;

export type VisualEventSort = (typeof VISUAL_ONE_SORT_OPTIONS)[number]['value'];

export const EVENT_GRID_CLASS =
    'grid grid-cols-1 gap-5 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 2xl:grid-cols-4';
