<script setup lang="ts">
import { computed } from 'vue';
import { MAX_VISIBLE_CHIPS } from '@/composables/visual-two/constants';
import CalendarEventChip from '@/components/events/visual-two/CalendarEventChip.vue';
import { bookedAccent, interestedAccent } from '@/lib/attendanceColors';
import { dayEventFlags } from '@/lib/calendarEvents';
import {
    getCalendarGridDays,
    isSameMonth,
    isToday,
    toDateKey,
    weekdayLabels,
} from '@/lib/calendar';
import { cn } from '@/lib/utils';
import type { VisualEvent } from '@/types/event';

const props = defineProps<{
    month: string;
    eventsByDate: Map<string, VisualEvent[]>;
    selectedDate: string | null;
}>();

const emit = defineEmits<{
    selectDate: [dateKey: string];
    selectEvent: [event: VisualEvent];
}>();

const weekdays = weekdayLabels();
const gridDays = computed(() => getCalendarGridDays(props.month));

function dayEvents(date: Date): VisualEvent[] {
    return props.eventsByDate.get(toDateKey(date)) ?? [];
}

function hiddenCount(date: Date): number {
    const count = dayEvents(date).length;

    return count > MAX_VISIBLE_CHIPS ? count - MAX_VISIBLE_CHIPS : 0;
}

function flags(date: Date) {
    return dayEventFlags(dayEvents(date));
}
</script>

<template>
    <div class="overflow-hidden rounded-xl border border-border/60 bg-card shadow-sm">
        <div class="grid grid-cols-7 border-b border-border/60 bg-muted/30">
            <div
                v-for="label in weekdays"
                :key="label"
                class="px-2 py-2 text-center text-xs font-medium tracking-wide text-muted-foreground uppercase"
            >
                {{ label }}
            </div>
        </div>

        <div class="grid grid-cols-7">
            <button
                v-for="date in gridDays"
                :key="toDateKey(date)"
                type="button"
                :class="cn(
                    'group/cell relative flex min-h-[5.5rem] flex-col gap-0.5 border-b border-r border-border/40 p-1.5 text-left transition-colors duration-150 sm:min-h-[6.5rem] sm:p-2',
                    'hover:bg-muted/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-inset',
                    !isSameMonth(date, month) && 'bg-muted/15 text-muted-foreground/60',
                    flags(date).hasBooked && bookedAccent.cell,
                    flags(date).hasInterested && interestedAccent.cell,
                    selectedDate === toDateKey(date) && 'ring-1 ring-inset ring-primary/40',
                )"
                @click="emit('selectDate', toDateKey(date))"
            >
                <div class="mb-0.5 flex items-center gap-1">
                    <span
                        :class="cn(
                            'inline-flex size-6 items-center justify-center rounded-full text-xs font-medium',
                            isToday(date) && 'bg-primary text-primary-foreground',
                            !isToday(date) && 'text-foreground',
                        )"
                    >
                        {{ date.getDate() }}
                    </span>

                    <span
                        v-if="flags(date).hasBooked"
                        :class="cn('size-1.5 rounded-full', bookedAccent.dot)"
                        title="You have booked events"
                        aria-hidden="true"
                    />
                    <span
                        v-if="flags(date).hasInterested"
                        :class="cn('size-1.5 rounded-full', interestedAccent.dot)"
                        title="You have interested events"
                        aria-hidden="true"
                    />
                </div>

                <div class="flex flex-1 flex-col gap-0.5 overflow-hidden">
                    <CalendarEventChip
                        v-for="event in dayEvents(date).slice(0, MAX_VISIBLE_CHIPS)"
                        :key="event.id"
                        :event="event"
                        @select="emit('selectEvent', $event)"
                    />

                    <span
                        v-if="hiddenCount(date) > 0"
                        class="px-1 text-[10px] font-medium text-muted-foreground"
                    >
                        +{{ hiddenCount(date) }} more
                    </span>
                </div>
            </button>
        </div>
    </div>
</template>
