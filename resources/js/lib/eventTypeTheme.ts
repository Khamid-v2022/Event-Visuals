/**
 * Visual identity per event type — single source of truth for cards, badges, and future layouts.
 * Keep presentation tokens here rather than scattering Tailwind classes across components.
 */
export const EVENT_TYPES = [
    'concert',
    'conference',
    'meetup',
    'workshop',
    'festival',
    'sports',
    'networking',
    'exhibition',
] as const;

export type EventType = (typeof EVENT_TYPES)[number];

export interface EventTypeTheme {
    /** 1px gradient frame + outward glow (applied on the card shell). */
    shell: string;
    /** Small accents inside the card — icons, hover title, etc. */
    accent: string;
    titleHover: string;
}

const defaultTheme: EventTypeTheme = {
    shell: 'bg-gradient-to-br from-zinc-500/55 via-zinc-600/30 to-zinc-700/20 shadow-[0_0_12px_-14px_rgba(161,161,170,0.28)] group-hover:shadow-[0_0_14px_-13px_rgba(161,161,170,0.36)]',
    accent: 'text-zinc-400',
    titleHover: 'group-hover:text-zinc-200',
};

/** Tuned for dark UI: muted gradients with a tight outer bloom. */
export const eventTypeThemes: Record<EventType, EventTypeTheme> = {
    concert: {
        shell: 'bg-gradient-to-br from-violet-500/80 via-purple-500/50 to-fuchsia-600/35 shadow-[0_0_14px_-14px_rgba(168,85,247,0.38)] group-hover:shadow-[0_0_16px_-13px_rgba(192,132,252,0.46)]',
        accent: 'text-violet-400',
        titleHover: 'group-hover:text-violet-300',
    },
    conference: {
        shell: 'bg-gradient-to-br from-blue-500/75 via-indigo-500/45 to-sky-600/30 shadow-[0_0_14px_-14px_rgba(59,130,246,0.35)] group-hover:shadow-[0_0_16px_-13px_rgba(96,165,250,0.44)]',
        accent: 'text-blue-400',
        titleHover: 'group-hover:text-blue-300',
    },
    meetup: {
        shell: 'bg-gradient-to-br from-emerald-500/75 via-teal-500/45 to-cyan-600/30 shadow-[0_0_14px_-14px_rgba(16,185,129,0.35)] group-hover:shadow-[0_0_16px_-13px_rgba(52,211,153,0.44)]',
        accent: 'text-emerald-400',
        titleHover: 'group-hover:text-emerald-300',
    },
    workshop: {
        shell: 'bg-gradient-to-br from-amber-500/75 via-orange-500/45 to-yellow-600/30 shadow-[0_0_14px_-14px_rgba(245,158,11,0.35)] group-hover:shadow-[0_0_16px_-13px_rgba(251,191,36,0.44)]',
        accent: 'text-amber-400',
        titleHover: 'group-hover:text-amber-300',
    },
    festival: {
        shell: 'bg-gradient-to-br from-rose-500/80 via-pink-500/50 to-fuchsia-500/35 shadow-[0_0_14px_-14px_rgba(244,63,94,0.35)] group-hover:shadow-[0_0_16px_-13px_rgba(251,113,133,0.45)]',
        accent: 'text-rose-400',
        titleHover: 'group-hover:text-rose-300',
    },
    sports: {
        shell: 'bg-gradient-to-br from-lime-500/70 via-green-500/45 to-emerald-600/30 shadow-[0_0_14px_-14px_rgba(132,204,22,0.32)] group-hover:shadow-[0_0_16px_-13px_rgba(163,230,53,0.4)]',
        accent: 'text-lime-400',
        titleHover: 'group-hover:text-lime-300',
    },
    networking: {
        shell: 'bg-gradient-to-br from-cyan-500/75 via-sky-500/45 to-blue-600/30 shadow-[0_0_14px_-14px_rgba(6,182,212,0.35)] group-hover:shadow-[0_0_16px_-13px_rgba(34,211,238,0.44)]',
        accent: 'text-cyan-400',
        titleHover: 'group-hover:text-cyan-300',
    },
    exhibition: {
        shell: 'bg-gradient-to-br from-purple-500/70 via-violet-500/40 to-slate-500/35 shadow-[0_0_14px_-14px_rgba(147,51,234,0.32)] group-hover:shadow-[0_0_16px_-13px_rgba(167,139,250,0.4)]',
        accent: 'text-purple-400',
        titleHover: 'group-hover:text-purple-300',
    },
};

export function getEventTypeTheme(type: string): EventTypeTheme {
    if ((EVENT_TYPES as readonly string[]).includes(type)) {
        return eventTypeThemes[type as EventType];
    }

    return defaultTheme;
}
