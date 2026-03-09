<?php

use yii\db\Migration;

/**
 * Хранит состояние очереди и результат публикации поста во внешнем сервисе.
 */
class m260309_160000_add_remote_publish_fields_to_zen_post extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%zen_post}}', 'remote_job_id', $this->string(64)->null());
        $this->addColumn('{{%zen_post}}', 'remote_publish_status', $this->string(32)->notNull()->defaultValue('new'));
        $this->addColumn('{{%zen_post}}', 'remote_publish_message', $this->text()->null());
        $this->addColumn('{{%zen_post}}', 'remote_publish_logs_json', $this->text()->null());
        $this->addColumn('{{%zen_post}}', 'remote_publish_response_json', $this->text()->null());
        $this->addColumn('{{%zen_post}}', 'remote_publish_started_at', $this->integer()->null());
        $this->addColumn('{{%zen_post}}', 'remote_publish_finished_at', $this->integer()->null());

        $this->createIndex('idx-zen_post-remote_job_id', '{{%zen_post}}', 'remote_job_id');
        $this->createIndex('idx-zen_post-remote_publish_status', '{{%zen_post}}', 'remote_publish_status');
    }

    public function safeDown()
    {
        $this->dropIndex('idx-zen_post-remote_publish_status', '{{%zen_post}}');
        $this->dropIndex('idx-zen_post-remote_job_id', '{{%zen_post}}');

        $this->dropColumn('{{%zen_post}}', 'remote_publish_finished_at');
        $this->dropColumn('{{%zen_post}}', 'remote_publish_started_at');
        $this->dropColumn('{{%zen_post}}', 'remote_publish_response_json');
        $this->dropColumn('{{%zen_post}}', 'remote_publish_logs_json');
        $this->dropColumn('{{%zen_post}}', 'remote_publish_message');
        $this->dropColumn('{{%zen_post}}', 'remote_publish_status');
        $this->dropColumn('{{%zen_post}}', 'remote_job_id');
    }
}
