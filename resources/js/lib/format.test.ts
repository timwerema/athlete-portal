import { describe, expect, it } from 'vitest';
import { readAppearance, resolvesToDark } from '../hooks/use-appearance-logic';
import { cmToFeetInches, feetInchesToCm, formatDate, formatHeight, formatStat, formatYears, initials } from './format';

describe('height', () => {
    it('converts centimetres to feet and inches', () => {
        expect(cmToFeetInches(170)).toEqual({ feet: 5, inches: 7 });
        expect(cmToFeetInches(183)).toEqual({ feet: 6, inches: 0 });
        expect(cmToFeetInches(152)).toEqual({ feet: 5, inches: 0 });
    });

    it('never produces 12 inches', () => {
        for (let cm = 120; cm <= 245; cm++) {
            expect(cmToFeetInches(cm).inches).toBeLessThan(12);
        }
    });

    it('round-trips every feet and inches choice the form offers', () => {
        for (let feet = 4; feet <= 7; feet++) {
            for (let inches = 0; inches < 12; inches++) {
                expect(cmToFeetInches(feetInchesToCm(feet, inches))).toEqual({ feet, inches });
            }
        }
    });

    it('keeps every choice inside the range the server accepts', () => {
        expect(feetInchesToCm(4, 0)).toBeGreaterThanOrEqual(120);
        expect(feetInchesToCm(7, 11)).toBeLessThanOrEqual(245);
    });

    it('formats for display', () => {
        expect(formatHeight(170)).toBe('5′7″ (170 cm)');
        expect(formatHeight(null)).toBeNull();
    });
});

describe('stats', () => {
    it('shows a dash for a missing number, never zero', () => {
        expect(formatStat(undefined)).toBe('–');
        expect(formatStat(null)).toBe('–');
        expect(formatStat(0)).toBe('0');
    });

    it('formats thousands and goals-against average', () => {
        expect(formatStat(1462)).toBe('1,462');
        expect(formatStat(0.9, 'goals_against_average')).toBe('0.90');
    });
});

describe('years and dates', () => {
    it('formats ranges', () => {
        expect(formatYears(2021, 2025)).toBe('2021–2025');
        expect(formatYears(2025, null)).toBe('2025–present');
        expect(formatYears(2024, 2024)).toBe('2024');
        expect(formatYears(null, 2024)).toBe('until 2024');
        expect(formatYears(null, null)).toBe('');
    });

    it('does not shift a plain date by a day', () => {
        expect(formatDate('2026-11-23')).toBe('Nov 23, 2026');
        expect(formatDate(null)).toBe('');
    });
});

describe('initials', () => {
    it('takes up to two', () => {
        expect(initials('Jordan Avery')).toBe('JA');
        expect(initials('  madonna ')).toBe('M');
        expect(initials('Ana Maria de Souza')).toBe('AM');
        expect(initials('')).toBe('');
    });
});

describe('appearance', () => {
    it('defaults to dark', () => {
        expect(readAppearance('')).toBe('dark');
        expect(readAppearance('other=1')).toBe('dark');
        expect(readAppearance('appearance=bogus')).toBe('dark');
    });

    it('reads the saved choice', () => {
        expect(readAppearance('a=1; appearance=light; b=2')).toBe('light');
        expect(readAppearance('appearance=system')).toBe('system');
    });

    it('resolves system to the device setting', () => {
        expect(resolvesToDark('system', true)).toBe(true);
        expect(resolvesToDark('system', false)).toBe(false);
        expect(resolvesToDark('light', true)).toBe(false);
        expect(resolvesToDark('dark', false)).toBe(true);
    });
});
