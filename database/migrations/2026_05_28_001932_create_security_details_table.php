<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_details', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('security_id')->unique();

            /*
            |--------------------------------------------------------------------------
            | ETF Metadata
            |--------------------------------------------------------------------------
            */

            $table->string('security_name');

            $table->unsignedBigInteger('etf_issuer_id')->nullable();

            $table->unsignedBigInteger('etf_strategy_type_id')->nullable();

            $table->unsignedBigInteger('distribution_frequency_id')->nullable();

            $table->decimal('expense_ratio', 8, 4)->nullable();

            /*
            |--------------------------------------------------------------------------
            | General Security Metadata
            |--------------------------------------------------------------------------
            */

            $table->string('exchange')->nullable();

            $table->string('sector')->nullable();

            $table->string('industry')->nullable();

            $table->date('inception_date')->nullable();

            $table->string('source')->nullable();

            $table->string('website_url')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index('security_id');

            $table->index('etf_issuer_id');

            $table->index('etf_strategy_type_id');

            $table->index('distribution_frequency_id');

            $table->index('exchange');

            $table->index('sector');

            $table->index('industry');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_details');
    }
};
