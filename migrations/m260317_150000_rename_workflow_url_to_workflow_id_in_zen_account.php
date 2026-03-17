<?php

use yii\db\Migration;

/**
 * Переименовывает workflow_url в workflow_id у аккаунтов Дзен.
 */
class m260317_150000_rename_workflow_url_to_workflow_id_in_zen_account extends Migration
{
    public function safeUp()
    {
        $this->renameColumn('{{%zen_account}}', 'workflow_url', 'workflow_id');
    }

    public function safeDown()
    {
        $this->renameColumn('{{%zen_account}}', 'workflow_id', 'workflow_url');
    }
}
