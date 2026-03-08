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

    <p><?= Html::a('Создать пользователя', ['create'], ['class' => 'btn btn-success']) ?></p>

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
                'template' => '{update} {delete}',
                'urlCreator' => function ($action, $model) {
                    return ['/admin/user/' . $action, 'id' => $model->id];
                },
                'visibleButtons' => [
                    'delete' => function ($model) {
                        return (string) $model->id !== (string) Yii::$app->user->id;
                    },
                ],
            ],
        ],
    ]) ?>
</div>
