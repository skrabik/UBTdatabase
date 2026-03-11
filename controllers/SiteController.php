<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;

class SiteController extends Controller
{
    public function actionIndex(): Response
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/admin/login']);
        }
        return $this->redirect(['/admin/default/index']);
    }

    public function actionPhpInfo(): Response
    {
        ob_start();
        phpinfo();
        $content = (string) ob_get_clean();

        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->content = $content;

        return $response;
    }
}
