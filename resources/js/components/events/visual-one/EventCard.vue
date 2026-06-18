<script setup lang="ts">
import { computed } from 'vue';
import { Calendar, Heart, Ticket } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatCardDate, formatPrice, statusLabel, statusVariant, typeLabel } from '@/lib/eventFormat';
import { getEventTypeTheme } from '@/lib/eventTypeTheme';
import { cn } from '@/lib/utils';
import type { VisualEvent } from '@/types/event';

const props = defineProps<{
    event: VisualEvent;
}>();

const emit = defineEmits<{
    select: [event: VisualEvent];
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

        <Button
          type="button"
          variant="secondary"
          size="icon-sm"
          :class="cn(
            'absolute top-3 right-3 size-8 rounded-full shadow-md backdrop-blur-sm transition-colors',
            event.interested
              ? 'bg-white ring-2 ring-rose-400/60 hover:bg-white'
              : 'bg-black/35 hover:bg-black/45',
          )"
          :aria-pressed="event.interested ?? false"
          :aria-label="event.interested ? 'Remove from interested' : 'Mark as interested'"
          @click.stop="emit('toggleInterest', event.id)"
        >
          <Heart
            :class="cn(
              'size-4 transition-colors',
              event.interested
                ? 'fill-rose-500 text-rose-500'
                : 'text-white drop-shadow-sm',
            )"
          />
        </Button>

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

        <div v-if="event.tags.length" class="flex flex-wrap gap-1.5 pt-1">
          <Badge
            v-for="tag in event.tags.slice(0, 3)"
            :key="tag"
            variant="outline"
            class="text-xs font-normal capitalize"
          >
            {{ tag }}
          </Badge>
        </div>
      </div>
    </article>
  </div>
</template>
