<?php

namespace Database\Factories;

use App\Models\SecurityType;
use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

class SecurityFactory extends Factory
{
    public function definition(): array
    {
        return [

            'security_type_id' => SecurityType::ETF,

            'status_id' => Status::ACTIVE,

            'symbol' => strtoupper(

                $this->faker->unique()
                    ->lexify('????')

            ),

        ];
    }
}
