<?php

declare(strict_types=1);

use App\Enums\RecurrentFrequency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', static function (Blueprint $table) {
            $table->id();
            $table->string('title', 50);
            $table->string('description')->nullable();
            $table->dateTimeTz('starts_at');
            $table->dateTimeTz('ends_at');
            $table->boolean('recurrent');
            $table->enum('frequency', RecurrentFrequency::values())->nullable();
            $table->dateTimeTz('repeat_until')->nullable();
            $table->timestamps();

            $table->foreignId('parent_id')
                ->nullable()
                ->index()
                ->constrained('events')
                ->cascadeOnDelete();

            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
