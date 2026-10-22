<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('preferences', static function (Blueprint $table): void {
            $table->boolean('private_rooms')->default(true);
            $table->boolean('play_together')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('preferences', static function (Blueprint $table): void {
            $table->dropColumn(['private_rooms', 'play_together']);
        });
    }
};
