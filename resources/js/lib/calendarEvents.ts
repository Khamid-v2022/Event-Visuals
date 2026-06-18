import type { VisualEvent } from '@/types/event';

/** Booked first, then interested, then by start time. */
export function sortDayEvents(events: VisualEvent[]): VisualEvent[] {
    return [...events].sort((a, b) => {
        const bookedDiff = Number(b.booked ?? false) - Number(a.booked ?? false);
        if (bookedDiff !== 0) {
            return bookedDiff;
        }

        const interestedDiff = Number(b.interested ?? false) - Number(a.interested ?? false);
        if (interestedDiff !== 0) {
            return interestedDiff;
        }

        return a.schedule.starts_at - b.schedule.starts_at;
    });
}

export interface DayEventFlags {
    hasBooked: boolean;
    hasInterested: boolean;
}

export function dayEventFlags(events: VisualEvent[]): DayEventFlags {
    return {
        hasBooked: events.some((event) => event.booked),
        hasInterested: events.some((event) => event.interested && !event.booked),
    };
}

export type ChipVariant = 'booked' | 'interested' | 'default';

export function chipVariant(event: VisualEvent): ChipVariant {
    if (event.booked) {
        return 'booked';
    }

    if (event.interested) {
        return 'interested';
    }

    return 'default';
}
