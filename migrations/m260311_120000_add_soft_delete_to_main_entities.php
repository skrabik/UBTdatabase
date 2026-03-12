<?php

use yii\db\Migration;

/**
 * Добавляет мягкое удаление для пользователей, каналов и постов.
 */
class m260311_120000_add_soft_delete_to_main_entities extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%user}}', 'deleted_at', $this->integer()->after('updated_at'));
        $this->addColumn('{{%zen_account}}', 'deleted_at', $this->integer()->after('updated_at'));
        $this->addColumn('{{%zen_post}}', 'deleted_at', $this->integer()->after('updated_at'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%user}}', 'deleted_at');
        $this->dropColumn('{{%zen_account}}', 'deleted_at');
        $this->dropColumn('{{%zen_post}}', 'deleted_at');
    }
}
