<?php
/** @var yii\web\View $this */
/** @var app\models\ZenPost $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = $model->isNewRecord ? 'Новый пост' : 'Редактировать пост';
$this->params['breadcrumbs'][] = ['label' => 'Аккаунты Яндекс.Дзен', 'url' => ['/admin/zen-account/index']];
$this->params['breadcrumbs'][] = ['label' => 'Посты', 'url' => ['index', 'account_id' => $model->account_id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="zen-post-form">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'account_id')->dropDownList(
        \app\models\ZenAccount::find()->select(['name', 'id'])->indexBy('id')->column(),
        ['prompt' => 'Выберите аккаунт']
    ) ?>
    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'content')->textarea(['rows' => 6]) ?>
    <?= $form->field($model, 'status')->dropDownList(\app\models\ZenPost::statusLabels()) ?>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Отмена', ['index', 'account_id' => $model->account_id], ['class' => 'btn btn-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
