<?php

namespace Tests\Concerns;

use App\Models\Profile;
use App\Models\ShareLink;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Testing\TestResponse;

trait MakesAthletes
{
    protected const BROWSER = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';

    protected function athlete(array $profile = [], array $user = []): User
    {
        $account = User::factory()->create($user);
        Profile::factory()->create(['user_id' => $account->id, ...$profile]);

        return $account->fresh();
    }

    protected function linkFor(User $user): ShareLink
    {
        return ShareLink::issueFor($user->profile);
    }

    /** @return array<string, mixed> */
    protected function props(TestResponse $response): array
    {
        return $response->viewData('page')['props'];
    }

    protected function asVisitor(string $ip = '203.0.113.10', string $agent = self::BROWSER): static
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->withHeader('User-Agent', $agent);
    }

    /** @return array<string, string> */
    protected function coachMessage(array $overrides = []): array
    {
        return [
            'name' => 'Casey Coach',
            'school' => 'Example College',
            'role' => 'Assistant coach',
            'email' => 'coach@example.edu',
            'message' => 'We watched your highlights and would like to talk about our programme.',
            'website' => '',
            'started' => Crypt::encryptString((string) now()->subSeconds(5)->timestamp),
            ...$overrides,
        ];
    }
}
