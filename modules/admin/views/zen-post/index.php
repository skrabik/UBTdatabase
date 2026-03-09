<?php
/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var int|null $accountId */

use app\models\ZenPost;
use yii\bootstrap5\Html;
use yii\grid\GridView;

$this->title = $accountId ? 'Посты аккаунта' : 'Все посты';
$this->params['breadcrumbs'][] = ['label' => 'Аккаунты Яндекс.Дзен', 'url' => ['/admin/zen-account/index']];
if ($accountId) {
    $account = \app\models\ZenAccount::findOne($accountId);
    $this->params['breadcrumbs'][] = $account ? $account->name : (string) $accountId;
}
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="zen-post-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Добавить пост', ['/admin/zen-post/create', 'account_id' => $accountId], ['class' => 'btn btn-success']) ?>
        <?= Html::a('К аккаунтам', ['/admin/zen-account/index'], ['class' => 'btn btn-outline-secondary']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'id',
            [
                'attribute' => 'title',
                'contentOptions' => ['style' => 'max-width: 250px;'],
            ],
            [
                'attribute' => 'status',
                'format' => 'raw',
                'value' => function ($m) {
                    $labels = ZenPost::statusLabels();
                    $label = $labels[$m->status] ?? $m->status;
                    $class = $m->status === ZenPost::STATUS_POSTED ? 'success' : ($m->status === ZenPost::STATUS_PENDING ? 'warning' : 'secondary');
                    return Html::tag('span', $label, ['class' => "badge bg-{$class}"]);
                },
            ],
            [
                'attribute' => 'posted_at',
                'format' => ['date', 'php:d.m.Y H:i'],
            ],
            [
                'label' => 'Удалённая публикация',
                'format' => 'raw',
                'value' => function ($m) {
                    $labels = \app\models\ZenPost::remotePublishStatusLabels();
                    $label = $labels[$m->remote_publish_status] ?? $m->remote_publish_status;
                    $classMap = [
                        \app\models\ZenPost::REMOTE_PUBLISH_NEW => 'secondary',
                        \app\models\ZenPost::REMOTE_PUBLISH_QUEUED => 'warning',
                        \app\models\ZenPost::REMOTE_PUBLISH_RUNNING => 'info',
                        \app\models\ZenPost::REMOTE_PUBLISH_SUCCESS => 'success',
                        \app\models\ZenPost::REMOTE_PUBLISH_ERROR => 'danger',
                    ];
                    $class = $classMap[$m->remote_publish_status] ?? 'secondary';
                    $badge = Html::tag('span', $label, ['class' => "badge bg-{$class}"]);
                    $link = Html::a('Результат', ['/admin/zen-post/send-log', 'account_id' => $m->account_id, 'id' => $m->id], ['class' => 'ms-2']);
                    return $badge . $link;
                },
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{update} {set-posted} {set-pending} {delete}',
                'buttons' => [
                    'set-posted' => function ($url, $model) {
                        if ($model->status === ZenPost::STATUS_POSTED) return '';
                        return Html::a('Запощено', ['/admin/zen-post/set-status', 'account_id' => $model->account_id, 'id' => $model->id, 'status' => ZenPost::STATUS_POSTED], ['class' => 'btn btn-sm btn-success', 'data-method' => 'post']);
                    },
                    'set-pending' => function ($url, $model) {
                        if ($model->status === ZenPost::STATUS_PENDING) return '';
                        return Html::a('В очередь', ['/admin/zen-post/set-status', 'account_id' => $model->account_id, 'id' => $model->id, 'status' => ZenPost::STATUS_PENDING], ['class' => 'btn btn-sm btn-warning', 'data-method' => 'post']);
                    },
                    'delete' => function ($url, $model) {
                        return Html::a('Удалить', ['/admin/zen-post/delete', 'account_id' => $model->account_id, 'id' => $model->id], [
                            'title' => 'Удалить',
                            'data-confirm-modal' => 'Удалить этот пост?',
                            'data-confirm-title' => 'Удалить пост',
                            'data-method' => 'post',
                            'data-pjax' => '0',
                        ]);
                    },
                ],
                'urlCreator' => function ($action, $model) {
                    if (in_array($action, ['update', 'delete'], true)) {
                        return ['/admin/zen-post/' . $action, 'account_id' => $model->account_id, 'id' => $model->id];
                    }
                    return '#';
                },
            ],
        ],
    ]) ?>
</div>
