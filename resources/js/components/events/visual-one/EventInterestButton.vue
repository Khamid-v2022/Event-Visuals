<script setup lang="ts">
import { Heart } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

defineProps<{
    interested: boolean;
}>();

const emit = defineEmits<{
    toggle: [];
}>();
</script>

<template>
    <Button
        type="button"
        variant="secondary"
        size="icon-sm"
        :class="cn(
            'size-8 rounded-full shadow-md backdrop-blur-sm transition-colors',
            interested
                ? 'bg-white ring-2 ring-rose-400/60 hover:bg-white'
                : 'bg-black/35 hover:bg-black/45',
        )"
        :aria-pressed="interested"
        :aria-label="interested ? 'Remove from interested' : 'Mark as interested'"
        @click.stop="emit('toggle')"
    >
        <Heart
            :class="cn(
                'size-4 transition-colors',
                interested ? 'fill-rose-500 text-rose-500' : 'text-white drop-shadow-sm',
            )"
        />
    </Button>
</template>
