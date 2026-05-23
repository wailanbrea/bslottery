<?php

return [
    'api_base_url' => env('LICENSING_API_BASE_URL', 'https://api.bsolutions.dev/v1'),
    'project_code' => env('LICENSING_PROJECT_CODE', 'bslottery'),
    'default_location_code' => env('LICENSING_DEFAULT_LOCATION_CODE', 'principal'),
    'device_name' => env('LICENSING_DEVICE_NAME', 'Servidor principal'),
    'device_type' => env('LICENSING_DEVICE_TYPE', 'web'),
    'app_version' => env('LICENSING_APP_VERSION', env('APP_VERSION', '1.0.0')),
    'request_timeout_seconds' => (int) env('LICENSING_REQUEST_TIMEOUT_SECONDS', 8),
    'retry_attempts' => (int) env('LICENSING_RETRY_ATTEMPTS', 2),
    'retry_sleep_milliseconds' => (int) env('LICENSING_RETRY_SLEEP_MILLISECONDS', 300),
    'verify_tls' => filter_var(env('LICENSING_VERIFY_TLS', true), FILTER_VALIDATE_BOOL),
    'warning_days_before_expiration' => (int) env('LICENSING_WARNING_DAYS_BEFORE_EXPIRATION', 7),
    'fingerprint_path' => env('LICENSING_FINGERPRINT_PATH', storage_path('app/private/installation.id')),
];

