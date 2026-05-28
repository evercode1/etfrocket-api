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
        Schema::create('etf_issuers', function (Blueprint $table) {

            $table->id();
            $table->string('etf_issuer_name')->unique();
            $table->string('website_url')->nullable();
            $table->unsignedInteger('status_id')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etf_issuers');
    }
};
