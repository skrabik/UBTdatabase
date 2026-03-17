<?php

use yii\db\Migration;

/**
 * Делает заголовок поста необязательным на уровне БД.
 */
class m260317_160000_make_zen_post_title_nullable extends Migration
{
    public function safeUp()
    {
        $this->alterColumn('{{%zen_post}}', 'title', $this->string(500)->null());
    }

    public function safeDown()
    {
        $this->update('{{%zen_post}}', ['title' => ''], ['title' => null]);
        $this->alterColumn('{{%zen_post}}', 'title', $this->string(500)->notNull());
    }
}
