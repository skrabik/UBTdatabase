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
 * @property string|null $proxy_ip
 * @property int|null $created_at
 * @property int|null $updated_at
 *
 * @property Theme[] $themeRelations
 * @property ZenPost[] $posts
 */
class ZenAccount extends ActiveRecord
{
    public const SCENARIO_CREATE = 'create';
    public const SCENARIO_UPDATE = 'update';

    /** Массив id тематик для множественного выбора (не хранится в БД). */
    public $themeIds = [];

    public static function tableName(): string
    {
        return '{{%zen_account}}';
    }

    public function rules(): array
    {
        return [
            [['name', 'slug', 'url', 'login', 'password'], 'required', 'on' => self::SCENARIO_CREATE],
            [['name'], 'string', 'max' => 255],
            [['slug'], 'string', 'max' => 255],
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
            [['proxy_ip'], 'string', 'max' => 255],
            [['description'], 'string'],
            [['created_at', 'updated_at'], 'integer'],
        ];
    }

    public function scenarios(): array
    {
        $scenarios = parent::scenarios();
        $attributes = ['name', 'slug', 'description', 'url', 'theme', 'themeIds', 'login', 'password', 'proxy_ip'];
        $scenarios[self::SCENARIO_CREATE] = $attributes;
        $scenarios[self::SCENARIO_UPDATE] = $attributes;

        return $scenarios;
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
            'login' => 'Логин (Почта)',
            'password' => 'Пароль',
            'proxy_ip' => 'Прокси IP',
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
            if (trim((string) $this->slug) !== '') {
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
