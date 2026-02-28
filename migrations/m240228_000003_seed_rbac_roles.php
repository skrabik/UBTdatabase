<?php

use yii\db\Migration;
use yii\rbac\DbManager;

/**
 * Создание ролей и назначение admin пользователю id=1.
 */
class m240228_000003_seed_rbac_roles extends Migration
{
    public function safeUp()
    {
        /** @var DbManager $auth */
        $auth = Yii::$app->authManager;

        // Роли
        $admin = $auth->createRole('admin');
        $admin->description = 'Администратор';
        $auth->add($admin);

        $manager = $auth->createRole('manager');
        $manager->description = 'Менеджер';
        $auth->add($manager);

        $user = $auth->createRole('user');
        $user->description = 'Пользователь';
        $auth->add($user);

        // Иерархия: admin наследует manager, manager наследует user
        $auth->addChild($admin, $manager);
        $auth->addChild($manager, $user);

        // Назначаем роль admin пользователю с id=1 (создан в первой миграции)
        $auth->assign($admin, '1');
    }

    public function safeDown()
    {
        $auth = Yii::$app->authManager;
        $auth->removeAll();
    }
}
