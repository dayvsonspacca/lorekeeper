<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('accounts', static function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->text('password');
            $table->string('server')->nullable();
            $table->unsignedTinyInteger('level')->nullable();
            $table->string('status')->default('offline')->index();
            $table->unsignedInteger('pid')->nullable();
            $table->string('result')->nullable();
            $table->string('map')->nullable();
            $table->json('scene')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->timestamps();
        });

        Schema::create('bot_logs', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('account_id')->index()->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->string('level')->default('info');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_logs');
        Schema::dropIfExists('accounts');
    }
};
