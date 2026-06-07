<?php

namespace App\Services\AI\AiSignals;

use Carbon\Carbon;

class IsMarketOpenService
{
    /**
     * NYSE / Nasdaq holidays.
     *
     * Covers 2026 and 2027.
     */
    private array $holidays = [

        // 2026

        '2026-01-01', // New Year's Day

        '2026-01-19', // Martin Luther King Jr. Day

        '2026-02-16', // Presidents' Day

        '2026-04-03', // Good Friday

        '2026-05-25', // Memorial Day

        '2026-06-19', // Juneteenth

        '2026-07-03', // Independence Day Observed

        '2026-09-07', // Labor Day

        '2026-11-26', // Thanksgiving

        '2026-12-25', // Christmas Day

        // 2027

        '2027-01-01', // New Year's Day

        '2027-01-18', // Martin Luther King Jr. Day

        '2027-02-15', // Presidents' Day

        '2027-03-26', // Good Friday

        '2027-05-31', // Memorial Day

        '2027-06-18', // Juneteenth Observed

        '2027-07-05', // Independence Day Observed

        '2027-09-06', // Labor Day

        '2027-11-25', // Thanksgiving

        '2027-12-24', // Christmas Observed

        // 2028

        '2028-01-17', // Martin Luther King Jr. Day

        '2028-02-21', // Presidents' Day

        '2028-04-14', // Good Friday

        '2028-05-29', // Memorial Day

        '2028-06-19', // Juneteenth

        '2028-07-04', // Independence Day

        '2028-09-04', // Labor Day

        '2028-11-23', // Thanksgiving

        '2028-12-25', // Christmas Day

    ];

    /**
     * U.S. equities market hours:
     *
     * Monday - Friday
     * 9:30 AM - 4:00 PM ET
     */
    public function isOpen(): bool
    {
        $now =
            Carbon::now(
                'America/New_York'
            );

        if (
            $this->isWeekend(
                $now
            )
        ) {

            return false;
        }

        if (
            $this->isHoliday(
                $now
            )
        ) {

            return false;
        }

        return $this->isWithinMarketHours(
            $now
        );
    }

    private function isWeekend(
        Carbon $date
    ): bool {

        return $date->isWeekend();
    }

    private function isHoliday(
        Carbon $date
    ): bool {

        return in_array(

            $date->toDateString(),

            $this->holidays

        );
    }

    private function isWithinMarketHours(
        Carbon $date
    ): bool {

        $marketOpen =
            $date->copy()
                ->setTime(
                    9,
                    30
                );

        $marketClose =
            $date->copy()
                ->setTime(
                    16,
                    0
                );

        return $date->between(

            $marketOpen,

            $marketClose

        );
    }
}
