<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    |
    | This array contains the notification channels that are available to your
    | application. You can add or remove channels as needed.
    |
    */

    'channels' => [
        'database' => [
            'driver' => 'database',
            'table' => 'notifications',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Queue
    |--------------------------------------------------------------------------
    |
    | This option allows you to control whether notifications are queued or not.
    | When set to true, notifications will be processed in the background.
    |
    */

    'queue' => true,

    /*
    |--------------------------------------------------------------------------
    | Notification Database Table
    |--------------------------------------------------------------------------
    |
    | This is the database table that will be used to store notifications.
    | You can modify this table name if needed.
    |
    */

    'table' => 'notifications',
]; 