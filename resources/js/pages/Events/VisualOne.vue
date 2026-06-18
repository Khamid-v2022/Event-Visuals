<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { useIntersectionObserver } from '@vueuse/core';
import { computed, onMounted, ref, watch } from 'vue';
import EventCard from '@/components/events/visual-one/EventCard.vue';
import EventCardSkeletonGrid from '@/components/events/visual-one/EventCardSkeletonGrid.vue';
import EventDetailModal from '@/components/events/visual-one/EventDetailModal.vue';
import EventEmptyState from '@/components/events/visual-one/EventEmptyState.vue';
import EventFilters from '@/components/events/visual-one/EventFilters.vue';
import EventSortBar from '@/components/events/visual-one/EventSortBar.vue';
import { useVisualOneEvents } from '@/composables/useVisualOneEvents';
import { EVENT_GRID_CLASS } from '@/composables/visual-one/constants';
import type { VisualEvent } from '@/types/event';

const page = usePage();
const isAuthenticated = computed(() => (page.props.auth as { user: unknown }).user != null);

const {
    filters,
    sort,
    events,
    hasMore,
    total,
    loading,
    loadingMore,
    hasLoadedOnce,
    suggestions,
    suggestionLoading,
    loadMore,
    fetchPage,
    resetFilters,
    debouncedFetchLocationSuggestions,
    initFromUrl,
    applyFilters,
    applySort,
    clearSuggestions,
    toggleInterest,
} = useVisualOneEvents();

const selectedEvent = ref<VisualEvent | null>(null);
const modalOpen = ref(false);
const sentinel = ref<HTMLElement | null>(null);

const canLoadMore = computed(
    () => hasLoadedOnce.value && hasMore.value && !loading.value && !loadingMore.value,
);

const showEmptyState = computed(
    () => hasLoadedOnce.value && !loading.value && !loadingMore.value && events.value.length === 0,
);

useIntersectionObserver(sentinel, ([entry]) => {
    if (entry?.isIntersecting && canLoadMore.value) {
        loadMore();
    }
});

watch([loading, loadingMore], () => {
    if (!canLoadMore.value || !sentinel.value) {
        return;
    }

    const { top } = sentinel.value.getBoundingClientRect();
    if (top <= window.innerHeight) {
        loadMore();
    }
});

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

function onToggleInterest(eventId: string) {
    toggleInterest(eventId, isAuthenticated.value);
}

onMounted(() => {
    initFromUrl();
    fetchPage(1, 'replace');
});
</script>

<template>
    <Head title="Event Directory" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
        <header class="flex items-center justify-between gap-3">
            <h1 class="text-2xl font-semibold tracking-tight text-foreground">Event Directory</h1>
            <p class="text-sm text-muted-foreground">
                <template v-if="hasLoadedOnce && !loading">
                    {{ total.toLocaleString() }} event{{ total === 1 ? '' : 's' }}
                </template>
            </p>
        </header>

        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:gap-6">
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

            <div class="flex min-w-0 flex-1 flex-col gap-4">
                <EventSortBar
                    v-model:sort="sort"
                    v-model:interested-only="filters.interested_only"
                    :show-interested-toggle="isAuthenticated"
                    :loading="loading"
                    @change="applySort()"
                    @interested-change="applyFilters()"
                />

                <EventCardSkeletonGrid v-if="loading" />

                <template v-else>
                    <EventEmptyState
                        v-if="showEmptyState"
                        :interested-only="filters.interested_only"
                    />

                    <div v-else :class="EVENT_GRID_CLASS">
                        <EventCard
                            v-for="event in events"
                            :key="event.id"
                            :event="event"
                            @select="openEvent"
                            @toggle-interest="onToggleInterest"
                        />
                    </div>

                    <EventCardSkeletonGrid v-if="loadingMore" :count="6" />

                    <div
                        v-if="hasLoadedOnce && hasMore"
                        ref="sentinel"
                        class="h-px w-full"
                        aria-hidden="true"
                    />

                    <p
                        v-if="hasLoadedOnce && events.length > 0 && !hasMore"
                        class="py-4 text-center text-sm text-muted-foreground"
                    >
                        All events loaded
                    </p>
                </template>

                <EventDetailModal v-model:open="modalOpen" :event="selectedEvent" />
            </div>
        </div>
    </div>
</template>
