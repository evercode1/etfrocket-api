<?php

namespace Database\Factories;

use App\Models\Security;
use App\Models\SecurityUpdateSchedule;
use App\Models\SecurityUpdateType;
use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

class SecurityUpdateScheduleFactory extends Factory
{
    protected $model =
        SecurityUpdateSchedule::class;

    public function definition(): array
    {
        return [

            'security_id' => Security::factory(),

            'security_update_type_id' => SecurityUpdateType::DIVIDEND,

            'run_day' => 1,

            'run_hour' => 2,

            'last_run_at' => null,

            'status_id' => Status::ACTIVE,

        ];
    }

    public function dividend(): static
    {
        return $this->state([
            'security_update_type_id' => SecurityUpdateType::DIVIDEND,
        ]);
    }

    public function aum(): static
    {
        return $this->state([
            'security_update_type_id' => SecurityUpdateType::FUND_DATA,
        ]);
    }

    public function nav(): static
    {
        return $this->state([
            'security_update_type_id' => SecurityUpdateType::FUND_DATA,
        ]);
    }

    public function inactive(): static
    {
        return $this->state([
            'status_id' => Status::INACTIVE,
        ]);
    }

    public function scheduledFor(
        int $day,
        int $hour
    ): static {

        return $this->state([

            'run_day' => $day,

            'run_hour' => $hour,

        ]);
    }
}
