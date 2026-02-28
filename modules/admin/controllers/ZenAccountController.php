<?php

namespace app\modules\admin\controllers;

use app\models\ZenAccount;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class ZenAccountController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [['allow' => true, 'roles' => ['admin']]],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $dataProvider = new ActiveDataProvider([
            'query' => ZenAccount::find()->orderBy(['id' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('index', ['dataProvider' => $dataProvider]);
    }

    public function actionCreate(): string|\yii\web\Response
    {
        $model = new ZenAccount();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Аккаунт создан.');
            return $this->redirect(['index']);
        }

        return $this->render('form', ['model' => $model]);
    }

    public function actionUpdate(int $id): string|\yii\web\Response
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Аккаунт обновлён.');
            return $this->redirect(['index']);
        }

        return $this->render('form', ['model' => $model]);
    }

    public function actionDelete(int $id): \yii\web\Response
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Аккаунт удалён.');
        return $this->redirect(['index']);
    }

    protected function findModel(int $id): ZenAccount
    {
        $model = ZenAccount::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Аккаунт не найден.');
        }
        return $model;
    }
}
