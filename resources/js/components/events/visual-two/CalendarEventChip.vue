<script setup lang="ts">
import { bookedAccent, interestedAccent } from '@/lib/attendanceColors';
import { chipVariant } from '@/lib/calendarEvents';
import { cn } from '@/lib/utils';
import type { VisualEvent } from '@/types/event';

defineProps<{
    event: VisualEvent;
}>();

const emit = defineEmits<{
    select: [event: VisualEvent];
}>();

const variantStyles = {
    booked: bookedAccent.chip,
    interested: interestedAccent.chip,
    default: 'bg-muted/70 hover:bg-muted',
} as const;

function styles(event: VisualEvent) {
    return variantStyles[chipVariant(event)];
}
</script>

<template>
    <button
        type="button"
        :class="cn(
            'flex w-full items-center gap-1.5 rounded-md px-1.5 py-0.5 text-left text-[11px] leading-tight font-medium transition-all duration-150 hover:shadow-sm',
            styles(event),
        )"
        :title="event.name"
        @click.stop="emit('select', event)"
    >
        <span class="truncate text-foreground">{{ event.name }}</span>
    </button>
</template>
