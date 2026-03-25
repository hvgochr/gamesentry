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
            $table->string('name');
            $table->string('discord_guild_id', 40);
            $table->string('alert_channel_name')->nullable();
            $table->string('alert_channel_id', 40)->nullable();
            $table->boolean('roast_enabled')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'discord_guild_id']);
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
