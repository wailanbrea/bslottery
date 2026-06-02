<?php

return [
    'agent_url'   => env('PRINT_AGENT_URL', 'http://127.0.0.1:8765'),
    'agent_token' => env('PRINT_AGENT_TOKEN', ''),
    'qz_digital_certificate_path' => env('QZ_DIGITAL_CERTIFICATE_PATH', storage_path('app/qz/digital-certificate.txt')),
    'qz_certificate_path' => env('QZ_CERTIFICATE_PATH', storage_path('app/qz/qz-certificate.pem')),
    'qz_private_key_path' => env('QZ_PRIVATE_KEY_PATH', storage_path('app/qz/private-key.pem')),
    'qz_install_command' => env('QZ_INSTALL_COMMAND', 'irm pwsh.sh | iex'),
];
