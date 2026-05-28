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
        Schema::create('portfolio_transactions', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('portfolio_id');

            $table->unsignedBigInteger('security_id');

            $table->unsignedInteger('transaction_type_id');

            $table->decimal('shares', 12, 4);

            $table->decimal('price_per_share', 12, 4);

            $table->date('transaction_date');

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index('portfolio_id');

            $table->index('security_id');

            $table->index('transaction_type_id');

            $table->index([
                'portfolio_id',
                'security_id',
            ]);

            $table->index([
                'portfolio_id',
                'transaction_date',
            ]);

            $table->index([
                'security_id',
                'transaction_date',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portfolio_transactions');
    }
};
