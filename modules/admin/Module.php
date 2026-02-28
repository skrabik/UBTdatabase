<?php

namespace app\modules\admin;

use Yii;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'app\modules\admin\controllers';

    public function init()
    {
        parent::init();
        $this->layout = 'main';
        Yii::$app->set('user', [
            'class' => \yii\web\User::class,
            'identityClass' => \app\models\User::class,
            'enableAutoLogin' => true,
            'loginUrl' => ['/admin/login'],
        ]);
    }
}
