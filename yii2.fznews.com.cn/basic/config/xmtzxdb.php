<?php
// 新媒体中心数据库连接
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=129.0.98.14;dbname=fzrbxmtzx',
    'username' => 'root',
    'password' => 'osaMo3sXWtPV8rVl',
    'charset' => 'utf8mb4',
    'attributes' => [
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];
