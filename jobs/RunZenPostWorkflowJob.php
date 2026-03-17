<?php

namespace app\jobs;

use app\models\ZenPost;
use app\services\DifyWorkflowApiService;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class RunZenPostWorkflowJob extends BaseObject implements JobInterface
{
    public int $postId;

    public function execute($queue): void
    {
        $post = ZenPost::find()
            ->with('account')
            ->andWhere(['id' => $this->postId])
            ->one();

        if ($post === null) {
            Yii::warning([
                'msg' => 'Queue job skipped: post not found for workflow run',
                'post_id' => $this->postId,
            ], __METHOD__);
            return;
        }

        $account = $post->account;
        if ($account === null) {
            Yii::warning([
                'msg' => 'Queue job skipped: account not found for workflow run',
                'post_id' => $post->id,
            ], __METHOD__);
            return;
        }

        if (
            trim((string) $account->workflow_id) === ''
            || trim((string) $account->workflow_key) === ''
            || trim((string) $post->scenario) === ''
        ) {
            Yii::warning([
                'msg' => 'Queue job skipped: workflow settings are incomplete',
                'post_id' => $post->id,
                'account_id' => $account->id,
                'has_workflow_id' => trim((string) $account->workflow_id) !== '',
                'has_workflow_key' => trim((string) $account->workflow_key) !== '',
                'has_scenario' => trim((string) $post->scenario) !== '',
            ], __METHOD__);
            return;
        }

        try {
            $service = DifyWorkflowApiService::forAccount($account);
            $httpCode = $service->triggerSpecificWorkflow(
                (string) $account->workflow_id,
                [
                    'scenario' => (string) $post->scenario,
                    'post_id' => (int) $post->id,
                    'channel_id' => (int) $account->id,
                ],
                'zen-post-' . $post->id,
                [],
                'zen-post-' . $post->id . '-' . time()
            );

            Yii::info([
                'msg' => 'Workflow queued job finished successfully',
                'post_id' => $post->id,
                'account_id' => $account->id,
                'workflow_id' => $account->workflow_id,
                'http_code' => $httpCode,
            ], __METHOD__);
        } catch (\Throwable $e) {
            Yii::warning([
                'msg' => 'Workflow queued job failed',
                'post_id' => $post->id,
                'account_id' => $account->id,
                'workflow_id' => $account->workflow_id,
                'error' => $e->getMessage(),
            ], __METHOD__);
        }
    }
}
