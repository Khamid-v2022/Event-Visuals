import { useDebounceFn } from '@vueuse/core';
import { router } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import { currentMonthParam, toDateKey } from '@/lib/calendar';
import { sortDayEvents } from '@/lib/calendarEvents';
import {
    DEFAULT_SORT,
    DEFAULT_STATUS,
    DEFAULT_TAB,
    parseUrlState,
    syncUrlState,
    type VisualEventTab,
} from '@/composables/visual-two/urlState';
import type { VisualEventSort } from '@/composables/visual-two/constants';
import { fetchJson } from '@/lib/fetchJson';
import type {
    LocationSuggestion,
    VisualEvent,
    VisualEventFilters,
} from '@/types/event';

const defaultFilters = (): VisualEventFilters => ({
    date_from: '',
    date_to: '',
    location: '',
    q: '',
    type: '',
    status: DEFAULT_STATUS,
});

export interface VisualCalendarPage {
    data: VisualEvent[];
    total: number;
}

export function useVisualTwoEvents() {
    const initialState = parseUrlState(
        typeof window !== 'undefined' ? window.location.search : '',
    );

    const filters = reactive<VisualEventFilters>({ ...defaultFilters(), ...initialState.filters });
    const sort = ref<VisualEventSort>(initialState.sort);
    const tab = ref<VisualEventTab>(initialState.tab);
    const month = ref(initialState.month || currentMonthParam());
    const events = ref<VisualEvent[]>([]);
    const total = ref(0);
    const loading = ref(false);
    const hasLoadedOnce = ref(false);
    const selectedDate = ref<string | null>(null);
    /** Panel list order — re-sorted only when the day changes or month data reloads. */
    const selectedDayEvents = ref<VisualEvent[]>([]);
    const suggestions = ref<LocationSuggestion[]>([]);
    const suggestionLoading = ref(false);
    const monthCache = new Map<string, VisualCalendarPage>();

    const eventsByDate = computed(() => {
        const map = new Map<string, VisualEvent[]>();

        for (const event of events.value) {
            const key = toDateKey(new Date(event.schedule.starts_at * 1000));
            const bucket = map.get(key);

            if (bucket) {
                bucket.push(event);
            } else {
                map.set(key, [event]);
            }
        }

        for (const [key, dayEvents] of map) {
            map.set(key, sortDayEvents(dayEvents));
        }

        return map;
    });

    function sortedEventsForDate(dateKey: string): VisualEvent[] {
        const bucket: VisualEvent[] = [];

        for (const event of events.value) {
            if (toDateKey(new Date(event.schedule.starts_at * 1000)) === dateKey) {
                bucket.push(event);
            }
        }

        return sortDayEvents(bucket);
    }

    function refreshSelectedDayEvents() {
        if (!selectedDate.value) {
            selectedDayEvents.value = [];

            return;
        }

        selectedDayEvents.value = sortedEventsForDate(selectedDate.value);
    }

    watch(selectedDate, () => {
        refreshSelectedDayEvents();
    });

    function initFromUrl() {
        const state = parseUrlState(window.location.search);
        Object.assign(filters, state.filters);
        sort.value = state.sort;
        tab.value = state.tab;
        month.value = state.month || currentMonthParam();
    }

    function syncUrl() {
        syncUrlState(filters, sort.value, tab.value, month.value);
    }

    function cacheKey() {
        return JSON.stringify({
            month: month.value,
            sort: sort.value,
            tab: tab.value,
            date_from: filters.date_from,
            date_to: filters.date_to,
            location: filters.location.trim(),
            q: filters.q.trim(),
            type: filters.type,
            status: filters.status,
        });
    }

    function buildParams() {
        const params = new URLSearchParams({
            month: month.value,
            sort: sort.value,
        });

        if (filters.date_from) params.set('date_from', filters.date_from);
        if (filters.date_to) params.set('date_to', filters.date_to);
        if (filters.location.trim()) params.set('location', filters.location.trim());
        if (filters.q.trim()) params.set('q', filters.q.trim());
        if (filters.type) params.set('type', filters.type);
        if (filters.status && filters.status !== DEFAULT_STATUS) {
            params.set('status', filters.status);
        }
        if (tab.value === 'booked') params.set('booked_only', '1');
        if (tab.value === 'interested') params.set('interested_only', '1');

        return params;
    }

    function applyPayload(payload: VisualCalendarPage) {
        events.value = payload.data;
        total.value = payload.total;
        hasLoadedOnce.value = true;
        refreshSelectedDayEvents();
    }

    function clearMonthCache() {
        monthCache.clear();
    }

    async function requestMonth(): Promise<VisualCalendarPage> {
        const params = buildParams();
        const response = await fetch(`/events-visual-2/data?${params.toString()}`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error(`Request failed (${response.status})`);
        }

        const payload = (await response.json()) as VisualCalendarPage;
        monthCache.set(cacheKey(), payload);

        return payload;
    }

    async function fetchMonth() {
        const cached = monthCache.get(cacheKey());
        if (cached) {
            applyPayload(cached);

            return;
        }

        loading.value = true;

        try {
            const payload = await requestMonth();
            applyPayload(payload);
        } finally {
            loading.value = false;
        }
    }

    function applyFilters() {
        clearMonthCache();
        syncUrl();
        fetchMonth();
    }

    function applySort() {
        clearMonthCache();
        syncUrl();
        fetchMonth();
    }

    function applyTab() {
        clearMonthCache();
        syncUrl();
        fetchMonth();
    }

    function navigateMonth(nextMonth: string) {
        month.value = nextMonth;
        clearMonthCache();
        syncUrl();
        fetchMonth();
    }

    function selectDate(dateKey: string) {
        selectedDate.value = dateKey;
    }

    function findEvent(eventId: string): VisualEvent | undefined {
        return events.value.find((item) => item.id === eventId);
    }

    async function toggleBook(eventId: string, isAuthenticated: boolean) {
        if (!isAuthenticated) {
            router.visit('/login');

            return;
        }

        const event = findEvent(eventId);
        if (!event) {
            return;
        }

        const wasBooked = event.booked ?? false;
        event.booked = !wasBooked;

        try {
            await fetchJson<{ booked: boolean }>(`/events-visual-2/attendances/${eventId}`, {
                method: wasBooked ? 'DELETE' : 'POST',
            });

            if (tab.value === 'booked' && wasBooked) {
                events.value = events.value.filter((item) => item.id !== eventId);
                selectedDayEvents.value = selectedDayEvents.value.filter((item) => item.id !== eventId);
            }

            clearMonthCache();
        } catch {
            event.booked = wasBooked;
        }
    }

    async function toggleInterest(eventId: string, isAuthenticated: boolean) {
        if (!isAuthenticated) {
            router.visit('/login');

            return;
        }

        const event = findEvent(eventId);
        if (!event) {
            return;
        }

        const wasInterested = event.interested ?? false;
        event.interested = !wasInterested;

        try {
            await fetchJson<{ interested: boolean }>(`/events-visual-2/interests/${eventId}`, {
                method: wasInterested ? 'DELETE' : 'POST',
            });

            if (tab.value === 'interested' && wasInterested) {
                events.value = events.value.filter((item) => item.id !== eventId);
                selectedDayEvents.value = selectedDayEvents.value.filter((item) => item.id !== eventId);
            }

            clearMonthCache();
        } catch {
            event.interested = wasInterested;
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
            const response = await fetch(`/events-visual-2/locations?${new URLSearchParams({ query }).toString()}`, {
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
        Object.assign(filters, defaultFilters());
        sort.value = DEFAULT_SORT;
        tab.value = DEFAULT_TAB;
        clearSuggestions();
        clearMonthCache();
        syncUrl();
        fetchMonth();
    }

    return {
        filters,
        sort,
        tab,
        month,
        events,
        eventsByDate,
        selectedDate,
        selectedDayEvents,
        total,
        loading,
        hasLoadedOnce,
        suggestions,
        suggestionLoading,
        initFromUrl,
        applyFilters,
        applySort,
        applyTab,
        navigateMonth,
        selectDate,
        fetchMonth,
        resetFilters,
        toggleBook,
        toggleInterest,
        debouncedFetchLocationSuggestions,
        clearSuggestions,
    };
}
