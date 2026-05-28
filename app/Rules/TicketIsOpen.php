<?php

namespace App\Rules;

use App\Models\Status;
use App\Models\SupportTicket;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class TicketIsOpen implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        $open = Status::getStatusId('open');

        if (SupportTicket::where('id', $value)

            ->where('status_id', $open)

            ->doesntExist()
        ) {

            $fail('The ticket is not open.');
        }
    }
}
