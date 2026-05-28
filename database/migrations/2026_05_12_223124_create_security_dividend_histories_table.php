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
        Schema::create('security_dividend_histories', function (Blueprint $table) {

            $table->id();
            $table->unsignedInteger('security_id')->index();
            $table->decimal('dividend_amount', 12, 4);
            $table->date('ex_dividend_date')->index();
            $table->date('payment_date')->nullable()->index();
            $table->unsignedInteger('data_source_id')->nullable();
            $table->timestamp('retrieved_at')->nullable();
            $table->timestamps();

            // indexes

            $table->unique(
                [
                    'security_id',
                    'ex_dividend_date',
                    'dividend_amount',
                ],
                'security_dividend_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_dividend_histories');
    }
};
