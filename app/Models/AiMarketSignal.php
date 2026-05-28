<?php

namespace App\Models;

use Database\Factories\AiMarketSignalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiMarketSignal extends Model
{
    /** @use HasFactory<AiMarketSignalFactory> */
    use HasFactory;

    protected $fillable = [

        'signal_type_id',
        'title',
        'subtitle',
        'market_mood',
        'confidence_score',
        'markdown_content',
        'payload_json',
        'generated_at',
        'expires_at',
        'is_active',
        'ai_model',

    ];

    protected function casts(): array
    {

        return [

            'created_at' => 'date:Y-m-d',
            'updated_at' => 'date:Y-m-d',
            'payload_json' => 'array',
            'generated_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',

        ];
    }

    public function signalType()
    {

        return $this->belongsTo(

            SignalType::class

        );
    }
}
