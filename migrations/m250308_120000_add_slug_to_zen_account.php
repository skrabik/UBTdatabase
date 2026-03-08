<?php

use yii\db\Migration;

/**
 * Добавляет поле slug в zen_account (уникальный человекочитаемый идентификатор).
 */
class m250308_120000_add_slug_to_zen_account extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%zen_account}}', 'slug', $this->string(255)->after('url'));
        $rows = $this->db->createCommand('SELECT id, name FROM {{%zen_account}}')->queryAll();
        foreach ($rows as $row) {
            $slug = $this->generateSlug($row['name'], (int) $row['id']);
            $this->db->createCommand()->update('{{%zen_account}}', ['slug' => $slug], ['id' => $row['id']])->execute();
        }
        $this->alterColumn('{{%zen_account}}', 'slug', $this->string(255)->notNull());
        $this->createIndex('idx-zen_account-slug', '{{%zen_account}}', 'slug', true);
    }

    private function generateSlug(string $name, int $id): string
    {
        $slug = preg_replace('/[^a-z0-9\-]/', '-', strtolower(trim($name)));
        $slug = preg_replace('/-+/', '-', $slug) ?: 'channel';
        return $slug . '-' . $id;
    }

    public function safeDown()
    {
        $this->dropIndex('idx-zen_account-slug', '{{%zen_account}}');
        $this->dropColumn('{{%zen_account}}', 'slug');
    }
}
