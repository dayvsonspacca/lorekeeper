<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('items', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('aqw_id')->unique();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('item_tags', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->string('tag', 32);
            $table->timestamp('created_at')->nullable();

            $table->unique(['item_id', 'tag']);
        });

        Schema::create('armors', static function (Blueprint $table): void {
            $table->foreignId('item_id')->primary()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('weapons', static function (Blueprint $table): void {
            $table->foreignId('item_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('type', 16)->nullable();
            $table->unsignedTinyInteger('damage_variance')->nullable();
            $table->timestamps();
        });

        Schema::create('helms', static function (Blueprint $table): void {
            $table->foreignId('item_id')->primary()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('capes', static function (Blueprint $table): void {
            $table->foreignId('item_id')->primary()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('pets', static function (Blueprint $table): void {
            $table->foreignId('item_id')->primary()->constrained()->cascadeOnDelete();
            $table->boolean('combat')->nullable();
            $table->timestamps();
        });

        Schema::create('ground_items', static function (Blueprint $table): void {
            $table->foreignId('item_id')->primary()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('class_items', static function (Blueprint $table): void {
            $table->foreignId('item_id')->primary()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('house_items', static function (Blueprint $table): void {
            $table->foreignId('item_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('type', 16)->nullable();
            $table->timestamps();
        });

        Schema::create('boosts', static function (Blueprint $table): void {
            $table->foreignId('item_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('stack')->nullable();
            $table->timestamps();
        });

        Schema::create('resources', static function (Blueprint $table): void {
            $table->foreignId('item_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('stack')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resources');
        Schema::dropIfExists('boosts');
        Schema::dropIfExists('house_items');
        Schema::dropIfExists('class_items');
        Schema::dropIfExists('ground_items');
        Schema::dropIfExists('pets');
        Schema::dropIfExists('capes');
        Schema::dropIfExists('helms');
        Schema::dropIfExists('weapons');
        Schema::dropIfExists('armors');
        Schema::dropIfExists('item_tags');
        Schema::dropIfExists('items');
    }
};
