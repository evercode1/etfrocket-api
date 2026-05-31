<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(

            'security_ingestion_batch_items',

            function (Blueprint $table) {

                $table->id();

                $table->unsignedBigInteger('security_ingestion_batch_id');

                $table->unsignedBigInteger('security_id');

                $table->unsignedBigInteger('status_id')->nullable();

                $table->unsignedBigInteger('security_update_schedule_id')->nullable();

                $table->unsignedBigInteger('security_update_type_id')->nullable();

                $table->integer('attempts')->default(0);

                $table->integer('runtime_ms')->nullable();

                $table->boolean('is_processed')->default(false);

                $table->boolean('is_success')->default(false);

                $table->longText('error_message')->nullable();

                $table->timestamp('started_at')->nullable();

                $table->timestamp('completed_at')->nullable();

                $table->timestamps();

                // indexes

                $table->index(['security_ingestion_batch_id']);

                $table->index(['security_id']);

                $table->index(['security_update_schedule_id']);

                $table->index(['security_update_type_id']);

                $table->index(['is_processed']);

                $table->index(['is_success']);
            }

        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'security_ingestion_batch_items'
        );
    }
};
