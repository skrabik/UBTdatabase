<?php

use yii\db\Migration;

/**
 * Таблицы для аккаунтов Яндекс.Дзен и постов.
 */
class m240228_100001_create_zen_tables extends Migration
{
    public function safeUp()
    {
        // Аккаунты Яндекс.Дзен
        // zen_account: название, описание, ссылка, тематика
        $this->createTable('{{%zen_account}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'url' => $this->string(500)->notNull(),
            'theme' => $this->string(255),
            'created_at' => $this->integer(),
            'updated_at' => $this->integer(),
        ]);

        // zen_post: статус draft | pending | posted
        $this->createTable('{{%zen_post}}', [
            'id' => $this->primaryKey(),
            'account_id' => $this->integer()->notNull(),
            'title' => $this->string(500)->notNull(),
            'content' => $this->text(),
            'status' => $this->string(32)->notNull()->defaultValue('pending'),
            'scheduled_at' => $this->integer(),
            'posted_at' => $this->integer(),
            'created_at' => $this->integer(),
            'updated_at' => $this->integer(),
        ]);

        $this->createIndex('idx-zen_post-account_id', '{{%zen_post}}', 'account_id');
        $this->createIndex('idx-zen_post-status', '{{%zen_post}}', 'status');
        $this->addForeignKey(
            'fk-zen_post-account_id',
            '{{%zen_post}}',
            'account_id',
            '{{%zen_account}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-zen_post-account_id', '{{%zen_post}}');
        $this->dropTable('{{%zen_post}}');
        $this->dropTable('{{%zen_account}}');
    }
}
