<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAiMarketSignalsTable extends Migration
{
    public function up()
    {
        Schema::create('ai_market_signals', function (Blueprint $table) {

            $table->id();

            $table->unsignedInteger('signal_type_id');

            $table->string('title');

            $table->string('subtitle')->nullable();

            $table->string('market_mood')->nullable();

            $table->unsignedTinyInteger('confidence_score')->nullable();

            $table->longText('markdown_content');

            $table->json('payload_json')->nullable();

            $table->timestamp('generated_at');

            $table->timestamp('expires_at')->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->string('ai_model')
                ->nullable();

            $table->timestamps();

            // Indexes

            $table->index('signal_type_id');

            $table->index('generated_at');

            $table->index('is_active');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ai_market_signals');
    }
}
