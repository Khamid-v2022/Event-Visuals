<script setup lang="ts">
import { computed } from 'vue';
import { Calendar, Ticket } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import EventBookButton from '@/components/events/visual-one/EventBookButton.vue';
import EventInterestButton from '@/components/events/visual-one/EventInterestButton.vue';
import { formatCardDate, formatPrice, statusLabel, statusVariant, typeLabel } from '@/lib/eventFormat';
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
</script>

<template>
  <div
    :class="cn(
      'group cursor-pointer rounded-2xl p-px transition-all duration-300 hover:-translate-y-1',
      theme.shell,
    )"
    @click="emit('select', event)"
  >
    <article class="flex h-full flex-col overflow-hidden rounded-[calc(1rem-1px)] bg-card">
      <div class="relative aspect-[16/10] overflow-hidden bg-muted">
        <img
          :src="event.images[0]"
          :alt="event.name"
          class="size-full object-cover transition-transform duration-500 group-hover:scale-105"
          loading="lazy"
        />
        <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent" />

        <EventInterestButton
          class="absolute top-3 right-3"
          :interested="event.interested ?? false"
          @toggle="emit('toggleInterest', event.id)"
        />

        <div class="absolute top-3 left-3 flex flex-wrap gap-1.5">
          <Badge variant="secondary" class="bg-background/90 text-foreground backdrop-blur-sm">
            {{ typeLabel(event.type) }}
          </Badge>
          <Badge :variant="statusVariant(event.status)" class="capitalize backdrop-blur-sm">
            {{ statusLabel(event.status) }}
          </Badge>
        </div>

        <p class="absolute right-3 bottom-3 rounded-full bg-background/90 px-3 py-1 text-sm font-semibold text-foreground shadow-sm backdrop-blur-sm">
          {{ formatPrice(event.pricing) }}
        </p>
      </div>

      <div class="flex flex-1 flex-col gap-3 p-4">
        <div>
          <h3
            :class="cn(
              'line-clamp-2 text-base font-semibold leading-snug text-foreground transition-colors',
              theme.titleHover,
            )"
          >
            {{ event.name }}
          </h3>
          <p class="mt-1 line-clamp-2 text-sm text-muted-foreground">
            {{ event.description }}
          </p>
        </div>

        <div class="mt-auto space-y-2 text-sm text-muted-foreground">
          <p class="flex items-start gap-2">
            <Calendar :class="cn('mt-0.5 size-4 shrink-0', theme.accent)" />
            <span>{{ formatCardDate(event.schedule) }}</span>
          </p>
          <p v-if="event.venue?.name" class="flex items-start gap-2">
            <Ticket :class="cn('mt-0.5 size-4 shrink-0', theme.accent)" />
            <span class="line-clamp-1">{{ event.venue.name }}</span>
          </p>
        </div>

        <div class="flex items-center justify-between gap-2 pt-1">
          <div v-if="event.tags.length" class="flex flex-wrap gap-1.5">
            <Badge
              v-for="tag in event.tags.slice(0, 2)"
              :key="tag"
              variant="outline"
              class="text-xs font-normal capitalize"
            >
              {{ tag }}
            </Badge>
          </div>
          <EventBookButton
            :booked="event.booked ?? false"
            class="ml-auto shrink-0"
            @toggle="emit('toggleBook', event.id)"
          />
        </div>
      </div>
    </article>
  </div>
</template>
