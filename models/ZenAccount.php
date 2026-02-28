<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $url
 * @property string|null $theme
 * @property int|null $created_at
 * @property int|null $updated_at
 *
 * @property ZenPost[] $posts
 */
class ZenAccount extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%zen_account}}';
    }

    public function rules(): array
    {
        return [
            [['name', 'url'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['url'], 'string', 'max' => 500],
            [['theme'], 'string', 'max' => 255],
            [['description'], 'string'],
            [['created_at', 'updated_at'], 'integer'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Название',
            'description' => 'Описание',
            'url' => 'Ссылка на канал',
            'theme' => 'Тематика',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлён',
        ];
    }

    public function getPosts()
    {
        return $this->hasMany(ZenPost::class, ['account_id' => 'id']);
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
