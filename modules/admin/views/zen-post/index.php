<?php
/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var int|null $accountId */

use app\models\ZenPostPublishAttempt;
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
                'label' => 'Удалённая публикация',
                'format' => 'raw',
                'value' => function ($m) {
                    $attempt = $m->latestPublishAttempt;
                    $status = $attempt?->status ?? ZenPostPublishAttempt::STATUS_NEW;
                    $labels = ZenPostPublishAttempt::statusLabels();
                    $label = $labels[$status] ?? $status;
                    $classMap = [
                        ZenPostPublishAttempt::STATUS_NEW => 'secondary',
                        ZenPostPublishAttempt::STATUS_QUEUED => 'warning',
                        ZenPostPublishAttempt::STATUS_RUNNING => 'info',
                        ZenPostPublishAttempt::STATUS_SUCCESS => 'success',
                        ZenPostPublishAttempt::STATUS_ERROR => 'danger',
                    ];
                    $class = $classMap[$status] ?? 'secondary';
                    $badge = Html::tag('span', $label, ['class' => "badge bg-{$class}"]);
                    if ($attempt === null) {
                        return $badge;
                    }
                    $link = Html::a('Результат', ['/admin/zen-post/send-log', 'account_id' => $m->account_id, 'id' => $m->id], ['class' => 'ms-2']);
                    return $badge . $link;
                },
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{update}',
                'buttons' => [
                    'send' => function ($url, $model) {
                        $label = $model->latestPublishAttempt === null ? 'Отправить' : 'Отправить снова';
                        return Html::a($label, ['/admin/zen-post/send', 'account_id' => $model->account_id, 'id' => $model->id], [
                            'class' => 'btn btn-sm btn-primary',
                            'data-method' => 'post',
                        ]);
                    },
                ],
                'urlCreator' => function ($action, $model) {
                    if ($action === 'update') {
                        return ['/admin/zen-post/' . $action, 'account_id' => $model->account_id, 'id' => $model->id];
                    }
                    return '#';
                },
            ],
        ],
    ]) ?>
</div>
