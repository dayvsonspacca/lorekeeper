<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bot_tasks', static function (Blueprint $table): void {
            $table->unsignedTinyInteger('level_to')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bot_tasks', static function (Blueprint $table): void {
            $table->dropColumn('level_to');
        });
    }
};
