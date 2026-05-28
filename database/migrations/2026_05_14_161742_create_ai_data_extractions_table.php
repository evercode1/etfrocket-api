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
        Schema::create('ai_data_extractions', function (Blueprint $table) {

            $table->id();

            $table->unsignedInteger('security_id')->nullable()->index();

            $table->unsignedInteger('data_source_id')->nullable()->index();

            $table->string('source_url')->nullable();

            $table->longText('raw_payload')->nullable();

            $table->longText('prompt')->nullable();

            $table->json('extracted_data')->nullable();

            $table->boolean('is_validated')->default(false);

            $table->text('validation_notes')->nullable();

            $table->timestamp('processed_at')->nullable();

            $table->timestamp('failed_at')->nullable();

            $table->text('failure_reason')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_data_extractions');
    }
};
