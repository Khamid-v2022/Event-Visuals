<script setup lang="ts">
import { CalendarSearch } from '@lucide/vue';
import type { VisualEventTab } from '@/composables/visual-one/urlState';

defineProps<{
    tab?: VisualEventTab;
}>();
</script>

<template>
    <div
        class="flex flex-col items-center justify-center rounded-xl border border-dashed border-border/60 bg-muted/20 px-6 py-16 text-center"
    >
        <div class="mb-4 rounded-full bg-muted/60 p-3">
            <CalendarSearch class="size-6 text-muted-foreground" aria-hidden="true" />
        </div>
        <p class="text-base font-medium text-foreground">No events found</p>
        <p class="mt-1.5 max-w-xs text-sm leading-relaxed text-muted-foreground">
            <template v-if="tab === 'booked'">
                You have not booked any events yet.
            </template>
            <template v-else-if="tab === 'interested'">
                You have not marked any events as interested yet.
            </template>
            <template v-else>
                Try adjusting your filters or search terms.
            </template>
        </p>
    </div>
</template>
