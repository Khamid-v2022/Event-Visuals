import type { VisualEventFilters } from '@/types/event';
import { VISUAL_ONE_SORT_OPTIONS, type VisualEventSort } from '@/composables/visual-one/constants';

export const DEFAULT_STATUS = 'all';
export const DEFAULT_SORT: VisualEventSort = 'recent';

const VALID_SORTS = new Set(VISUAL_ONE_SORT_OPTIONS.map((option) => option.value));

export function parseUrlState(search: string): {
    filters: VisualEventFilters;
    sort: VisualEventSort;
} {
    const params = new URLSearchParams(search);
    const sortParam = params.get('sort');

    return {
        filters: {
            date_from: params.get('date_from') ?? '',
            date_to: params.get('date_to') ?? '',
            location: params.get('location') ?? '',
            q: params.get('q') ?? '',
            type: params.get('type') ?? '',
            status: params.get('status') ?? DEFAULT_STATUS,
            interested_only: params.get('interested_only') === '1',
        },
        sort: sortParam && VALID_SORTS.has(sortParam as VisualEventSort)
            ? (sortParam as VisualEventSort)
            : DEFAULT_SORT,
    };
}

export function buildUrlQuery(filters: VisualEventFilters, sort: VisualEventSort): string {
    const params = new URLSearchParams();

    if (filters.q.trim()) params.set('q', filters.q.trim());
    if (filters.location.trim()) params.set('location', filters.location.trim());
    if (filters.date_from) params.set('date_from', filters.date_from);
    if (filters.date_to) params.set('date_to', filters.date_to);
    if (filters.type) params.set('type', filters.type);
    if (filters.status && filters.status !== DEFAULT_STATUS) {
        params.set('status', filters.status);
    }
    if (filters.interested_only) params.set('interested_only', '1');
    if (sort !== DEFAULT_SORT) params.set('sort', sort);

    return params.toString();
}

export function syncUrlState(filters: VisualEventFilters, sort: VisualEventSort): void {
    const query = buildUrlQuery(filters, sort);
    const url = query ? `${window.location.pathname}?${query}` : window.location.pathname;
    window.history.replaceState(window.history.state, '', url);
}
