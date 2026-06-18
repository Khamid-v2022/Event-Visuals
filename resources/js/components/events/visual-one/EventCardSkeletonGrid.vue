<script setup lang="ts">
import { EVENT_GRID_CLASS } from '@/composables/visual-one/constants';

defineProps<{
    count?: number;
}>();
</script>

<template>
    <div :class="EVENT_GRID_CLASS">
        <div
            v-for="n in count ?? 12"
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
</template>
