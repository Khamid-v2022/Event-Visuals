export interface EventOrganizer {
    name: string;
    verified?: boolean;
}

export interface EventVenue {
    name: string;
    capacity?: string | number;
}

export interface EventSchedule {
    starts_at: number;
    ends_at: number;
}

export interface EventPricing {
    currency: string;
    min_price: string | number;
}

export interface VisualEvent {
    id: string;
    type: string;
    status: string;
    name: string;
    description: string;
    notes: string;
    organizer: EventOrganizer | null;
    venue: EventVenue | null;
    schedule: EventSchedule;
    pricing: EventPricing | null;
    tags: string[];
    latitude: number;
    longitude: number;
    images: string[];
    interested?: boolean;
}

export interface VisualEventFilters {
    date_from: string;
    date_to: string;
    location: string;
    q: string;
    type: string;
    status: string;
    interested_only: boolean;
}

export interface VisualEventPage {
    data: VisualEvent[];
    current_page: number;
    has_more?: boolean;
    total: number | null;
}

export interface LocationSuggestion {
    label: string;
    lat: number;
    lng: number;
}
