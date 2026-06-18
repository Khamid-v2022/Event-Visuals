import type { VisualEventFilters } from '@/types/event';
import { currentMonthParam } from '@/lib/calendar';
import { VISUAL_TWO_SORT_OPTIONS, type VisualEventSort } from '@/composables/visual-two/constants';

export const DEFAULT_STATUS = 'all';
export const DEFAULT_SORT: VisualEventSort = 'recent';
export const DEFAULT_TAB = 'all' as const;

export type VisualEventTab = 'all' | 'interested' | 'booked';

const VALID_SORTS = new Set(VISUAL_TWO_SORT_OPTIONS.map((option) => option.value));
const VALID_TABS = new Set<VisualEventTab>(['all', 'interested', 'booked']);
const MONTH_PATTERN = /^\d{4}-\d{2}$/;

export function parseUrlState(search: string): {
    filters: VisualEventFilters;
    sort: VisualEventSort;
    tab: VisualEventTab;
    month: string;
} {
    const params = new URLSearchParams(search);
    const sortParam = params.get('sort');
    const tabParam = params.get('tab');
    const monthParam = params.get('month');

    return {
        filters: {
            date_from: params.get('date_from') ?? '',
            date_to: params.get('date_to') ?? '',
            location: params.get('location') ?? '',
            q: params.get('q') ?? '',
            type: params.get('type') ?? '',
            status: params.get('status') ?? DEFAULT_STATUS,
        },
        tab: tabParam && VALID_TABS.has(tabParam as VisualEventTab)
            ? (tabParam as VisualEventTab)
            : DEFAULT_TAB,
        sort: sortParam && VALID_SORTS.has(sortParam as VisualEventSort)
            ? (sortParam as VisualEventSort)
            : DEFAULT_SORT,
        month: monthParam && MONTH_PATTERN.test(monthParam) ? monthParam : currentMonthParam(),
    };
}

export function buildUrlQuery(
    filters: VisualEventFilters,
    sort: VisualEventSort,
    tab: VisualEventTab,
    month: string,
): string {
    const params = new URLSearchParams();

    if (filters.q.trim()) params.set('q', filters.q.trim());
    if (filters.location.trim()) params.set('location', filters.location.trim());
    if (filters.date_from) params.set('date_from', filters.date_from);
    if (filters.date_to) params.set('date_to', filters.date_to);
    if (filters.type) params.set('type', filters.type);
    if (filters.status && filters.status !== DEFAULT_STATUS) {
        params.set('status', filters.status);
    }
    if (tab !== DEFAULT_TAB) params.set('tab', tab);
    if (sort !== DEFAULT_SORT) params.set('sort', sort);
    if (month !== currentMonthParam()) params.set('month', month);

    return params.toString();
}

export function syncUrlState(
    filters: VisualEventFilters,
    sort: VisualEventSort,
    tab: VisualEventTab,
    month: string,
): void {
    const query = buildUrlQuery(filters, sort, tab, month);
    const url = query ? `${window.location.pathname}?${query}` : window.location.pathname;
    window.history.replaceState(window.history.state, '', url);
}
