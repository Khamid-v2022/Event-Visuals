<script setup lang="ts">
import { ref, toRef, watch } from 'vue';
import { BadgeCheck, Calendar, MapPin, Ticket, Users } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useEventDetailAddress } from '@/composables/useEventAddress';
import EventBookButton from '@/components/events/visual-one/EventBookButton.vue';
import EventInterestButton from '@/components/events/visual-one/EventInterestButton.vue';
import {
    formatPrice,
    formatScheduleRange,
    statusLabel,
    statusVariant,
    typeLabel,
} from '@/lib/eventFormat';
import type { VisualEvent } from '@/types/event';

const open = defineModel<boolean>('open', { default: false });

const props = defineProps<{
    event: VisualEvent | null;
}>();

const emit = defineEmits<{
    toggleBook: [eventId: string];
    toggleInterest: [eventId: string];
}>();

const selectedImage = ref(0);
const { address, loading: addressLoading, error: addressError } = useEventDetailAddress(
    toRef(props, 'event'),
    open,
);

watch(
    () => props.event?.id,
    () => {
        selectedImage.value = 0;
    },
);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent
            v-if="event"
            class="flex max-h-[90vh] w-[calc(100%-2rem)] max-w-5xl flex-col gap-0 overflow-hidden p-0 sm:max-w-5xl"
        >
            <div class="grid max-h-[90vh] overflow-y-auto lg:grid-cols-2">
                <!-- Image gallery — main + thumbnails -->
                <div class="border-b border-border bg-muted/30 p-5 lg:border-r lg:border-b-0">
                    <div class="overflow-hidden rounded-xl border border-border/60 bg-background shadow-inner">
                        <img
                            :src="event.images[selectedImage]"
                            :alt="`${event.name} — image ${selectedImage + 1}`"
                            class="aspect-[4/3] w-full object-cover"
                        />
                    </div>

                    <div class="mt-3 flex gap-2">
                        <button
                            v-for="(image, index) in event.images"
                            :key="image"
                            type="button"
                            class="overflow-hidden rounded-lg border-2 transition-all duration-200"
                            :class="
                                selectedImage === index
                                    ? 'border-primary ring-2 ring-primary/30'
                                    : 'border-transparent opacity-70 hover:opacity-100'
                            "
                            @click="selectedImage = index"
                        >
                            <img
                                :src="image"
                                :alt="`${event.name} thumbnail ${index + 1}`"
                                class="size-16 object-cover sm:size-20"
                            />
                        </button>
                    </div>
                </div>

                <!-- Event details -->
                <div class="flex flex-col gap-5 p-6">
                    <DialogHeader class="space-y-3 text-left">
                        <div class="flex flex-wrap gap-2">
                            <Badge variant="secondary">{{ typeLabel(event.type) }}</Badge>
                            <Badge :variant="statusVariant(event.status)" class="capitalize">
                                {{ statusLabel(event.status) }}
                            </Badge>
                            <Badge
                                v-for="tag in event.tags"
                                :key="tag"
                                variant="outline"
                                class="capitalize"
                            >
                                {{ tag }}
                            </Badge>
                        </div>

                        <DialogTitle class="text-2xl leading-tight font-semibold">
                            {{ event.name }}
                        </DialogTitle>

                        <div class="flex flex-wrap items-center gap-2">
                            <EventInterestButton
                                :interested="event.interested ?? false"
                                @toggle="emit('toggleInterest', event.id)"
                            />
                        </div>

                        <DialogDescription class="text-base leading-relaxed text-muted-foreground">
                            {{ event.description }}
                        </DialogDescription>
                    </DialogHeader>

                    <div class="space-y-4 rounded-xl border border-border/60 bg-muted/20 p-4 text-sm">
                        <div class="flex gap-3">
                            <Calendar class="mt-0.5 size-4 shrink-0 text-primary" />
                            <div>
                                <p class="font-medium text-foreground">Schedule</p>
                                <p class="text-muted-foreground">{{ formatScheduleRange(event.schedule) }}</p>
                            </div>
                        </div>

                        <div
                            v-if="event.latitude !== 0 || event.longitude !== 0"
                            class="flex gap-3"
                        >
                            <MapPin class="mt-0.5 size-4 shrink-0 text-primary" />
                            <div>
                                <p class="font-medium text-foreground">Location</p>
                                <p v-if="addressLoading" class="text-muted-foreground">
                                    Resolving address…
                                </p>
                                <p v-else-if="address" class="text-muted-foreground">
                                    {{ address }}
                                </p>
                                <p v-else-if="addressError" class="text-muted-foreground">
                                    Address unavailable
                                </p>
                            </div>
                        </div>

                        <div v-if="event.venue?.name" class="flex gap-3">
                            <Ticket class="mt-0.5 size-4 shrink-0 text-primary" />
                            <div>
                                <p class="font-medium text-foreground">Venue</p>
                                <p class="text-muted-foreground">
                                    {{ event.venue.name }}
                                    <span v-if="event.venue.capacity" class="text-muted-foreground/80">
                                        · capacity {{ Number(event.venue.capacity).toLocaleString() }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <Users class="mt-0.5 size-4 shrink-0 text-primary" />
                            <div>
                                <p class="font-medium text-foreground">Organizer</p>
                                <p class="flex items-center gap-1.5 text-muted-foreground">
                                    {{ event.organizer?.name ?? '—' }}
                                    <BadgeCheck
                                        v-if="event.organizer?.verified"
                                        class="size-4 text-primary"
                                        aria-label="Verified organizer"
                                    />
                                </p>
                            </div>
                        </div>

                        <div class="border-t border-border/60 pt-3">
                            <p class="font-medium text-foreground">From {{ formatPrice(event.pricing) }}</p>
                        </div>
                    </div>

                    <div v-if="event.notes" class="space-y-2">
                        <h4 class="text-sm font-semibold text-foreground">Additional notes</h4>
                        <p class="max-h-40 overflow-y-auto text-sm leading-relaxed text-muted-foreground">
                            {{ event.notes }}
                        </p>
                    </div>

                    <EventBookButton
                        :booked="event.booked ?? false"
                        size="default"
                        block
                        @toggle="emit('toggleBook', event.id)"
                    />
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
