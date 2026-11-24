<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ORDERED = ['profile_teams', 'honours', 'videos', 'profile_references'];

    public function up(): void
    {
        foreach (self::ORDERED as $name) {
            Schema::table($name, function (Blueprint $table) {
                // The athlete's own order for this list. Smaller comes first.
                $table->unsignedInteger('position')->default(0);
            });

            DB::table($name)->update(['position' => DB::raw('id')]);
        }

        Schema::table('users', function (Blueprint $table) {
            // A new email address waiting to be confirmed from that address.
            $table->string('pending_email')->nullable();
        });
    }

    public function down(): void
    {
        foreach (self::ORDERED as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropColumn('position');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('pending_email');
        });
    }
};
