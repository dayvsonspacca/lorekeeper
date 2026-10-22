<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const array COLUMNS = ['private_rooms', 'play_together'];

    public function up(): void
    {
        $missing = array_values(array_filter(
            self::COLUMNS,
            static fn(string $column): bool => !Schema::hasColumn('preferences', $column),
        ));

        Schema::table('preferences', static function (Blueprint $table) use ($missing): void {
            foreach ($missing as $column) {
                $table->boolean($column)->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('preferences', static function (Blueprint $table): void {
            $table->dropColumn(self::COLUMNS);
        });
    }
};
