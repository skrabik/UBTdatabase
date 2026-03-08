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

    public function actionIndex(int $account_id): string
    {
        $this->findAccount($account_id);
        $dataProvider = new ActiveDataProvider([
            'query' => ZenPost::find()->with('account')->andWhere(['account_id' => $account_id])->orderBy(['id' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'accountId' => $account_id,
        ]);
    }

    public function actionCreate(int $account_id): string|\yii\web\Response
    {
        $account = $this->findAccount($account_id);
        $model = new ZenPost();
        $model->account_id = $account_id;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Пост создан.');
            return $this->redirect(['/admin/zen-post/index', 'account_id' => $account_id]);
        }

        return $this->render('form', ['model' => $model, 'accountId' => $account_id]);
    }

    public function actionUpdate(int $account_id, int $id): string|\yii\web\Response
    {
        $this->findAccount($account_id);
        $model = $this->findModel($id);
        if ((int) $model->account_id !== (int) $account_id) {
            throw new NotFoundHttpException('Пост не принадлежит этому аккаунту.');
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Пост обновлён.');
            return $this->redirect(['/admin/zen-post/index', 'account_id' => $account_id]);
        }

        return $this->render('form', ['model' => $model, 'accountId' => $account_id]);
    }

    public function actionDelete(int $account_id, int $id): \yii\web\Response
    {
        $this->findAccount($account_id);
        $model = $this->findModel($id);
        if ((int) $model->account_id !== (int) $account_id) {
            throw new NotFoundHttpException('Пост не принадлежит этому аккаунту.');
        }
        $model->delete();
        Yii::$app->session->setFlash('success', 'Пост удалён.');
        return $this->redirect(['/admin/zen-post/index', 'account_id' => $account_id]);
    }

    public function actionSetStatus(int $account_id, int $id, string $status): \yii\web\Response
    {
        $this->findAccount($account_id);
        $model = $this->findModel($id);
        if ((int) $model->account_id !== (int) $account_id) {
            throw new NotFoundHttpException('Пост не принадлежит этому аккаунту.');
        }
        if (in_array($status, [ZenPost::STATUS_DRAFT, ZenPost::STATUS_PENDING, ZenPost::STATUS_POSTED], true)) {
            $model->status = $status;
            if ($status === ZenPost::STATUS_POSTED) {
                $model->posted_at = time();
            }
            $model->save(false);
            Yii::$app->session->setFlash('success', 'Статус обновлён.');
        }
        return $this->redirect(['/admin/zen-post/index', 'account_id' => $account_id]);
    }

    protected function findAccount(int $id): ZenAccount
    {
        $model = ZenAccount::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Аккаунт не найден.');
        }
        return $model;
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
