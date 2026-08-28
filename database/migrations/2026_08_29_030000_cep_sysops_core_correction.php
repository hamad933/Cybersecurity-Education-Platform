<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('processing_runs', function (Blueprint $table): void {
            if (!Schema::hasColumn('processing_runs', 'safe_error_message')) {
                $table->string('safe_error_message', 500)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('processing_runs', function (Blueprint $table): void {
            if (Schema::hasColumn('processing_runs', 'safe_error_message')) {
                $table->dropColumn('safe_error_message');
            }
        });
    }
};
