<script setup lang="ts">
import { CalendarRange, Filter, MapPin, RotateCcw, Search } from '@lucide/vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
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
</script>

<template>
    <form
        class="rounded-2xl border border-border/60 bg-card p-4 shadow-sm"
        @submit.prevent="emit('apply')"
    >
        <div class="grid gap-4 xl:grid-cols-12">
            <div class="xl:col-span-4">
                <label
                    for="search"
                    class="mb-1.5 flex items-center gap-1.5 text-xs font-medium text-muted-foreground"
                >
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

            <div class="relative xl:col-span-4">
                <label
                    for="location"
                    class="mb-1.5 flex items-center gap-1.5 text-xs font-medium text-muted-foreground"
                >
                    <MapPin class="size-3.5" />
                    Location
                </label>
                <Input
                    id="location"
                    v-model="filters.location"
                    type="search"
                    autocomplete="off"
                    placeholder="City, neighborhood, or address"
                    class="bg-background"
                    @input="emit('suggest')"
                />

                <div
                    v-if="suggestionLoading || (suggestions?.length ?? 0) > 0"
                    class="absolute z-20 mt-2 w-full overflow-hidden rounded-xl border border-border bg-popover shadow-lg"
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

            <div class="grid gap-4 sm:grid-cols-2 xl:col-span-4">
                <div class="flex flex-col gap-1.5">
                    <label
                        for="date-from"
                        class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground"
                    >
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

                <div class="flex flex-col gap-1.5">
                    <label
                        for="date-to"
                        class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground"
                    >
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
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2">
            <Button type="submit" :disabled="loading">
                <Filter class="size-4" />
                Filter
            </Button>
            <Button type="button" variant="outline" :disabled="loading" @click="emit('reset')">
                <RotateCcw class="size-4" />
                Reset
            </Button>
            <span v-if="loading" class="text-xs text-muted-foreground animate-pulse">Loading...</span>
        </div>
    </form>
</template>
