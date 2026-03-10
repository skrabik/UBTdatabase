<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $zen_post_id
 * @property string|null $job_id
 * @property string $status
 * @property string|null $message
 * @property string|null $logs_json
 * @property string|null $response_json
 * @property int|null $started_at
 * @property int|null $finished_at
 * @property int|null $created_at
 * @property int|null $updated_at
 *
 * @property ZenPost $post
 */
class ZenPostPublishAttempt extends ActiveRecord
{
    public const STATUS_NEW = 'new';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_ERROR = 'error';

    public static function tableName(): string
    {
        return '{{%zen_post_publish_attempt}}';
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_NEW => 'Не запускалось',
            self::STATUS_QUEUED => 'В очереди',
            self::STATUS_RUNNING => 'Выполняется',
            self::STATUS_SUCCESS => 'Успешно',
            self::STATUS_ERROR => 'Ошибка',
        ];
    }

    public function rules(): array
    {
        return [
            [['zen_post_id'], 'required'],
            [['zen_post_id', 'started_at', 'finished_at', 'created_at', 'updated_at'], 'integer'],
            [['status'], 'in', 'range' => array_keys(self::statusLabels())],
            [['status'], 'default', 'value' => self::STATUS_NEW],
            [['message', 'logs_json', 'response_json'], 'string'],
            [['job_id'], 'string', 'max' => 64],
            [['zen_post_id'], 'exist', 'targetClass' => ZenPost::class, 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'zen_post_id' => 'Пост',
            'job_id' => 'ID job',
            'status' => 'Статус отправки',
            'message' => 'Сообщение отправки',
            'started_at' => 'Запуск отправки',
            'finished_at' => 'Завершение отправки',
            'created_at' => 'Создана',
            'updated_at' => 'Обновлена',
        ];
    }

    public function getPost()
    {
        return $this->hasOne(ZenPost::class, ['id' => 'zen_post_id']);
    }

    public function getLogs(): array
    {
        $decoded = json_decode((string) $this->logs_json, true);
        return is_array($decoded) ? array_values(array_map('strval', $decoded)) : [];
    }

    public function getResponse(): array
    {
        $decoded = json_decode((string) $this->response_json, true);
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
}
