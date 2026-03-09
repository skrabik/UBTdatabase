<?php

namespace app\jobs;

use app\models\ZenPost;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class SendZenPostJob extends BaseObject implements JobInterface
{
    public int $postId;

    public function execute($queue): void
    {
        $post = ZenPost::findOne($this->postId);
        if ($post === null) {
            Yii::warning([
                'msg' => 'Queue job skipped: post not found',
                'post_id' => $this->postId,
            ], __METHOD__);
            return;
        }

        $post->remote_publish_status = ZenPost::REMOTE_PUBLISH_RUNNING;
        $post->remote_publish_started_at = time();
        $post->remote_publish_finished_at = null;
        $post->remote_publish_message = 'Запрос выполняется...';
        $post->save(false, [
            'remote_publish_status',
            'remote_publish_started_at',
            'remote_publish_finished_at',
            'remote_publish_message',
            'updated_at',
        ]);

        $account = $post->account;
        if ($account === null) {
            $this->finishWithError($post, 'Аккаунт не найден.', [
                'status' => 'error',
                'account_slug' => '',
                'message' => 'Аккаунт не найден.',
                'logs' => [],
            ]);
            return;
        }

        $url = Yii::$app->params['postArticleUrl'] ?? '';
        if ($url === '') {
            $this->finishWithError($post, 'POST_ARTICLE_URL не задан.', [
                'status' => 'error',
                'account_slug' => $account->slug,
                'message' => 'POST_ARTICLE_URL не задан.',
                'logs' => [],
            ]);
            return;
        }

        $payload = [
            'account-slug' => $account->slug,
            'login' => $account->login ?? '',
            'password' => $account->password ?? '',
            'article_title' => $post->title,
            'article_text' => (string) $post->content,
            'headless' => (bool) (Yii::$app->params['postArticleHeadless'] ?? false),
        ];

        Yii::info([
            'msg' => 'Queue job: отправляем POST на внешний сервис',
            'post_id' => $post->id,
            'url' => $url,
            'account_slug' => $account->slug,
            'headless' => $payload['headless'],
        ], __METHOD__);

        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => 180,
            ]);
            $raw = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);
        } catch (\Throwable $e) {
            $this->finishWithError($post, $e->getMessage(), [
                'status' => 'error',
                'account_slug' => $account->slug,
                'message' => $e->getMessage(),
                'logs' => [],
            ]);
            return;
        }

        if ($raw === false || $curlError !== '') {
            $this->finishWithError($post, $curlError ?: 'Ошибка curl запроса.', [
                'status' => 'error',
                'account_slug' => $account->slug,
                'message' => $curlError ?: 'Ошибка curl запроса.',
                'logs' => [],
            ]);
            return;
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $this->finishWithError($post, 'Внешний сервис вернул невалидный JSON.', [
                'status' => 'error',
                'account_slug' => $account->slug,
                'message' => 'Внешний сервис вернул невалидный JSON.',
                'logs' => $raw ? [$raw] : [],
            ]);
            return;
        }

        if ($httpCode >= 400) {
            $this->finishWithError($post, 'Внешний сервис вернул HTTP ' . $httpCode . '.', $data);
            return;
        }

        $post->remote_publish_status = ($data['status'] ?? null) === 'ok'
            ? ZenPost::REMOTE_PUBLISH_SUCCESS
            : ZenPost::REMOTE_PUBLISH_ERROR;
        $post->remote_publish_message = (string) ($data['message'] ?? '');
        $post->remote_publish_logs_json = json_encode(
            is_array($data['logs'] ?? null) ? array_values($data['logs']) : [],
            JSON_UNESCAPED_UNICODE
        );
        $post->remote_publish_response_json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $post->remote_publish_finished_at = time();
        $post->save(false, [
            'remote_publish_status',
            'remote_publish_message',
            'remote_publish_logs_json',
            'remote_publish_response_json',
            'remote_publish_finished_at',
            'updated_at',
        ]);

        Yii::info([
            'msg' => 'Queue job: получен ответ внешнего сервиса',
            'post_id' => $post->id,
            'status' => $data['status'] ?? null,
            'logs_count' => is_array($data['logs'] ?? null) ? count($data['logs']) : 0,
        ], __METHOD__);
    }

    protected function finishWithError(ZenPost $post, string $message, array $responseData): void
    {
        $post->remote_publish_status = ZenPost::REMOTE_PUBLISH_ERROR;
        $post->remote_publish_message = $message;
        $post->remote_publish_logs_json = json_encode(
            is_array($responseData['logs'] ?? null) ? array_values($responseData['logs']) : [],
            JSON_UNESCAPED_UNICODE
        );
        $post->remote_publish_response_json = json_encode($responseData, JSON_UNESCAPED_UNICODE);
        $post->remote_publish_finished_at = time();
        $post->save(false, [
            'remote_publish_status',
            'remote_publish_message',
            'remote_publish_logs_json',
            'remote_publish_response_json',
            'remote_publish_finished_at',
            'updated_at',
        ]);

        Yii::warning([
            'msg' => 'Queue job: ошибка отправки поста',
            'post_id' => $post->id,
            'error' => $message,
        ], __METHOD__);
    }
}
