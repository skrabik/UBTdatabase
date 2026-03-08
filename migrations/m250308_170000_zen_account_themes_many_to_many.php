<?php

use yii\db\Migration;

/**
 * Тематики канала — множественный выбор: junction zen_account_theme (many-to-many).
 */
class m250308_170000_zen_account_themes_many_to_many extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%zen_account_theme}}', [
            'zen_account_id' => $this->integer()->notNull(),
            'theme_id' => $this->integer()->notNull(),
        ]);
        $this->addPrimaryKey('pk-zen_account_theme', '{{%zen_account_theme}}', ['zen_account_id', 'theme_id']);
        $this->addForeignKey(
            'fk-zen_account_theme-zen_account_id',
            '{{%zen_account_theme}}',
            'zen_account_id',
            '{{%zen_account}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk-zen_account_theme-theme_id',
            '{{%zen_account_theme}}',
            'theme_id',
            '{{%theme}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Перенос данных из theme_id в junction
        $rows = $this->db->createCommand('SELECT id, theme_id FROM {{%zen_account}} WHERE theme_id IS NOT NULL')->queryAll();
        foreach ($rows as $row) {
            $this->db->createCommand()->insert('{{%zen_account_theme}}', [
                'zen_account_id' => $row['id'],
                'theme_id' => $row['theme_id'],
            ])->execute();
        }

        $this->dropForeignKey('fk-zen_account-theme_id', '{{%zen_account}}');
        $this->dropIndex('idx-zen_account-theme_id', '{{%zen_account}}');
        $this->dropColumn('{{%zen_account}}', 'theme_id');
    }

    public function safeDown()
    {
        $this->addColumn('{{%zen_account}}', 'theme_id', $this->integer()->null());
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
        $rows = $this->db->createCommand('SELECT zen_account_id, MIN(theme_id) as theme_id FROM {{%zen_account_theme}} GROUP BY zen_account_id')->queryAll();
        foreach ($rows as $row) {
            $this->db->createCommand()->update('{{%zen_account}}', ['theme_id' => $row['theme_id']], ['id' => $row['zen_account_id']])->execute();
        }
        $this->dropForeignKey('fk-zen_account_theme-theme_id', '{{%zen_account_theme}}');
        $this->dropForeignKey('fk-zen_account_theme-zen_account_id', '{{%zen_account_theme}}');
        $this->dropTable('{{%zen_account_theme}}');
    }
}
