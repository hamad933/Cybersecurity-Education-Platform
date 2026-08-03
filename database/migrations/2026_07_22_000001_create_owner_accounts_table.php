<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_accounts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('display_name', 120);
            $table->string('email', 320)->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestampTz('last_login_at')->nullable();
            $table->timestampsTz();
        });

        DB::statement('CREATE UNIQUE INDEX owner_accounts_single_active ON owner_accounts ((is_active)) WHERE is_active = TRUE');
        DB::statement('ALTER TABLE owner_accounts ADD CONSTRAINT owner_email_normalized CHECK (email = lower(email))');
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_accounts');
    }
};
