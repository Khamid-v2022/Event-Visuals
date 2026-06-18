/** Shared palette for booked / interested states across calendar chips, cells, and tabs. */

export const bookedAccent = {
    dot: 'bg-emerald-500',
    chip: 'border-l-2 border-l-emerald-500 bg-emerald-500/15 hover:bg-emerald-500/20',
    cell: 'bg-emerald-500/[0.06]',
    button: 'bg-emerald-500/10 text-emerald-700 ring-1 ring-emerald-500/40 hover:bg-emerald-500/15 dark:text-emerald-400',
    buttonIcon: 'text-emerald-600 dark:text-emerald-400',
} as const;

export const interestedAccent = {
    dot: 'bg-rose-500',
    chip: 'border-l-2 border-l-rose-500 bg-rose-500/15 hover:bg-rose-500/20',
    cell: 'bg-rose-500/[0.05]',
} as const;
