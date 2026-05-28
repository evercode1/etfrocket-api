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
        Schema::create('security_metrics', function (Blueprint $table) {

            $table->id();

            $table->unsignedInteger('security_id')->index();
            $table->unsignedInteger('performance_range_type_id')->index();

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->decimal('start_price', 12, 4)->nullable();
            $table->decimal('end_price', 12, 4)->nullable();
            $table->decimal('price_change', 12, 4)->nullable();
            $table->decimal('price_change_percentage', 10, 4)->nullable();

            $table->decimal('dividends_paid', 12, 4)->default(0);
            $table->unsignedInteger('dividend_count')->default(0);
            $table->decimal('average_dividend', 12, 4)->nullable();

            $table->decimal('total_return_percentage', 10, 4)->nullable();

            $table->decimal('start_nav', 12, 4)->nullable();
            $table->decimal('end_nav', 12, 4)->nullable();
            $table->decimal('nav_change', 12, 4)->nullable();
            $table->decimal('nav_erosion_percentage', 10, 4)->nullable();
            $table->unsignedInteger('nav_direction_id')->nullable()->index();

            $table->bigInteger('start_aum')->nullable();
            $table->bigInteger('end_aum')->nullable();
            $table->bigInteger('aum_change')->nullable();
            $table->decimal('aum_change_percentage', 10, 4)->nullable();
            $table->unsignedInteger('aum_direction_id')->nullable()->index();

            $table->timestamp('calculated_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['security_id', 'performance_range_type_id'],
                'security_metrics_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_metrics');
    }
};
