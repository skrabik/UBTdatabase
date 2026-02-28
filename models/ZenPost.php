<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $account_id
 * @property string $title
 * @property string|null $content
 * @property string $status
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

    public static function tableName(): string
    {
        return '{{%zen_post}}';
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'Черновик',
            self::STATUS_PENDING => 'Ожидает постинга',
            self::STATUS_POSTED => 'Запощено',
        ];
    }

    public function rules(): array
    {
        return [
            [['account_id', 'title'], 'required'],
            [['account_id', 'scheduled_at', 'posted_at', 'created_at', 'updated_at'], 'integer'],
            [['status'], 'in', 'range' => array_keys(self::statusLabels())],
            [['status'], 'default', 'value' => self::STATUS_PENDING],
            [['title'], 'string', 'max' => 500],
            [['content'], 'string'],
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
