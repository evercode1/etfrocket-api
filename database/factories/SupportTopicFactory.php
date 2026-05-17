<?php

namespace Database\Factories;

use App\Models\SupportTopic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTopic>
 */
class SupportTopicFactory extends Factory
{
    protected $model = SupportTopic::class;

    public function definition(): array
    {
        return [

            'support_topic_name' => fake()->unique()->randomElement([

                'Account Access',
                'Password Reset',
                'Portfolio Help',
                'ETF Data',
                'Billing',
                'Imports',
                'Technical Issue',
                'Feature Request',
                'Bug Report',
                'General Support',

            ]),

        ];
    }
}
