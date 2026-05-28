<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
    use HasFactory;

    protected $primaryKey = 'token';

    protected $table = 'password_reset_tokens';

    const UPDATED_AT = null;

    protected $fillable = [

        'email',
        'token',

    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'token');
    }
}
