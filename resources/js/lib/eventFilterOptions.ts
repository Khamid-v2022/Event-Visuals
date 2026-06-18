import { EVENT_TYPES } from '@/lib/eventTypeTheme';

export const EVENT_STATUSES = ['draft', 'published', 'cancelled', 'sold_out'] as const;

export type EventStatus = (typeof EVENT_STATUSES)[number];

export { EVENT_TYPES };
