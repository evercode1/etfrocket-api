<?php

namespace App\Services\Auth;

class ProfanityFilterService
{
    private static $patterns = [

        '/n[\W_]*[i1!|l][\W_]*g[\W_]*g[\W_]*[e3][\W_]*r/i',         // N-word (expanded)
        '/f[\W_]*u[\W_]*c[\W_]*k/i',                               // F-word
        '/s[\W_]*h[\W_]*[i1!][\W_]*[t7]/i',                        // Shit
        '/d[\W_]*[i1!a@][\W_]*[c|m][\W_]*[k|n]/i',                 // Dick / Damn
        '/b[\W_]*[i1!][\W_]*[t7][\W_]*c[\W_]*h/i',                 // B-word
        '/[a@][\W_]*[s5$]+[\W_]*[s5$]+/i',                         // Ass
        '/c[\W_]*u[\W_]*n[\W_]*[t7]/i',                            // C-word
        '/p[\W_]*u[\W_]*[s5$]+[\W_]*[yie13]{1,2}/i',               // P-word
        '/c[\W_]*[o0][\W_]*c[\W_]*k/i',                            // Cock
    ];

    /**
     * Returns a boolean indicating whether this string matches
     * any of the regex profanty filters.
     *
     * @param [type] $string
     * @return void
     */
    public function matches(string $string)
    {
        foreach (self::$patterns as $pattern) {

            if (preg_match($pattern, strtolower($string))) {

                return true;
            }
        }

        return false;
    }
}
