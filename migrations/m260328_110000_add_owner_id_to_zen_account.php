<?php

use yii\db\Migration;

/**
 * Связь один-ко-многим: User -> ZenAccount.
 */
class m260328_110000_add_owner_id_to_zen_account extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%zen_account}}', 'owner_id', $this->integer()->null()->after('url'));
        $this->createIndex('idx-zen_account-owner_id', '{{%zen_account}}', 'owner_id');
        $this->addForeignKey(
            'fk-zen_account-owner_id',
            '{{%zen_account}}',
            'owner_id',
            '{{%user}}',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-zen_account-owner_id', '{{%zen_account}}');
        $this->dropIndex('idx-zen_account-owner_id', '{{%zen_account}}');
        $this->dropColumn('{{%zen_account}}', 'owner_id');
    }
}
