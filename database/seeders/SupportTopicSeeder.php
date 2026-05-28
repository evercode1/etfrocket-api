<?php

namespace Database\Seeders;

use App\Models\SupportTopic;
use Illuminate\Database\Seeder;

class SupportTopicSeeder extends Seeder
{
    public function run(): void
    {
        SupportTopic::truncate();

        $topics = [

            'Account Access',
            'Login Issues',
            'Password Reset',
            'Email Verification',
            'Subscription & Billing',
            'Cancel Subscription',
            'Dividend Data Issue',
            'ETF Data Issue',
            'Stock Price Issue',
            'Missing Dividend Information',
            'Incorrect Dividend Yield',
            'App Bug',
            'Mobile App Issue',
            'Website Issue',
            'Performance Issue',
            'Feature Request',
            'Notifications',
            'Email Notifications',
            'Push Notifications',
            'Watchlist Issue',
            'Portfolio Tracking',
            'Delayed Market Data',
            'Payment Issue',
            'Refund Request',
            'Account Deletion',
            'Security Concern',
            'API Access',
            'General Question',
            'Other',

        ];

        foreach ($topics as $topic_name) {

            SupportTopic::create([

                'support_topic_name' => $topic_name,

            ]);
        }
    }
}
