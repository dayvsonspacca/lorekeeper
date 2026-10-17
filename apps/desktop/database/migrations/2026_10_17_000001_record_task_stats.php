<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('accounts', static function (Blueprint $table): void {
            $table->unsignedTinyInteger('level')->nullable();
        });

        Schema::table('bot_tasks', static function (Blueprint $table): void {
            $table->unsignedInteger('kills')->default(0);
            $table->unsignedInteger('deaths')->default(0);
            $table->unsignedBigInteger('experience')->default(0);
            $table->unsignedBigInteger('gold')->default(0);
            $table->unsignedTinyInteger('level_from')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bot_tasks', static function (Blueprint $table): void {
            $table->dropColumn(['kills', 'deaths', 'experience', 'gold', 'level_from']);
        });

        Schema::table('accounts', static function (Blueprint $table): void {
            $table->dropColumn('level');
        });
    }
};
