<?php

use yii\db\Migration;

/**
 * Добавляет workflow URL и ключ для аккаунта Дзен.
 */
class m260317_140000_add_workflow_fields_to_zen_account extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%zen_account}}', 'workflow_url', $this->string(2048)->null()->after('proxy_ip'));
        $this->addColumn('{{%zen_account}}', 'workflow_key', $this->string(2048)->null()->after('workflow_url'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%zen_account}}', 'workflow_key');
        $this->dropColumn('{{%zen_account}}', 'workflow_url');
    }
}