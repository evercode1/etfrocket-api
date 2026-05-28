<?php

namespace App\Rules;

use App\Models\TicketResponse;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class TicketResponseBelongsToUser implements ValidationRule
{
    public int $user_id;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(int $user_id)
    {

        $this->user_id = $user_id;

    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (TicketResponse::where('id', $value)
            ->where('user_id', $this->user_id)
            ->doesntExist()) {

            $fail('The ticket response does not belong to the user.');

        }

    }
}
