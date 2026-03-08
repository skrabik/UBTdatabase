<?php
/** @var yii\web\View $this */
/** @var app\models\ZenAccount $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = $model->isNewRecord ? 'Новый аккаунт' : 'Редактировать аккаунт';
$this->params['breadcrumbs'][] = ['label' => 'Аккаунты Яндекс.Дзен', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="zen-account-form">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'slug')->textInput(['maxlength' => true])->hint('Оставьте пустым для автогенерации из названия. Только a-z, 0-9, дефис.') ?>
    <?= $form->field($model, 'description')->textarea(['rows' => 3]) ?>
    <?= $form->field($model, 'url')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'theme')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'login')->textInput(['maxlength' => 2048]) ?>
    <?= $form->field($model, 'password')->textInput(['maxlength' => 2048]) ?>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Отмена', ['index'], ['class' => 'btn btn-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
