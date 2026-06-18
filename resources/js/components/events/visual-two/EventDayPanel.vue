<script setup lang="ts">
import { CalendarDays } from '@lucide/vue';
import EventDayCard from '@/components/events/visual-two/EventDayCard.vue';
import { formatDayHeading } from '@/lib/calendar';
import type { VisualEvent } from '@/types/event';

defineProps<{
    dateKey: string | null;
    events: VisualEvent[];
}>();

const emit = defineEmits<{
    select: [event: VisualEvent];
    toggleBook: [eventId: string];
    toggleInterest: [eventId: string];
}>();
</script>

<template>
    <aside
        class="flex max-h-[calc(100vh-10rem)] flex-col overflow-hidden rounded-xl border border-border/60 bg-card shadow-sm xl:sticky xl:top-4"
    >
        <header class="border-b border-border/60 px-4 py-3">
            <template v-if="dateKey">
                <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    Selected day
                </p>
                <h3 class="mt-0.5 text-base font-semibold text-foreground">
                    {{ formatDayHeading(dateKey) }}
                </h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ events.length }} event{{ events.length === 1 ? '' : 's' }}
                </p>
            </template>
            <template v-else>
                <p class="text-sm font-medium text-foreground">Day details</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Select a date on the calendar to browse events.
                </p>
            </template>
        </header>

        <div class="flex-1 overflow-y-auto p-3">
            <div
                v-if="!dateKey"
                class="flex flex-col items-center justify-center px-4 py-12 text-center"
            >
                <div class="mb-3 rounded-full bg-muted/60 p-3">
                    <CalendarDays class="size-5 text-muted-foreground" aria-hidden="true" />
                </div>
                <p class="text-sm text-muted-foreground">
                    Click any day to see full event cards with images, times, and booking actions.
                </p>
            </div>

            <div
                v-else-if="events.length === 0"
                class="flex flex-col items-center justify-center px-4 py-12 text-center"
            >
                <p class="text-sm font-medium text-foreground">No events this day</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Try another date or adjust your filters.
                </p>
            </div>

            <div v-else class="flex flex-col gap-3">
                <EventDayCard
                    v-for="event in events"
                    :key="event.id"
                    :event="event"
                    @select="emit('select', $event)"
                    @toggle-book="emit('toggleBook', $event)"
                    @toggle-interest="emit('toggleInterest', $event)"
                />
            </div>
        </div>
    </aside>
</template>
