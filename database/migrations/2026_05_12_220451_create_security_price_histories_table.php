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
        Schema::create('security_price_histories', function (Blueprint $table) {

            $table->id();
            $table->unsignedInteger('security_id')->index();
            $table->date('price_date')->index();
            $table->decimal('close_price', 12, 4);
            $table->bigInteger('volume')->nullable();
            $table->unsignedInteger('data_source_id')->nullable();
            $table->timestamp('retrieved_at')->nullable();

            $table->timestamps();

            // indexes

            $table->unique(['security_id', 'price_date']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_price_histories');
    }
};
