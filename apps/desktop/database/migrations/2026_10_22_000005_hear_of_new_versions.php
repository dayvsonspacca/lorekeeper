<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('preferences', static function (Blueprint $table): void {
            $table->boolean('notify_updates')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('preferences', static function (Blueprint $table): void {
            $table->dropColumn('notify_updates');
        });
    }
};
