<?php
/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\bootstrap5\Html;
use yii\grid\GridView;

$this->title = 'Аккаунты Яндекс.Дзен';
$this->params['breadcrumbs'][] = $this->title;

$this->registerCss(<<<CSS
.zen-account-index .table > tbody > tr > td {
    transition: background-color 0.12s linear;
}

.zen-account-index .table > tbody > tr:hover > td {
    background-color: #e1efff;
}
CSS);
?>
<div class="zen-account-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Добавить аккаунт', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'rowOptions' => function ($model) {
            return [
                'class' => 'js-zen-account-row',
                'data-id' => $model->id,
            ];
        },
        'columns' => [
            'id',
            'name',
            'slug',
            [
                'attribute' => 'description',
                'value' => fn ($m) => $m->description ? (mb_strlen($m->description) > 50 ? mb_substr($m->description, 0, 50) . '…' : $m->description) : '—',
            ],
            [
                'attribute' => 'url',
                'format' => 'raw',
                'value' => fn ($m) => $m->url ? Html::a($m->url, $m->url, ['target' => '_blank', 'rel' => 'noopener noreferrer', 'title' => $m->url]) : '—',
                'contentOptions' => ['style' => 'max-width: 200px; overflow: hidden; text-overflow: ellipsis;'],
            ],
            [
                'attribute' => 'themeIds',
                'value' => fn ($m) => implode(', ', array_column($m->themeRelations, 'name')) ?: '—',
                'label' => 'Тематики',
            ],
            [
                'attribute' => 'login',
                'value' => fn ($m) => $m->login ? (mb_strlen($m->login) > 30 ? mb_substr($m->login, 0, 30) . '…' : $m->login) : '—',
            ],
            [
                'attribute' => 'proxy_ip',
                'value' => fn ($m) => $m->proxy_ip ?: '—',
            ],
            [
                'attribute' => 'workflow_id',
                'value' => fn ($m) => $m->workflow_id ?: '—',
                'contentOptions' => ['style' => 'max-width: 220px; overflow: hidden; text-overflow: ellipsis;'],
            ],
            [
                'attribute' => 'created_at',
                'format' => ['date', 'php:d.m.Y H:i'],
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{posts} {update} {delete}',
                'buttons' => [
                    'posts' => function ($url, $model) {
                        return Html::a('Посты', ['/admin/zen-post/index', 'account_id' => $model->id], ['class' => 'btn btn-sm btn-outline-primary']);
                    },
                    'update' => function ($url, $model) {
                        return Html::a('Редактировать', ['update', 'id' => $model->id], ['class' => 'btn btn-sm btn-outline-secondary']);
                    },
                    'delete' => function ($url, $model, $key) {
                        return Html::a('Удалить', ['delete', 'id' => $model->id], [
                            'title' => 'Удалить',
                            'data-confirm-modal' => 'Удалить этот аккаунт и все его посты?',
                            'data-confirm-title' => 'Удалить аккаунт',
                            'data-method' => 'post',
                            'data-pjax' => '0',
                        ]);
                    },
                ],
                'urlCreator' => function ($action, $model) {
                    if ($action === 'update') return ['update', 'id' => $model->id];
                    if ($action === 'delete') return ['delete', 'id' => $model->id];
                    return '#';
                },
            ],
        ],
    ]) ?>
</div>
