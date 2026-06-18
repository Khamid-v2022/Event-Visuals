<script setup lang="ts">
import { weekdayLabels } from '@/lib/calendar';
</script>

<template>
    <div class="overflow-hidden rounded-xl border border-border/60 bg-card shadow-sm">
        <div class="grid grid-cols-7 border-b border-border/60 bg-muted/30">
            <div
                v-for="label in weekdayLabels()"
                :key="label"
                class="px-2 py-2.5 text-center"
            >
                <div class="mx-auto h-3 w-8 animate-pulse rounded bg-muted" />
            </div>
        </div>

        <div class="grid grid-cols-7">
            <div
                v-for="i in 42"
                :key="i"
                class="min-h-[5.5rem] border-b border-r border-border/40 p-2 sm:min-h-[6.5rem]"
            >
                <div class="mb-2 size-6 animate-pulse rounded-full bg-muted" />
                <div class="space-y-1">
                    <div class="h-4 w-full animate-pulse rounded bg-muted/80" />
                    <div class="h-4 w-4/5 animate-pulse rounded bg-muted/60" />
                </div>
            </div>
        </div>
    </div>
</template>
