<?php

namespace App\Http\Controllers\Dev\ExternalSeeders;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;

class MakeAdminUsersSeederController extends Controller
{

    public function run()
    {

        if (! env('ALLOW_SEEDS')) {

            return ['message' => 'ALLOW_SEEDS seeds set to false. Check your ENV file.'];
        }


        User::truncate();

        // Bill

        $user1 = User::create([

            'name' => 'Brokie1',
            'email' => 'ikon321@yahoo.com',
            'password' => '12345678',
            'is_admin' => 1,
            'email_verified_at' => Carbon::now()->subDays(6),


        ]);

        // Demo

        $user2 = User::create([

            'name' => 'Demo',
            'email' => 'demo@demo.com',
            'password' => '12345678',
            'is_admin' => 1,
            'email_verified_at' => Carbon::now()->subDays(6),


        ]);


        // use UserFactory to create 100 random users, no admins. 
        User::factory()
            ->count(98)
            ->create()
            ->each(function ($user) {

                $createdAt = Carbon::now()->subDays(rand(0, 730));

                $isVerified = rand(1, 100) <= 70;

                $user->created_at = $createdAt;

                $user->updated_at = $createdAt;

                $user->email_verified_at = $isVerified
                    ? $createdAt->copy()->addDays(rand(0, 14))
                    : null;

                $user->is_admin = 0;

                $user->save();
            });


        return ['message' => 'Admin Users Seeded'];
    }
}
