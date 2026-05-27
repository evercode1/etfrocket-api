<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(

            'ai_signal_batch_items',

            function (Blueprint $table) {

                $table->id();

                $table->foreignId(
                    'ai_signal_batch_id'
                );

                $table->foreignId(
                    'signal_type_id'
                );

                $table->foreignId(
                    'import_type_id'
                );

                $table->foreignId(
                    'status_id'
                );

                $table->integer(
                    'attempts'
                )->default(0);

                $table->integer(
                    'runtime_ms'
                )->nullable();

                $table->boolean(
                    'is_processed'
                )->default(false);

                $table->boolean(
                    'is_success'
                )->default(false);

                $table->longText(
                    'error_message'
                )->nullable();

                $table->timestamp(
                    'started_at'
                )->nullable();

                $table->timestamp(
                    'completed_at'
                )->nullable();

                $table->timestamps();
            }

        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'ai_signal_batch_items'
        );
    }
};
