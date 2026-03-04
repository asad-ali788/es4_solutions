<?php

return [
    'client_id'     => env('AMZ_ADS_CLIENT_ID'),
    'client_secret' => env('AMZ_ADS_CLIENT_SECRET'),
    'refresh_token' => env('AMZ_ADS_REFRESH_TOKEN'),
    'profile_id_ca' => env('AMZ_ADS_PROFILE_ID_CA'),
    'region'        => env('AMZ_ADS_REGION', 'NA'),
    'user_agent'    => env('AMZ_ADS_USER_AGENT', 'NA'),
    'sandbox'       => env('AMZ_ADS_SANDBOX', false),
    'profiles' => [
        'US' => env('AMAZON_ADS_PROFILE_ID_US'),
        'CA' => env('AMAZON_ADS_PROFILE_ID_CA'),
    ],

];
