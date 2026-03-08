<?php

use yii\db\Migration;

/**
 * Добавляет поле proxy_ip в zen_account (IP прокси, опционально).
 */
class m250308_180000_add_proxy_ip_to_zen_account extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%zen_account}}', 'proxy_ip', $this->string(255)->null());
    }

    public function safeDown()
    {
        $this->dropColumn('{{%zen_account}}', 'proxy_ip');
    }
}
