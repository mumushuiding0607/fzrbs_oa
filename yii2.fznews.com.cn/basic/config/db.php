<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=129.0.98.14;dbname=fzstm',
    'username' => 'root',
    'password' => 'osaMo3sXWtPV8rVl',
    'charset' => 'utf8mb4',
    'attributes' => [
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],

    // 'class' => 'yii\db\Connection',
    // 'dsn' => 'mysql:host=129.0.99.64;dbname=fzrbwx',
    // 'username' => 'root',
    // 'password' => 'JXCot%nntYR%CMh0',
    // 'charset' => 'utf8mb4',

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];
