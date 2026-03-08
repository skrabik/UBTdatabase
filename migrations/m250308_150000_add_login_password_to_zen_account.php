<?php

use yii\db\Migration;

/**
 * Добавляет поля login и password в zen_account (varchar 2048, не секретные).
 */
class m250308_150000_add_login_password_to_zen_account extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%zen_account}}', 'login', $this->string(2048)->null());
        $this->addColumn('{{%zen_account}}', 'password', $this->string(2048)->null());
    }

    public function safeDown()
    {
        $this->dropColumn('{{%zen_account}}', 'login');
        $this->dropColumn('{{%zen_account}}', 'password');
    }
}
