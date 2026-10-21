<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('accounts', static function (Blueprint $table): void {
            $table->foreignId('script_id')->nullable()->constrained()->nullOnDelete();
            $table->string('step')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('accounts', static function (Blueprint $table): void {
            $table->dropConstrainedForeignId('script_id');
            $table->dropColumn('step');
        });
    }
};
