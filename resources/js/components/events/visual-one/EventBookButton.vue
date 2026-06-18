<script setup lang="ts">
import { TicketCheck } from '@lucide/vue';
import { Button } from '@/components/ui/button';
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
        :class="cn('gap-1.5', block && 'w-full', booked && 'ring-1 ring-primary/30')"
        :aria-pressed="booked"
        :aria-label="booked ? 'Cancel booking' : 'Book attendance'"
        @click.stop="emit('toggle')"
    >
        <TicketCheck :class="cn('size-4', booked && 'text-primary')" />
        {{ booked ? 'Booked' : 'Book' }}
    </Button>
</template>
