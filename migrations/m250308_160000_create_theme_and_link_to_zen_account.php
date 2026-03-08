<?php

use yii\db\Migration;

/**
 * Таблица тематик каналов и связь zen_account.theme_id -> theme (один ко многим).
 */
class m250308_160000_create_theme_and_link_to_zen_account extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%theme}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
        ]);

        $this->addColumn('{{%zen_account}}', 'theme_id', $this->integer()->null()->after('theme'));
        $this->createIndex('idx-zen_account-theme_id', '{{%zen_account}}', 'theme_id');
        $this->addForeignKey(
            'fk-zen_account-theme_id',
            '{{%zen_account}}',
            'theme_id',
            '{{%theme}}',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-zen_account-theme_id', '{{%zen_account}}');
        $this->dropIndex('idx-zen_account-theme_id', '{{%zen_account}}');
        $this->dropColumn('{{%zen_account}}', 'theme_id');
        $this->dropTable('{{%theme}}');
    }
}
