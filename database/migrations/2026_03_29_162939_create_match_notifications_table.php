<?php

use App\Models\DiscordServer;
use App\Models\WatchedPlayer;
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
        Schema::create('match_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(WatchedPlayer::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(DiscordServer::class)->constrained()->cascadeOnDelete();
            $table->string('game', 16);
            $table->string('riot_match_id');
            $table->json('match_payload')->nullable();
            $table->json('discord_embed_payload')->nullable();
            $table->text('roast_text')->nullable();
            $table->string('status', 16)->default('pending');
            $table->text('failure_reason')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->unique(['watched_player_id', 'riot_match_id']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_notifications');
    }
};
