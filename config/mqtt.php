<?php

return [
    'host'      => env('MQTT_HOST', '127.0.0.1'),
    'port'      => env('MQTT_PORT', 1883),
    'client_id' => env('MQTT_CLIENT_ID', 'gudangsafe-laravel'),
    'username'  => env('MQTT_USERNAME', null),
    'password'  => env('MQTT_PASSWORD', null),
];
