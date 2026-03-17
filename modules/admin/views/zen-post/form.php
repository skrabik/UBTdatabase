<?php
/** @var yii\web\View $this */
/** @var app\models\ZenPost $model */
/** @var int $accountId */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$accountId = $accountId ?? $model->account_id;
$this->title = $model->isNewRecord ? 'Новый пост' : 'Редактировать пост';
$this->params['breadcrumbs'][] = ['label' => 'Аккаунты Яндекс.Дзен', 'url' => ['/admin/zen-account/index']];
$this->params['breadcrumbs'][] = ['label' => 'Посты', 'url' => ['/admin/zen-post/index', 'account_id' => $accountId]];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="zen-post-form">
    <h1><?= Html::encode($this->title) ?></h1>
    <p class="text-muted">Тема (сценарий) задаёт основу и смысл будущего поста, а в тексте можно сохранить уже готовый вариант публикации.</p>

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'scenario')->textarea(['rows' => 4]) ?>
    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'content')->textarea(['rows' => 6]) ?>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Отмена', ['/admin/zen-post/index', 'account_id' => $accountId], ['class' => 'btn btn-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>