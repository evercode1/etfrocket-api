<?php

namespace Database\Factories;

use App\Models\SignalType;
use Illuminate\Database\Eloquent\Factories\Factory;

class SignalTypeFactory extends Factory
{
    protected $model =
    SignalType::class;

    public function definition()
    {
        return [

            'signal_type_name' =>
            $this->faker->unique()
                ->randomElement([

                    'snapshot',

                    'conditions',

                    'events',

                ]),

        ];
    }
}
