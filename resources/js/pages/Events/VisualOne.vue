<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import EventCard from '@/components/events/visual-one/EventCard.vue';
import EventDetailModal from '@/components/events/visual-one/EventDetailModal.vue';
import EventFilters from '@/components/events/visual-one/EventFilters.vue';
import EventPagination from '@/components/events/visual-one/EventPagination.vue';
import { useVisualOneEvents } from '@/composables/useVisualOneEvents';
import type { VisualEvent } from '@/types/event';

const {
    filters,
    events,
    page,
    lastPage,
    total,
    loading,
    hasLoadedOnce,
    suggestions,
    suggestionLoading,
    goToPage,
    fetchPage,
    resetFilters,
    debouncedFetchLocationSuggestions,
    applyFilters,
    clearSuggestions,
} = useVisualOneEvents();

const selectedEvent = ref<VisualEvent | null>(null);
const modalOpen = ref(false);

function openEvent(event: VisualEvent) {
    selectedEvent.value = event;
    modalOpen.value = true;
}

function onSelectSuggestion(value: string) {
    filters.location = value;
    clearSuggestions();
}

function onSuggestLocations() {
    debouncedFetchLocationSuggestions();
}

function onApplyFilters() {
    clearSuggestions();
    applyFilters();
}

function onResetFilters() {
    clearSuggestions();
    resetFilters();
}

onMounted(() => {
    fetchPage(1);
});
</script>

<template>
    <Head title="Event Directory" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6 lg:flex-row lg:gap-6 lg:p-8">
        <EventFilters
            v-model="filters"
            :loading="loading"
            :suggestions="suggestions"
            :suggestion-loading="suggestionLoading"
            @apply="onApplyFilters"
            @reset="onResetFilters"
            @suggest="onSuggestLocations"
            @select-suggestion="onSelectSuggestion"
        />

        <div class="flex min-w-0 flex-1 flex-col gap-4">
            <header class="flex items-center justify-between gap-3">
                <h1 class="text-2xl font-semibold tracking-tight text-foreground">Event Directory</h1>
                <p class="text-sm text-muted-foreground">
                    <template v-if="total > 0">
                        {{ total.toLocaleString() }} event{{ total === 1 ? '' : 's' }}
                    </template>
                    <template v-else-if="hasLoadedOnce && !loading">No events found</template>
                </p>
            </header>

            <EventPagination
                v-if="lastPage > 1"
                :page="page"
                :last-page="lastPage"
                :loading="loading"
                class="justify-end"
                @go-to-page="goToPage"
            />

            <!-- Loading skeleton -->
            <div
                v-if="loading && !hasLoadedOnce"
                class="grid gap-5 sm:grid-cols-2 xl:grid-cols-2 2xl:grid-cols-3"
            >
                <div
                    v-for="n in 6"
                    :key="n"
                    class="animate-pulse overflow-hidden rounded-2xl border border-border/50 bg-card"
                >
                    <div class="aspect-[16/10] bg-muted" />
                    <div class="space-y-3 p-4">
                        <div class="h-4 w-3/4 rounded bg-muted" />
                        <div class="h-3 w-full rounded bg-muted" />
                        <div class="h-3 w-2/3 rounded bg-muted" />
                    </div>
                </div>
            </div>

            <!-- Results grid -->
            <div
                v-else
                class="grid gap-5 transition-opacity duration-300 sm:grid-cols-2 xl:grid-cols-2 2xl:grid-cols-3"
                :class="loading ? 'pointer-events-none opacity-50' : 'opacity-100'"
            >
                <EventCard
                    v-for="event in events"
                    :key="event.id"
                    :event="event"
                    @select="openEvent"
                />
            </div>

            <EventPagination
                v-if="lastPage > 1"
                :page="page"
                :last-page="lastPage"
                :loading="loading"
                class="pt-1"
                @go-to-page="goToPage"
            />

            <EventDetailModal v-model:open="modalOpen" :event="selectedEvent" />
        </div>
    </div>
</template>
