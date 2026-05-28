<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(

            'etf_ingestion_batch_items',

            function (Blueprint $table) {

                $table->id();

                $table->unsignedBigInteger('etf_ingestion_batch_id');

                $table->unsignedBigInteger('etf_id');

                $table->unsignedBigInteger('status_id')->nullable();

                $table->integer('attempts')->default(0);

                $table->integer('runtime_ms')->nullable();

                $table->boolean('is_processed')->default(false);

                $table->boolean('is_success')->default(false);

                $table->longText('error_message')->nullable();

                $table->timestamp('started_at')->nullable();

                $table->timestamp('completed_at')->nullable();

                $table->timestamps();

                // indexes

                $table->index(['etf_ingestion_batch_id']);

                $table->index(['etf_id']);

                $table->index(['is_processed']);

                $table->index(['is_success']);
            }

        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'etf_ingestion_batch_items'
        );
    }
};
