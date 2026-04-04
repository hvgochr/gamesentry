<?php

use App\Models\DiscordServer;
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
        Schema::create('watched_players', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(DiscordServer::class)->constrained()->cascadeOnDelete();
            $table->string('game', 16);
            $table->string('routing_region', 16);
            $table->string('game_name', 64);
            $table->string('tag_line', 16);
            $table->string('riot_puuid', 128);
            $table->string('discord_user_id', 32);
            $table->string('last_seen_match_id')->nullable();
            $table->timestamp('last_polled_at')->nullable();
            $table->timestamp('next_poll_at')->nullable();
            $table->unsignedInteger('poll_interval_seconds')->default(300);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['discord_server_id', 'game', 'riot_puuid']);
            $table->index(['game', 'next_poll_at']);
            $table->index('discord_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('watched_players');
    }
};
