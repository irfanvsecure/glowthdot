<?php

return [

    // The MCP server at /mcp (routes/ai.php): a long random secret, at least 32 characters
    'mcp' => [
        'secret' => env('MCP_SECRET'),
    ],

    // Optional: purged by the clear_cache tool when both are set
    'cloudflare' => [
        'zone_id' => env('CLOUDFLARE_ZONE_ID'),
        'api_token' => env('CLOUDFLARE_API_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Website form enquiries are always saved to storage/app/private/leads; also emailed
    // (comma-separated list) and, for the audit funnel, posted to a Google Sheet when set
    'leads' => [
        'to' => env('LEADS_EMAIL'),
        'reply_to' => env('LEADS_REPLY_TO', 'sales@glowthdot.com'),
        'sheet_url' => env('LEADS_SHEET_URL'),
    ],

];
