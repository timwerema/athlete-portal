/** Small pure helpers used by forms and the coach page. Covered by format.test.ts. */

export interface FeetInches {
    feet: number;
    inches: number;
}

export function cmToFeetInches(cm: number): FeetInches {
    const totalInches = Math.round(cm / 2.54);

    return { feet: Math.floor(totalInches / 12), inches: totalInches % 12 };
}

export function feetInchesToCm(feet: number, inches: number): number {
    return Math.round((feet * 12 + inches) * 2.54);
}

/** "5′7″ (170 cm)" */
export function formatHeight(cm: number | null | undefined): string | null {
    if (!cm) {
        return null;
    }

    const { feet, inches } = cmToFeetInches(cm);

    return `${feet}′${inches}″ (${cm} cm)`;
}

/** A missing stat is shown as a dash, never as zero: "not entered" is not the same as "none". */
export function formatStat(value: number | null | undefined, key?: string): string {
    if (value === null || value === undefined) {
        return '–';
    }

    if (key === 'goals_against_average') {
        return value.toFixed(2);
    }

    return value.toLocaleString('en-US');
}

/** "2021–2025", "2025–present", "2024" */
export function formatYears(start: number | null, end: number | null): string {
    if (start && end) {
        return start === end ? String(start) : `${start}–${end}`;
    }

    if (start) {
        return `${start}–present`;
    }

    return end ? `until ${end}` : '';
}

export function formatDate(iso: string | null | undefined): string {
    if (!iso) {
        return '';
    }

    const date = new Date(iso.length === 10 ? `${iso}T12:00:00` : iso);

    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

export function initials(name: string): string {
    return name
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}
