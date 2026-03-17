<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'language' => 'ru-RU',
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => '8OZ8qCHvL3XX3PA9kdi__s4rFFTVpYwP',
            // Парсинг JSON в теле запроса для API
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
            // Если приложение открывается по https://domain/ubt/ — раскомментируй:
            // 'baseUrl' => '/ubt',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
            'loginUrl' => ['/admin/login'],
        ],
        'errorHandler' => [
            'errorAction' => 'admin/auth/error',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            // send all mails to a file by default.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 10 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning', 'info', 'trace', 'profile'],
                ],
            ],
        ],
        'db' => $db,
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
            // uncomment to use cached (recommended for production):
            // 'cache' => 'cache',
        ],
        'queue' => [
            'class' => \yii\queue\file\Queue::class,
            'path' => '@runtime/queue',
            'as log' => \yii\queue\LogBehavior::class,
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'phpinfo' => 'site/php-info',
                // API каналов (id — число, slug — латиница/цифры/дефис)
                'GET api/channels' => 'api/channel/index',
                'GET api/channels/<idOrSlug:\d+|[a-z0-9\-]+>' => 'api/channel/view',
                'POST api/channels' => 'api/channel/create',
                'DELETE api/channels/<idOrSlug:\d+|[a-z0-9\-]+>' => 'api/channel/delete',
                // Посты канала (канал по id или slug)
                'GET api/channels/<idOrSlug:\d+|[a-z0-9\-]+>/posts' => 'api/channel/posts',
                'GET api/channels/<idOrSlug:\d+|[a-z0-9\-]+>/posts/<id:\d+>' => 'api/channel/view-post',
                'POST api/channels/<idOrSlug:\d+|[a-z0-9\-]+>/posts' => 'api/channel/create-post',
                'PUT api/channels/<idOrSlug:\d+|[a-z0-9\-]+>/posts/<id:\d+>' => 'api/channel/update-post',
                'PATCH api/channels/<idOrSlug:\d+|[a-z0-9\-]+>/posts/<id:\d+>' => 'api/channel/update-post',
                'DELETE api/channels/<idOrSlug:\d+|[a-z0-9\-]+>/posts/<id:\d+>' => 'api/channel/delete-post',
                // Admin: посты вложены в аккаунт — zen-account/{account_id}/zen-post/...
                ['pattern' => 'admin/zen-account/<account_id:\d+>/zen-post', 'route' => 'admin/zen-post/index'],
                ['pattern' => 'admin/zen-account/<account_id:\d+>/zen-post/create', 'route' => 'admin/zen-post/create'],
                ['pattern' => 'admin/zen-account/<account_id:\d+>/zen-post/send/<id:\d+>', 'route' => 'admin/zen-post/send'],
                ['pattern' => 'admin/zen-account/<account_id:\d+>/zen-post/run-workflow/<id:\d+>', 'route' => 'admin/zen-post/run-workflow'],
                ['pattern' => 'admin/zen-account/<account_id:\d+>/zen-post/send-log/<id:\d+>', 'route' => 'admin/zen-post/send-log'],
                ['pattern' => 'admin/zen-account/<account_id:\d+>/zen-post/send-log-data/<id:\d+>', 'route' => 'admin/zen-post/send-log-data'],
                ['pattern' => 'admin/zen-account/<account_id:\d+>/zen-post/update/<id:\d+>', 'route' => 'admin/zen-post/update'],
                ['pattern' => 'admin/zen-account/<account_id:\d+>/zen-post/delete/<id:\d+>', 'route' => 'admin/zen-post/delete'],
                ['pattern' => 'admin/zen-account/<account_id:\d+>/zen-post/set-status/<id:\d+>', 'route' => 'admin/zen-post/set-status'],
                // Admin
                'admin/login' => 'admin/auth/login',
                'admin/logout' => 'admin/auth/logout',
                'admin/error' => 'admin/auth/error',
            ],
        ],
    ],
    'modules' => [
        'admin' => [
            'class' => 'app\modules\admin\Module',
        ],
        'api' => [
            'class' => 'app\modules\api\Module',
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
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
