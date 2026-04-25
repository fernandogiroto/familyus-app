<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('task_completions', function (Blueprint $table) {
            $table->date('completion_date')->nullable()->after('week_start');
        });
    }

    public function down(): void
    {
        Schema::table('task_completions', function (Blueprint $table) {
            $table->dropColumn('completion_date');
        });
    }
};
