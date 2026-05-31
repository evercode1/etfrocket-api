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

                $table->unsignedBigInteger('ai_signal_batch_id');

                $table->unsignedBigInteger('signal_type_id');

                $table->unsignedBigInteger('import_type_id');

                $table->unsignedBigInteger('status_id');

                $table->integer('attempts')->default(0);

                $table->integer('runtime_ms')->nullable();

                $table->boolean('is_processed')->default(false);

                $table->boolean('is_success')->default(false);

                $table->longText('error_message')->nullable();

                $table->timestamp('started_at')->nullable();

                $table->timestamp('completed_at')->nullable();

                $table->timestamps();

                // indexes

                $table->index(
                    'ai_signal_batch_id',
                    'asbi_batch_idx'
                );

                $table->index(
                    'signal_type_id',
                    'asbi_signal_type_idx'
                );

                $table->index(
                    'import_type_id',
                    'asbi_import_type_idx'
                );

                $table->index(
                    'status_id',
                    'asbi_status_idx'
                );
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
