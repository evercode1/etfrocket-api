<?php

namespace Database\Factories;

use App\Models\SecurityDetail;
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

    public function configure(): static
    {
        return $this->afterCreating(

            function ($security) {

                SecurityDetail::factory()
                    ->create([

                        'security_id' => $security->id,

                        'security_name' => $security->symbol.'_name',

                        'website_url' => 'https://'.

                        strtolower(
                            $security->symbol
                        )

                        .'.com',

                        'source' => $security->symbol.'_source',

                        'notes' => $security->symbol.'_notes',

                    ]);
            }

        );
    }
}
