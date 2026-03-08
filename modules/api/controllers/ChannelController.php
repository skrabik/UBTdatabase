<?php

namespace app\modules\api\controllers;

use app\models\ZenAccount;
use app\models\ZenPost;
use Yii;
use yii\filters\ContentNegotiator;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * API каналов (ZenAccount): список, просмотр, создание, удаление.
 */
class ChannelController extends Controller
{
    /** Отключаем CSRF для API (запросы без cookie). */
    public $enableCsrfValidation = false;

    public function behaviors(): array
    {
        return [
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
            'verbFilter' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['get'],
                    'view' => ['get'],
                    'create' => ['post'],
                    'delete' => ['delete'],
                    'posts' => ['get'],
                    'view-post' => ['get'],
                    'create-post' => ['post'],
                    'update-post' => ['put', 'patch'],
                    'delete-post' => ['delete'],
                ],
            ],
        ];
    }

    /**
     * Список каналов.
     * GET /api/channels
     */
    public function actionIndex(): array
    {
        $models = ZenAccount::find()->orderBy(['id' => SORT_DESC])->all();
        return [
            'items' => array_map([$this, 'channelToArray'], $models),
            'total' => count($models),
        ];
    }

    /**
     * Один канал по id или slug.
     * GET /api/channels/<id>  или  GET /api/channels/<slug>
     */
    public function actionView(string $idOrSlug): array
    {
        return $this->channelToArray($this->findModel($idOrSlug));
    }

    /**
     * Создание канала.
     * POST /api/channels
     * Body JSON: { "name": "...", "url": "...", "description": "...", "theme": "..." }
     */
    public function actionCreate(): array|Response
    {
        $model = new ZenAccount();
        $body = Yii::$app->request->getBodyParams();

        $model->name = $body['name'] ?? '';
        $model->slug = isset($body['slug']) ? trim((string) $body['slug']) : '';
        $model->url = $body['url'] ?? '';
        $model->description = $body['description'] ?? null;
        $model->theme = $body['theme'] ?? null;
        $model->login = isset($body['login']) ? (string) $body['login'] : null;
        $model->password = isset($body['password']) ? (string) $body['password'] : null;

        if (!$model->validate()) {
            Yii::$app->response->setStatusCode(422);
            return ['errors' => $model->getFirstErrors()];
        }

        if (!$model->save(false)) {
            throw new ServerErrorHttpException('Не удалось создать канал.');
        }

        Yii::$app->response->setStatusCode(201);
        return $this->channelToArray($model);
    }

    /**
     * Список постов канала (канал по id или slug).
     * GET /api/channels/<idOrSlug>/posts
     */
    public function actionPosts(string $idOrSlug): array
    {
        $channel = $this->findModel($idOrSlug);
        $models = ZenPost::find()->andWhere(['account_id' => $channel->id])->orderBy(['id' => SORT_DESC])->all();
        return [
            'items' => array_map([$this, 'postToArray'], $models),
            'total' => count($models),
        ];
    }

    /**
     * Один пост канала по id поста (канал по id или slug).
     * GET /api/channels/<idOrSlug>/posts/<id>
     */
    public function actionViewPost(string $idOrSlug, int $id): array
    {
        $channel = $this->findModel($idOrSlug);
        $post = $this->findPost($id);
        if ((int) $post->account_id !== (int) $channel->id) {
            throw new NotFoundHttpException('Пост не найден в этом канале.');
        }
        return $this->postToArray($post);
    }

    /**
     * Добавление поста в канал (канал по id или slug).
     * POST /api/channels/<idOrSlug>/posts
     * Body JSON: { "title": "...", "content": "...", "status": "draft|pending|posted", "scheduled_at": unix }
     */
    public function actionCreatePost(string $idOrSlug): array|Response
    {
        $channel = $this->findModel($idOrSlug);
        $body = Yii::$app->request->getBodyParams();

        $model = new ZenPost();
        $model->account_id = $channel->id;
        $model->title = $body['title'] ?? '';
        $model->content = isset($body['content']) ? (string) $body['content'] : null;
        $model->status = $body['status'] ?? ZenPost::STATUS_PENDING;
        $model->scheduled_at = isset($body['scheduled_at']) ? (int) $body['scheduled_at'] : null;

        if (!in_array($model->status, [ZenPost::STATUS_DRAFT, ZenPost::STATUS_PENDING, ZenPost::STATUS_POSTED], true)) {
            $model->status = ZenPost::STATUS_PENDING;
        }

        if (!$model->validate()) {
            Yii::$app->response->setStatusCode(422);
            return ['errors' => $model->getFirstErrors()];
        }

        if (!$model->save(false)) {
            throw new ServerErrorHttpException('Не удалось создать пост.');
        }

        Yii::$app->response->setStatusCode(201);
        return $this->postToArray($model);
    }

    /**
     * Обновление поста (канал по id или slug).
     * PUT/PATCH /api/channels/<idOrSlug>/posts/<id>
     */
    public function actionUpdatePost(string $idOrSlug, int $id): array|Response
    {
        $channel = $this->findModel($idOrSlug);
        $post = $this->findPost($id);
        if ((int) $post->account_id !== (int) $channel->id) {
            throw new NotFoundHttpException('Пост не найден в этом канале.');
        }
        $body = Yii::$app->request->getBodyParams();
        if (isset($body['title'])) {
            $post->title = $body['title'];
        }
        if (array_key_exists('content', $body)) {
            $post->content = $body['content'] === null ? null : (string) $body['content'];
        }
        if (isset($body['status']) && in_array($body['status'], [ZenPost::STATUS_DRAFT, ZenPost::STATUS_PENDING, ZenPost::STATUS_POSTED], true)) {
            $post->status = $body['status'];
            if ($post->status === ZenPost::STATUS_POSTED && !$post->posted_at) {
                $post->posted_at = time();
            }
        }
        if (array_key_exists('scheduled_at', $body)) {
            $post->scheduled_at = $body['scheduled_at'] === null ? null : (int) $body['scheduled_at'];
        }
        if (!$post->validate()) {
            Yii::$app->response->setStatusCode(422);
            return ['errors' => $post->getFirstErrors()];
        }
        $post->save(false);
        return $this->postToArray($post);
    }

    /**
     * Удаление поста (канал по id или slug).
     * DELETE /api/channels/<idOrSlug>/posts/<id>
     */
    public function actionDeletePost(string $idOrSlug, int $id)
    {
        $channel = $this->findModel($idOrSlug);
        $post = $this->findPost($id);
        if ((int) $post->account_id !== (int) $channel->id) {
            throw new NotFoundHttpException('Пост не найден в этом канале.');
        }
        $post->delete();
        Yii::$app->response->setStatusCode(204);
        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->content = '';
        return '';
    }

    /**
     * Удаление канала по id или slug.
     * DELETE /api/channels/<id>  или  DELETE /api/channels/<slug>
     */
    public function actionDelete(string $idOrSlug)
    {
        $this->findModel($idOrSlug)->delete();
        Yii::$app->response->setStatusCode(204);
        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->content = '';
        return '';
    }

    protected function findModel(string|int $idOrSlug): ZenAccount
    {
        $model = ZenAccount::findByIdOrSlug($idOrSlug);
        if ($model === null) {
            throw new NotFoundHttpException('Канал не найден.');
        }
        return $model;
    }

    protected function findPost(int $id): ZenPost
    {
        $model = ZenPost::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Пост не найден.');
        }
        return $model;
    }

    protected function channelToArray(ZenAccount $channel): array
    {
        return [
            'id' => (int) $channel->id,
            'slug' => $channel->slug,
            'name' => $channel->name,
            'url' => $channel->url,
            'description' => $channel->description,
            'theme' => $channel->theme,
            'login' => $channel->login,
            'password' => $channel->password,
            'created_at' => $channel->created_at,
            'updated_at' => $channel->updated_at,
        ];
    }

    protected function postToArray(ZenPost $post): array
    {
        return [
            'id' => (int) $post->id,
            'account_id' => (int) $post->account_id,
            'title' => $post->title,
            'content' => $post->content,
            'status' => $post->status,
            'scheduled_at' => $post->scheduled_at,
            'posted_at' => $post->posted_at,
            'created_at' => $post->created_at,
            'updated_at' => $post->updated_at,
        ];
    }
}
