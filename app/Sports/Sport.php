<?php

namespace App\Sports;

/**
 * Everything that differs between sports lives behind this interface, so a
 * second sport is a new class rather than a schema change.
 */
interface Sport
{
    public function key(): string;

    public function label(): string;

    /** @return array<string, string> code => label */
    public function positions(): array;

    /** Validation rules for the sport_data column (positions, handedness and so on). */
    public function profileRules(): array;

    /**
     * Stat columns for one season.
     *
     * @return list<array{key: string, label: string, short: string, type: 'int'|'decimal', group: string, max: int}>
     */
    public function statFields(): array;

    /** Is this position code one that uses the goalkeeper stat group? */
    public function usesGroup(array $sportData, string $group): bool;
}
