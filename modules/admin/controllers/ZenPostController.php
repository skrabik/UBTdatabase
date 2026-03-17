<?php

namespace app\modules\admin\controllers;

use app\models\ZenAccount;
use app\models\ZenPost;
use app\models\ZenPostPublishAttempt;
use app\services\DifyWorkflowApiService;
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
            'query' => ZenPost::find()->with(['account', 'latestPublishAttempt'])->andWhere(['account_id' => $account_id])->orderBy(['id' => SORT_DESC]),
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
                $model->posted_at = $model->posted_at ?: time();
            } else {
                $model->posted_at = null;
            }
            $model->save(false);
            Yii::$app->session->setFlash('success', 'Статус обновлён.');
        }
        return $this->redirect(['/admin/zen-post/index', 'account_id' => $account_id]);
    }

    /**
     * Ставит пост в очередь на отправку по кнопке.
     */
    public function actionSend(int $account_id, int $id): \yii\web\Response
    {
        $this->findAccount($account_id);
        $model = $this->findModel($id);
        if ((int) $model->account_id !== (int) $account_id) {
            throw new NotFoundHttpException('Пост не принадлежит этому аккаунту.');
        }

        $attempt = $model->enqueueRemotePublish();
        $flashType = $attempt->status === ZenPostPublishAttempt::STATUS_ERROR ? 'danger' : 'success';
        $flashMessage = $attempt->status === ZenPostPublishAttempt::STATUS_ERROR
            ? 'Не удалось поставить пост в очередь.'
            : 'Пост поставлен в очередь на отправку.';
        Yii::$app->session->setFlash($flashType, $flashMessage);

        return $this->redirect(['/admin/zen-post/send-log', 'account_id' => $account_id, 'id' => $model->id]);
    }

    public function actionRunWorkflow(int $account_id, int $id): \yii\web\Response
    {
        $account = $this->findAccount($account_id);
        $model = $this->findModel($id);
        if ((int) $model->account_id !== (int) $account_id) {
            throw new NotFoundHttpException('Пост не принадлежит этому аккаунту.');
        }

        if (trim((string) $account->workflow_url) === '') {
            Yii::$app->session->setFlash('danger', 'Для аккаунта не задан Workflow URL.');
            return $this->redirect(['/admin/zen-post/update', 'account_id' => $account_id, 'id' => $model->id]);
        }

        if (trim((string) $account->workflow_key) === '') {
            Yii::$app->session->setFlash('danger', 'Для аккаунта не задан Workflow Key.');
            return $this->redirect(['/admin/zen-post/update', 'account_id' => $account_id, 'id' => $model->id]);
        }

        if (trim((string) $model->scenario) === '') {
            Yii::$app->session->setFlash('danger', 'У поста пустой scenario, запуск workflow невозможен.');
            return $this->redirect(['/admin/zen-post/update', 'account_id' => $account_id, 'id' => $model->id]);
        }

        try {
            $service = DifyWorkflowApiService::forAccount($account);
            $httpCode = $service->triggerPipelineUrl(
                (string) $account->workflow_url,
                [
                    'scenario' => (string) $model->scenario,
                    'post_id' => (int) $model->id,
                    'channel_id' => (int) $account->id,
                ],
                'zen-post-' . $model->id,
                [],
                'zen-post-' . $model->id . '-' . time()
            );

            Yii::$app->session->setFlash('success', 'Workflow запущен. HTTP ' . $httpCode . '.');
        } catch (\Throwable $e) {
            Yii::warning([
                'msg' => 'Не удалось запустить workflow для поста',
                'post_id' => $model->id,
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ], __METHOD__);

            Yii::$app->session->setFlash('danger', 'Не удалось запустить workflow: ' . $e->getMessage());
        }

        return $this->redirect(['/admin/zen-post/update', 'account_id' => $account_id, 'id' => $model->id]);
    }

    /**
     * Страница результата последней отправки поста на внешний сервис.
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
        $attempt = $this->findLatestAttempt($model);
        if ($attempt === null) {
            return [
                'job_id' => null,
                'queue_status' => ZenPostPublishAttempt::STATUS_NEW,
                'queue_status_label' => ZenPostPublishAttempt::statusLabels()[ZenPostPublishAttempt::STATUS_NEW],
                'is_finished' => true,
                'message' => 'Отправка ещё не запускалась.',
                'response' => [],
                'logs' => [],
            ];
        }

        $responseData = $attempt->getResponse();
        $logs = $attempt->getLogs();
        $labels = ZenPostPublishAttempt::statusLabels();

        return [
            'job_id' => $attempt->job_id,
            'queue_status' => $attempt->status,
            'queue_status_label' => $labels[$attempt->status] ?? $attempt->status,
            'is_finished' => in_array($attempt->status, [ZenPostPublishAttempt::STATUS_SUCCESS, ZenPostPublishAttempt::STATUS_ERROR], true),
            'message' => $attempt->message ?: ($responseData['message'] ?? ''),
            'response' => $responseData,
            'logs' => $logs,
        ];
    }

    protected function findLatestAttempt(ZenPost $model): ?ZenPostPublishAttempt
    {
        return $model->latestPublishAttempt ?: ZenPostPublishAttempt::find()
            ->andWhere(['zen_post_id' => $model->id])
            ->orderBy(['id' => SORT_DESC])
            ->one();
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
