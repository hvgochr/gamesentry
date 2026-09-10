<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('watched_players', function (Blueprint $table) {
            $table->unsignedInteger('profile_icon_id')->nullable()->after('riot_puuid');
            $table->timestamp('profile_refreshed_at')->nullable()->after('profile_icon_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('watched_players', function (Blueprint $table) {
            $table->dropColumn(['profile_icon_id', 'profile_refreshed_at']);
        });
    }
};
