<?php

namespace app\modules\admin\controllers;

use app\models\ZenAccount;
use app\models\ZenPost;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class ZenPostController extends Controller
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

    public function actionIndex(int $account_id = null): string
    {
        $query = ZenPost::find()->with('account')->orderBy(['id' => SORT_DESC]);
        if ($account_id !== null) {
            $query->andWhere(['account_id' => $account_id]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'accountId' => $account_id,
        ]);
    }

    public function actionCreate(int $account_id = null): string|\yii\web\Response
    {
        $model = new ZenPost();
        if ($account_id !== null) {
            $model->account_id = $account_id;
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Пост создан.');
            return $this->redirect(['index', 'account_id' => $model->account_id]);
        }

        return $this->render('form', ['model' => $model]);
    }

    public function actionUpdate(int $id): string|\yii\web\Response
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Пост обновлён.');
            return $this->redirect(['index', 'account_id' => $model->account_id]);
        }

        return $this->render('form', ['model' => $model]);
    }

    public function actionDelete(int $id): \yii\web\Response
    {
        $model = $this->findModel($id);
        $accountId = $model->account_id;
        $model->delete();
        Yii::$app->session->setFlash('success', 'Пост удалён.');
        return $this->redirect(['index', 'account_id' => $accountId]);
    }

    public function actionSetStatus(int $id, string $status): \yii\web\Response
    {
        $model = $this->findModel($id);
        if (in_array($status, [ZenPost::STATUS_DRAFT, ZenPost::STATUS_PENDING, ZenPost::STATUS_POSTED], true)) {
            $model->status = $status;
            if ($status === ZenPost::STATUS_POSTED) {
                $model->posted_at = time();
            }
            $model->save(false);
            Yii::$app->session->setFlash('success', 'Статус обновлён.');
        }
        return $this->redirect(['index', 'account_id' => $model->account_id]);
    }

    protected function findModel(int $id): ZenPost
    {
        $model = ZenPost::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Пост не найден.');
        }
        return $model;
    }
}
