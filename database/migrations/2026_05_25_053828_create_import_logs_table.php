<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('import_logs', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger(
                'import_type_id'
            );

            $table->unsignedInteger(
                'status_id'
            );

            $table->unsignedInteger(
                'data_source_id'
            )->nullable();

            $table->integer(
                'run_time'
            )->default(0);

            $table->integer(
                'rows_processed'
            )->default(0);

            $table->integer(
                'records_created'
            )->default(0);

            $table->integer(
                'records_updated'
            )->default(0);

            $table->integer(
                'duplicate_rows'
            )->default(0);

            $table->integer(
                'failure_count'
            )->default(0);

            $table->longText(
                'generated_markdown'
            )->nullable();

            $table->longText(
                'processing_notes'
            )->nullable();

            $table->longText(
                'import_fail_details'
            )->nullable();

            $table->boolean(
                'passed_data_integrity_check'
            )->default(0);

            $table->dateTime(
                'started_at'
            );

            $table->dateTime(
                'completed_at'
            )->nullable();

            $table->timestamps();

            $table->index(
                'import_type_id'
            );

            $table->index(
                'status_id'
            );

            $table->index(
                'data_source_id'
            );

            $table->index(
                'started_at'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'import_logs'
        );
    }
};
