<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('houses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon')->default('🏠');
            $table->string('invite_code', 10)->unique();
            $table->foreignId('created_by')->constrained('users');
            $table->enum('status', ['waiting', 'setup', 'active'])->default('waiting');
            $table->date('start_date')->nullable();
            $table->timestamp('game_started_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('houses');
    }
};
