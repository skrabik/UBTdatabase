<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * @property int $id
 * @property string $username
 * @property string $auth_key
 * @property string $password_hash
 * @property int|null $created_at
 * @property int|null $updated_at
 */
class User extends ActiveRecord implements IdentityInterface
{
    public static function tableName(): string
    {
        return '{{%user}}';
    }

    public function rules(): array
    {
        return [
            [['username', 'password_hash'], 'required'],
            [['username'], 'string', 'max' => 255],
            [['username'], 'unique'],
            [['created_at', 'updated_at'], 'integer'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'username' => 'Логин',
            'auth_key' => 'Ключ авторизации',
            'password_hash' => 'Хеш пароля',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлён',
        ];
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
            $this->updated_at = time();
            if ($insert) {
                $this->created_at = time();
            }
            return true;
        }
        return false;
    }
}
