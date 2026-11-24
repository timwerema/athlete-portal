<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesAthletes;
use Tests\TestCase;

class ReorderTest extends TestCase
{
    use MakesAthletes, RefreshDatabase;

    private function names($user): array
    {
        return $user->profile->teams()->pluck('name')->all();
    }

    private function addTeams($user, array $names): void
    {
        foreach ($names as $name) {
            $this->actingAs($user)->post('/profile/items/teams', ['kind' => 'club', 'name' => $name])->assertSessionHasNoErrors();
        }
    }

    public function test_new_entries_go_to_the_end_in_the_order_added(): void
    {
        $user = $this->athlete();
        $this->addTeams($user, ['A', 'B', 'C']);

        $this->assertSame(['A', 'B', 'C'], $this->names($user));
    }

    public function test_an_entry_can_be_moved_up_and_down(): void
    {
        $user = $this->athlete();
        $this->addTeams($user, ['A', 'B', 'C']);
        $c = $user->profile->teams()->where('name', 'C')->first();

        $this->actingAs($user)->post("/profile/items/teams/{$c->id}/move", ['direction' => 'up'])->assertSessionHasNoErrors();
        $this->assertSame(['A', 'C', 'B'], $this->names($user));

        $this->actingAs($user)->post("/profile/items/teams/{$c->id}/move", ['direction' => 'up']);
        $this->assertSame(['C', 'A', 'B'], $this->names($user));

        // already first: nothing changes
        $this->actingAs($user)->post("/profile/items/teams/{$c->id}/move", ['direction' => 'up'])->assertSessionHasNoErrors();
        $this->assertSame(['C', 'A', 'B'], $this->names($user));

        $this->actingAs($user)->post("/profile/items/teams/{$c->id}/move", ['direction' => 'down']);
        $this->assertSame(['A', 'C', 'B'], $this->names($user));

        $this->assertSame([1, 2, 3], $user->profile->teams()->pluck('position')->all());
    }

    public function test_the_coach_page_and_editor_follow_the_chosen_order(): void
    {
        $user = $this->athlete();
        $this->addTeams($user, ['A', 'B']);
        $b = $user->profile->teams()->where('name', 'B')->first();
        $this->actingAs($user)->post("/profile/items/teams/{$b->id}/move", ['direction' => 'up']);

        $coach = $this->props($this->actingAs($user)->get('/profile/preview'));
        $this->assertSame(['B', 'A'], array_column($coach['profile']['teams'], 'name'));

        $editor = $this->props($this->actingAs($user)->get('/profile/history'));
        $this->assertSame(['B', 'A'], collect($editor['profile']['teams'])->pluck('name')->all());
    }

    public function test_order_still_works_after_an_entry_is_removed(): void
    {
        $user = $this->athlete();
        $this->addTeams($user, ['A', 'B', 'C']);
        $b = $user->profile->teams()->where('name', 'B')->first();
        $this->actingAs($user)->delete("/profile/items/teams/{$b->id}");
        $this->addTeams($user, ['D']);

        $this->assertSame(['A', 'C', 'D'], $this->names($user));
    }

    public function test_honours_videos_and_references_can_be_reordered_but_stats_cannot(): void
    {
        $user = $this->athlete();
        $this->actingAs($user)->post('/profile/items/honours', ['title' => 'First']);
        $this->actingAs($user)->post('/profile/items/honours', ['title' => 'Second']);
        $second = $user->profile->honours()->where('title', 'Second')->first();
        $this->actingAs($user)->post("/profile/items/honours/{$second->id}/move", ['direction' => 'up']);
        $this->assertSame(['Second', 'First'], $user->profile->honours()->pluck('title')->all());

        $this->actingAs($user)->post('/profile/items/videos', ['kind' => 'highlight', 'title' => 'V1', 'url' => 'https://example.com/1']);
        $this->actingAs($user)->post('/profile/items/videos', ['kind' => 'full_match', 'title' => 'V2', 'url' => 'https://example.com/2']);
        $v2 = $user->profile->videos()->where('title', 'V2')->first();
        $this->actingAs($user)->post("/profile/items/videos/{$v2->id}/move", ['direction' => 'up']);
        $this->assertSame(['V2', 'V1'], $user->profile->videos()->pluck('title')->all());

        $this->actingAs($user)->post('/profile/items/stats', ['season' => '2025', 'team' => 'T']);
        $stat = $user->profile->seasonStats()->first();
        $this->actingAs($user)->post("/profile/items/stats/{$stat->id}/move", ['direction' => 'up'])->assertNotFound();
    }

    public function test_bad_directions_are_refused_and_position_cannot_be_set_directly(): void
    {
        $user = $this->athlete();
        $this->addTeams($user, ['A', 'B']);
        $a = $user->profile->teams()->where('name', 'A')->first();

        $this->actingAs($user)->post("/profile/items/teams/{$a->id}/move", ['direction' => 'sideways'])->assertSessionHasErrors('direction');
        $this->actingAs($user)->put("/profile/items/teams/{$a->id}", ['kind' => 'club', 'name' => 'A', 'position' => 99])->assertSessionHasNoErrors();

        $this->assertSame(['A', 'B'], $this->names($user));
    }

    public function test_one_athlete_cannot_move_anothers_entries_and_guests_cannot_move_anything(): void
    {
        $owner = $this->athlete();
        $this->addTeams($owner, ['A', 'B']);
        $b = $owner->profile->teams()->where('name', 'B')->first();
        $intruder = $this->athlete();

        $this->actingAs($intruder)->post("/profile/items/teams/{$b->id}/move", ['direction' => 'up'])->assertNotFound();
        $this->assertSame(['A', 'B'], $this->names($owner));

        auth()->logout();
        $this->post("/profile/items/teams/{$b->id}/move", ['direction' => 'up'])->assertRedirect('/login');
    }
}
