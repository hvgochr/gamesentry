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
        Schema::create('tracked_players', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(DiscordServer::class)->constrained()->cascadeOnDelete();
            $table->string('game', 40);
            $table->string('riot_name', 32);
            $table->string('riot_tagline', 10);
            $table->string('puuid', 78)->nullable();
            $table->string('region', 32);
            $table->string('routing_region', 16)->nullable();
            $table->string('summoner_id')->nullable();
            $table->string('discord_user_id', 40);
            $table->string('last_seen_match_id', 64)->nullable();
            $table->timestamp('last_polled_at')->nullable();
            $table->timestamp('riot_synced_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['discord_server_id', 'game', 'riot_name', 'riot_tagline']);
            $table->unique(['discord_server_id', 'game', 'puuid']);
            $table->index('puuid');
            $table->index(['game', 'routing_region', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracked_players');
    }
};
