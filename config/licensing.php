<?php

return [
    // BSOLUTIONS_* is the canonical contract. The LICENSING_* fallback keeps
    // existing deployments upgradeable without silently changing their URL.
    'api_base_url' => env('BSOLUTIONS_API_URL', env('LICENSING_API_BASE_URL', 'https://license-api.bsolutions.dev/v1')),
    'project_code' => env('BSOLUTIONS_PROJECT_CODE', env('LICENSING_PROJECT_CODE', 'bslottery')),
    'default_location_code' => env('BSOLUTIONS_DEFAULT_LOCATION_CODE', env('LICENSING_DEFAULT_LOCATION_CODE', 'principal')),
    'device_name' => env('BSOLUTIONS_DEVICE_NAME', env('LICENSING_DEVICE_NAME', 'Servidor principal')),
    'device_type' => env('BSOLUTIONS_DEVICE_TYPE', env('LICENSING_DEVICE_TYPE', 'web_server')),
    'app_version' => env('BSOLUTIONS_APP_VERSION', env('LICENSING_APP_VERSION', env('APP_VERSION', '1.0.0'))),
    'request_timeout_seconds' => (int) env('BSOLUTIONS_REQUEST_TIMEOUT_SECONDS', env('LICENSING_REQUEST_TIMEOUT_SECONDS', 8)),
    'retry_attempts' => (int) env('BSOLUTIONS_RETRY_ATTEMPTS', env('LICENSING_RETRY_ATTEMPTS', 2)),
    'retry_sleep_milliseconds' => (int) env('BSOLUTIONS_RETRY_SLEEP_MILLISECONDS', env('LICENSING_RETRY_SLEEP_MILLISECONDS', 300)),
    'verify_tls' => filter_var(env('BSOLUTIONS_VERIFY_TLS', env('LICENSING_VERIFY_TLS', true)), FILTER_VALIDATE_BOOL),
    'warning_days_before_expiration' => (int) env('BSOLUTIONS_WARNING_DAYS_BEFORE_EXPIRATION', env('LICENSING_WARNING_DAYS_BEFORE_EXPIRATION', 7)),
    'fingerprint_path' => env('BSOLUTIONS_DEVICE_FINGERPRINT_PATH', env('LICENSING_FINGERPRINT_PATH', storage_path('app/private/installation.id'))),
];
