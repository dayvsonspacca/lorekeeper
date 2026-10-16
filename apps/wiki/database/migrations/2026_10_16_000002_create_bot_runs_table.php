<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bot_runs', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('script');
            $table->json('parameters');
            $table->string('status')->index();
            $table->unsignedInteger('pid')->nullable();
            $table->string('result')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('bot_run_logs', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bot_run_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_run_logs');
        Schema::dropIfExists('bot_runs');
    }
};
