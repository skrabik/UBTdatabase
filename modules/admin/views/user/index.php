<?php
/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\bootstrap5\Html;
use yii\grid\GridView;

$this->title = 'Пользователи и роли';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="admin-user-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'id',
            'username',
            [
                'attribute' => 'Роли',
                'value' => function ($model) {
                    return implode(', ', $model->getRoleNames()) ?: '—';
                },
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{update}',
                'urlCreator' => function ($action, $model) {
                    return ['/admin/user/update', 'id' => $model->id];
                },
            ],
        ],
    ]) ?>
</div>
