<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Protect assessment_definitions
        DB::unprepared("
            CREATE OR REPLACE FUNCTION prevent_assessment_definition_mutation()
            RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'Assessment definitions are immutable. Updates and Deletions are forbidden.';
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER enforce_assessment_definition_immutability
            BEFORE UPDATE OR DELETE ON assessment_definitions
            FOR EACH ROW
            EXECUTE FUNCTION prevent_assessment_definition_mutation();
        ");

        // Protect assessment_results
        DB::unprepared("
            CREATE OR REPLACE FUNCTION prevent_assessment_result_mutation()
            RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'Assessment results are immutable. Updates and Deletions are forbidden.';
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER enforce_assessment_result_immutability
            BEFORE UPDATE OR DELETE ON assessment_results
            FOR EACH ROW
            EXECUTE FUNCTION prevent_assessment_result_mutation();
        ");

        // Protect terminal assessment_attempts
        DB::unprepared("
            CREATE OR REPLACE FUNCTION prevent_terminal_assessment_attempt_mutation()
            RETURNS trigger AS $$
            BEGIN
                IF TG_OP = 'DELETE' THEN
                    RAISE EXCEPTION 'Assessment attempts cannot be deleted.';
                END IF;
                IF TG_OP = 'UPDATE' AND OLD.status = 'submitted' THEN
                    RAISE EXCEPTION 'Submitted assessment attempts are terminal and immutable.';
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER enforce_terminal_assessment_attempt_immutability
            BEFORE UPDATE OR DELETE ON assessment_attempts
            FOR EACH ROW
            EXECUTE FUNCTION prevent_terminal_assessment_attempt_mutation();
        ");
    }

    public function down(): void
    {
        DB::unprepared("DROP TRIGGER IF EXISTS enforce_terminal_assessment_attempt_immutability ON assessment_attempts");
        DB::unprepared("DROP FUNCTION IF EXISTS prevent_terminal_assessment_attempt_mutation()");

        DB::unprepared("DROP TRIGGER IF EXISTS enforce_assessment_result_immutability ON assessment_results");
        DB::unprepared("DROP FUNCTION IF EXISTS prevent_assessment_result_mutation()");

        DB::unprepared("DROP TRIGGER IF EXISTS enforce_assessment_definition_immutability ON assessment_definitions");
        DB::unprepared("DROP FUNCTION IF EXISTS prevent_assessment_definition_mutation()");
    }
};
