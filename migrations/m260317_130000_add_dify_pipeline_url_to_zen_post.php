<?php

use yii\db\Migration;

/**
 * Добавляет ссылку на Dify pipeline для поста.
 */
class m260317_130000_add_dify_pipeline_url_to_zen_post extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%zen_post}}', 'dify_pipeline_url', $this->string(2048)->null()->after('scenario'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%zen_post}}', 'dify_pipeline_url');
    }
}
