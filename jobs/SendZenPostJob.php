<?php

namespace app\jobs;

use app\models\ZenPostPublishAttempt;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class SendZenPostJob extends BaseObject implements JobInterface
{
    public int $attemptId;

    public function execute($queue): void
    {
        $attempt = ZenPostPublishAttempt::find()
            ->with(['post.account'])
            ->andWhere(['id' => $this->attemptId])
            ->one();

        if ($attempt === null) {
            Yii::warning([
                'msg' => 'Queue job skipped: publish attempt not found',
                'attempt_id' => $this->attemptId,
            ], __METHOD__);
            return;
        }

        $post = $attempt->post;
        if ($post === null) {
            $this->finishWithError($attempt, 'Пост не найден.', [
                'status' => 'error',
                'message' => 'Пост не найден.',
                'logs' => [],
            ]);
            return;
        }

        $attempt->status = ZenPostPublishAttempt::STATUS_RUNNING;
        $attempt->started_at = time();
        $attempt->finished_at = null;
        $attempt->message = 'Запрос выполняется...';
        $attempt->save(false, [
            'status',
            'started_at',
            'finished_at',
            'message',
            'updated_at',
        ]);

        $account = $post->account;
        if ($account === null) {
            $this->finishWithError($attempt, 'Аккаунт не найден.', [
                'status' => 'error',
                'account_slug' => '',
                'message' => 'Аккаунт не найден.',
                'logs' => [],
            ]);
            return;
        }

        $url = Yii::$app->params['postArticleUrl'] ?? '';
        if ($url === '') {
            $this->finishWithError($attempt, 'POST_ARTICLE_URL не задан.', [
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
            'article_title' => (string) $post->title,
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
            $this->finishWithError($attempt, $e->getMessage(), [
                'status' => 'error',
                'account_slug' => $account->slug,
                'message' => $e->getMessage(),
                'logs' => [],
            ]);
            return;
        }

        if ($raw === false || $curlError !== '') {
            $this->finishWithError($attempt, $curlError ?: 'Ошибка curl запроса.', [
                'status' => 'error',
                'account_slug' => $account->slug,
                'message' => $curlError ?: 'Ошибка curl запроса.',
                'logs' => [],
            ]);
            return;
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $this->finishWithError($attempt, 'Внешний сервис вернул невалидный JSON.', [
                'status' => 'error',
                'account_slug' => $account->slug,
                'message' => 'Внешний сервис вернул невалидный JSON.',
                'logs' => $raw ? [$raw] : [],
            ]);
            return;
        }

        if ($httpCode >= 400) {
            $this->finishWithError($attempt, 'Внешний сервис вернул HTTP ' . $httpCode . '.', $data);
            return;
        }

        $attempt->status = ($data['status'] ?? null) === 'ok'
            ? ZenPostPublishAttempt::STATUS_SUCCESS
            : ZenPostPublishAttempt::STATUS_ERROR;
        $attempt->message = (string) ($data['message'] ?? '');
        $attempt->logs_json = json_encode(
            is_array($data['logs'] ?? null) ? array_values($data['logs']) : [],
            JSON_UNESCAPED_UNICODE
        );
        $attempt->response_json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $attempt->finished_at = time();
        $attempt->save(false, [
            'status',
            'message',
            'logs_json',
            'response_json',
            'finished_at',
            'updated_at',
        ]);

        Yii::info([
            'msg' => 'Queue job: получен ответ внешнего сервиса',
            'post_id' => $post->id,
            'status' => $data['status'] ?? null,
            'logs_count' => is_array($data['logs'] ?? null) ? count($data['logs']) : 0,
        ], __METHOD__);
    }

    protected function finishWithError(ZenPostPublishAttempt $attempt, string $message, array $responseData): void
    {
        $post = $attempt->post;

        $attempt->status = ZenPostPublishAttempt::STATUS_ERROR;
        $attempt->message = $message;
        $attempt->logs_json = json_encode(
            is_array($responseData['logs'] ?? null) ? array_values($responseData['logs']) : [],
            JSON_UNESCAPED_UNICODE
        );
        $attempt->response_json = json_encode($responseData, JSON_UNESCAPED_UNICODE);
        $attempt->finished_at = time();
        $attempt->save(false, [
            'status',
            'message',
            'logs_json',
            'response_json',
            'finished_at',
            'updated_at',
        ]);

        Yii::warning([
            'msg' => 'Queue job: ошибка отправки поста',
            'post_id' => $post?->id,
            'attempt_id' => $attempt->id,
            'error' => $message,
        ], __METHOD__);
    }
}
