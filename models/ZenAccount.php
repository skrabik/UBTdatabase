<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\helpers\Inflector;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $url
 * @property string|null $theme
 * @property string|null $login
 * @property string|null $password
 * @property int|null $created_at
 * @property int|null $updated_at
 *
 * @property Theme[] $themeRelations
 * @property ZenPost[] $posts
 */
class ZenAccount extends ActiveRecord
{
    /** Массив id тематик для множественного выбора (не хранится в БД). */
    public $themeIds = [];

    public static function tableName(): string
    {
        return '{{%zen_account}}';
    }

    public function rules(): array
    {
        return [
            [['name', 'url'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['slug'], 'string', 'max' => 255],
            [['slug'], 'default', 'value' => ''],
            [['slug'], 'match', 'pattern' => '/^[a-z0-9\-]+$/', 'when' => function () { return $this->slug !== ''; }],
            [['slug'], 'unique', 'targetAttribute' => 'slug', 'filter' => function ($query) {
                if (!$this->isNewRecord) {
                    $query->andWhere(['not', ['id' => $this->id]]);
                }
            }, 'when' => function () { return $this->slug !== ''; }],
            [['url'], 'string', 'max' => 500],
            [['theme'], 'string', 'max' => 255],
            [['themeIds'], 'each', 'rule' => ['integer']],
            [['themeIds'], 'each', 'rule' => ['exist', 'targetClass' => Theme::class, 'targetAttribute' => 'id']],
            [['login', 'password'], 'string', 'max' => 2048],
            [['description'], 'string'],
            [['created_at', 'updated_at'], 'integer'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Название',
            'slug' => 'Slug',
            'description' => 'Описание',
            'url' => 'Ссылка на канал',
            'theme' => 'Тематика (текст)',
            'themeIds' => 'Тематики',
            'login' => 'Логин',
            'password' => 'Пароль',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлён',
        ];
    }

    public function getThemeRelations()
    {
        return $this->hasMany(Theme::class, ['id' => 'theme_id'])
            ->viaTable('{{%zen_account_theme}}', ['zen_account_id' => 'id']);
    }

    public function getPosts()
    {
        return $this->hasMany(ZenPost::class, ['account_id' => 'id']);
    }

    public function afterFind()
    {
        parent::afterFind();
        $this->themeIds = array_column($this->themeRelations, 'id');
    }

    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            if (trim((string) $this->slug) === '') {
                $this->slug = $this->generateSlug();
            } else {
                $this->slug = $this->normalizeSlug($this->slug);
            }
            if (!is_array($this->themeIds)) {
                $this->themeIds = [];
            }
            $this->themeIds = array_map('intval', array_filter($this->themeIds));
        }
        return true;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if (!$insert) {
            $this->db->createCommand()->delete('{{%zen_account_theme}}', ['zen_account_id' => $this->id])->execute();
        }
        foreach ($this->themeIds as $themeId) {
            $this->db->createCommand()->insert('{{%zen_account_theme}}', [
                'zen_account_id' => $this->id,
                'theme_id' => $themeId,
            ])->execute();
        }
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

    /**
     * Генерирует уникальный slug из названия.
     */
    protected function generateSlug(): string
    {
        $base = Inflector::slug(Inflector::transliterate($this->name), '-', true);
        $base = preg_replace('/[^a-z0-9\-]/', '', $base) ?: 'channel';
        $slug = $base;
        $n = 0;
        while (static::find()->andWhere(['slug' => $slug])->andWhere(['not', ['id' => $this->id ?? 0]])->exists()) {
            $slug = $base . '-' . (++$n);
        }
        return $slug;
    }

    /**
     * Нормализация slug: только латиница, цифры, дефис.
     */
    protected function normalizeSlug(string $value): string
    {
        $value = Inflector::slug(Inflector::transliterate($value), '-', true);
        return preg_replace('/[^a-z0-9\-]/', '', $value) ?: 'channel';
    }

    /**
     * Поиск по id или slug.
     */
    public static function findByIdOrSlug(string|int $idOrSlug): ?static
    {
        if (is_numeric($idOrSlug) && (string) (int) $idOrSlug === (string) $idOrSlug) {
            return static::findOne((int) $idOrSlug);
        }
        return static::findOne(['slug' => $idOrSlug]);
    }
}
