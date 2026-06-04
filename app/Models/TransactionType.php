<?php

namespace App\Models;

use Database\Factories\TransactionTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionType extends Model
{
    /** @use HasFactory<TransactionTypeFactory> */
    use HasFactory;

    public const BUY = 1;

    public const SELL = 2;

    protected $fillable = [
        'transaction_type_name',
        'slug',
    ];

    protected function casts(): array
    {

        return [

            'created_at' => 'date:Y-m-d',
            'updated_at' => 'date:Y-m-d',

        ];

    }

    public static function getSelects()
    {

        return self::orderBy('transaction_type_name', 'asc')

            ->pluck('transaction_type_name', 'id');
    }
}
