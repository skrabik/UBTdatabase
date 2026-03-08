<?php
/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\bootstrap5\Html;
use yii\grid\GridView;

$this->title = 'Аккаунты Яндекс.Дзен';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="zen-account-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Добавить аккаунт', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'id',
            'name',
            [
                'attribute' => 'description',
                'value' => fn ($m) => $m->description ? (mb_strlen($m->description) > 50 ? mb_substr($m->description, 0, 50) . '…' : $m->description) : '—',
            ],
            [
                'attribute' => 'url',
                'format' => 'url',
                'contentOptions' => ['style' => 'max-width: 200px; overflow: hidden; text-overflow: ellipsis;'],
            ],
            'theme',
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
