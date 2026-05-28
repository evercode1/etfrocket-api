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
        Schema::create('etf_aum_histories', function (Blueprint $table) {

            $table->id();
            $table->unsignedInteger('security_id')->index();
            $table->date('aum_date')->index();
            $table->bigInteger('assets_under_management');
            $table->unsignedInteger('data_source_id')->nullable();
            $table->timestamp('retrieved_at')->nullable();
            $table->timestamps();

            // indexes

            $table->unique(['security_id', 'aum_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etf_aum_histories');
    }
};
