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
        $this->findAccount($account_id);
        $model = new ZenPost();
        $model->account_id = $account_id;

        if ($model->load(Yii::$app->request->post())) {
            Yii::$app->params['skipPostArticleSend'] = true;
            if ($model->save()) {
                $model->enqueueRemotePublish();
                Yii::$app->session->setFlash('success', 'Пост создан.');
                return $this->redirect(['/admin/zen-post/send-log', 'account_id' => $account_id, 'id' => $model->id]);
            }
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

    /**
     * Страница результата отправки поста на внешний сервис.
     */
    public function actionSendLog(int $account_id, int $id): string
    {
        $this->findAccount($account_id);
        $model = $this->findModel($id);
        if ((int) $model->account_id !== (int) $account_id) {
            throw new NotFoundHttpException('Пост не принадлежит этому аккаунту.');
        }

        return $this->render('send-log', [
            'postId' => $id,
            'indexUrl' => Yii::$app->urlManager->createUrl([
                '/admin/zen-post/index',
                'account_id' => $account_id,
            ]),
            'statusUrl' => Yii::$app->urlManager->createUrl([
                '/admin/zen-post/send-log-data',
                'account_id' => $account_id,
                'id' => $id,
            ]),
            'payload' => $this->buildSendLogPayload($model),
        ]);
    }

    /**
     * JSON-эндпоинт состояния публикации для страницы результата.
     */
    public function actionSendLogData(int $account_id, int $id)
    {
        $this->findAccount($account_id);
        $model = $this->findModel($id);
        if ((int) $model->account_id !== (int) $account_id) {
            throw new NotFoundHttpException('Пост не принадлежит этому аккаунту.');
        }

        return $this->asJson($this->buildSendLogPayload($model));
    }

    protected function buildSendLogPayload(ZenPost $model): array
    {
        $responseData = $model->getRemotePublishResponse();
        $logs = $model->getRemotePublishLogs();
        $labels = ZenPost::remotePublishStatusLabels();

        return [
            'job_id' => $model->remote_job_id,
            'queue_status' => $model->remote_publish_status,
            'queue_status_label' => $labels[$model->remote_publish_status] ?? $model->remote_publish_status,
            'is_finished' => in_array($model->remote_publish_status, [ZenPost::REMOTE_PUBLISH_SUCCESS, ZenPost::REMOTE_PUBLISH_ERROR], true),
            'message' => $model->remote_publish_message ?: ($responseData['message'] ?? ''),
            'response' => $responseData,
            'logs' => $logs,
        ];
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
