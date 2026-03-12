<?php

namespace app\models;

use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * @property int $id
 * @property string $username
 * @property string $auth_key
 * @property string $password_hash
 * @property int|null $created_at
 * @property int|null $updated_at
 * @property int|null $deleted_at
 */
class User extends ActiveRecord implements IdentityInterface
{
    /** Пароль (только для формы, в БД не хранится) */
    public ?string $password = null;

    public static function tableName(): string
    {
        return '{{%user}}';
    }

    public static function find(): ActiveQuery
    {
        return parent::find()->andWhere(['deleted_at' => null]);
    }

    public function rules(): array
    {
        return [
            [['username'], 'required'],
            [['username'], 'string', 'max' => 255],
            [['username'], 'unique', 'filter' => function ($query) {
                $query->andWhere(['deleted_at' => null]);
                if (!$this->isNewRecord) {
                    $query->andWhere(['not', ['id' => $this->id]]);
                }
            }],
            [['password'], 'string', 'min' => 3],
            [['password'], 'required', 'on' => 'create'],
            [['created_at', 'updated_at', 'deleted_at'], 'integer'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'username' => 'Логин',
            'password' => 'Пароль',
            'auth_key' => 'Ключ авторизации',
            'password_hash' => 'Хеш пароля',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлён',
            'deleted_at' => 'Удалён',
        ];
    }

    public function setPassword(string $password): void
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return null;
    }

    public static function findByUsername(string $username): ?static
    {
        return static::findOne(['username' => $username]);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthKey(): string
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey): bool
    {
        return $this->auth_key === $authKey;
    }

    public function validatePassword(string $password): bool
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Роли пользователя (названия).
     * @return string[]
     */
    public function getRoleNames(): array
    {
        $roles = Yii::$app->authManager->getRolesByUser((string) $this->id);
        return array_keys($roles);
    }

    /**
     * Есть ли у пользователя роль (в т.ч. наследственные).
     */
    public function hasRole(string $roleName): bool
    {
        return Yii::$app->authManager->checkAccess($this->id, $roleName);
    }

    public function beforeSave($insert): bool
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->auth_key = Yii::$app->security->generateRandomString();
            }
            if ($this->password !== null && $this->password !== '') {
                $this->setPassword($this->password);
            }
            $this->updated_at = time();
            if ($insert) {
                $this->created_at = time();
            }
            return true;
        }
        return false;
    }

    public function delete()
    {
        if ($this->getIsNewRecord()) {
            return 0;
        }

        $time = time();
        $suffix = '__deleted_' . $this->id . '_' . $time;
        $base = mb_substr((string) $this->username, 0, max(0, 255 - strlen($suffix)));
        $updated = $this->updateAttributes([
            'username' => $base . $suffix,
            'deleted_at' => $time,
            'updated_at' => $time,
        ]);

        return $updated === false ? false : 1;
    }
}
