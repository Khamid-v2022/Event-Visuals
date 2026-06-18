<script setup lang="ts">
import { TicketCheck } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { bookedAccent } from '@/lib/attendanceColors';
import { cn } from '@/lib/utils';

withDefaults(
    defineProps<{
        booked: boolean;
        size?: 'sm' | 'default';
        block?: boolean;
    }>(),
    {
        size: 'sm',
        block: false,
    },
);

const emit = defineEmits<{
    toggle: [];
}>();
</script>

<template>
    <Button
        type="button"
        :variant="booked ? 'secondary' : 'default'"
        :size="size"
        :class="cn('gap-1.5', block && 'w-full', booked && bookedAccent.button)"
        :aria-pressed="booked"
        :aria-label="booked ? 'Cancel booking' : 'Book attendance'"
        @click.stop="emit('toggle')"
    >
        <TicketCheck :class="cn('size-4', booked && bookedAccent.buttonIcon)" />
        {{ booked ? 'Booked' : 'Book' }}
    </Button>
</template>
