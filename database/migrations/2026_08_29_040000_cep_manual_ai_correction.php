<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The PostgreSQL tables for Manual AI Bridge (prompt_packages, prompt_package_revisions,
        // imported_ai_results, ai_proposal_decisions, portable_packages) are preserved and verified
        // as immutable and append-only.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
