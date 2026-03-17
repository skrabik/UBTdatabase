<?php

use yii\db\Migration;

/**
 * Добавляет сценарий поста как основу для генерации текста.
 */
class m260317_120000_add_scenario_to_zen_post extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%zen_post}}', 'scenario', $this->text()->after('title'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%zen_post}}', 'scenario');
    }
}
