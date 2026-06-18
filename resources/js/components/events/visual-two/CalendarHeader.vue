<script setup lang="ts">
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { formatMonthLabel, shiftMonth } from '@/lib/calendar';

const month = defineModel<string>('month', { required: true });

defineProps<{
    loading?: boolean;
    total?: number;
}>();

const emit = defineEmits<{
    today: [];
    change: [];
}>();

function goPrev() {
    month.value = shiftMonth(month.value, -1);
    emit('change');
}

function goNext() {
    month.value = shiftMonth(month.value, 1);
    emit('change');
}
</script>

<template>
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <div class="flex items-center rounded-lg border border-border/60 bg-muted/30 p-0.5">
                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    :disabled="loading"
                    aria-label="Previous month"
                    @click="goPrev"
                >
                    <ChevronLeft class="size-4" />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    :disabled="loading"
                    aria-label="Next month"
                    @click="goNext"
                >
                    <ChevronRight class="size-4" />
                </Button>
            </div>

            <h2 class="text-lg font-semibold tracking-tight text-foreground">
                {{ formatMonthLabel(month) }}
            </h2>

            <Button
                type="button"
                variant="outline"
                size="sm"
                :disabled="loading"
                @click="emit('today')"
            >
                Today
            </Button>
        </div>

        <p v-if="total !== undefined && !loading" class="text-xs text-muted-foreground">
            {{ total.toLocaleString() }} event{{ total === 1 ? '' : 's' }}
        </p>
    </div>
</template>
