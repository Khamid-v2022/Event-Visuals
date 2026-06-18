import type { EventPricing, EventSchedule } from '@/types/event';

const dateTimeFormatter = new Intl.DateTimeFormat(undefined, {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
});

const dateFormatter = new Intl.DateTimeFormat(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
});

/** Unix timestamps in the payload are UTC; we render in the viewer's locale. */
export function formatScheduleRange(schedule: EventSchedule): string {
    const start = new Date(schedule.starts_at * 1000);
    const end = new Date(schedule.ends_at * 1000);

    const sameDay =
        start.getFullYear() === end.getFullYear() &&
        start.getMonth() === end.getMonth() &&
        start.getDate() === end.getDate();

    if (sameDay) {
        return `${dateFormatter.format(start)} · ${start.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' })} – ${end.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' })}`;
    }

    return `${dateTimeFormatter.format(start)} → ${dateTimeFormatter.format(end)}`;
}

export function formatCardDate(schedule: EventSchedule): string {
    return dateFormatter.format(new Date(schedule.starts_at * 1000));
}

export function formatPrice(pricing: EventPricing | null): string {
    if (!pricing) {
        return 'Free';
    }

    const amount = Number(pricing.min_price);
    if (amount <= 0) {
        return 'Free';
    }

    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: pricing.currency || 'USD',
    }).format(amount);
}

export function statusLabel(status: string): string {
    return status.replace(/_/g, ' ');
}

export function statusVariant(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'published':
            return 'default';
        case 'sold_out':
            return 'secondary';
        case 'cancelled':
            return 'destructive';
        default:
            return 'outline';
    }
}

export function typeLabel(type: string): string {
    return type.charAt(0).toUpperCase() + type.slice(1);
}
