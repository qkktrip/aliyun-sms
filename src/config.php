<?php

return [
    'access_id' => env('ALIYUN_SMS_ACCESSID', 'your-access-id'),
    'access_key' => env('ALIYUN_SMS_ACCESSKEY', 'your-access-key'),

    'sms_template' => [
        'register' => [
            'sign_name' => '',
            'template_code' => ''
        ]

    ],
];