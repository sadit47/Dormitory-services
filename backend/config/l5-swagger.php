<?php

return [

    'default' => 'default',

    'documentations' => [

        'default' => [

            'api' => [
                'title' => 'Dorm-Service Swagger UI',
            ],

            'routes' => [
                // หน้า UI
                'api' => 'api/documentation',
            ],

            'paths' => [

                // ให้ UI อ้าง asset แบบ full url (มักแก้ปัญหา asset หายหลังขึ้น server)
                'use_absolute_path' => true,

                // ที่เก็บ swagger-ui assets
                'swagger_ui_assets_path' => 'vendor/swagger-api/swagger-ui/dist/',

                // ชื่อไฟล์ output
                'docs_json' => 'api-docs.json',
                'docs_yaml' => 'api-docs.yaml',


                // เลือกใช้ json/yaml ในหน้า UI
                'format_to_use_for_docs' => 'json',

                // โฟลเดอร์ที่ให้สแกน annotation (สำคัญสุด)
                'annotations' => [
                    base_path('app/Swagger'),
                    base_path('app/Http/Controllers'),
                ],
            ],
        ],
    ],

    'defaults' => [

        'routes' => [
            // endpoint สำหรับไฟล์ spec ที่ generate แล้ว
            'docs' => 'docs',

            'oauth2_callback' => 'api/oauth2-callback',

            'middleware' => [
                'api' => [],
                'asset' => [],
                'docs' => [],
                'oauth2_callback' => [],
            ],

            'group_options' => [],
        ],

        'paths' => [

            // เก็บไฟล์ spec ที่ generate แล้ว
            'docs' => storage_path('api-docs'),

            // view ของ swagger (ถ้าคุณ publish vendor จะมาอยู่ตรงนี้)
            'views' => base_path('resources/views/vendor/l5-swagger'),

            // base path ของ api (ถ้าใช้ nginx reverse proxy/มี prefix ค่อยกำหนด)
            'base' => env('L5_SWAGGER_BASE_PATH', null),

            // exclude path ไม่ต้องสแกน
            'excludes' => [],
        ],

        'scanOptions' => [

            'default_processors_configuration' => [],

            'analyser' => null,
            'analysis' => null,

            'processors' => [
                // new \App\SwaggerProcessors\SchemaQueryParameter(),
            ],

            'pattern' => '*.php',

            // exclude แบบใหม่ (ทับ paths.excludes)
            'exclude' => [],

            'open_api_spec_version' => env(
                'L5_SWAGGER_OPEN_API_SPEC_VERSION',
                \L5Swagger\Generator::OPEN_API_DEFAULT_SPEC_VERSION
            ),
        ],

        'securityDefinitions' => [
            'securitySchemes' => [
                // ถ้าใช้ Bearer token (Sanctum/JWT) ให้เปิดอันนี้ได้

                'bearerAuth' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'Sanctum',
                'description' => 'Paste token only (Swagger will send: Authorization: Bearer <token>)',
              ],
                
            ],
            'security' => [
                // ตัวอย่างการบังคับใช้ security ทั้งระบบ
                
                [
                    'bearerAuth' => [],
                ],
                
            ],
        ],

        // dev: true เพื่อให้ refresh แล้ว regen ทุกครั้ง / prod: false แนะนำ
        'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', false),

        'generate_yaml_copy' => false,

        'proxy' => false,

        'additional_config_url' => null,

        'operations_sort' => env('L5_SWAGGER_OPERATIONS_SORT', null),

        'validator_url' => null,

        'ui' => [
            'display' => [
                'dark_mode' => env('L5_SWAGGER_UI_DARK_MODE', false),
                'doc_expansion' => env('L5_SWAGGER_UI_DOC_EXPANSION', 'none'),
                'filter' => env('L5_SWAGGER_UI_FILTERS', true),
            ],

            'authorization' => [
                // ✅ แนะนำให้ true เพื่อ refresh แล้วยังจำ token
                'persist_authorization' => true,

                'oauth2' => [
                    'use_pkce_with_authorization_code_grant' => false,
                ],
            ],
        ],

        'constants' => [
            'L5_SWAGGER_CONST_HOST' => env('L5_SWAGGER_CONST_HOST', 'http://localhost'),
        ],
    ],
];