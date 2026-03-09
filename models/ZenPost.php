<?php

namespace app\models;

use app\jobs\SendZenPostJob;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $account_id
 * @property string $title
 * @property string|null $content
 * @property string $status
 * @property string|null $remote_job_id
 * @property string $remote_publish_status
 * @property string|null $remote_publish_message
 * @property string|null $remote_publish_logs_json
 * @property string|null $remote_publish_response_json
 * @property int|null $remote_publish_started_at
 * @property int|null $remote_publish_finished_at
 * @property int|null $scheduled_at
 * @property int|null $posted_at
 * @property int|null $created_at
 * @property int|null $updated_at
 *
 * @property ZenAccount $account
 */
class ZenPost extends ActiveRecord
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_POSTED = 'posted';

    public const REMOTE_PUBLISH_NEW = 'new';
    public const REMOTE_PUBLISH_QUEUED = 'queued';
    public const REMOTE_PUBLISH_RUNNING = 'running';
    public const REMOTE_PUBLISH_SUCCESS = 'success';
    public const REMOTE_PUBLISH_ERROR = 'error';

    public static function tableName(): string
    {
        return '{{%zen_post}}';
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'Черновик',
            self::STATUS_PENDING => 'Ожидание публикации',
            self::STATUS_POSTED => 'Опубликован',
        ];
    }

    public static function remotePublishStatusLabels(): array
    {
        return [
            self::REMOTE_PUBLISH_NEW => 'Не запускалось',
            self::REMOTE_PUBLISH_QUEUED => 'В очереди',
            self::REMOTE_PUBLISH_RUNNING => 'Выполняется',
            self::REMOTE_PUBLISH_SUCCESS => 'Успешно',
            self::REMOTE_PUBLISH_ERROR => 'Ошибка',
        ];
    }

    public function rules(): array
    {
        return [
            [['account_id', 'title'], 'required'],
            [['account_id', 'scheduled_at', 'posted_at', 'created_at', 'updated_at', 'remote_publish_started_at', 'remote_publish_finished_at'], 'integer'],
            [['status'], 'in', 'range' => array_keys(self::statusLabels())],
            [['remote_publish_status'], 'in', 'range' => array_keys(self::remotePublishStatusLabels())],
            [['status'], 'default', 'value' => self::STATUS_PENDING],
            [['remote_publish_status'], 'default', 'value' => self::REMOTE_PUBLISH_NEW],
            [['title'], 'string', 'max' => 500],
            [['content', 'remote_publish_message', 'remote_publish_logs_json', 'remote_publish_response_json'], 'string'],
            [['remote_job_id'], 'string', 'max' => 64],
            [['account_id'], 'exist', 'targetClass' => ZenAccount::class, 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'account_id' => 'Аккаунт',
            'title' => 'Заголовок',
            'content' => 'Текст',
            'status' => 'Статус',
            'remote_job_id' => 'ID job',
            'remote_publish_status' => 'Статус отправки',
            'remote_publish_message' => 'Сообщение отправки',
            'remote_publish_started_at' => 'Запуск отправки',
            'remote_publish_finished_at' => 'Завершение отправки',
            'scheduled_at' => 'Запланировано на',
            'posted_at' => 'Опубликовано в',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлён',
        ];
    }

    public function getAccount()
    {
        return $this->hasOne(ZenAccount::class, ['id' => 'account_id']);
    }

    public function getRemotePublishLogs(): array
    {
        $decoded = json_decode((string) $this->remote_publish_logs_json, true);
        return is_array($decoded) ? array_values(array_map('strval', $decoded)) : [];
    }

    public function getRemotePublishResponse(): array
    {
        $decoded = json_decode((string) $this->remote_publish_response_json, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function beforeSave($insert): bool
    {
        if (parent::beforeSave($insert)) {
            $this->updated_at = time();
            if ($insert) {
                $this->created_at = time();
            }
            return true;
        }
        return false;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if ($insert && !(\Yii::$app->params['skipPostArticleSend'] ?? false)) {
            $this->enqueueRemotePublish();
        }
    }

    /**
     * Ставит публикацию поста в очередь и связывает job с записью статьи.
     */
    public function enqueueRemotePublish(): void
    {
        try {
            $jobId = \Yii::$app->queue->push(new SendZenPostJob([
                'postId' => (int) $this->id,
            ]));

            $this->remote_job_id = (string) $jobId;
            $this->remote_publish_status = self::REMOTE_PUBLISH_QUEUED;
            $this->remote_publish_message = 'Задача поставлена в очередь.';
            $this->remote_publish_logs_json = json_encode([], JSON_UNESCAPED_UNICODE);
            $this->remote_publish_response_json = null;
            $this->remote_publish_started_at = null;
            $this->remote_publish_finished_at = null;
            $this->save(false, [
                'remote_job_id',
                'remote_publish_status',
                'remote_publish_message',
                'remote_publish_logs_json',
                'remote_publish_response_json',
                'remote_publish_started_at',
                'remote_publish_finished_at',
                'updated_at',
            ]);

            \Yii::info([
                'msg' => 'Пост поставлен в очередь на удалённую публикацию',
                'post_id' => $this->id,
                'job_id' => $jobId,
            ], __METHOD__);
        } catch (\Throwable $e) {
            $this->remote_publish_status = self::REMOTE_PUBLISH_ERROR;
            $this->remote_publish_message = $e->getMessage();
            $this->remote_publish_logs_json = json_encode([], JSON_UNESCAPED_UNICODE);
            $this->remote_publish_response_json = json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
                'logs' => [],
            ], JSON_UNESCAPED_UNICODE);
            $this->remote_publish_finished_at = time();
            $this->save(false, [
                'remote_publish_status',
                'remote_publish_message',
                'remote_publish_logs_json',
                'remote_publish_response_json',
                'remote_publish_finished_at',
                'updated_at',
            ]);

            \Yii::error([
                'msg' => 'Не удалось поставить пост в очередь',
                'post_id' => $this->id,
                'error' => $e->getMessage(),
            ], __METHOD__);
        }
    }
}
