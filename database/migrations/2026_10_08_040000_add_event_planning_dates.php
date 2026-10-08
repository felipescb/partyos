<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->date('planning_starts_at')->nullable()->after('status');
            $table->unsignedInteger('duration_minutes')->nullable()->after('ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['planning_starts_at', 'duration_minutes']);
        });
    }
};
