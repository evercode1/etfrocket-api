<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportTopic extends Model
{
    use HasFactory;

    protected $fillable = ['support_topic_name'];

    const ACCOUNT_ACCESS = 1;

    const LOGIN_ISSUES = 2;

    const PASSWORD_RESET = 3;

    const EMAIL_VERIFICATION = 4;

    const SUBSCRIPTION_BILLING = 5;

    const CANCEL_SUBSCRIPTION = 6;

    const DIVIDEND_DATA_ISSUE = 7;

    const ETF_DATA_ISSUE = 8;

    const STOCK_PRICE_ISSUE = 9;

    const MISSING_DIVIDEND = 10;

    const INCORRECT_DIVIDEND_YIELD = 11;

    const APP_BUG = 12;

    const MOBILE_APP_ISSUE = 13;

    const WEBSITE_ISSUE = 14;

    const PERFORMANCE_ISSUE = 15;

    const FEATURE_REQUEST = 16;

    const NOTIFICATIONS = 17;

    const EMAIL_NOTIFICATIONS = 18;

    const PUSH_NOTIFICATIONS = 19;

    const WATCHLIST_ISSUE = 20;

    const PORTFOLIO_TRACKING = 21;

    const DATA_DELAY = 22;

    const PAYMENT_ISSUE = 23;

    const REFUND_REQUEST = 24;

    const ACCOUNT_DELETION = 25;

    const SECURITY_CONCERN = 26;

    const API_ACCESS = 27;

    const GENERAL_QUESTION = 28;

    const OTHER = 29;

    protected function casts(): array
    {

        return [

            'created_at' => 'date:Y-m-d',
            'updated_at' => 'date:Y-m-d',

        ];
    }

    public static function getSelects()
    {

        return self::orderBy('support_topic_name', 'asc')

            ->pluck('support_topic_name', 'id');
    }
}
