<script setup lang="ts">
import { ArrowUpDown, Heart } from '@lucide/vue';
import { VISUAL_ONE_SORT_OPTIONS, type VisualEventSort } from '@/composables/visual-one/constants';
import { cn } from '@/lib/utils';

const sort = defineModel<VisualEventSort>('sort', { required: true });
const interestedOnly = defineModel<boolean>('interestedOnly', { default: false });

defineProps<{
    loading?: boolean;
    showInterestedToggle?: boolean;
}>();

const emit = defineEmits<{
    change: [];
    interestedChange: [];
}>();

const selectClass =
    'h-9 min-w-44 rounded-md border border-input bg-background px-3 text-sm text-foreground shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:opacity-50';

function toggleInterestedOnly() {
    interestedOnly.value = !interestedOnly.value;
    emit('interestedChange');
}
</script>

<template>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <label
            v-if="showInterestedToggle"
            class="flex cursor-pointer items-center gap-2.5 text-sm text-muted-foreground"
        >
            <Heart
                :class="cn(
                    'size-4 transition-colors',
                    interestedOnly ? 'fill-rose-500 text-rose-500' : 'text-muted-foreground',
                )"
            />
            <span :class="interestedOnly ? 'font-medium text-foreground' : ''">Interested only</span>
            <button
                type="button"
                role="switch"
                :aria-checked="interestedOnly"
                :disabled="loading"
                :class="cn(
                    'relative inline-flex h-5 w-9 shrink-0 items-center rounded-full p-0.5 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50 disabled:opacity-50',
                    interestedOnly ? 'bg-primary' : 'bg-input',
                )"
                @click.prevent="toggleInterestedOnly"
            >
                <span
                    :class="cn(
                        'pointer-events-none block size-4 rounded-full bg-background shadow-sm transition-transform',
                        interestedOnly ? 'translate-x-4' : 'translate-x-0',
                    )"
                />
            </button>
        </label>
        <div v-else aria-hidden="true" />

        <div class="flex items-center gap-2">
            <label for="sort-by" class="flex items-center gap-1.5 text-sm text-muted-foreground">
                <ArrowUpDown class="size-4" />
                Sort by
            </label>
            <select
                id="sort-by"
                v-model="sort"
                :class="selectClass"
                :disabled="loading"
                @change="emit('change')"
            >
                <option v-for="option in VISUAL_ONE_SORT_OPTIONS" :key="option.value" :value="option.value">
                    {{ option.label }}
                </option>
            </select>
        </div>
    </div>
</template>
