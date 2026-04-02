<?php

use App\Models\User;
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
        Schema::create('discord_servers', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('discord_guild_id', 32)->unique();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->string('discord_channel_id', 32);
            $table->string('discord_channel_name');
            $table->timestamp('bot_installed_at')->nullable();
            $table->timestamp('settings_synced_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discord_servers');
    }
};
