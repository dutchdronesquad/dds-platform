<?php

return [
    'redact_payload_fields' => array_values(array_unique([
        ...explode(',', (string) env('NIGHTWATCH_REDACT_PAYLOAD_FIELDS', '_token,password,password_confirmation')),
        'api_key',
    ])),
];
