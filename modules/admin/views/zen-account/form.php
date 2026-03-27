<?php
/** @var yii\web\View $this */
/** @var app\models\ZenAccount $model */

use app\models\Theme;
use app\models\ZenAccount;
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
    <?= $form->field($model, 'slug')->textInput(['maxlength' => true])->hint('Только a-z, 0-9 и дефис.') ?>
    <?= $form->field($model, 'description')->textarea(['rows' => 3]) ?>
    <?= $form->field($model, 'url')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'themeIds')->checkboxList(
        Theme::find()->select(['name', 'id'])->orderBy(['name' => SORT_ASC])->indexBy('id')->column(),
        ['itemOptions' => ['class' => 'form-check-input'], 'item' => function ($index, $label, $name, $checked, $value) {
            return '<div class="form-check">' . Html::checkbox($name, $checked, ['value' => $value, 'class' => 'form-check-input', 'id' => 'theme-' . $value]) . ' <label class="form-check-label" for="theme-' . $value . '">' . Html::encode($label) . '</label></div>';
        }]
    )->label('Тематики') ?>
    <?= $form->field($model, 'login_type')->dropDownList(ZenAccount::loginTypeLabels(), [
        'class' => 'form-select',
    ])->hint('Логин и пароль Яндекс и ВКонтакте необязательны; тип входа задаёт смысл полей для автоматизации.') ?>
    <?= $form->field($model, 'login')->textInput(['maxlength' => 2048]) ?>
    <?= $form->field($model, 'password')->textInput(['maxlength' => 2048]) ?>
    <?= $form->field($model, 'vk_login')->textInput(['maxlength' => 2048]) ?>
    <?= $form->field($model, 'vk_password')->textInput(['maxlength' => 2048]) ?>
    <?= $form->field($model, 'proxy_ip')->textInput(['maxlength' => 255])->hint('Например: 1.2.3.4 или 1.2.3.4:8080') ?>
    <?= $form->field($model, 'workflow_id')->textInput(['maxlength' => 2048])->hint('UUID опубликованной версии workflow из Dify.') ?>
    <?php if (!$model->isNewRecord): ?>
        <?= $form->field($model, 'workflow_key')->passwordInput(['maxlength' => 2048])->hint('Отдельный API ключ для workflow этого аккаунта.') ?>
    <?php endif; ?>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Отмена', ['index'], ['class' => 'btn btn-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
