<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import EventDetailModal from '@/components/events/visual-one/EventDetailModal.vue';
import EventEmptyState from '@/components/events/visual-one/EventEmptyState.vue';
import EventFilters from '@/components/events/visual-one/EventFilters.vue';
import EventSortBar from '@/components/events/visual-two/EventSortBar.vue';
import EventViewTabs from '@/components/events/visual-one/EventViewTabs.vue';
import CalendarHeader from '@/components/events/visual-two/CalendarHeader.vue';
import CalendarSkeleton from '@/components/events/visual-two/CalendarSkeleton.vue';
import EventCalendar from '@/components/events/visual-two/EventCalendar.vue';
import EventDayPanel from '@/components/events/visual-two/EventDayPanel.vue';
import { useVisualTwoEvents } from '@/composables/useVisualTwoEvents';
import { currentMonthParam, isSameMonth, toDateKey } from '@/lib/calendar';
import type { VisualEvent } from '@/types/event';

const page = usePage();
const isAuthenticated = computed(() => (page.props.auth as { user: unknown }).user != null);

const {
    filters,
    sort,
    tab,
    month,
    eventsByDate,
    selectedDate,
    selectedDayEvents,
    total,
    loading,
    hasLoadedOnce,
    suggestions,
    suggestionLoading,
    fetchMonth,
    navigateMonth,
    selectDate,
    resetFilters,
    debouncedFetchLocationSuggestions,
    applyFilters,
    applySort,
    applyTab,
    clearSuggestions,
    toggleBook,
    toggleInterest,
} = useVisualTwoEvents();

const selectedEvent = ref<VisualEvent | null>(null);
const modalOpen = ref(false);

const showEmptyState = computed(
    () => hasLoadedOnce.value && !loading && eventsByDate.value.size === 0,
);

function openEvent(event: VisualEvent) {
    selectedEvent.value = event;
    modalOpen.value = true;
}

function onApplyFilters() {
    clearSuggestions();
    applyFilters();
}

function onSelectSuggestion(value: string) {
    filters.location = value;
    clearSuggestions();
    applyFilters();
}

function onToggleBook(eventId: string) {
    toggleBook(eventId, isAuthenticated.value);
}

function onToggleInterest(eventId: string) {
    toggleInterest(eventId, isAuthenticated.value);
}

function onMonthChange() {
    navigateMonth(month.value);
    autoSelectDate();
}

function goToToday() {
    month.value = currentMonthParam();
    navigateMonth(month.value);
    selectedDate.value = toDateKey(new Date());
}

function onSelectDate(dateKey: string) {
    selectDate(dateKey);
}

function autoSelectDate() {
    const today = new Date();
    const todayKey = toDateKey(today);

    if (isSameMonth(today, month.value)) {
        selectedDate.value = todayKey;

        return;
    }

    const firstWithEvents = [...eventsByDate.value.keys()].sort()[0];
    selectedDate.value = firstWithEvents ?? null;
}

watch(eventsByDate, () => {
    if (selectedDate.value && eventsByDate.value.has(selectedDate.value)) {
        return;
    }

    autoSelectDate();
});

onMounted(() => {
    if (!selectedDate.value) {
        selectedDate.value = isSameMonth(new Date(), month.value) ? toDateKey(new Date()) : null;
    }
    fetchMonth();
});
</script>

<template>
    <Head title="Event Calendar" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-5 lg:p-6">
        <header class="flex flex-wrap items-center justify-between gap-2">
            <h1 class="text-xl font-semibold tracking-tight text-foreground">Event Calendar</h1>
        </header>

        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:gap-5">
            <EventFilters
                v-model="filters"
                :loading="loading"
                :suggestions="suggestions"
                :suggestion-loading="suggestionLoading"
                @apply="onApplyFilters"
                @reset="resetFilters()"
                @suggest="debouncedFetchLocationSuggestions()"
                @select-suggestion="onSelectSuggestion"
            />

            <div class="flex min-w-0 flex-1 flex-col gap-3">
                <CalendarHeader
                    v-model:month="month"
                    :loading="loading"
                    :total="hasLoadedOnce ? total : undefined"
                    @today="goToToday"
                    @change="onMonthChange"
                />

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <EventViewTabs v-model:tab="tab" :loading="loading" @change="applyTab()" />
                    <EventSortBar v-model:sort="sort" :loading="loading" @change="applySort()" />
                </div>

                <CalendarSkeleton v-if="loading && !hasLoadedOnce" />

                <template v-else>
                    <EventEmptyState
                        v-if="showEmptyState"
                        :tab="tab"
                    />

                    <div
                        v-else
                        class="grid gap-4 xl:grid-cols-[1fr_minmax(280px,320px)]"
                    >
                        <EventCalendar
                            :month="month"
                            :events-by-date="eventsByDate"
                            :selected-date="selectedDate"
                            @select-date="onSelectDate"
                            @select-event="openEvent"
                        />

                        <EventDayPanel
                            :date-key="selectedDate"
                            :events="selectedDayEvents"
                            @select="openEvent"
                            @toggle-book="onToggleBook"
                            @toggle-interest="onToggleInterest"
                        />
                    </div>
                </template>

                <EventDetailModal
                    v-model:open="modalOpen"
                    :event="selectedEvent"
                    @toggle-book="onToggleBook"
                    @toggle-interest="onToggleInterest"
                />
            </div>
        </div>
    </div>
</template>
