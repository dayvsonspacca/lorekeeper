<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('preferences', static function (Blueprint $table): void {
            $table->id();
            $table->boolean('block_goto')->default(true);
            $table->boolean('block_whispers')->default(true);
            $table->boolean('block_friend_requests')->default(true);
            $table->boolean('block_party_invites')->default(true);
            $table->boolean('block_guild_invites')->default(true);
            $table->boolean('block_duels')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preferences');
    }
};
