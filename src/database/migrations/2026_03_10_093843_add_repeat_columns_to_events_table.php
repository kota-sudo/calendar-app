<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'repeat_weekdays')) {
                $table->json('repeat_weekdays')->nullable();
            }

            if (!Schema::hasColumn('events', 'repeat_month_mode')) {
                $table->string('repeat_month_mode')->nullable();
            }

            if (!Schema::hasColumn('events', 'repeat_month_nth')) {
                $table->string('repeat_month_nth')->nullable();
            }

            if (!Schema::hasColumn('events', 'repeat_month_weekday')) {
                $table->string('repeat_month_weekday')->nullable();
            }

            if (!Schema::hasColumn('events', 'repeat_end_type')) {
                $table->string('repeat_end_type')->default('never');
            }

            if (!Schema::hasColumn('events', 'repeat_count')) {
                $table->unsignedInteger('repeat_count')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $dropColumns = [];

            foreach ([
                'repeat_weekdays',
                'repeat_month_mode',
                'repeat_month_nth',
                'repeat_month_weekday',
                'repeat_end_type',
                'repeat_count',
            ] as $column) {
                if (Schema::hasColumn('events', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};