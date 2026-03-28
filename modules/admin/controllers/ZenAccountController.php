<?php

namespace app\modules\admin\controllers;

use app\models\User;
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
        $ownerId = Yii::$app->request->get('owner_id');
        $query = ZenAccount::find()->with(['themeRelations', 'owner']);

        if ($ownerId !== null && $ownerId !== '') {
            $query->andWhere(['owner_id' => (int) $ownerId]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query->orderBy(['id' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'ownerOptions' => $this->getOwnerOptions(),
            'selectedOwnerId' => $ownerId === null || $ownerId === '' ? null : (int) $ownerId,
        ]);
    }

    public function actionCreate(): string|\yii\web\Response
    {
        $model = new ZenAccount();
        $model->scenario = ZenAccount::SCENARIO_CREATE;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Аккаунт создан.');
            return $this->redirect(['index']);
        }

        return $this->render('form', [
            'model' => $model,
            'ownerOptions' => $this->getOwnerOptions(),
        ]);
    }

    public function actionUpdate(int $id): string|\yii\web\Response
    {
        $model = $this->findModel($id);
        $model->scenario = ZenAccount::SCENARIO_UPDATE;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Аккаунт обновлён.');
            return $this->redirect(['index']);
        }

        return $this->render('form', [
            'model' => $model,
            'ownerOptions' => $this->getOwnerOptions(),
        ]);
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

    protected function getOwnerOptions(): array
    {
        return User::find()
            ->select(['username', 'id'])
            ->orderBy(['username' => SORT_ASC])
            ->indexBy('id')
            ->column();
    }
}
