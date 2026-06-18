<script setup lang="ts">
import { DEFAULT_TAB, type VisualEventTab } from '@/composables/visual-one/urlState';
import { bookedAccent, interestedAccent } from '@/lib/attendanceColors';
import { cn } from '@/lib/utils';

const tab = defineModel<VisualEventTab>('tab', { default: DEFAULT_TAB });

defineProps<{
    loading?: boolean;
}>();

const emit = defineEmits<{
    change: [];
}>();

const tabs: {
    value: VisualEventTab;
    label: string;
    dotClass?: string;
}[] = [
    { value: 'all', label: 'All events' },
    { value: 'interested', label: 'Interested', dotClass: interestedAccent.dot },
    { value: 'booked', label: 'Booked', dotClass: bookedAccent.dot },
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
                'inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-colors disabled:opacity-50',
                tab === option.value
                    ? 'bg-background text-foreground shadow-sm'
                    : 'text-muted-foreground hover:text-foreground',
            )"
            @click="select(option.value)"
        >
            <span
                v-if="option.dotClass"
                :class="cn('size-2 shrink-0 rounded-full ring-1 ring-black/10', option.dotClass)"
                aria-hidden="true"
            />
            {{ option.label }}
        </button>
    </div>
</template>
