<?php
// 报社内网数据库连接
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=129.0.99.64;dbname=fzrbcms',
    'username' => 'root',
    'password' => 'JXCot%nntYR%CMh0',
    'charset' => 'utf8mb4',
    'attributes' => [
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];
