<script setup lang="ts">
import { DEFAULT_TAB, type VisualEventTab } from '@/composables/visual-one/urlState';
import { cn } from '@/lib/utils';

const tab = defineModel<VisualEventTab>('tab', { default: DEFAULT_TAB });

defineProps<{
    loading?: boolean;
}>();

const emit = defineEmits<{
    change: [];
}>();

const tabs: { value: VisualEventTab; label: string }[] = [
    { value: 'all', label: 'All events' },
    { value: 'interested', label: 'Interested' },
    { value: 'booked', label: 'Booked' },
];

function select(next: VisualEventTab) {
    if (tab.value === next) {
        return;
    }

    tab.value = next;
    emit('change');
}
</script>

<template>
    <div
        class="inline-flex rounded-lg border border-border/60 bg-muted/30 p-1"
        role="tablist"
        aria-label="Event views"
    >
        <button
            v-for="option in tabs"
            :key="option.value"
            type="button"
            role="tab"
            :aria-selected="tab === option.value"
            :disabled="loading"
            :class="cn(
                'rounded-md px-3 py-1.5 text-sm font-medium transition-colors disabled:opacity-50',
                tab === option.value
                    ? 'bg-background text-foreground shadow-sm'
                    : 'text-muted-foreground hover:text-foreground',
            )"
            @click="select(option.value)"
        >
            {{ option.label }}
        </button>
    </div>
</template>
