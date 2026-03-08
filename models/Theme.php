<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Тематика каналов (многие ко многим с каналами).
 *
 * @property int $id
 * @property string $name
 *
 * @property ZenAccount[] $zenAccounts
 */
class Theme extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%theme}}';
    }

    public function rules(): array
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Название',
        ];
    }

    public function getZenAccounts()
    {
        return $this->hasMany(ZenAccount::class, ['id' => 'zen_account_id'])
            ->viaTable('{{%zen_account_theme}}', ['theme_id' => 'id']);
    }
}
