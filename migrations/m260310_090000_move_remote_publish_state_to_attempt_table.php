<?php

use yii\db\Migration;

/**
 * Выносит состояния и результаты удалённой публикации из zen_post в zen_post_publish_attempt.
 */
class m260310_090000_move_remote_publish_state_to_attempt_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%zen_post_publish_attempt}}', [
            'id' => $this->primaryKey(),
            'zen_post_id' => $this->integer()->notNull(),
            'job_id' => $this->string(64)->null(),
            'status' => $this->string(32)->notNull()->defaultValue('new'),
            'message' => $this->text()->null(),
            'logs_json' => $this->text()->null(),
            'response_json' => $this->text()->null(),
            'started_at' => $this->integer()->null(),
            'finished_at' => $this->integer()->null(),
            'created_at' => $this->integer()->null(),
            'updated_at' => $this->integer()->null(),
        ]);

        $this->createIndex('idx-zen_post_publish_attempt-zen_post_id', '{{%zen_post_publish_attempt}}', 'zen_post_id');
        $this->createIndex('idx-zen_post_publish_attempt-job_id', '{{%zen_post_publish_attempt}}', 'job_id');
        $this->createIndex('idx-zen_post_publish_attempt-status', '{{%zen_post_publish_attempt}}', 'status');
        $this->addForeignKey(
            'fk-zen_post_publish_attempt-zen_post_id',
            '{{%zen_post_publish_attempt}}',
            'zen_post_id',
            '{{%zen_post}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $rows = $this->db->createCommand('
            SELECT
                id,
                remote_job_id,
                remote_publish_status,
                remote_publish_message,
                remote_publish_logs_json,
                remote_publish_response_json,
                remote_publish_started_at,
                remote_publish_finished_at,
                created_at,
                updated_at
            FROM {{%zen_post}}
        ')->queryAll();

        foreach ($rows as $row) {
            $hasAttemptData =
                !empty($row['remote_job_id']) ||
                ($row['remote_publish_status'] ?? 'new') !== 'new' ||
                !empty($row['remote_publish_message']) ||
                !empty($row['remote_publish_logs_json']) ||
                !empty($row['remote_publish_response_json']) ||
                !empty($row['remote_publish_started_at']) ||
                !empty($row['remote_publish_finished_at']);

            if (!$hasAttemptData) {
                continue;
            }

            $createdAt = $row['remote_publish_started_at']
                ?: $row['remote_publish_finished_at']
                ?: $row['updated_at']
                ?: $row['created_at']
                ?: time();

            $updatedAt = $row['remote_publish_finished_at']
                ?: $row['updated_at']
                ?: $createdAt;

            $this->insert('{{%zen_post_publish_attempt}}', [
                'zen_post_id' => $row['id'],
                'job_id' => $row['remote_job_id'],
                'status' => $row['remote_publish_status'] ?: 'new',
                'message' => $row['remote_publish_message'],
                'logs_json' => $row['remote_publish_logs_json'],
                'response_json' => $row['remote_publish_response_json'],
                'started_at' => $row['remote_publish_started_at'],
                'finished_at' => $row['remote_publish_finished_at'],
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);
        }

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

    public function safeDown()
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

        $rows = $this->db->createCommand('
            SELECT a.*
            FROM {{%zen_post_publish_attempt}} a
            INNER JOIN (
                SELECT zen_post_id, MAX(id) AS max_id
                FROM {{%zen_post_publish_attempt}}
                GROUP BY zen_post_id
            ) latest ON latest.max_id = a.id
        ')->queryAll();

        foreach ($rows as $row) {
            $this->update('{{%zen_post}}', [
                'remote_job_id' => $row['job_id'],
                'remote_publish_status' => $row['status'] ?: 'new',
                'remote_publish_message' => $row['message'],
                'remote_publish_logs_json' => $row['logs_json'],
                'remote_publish_response_json' => $row['response_json'],
                'remote_publish_started_at' => $row['started_at'],
                'remote_publish_finished_at' => $row['finished_at'],
            ], ['id' => $row['zen_post_id']]);
        }

        $this->dropForeignKey('fk-zen_post_publish_attempt-zen_post_id', '{{%zen_post_publish_attempt}}');
        $this->dropTable('{{%zen_post_publish_attempt}}');
    }
}
