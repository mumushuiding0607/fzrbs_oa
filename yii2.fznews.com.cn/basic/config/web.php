<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';
$redis = require __DIR__ . '/redis.php';
$routes = require __DIR__ . '/routes.php';
// 微信接口相关数据库连接
$weixinModuleConfig = require __DIR__ . '/../modules/weixin/config.php';
$wxdb = $weixinModuleConfig['components']['db'];
// 报社内网数据库连接
$fzrbsnwdb = require __DIR__ . '/fzrbsnwdb.php';
// 新媒体中心数据库连接
$xmtzxdb = require __DIR__ . '/xmtzxdb.php';


$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'aabbxxyyzz',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'wxdb' => $wxdb,
        'fzrbsnwdb' => $fzrbsnwdb,
        'xmtzxdb' => $xmtzxdb,
        'haowufzdb' => $haowufzdb,
        'session' => [
            'class' => 'yii\web\DbSession',
            'sessionTable' => 'fzrbs_session'
        ],
        'paymentdb' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=129.0.99.64;dbname=fznews_payment',
            'username' => 'root',
            'password' => 'JXCot%nntYR%CMh0',
            'charset' => 'utf8mb4',
            'attributes' => [
                PDO::ATTR_STRINGIFY_FETCHES => false,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ],
        'redis' => $redis,
        'xunsearch' => [
            'class' => 'hightman\xunsearch\Connection',
            'iniDirectory' => '@app/config',
            'charset' => 'utf-8',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => false,
            'showScriptName' => false,
            'rules' => $routes,
        ],
    ],
    'modules' => [
        'api' => [
            'class' => 'app\modules\api\Api',
        ],
        'weixin' => [
            'class' => 'app\modules\weixin\Weixin',
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['127.0.0.1', '::1', '130.0.4.5', '130.0.12.173'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['127.0.0.1', '::1', '130.0.4.5', '130.0.12.173'],
    ];
}

return $config;
