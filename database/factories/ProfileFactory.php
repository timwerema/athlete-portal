<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'display_name' => fake()->name(),
            'current_school' => 'Test College',
            'division' => 'NAIA',
            'class_year' => 'Junior',
            'sport_data' => ['positions' => ['CM'], 'preferred_foot' => 'right'],
        ];
    }
}
