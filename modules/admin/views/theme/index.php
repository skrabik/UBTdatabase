<?php
/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\bootstrap5\Html;
use yii\grid\GridView;

$this->title = 'Тематики каналов';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="theme-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Добавить тематику', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'id',
            'name',
            [
                'attribute' => 'Каналов',
                'value' => fn ($model) => $model->getZenAccounts()->count(),
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{update} {delete}',
                'buttons' => [
                    'update' => function ($url, $model) {
                        return Html::a('Редактировать', ['update', 'id' => $model->id], ['class' => 'btn btn-sm btn-outline-secondary']);
                    },
                    'delete' => function ($url, $model, $key) {
                        return Html::a('Удалить', ['delete', 'id' => $model->id], [
                            'title' => 'Удалить',
                            'data-confirm-modal' => 'Удалить эту тематику? Связанные каналы останутся без тематики.',
                            'data-confirm-title' => 'Удалить тематику',
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
