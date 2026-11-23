<?php

namespace App\Sports;

use Illuminate\Validation\Rule;

class Soccer implements Sport
{
    public const FEET = ['left' => 'Left', 'right' => 'Right', 'both' => 'Both'];

    public function key(): string
    {
        return 'soccer';
    }

    public function label(): string
    {
        return 'Soccer';
    }

    public function positions(): array
    {
        return [
            'GK' => 'Goalkeeper',
            'CB' => 'Centre back',
            'LB' => 'Left back',
            'RB' => 'Right back',
            'LWB' => 'Left wing back',
            'RWB' => 'Right wing back',
            'CDM' => 'Defensive midfielder',
            'CM' => 'Central midfielder',
            'CAM' => 'Attacking midfielder',
            'LM' => 'Left midfielder',
            'RM' => 'Right midfielder',
            'LW' => 'Left winger',
            'RW' => 'Right winger',
            'ST' => 'Striker',
        ];
    }

    public function profileRules(): array
    {
        return [
            'positions' => ['nullable', 'array', 'max:3'],
            'positions.*' => ['string', 'distinct', Rule::in(array_keys($this->positions()))],
            'preferred_foot' => ['nullable', Rule::in(array_keys(self::FEET))],
        ];
    }

    public function statFields(): array
    {
        return [
            ['key' => 'appearances', 'label' => 'Appearances', 'short' => 'Apps', 'type' => 'int', 'group' => 'all', 'max' => 100],
            ['key' => 'starts', 'label' => 'Starts', 'short' => 'Starts', 'type' => 'int', 'group' => 'all', 'max' => 100],
            ['key' => 'minutes', 'label' => 'Minutes', 'short' => 'Min', 'type' => 'int', 'group' => 'all', 'max' => 12000],
            ['key' => 'goals', 'label' => 'Goals', 'short' => 'G', 'type' => 'int', 'group' => 'all', 'max' => 200],
            ['key' => 'assists', 'label' => 'Assists', 'short' => 'A', 'type' => 'int', 'group' => 'all', 'max' => 200],
            ['key' => 'saves', 'label' => 'Saves', 'short' => 'Sv', 'type' => 'int', 'group' => 'goalkeeper', 'max' => 1000],
            ['key' => 'clean_sheets', 'label' => 'Clean sheets', 'short' => 'CS', 'type' => 'int', 'group' => 'goalkeeper', 'max' => 100],
            ['key' => 'goals_against_average', 'label' => 'Goals-against average', 'short' => 'GAA', 'type' => 'decimal', 'group' => 'goalkeeper', 'max' => 20],
        ];
    }

    public function usesGroup(array $sportData, string $group): bool
    {
        if ($group === 'all') {
            return true;
        }

        return $group === 'goalkeeper' && in_array('GK', $sportData['positions'] ?? [], true);
    }
}
