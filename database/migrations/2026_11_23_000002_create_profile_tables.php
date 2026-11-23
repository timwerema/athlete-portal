<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('sport', 32)->default('soccer');
            $table->boolean('is_demo')->default(false);

            $table->string('display_name', 120)->nullable();
            $table->string('pronouns', 40)->nullable();
            $table->string('photo_path')->nullable();
            $table->unsignedSmallInteger('height_cm')->nullable();

            $table->string('current_school', 160)->nullable();
            $table->string('division', 40)->nullable();
            $table->string('conference', 120)->nullable();
            $table->string('class_year', 40)->nullable();
            $table->unsignedTinyInteger('eligibility_years_left')->nullable();
            $table->date('portal_entered_on')->nullable();

            // Sport-specific answers (for soccer: positions and preferred foot).
            $table->json('sport_data')->nullable();

            $table->string('major', 160)->nullable();
            $table->decimal('gpa', 3, 2)->nullable();
            $table->string('gpa_scale', 10)->nullable();

            $table->json('looking_for')->nullable();
            $table->text('statement')->nullable();

            // Section keys the athlete has chosen to hide from the shared page.
            $table->json('hidden_sections')->nullable();

            $table->timestamps();
        });

        Schema::create('profile_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('name', 160);
            $table->string('detail', 160)->nullable();
            $table->unsignedSmallInteger('start_year')->nullable();
            $table->unsignedSmallInteger('end_year')->nullable();
            $table->timestamps();
        });

        Schema::create('season_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('season', 20);
            $table->string('team', 160);
            $table->string('competition', 120)->nullable();
            // Keys come from the sport definition, so a new sport needs no schema change.
            $table->json('stats');
            $table->timestamps();
        });

        Schema::create('honours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('title', 200);
            $table->unsignedSmallInteger('year')->nullable();
            $table->timestamps();
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('title', 160);
            $table->string('url', 500);
            $table->timestamps();
        });

        Schema::create('profile_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('role', 120);
            $table->string('organization', 160)->nullable();
            $table->text('contact'); // encrypted
            $table->timestamp('consent_confirmed_at');
            $table->timestamps();
        });

        Schema::create('share_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->text('token_encrypted');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('profile_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('share_link_id')->constrained()->cascadeOnDelete();
            $table->date('viewed_on');
            // A keyed hash that changes every day; it cannot be turned back into an IP address.
            $table->string('visitor_hash', 64);
            $table->timestamp('created_at')->nullable();
            $table->unique(['share_link_id', 'viewed_on', 'visitor_hash']);
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('share_link_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sender_name', 120);
            $table->string('sender_school', 160);
            $table->string('sender_role', 120);
            $table->text('sender_email'); // encrypted
            $table->text('body'); // encrypted
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'contact_messages', 'profile_views', 'share_links', 'profile_references',
            'videos', 'honours', 'season_stats', 'profile_teams', 'profiles',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
