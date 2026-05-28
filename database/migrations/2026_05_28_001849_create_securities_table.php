<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('securities', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('security_type_id');

            $table->unsignedBigInteger('status_id');

            $table->string('symbol')->unique();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index('security_type_id');

            $table->index('status_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('securities');
    }
};
