<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bot_tasks', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bot_run_id')->constrained()->cascadeOnDelete();
            $table->string('script');
            $table->json('parameters');
            $table->string('status')->index();
            $table->string('result')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        DB::table('bot_runs')->where('status', 'finished')->update(['status' => 'stopped']);

        Schema::table('bot_runs', static function (Blueprint $table): void {
            $table->dropColumn(['script', 'parameters']);
            $table->string('map')->nullable();
        });

        Schema::table('bot_run_logs', static function (Blueprint $table): void {
            $table->foreignId('bot_task_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bot_run_logs', static function (Blueprint $table): void {
            $table->dropConstrainedForeignId('bot_task_id');
        });

        Schema::table('bot_runs', static function (Blueprint $table): void {
            $table->dropColumn('map');
            $table->string('script')->default('go-to-map');
            $table->json('parameters')->default('{}');
        });

        Schema::dropIfExists('bot_tasks');
    }
};
