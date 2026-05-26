<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(

            'etf_ingestion_batches',

            function (Blueprint $table) {

                $table->id();

                $table->uuid('batch_uuid')->unique();

                $table->unsignedBigInteger('import_type_id');

                $table->unsignedBigInteger('status_id')->nullable();

                $table->integer('total_etfs')->default(0);

                $table->integer('processed_count')->default(0);

                $table->integer('success_count')->default(0);

                $table->integer('failure_count')->default(0);

                $table->integer('duplicate_count')->default(0);

                $table->boolean('passed_data_integrity_check')->default(false);

                $table->longText('processing_notes')->nullable();

                $table->longText('import_fail_details')->nullable();

                $table->timestamp('started_at')->nullable();

                $table->timestamp('completed_at')->nullable();

                $table->timestamps();
            }

        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'etf_ingestion_batches'
        );
    }
};
