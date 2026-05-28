<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;

#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [

        'name',
        'email',
        'password',
        'is_admin',
        'is_influencer',
        'is_subscriber',
        'is_active',
        'email_verified_at',

    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_admin' => 'boolean',
            'is_influencer' => 'boolean',
            'password' => 'hashed',
            'created_at' => 'date:Y-m-d',
            'updated_at' => 'date:Y-m-d',
            'email_verified_at' => 'datetime:Y-m-d H:i:s',
        ];
    }

    public function isAdmin()
    {

        return $this->is_admin === true;
    }

    public function isInfluencer()
    {

        return $this->is_influencer === true;
    }

    public function isSubscriber()
    {

        return $this->is_subscriber === true;
    }

    public function isActive()
    {

        return $this->is_active === true;
    }
}
