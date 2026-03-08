<?php

namespace app\modules\admin\controllers;

use app\models\Theme;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class ThemeController extends Controller
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
            'query' => Theme::find()->orderBy(['name' => SORT_ASC]),
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('index', ['dataProvider' => $dataProvider]);
    }

    public function actionCreate(): string|\yii\web\Response
    {
        $model = new Theme();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Тематика создана.');
            return $this->redirect(['index']);
        }

        return $this->render('form', ['model' => $model]);
    }

    public function actionUpdate(int $id): string|\yii\web\Response
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Тематика обновлена.');
            return $this->redirect(['index']);
        }

        return $this->render('form', ['model' => $model]);
    }

    public function actionDelete(int $id): \yii\web\Response
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Тематика удалена.');
        return $this->redirect(['index']);
    }

    protected function findModel(int $id): Theme
    {
        $model = Theme::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Тематика не найдена.');
        }
        return $model;
    }
}
