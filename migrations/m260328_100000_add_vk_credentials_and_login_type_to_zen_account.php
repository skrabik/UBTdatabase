<?php

use yii\db\Migration;

/**
 * Тип входа (Яндекс / ВК / оба) и учётные данные ВКонтакте для ZenAccount.
 */
class m260328_100000_add_vk_credentials_and_login_type_to_zen_account extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%zen_account}}', 'login_type', $this->string(32)->notNull()->defaultValue('yandex'));
        $this->addColumn('{{%zen_account}}', 'vk_login', $this->string(2048)->null());
        $this->addColumn('{{%zen_account}}', 'vk_password', $this->string(2048)->null());
    }

    public function safeDown()
    {
        $this->dropColumn('{{%zen_account}}', 'vk_password');
        $this->dropColumn('{{%zen_account}}', 'vk_login');
        $this->dropColumn('{{%zen_account}}', 'login_type');
    }
}
