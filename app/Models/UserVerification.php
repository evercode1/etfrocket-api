<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserVerification extends Model
{
    use HasFactory;

    protected $primaryKey = 'user_id';

    // Add this to prevent Laravel from trying to auto-increment the user_id
    public $incrementing = false;

    protected $fillable = [

        'user_id',
        'token',

    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
