<?php

namespace app\models;

use app\jobs\SendZenPostJob;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $account_id
 * @property string $title
 * @property string|null $scenario
 * @property string|null $dify_pipeline_url
 * @property string|null $content
 * @property string $status
 * @property int|null $scheduled_at
 * @property int|null $posted_at
 * @property int|null $created_at
 * @property int|null $updated_at
 * @property int|null $deleted_at
 *
 * @property ZenAccount $account
 * @property ZenPostPublishAttempt[] $publishAttempts
 * @property ZenPostPublishAttempt|null $latestPublishAttempt
 */
class ZenPost extends ActiveRecord
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_POSTED = 'posted';

    public static function tableName(): string
    {
        return '{{%zen_post}}';
    }

    public static function find(): ActiveQuery
    {
        return parent::find()->andWhere(['deleted_at' => null]);
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'Черновик',
            self::STATUS_PENDING => 'Ожидание публикации',
            self::STATUS_POSTED => 'Опубликован',
        ];
    }

    public function rules(): array
    {
        return [
            [['account_id', 'title'], 'required'],
            [['account_id', 'scheduled_at', 'posted_at', 'created_at', 'updated_at', 'deleted_at'], 'integer'],
            [['status'], 'in', 'range' => array_keys(self::statusLabels())],
            [['status'], 'default', 'value' => self::STATUS_PENDING],
            [['title'], 'string', 'max' => 500],
            [['scenario', 'content'], 'string'],
            [['dify_pipeline_url'], 'string', 'max' => 2048],
            [['account_id'], 'exist', 'targetClass' => ZenAccount::class, 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'account_id' => 'Аккаунт',
            'title' => 'Заголовок',
            'scenario' => 'Тема (сценарий)',
            'dify_pipeline_url' => 'URL Dify pipeline',
            'content' => 'Текст',
            'status' => 'Статус',
            'scheduled_at' => 'Запланировано на',
            'posted_at' => 'Опубликовано в',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлён',
            'deleted_at' => 'Удалён',
        ];
    }

    public function getAccount()
    {
        return $this->hasOne(ZenAccount::class, ['id' => 'account_id']);
    }

    public function getPublishAttempts()
    {
        return $this->hasMany(ZenPostPublishAttempt::class, ['zen_post_id' => 'id'])->orderBy(['id' => SORT_DESC]);
    }

    public function getLatestPublishAttempt()
    {
        return $this->hasOne(ZenPostPublishAttempt::class, ['zen_post_id' => 'id'])->orderBy(['id' => SORT_DESC]);
    }

    public function beforeSave($insert): bool
    {
        if (parent::beforeSave($insert)) {
            $this->updated_at = time();
            if ($insert) {
                $this->created_at = time();
            }
            if ($this->status === self::STATUS_POSTED) {
                $this->posted_at = $this->posted_at ?: time();
            } else {
                $this->posted_at = null;
            }
            return true;
        }
        return false;
    }

    public function enqueueRemotePublish(): ZenPostPublishAttempt
    {
        $attempt = new ZenPostPublishAttempt([
            'zen_post_id' => (int) $this->id,
            'status' => ZenPostPublishAttempt::STATUS_NEW,
            'logs_json' => json_encode([], JSON_UNESCAPED_UNICODE),
        ]);
        $attempt->save(false);

        try {
            $jobId = \Yii::$app->queue->push(new SendZenPostJob([
                'attemptId' => (int) $attempt->id,
            ]));

            $attempt->job_id = (string) $jobId;
            $attempt->status = ZenPostPublishAttempt::STATUS_QUEUED;
            $attempt->message = 'Задача поставлена в очередь.';
            $attempt->logs_json = json_encode([], JSON_UNESCAPED_UNICODE);
            $attempt->response_json = null;
            $attempt->started_at = null;
            $attempt->finished_at = null;
            $attempt->save(false, [
                'job_id',
                'status',
                'message',
                'logs_json',
                'response_json',
                'started_at',
                'finished_at',
                'updated_at',
            ]);

            \Yii::info([
                'msg' => 'Пост поставлен в очередь на удалённую публикацию',
                'post_id' => $this->id,
                'job_id' => $jobId,
            ], __METHOD__);
        } catch (\Throwable $e) {
            $attempt->status = ZenPostPublishAttempt::STATUS_ERROR;
            $attempt->message = $e->getMessage();
            $attempt->logs_json = json_encode([], JSON_UNESCAPED_UNICODE);
            $attempt->response_json = json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
                'logs' => [],
            ], JSON_UNESCAPED_UNICODE);
            $attempt->finished_at = time();
            $attempt->save(false, [
                'status',
                'message',
                'logs_json',
                'response_json',
                'finished_at',
                'updated_at',
            ]);

            \Yii::error([
                'msg' => 'Не удалось поставить пост в очередь',
                'post_id' => $this->id,
                'error' => $e->getMessage(),
            ], __METHOD__);
        }

        return $attempt;
    }

    public function delete()
    {
        if ($this->getIsNewRecord()) {
            return 0;
        }

        $time = time();
        $updated = $this->updateAttributes([
            'deleted_at' => $time,
            'updated_at' => $time,
        ]);

        return $updated === false ? false : 1;
    }
}
