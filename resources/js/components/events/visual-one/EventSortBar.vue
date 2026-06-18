<script setup lang="ts">
import { ArrowUpDown } from '@lucide/vue';
import { VISUAL_ONE_SORT_OPTIONS, type VisualEventSort } from '@/composables/visual-one/constants';

const sort = defineModel<VisualEventSort>({ required: true });

defineProps<{
    loading?: boolean;
}>();

const emit = defineEmits<{
    change: [];
}>();

const selectClass =
    'h-9 min-w-44 rounded-md border border-input bg-background px-3 text-sm text-foreground shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:opacity-50';
</script>

<template>
    <div class="flex items-center justify-end gap-2">
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
</template>
