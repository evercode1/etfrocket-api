<?php

namespace Database\Factories;

use App\Models\Status;
use App\Models\SupportTicket;
use App\Models\SupportTopic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicket>
 */
class SupportTicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => rand(1, 100),
            'support_topic_id' => SupportTopic::first()?->id,
            'status_id' => Status::OPEN,
            'ticket_text' => $this->faker->sentence(),
        ];
    }
}
