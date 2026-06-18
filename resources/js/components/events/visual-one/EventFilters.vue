<script setup lang="ts">
import { ref } from 'vue';
import {
    CalendarRange,
    ChevronLeft,
    ChevronRight,
    Filter,
    MapPin,
    RotateCcw,
    Search,
    SlidersHorizontal,
    Tag,
    ToggleLeft,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { EVENT_STATUSES, EVENT_TYPES } from '@/lib/eventFilterOptions';
import { statusLabel, typeLabel } from '@/lib/eventFormat';
import { cn } from '@/lib/utils';
import type { LocationSuggestion, VisualEventFilters } from '@/types/event';

const filters = defineModel<VisualEventFilters>({ required: true });

defineProps<{
    loading?: boolean;
    suggestions?: LocationSuggestion[];
    suggestionLoading?: boolean;
}>();

const emit = defineEmits<{
    apply: [];
    reset: [];
    suggest: [];
    selectSuggestion: [value: string];
}>();

const collapsed = ref(false);

const selectClass =
    'h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';
</script>

<template>
    <aside
        :class="cn(
            'sticky top-4 shrink-0 self-start overflow-hidden rounded-xl border border-border/60 bg-card shadow-sm transition-[width] duration-300',
            collapsed ? 'w-11' : 'w-full lg:w-64 xl:w-72',
        )"
    >
        <div
            :class="cn(
                'flex items-center border-b border-border/60',
                collapsed ? 'justify-center p-2' : 'gap-2 px-3 py-2',
            )"
        >
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :aria-label="collapsed ? 'Expand filters' : 'Collapse filters'"
                @click="collapsed = !collapsed"
            >
                <ChevronRight v-if="collapsed" class="size-4" />
                <ChevronLeft v-else class="size-4" />
            </Button>
            <span v-if="!collapsed" class="text-sm font-semibold text-foreground">Filters</span>
        </div>

        <form
            v-if="!collapsed"
            class="flex max-h-[calc(100vh-8rem)] flex-col gap-5 overflow-y-auto p-4"
            @submit.prevent="emit('apply')"
        >
            <div class="space-y-1.5">
                <label for="search" class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                    <Search class="size-3.5" />
                    Search
                </label>
                <Input
                    id="search"
                    v-model="filters.q"
                    type="search"
                    placeholder="Name, tags, venue, or place"
                    class="bg-background"
                />
            </div>

            <div class="relative space-y-1.5">
                <label for="location" class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                    <MapPin class="size-3.5" />
                    Location
                </label>
                <Input
                    id="location"
                    v-model="filters.location"
                    type="search"
                    autocomplete="off"
                    placeholder="City or address"
                    class="bg-background"
                    @input="emit('suggest')"
                />

                <div
                    v-if="suggestionLoading || (suggestions?.length ?? 0) > 0"
                    class="absolute z-20 mt-1 w-full overflow-hidden rounded-lg border border-border bg-popover shadow-lg"
                >
                    <div
                        v-if="suggestionLoading"
                        class="px-3 py-2 text-xs text-muted-foreground"
                    >
                        Finding locations...
                    </div>

                    <button
                        v-for="suggestion in suggestions"
                        :key="`${suggestion.label}-${suggestion.lat}-${suggestion.lng}`"
                        type="button"
                        class="block w-full border-b border-border/60 px-3 py-2 text-left text-sm transition-colors last:border-b-0 hover:bg-accent"
                        @click="emit('selectSuggestion', suggestion.label)"
                    >
                        {{ suggestion.label }}
                    </button>
                </div>
            </div>

            <div class="space-y-1.5">
                <label for="date-from" class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                    <CalendarRange class="size-3.5" />
                    From
                </label>
                <Input
                    id="date-from"
                    v-model="filters.date_from"
                    type="date"
                    class="bg-background"
                />
            </div>

            <div class="space-y-1.5">
                <label for="date-to" class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                    <CalendarRange class="size-3.5" />
                    To
                </label>
                <Input
                    id="date-to"
                    v-model="filters.date_to"
                    type="date"
                    class="bg-background"
                />
            </div>

            <div class="space-y-1.5">
                <label for="type" class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                    <Tag class="size-3.5" />
                    Type
                </label>
                <select id="type" v-model="filters.type" :class="selectClass">
                    <option value="">All types</option>
                    <option v-for="type in EVENT_TYPES" :key="type" :value="type">
                        {{ typeLabel(type) }}
                    </option>
                </select>
            </div>

            <div class="space-y-1.5">
                <label for="status" class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                    <ToggleLeft class="size-3.5" />
                    Status
                </label>
                <select id="status" v-model="filters.status" :class="selectClass">
                    <option value="">Published & sold out</option>
                    <option v-for="status in EVENT_STATUSES" :key="status" :value="status">
                        {{ statusLabel(status) }}
                    </option>
                </select>
            </div>

            <div class="flex flex-col gap-2 border-t border-border/60 pt-4">
                <Button type="submit" :disabled="loading" class="w-full">
                    <Filter class="size-4" />
                    Filter
                </Button>
                <Button type="button" variant="outline" :disabled="loading" class="w-full" @click="emit('reset')">
                    <RotateCcw class="size-4" />
                    Reset
                </Button>
                <span v-if="loading" class="text-center text-xs text-muted-foreground animate-pulse">
                    Loading...
                </span>
            </div>
        </form>

        <div v-else class="flex justify-center py-3">
            <SlidersHorizontal class="size-4 text-muted-foreground" aria-hidden="true" />
        </div>
    </aside>
</template>
