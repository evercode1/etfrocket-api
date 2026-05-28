<?php

namespace Database\Factories;

use App\Models\TicketResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketResponse>
 */
class TicketResponseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'support_topic_id' => rand(1, 10),
            'support_ticket_id' => rand(1, 50),
            'response_text' => $this->faker->sentence(),
            'user_id' => rand(1, 100),
            'is_read' => false,
            'is_from_customer' => false,
        ];
    }
}
