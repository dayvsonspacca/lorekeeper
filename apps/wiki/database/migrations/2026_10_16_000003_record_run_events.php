<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bot_run_logs', static function (Blueprint $table): void {
            $table->string('level')->default('info');
        });

        Schema::create('bot_run_events', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bot_run_id')->constrained()->cascadeOnDelete();
            $table->string('name')->index();
            $table->text('summary');
            $table->longText('raw')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_run_events');

        Schema::table('bot_run_logs', static function (Blueprint $table): void {
            $table->dropColumn('level');
        });
    }
};
