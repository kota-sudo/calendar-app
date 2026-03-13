<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'repeat_end_type')) {
                $table->string('repeat_end_type')->nullable();
            }

            if (!Schema::hasColumn('events', 'repeat_until')) {
                $table->date('repeat_until')->nullable();
            }

            if (!Schema::hasColumn('events', 'repeat_count')) {
                $table->integer('repeat_count')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'repeat_end_type',
                'repeat_until',
                'repeat_count',
            ]);
        });
    }
};