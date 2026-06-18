import { useDebounceFn } from '@vueuse/core';
import { reactive, ref } from 'vue';
import { pageOffset, perPageForVisualPage, type VisualEventSort } from '@/composables/visual-one/constants';
import type {
    LocationSuggestion,
    VisualEvent,
    VisualEventFilters,
    VisualEventPage,
} from '@/types/event';

const emptyFilters = (): VisualEventFilters => ({
    date_from: '',
    date_to: '',
    location: '',
    q: '',
    type: '',
    status: '',
});

type FetchMode = 'replace' | 'append';

export function useVisualOneEvents() {
    const filters = reactive<VisualEventFilters>(emptyFilters());
    const sort = ref<VisualEventSort>('recent');
    const events = ref<VisualEvent[]>([]);
    const page = ref(0);
    const hasMore = ref(false);
    const total = ref(0);
    const loading = ref(false);
    const loadingMore = ref(false);
    const hasLoadedOnce = ref(false);
    const suggestions = ref<LocationSuggestion[]>([]);
    const suggestionLoading = ref(false);
    const pageCache = new Map<string, VisualEventPage>();

    function cacheKey(targetPage: number) {
        return JSON.stringify({
            page: targetPage,
            per_page: perPageForVisualPage(targetPage),
            offset: pageOffset(targetPage),
            sort: sort.value,
            date_from: filters.date_from,
            date_to: filters.date_to,
            location: filters.location.trim(),
            q: filters.q.trim(),
            type: filters.type,
            status: filters.status,
        });
    }

    function buildParams(targetPage: number) {
        const params = new URLSearchParams({
            page: String(targetPage),
            per_page: String(perPageForVisualPage(targetPage)),
            offset: String(pageOffset(targetPage)),
            sort: sort.value,
        });
        if (filters.date_from) params.set('date_from', filters.date_from);
        if (filters.date_to) params.set('date_to', filters.date_to);
        if (filters.location.trim()) params.set('location', filters.location.trim());
        if (filters.q.trim()) params.set('q', filters.q.trim());
        if (filters.type) params.set('type', filters.type);
        if (filters.status) params.set('status', filters.status);

        return params;
    }

    function applyPayload(payload: VisualEventPage, append: boolean) {
        events.value = append ? [...events.value, ...payload.data] : payload.data;
        page.value = payload.current_page;
        hasMore.value = payload.has_more ?? false;

        if (payload.total !== null && payload.total !== undefined) {
            total.value = payload.total;
        }

        hasLoadedOnce.value = true;
    }

    function clearPageCache() {
        pageCache.clear();
    }

    async function requestPage(targetPage: number): Promise<VisualEventPage> {
        const params = buildParams(targetPage);
        const response = await fetch(`/events-visual-1/data?${params.toString()}`, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error(`Request failed (${response.status})`);
        }

        const payload = (await response.json()) as VisualEventPage;
        pageCache.set(cacheKey(targetPage), payload);

        return payload;
    }

    /** Warm the next page in the background so scroll feels instant. */
    function prefetchNextPage(fromPage: number) {
        if (!hasMore.value) {
            return;
        }

        const nextPage = fromPage + 1;
        if (pageCache.has(cacheKey(nextPage))) {
            return;
        }

        requestPage(nextPage).catch(() => {
            // Prefetch is best-effort; scroll will retry on demand.
        });
    }

    async function fetchPage(targetPage: number, mode: FetchMode = 'replace') {
        const append = mode === 'append';

        if (append && (loadingMore.value || loading.value || !hasMore.value)) {
            return;
        }

        const cached = pageCache.get(cacheKey(targetPage));
        if (cached) {
            applyPayload(cached, append);
            prefetchNextPage(targetPage);

            return;
        }

        if (append) {
            loadingMore.value = true;
        } else {
            loading.value = true;
        }

        try {
            const payload = await requestPage(targetPage);
            applyPayload(payload, append);
            prefetchNextPage(targetPage);
        } finally {
            loading.value = false;
            loadingMore.value = false;
        }
    }

    function applyFilters() {
        clearPageCache();
        fetchPage(1, 'replace');
    }

    function applySort() {
        clearPageCache();
        fetchPage(1, 'replace');
    }

    function loadMore() {
        if (!hasMore.value || loading.value || loadingMore.value) {
            return;
        }

        fetchPage(page.value + 1, 'append');
    }

    async function fetchLocationSuggestions() {
        const query = filters.location.trim();
        if (query.length < 2) {
            clearSuggestions();

            return;
        }

        suggestionLoading.value = true;

        try {
            const response = await fetch(`/events-visual-1/locations?${new URLSearchParams({ query }).toString()}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                throw new Error(`Request failed (${response.status})`);
            }

            const payload = (await response.json()) as { data: LocationSuggestion[] };
            suggestions.value = payload.data;
        } finally {
            suggestionLoading.value = false;
        }
    }

    const debouncedFetchLocationSuggestions = useDebounceFn(fetchLocationSuggestions, 250);

    function clearSuggestions() {
        suggestions.value = [];
    }

    function resetFilters() {
        filters.date_from = '';
        filters.date_to = '';
        filters.location = '';
        filters.q = '';
        filters.type = '';
        filters.status = '';
        sort.value = 'recent';
        clearSuggestions();
        clearPageCache();
        fetchPage(1, 'replace');
    }

    return {
        filters,
        sort,
        events,
        page,
        hasMore,
        total,
        loading,
        loadingMore,
        hasLoadedOnce,
        suggestions,
        suggestionLoading,
        applyFilters,
        applySort,
        loadMore,
        fetchPage,
        resetFilters,
        debouncedFetchLocationSuggestions,
        clearSuggestions,
    };
}
