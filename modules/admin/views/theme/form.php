<?php
/** @var yii\web\View $this */
/** @var app\models\Theme $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = $model->isNewRecord ? 'Новая тематика' : 'Редактировать тематику';
$this->params['breadcrumbs'][] = ['label' => 'Тематики каналов', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="theme-form">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Отмена', ['index'], ['class' => 'btn btn-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
