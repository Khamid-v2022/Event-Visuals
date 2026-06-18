/** Local calendar helpers — week starts on Sunday to match the month grid. */

const MONTH_PATTERN = /^\d{4}-\d{2}$/;

export function isValidMonthParam(month: string): boolean {
    if (!MONTH_PATTERN.test(month)) {
        return false;
    }

    const { year, monthIndex } = parseMonthParamRaw(month);

    return Number.isInteger(year) && Number.isInteger(monthIndex) && monthIndex >= 0 && monthIndex <= 11;
}

function parseMonthParamRaw(month: string): { year: number; monthIndex: number } {
    const [year, monthNum] = month.split('-').map(Number);

    return { year, monthIndex: monthNum - 1 };
}

export function toDateKey(date: Date): string {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');

    return `${y}-${m}-${d}`;
}

export function parseMonthParam(month: string): { year: number; monthIndex: number } {
    const normalized = isValidMonthParam(month) ? month : currentMonthParam();

    return parseMonthParamRaw(normalized);
}

export function formatMonthParam(year: number, monthIndex: number): string {
    return `${year}-${String(monthIndex + 1).padStart(2, '0')}`;
}

export function currentMonthParam(): string {
    const now = new Date();

    return formatMonthParam(now.getFullYear(), now.getMonth());
}

export function shiftMonth(month: string, delta: number): string {
    const { year, monthIndex } = parseMonthParam(month);
    const date = new Date(year, monthIndex + delta, 1);

    return formatMonthParam(date.getFullYear(), date.getMonth());
}

/** 42-day grid (6 rows) for a month view. */
export function getCalendarGridDays(month: string): Date[] {
    const { year, monthIndex } = parseMonthParam(month);
    const firstOfMonth = new Date(year, monthIndex, 1);
    const startOffset = firstOfMonth.getDay();
    const gridStart = new Date(year, monthIndex, 1 - startOffset);
    const days: Date[] = [];

    for (let i = 0; i < 42; i++) {
        days.push(new Date(gridStart.getFullYear(), gridStart.getMonth(), gridStart.getDate() + i));
    }

    return days;
}

export function isSameMonth(date: Date, month: string): boolean {
    const { year, monthIndex } = parseMonthParam(month);

    return date.getFullYear() === year && date.getMonth() === monthIndex;
}

export function isToday(date: Date): boolean {
    const now = new Date();

    return (
        date.getFullYear() === now.getFullYear() &&
        date.getMonth() === now.getMonth() &&
        date.getDate() === now.getDate()
    );
}

const monthYearFormatter = new Intl.DateTimeFormat(undefined, {
    month: 'long',
    year: 'numeric',
});

export function formatMonthLabel(month: string): string {
    const { year, monthIndex } = parseMonthParam(month);
    const date = new Date(year, monthIndex, 1);

    if (Number.isNaN(date.getTime())) {
        return monthYearFormatter.format(new Date());
    }

    return monthYearFormatter.format(date);
}

const weekdayFormatter = new Intl.DateTimeFormat(undefined, { weekday: 'short' });

export function weekdayLabels(): string[] {
    const sunday = new Date(2024, 0, 7);

    return Array.from({ length: 7 }, (_, i) =>
        weekdayFormatter.format(new Date(sunday.getFullYear(), sunday.getMonth(), sunday.getDate() + i)),
    );
}

const dayHeadingFormatter = new Intl.DateTimeFormat(undefined, {
    weekday: 'long',
    month: 'long',
    day: 'numeric',
});

export function formatDayHeading(dateKey: string): string {
    const [y, m, d] = dateKey.split('-').map(Number);

    return dayHeadingFormatter.format(new Date(y, m - 1, d));
}
