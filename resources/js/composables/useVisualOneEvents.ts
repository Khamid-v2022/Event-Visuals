import { useDebounceFn } from '@vueuse/core';
import { reactive, ref } from 'vue';
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

export function useVisualOneEvents() {
    const filters = reactive<VisualEventFilters>(emptyFilters());
    const events = ref<VisualEvent[]>([]);
    const page = ref(0);
    const lastPage = ref(1);
    const total = ref(0);
    const loading = ref(false);
    const hasLoadedOnce = ref(false);
    const suggestions = ref<LocationSuggestion[]>([]);
    const suggestionLoading = ref(false);
    const pageCache = new Map<string, VisualEventPage>();

    function cacheKey(targetPage: number) {
        return JSON.stringify({
            page: targetPage,
            date_from: filters.date_from,
            date_to: filters.date_to,
            location: filters.location.trim(),
            q: filters.q.trim(),
            type: filters.type,
            status: filters.status,
        });
    }

    function buildParams(targetPage: number) {
        const params = new URLSearchParams({ page: String(targetPage) });
        if (filters.date_from) params.set('date_from', filters.date_from);
        if (filters.date_to) params.set('date_to', filters.date_to);
        if (filters.location.trim()) params.set('location', filters.location.trim());
        if (filters.q.trim()) params.set('q', filters.q.trim());
        if (filters.type) params.set('type', filters.type);
        if (filters.status) params.set('status', filters.status);

        return params;
    }

    async function fetchPage(targetPage: number) {
        const key = cacheKey(targetPage);
        const cached = pageCache.get(key);

        if (cached) {
            events.value = cached.data;
            page.value = cached.current_page;
            lastPage.value = cached.last_page;
            total.value = cached.total;
            hasLoadedOnce.value = true;
            prefetchPage(targetPage + 1);
            prefetchPage(targetPage - 1);

            return;
        }

        loading.value = true;

        const params = buildParams(targetPage);

        try {
            const response = await fetch(`/events-visual-1/data?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                throw new Error(`Request failed (${response.status})`);
            }

            const payload = (await response.json()) as VisualEventPage;
            pageCache.set(key, payload);
            events.value = payload.data;
            page.value = payload.current_page;
            lastPage.value = payload.last_page;
            total.value = payload.total;
            hasLoadedOnce.value = true;
            prefetchPage(targetPage + 1);
            prefetchPage(targetPage - 1);
        } finally {
            loading.value = false;
        }
    }

    function applyFilters() {
        pageCache.clear();
        fetchPage(1);
    }

    function goToPage(nextPage: number) {
        if (loading.value || lastPage.value < 1) {
            return;
        }

        const target = Math.min(Math.max(1, Math.trunc(nextPage)), lastPage.value);

        if (target === page.value) {
            return;
        }

        fetchPage(target);
    }

    async function prefetchPage(targetPage: number) {
        if (targetPage < 1) {
            return;
        }

        const key = cacheKey(targetPage);
        if (pageCache.has(key)) {
            return;
        }

        const params = buildParams(targetPage);

        try {
            const response = await fetch(`/events-visual-1/data?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                return;
            }

            const payload = (await response.json()) as VisualEventPage;
            pageCache.set(key, payload);
        } catch {
            // Prefetch is opportunistic; ignore failures and let real navigation retry.
        }
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
        clearSuggestions();
        pageCache.clear();
        fetchPage(1);
    }

    return {
        filters,
        events,
        page,
        lastPage,
        total,
        loading,
        hasLoadedOnce,
        suggestions,
        suggestionLoading,
        applyFilters,
        goToPage,
        fetchPage,
        resetFilters,
        fetchLocationSuggestions,
        debouncedFetchLocationSuggestions,
        clearSuggestions,
    };
}
