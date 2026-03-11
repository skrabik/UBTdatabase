<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/load-env.php';

$yiiDebug = filter_var(getenv('YII_DEBUG') ?: '0', FILTER_VALIDATE_BOOLEAN);
$yiiEnv = getenv('YII_ENV') ?: 'prod';

defined('YII_DEBUG') or define('YII_DEBUG', $yiiDebug);
defined('YII_ENV') or define('YII_ENV', $yiiEnv);

require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';

(new yii\web\Application($config))->run();
