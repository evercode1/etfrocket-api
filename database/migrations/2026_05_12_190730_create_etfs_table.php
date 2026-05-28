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
        Schema::create('etfs', function (Blueprint $table) {

            $table->id();
            $table->string('symbol')->unique();
            $table->string('fund_name');
            $table->unsignedInteger('etf_issuer_id')->index();
            $table->unsignedInteger('etf_strategy_type_id')->index();
            $table->unsignedInteger('distribution_frequency_id')->index();
            $table->unsignedInteger('status_id')->index();
            $table->decimal('expense_ratio', 5, 2)->nullable();
            $table->date('inception_date')->nullable();
            $table->string('source')->nullable();
            $table->string('website_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etfs');
    }
};
