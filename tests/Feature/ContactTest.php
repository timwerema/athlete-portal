<?php

namespace Tests\Feature;

use App\Mail\CoachMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\MakesAthletes;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use MakesAthletes, RefreshDatabase;

    public function test_a_coach_can_send_a_message_and_the_athlete_gets_it_by_email_and_inbox(): void
    {
        Mail::fake();
        $user = $this->athlete();
        $link = $this->linkFor($user);
        $url = '/p/'.$link->token();

        // Use the "started" stamp the page itself hands out, then wait like a person would.
        $started = $this->props($this->get($url))['contact']['started'];
        $this->travel(5)->seconds();

        $this->from($url)->post($url.'/contact', $this->coachMessage(['started' => $started]))
            ->assertSessionHasNoErrors()
            ->assertRedirect($url)
            ->assertSessionHas('status', 'contact-sent');

        $message = ContactMessage::firstOrFail();
        $this->assertSame($user->profile->id, $message->profile_id);
        $this->assertSame($link->id, $message->share_link_id);
        $this->assertSame('coach@example.edu', $message->sender_email);
        $this->assertNull($message->read_at);

        $raw = DB::table('contact_messages')->first();
        $this->assertStringNotContainsString('highlights', $raw->body);
        $this->assertStringNotContainsString('coach@example.edu', $raw->sender_email);
        $this->assertObjectNotHasProperty('ip', $raw);
        $this->assertObjectNotHasProperty('ip_address', $raw);

        Mail::assertSent(CoachMessageReceived::class, function (CoachMessageReceived $mail) use ($user, $message) {
            return $mail->hasTo($user->email)
                && $mail->hasReplyTo('coach@example.edu')
                && $mail->contactMessage->is($message);
        });
        Mail::assertSentCount(1);
    }

    public function test_the_email_has_the_message_and_a_safety_note(): void
    {
        $user = $this->athlete();
        $message = $user->profile->messages()->create([
            'sender_name' => 'Casey Coach', 'sender_school' => 'Example College', 'sender_role' => 'Assistant coach',
            'sender_email' => 'coach@example.edu', 'body' => 'Hello <script>alert(1)</script> there.',
        ]);

        $mail = new CoachMessageReceived($message);

        $mail->assertSeeInHtml('Casey Coach');
        $mail->assertSeeInHtml('Example College');
        $mail->assertSeeInHtml('coach@example.edu');
        $mail->assertSeeInHtml('We do not check who sends these messages');
        $mail->assertSeeInHtml('compliance office');
        $mail->assertDontSeeInHtml('<script>alert(1)</script>', false);
        $mail->assertHasSubject('New message about your profile from Casey Coach');
    }

    public function test_the_response_to_the_coach_never_reveals_the_athletes_email(): void
    {
        Mail::fake();
        $user = $this->athlete([], ['email' => 'hidden.athlete@example.com']);
        $url = '/p/'.$this->linkFor($user)->token();

        $this->from($url)->post($url.'/contact', $this->coachMessage());
        $after = $this->get($url)->assertOk();

        $this->assertStringNotContainsString('hidden.athlete@example.com', $after->getContent());
    }

    public function test_a_filled_bot_trap_is_refused_and_nothing_is_stored_or_sent(): void
    {
        Mail::fake();
        $url = '/p/'.$this->linkFor($this->athlete())->token();

        $this->post($url.'/contact', $this->coachMessage(['website' => 'https://spam.example']))
            ->assertSessionHasErrors('website');

        $this->assertSame(0, ContactMessage::count());
        Mail::assertNothingSent();
    }

    public function test_a_form_sent_too_fast_or_too_late_is_refused(): void
    {
        Mail::fake();
        $url = '/p/'.$this->linkFor($this->athlete())->token();

        $this->post($url.'/contact', $this->coachMessage(['started' => Crypt::encryptString((string) now()->timestamp)]))
            ->assertSessionHasErrors('message');

        $this->post($url.'/contact', $this->coachMessage(['started' => Crypt::encryptString((string) now()->subDays(2)->timestamp)]))
            ->assertSessionHasErrors('message');

        $this->post($url.'/contact', $this->coachMessage(['started' => Crypt::encryptString((string) now()->addHour()->timestamp)]))
            ->assertSessionHasErrors('message');

        $this->assertSame(0, ContactMessage::count());
        Mail::assertNothingSent();
    }

    public function test_a_missing_or_forged_start_stamp_is_refused(): void
    {
        Mail::fake();
        $url = '/p/'.$this->linkFor($this->athlete())->token();

        $this->post($url.'/contact', $this->coachMessage(['started' => null]))->assertSessionHasErrors('started');
        $this->post($url.'/contact', $this->coachMessage(['started' => (string) now()->subMinute()->timestamp]))->assertSessionHasErrors('message');
        $this->post($url.'/contact', $this->coachMessage(['started' => base64_encode('{"iv":"x","value":"y","mac":"z"}')]))->assertSessionHasErrors('message');

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_contact_fields_are_validated(): void
    {
        $url = '/p/'.$this->linkFor($this->athlete())->token();

        $cases = [
            ['name' => ''], ['school' => ''], ['role' => ''],
            ['email' => 'not-an-email'], ['email' => ''],
            ['message' => 'Too short'], ['message' => str_repeat('a', 3001)],
            ['name' => str_repeat('a', 121)],
        ];

        foreach ($cases as $i => $bad) {
            $this->withServerVariables(['REMOTE_ADDR' => "192.0.2.{$i}"])
                ->post($url.'/contact', $this->coachMessage($bad))
                ->assertSessionHasErrors(array_key_first($bad));
        }

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_the_sixth_message_from_one_address_in_an_hour_is_blocked(): void
    {
        Mail::fake();
        $url = '/p/'.$this->linkFor($this->athlete())->token();

        for ($i = 0; $i < 5; $i++) {
            $this->asVisitor('203.0.113.40')->post($url.'/contact', $this->coachMessage())->assertSessionHasNoErrors();
        }

        $this->asVisitor('203.0.113.40')->post($url.'/contact', $this->coachMessage())->assertStatus(429);
        $this->assertSame(5, ContactMessage::count());

        // Someone else is not affected, and the limit lifts after the hour.
        $this->asVisitor('203.0.113.41')->post($url.'/contact', $this->coachMessage())->assertSessionHasNoErrors();
        $this->travel(61)->minutes();
        $this->asVisitor('203.0.113.40')->post($url.'/contact', $this->coachMessage())->assertSessionHasNoErrors();
        $this->assertSame(7, ContactMessage::count());
    }

    public function test_one_link_accepts_at_most_thirty_messages_a_day_from_everyone_together(): void
    {
        Mail::fake();
        $url = '/p/'.$this->linkFor($this->athlete())->token();

        for ($i = 0; $i < 30; $i++) {
            $this->asVisitor("198.51.100.{$i}")->post($url.'/contact', $this->coachMessage())->assertSessionHasNoErrors();
        }

        $this->asVisitor('198.51.100.200')->post($url.'/contact', $this->coachMessage())->assertStatus(429);
        $this->assertSame(30, ContactMessage::count());
    }

    public function test_a_revoked_or_unknown_link_takes_no_messages(): void
    {
        Mail::fake();
        $user = $this->athlete();
        $link = $this->linkFor($user);
        $this->actingAs($user)->delete('/sharing/link');
        $this->post('/logout');

        $this->post('/p/'.$link->token().'/contact', $this->coachMessage())->assertNotFound();
        $this->post('/p/'.str_repeat('a', 43).'/contact', $this->coachMessage())->assertNotFound();

        $this->assertSame(0, ContactMessage::count());
        Mail::assertNothingSent();
    }

    public function test_a_mail_failure_does_not_lose_the_message(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('mail server down'));
        $url = '/p/'.$this->linkFor($this->athlete())->token();

        $this->post($url.'/contact', $this->coachMessage())->assertSessionHasNoErrors()->assertSessionHas('status', 'contact-sent');

        $this->assertSame(1, ContactMessage::count());
    }

    public function test_inbox_lists_messages_marks_them_read_and_deletes_them(): void
    {
        Mail::fake();
        $user = $this->athlete();
        $url = '/p/'.$this->linkFor($user)->token();
        $this->post($url.'/contact', $this->coachMessage());
        $message = ContactMessage::firstOrFail();

        $this->actingAs($user);

        $inbox = $this->get('/inbox')->assertOk();
        $rows = $this->props($inbox)['messages'];
        $this->assertCount(1, $rows);
        $this->assertSame('Casey Coach', $rows[0]['sender_name']);
        $this->assertFalse($rows[0]['read']);
        $this->assertArrayNotHasKey('sender_email', $rows[0]);
        $this->assertSame(1, $this->props($inbox)['unread']);
        $this->assertSame(1, $this->props($this->get('/dashboard'))['unreadMessages']);

        $show = $this->get("/inbox/{$message->id}")->assertOk();
        $this->assertSame('coach@example.edu', $this->props($show)['message']['sender_email']);
        $this->assertStringContainsString('highlights', $this->props($show)['message']['body']);
        $this->assertNotNull($message->fresh()->read_at);
        $this->assertSame(0, $this->props($this->get('/inbox'))['unread']);

        $this->delete("/inbox/{$message->id}")->assertRedirect(route('inbox.index'));
        $this->assertSame(0, ContactMessage::count());
        $this->get("/inbox/{$message->id}")->assertNotFound();
    }

    public function test_messages_survive_when_their_link_is_replaced(): void
    {
        Mail::fake();
        $user = $this->athlete();
        $url = '/p/'.$this->linkFor($user)->token();
        $this->post($url.'/contact', $this->coachMessage());

        $this->actingAs($user)->post('/sharing/link');

        $this->assertSame(1, $user->profile->messages()->count());
    }
}
