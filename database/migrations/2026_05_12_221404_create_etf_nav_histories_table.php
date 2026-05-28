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
        Schema::create('etf_nav_histories', function (Blueprint $table) {

            $table->id();
            $table->unsignedInteger('security_id')->index();
            $table->date('nav_date')->index();
            $table->decimal('nav_per_share', 12, 4);
            $table->unsignedInteger('data_source_id')->nullable();
            $table->timestamp('retrieved_at')->nullable();
            $table->timestamps();

            // indexes

            $table->unique(['security_id', 'nav_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etf_nav_histories');
    }
};
