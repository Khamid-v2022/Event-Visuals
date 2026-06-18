<script setup lang="ts">
import { computed } from 'vue';
import { Clock, Ticket } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import EventBookButton from '@/components/events/visual-one/EventBookButton.vue';
import EventInterestButton from '@/components/events/visual-one/EventInterestButton.vue';
import { formatPrice, statusLabel, statusVariant, typeLabel } from '@/lib/eventFormat';
import { getEventTypeTheme } from '@/lib/eventTypeTheme';
import { cn } from '@/lib/utils';
import type { VisualEvent } from '@/types/event';

const props = defineProps<{
    event: VisualEvent;
}>();

const emit = defineEmits<{
    select: [event: VisualEvent];
    toggleBook: [eventId: string];
    toggleInterest: [eventId: string];
}>();

const theme = computed(() => getEventTypeTheme(props.event.type));

function formatTime(schedule: VisualEvent['schedule']): string {
    const start = new Date(schedule.starts_at * 1000);

    return start.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
}
</script>

<template>
    <div
        :class="cn(
            'group rounded-2xl p-px transition-all duration-300 hover:-translate-y-0.5',
            theme.shell,
        )"
    >
        <article class="flex gap-3 rounded-[calc(1rem-1px)] bg-card p-3">
            <button
                type="button"
                class="relative size-20 shrink-0 overflow-hidden rounded-lg bg-muted sm:size-24"
                @click="emit('select', event)"
            >
                <img
                    :src="event.images[0]"
                    :alt="event.name"
                    class="size-full object-cover transition-transform duration-300 group-hover:scale-105"
                    loading="lazy"
                />
            </button>

            <div class="flex min-w-0 flex-1 flex-col gap-2">
                <div class="flex flex-wrap gap-1">
                    <Badge variant="secondary" class="text-[10px]">
                        {{ typeLabel(event.type) }}
                    </Badge>
                    <Badge :variant="statusVariant(event.status)" class="text-[10px] capitalize">
                        {{ statusLabel(event.status) }}
                    </Badge>
                </div>

                <div class="flex items-start justify-between gap-2">
                    <button
                        type="button"
                        class="min-w-0 text-left"
                        @click="emit('select', event)"
                    >
                        <h4
                            :class="cn(
                                'line-clamp-2 text-sm font-semibold leading-snug text-foreground transition-colors',
                                theme.titleHover,
                            )"
                        >
                            {{ event.name }}
                        </h4>
                    </button>
                    <EventInterestButton
                        :interested="event.interested ?? false"
                        @toggle="emit('toggleInterest', event.id)"
                    />
                </div>

                <div class="space-y-1 text-xs text-muted-foreground">
                    <p class="flex items-center gap-1.5">
                        <Clock :class="cn('size-3.5 shrink-0', theme.accent)" />
                        {{ formatTime(event.schedule) }}
                    </p>
                    <p v-if="event.venue?.name" class="flex items-center gap-1.5">
                        <Ticket :class="cn('size-3.5 shrink-0', theme.accent)" />
                        <span class="line-clamp-1">{{ event.venue.name }}</span>
                    </p>
                </div>

                <div class="mt-auto flex items-center justify-between gap-2">
                    <span class="text-xs font-medium text-foreground">
                        {{ formatPrice(event.pricing) }}
                    </span>
                    <EventBookButton
                        :booked="event.booked ?? false"
                        @toggle="emit('toggleBook', event.id)"
                    />
                </div>
            </div>
        </article>
    </div>
</template>
